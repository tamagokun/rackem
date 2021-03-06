<?php
namespace Rackem;

class Response implements \ArrayAccess
{
	public $status, $header, $body;

	public function __construct($body=array(), $status=200, $header=array())
	{
		if(func_num_args() == 1 && is_array(func_get_arg(0)))
			list($status, $header, $body) = func_get_arg(0);
		$this->status = $status;
		$this->header = array_merge(array("Content-Type"=>"text/html"),$header);

		$this->body = array();
		$this->write($body);
	}

	public function body()
	{
		return $this->__toString();
	}

	public function finish()
	{
		if(in_array($this->status, array(204,205,304)))
		{
			unset($this->header["Content-Type"]);
			unset($this->header["Content-Length"]);
			return array($this->status, $this->header, array());
		}

		$this->header["Content-Length"] = strlen($this);
		return array($this->status, $this->header, $this->body);
	}

	public function redirect($target, $status=302)
	{
		$this->status = $status;
		$this->header["Location"] = $target;
	}

	public function send($args)
	{
		$args = (is_array($args))? $args : func_get_args();
		foreach($args as $arg)
		{
			if(is_int($arg)) $this->status = $arg;
			elseif(is_array($arg) && array_keys($arg) !== array_keys(array_keys($arg)))
				$this->header = array_merge($this->header,$arg);
			elseif(is_array($arg) || is_string($arg)) $this->write($arg);
		}
	}

	public function write($value)
	{
		if(is_array($value))
		{
			foreach($value as $piece) $this->write($piece);
			return;
		}
		$this[] = $value;
	}

	public function set_cookie($key,$value=array())
	{
		$this->header = Utils::set_cookie_header($this->header,$key,$value);
	}

	public function delete_cookie($key,$value=array())
	{
		$this->header = Utils::delete_cookie_header($this->header,$key,$value);
	}

	public function __toString()
	{
		return implode("", $this->body);
	}

// ArrayAccess
	public function offsetSet($offset, $value)
	{
		if(is_null($offset))
			$this->body[] = $value;
		else
			$this->body[$offset] = $value;
	}

	public function offsetExists($offset)
	{
		return isset($this->body[$offset]);
	}

	public function offsetUnset($offset)
	{
		unset($this->body[$offset]);
	}
	public function offsetGet($offset)
	{
		return isset($this->body[$offset]) ? $this->body[$offset] : null;
	}
}
