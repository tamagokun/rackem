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
}