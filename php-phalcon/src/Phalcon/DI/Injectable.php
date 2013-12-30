<?php
namespace Phalcon\DI;

use Phalcon\DI;

class Injectable implements InjectionAwareInterface
{
    protected $di;

    public function getDi()
    {
        if ( !isset($this->di) ) {
            $this->di = DI::getDefault();
        }
        return $this->di;
    }

    public function setDi($di)
    {
        $this->di = $di;
    }

    public function __get($name)
    {
        if ( $this->getDI()->has($name) ) {
            return $this->di->get($name);
        }
    }
}
