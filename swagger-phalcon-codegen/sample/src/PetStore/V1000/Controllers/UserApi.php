
# This is a sample api mustache template.  It is representing a ficticous 
# language and won't be usable or compile to anything without lots of changes.
# Use it as an example.  You can access the variables in the generator object
# like such:

# use the package from the `apiPackage` variable
package: PetStore\V1000\Controllers

# operations block
classname: UserApi

# loop over each operation in the API:

# each operation has a `nickname`:
nickname: createUser

# and parameters:
body: User


# each operation has a `nickname`:
nickname: createUsersWithArrayInput

# and parameters:
body: array[User]


# each operation has a `nickname`:
nickname: createUsersWithListInput

# and parameters:
body: array[User]


# each operation has a `nickname`:
nickname: loginUser

# and parameters:
username: string
password: string


# each operation has a `nickname`:
nickname: logoutUser

# and parameters:


# each operation has a `nickname`:
nickname: getUserByName

# and parameters:
username: string


# each operation has a `nickname`:
nickname: updateUser

# and parameters:
username: string
body: User


# each operation has a `nickname`:
nickname: deleteUser

# and parameters:
username: string


# end of operations block
