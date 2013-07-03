<?php
namespace Php\Inspector;

class Autoloader
{
    private static $instance;
    private $registered = false;
    private $basePath;

    public static function getInstance()
    {
        if ( !isset(self::$instance) ) {
            self::$instance = new self;
        }
        return self::$instance;
    }
    
    public function register()
    {
        if ( !$this->registered ) {
            $this->registered = true;
            spl_autoload_register(array($this, 'autoload'));
        }
    }

    public function getBasePath()
    {
        if ( !isset($this->basePath) ) {
            $this->basePath = dirname(dirname(__DIR__));
        }
        return $this->basePath;
    }
    
    public function autoload($class)
    {
        if ( strpos($class, 'Php\Inspector') !== false ) {
            require($this->getBasePath() .'/'. str_replace('\\', '/', $class) . '.php');
        }
    }
}
