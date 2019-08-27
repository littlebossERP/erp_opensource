<?php
/**
 * 修改店铺设置
 * @package interface.ebay.tradingapi
 */
class EbayInterface_SetStore extends EbayInterface_base{
    /**
     * 修改 详情
     */
	public function updateDescription($values=array()){
        $this->verb = 'SetStore';
        if(count($values)>0){
            $this->values=$values;
        }
        if(isset($this->values['store_name'])){
            $store['Name']=$this->values['store_name'];
        }
        if(isset($this->values['description'])){
            $store['Description']=$this->values['description'];
        }
        $xmlArr=array(
            'RequesterCredentials'=>array(
				'eBayAuthToken'=>$this->eBayAuthToken,
			),
            'ErrorLanguage'=>'zh_CN',
            'Store'=>$store,
// 			'UserID'=>$userid,
		);
		if(isset($this->values['siteid'])){
//             $xmlArr['SiteId']=$this->values['siteid'];
			$this->siteID=$this->values['siteid'];
        }
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		if($result['Ack']=='Success'){
			return $result;
		}else{
			return false;
		}
    }
    
}
