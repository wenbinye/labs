<?php
use Phalcon\DI\FactoryDefault;
use Phalcon\Loader;
use Phalcon\Cache;
use Phalcon\Mvc\Router\Annotations as RouterAnnotations;
use Phalcon\Annotations\Adapter\Apc as Annotations;
use PhalconX\Mvc\Micro;

$loader = new Loader;
$loader->registerDirs(['.']);
$loader->register();

$di = new FactoryDefault();
$di['loader'] = $loader;

$di['petService'] = 'PetStore\\V10000\\Services\\PetService';
$di['validator'] = 'PhalconX\\Validator';
$di['reflection'] = 'PhalconX\\Reflection';
$di['objectConverter'] = 'PhalconX\\ObjectConverter';
$di['annotations'] = Annotations::CLASS;
$di['cache'] = function() {
    $frontend = new Cache\Frontend\None;
    return new Cache\Backend\Memory($frontend);
};

$di['router'] = function() use($di) {
    $router = new RouterAnnotations(false);
    $router->setDI($di);
    $router->addResource('PetStore\\V10000\Controllers\Pet');
    return $router;
};

$app = new Micro($di);
$app->error(function($e) {
        echo $e->getMessage(), "\n";
        echo $e->getTraceAsString();
});
echo json_encode($app->handle());
