<?php
$GLOBALS['THRIFT_ROOT'] = 'php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift.php';
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/THttpClient.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/packages/hello/UserManager.php';

use hello\UserManagerClient;
use hello\SexType;
use hello\User;
use hello\InvalidValueException;

try {

    $socket = new TSocket('localhost', 9090);
    $transport = new TBufferedTransport($socket, 1024, 1024);
    $protocol = new TBinaryProtocol($transport);
    $client = new UserManagerClient($protocol);

    $transport->open();
    $client->ping();
    $u = new User();
    $u->user_id = 1;
    $u->firstname = 'John';
    $u->lastname = 'Smith';
    $u->sex = SexType::MALE;
    if ($client->add_user($u))
    {
        echo 'user added succesfully</br>';
    }
    
    var_export($client->get_user(0));
    
    $client->clear_list();

    $u2 = new User();
    $client->add_user($u2);
    
} catch (InvalidValueException $e) {
    echo $e->error_msg.'<br/>';
}

?>
