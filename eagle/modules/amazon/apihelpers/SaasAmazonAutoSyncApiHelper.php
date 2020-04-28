<?php

namespace eagle\modules\amazon\apihelpers;
use \Yii;
//use eagle\models\SaasAmazonAutosync;
use eagle\models\SaasAmazonAutosyncV2;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\models\AmazonTempOrderidQueue;
use eagle\models\AmzOrder;
use eagle\models\AmzOrderDetail;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\AmazonOrderSubmitQueue;
use eagle\models\SaasAmazonUser;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\SellerCallPlatformApiCounter;
use eagle\modules\util\helpers\TimeUtil;
use common\helpers\Helper_Array;
use eagle\modules\order\apihelpers\OrderApiHelper;
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

class SaasAmazonAutoSyncApiHelper{
	
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
		'A1805IZSGTT6HS'=>"NL",
    );
	
	public static $COUNTRYCODE_NAME_MAP=array("US"=>"美国","CA"=>"加拿大","DE"=>"德国","ES"=>"西班牙","FR"=>"法国","IN"=>"印度","IT"=>"意大利",
	        "UK"=>"英国","JP"=>"日本","CN"=>"中国","MX"=>"墨西哥","AU"=>"澳大利亚","BR"=>"巴西","TR"=>"土耳其","AE"=>"阿联酋","SG"=>"新加坡",
			"NL"=>"荷兰");
	
	public static $BuyerShippingServices = array(
		'Expedited'=>'Expedited',
		'FreeEconomy'=>'FreeEconomy',
		'NextDay'=>'NextDay',
		'SameDay'=>'SameDay',
		'SecondDay'=>'SecondDay',
		'Scheduled'=>'Scheduled',
		'Standard'=>'Standard',
	);
	
	
	const EAGLE_UNSHIPPED=0;
	const EAGLE_PARTIALSHIPPED=1;
	const EAGLE_SHIPPED=2;
	
	
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
	
		
	public static function _getMerchantIdAccountInfoMap(){
		$merchantIdAccountMap=array();
		$tempAmazonUids=array();
		$rows=SaasAmazonUserMarketplace::find()->asArray()->all();
		foreach($rows as $row){
			if (in_array($row["amazon_uid"], $tempAmazonUids)) continue;
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
			Yii::error("_getOrderItemsFromAmazonById AmazonProxyConnectApiHelper::call_amazon_api error msg:".$e->getMessage(),"file");
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
	private static function _caculateTotalAmount($orderItems) {
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
	//把amazon的订单信息header和items 同步到eagle系统中的od_order和od_order_item
	private static function _saveAmazonOrderToEagle($orderHeaderInfo,$orderItems,$uid,$merchantId,$marketplaceId){
		
		//！！！！！！注意：如果订单状态为取消 header和item信息量少				
		/*
		 * header信息
		 * {"AmazonOrderId":"171-1288495-6303559","PurchaseDate":"2015-04-12T20:40:27Z","LastUpdateDate":"2015-04-14T06:22:58Z","Status":"Canceled",
		 * "SalesChannel":"Amazon.fr","ShipServiceLevel":"Std FR Dom","Currency":"EUR","Amount":"38.99","NumberOfItemsShipped":"0","NumberOfItemsUnshipped":"0",
		 * "ShipmentServiceLevelCategory":"Standard","EarliestShipDate":"2015-04-12T22:00:00Z","LatestShipDate":"2015-04-14T21:59:59Z","FulfillmentChannel":"MFN",
		 * "MarketplaceId":"A13V1IB3VIYZZH","ShippedByAmazonTFM":"false","OrderType":"StandardOrder","type":"MFN"}
         *
		 * 如果是FBA的订单，信息可能更少。 如：没有Amount
		 *
		 * 
		 * item信息		  
		 * [ASIN] => B00KXXMMFA
		 * [SellerSKU] => W13000000
		 * [OrderItemId] => 3098360
		 * [Title] => Nillkin H+ 
		 * [QuantityOrdered] => 1
		 * [QuantityShipped] => 0
		 */

		
		
		//根据item信息，计算order header subtotal,shipping cost,total discount 这3个信息在amazon的原始header中没有包括		 
		list($subtotal,$shippingCost,$totalDiscount)=self::_caculateTotalAmount($orderItems);
		
		//整理导入eagle平台的订单信息
		$reqParams=array();
		
		$amazonOrderId=$orderHeaderInfo['AmazonOrderId'];

		//eagle的订单状态
		$order_status=self::$AMAZON_EAGLE_ORDER_STATUS_MAP[$orderHeaderInfo["Status"]];
		
		$is_manual_order=0;
		if (strtolower($orderHeaderInfo["Status"])=="canceled"){
				//挂起，需要及时处理的订单，可能不需要发货
				$is_manual_order = 1;
		}
		//订单类型 MFN或AFN(FBA)----------非FBA的订单，默认不传入order_type 参数或该参数值传入为空
		if (!isset($orderHeaderInfo["type"]) or $orderHeaderInfo["type"]=="") $order_type="MFN";
		else $order_type=$orderHeaderInfo["type"];
		
		$consignee_country="";
		if (isset($orderHeaderInfo['CountryCode'])){
			$sysCountry=SysCountry::findOne(['country_code'=>$orderHeaderInfo['CountryCode']]);
			if ($sysCountry<>null) $consignee_country=$sysCountry->country_en;			
		}

		//1.  订单header信息
		$order_arr=array(//主订单数组
				'order_status'=>$order_status,
				'order_source_status'=>$orderHeaderInfo["Status"],				
				'is_manual_order'=>$is_manual_order,				
				'order_source'=>'amazon',				
				'order_type'=>$order_type,  //订单类型如amazon FBA订单
				'order_source_site_id'=>self::$AMAZON_MARKETPLACE_REGION_CONFIG[$marketplaceId],
				
				'order_source_order_id'=>$orderHeaderInfo['AmazonOrderId'],  //订单来源平台订单号
				'selleruserid'=>$merchantId,
				'source_buyer_user_id'=>isset($orderHeaderInfo['BuyerName'])?$orderHeaderInfo['BuyerName']:'',	//来源买家用户名			
				'order_source_create_time'=>strtotime($orderHeaderInfo["PurchaseDate"]), //时间戳
				'subtotal'=>$subtotal,
				'shipping_cost'=>$shippingCost,
				'grand_total'=>isset($orderHeaderInfo['Amount'])?$orderHeaderInfo['Amount']:0,
				'discount_amount'=>$totalDiscount,
				'currency'=>isset($orderHeaderInfo['Currency'])?$orderHeaderInfo['Currency']:'USD', //TODO currency需要提供，不能为空，不然导入订单到eagle接口会有问题
				'consignee'=>isset($orderHeaderInfo['Name'])?$orderHeaderInfo['Name']:'',
				'consignee_postal_code'=>isset($orderHeaderInfo['PostalCode'])?$orderHeaderInfo['PostalCode']:'',
				'consignee_city'=>isset($orderHeaderInfo['City'])?$orderHeaderInfo['City']:'', 
				'consignee_phone'=>isset($orderHeaderInfo['Phone'])?$orderHeaderInfo['Phone']:'',
				//'consignee_mobile'=>"",
				'consignee_email'=>isset($orderHeaderInfo['BuyerEmail'])?$orderHeaderInfo['BuyerEmail']:'',
				'consignee_country'=>$consignee_country,
				'consignee_country_code'=>isset($orderHeaderInfo['CountryCode'])?$orderHeaderInfo['CountryCode']:'',
				'consignee_province'=>isset($orderHeaderInfo['State'])?$orderHeaderInfo['State']:'',
				'consignee_address_line1'=>isset($orderHeaderInfo['AddressLine1'])?$orderHeaderInfo['AddressLine1']:'',
				'consignee_address_line2' =>isset($orderHeaderInfo['AddressLine2'])?$orderHeaderInfo['AddressLine2']:'',
				'consignee_address_line3' =>isset($orderHeaderInfo['AddressLine3'])?$orderHeaderInfo['AddressLine3']:'',
				'paid_time'=>strtotime($orderHeaderInfo["PurchaseDate"]), //时间戳 , PurchaseDate是amazon 订单创建时间
				'delivery_time'=>isset($orderHeaderInfo['LatestDeliveryDate'])?$orderHeaderInfo['LatestDeliveryDate']:0, //时间戳
				//'user_message'=>json_encode($OrderById['orderMsgList']),
				//'orderShipped'=>$orderShipped,
		);
		
		//2. 订单的items信息
		$userMessage = '';
		$orderitem_arr=array();//订单商品数组
		foreach ($orderItems as $one){		
			$orderItemsArr = array(
					'order_source_order_id'=>$orderHeaderInfo['AmazonOrderId'],  //订单来源平台订单号
					'order_source_order_item_id'=>$one['OrderItemId'],
					//'order_source_transactionid'=>$one['childid'],//订单来源交易号或子订单号
					'order_source_itemid'=>$one['ASIN'],//产品ID listing的唯一标示
					
					'asin'=>$one['ASIN'],  //lolo add -- 速卖通貌似没有的
					'sent_quantity'=>$one['QuantityShipped'],  //lolo add -- 速卖通貌似没有的
					'promotion_discount'=>isset($one['PromotionDiscount'])?$one['PromotionDiscount']:0,   //lolo add -- 速卖通貌似没有的
					'shipping_price'=>isset($one['ShippingPrice'])?$one['ShippingPrice']:0,  //lolo add -- 速卖通貌似没有的
					'shipping_discount'=>isset($one['ShippingDiscount'])?$one['ShippingDiscount']:0,  //lolo add -- 速卖通貌似没有的
					
					'sku'=>$one['SellerSKU'],//商品编码
					'price'=>isset($one['ItemPrice'])?$one['ItemPrice']:0,//如果订单是取消状态，该字段amazon不会返回
					'ordered_quantity'=>isset($one['QuantityOrdered'])?$one['QuantityOrdered']:0,//下单时候的数量
					'quantity'=>isset($one['QuantityOrdered'])?$one['QuantityOrdered']:0,  //需发货的商品数量,原则上和下单时候的数量相等，订单可能会修改发货数量，发货的时候按照quantity发货
					'product_name'=>$one['Title'],//下单时标题
					//'photo_primary'=>$one['productimgurl'],//商品主图冗余
				//	'desc'=>$one['memo'],//订单商品备注,
				//	'product_attributes'=>$attr_str,//商品属性
					//'product_unit'=>$one['productunit'],//单位
					//'lot_num'=>$one['lotnum'],//单位数量
				//	'product_url'=>$one['productsnapurl'],//商品url
			);
			//赋缺省值			
			$orderitem_arr[]=array_merge(OrderHelper::$order_item_demo,$orderItemsArr);
			//$userMessage = $one['memo'];
		}

		
		//订单商品
		$order_arr['items']=$orderitem_arr;
		//订单备注
		$order_arr['user_message']= "";
				//赋缺省值
		$myorder_arr[$uid]=array_merge(OrderHelper::$order_demo,$order_arr);
		
		//3.  订单header和items信息导入到eagle系统
	//	\Yii::info("before OrderHelper::importPlatformOrder info:".json_encode($myorder_arr,true),"file");
		try{
	    	$result =  OrderHelper::importPlatformOrder($myorder_arr);
		}catch(\Exception $e){
			echo "OrderHelper::importPlatformOrder fails. Exception  \n";
			\Yii::error("OrderHelper::importPlatformOrder fails.  amazonId=$amazonOrderId  Exception error:".$e->getMessage()." trace:".$e->getTraceAsString(),"file");
		
			return ['success'=>1,'message'=>$e->getMessage()];			
		}
	//	echo "after OrderHelper::importPlatformOrder result:".print_r($result,true);
		// ！！！注意  result['success']的返回值。    0----表示ok,1---表示fail
		if ($result['success']===1){ 
		//	SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","OrderHelper::importPlatformOrder fails. error:".$result['message'],"error");
			\Yii::error("OrderHelper::importPlatformOrder fails. amazonId=$amazonOrderId error:".$result['message'],"file");
		}
		
		

		return $result;
	}
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
		\Yii::error("orderid:".$queueOrderForSync->order_id."  ".$message,"file");
	}

	//后台crontab触发， amazon的订单items拉取
	// 这里才会把订单信息传递到eagle中！！！！
// 	public static function cronAutoFetchOrderItems(){
// 	    Yii::info("entering cronAutoFetchOrderDetail","file");
		
// 		$SAA_objs=AmazonTempOrderidQueue::find()
// 		->where('process_status=0')
// 		->orWhere('process_status =3')->limit(150)->all();
		
// 		echo "count:".count($SAA_objs);
// //		SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","There are ".count($SAA_objs)." orders waiting for fetching detail by id" ,"info");
//         Yii::info("There are ".count($SAA_objs)." orders waiting for fetching detail by id","file");
// 		$syncIdAccountMap=self::_getSyncIdAccountInfoMap();
// 		echo print_r($syncIdAccountMap,true);
// 		if(count($SAA_objs)){
// 			foreach($SAA_objs as $SAA_obj) {
// 				$syncId=$SAA_obj->saas_amazon_autosync_id;
// 				if (!isset($syncIdAccountMap[$syncId])){
// 					//TODO 
// 					Yii::error("saas_amazon_autosync_id:".$syncId." not exist in saas_amazon_user","file");
// 					$SAA_obj->process_status=4;//4 异常情况，不需要重试，等待it人工分析
// 					$SAA_obj->update_time=time();
// 					$SAA_obj->error_count=$SAA_obj->error_count+1;
// 					$SAA_obj->error_message="saas_amazon_autosync_id:".$syncId." not exist in saas_amazon_user";						
// 					$SAA_obj->save(false);
// 					continue;						
// 				}
				
// 				$SAA_obj->process_status=1;
// 				$SAA_obj->update_time=time();
// 				$SAA_obj->save(false);
				
// 				\Yii::info("before _getOrderItemsFromAmazonById amazonId:".$SAA_obj->order_id,"file");
// 				list($ret,$itemsArr,$retryCount,$errorMessage)=self::_getOrderItemsFromAmazonById($SAA_obj->order_id, $syncIdAccountMap[$syncId]);
// 				\Yii::info("after _getOrderItemsFromAmazonById amazonId:".$SAA_obj->order_id,"file");
				
// 				if ($ret==false) {
// 					//TODO
// 					//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","readyGetDetailQueue->save error:".print_r($readyGetDetailQueue->errors,true),"error");
// 					echo "_getOrderItemsFromAmazonById false \n";
// 					self::_handleFetchItemsError($SAA_obj,$errorMessage);
// 					continue;
// 				}
				
		
				
// 				//1.保存order header信息到指定用户数据库user_? 的原始订单header表
// 				$uid=$syncIdAccountMap[$syncId]["uid"]; 
// 				if ($uid==0){
// 					//异常情况 
// 					echo "uid:0  exception!!!! \n";
// 					//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","syncId:$syncId uid:0","error");
// 					Yii::error("syncId:$syncId uid:0","file");
// 					$SAA_obj->process_status=4;
// 					$SAA_obj->error_count=$queueOrderForSync->error_count+1;
// 					$SAA_obj->error_message="uid:0  stop retry";
// 					$SAA_obj->update_time=time();
// 					$SAA_obj->save(false);						
// 					continue;
// 				}
				
// 				$merchantId=$syncIdAccountMap[$syncId]["merchant_id"];
// 				$marketplaceId=$syncIdAccountMap[$syncId]["marketplace_id"];			
// 				$orderHeaderArr=json_decode($SAA_obj->order_header_json,true);
// 				//echo "before _saveAmazonOriginHeaderByArr orderHeaderArr:".print_r($orderHeaderArr,true);
// 				\Yii::info("before _saveAmazonOriginHeaderByArr amazonId:".$orderHeaderArr["AmazonOrderId"],"file");				
// 				try{
// 					//保存order header信息到指定用户数据库user_? 的原始订单header表
// 				    list($ret,$errorMessage)=self::_saveAmazonOriginHeaderByArr($orderHeaderArr,$merchantId,$marketplaceId);
// 				}catch(\Exception $e) {
// 				   $errorMessage=$e->getMessage();
// 				   $ret=false;
// 				 }
// 				 \Yii::info("after _saveAmazonOriginHeaderByArr amazonId:".$orderHeaderArr["AmazonOrderId"],"file");				 
				 
// 				if ($ret===false){
// 					self::_handleFetchItemsError($SAA_obj,$errorMessage);
// 					continue;
// 				}
				
// 				//2. 保存order items信息信息到指定用户数据库user_? 的原始订单items表
// 				list($ret,$errorMessage)=self::_saveAmazonOriginItemsByArr($SAA_obj->order_id,$itemsArr);
// 				\Yii::info("after _saveAmazonOriginItemsByArr amazonId:".$orderHeaderArr["AmazonOrderId"],"file");
// 				if ($ret===false){
// 					self::_handleFetchItemsError($SAA_obj,$errorMessage);
// 					continue;
// 				}				
				
				
// 				//3. 把amazon的订单信息header和items 同步到eagle系统中的od_order和od_order_item
// 			//	echo "before savemazonordertoeagle orderHeaderArr:".print_r($orderHeaderArr,true); 
// 				$result=self::_saveAmazonOrderToEagle($orderHeaderArr,$itemsArr,$uid,$merchantId,$marketplaceId);
// 				\Yii::info("after _saveAmazonOrderToEagle amazonId:".$orderHeaderArr["AmazonOrderId"],"file");
				
// 				if ($result['success']===1){ // result['success']    0----ok,1---fail
// 					self::_handleFetchItemsError($SAA_obj,"_saveAmazonOrderToEagle() fails.  error:".$result['message']);
// 					continue;
// 				}
				
// 				//4. after sync is ok,set the order item of the queue
// 				$SAA_obj->process_status=2;
// 				$SAA_obj->update_time=time();
// 				$SAA_obj->error_count=0;
// 				$SAA_obj->error_message="";
// 				$SAA_obj->save();				
// 			}
// 		}
// 	}
	
	//后台crontab触发， amazon的订单拉取
	//信息只有记录到临时表，  获取items的后台程序才会把订单信息传递到eagle中！！！！
	// public static function cronAutoFetchOrderList(){
	// 	//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","entering actionCronAmazonOrderListSync" ,"info");
	// 	Yii::info("entering cronAutoFetchOrderList","file");
		
	// 	$cronTriggerTime=time(); // crontab触发的时间点.目前设置每分钟触发一次
	// 	$nextTriggerTime=$cronTriggerTime+5*60; //最多5分钟触发一次
		
		
	// 	$SAA_objs=SaasAmazonAutosync::find()
	// 	->where('status=1')
	// 	->andWhere('error_count < 30')
	// 	->andWhere('process_status <> 1')->limit(2000)->all(); // 这里限制了总数，但amazon多到一定程度的时候，就需要使用多进程的方式来并发拉取。
		
	// 	//用户puid和该用户amazon账号信息的map
	// 	$amazonAccessInfoMap=array();		
	// 	Yii::info("There are ".count($SAA_objs)." stores waiting for sync" ,"file");
	// 	if(count($SAA_objs)){
	// 		foreach($SAA_objs as $SAA_obj) {
	// 			//echo "=========begin SAA_obj:".print_r($SAA_obj,true)." \n";
	// 			echo "=========begin SAA_obj merchantId:".$SAA_obj->merchant_id.",marketplaceId:".$SAA_obj->marketplace_id." \n";
				
	// 			//先判断是否满足最多5分钟触发一次的限制。
	// 			//用户第一次绑定的话，SAA_obj->next_execute_time=null，不受时间限制
	// 			if ($SAA_obj->next_execute_time<>null and $SAA_obj->next_execute_time>0 and $SAA_obj->next_execute_time>$cronTriggerTime){					
	// 			    //还没有到触发时间间隔
	// 			    echo " SAA_obj->next_execute_time continue \n";
	// 				continue;
	// 			}				
				
	// 			$end_time = time()-300; //需要获取的订单list的开始时间，注意：如果这个超过当前时间的话，amazon会请求失败,为了保险起见这里推前了几分钟！！！！！
	// 			//是否首次同步,是则默认同步20天的订单
	// 			if ($SAA_obj->last_finish_time > 0){//否
	// 				echo "not first fetch \n";
	// 				$last_time = $SAA_obj->last_finish_time<strtotime("-20 day")?strtotime("-20 day"):$SAA_obj->last_finish_time;
	// 			}else{//是
	// 				echo "first fetch \n";
	// 				$last_time =strtotime("-20 day");
	// 			}
				
	// 			if ($SAA_obj->has_massive_order==1){
	// 				//有大量订单的用户，对于这类用户，访问的间隔（begin_time和end_time）是有额外的控制。
	// 				if ($end_time-$last_time>$SAA_obj->max_from_to_interval){
	// 					$end_time=$last_time+$SAA_obj->max_from_to_interval;
	// 				}					
	// 			}				
				
				
	// //			$last_time =strtotime("-60 day");  //TODO lolotest
				
	// 			$SAA_obj->last_finish_time = $last_time;
	// 			$SAA_obj->fetch_begin_time = $last_time;
	// 			$SAA_obj->fetch_end_time = $end_time;
	// 			$SAA_obj->process_status = 1;
	// 			$SAA_obj->next_execute_time=$nextTriggerTime;				
	// 			if (!$SAA_obj->save(false)){
	// 				Yii::error("cronAutoFetchOrderList SAA_obj->save fail","file");
	// 				continue;
	// 			}
				
	// 			if (!isset($amazonAccessInfoMap[$SAA_obj->amazon_user_id])) {
	// 				$row=SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid"=>$SAA_obj->amazon_user_id))->asArray()->one();					
	// 				//$row=SaasAmazonUserMarketplace::model()->find("amazon_uid=:amazon_uid",array(":amazon_uid"=>$SAA_obj->amazon_user_id));
	// 				//echo "row:".print_r($row->attributes,true);
	// 				echo "amazonAccessInfoMap amazon_user_id:".$SAA_obj->amazon_user_id."\n";
	// 				if ($row<>null){
	// 					echo "amazonAccessInfoMap 2\n";
	// 					$amazonAccessInfoMap[$SAA_obj->amazon_user_id]=$row;
	// 				}
	// 			}
	// 			$amazonAccessInfo=$amazonAccessInfoMap[$SAA_obj->amazon_user_id];
				
	// 			//2.通过访问proxy获取amazon的订单header信息，并保存到相应的数据表。
	// 			//2.1 先进行非FBA订单的拉取
	// 			$type="MFN";
	// 			list($ret,$retryCount,$message)=self::_getOrderHeaderFromAmazonAndSave($SAA_obj,$amazonAccessInfo,$type);
	// 			if ($ret===false){					
	// 				$SAA_obj->error_message = $type." ".$message;
	// 				$SAA_obj->process_status = 3;
	// 				$SAA_obj->error_count += 1;					
	// 				$SAA_obj->save();
	// 				continue;	//非FBA拉取失败的话，就不拉取FBA的订单			
	// 			}
				
	// 			//2.2 进行FBA订单的拉取
	// 			$type="AFN";
	// 			//如果用户没有开通FBA，这里是否也会返回true？？？
	// 			list($ret,$retryCount,$message)=self::_getOrderHeaderFromAmazonAndSave($SAA_obj,$amazonAccessInfo,$type);
	// 			if($ret===true) { // 成功同步
	// 				$SAA_obj->last_finish_time = $SAA_obj->fetch_end_time;
	// 				$SAA_obj->process_status = 2;
	// 				$SAA_obj->error_count=0;
	// 				$SAA_obj->error_message ="";					
	// 				$SAA_obj->save();
	// 			}else{
	// 				if ($retryCount==-3){
	// 					//该用户订单太多，需要设置为一次最多获取一天的订单！！！！
	// 					$SAA_obj->error_message = "该用户订单太多，需要设置为一次最多获取一天的订单！！！！";
	// 					$SAA_obj->process_status = 3;
	// 					$SAA_obj->error_count += 1;
	// 					$SAA_obj->has_massive_order=1;
	// 					$SAA_obj->max_from_to_interval=3600*24;						
	// 					$SAA_obj->save();
	// 					continue;						
	// 				}
					
	// 				$SAA_obj->error_message = $message;
	// 				$SAA_obj->process_status = 3;
	// 				$SAA_obj->error_count += 1;					
	// 				$SAA_obj->save();
	// 			}
				
	// 		}
	// 	}		
	// }
	
	
	/**
	 * 提交到proxy获取submitId
	 * @param unknown $sumbitOrderQueueItem
	 * @param unknown $amazonAccessInfo
	 * @return multitype:boolean number NULL string |multitype:boolean number string |multitype:boolean string unknown
	 */
	private static function _submitEagleOrderToAmazon($sumbitOrderQueueItem,$amazonAccessInfo){
		
		$AMAZON_REGION_MARKETPLACE_CONFIG=array_flip(self::$AMAZON_MARKETPLACE_REGION_CONFIG);
		$submitId=0;
		$marketplace_id=$AMAZON_REGION_MARKETPLACE_CONFIG[$sumbitOrderQueueItem->marketplace_short];
		$config=array(
				'merchant_id' =>$sumbitOrderQueueItem->merchant_id,
				'marketplace_id' => $marketplace_id,
		        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 				'access_key_id' => $amazonAccessInfo["access_key_id"],
// 				'secret_access_key' => $amazonAccessInfo["secret_access_key"],
		        'mws_auth_token' => $amazonAccessInfo["mws_auth_token"],
		);
		$timeMS1=TimeUtil::getCurrentTimestampMS();
		$reqParams=array();
		$reqParams["config"]=json_encode($config);
		$timeout=120; //s
		echo "before call_amazon_api  config:".print_r($config,true)." ".print_r($sumbitOrderQueueItem->attributes,true)." \n";
//		SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","before call_amazon_api fromDateTime:$fromDateTime,toDateTime:$toDateTime config:".print_r($reqParams,true),"info");
// 		\Yii::info(["Order", __CLASS__,__FUNCTION__,"","before call_amazon_api fromDateTime:$fromDateTime,toDateTime:$toDateTime config:".print_r($reqParams,true)],'edb\global');
		
		$params = $sumbitOrderQueueItem->parms;
		// dzt20150918 order_item_v2 表的order_source_order_item_id 字段为bigint 20 导致amazon 导入的order_source_order_item_id 前面有 0开头的被去掉0
		// 目前手动重新格式化order_source_order_item_id 从amazon原始表 来大概得到 order_source_order_item_id 是 "00005935024747" 这样的14位数字 ，所以这里临时做补零
		if($sumbitOrderQueueItem->api_action == 'ShipAmazonOrder'){
			$paramsArr = json_decode($params,true);
			$newItemsArr = array();
			foreach ($paramsArr['items'] as $index=>$item){
				if(strlen($item['ItemCode']) < 14){
					$item['ItemCode'] = str_pad($item['ItemCode'],14,"0",STR_PAD_LEFT);
				}
				$newItemsArr[$index] = $item;
			}
			$paramsArr['items'] = $newItemsArr;
			$params = json_encode($paramsArr);
		}
		Yii::info('_submitEagleOrderToAmazon params :'.print_r($params , true),"file");
		$retInfo=AmazonProxyConnectApiHelper::call_amazon_api($sumbitOrderQueueItem->api_action,$reqParams,$timeout,'json',array("parms"=>$params));
		echo "after call_amazon_api retinfo:".print_r($retInfo,true);
		$timeMS2=TimeUtil::getCurrentTimestampMS();
		// 无论proxy获取amazon信息结果如何，记录访问成功的 amazon api次数
		if ($retInfo["success"] !==false){
			$SAU_obj = SaasAmazonUser::findOne(['merchant_id'=>$sumbitOrderQueueItem->merchant_id]);
			if(!empty($SAU_obj)){
				if(isset($retInfo['response']['apiCallTimes'])){
			
					// 获取某个 puid 的amazon merchant 这个小时里  ShipAmazonOrder 的调用次数记录
					$apiCounter = SellerCallPlatformApiCounter::findOne(['puid'=>$SAU_obj->uid,'platform'=>'amazon','merchant_id'=>$SAU_obj->merchant_id,'datetime'=>date('Y-m-d H',time()),'call_type'=>$sumbitOrderQueueItem->api_action]);
					if(empty($apiCounter)){
						$apiCounter = new SellerCallPlatformApiCounter();
						$apiCounter->puid = $SAU_obj->uid;
						$apiCounter->platform = 'amazon';
						$apiCounter->merchant_id = $SAU_obj->merchant_id;
						$apiCounter->datetime = date('Y-m-d H',time());
						$apiCounter->call_type = $sumbitOrderQueueItem->api_action;// amazon 的与 call_amazon_api 的action一致
						$apiCounter->count = 1;
					}else{
						$apiCounter->count = $apiCounter->count + 1;
					}
			
					if(!$apiCounter->save()){
						Yii::error('_getOrderItemsFromAmazonById $apiCounter->save() error :'.print_r($apiCounter->getErrors() , true),"file");
					}
			
				}else{
					Yii::error("_getOrderItemsFromAmazonById count apiCallTimes error : apiCallTimes is empty. uid:".$SAU_obj->uid." response:".print_r($retInfo['response'],true),"file");
				}
			
			}else{
				Yii::error("_getOrderItemsFromAmazonById count apiCallTimes error : Cannot find SaasAmazonUser merchant_id:".$sumbitOrderQueueItem->merchant_id,"file");
			}
		}
		
		$timeMS3=TimeUtil::getCurrentTimestampMS();
		Yii::info("cronBatchAutoOrderSubmit,api_action=$sumbitOrderQueueItem->api_action,order_id=".$sumbitOrderQueueItem->order_id.",t2_1=".($timeMS2-$timeMS1).
		",t3_2=".($timeMS3-$timeMS2)."t3_1=".($timeMS3-$timeMS1),"file");
		
		//check the return info
		if ($retInfo["success"]===false or $retInfo["response"]["success"]===false  )  {
			if (isset($retInfo["response"]) and isset($retInfo["response"]["message"])){
			    //SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","after call_amazon_api .  error message:".$retInfo["response"]["message"]." retryCount:".$retInfo["response"]["retryCount"],"error");
				//\Yii::info(["Order", __CLASS__,__FUNCTION__,"","after call_amazon_api .  error message:".$retInfo["response"]["message"]." retryCount:".$retInfo["response"]["retryCount"]],'edb\global');
				return array(false,-1,$retInfo["response"]["message"],$submitId,"");
			}
			return array(false,-1,"amazon proxy return error",$submitId,"");
		}
		$retryCount=$retInfo["response"]["retryCount"];
		
		if (isset($retInfo["response"]["submit_id"])) {
			$submitId=$retInfo["response"]["submit_id"];
			return array(true,$retryCount,"",$submitId,$retInfo["response"]);
		}
		
		return array(false,-1,"amazon proxy return error",$submitId,"");
		
	}
	
	/**
	 * 提交到proxy获取submitId
	 * @param unknown $sumbitOrders
	 * @param unknown $merchant_id
	 * @param unknown $marketplace_short
	 * @param unknown $amazonAccessInfo
	 * @return multitype:boolean number NULL string |multitype:boolean number string |multitype:boolean string unknown
	 */
	private static function _batchSubmitEagleOrderToAmazon($sumbitOrders,$merchant_id,$marketplace_short,$amazonAccessInfo){
	
		$AMAZON_REGION_MARKETPLACE_CONFIG=array_flip(self::$AMAZON_MARKETPLACE_REGION_CONFIG);
		$submitId=0;
		$marketplace_id=$AMAZON_REGION_MARKETPLACE_CONFIG[$marketplace_short];
		$config=array(
				'merchant_id' =>$merchant_id,
				'marketplace_id' => $marketplace_id,
		        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 				'access_key_id' => $amazonAccessInfo["access_key_id"],
// 				'secret_access_key' => $amazonAccessInfo["secret_access_key"],
		        'mws_auth_token' => $amazonAccessInfo["mws_auth_token"],
		);
		$timeMS1=TimeUtil::getCurrentTimestampMS();
		$reqParams=array();
		$reqParams["config"]=json_encode($config);
		$timeout=120; //s
		$params = json_encode(array('orders'=>$sumbitOrders));
		echo "before call_amazon_api  config:".print_r($config,true)." ".print_r($sumbitOrders,true)." \n";
		//		SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","before call_amazon_api fromDateTime:$fromDateTime,toDateTime:$toDateTime config:".print_r($reqParams,true),"info");
		// 		\Yii::info(["Order", __CLASS__,__FUNCTION__,"","before call_amazon_api fromDateTime:$fromDateTime,toDateTime:$toDateTime config:".print_r($reqParams,true)],'edb\global');
		Yii::info('_submitEagleOrderToAmazon params :'.print_r($params , true),"file");
		$retInfo=AmazonProxyConnectApiHelper::call_amazon_api("batchShipAmazonOrders",$reqParams,$timeout,'json',array("parms"=>$params));
		echo "after call_amazon_api retinfo:".print_r($retInfo,true);
		$timeMS2=TimeUtil::getCurrentTimestampMS();
		// 无论proxy获取amazon信息结果如何，记录访问成功的 amazon api次数
		if ($retInfo["success"] !==false){
			$SAU_obj = SaasAmazonUser::findOne(['merchant_id'=>$merchant_id]);
			if(!empty($SAU_obj)){
				if(isset($retInfo['response']['apiCallTimes'])){
						
					// 获取某个 puid 的amazon merchant 这个小时里  ShipAmazonOrder 的调用次数记录
					$apiCounter = SellerCallPlatformApiCounter::findOne(['puid'=>$SAU_obj->uid,'platform'=>'amazon','merchant_id'=>$SAU_obj->merchant_id,'datetime'=>date('Y-m-d H',time()),'call_type'=>"batchShipAmazonOrders"]);
					if(empty($apiCounter)){
						$apiCounter = new SellerCallPlatformApiCounter();
						$apiCounter->puid = $SAU_obj->uid;
						$apiCounter->platform = 'amazon';
						$apiCounter->merchant_id = $SAU_obj->merchant_id;
						$apiCounter->datetime = date('Y-m-d H',time());
						$apiCounter->call_type = "batchShipAmazonOrders";// amazon 的与 call_amazon_api 的action一致
						$apiCounter->count = 1;
					}else{
						$apiCounter->count = $apiCounter->count + 1;
					}
						
					if(!$apiCounter->save()){
						Yii::error('_getOrderItemsFromAmazonById $apiCounter->save() error :'.print_r($apiCounter->getErrors() , true),"file");
					}
						
				}else{
					Yii::error("_getOrderItemsFromAmazonById count apiCallTimes error : apiCallTimes is empty. uid:".$SAU_obj->uid." response:".print_r($retInfo['response'],true),"file");
				}
					
			}else{
				Yii::error("_getOrderItemsFromAmazonById count apiCallTimes error : Cannot find SaasAmazonUser merchant_id:".$merchant_id,"file");
			}
		}
	
		$timeMS3=TimeUtil::getCurrentTimestampMS();
		Yii::info("cronBatchAutoOrderSubmit,api_action=batchShipAmazonOrders,group:".$merchant_id.",".$marketplace_short."t2_1=".($timeMS2-$timeMS1).
		",t3_2=".($timeMS3-$timeMS2)."t3_1=".($timeMS3-$timeMS1),"file");
	
		//check the return info
		if ($retInfo["success"]===false or $retInfo["response"]["success"]===false  )  {
			if (isset($retInfo["response"]) and isset($retInfo["response"]["message"])){
				//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","after call_amazon_api .  error message:".$retInfo["response"]["message"]." retryCount:".$retInfo["response"]["retryCount"],"error");
				//\Yii::info(["Order", __CLASS__,__FUNCTION__,"","after call_amazon_api .  error message:".$retInfo["response"]["message"]." retryCount:".$retInfo["response"]["retryCount"]],'edb\global');
				return array(false,-1,$retInfo["response"]["message"],$submitId,"");
			}
			return array(false,-1,"amazon proxy return error",$submitId,"");
		}
		$retryCount=$retInfo["response"]["retryCount"];
	
		if (isset($retInfo["response"]["submit_id"])) {
			$submitId=$retInfo["response"]["submit_id"];
			return array(true,$retryCount,"",$submitId,$retInfo["response"]);
		}
	
		return array(false,-1,"amazon proxy return error",$submitId,"");
	
	}

	/**
	 * 后台crontab触发， amazon的订单在eagle上的状态变化需要同步到amazon平台
	 * 从amazon_order_submit_queue找到待标志发货的订单，并提交到amazon，同时获取到amazon返回的submitid
	 */
	public static function cronAutoOrderSubmit(){
		\Yii::info("entering cronAutoOrderSubmit");
		
		//process_status --- 0 没同步; 1 同步中; 2 submit成功; 3 submit失败; 4 check 中; 5 check 成功; 6 check 访问失败;7 amazon返回之前提交的任务执行失败
		$SAA_objs = AmazonOrderSubmitQueue::find()->where('`process_status` = 0 OR `process_status` = 3')->limit(100)->all();
		echo "count:".count($SAA_objs)." \n"; 
		\Yii::info("There are ".count($SAA_objs)." orders waiting for being committed to eagle","file");
		
		$merchantIdAccountMap=self::_getMerchantIdAccountInfoMap();
		if(count($SAA_objs)){
			foreach($SAA_objs as $SAA_obj){
				$amazonAccessInfo=$merchantIdAccountMap[$SAA_obj->merchant_id];				
				$SAA_obj->process_status=1;
				\Yii::info("before _submitEagleOrderToAmazon");
				list($ret,$retryCount,$message,$submitId,$response)=self::_submitEagleOrderToAmazon($SAA_obj,$amazonAccessInfo);
				\Yii::info("after _submitEagleOrderToAmazon");
				if ($ret===false){
					echo "error:$message \n";
					$SAA_obj->process_status=3;
					$SAA_obj->error_count=$SAA_obj->error_count+1;
					$SAA_obj->error_message=$message;
					$SAA_obj->update_time=time();
					$SAA_obj->save(false);
					continue;
				}
				
				$SAA_obj->process_status=2;
				$SAA_obj->results=json_encode($response);				
				$SAA_obj->submit_id=$submitId;
				$SAA_obj->error_count=0;
				$SAA_obj->error_message="";
				$SAA_obj->update_time=time();
				$SAA_obj->next_execution_time=time()+600;  //next_execution_time 最早执行检查submit job的时间点  
				$SAA_obj->submit_finish_time=$SAA_obj->update_time;
				$SAA_obj->save(false);
				\Yii::info("SAA_obj->save ok");
			}
			
		}
	}
	
	/**
	 * 后台crontab 批量触发， amazon的订单在eagle上的状态变化需要同步到amazon平台
	 * 从amazon_order_submit_queue找到待标志发货的订单，并提交到amazon，同时获取到amazon返回的submitid
	 */
	public static function cronBatchAutoOrderSubmit(){
		\Yii::info("entering cronBatchAutoOrderSubmit","file");
		$nowTime = time();
		
		// 这里并没有过滤 error_count 和 next_execution_time
		//process_status --- 0 没同步; 1 同步中; 2 submit成功; 3 submit失败; 4 check 中; 5 check 成功; 6 check 访问失败;7 amazon返回之前提交的任务执行失败
		$SAA_groups = AmazonOrderSubmitQueue::find()->select('merchant_id,marketplace_short')->where('`process_status` = 0 OR `process_status` = 3')
		
		->andWhere("`next_execution_time` is null or `next_execution_time`<".$nowTime)
		->andWhere("`error_count`< 30")
		->groupBy('merchant_id,marketplace_short')->asArray()->all();
	
		echo "There are :".count($SAA_groups)."groups. \n";
		foreach ($SAA_groups as $group){
		    
		    $merchantIdAccountMap=self::_getMerchantIdAccountInfoMap();
		    if(empty($merchantIdAccountMap[$group['merchant_id']])){
		        //dzt20180915 添加一个process_status = 8 for未知原因账号已经不存在的，不再处理
		        $errCount = AmazonOrderSubmitQueue::updateAll(['process_status'=>8],['merchant_id'=>$group['merchant_id']]);
		        echo "group:".$group['merchant_id'].",".$group['marketplace_short']." merchant api info not found!!!. count:".$errCount." \n";
		        \Yii::error("cronBatchAutoOrderSubmit group:".$group['merchant_id'].",".$group['marketplace_short']." merchant api info not found!!!. count:".$errCount,"file");
	          
		    }
		    
			$SAA_objs = AmazonOrderSubmitQueue::find()->where('`process_status` = 0 OR `process_status` = 3')
			->andWhere(['merchant_id'=>$group['merchant_id'],'marketplace_short'=>$group['marketplace_short']])->andWhere("`error_count`< 30")->limit(20)->all();// 最多20个一次 submit
				
			echo "group:".$group['merchant_id'].",".$group['marketplace_short']." count:".count($SAA_objs)." \n";
	
			\Yii::info("There are ".count($SAA_objs)." orders waiting for being committed to eagle","file");
				
			
			if(count($SAA_objs)){
				$amazonAccessInfo=$merchantIdAccountMap[$group['merchant_id']];// v2不清楚为什么没有merchant_id A3BBBK0I6CSR4Q 的信息。web2有
				$submitQueueIds = array();
				$groupShipOrders = array();
				foreach($SAA_objs as $SAA_obj){
					if(!in_array($SAA_obj->id,$submitQueueIds)){
						$SAA_obj->process_status=1;
						$hasGot = $SAA_obj->update(false);
						if($hasGot == 0) continue;
						
						$params = $SAA_obj->parms;
						// dzt20150918 order_item_v2 表的order_source_order_item_id 字段为bigint 20 导致amazon 导入的order_source_order_item_id 前面有 0开头的被去掉0
						// 目前手动重新格式化order_source_order_item_id 从amazon原始表 来大概得到 order_source_order_item_id 是 "00005935024747" 这样的14位数字 ，所以这里临时做补零
						if($SAA_obj->api_action == 'ShipAmazonOrder'){
							$paramsArr = json_decode($params,true);
							$newItemsArr = array();
							foreach ($paramsArr['items'] as $index=>$item){
								if(strlen($item['ItemCode']) < 14){
									$item['ItemCode'] = str_pad($item['ItemCode'],14,"0",STR_PAD_LEFT);
								}
								if ($item['ItemShipQty']=="0") {
									echo "ItemShipQty zero\n";
									\Yii::info("ItemShipQty zero","file");
									continue;
								}
								$newItemsArr[$index] = $item;

							}
							$paramsArr['items'] = $newItemsArr;
							$groupShipOrders[] = $paramsArr;
							$submitQueueIds[] = $SAA_obj->id;
						}
					}
				}
	
				\Yii::info("before _submitEagleOrderToAmazon","file");
				list($ret,$retryCount,$message,$submitId,$response)=self::_batchSubmitEagleOrderToAmazon($groupShipOrders, $group['merchant_id'], $group['marketplace_short'], $amazonAccessInfo);
				\Yii::info("after _submitEagleOrderToAmazon","file");
	
				if ($ret===false){
					echo "error:$message \n";
					foreach ($submitQueueIds as $submitQueueId){
						$SAA_obj = AmazonOrderSubmitQueue::findOne(['id'=>$submitQueueId]);
						$SAA_obj->process_status=3;
						$SAA_obj->error_count=$SAA_obj->error_count+1;
						$SAA_obj->error_message=$message;
						$SAA_obj->update_time=time();
						$SAA_obj->next_execution_time=time()+60; // dzt20151215 submitFeed add next_execution_time
						$SAA_obj->save(false);
						//更新订单 虚拟发货 状态 start
						if ($SAA_obj->error_count >= 30) {
							//通知oms的发货情况
							if ($SAA_obj->api_action=="ShipAmazonOrder"){
								if (!empty($SAA_obj->addition_info)){
									$addition_info=json_decode($SAA_obj->addition_info,true);
									$osObj = OdOrderShipped::findOne($addition_info["order_shipped_id"]);
									if (!is_null($osObj)){
										$osObj->result = 'false';
										$osObj->errors =$message;
										$osObj->updated = time();
										$osObj->lasttime = time();
										$osObj->save(false);
										echo "submit error record\n";
										// 更新订单 虚拟发货 状态 start
										OrderApiHelper::setOrderSyncShippedStatus($osObj->order_id,"F");
										// 更新订单 虚拟发货 状态 end
									}
								}
							}
						}
						// 更新订单 虚拟发货 状态 end

					}
					continue;
				}
	
				foreach ($submitQueueIds as $submitQueueId){
					$SAA_obj = AmazonOrderSubmitQueue::findOne(['id'=>$submitQueueId]);
					$SAA_obj->process_status=2;
					$SAA_obj->results=json_encode($response);
					$SAA_obj->submit_id=$submitId;
					$SAA_obj->error_count=0;
					$SAA_obj->error_message="";
					$SAA_obj->update_time=time();
					$SAA_obj->next_execution_time=time()+600;  //next_execution_time 最早执行检查submit job的时间点
					$SAA_obj->submit_finish_time=$SAA_obj->update_time;
					$SAA_obj->save(false);
					\Yii::info("SAA_obj->save ok","file");
				}
			}
		}
	}
	
	/**
	 * 
	 * @param unknown $sumbitOrderQueueItem
	 * @param unknown $amazonAccessInfo
	 * @return array( $ret,$message)
	 * 其中ret
	 * -1 --- 网络异常。   本机连接proxy机器 需要重试的
	 * -2 --- 网络正常。  amazon返回失败。提示还没有到检查是否执行时间，需要等待5分钟再查询。
	 * -3 --- amazon返回，之前submit的信息有误的情况。如：如期格式有误。     提示前台，并且不再重试 ！！！
	 * -4 --- 网络异常。  proxy连接amazon网络异常。需要等待下一次周期再查询。
	 * true  --- 最后才是amazon已经成功执行之前submit的请求
	 *  
	 */
	private static function _checkAmazonCompletedOrNot($sumbitOrderQueueItem,$amazonAccessInfo){
		$AMAZON_REGION_MARKETPLACE_CONFIG=array_flip(self::$AMAZON_MARKETPLACE_REGION_CONFIG);
		$submitId=$sumbitOrderQueueItem->submit_id;
		$marketplace_id=$AMAZON_REGION_MARKETPLACE_CONFIG[$sumbitOrderQueueItem->marketplace_short];
		$config=array(
				'merchant_id' =>$sumbitOrderQueueItem->merchant_id,
				'marketplace_id' => $marketplace_id,
		        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 				'access_key_id' => $amazonAccessInfo["access_key_id"],
// 				'secret_access_key' => $amazonAccessInfo["secret_access_key"],
		        'mws_auth_token' => $amazonAccessInfo["mws_auth_token"],
		);
		
		$timeout=120; //s		
		$parms = array('parms' => json_encode(array('submit_id' =>$submitId)), 
		               'config' => json_encode($config), 
		              );
		echo "before call_amazon_api  config:".print_r($config,true)." ".print_r($sumbitOrderQueueItem->attributes,true)." \n";
		$retInfo = AmazonProxyConnectApiHelper::call_amazon_api('GetAmazonSubmitFeedResult', $parms,$timeout);
		echo "after call_amazon_api retinfo:".print_r($retInfo,true);
		
		//check the return info---失败有4种情况！！！！！！！！！！！
		//1. 网络异常。   本机连接proxy机器 需要重试的
		if ($retInfo["success"]===false){
			return array(-1,"amazon proxy return error",$submitId,"  message:".$retInfo["message"]);
		}
		
		// 1.1无论proxy获取amazon信息结果如何，记录访问成功的 amazon api次数
		if ($retInfo["success"] !==false){
			$SAU_obj = SaasAmazonUser::findOne(['merchant_id'=>$sumbitOrderQueueItem->merchant_id]);
			if(!empty($SAU_obj)){
				if(isset($retInfo['response']['apiCallTimes'])){
						
					// 获取某个 puid 的amazon merchant 这个小时里  GetAmazonSubmitFeedResult 的调用次数记录
					$apiCounter = SellerCallPlatformApiCounter::findOne(['puid'=>$SAU_obj->uid,'platform'=>'amazon','merchant_id'=>$SAU_obj->merchant_id,'datetime'=>date('Y-m-d H',time()),'call_type'=>'GetAmazonSubmitFeedResult']);
					if(empty($apiCounter)){
						$apiCounter = new SellerCallPlatformApiCounter();
						$apiCounter->puid = $SAU_obj->uid;
						$apiCounter->platform = 'amazon';
						$apiCounter->merchant_id = $SAU_obj->merchant_id;
						$apiCounter->datetime = date('Y-m-d H',time());
						$apiCounter->call_type = 'GetAmazonSubmitFeedResult';// amazon 的与 call_amazon_api 的action一致
						$apiCounter->count = 1;
					}else{
						$apiCounter->count = $apiCounter->count + 1;
					}
						
					if(!$apiCounter->save()){
						Yii::error('_getOrderItemsFromAmazonById $apiCounter->save() error :'.print_r($apiCounter->getErrors() , true),"file");
					}
						
				}else{
					Yii::error("_getOrderItemsFromAmazonById count apiCallTimes error : apiCallTimes is empty. uid:".$SAU_obj->uid." response:".print_r($retInfo['response'],true),"file");
				}
					
			}else{
				Yii::error("_getOrderItemsFromAmazonById count apiCallTimes error : Cannot find SaasAmazonUser merchant_id:".$sumbitOrderQueueItem->merchant_id,"file");
			}
		}
		
		//2.网络正常。  amazon返回失败。提示还没有到检查是否执行时间，需要等待5分钟再查询。
		if ($retInfo["response"]["success"]===false  )  {
			if (isset($retInfo["response"]["errorCode"]) and $retInfo["response"]["errorCode"]=="FeedProcessingResultNotReady"){
				//如果errorCode是 FeedProcessingResultNotReady，     ---表示amazon还没有开始执行对应submitid的请求，并不是错误！！！！
				return array(-2,$retInfo["response"]["message"]);
			}	
		}
	    //3. amazon返回，之前submit的信息有误的情况。如：如期格式有误。     提示前台，并且不再重试 ！！！
		if ($retInfo["response"]["success"]===false  )  {
			if (isset($retInfo["response"]["ProcessingReport"]) and isset($retInfo["response"]["ProcessingReport"]["status"])){
				$status=$retInfo["response"]["ProcessingReport"]["status"];
				if ($status[0]=="Complete"){
					if(is_array($retInfo["response"]["message"])){
						return array(-3,implode(';', $retInfo["response"]["message"]));
					}else{
						return array(-3,$retInfo["response"]["message"]);
					}
				}
			}
		}
		//4.网络异常。  proxy连接amazon网络异常。需要等待下一次周期再查询。
		if ($retInfo["response"]["success"]===false  )  {
			return array(-4,$retInfo["response"]["message"]);
		}		
	    
		//=====最后才是amazon已经成功执行之前submit的请求		
		return array(true,json_encode($retInfo['response']['ProcessingReport']));
	}
	
	/**
	 *
	 * @param unknown $sumbitOrderQueueItem
	 * @param unknown $amazonAccessInfo
	 * @return array( $ret,$message)
	 * 其中ret
	 * -1 --- 网络异常。   本机连接proxy机器 需要重试的
	 * -2 --- 网络正常。  amazon返回失败。提示还没有到检查是否执行时间，需要等待5分钟再查询。
	 * -3 --- amazon返回，之前submit的信息有误的情况。如：如期格式有误。     提示前台，并且不再重试 ！！！
	 * -4 --- 网络异常。  proxy连接amazon网络异常。需要等待下一次周期再查询。
	 * true  --- 最后才是amazon已经成功执行之前submit的请求
	 *
	 */
	private static function _batchCheckAmazonCompletedOrNot($submit_id,$merchant_id,$marketplace_short,$amazonAccessInfo){
		$AMAZON_REGION_MARKETPLACE_CONFIG=array_flip(self::$AMAZON_MARKETPLACE_REGION_CONFIG);
		$submitId = $submit_id;
		$marketplace_id=$AMAZON_REGION_MARKETPLACE_CONFIG[$marketplace_short];
		$config=array(
				'merchant_id' =>$merchant_id,
				'marketplace_id' => $marketplace_id,
		        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 				'access_key_id' => $amazonAccessInfo["access_key_id"],
// 				'secret_access_key' => $amazonAccessInfo["secret_access_key"],
		        'mws_auth_token' => $amazonAccessInfo["mws_auth_token"],
		);
	
		$timeout=120; //s
		$parms = array('parms' => json_encode(array('submit_id' =>$submitId)),
				'config' => json_encode($config),
		);
		echo "before call_amazon_api  config:".print_r($config,true)." submitId:$submitId \n";
		$retInfo = AmazonProxyConnectApiHelper::call_amazon_api('GetAmazonSubmitFeedResult', $parms,$timeout);
		echo "after call_amazon_api retinfo:".print_r($retInfo,true);
	
		//check the return info---失败有4种情况！！！！！！！！！！！
		//1. 网络异常。   本机连接proxy机器 需要重试的
		if ($retInfo["success"]===false){
			return array(-1,"amazon proxy return error",$submitId,"  message:".$retInfo["message"]);
		}
	
		// 1.1无论proxy获取amazon信息结果如何，记录访问成功的 amazon api次数
		if ($retInfo["success"] !==false){
			$SAU_obj = SaasAmazonUser::findOne(['merchant_id'=>$merchant_id]);
			if(!empty($SAU_obj)){
				if(isset($retInfo['response']['apiCallTimes'])){
	
					// 获取某个 puid 的amazon merchant 这个小时里  GetAmazonSubmitFeedResult 的调用次数记录
					$apiCounter = SellerCallPlatformApiCounter::findOne(['puid'=>$SAU_obj->uid,'platform'=>'amazon','merchant_id'=>$SAU_obj->merchant_id,'datetime'=>date('Y-m-d H',time()),'call_type'=>'GetAmazonSubmitFeedResult']);
					if(empty($apiCounter)){
						$apiCounter = new SellerCallPlatformApiCounter();
						$apiCounter->puid = $SAU_obj->uid;
						$apiCounter->platform = 'amazon';
						$apiCounter->merchant_id = $SAU_obj->merchant_id;
						$apiCounter->datetime = date('Y-m-d H',time());
						$apiCounter->call_type = 'GetAmazonSubmitFeedResult';// amazon 的与 call_amazon_api 的action一致
						$apiCounter->count = 1;
					}else{
						$apiCounter->count = $apiCounter->count + 1;
					}
	
					if(!$apiCounter->save()){
						Yii::error('_batchCheckAmazonCompletedOrNot $apiCounter->save() error :'.print_r($apiCounter->getErrors() , true),"file");
					}
	
				}else{
					Yii::error("_batchCheckAmazonCompletedOrNot count apiCallTimes error : apiCallTimes is empty. uid:".$SAU_obj->uid." response:".print_r($retInfo['response'],true),"file");
				}
					
			}else{
				Yii::error("_batchCheckAmazonCompletedOrNot count apiCallTimes error : Cannot find SaasAmazonUser merchant_id:".$merchant_id,"file");
			}
		}
	
		//2.网络正常。  amazon返回失败。提示还没有到检查是否执行时间，需要等待5分钟再查询。
		if ($retInfo["response"]["success"]===false  )  {
			if (isset($retInfo["response"]["errorCode"]) and $retInfo["response"]["errorCode"]=="FeedProcessingResultNotReady"){
				//如果errorCode是 FeedProcessingResultNotReady，     ---表示amazon还没有开始执行对应submitid的请求，并不是错误！！！！
				return array(-2,$retInfo["response"]["message"]);
			}
		}
		//3. amazon返回，之前submit的信息有误的情况。如：如期格式有误。     提示前台，并且不再重试 ！！！
		if ($retInfo["response"]["success"]===false  )  {
			if (isset($retInfo["response"]["ProcessingReport"]) and isset($retInfo["response"]["ProcessingReport"]["status"])){
				$status=$retInfo["response"]["ProcessingReport"]["status"];
				if ($status[0]=="Complete"){
					if(is_array($retInfo["response"]["message"])){
						return array(-3,implode(';', $retInfo["response"]["message"]));
					}else{
						return array(-3,$retInfo["response"]["message"]);
					}
				}
			}
		}
		//4.网络异常。  proxy连接amazon网络异常。需要等待下一次周期再查询。
		if ($retInfo["response"]["success"]===false  )  {
			return array(-4,$retInfo["response"]["message"]);
		}
		 
		//=====最后才是amazon已经成功执行之前submit的请求
		return array(true,json_encode($retInfo['response']['ProcessingReport']));
	}
	
	/**
	 * 后台crontab触发， 检查之前提交给amazon的请求是否都已经处理完成
	 * 从amazon_order_submit_queue提取待检查的submitid
	 */
	public static function cronCheckAutoOrderSubmit(){
		
		$nowTime=time();
		
		//process_status --- 0 没同步; 1 同步中; 2 submit成功; 3 submit失败; 4 check 中; 5 check 成功; 6 check 访问失败;7 amazon返回之前提交的任务执行失败
		$SAA_objs = AmazonOrderSubmitQueue::find()
		->where('(`process_status` = 2 OR `process_status` = 6 ) AND `next_execution_time`<'.$nowTime." AND `error_count`<15")
		->limit(100)
		->all();
		
		echo "count:".count($SAA_objs)." \n";
//  	\Yii::info(["Order", __CLASS__,__FUNCTION__,"","There are ".count($SAA_objs)." orders waiting for check"],'edb\global');
		\Yii::info("cronCheckAutoOrderSubmit. There are ".count($SAA_objs)." orders waiting for check");
		
		$merchantIdAccountMap=self::_getMerchantIdAccountInfoMap();		
		if(count($SAA_objs)){
			foreach($SAA_objs as $SAA_obj){
				$amzUser = SaasAmazonUser::findOne(["merchant_id"=>$SAA_obj->merchant_id]);
				$merchantUid = null;
				if(!empty($amzUser)) 
					$merchantUid = $amzUser->uid;
				
				$amazonAccessInfo=$merchantIdAccountMap[$SAA_obj->merchant_id];
				$SAA_obj->process_status=1;
				
				\Yii::info("cronCheckAutoOrderSubmit. before _checkAmazonCompletedOrNot");
				list($ret,$message)=self::_checkAmazonCompletedOrNot($SAA_obj,$amazonAccessInfo);
				/* 这里返回ret
				* -1 --- 网络异常。   本机连接proxy机器 需要等待下一次周期再查询。
				* -2 --- 网络正常。  amazon返回失败。提示还没有到检查是否执行时间，需要等待5分钟再查询。
				* -3 --- amazon返回，之前submit的信息有误的情况。如：如期格式有误。     提示前台，并且不再重试 ！！！
				* -4 --- 网络异常。  proxy连接amazon网络异常。需要等待下一次周期再查询。
				* true  --- 最后才是amazon已经成功执行之前submit的请求  */
				
				\Yii::info("cronCheckAutoOrderSubmit. after _checkAmazonCompletedOrNot");
				
				if ($ret===-1 or $ret===-4) { 
					echo "error:$message \n";
					$SAA_obj->process_status=6;
					$SAA_obj->error_count=$SAA_obj->error_count+1;
					$SAA_obj->error_message=$message;
					$SAA_obj->update_time=time();
					$SAA_obj->next_execution_time=time()+60;
					$SAA_obj->save(false);
					continue;
				}
				if ($ret===-2) {
					echo "error:$message \n";
					$SAA_obj->process_status=6;
					$SAA_obj->error_count=$SAA_obj->error_count+1;
					$SAA_obj->error_message=$message;
					$SAA_obj->update_time=time();
					$SAA_obj->next_execution_time=time()+300;
					$SAA_obj->save(false);
					continue;
				}
				if ($ret===-3) {
					echo "error:$message \n";
					$SAA_obj->process_status=7;
					$SAA_obj->error_count=$SAA_obj->error_count+1;
					$SAA_obj->error_message=$message;
					$SAA_obj->update_time=time();
					$SAA_obj->save(false);
					
					//通知oms的发货情况
					if ($SAA_obj->api_action=="ShipAmazonOrder"){
						if (!empty($SAA_obj->addition_info)){
							$addition_info=json_decode($SAA_obj->addition_info,true);
					
							$osObj = OdOrderShipped::findOne($addition_info["order_shipped_id"]);
							if (!is_null($osObj)){	
								$osObj->status = 2;// 界面显示发货失败（红色的）
								$osObj->result = 'false';
								$osObj->errors =$message;								
								$osObj->updated = time();
								$osObj->lasttime = time();
								$osObj->save(false);
							}
						}
					}
					continue;
				}
				
				//成功的情况
				echo "meass:".$message." \n";
				$SAA_obj->process_status=5;
				$SAA_obj->check_result=$message;				
				$SAA_obj->error_count=0;
				$SAA_obj->error_message="";
				$SAA_obj->update_time=time();
				$SAA_obj->check_finish_time=$SAA_obj->update_time;				
				$SAA_obj->save(false);
				
				//如果是标记发货成功的话，这里需要设置oms的发货结果od_order_shipped
				if ($SAA_obj->api_action=="ShipAmazonOrder"){					
					if (!empty($SAA_obj->addition_info)){
						$addition_info=json_decode($SAA_obj->addition_info,true);
						
						$osObj = OdOrderShipped::findOne($addition_info["order_shipped_id"]);
						if (!is_null($osObj)){
							$osObj->status = 1;
							$osObj->result = 'true';
							$osObj->errors = '';
							$osObj->updated = time();
							$osObj->lasttime = time();
							$osObj->save(false);
						}
					}
				}
			}	
		}
	}	
	
	/**
	 * 后台crontab触发， 批量检查之前提交给amazon的请求是否都已经处理完成
	 * 从amazon_order_submit_queue提取待检查的submitid
	 */
	public static function cronBatchCheckAutoOrderSubmit(){
	
		$nowTime=time();
		
		// 每天6点清理记录
		if(date("H") == "06"){
		    $threeMonsAgo = $nowTime - 90*86400;
		    AmazonOrderSubmitQueue::deleteAll("`create_time`<".$threeMonsAgo);
		}
		
		//process_status --- 0 没同步; 1 同步中; 2 submit成功; 3 submit失败; 4 check 中; 5 check 成功; 6 check 访问失败;7 amazon返回之前提交的任务执行失败
		$SAA_groups = AmazonOrderSubmitQueue::find()
		->where("(`process_status`=2 OR `process_status`=6 ) AND `next_execution_time`<".$nowTime." AND `error_count`<15")
		->groupBy('submit_id')->asArray()->all();
		echo "There are :".count($SAA_groups)."groups. \n";
		
		foreach ($SAA_groups as $group){
			$SAA_objs = AmazonOrderSubmitQueue::find()->where('`process_status`=2 OR `process_status`=6 ')
			->andWhere(['submit_id'=>$group['submit_id']])->all();
				
			echo "group:".$group['submit_id']." count:".count($SAA_objs)." \n";
			//  	\Yii::info(["Order", __CLASS__,__FUNCTION__,"","There are ".count($SAA_objs)." orders waiting for check"],'edb\global');
			\Yii::info("cronCheckAutoOrderSubmit. There are ".count($SAA_objs)." orders waiting for check");
			
			$merchantIdAccountMap=self::_getMerchantIdAccountInfoMap();
			
			if(count($SAA_objs)){
			    
			    
			    $nowTime = time();
				$amzUser = SaasAmazonUser::findOne(["merchant_id"=>$group['merchant_id']]);
				$merchantUid = null;
				if(!empty($amzUser)){
					$merchantUid = $amzUser->uid;
				}else{// 账号已经不存在
				    $message = "error:merchant_id:".$group['merchant_id']." not exist.";
				    echo "error:merchant_id:".$group['merchant_id']." not exist, stop and callback.\n";
					foreach ($SAA_objs as $SAA_obj){
						$SAA_obj->process_status = 7;
						$SAA_obj->error_count = $SAA_obj->error_count + 1;
						$SAA_obj->error_message = $message;
						$SAA_obj->update_time = $nowTime;
						$SAA_obj->save(false);
						//通知oms的发货情况
						// merchant_id 记录已经丢失 找不到uid通知OMS了。
					}
					
					continue;
				}
				echo "uid:".$merchantUid."\n";
						
				$amazonAccessInfo=$merchantIdAccountMap[$group['merchant_id']];
				$submitQueueIds = Helper_Array::getCols($SAA_objs, 'id');
				\Yii::info("cronCheckAutoOrderSubmit. before _checkAmazonCompletedOrNot submitQueueIds:".implode(',', $submitQueueIds),"file");
				
				$timeMS1=TimeUtil::getCurrentTimestampMS();
				list($ret,$message)=self::_batchCheckAmazonCompletedOrNot($group['submit_id'],$group['merchant_id'], $group['marketplace_short'],$amazonAccessInfo);
				$timeMS2=TimeUtil::getCurrentTimestampMS();
				/* 这里返回ret
				 * -1 --- 网络异常。   本机连接proxy机器 需要等待下一次周期再查询。
				* -2 --- 网络正常。  amazon返回失败。提示还没有到检查是否执行时间，需要等待5分钟再查询。
				* -3 --- amazon返回，之前submit的信息有误的情况。如：如期格式有误。     提示前台，并且不再重试 ！！！
				* -4 --- 网络异常。  proxy连接amazon网络异常。需要等待下一次周期再查询。
				* true  --- 最后才是amazon已经成功执行之前submit的请求  */
				
				\Yii::info("cronCheckAutoOrderSubmit. after _checkAmazonCompletedOrNot");
				$nowTime = time();
				if ($ret===-1 or $ret===-4) {
					echo "error:$message \n";
					foreach ($submitQueueIds as $submitQueueId){
						$SAA_obj = AmazonOrderSubmitQueue::findOne(['id'=>$submitQueueId]);
						$SAA_obj->process_status=6;
						$SAA_obj->error_count=$SAA_obj->error_count+1;
						$SAA_obj->error_message=$message;
						$SAA_obj->update_time = $nowTime;
						$SAA_obj->next_execution_time = $nowTime + 60;
						$SAA_obj->save(false);
						//通知oms的发货情况
						// if ($SAA_obj->api_action=="ShipAmazonOrder"){
						// 	if (!empty($SAA_obj->addition_info)){
						// 		$addition_info=json_decode($SAA_obj->addition_info,true);
						// 		$osObj = OdOrderShipped::findOne($addition_info["order_shipped_id"]);
						// 		if (!is_null($osObj)){
						// 		// 更新订单 虚拟发货 状态 start
						// 		OrderApiHelper::setOrderSyncShippedStatus($osObj->order_id, "F");
						// 		// 更新订单 虚拟发货 状态 end
						// 		}
						// 	}
						// }
					}
					continue;
				}
				
				if ($ret===-2) {
					echo "error:$message \n";
					foreach ($submitQueueIds as $submitQueueId){
						$SAA_obj = AmazonOrderSubmitQueue::findOne(['id'=>$submitQueueId]);
						$SAA_obj->process_status=6;
						$SAA_obj->error_count=$SAA_obj->error_count+1;
						$SAA_obj->error_message=$message;
						$SAA_obj->update_time = $nowTime;
						$SAA_obj->next_execution_time = $nowTime + 300;
						$SAA_obj->save(false);
					}
					continue;
				}
				
				if ($ret===-3) {
					echo "error:$message \n";
					foreach ($submitQueueIds as $submitQueueId){
						if (self::batchCheckSubmitErrhandle($submitQueueId,$message)) {
							continue;
						}
						$SAA_obj = AmazonOrderSubmitQueue::findOne(['id'=>$submitQueueId]);
						$SAA_obj->process_status=7;
						$SAA_obj->error_count=$SAA_obj->error_count+1;
						$SAA_obj->error_message=$message;
						$SAA_obj->update_time = $nowTime;
						$SAA_obj->save(false);
						//通知oms的发货情况
						if ($SAA_obj->api_action=="ShipAmazonOrder"){
							if (!empty($SAA_obj->addition_info)){
								$addition_info=json_decode($SAA_obj->addition_info,true);
						
								$osObj = OdOrderShipped::findOne($addition_info["order_shipped_id"]);
								if (!is_null($osObj)){
									$osObj->result = 'false';
									$osObj->errors =$message;
									$osObj->updated = $nowTime;
									$osObj->lasttime = $nowTime;
									$osObj->save(false);
									echo "ret:-3 record the file\n";
								// 更新订单 虚拟发货 状态 start
								OrderApiHelper::setOrderSyncShippedStatus($osObj->order_id,"F");
								// 更新订单 虚拟发货 状态 end
								}
							}
						}
					}
					
					continue;
				}
				
				//成功的情况
				foreach ($submitQueueIds as $submitQueueId){
					$SAA_obj = AmazonOrderSubmitQueue::findOne(['id'=>$submitQueueId]);
					echo "meass:".$message." \n";
					$SAA_obj->process_status=5;
					$SAA_obj->check_result=$message;
					$SAA_obj->error_count=0;
					$SAA_obj->error_message="";
					$SAA_obj->update_time = $nowTime;
					$SAA_obj->check_finish_time=$SAA_obj->update_time;
					$SAA_obj->save(false);
					
					//如果是标记发货成功的话，这里需要设置oms的发货结果od_order_shipped
					if ($SAA_obj->api_action=="ShipAmazonOrder"){
						if (!empty($SAA_obj->addition_info)){
							$addition_info=json_decode($SAA_obj->addition_info,true);
					
							$osObj = OdOrderShipped::findOne($addition_info["order_shipped_id"]);
							if (!is_null($osObj)){
								$osObj->status = 1;
								$osObj->result = 'true';
								$osObj->errors = '';
								$osObj->updated = $nowTime;
								$osObj->lasttime = $nowTime;
								$osObj->save(false);
								//获取发货时间，并转换时间戳
								$timestring=json_decode($SAA_obj->parms,true);
								$deliverytime=strtotime($timestring["ship_date"]);
								echo "ret:success record format time:".$timestring["ship_date"]." timestamp:".$deliverytime."\n";
								// 更新订单 虚拟发货 状态 start
								OrderApiHelper::setOrderSyncShippedStatus($osObj->order_id,"C",$deliverytime);
								// 更新订单 虚拟发货 状态 end
							}
						}
					}


				}
				
				$timeMS3=TimeUtil::getCurrentTimestampMS();
				
				Yii::info("cronBatchCheckAutoOrderSubmit,group:".$group['submit_id']."t2_1=".($timeMS2-$timeMS1).
				",t3_2=".($timeMS3-$timeMS2)."t3_1=".($timeMS3-$timeMS1),"file");
			}
		}
	}

	/**
	 * [batchCheckSubmitErrhandle 用于错误处理,cancel订单,导致一批报错]
	 * @Author willage 2016-12-13T15:42:20+0800
	 * @Editor willage 2016-12-13T15:42:20+0800
	 * Array
	 *(
	 *	[0] => The items in your order cannot be found using order ID 110-1990349-8205008.
	 *)
	 */
	public static function batchCheckSubmitErrhandle($submitQueueId,$msg){
		$SAA_obj = AmazonOrderSubmitQueue::findOne(['id'=>$submitQueueId]);
		$matchR=strpos($msg,"The items in your order cannot be found using order");
		if ($matchR) {
			if (!strpos($msg,$SAA_obj->order_id)) {
				echo "the other order submit error ,but set this order success ".$SAA_obj->order_id."\n";
				$SAA_obj->process_status=5;
				$SAA_obj->check_result=$msg;
				$SAA_obj->error_count=0;
				$SAA_obj->error_message="";
				$SAA_obj->update_time=time();
				$SAA_obj->check_finish_time=$SAA_obj->update_time;
				$SAA_obj->save(false);
				//如果是标记发货成功的话，这里需要设置oms的发货结果od_order_shipped
				if ($SAA_obj->api_action=="ShipAmazonOrder"){
					if (!empty($SAA_obj->addition_info)){
						$addition_info=json_decode($SAA_obj->addition_info,true);
						$osObj = OdOrderShipped::findOne($addition_info["order_shipped_id"]);
						if (!is_null($osObj)){
							$osObj->status = 1;
							$osObj->result = 'true';
							$osObj->errors = '';
							$osObj->updated = time();
							$osObj->lasttime = time();
							$osObj->save(false);
							//获取发货时间，并转换时间戳
							$timestring=json_decode($SAA_obj->parms,true);
							$deliverytime=strtotime($timestring["ship_date"]);
							echo "ret:success record format time:".$timestring["ship_date"]." timestamp:".$deliverytime."\n";
							// 更新订单 虚拟发货 状态 start
							OrderApiHelper::setOrderSyncShippedStatus($osObj->order_id,"C",$deliverytime);
							// 更新订单 虚拟发货 状态 end
						}
					}
				}
				return true;
			}
		}
		return false;
	}

}
?>