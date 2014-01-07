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
    echo implode("\r\n", $head);
    echo "\r\n\r\n";
    echo implode("", $body);
  }

  public function env()
  {
    $url_scheme = $_ENV['rack_url_scheme'];

    $env = array_merge($_ENV, array(
      "rack.input" => fopen('php://input', 'r'),
      "rack.errors" => fopen('php://stderr', 'wb'),
      "rack.sessions" => array(),
      "rack.logger" => "",
      "rack.version" => \Rackem::version(),
      "rack.url_scheme" => $url_scheme
    ));
    unset($env['rack_url_scheme']);
    unset($env['rackem_handler']);
    unset($env['PWD']);
    unset($env['SHLVL']);
    unset($env['_']);
    unset($env['__CF_USER_TEXT_ENCODING']);
    return new \ArrayObject($env);
  }
}
