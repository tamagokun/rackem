# Rack for PHP

Rack'em is an attempt to provide the awesomeness that Rack has brought Ruby, to PHP.

![](https://api.travis-ci.org/tamagokun/rackem.png?branch=master)

```php
<?php
# config.php
return \Rackem::run(function($env) {
	return array(200, array("Content-Type"=>"text/html"), array("Hello, from Rack'em!"));
});
```

```bash
$ vendor/bin/rackem
$ open http://localhost:9393
```

![](https://raw.github.com/tamagokun/rackem/master/hello-world.png)

## Features

* Tiny
* Provides a common interface for applications
* Environment values are consistent regardless of web server
* Run applications locally without other dependencies

## Getting Started

Rack'em likes [Composer](http://getcomposer.org/), go ahead and install it if it isn't already.

### Installing Rack'em

Installing with Composer is the way to go:

```bash
$ composer require rackem/rackem:@stable
```

Installing globally is awesome:

```bash
$ composer global require rackem/rackem:@stable
```

Optionally, download Rack'em and require rackem.php:

```php
<?php
require 'rackem/rackem.php';
```

## rackem

rackem is a HTTP server for running Rack'em applications. This makes developing PHP applications a breeze.

Provide rackem your main application script, and you are good to go:

```bash
$ rackem config.php
== Rackem on http://0.0.0.0:9393
>> Rackem web server
>> Listening on 0.0.0.0:9393, CTRL+C to stop
```

## Usage

Anything that `is_callable()` or has an instance method `call()` can be considered an application. The application must return an HTTP response array containing: status code, headers, and body.

Here is an example of a basic Rack'em application:

```php
<?php

class App
{
	public function call($env)
	{
		return array(200,array('Content-Type'=>'text/html'),array('Hello World!'));
	}
}

return \Rackem::run("App");
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
return \Rackem::run($app);
```

## Middleware

Fill your rack with middleware for ultimate awesomeness.

Middleware is basically an application that is passed the previous application in the stack and optionally an array of options in its constructor.

The most basic middleware (hint: it doesn't do anything):

```php
<?php

class MyMiddleware
{
	public $app, $options;

	public function __construct($app, $options = array())
	{
		$this->app = $app;
		$this->options = $options;
	}

	public function call($env)
	{
		return $this->app->call($env);
	}
}

\Rackem::use_middleware("MyMiddleware");
return \Rackem::run( new App() );
```

There is also of course a helper class to make things a bit easier:

```php
<?php

class MyMiddleware extends \Rackem\Middleware
{
	public function call($env)
	{
		// do stuff
		return parent::call($env);
	}
}
```

## Mapping

You can route paths to applications easily:

```php
<?php

\Rackem::map("/hello", function($env) {
	return array(200, array("Content-Type"=>"text/html"), array("Hello from Rack'em!"));
});

\Rackem::map("/admin","MyAdminApp");

return \Rackem::run();
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

 - run apps using `\Rackem::run`
 - stack some middleware on it `\Rackem::use_middleware`
 - Request and Response objects for helping out.
 - Rack compatible logger for logging to streams (STDERR, files, etc)
 - RubyRack class + config.ru for serving Rackem apps via Ruby web servers
 - Mime class for file type detection
 - Exceptions middleware for handling exceptions
 - File middleware for serving files
 - Basic authentication middleware
 - Session handling
 - Protection middlewares to prevent attacks (csrf,xss,clickjacking,ip spoofing,dir traversal,session hijacking)
 - rackem for serving applications locally

## Credits

Everyone who has worked on the Rack project. A lot of the code is ported directly from Rack.

[creationx](https://github.com/creationix/rack-php) for a Rack script that will serve a PHP-based Rack-compliant application.
