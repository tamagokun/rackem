<?php
namespace Rackem\Protection;

class SessionHijacking extends \Rackem\Protection
{
	public $key = 'tracking';
	public $track = array('HTTP_USER_AGENT','HTTP_ACCEPT_ENCODING','HTTP_ACCEPT_LANGUAGE');

	public function accepts($env)
	{
		$session = $this->session($env);
		if(!$session) return true;
		if(array_key_exists($this->key,$session))
		{
			foreach($session[$this->key] as $k=>$v)
				if($v !== $this->encrypt($env[$k])) return false;
		}else
		{
			$session[$this->key] = array();
			foreach($this->track as $key) $session[$this->key][$key] = $this->encrypt(isset($env[$key])? $env[$key] :'');
			return true;
		}
	}
}
