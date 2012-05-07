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
		if($env['HTTP_X_CSRF_TOKEN'] == $token) return true;
		$request = new \Rackem\Request($env);
		return $request->params['authenticity_token'] == $token;
	}
}