<?php

namespace eagle\modules\amazon\apihelpers;
use \Yii;
use eagle\models\SaasAmazonAutosync;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\models\AmazonTempOrderidQueueHighpriority;
use eagle\models\AmazonTempOrderidQueueLowpriority;
use eagle\models\AmzOrder;
use eagle\models\AmzOrderDetail;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\AmazonOrderSubmitQueue;
use eagle\models\SaasAmazonUser;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\models\SellerCallPlatformApiCounter;
/**
 +---------------------------------------------------------------------------------------------
 * eagle的web代码(非amazon的后台程序)需要跟amazon proxy进行交互，这里的helper提供了通信的基础函数
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		xjq		2014/08/01				初始化
 +---------------------------------------------------------------------------------------------
 **/

/**
 *
 * @status 				Statuses shall be retrieved. e.g. Unshipped / Unshipped,Shipped
 * Valid Status are below:
 * Pending 订单已下达，但付款
 *  		尚未经过授权。未准
 *  		备好进行发货。
 * Unshipped 付款已经过授权，订
 *  		单已准备好进行发
 *  		货，但订单中商品尚
 *  		未发运。
 * PartiallyShipped 订单中的一个或多个
 *  		（但并非全部）商品
 *  		已经发货。
 * Shipped 订单中的所有商品均
 *  		已发货。
 * InvoiceUnconfirmed 订单内所有的商品都
 *  		已发货，但是卖家还
 *  		没有向亚马逊确认已
 *  		经向买家寄出发票。
 *  		请注意：此参数仅适
 *  		用于中国地区。
 * Canceled 订单已取消。
 * Unfulfillable 订单无法进行配送。
 *  		该状态仅适用于通过
 *  		亚马逊零售网站之外
 *  		的渠道下达但由亚马
 *  		逊进行配送的订单。
 *
 *  请注意： 在此版本的“订单 API”部分中，必须
 *  	    同时使用未发货和已部分发货。仅使用
 *		    其中一个状态值，则会返回错误。
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 +---------------------------------------------------------------------------------------------
 **/

class AmazonSyncFetchOrderApiHelper{
	
	public static $AMAZON_MARKETPLACE_REGION_CONFIG = array(
		'A2EUQ1WTGCTBG2'=>"CA",
		'ATVPDKIKX0DER'=>"US",
		'A1PA6795UKMFR9'=>"DE",
		'A1RKKUPIHCS9HS'=>"ES",
		'A13V1IB3VIYZZH'=>"FR",
		'A21TJRUUN4KGV'=>"IN",
		'APJ6JRA9NG5V4'=>"IT",
		'A1F83G8C2ARO7P'=>"UK",
		'A1VC38T7YXB528'=>"JP",
		'AAHKV2X7AFYLW'=>"CN",
		'A1AM78C64UM0Y8'=>"MX",
		'A39IBJ37TRP1C6'=>"AU",
		'A2Q3Y263D00KWC'=>"BR",
        'A2VIGQ35RCS4UG'=>"AE",
        'A33AVAJ2PDY3EV'=>"TR",
		'A19VAU5U5O7RUS'=>"SG",
    );
	
	public static $COUNTRYCODE_NAME_MAP=array("US"=>"美国","CA"=>"加拿大","DE"=>"德国","ES"=>"西班牙","FR"=>"法国","IN"=>"印度","IT"=>"意大利",
	"UK"=>"英国","JP"=>"日本","CN"=>"中国","MX"=>"墨西哥","AU"=>"澳大利亚","BR"=>"巴西","TR"=>"土耳其","AE"=>"阿联酋","SG"=>"新加坡");
	
	const EAGLE_UNSHIPPED=0;
	const EAGLE_PARTIALSHIPPED=1;
	const EAGLE_SHIPPED=2;
	public static $cronJobId=0; 
	public static $amazonAccessInfoMap;
	
	//amazon平台的状态跟eagle的订单状态的对应关系
	public static $AMAZON_EAGLE_ORDER_STATUS_MAP = array(
		//	'Pending' =>100,  //等待买家付款
			'Unshipped' => 200, //买家已付款
			'Shipped' => 500,//CUBE_CONST::SentGood,  //卖家已发货
			'PartiallyShipped' => 200,
			'Canceled'=>600
			//'Canceled' => $Canceled,	//交易关闭
	);
	
	public static $CronFetchOrderListStatus='Unshipped,Shipped,PartiallyShipped,Canceled'; // eagle拉取amazon平台的订单状态
	public static $CronFetchFBAOrderListStatus='Shipped,Canceled'; // eagle拉取amazon平台的FBA订单状态
	
	/**
	 * @return the $cronJobId
	 */
	public static function getCronJobId() {
		return AmazonSyncFetchOrderApiHelper::$cronJobId;
	}

	/**
	 * @param number $cronJobId
	 */
	public static function setCronJobId($cronJobId) {
		AmazonSyncFetchOrderApiHelper::$cronJobId = $cronJobId;
	}
	
	
	/**
	 * 参数$amazonUserId----- SaasAmazonUserMarketplace的 amazon_uid
	 */
	private static function _getAmazonAccessInfo($amazonUserId){
		if (!isset(self::$amazonAccessInfoMap[$amazonUserId])) {
			$row=SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid"=>$amazonUserId))->asArray()->one();
			//$row=SaasAmazonUserMarketplace::model()->find("amazon_uid=:amazon_uid",array(":amazon_uid"=>$SAA_obj->amazon_user_id));
			//echo "row:".print_r($row->attributes,true);
			//echo "amazonAccessInfoMap amazon_user_id:".$SAA_obj->amazon_user_id."\n";
			if ($row<>null){
				echo "amazonAccessInfoMap 2\n";
				self::$amazonAccessInfoMap[$amazonUserId]=$row;
			}
		}
		$amazonAccessInfo=self::$amazonAccessInfoMap[$amazonUserId];
		
		return $amazonAccessInfo;
	} 

