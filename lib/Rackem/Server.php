<?php
namespace Rackem;

class Server
{
	private $host, $port;

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
		echo "== Rackem on http://{$this->host}:{$this->port}\n";
		echo ">> Rackem web server\n";
		echo ">> Listeneing on {$this->host}:{$this->port}, CTRL+C to stop\n";
	}

	public function start($app)
	{
		while ($client = stream_socket_accept($this->master))
		{
			$buffer = '';

			while (!preg_match('/\r?\n\r?\n/', $buffer))
			{
				$buffer .= fread($client, 2046);
			}
			$request = $this->parse_request($buffer);

			//print_r($request);

			$env = $this->env($request);
			list($status, $headers, $body) = $app->call($env);

			//fwrite($client, $response);
			fclose($client);
		}
	}

/* private */

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

		//$parsed['request_url'] = $this->get_url_parts($parts['start'][1], $parsed);

		return $parsed;
	}

	protected function env($req)
	{
		$headers = $req->getHeaders();
		$env = array(
			'REQUEST_METHOD' => $req->getMethod(),
			'SCRIPT_NAME' => "",
			'PATH_INFO' => $req->getPath(),
			'SERVER_NAME' => $this->host,
			'SERVER_PORT' => $this->port,
			'QUERY_STRING' => $req->getQuery()
		);
		return new \ArrayObject($env);
	}
}
