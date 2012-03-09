<?php
require __DIR__."/../autoload.php";

session_start();

\Rackem\Rack::use_middleware("\Rackem\Session\Cookie",array(
	"path"=>"/",
	"expire_after"=>2592000,
	"secret"=>"some_secret_crap"
));

$app = function($env) {
	$env['rack.session']["name"] = "Mike";

	return array(200,array(),array("<pre>",print_r($env,true)));
};

\Rackem\Rack::run($app);
