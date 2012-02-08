<?php
namespace Rackem;

class Request
{
	const FORM_DATA_MEDIA_TYPES = array(
		'application/x-www-form-urlencoded',
		'multipart/form-data'
	);
	const PARSEABLE_DATA_MEDIA_TYPES = array(
		'multipart/related',
		'multipart/mixed'
	);
	
	public $env;
	
	public function __construct($env = array())
	{
		$this->env = $env;
	}
	
	public function base_url()
	{
		$url = "{$this->scheme()}://{$this->host()}";
		if($this->scheme() == "https" && $this->port() != 443 || $this->scheme() == "http" && $this->port() !=80)
			$url .= ":{$this->port()}";
		return $url;
	}
	
	public function body()
	{
		return $this->env["rack.input"];
	}
	
	public function content_type()
	{
		return (isset($this->env["CONTENT_TYPE"]))? $this->env["CONTENT_TYPE"] : null;
	}
	
	public function form_data()
	{
		return ($this->env["REQUEST_METHOD"] == "POST");
	}
	
	public function fullpath()
	{
		return (empty($this->query_string()))? $this->path() : "{$this->path()}?{$this->query_string()}";
	}
	
	public function get()
	{
		if($this->env["rack.request.query_string"] == $this->query_string())
			return $this->env["rack.request.query_hash"];
		$this->env["rack.request.query_string"] = $this->query_string();
		$this->env["rack.request.query_hash"] = $this->parse_query($this->query_string());
		return $this->env["rack.request.query_hash"];
	}
	
	public function media_type()
	{
		return $this->content_type() && strtolower(array_shift(split("/\s*[;,]\s*/",$this->content_type(),2)));
	}
	
	public function media_type_params()
	{
		if( is_null($this->content_type()) ) return array();
		$params = array();
		return array_map(function($p) use (&$params) {
			list($k,$v) = split("=",$p,2);
			$params[strtolower($k)] = $v;
		}, array_slice(split("/\s*[;,]\s*/",$this->content_type()),1);
		return $params;
	}
	
	public function content_charset()
	{
		$params = $this->media_type_params();
		return (isset($params['charset']))? $params['charset'] : null;
	}
	
	public function params()
	{
		return array_merge($this->get(),$this->post());
	}
	
	public function parseable_data()
	{
		return in_array($this->media_type(), Request::PARSEABLE_DATA_MEDIA_TYPES);
	}
	
	public function path()
	{
		return $this->env["SCRIPT_NAME"] . $this->env["PATH_INFO"];
	}
	
	public function port()
	{
		return $this->env["SERVER_PORT"];
	}
	
	public function post()
	{
		if(is_null($this->env["rack.input"])) return array();
		if($this->env["rack.request.form_input"] == $this->env["rack.input"])
			return $this->env["rack.request.form_hash"];
		if($this->form_data() || $this->parseable_form_data())
		{
			$this->env["rack.request.form_input"] = $this->env["rack.input"];
			if(! $this->env["rack.request.form_hash"] = $this->parse_multipart($this->env))
			{
				$form_vars = fread($this->env["rack.input"]);
				str_replace("/\0\z/",$form_vars);
				
				$this->env["rack.request.form_vars"] = $form_vars;
				$this->env["rack.request.form_hash"] = $this->parse_query($form_vars);
				rewind($this->env["rack.input"]);
			}
		}
		return $this->env["rack.request.form_hash"];
	}
	
	public function query_string()
	{
		return $this->env["QUERY_STRING"];
	}
	
	public function scheme()
	{
		return $this->env["rack.url_scheme"];
	}
	
	public function ssl()
	{
		return $this->scheme() == "https";
	}
	
	public function url()
	{
		return "{$this->base_url()}{$this->fullpath()}";
	}
	
	public function user_agent()
	{
		return $this->env["HTTP_USER_AGENT"];
	}
	
	//private
	private function parse_query($qs)
	{
		return Utils::parse_nested_query($qs);
	}
	
	private function parse_multipart($env)
	{
		return null;
		//TODO: \Rackem\Multipart
	}
	
}