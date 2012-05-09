<?php
namespace Rackem\Protection;

class RemoteToken extends \Rackem\Protection\AuthenticityToken
{
	public function accepts($env)
	{
		$req = new \Rackem\Request($env);
		return parent::accepts($env) || $this->referrer($env) == $req->host();
	}
}