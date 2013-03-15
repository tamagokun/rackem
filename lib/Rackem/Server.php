<?php
namespace Rackem;

class Server
{
	public $reload = true;
	private $host, $port, $app, $listening;

	public function __construct($host = '0.0.0.0', $port = 9393)
	{
		declare(ticks=1);
		$this->host = $host;
		$this->port = $port;
	}

	public function start($app)
	{
		$this->init();
		while($this->listening)
		{
			$client = @socket_accept($this->master);
			if($client === false)
			{
				usleep(100);
				continue;
			}
			if($client < 0)
			{
				echo ">> Error: ", socket_strerror($client), "\n";
				exit();
			}
			
			$pid = pcntl_fork();
			if($pid == -1)
			{
				echo ">> Fork failure.\n";
				exit();
			}else if($pid > 0)
			{
				socket_close($client);
			}else
			{
				$this->listening = false;
				socket_close($this->master);
				$buffer = '';
				//while(!preg_match('/\r?\n\r?\n/',$buffer, $s))
				$buffer .= socket_read($client, 1024);

				if(!strlen($buffer))
				{
					socket_close($client);
					exit();
				}

				if(preg_match('/Content-Length: (\d+)/',$buffer,$m))
				{
					$offset = strpos($buffer, "\r\n\r\n");
					if($offset === false) $offset = strpos($buffer, "\n\n");
					if($offset === false) $offset = strpos($buffer, "\r\r");
					if($offset === false) $offset = strpos($buffer, "\r\n");
					$length = $m[1] - $offset + 2;
					$body = '';
					while(strlen($body) < $length) $body .= socket_read($client, 1024);
					$buffer = $buffer . $body;
				}

				$res = $this->reload? $this->process_from_cli($app, $buffer) : $this->process($app, $buffer);
				socket_write($client, $res);
				socket_close($client);
				exit();
			}
		}
	}

	public function process($app, $buffer)
	{
		ob_start();
		$req = $this->parse_request($buffer);
		$env = $this->env($req);

		$this->app = include($app);
		$res = new Response($this->app->call($env));
		$output = ob_get_clean();
		fwrite($env['rack.errors'], $output);
		fclose($env['rack.input']);
		fclose($env['rack.errors']);
		if($env['rack.logger']) $env['rack.logger']->close();
		return $this->write_response($req, $res);
	}

	public function stop()
	{
		$this->listening = false;
		$this->child_handler();
		@socket_close($this->master);
		echo ">> Stopping...\n";
		exit(0);
	}

/* private */

