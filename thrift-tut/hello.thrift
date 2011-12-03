namespace cpp hello
namespace perl hello
namespace php hello

enum SexType {
  MALE = 1,
  FEMALE = 2
}

struct User {
  1: string firstname,
  2: string lastname,
  3: i32 user_id = 0,
  4: SexType sex,
  5: bool active = 0,
  6: optional string description
}

exception InvalidValueException {
  1: i32 error_code,
  2: string error_msg
}

service UserManager {
  void ping(),
  i32 add_user(1:User u) throws(1: InvalidValueException e),
  User get_user(1:i32 uid) throws (1: InvalidValueException e),
  oneway void clear_list()
}
