<?php
namespace Phalcon\Events;

class Event
{
    private $type;
    private $source;
    private $data;
    private $cancelable;
    private $stopped = false;
    
    public function __construct($type, $source, $data=null, $cancelable=true)
    {
        $this->type = $type;
        $this->source = $source;
        $this->data = $data;
        $this->cancelable = $cancelable;
    }
    
    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getSource()
    {
        return $this->source;
    }

    public function setData($data)
    {
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setCancelable($cancelable)
    {
        $this->cancelable = $cancelable;
    }

    public function getCancelable()
    {
        return $this->cancelable;
    }
    
    public function isCancelable()
    {
        return $this->cancelable;
    }

    public function stop()
    {
        $this->stopped = true;
    }

    public function isStopped()
    {
        return $this->stopped;
    }
}
