<?php
require __DIR__."/../rackem.php";

class App
{
	public function call($env)
	{
		return array(200,array(),array("<pre>",print_r($env,true)));
	}
}

\Rackem\Rack::use_middleware("\Rackem\Auth\Basic",function($username,$password) {
	return $password == "poop";
});
\Rackem\Rack::run("App");
