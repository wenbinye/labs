<?php
namespace Phalcon\Mvc;

class Dispatcher
{
    private $controllerName;
    private $actionName;
    
    public function getControllerName()
    {
        return $this->controllerName;
    }

    public function setControllerName($controllerName)
    {
        $this->controllerName = $controllerName;
    }

    public function getActionName()
    {
        return $this->actionName;
    }

    public function setActionName($actionName)
    {
        $this->actionName = $actionName;
    }
}
