<?php
namespace Rackem;

class Server
{
	private $host, $port;

	public function __construct($host = '0.0.0.0', $port = 9393)
	{
		$this->host = $host;
		$this->port = $port;
	}

	public function start($app)
	{
		$loop = \React\EventLoop\Factory::create();
		$this->socket = new \React\Socket\Server($loop);

		$http = new \React\Http\Server($this->socket);
		$http->on('request', function($req,$res) use($app) {
			$env = $this->env($req);
			list($status, $headers, $body) = $app->call($env);
			$res->writeHead($status, $headers);
			$res->end(implode("\n",$body));
		});
		
		try {
			$this->socket->listen($this->port);
			echo "== Rackem on http://{$this->host}:{$this->port}/\n";
			echo ">> Rackem web server\n";
			echo ">> Listening on {$this->host}:{$this->port}, CTRL+C to stop\n";
			$loop->run();
		}catch(Exception $e)
		{
			echo ">> Failed to start server.";
			exit(1);
		}
	}

/* private */

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
