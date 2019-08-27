<?php
namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
class offerrefunduponreturn extends base  {
    //从ebay获取case详情
    public $verb='offerRefundUponReturn';
    public function api($id,$type,$text,$city,$country,$name,$postalcode,$state,$street1,$street2){

       	$xmlArr=array(
    		'caseId'=>array(
       			'id'=>$id,
       			'type'=>$type,
       		),
       		'returnAddress'=>array(
       			'city'=>$city,
       			'country'=>$country,
       			'name'=>$name,
       			'postalCode'=>$postalcode,
       			'stateOrProvince'=>$state,
       			'street1'=>$street1,
       			'street2'=>$street2
       		),
       		'additionalReturnInstructions'=>$text
       	);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>