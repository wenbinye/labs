<?php
namespace PetStore\V10000\Controllers;

use Phalcon\Mvc\Controller;
use Phalcon\Validation\Validator\Between;
use PetStore\V10000\Requests\NewPetRequest;

/**
 * @RoutePrefix("/api/pets")
 */
class PetController extends Controller
{
    /**
     * @Get("/{id}")
     */
    public function findPetById($id)
    {
        $this->validator->validate([
            [
                'name' => 'id',
                'value' => $id,
                'required' => true,
                'type' => 'integer',
            ]
        ]);
        return $this->petService->findPetById($id);
    }

    /**
     * @Delete("/{id}")
     */
    public function deletePet($id) 
    {
        $this->validator->validate([
            [
                'name' => 'id',
                'value' => $id,
                'required' => true,
                'type' => 'integer',
            ]
        ]);
        return $this->petService->deletePet($id);
    }

    /**
     * @Get("")
     */
    public function findPets($tags, $limit)
    {
        $this->validator->validate([
            [
                'name' => 'tags',
                'value' => $tags,
                'required' => false,
                'type' => 'array',
            ],
            [
                'name' => 'limit',
                'value' => &$limit,
                'required' => false,
                'default' => 10,
                'validator' => new Between(['minimum' => 1, 'maximum' => 20]),
                'type' => 'integer'
            ]
        ]);
        return $this->petService->findPets($tags, $limit);
    }

    /**
     * @Post("")
     */
    public function addPet()
    {
        $request = new NewPetRequest($_POST);
        $this->validator->validate($request);
        return $this->petService->addPet($request);
    }
}
