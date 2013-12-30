<?php
namespace Phalcon;

/**
 * 兼容 Phalcon\DI 的 php 实现
 * @see http://docs.phalconphp.com/en/latest/reference/di.html
 */
class DI implements \ArrayAccess
{
    private static $default;
    private $factories;

    protected $definitions = array();
    protected $shared = array();
    protected $values;

    public static function getDefault()
    {
        if ( !self::$default ) {
            self::$default = new self;
        }
        return self::$default;
    }

    public static function setDefault($di)
    {
        self::$default = $di;
    }

    public function __call($name, $args)
    {
        if ( strpos($name, 'get') === 0 ) {
            return $this->resolve(strtolower(substr($name, 3, 1)) . substr($name, 4), $args);
        } else {
            trigger_error('Call to undefined method ' . get_class($this) . '::'.$name, E_USER_ERROR);
        }
    }

    public function getService($name)
    {
        if ( isset($this->definitions[$name]) ) {
            return new DI\Service($this, $name, $this->definitions[$name], $this->shared[$name]);
        }
    }

    public function has($name)
    {
        return isset($this->definitions[$name]);
    }
    
    public function set($name, $definition, $shared=false)
    {
        if ( is_array($definition) && isset($definition['autoload']) ) {
            $definition['name'] = $name;
        }
        $this->definitions[$name] = $definition;
        $this->shared[$name] = $shared;
    }

    public function get($name)
    {
        $args = func_get_args();
        if ( count($args) > 1 ) {
            return $this->resolve($name, array_slice($args, 1));
        } else {
            return $this->resolve($name);
        }
    }

    public function getShared($name)
    {
        if ( isset($this->values[$name]) ) {
            return $this->values[$name];
        }
        return $this->values[$name] = call_user_func_array(array($this, 'get'), func_get_args());
    }
    
    public function setShared($name, $definition)
    {
        $this->set($name, $definition, true);
    }

    public function offsetSet($name, $definition)
    {
        $this->set($name, $definition);
    }

    public function offsetGet($name)
    {
        return $this->resolve($name);
    }

    protected function resolve($name, $args=array())
    {
        if ( isset($this->shared[$name], $this->values[$name]) && $this->shared[$name] ) {
            return $this->values[$name];
        }
        $def = array_key_exists($name, $this->definitions) ? $this->definitions[$name] : array('className' => $name);
        $resolved = $this->createInstance($def, $args);
        if ( isset($resolved) ) {
            if ( isset($this->shared[$name]) && $this->shared[$name] ) {
                $this->values[$name] = $resolved;
            }
        } else {
            $resolved = $this->definitions[$name];
        }
        return $resolved;
    }
    
    public function createInstance($def, $args=array())
    {
        $resovled = null;
        if ( is_callable($def) ) {
            $resovled = call_user_func_array($def, $args);
        } elseif ( is_array($def) ) {
            if ( isset($def['autoload']) ) {
                $factory = $this->createFactory($def['autoload']);
                $resovled = call_user_func_array(array($factory, 'get'.$def['name']), $args);
            } elseif ( isset($def['className']) ) {
                $class = $def['className'];
                if ( empty($args) && isset($def['arguments']) ) {
                    $args = $this->resolveArguments($def['arguments']);
                }
                if ( empty($args) ) {
                    $resovled = new $class;
                } elseif ( count($args) == 1 ) {
                    $resovled = new $class($args[0]);
                } elseif ( count($args) == 2 ) {
                    $resovled = new $class($args[0], $args[1]);
                } elseif ( count($args) == 3 ) {
                    $resovled = new $class($args[0], $args[1], $args[2]);
                } else {
                    $refl = new \ReflectionClass($class);
                    $resovled = call_user_func_array(array($refl, 'newInstance'), $args);
                }
                if ( isset($def['calls']) ) {
                    foreach ( $def['calls'] as $call ) {
                        if ( isset($call['method']) ) {
                            $method = $call['method'];
                            if ( isset($call['arguments']) ) {
                                call_user_func_array(array($resovled, $method), $this->resolveArguments($call['arguments']));
                            } else {
                                $resovled->$method();
                            }
                        }
                    }
                }
                if ( isset($def['properties']) ) {
                    foreach ( $def['properties'] as $prop ) {
                        if ( isset($prop['name'], $prop['value']) ) {
                            $name = $prop['name'];
                            $resovled->$name = $this->resolveValue($prop['value']);
                        }
                    }
                }
            }
        } else {
            $resovled = $def;
        }
        if ( is_object($resovled) && $resovled instanceof DI\InjectionAwareInterface ) {
            $resovled->setDI($this);
        }
        return $resovled;
    }

    protected function createFactory($class)
    {
        if ( isset($this->factories[$class]) ) {
            return $this->factories[$class];
        }
        return $this->factories[$class] = new $class($this);
    }

    protected function resolveValue($def)
    {
        if ( !isset($def['type']) ) {
            throw new \RuntimeException("Invalid arguments: no type");
        }
        switch ( $def['type'] ) {
        case 'parameter':
            return $def['value'];
            break;
        case 'service':
            return $this->get($def['name']);
            break;
        case 'instance':
            return $this->createInstance($def);
            break;
        default:
            throw new \RuntimeException("Invalid arguments: unknown type '{$def['type']}'");
        }
    }
    
    protected function resolveArguments($defs)
    {
        if ( !is_array($defs) ) {
            throw new \RuntimeException("Invalid DI arguments, should be array");
        }
        $args = array();
        foreach ( $defs as $def ) {
            $args[] = $this->resolveValue($def);
        }
        return $args;
    }
    
    public function offsetExists($name)
    {
        return array_key_exists($name, $this->definitions);
    }

    public function offsetUnset($name)
    {
        unset($this->definitions[$name]);
        unset($this->values[$name]);
        unset($this->shared[$name]);
    }
}

namespace Phalcon\DI;

class Service
{
    private $di;
    private $name;
    private $definition;
    private $shared;
    
    public function __construct($di, $name, $definition, $shared)
    {
        $this->di = $di;
        $this->name = $name;
        $this->definition = $definition;
        $this->shared = $shared;
    }

    public function getDefinition()
    {
        return $this->definition;
    }

    public function setDefinition($definition)
    {
        $this->definition = $definition;
        return $this->di->set($this->name, $this->definition, $this->shared);
    }
    
    public function resolve()
    {
        return $this->di->createInstance($this->definition, func_get_args());
    }

    public function isShared()
    {
        return $this->shared;
    }
    
    public function setShared($shared)
    {
        $this->shared = (bool)$shared;
        $this->di->set($this->name, $this->definition, $this->shared);
    }
}

