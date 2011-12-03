#include "UserManagerHandler.h"
#include <vector>
#include <string>

namespace hello {
    static std::vector<User> users;
    void UserManagerHandler::ping() {
        // Your implementation goes here
        printf("ping\n");
    }

    int32_t UserManagerHandler::add_user(const User& u) {
        InvalidValueException e;
        // Your implementation goes here
        printf("add_user\n");
        if ( u.firstname.empty() ) {
            e.error_code = 1;
            e.error_msg = std::string("invalid firstname");
            throw e;
        }
        if ( u.lastname.empty() ) {
            e.error_code = 2;
            e.error_msg = std::string("invalid lastname");
            throw e;
        }
        if ( u.user_id <= 0 ) {
            e.error_code = 3;
            e.error_msg = std::string("invalid user_id");
            throw e;
        }
        if ( u.sex != SexType::MALE && u.sex != SexType::FEMALE ) {
            e.error_code = 4;
            e.error_msg = std::string("invalid sex type");
            throw e;
        }
        users.push_back(u);
        return true;
    }

    void UserManagerHandler::get_user(User& _return, const int32_t uid) {
        // Your implementation goes here
        printf("get_user\n");
        if ( uid < users.size() ) {
            _return = users[uid];
        } else {
            InvalidValueException e;
            e.error_code = 5;
            e.error_msg = std::string("invalid user id");
            throw e;
        }
    }

    void UserManagerHandler::clear_list() {
        // Your implementation goes here
        printf("clear_list\n");
        users.clear();
    }
} // end namespace
