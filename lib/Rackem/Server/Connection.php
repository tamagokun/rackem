<?php
namespace Rackem\Server;

class Connection
{
  public $env;

  // processing requests
  public $client;
  public $socket;
  public $state = 0;
  public $header = array();
  public $body;
  public $request;
  public $content_length = 0;
  public $version;
  public $start_time;

  protected $bytes_read = 0;
  protected $header_buffer = '';
  protected $is_chunked = false;

  // processing responses
  public $buffer = '';
  public $bytes_written = 0;
  public $proc = null;
  public $stream = null;

  const READ_CHUNK_HEADER = 0;
  const READ_CHUNK_DATA = 1;
  const READ_CHUNK_TRAILER = 2;

  const READ_HEADERS = 0;
  const READ_CONTENT = 1;
  const READ_COMPLETE = 2;

  public function __construct($client)
  {
    $this->socket = $client;
    $this->body = fopen("data://text/plain,", "r+b");

    stream_set_blocking($this->socket, 0);
    $client = stream_socket_get_name($this->socket, false);
    if(strpos($client, ':') !== false) list($client, $port) = explode(':', $client, 2);
    $this->client = $client;
  }

  public function cleanup()
  {
    @fclose($this->body);
    $this->body = null;
  }

  public function is_request_complete()
  {
    return $this->state == Connection::READ_COMPLETE;
  }

  public function is_response_complete()
  {
    $status = proc_get_status($this->proc);
    return $status['running'] ? false : true;
  }

  public function get_header($key)
  {
    return isset($this->header[$key]) ? $this->header[$key][0] : "";
  }

  /*
   * Append data from request
   */
  public function data($data)
  {
    if($this->state == Connection::READ_HEADERS)
    {
      if(!$this->start_time) $this->start_time = microtime(true);
      $this->header_buffer .= $data;

      $end = strpos($this->header_buffer, "\r\n\r\n", 4);
      if($end === false) return;

      // parse HTTP request
      $this->parse_request(substr($this->header_buffer, 0, $end));

      // TODO: check for Transfer-Encoding: chunked

      $this->content_length = (int)$this->get_header('Content-Length');

      $body_start = $end + 4;
      $data = substr($this->header_buffer, $body_start);

      $this->state = Connection::READ_CONTENT;
    }

    if($this->state == Connection::READ_CONTENT)
    {
      if($this->is_chunked)
      {
        // TODO: Handle chunked data

      }else
      {
        fwrite($this->body, $data);
        $this->bytes_read += strlen($data);

        if($this->content_length - $this->bytes_read <= 0) $this->state = Connection::READ_COMPLETE;
      }
    }

    if($this->state == Connection::READ_COMPLETE) fseek($this->body, 0);
  }

  /*
   * Read data from Rack'em response
   */
  public function read()
  {
    $data = @fread($this->stream, 30000);

    if($data === false) return;

    $this->buffer .= $data;
  }

  /*
   * Begin response for request.
   */
  public function process($app)
  {
    $res = "";
    $spec = array(
      0 => array("pipe", "rb"),
      1 => array("pipe", "wb"),
      2 => STDERR
    );

    $env = array();
    // bring over standard $_SERVER array
    foreach($_SERVER as $k=>$v) if(is_numeric($v) || is_string($v)) $env[$k] = $v;
    $env = array_merge($env, $this->env);
    foreach($this->header as $k=>$v)
    {
      $env_name = str_replace("-", "_", strtoupper($k));
      if(!isset($this->env[$env_name]))
        $env["HTTP_$env_name"] = is_array($v) ? implode("\n", $v) : $v;
    }

    $php = defined("PHP_BINARY") ? PHP_BINARY : PHP_BINDIR."/php";
    $app = realpath($app);
    $this->proc = proc_open("$php $app", $spec, $pipes, null, $env);
    if(!is_resource($this->proc)) return false;

    fclose($pipes[0]);
    stream_set_blocking($pipes[1], 0);

    return $this->stream = $pipes[1];
  }

// private
  protected function parse_request($raw)
  {
    $headers = explode("\r\n", $raw);

    $this->request = array_shift($headers);

    list($method, $uri, $version) = explode(' ', $this->request, 3);

    $parsed_uri = parse_url($uri);

    foreach($headers as $line)
    {
      list($k, $v) = explode(": ", $line, 2);
      if(!isset($this->header[$k]))
        $this->header[$k] = array($v);
      else
        $this->header[$k][] = $v;
    }

    $scheme = 'http';
    $host = $this->get_header('Host');
    $port = 80;
    if(strpos($host, ':') !== false)
    {
      list($host, $port) = explode(':', $host);
      $port = (int)trim($port);
      if($port == 443) $scheme = 'https';
    }

    $this->env = array(
      "CONTENT_LENGTH" => $this->content_length,
      "CONTENT_TYPE" => $this->get_header('Content-Type'),
      "REQUEST_METHOD" => $method,
      "SCRIPT_NAME" => "",
      "PATH_INFO" => $parsed_uri['path'],
      "SERVER_NAME" => $host,
      "SERVER_PORT" => $port,
      "SERVER_PROTOCOL" => "HTTP/1.1",
      "QUERY_STRING" => isset($parsed_uri['query']) ? $parsed_uri['query'] : '',
      "rack_url_scheme" => $scheme,
      "rackem_handler" => "rackem"
    );
  }

}
