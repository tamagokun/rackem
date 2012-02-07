<?php
error_reporting(-1);
require __DIR__."/../autoload.php";

class ToJson
{
	public function __construct($app)
	{
		$this->app = $app;
	}
	
	public function call(&$env)
	{
		list($status, $headers, $body) = $this->app->call($env);
		$body[] =  "{response:\"".array_pop($body)."\"}";
		return array($status, $headers, $body);
	}
}

class Goodbye
{
	public function __construct($app)
	{
		$this->app = $app;
	}
	
	public function call(&$env)
	{
		list($status, $headers, $body) = $this->app->call($env);
		foreach($body as &$value) $value = str_replace("Hello","Goodbye",$value);
		return array($status, $headers, $body);
	}
}

class App
{
	public function call(&$env)
	{
		return array(200, array("Content-Type" => "text/plain"), array("Hello World!"));
	}
}

\Rackem\Rack::use_middleware("ToJson");
\Rackem\Rack::use_middleware("Goodbye");
\Rackem\Rack::run("App");