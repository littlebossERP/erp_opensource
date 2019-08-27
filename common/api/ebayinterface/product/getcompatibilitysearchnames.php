<?php
namespace common\api\ebayinterface\product;

use common\api\ebayinterface\product\base;
use eagle\models\EbayCategory;
class getcompatibilitysearchnames extends base{
	public $verb = 'getCompatibilitySearchNames';
	public function api($CategoryID=0,$siteid=100){
		$this->siteID=$siteid;
		//$siteid==100?$this->site='EBAY-MOTOR':'EBAY-DE';
		switch ($siteid){
			case '100':
				$this->site='EBAY-MOTOR';break;
			case '77':
				$this->site='EBAY-DE';break;
			case '3':
				$this->site='EBAY-GB';break;
			case '15':
				$this->site='EBAY-AU';break;
		}
		$this->verb='getCompatibilitySearchNames';
// 		$cache=$this->xmlCache($CategoryID);
// 		if ($cache) return parent::xmlparse($cache);
		/*$xmlArr=array(
			'categoryId'=>$CategoryID,
			'dataset'=>'Searchable',
		);*/
		
		$xmlArr=array('getCompatibilitySearchNamesRequest xmlns="http://www.ebay.com/marketplace/marketplacecatalog/v1/services"'=>array(
			'categoryId'=>$CategoryID,
			'dataset'=>'Searchable'
   		));
		$xml=$this->sendHttpRequest($xmlArr,true);
		/*$xml=$this->sendHttpRequest(array(
			'getCompatibilitySearchNamesRequest xmlns="http://www.ebay.com/marketplace/marketplacecatalog/v1/services"'=>$xmlArr
		),1);*/
		$result=parent::xmlparse($xml);
// 		if ($result['ack']=='Success'){
// 			$this->xmlCache($CategoryID,$xml);
// 		}
		return $result;
	}
	
	public function GetPropertyName($CategoryID=0,$siteid=100){
		$r=$this->api($CategoryID,$siteid);
		$propertyName=array();
	
		foreach($r['properties']['propertyName'] as $p){
			$propertyName[$p['propertyNameMetadata']['displaySequence']]=$p["propertyName"];
		}
		$type = EbayCategory::findOne(['categoryid'=>$CategoryID,'siteid'=>$siteid])->fitmentname;
		if ($type=='Year,Make,Model,Trim,Engine'){
			$propertyName=array('Make','Model','Year','Trim','Engine');
		}elseif($type=='Year,Make,Model,Submodel'){
			$propertyName=array('Make','Model','Year','Submodel');
		}elseif ($type=='Make,Model,Platform,Type,Engine,Production Period'){
			$propertyName=array('Make','Model','Platform','Type','Engine','ProductionPeriod');
		}elseif ($type=='Car Make,Model,Variant,BodyStyle,Cars Type,Cars Year,Engine'){
			$propertyName=array('CarMake','Model','Variant','BodyStyle','CarsType','CarsYear','Engine');
		}elseif ($type=='Year,Make,Model,Submodel,Variant,Engine'){
			$propertyName=array('Make','Model','Year','Submodel','Variant','Engine');
		}
		return $propertyName;
	}
}
?>