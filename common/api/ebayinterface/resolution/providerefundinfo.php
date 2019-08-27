<?php
namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
class providerefundinfo extends base  {
    //从ebay获取case详情
    public $verb='provideRefundInfo';
    public function api($id,$type,$text){
       	$xmlArr=array(
    		'caseId'=>array(
       			'id'=>$id,
       			'type'=>$type,
       		),
       		'refundMessage'=>$text,
       	);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>