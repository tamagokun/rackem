<?php

require __DIR__."/../autoload.php";

$app = function($env) {
	return array(200, array('Content-Type' => 'text/html'), array('Hello World!'));
};

\Rackem\Rack::run($app);