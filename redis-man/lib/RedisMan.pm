package RedisMan;

use strict; 
use warnings;

use Carp qw();
use File::Spec qw();
use Getopt::Long qw(GetOptions);
use POSIX qw(strftime);
use Redis qw();
use JSON::XS qw(decode_json encode_json);
use constant DEFAULT_PORT => 6379;
use Data::Dumper qw(Dumper);

our $DEBUG = 1;

my %OPTIONS = (
    deploy => ['slaveof=s','alias=s'],
    register => ['alias=s']
);

sub new {
    my $class = shift;
    my $self = {};
    bless $self, $class;
    $self->{config} = shift;
    return $self;
}

sub get_config {
    my ($self, $name) = @_;
    return exists $self->{config}{$name} ? $self->{config}{$name} : '';
}

sub redis {
    my $self = shift;
    if ( !$self->{redis} ) {
        $self->{redis} = new Redis(%{$self->get_config('db')});
    }
    return $self->{redis};
}

sub dispatch {
    my $self = shift;
    if ( !@ARGV ) {
        $self->help();
    }
    my $cmd = shift @ARGV;
    $cmd =~ s/-/_/;
    my $method = 'action_' . $cmd;
    if ( $self->can($method) ) {
        my %options;
        if ( exists $OPTIONS{$cmd} ) {
            GetOptions(\%options, @{$OPTIONS{$cmd}});
        }
        $self->$method(\%options, @ARGV);
    }
}

sub action_deploy {
    my ($self, $options, $server) = @_;
    if ( !$server ) {
        $self->help("Command 'deploy' requires server");
    }
    my $src_dir = $self->get_config('src_dir');
    my $deploy_dir = $self->get_config('deploy_dir');
    my ($host, $port) = parse_server($server);
    my %vars = (path => $deploy_dir);
    if ( $options->{slaveof} ) {
        ($vars{master_host}, $vars{master_port}) = $self->get_server($options->{slaveof});
    }
    my $conf_dir = File::Spec->catdir($src_dir,'conf');
    my $conf_file = create_conf($conf_dir, $port, !$options->{slaveof}, \%vars);
    my @cmd;
    if ( redis_exists($host, $deploy_dir) ) {
        debug("Redis is found in $host directory $deploy_dir");
        @cmd =("scp", "-q", $conf_file, "$host:$deploy_dir/conf");
    } else {
        debug("Copy $src_dir to $host directory $deploy_dir");
        @cmd =("scp", "-q", "-r", $src_dir, "$host:$deploy_dir");
    }
    if ( do_system(@cmd) ) {
        print STDERR "deploy ok\n";
        $self->register($server);
        if ( $options->{slaveof} ) {
            $self->slaveof($server, $vars{master_host}.':'.$vars{master_port});
        }
        if ( $options->{alias} ) {
            $self->alias($server, $options->{alias});
        }
    } else {
        print STDERR "deploy failed\n";
    }
}

sub action_alias {
    my ($self, $options, $server, $alias) = @_;
    if ( !$server ) {
        $self->help("Command 'alias' requires server and alias");
    }
    my $ret = $self->alias($server, $alias);
    if ( $alias ) {
        print "Alias $server to $ret\n";
    } else {
        print "Remove $server alias '$ret'\n";
    }
}

sub action_register {
    my ($self, $options, $server) = @_;
    if ( !$server ) {
        $self->help("Command 'register' requires server");
    }
    $self->register($server);
    if ( $options->{alias} ) {
        $self->alias($server, $options->{alias});
    }
    print "Register $server ok\n";
}

sub action_remove {
    my ($self, $options, $server) = @_;
    if ( !$server ) {
        $self->help("Command 'remove' requires server");
    }
    my ($host, $port) = $self->get_server($server);
    $self->remove($host.':'.$port);
    print "Remove $host:$port ok\n";
}

sub action_list {
    my ($self, $options) = @_;
    my $redis = $self->redis();
    my $servers = hgetall($redis, $self->get_config('servers_key'));
    my $aliases = hgetall($redis, $self->get_config('alias_key'));
    my %alias = reverse(%$aliases);
    print join("\t", "server", "create time", "alias", "master", "slaves"), "\n";
    for my $server ( sort keys %$servers ) {
        my $data = decode_json($servers->{$server});
        my ($master, $slaves) = ('', '');
        if ( $data->{master} || $data->{slaves} ) {
            $master = $data->{master} || '[master]';
            $slaves = $data->{slaves} ? join(",", keys %{$data->{slaves}}) : '[no slaves]';
        }
        print join("\t", $server, $data->{create_time}, $alias{$server} || '[no alias]', $master, $slaves), "\n";
    }
}

sub action_start {
    my ($self, $options, $server) = @_;
    my ($host, $port) = $self->get_server($server);
    my $deploy_dir = $self->get_config('deploy_dir');
    my $ret = do_system("ssh $host '$deploy_dir/bin/redis-server $deploy_dir/conf/redis-$port.conf'");
    if ( $ret ) {
        print "Start $host:$port successfully\n";
    } else {
        print "Start $host:$port failed\n";
    }
}

sub action_stop {
    my ($self, $options, $server) = @_;
    my ($host, $port) = $self->get_server($server);
    my $deploy_dir = $self->get_config('deploy_dir');
    my $ret = do_system("ssh $host 'kill `cat $deploy_dir/logs/redis-$port.pid`'");
    if ( $ret ) {
        print "Stop $host:$port successfully\n";
    } else {
        print "Stop $host:$port failed\n";
    }
}

