<?php
namespace Rackem;

class Cookie extends Id
{
	public $secret,$coder;

	public function __construct($app, $options=array())
	{
		$this->secret = isset($options["secret"])? $options["secret"] : "";
		$this->coder = isset($options["coder"])? $options["coder"] : "base64";
		parent::__construct($app,$options);
	}

	public function set_cookie($env, $headers, $cookie)
	{

	}

	public function set_session($env, $session_id, $session, $options)
	{

	}

	public function destroy_session($env, $session_id, $options)
	{
		
	}
}