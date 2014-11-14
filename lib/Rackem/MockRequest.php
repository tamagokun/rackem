<?php
namespace Rackem;

class MockRequest
{
	public $app;

	public static function default_env() {
		return array(
			"rack.version" => \Rackem::version(),
			"rack.url_scheme" => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'])? 'https' : 'http',
			"rack.input" => fopen('php://temp', 'r+b'),
			"rack.errors" => fopen('php://stderr', 'wb'),
			"rack.multithread" => false,
			"rack.multiprocess" => false,
			"rack.run_once" => false
		);
	}

	public function __construct($app)
	{
		$this->app = new Builder($app);
	}

	public function get($uri,$opts=array()) { return $this->request("GET",$uri,$opts); }
	public function post($uri,$opts=array()) { return $this->request("POST",$uri,$opts); }
	public function put($uri,$opts=array()) { return $this->request("PUT",$uri,$opts); }
	public function patch($uri,$opts=array()) { return $this->request("PATCH",$uri,$opts); }
	public function delete($uri,$opts=array()) { return $this->request("DELETE",$uri,$opts); }
	public function head($uri,$opts=array()) { return $this->request("HEAD",$uri,$opts); }

	public function env_for($uri="",$opts=array())
	{
		$uri = parse_url($uri);
		$uri["path"] = '/'.ltrim($uri["path"],'/');

		$env = static::default_env();
		$env["REQUEST_METHOD"] = isset($opts["method"])? strtoupper($opts["method"]) : "GET";
		$env["SERVER_NAME"] = isset($uri["host"])? $uri["host"] : "example.org";
		$env["SERVER_PORT"] = isset($uri["port"])? $uri["port"] : "80";
		$env["QUERY_STRING"] = isset($uri["query"])? $uri["query"] : "";
		$env["PATH_INFO"] = !$uri["path"] || empty($uri["path"])? "/" : $uri["path"];
		$env["rack.url_scheme"] = isset($uri["scheme"])? $uri["scheme"] : "http";
		$env["HTTPS"] = $env["rack.url_scheme"] == "https"? "on" : "off";
		$env["SCRIPT_NAME"] = isset($opts["script_name"])? $opts["script_name"] : "";
        $env["CONTENT_TYPE"] = isset($opts["content_type"])? $opts["content_type"] : "";

		if(isset($opts["params"]))
		{
            $params = is_string($opts["params"])? $opts["params"] : http_build_query($opts["params"]);
			fwrite($env["rack.input"], $params);
			rewind($env["rack.input"]);
		}

		return $env;
	}

	protected function request($method="GET",$uri="",$opts=array())
	{
		$opts["method"] = $method;
		$env = $this->env_for($uri,$opts);
		$res = new Response($this->app->call($env));
		$res->finish();
		return $res;
	}
}
