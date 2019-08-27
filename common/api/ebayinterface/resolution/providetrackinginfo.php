<?php
namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
class providetrackinginfo extends base  {
    //从ebay获取case详情
    public $verb='provideTrackingInfo';
    public function api($id,$type,$text,$carrierused,$number){


       	$xmlArr=array(
    		'caseId'=>array(
       			'id'=>$id,
       			'type'=>$type,
       		),
       		'carrierUsed'=>$carrierused,
       		'comments'=>$text,
       		'trackingNumber'=>$number
       	);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>