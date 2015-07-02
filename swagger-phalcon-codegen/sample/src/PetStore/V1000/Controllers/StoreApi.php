
# This is a sample api mustache template.  It is representing a ficticous 
# language and won't be usable or compile to anything without lots of changes.
# Use it as an example.  You can access the variables in the generator object
# like such:

# use the package from the `apiPackage` variable
package: PetStore\V1000\Controllers

# operations block
classname: StoreApi

# loop over each operation in the API:

# each operation has a `nickname`:
nickname: getInventory

# and parameters:


# each operation has a `nickname`:
nickname: placeOrder

# and parameters:
body: Order


# each operation has a `nickname`:
nickname: getOrderById

# and parameters:
order_id: string


# each operation has a `nickname`:
nickname: deleteOrder

# and parameters:
order_id: string


# end of operations block
