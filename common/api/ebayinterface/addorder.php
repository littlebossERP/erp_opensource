<?php
/**
 * 合并订单SendInvoice
 * @package interface.ebay.tradingapi
 */
class EbayInterface_addorder extends EbayInterface_base{
    //从ebay获取相应itemid的信息
    public function api($array){
        $this->verb = 'AddOrder';
        $xmlArr=array(
  				'RequesterCredentials'=>array(
  					'eBayAuthToken'=>$this->token,
  				),
  				'Order'=>$array,
    		);
		$requestArr=$this->setRequestBody($xmlArr)->sendRequest();
		return $requestArr;
    }
}
?>