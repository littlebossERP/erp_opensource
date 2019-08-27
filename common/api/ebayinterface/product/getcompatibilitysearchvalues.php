<?php
namespace common\api\ebayinterface\product;

use common\api\ebayinterface\product\base;
class getcompatibilitysearchvalues extends base{
	public $verb = 'getCompatibilitySearchValues';
public function api($CategoryID=0,$siteid=100,$propertyname,$propertyFilter=null){
		$this->siteID=$siteid;
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
		
 		$cache=$this->xmlCache($CategoryID.$propertyname.serialize($propertyFilter));
 		if ($cache) return parent::xmlparse($cache);
		
		$xmlArr2=array(
				'categoryId'=>$CategoryID,
				'propertyName'=>$propertyname,
				);
		
		if($propertyFilter){
			$xmlArr2['propertyFilter']=$propertyFilter;
		}
		
		$xmlArr=array('getCompatibilitySearchValuesRequest xmlns="http://www.ebay.com/marketplace/marketplacecatalog/v1/services"'=>$xmlArr2);
		$xml=$this->sendHttpRequest($xmlArr,true);
		$result=parent::xmlparse($xml);
		if ($result['ack']=='Success'){
			$this->xmlCache($CategoryID.$propertyname,$xml);
		}
		return $result;
	}
	
	public function get($propertyname,$CategoryID=0,$siteid=100,$propertyFilter=null){
		if($propertyname=='CarMake'){$propertyname='Car Make';}
		$r=$this->api($CategoryID,$siteid,$propertyname,$propertyFilter);
		$PropertyNameValue=array();
		
		if($r['propertyValuesTree']){
			foreach($r['propertyValuesTree']['childPropertyNameValue'] as $pv){
				/*
				 * 部分数据只有一条。如果继续使用$PropertyNameValue[$pv['propertyName']][$pv['value']['text']['value']]
				*                             		= $pv['value']['text']['value'];
				* 将会产生一个Fatal error级别的错误：Cannot use string offset as an array
				*/
				if(count($pv) == 1)
				{
					$PropertyNameValue[$r['propertyValuesTree']['childPropertyNameValue']['propertyName']]
					[$r['propertyValuesTree']['childPropertyNameValue']['value']['text']['value']] = $r['propertyValuesTree']['childPropertyNameValue']['value']['text']['value'];
				}
				else
				{
					$PropertyNameValue[$pv['propertyName']][$pv['value']['text']['value']] = $pv['value']['text']['value'];
				}
			}
			foreach($PropertyNameValue as &$v){
				asort($v,SORT_STRING);
			}
		}
		if(isset($PropertyNameValue['Car Make'])){
			$PropertyNameValue['CarMake']=$PropertyNameValue['Car Make'];
			unset($PropertyNameValue['Car Make']);
		}
		return $PropertyNameValue;
	}
}
?>