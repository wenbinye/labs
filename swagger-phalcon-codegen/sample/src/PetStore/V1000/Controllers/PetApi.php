
# This is a sample api mustache template.  It is representing a ficticous 
# language and won't be usable or compile to anything without lots of changes.
# Use it as an example.  You can access the variables in the generator object
# like such:

# use the package from the `apiPackage` variable
package: PetStore\V1000\Controllers

# operations block
classname: PetApi

# loop over each operation in the API:

# each operation has a `nickname`:
nickname: updatePet

# and parameters:
body: Pet


# each operation has a `nickname`:
nickname: addPet

# and parameters:
body: Pet


# each operation has a `nickname`:
nickname: findPetsByStatus

# and parameters:
status: array[string]


# each operation has a `nickname`:
nickname: findPetsByTags

# and parameters:
tags: array[string]


# each operation has a `nickname`:
nickname: getPetById

# and parameters:
pet_id: int


# each operation has a `nickname`:
nickname: updatePetWithForm

# and parameters:
pet_id: string
name: string
status: string


# each operation has a `nickname`:
nickname: deletePet

# and parameters:
api_key: string
pet_id: int


# each operation has a `nickname`:
nickname: uploadFile

# and parameters:
pet_id: int
additional_metadata: string
file: string


# end of operations block
