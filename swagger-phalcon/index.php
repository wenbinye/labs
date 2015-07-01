<?php
use Phalcon\DI\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Mvc\Router\Annotations as RouterAnnotations;
use Phalcon\Annotations\Adapter\Apc as Annotations;
use PhalconX\Mvc\Micro;
use PhalconX\Mvc\SwaggerApplication;

$loader = new Loader;
$loader->registerDirs(['.']);
$loader->register();

$di = new FactoryDefault();
$di['loader'] = $loader;

$di['petService'] = 'PetStore\\V10000\\Services\\PetService';
$di['validator'] = 'PhalconX\\Validator';
$di['annotations'] = Annotations::CLASS;
    
$di['router'] = function() use($di) {
    $router = new RouterAnnotations(false);
    $router->setDI($di);
    $router->addResource('PetStore\\V10000\Controllers\Pet');
    return $router;
};

$app = new Micro($di);
// $app = new SwaggerApplication($di);
echo json_encode($app->handle());
