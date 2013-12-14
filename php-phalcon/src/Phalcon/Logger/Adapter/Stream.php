<?php
namespace Phalcon\Logger\Adapter;

use Phalcon\Logger\Adapter;

class Stream extends Adapter
{
    protected $stream;
    public function __construct($stream)
    {
        if ( is_string($stream) ) {
            $stream = fopen($stream, 'a');
        }
        $this->stream = $stream;
    }
    
    protected function logInternal($msg, $type, $timestamp)
    {
        fwrite($this->stream, $this->format($msg, $type, $timestamp) . "\n");
    }
}
