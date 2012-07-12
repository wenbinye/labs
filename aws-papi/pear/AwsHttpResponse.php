<?php
class AwsHttpResponse extends CFResponse
{
    const PRICE_STATUS_OK       = 0;
    const PRICE_STATUS_NA       = -1;
    const PRICE_STATUS_TOO_LOW  = -2;
    const PRICE_STATUS_NO_OFFER = -3;

    public $content;
    protected $_results;
    public static $ITEM_PROPERTIES_XPATH = array(
        'Availability' => array('Offers', 'Offer', 'OfferListing', 'Availability'),
        'Catalog' => array('ItemAttributes', 'ProductGroup'),
        'Binding' => array('ItemAttributes', 'Binding'),
        'LargeImageUrl' => array('LargeImage', 'URL'),
        'MediumImageUrl' => array('MediumImage', 'URL'),
        'SmallImageUrl' => array('SmallImage', 'URL'),
        'Manufacturer' => array('ItemAttributes', 'Manufacturer'),
        'FormattedPrice' => array('Offers', 'Offer', 'OfferListing', 'Price', 'FormattedPrice'),
        'Price' => array('Offers', 'Offer', 'OfferListing', 'Price', 'Amount'),
        'ThirdPartyNewPrice' => array('OfferSummary', 'LowestNewPrice', 'Amount'),
        'ThirdPartyNewFormattedPrice' => array('OfferSummary', 'LowestNewPrice', 'FormattedPrice'),
        'UsedPrice' => array('OfferSummary', 'LowestUsedPrice', 'Amount'),
        'UsedFormattedPrice' => array('OfferSummary', 'LowestUsedPrice', 'FormattedPrice'),
        'Title' => array('ItemAttributes', 'Title'),
        'TotalOffers' => array('Offers', 'TotalOffers'),
        'ReleaseDate' => array('ItemAttributes', 'ReleaseDate'),
        'EditorialReviews'=>array('EditorialReviews', 'EditorialReview'),
        'UPC'=>array('ItemAttributes', 'EAN'),
    );

	public function __construct($header, $body, $status=null)
	{
        parent::__construct($header, $body, $status);
        if ( $this->body instanceof SimpleXMLIterator ) {
            $this->results = self::xmlToArray($this->body);
        }
    }

    private static function xmlToArray($xml)
    {
        if ( is_object($xml) ){
            $arr = get_object_vars($xml);
            foreach ($arr as $k => $v) {
                $arr[$k] = self::xmlToArray($v);
            }
            return $arr;
        } elseif ( is_array($xml) ) {
            return array_map(array(__CLASS__, 'xmlToArray'), $xml);
        }
        else {
            return $xml;
        }
    }

    public static function getPropertyPath($name)
    {
        return isset(self::$ITEM_PROPERTIES_XPATH[$name])
            ? self::$ITEM_PROPERTIES_XPATH[$name]
            : array($name);
    }

    public static function nodeValue($root, $path)
    {
        if ( null === $root ) {
            return null;
        }
        $len = count($path);
        for ( $i=0; $i<$len; $i++ ) {
            $name = $path[$i];
            if ( is_array($root) ) {
                if ( isset($root[0]) ) {
                    $results = array();
                    $rest_path = array_slice($path, $i);
                    foreach ( $root as $node ) {
                        $r = self::nodeValue($node, $rest_path);
                        if ( is_array($r) && isset($r[0]) ) {
                            foreach ( $r as $elem ) {
                                $results[] = $elem;
                            }
                        } elseif ( isset($r) ) {
                            $results[] = $r;
                        }
                    }
                    $root = $results;
                    break;
                } else {
                    $root = isset($root[$name]) ? $root[$name] : null;
                }
            } else {
                $root = null;
                break;
            }
        }
        return $root;
    }
    
    public function getNode( /* path */ )
    {
        return self::nodeValue($this->results, func_get_args());
    }

    public function getCode()
    {
        return $this->getNode('Error', 'Code');
    }

    public function getMessage()
    {
        return $this->getNode('Error', 'Message');
    }
    
    public function getErrors()
    {
        $errors = $this->getNode('Items', 'Request', 'Errors', 'Error');
        if ( null == $errors ) {
            $errors = array();
        }
        elseif ( !empty($errors) && !isset($errors[0]) ) {
            $errors = array($errors);
        }
        return $errors;
    }

    public function getItems($options=array(), $path=array('Items','Item'))
    {
        $items = self::nodeValue($this->results, $path);
        $result = array();
        if ( empty($items) ) {
            return $result;
        }
        if ( !isset($items[0]) ) {
            $items = array($items);
        }
        $response_group = isset($options['ResponseGroup']) ? $options['ResponseGroup'] : null;
        $fields = array(
            'ASIN', 'Title', 'SmallImageUrl', 'LargeImageUrl', 'FormattedPrice', 'Price',
            'TotalOffers', 'DetailPageURL', 'ThirdPartyNewPrice', 'ThirdPartyNewFormattedPrice',
            'UsedPrice', 'UsedFormattedPrice',
        );
        if ( !empty($response_group) ) {
            $fields = array_merge($fields, array('Catalog', 'Manufacturer', 'Availability', 'UPC', 'EditorialReviews'));
        }
        foreach ( $items as $item ) {
            $props = array();
            foreach ($fields as $name ) {
                $props[$name] = self::nodeValue($item, self::getPropertyPath($name));
            }
            if ( isset($props['EditorialReviews']) && !isset($props['EditorialReviews'][0]) ) {
                $props['EditorialReviews'] = array($props['EditorialReviews']);
            }
            if ( !empty($props['FormattedPrice']) && preg_match('/too low/i', $props['FormattedPrice']) ) {
                unset($props['FormattedPrice']);
                unset($props['Price']);
                $props['PriceStatus'] = self::PRICE_STATUS_TOO_LOW;
            } elseif ( isset($props['TotalOffers']) && $props['TotalOffers'] == 0 ) {
                unset($props['FormattedPrice']);
                $props['Price'] = -1;
                $props['PriceStatus'] = self::PRICE_STATUS_NO_OFFER;
            } elseif ( isset($props['Price']) && $props['Price'] > 0 ) {
                $props['PriceStatus'] = self::PRICE_STATUS_OK;
            }
            // 第三方价格为 too low 时, price_status 也等于 PRICE_STATUS_TOO_LOW
            if ( !empty($props['ThirdPartyNewFormattedPrice']) && preg_match('/too low/i', $props['ThirdPartyNewFormattedPrice']) ) {
                unset($props['ThirdPartyNewFormattedPrice']);
                unset($props['ThirdPartyNewPrice']);
                $props['PriceStatus'] = self::PRICE_STATUS_TOO_LOW;
            }
            $result[] = $props;
        }
        return $result;
    }
}
