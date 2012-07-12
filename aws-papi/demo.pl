#!/usr/bin/perl -w
# demo.pl --- 
# Author: Ye Wenbin <dabing.ywb@taobao.com>
# Created: 12 Jul 2012
# Version: 0.01

use warnings;
use strict;
use Data::Dumper qw(Dumper);
use lib qw(lib);
use Net::Amazon;
use Net::Amazon::RequestX::ASIN;
use Log::Log4perl qw(:easy);

Log::Log4perl->easy_init($DEBUG);

my $ua = Net::Amazon->new(
    'token' => '',
    'secret_key' => '',
    'associate_tag' => 'ruyitao-20',
);

my @item_ids = qw( B004MYFTF6 B004V3KCIM B003B3P2CY B001ET5O70 B003ZX8B2S
B001H4B0AC B004MYFTFQ B007M4Z7GO B001VEJEGK B004V3KD4K B004MYFTEW
B003B3P2CO B004MYFTDI B003ZX8B3W B005J8OBJE B000U9WVW6 );

my $request = Net::Amazon::RequestX::ASIN->new(
    'type' => 'Small',
    'asin' => \@item_ids
);
my $response = $ua->request($request);
print Dumper($response), "\n";

__END__

=head1 NAME

demo.pl - Describe the usage of script briefly

=head1 SYNOPSIS

demo.pl [options] args

      -opt --long      Option description

=head1 DESCRIPTION

Stub documentation for demo.pl, 

=head1 AUTHOR

Ye Wenbin, E<lt>dabing.ywb@taobao.comE<gt>

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2012 by Ye Wenbin

This program is free software; you can redistribute it and/or modify
it under the same terms as Perl itself, either Perl version 5.8.2 or,
at your option, any later version of Perl 5 you may have available.

=head1 BUGS

None reported... yet.

=cut
