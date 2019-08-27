<?php
namespace common\api\ebayinterface;
/***
 *  GetSuggestedCategories
 *  
 */ 
class getsuggestedcategories extends base{
    public $verb = 'GetSuggestedCategories';
    
    function api($query){
        //$num2+=$num;
        $xmlArr=array(
                'Query'=>$query,
            );
		$this->setRequestBody($xmlArr);
        $result=$this->sendRequest();
        return $result;
    }
}