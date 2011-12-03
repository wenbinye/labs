CC=g++ -Wall -O2 -lthrift
CFLAGS=-g -I./gen-cpp -I/usr/local/include/thrift/ -c
GEN=gen-cpp
SRC=cpp
OBJS=${GEN}/hello_constants.o ${GEN}/hello_types.o ${GEN}/UserManager.o ${SRC}/UserManagerHandler.o ${SRC}/UserManager_server.o

all: UserManager_server

thrift: hello.thrift
	thrift --gen cpp hello.thrift
	thrift --gen perl hello.thrift
	thrift --gen php:namespace=hello hello.thrift

UserManager_server: ${OBJS}
	$(CC) ${OBJS} -o $@

%.o: %.cpp
	$(CC) -c $(CFLAGS) $(CPPFLAGS) $< -o $@ 

clean:
	rm -rf ${GEN}/*.o UserManager_server

realclean: clean
	rm -rf gen-cpp gen-php gen-perl

.PHONY: realclean clean