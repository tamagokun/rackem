<?php
namespace Rackem;

class Builder
{
	protected $map, $use, $run;

	public function __construct($app = null, $middleware = array())
	{
		$this->run($app);
		$this->use = $middleware;
		$this->map = array();
	}

	public function call($env)
	{
		$this->use = $this->use ? array_reverse($this->use) : array();
		$app = empty($this->map) ? $this->run : $this->generate_map($this->run, $this->map);
		try
		{
			foreach($this->use as $middleware) $app = $middleware($app);
			return $app->call($env);
		}catch(Exception $e)
		{
			return $e->finish();
		}
	}

	public function map($path, $block)
	{
		$this->map[$path] = $block;
	}

	public function run($app)
	{
		$this->run = $this->build_app($app);
	}

	public function use_middleware($middleware, $options = array())
	{
		$this->use[] = function($app) use ($middleware, $options) {
			return is_object($middleware)? $middleware : new $middleware($app, $options);
		};
	}

/* private */

	private function generate_map($default_app, $map)
	{
		$mapped = $default_app ? array("/" => $default_app) : array();
		foreach($map as $route=>$app)
			$mapped[$route] = new Builder($app);
		return new URLMap($mapped);
	}

	private function build_app($app)
	{
		if(is_callable($app)) $app = new Shim($app);
		if(is_string($app)) $app = new $app();
		return $app;
	}
}
