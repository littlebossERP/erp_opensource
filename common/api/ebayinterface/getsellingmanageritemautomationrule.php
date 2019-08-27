<?php
/**
 * 结束刊登
 * @package interface.ebay.tradingapi
 *
 */
class EbayInterface_GetSellingManagerItemAutomationRule extends EbayInterface_base{
	public $verb='GetSellingManagerItemAutomationRule';
    public function api($ItemID){
        $xmlArrData=array(
    		'ItemID'=>$ItemID,
        );
		$result=$this->setRequestBody($xmlArrData)->sendRequest();
        return $result;
    }
}