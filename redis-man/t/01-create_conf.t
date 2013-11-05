#!/usr/bin/perl
use Test::More;
BEGIN { use_ok( "RedisMan" ); }
use FindBin qw/$Bin/;

my $content;
RedisMan::create_conf($Bin.'/data', 59595, 1, {path => '/home/admin/redis'});
is(file_get_contents($Bin.'/data/redis-59595.conf'), <<EOF, 'master config ok');
daemonize yes
pidfile /home/admin/redis/logs/redis-59595.pid
port 59595
# save 10
logfile /home/admin/redis/logs/stdout-59595.log
dbfilename dump-59595.rdb
dir 59595/data
# slaveof {{master_host}} {{master_port}}
EOF

RedisMan::create_conf($Bin.'/data', 59596, 0, {
    path => '/home/admin/redis',
    master_host => 'localhost',
    master_port => '59595'
});
is(file_get_contents($Bin.'/data/redis-59596.conf'), <<EOF, 'slave config ok');
daemonize yes
pidfile /home/admin/redis/logs/redis-59596.pid
port 59596
save 10
logfile /home/admin/redis/logs/stdout-59596.log
dbfilename dump-59596.rdb
dir 59596/data
slaveof localhost 59595
EOF

done_testing();

sub file_get_contents {
    my $file = shift;
    open(my $fh, "<", $file) or die "Can't open file $file: $!";
    local $/ = undef;
    return <$fh>;
}