	/**
	 * 通过访问proxy获取amazon的订单header信息，并保存到临时数据表（为了后面获取items信息）同时保存到用户数据库（如：user_1）对应的amazon订单原始数据表中。  type--表示订单类型，  非fba或者fba！！
	 * @param unknown $puid
	 * @param unknown $autoSyncId  ----------  saas_amazon_autosync数据表的id
	 * @param unknown $configParams
	 * @param unknown $fetch_begin_time -------- unix时间戳
	 * @param unknown $fetch_end_time -------- unix时间戳
	 * @param string $type
	 * @return multitype:boolean number string |multitype:boolean string unknown
	 */
// 	private static function _getOrderHeaderFromAmazonAndSave($puid,$autoSyncId,$configParams,$fetch_begin_time,$fetch_end_time,$type="MFN"){		
		
// 		//1.整理请求参数
// 		$config=$configParams;
// 		$merchantId=$config["merchant_id"];
// 		$marketplaceId=$config["marketplace_id"];
// 		//需要获取的订单list的开始时间gmt0，注意：如果这个超过当前时间的话，amazon会请求失败！！！！！
// 		$fromDateTime=gmdate("Y-m-d H:i:s",$fetch_begin_time); 
// 		$toDateTime=gmdate("Y-m-d H:i:s",$fetch_end_time);
// 		$reqParams=array();		
// 		$reqParams["fromDateTime"]=$fromDateTime;		
// 		//$reqParams["fromDateTime"]="2014-06-19 09:29:05";		
// 		$reqParams["toDateTime"]=$toDateTime;
// 		$reqParams["orderType"]=$type;
// 		$reqParams["config"]=json_encode($config);
// 		if ($type=="AFN")  $reqParams["status"]=self::$CronFetchFBAOrderListStatus;
// 		else $reqParams["status"]=self::$CronFetchOrderListStatus;		
// 		$timeout=140; //超时设置
// 		//echo "before call_amazon_api fromDateTime:$fromDateTime,toDateTime:$toDateTime  \n";
// //		Yii::info("before call_amazon_api fromDateTime:$fromDateTime,toDateTime:$toDateTime  ","file");
// 		//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","before call_amazon_api fromDateTime:$fromDateTime,toDateTime:$toDateTime config:".print_r($reqParams,true),"info");
		
// 		Yii::info("call_amazon_api order_list merchantId:$merchantId,marketplaceId:$marketplaceId,fromDateTime:$fromDateTime,toDateTime:$toDateTime","file");
// 		try
// 		{
// 			$retInfo=AmazonProxyConnectApiHelper::call_amazon_api("getorder",$reqParams,$timeout);
// 			$getProxyResultTimeStr=date("Y-m-d H:i:s");
// 			Yii::info("after call_amazon_api order_list  merchantId:$merchantId,marketplaceId:$marketplaceId,type:$type,retinfo","file");
// 		}catch(\Exception $e){
// 			Yii::error("AmazonProxyConnectApiHelper::call_amazon_api error msg:".$e->getMessage(),"file");
// 			return array(false,-1,"AmazonProxyConnectApiHelper::call_amazon_api  exception");
// 		}
// 		//echo "after call_amazon_api retinfo:".print_r($retInfo,true);
		
		
		
		
// 		//检查返回结果	
// 		//异常情况：eagle跟proxy的通信有问题，proxy跟amazon通信有问题，proxy的返回的结果结构上有问题。
// 		//1. eagle跟proxy的通信有问题
// 		if ($retInfo["success"]===false){
// 			if (isset($retInfo["message"])) $errorMsg=$retInfo["message"];
// 			else $errorMsg="amazon proxy return error";
			
// 			return array(false,-1,$getProxyResultTimeStr." ".$errorMsg);
// 		}
		
// 		//1.1 无论proxy获取amazon信息结果如何，记录访问成功的 amazon api次数
// 		$SAU_obj = SaasAmazonUser::findOne(['merchant_id'=>$merchantId]);
// 		if(!empty($SAU_obj)){
// 		/*	if(isset($retInfo['response']['apiCallTimes'])){
		
// 				// 获取某个 puid 的amazon merchant 这个小时里  getorder 的调用次数记录
// 				$apiCounter = SellerCallPlatformApiCounter::findOne(['puid'=>$SAU_obj->uid,'platform'=>'amazon','merchant_id'=>$SAU_obj->merchant_id,'datetime'=>date('Y-m-d H',time()),'call_type'=>'getorder']);
// 				if(empty($apiCounter)){
// 					$apiCounter = new SellerCallPlatformApiCounter();
// 					$apiCounter->puid = $SAU_obj->uid;
// 					$apiCounter->platform = 'amazon';
// 					$apiCounter->merchant_id = $SAU_obj->merchant_id;
// 					$apiCounter->datetime = date('Y-m-d H',time());
// 					$apiCounter->call_type = 'getorder';// amazon 的与 call_amazon_api 的action一致
// 					$apiCounter->count = 1;
// 				}else{
// 					$apiCounter->count = $apiCounter->count + 1;
// 				}
		
// 				if(!$apiCounter->save()){
// 					Yii::error('_getOrderItemsFromAmazonById $apiCounter->save() error :'.print_r($apiCounter->getErrors() , true),"file");
// 				}
		
// 			}else{
// 				Yii::error("_getOrderItemsFromAmazonById count apiCallTimes error : apiCallTimes is not included. uid:".$SAU_obj->uid." response:".print_r($retInfo['response'],true),"file");
// 			}*/
		
// 		}else{
// 			Yii::error("_getOrderItemsFromAmazonById count apiCallTimes error : Cannot find SaasAmazonUser merchantId:".$merchantId,"file");
// 		}
		
// 		//2. proxy跟amazon通信有问题
// 		if ($retInfo["response"]["success"]===false){
// 			//先检查是否该用户订单数太多，导致第一次拉取的超时或者超出访问限制
// 			if (isset($retInfo["response"]["order"])){
// 				if (count($retInfo["response"]["order"])>200){
// 					return array(false,-3,$getProxyResultTimeStr." "."he gets too much orders");
// 				}
// 			}
						
// 			if (isset($retInfo["response"]["message"]))  $errorMsg=$retInfo["response"]["message"];
// 			else $errorMsg="amazon proxy return error";
			
// 			return array(false,-1,$getProxyResultTimeStr." ".$errorMsg);
// 		}
// 		//3. proxy的返回的结果结构上有问题
// 		if (!in_array("order",array_keys($retInfo["response"]))) {			
// 			Yii::error("no [order] element in reponse data structure","file");
// 			return array(false,-1,$getProxyResultTimeStr." "."no [order] element in reponse data structure");
// 		}
		
		
// 		//记录proxy访问amazon的重试情况
// 		$retryCount=$retInfo["response"]["retryCount"];
// 		if (($retInfo["response"]["order"]===null) or count($retInfo["response"]["order"])==0) { //no order returns
// 			//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","after call amazon_api retryCount:$retryCount,orderNumber:".count($ordersArr),"info");
// 			Yii::info("after call amazon_api retryCount:$retryCount,orderNumber:".count($retInfo["response"]["order"]),"file");
// 			return array(true,$retryCount,"");
// 		}		
		
// 		//检查是否该用户订单数太多，导致第一次拉取的超时或者超出访问限制
// 		if ($retInfo["success"]===true and $retInfo["response"]["success"]===false  )  {
// 			if (isset($retInfo["response"]["order"])){
// 				if (count($retInfo["response"]["order"])>200){
// 					return array(false,-3,$getProxyResultTimeStr." "."he gets too much orders");
// 				}
// 			}
// 		}
		
		

// 		//get all orderids from response data
// 		$ordersArr=$retInfo["response"]["order"];
// 		echo "after call amazon_api retryCount:$retryCount,orderNumber:".count($ordersArr)." \n";
// //		SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","after call amazon_api retryCount:$retryCount,orderNumber:".count($ordersArr),"info");
        
			
		
// 		foreach($ordersArr as $order){
// 			$orderId=$order["AmazonOrderId"];
// 			echo "orderId:".$orderId." \n";		
			
// 			//$readyGetDetailQueue = AmazonTempOrderidQueue::model()->find('order_id = :orderId',array(':orderId'=>$orderId));
// 			$readyGetDetailQueue = AmazonTempOrderidQueue::find()->where('order_id = :orderId',array(':orderId'=>$orderId))->one();
// 			if ($readyGetDetailQueue===null){
// 				//不存在，需要insert
// 				$readyGetDetailQueue=new AmazonTempOrderidQueue();
// 				$readyGetDetailQueue->order_id=$orderId;
// 				$readyGetDetailQueue->type=$type;				
// 				$readyGetDetailQueue->create_time=time();
// 				$readyGetDetailQueue->update_time=$readyGetDetailQueue->create_time;
// 				$readyGetDetailQueue->saas_amazon_autosync_id=$autoSyncId;
// 				$readyGetDetailQueue->error_count=0;
// 				$readyGetDetailQueue->process_status=0;
// 				$readyGetDetailQueue->puid=$puid;
				
				
// 				$order["type"]=$type; // header json 信息中处理type都是amazon原始返回的信息; type   MFN or AFN
// 				$readyGetDetailQueue->order_header_json=json_encode($order);				
// 			}else{
// 				//存在，需要update
// 				$readyGetDetailQueue->puid=$puid;
// 				$readyGetDetailQueue->order_header_json=json_encode($order);
// 				$readyGetDetailQueue->process_status=0; //重置状态重新获取订单内容
// 				$readyGetDetailQueue->update_time=time();					
// 			}
// 			if (!$readyGetDetailQueue->save()){
// 				//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","readyGetDetailQueue->save error:".print_r($readyGetDetailQueue->errors,true),"error");
// 				Yii::error("readyGetDetailQueue->save error:".print_r($readyGetDetailQueue->errors,true),"file");
// 			}
// 		}
// 		Yii::info("after all order_header save ,orderNumber:".count($ordersArr),"file");
			
// 		return array(true,$retryCount,"");			
// 	}
	/**
	 * 保存order的header信息到user_库的amz_order表
	 * @param $orderHeaderArr---amazon的返回的order header信息（就是amazon order list接口返回的其中1个订单的信息）
	 * @param $merchantId
	 * @param $marketPlaceId
	 * @return array($ret,$message)
	 * $ret--- true or false
	 */
	// public static function _saveAmazonOriginHeaderByArr($orderHeaderArr,$merchantId,$marketPlaceId){
	// 	echo "merchantId:$merchantId marketPlaceId:$marketPlaceId \n";
	// 	$orderHeaderArr["merchant_id"]=$merchantId;
	// 	$orderHeaderArr["marketplace_short"]=self::$AMAZON_MARKETPLACE_REGION_CONFIG[$marketPlaceId];
	// 	//		$amazonOrder=AmzOrder::model()->findByPk($orderHeaderArr["AmazonOrderId"]);
	// 	$amazonOrder=AmzOrder::find()->where(["AmazonOrderId"=>$orderHeaderArr["AmazonOrderId"]])->one();		
	// 	if ($amazonOrder){
	// 		//已经存在
	// 		$amazonOrder->attributes=$orderHeaderArr;
	// 	}else{
	// 		$amazonOrder=new AmzOrder();
	// 		$amazonOrder->attributes=$orderHeaderArr;
	// 	}
		
	// 	if (isset($orderHeaderArr["EarliestShipDate"]))		$amazonOrder["EarliestShipDate"]=strtotime($orderHeaderArr["EarliestShipDate"]);
	// 	if (isset($orderHeaderArr["LatestShipDate"]))		$amazonOrder["LatestShipDate"]=strtotime($orderHeaderArr["LatestShipDate"]);
	// 	if (isset($orderHeaderArr["EarliestDeliveryDate"]))		$amazonOrder["EarliestDeliveryDate"]=strtotime($orderHeaderArr["EarliestDeliveryDate"]);
	// 	if (isset($orderHeaderArr["LatestDeliveryDate"]))		$amazonOrder["LatestDeliveryDate"]=strtotime($orderHeaderArr["LatestDeliveryDate"]);
		
		
	// 	//TODO  save(false) for good performance
	// 	if ($amazonOrder->save()) return array(true,"");
	// 	$errorMessage="amazonOrder->save() orderid:".$orderHeaderArr["AmazonOrderId"].print_r($amazonOrder->errors,true);
	// 	//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"",$errorMessage,"error");
	// 	Yii::info("err:".$errorMessage,"file");
	// 	Yii::error($errorMessage,"file");
	// 	return array(false,$errorMessage);
			
