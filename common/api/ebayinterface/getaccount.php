<?php
/**
 * 获得eBay账户信息
 * @package interface.ebay.tradingapi
 */
class EbayInterface_getAccount extends EbayInterface_base{
    //从ebay获取相应账户的信息
    public function api(){
        $this->verb = 'GetAccount';
        $xmlArr=array(
			'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
//			'AccountHistorySelection'=> 'LastInvoice'
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();

		if($result['Ack']=='Success'){
			return $result;
		}else{
	//		dump($result,null,10);
			return false;
		}
	}
}
?>