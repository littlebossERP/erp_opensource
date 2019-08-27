<?php
namespace common\api\ebayinterface\resolution;

use common\api\ebayinterface\resolution\base;
class provideshippinginfo extends base  {
    //从ebay获取case详情
    public $verb='provideShippingInfo';
    public function api($id,$type,$text,$carrierused,$date){
   	$this->verb='provideShippingInfo';

   	$xmlArr=array(
		'caseId'=>array(
   			'id'=>$id,
   			'type'=>$type,
   		),
   		'carrierUsed'=>$carrierused,
   		'comments'=>$text,
   		'shippedDate'=>$date.'T19:14:46.173Z',
   	);
		$result=$this->setRequestBody($xmlArr)->sendRequest();
		return $result;
	}
}
?>