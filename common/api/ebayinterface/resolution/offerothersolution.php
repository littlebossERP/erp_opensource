<?php
namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
class offerothersolution extends base  {
    //从ebay获取case详情
    public $verb='offerOtherSolution';
    public function api($id,$type,$text){
       	$xmlArr=array(
    		'caseId'=>array(
       			'id'=>$id,
       			'type'=>$type,
       		),
       		'messageToBuyer'=>$text,
       	);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>