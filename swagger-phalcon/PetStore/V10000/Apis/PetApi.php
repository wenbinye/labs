<?php
namespace PetStore\V10000\Apis;

use PetStore\V10000\Requests\NewPetRequest;

interface PetApi
{
    function findPetById($id);

    function deletePet($id);

    function findPets($tags, $limit);

    function addPet(NewPetRequest $request);
}
