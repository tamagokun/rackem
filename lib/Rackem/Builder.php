<?php
namespace Rackem;

class Builder
{
	protected $use, $run;

	public function __construct($app = null, $middleware = array())
	{
		$this->run($app);
		$this->use = $middleware;
	}

	public function call($env)
	{
		$this->use = array_reverse($this->use);
		$app = $this->run;
		try
		{
			if(!empty($this->use)) foreach($this->use as $middleware) $app = $middleware($app);
			return $app->call($env);
		}catch(Exception $e)
		{
			return $e->finish();
		}
	}

	public function map($path, $block)
	{
		//not yet implemented
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

	private function build_app($app)
	{
		if(is_callable($app)) $app = new Shim($app);
		if(is_string($app)) $app = new $app();
		return $app;
	}
}
