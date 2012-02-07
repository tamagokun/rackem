#!/usr/bin/env php
<?php
require "lib/Rack.php";
require "lib/RubyRack.php";

class App
{
	public function call($env)
	{
		return array(200, array("Content-Type" => "text/plain"), array("Hello World!"));
	}
}

\Rackem\RubyRack::run( new App());