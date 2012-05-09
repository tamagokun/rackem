<?php
namespace Rackem\Protection;

class FormToken extends \Rackem\Protection\AuthenticityToken
{
	public function accepts($env)
	{
		return $env['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' || parent::accepts($env);
	}
}