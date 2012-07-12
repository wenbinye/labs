package Log::Parser;
use Moose;
use namespace::autoclean;
use Time::Local;

has format => (
    isa => 'Str',
    is => 'rw',
    default => 'combined'
);

## PRIVATE ATTRIBUTES ##

has regexp => (
    isa => 'RegexpRef',
    is => 'ro',
    lazy => 1,
    builder => '_build_format_regexp'
);

has fields => (
    isa => 'ArrayRef',
    is => 'ro',
    lazy => 1,
    builder => '_build_format_fields'
);

__PACKAGE__->meta->make_immutable;

my %months = (
	jan => 0,
	feb => 1,
	mar => 2,
	apr => 3,
	may => 4,
	jun => 5,
	jul => 6,
	aug => 7,
	sep => 8,
	sep => 8,
	oct => 9,
	nov => 10,
	dec => 11,
);

my %LogFormats = (
    'combined' => {
        'regexp' => qr/(?-xism:^(\S+) (?:.*?) (?:.*?) (?:\[(\d{2}\/\w{3}\/\d{4}(?::\d{2}){3} [-+]\d{4})\]) (?:\"(.*?)\") (\d+) (-|\d+) (?:\"(.*?)\") (?:\"(.*?)\"))/,
        'fields' => [qw/ip date req status bytes ref ua/]
    },
    'timed' => {
        'regexp' => qr/(?-xism:^(\S+) (?:.*?) (?:.*?) (?:\[(\d{2}\/\w{3}\/\d{4}(?::\d{2}){3} [-+]\d{4})\]) (?:\"(.*?)\") (\d+) (-|\d+) (?:\"(.*?)\") (?:\"(.*?)\")(?: (-|[\d\.]+) (?:-|[\d\.]+) (?:[.p]))?$)/,
        'fields' => [qw/ip date req status bytes ref ua request_time/]
    }
);

sub _get_format_entry {
    my ($format, $entry) = @_;
    if ( !exists $LogFormats{$format} ) {
        confess "Format '$format' not support!\n";
    }
    return $LogFormats{$format}{$entry};
}

sub _build_format_regexp {
    my $self = shift;
    return _get_format_entry($self->format, 'regexp');
}

sub _build_format_fields {
    my $self = shift;
    return _get_format_entry($self->format, 'fields');
}

sub register_format {
    my ($name, $regexp, $fields) = @_;
    $LogFormats{$name} = {
        'regexp' => $regexp,
        'fields' => $fields
    };
}

sub parse {
    my $self = shift;
    my $fields = $self->fields;
    my $re = $self->regexp;
    my %log;
    @log{@$fields} = ($_[0] =~ /$re/);
    # regexp match successfully
    if ( defined $log{$fields->[0]} ) {
        if ( exists $log{req} ) {
            my ($method, $url) = split /\s+/, $log{req};
            delete $log{req};
            $log{url} = $url || '';
            # nginx dump non-utf8 characters use \x[0-f][0-f]
            $log{url} =~ s/([^\\])\\x([0-9a-fA-F]{2})/$1.chr(hex($2))/eg;
        }
        return \%log;
    }
    return undef;
}

sub parse_date {
    my $datestr = shift;
    my ($date) = split / /, $datestr;
    my ($day, $month, $year, $hour, $min, $sec) = split /[\/:]/, $date;
    $month = lc($month);
    if ( exists $months{$month} ) {
        return timelocal($sec, $min, $hour, $day, $months{$month}, $year);
    }
}

1;

__END__

=head1 NAME

Log::Parser - Perl Module for Web Log Parsing

=head1 SYNOPSIS

   use Log::Parser;
   use Data::Dumper;
   my $parser = Log::Parser->new;
   my $log = $parser->parse('127.0.0.1 - - [19/Sep/2011:11:06:26 +0800] "GET / HTTP/1.1" 200 13832 "-" "curl/7.21.3 (i686-pc-linux-gnu) libcurl/7.21.3 OpenSSL/0.9.8o zlib/1.2.3.4 libidn/1.18"');
   print Data::Dumper::Dumper($log);
   # output:
   # $VAR1 = {
   #     'ua' => 'curl/7.21.3 (i686-pc-linux-gnu) libcurl/7.21.3 OpenSSL/0.9.8o zlib/1.2.3.4 libidn/1.18',
   #     'bytes' => '13832',
   #     'url' => '/',
   #     'ref' => '-',
   #     'status' => '200',
   #     'date' => '19/Sep/2011:11:06:26 +0800',
   #     'ip' => '127.0.0.1'
   # }

=head1 DESCRIPTION

This module provides simple interface to parse web log (or either any log by
registering your own log format). It is simple using the regexp to match given
log text line, and capture the matching words use given name to construct a
hash.

=head1 METHODS

=over 4

=item new

This is a class method, the constructor for Log::Parser. Set 'format'
options only. There are two web log format defined:

=over 2

=item combined

This common log format.

=item timed

log contains request_time and other time.

=back

=item parse(LOG_LINE)

The LOG_LINE parameter should be a line of log.

=back

=head1 CLASS METHODS

=over 4

=item register_format(FORMAT_NAME, REGEXP, FIELD_NAMES)

This method register a new log format.

=item parse_date(DATE_STRING)

This method parse the DATE_STRING to unix time stamp. The DATE_STRING should
be in format of 'dd/month/yyyy:hh:ii:ss‘, eg. '19/Sep/2011:11:06:26'. Extra text
will be ignored.

=back

=cut

