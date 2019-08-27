<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
/***
 *  为数量即将到0的一口价商品补库存  
 *  
 */ 
class getsellerevents extends base{
    public $verb = 'GetSellerEvents';
    
    function api($params){
        //$num2+=$num;
        $xmlArr=$params;
        $xmlArr['DetailLevel']='ReturnAll';
        $xmlArr['IncludeVariationSpecifics']='true';
        $xmlArr['IncludeWatchCount']='true';
        if(isset($this->_before_request_xmlarray['OutputSelector'])){
        	$xmlArr['OutputSelector']=$this->_before_request_xmlarray['OutputSelector'];
        }
		$this->setRequestBody($xmlArr);
        $result=$this->sendRequest();
        return $result;
    }
}