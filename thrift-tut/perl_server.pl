#!/usr/bin/perl

#
# Licensed to the Apache Software Foundation (ASF) under one
# or more contributor license agreements. See the NOTICE file
# distributed with this work for additional information
# regarding copyright ownership. The ASF licenses this file
# to you under the Apache License, Version 2.0 (the
# "License"); you may not use this file except in compliance
# with the License. You may obtain a copy of the License at
#
#   http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing,
# software distributed under the License is distributed on an
# "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
# KIND, either express or implied. See the License for the
# specific language governing permissions and limitations
# under the License.
#

use strict;
use lib 'gen-perl';
use Thrift::Socket;
use Thrift::Server;
use hello::UserManager;

package UserManagerHandler;
use base qw(hello::UserManagerIf);
use Data::Dumper qw(Dumper);
my @users;

sub new {
    my $classname = shift;
    my $self      = {};

    return bless($self,$classname);
}


sub ping
{
  print STDERR "ping()\n";
}

sub throw {
    my ($error_code, $error_msg) = @_;
    my $e = new hello::InvalidValueException;
    $e->error_code($error_code);
    $e->error_msg($error_msg);
    die $e;
}

sub add_user {
    my ($self, $user) = @_;
    if ( !defined($user->{firstname}) ) {
        throw(1, 'no firstname exception');
    }
    if ( !defined($user->{lastname}) ) {
        throw(2, 'no lastname exception');
    }
    if ( $user->{user_id} <= 0 ) {
        throw(3, 'wrong user_id');
    }
    if ( $user->{sex} != hello::SexType::MALE && $user->{sex} != hello::SexType::FEMALE ) {
        throw(4, 'wrong sex type');
    }
    print STDERR "add user: ". Dumper($user), "\n";
    push @users, $user;
    return 1;
}

sub get_user {
    my ($self, $user_id) = @_;
    if ( $user_id < 0 || $user_id > $#users ) {
        throw(5, 'wrong user id');
    }
    print STDERR "get user $user_id\n";
    return $users[$user_id];
}

sub clear_list {
    print STDERR "clear user list";
    @users = ();
}

package main;

eval {
  my $handler       = new UserManagerHandler;
  my $processor     = new hello::UserManagerProcessor($handler);
  my $serversocket  = new Thrift::ServerSocket(9090);
  my $forkingserver = new Thrift::ForkingServer($processor, $serversocket);
  print "Starting the server...\n";
  $forkingserver->serve();
  print "done.\n";
};

if ($@) {
  if ($@ =~ m/TException/ and exists $@->{message}) {
    my $message = $@->{message};
    my $code    = $@->{code};
    my $out     = $code . ':' . $message;
    die $out;
  } else {
    die $@;
  }
}

