<?php
namespace Rackem;

abstract class Id
{
	const ENV_SESSION_KEY = "rack.session";
	const ENV_SESSION_OPTIONS_KEY = "rack.session.options";

	public static $default_options = array(
		"key" 			=> "rack.session",
		"path"			=> "/",
		"domain"		=> null,
		"expire_after"	=> null,
		"secure"		=> false,
		"httponly"		=> true,
		"defer"			=> false,
		"renew"			=> false,
		"sidbits"		=> 128,
		"cookie_only"	=> true,
		"secure_random"	=> ""
	);

	public $app,$default_options,$key;

	public function __construct($app, $options = array())
	{
		$this->app = $app;
		$this->default_options = array_merge(Id::$default_options,$options);
		$this->key = isset($options["key"])? $options["key"] : "rack.session";
	}

	public function call($env)
	{
		return $this->context($env);
	}

	public function context($env,$app=null)
	{
		if(is_null($app)) $app = $this;
		$this->prepare_session($env);
		list($status,$headers,$body) = $app->call($env);
		return $this->commit_session($env,$status,$headers,$body);
	}

	public function generate_sid($secure)
	{
		if(!$secure) $secure = $this->default_options['secure_random'];
		if($secure)
			return "";
		else return "";
	}

	public function prepare_session($env)
	{
		$session_was = isset($env[self::ENV_SESSION_KEY])? $env[self::ENV_SESSION_KEY] : null;
		$env[self::ENV_SESSION_KEY] = array();
		$env[self::ENV_SESSION_OPTIONS_KEY] = $this->default_options;
		if($session_was)
			$env[self::ENV_SESSION_KEY] = array_merge($session_was,$env[self::ENV_SESSION_KEY]);
	}

	public function load_session($env)
	{
		$sid = $this->current_session_id($env);
		list($sid,$session) = $this->get_session($env,$sid);
		return array($sid, $session);
	}

	public function extract_session_id($env)
	{
		$request = new Request($env);
		$sid = $request->cookies[$this->key];
		if(!$this->cookie_only && isset($request->params->{$this->key}))
			$sid = $request->params->{$this->key};
		return $sid;
	}

	public function current_session_id($env)
	{
		return $env[self::ENV_SESSION_OPTIONS_KEY]["id"];
	}

	public function session_exists($env)
	{
		$value = $this->current_session_id($env);
		return $value && !empty($value);
	}

	public function commit_session($env,$status,$headers,$body)
	{
		$session = $env['rack.session'];
		$options = $env['rack.session.options'];

		$id = isset($options["id"])? $options["id"] : $this->generate_sid();
		if($options["drop"] || $options["renew"])
		{
			$session_id = $this->destroy_session($env,$id,$options);
			if(!$session_id) return array($status,$headers,$body);
		}
		if(!$data = $this->set_session($env,$id,$session,$options))
			fwrite($env['rack.errors'],"Warning! Failed to save session. Content dropped.");
		elseif($options["defer"] && !$options["renew"])
			fwrite($env['rack.errors'],"Defering cookie for $id");
		else
		{
			$expiration = $options["expires_after"]? time() + $options["expires_after"] : null;
			$cookie = array("value" => $data,"expires" => $expiration;);
			$this->set_cookie($env,$headers,array_merge($options,$cookie));
		}
		return array($status,$headers,$body);
	}

	public function set_cookie($env,$headers,$cookie)
	{
		$request = new Request($env);
		if($request->cookies[$this->key] !== $cookie["value"] || $cookie["expires"])
			Utils::set_cookie_header($headers,$this->key,$cookie);
	}
}