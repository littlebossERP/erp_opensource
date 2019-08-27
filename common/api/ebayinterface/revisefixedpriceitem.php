<?php
namespace common\api\ebayinterface;

use common\api\ebayinterface\base;
class revisefixedpriceitem extends base{
	public $verb = 'ReviseFixedPriceItem';
	function apiCore($itemid,$params){
		$params['ItemID']=$itemid;
		$xmlArr['Item']=$params;
		$result = $this->setRequestBody($xmlArr)->sendRequest();
		/* if ($result['Ack']=='Success'||$result['Ack']=='Warning'){
		    //检测是否应用促销
		    $a = Ebay_Promotion_Items::find('itemid = ?',$itemid)->getOne();
		    if (!$a->isNewRecord){
		        $promotionalsaleid = $a->promotionalsaleid;
		        $SetPromotionalSaleListings_api = new EbayInterface_SetPromotionalSaleListings();
		        $SetPromotionalSaleListings_api->eBayAuthToken=$this->eBayAuthToken;
		        $SetPromotionalSaleListings_api->api('Add',$promotionalsaleid,array($itemid));
		    }
		} */
		return $result;
	}
    function api($itemid,$ItemCompatibilityList,$token){
    	/* 
	   <ItemCompatibilityList> ItemCompatibilityListType
	      <Compatibility> ItemCompatibilityType
	        <CompatibilityNotes> string </CompatibilityNotes>
	        <Delete> boolean </Delete>
	        <NameValueList> NameValueListType
	          <Name> string </Name>
	          <Value> string </Value>
	          <!-- ... more Value nodes here ... -->
	        </NameValueList>
	        <!-- ... more NameValueList nodes here ... -->
	      </Compatibility>
	      <!-- ... more Compatibility nodes here ... -->
	      <ReplaceAll> boolean </ReplaceAll>
	    </ItemCompatibilityList>
		*/
		$ItemCompatibilityListXML=new SimpleXMLElement('<ItemCompatibilityList></ItemCompatibilityList>');
		$ItemCompatibilityListXML->addChild('ReplaceAll',"true");
		foreach ($ItemCompatibilityList as $value){
			$Compatibility=$ItemCompatibilityListXML->addChild("Compatibility");
			$Compatibility->addChild('CompatibilityNotes',"");
			$Compatibility->addChild('Delete',"false");
			foreach ($value as $k=>$v){
				if (empty($v)){
					continue;
				}
				$NameValueList=$Compatibility->addChild('NameValueList');
				$NameValueList->addChild('Name',$k);
				$NameValueList->addChild('Value',$v);
			}
		}
        $xmlArr=array(
				'RequesterCredentials'=>array(
					'eBayAuthToken'=>$token,
				),
				'Item'=>array(
				    'ItemID'=>$itemid,
				    'ItemCompatibilityList'=>$ItemCompatibilityListXML,
				),
			
		);

		//dump($xmlArr,null,100);die;
		$result=$this->setRequestBody($xmlArr)->sendRequest();
        $baselog=array(
            "base_data"=>"siteID: ".@$this->siteID.", devAccountID: ".@$this->devAccountID." api:".@$this->verb,
            "revisefixedpriceitem_data"=>$xmlArr,
            "base_resp"=>"[Ack]: ".@$this->_last_response_array['Ack'].", [Errors]: ".print_r(@$this->_last_response_array['Errors'],1));
        \Yii::info(print_r($baselog,1),"ebayapi");
		return $result;
       /* if($result['Ack']=='Success'){
			return true;
		}*/
    }
    
    /**
     * 改数量
     * @param unknown_type $itemid
     * @param unknown_type $toQuantity
     * @param unknown_type $sku
     * @return Ambigous <multitype:, xml, boolean, unknown, string, resource, mixed, xmlArray, SimpleXMLElement, NULL>
     */
    function apiReviseQuantity($itemid,$toQuantity,$sku=null){
    	if($sku){
	    	$params = array (
	    			'Variations' => array (
	    					'Variation' => array (
	    							'Quantity' => $toQuantity,
	    							'SKU'=>$sku
	    					)
	    			)
	    	);
    	}else{
    		$params['Quantity']=$toQuantity;
    	}
    	return $this->apiCore($itemid, $params);
    }
    
    

}
