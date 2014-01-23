<?php
namespace Rackem;

class RackLogger extends Middleware
{
    public function call($env)
    {
        $env['rack.logger'] = new \Rackem\Logger($env['rack.errors']);
        return $this->app->call($env);
    }
}
