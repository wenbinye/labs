<?php
namespace Phalcon\DI;

interface InjectionAwareInterface
{
    public function setDI($di);
    public function getDI();
}
