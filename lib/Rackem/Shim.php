<?php
namespace Rackem;

class Shim
{
	protected $block;
	
	public function __construct($block)
	{
		$this->block = $block;
	}
	
	public function call($env)
	{
		$block = $this->block;
		return $block($env);
	}
}