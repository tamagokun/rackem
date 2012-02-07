<?php
namespace Rackem;

class Request
{
	public $env;
	
	public function __construct($env = array())
	{
		$this->env = $env;
	}
	
	public function body()
	{
		return $this->env["rack.input"];
	}
	
	public function content_type()
	{
		return (isset($this->env["CONTENT_TYPE"]))? $this->env["CONTENT_TYPE"] : null;
	}
	
	public function media_type()
	{
		return $this->content_type() && strtolower(array_shift(split("/\s*[;,]\s*/",2,$this->content_type())));
	}
	
	public function media_type_params()
	{
		if( is_null($this->content_type()) ) return array();
		//TODO	
	}
	
	public function content_charset()
	{
		$params = $this->media_type_params();
		return (isset($params['charset']))? $params['charset'] : null;
	}
	
	public function scheme()
	{
		return $this->env["rack.url_scheme"];
	}
	
	public function ssl()
	{
		return $this->scheme() == "https";
	}
	
}