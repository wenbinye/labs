<?php
namespace PetStore;

class ValidationException extends InvalidArgumentException
{
    private $errors;
    
    public function __construct($errors)
    {
        parent::__construct();
        $this->errors = $errors;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}
