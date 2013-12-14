<?php
namespace Phalcon\Cache;

abstract class Backened
{
    protected $options;

    public function __construct($options=null)
    {
        if ( isset($options) ) {
            $this->setOptions($options);
        }
    }

    public function getOption($name, $default=null)
    {
        return isset($this->options[$name]) ? $this->options[$name] : $default;
    }

    public function setOption($name, $val)
    {
        $this->options[$name] = $val;
        return $this;
    }
    
    public function setOptions($options)
    {
        foreach ( $options as $name => $val ) {
            $this->setOption($name, $val);
        }
        return $this;
    }

    abstract protected function getValue($key);
    abstract protected function setValue($key, $val, $lifetime);
    abstract public function delete($key);

    public function get($key)
    {
        $val = $this->getValue($key);
        if ( $val === false ) {
            return null;
        }
        $val = unserialize($val);
        return $val === null ? false : $val;
    }

    public function set($key, $val, $lifetime=null)
    {
        return $this->save($key, $val, $lifetime);
    }
    
    public function save($key, $val, $lifetime=null)
    {
        if ( $val === false ) {
            $val = null;
        }
        if ( !isset($lifetime) ) {
            $lifetime = $this->getOption('lifetime', 0);
        }
        return $this->setValue($key, serialize($val), 0, $lifetime);
    }
}
