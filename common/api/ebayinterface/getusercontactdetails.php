<?php
/**
 * 从ebay获取相应客户的详细联系信息
 * @package interface.ebay.tradingapi
 */
class EbayInterface_GetUserContactDetails extends EbayInterface_base{
    public function api($selleruserid,$buyeruserid,$itemid){
        $this->verb = 'GetUserContactDetails';
        $xmlArr=array(
			'RequesterID'=> $selleruserid,
        	'ContactID'=>$buyeruserid,
        	'ItemID'=> $itemid
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();

		return $result;
	}
}
?>