	// }
	// syncId=>array(("merchant_id"=>,"marketplace_id"=>,uid=>),)
	public static function _getSyncIdAccountInfoMap($syncid_array){
		$syncIdAccountMap=array();
		$syncIdUidMap = array();
		if (empty($syncid_array)) {
			$sql="SELECT aa.id AS sync_id, am.access_key_id AS access_key_id,am.secret_access_key AS secret_access_key,am.mws_auth_token AS mws_auth_token,aa.platform_user_id AS merchant_id, aa.site_id AS marketplace_id, au.uid AS uid ".
              " FROM  `saas_amazon_user` au, saas_amazon_autosync_v2 aa, saas_amazon_user_marketplace am ".
              " WHERE au.amazon_uid = aa.eagle_platform_user_id ".
             " AND am.amazon_uid = aa.eagle_platform_user_id ".
             " AND aa.site_id = am.marketplace_id ";
		}else{
			$sql="SELECT aa.id AS sync_id, am.access_key_id AS access_key_id,am.secret_access_key AS secret_access_key,am.mws_auth_token AS mws_auth_token,aa.platform_user_id AS merchant_id, aa.site_id AS marketplace_id, au.uid AS uid ".
              " FROM  `saas_amazon_user` au, saas_amazon_autosync_v2 aa, saas_amazon_user_marketplace am ".
               " WHERE aa.id  IN (".implode(',',$syncid_array).")".
              " AND au.amazon_uid = aa.eagle_platform_user_id ".
             " AND am.amazon_uid = aa.eagle_platform_user_id ".
             " AND aa.site_id = am.marketplace_id ";
		}
		$rows=Yii::$app->db->createCommand($sql)->queryAll();

		foreach($rows as $row){
	//		$accountInfo=array();
//			$accountInfo["uid"]=$row["uid"];
//			$accountInfo["merchant_id"]=$row["merchant_id"];
		//	$accountInfo["marketplace_id"]=$row["marketplace_id"];
			$syncIdUidMap[$row["sync_id"]]=$row;			
		}
		return $syncIdUidMap;			
	}
	// merchant_id=>array(("access_key_id"=>,)	
	public static function _getMerchantIdAccountInfoMap(){
		$merchantIdAccountMap=array();
		$tempAmazonUids=array();
		$rows=SaasAmazonUserMarketplace::find()->asArray()->all();
		foreach($rows as $row){
			if (isset($tempAmazonUids[$row["amazon_uid"]])) continue;
			$userObj=SaasAmazonUser::findOne($row["amazon_uid"]);
			if ($userObj){
				$merchantIdAccountMap[$userObj->merchant_id]=$row;
				$tempAmazonUids[]=$row["amazon_uid"];
			} 
		}
		
		return $merchantIdAccountMap;
	}
	
	
	//get the order items from amazon 
	private static function _getOrderItemsFromAmazonById($orderId,$accountInfo)
	{
		// call the proxy method
		$config=array(
				'merchant_id' => $accountInfo["merchant_id"],
				'marketplace_id' => $accountInfo["marketplace_id"],
		        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 				'access_key_id' => $accountInfo["access_key_id"],
// 				'secret_access_key' => $accountInfo["secret_access_key"],
		        'mws_auth_token' => $accountInfo["mws_auth_token"],
		);
		$reqParams=array();
		$reqParams["config"]=json_encode($config);
		$reqParams["orderid"]=$orderId;	
		
		try{
			echo "request parameters:".print_r($reqParams,true);
			$timeout=60; //s
			$retInfo=AmazonProxyConnectApiHelper::call_amazon_api("getorderitem",$reqParams,$timeout);
			echo "return:".print_r($retInfo,true);
		//	\Yii::info("return:".print_r($retInfo,true),"file");
		}catch(\Exception $e) {		
			Yii::error("_getOrderItemsFromAmazonById:$orderId AmazonProxyConnectApiHelper::call_amazon_api error msg:".$e->getMessage(),"file");
			return array(false,array(),0,"AmazonProxyConnectApiHelper::call_amazon_api  exception");			
		}
			
		//检查返回结果
		//异常情况：eagle跟proxy的通信有问题，proxy跟amazon通信有问题，proxy的返回的结果结构上有问题。
		//1. eagle跟proxy的通信有问题
		if ($retInfo["success"]===false){
			if (isset($retInfo["message"])) $errorMsg=$retInfo["message"];
			else $errorMsg="amazon proxy return error";

			return array(false,array(),0,$errorMsg);			
		}
		
		//1.1 无论proxy获取amazon信息结果如何，记录访问成功的 amazon api次数
		if(!empty($accountInfo['uid'])){
		/*	if(isset($retInfo['response']['apiCallTimes'])){
				
				// 获取某个 puid 的amazon merchant 这个小时里  getorderitem 的调用次数记录
				$apiCounter = SellerCallPlatformApiCounter::findOne(['puid'=>$accountInfo['uid'],'platform'=>'amazon','merchant_id'=>$accountInfo["merchant_id"],'datetime'=>date('Y-m-d H',time()),'call_type'=>'getorderitem']);
				if(empty($apiCounter)){
					$apiCounter = new SellerCallPlatformApiCounter();
					$apiCounter->puid = $accountInfo['uid'];
					$apiCounter->platform = 'amazon';
					$apiCounter->merchant_id = $accountInfo["merchant_id"];
					$apiCounter->datetime = date('Y-m-d H',time());
					$apiCounter->call_type = 'getorderitem';// amazon 的与 call_amazon_api 的action一致
					$apiCounter->count = 1;
				}else{
					$apiCounter->count = $apiCounter->count + 1;
				}
				
				if(!$apiCounter->save()){
					Yii::error('_getOrderItemsFromAmazonById $apiCounter->save() error :'.print_r($apiCounter->getErrors() , true),"file");
				}
				;
				
			}else{
				Yii::error("_getOrderItemsFromAmazonById count apiCallTimes error : apiCallTimes is empty. uid:".$accountInfo['uid']." response:".print_r($retInfo['response'],true),"file");
			}*/
				
		}else{
			Yii::error("_getOrderItemsFromAmazonById:$orderId count apiCallTimes error : uid is empty. accountInfo: ".print_r($accountInfo,true),"file");
		}
		
		
		//2. proxy跟amazon通信有问题
		if ($retInfo["response"]["success"]===false){
			if (isset($retInfo["response"]["message"]))  $errorMsg=$retInfo["message"];
			else $errorMsg="amazon proxy return error";
				
			return array(false,array(),0,$errorMsg);	
		}
		
	
		$retryCount=$retInfo["response"]["retryCount"];
		//get all orderids from response data
		$itemsArr=$retInfo["response"]["item"];
	//	foreach($itemsArr as $item){
			// insert into
			//var_dump($item);
			//insertIntoOrderItem($item,$orderId,$saas_amazon_autosync_id);
		//}
		return array(true,$itemsArr,$retryCount,"");
	}	

	//根据item信息，计算order header subtotal,shipping cost,total discount 这3个信息在amazon的原始header中没有包括
	public static function _caculateTotalAmount($orderItems) {
		$subtotal=0;
		$shippingCost=0;
		$totalDiscount=0;
		foreach($orderItems as $item){
			$itemPrice=isset($item["ItemPrice"])?$item["ItemPrice"]:0;
			$subtotal=$subtotal+$itemPrice*$item["QuantityOrdered"];
			$shipDiscount=isset($item["ShippingDiscount"])?$item["ShippingDiscount"]:0;
			$promotionDiscount=isset($item["PromotionDiscount"])?$item["PromotionDiscount"]:0;
			$shippingPrice=isset($item["ShippingPrice"])?$item["ShippingPrice"]:0;
			
			$totalDiscount=$totalDiscount+$shipDiscount+$promotionDiscount;
			$shippingCost=$shippingCost+$shippingPrice;
		}
	
		return array($subtotal,$shippingCost,$totalDiscount);
	}	
	
	//该订单是取消状态，需要根据amazon 订单items的信息来判断指定的订单的发货状态--没发货，部分发货或者全部发货
	private static function _getShipStatusFromAmazonOrderItems($orderItems){
		//$shipped=SaasAmazonAutoSyncHelper::EAGLE_UNSHIPPED;
		$isUnshipped=true;
		$isAllShipped=true;		
		foreach($orderItems as $item){
			if ($item['QuantityShipped']>0) $isUnshipped=false;
			if  ($item['QuantityOrdered']>$item['QuantityShipped']  ){
				$isAllShipped=false;
			}
		}
		
		if ($isUnshipped) return self::EAGLE_UNSHIPPED;
		if ($isAllShipped)	return self::EAGLE_SHIPPED;
		
		return self::EAGLE_PARTIALSHIPPED;
		
		
	}
	
	

