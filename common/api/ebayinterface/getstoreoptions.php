<?php
/**
 * 获得店铺选项
 * @package interface.ebay.tradingapi
 */
class EbayInterface_GetStoreOptions extends EbayInterface_base{
    public function api(){
        $this->verb = 'GetStoreOptions';
        $xmlArr=array(
            'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
            'ErrorLanguage'=>'zh_CN',
		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
// 		var_dump($result);
		if($result['Ack']=='Success'){
			return $result;
		}else{
			return false;
		}
	}
}
