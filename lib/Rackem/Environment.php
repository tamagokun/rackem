<?php
namespace Rackem;

class Environment implements \ArrayAccess
{
	private $arr;
	
	public function __construct($arr = array())
	{
		$this->arr = $arr;
	}
	
	public function offsetExists($offset)
	{
		return isset($this->arr[$offset]);
	}
	
	public function offsetGet($offset)
	{
		return isset($this->arr[$offset])? $this->arr[$offset] : null;
	}
	
	public function offsetSet($offset, $value)
	{
		if(is_null($offset))
			$this->arr[] = $value;
		else
			$this->arr[$offset] = $value;
	}
	
	public function offsetUnset($offset)
	{
		unset($this->arr[$offset]);
	}
}