<?php

class Rackem
{
	public static $handler = null;
	protected static $builder = null;

	public static function map($path, $app)
	{
		self::ensure_builder();
		self::$builder->map($path, $app);
	}

	public static function run($app = null)
	{
		self::ensure_builder();
		self::$builder->run($app);

    if(!isset($_SERVER['rackem_handler'])) self::$handler = new \Rackem\Handler\Sapi();
    else
    {
      if($_SERVER['rackem_handler'] == "ruby") self::$handler = new \Rackem\Handler\Ruby();
      if($_SERVER['rackem_handler'] == "rackem") self::$handler = new \Rackem\Handler\Rackem();
    }

		return self::$handler->run(self::$builder);
	}

	public static function use_middleware($middleware, $options = array())
	{
		self::ensure_builder();
		self::$builder->use_middleware($middleware, $options);
	}

	public static function version()
	{
		return array(0,4,5);
	}

/* private */
	protected static function ensure_builder()
	{
		if(!self::$builder) self::$builder = new Rackem\Builder();
	}
}
