<?php

namespace spec\Rackem;

use PHPSpec2\ObjectBehavior;

class File extends ObjectBehavior
{
	public function let()
	{
		$this->beConstructedWith(dirname(dirname(__DIR__)));
	}

	public function it_should_be_initializable()
	{
		$this->shouldHaveType('Rackem\File');
	}

	public function it_should_serve_existing_files()
	{
		$file = dirname(dirname(__DIR__))."/README.md";
		$env = array(
			"PATH_INFO" => "/README.md"
		);
		$data = file_get_contents($file);
		$expect = array(
			200,
			array("Last-Modified"=>filemtime($file), "Content-Type"=>"text/plain", "Content-Length"=>filesize($file).""),
			array($data)
		);
		$this->call($env)->shouldReturn($expect);
	}

	public function it_should_handle_missing_files()
	{
		$path = "/foo";
		$length = strlen("File not found: $path\n");
		$expect = array(
			404,
			array("Content-Type"=>"text/plain", "Content-Length"=>"$length", "X-Cascade"=>"pass"),
			array("File not found: $path\n")
		);
		$this->call(array("PATH_INFO"=>$path))->shouldReturn($expect);
	}
}
