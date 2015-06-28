<?php
namespace PetStore\Tests\Mvc;

use PetStore\DummyValidator;
use PetStore\V10000\Models\Pet;

class ValidatorTest extends \PHPUnit_Framework_TestCase
{
    private $validator;

    public function setUp()
    {
        $this->validator = new DummyValidator();
    }
    
    public function testValidate()
    {
        $reader = new \Phalcon\Annotations\Adapter\Apc();
        $properties = $reader->getProperties(Pet::CLASS);
        $validators = [];
        foreach ($properties as $name => $annotations) {
            foreach ($annotations as $annotation) {
                $validator = $annotation->getArguments();
                $validator['name'] = $name;
                $validators[] = $validator;
            }
        }
        
        print_r($validators);
        
        // $form = new Pet();
        // $this->validator->validate($form);
    }
}
