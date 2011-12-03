Base on http://www.thrift.pl/Thrift-tutorial-our-own-hello-world.html

Download thrift from http://thrift.apache.org/download/

Install thrift (http://wiki.apache.org/thrift/GettingUbuntuPackages)

    tar zxvf thrift-0.7.0.tar.gz 
    export THRIFT_DIR=`pwd`thrift-0.7.0


    git clone https://github.com/wenbinye/labs
    cd labs/thrift-tut
    make thrift
    make
    cp $THRIFT_DIR/lib/php php
    mkdir -p php/packages
    ln -sf `pwd`/gen-php/hello php/packages
    
Start perl server:

    perl -I/usr/local/lib/perl5 perl_server.pl
    
Or start cpp server

    ./UserManager_server
    
run client:

    php client.php