sub action_info {
    my ($self, $options, $server) = @_;
}

sub hgetall {
    my ($redis, $key) = @_;
    my $ret = $redis->hgetall($key);
    my %data;
    for ( my $i=0; $i<$#{$ret}; $i+=2 ) {
        $data{$ret->[$i]} = $ret->[$i+1];
    }
    return \%data;
}

sub slaveof {
    my ($self, $server, $master) = @_;
    my $data = $self->get_server_data($server);
    $data->{master} = $master;
    $self->set_server_data($server, $data);
    my $master_data = $self->get_server_data($master);
    $master_data->{slaves}{$server} = 1;
    $self->set_server_data($master, $master_data);
}

sub register {
    my ($self, $server) = @_;
    my $data = $self->get_server_data($server);
    if ( exists $data->{create_time} ) {
        return 1;
    }
    $self->set_server_data($server, {"create_time" => POSIX::strftime('%F %T', localtime)});
}

sub remove {
    my ($self, $server) = @_;
    my $redis = $self->redis();
    my $data = $self->get_server_data($server);
    if ( $data->{alias} ) {
        $redis->hdel($self->get_config('alias_key'), $data->{alias});
    }
    $redis->hdel($self->get_config('servers_key'),$server);
}

sub get_server_data {
    my ($self, $server) = @_;
    my $data = $self->redis()->hget($self->get_config('servers_key'), $server);
    if ( $data ) {
        $data = eval { decode_json($data) };
        if ( $@ ) {
            return {};
        }
        return $data;
    }
    return {};
}

sub set_server_data {
    my ($self, $server, $data) = @_;
    $self->redis()->hset($self->get_config('servers_key'), $server, encode_json($data));
}

sub alias {
    my ($self, $server, $alias) = @_;
    my $redis = $self->redis();
    my $data = $self->get_server_data($server);
    if ( !$data ) {
        print STDERR "Redis server '$server' not found\n";
        exit;
    }
    my $key = $self->get_config('alias_key');
    if ( !$alias ) {
        if ( $data->{alias} ) {
            $alias = $data->{alias};
            delete $data->{alias};
            $self->set_server_data($server, $data);
        }
        my $aliases = hgetall($redis, $key);
        my %alias = reverse(%$aliases);
        if ( exists $alias{$server} ) {
            $alias = $alias{$server};
            $redis->hdel($key, $alias{$server});
        }
        return $alias;
    }
    my $old = $redis->hget($key, $alias);
    if ( $old ) {
        print STDERR "'$alias' is assigned to redis server $old. You can use following command to unset the alias:\n",
            "\t$0 alias $old\n";
        exit;
    }
    $data->{alias} = $alias;
    $self->set_server_data($server, $data);
    $redis->hset($key, $alias, $server);
    return $alias;
}

sub help {
    my ($self, $msg, $exit) = @_;
    if ( $msg ) {
        print STDERR $msg, "\n\n";
    }
    print "Usage:\n\t$0 command [options]\n";
    exit($exit || 0);
}

sub parse_server {
    my $server = shift;
    my ($host, $port) = split ':', $server;
    if ( !$port ) {
        $port = DEFAULT_PORT;
    }
    return ($host, $port);
}

sub get_server {
    my ($self, $server_or_alias) = @_;
    my $redis = $self->redis();
    my $server = $redis->hget($self->get_config('alias_key'), $server_or_alias);
    my ($host,$port) = parse_server($server || $server_or_alias);
    if ( $redis->hexists($self->get_config('servers_key'), $host.':'.$port) ) {
        return ($host,$port);
    } else {
        print STDERR "Cann't not find server match '$server', forget register it?\n";
        exit;
    }
}

sub redis_exists {
    my ($host, $dir) = @_;
    return (system("ssh $host 'ls $dir > /dev/null 2>&1'") == 0);
}

sub do_system {
    my @cmd = @_;
    debug(join(" ", @cmd));
    my $ret = (system(@cmd) == 0);
    if ( !$ret ) {
        print STDERR "Failed: $!\n";
    }
    return $ret;
}

sub debug {
    my $msg = shift;
    if ( $DEBUG ) {
        print STDERR POSIX::strftime("%F %T", localtime), " ", $msg, "\n";
    }
}

sub create_conf {
    my ($path, $port, $is_master, $options) = @_;
    my $file = File::Spec->catfile($path, 'redis.conf');
    open(my $fh, "<", $file) or die "Can't open file $file: $!";
    my $outfile = File::Spec->catfile($path, "redis-$port.conf");
    open(my $ofh, ">", $outfile) or die "Can't create file $outfile: $!";
    $options->{port} = $port;
    my $comment = "# ";
    while ( <$fh> ) {
        if ( $is_master && /^(save|slaveof)/ ) {
            print {$ofh} $comment, $_;
        } else {
            s/{{(\w+)}}/exists $options->{$1} ? $options->{$1} : ''/eg;  
            print {$ofh} $_;
        }
    }
    close($fh);
    close($ofh);
    return $outfile;
}

1;

__END__
    
=head1 NAME

RedisMan - 
    
=head1 SYNOPSIS

redism.pl deploy localhost:6389 --alias c01

redism.pl deploy localhost:6390 --slaveof localhost:6389

redism.pl alias localhost:6389 c01

redism.pl start localhost:6389

redism.pl start c01

redism.pl cluster-add 'c*'

redism.pl cluster-list 

=head1 DESCRIPTION



=cut

