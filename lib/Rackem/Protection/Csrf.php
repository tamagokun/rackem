<?php
namespace Rackem\Protection;

class Csrf extends \Rackem\Protection\AuthenticityToken
{
	public static $field = '_csrf';
	public static $key = 'csrf.token';

	public static function token($env)
	{
		if(isset($env['rack.session']) && isset($env['rack.session'][self::$key])) return $env['rack.session'][self::$key];
		$env['rack.session'][self::$key] = \Rackem\Utils::random_hex(32);
	}

	public static function tag($env)
	{
		return '<input type="hidden" name="'.self::$field.'" value="'.self::token($env).'" />';
	}

	public function call($env)
	{
		self::token($env);
		return parent::call($env);
	}
}