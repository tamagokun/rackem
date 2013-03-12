<?php
namespace Rackem;

class Rack
{
	public static function map($path, $app)
	{
		echo "DEPRECATED: Please use \Rackem::map()\n";
		return \Rackem::map($path, $app);
	}

	public static function run($app = null)
	{
		echo "DEPRECATED: Please use \Rackem::run()\n";
		return \Rackem::run($app);
	}

	public static function use_middleware($middleware,$options = array())
	{
		echo "DEPRECATED: Please use \Rackem::use_middleware()\n";
		return \Rackem::use_middleware($middleware, $options);
	}
}