	/**
	 * 把amazon的订单信息header和items 同步到eagle1系统中user_库的od_order和od_order_item。  
	 * 这里主要是通过eagle1提供的 http api的方式
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveAmazonOrderToOldEagle1($orderHeaderInfo,$orderItems,$uid,$merchantId,$marketplaceId){
	
		
		
		
		list($subtotal,$shippingCost,$totalDiscount)=self::_caculateTotalAmount($orderItems);
		$reqParams=array();
		$oneOrderReq=array();
		$oneOrderReq["subtotal"]=$subtotal;
		$oneOrderReq["shipping_cost"]=$shippingCost;
		$oneOrderReq["discount_amount"]=$totalDiscount;
		
		$oneOrderReq["order_source_create_time"] = strtotime($orderHeaderInfo["PurchaseDate"]);
		
		//订单类型 FBA或非FBA----------非FBA的订单，默认不传入order_type 参数或该参数值传入为空
		if (isset($orderHeaderInfo["type"]) and $orderHeaderInfo["type"]=="AFN") $oneOrderReq["order_type"] ="FBA";
		
		$oneOrderReq["paid_time"]=$oneOrderReq["order_source_create_time"];
			
		
		$oneOrderReq["order_status"]=self::$AMAZON_EAGLE_ORDER_STATUS_MAP[$orderHeaderInfo["Status"]];
		
		if (strtolower($orderHeaderInfo["Status"])=="canceled"){
			//订单状态canceled的时候，需要根据items的信息来判断发货状态
			$oneOrderReq['shipping_status']=self::_getShipStatusFromAmazonOrderItems($orderItems);
		}else{
			if (strtolower($orderHeaderInfo["Status"])=="unshipped") $oneOrderReq['shipping_status']=self::EAGLE_UNSHIPPED;
			else  if (strtolower($orderHeaderInfo["Status"])=="shipped") $oneOrderReq['shipping_status']=self::EAGLE_SHIPPED;
			else  if (strtolower($orderHeaderInfo["Status"])=="partiallyshipped") $oneOrderReq['shipping_status']=self::EAGLE_PARTIALSHIPPED;
		}
		
		
		$oneOrderReq["order_source"]="amazon";
		$oneOrderReq["selleruserid"]=$merchantId;
		$oneOrderReq["order_source_site_id"]=self::$AMAZON_MARKETPLACE_REGION_CONFIG[$marketplaceId];
		
		if (strtolower($orderHeaderInfo["Status"])<>"canceled"){
			$sysCountry=SysCountry::findOne(['country_code'=>$orderHeaderInfo['CountryCode']]);
			if($sysCountry<>null)	$oneOrderReq["consignee_country"]=$sysCountry->country_en;
		}
			
		
		$headerMap = array(
				//"selleruserid" => "merchant_id",
				//"order_source_site_id" => "marketplace_short",
				"order_source_order_id" => "AmazonOrderId",
				"source_buyer_user_id" => "Name",
				"consignee_country_code" => "CountryCode",
				"consignee_province" => "State",
				"consignee_city" => "City",
				"consignee_postal_code" => "PostalCode",
				"consignee_phone" => "Phone",
				"currency" => "Currency",
				//"payment_method" => "PaymentMethod",
				"consignee_email" => "BuyerEmail",
				"consignee_address_line1" => "AddressLine1",
				"consignee_address_line2" => "AddressLine2",
				"consignee_address_line3" => "AddressLine3",
				"order_source_shipping_method" => "ShipServiceLevel",
				"consignee_district" => "District",
				"consignee_county" => "County",
				"grand_total" => "Amount",
				"consignee" => "Name"
		);
		//1. 设置header的信息
		foreach($headerMap as $key1=>$key2){
			if (isset($orderHeaderInfo[$key2])) {
				$oneOrderReq[$key1]=$orderHeaderInfo[$key2];
			}else{
				//echo "_saveAmazonOrderToEagle orderHeaderInfo $key2  not isset \n";
			}
		}
		
		$itemMap=array("OrderItemId"=>"order_source_order_item_id","PromotionDiscount"=>"promotion_discount",
				"ShippingPrice"=>"shipping_price","ShippingDiscount"=>"shipping_discount","SellerSKU"=>"sku","ItemPrice"=>"price",
				"QuantityOrdered"=>"quantity","QuantityShipped"=>"sent_quantity","Title"=>"product_name","ASIN"=>"asin"
		);
		
		//2. 设置item的信息
		$itemsInfo=array();
		foreach($orderItems as $item){
			$oneItem=array();
			foreach($itemMap as $key=>$value){
				if (isset($item[$key])) {
					$oneItem[$value]=$item[$key];
				}
			}
			$itemsInfo[]=$oneItem;
		}
		$oneOrderReq["items"]=$itemsInfo;
		
		//3. 总的请求信息
		$reqInfo=array();
		$ordersReq=array();
		$ordersReq[]=$oneOrderReq;
		//$uid=$merchantidUidMap[$orderHeaderInfo["merchant_id"]];
		//其中Uid是saas_amazon_user中的uid，这里为了便于eagle的api找到合适的数据库。
		$uid=\Yii::$app->subdb->getCurrentPuid();
		$reqInfo[$uid]=$ordersReq;
		$reqInfoJson=json_encode($reqInfo,true);
		
		//		$reqInfoJson=json_encode($reqInfo,true);
		//		write_to_log("call_eagle_api reqParam:".$reqInfoJson,"debug", __FUNCTION__,basename(__FILE__)); //save the request
		echo "before OrderHelper::importPlatformOrder info:".json_encode($reqInfo,true)."\n";
		$postParams=array("orderinfo"=>$reqInfoJson);
		$result=EagleOneHttpApiHelper::sendOrderInfoToEagle($postParams);
		echo "result:".print_r($result,true);
		
	//	$result =  OrderHelper::importPlatformOrder($reqInfo);
	//	echo "after OrderHelper::importPlatformOrder result:".print_r($result,true);
	//	if ($result['success']===1){ // result['success']    0----ok,1---fail
	//		SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","OrderHelper::importPlatformOrder fails. error:".$result['message'],"error");
	//	}		
		
	
	
		return $result;
	}	
	
	
	/**
	 * 把amazon的订单信息header和items 同步到eagle系统中user_库的od_order和od_order_item 
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	// private static function _saveAmazonOrderToEagle($orderHeaderInfo,$orderItems,$uid,$merchantId,$marketplaceId,$eagleOrderId=-1){
		
	// 	//！！！！！！注意：如果订单状态为取消 header和item信息量少				
	// 	/*
	// 	 * header信息
	// 	 * {"AmazonOrderId":"171-1288495-6303559","PurchaseDate":"2015-04-12T20:40:27Z","LastUpdateDate":"2015-04-14T06:22:58Z","Status":"Canceled",
	// 	 * "SalesChannel":"Amazon.fr","ShipServiceLevel":"Std FR Dom","Currency":"EUR","Amount":"38.99","NumberOfItemsShipped":"0","NumberOfItemsUnshipped":"0",
	// 	 * "ShipmentServiceLevelCategory":"Standard","EarliestShipDate":"2015-04-12T22:00:00Z","LatestShipDate":"2015-04-14T21:59:59Z","FulfillmentChannel":"MFN",
	// 	 * "MarketplaceId":"A13V1IB3VIYZZH","ShippedByAmazonTFM":"false","OrderType":"StandardOrder","type":"MFN"}
 //         *
	// 	 * 如果是FBA的订单，信息可能更少。 如：没有Amount
	// 	 *
	// 	 * 
	// 	 * item信息		  
	// 	 * [ASIN] => B00KXXMMFA
	// 	 * [SellerSKU] => W13000000
	// 	 * [OrderItemId] => 3098360
	// 	 * [Title] => Nillkin H+ 
	// 	 * [QuantityOrdered] => 1
	// 	 * [QuantityShipped] => 0
	// 	 */		
		
	// 	//根据item信息，计算order header subtotal,shipping cost,total discount 这3个信息在amazon的原始header中没有包括		 
	// 	list($subtotal,$shippingCost,$totalDiscount)=self::_caculateTotalAmount($orderItems);
		
	// 	//整理导入eagle平台的订单信息
	// 	$reqParams=array();
		
	// 	$amazonOrderId=$orderHeaderInfo['AmazonOrderId'];

	// 	//eagle的订单状态
	// 	$order_status=self::$AMAZON_EAGLE_ORDER_STATUS_MAP[$orderHeaderInfo["Status"]];
		
	// 	$is_manual_order=0;
	// 	/*if (strtolower($orderHeaderInfo["Status"])=="canceled"){
	// 			//挂起，需要及时处理的订单，可能不需要发货
	// 			$is_manual_order = 1;
	// 	}*/
	// 	//订单类型 MFN或AFN(FBA)----------非FBA的订单，默认不传入order_type 参数或该参数值传入为空
	// 	if (!isset($orderHeaderInfo["type"]) or $orderHeaderInfo["type"]=="") $order_type="MFN";
	// 	else $order_type=$orderHeaderInfo["type"];
		
	// 	$consignee_country="";
	// 	if (isset($orderHeaderInfo['CountryCode'])){
	// 		$sysCountry=SysCountry::findOne(['country_code'=>$orderHeaderInfo['CountryCode']]);
	// 		if ($sysCountry<>null) $consignee_country=$sysCountry->country_en;			
	// 	}
		
		

	// 	//1.  订单header信息
	// 	$order_arr=array(//主订单数组
	// 			'order_status'=>$order_status,
	// 			'order_source_status'=>$orderHeaderInfo["Status"],				
	// 			'is_manual_order'=>$is_manual_order,				
	// 			'order_source'=>'amazon',				
	// 			'order_type'=>$order_type,  //订单类型如amazon FBA订单
	// 			'order_source_site_id'=>self::$AMAZON_MARKETPLACE_REGION_CONFIG[$marketplaceId],
				
