<?php
namespace Rackem\Protection;

class JsonCsrf extends \Rackem\Protection
{
	public function call($env)
	{
		list($status,$headers,$body) = $this->app->call($env);
		$content_type = isset($headers['Content-Type'])? explode(';',$headers['Content-Type'],2) : array('');
		if(preg_match('/^\s*application\/json\s*$/',array_shift($content_type)) !== false)
		{
			$req = new \Rackem\Request($env);
			if($this->referrer($env) != $req->host())
			{
				$result = $this->react($env);
				$this->warn($env,"attack prevented by ".get_class($this));
			}
		}
		return isset($result)? $result : array($status,$headers,$body);
	}
}