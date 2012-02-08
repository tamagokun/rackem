<?php
require __DIR__."/../autoload.php";

class ToJson extends \Rackem\Middleware
{	
	public function call($env)
	{
		list($status, $headers, $body) = $this->app->call($env);
		$body[] =  "{response:\"".array_pop($body)."\"}";
		return array($status, $headers, $body);
	}
}

class Goodbye
{
	public function __construct($app) { $this->app = $app; }
	
	public function call($env)
	{
		list($status, $headers, $body) = $this->app->call($env);
		$req = new \Rackem\Request($env);
		$body[] = $req->media_type();
		$body[] = print_r($env,true);
		$res = new \Rackem\Response($body,$status,$headers);
		foreach($res->body as &$part) $part = str_replace("Hello","Goodbye",$part);
		return $res->finish();
	}
}

class App
{
	public function call($env)
	{
		return array(200, array("Content-Type" => "text/plain"), array("Hello World!"));
	}
}

\Rackem\Rack::use_middleware("ToJson");
\Rackem\Rack::use_middleware("Goodbye");
\Rackem\Rack::run("App");