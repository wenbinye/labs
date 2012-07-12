package Net::Amazon::ResponseX::ASIN;

use strict;
use warnings;

use Carp;

use Net::Amazon::Property;
use base qw(Net::Amazon::Response::ASIN);

$Net::Amazon::Property::DEFAULT_ATTRIBUTES_XPATH{CurrencyCode}
    = [qw(Offers Offer OfferListing Price CurrencyCode)];

sub get_xml {
    my $self = shift;
    return $self->{xml};
}

sub xml_parse {
    my ($self, $xml) = @_;
    $self->{xml} = $xml;
    my $xs = XML::Simple->new();
    my $ref = $xs->XMLin($xml, ForceArray => [ @Net::Amazon::Response::FORCE_ARRAY_FIELDS, 'Items', 'Item' ]);
    if ( exists $ref->{Items} && ref $ref->{Items} eq 'ARRAY' ) {
        my %items;
        foreach my $item ( @{$ref->{Items}} ) {
            push @{$items{Item}}, @{$item->{Item}} if $item->{Item};
            $items{Request} = $item->{Request};
        }
        $ref->{Items} = \%items;
    }
    return $ref;
}

sub properties {
    my ($self) = @_;
    my @properties = $self->SUPER::properties();
    foreach my $p ( @properties ) {
        if ( exists $p->{xmlref}{EditorialReviews} ) {
            $p->{EditorialReviews} = $p->{xmlref}{EditorialReviews}{EditorialReview};
        }
    }
    if ( wantarray ) {
        return (@properties);
    }
    if ( @properties ) {
        return $properties[0];
    }
}

1;
