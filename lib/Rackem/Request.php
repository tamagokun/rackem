<?php
namespace Rackem;

class Request
{
	public $env;
	
	protected $form_data_media_types = array(
		'application/x-www-form-urlencoded',
		'multipart/form-data'
	);
	protected $parseable_data_media_types = array(
		'multipart/related',
		'multipart/mixed'
	);
	
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
	
	public function content_length()
	{
		return $this->env["CONTENT_LENGTH"];
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
		$query_string = $this->query_string();
		return empty($query_string)? $this->path() : "{$this->path()}?{$this->query_string()}";
	}
	
	public function get()
	{
		if($this->env["rack.request.query_string"] == $this->query_string())
			return $this->env["rack.request.query_hash"];
		$this->env["rack.request.query_string"] = $this->query_string();
		$this->env["rack.request.query_hash"] = $this->parse_query($this->query_string());
		return $this->env["rack.request.query_hash"];
	}
	
	public function host()
	{
		return str_replace(":\d+\z","",$this->host_with_port());
	}
	
	public function host_with_port()
	{
		if(isset($this->env["HTTP_X_FORWARDED_HOST"]))
			return array_pop(split(",\s?",$this->env["HTTP_X_FORWARDED_HOST"]));
		return (isset($this->env["HTTP_HOST"]))? $this->env["HTTP_HOST"] : "{$this->env["SERVER_ADDR"]}:{$this->env["SERVER_PORT"]}";
	}
	
	public function is_delete() { return $this->request_method() == "DELETE"; }
	public function is_get() { return $this->request_method() == "GET"; }
	public function is_head() { return $this->request_method() == "HEAD"; }
	public function is_options() { return $this->request_method() == "OPTIONS"; }
	public function is_patch() { return $this->request_method() == "PATCH"; }
	public function is_post() { return $this->request_method() == "POST"; }
	public function is_put() { return $this->request_method() == "PUT"; }
	public function is_trace() { return $this->request_method() == "TRACE"; }
	
	public function is_xhr() 
	{ 
		return isset($this->env["HTTP_X_REQUESTED_WITH"]) && $this->env["HTTP_X_REQUESTED_WITH"] == "XMLHttpRequest";
	}
	
	public function media_type()
	{
		return (is_null($this->content_type()))? null:strtolower(array_shift(split("\s*[;,]\s*",$this->content_type(),2)));
	}
	
	public function media_type_params()
	{
		if( is_null($this->content_type()) ) return array();
		$params = array();
		return array_map(function($p) use (&$params) {
			list($k,$v) = split("=",$p,2);
			$params[strtolower($k)] = $v;
		}, array_slice(split("\s*[;,]\s*",$this->content_type()),1));
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
		return in_array($this->media_type(), $this->parseable_data_media_types);
	}
	
	public function path()
	{
		//TODO: What the hell is SCRIPT_NAME ?
		//return $this->env["SCRIPT_NAME"] . $this->env["PATH_INFO"];
		return $this->env["PATH_INFO"];
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
		if($this->form_data() || $this->parseable_data())
		{
			$this->env["rack.request.form_input"] = $this->env["rack.input"];
			if(! $this->env["rack.request.form_hash"] = $this->parse_multipart($this->env))
			{
				$form_vars = str_replace("\0\z","",stream_get_contents($this->env["rack.input"]));
				
				$this->env["rack.request.form_vars"] = $form_vars;
				//$this->env["rack.request.form_hash"] = $this->parse_query($form_vars);
				$this->env["rack.request.form_hash"] = &$_POST;
			}
		}
		return $this->env["rack.request.form_hash"];
	}
	
	public function query_string()
	{
		return $this->env["QUERY_STRING"];
	}
	
	public function request_method()
	{
		return $this->env["REQUEST_METHOD"];
	}
	
	public function session()
	{
		return $this->env["rack.session"];
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
		return $_FILES;
	}
	
}