<?php
/**
 * 获得店铺设置和属性
 * @package interface.ebay.tradingapi
 */
class EbayInterface_GetStorePreferences extends EbayInterface_base{
    public function api(){
        $this->verb = 'GetStorePreferences';
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
