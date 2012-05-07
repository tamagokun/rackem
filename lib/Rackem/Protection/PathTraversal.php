<?php
namespace Rackem\Protection;

class PathTraversal extends \Rackem\Protection
{
	public function call($env)
	{
		$path_was = $env["PATH_INFO"];
		if($path_was) $env["PATH_INFO"] = $this->cleanup($path_was);
		if($env["PATH_INFO"] == $path_was)
			return $this->app->call($env);
	}
	
	public function cleanup($path)
	{
		$parts = array();
		$unescaped = str_replace(array('%2e','%2f'),array('.','/'),$path);
		foreach(explode('/',$unescaped) as $part)
		{
			if(empty($part) || $part == '.') continue;
			$part == '..'? array_pop($parts) : $parts[] = $part;
		}
		$cleaned = '/'.implode('/',$parts);
		if(!empty($parts) && preg_match('/\/\.{0,2}$/',$unescaped) !== false) $cleaned.='/';
		return $cleaned;
	}
}