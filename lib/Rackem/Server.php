<?php
namespace Rackem;

class Server
{
	public $reload = true;
	private $host, $port, $app;

	public function __construct($host = '0.0.0.0', $port = 9393)
	{
		$this->host = $host;
		$this->port = $port;
		$this->master = @stream_socket_server("tcp://$host:$port", $errno, $errstr);
		if($this->master === false)
		{
			echo ">> Failed to start server.\n";
			exit($errno > 0? $errno : 2);
		}
		stream_set_blocking($this->master, 0);
		declare(ticks=1);
		pcntl_signal(SIGINT, array($this, "stop"));
		pcntl_signal(SIGTERM, array($this, "stop"));
	}

	public function start($app)
	{
		echo "== Rackem on http://{$this->host}:{$this->port}\n";
		echo ">> Rackem web server\n";
		echo ">> Listening on {$this->host}:{$this->port}, CTRL+C to stop\n";

		$sockets = array($this->master);
		$null = null;
		while(1 === @stream_select($sockets, $null, $null, null))
		{
			$client = stream_socket_accept($this->master);
			$buffer = '';

			while (!preg_match('/\r?\n\r?\n/', $buffer))
			{
				$buffer .= fread($client, 2046);
			}
			$req = $this->parse_request($buffer);

			ob_start();
			$env = $this->env($req);
			if($this->reload)
			{
				$res = shell_exec($this->run_from_cli($app, $env));
				fwrite($client, $res);
			}else
			{
				$this->app = include($app);
				$res = new Response($this->app->call($env));
				fwrite($client, $this->write_response($req, $res));
			}

			fclose($client);
			fclose($env['rack.input']);
			fclose($env['rack.errors']);
			if($env['rack.logger']) $env['rack.logger']->close();
		}
	}

	public function stop()
	{
		echo ">> Stopping ...\n";
		fclose($this->master);
		exit(0);
	}

/* private */

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
			'rack.version' => Rack::version(),
			'rack.url_scheme' => $req['request_url']['scheme'],
			'rack.input' => fopen('php://input', 'r'),
			'rack.errors' => fopen('php://stderr', 'wb'),
			'rack.multithread' => false,
			'rack.multiprocess' => false,
			'rack.run_once' => false,
			'rack.session' => array(),
			'rack.logger' => ""
		);
		fwrite($env['rack.input'], $req['body']);
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

	protected function run_from_cli($app, $env)
	{
		$request_uri = $env['PATH_INFO'];
		$request_method = $env['REQUEST_METHOD'];
		$query_string = $env['QUERY_STRING'];
		$server_name = $env['SERVER_NAME'];
		$server_port = $env['SERVER_PORT'];
		$server_protocol = $env['SERVER_PROTOCOL'];
		$rackem = dirname(dirname(__DIR__)).'/rackem.php';
		$cmd = <<<EOT
php --run '
	\$_SERVER["REQUEST_URI"] = "$request_uri";
	\$_SERVER["REQUEST_METHOD"] = "$request_method";
	\$_SERVER["SCRIPT_NAME"] = "/";
	\$_SERVER["QUERY_STRING"] = "$query_string";
	\$_SERVER["SERVER_NAME"] = "$server_name";
	\$_SERVER["SERVER_PORT"] = "$server_port";
	\$_SERVER["SERVER_PROTOCOL"] = "$server_protocol";
	include("$rackem");
	include("$app");
'
EOT;
		return $cmd;
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
