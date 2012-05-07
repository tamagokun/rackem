<?php
namespace Rackem\Protection;

class XSSHeader extends \Rackem\Protection
{
	public function header()
	{
		return array('X-XSS-Protection' => "1; mode=block");
	}
	
	public function call($env)
	{
		list($status,$headers,$body) = $this->app->call($env);
		return array($status,array_merge($headers? $headers : array(),$this->header()),$body);
	}
}