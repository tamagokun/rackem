<?php
namespace Rackem\Handler;

class Ruby
{
	public function run($app)
	{
		$env = $this->env();

		ob_start();
		list($status, $headers, $body) = $app->call($env);
		$output = ob_get_clean();
		if($output) $headers['X-Output'] = json_encode($output);

		foreach($headers as $k=>&$v) if(is_numeric($v)) $v = (string)$v;
		$headers = json_encode($headers);
		$body = implode("",$body);
		exit(implode("\n",array($status,$headers,$body)));
	}

	public function env()
	{
		$env = json_decode(file_get_contents('php://stdin'), true);
		return new \ArrayObject($env);
	}
}
