<?php
namespace Rackem\Protection;

class IPSpoofing extends \Rackem\Protection
{
	public function accepts($env)
	{
		if(!isset($env['HTTP_X_FORWARDED_FOR'])) return true;
		$ips = preg_split('/\s*,\s*/',$env['HTTP_X_FORWARDED_FOR']);
		if(isset($env['HTTP_CLIENT_IP']) && !in_array($env['HTTP_CLIENT_IP'],$ips)) return false;
		if(isset($env['HTTP_X_REAL_IP']) && !in_array($env['HTTP_X_REAL_IP'],$ips)) return false;
		return true;
	}
}