	// 			'order_source_order_id'=>$orderHeaderInfo['AmazonOrderId'],  //订单来源平台订单号
	// 			'selleruserid'=>$merchantId,
	// 			'source_buyer_user_id'=>isset($orderHeaderInfo['BuyerName'])?$orderHeaderInfo['BuyerName']:'',	//来源买家用户名			
	// 			'order_source_create_time'=>strtotime($orderHeaderInfo["PurchaseDate"]), //时间戳
	// 			'subtotal'=>$subtotal,
	// 			'shipping_cost'=>$shippingCost,
	// 			'grand_total'=>isset($orderHeaderInfo['Amount'])?$orderHeaderInfo['Amount']:0,
	// 			'discount_amount'=>$totalDiscount,
	// 			'currency'=>isset($orderHeaderInfo['Currency'])?$orderHeaderInfo['Currency']:'USD', //TODO currency需要提供，不能为空，不然导入订单到eagle接口会有问题
	// 			'consignee'=>isset($orderHeaderInfo['Name'])?$orderHeaderInfo['Name']:'',
	// 			'consignee_postal_code'=>isset($orderHeaderInfo['PostalCode'])?$orderHeaderInfo['PostalCode']:'',
	// 			'consignee_city'=>isset($orderHeaderInfo['City'])?$orderHeaderInfo['City']:'', 
	// 			'consignee_phone'=>isset($orderHeaderInfo['Phone'])?$orderHeaderInfo['Phone']:'',
	// 			//'consignee_mobile'=>"",
	// 			'consignee_email'=>isset($orderHeaderInfo['BuyerEmail'])?$orderHeaderInfo['BuyerEmail']:'',
	// 			'consignee_country'=>$consignee_country,
	// 			'consignee_country_code'=>isset($orderHeaderInfo['CountryCode'])?$orderHeaderInfo['CountryCode']:'',
	// 			'consignee_province'=>isset($orderHeaderInfo['State'])?$orderHeaderInfo['State']:'',
	// 			'consignee_address_line1'=>isset($orderHeaderInfo['AddressLine1'])?$orderHeaderInfo['AddressLine1']:'',
	// 			'consignee_address_line2' =>isset($orderHeaderInfo['AddressLine2'])?$orderHeaderInfo['AddressLine2']:'',
	// 			'consignee_address_line3' =>isset($orderHeaderInfo['AddressLine3'])?$orderHeaderInfo['AddressLine3']:'',
	// 			'paid_time'=>strtotime($orderHeaderInfo["PurchaseDate"]), //时间戳 , PurchaseDate是amazon 订单创建时间
	// 			//amazon是没有返回发货时间的！！！！！
	// 			//当订单是FBA的时候，LatestDeliveryDate貌似为空
	// 			//'delivery_time'=>isset($orderHeaderInfo['LatestDeliveryDate'])?strtotime($orderHeaderInfo['LatestDeliveryDate']):0, //时间戳
	// 			//'user_message'=>json_encode($OrderById['orderMsgList']),
	// 			//'orderShipped'=>$orderShipped,
	// 			// strtotime 对这种时间2016-03-16T06:59:59Z 返回utc 0时间戳
	// 			'fulfill_deadline'=>isset($orderHeaderInfo['LatestShipDate'])?strtotime($orderHeaderInfo['LatestShipDate']):'',// dzt20160312 加最迟发货时间 到订单表做提示
	// 			// dzt20160519 add 买家运输服务 for 匹配
	// 			'order_source_shipping_method'=>isset($orderHeaderInfo['ShipmentServiceLevelCategory'])?$orderHeaderInfo['ShipmentServiceLevelCategory']:'',
	// 	);
		
		
	// 	print_r($order_arr);  //lolotest
		
		
		
	// 	//2. 订单的items信息
	// 	$userMessage = '';
	// 	$orderitem_arr=array();//订单商品数组
	// 	foreach ($orderItems as $one){		
	// 		$orderItemsArr = array(
	// 				'order_source_order_id'=>$orderHeaderInfo['AmazonOrderId'],  //订单来源平台订单号
	// 				'order_source_order_item_id'=>$one['OrderItemId'],
	// 				//'order_source_transactionid'=>$one['childid'],//订单来源交易号或子订单号
	// 				'order_source_itemid'=>$one['ASIN'],//产品ID listing的唯一标示
					
	// 				'asin'=>$one['ASIN'],  //lolo add -- 速卖通貌似没有的
	// 				'sent_quantity'=>$one['QuantityShipped'],  //lolo add -- 速卖通貌似没有的
	// 				'promotion_discount'=>isset($one['PromotionDiscount'])?$one['PromotionDiscount']:0,   //lolo add -- 速卖通貌似没有的
	// 				'shipping_price'=>isset($one['ShippingPrice'])?$one['ShippingPrice']:0,  //lolo add -- 速卖通貌似没有的
	// 				'shipping_discount'=>isset($one['ShippingDiscount'])?$one['ShippingDiscount']:0,  //lolo add -- 速卖通貌似没有的
					
	// 				'sku'=>$one['SellerSKU'],//商品编码
	// 				'price'=>isset($one['ItemPrice'])?$one['ItemPrice']:0,//如果订单是取消状态，该字段amazon不会返回
	// 				'ordered_quantity'=>isset($one['QuantityOrdered'])?$one['QuantityOrdered']:0,//下单时候的数量
	// 				'quantity'=>isset($one['QuantityOrdered'])?$one['QuantityOrdered']:0,  //需发货的商品数量,原则上和下单时候的数量相等，订单可能会修改发货数量，发货的时候按照quantity发货
	// 				'product_name'=>$one['Title'],//下单时标题
	// 				'photo_primary'=>isset($one['SmallImageUrl'])?$one['SmallImageUrl']:"",//商品主图冗余
	// 			//	'desc'=>$one['memo'],//订单商品备注,
	// 			//	'product_attributes'=>$attr_str,//商品属性
	// 				//'product_unit'=>$one['productunit'],//单位
	// 				//'lot_num'=>$one['lotnum'],//单位数量
	// 			//	'product_url'=>$one['productsnapurl'],//商品url
	// 		);
	// 		//赋缺省值			
	// 		$orderitem_arr[]=array_merge(OrderHelper::$order_item_demo,$orderItemsArr);
	// 		//$userMessage = $one['memo'];
	// 	}

		
	// 	//订单商品
	// 	$order_arr['items']=$orderitem_arr;
	// 	//订单备注
	// 	$order_arr['user_message']= "";
	// 			//赋缺省值
	// 	$myorder_arr[$uid]=array_merge(OrderHelper::$order_demo,$order_arr);
		
	// 	//3.  订单header和items信息导入到eagle系统
	// //	\Yii::info("before OrderHelper::importPlatformOrder info:".json_encode($myorder_arr,true),"file");
	// 	try{
	//     	$result =  OrderHelper::importPlatformOrder($myorder_arr,$eagleOrderId);
	// 	}catch(\Exception $e){
	// 		echo "OrderHelper::importPlatformOrder fails. Exception  \n";
	// 		\Yii::error("OrderHelper::importPlatformOrder fails.  amazonId=$amazonOrderId  Exception error:".$e->getMessage()." trace:".$e->getTraceAsString(),"file");
		
	// 		return ['success'=>1,'message'=>$e->getMessage()];			
	// 	}
	// 	echo "after OrderHelper::importPlatformOrder result:".print_r($result,true);
	// 	// ！！！注意  result['success']的返回值。    0----表示ok,1---表示fail
	// 	if ($result['success']===1){ 
	// 	//	SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","OrderHelper::importPlatformOrder fails. error:".$result['message'],"error");
	// 		\Yii::error("OrderHelper::importPlatformOrder fails. amazonId=$amazonOrderId error:".$result['message'],"file");
	// 	}
		
		

	// 	return $result;
	// }
	//保存amazon的订单的items信息到数据库
	private static function _saveAmazonOriginItemsByArr($orderId,$itemsArr){
		/**  对于canceled状态，proxy的返回的订单其中1个item信息
		 * [ASIN] => B00KXXMMFA
		[SellerSKU] => W13000000
		[OrderItemId] => 3098360
		[Title] => Nillkin H+ 
		[QuantityOrdered] => 1
		[QuantityShipped] => 0
		 */
		
		foreach($itemsArr as $itemInfo){
			//$amazonOrderDetail=AmzOrderDetail::model()->findByPk($itemInfo["OrderItemId"]);
			$amazonOrderDetail=AmzOrderDetail::find()->where(["OrderItemId"=>$itemInfo["OrderItemId"]])->one();
			$itemInfo["AmazonOrderId"]=$orderId;
			if ($amazonOrderDetail===null){
				$amazonOrderDetail=new AmzOrderDetail();
			}
			$amazonOrderDetail->attributes=$itemInfo;
			if (!$amazonOrderDetail->save()){ 
				$errorMessage="amazonOrderDetail->save() orderid:".$itemInfo["OrderItemId"]." ".print_r($amazonOrderDetail->errors,true);
				//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"",$errorMessage,"error");
				Yii::error($errorMessage,"file");
				return array(false,$errorMessage);
			}
		}
		
		return array(true,"");
	}
	
	private static function _handleFetchItemsError($queueOrderForSync,$message){		
		$queueOrderForSync->process_status=3;
		$queueOrderForSync->error_count=$queueOrderForSync->error_count+1;
		$queueOrderForSync->error_message=$message;
		$queueOrderForSync->update_time=time();
		$queueOrderForSync->save(false);
		\Yii::error("amazon_fetch_orderitem fail orderid:".$queueOrderForSync->order_id."  ".$message,"file");
	}
	
