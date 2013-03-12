<?php
namespace Rackem;

class Protection extends \Rackem\Middleware
{
	public $default_options = array(
		'reaction' => 'default_reaction', 'logging' => true,
		'message' => 'Forbidden', 'session_key' => 'rack.session',
		'status' => 403, 'allow_empty_referrer' => true
	);

	public static function protect($except = array(), $rackem = "\Rackem\Rack")
	{
		if(!in_array('frame_options',$except))     $rackem::use_middleware("\Rackem\Protection\FrameOptions");
		if(!in_array('ip_spoofing',$except))       $rackem::use_middleware("\Rackem\Protection\IPSpoofing");
		if(!in_array('json_csrf',$except))         $rackem::use_middleware("\Rackem\Protection\JsonCsrf");
		if(!in_array('path_traversal',$except))    $rackem::use_middleware("\Rackem\Protection\PathTraversal");
		if(!in_array('remote_token',$except))      $rackem::use_middleware("\Rackem\Protection\RemoteToken");
		if(!in_array('session_hijacking',$except)) $rackem::use_middleware("\Rackem\Protection\SessionHijacking");
		if(!in_array('xss_header',$except))        $rackem::use_middleware("\Rackem\Protection\XSSHeader");
	}

	public function __construct($app,$options=array())
	{
		$options = array_merge($options,$this->default_options);
		parent::__construct($app,$options);
	}

	public function is_safe($env)
	{
		return in_array($env['REQUEST_METHOD'],array('GET','HEAD','OPTIONS','TRACE'));
	}

	public function accepts($env) { return false; }

	public function call($env)
	{
		if(!$this->accepts($env))
		{
			$this->warn($env, "attack prevented by ".get_class($this));
			$result = $this->react($env);
			if($result) return $result;
		}
		return $this->app->call($env);
	}

	public function react($env)
	{
		return $this->deny($env);
	}

	public function warn($env, $message)
	{
		if(!$this->options['logging']) return;
		$l = isset($env['rack.logger']) && is_object($env['rack.logger'])? $env['rack.logger'] : new \Rackem\Logger($env['rack.errors']);
		$l->warn($message);
	}

	public function deny($env)
	{
		return array($this->options['status'], array('Content-Type'=>'text/plain'),array($this->options['message']));
	}

	public function has_session($env)
	{
		return isset($env[$this->options['session_key']]);
	}

	public function session($env)
	{
		if($this->has_session($env)) return $env[$this->options['session_key']];
	}

	public function drop_session($env)
	{
		if($this->has_session($env)) $env[$this->options['session_key']] = array();
	}

	public function referrer($env)
	{
		$ref = isset($env['HTTP_REFERER'])? $env['HTTP_REFERER'] : '';
		if(!$this->options['allow_empty_referrer'] && empty($ref)) return;
		$parts = parse_url($ref);
		if(isset($parts['host'])) return $parts['host'];
		$req = new \Rackem\Request($env);
		return $req->host();
	}

	public function encrypt($value)
	{
		return sha1($value);
	}
}
