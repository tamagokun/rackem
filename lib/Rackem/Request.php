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
		return isset($this->env["CONTENT_LENGTH"])? $this->env["CONTENT_LENGTH"] : null;
	}

	public function content_type()
	{
		return isset($this->env["CONTENT_TYPE"])? $this->env["CONTENT_TYPE"] : null;
	}

	public function cookies($key=null)
	{
		$hash = isset($this->env["rack.request.cookie_hash"])? $this->env["rack.request.cookie_hash"] : array();
		$string = isset($this->env["HTTP_COOKIE"])? $this->env["HTTP_COOKIE"] : "";
		if(!$string) $hash = array();
		if(isset($this->env["rack.request.cookie_string"]) && $string == $this->env["rack.request.cookie_string"])
			return $hash;
		
		foreach(Utils::parse_query($string,";,") as $k=>$v)
			$hash[$k] = is_array($v)? array_shift($v) : $v;
		$this->env["rack.request.cookie_string"] = $string;
		$this->env["rack.request.cookie_hash"] = $hash;
		return $hash;
	}

	public function form_data()
	{
		$available_methods = array("POST", "PUT", "PATCH");
		$method = $this->env["REQUEST_METHOD"];
		return in_array($method, $available_methods);
	}

	public function fullpath()
	{
		$query_string = $this->query_string();
		return empty($query_string)? $this->path() : "{$this->path()}?{$this->query_string()}";
	}

	public function get()
	{
		if(isset($this->env["rack.request.query_string"]) && $this->env["rack.request.query_string"] == $this->query_string())
			return $this->env["rack.request.query_hash"];
		$this->env["rack.request.query_string"] = $this->query_string();
		$this->env["rack.request.query_hash"] = $this->parse_query($this->query_string());
		return $this->env["rack.request.query_hash"];
	}

	public function host()
	{
		return preg_replace('/:\d+\z/',"",$this->host_with_port());
	}

	public function host_with_port()
	{
		if(isset($this->env["HTTP_X_FORWARDED_HOST"]))
			return array_pop(split(",\s?",$this->env["HTTP_X_FORWARDED_HOST"]));
		return (isset($this->env["HTTP_HOST"]))? $this->env["HTTP_HOST"] : "{$this->env["SERVER_NAME"]}:{$this->env["SERVER_PORT"]}";
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

	public function logger()
	{
		return $this->env['rack.logger'];
	}

	public function media_type()
	{
		if(is_null($this->content_type())) return null;
		$media_type = preg_split('/\s*[;,]\s*/', $this->content_type(), 2);
		return strtolower(array_shift($media_type));
	}

	public function media_type_params()
	{
		if( is_null($this->content_type()) ) return array();
		$params = array();
		$media_type_params = preg_split('/\s*[;,]\s*/', $this->content_type());
		array_map(function($p) use (&$params) {
			list($k,$v) = explode("=",$p,2);
			$params[strtolower($k)] = $v;
		}, array_slice($media_type_params, 1));
		return $params;
	}

	public function content_charset()
	{
		$params = $this->media_type_params();
		return isset($params['charset'])? $params['charset'] : null;
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
		return $this->env["SCRIPT_NAME"] . $this->path_info();
	}

	public function path_info()
	{
		return $this->env["PATH_INFO"];
	}

	public function port()
	{
		return intval($this->env["SERVER_PORT"]);
	}

	public function post()
	{
		if(is_null($this->env["rack.input"])) return array();
		if(isset($this->env["rack.request.form_input"]) && $this->env["rack.request.form_input"] == $this->env["rack.input"])
			return $this->env["rack.request.form_hash"];
		if($this->form_data() || $this->parseable_data())
		{
			$this->env["rack.request.form_input"] = $this->env["rack.input"];
			$form_vars = str_replace("\0\z","",stream_get_contents($this->env["rack.input"]));
			$this->env["rack.request.form_vars"] = $form_vars;
			if($this->media_type() == "application/json")
			{
				$this->env["rack.request.form_hash"] = json_decode($form_vars, true);
			}else
			{
				$this->env["rack.request.form_hash"] = $this->parse_multipart($form_vars);
			}
		}else
			$this->env["rack.request.form_hash"] = array();
		return $this->env["rack.request.form_hash"];
	}

	public function query_string()
	{
		return $this->env["QUERY_STRING"];
	}

	public function referer()
	{
		return isset($this->env["HTTP_REFERER"])? $this->env["HTTP_REFERER"] : null;
	}

	public function request_method()
	{
		return $this->env["REQUEST_METHOD"];
	}

	public function session($key=null)
	{
		return is_null($key)? $this->env["rack.session"] : $this->env["rack.session"][$key];
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
		parse_str(urldecode($qs), $data);
		return $data;
	}

	private function parse_multipart($d)
	{
		if(!empty($_POST) || !empty($_FILES)) return array_merge($_POST, $_FILES);
		return Utils::parse_form_data($d, $this->content_type());
	}
}
