<?php
namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
class appealtocustomersupport extends base  {
    //从ebay获取case详情
    public $verb='appealToCustomerSupport';
    public function api($id,$type,$text,$reason){
       	$xmlArr=array(
    		'caseId'=>array(
       			'id'=>$id,
       			'type'=>$type,
       		),
       		'comments'=>$text,
       		'appealReason'=>$reason,
       	);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>