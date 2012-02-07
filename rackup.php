#!/usr/bin/env php
<?php
require "autoload.php";

class App
{
	public function call($env)
	{
		return array(200, array("Content-Type" => "text/plain"), array(print_r($env,true)));
	}
}

\Rackem\RubyRack::run("App");