<?php
/**
 * 获得notification的使用情况
 * @package interface.ebay.tradingapi
 */
class EbayInterface_GetNotificationsUsage extends EbayInterface_base{
    //从ebay获取相应itemid的信息
    public $verb='GetNotificationsUsage';
    public function api($itemId=null,$StartTime=null,$EndTime=null){
//        $this->config['compatabilityLevel']=631;
        $xmlArr=array(
			'StartTime'=>$this->dateTime($StartTime),
			'EndTime'=>$this->dateTime($EndTime),
		);
		if (!is_null($itemId)){
			$xmlArr['ItemID']=$itemId;
		}
		if (!is_null($StartTime)){
			$xmlArr['StartTime']=$this->dateTime($StartTime);
		}else {
			$xmlArr['StartTime']=$this->dateTime(CURRENT_TIMESTAMP-ONEDAY);
		}
		if (!is_null($EndTime)){
			$xmlArr['EndTime']=$this->dateTime($EndTime);
		}else {
			$xmlArr['EndTime']=$this->dateTime(CURRENT_TIMESTAMP);
		}
		
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>