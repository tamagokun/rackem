<?php
namespace Rackem;

class Rack
{
	private static $middleware = array();
	
	protected static function build_env()
	{
		$script_name = dirname($_SERVER['SCRIPT_NAME']);
		$full_path = str_replace($script_name,'',$_SERVER['REQUEST_URI']);
		$env = array(
			"REQUEST_METHOD" => $_SERVER['REQUEST_METHOD'],
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
			"rack.session" => "",
			"rack.logger" => ""
		);
		return array_merge(static::default_env(),$env);
	}
	
	protected static function build_app($app)
	{
		if(is_callable($app)) $app = new Shim($app);
		if(is_string($app)) $app = new $app();
		return $app;
	}
	
	protected static function default_env()
	{
		return $_SERVER;	//use array_map to manipulate?
	}
	
	protected static function execute($result, $env)
	{
		list($status, $headers, $body) = $result;
		fclose($env['rack.input']);
		fclose($env['rack.errors']);
		$headers['X-Powered-By'] = "Rack'em ".implode(".",$env['rack.version']);
		$headers['Status'] = $status;
		foreach($headers as $key=>$value) header("$key: $value");
		foreach($body as $section) echo $section;
		exit;
	}
	
	protected static function middleware($app, &$env)
	{
		self::$middleware = array_reverse(self::$middleware);
		self::use_middleware('Rackem\Exceptions');
		
		if(!empty(self::$middleware))
			foreach(self::$middleware as $ware) $app = $ware($app);
		return $app->call($env);
	}
	
	public static function run($app)
	{
		$env = static::build_env();
		$app = static::build_app($app);
		ob_start();
		$result = self::middleware($app, $env);
		$output = ob_get_clean();
		static::execute($result, $env);
	}
		
	public static function use_middleware($middleware,$options = array())
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