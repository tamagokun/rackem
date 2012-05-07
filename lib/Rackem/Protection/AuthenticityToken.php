<?php
namespace Rackem\Protection;

class AuthenticityToken extends \Rackem\Protection
{
	public function accepts($env)
	{
		if($this->is_safe($env)) return true;
		$session = $this->session($env);
		$token = isset($session['csrf.token'])? $session['csrf.token'] : false;
		if(!$token && isset($session['csrf'])) $token = $session['csrf'];
		if(!$token && isset($session['_csrf_token'])) $token = $session['_csrf_token'];
		if(isset($env['HTTP_X_CSRF_TOKEN']) && $env['HTTP_X_CSRF_TOKEN'] == $token) return true;
		$req = new \Rackem\Request($env);
		$params = $req->params();
		if(isset($params['authenticity_token']) && $params['authenticity_token'] == $token) return true;
		if(isset($params['_csrf']) && $params['_csrf'] == $token) return true;
		return false;
	}
}