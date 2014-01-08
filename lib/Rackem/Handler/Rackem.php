<?php
namespace Rackem\Handler;

use \Rackem\Utils;

class Rackem
{
  public function run($app)
  {
    $env = $this->env();

    ob_start();
    list($status, $headers, $body) = $app->call($env);
    $output = ob_get_clean();
    if($output) $headers['X-Output'] = json_encode($output);

    fclose($env['rack.input']);
    fclose($env['rack.errors']);
    if($env['rack.logger']) $env['rack.logger']->close();
    $headers['X-Powered-By'] = "Rack'em ".implode(".",$env['rack.version']);
    $headers['Status'] = $status;
    $phrase = Utils::status_code($status);

    $head = array("{$env['SERVER_PROTOCOL']} $status $phrase");
    foreach($headers as $key=>$values)
      foreach(explode("\n",$values) as $value) $head[] = "$key: $value";
    echo implode("\r\n", $head) . "\r\n\r\n" . implode("", $body);
  }

  public function env()
  {
    $url_scheme = $_SERVER['rack_url_scheme'];
    $accepted = array(
      "CONTENT_LENGTH",
      "CONTENT_TYPE",
      "REQUEST_METHOD",
      "SCRIPT_NAME",
      "PATH_INFO",
      "SERVER_NAME",
      "SERVER_PORT",
      "SERVER_PROTOCOL",
      "QUERY_STRING",
      "rack.version",
      "rack.url_scheme",
      "rack.input",
      "rack.errors",
      "rack.multithread",
      "rack.multiprocess",
      "rack.run_once",
      "rack.hijack?",
      "rack.hijack",
      "rack.hijack_io",
      "rack.session",
      "rack.logger"
    );

    $env = array_merge($_SERVER, array(
      "rack.input" => fopen('php://input', 'r'),
      "rack.errors" => fopen('php://stderr', 'wb'),
      "rack.sessions" => array(),
      "rack.logger" => "",
      "rack.version" => \Rackem::version(),
      "rack.url_scheme" => $url_scheme
    ));

    foreach($env as $k=>$v) if(!in_array($k, $accepted) && substr($k,0,5) !== "HTTP_)") unset($env[$k]);
    return new \ArrayObject($env);
  }
}
