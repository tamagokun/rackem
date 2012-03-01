<?php
require __DIR__."/../autoload.php";

class App
{
	public function call($env)
	{
		return array(200,array(),array("<pre>",print_r($env,true)));
	}
}

$app = new App();
$auth = new \Rackem\Auth\Basic($app,function($username, $password) {
	return $password == "poop";
});

\Rackem\Rack::use_middleware($auth);
\Rackem\Rack::run($app);