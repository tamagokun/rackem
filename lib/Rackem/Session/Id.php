<?php
namespace Rackem;

abstract class Id
{
	public static $default_options = array(
		"key" 			=> "rack.session",
		"path"			=> "/",
		"domain"		=> null,
		"expire_after"	=> null,
		"secure"		=> false,
		"httponly"		=> true,
		"defer"			=> false,
		"renew"			=> false,
		"sidbits"		=> 128,
		"cookie_only"	=> true,
		"secure_random"	=> ""
	);

	public $app,$default_options,$key;

	public function __construct($app, $options = array())
	{
		$this->app = $app;
		$this->default_options = array_merge(Id::$default_options,$options);
		$this->key = isset($options["key"])? $options["key"] : "rack.session";
	}

	public function call($env)
	{

	}

	public function context()
}