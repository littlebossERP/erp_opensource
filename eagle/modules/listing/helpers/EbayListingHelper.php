<?php
namespace eagle\modules\listing\helpers;
use \Yii;
use eagle\modules\listing\models\Mytemplate;
use eagle\modules\listing\models\EbaySalesInformation;
use eagle\modules\listing\models\EbayCrossselling;
use eagle\modules\listing\models\EbayCrosssellingItem;
use eagle\models\EbaySite;
use common\helpers\Helper_Array;
use eagle\modules\listing\models\EbayCrosssellingV2;
use eagle\modules\listing\models\EbayCrosssellingItemV2;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\util\helpers\UrlParamCryptHelper;
use eagle\modules\listing\models\EbayItemDetail;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: fanjs
+----------------------------------------------------------------------
| Create Date: 2014-08-01
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 刊登模块模板业务
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @author		fanjs
 +------------------------------------------------------------------------------
 */
class EbayListingHelper {
	public static $listingVer = '1_3';
	public static $ItemIdObjectMap=array();
	
	public static $SiteURLMAp=array(
			"Australia"=>"http://www.ebay.com.au",
			"Austria"=>"http://www.ebay.at",
			"Belgium_Dutch"=>"http://www.ebay.be",
			"Belgium_French"=>"http://www.ebay.be",
			"Canada"=>"http://www.ebay.ca",
			"France"=>"http://www.ebay.fr",
			"Germany"=>"http://www.ebay.de",
			"HongKong"=>"http://www.ebay.com.hk",
			"India"=>"http://www.ebay.in",
			"Ireland"=>"http://www.ebay.ie",
			"Italy"=>"http://www.ebay.it",
			"Malaysia"=>"http://www.ebay.com.my",
			"Netherlands"=>"http://www.ebay.nl",
			"Philippines"=>"http://www.ebay.ph",
			"Poland"=>"http://www.ebay.pl",
			"RussiaSingapore"=>"http://www.ebay.com.sg",
			"Spain"=>"http://www.ebay.es",
			"Switzerland"=>"http://www.ebay.ch",
			"UK"=>"http://www.ebay.co.uk",
			"US"=>"http://www.ebay.com",
			"CanadaFrench"=>"",
			"CustomCode"=>""
			
	);
	
	
	
	//$templateId 转成 加密url
	public static function encodeTemplateidUrl($templateId=0){	
		
		$encodedUrl=UrlParamCryptHelper::authCode("{$templateId}",'ENCODE');
	
		return $encodedUrl;
	}
	//$templateId 转成 加密url
	public static function decodeTemplateidUrl($url=0){
	
		$templateId=UrlParamCryptHelper::authCode("{$url}",'DECODE');
	
		return $templateId;
	}
		

	//$templateId  0 表示没有使用template
	public static function encodeUrl($ebayItemId,$puid,$templateId=0,$version="v1"){
		
		if ($version==="v1"){
			$encodedUrl=UrlParamCryptHelper::authCode("{$version}={$puid}={$ebayItemId}={$templateId}",'ENCODE');
			
		}
		
		return $encodedUrl;
	}
	/**
	 * 
	 * @param unknown $url
	 * @return multitype:
	 * $version,$puid,$ebayItemId,$templateId}
	 */
	private static function _decodeUrl($url){
		$decodedStr=UrlParamCryptHelper::authCode($url,'DECODE');
		$params=explode("=",$decodedStr);
		
		return $params;
		
	}
	
	/**
	 * 
	 * @param unknown $url
	 */
	public static function getTemplateCss($url){
		//1. 解释puid和ebayItemId
		$templateId=self::decodeTemplateidUrl($url);
		
		//2. $templateId 找到对应的css文件
		$cssFilePath=\Yii::getAlias('@webroot').DIRECTORY_SEPARATOR."attachment".DIRECTORY_SEPARATOR."ebay_template_v2".DIRECTORY_SEPARATOR."{$templateId}".DIRECTORY_SEPARATOR."template.css";
		return $cssFilePath;
		//echo file_get_contents($cssFilePath);
	}
	
	public static function getTemplateJs($url){
		//1. 解释puid和ebayItemId
		$templateId=self::decodeTemplateidUrl($url);
	
		//2. $templateId 找到对应的css文件
		$jsFilePath=\Yii::getAlias('@webroot').DIRECTORY_SEPARATOR."attachment".DIRECTORY_SEPARATOR."ebay_template_v2".DIRECTORY_SEPARATOR."{$templateId}".DIRECTORY_SEPARATOR."template.js";
		return $jsFilePath;
	//	echo file_get_contents($jsFilePath);
	}
	
