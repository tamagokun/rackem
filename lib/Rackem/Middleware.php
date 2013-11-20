<?php
namespace Rackem;

class Middleware
{
	protected $app, $options;

	public function __construct($app, $options=array())
	{
		$this->app = $app;
		$this->options = $options;
	}

	public function call($env)
	{
		return $this->app->call($env);
	}
}
