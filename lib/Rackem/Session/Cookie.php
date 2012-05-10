<?php
namespace Rackem\Session;

use \Rackem\Utils,\Rackem\Request;

class Cookie extends Id
{
	public $secret;

	public function __construct($app, $options=array())
	{
		$this->secret = isset($options["secret"])? $options["secret"] : "";
		parent::__construct($app,$options);
	}

	public function load_session($env)
	{
		$data = $this->unpacked_cookie_data($env);
		return array($data["session_id"], $data);
	}

	public function extract_session_id($env)
	{
		$data = $this->unpacked_cookie_data($env);
		return $data["session_id"];
	}

	public function unpacked_cookie_data($env,$sid = null)
	{
		if(isset($env["rack.session.unpacked_cookie_data"]))
			return $env["rack.session.unpacked_cookie_data"];
		$request = new Request($env);
		$session_data = $request->cookies($this->key);
		$session_data = isset($session_data[$this->key])? $session_data[$this->key] : null;
		if($this->secret && $session_data)
		{
			list($session_data,$digest) = explode("--",$session_data,2);
			if($digest !== $this->generate_hmac($session_data)) $session_data = null;
		}
		$env["rack.session.unpacked_cookie_data"] = unserialize(base64_decode($session_data));
		$env["rack.session.unpacked_cookie_data"]["session_id"] = ($sid)? $sid : $this->generate_sid();
		return $env["rack.session.unpacked_cookie_data"];
	}

	public function set_cookie($env, $header, $cookie)
	{
		return Utils::set_cookie_header($header, $this->key, $cookie);
	}

	public function set_session($env, $session_id, $session, $options)
	{
		$session = array_merge($session,array("session_id"=>$session_id));
		$session_data = base64_encode(serialize($session));
		if($this->secret)
			$session_data = "$session_data--{$this->generate_hmac($session_data)}";
		if(strlen($session_data) > (4096 - strlen($this->key)))
		{
			fwrite($env['rack.errors'],"Warning! Cookie data size exceeds 4K.");
			return null;
		}else
			return $session_data;
	}

	public function destroy_session($env, $session_id, $options)
	{
		if(isset($options["drop"]) && !$options["drop"]) $this->generate_sid();
	}

	protected function generate_hmac($data)
	{
		return hash_hmac("sha1",$data,$this->secret);
	}
}
