<?php
/**
 * 获得店铺页面信息
 * @package interface.ebay.tradingapi
 */
class EbayInterface_GetStoreCustomPage extends EbayInterface_base{
    public function api($pageId){
        $this->verb = 'GetStoreCustomPage';
        $xmlArr=array(
            'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
            'ErrorLanguage'=>'zh_CN',
		);
		if($pageId){
            $xmlArr['PageID']=$pageId;
        }
		$result=$this->setRequestBody($xmlArr)->sendRequest();
// 		var_dump($result);
		if($result['Ack']=='Success'){
			return $result;
		}else{
			return false;
		}
	}
}
