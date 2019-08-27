<?php
/**
 *  @package interface.ebay.tradingapi
 */ 
class EbayInterface_deleteMyMessages extends EbayInterface_base{
	public $verb = 'DeleteMyMessages';
	function api($messageIDs){
		$messageIDs=Q::normalize($messageIDs);
		$xmlArr=array(
			'MessageIDs'=>array('MessageID'=>$messageIDs)
			);
		$r=$this->setRequestBody($xmlArr)
			->sendRequest();
		return $r;
	}
}
