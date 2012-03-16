<?php
namespace Rackem;

class Utils
{
	const DEFAULT_SEP = "/[&;] */";
	
	public static function parse_nested_query($qs, $d=null)
	{
		$params = array();
		if(empty($qs)) return $params;
		array_map(function($p) use (&$params) {
			list($k,$v) = explode("=",$p,2);
			$params[$k] = $v;
		},preg_split((!is_null($d))? "/[$d] */" : self::DEFAULT_SEP,$qs));
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
		},preg_split(!is_null($d)? "/[$d] */" : self::DEFAULT_SEP,$qs));
		return $params;
	}

	public static function set_cookie_header($header,$key,$value)
	{
		$parts = array();
		if(isset($value["domain"])) $parts[] = "; domain={$value["domain"]}";
		if(isset($value["path"])) $parts[] = "; path={$value["path"]}";
		if(isset($value["expires"]))
		{
			$time = gmdate("D, d-M-Y H:i:s",$value["expires"])." GMT";
			$parts[] = "; expires={$time}";
		}
		if(isset($value["secure"])) $parts[] = "; secure";
		if(isset($value["httponly"])) $parts[] = "; HttpOnly";
		$value = $value["value"];
		$value = is_array($value)? $value : array($value);
		$cookie = $key."=".implode("&",$value).implode('',$parts);
		if(isset($header["Set-Cookie"]))
		{
			$header["Set-Cookie"] = is_array($header["Set-Cookie"])? implode("\n",$header["Set-Cookie"] + array($cookie))
				: implode("\n",array($header["Set-Cookie"],$cookie));
		}else $header["Set-Cookie"] = $cookie;
		return $header;
	}

	public static function delete_cookie_header($header,$key,$value = array())
	{
		$cookies = array();
		if(isset($header["Set-Cookie"]))
			$cookies = is_array($header["Set-Cookie"])? $header["Set-Cookie"] : explode("\n",$header["Set-Cookie"]);
		foreach($cookies as $key=>$cookie)
		{
			if(isset($value["domain"]))
				if(preg_match_all('/\A{$key}=.*domain={$value["domain"]}/',$cookie) > 0) unset($cookies[$key]);
			else
				if(preg_match_all('/\A{$key}=/',$cookie) > 0) unset($cookies[$key]);
		}
		$header["Set-Cookie"] = implode("\n",$cookies);
		self::set_cookie_header($header,$key,array_merge($value,array(
			"value"=>"","path"=>null,"domain"=>null,"expires"=>time(0))));
		return null;
	}

	public static function byte_ranges($env, $size)
	{
		if(isset($env['HTTP_RANGE'])) $http_range = $env['HTTP_RANGE'];
		else return null;
		$ranges = array();
		foreach(preg_split('/,\s*/',$http_range) as $range_spec)
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
		return reset(unpack("H*",self::random_bytes($n)));
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
