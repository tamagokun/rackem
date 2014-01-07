<?php
namespace Rackem;

use \Rackem\Server\Connection;

class Server
{
  public $app, $reload = true;
  private $host, $port, $running, $in, $out;

  public function __construct($host = '0.0.0.0', $port = 9393, $app)
  {
    declare(ticks=1);
    $this->host = $host;
    $this->port = $port;
    $this->app = $app;
    $this->in = array();
    $this->out = array();

    \Rackem::$handler = new \Rackem\Handler\Rackem();
  }

	/* public function process($buffer) */
	/* { */
	/* 	$start = microtime(true); */
	/* 	ob_start(); */
	/* 	$req = $this->parse_request($buffer); */
	/* 	$env = $this->env($req); */

	/* 	$app = $this->app(); */
	/* 	$res = new Response($app->call($env)); */
	/* 	$output = ob_get_clean(); */
	/* 	fwrite($env['rack.errors'], $output); */
	/* 	// fwrite($env['rack.errors'], $this->log_request($req, $res)); */
	/* 	if($env['rack.logger']) */
	/* 	{ */
	/* 		$time = microtime(true) - $start; */
	/* 		fwrite($env['rack.logger']->stream, $this->log_request($req, $res, $client, $time)); */
	/* 		$env['rack.logger']->close(); */
	/* 	} */
	/* 	fclose($env['rack.input']); */
	/* 	if(is_resource($env['rack.errors'])) fclose($env['rack.errors']); */
	/* 	return $this->write_response($req, $res); */
	/* } */

  public function start()
  {
    $this->init();
    $this->running = true;

    while($this->step())
    {

    }
  }

  public function step()
  {
    $read = array();
    $write = array();
    $except = null;

    foreach($this->in as $id => $conn)
    {
      if(!$conn->is_request_complete())
      {
        $read[] = $conn->socket;
      }else
      {
        if(strlen($conn->buffer)) $write[] = $conn->socket;
        if(!$conn->is_response_complete()) $read[] = $conn->stream;
      }
    }
    $read[] = $this->master;

    // @stream_select($read, $write, $except, 0, 200000)
    if(@stream_select($read, $write, $except, null) < 1) return $this->running;

    if(in_array($this->master, $read))
    {
      $client = stream_socket_accept($this->master);
      $this->in[(int)$client] = new Connection($client);

      $key = array_search($this->master, $read);
      unset($read[$key]);
    }

    foreach($read as $stream)
    {
      if(isset($this->out[(int)$stream])) $this->out[(int)$stream]->read($stream);
      else $this->read_request($stream);
    }

    foreach($write as $client) $this->write($client);

    /*   if(is_resource($client)) */
    /*   { */
    /*     stream_socket_shutdown($client, STREAM_SHUT_RDWR); */
    /*     fclose($client); */
    /*   } */

    return $this->running;
  }

  public function read_request($socket)
  {
    $conn = $this->in[(int)$socket];
    $data = @fread($socket, 30000);

    if($data === false || $data == '') return $this->close_in($conn);

    $conn->data($data);
    if($conn->is_request_complete())
    {
      // Log request
      $stream = $conn->process($this->app);
      $this->out[(int)$stream] = $conn;
    }
  }

  public function write($socket)
  {
    $conn = $this->in[(int)$socket];

    $bytes = @fwrite($socket, $conn->buffer);
    if($bytes === false) return $this->close_in($conn);

    $conn->bytes_written += $bytes;
    $conn->buffer = substr($conn->buffer, $bytes);

    if($conn->is_response_complete())
    {
      // request_done($conn)
      // or log here?
      if($conn->header['Connection'] === 'close' || $conn->version !== 'HTTP/1.1')
      {
        $this->close_in($conn);
      }else
      {
        $conn->cleanup();
        $this->close_out($conn);
        $this->in[(int)$socket] = new Connection($socket);
      }
    }
  }

  public function close_in($connection)
  {
    $connection->cleanup();
    @fclose($connection->socket);
    unset($this->in[(int)$connection->socket]);
    $connection->socket = null;
  }

  public function close_out($connection)
  {
    if(!$connection->stream) return;
    @fclose($connection->stream);
    unset($this->out[(int)$connection->stream]);
    $connection->stream = null;
    if($connection->proc)
    {
      proc_close($connection->proc);
      $connection->proc = null;
    }
  }

  public function stop()
  {
    $this->running = false;
    fclose($this->master);
    echo ">> Stopping...\n";
    exit(0);
  }

/* private */

  protected function init()
  {
    set_time_limit(0);
    $this->master = @stream_socket_server("tcp://{$this->host}:{$this->port}", $errno, $errstr);
    if($this->master === false)
    {
      echo ">> Failed to bind socket.\n", $errno, " - ", $errstr, "\n";
      exit(2);
    }
    stream_set_blocking($this->master, 0);

    echo "== Rack'em on http://{$this->host}:{$this->port}\n";
    echo ">> Rack'em web server\n";
    echo ">> Listening on {$this->host}:{$this->port}, CTRL+C to stop\n";
    if(function_exists('pcntl_signal'))
    {
      pcntl_signal(SIGINT, array($this, "stop"));
      pcntl_signal(SIGTERM, array($this, "stop"));
    }
  }

	protected function log_request($req, $res, $client, $time)
	{
		$date = @date("D M d H:i:s Y");
		$time = sprintf('%.4f', $time);
		$request = $req['method'].' '.$req['request_url']['path'].' '.$req['protocol'].'/'.$req['version'];

		return "{$client} - - [{$date}] \"{$request}\" {$res->status} - {$time}\n";
	}

}
