<?php
namespace PetStore\V10000\Services;

use PetStore\V10000\Apis\PetApi;
use PetStore\V10000\Requests\NewPetRequest;
use PetStore\V10000\Models\Pet;

class PetService implements PetApi
{
    public function findPetById($id)
    {
        $pet = new Pet;
        $pet->id = $id;
        $pet->name = 'huahua';
        $pet->tag = 'dog';
        return $pet;
    }
    
    public function deletePet($id)
    {
    }
    
    public function findPets($tags, $limit)
    {
    }
    
    public function addPet(NewPetRequest $request)
    {
    }
}

