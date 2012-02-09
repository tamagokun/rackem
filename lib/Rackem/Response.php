<?php
namespace Rackem;

class Response
{
	public $status,$header,$body;
	
	public function __construct($body=array(), $status=200, $header=array())
	{
		$this->status = $status;
		$this->header = array_merge(array("Content-Type"=>"text/html"),$header);
		
		$this->body = array();
		if(is_string($body)) 
			$this->write($body);
		else
			foreach($body as $part) $this->write($part);
	}
	
	public function finish()
	{
		if(in_array($this->status, array(204,205,304)))
		{
			unset($this->header["Content-Type"]);
			unset($this->header["Content-Length"]);
		}else
		{
			$this->header["Content-Length"] = strlen(implode("",$this->body));
		}
		return array($this->status, $this->header, $this->body);
	}
	
	public function write($value)
	{
		$this->body[] = $value;
	}
}