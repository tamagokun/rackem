<?php
namespace Rackem;

class Exceptions
{
	public function __construct($app)
	{
		$this->app = $app;
	}
	
	public function call($env)
	{
		try
		{
			return $this->app->call($env);
		}catch(\Exception $e)
		{
			$body = $this->handle_exception($env, $e);
			return array(500, array('Content-Type' => 'text/html'), $body);
		}
	}
	
	protected function handle_exception($env,$exception)
	{
		fwrite($env['rack.errors'], $exception->__toString());
		return array($exception->__toString());
	}
}