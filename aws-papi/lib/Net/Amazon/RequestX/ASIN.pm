package Net::Amazon::RequestX::ASIN;
######################################################################
use base qw(Net::Amazon::Request);

use constant MAX_ASINS_PER_REQUEST => 20;

sub new {
    my($class, %options) = @_;

    $class->_assert_options_defined(\%options, 'asin');

    $class->_convert_option(\%options,
                            'asin',
                            'ItemId',
                            \&_process_asin_option);
    
    my $self = $class->SUPER::new(%options);

    bless $self, $class;   # reconsecrate
}

sub _process_asin_option {
    my ($options, $key) = @_;

    my $item_ids = $options->{$key};
    if ( ref $item_ids eq 'ARRAY' ) {
        die "Only ".MAX_ASINS_PER_REQUEST." may be requested at a time"
            if ( @{$item_ids} > MAX_ASINS_PER_REQUEST );
        my $half = MAX_ASINS_PER_REQUEST/2;
        if ( @{$item_ids} > $half  ) {
            $options->{'ItemLookup.1.'. $key} = join(',', @{$item_ids}[0..($half-1)]);
            $options->{'ItemLookup.2.'. $key} = join(',', @{$item_ids}[$half..$#{$item_ids}]);
            delete $options->{$key};
        } else {
            $options->{$key} = join(',', @{$item_ids});
        }
    } elsif ( ref $item_ids ) {
        die "The 'asin' parameter must either be a scalar or an array";
    }
    return 1;
}

1;
