<?php
namespace Rackem\Session;

use \Rackem\Utils,\Rackem\Request;

abstract class Id
{
	const ENV_SESSION_KEY = "rack.session";
	const ENV_SESSION_OPTIONS_KEY = "rack.session.options";

	public static $options = array(
		"key"          => "rack.session",
		"path"         => "/",
		"domain"       => null,
		"expire_after" => null,
		"secure"       => false,
		"httponly"     => true,
		"drop"         => false,
		"defer"        => false,
		"renew"        => false,
		"sidbits"      => 128,
		"cookie_only"  => true
	);

	public $app,$default_options,$key,$cookie_only;
	protected $sid_length;

	public function __construct($app, $options = array())
	{
		$this->app = $app;
		$this->default_options = array_merge(Id::$options,$options);
		$this->key = $this->default_options["key"];
		$this->cookie_only = $this->default_options["cookie_only"];
		unset($this->default_options["key"]);
		unset($this->default_options["cookie_only"]);
		$this->sid_length = $this->default_options["sidbits"] / 4;
	}

	public function call($env)
	{
		return $this->context($env);
	}

	public function context($env,$app=null)
	{
		if(is_null($app)) $app = $this->app;
		$this->prepare_session($env);
		list($status,$headers,$body) = $app->call($env);
		return $this->commit_session($env,$status,$headers,$body);
	}

	public function generate_sid()
	{
		return Utils::random_hex($this->sid_length);
	}

	public function prepare_session($env)
	{
		$session_was = isset($env[self::ENV_SESSION_KEY])? $env[self::ENV_SESSION_KEY] : array();
		list($id,$session) = $this->load_session($env);
		$env[self::ENV_SESSION_KEY] = array_merge($session_was,$session);
		$env[self::ENV_SESSION_OPTIONS_KEY] = $this->default_options;
		$env[self::ENV_SESSION_OPTIONS_KEY]["id"] = $id;
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
		$sid = $request->cookies($this->key);
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
		$session = $env['rack.session']? $env['rack.session'] : array();
		$options = $env['rack.session.options']? $env['rack.session.options'] : array();

		if((isset($options["drop"]) && $options["drop"]) || (isset($options["renew"]) && $options["renew"]))
		{
			$id = isset($options["id"])? $options["id"] : $this->generate_sid();
			$session_id = $this->destroy_session($env,$id,$options);
			if(!$session_id) return array($status,$headers,$body);
		}

		if(isset($options["skip"]) && $options["skip"])
			return array($status,$headers,$body);
		$session_id = isset($options["id"])? $options["id"] : $this->generate_sid();

		if(!$data = $this->set_session($env,$session_id,$session,$options))
			fwrite($env['rack.errors'],"Warning! Failed to save session. Content dropped.");
		elseif(isset($options["defer"]) && $options["defer"] && !$options["renew"])
			fwrite($env['rack.errors'],"Defering cookie for $id");
		else
		{
			$expiration = isset($options["expire_after"])? time() + $options["expire_after"] : null;
			$cookie = array("value" => $data,"expires" => $expiration);
			$headers = $this->set_cookie($env,$headers,array_merge($options,$cookie));
		}
		return array($status,$headers,$body);
	}

	public function set_cookie($env,$headers,$cookie)
	{
		$request = new Request($env);
		if($request->cookies($this->key) !== $cookie["value"] || $cookie["expires"])
			return Utils::set_cookie_header($headers,$this->key,$cookie);
		return $headers;
	}
}
