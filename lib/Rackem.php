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
        return self::handler()->run(self::$builder);
    }

    public static function use_middleware($middleware, $options = array())
    {
        self::ensure_builder();
        self::$builder->use_middleware($middleware, $options);
    }

    public static function version()
    {
        return array(0,4,13);
    }

    /* private */
    protected static function ensure_builder()
    {
        if (!self::$builder) self::$builder = new Rackem\Builder();
    }

    protected static function handler()
    {
        $name = isset($_SERVER['rackem_handler'])? $_SERVER['rackem_handler'] : false;
        if ($name == "ruby") return self::$handler = new \Rackem\Handler\Ruby();
        if ($name == "rackem") return self::$handler = new \Rackem\Handler\Rackem();
        // fallback to sapi since they won't be reporting which handler to use.
        return self::$handler = new \Rackem\Handler\Sapi();
    }
}
