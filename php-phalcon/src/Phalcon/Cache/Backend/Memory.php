<?php
namespace Phalcon\Cache\Backend;

use Phalcon\Cache\Backend;

class Memory extends Backend
{
    private $cache;

    protected function getValue($key)
    {
        return isset($this->cache[$key]) ? $this->cache[$key] : false;
    }

    protected function setValue($key, $val, $lifetime)
    {
        $this->cache[$key] = $val;
        return true;
    }

    public function delete($key)
    {
        unset($this->cache[$key]);
    }
}

