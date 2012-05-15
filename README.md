# Rack for PHP

## Setup

Add rackem/rackem to your `composer.json`

```json
{
  "require": {
	  "rackem/rackem": "dev-master"
	}
}
```

Install

```shell
$ composer install
```

Autoload

```php
require 'vendor/autoload.php'
```

Rackem also supports PSR-0 autoloading by using `require 'rackem.php';`

## Usage

Any object that has a callable method `call()` can be considered a Rack application. Rack expects call to return an HTTP response array containing: status code, headers, and body.

Here is an example of a basic Rackem application:

```php
<?php
require "rackem.php";

  class App
  {
	public function call($env)
	{
	  return array(200,array('Content-Type'=>'text/html'),array('Hello World!'));
	}
  }

  \Rackem\Rack::run("App");
```

`Rack::run()` accepts 1 of 3 things:

 - String referencing a Class
 - Class instance
 - Closure

Here would be an example of using a Closure:

```php
$app = function($env) {
  return array(200,array('Content-Type'=>'text/html'),array('Hello World!'));
};
\Rackem\Rack::run($app);
```

## Middleware

Just like Rack, Rackem supports the use of Middleware. Middleware is basically a Rack application that must be passed `$app` in its constructor, with an optional `$options` parameter. `$app` is an instance of the previous application in the Rack stack.

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
\Rackem\Rack::run( new App() );
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

## What it has

 - run apps using `\Rackem\Rack::run("MyApp")`
 - stack some middleware on it `\Rackem\Rack::use_middleware("MyMiddleware");`
 - Request and Response objects for all sorts of awesome.
 - Rack compatible logger for logging to streams (STDERR, files, etc)
 - RubyRack class + config.ru for serving Rackem apps via Ruby web servers!! [Thanks to creationix](https://github.com/creationix/rack-php)
 - Mime class for file type detection
 - Exceptions middleware for handling exceptions
 - File middleware for serving files
 - Basic authentication middleware
 - Session handling
 - Protection middlewares to prevent attacks (csrf,xss,clickjacking,ip spoofing,dir traversal,session hijacking)

## What it needs

 - specs!
