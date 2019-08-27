<?php

namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
/**
 * SendInvoice
 * @package interface.ebay.tradingapi
 */
class sendinvoice extends base{
	public $verb='SendInvoice';
	/**
	 * Enter description here...
	 *
	 * @param array $ids array(OrderID or ItemID+TransactionID)
	 * @param unknown_type $EmailCopyToSeller
	 */
    function api($ids,$ShippingService,$EmailCopyToSeller=true,$CheckoutInstructions=null){
    	$xmlBody=$ids;
    	if (!isset($xmlBody['OrderID']) && (!isset($xmlBody['ItemID']) && !isset($xmlBody['TransactionID']))){
    		throw new EbayInterfaceException('接口调用不正确');
    	}
    	$xmlBody=array_merge($xmlBody,$ShippingService);
    	if ($EmailCopyToSeller){
    		$xmlBody['EmailCopyToSeller']=1;
    	}
    	if ($CheckoutInstructions){
    		$xmlBody['CheckoutInstructions']=$CheckoutInstructions;
    	}
    	return $this->setRequestBody($xmlBody)->sendRequest();
    }
	//从ebay获取相应itemid的信息
}
?>