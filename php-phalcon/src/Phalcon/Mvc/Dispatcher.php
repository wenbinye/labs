<?php
namespace Phalcon\Mvc;

use Phalcon\DI\Injectable;

class Dispatcher extends Injectable
{
    private $defaultNamespace = null;
    private $controllerName;
    private $actionName;

    public function getDefaultNamespace()
    {
        return $this->defaultNamespace;
    }

    public function setDefaultNamespace($defaultNamespace)
    {
        $this->defaultNamespace = $defaultNamespace;
    }
    
    public function getControllerName()
    {
        if ( !isset($this->controllerName) ) {
            $this->controllerName = $this->router->getControllerName();
        }
        return $this->controllerName;
    }

    public function setControllerName($controllerName)
    {
        $this->controllerName = $controllerName;
    }

    public function getActionName()
    {
        if ( !isset($this->actionName) ) {
            $this->actionName = $this->router->getActionName();
        }
        return $this->actionName;
    }

    public function setActionName($actionName)
    {
        $this->actionName = $actionName;
    }

    public function dispatch()
    {
        $controller = ucfirst($this->getControllerName()) . 'Controller';
        if ( isset($this->defaultNamespace) ) {
            $controller = $this->defaultNamespace . '\\' . $controller;
        }
        $action = $this->getActionName() . 'Action';
        $controller = $this->getDI()->get($controller);
        $controller->$action();
    }
}
