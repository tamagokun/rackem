<?php
namespace Rackem;

class Utils
{
	const DEFAULT_SEP = "[&;] *";
	
	public static function parse_nested_query($qs, $d=null)
	{
		$params = array();
		if(empty($qs)) return $params;
		array_map(function($p) use (&$params) {
			list($k,$v) = explode("=",$p,2);
			$params[$k] = $v;
		},explode((!is_null($d))? "[$d] *" : self::DEFAULT_SEP,$qs));
		return $params;
	}
	
	public static function parse_query($qs, $d=null)
	{
		$params = array();
		array_map(function($p) use(&$params) {
			list($k,$v) = explode("=",$p,2);
			if(isset($params[$k]))
			{
				if(!is_array($params[$k])) $params[$k] = array($params[$k]);
				$params[$k][] = $v;
			}else
				$params[$k] = $v;
		}, explode(($d)? "[$d] *" : self::DEFAULT_SEP,$qs));
		return $params;
	}

	public static function set_cookie_header($header,$key,$value)
	{
		if(isset($value["domain"])) $domain = "; domain={$value["domain"]}";
		if(isset($value["path"])) $path = "; path={$value["path"]}";
		if(isset($value["expires"]))
			$expires = "; expires=";
		if(isset($value["secure"])) $secure = "; secure";
		if(isset($value["httponly"])) $httponly = "; HttpOnly";
		$value = $value["value"];
		$value = is_array($value)? $value : array($value);
		$cookie = "$key={implode("&",$value)}{$domain}{$path}{$expires}{$secure}{$httponly}";
		if(isset($header["Set-Cookie"]))
		{
			$header["Set-Cookie"] = is_array($header["Set-Cookie"])? implode("\n",$header["Set-Cookie"] + array($cookie))
				: implode("\n",array($header["Set-Cookie"],$cookie));
		}else $header["Set-Cookie"] = $cookie;
		return $header;
	}

	public static function delete_cookie_header($header,$key,$value = array())
	{
		
	}

	public static function byte_ranges($env, $size)
	{
		if(isset($env['HTTP_RANGE'])) $http_range = $env['HTTP_RANGE'];
		else return null;
		$ranges = array();
		foreach(explode('/,\s*/',$http_range) as $range_spec)
		{
			$matches = array();
			preg_match_all('/bytes=(\d*)-(\d*)/',$range_spec,$matches);
			if(!$matches) return null;
			$r0 = $matches[1];
			$r1 = $matches[2];
			if(empty($r0))
			{
				if(empty($r1)) return null;
				$r0 = max($size - $r1, 0);
				$r1 = $size - 1;
			}else
			{
				if(empty($r1)) $r1 = $size - 1;
				else
				{
					if($r1 < $r0) return null;
					if($r1 >= $size) $r1 = $size - 1;
				}
			}
			if($r0 <= $r1)
			{
				$ranges[] = array($r0,$r1);
			}
		}
		return $ranges;
	}

	public static function random_hex($n)
	{
		return array_shift(unpack("H*",self::random_bytes($n)));
	}

	public static function random_bytes($n=16)
	{
		if(function_exists("openssl_random_pseudo_bytes")) return openssl_random_pseudo_bytes($n);
		if(file_exists("/dev/urandom"))
		{
			$handle = fopen("/dev/urandom","r");
			$rand = fread($handle,$n);
			fclose($handle);
			if($rand !== false) return $rand;
		}
		//TODO: implement Windows method
		throw new \Exception("No random device");
	}
}
