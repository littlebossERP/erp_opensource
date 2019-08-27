<?php
namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
class offerpartialrefund extends base  {
    //从ebay获取case详情
    public $verb='offerPartialRefund';
    public function api($id,$type,$text,$amount,$currency){
    
       	$xmlArr=array(
    		'caseId'=>array(
       			'id'=>$id,
       			'type'=>$type,
       		),
       		'comments'=>$text,
       		'amount'=>$amount,
       	);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>