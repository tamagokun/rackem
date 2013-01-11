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
		if($this->is_valid())
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
	protected $credentials, $env, $key, $parts, $scheme, $params;

	public function __construct($env) { $this->env = $env; }

	public function is_provided()
	{
		return $this->authorization_keys();
	}

	public function credentials()
	{
		if($this->key == 'PHP_AUTH_USER')
			$credentials = "{$this->env['PHP_AUTH_USER']}:{$this->env['PHP_AUTH_PW']}";
		else
			$credentials = base64_decode($this->params());
		$this->credentials = split(':', $credentials, 2);
		return $this->credentials;
	}

	public function scheme()
	{
		$parts = $this->parts();
		$this->scheme = strtolower($parts[0]);
		return $this->scheme;
	}

	public function params()
	{
		$parts = $this->parts();
		$this->params = $parts[count($parts) - 1];
		return $this->params;
	}

	public function parts()
	{
		$this->parts = explode(' ', $this->env[$this->key], 2);
		return $this->parts;
	}

	public function username()
	{
		return $this->env['PHP_AUTH_USER'];
	}

/* private */
	private function authorization_keys()
	{
		$keys = array('PHP_AUTH_USER','HTTP_AUTHORIZATION','X-HTTP_AUTHORIZATION','X_HTTP_AUTHORIZATION');	
		foreach($keys as $key)
			if(isset($this->env[$key])) $this->key = $key;
		return $this->key;
	}
}