	public static function getRecommendProdsForBuyer($url){
		//1. 解释puid和ebayItemId
		list($version,$puid,$ebayItemId,$templateId)=self::_decodeUrl($url);
		
		//2. 从cache中获取结果
		//3. 从db中获取结果
	//	$ebayItemObject=EbayItem::find()->where(["itemid"=>$ebayItemId])->one();
		$maxNumber=5;
	    $recommendProds=self::getRecommendProdsFromDB($puid, $ebayItemId, $maxNumber);
	    
	    return  $recommendProds;
		
	}
	
    /**
     * 
     * @param unknown $puid
     * $ebayItemId   ebay 唯一的刊登itemid      
     * @param unknown $maxNumber
     * @param unknown $crossSellingType
     *     top bottom
     */
	public static function getRecommendProdsFromDB($puid,$ebayItemId,$maxNumber,$crossSellingType="top") {
		
	 
		
		//1. 获取当前item对应的信息
		if (!isset(self::$ItemIdObjectMap[$ebayItemId])){
			$ebayItemObject=EbayItem::find()->where(["itemid"=>$ebayItemId])->asArray()->one();
			if ($ebayItemObject===null){
				return array();
			}
			self::$ItemIdObjectMap[$ebayItemId]=$ebayItemObject;
		}else{
			$ebayItemObject=self::$ItemIdObjectMap[$ebayItemId];
		}
		
		if ($crossSellingType=="top"){
			$crossSellingId=$ebayItemObject["top_crosssellid"];			
		}else if ($crossSellingType=="bottom"){
			$crossSellingId=$ebayItemObject["bottom_crosssellid"];
		}else return array();		
		
		
	    $ebayCrossSellObject=EbayCrosssellingV2::find()->where(["id"=>$crossSellingId])->one();
	    if ($ebayCrossSellObject==null) return array();
	    
	    
	    
	    
	    //2. 处理按“指定商品”的方式    type----1 按条件获取，2 指定商品
		if ($ebayCrossSellObject->type===2){
		    $ebayCrossItems=EbayCrosssellingItemV2::find()->where(["crosssellingid"=>$crossSellingId])->asArray()->all();
		    return $ebayCrossItems;
		}
		
		//3. 处理按“条件获取”的方式    type----1 按条件获取，2 指定商品
		//3.1  分析additional_info 获取更加详尽的搜索条件
		$selleruseridCond=" selleruserid='".$ebayItemObject["selleruserid"]."'";
		//$siteCond=$ebayItemObject->site;
		$siteCond="";
		$primaryCategoryCond="";
		$storeCategoryCond="";
		
		$lowestPriceCond=0; //美元为单位
		$highestPriceCond=0;
		
		
		if (!empty($ebayCrossSellObject->additional_info)){
			//additional_info-----array( 
			// selleruserid --- 取值3种可能， lb_any是任何site（或者为空，默认）， lb_same表示跟对应item的是一样的， 指定其他array("2342","234")
			// site --- 取值3种可能， lb_any是任何site（或者为空，默认）， lb_same表示跟对应item的是一样的， 指定其他array("2342","234")
		    // primarycategory --- 取值3种可能， lb_any是任何site（或者为空，默认）， lb_same表示跟对应item的是一样的， 指定其他array("2342","234")
		    // storecategoryid ---取值3种可能， lb_any是任何site（或者为空，默认）， lb_same表示跟对应item的是一样的， 指定其他array("2342","234")
		//	)
			
			$additional_info=json_decode($ebayCrossSellObject->additional_info,true);		
			
			$oneQueryCond=" ";
			$oneCriteria=isset($additional_info["selleruserid"])?$additional_info["selleruserid"]:null;			
			if ($oneCriteria===null or $oneCriteria==="lb_any") $oneQueryCond=" ";
			else if ($oneCriteria==="lb_same") {
				$oneQueryCond=" selleruserid='".$ebayItemObject["selleruserid"]."' ";
			}else if (count($oneCriteria)==1) {
				$oneQueryCond=" selleruserid='".$oneCriteria[0]."' ";
			} else	{				
				$oneQueryCond=" selleruserid in ( ";
				foreach($oneCriteria as $oneCriteriaOption){
					$oneQueryCond=$oneQueryCond."'".$oneCriteriaOption."',";
				}
				$oneQueryCond=substr($oneQueryCond,strlen($oneQueryCond)-1);
				$oneQueryCond=$oneQueryCond.")";
			}
			$selleruseridCond=$oneQueryCond;
			
		
			$oneQueryCond=" ";			
			$oneCriteria=isset($additional_info["site"])?$additional_info["site"]:null;
			if ($oneCriteria===null or $oneCriteria==="lb_any") $oneQueryCond=" ";
			else if ($oneCriteria==="lb_same") {
				$oneQueryCond=" site='".$ebayItemObject["site"]."' ";
			}else if (count($oneCriteria)==1) {
				$oneQueryCond=" site='".$oneCriteria[0]."' ";
			} else	{
				$oneQueryCond=" site in ( ";
				foreach($oneCriteria as $oneCriteriaOption){
					$oneQueryCond=$oneQueryCond."'".$oneCriteriaOption."',";
				}
				$oneQueryCond=substr($oneQueryCond,strlen($oneQueryCond)-1);
				$oneQueryCond=$oneQueryCond.")";
			}
			$siteCond=$oneQueryCond;
			
			
			//TODO 后面可以加入分类，但前提是先把ebay_item_detail的分类信息加入到 ebay_item 表
			$oneQueryCond=" ";
			$oneCriteria=isset($additional_info["primarycategory"])?$additional_info["primarycategory"]:null;
			if ($oneCriteria===null or $oneCriteria==="lb_any") $oneQueryCond=" ";
			else if ($oneCriteria==="lb_same") {
				$oneQueryCond=" primarycategory=".$ebayItemObject["primarycategory"]." ";
			}else if (count($oneCriteria)==1) {
				$oneQueryCond=" primarycategory=".$oneCriteria[0]." ";
			} else	{
				$oneQueryCond=" primarycategory in ( ";
				foreach($oneCriteria as $oneCriteriaOption){
					$oneQueryCond=$oneQueryCond."".$oneCriteriaOption.",";
				}
				$oneQueryCond=substr($oneQueryCond,strlen($oneQueryCond)-1);
				$oneQueryCond=$oneQueryCond.")";
			}
			$primaryCategoryCond=$oneQueryCond;			
			

			$oneQueryCond=" ";
			//$oneCriteria=$additional_info["storecategoryid"];
			$oneCriteria=isset($additional_info["storecategoryid"])?$additional_info["storecategoryid"]:null;
			if ($oneCriteria===null or $oneCriteria==="lb_any") $oneQueryCond=" ";
			else if ($oneCriteria==="lb_same") {
				$oneQueryCond=" storecategoryid=".$ebayItemObject["storecategoryid"]." ";
			}else if (count($oneCriteria)==1) {
				$oneQueryCond=" storecategoryid=".$oneCriteria[0]." ";
			} else	{
				$oneQueryCond=" storecategoryid in ( ";
				foreach($oneCriteria as $oneCriteriaOption){
					$oneQueryCond=$oneQueryCond."".$oneCriteriaOption.",";
				}
				$oneQueryCond=substr($oneQueryCond,strlen($oneQueryCond)-1);
				$oneQueryCond=$oneQueryCond.")";
			}
			$storeCategoryCond=$oneQueryCond;			
			
	
		    
		 /*  TODO 需要给ebay_item加个字段来保存美元为货币的price   
		   if (isset($queryCondArr["lowestprice"])){
		        $lowestPriceCond=$queryCondArr["lowestprice"];
		    }
		    if (isset($queryCondArr["highestprice"])){
		        $highestPriceCond=$queryCondArr["highestprice"];
		    }*/
		    
	
		}
		
		//3.2 拼接sql

		$nowTime=time();		
		$currentEndTime=$nowTime+12*3600;// 需要考虑到cache的原因或者数据更新的周期，所以这里如果还有12个小时之内就要结束的刊登，都要剔除。
		$sortCond=$ebayCrossSellObject->sort;
		$sqlStr= "SELECT itemid ,starttime,endtime,site,itemtitle,mainimg,currentprice,currency,listingtype FROM ebay_item WHERE listingstatus='Active' AND endtime>{$currentEndTime}";
		if (trim($selleruseridCond)<>"") $sqlStr=$sqlStr." AND ".$selleruseridCond." ";
		if (trim($siteCond)<>"") $sqlStr=$sqlStr." AND ".$siteCond." ";
		if (trim($primaryCategoryCond)<>"") $sqlStr=$sqlStr." AND ".$primaryCategoryCond." ";
		if (trim($storeCategoryCond)<>"") $sqlStr=$sqlStr." AND ".$storeCategoryCond." ";
		
		
		//sort-----endsoon,hotsale,newlist,nothotsale
		//TODO  以后再支持随机  random
		$orderStr="";
		if ($sortCond==="endsoon"){
		    $orderStr=" ORDER BY endtime ";
		}else if ($sortCond==="hotsale"){
		    $orderStr=" ORDER BY quantitysold desc";
		} else if ($sortCond==="hotsale"){
		    $orderStr=" ORDER BY quantitysold";
		} else if ($sortCond==="newlist"){
		    $orderStr=" ORDER BY starttime desc";		    
		}
		$sqlStr=$sqlStr." ".$orderStr." LIMIT 0,{$maxNumber}";
		
		
		
		$rows=\Yii::$app->subdb->createCommand($sqlStr)->queryAll();
		
		
		
        //3.3 结果转换成 最终的格式
        $resultRows=self::_formatCrosssellProdsData($rows);
		
		return $resultRows;
				
	} 
	
