<?php
namespace Rackem;

class ShowExceptions
{
	public function __construct($app)
	{
		$this->app = $app;
	}

	public function call($env)
	{
		$this->env = $env;
		set_error_handler(array($this,'error_handler'));
		set_exception_handler(array($this,'exception_handler'));
		return $this->app->call($env);
	}

	public function error_handler($no,$str,$file,$line)
	{
		$e = new \ErrorException($str,$no,0,$file,$line);
		$this->exception_handler($e);
		return true;
	}

	public function exception_handler($e)
	{
		$body = $this->handle_exception($this->env,$e);
		throw new Exception(500, array('Content-Type' => 'text/html'), $body);
	}

	protected function handle_exception($env,$exception)
	{
		$string = $exception->__toString().PHP_EOL;
		fwrite($env['rack.errors'], $string);
		return array($string);
	}
}
