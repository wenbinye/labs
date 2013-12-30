<?php
namespace Phalcon\DI;

use Phalcon\DI;

class FactoryDefault extends DI
{
    protected $definitions = array(
        'filter' => array(
            'className' => 'Phalcon\Filter'
        ),
        'request' => array(
            'className' => 'Phalcon\Http\Request'
        ),
        'response' => array(
            'className' => 'Phalcon\Http\Response'
        ),
        'dispatcher' => array(
            'className' => 'Phalcon\Mvc\Dispatcher'
        ),
        'router' => array(
            'className' => 'Phalcon\Mvc\Router'
        )
    );

    protected $shared = array(
        'filter' => true,
        'request' => true,
        'response' => true,
        'dispatcher' => true,
        'router' => true,
    );

    public function __construct()
    {
        DI::setDefault($this);
    }
}
