<?php
require __DIR__."/../rackem.php";

\Rackem\Rack::map("/hello", function($env) {
	return array(200, array("Content-Type"=>"text/html"), array("Hello!!!"));
});

\Rackem\Rack::map("/world", function($env) {
	return array(200, array("Content-Type"=>"text/html"), array("World."));
});

return \Rackem\Rack::run();
