<?php
namespace Rackem;

class RackLogger
{
	protected $app,$level;

	public function __construct($app, $level = \Rackem\Logger::INFO)
	{
		$this->app = $app;
		$this->level = empty($level)? \Rackem\Logger::INFO : $level;
	}

	public function call($env)
	{
		$logger = new \Rackem\Logger($env['rack.errors']);
		$logger->level = $this->level;

		$env['rack.logger'] = $logger;
		return $this->app->call($env);
	}
}