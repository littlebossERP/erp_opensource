<?php
namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
class escalatetocustomersupport extends base  {
    //从ebay获取case详情
    public $verb='escalateToCustomerSupport';
    public function api($id,$type,$text,$reason){

       	$xmlArr=array(
    		'caseId'=>array(
       			'id'=>$id,
       			'type'=>$type,
       		),
       		'comments'=>$text,
       		'escalationReason'=>$reason,
       	);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>