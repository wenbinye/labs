<?php
namespace PetStore\Tests\Mvc;

use Phalcon\DI;
use Phalcon\Mvc\Router;
use Phalcon\Mvc\Router\Annotations as RouterAnnotations;

class RouterTest extends \PHPUnit_Framework_TestCase
{
    public function testRouter()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        
        $router = new Router(false);
        $router->setDI(DI::getDefault());
        $router->addGet('/api/pets/{id}', array(
            'controller' => 'PetStore\V10000\Controllers\Pet',
            'action' => 'findPetById'
        ));
        
        $router->handle('/api/pets/1');

        // print_r($router);
        
        if ($router->wasMatched()) {
            echo 'Controller: ', $router->getControllerName(), '<br>';
            echo 'Action: ', $router->getActionName(), '<br>';
        } else {
            echo 'The route wasn\'t matched by any route<br>';
        }
    }
}
