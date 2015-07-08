<?php
namespace PetStore\V10000\Models;

class NetPetRequest
{
    /**
     * @Valid(type=integer)
     */
    public $id;

    /**
     * @Valid(required=true)
     */
    public $name;

    public $tag;
}
