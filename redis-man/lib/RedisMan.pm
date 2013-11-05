package RedisMan;

use strict; 
use warnings;

use Carp;
use File::Spec;
use Getopt::Long;
use POSIX;
use Redis;
use JSON::XS;
use constant DEFAULT_PORT => 6379;

our $DEBUG = 1;

my %OPTIONS = (
    deploy => ['slaveof=s','alias=s'],
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
        ($vars{master_port}, $vars{master_port}) = parse_server($options->{slaveof});
    }
    my $conf_dir = File::Spec->catdir($src_dir,'conf');
    my $conf_file = create_conf($conf_dir, $port, !!$options->{slaveof}, \%vars);
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
        if ( $options->{alias} ) {
            $self->alias($server, $options->{alias});
        }
    } else {
        print STDERR "deploy failed\n";
    }
}

sub action_alias {
    my ($self, $options, $server, $alias) = @_;
    if ( !$server || !$alias ) {
        $self->help("Command 'alias' requires server and alias");
    }
    $self->alias($server, $alias);
}

sub action_register {
    my ($self, $options, $server) = @_;
    if ( !$server ) {
        $self->help("Command 'register' requires server");
    }
    $self->register($server);
    print "Register $server ok";
}

sub action_info {
}

sub register {
    my ($self, $server) = @_;
    my $redis = $self->redis();
    my $key = $self->get_config('servers_key');
    if ( $redis->hexists($key, $server) ) {
        return 1;
    } 
    $redis->hset($key, $server, encode_json({"create_time" => POSIX::strftime('%F %T', localtime)}));
}

sub alias {
    my ($self, $server, $alias) = @_;
    my $redis = $self->redis();
    if ( !$redis->hexists($self->get_config('servers_key'), $server) ) {
        print STDERR "Redis server '$server' not found\n";
        exit;
    }
    my $key = $self->get_config('alias_key');
    my $old = $redis->hget($key, $alias);
    if ( $old ) {
        print STDERR "'$alias' is assigned to redis server $old. You can use following command to unset the alias:\n",
            "\t$0 alias $old\n";
        exit;
    }
    $redis->hset($key, $alias, $server);
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

redism.pl deploy localhost:6389 --alias 1

redism.pl deploy localhost:6390 --slaveof localhost:6389

redism.pl alias localhost:6389 1

redism.pl start localhost:6389

redism.pl start 1


=head1 DESCRIPTION



=cut

