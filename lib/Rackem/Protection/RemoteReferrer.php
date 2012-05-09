<?php
namespace Rackem\Protection;

class RemoteReferrer extends \Rackem\Protection
{
	public function accepts($env)
	{
		$req = new \Rackem\Request($env);
		return $this->is_safe($env) || $this->referrer($env) == $req->host();
	}
}