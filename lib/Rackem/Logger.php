<?php
namespace Rackem;

class Logger
{
    const EMERGENCY = 'emergency';
    const ALERT     = 'alert';
    const CRITICAL  = 'critical';
    const ERROR     = 'error';
    const WARNING   = 'warning';
    const NOTICE    = 'notice';
    const INFO      = 'info';
    const DEBUG     = 'debug';

    public $stream, $formatter, $datetime_format;

    public function __construct($stream)
    {
        $this->stream = is_string($stream)? fopen($stream, 'a') : $stream;
        $this->datetime_format = "D M d H:i:s Y";
        $this->formatter = function($severity, $datetime, $msg) {
            return "[$datetime] $severity -- $msg\n";
        };
    }

    public function close()
    {
        if(is_resource($this->stream)) fclose($this->stream);
    }

    public function emergency($message, array $context = array())
    {
        $this->log(static::EMERGENCY, $message, $context);
    }

    public function alert($message, array $context = array())
    {
        $this->log(static::ALERT, $message, $context);
    }

    public function critical($message, array $context = array())
    {
        $this->log(static::CRITICAL, $message, $context);
    }

    public function error($message, array $context = array())
    {
        $this->log(static::ERROR, $message, $context);
    }

    public function warning($message, array $context = array())
    {
        $this->log(static::WARNING, $message, $context);
    }

    public function notice($message, array $context = array())
    {
        $this->log(static::NOTICE, $message, $context);
    }

    public function info($message, array $context = array())
    {
        $this->log(static::INFO, $message, $context);
    }

    public function debug($message, array $context = array())
    {
        $this->log(static::DEBUG, $message, $context);
    }

    public function log($level, $message, array $context = array())
    {
        if(!defined(__CLASS__.'::'.strtoupper($level))) {
            throw new \InvalidArgumentException("Log level $level is not defined.");
        }

        if(is_callable($message)) $message = $message($context);
        $this->write($level, $message, $this->formatter);
    }

    //protected
    protected function write($level, $message, $format)
    {
        fwrite( $this->stream, $format(strtoupper($level), @date($this->datetime_format), $message) );
    }
}
