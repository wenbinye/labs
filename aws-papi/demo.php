<?php
require_once('/home/admin/ruyitao/lib/php/aws/sdk.class.php');
require_once(dirname(__FILE__).'/pear/AwsHttpResponse.php');
require_once(dirname(__FILE__).'/pear/AwsClient.php');

define('AWS_KEY', '');
define('AWS_SECRET', '');

$ua = new AwsClient(AWS_KEY, AWS_SECRET, 'ruyitao-20');
$ua->set_locale(AmazonPAS::LOCALE_US);
// $item_id = 'B004MYFTF6,B004V3KCIM,B003B3P2CY,B001ET5O70,B003ZX8B2S,B001H4B0AC,B004MYFTFQ,B007M4Z7GO,B001VEJEGK,B004V3KD4K,B004MYFTEW,B003B3P2CO,B004MYFTDI,B003ZX8B3W,B005J8OBJE,B000U9WVW6';
$item_id = 'B004MYFTF6,B004V3KCIM';
$response = $ua->item_lookup($item_id, array('ResponseGroup' => 'Large'));
$items = $response->getItems();
var_export($items);
