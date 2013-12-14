<?php
namespace Phalcon;

use Phalcon\DI;

class DITest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Object::$count = 0;
    }

    function testSetClosure()
    {
        $di = new DI;
        $di->set('config', function() {
                return new Object;
            }, false);
        $this->assertEquals(0, $di->getConfig()->id);
        $this->assertEquals(1, $di->getConfig()->id);
    }
    
    function testSetShared()
    {
        $di = new DI;
        $di->setShared('config', function() {
                return new Object;
            });
        $this->assertEquals(0, $di->getConfig()->id);
        $this->assertEquals(0, $di->getConfig()->id);
    }

    function testGetService()
    {
        $di = new DI;
        $di->setShared('config', function() {
                return new Object;
            });
        $service = $di->getService('config');
        $this->assertTrue($service->isShared());
        
        $def = $service->getDefinition();
        $service->setDefinition(function() use($def) {
                $obj = $def();
                $obj->id = 10;
                return $obj;
            });
        $this->assertEquals(10, $di->get('config')->id);
    }

    function testGetWithArg()
    {
        $di = new DI;
        $di->set('config', function($arg) {
                $obj = new Object;
                $obj->id = $arg;
                return $obj;
            }, false);
        $this->assertEquals(10, $di->getConfig(10)->id);
    }

    function testGetWithCallName()
    {
        $di = new DI;
        $di->set('config', array(
            'className' => __NAMESPACE__.'\Object',
        ));
        $this->assertEquals(10, $di->getConfig(10)->id);
    }

    function testGetWithArguments()
    {
        $di = new DI;
        $di->set('config', array(
            'className' => __NAMESPACE__.'\Object',
            'arguments' => array(
                array('type'=>'parameter', 'value'=>10)
            )));
        $this->assertEquals(10, $di->getConfig(10)->id);
    }
    
    function testGetWithCalls()
    {
        $di = new DI;
        $di->set('config', array(
            'className' => __NAMESPACE__.'\Object',
            'calls' => array(
                array(
                    'method' => 'setId',
                    'arguments' => array(
                        array('type'=>'parameter', 'value'=>10)
                    )
                )
            )
        ));
        $this->assertEquals(10, $di->getConfig(10)->id);
    }

    function testInjectProperties()
    {
        $di = new DI;
        $di->set('config', array(
            'className' => __NAMESPACE__.'\Object',
            'properties' => array(
                array('name'=>'id', 'value' => array('type'=>'parameter', 'value'=>10))
            )
        ));
        $this->assertEquals(10, $di->getConfig()->id);
    }

    function testSetObj()
    {
        $di = new DI;
        $di->setShared('config', new Object(10));
        $this->assertEquals(10, $di->getConfig()->id);
    }

    function testClassName()
    {
        $di = new DI;
        $di->set('obj', array(
            'className' => __NAMESPACE__ .'\InjectObject'
        ));
        $i = $di->getObj();
        $this->assertTrue($i instanceof InjectObject);
    }
}

class Object
{
    public static $count=0;
    public $id;

    public function __construct($id=null)
    {
        if ( isset($id) ) {
            $this->id = $id;
        } else {
            $this->id = self::$count++;
        }
    }
    public function setId($id)
    {
        $this->id = $id;
    }
}

class InjectObject implements DI\InjectionAwareInterface
{
    private $di;
    
    public function getDi()
    {
        return $this->di;
    }

    public function setDi($di)
    {
        $this->di = $di;
    }
}
