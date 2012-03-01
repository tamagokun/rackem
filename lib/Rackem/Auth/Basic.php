<?php
namespace Rackem\Auth;

class Basic
{
	private $authenticator,$auth,$realm;

	public function __construct($app)
	{
		$params = func_get_args();
		$this->authenticator = array_pop($params);
		$this->app = array_shift($params);
		if(count($params)) $this->realm = array_shift($params);
	}

	public function call($env)
	{
		$this->auth = new BasicRequest($env);

		if(!$this->auth->is_provided()) return $this->unauthorized();
		if($this->is_valid($this->auth))
		{
			$this->env['REMOTE_USER'] = $this->auth->username();
			return $this->app->call($env);
		}
		return $this->unauthorized();
	}

	private function is_valid()
	{
		return call_user_func_array($this->authenticator,$this->auth->credentials());
	}

	private function realm()
	{
		return isset($this->realm)? " realm={$this->realm}" : "";
	}

	private function bad_request()
	{
		return array(400,array("Content-Type" => "text/plain","Content-Length" => "0"),array());
	}

	private function unauthorized()
	{
		return array(401,array("Content-Type" => "text/plain",
			"Content-Length" => "0",'WWW-Authenticate' => "Basic{$this->realm()}"),array());
	}
}

class BasicRequest
{
	protected $credentials, $env;

	public function __construct($env) { $this->env = $env; }

	public function is_provided()
	{
		return isset($this->env['PHP_AUTH_USER']);
	}

	public function credentials()
	{
		return array($this->env['PHP_AUTH_USER'],$this->env['PHP_AUTH_PW']);
	}

	public function username()
	{
		return $this->env['PHP_AUTH_USER'];
	}
}