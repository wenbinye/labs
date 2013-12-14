<?php
namespace Phalcon\Http;

use Phalcon\DI;

class Request
{
    private $filter;
    
    public function __construct()
    {
        $this->filter = DI::getDefault()->getFilter();
    }
    
    public function get($name, $filter=null, $default=null)
    {
        if ( isset($_REQUEST[$name]) ) {
            return isset($filter) ? $this->filter->sanitize($_REQUEST[$name], $filter)
                : $_REQUEST[$name];
        } else {
            return $default;
        }
    }

    public function getQuery($name, $filter=null, $default=null)
    {
        if ( isset($_GET[$name]) ) {
            return isset($filter) ? $this->filter->sanitize($_GET[$name], $filter)
                : $_GET[$name];
        } else {
            return $default;
        }
    }

    public function getPost($name, $filter=null, $default=null)
    {
        if ( isset($_POST[$name]) ) {
            return isset($filter) ? $this->filter->sanitize($_POST[$name], $filter)
                : $_POST[$name];
        } else {
            return $default;
        }
    }

    public function getHttpReferer()
    {
		return isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:null;
    }

    public function getClientAddress()
    {
		return isset($_SERVER['REMOTE_ADDR'])?$_SERVER['REMOTE_ADDR']:'127.0.0.1';
    }

    public function isPost()
    {
		return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'POST');
    }

    public function isDelete()
    {
        return isset($_SERVER['REQUEST_METHOD']) && !strcasecmp($_SERVER['REQUEST_METHOD'],'DELETE');
    }
}