	/**
	 * 先判断是否真的抢到待处理账号
	 * @param  $orderId  -- amazon_temp_orderid_queue表的orderid
	 * @return null或者$SAA_obj , null表示抢不到记录
	 */
	// private static function _lockAutosyncRecord($orderId){
	// 	$nowTime=time();
	// 	$connection=Yii::$app->db;
	// 	//$command = $connection->createCommand("update amazon_temp_orderid_queue set process_status=1,update_time=$nowTime where order_id ='". $orderId."' and process_status<>1 ") ;
	// 	$command = $connection->createCommand("update amazon_temp_orderid_queue set process_status=1,update_time=$nowTime where order_id ='". $orderId."' and (process_status=0 or process_status=3) ") ;
		
	// 	$affectRows = $command->execute();
	// 	if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
	// 	// 抢到记录		
	// 	//$SAA_obj = 	AmazonTempOrderidQueue::findOne($autosyncId);
	// 	$SAA_obj = 	AmazonTempOrderidQueue::find()->where(["order_id"=>$orderId])->one();
	// 	return $SAA_obj;
	// }	

	/**
	 * 后台触发多进程， 从amazon_temp_orderid_queue根据订单id，拉取amazon的订单items
	 * 这里才会把订单信息传递到eagle中！！！！
	 */
	// public static function cronAutoFetchOrderItems(){
	//     Yii::info("entering cronAutoFetchOrderDetail","file");
	    
	//     $backgroundJobId=self::getCronJobId();
		
	// 	$SAA_objs=AmazonTempOrderidQueue::find()
	// 	->where('(process_status=0 or process_status =3) and error_count<30')->limit(150)->all();
		
	// 	$handledOrderCount=0;		
	// 	$oneJobMaxHandleNum=800; // 一个进程最多处理订单个数
	// 	$hasGotRecord=false; //是否有抢到过待处理的记录
		
	// 	echo "count:".count($SAA_objs);
 //        Yii::info("There are ".count($SAA_objs)." orders waiting for fetching detail by id","file");
	// 	$syncIdAccountMap=self::_getSyncIdAccountInfoMap();
	// 	if(count($SAA_objs)){
	// 		foreach($SAA_objs as $SAA_obj) {
	// 			$syncId=$SAA_obj->saas_amazon_autosync_id;
	// 			if (!isset($syncIdAccountMap[$syncId])){
	// 				//TODO 
	// 				Yii::error("saas_amazon_autosync_id:".$syncId." not exist in saas_amazon_user","file");
	// 				$SAA_obj->process_status=4;//4 异常情况，不需要重试，等待it人工分析
	// 				$SAA_obj->update_time=time();
	// 				$SAA_obj->error_count=$SAA_obj->error_count+1;
	// 				$SAA_obj->error_message="saas_amazon_autosync_id:".$syncId." not exist in saas_amazon_user";						
	// 				$SAA_obj->save(false);
	// 				continue;						
	// 			}
				
	// //			$SAA_obj->process_status=1;
	// //			$SAA_obj->update_time=time();
	// //			$SAA_obj->save(false);
				
	// 			$SAA_obj=self::_lockAutosyncRecord($SAA_obj->order_id);
	// 			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
	// 			$hasGotRecord=true;  // 抢到记录
				
	// 			$timeMS1=TimeUtil::getCurrentTimestampMS();
				
	// 			\Yii::info("before _getOrderItemsFromAmazonById amazonId:".$SAA_obj->order_id,"file");
	// 			list($ret,$itemsArr,$retryCount,$errorMessage)=self::_getOrderItemsFromAmazonById($SAA_obj->order_id, $syncIdAccountMap[$syncId]);
	// 			\Yii::info("after _getOrderItemsFromAmazonById amazonId:".$SAA_obj->order_id,"file");
				
	// 			if ($ret==false) {
	// 				//TODO
	// 				self::_handleFetchItemsError($SAA_obj,$errorMessage);
	// 				continue;
	// 			}			
		
				
	// 			//1.
	// 			$uid=$syncIdAccountMap[$syncId]["uid"]; 
	// 			if ($uid==0){
	// 				//异常情况 
	// 				echo "uid:0  exception!!!! \n";
	// 				//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","syncId:$syncId uid:0","error");
	// 				Yii::error("syncId:$syncId uid:0","file");
	// 				$SAA_obj->process_status=4;
	// 				$SAA_obj->error_count=$queueOrderForSync->error_count+1;
	// 				$SAA_obj->error_message="uid:0  stop retry";
	// 				$SAA_obj->update_time=time();
	// 				$SAA_obj->save(false);						
	// 				continue;
	// 			}
	// 			$timeMS2=TimeUtil::getCurrentTimestampMS();
				
	// 			$merchantId=$syncIdAccountMap[$syncId]["merchant_id"];
	// 			$marketplaceId=$syncIdAccountMap[$syncId]["marketplace_id"];
	// 			$orderHeaderArr=json_decode($SAA_obj->order_header_json,true);
	// 			// \Yii::info("before _saveAmazonOriginHeaderByArr amazonId:".$orderHeaderArr["AmazonOrderId"],"file");				
	// 			// try{
	// 			// 	//保存order header信息到指定用户数据库user_? 的原始订单header表
	// 			//     list($ret,$errorMessage)=self::_saveAmazonOriginHeaderByArr($orderHeaderArr,$merchantId,$marketplaceId);
	// 			// }catch(\Exception $e) {
	// 			//    $errorMessage=$e->getMessage();
	// 			//    $ret=false;
	// 			//  }
	// 			//  \Yii::info("after _saveAmazonOriginHeaderByArr amazonId:".$orderHeaderArr["AmazonOrderId"],"file");				 
				 
	// 			// if ($ret===false){
	// 			// 	self::_handleFetchItemsError($SAA_obj,$errorMessage);
	// 			// 	continue;
	// 			// }
				
	// 			//2. 保存order items信息信息到指定用户数据库user_? 的原始订单items表
	// 			// list($ret,$errorMessage)=self::_saveAmazonOriginItemsByArr($SAA_obj->order_id,$itemsArr);
	// 			// \Yii::info("after _saveAmazonOriginItemsByArr amazonId:".$orderHeaderArr["AmazonOrderId"],"file");
	// 			// if ($ret===false){
	// 			// 	self::_handleFetchItemsError($SAA_obj,$errorMessage);
	// 			// 	continue;
	// 			// }				
	// 			//$timeMS3=TimeUtil::getCurrentTimestampMS();
			
	// 			$eagleOrderId=-1;
				
	// 			$timeMS3=TimeUtil::getCurrentTimestampMS();
				
	// 			//4. 把amazon的订单信息header和items通过http api同步到eagle 2系统中的od_order_v2和od_order_item_v2
	// 			$result=self::_saveAmazonOrderToEagle($orderHeaderArr,$itemsArr,$uid,$merchantId,$marketplaceId,$eagleOrderId);
	// 			\Yii::info("after _saveAmazonOrderToEagle amazonId:".$orderHeaderArr["AmazonOrderId"],"file");
				
	// 			if ($result['success']===1){ // result['success']    0----ok,1---fail
	// 				self::_handleFetchItemsError($SAA_obj,"_saveAmazonOrderToEagle() fails.  error:".$result['message']);
	// 				continue;
	// 			}
	// 			$timeMS5=TimeUtil::getCurrentTimestampMS();
				
	// 			//5. after sync is ok,set the order item of the queue
	// 			$SAA_obj->process_status=2;
	// 			$SAA_obj->update_time=time();
	// 			$SAA_obj->error_count=0;
	// 			$SAA_obj->error_message="";
	// 			$SAA_obj->save(false);
				
				
	// 			Yii::info("amazon_fetch_order_items afid_jobid=$backgroundJobId,puid=$uid,order_id=".$SAA_obj->order_id.",t2_1=".($timeMS2-$timeMS1).
	// 			",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS5-$timeMS1),"file");
				
	// 			$handledOrderCount++;
	// 			if ($handledOrderCount>$oneJobMaxHandleNum){
	// 				//处理数超过800，进程主动退出
	// 				break;
	// 			}
				
	// 		}
	// 	}
		
	// 	return $hasGotRecord;
	// }
	
	
	
	
	
	
	/**
	 *  获取saas_amazon_user的amazon_uid和puid对应关系
	 */
	private static function _getAmazonuidPuidMap(){
		$resultMap=array();
		$sourcesql='select * from saas_amazon_user';
    	$rows=\Yii::$app->db->createCommand($sourcesql)->queryAll();
    						
		foreach ($rows as $row){
			$resultMap[$row["amazon_uid"]]=$row["uid"];
		}
		return $resultMap;
	}
	
	
	/**
	 * 获取指定时间内的amazon订单 （非fba和fba），并保存到数据库
	 * @param unknown $saasAmazonSyncInfoArr --
	 * array("id"=>234,"merchant_id"=>"","marketplace_id"=>"2343","amazon_user_id"=>"2343")
	 * @param unknown $saasAmazonSyncObj
	 * @param unknown $amazonAccessInfo
	 * @param unknown $fetch_begin_time
	 * @param unknown $fetch_end_time
	 * @param string $type
	 */
	// public static function getOrderHeaderFromAmazonAndSave($saasAmazonSyncInfoArr,$fetch_begin_time,$fetch_end_time){
	// 	//1. 整理参数
	// 	$amazonUserId=$saasAmazonSyncInfoArr["amazon_user_id"];
	// 	$merchantId=$saasAmazonSyncInfoArr["merchant_id"];
		
