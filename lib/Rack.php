<?php
namespace Rackem;

class Rack
{
	private static $middleware = array();
	
	private static function build_env()
	{
		$script_name = dirname($_SERVER['SCRIPT_NAME']);
		$full_path = str_replace($script_name,'',$_SERVER['REQUEST_URI']);
		$env = array(
			"REQUEST_METHOD" => $_SERVER['REQUEST_METHOD'];
			"SCRIPT_NAME" => $script_name,
			"PATH_INFO" => substr($full_path, 0, strpos($full_path,"?")),
			"QUERY_STRING" => substr($full_path, strpos($full_path,"?")+1),
			"SERVER_NAME" => $_SERVER['SERVER_NAME'],
			"SERVER_PORT" => $_SERVER['SERVER_PORT'],
			"rack.version" => static::version(),
			"rack.url_scheme" => (@$_SERVER['HTTPS'] ? 'https' : 'http'),
			"rack.input" => fopen('php://input', 'r'),
			"rack.errors" => fopen('php://stderr', 'w'),
			"rack.multithread" => false,
			"rack.multiprocess" => false,
			"rack.run_once" => false,
			"rack.session" =>,
			"rack.logger" =>
		);
		return array_merge(static::default_env(),$env);
	}
	
	private static function default_env()
	{
		return $_SERVER;	//use array_map to manipulate?
	}
	
	private static function execute($result, $env)
	{
		list($status, $headers, $body) = $result;
		fclose($env['rack.input']);
		fclose($env['rack.errors']);
		$headers['X-Powered-By'] = "Rack'em ".implode(".",$env['rack.version']);
		$headers['Status'] = $status;
		foreach($headers as $key=>$value) header("$key: $value");
		foreach($body as $section) print $section;
		exit;
	}
	
	private static function middleware($app, $env)
	{
		$middleware = array_reverse(self::$middleware);
		//$middleware::use('Rackem\Exceptions');
		if(empty($middleware)) return $app($env);
		
		foreach($middleware as $ware) $app = $ware($app);
		return $app($env);
	}
	
	public static function run($app)
	{
		$env =& static::build_env();
		ob_start();
		$result = self::middleware($app, $env);
		$output = ob_get_clean();
		static::execute($result, $env);
	}
	
	public static function use($middleware,$options = array())
	{
		self::$middleware[] = function($app) use ($middleware, $options) {
			return new $middleware($app, $options);
		};
	}
	
	public static function version()
	{
		return array(0,1);
	}
	
}