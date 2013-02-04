<?php
namespace Rackem;

class URLMap
{
	protected $map;

	public function __construct($map)
	{
		$this->map = $this->remap($map);
	}

	public function call($env)
	{
		$path = $env['PATH_INFO'];
		$script_name = $env['SCRIPT_NAME'];
		$http_host = $env['HTTP_HOST'];
		$server_name = $env['SERVER_NAME'];
		$port = $env['SERVER_PORT'];

		foreach($this->map as $route=>$info)
		{
			list($host, $route, $match, $app) = $info;

			if($host !== $http_host && $host !== $server_name && !(!$host && ($http_host == $server_name || $http_host = "{$server_name}:{$port}")))
				continue;

			$m = array();
			if(preg_match($match, $path, $m) === false || empty($m)) continue;

			$rest = $m[1];
			if($rest || !empty($rest) || substr($rest,0,1) == '/') continue;

			//we have a match
			$env['SCRIPT_NAME'] = "{$script_name}{$route}";
			$env['PATH_INFO'] = $rest;
			return $app->call($env);
		}
		return array(404, array("Content-Type"=>"text/plain", "X-Cascade"=>"pass"), array("Not Found: {$path}"));
	}

/* private */

	protected function remap($map)
	{
		foreach($map as $route=>$app)
		{
			$host = null;
			// if(preg_match('/https?:\/\/(.*?)(\/.*)/', $route, $m) !== false)
			// 	list($host, $route) = $m;

			if(substr($route,0,1) !== '/') throw new Exception('paths need to start with /');

			$route = rtrim($route, "/");
			$regex_route = str_replace("/", "\/+", $route);
			$match = "/^{$regex_route}(.*)/";
			
			$map[$route] = array($host, $route, $match, $app);
		}
		return $map;
	}
}
