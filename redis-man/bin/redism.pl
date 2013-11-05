#!/usr/bin/env perl
# redism.pl --- 
# Author: Ye Wenbin <dabing.ywb@taobao.com>
# Created: 05 Nov 2013
# Version: 0.01

use warnings;
use strict;

use RedisMan;

my %config = (
    'src_dir' => '/home/dabing.ywb/src/redis',
    'deploy_dir' => '/home/dabing.ywb/redis',
    'db' => { 'server' => 'localhost:59595' },
    'servers_key' => 'redis_servers:servers',
    'alias_key' => 'redis_servers:alias',
    'slaves_key' => 'redis_servers:slaves',
);

RedisMan->new(\%config)->dispatch;
