# Rack for PHP

## Usage

Any object that has a callable method `call()` can be considered a Rack application. Rack expects call to return an HTTP response array containing: status code, headers, and body.

Here is an example of a basic Rack application:

    <?php
    require "autoload.php";
    
    class App
    {
	    public function call(&$env)
	    {
		    return array(200,array('Content-Type'=>'text/html'),array('Hello World!'));
	    }
    }
    
    \Rackem\Rack::run("App");

`Rack::run()` accepts 1 of 3 things:

 - String referencing a Class
 - Class instance
 - Closure
 
Here would be an example of using a Closure:

    $app = function(&$env) {
    	return array(200,array('Content-Type'=>'text/html'),array('Hello World!'));
    };
    \Rackem\Rack::run($app);

## Middleware

Just like Rack, Rackem supports the use of Middleware. Middleware is basically a Rack application that must be passed `$app` in its constructor, with an optional `$options` parameter. `$app` is an instance of the previous application in the Rack stack.

Here is an example of a Middleware class that just passes the response on:

    <?php
    
    class MyMiddleware
    {
    	public function __construct($app)
    	{
    		$this->app = $app;
    	}
    	
    	public function call(&$env)
    	{
    		return $this->app->call($env);
    	}
    }
    
    \Rackem\Rack::use_middleware("MyMiddleware");
    \Rackem\Rack::run( new App() );

