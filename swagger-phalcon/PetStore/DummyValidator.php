<?php
namespace PetStore;

use Phalcon\DI\Injectable;
use Phalcon\Validation\Validator\PresenceOf;
use Phalcon\Validation\Validator\Digit;

class DummyValidator extends Injectable
{
    private $annotationName = 'Valid';
    private $validators = [];
    private $types = [
        'integer' => new Digit(),
    ];
    
    public function __construct($options = null)
    {
        if (is_array($options)) {
            if (isset($options['annotation'])) {
                $this->annotationName = $options['annotation'];
            }
        }
    }
    
    public function validate($form)
    {
        if (!is_array($form)) {
            return $this->validate($this->getAnnotations($form));
        }
        $validation = new Validation;
        $data = [];
        foreach ($form as &$elem) {
            if ((!isset($elem['value']) || $elem['value'] == '') && isset($elem['default'])) {
                $elem['value'] = $elem['default'];
            }
            $data[$elem['name']] = $elem['value'];
            if (!empty($elem['required'])) {
                $validation->add([$elem['name']], new PresenceOf());
            }
            if (isset($elem['type'])) {
                if (!isset($this->types[$elem['type']])) {
                    throw new \UnexpectedValueException("Cannot handle type {$elem['type']} for field {$elem['name']}");
                }
                $validation->add($elem['name'], $this->types[$elem['type']]);
            }
            if (isset($elem['validator'])) {
                $validation->add($elem['name'], $elem['validator']);
            }
        }
        $errors = $validation->validate($data);
        if (count($errors)) {
            throw new ValidationException($errors);
        }
    }

    private function getAnnotations($form)
    {
        $properties = $this->annotations->getProperties(Pet::CLASS);
        $validators = [];
        foreach ($properties as $name => $annotations) {
            unset($value);
            $value = &$form->$name;
            foreach ($annotations as $annotation) {
                if ($annotation->getName() != $this->annotationName) {
                    continue;
                }
                $validator = $annotation->getArguments();
                $validator['name'] = $name;
                if (isset($validator['default'])) {
                    $validator['value'] = &$value;
                } else {
                    $validator['value'] = $value;
                }
                if (isset($validator['validator'])) {
                    $validator['validator'] = $this->createValidator($validator['validator']);
                }
                $validators[] = $validator;
            }
        }
    }

    private function createMessage($field, $type)
    {
        
    }

    private function createValidator($annotation)
    {
        $name = $annotation->getName();
        if (isset($this->validators[$name])) {
            $class = $this->validators[$name];
        } else {
            $class = 'Phalcon\Validation\Validator\\' . $name;
        }
        return new $class($annotation->getArguments());
    }
}
