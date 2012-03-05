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
}