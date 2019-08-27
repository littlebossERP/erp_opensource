<?php
/**
 *  @package interface.ebay.tradingapi
 */ 
class EbayInterface_reviseMyMessages extends EbayInterface_base{
	public $verb = 'ReviseMyMessages';
	function changeReadStatus($messageIDs,$read=true){
		$messageIDs=Q::normalize($messageIDs);
		$xmlArr=array(
			'Read'=>$read,
			'MessageIDs'=>array('MessageID'=>$messageIDs)
			);
		$r=$this->setRequestBody($xmlArr)
			->sendRequest();
		return $r;
	}
}
