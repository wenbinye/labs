<?php
namespace Phalcon\Events;

/**
 * phalcon 兼容 event 实现
 * @see http://docs.phalconphp.com/en/latest/reference/events.html
 */
class Manager
{
    const NAME_SEPARATOR = ':';
    private $listeners;
    private $handlers;
    
    public function attach($type, $listener)
    {
        if ( !is_object($listener) && !is_callable($listener) ) {
            throw new \InvalidArgumentException("Event handler must be an Object");
        }
        list($ns, $name) = $this->parseType($type);
        if ( isset($name) ) {
            $this->listeners[$ns][$name][] = $listener;
        } else {
            $this->handlers[$ns][] = $listener;
        }
    }

    public function fire($type, $source, $data=null, $cancelable=true)
    {
        list($ns, $name) = $this->parseType($type);
        $event = new Event(isset($name) ? $name : $ns, $source, $data, $cancelable);
        $listeners = $this->getListeners($type);
        foreach ( $listeners as $listener ) {
            if ( $cancelable && $event->isStopped() ) {
                return;
            }
            if ( !is_callable($listener) ) {
                if ( !is_object($listener) || !isset($name) ) {
                    throw new \InvalidArgumentException("Invalid event type $type");
                }
                if ( method_exists($listener, $name) ) {
                    $listener = array($listener, $name);
                } else {
                    continue;
                }
            }
            call_user_func($listener, $event, $source, $data);
        }
    }

    public function detachAll($type)
    {
        list($ns, $name) = $this->parseType($type);
        if ( isset($name) ) {
            unset($this->listeners[$ns][$name]);
        } else {
            unset($this->handlers[$ns]);
        }
    }

    public function getListeners($type)
    {
        list($ns, $name) = $this->parseType($type);
        $listeners = array();
        if ( isset($name) && isset($this->listeners[$ns][$name]) ) {
            $listeners = $this->listeners[$ns][$name];
        }
        if ( isset($this->handlers[$ns]) ) {
            $listeners = array_merge($listeners, $this->handlers[$ns]);
        }
        return $listeners;
    }

    protected function parseType($type)
    {
        $pos = strpos($type, self::NAME_SEPARATOR);
        if ( $pos === false ) {
            return array($type, null);
        } else {
            return array(substr($type, 0, $pos), substr($type, $pos+1));
        }
    }
}
