<?php
namespace Phalcon\Logger\Adapter;

class File extends Stream
{
    public function __construct($file)
    {
        $dir = dirname($file);
        if ( !is_dir($dir) && !mkdir($dir, 0777, true) ) {
            error_log("Cannot create log directory '{$dir}'");
            parent::__construct("php://stderr");
            return;
        }
        parent::__construct($file);
    }
}
