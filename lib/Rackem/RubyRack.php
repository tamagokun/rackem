<?php
namespace Rackem;

class RubyRack extends Rack
{
	protected static function build_env()
	{
		return json_decode(file_get_contents('php://stdin'), true);
	}
	
	protected static function execute($result, $env)
	{
		list($status, $headers, $body) = $result;
		exit(json_encode(array($status, $headers, $body)));
	}
	
	public static function run($app = null)
	{
		$env = static::build_env();
		$app = static::build_app($app);
		
		if(is_null($app) && !is_null($env['rack.ruby_bridge_response']))
			$app = function($env) use ($env) { return $env['rack.ruby_bridge_response']; };
		ob_start();
		$result = self::middleware($app, $env);
		$output = ob_get_clean();
		static::execute($result, $env);
	}
}