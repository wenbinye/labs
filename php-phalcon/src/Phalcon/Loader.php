<?php
namespace Phalcon;

class Loader
{
    protected $registered = false;
    protected $classes;
    protected $namespaces;
    protected $dirs;
    protected $prefixes;

    public function __construct()
    {
        $this->initialize();
    }

    protected function initialize()
    {
        $this->namespaces['Phalcon'] = __DIR__;
    }
    
    public function getEventsManager()
    {
        
    }

    public function setEventsManager()
    {
    }
    
    public function register()
    {
        if ( $this->registered ) {
            return;
        }
        $this->registered = true;
        spl_autoload_register(array($this, 'autoload'));
    }

    public function autoload($className)
    {
        $file = $this->loadClass($className);
        if ( isset($file) ) {
            include($file);
            return true;
        }
        return false;
    }

    protected function loadClass($className)
    {
        $className = trim($className, '\\');
        if ( isset($this->classes[$className]) ) {
            return $this->classes[$className];
        }
        if ( strpos($className, '\\') !== false && isset($this->namespaces) ) {
            foreach ( $this->namespaces as $namespace => $path ) {
                if ( strpos($className, $namespace) === 0 ) {
                    $file = $this->normalizePath($path, substr($className, strlen($namespace)));
                    if ( file_exists($file) ) {
                        return $file;
                    }
                }
            }
        } elseif ( isset($this->prefixes) ) {
            foreach ( $this->prefixes as $prefix => $path ) {
                if ( strpos($className, $prefix) === 0 ) {
                    $file = $this->normalizePath($path, substr($className, strlen($prefix)));
                    if ( file_exists($file) ) {
                        return $file;
                    }
                }
            }
        }
        if ( isset($this->dirs) ) {
            foreach ( $this->dirs as $path ) {
                $file = $this->normalizePath($path, $className);
                if ( file_exists($file) ) {
                    return $file;
                }
            }
        }
    }

    public function normalizePath($path, $className)
    {
        $file = $path . \DIRECTORY_SEPARATOR . str_replace(array('\\', '_'), \DIRECTORY_SEPARATOR, ltrim($className, '\\_')). '.php';
        error_log("autoload $file");
        return $file;
    }
    
    public function registerDirs($dirs, $merge=false)
    {
        $dirs = array_map(function($dir) {
                return rtrim($dir, \DIRECTORY_SEPARATOR);
            }, $dirs);
        if ( $merge ) {
            $this->dirs = array_merge($this->dirs, $dirs);
        } else {
            $this->dirs = $dirs;
        }
        return $this;
    }

    public function registerNamespaces($namespaces, $merge=false)
    {
        $namespaces = array_map(function($dir) {
                return rtrim($dir, \DIRECTORY_SEPARATOR);
            }, $namespaces);
        if ( $merge ) {
            $this->namespaces = array_merge($this->namespaces, $namespaces);
        } else {
            $this->namespaces = $namespaces;
            $this->initialize();
        }
        return $this;
    }

    public function registerPrefixes($prefixes, $merge=false)
    {
        $prefixes = array_map(function($dir) {
                return rtrim($dir, \DIRECTORY_SEPARATOR);
            }, $prefixes);
        if ( $merge ) {
            $this->prefixes = array_merge($this->prefixes, $prefixes);
        } else {
            $this->prefixes = $prefixes;
        }
        return $this;
    }
    
    public function registerClasses($classes, $merge=false)
    {
        $clean = array();
        foreach ( $classes as $className => $path ) {
            $clean[trim($name, '\\')] = rtrim($path, \DIRECTORY_SEPARATOR);
        }
        if ( $merge ) {
            $this->classes = array_merge($this->classes, $clean);
        } else {
            $this->classes = $clean;
        }
        return $this;
    }
}
