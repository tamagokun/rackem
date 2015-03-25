<?php
namespace Rackem;

class Exception extends \ErrorException
{
	public $status, $header, $body;

	public function __construct($status, $header, $body)
	{
		$this->status = $status;
		$this->header = $header;
		$this->body = $body;
		parent::__construct('Rackem Exception');
	}

	public function finish()
	{
		return array($this->status,$this->header,$this->body);
	}
}
