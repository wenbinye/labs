<?php
namespace PetStore\Tests\Mvc;

use Phalcon\DI;
use Phalcon\Mvc\Router\Annotations as RouterAnnotations;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testRouter()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['_url'] = '/api/pets/1';
        
        $router = new RouterAnnotations(false);
        $router->setDI(DI::getDefault());
        $router->addResource('PetStore\\V10000\Controllers\Pet');
        $router->handle();

        print_r($router);
        
        if ($router->wasMatched()) {
            $route =$router->getMatchedRoute();
            print_r($route);
            echo 'Namespace: ', $router->getNamespaceName(), "\n";
            echo 'Controller: ', $router->getControllerName(), '<br>';
            echo 'Action: ', $router->getActionName(), '<br>';
            
        } else {
            echo 'The route wasn\'t matched by any route<br>';
        }
    }
}
