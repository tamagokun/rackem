<?php
namespace Rackem\Protection;

class Csrf
{
	public static $field = '_csrf';
	public static $key = 'csrf.token';
	
	public static function token($env)
	{
		if(isset($env['rack.session']) && isset($env['rack.session'][self::$key])) return $env['rack.session'][self::$key];
		$env['rack.session'][self::$key] = self::secure_random(32);
	}
	
	public static function tag($env)
	{
		return '<input type="hidden" name="'.self::$field.'" value="'.self::token($env).'" />';
	}
	
	protected static function secure_random($n)
	{
		$result = pack("m*",\Rackem\Utils::random_bytes($n));
		foreach($result as $index=>$part) if($part == '\n') unset($result[$index]);
		return $result;
	}
}