<?php
namespace Rackem;

class BasicWebServer
{
	public function call($env)
	{
		if(substr($env['PATH_INFO'], -1) == '/') $env['PATH_INFO'] .= "index.html";
		$file = new \Rackem\File(getcwd());
		return $file->call($env);
	}
}
