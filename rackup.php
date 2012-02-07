#!/usr/bin/env php
<?php
require "autoload.php";

class App
{
	public function call(&$env)
	{
		return array(200, array("Content-Type" => "text/plain"), array("Hello World!"));
	}
}

\Rackem\RubyRack::run("App");