<?php
namespace Phalcon\Mvc;

use Phalcon\DI;

class Controller
{
    private $di;

    public function getDI()
    {
        if ( !isset($this->di) ) {
            $this->di = DI::getDefault();
        }
        return $this->di;
    }

    public function __get($name)
    {
        if ( $this->getDI()->has($name) ) {
            return $this->di->get($name);
        }
    }
}
