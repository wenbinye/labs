<?php
namespace PetStore\V10000\Models;

class Pet
{
    /**
     * @Valid(type=integer, required=true, validator=@Between(minimum=1, maximum=20))
     */
    public $id;

    /**
     * @Valid(required=true)
     */
    public $name;

    public $tag;
}
