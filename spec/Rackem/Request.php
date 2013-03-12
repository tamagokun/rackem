<?php

namespace spec\Rackem;

use PHPSpec2\ObjectBehavior;

class Request extends ObjectBehavior
{
	public function it_should_be_initializable()
	{
		$this->shouldHaveType('Rackem\Request');
	}

	public function it_should_parse_get_variables()
	{
		$request = new \Rackem\MockRequest(null);
		$this->env = $request->env_for("/?foo=bar");
		$this->get()->shouldReturn(array("foo"=>"bar"));
	}

	public function it_should_parse_post_variables()
	{
		$request = new \Rackem\MockRequest(null);
		$this->env = $request->env_for("/", array("params"=>array("foo"=>"bar"), "method"=>"POST"));
		$this->post()->shouldReturn(array("foo"=>"bar"));
	}

	public function it_should_contain_all_variables_in_params()
	{
		$request = new \Rackem\MockRequest(null);
		$this->env = $request->env_for("/?foo=bar", array("params"=>array("fizz"=>"bazz"), "method"=>"POST"));
		$this->params()->shouldReturn(array("foo"=>"bar", "fizz"=>"bazz"));
	}

	public function it_should_parse_requests_properly()
	{
		$request = new \Rackem\MockRequest(null);
		$this->env = $request->env_for("/foo");
		$this->base_url()->shouldReturn("http://example.org");
		$this->fullpath()->shouldReturn("/foo");
		$this->host()->shouldReturn("example.org");
		$this->host_with_port()->shouldReturn("example.org:80");
		$this->path()->shouldReturn("/foo");
		$this->path_info()->shouldReturn("/foo");
		$this->port()->shouldReturn(80);
		$this->query_string()->shouldReturn("");
		$this->referer()->shouldReturn(null);
		$this->request_method()->shouldReturn("GET");
		$this->scheme()->shouldReturn("http");
		$this->ssl()->shouldReturn(false);
		$this->url()->shouldReturn("http://example.org/foo");

		$this->env = $request->env_for("https://google.com:7685/bar/foo?baz=true", array("method"=>"POST"));
		$this->base_url()->shouldReturn("https://google.com:7685");
		$this->fullpath()->shouldReturn("/bar/foo?baz=true");
		$this->host()->shouldReturn("google.com");
		$this->host_with_port()->shouldReturn("google.com:7685");
		$this->path()->shouldReturn("/bar/foo");
		$this->path_info()->shouldReturn("/bar/foo");
		$this->port()->shouldReturn(7685);
		$this->query_string()->shouldReturn("baz=true");
		$this->referer()->shouldReturn(null);
		$this->request_method()->shouldReturn("POST");
		$this->scheme()->shouldReturn("https");
		$this->ssl()->shouldReturn(true);
		$this->url()->shouldReturn("https://google.com:7685/bar/foo?baz=true");
	}
}
