<?php

namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;

class getactivityoptions extends base{
    //从ebay获取case下步可以进行的操作
    public $verb='getActivityOptions';
    public function api($id,$type){
	   	$xmlArr=array(
    			'caseId'=>array(
    	   			'id'=>$id,
    	   			'type'=>$type,
    	   		)
	   	);
	   	
	   	$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>