	// 	//获取  $access_key_id和 $secret_access_key
	// 	$amazonAccessInfo=SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid"=>$amazonUserId))->asArray()->one();
	// 	//获取puid
	// 	$amazonUserInfo=SaasAmazonUser::find()->where("merchant_id=:merchant_id",array(":merchant_id"=>$merchantId))->one();
	// 	if ($amazonUserInfo<>null) $puid=$amazonUserInfo->uid; else $puid=0; 
		
	// 	$amazonAccessInfo=SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid"=>$amazonUserId))->asArray()->one();
		
	// 	$configParams=array();
	// 	$configParams["marketplace_id"]=$saasAmazonSyncInfoArr["marketplace_id"];
	// 	$configParams["merchant_id"]=$saasAmazonSyncInfoArr["merchant_id"];
	// 	$configParams['access_key_id']= $amazonAccessInfo["access_key_id"];
	// 	$configParams['secret_access_key']= $amazonAccessInfo["secret_access_key"];
			
		
	// 	try{
	// 		// 2.先获取非fba的订单
	// 		$type="MFN";
			
	// 		$start1_time=TimeUtil::getCurrentTimestampMS();
	// 		$logStr="amazon_fetch_order_list  beforeGetHeader_$type bt=".date("Y-m-d H:i:s",$fetch_begin_time).",et=".date("Y-m-d H:i:s",$fetch_end_time);
	// 		echo $logStr." \n";
	// 		\Yii::info($logStr,"file");

			
	// 		list($ret,$retryCount,$message)=self::_getOrderHeaderFromAmazonAndSave($puid,$saasAmazonSyncInfoArr["id"], $configParams, $fetch_begin_time, $fetch_end_time,$type);
			
	// 		$start2_time=TimeUtil::getCurrentTimestampMS();
	// 		//echo "amazon_fetch_order_list  afterGetHeader_MFN rt=".($start2_time-$start1_time)." \n";
	// 		\Yii::info("amazon_fetch_order_list  afterGetHeader_$type,rt=".($start2_time-$start1_time),"file");
						
	// 		if ($ret===false){//非FBA拉取失败的话，就不拉取FBA的订单
	// 			return array(false,$retryCount,$type." ".$message);
	// 		}			
			
	// 		//3. 获取fba的订单
	// 		$type="AFN";

	// 		$start1_time=TimeUtil::getCurrentTimestampMS();
	// 		$logStr="amazon_fetch_order_list  beforeGetHeader_$type bt=".date("Y-m-d H:i:s",$fetch_begin_time).",et=".date("Y-m-d H:i:s",$fetch_end_time);
	// 		echo $logStr." \n";
	// 		\Yii::info($logStr,"file");
			
	// 		list($ret,$retryCount,$message)=self::_getOrderHeaderFromAmazonAndSave($puid,$saasAmazonSyncInfoArr["id"], $configParams, $fetch_begin_time, $fetch_end_time,$type);
				
	// 		$start2_time=TimeUtil::getCurrentTimestampMS();
	// 		\Yii::info("amazon_fetch_order_list  afterGetHeader_$type,rt=".($start2_time-$start1_time),"file");
			
			
	// 		if ($ret===false){
	// 			return array(false,$retryCount,$type." ".$message);
	// 		}
				
	// 	}catch(\Exception $e){
	// 		\Yii::error(print_r($e,true),"file");			
	// 		$ret=false;			
	// 		$message=date("Y-m-d H:i:s")." ".$e->getMessage()." ".$e->getTraceAsString();
	// 		$retryCount=-1;
	// 		return array($ret,$retryCount,$message);
	// 	}
		
		
	// 	return array($ret,$retryCount,$message);
		
	// }
	
	/**
	 * 这里是新订单header的拉取
	 * amazon的订单header拉取---分成2个时间段，新订单拉取和旧订单拉取。 
	 */
// 	private static function _fetchNewOrderList(){
// 		$backgroundJobId=self::getCronJobId();
// 		\Yii::info("amazon_fetch_order_list _fetchNewOrderList afol_jobid=$backgroundJobId","file");
// 		echo "amazon_fetch_order_list _fetchNewOrderList afol_jobid=$backgroundJobId \n";
				
// 		$FirstOldFetchIntervalByHour=2;  //第一次拉取旧订单的时间间隔（小时为单位）		
// 		$NextExecuteInterval=800; //同一个用户，2次同步间的时间间隔（秒为单位）
		
// 		$currentEnv=\Yii::$app->params["currentEnv"]; //当前环境--production或者test		
// 		if ($currentEnv=="test"){
// 		      $FirstOldFetchIntervalByHour=0; 
// 		      $NextExecuteInterval=10;
// 		}		
		
// 		$nowTime=time(); // crontab触发的时间点.目前设置每分钟触发一次
// 		$hasGotRecord=false;		

// 		//status --0表示没开启; 1 表示开启
// 		//process_status ---0 没同步; 1 同步中; 2 完成同步;
// 		$SAA_objs=SaasAmazonAutosync::find()
// 		->where('status=1')
// 		->andWhere('error_count < 30')
// // 		->andWhere('merchant_id = "A3TOWDGP98LAQZ"')
// 		->andWhere('process_status <> 1 and (isnull(next_execute_time) or  next_execute_time<'.$nowTime.')')
// 		// 由于不使用其他排序 这样就按Autosync id排，这样导致一个客户的多个站点都连续的调用
// 		// old new MFN AFN 4次，这样像A3TOWDGP98LAQZ 这样5个站点后面的站点就很可能一直都调用超限
// 		// 换一种排序只能随机解决这个问题
// 		->orderBy('next_execute_time asc') 
// // 		->limit(5)
// 		->all(); // amazon多到一定程度的时候，就需要使用多进程的方式来并发拉取。
		
// 		$connection = \Yii::$app->db;
// 		\Yii::info("amazon_fetch_order_list step1 afol_jobid=$backgroundJobId,sc=".count($SAA_objs),"file");
// 		if(count($SAA_objs)){
// 			foreach($SAA_objs as $SAA_obj) {
// 				echo "=========begin SAA_obj merchantId:".$SAA_obj->merchant_id.",marketplaceId:".$SAA_obj->marketplace_id." \n";
		
// 				//1. 先判断是否可以正常抢到该记录
// 				$command = $connection->createCommand("update saas_amazon_autosync set process_status=1 ".
// 						" where id =". $SAA_obj->id." and process_status<>1 ") ;
// 				$affectRows = $command->execute();
// 				if ($affectRows <= 0)	continue; //抢不到
				
// 				\Yii::info("amazon_fetch_order_list amazon_user_id:".$SAA_obj->amazon_user_id." merchantId:".$SAA_obj->merchant_id.",marketplaceId:".$SAA_obj->marketplace_id,"file");
				
// 				//2. 抢到记录，设置同步需要的参数
// 				$hasGotRecord=true;		
// 				//由于SAA_obj->id对应的记录被修改，这里重新加载一次
// 				$SAA_obj=SaasAmazonAutosync::findOne($SAA_obj->id);
				
// 				$nowTime=time();
// 				//需要获取的订单list的开始时间，注意：如果这个超过当前时间的话，amazon会请求失败,为了保险起见这里推前了几分钟！！！！！
// 				$end_time = $nowTime-300;
// 				//是否第一次拉取。
// 				if ($SAA_obj->last_finish_time==null or $SAA_obj->last_finish_time==0){
// 					echo "fetch for the first time \n";
// 					\Yii::info("amazon_fetch_order_list  merchantId:".$SAA_obj->merchant_id.",marketplaceId:".$SAA_obj->marketplace_id." fetch order list for the first time ","file");
// 					//若是第一次拉取。     一共拉取的时间为30天。假设今天是30日， 这里分成2个时间段来拉取，
// 					//21日~30日和1日~21日。  其中21日~30日 这个时间段是需要该进程马上拉取的，  另一个时间段是其他进程后面再慢慢拉取的
// 					$SAA_obj->dividing_time=strtotime("-10 day");
// 					$SAA_obj->old_last_finish_time=strtotime("-10 day");
// 					$SAA_obj->old_next_execute_time=$nowTime+$FirstOldFetchIntervalByHour*3600; //1个小时后才可以开始拉取旧的订单
// 					$SAA_obj->old_start_time=strtotime("-30 day");
// 					$SAA_obj->save(false);
						
// 					$last_time =$SAA_obj->dividing_time;
						
// 				}else{
// 					echo "not fetch for the first time \n";
// 					//不是第一次拉取
// 					$last_time = $SAA_obj->last_finish_time<strtotime("-10 day")?strtotime("-10 day"):$SAA_obj->last_finish_time;
// 				}
				
// 				if ($SAA_obj->has_massive_order==1){
// 					//有大量订单的用户，对于这类用户，访问的间隔（begin_time和end_time）是有额外的控制。
// 					if ($end_time-$last_time>$SAA_obj->max_from_to_interval){
// 						$end_time=$last_time+$SAA_obj->max_from_to_interval;
// 					}
// 				}
		
// 				echo "last_time:".date("Y-m-d H:i:s",$last_time)." \n";
// 				$SAA_obj->last_finish_time = $last_time;
// 				$SAA_obj->fetch_begin_time = $last_time;
// 				$SAA_obj->fetch_end_time = $end_time;
// 				//	$SAA_obj->process_status = 1;
// 				$SAA_obj->next_execute_time=$nowTime+$NextExecuteInterval;
// 				if (!$SAA_obj->save(false)){
// 					echo "cronAutoFetchOrderList SAA_obj->save fail \n";
// 					\Yii::error("cronAutoFetchOrderList SAA_obj->save fail","file");
// 					continue;
// 				}
				
// 				$fetch_begin_time=$SAA_obj->fetch_begin_time;
// 				$fetch_end_time=$SAA_obj->fetch_end_time;
		
// 				$saasAmazonSyncInfoArr=array(
// 				      "id"=>$SAA_obj->id,
// 				      "merchant_id"=>$SAA_obj->merchant_id,
// 				      "marketplace_id"=>$SAA_obj->marketplace_id,
// 				      "amazon_user_id"=>$SAA_obj->amazon_user_id
// 				);
					
// 				list($ret,$retryCount,$message)=self::getOrderHeaderFromAmazonAndSave($saasAmazonSyncInfoArr, $fetch_begin_time, $fetch_end_time);
				
// 				if($ret===true) { // 成功同步
// 					$SAA_obj->last_finish_time = $SAA_obj->fetch_end_time;
// 					$SAA_obj->process_status = 2;
// 					$SAA_obj->error_count=0;
// 					$SAA_obj->error_message ="";
// 					$SAA_obj->save(false);
// 				}else{
// 					if ($retryCount==-3){
// 						//该用户订单太多，需要设置为一次最多获取一天的订单！！！！
// 						//$SAA_obj->error_message = "该用户订单太多，需要设置为一次最多获取一天的订单！！！！";
// 						$SAA_obj->error_message =$message;
// 						$SAA_obj->process_status = 3;
// 						$SAA_obj->error_count += 1;
// 						$SAA_obj->has_massive_order=1;
// 						$SAA_obj->max_from_to_interval=3600*24;
// 						$SAA_obj->save(false);
// 						continue;
// 					}

// 					$SAA_obj->error_message = $message;
// 					$SAA_obj->process_status = 3;						
// 					if (strpos($message,"RequestThrottled")===false){
// 					    $SAA_obj->error_count += 1;
// 					}else{
// 					    //访问超限---需要控制下次访问的时间
// 					    $SAA_obj->next_execute_time=time()+$NextExecuteInterval+300;
// 					}
		
					
					
// 					$SAA_obj->save(false);
// 				}
// 			}
// 		}
		
// 		return $hasGotRecord;			
// 	}
	


