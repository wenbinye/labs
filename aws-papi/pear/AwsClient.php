<?php
class AwsClient extends AmazonPAS
{
    public $response_class = 'AwsHttpResponse';

    public function item_lookup($item_id, $opt=array())
    {
        if ( is_string($item_id) ) {
            $item_id = explode(',', $item_id);
        }
        if ( count($item_id) > 20 || empty($item_id) ) {
            throw new PAS_Exception("item_id number should between 1 and 20");
        }
        if ( count($item_id) > 10 ) {
            $opt['ItemLookup.1.ItemId'] = implode(',', array_slice($item_id, 0, 10));
            $opt['ItemLookup.2.ItemId'] = implode(',', array_slice($item_id, 10));
        } else {
            $opt['ItemId'] = implode(',', $item_id);
        }
		if (isset($this->assoc_id))
		{
			$opt['AssociateTag'] = $this->assoc_id;
		}
		return $this->pas_authenticate('ItemLookup', $opt);
    }
}
