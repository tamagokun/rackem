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
		$request = new \Rackem\Request($env);
		$request->get();
		$request->post();
		
		$response = new \Rackem\Response($this->app->call($env));
		$response->body[] = print_r($env,true);
		$response->body[] = print_r($request->base_url(),true);
		$response->body[] = "\n";
		$response->body[] = print_r($request->url(),true);
		$response->body[] = "\n";
		$response->body[] = print_r($request->media_type(),true);
		foreach($response->body as &$part) $part = str_replace("Hello","Goodbye",$part);
		return $response->finish();
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