	/**
	 * 这里是旧订单header的拉取
	 * amazon的订单header拉取---分成2个时间段，新订单拉取和旧订单拉取。
	 */
	// private static function _fetchOldOrderList(){
	// 	$backgroundJobId=self::getCronJobId();
	// 	Yii::info("amazon_fetch_order_list _fetchOldOrderList afol_jobid=$backgroundJobId","file");
	
	// 	$OldMaxFromToIntervalByDay=1;  // 每次拉取旧的订单的最大时间跨度(天为单位)。如 1的话，表示最多只能拉取1天的订单。
	// 	$nowTime=time(); // crontab触发的时间点.目前设置每分钟触发一次
	// 	$NextExecuteInterval=600; //同一个用户，2次同步间的时间间隔(秒为单位)
	// 	$currentEnv=\Yii::$app->params["currentEnv"]; //当前环境--production或者test
	// 	if ($currentEnv=="test"){
	// 		$NextExecuteInterval=10;
	// 	}
	
	
	// 	$OldMaxFromToInterval=$OldMaxFromToIntervalByDay*24*3600; // 每次拉取旧的订单的最大时间跨度(秒为单位)，  end_time-begin_time的最大值
	// 	$hasGotRecord=false;
	// 	//当old_last_finish_time = old_start_time 表示 旧订单拉取完成
	// 	$SAA_objs=SaasAmazonAutosync::find()
	// 	->where('status=1')
	// 	->andWhere('old_error_count < 30')
	// 	->andWhere('!isnull(old_last_finish_time) and old_last_finish_time > old_start_time') 
	// 	->andWhere('process_status <> 1 and (isnull(old_next_execute_time) or  old_next_execute_time<'.$nowTime.')')
	// 	->all(); // 这里限制了总数，但amazon多到一定程度的时候，就需要使用多进程的方式来并发拉取。
	
	
	// 	$connection = Yii::$app->db;
	// 	//Yii::info("There are ".count($SAA_objs)." stores waiting for sync" );
	// 	echo "There are ".count($SAA_objs)." stores waiting for fetching old orders \n";
	// 	if(count($SAA_objs)){
	// 		foreach($SAA_objs as $SAA_obj) {
	// 			echo "=========begin SAA_obj merchantId:".$SAA_obj->merchant_id.",marketplaceId:".$SAA_obj->marketplace_id." \n";
	
	// 			//先判断是否可以正常抢到该记录
	// 			$command = $connection->createCommand("update saas_amazon_autosync set process_status=1 ".
	// 					" where id =". $SAA_obj->id." and process_status<>1 ") ;
	// 			$affectRows = $command->execute();
	// 			if ($affectRows <= 0)	continue; //抢不到
	// 			$hasGotRecord=true;
	
	// 			Yii::info("amazon_fetch_order_list _fetchOldOrderList gotit afol_jobid=$backgroundJobId","file");
	
	// 			//由于SAA_obj->id对应的记录被修改，这里重新加载一次
	// 			$SAA_obj=SaasAmazonAutosync::findOne($SAA_obj->id);
	
	// 			$nowTime=time();
	// 			$end_time = $SAA_obj->old_last_finish_time;
	// 			$begin_time=$SAA_obj->old_start_time;
	
	// 			$maxFromToInterval=$OldMaxFromToInterval;
	// 			if ($SAA_obj->has_massive_order==1){
	// 				//有大量订单的用户，对于这类用户，访问的间隔（begin_time和end_time）是有额外的控制。
	// 				$maxFromToInterval=$SAA_obj->max_from_to_interval;
	// 			}
	// 			//访问的间隔（begin_time和end_time）的控制
	// 			if ($end_time-$begin_time>$maxFromToInterval){
	// 				$begin_time=$end_time-$maxFromToInterval;
	// 			}
	
	// 			$SAA_obj->old_begin_time = $begin_time;
	// 			$SAA_obj->old_end_time = $end_time;
	// 			$SAA_obj->old_next_execute_time=$nowTime+$NextExecuteInterval;
	// 			if (!$SAA_obj->save(false)){
	// 				echo "cronAutoFetchOrderList SAA_obj->save fail \n";
	// 				Yii::error("cronAutoFetchOrderList SAA_obj->save fail","file");
	// 				continue;
	// 			}
	
			
	// 			$fetch_begin_time=$SAA_obj->old_begin_time;
	// 			$fetch_end_time=$SAA_obj->old_end_time;
	
	// 			$saasAmazonSyncInfoArr=array(
	// 					"id"=>$SAA_obj->id,
	// 					"merchant_id"=>$SAA_obj->merchant_id,
	// 					"marketplace_id"=>$SAA_obj->marketplace_id,
	// 					"amazon_user_id"=>$SAA_obj->amazon_user_id
	// 			);
					
	// 			list($ret,$retryCount,$message)=self::getOrderHeaderFromAmazonAndSave($saasAmazonSyncInfoArr, $fetch_begin_time, $fetch_end_time);
	
	// 			if($ret===true) { // 成功同步
	// 				$SAA_obj->old_last_finish_time = $SAA_obj->old_begin_time;
	// 				$SAA_obj->process_status = 2;
	// 				$SAA_obj->old_error_count=0;
	// 				$SAA_obj->old_error_message ="";
	// 				$SAA_obj->save(false);
	// 			}else{
	// 				if ($retryCount==-3){
	// 					//该用户订单太多，需要设置为一次最多获取一天的订单！！！！
	// 					$SAA_obj->old_error_message = "该用户订单太多，需要设置为一次最多获取一天的订单！！！！";
	// 					$SAA_obj->process_status = 3;
	// 					$SAA_obj->old_error_count += 1;
	// 					//$SAA_obj->has_massive_order=1;
	// 					//$SAA_obj->max_from_to_interval=3600*24;
	// 					$SAA_obj->old_next_execute_time=$nowTime+10*60;
	// 					$SAA_obj->save(false);
	// 					continue;
	// 				}
	
	// 				$SAA_obj->old_error_message = $message;
	// 				$SAA_obj->process_status = 3;
	// 				$SAA_obj->old_error_count += 1;
	// 				$SAA_obj->save(false);
	// 			}
	// 		}
	// 	}
	// 	return $hasGotRecord;
	
	// }
	
	
	
	/**
	 * amazon的订单header拉取，后台常驻进程触发，每隔一段时间会自动退出。
	 * 订单的拉取分2个job完成。分别是header的抓取和items的抓取。这里只是header的抓取
	 * 信息只有记录到临时表，  只有items的抓取后台程序才会把订单信息传递到eagle中！！！！
	 */
	// public static function cronAutoFetchOrderList(){
	// 	\Yii::info("entering cronAutoFetchOrderList","file");
	// 	$ret=true;
	// 	//mazon的订单header拉取---分成2个时间段，新订单拉取和旧订单拉取。
	// 	//1. 新订单的拉取
	// 	$ret=self::_fetchNewOrderList();
	// 	//2. 旧订单的拉取
	// 	$oldRet=self::_fetchOldOrderList();

	// 	return $ret;
		
	// }
	
	
}


?>