<?php

namespace spec\Rackem;

use PHPSpec2\ObjectBehavior;

class Response extends ObjectBehavior
{
	public function it_should_be_initializable()
	{
		$this->shouldHaveType('Rackem\Response');
	}

	public function it_should_return_string()
	{
		$this->write(array("foo","bar","baz"));
		$this->body()->shouldReturn("foobarbaz");
	}

	public function it_should_set_content_length_and_type()
	{
		$this->send(200, array("foo"));
		$this->finish();
		$this->header->shouldBe(array("Content-Type"=>"text/html","Content-Length"=>3));
	}

	public function it_should_comply_to_http_status_codes()
	{
		$this->send(304);
		$this->finish()->shouldReturn(array(304,array(),array()));
	}

}
