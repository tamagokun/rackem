<?php

require __DIR__."/../lib/Rack.php";

class App
{
	public $app;
	
	public function __construct($app) 
	{
		$this->app = $app;
	}
	
	public function __invoke($env)
	{
		return array(200, array("Content-Type" => "text/plain"), array("Hello World!"));
	}
	
	public function call($env)
	{
		return array(200, array("Content-Type" => "text/plain"), array("Hello World!"));
	}
}

\Rackem\Rack::run( new App());