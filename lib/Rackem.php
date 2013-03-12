<?php

class Rackem
{
	public static $server = null;
	protected static $builder = null;

	public static function cli_req_is_file()
	{
		return file_exists($_SERVER['SCRIPT_FILENAME']) && !preg_match('/\.php/',$_SERVER['SCRIPT_FILENAME']);
	}

	public static function map($path, $app)
	{
		self::ensure_builder();
		self::$builder->map($path, $app);
	}

	public static function run($app = null)
	{
		if(php_sapi_name() == 'cli-server' && static::cli_req_is_file()) return false;

		self::ensure_builder();
		if($app) self::$builder->run($app);

		if(self::$server) return self::$builder;

		// typical web server
		$env = static::build_env();
		ob_start();
		$result = self::$builder->call($env);
		$output = ob_get_clean();
		if($output) $result[1]['X-Output'] = json_encode($output);
		static::execute($result, $env);
	}

	public static function use_middleware($middleware,$options = array())
	{
		self::ensure_builder();
		self::$builder->use_middleware($middleware, $options);
	}

	public static function version()
	{
		return array(1,1);
	}

/* private */

	protected static function build_env()
	{
		list($request_uri,$script_name) = static::url_parts();
		$env = array_merge($_SERVER, array(
			"REQUEST_METHOD" => $_SERVER['REQUEST_METHOD'],
			"SCRIPT_NAME" => $script_name,
			"PATH_INFO" => str_replace($script_name,"",$request_uri),
			"SERVER_NAME" => $_SERVER['SERVER_NAME'],
			"SERVER_PORT" => $_SERVER['SERVER_PORT'],
			"QUERY_STRING" => isset($_SERVER['QUERY_STRING'])? $_SERVER['QUERY_STRING'] : '',
			"rack.version" => static::version(),
			"rack.url_scheme" => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])? 'https' : 'http',
			"rack.input" => fopen('php://input', 'r'),
			"rack.errors" => fopen('php://stderr', 'wb'),
			"rack.multithread" => false,
			"rack.multiprocess" => false,
			"rack.run_once" => false,
			"rack.session" => array(),
			"rack.logger" => ""
		));
		return new \ArrayObject($env);
	}

	protected static function ensure_builder()
	{
		if(!self::$builder) self::$builder = new Builder();
	}

	protected static function execute($result, $env)
	{
		list($status, $headers, $body) = $result;
		fclose($env['rack.input']);
		fclose($env['rack.errors']);
		if($env['rack.logger']) $env['rack.logger']->close();
		$headers['X-Powered-By'] = "Rack'em ".implode(".",$env['rack.version']);
		$headers['Status'] = $status;
		header($env['SERVER_PROTOCOL']." ".$status);
		foreach($headers as $key=>$value) header("$key: $value");
		echo implode("",$body);
		exit();
	}

	protected static function url_parts()
	{
		$request_uri = ($q = strpos($_SERVER['REQUEST_URI'],'?')) !== false? substr($_SERVER['REQUEST_URI'],0,$q) : $_SERVER['REQUEST_URI'];
		$script_name = php_sapi_name() == 'cli-server'? '/' : $_SERVER['SCRIPT_NAME'];
		if(strpos($request_uri, $script_name) !== 0) $script_name = dirname($script_name);
		return array($request_uri,rtrim($script_name,'/'));
	}
}
