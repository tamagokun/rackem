<?php
require __DIR__."/../autoload.php";

\Rackem\Rack::use_middleware("\Rackem\Session\Cookie",array(
	"path"=>"/",
	"domain"=>"foo.com",
	"expire_after"=>2592000,
	"secret"=>"some_secret_crap"
));

$app = function($env) {
	$res = new \Rackem\Response();
	$res->status = 200;
	return $res->finish();
};

\Rackem\Rack::use_middleware("\Rackem\ShowExceptions");
\Rackem\Protection::protect();
\Rackem\Rack::run($app);
