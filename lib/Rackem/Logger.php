<?php
namespace Rackem;

class Logger
{
	const FATAL = 5;
	const ERROR = 4;
	const WARN = 3;
	const INFO  = 2;
	const DEBUG = 1;

	public $level=2, $stream, $formatter, $datetime_format;

	public function __construct($stream)
	{
		$this->stream = is_string($stream)? fopen($stream, 'a') : $stream;
		$this->datetime_format = "D M d H:i:s Y";
		$this->formatter = function($severity, $datetime, $progname, $msg) {
			return "[$datetime] $severity -- $progname: $msg\n";
		};
	}

	public function close()
	{
		if(is_resource($this->stream)) fclose($this->stream);
	}

	public function info($msg) { return $this->log(Logger::INFO,func_get_args()); }
	public function debug($msg) { return $this->log(Logger::DEBUG,func_get_args()); }
	public function warn($msg) { return $this->log(Logger::WARN,func_get_args()); }
	public function error($msg) { return $this->log(Logger::ERROR,func_get_args()); }
	public function fatal($msg) { return $this->log(Logger::FATAL,func_get_args()); }

	//protected
	protected function log($level,$args)
	{
		$prog = "";
		$message = "";
		$block = null;
		foreach($args as $arg)
		{
			if(is_string($arg)) $message = $arg;
			if(is_callable($arg)) $block = $arg;
		}
		if($level < $this->level) return false;
		$formatter = $this->formatter;
		if(!is_null($block))
		{
			$prog = $message;
			$message = $block();
		}
		return fwrite($this->stream, $formatter($this->severity_label($level),@date($this->datetime_format),$prog,$message));
	}
	
	protected function severity_label($level)
	{
		$ref = new \ReflectionClass($this);
		foreach($ref->getConstants() as $key=>$value) if($value == $level) return $key;
		return "";
	}
}
