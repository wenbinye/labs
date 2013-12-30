<?php
namespace Phalcon\Mvc;

class Router
{
    public $defaultController = 'index';
    public $defaultAction = 'index';
    
    private $controllerName;
    private $actionName;

    public function handle()
    {
        if ( !isset($_SERVER['PATH_INFO']) ) {
            throw new \RuntimeException("Please config nginx fastcgi_params PATH_INFO");
        }
        $parts = explode('/', $_SERVER['PATH_INFO']);
        $this->controllerName = empty($parts[0]) ? $this->defaultController : $parts[0];
        $this->actionName = empty($parts[1]) ? $this->defaultAction : $parts[1];
    }

    public function getControllerName()
    {
        return $this->controllerName;
    }

    public function getActionName()
    {
        return $this->actionName;
    }
}
