# Rack for PHP

Rack'em is an attempt to provide the awesomeness that Rack has brought Ruby, to PHP.

## Getting Started

Rack'em likes [Composer](http://getcomposer.org/), go ahead and install it isn't already.

I like to install Rack'em globally so that I can use it in any project. Unfortunately, Composer does not have a way of doing this by default, so here is an easy way to allow global package installation:

### Setting up Composer for global installtion

```bash
$ curl https://raw.github.com/gist/4242494/5d6344d2976e07d051ace18d41fa035113353e90/global_composer.sh | sh
```

### Installing Rack'em

If you are using the global installtion method from above, you can easily do:

```bash
$ cd ~/.composer && composer require rackem/rackem:dev-master
```

Otherwise, you need to add `rackem/rackem` to your project's composer.json:

```json
{
	"minimum-stability": "dev",
  "require": {
	  "rackem/rackem": "dev-master"
	}
}
```

There's also a shortcut to do this with Composer:

```bash
$ composer require rackem/rackem:dev-master
```

Optionally, there is a PSR-0 autoloader you can use:

```php
<?php
require 'rackem/rackem.php';
```

## rackem

rackem is a tool for running Rack'em applications without the need for a web server. This makes developing Rack applications with PHP a breeze.

Provide rackem your main application script, and you are good to go:

```bash
$ rackem config.php
== Rackem on http://0.0.0.0:9393
>> Rackem web server
>> Listening on 0.0.0.0:9393, CTRL+C to stop
```

## Usage

Any object that has a callable method `call()` can be considered a Rack application. Rack expects call to return an HTTP response array containing: status code, headers, and body.

Here is an example of a basic Rackem application:

```php
<?php

class App
{
  public function call($env)
  {
	return array(200,array('Content-Type'=>'text/html'),array('Hello World!'));
  }
}

return \Rackem\Rack::run("App");
```

`Rack::run()` accepts 1 of 3 things:

 - String referencing a Class
 - Class instance
 - Closure

Here would be an example of using a Closure:

```php
<?php
$app = function($env) {
  return array(200,array('Content-Type'=>'text/html'),array('Hello World!'));
};
return \Rackem\Rack::run($app);
```

## Middleware

Just like Rack, Rack'em supports the use of Middleware. Middleware is basically a Rack application that must be passed `$app` in its constructor, with an optional `$options` parameter. `$app` is an instance of the previous application in the Rack stack.

Here is an example of a Middleware class that just passes the response on:

```php
<?php

class MyMiddleware
{
  public function __construct($app)
  {
	$this->app = $app;
  }

  public function call($env)
  {
	return $this->app->call($env);
  }
}

\Rackem\Rack::use_middleware("MyMiddleware");
return \Rackem\Rack::run( new App() );
```

There is also a Middleware helper class to make things a bit easier:

```php
<?php

class MyMiddleware extends \Rackem\Middleware
{
  public function call($env)
  {
	return $this->app->call($env);
  }
}
```

## Mapping

You can route paths to applications easily:

```php
<?php

\Rackem\Rack::map("/hello", function($env) {
	return array(200, array("Content-Type"=>"text/html"), array("Hello from Rack'em!"));
});

\Rackem\Rack::map("/admin","MyAdminApp");

return \Rackem\Rack::run();
```

## Request and Response

```php
<?php

class JsonFormatter extends \Rackem\Middleware
{
  public function call($env)
  {
	$req = new \Rackem\Request($env);
	$res = new \Rackem\Response($this->app->call($env));
	
	if($req->params()->format == 'json')    //?format=json
	  $res->write(json_encode($res->body));
	return $res->finish();
  }
}
```

## What it has

 - run apps using `return \Rackem\Rack::run("MyApp")`
 - stack some middleware on it `\Rackem\Rack::use_middleware("MyMiddleware");`
 - Request and Response objects for all sorts of awesome.
 - Rack compatible logger for logging to streams (STDERR, files, etc)
 - RubyRack class + config.ru for serving Rackem apps via Ruby web servers
 - Mime class for file type detection
 - Exceptions middleware for handling exceptions
 - File middleware for serving files
 - Basic authentication middleware
 - Session handling
 - Protection middlewares to prevent attacks (csrf,xss,clickjacking,ip spoofing,dir traversal,session hijacking)
 - rackem for serving applications locally

## What it needs

 - specs

## Credits

[creationx](https://github.com/creationix/rack-php) for a Rack script that will serve a PHP-based Rack-compliant application.
