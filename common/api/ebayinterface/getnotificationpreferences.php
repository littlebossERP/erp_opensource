<?php
/**
 * 获得notification设置
 * @package interface.ebay.tradingapi
 */
class EbayInterface_GetNotificationPreferences extends EbayInterface_base{
    /**
     * 从ebay获取相应itemid的信息
     *
     * @param string $PreferenceLevel Application|User|Event
     * @return unknown
     */
    public function api($PreferenceLevel='Application'){
        $this->verb = 'GetNotificationPreferences';
//        $this->config['compatabilityLevel']=631;
        $xmlArr=array(
		       	'RequesterCredentials'=>array(
					'eBayAuthToken'=>$this->eBayAuthToken,
				),
				'ErrorLanguage'=>'zh_CN',
				'MessageID'=>1,
				'Version'=>$this->config['compatabilityLevel'],
				'PreferenceLevel'=>$PreferenceLevel,

		);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
        print_r($xmlbody);
        print_r($result);
		if($result['Ack']=='Success'){
			return true;
		}else{
			return false;
		}
	}
}
?>