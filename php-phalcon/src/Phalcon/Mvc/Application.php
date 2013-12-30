<?php
namespace Phalcon\Mvc;

use Phalcon\DI\Injectable;

class Application extends Injectable
{
    private $implicitView = true;
    
    public function handle()
    {
        $this->router->handle();
        $this->dispatcher->dispatch();
        return $this->response;
    }

    public function useImplicitView($turnOn)
    {
        $this->implicitView = $turnOn;
    }
}
