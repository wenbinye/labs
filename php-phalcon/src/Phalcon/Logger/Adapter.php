<?php
namespace Phalcon\Logger;

use Phalcon\Logger;

abstract class Adapter
{
    protected $_formatter = NULL;
    protected $_logLevel = Logger::SPECIAL;
    
    public function setLogLevel($level)
    {
        $this->_logLevel = $level;
    }

    public function getLogLevel()
    {
        return $this->_logLeve;
    }

    public function setFormatter($formatter)
    {
    }

    public function format($msg, $level, $timestamp)
    {
        return date('Y-m-d H:i:s', $timestamp) . ' ['.Logger::$LEVELS[$level].'] ' . $msg;
    }
    
    public function emergence($message)
    {
        $this->log($message, Logger::EMERGENCE);
    }

    public function debug($message)
    {
        $this->log($message, Logger::DEBUG);
    }

    public function error($message)
    {
        $this->log($message, Logger::ERROR);
    }

    public function info($message)
    {
        $this->log($message, Logger::INFO);
    }

    public function notice($message)
    {
        $this->log($message, Logger::NOTICE);
    }

    public function warning($message)
    {
        $this->log($message, Logger::WARNING);
    }

    public function alert($message)
    {
        $this->log($message, Logger::ALERT);
    }

    public function log($message, $type = NULL)
    {
        if ( !isset($type) ) {
            $type = Logger::DEBUG;
        }
        if ( $this->_logLevel >= $type ) {
            $this->logInternal($message, $type, time());
        }
    }

    abstract protected function logInternal($msg, $type, $timestamp);
}
