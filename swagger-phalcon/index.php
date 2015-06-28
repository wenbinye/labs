<?php
use Phalcon\DI\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\Router\Annotations as RouterAnnotations;
use Phalcon\Annotations\Adapter\Apc as Annotations;

$loader = new Loader;
$loader->registerDirs(['.']);
$loader->register();

$di = new FactoryDefault();
$di['loader'] = $loader;

$di['petService'] = 'PetStore\\V10000\\Services\\PetService';
$di['validator'] = 'PetStore\\DummyValidator';
$di['annotations'] = Annotations::CLASS;
    
$router = new RouterAnnotations(false);
$router->setDI($di);
$router->addResource('PetStore\\V10000\Controllers\Pet');

$router->handle();
if ($router->wasMatched()) {
    $controller = $router->getNamespaceName() . '\\' . ucfirst($router->getControllerName()) . 'Controller';
    $action = $router->getActionName();
    $returnValue = call_user_func_array(array($di->getShared($controller), $action), $router->getParams());
    echo json_encode($returnValue);
} else {
    echo '{"error": "Not Found"}';
}


