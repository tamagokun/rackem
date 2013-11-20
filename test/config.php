<?php

require dirname(__DIR__).'/rackem.php';

class App
{
	public function call($env)
	{
		return array(
			200,
			array(
				"Content-Type"=>"text/html"
			),
			array(
				"<h1>Hello World</h1>",
				"<pre>",
				print_r($env, true),
				"</pre>"
			)
		);
	}
}

return \Rackem::run(new App());
