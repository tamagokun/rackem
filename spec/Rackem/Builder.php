<?php

namespace spec\Rackem;

use PHPSpec2\ObjectBehavior;

class Builder extends ObjectBehavior
{
	public function it_should_call_applications()
	{
		$this->run(function() {
			return array(200, array(), array("Hello"));
		});
		$this->call(array())->shouldReturn(array(200, array(), array("Hello")));
	}

	public function it_should_use_middleware()
	{
		$this->run(function() {
			return array(200, array(), array("Hello"));
		});
		$this->use_middleware("\spec\Rackem\Translate", array("lang"=>"japanese"));
		$this->use_middleware("\spec\Rackem\Translate", array("lang"=>"french"));
		$this->call(array())->shouldReturn(array(200, array(), array("Konnichiwa")));
	}

	public function it_should_map_applications()
	{
		$this->map("/", function() {
			return array(200, array(), array("Hello"));
		});

		$this->map("/foo", function() {
			return array(200, array(), array("Foo!"));
		});

		$request = new \Rackem\MockRequest(null);

		$this->call($request->env_for("/"))->shouldReturn(array(200, array(), array("Hello")));
		$this->call($request->env_for("/foo"))->shouldReturn(array(200, array(), array("Foo!")));
	}

	public function it_should_not_find_missing_routes()
	{
		$this->map("/foo", function() {
			return array(200, array(), array("Foo!"));
		});
		$request = new \Rackem\MockRequest(null);
		$this->call($request->env_for("/bar"))->shouldReturn(array(404, array("Content-Type"=>"text/plain", "X-Cascade"=>"pass"), array("Not Found: /bar")));
	}

	public function it_should_catch_exceptions()
	{
		$this->run(function() {
			throw new \Rackem\Exception(500, array(), array("Internal Server Error."));
		});
		$this->shouldNotThrow(new \Rackem\Exception(500, array(), array("Internal Server Error.")))->duringCall(array());
		$this->call(array())->shouldReturn(array(500, array(), array("Internal Server Error.")));
	}
}

class Translate extends \Rackem\Middleware
{
	public function call($env)
	{
		list($status, $headers, $body) = $this->app->call($env);
		if(!isset($this->options["lang"])) return array($status, $headers, $body);
		switch($this->options["lang"])
		{
			case "japanese":
				return array($status, $headers, array("Konnichiwa"));
				break;
			case "french":
				return array($status, $headers, array("Bonjour"));
				break;
		}
	}
}
