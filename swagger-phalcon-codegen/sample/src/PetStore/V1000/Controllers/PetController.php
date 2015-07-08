<?php
/**
 *  Copyright 2015 SmartBear Software
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */
namespace PetStore\V1000\Controllers;

use PetStore\V1000\Models\Pet;
use PhalconX\Validators\Range;
        
/**
 * NOTE: This class is auto generated by the swagger code generator program. Do not edit the class manually.
 *
 * @RoutePrefix("/v2")
 */
class PetController extends BaseController {
    /**
     * Update an existing pet
     *
     * @PUT("/pet")
     */
    public function updatePet() {
        $body = $this->objectConverter->convert($_POST, Pet::CLASS);
        $this->petService->updatePet($body);
    }

    /**
     * Add a new pet to the store
     *
     * @POST("/pet")
     */
    public function addPet() {
        $body = $this->objectConverter->convert($_POST, Pet::CLASS);
        $this->petService->addPet($body);
    }

    /**
     * Finds Pets by status
     *
     * @GET("/pet/findByStatus")
     */
    public function findPetsByStatus() {
        $status = $this->request->get("status");
        $this->validator->validate([
            [
                'name' => 'status',
                'value' => &$status,
                'default' => 'available',
                'type' => 'array'
            ]
        ]);
        $this->petService->findPetsByStatus($status);
    }

    /**
     * Finds Pets by tags
     *
     * @GET("/pet/findByTags")
     */
    public function findPetsByTags() {
        $tags = $this->request->get("tags");
        $this->validator->validate([
            [
                'name' => 'tags',
                'value' => $tags,
                'type' => 'array'
            ]
        ]);
        $this->petService->findPetsByTags($tags);
    }

    /**
     * Find pet by ID
     *
     * @GET("/pet/{petId}")
     */
    public function getPetById($pet_id) {
        $this->validator->validate([
            [
                'name' => 'pet_id',
                'value' => $pet_id,
                'type' => 'integer',
                'required' => true,
                'validator' => new Range(['minimum' => 1.0])
            ]
        ]);
        $this->petService->getPetById($pet_id);
    }

    /**
     * Updates a pet in the store with form data
     *
     * @POST("/pet/{petId}")
     */
    public function updatePetWithForm($pet_id) {
        $name = $this->request->getPost("name");
        $status = $this->request->getPost("status");
        $this->validator->validate([
            [
                'name' => 'pet_id',
                'value' => $pet_id,
                'required' => true
            ]
        ]);
        $this->petService->updatePetWithForm($pet_id, $name, $status);
    }

    /**
     * Deletes a pet
     *
     * @DELETE("/pet/{petId}")
     */
    public function deletePet($pet_id) {
        $api_key = $this->request->getHeader("api_key");
        $this->validator->validate([
            [
                'name' => 'pet_id',
                'value' => $pet_id,
                'type' => 'integer',
                'required' => true
            ]
        ]);
        $this->petService->deletePet($api_key, $pet_id);
    }

    /**
     * uploads an image
     *
     * @POST("/pet/{petId}/uploadImage")
     */
    public function uploadFile($pet_id) {
        $additional_metadata = $this->request->getPost("additionalMetadata");
        $file = $this->getFile("file");
        $this->validator->validate([
            [
                'name' => 'pet_id',
                'value' => $pet_id,
                'type' => 'integer',
                'required' => true
            ]
        ]);
        $this->petService->uploadFile($pet_id, $additional_metadata, $file);
    }
}
