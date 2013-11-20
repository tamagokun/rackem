<?php
namespace Rackem\Handler;

class Sapi
{
	public function run($app)
	{
		if(php_sapi_name() == 'cli-server' && $this->request_is_file()) return false;
		$env = $this->env();

		ob_start();
		list($status, $headers, $body) = $app->call($env);
		$output = ob_get_clean();
		if($output) $headers['X-Output'] = json_encode($output);

		fclose($env['rack.input']);
		fclose($env['rack.errors']);
		if($env['rack.logger']) $env['rack.logger']->close();
		$headers['X-Powered-By'] = "Rack'em ".implode(".",$env['rack.version']);
		$headers['Status'] = $status;
		header($env['SERVER_PROTOCOL']." ".$status);
		foreach($headers as $key=>$values)
			foreach(explode("\n",$values) as $value) header("$key: $value");
		echo implode("",$body);
		exit();
	}

	public function env()
	{
		list($request_uri, $script_name) = $this->url_parts();
		$env = array_merge($_SERVER, array(
			"REQUEST_METHOD" => $_SERVER['REQUEST_METHOD'],
			"SCRIPT_NAME" => $script_name,
			"PATH_INFO" => str_replace($script_name, "", $request_uri),
			"SERVER_NAME" => $_SERVER['SERVER_NAME'],
			"SERVER_PORT" => $_SERVER['SERVER_PORT'],
			"QUERY_STRING" => isset($_SERVER['QUERY_STRING'])? $_SERVER['QUERY_STRING'] : '',
			"rack.version" => \Rackem::version(),
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

//private
	protected function request_is_file()
	{
		return file_exists($_SERVER['SCRIPT_FILENAME']) && !preg_match('/\.php/',$_SERVER['SCRIPT_FILENAME']);
	}

	protected function url_parts()
	{
		$request_uri = ($q = strpos($_SERVER['REQUEST_URI'],'?')) !== false? substr($_SERVER['REQUEST_URI'],0,$q) : $_SERVER['REQUEST_URI'];
		$script_name = php_sapi_name() == 'cli-server'? '/' : $_SERVER['SCRIPT_NAME'];
		if(strpos($request_uri, $script_name) !== 0) $script_name = dirname($script_name);
		return array($request_uri,rtrim($script_name,'/'));
	}
}
