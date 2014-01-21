<?php
namespace Rackem;

use \Rackem\Server\Connection;

class Server
{
  public $app, $reload = true;
  private $host, $port, $running, $in, $out;

  public function __construct($host = '0.0.0.0', $port = 9393, $app = 'config.php')
  {
    declare(ticks=1);
    $this->host = $host;
    $this->port = $port;
    $this->app = $app;
    $this->in = array();
    $this->out = array();

    \Rackem::$handler = new \Rackem\Handler\Rackem();
  }

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
        if(strlen($conn->buffer) || $conn->is_response_complete()) $write[] = $conn->socket;
        else $read[] = $conn->stream;
      }
    }
    $read[] = $this->master;

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
      if(isset($this->out[(int)$stream])) $this->out[(int)$stream]->read();
      else $this->read_request($stream);
    }

    foreach($write as $client) $this->write($client);

    return $this->running;
  }

  public function read_request($socket)
  {
    $conn = $this->in[(int)$socket];
    $data = @fread($socket, 30000);

    if($data === false || $data == '') return $this->close_connection($conn);

    $conn->data($data);
    if($conn->is_request_complete())
    {
      $stream = $conn->process($this->app);
      $this->out[(int)$stream] = $conn;
    }
  }

  public function write($socket)
  {
    $conn = $this->in[(int)$socket];

    $bytes = @fwrite($socket, $conn->buffer);
    if($bytes === false) return $this->close_connection($conn);

    $conn->bytes_written += $bytes;
    $conn->buffer = substr($conn->buffer, $bytes);

    if($conn->is_response_complete()) $this->complete_response($conn);
  }

  public function complete_response($conn)
  {
    fwrite(STDERR, $this->log_request($conn));
    if($conn->get_header('Connection') === 'close' || $conn->version !== 'HTTP/1.1')
    {
      $this->close_connection($conn);
    }else
    {
      $conn->cleanup();
      $this->close_response($conn);
      $this->in[(int)$conn->socket] = new Connection($conn->socket);
    }
  }

  public function close_connection($conn)
  {
    $conn->cleanup();
    @fclose($conn->socket);
    unset($this->in[(int)$conn->socket]);
    $conn->socket = null;

    $this->close_response($conn);
  }

  public function close_response($conn)
  {
    if(!$conn->stream) return;
    @fclose($conn->stream);
    unset($this->out[(int)$conn->stream]);
    $conn->stream = null;
    if($conn->proc)
    {
      proc_close($conn->proc);
      $conn->proc = null;
    }
  }

  public function stop()
  {
    $this->running = false;
    fclose($this->master);
    echo ">> Stopping...\n";
    exit(0);
  }

  public function help()
  {
    echo "Usage:\n";
    echo "rackem [options] [config]\n\n";
    echo "Options:\n";
    echo "        --basic        run a basic HTTP server\n";
    echo "        --host HOST    listen on HOST (default: 127.0.0.1)\n";
    echo "        --port PORT    use PORT (default: 9393)\n";
    echo "    -h                 show this message\n";
    echo "    -v, --version      show version.\n";
  }

  public function version()
  {
    echo explode(".", \Rackem::version()) ."\n";
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

  protected function log_request($conn)
  {
    $date = @date("d/M/Y H:i:s");
    $time = sprintf('%.4f', microtime(true) - $conn->start_time);

    return "{$conn->client} - - [{$date}] \"{$conn->request}\" {$conn->status} - {$time}\n";
  }

}
