#ifndef UserManagerHandler_H
#define UserManagerHandler_H

#include "UserManager.h"
namespace hello 
{
    class UserManagerHandler : virtual public UserManagerIf {
    public:
        UserManagerHandler() {
        }
        void ping();
        int32_t add_user(const User&u);
        void get_user(User& _return, const int32_t uid);
        void clear_list();
    };
}

#endif
