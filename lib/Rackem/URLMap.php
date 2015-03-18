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
		$http_host = isset($env['HTTP_HOST'])? $env['HTTP_HOST'] : null;
		$server_name = $env['SERVER_NAME'];
		$port = $env['SERVER_PORT'];

		foreach($this->map as $route=>$info)
		{
			list($host, $route, $match, $app) = $info;

			// This causes more issues than anything. Not worth it right now.
			//if(!($host === $http_host || $host === $server_name || (!$host && ($http_host == $server_name || $http_host == "{$server_name}:{$port}"))))
			//	continue;

			$m = array();
			if(!preg_match($match, $path, $m)) continue;

			$rest = $m[1];
			if($rest && !empty($rest) && substr($rest,0,1) !== '/') continue;

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
		$mapped = array();
		foreach($map as $route=>$app)
		{
			$host = null;
			if(preg_match('/https?:\/\/(.*?)(\/.*)/', $route, $m))
				list($host, $route) = array_slice($m, 1);
			if(substr($route,0,1) !== '/') throw new Exception('paths need to start with /');

			$route = rtrim($route, "/");
			$regex_route = str_replace("/", "\/+", $route);
			$match = "/^{$regex_route}(.*)/";
			
			$mapped[$route] = array($host, $route, $match, $app);
		}
		uksort($mapped, function($a, $b) { return strlen($a) < strlen($b); });
		return $mapped;
	}
}