	/**
	 * 由于前段js使用了PA的，所以数据也要整理成一样的格式
	 */
	private static function _formatCrosssellProdsData($rows){
		/**  下面是PA的格式
		 * "eBayItemID":"162207282404",
"PictureURL":"http://i.ebayimg.com/00/s/MTIwMFgxMjAw/z/IugAAOSwmfhX3hAI/$_1.JPG?set_id=880000500F",
"eBayListTime":"09-18-2016",   "eBayEndTime":"10-18-2016",
"Bid":0,   "MarkDown":"0","         OriginalPrice":"0.00 USD",      "FreeShipping":1
,"IsFixedPrice":"1",     "eBayItemTitle":"Fashionable \u0026 Portable Bamboo Dragonfly Mini USB Fan - Pink",
"Price":"5.00 USD",      "TRS":"0","URL":"http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem\u0026item="},
		 */
		// TRS 不知道是什么东东，先不管； 目前OriginalPrice先等于Price； Bid 目前buyer bid的个数
		
		$resultRows=array();
		foreach($rows as $row){
			$resultOneRows=array();
			$resultOneRows["eBayItemID"]=$row["itemid"];
			$ebayItemDetail=EbayItemDetail::find()->where(["itemid"=>$row["itemid"]])->one();
			//print_r($ebayItemDetail); exit;
			
			
			$resultOneRows["TRS"]=0;
			$resultOneRows["MarkDown"]=0;
			
			
			// Bid 目前buyer bid的个数
			$resultOneRows["Bid"]=0;
			if (!empty($ebayItemDetail->sellingstatus)){
				$resultOneRows["Bid"]=$ebayItemDetail->sellingstatus["BidCount"];
			}
			
			// 是否免运费
			$resultOneRows["FreeShipping"]=0;
			if (!empty($ebayItemDetail->shippingdetails)){				
				$shippingdetails=$ebayItemDetail->shippingdetails;
				if ($shippingdetails<>null){
					//print_r($shippingdetails); exit;
					if (isset($shippingdetails["ShippingServiceOptions"])){
						$shippingServiceOptions=$shippingdetails["ShippingServiceOptions"];
						if (isset($shippingServiceOptions["FreeShipping"])){
							//只有一个运输option
							if ($shippingServiceOptions["FreeShipping"]===true){
								$resultOneRows["FreeShipping"]=1;
							}
						}else{
						   foreach($shippingServiceOptions  as $shippingServiceOption){
					   	    	if ($shippingServiceOption["FreeShipping"]===true){
						    		$resultOneRows["FreeShipping"]=1;
						    		break;
					  	    	}
						   }
						}
					}
					
				}
			}
			 
			$resultOneRows["eBayListTime"]= gmdate("m-d-Y",$row["starttime"]);
			$resultOneRows["eBayEndTime"]= gmdate("m-d-Y",$row["endtime"]);
			 
			$resultOneRows["eBayItemTitle"]=$row["itemtitle"];
			$resultOneRows["PictureURL"]=$row["mainimg"];
			$resultOneRows["URL"]=self::$SiteURLMAp[$row["site"]]."/ws/eBayISAPI.dll?ViewItem\u0026item=";
			$resultOneRows["Price"]=$row["currentprice"]." ".$row["currency"];
			 
			 
			if ($row["listingtype"]==="FixedPriceItem"){
				$resultOneRows["IsFixedPrice"]=1;
			}else $resultOneRows["IsFixedPrice"]=0;
			 
			$resultRows[]=$resultOneRows;
		}	
	
		
		return $resultRows;	
		
	}
	
	public static function getEbayItemUrlPrefix($site){
		$returnUrl="http://cgi.ebay.com/ws/eBayISAPI.dll?ViewItem\u0026item=";
		if ($site==="UK"){
			$returnUrl= "http://cgi.ebay.co.uk/ws/eBayISAPI.dll?ViewItem\u0026item=";
		}  else if ($site==="Germany"){
			$returnUrl="http://cgi.ebay.de/ws/eBayISAPI.dll?ViewItem\u0026item=";
		}
		
		return $returnUrl;
		
	}
	
	
	
}
	
	
	