	protected function init()
	{
		if(($this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false)
		{
			echo ">> Failed to create socket.\n", socket_strerror($this->master), "\n";
			exit(1);
		}
		if(@socket_bind($this->master, $this->host, $this->port) === false)
		{
			echo ">> Failed to bind socket.\n", socket_strerror(socket_last_error()), "\n";
			exit(2);
		}
		if(@socket_listen($this->master, 0) === false)
		{
			echo ">> Failed to start server.\n", socket_strerror(socket_last_error()), "\n";
			exit(3);
		}
		socket_set_nonblock($this->master);

		echo "== Rackem on http://{$this->host}:{$this->port}\n";
		echo ">> Rackem web server\n";
		echo ">> Listening on {$this->host}:{$this->port}, CTRL+C to stop\n";
		pcntl_signal(SIGINT, array($this, "stop"));
		pcntl_signal(SIGTERM, array($this, "stop"));
		pcntl_signal(SIGCHLD, array($this, "child_handler"));
		$this->listening = true;
	}

	protected function child_handler()
	{
		pcntl_waitpid(-1, $status);
	}

	protected function env($req)
	{
		$env = array(
			'REQUEST_METHOD' => $req['method'],
			'SCRIPT_NAME' => "",
			'PATH_INFO' => $req['request_url']['path'],
			'SERVER_NAME' => $this->host,
			'SERVER_PORT' => $this->port,
			'SERVER_PROTOCOL' => $req['protocol'],
			'QUERY_STRING' => $req['request_url']['query'],
			'rack.version' => \Rackem::version(),
			'rack.url_scheme' => $req['request_url']['scheme'],
			'rack.input' => fopen('php://temp', 'r+b'),
			'rack.errors' => fopen('php://stderr', 'wb'),
			'rack.multithread' => false,
			'rack.multiprocess' => false,
			'rack.run_once' => false,
			'rack.session' => array(),
			'rack.logger' => ""
		);
		if(isset($req['headers']['Content-Type']))
		{
			$env['CONTENT_TYPE'] = $req['headers']['Content-Type'];
			unset($req['headers']['Content-Type']);
		}
		if(isset($req['headers']['Content-Length']))
		{
			$env['CONTENT_LENGTH'] = $req['headers']['Content-Length'];
			unset($req['headers']['Content-Length']);
		}
		fwrite($env['rack.input'], $req['body']);
		rewind($env['rack.input']);
		foreach($req['headers'] as $k=>$v) $env[strtoupper(str_replace("-","_","http_$k"))] = $v;
		return new \ArrayObject($env);
	}

	protected function get_url_parts($request, $parts)
	{
		$url = array(
			'path'   => $request,
			'scheme' => 'http',
			'host'   => '',
			'port'   => '',
			'query'  => ''
		);

		if(isset($parts['headers']['Host']))
			$url['host'] = $parts['headers']['Host'];
		elseif(isset($parts['headers']['host']))
			$url['host'] = $parts['headers']['host'];
		
		if(strpos($url['host'], ':') !== false)
		{
			$host = explode(':', $url['host']);
			$url['host'] = trim($host[0]);
			$url['port'] = (int) trim($host[1]);
			if($url['port'] == 443) $url['scheme'] = 'https';
		}

		$path  = $url['path'];
		$query = strpos($path, '?');
		if($query)
		{
			$url['query'] = substr($path, $query + 1);
			$url['path'] = substr($path, 0, $query);
		}

		return $url;
	}

	protected function parse_parts($req)
	{
		$start = null;
		$headers = array();
		$body = '';

		$lines = preg_split('/(\\r?\\n)/',$req, -1, PREG_SPLIT_DELIM_CAPTURE);
		for($i=0, $total = count($lines); $i < $total; $i += 2)
		{
			$line = $lines[$i];
			if(empty($line))
			{
				if($i < $total - 1) $body = implode('', array_slice($lines, $i + 2));
				break;
			}

			if(!$start)
			{
				$start = explode(' ', $line, 3);
			}elseif(strpos($line, ':'))
			{
				$parts = explode(':', $line, 2);
				$key = trim($parts[0]);
				$value = isset($parts[1])? trim($parts[1]) : '';
				if(!isset($headers[$key]))
					$headers[$key] = $value;
				elseif(!is_array($headers[$key]))
					$headers[$key] = array($headers[$key], $value);
				else
					$headers[$key][] = $value;
			}
		}

		return array(
			'start'   => $start,
			'headers' => $headers,
			'body'    => $body
		);
	}

	protected function parse_request($raw)
	{
		if(!$raw) return false;

		$parts = $this->parse_parts($raw);

		if(isset($parts['start'][2]))
		{
			$start = explode('/', $parts['start'][2]);
			$protocol = strtoupper($start[0]);
			$version = isset($start[1])? $start[1] : '1.1';
		}else
		{
			$protocol = 'HTTP';
			$version = '1.1';
		}

		$parsed = array(
			'method'   => strtoupper($parts['start'][0]),
			'protocol' => $protocol,
			'version'  => $version,
			'headers'  => $parts['headers'],
			'body'     => $parts['body']
		);

		$parsed['request_url'] = $this->get_url_parts($parts['start'][1], $parsed);

		return $parsed;
	}

	protected function process_from_cli($app, $buffer)
	{
		$res = "";
		$spec = array(
			0 => array("pipe", "rb"),
			1 => array("pipe", "wb"),
			2 => array("pipe", "wb")
		);

		$proc = proc_open(dirname(dirname(__DIR__))."/bin/rackem $app --process", $spec, $pipes);
		stream_set_blocking($pipes[2], 0);
		if(!is_resource($proc)) return "";

		fwrite($pipes[0], $buffer);
		fclose($pipes[0]);

		$res = stream_get_contents($pipes[1]);
		fclose($pipes[1]);

		echo stream_get_contents($pipes[2]);
		fclose($pipes[2]);

		proc_close($proc);
		return $res;
	}

	protected function write_response($req, $res)
	{
		list($status, $headers, $body) = $res->finish();
		$phrase = Utils::status_code($status);
		$body = implode("", $body);
		$head = "{$req['protocol']}/{$req['version']} $status $phrase\r\n";

		$raw_headers = array();
		foreach($headers as $k=>$v) $raw_headers[] = "$k: $v";

		$head .= implode("\r\n", $raw_headers);
		return "$head\r\n\r\n$body";
	}

}
