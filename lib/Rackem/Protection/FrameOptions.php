<?php
namespace Rackem\Protection;

class FrameOptions extends \Rackem\Protection\XSSHeader
{
	public function header()
	{
		return array('X-Frame-Options' => "sameorigin");
	}
}