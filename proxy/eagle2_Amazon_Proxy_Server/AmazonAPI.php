<?php
require_once(dirname(__FILE__)."/AmazonProductAdsAPI.php"); 

class AmazonAPI{
	// TODO proxy dev account @XXX@ 
	const AMAZON_APPLICATION_NAME = "@XXX@ ";
	const AMAZON_APPLICATION_VERSION = 1;
	
	static $AMAZON_MWS_URL_CONFIG = array(
		'DE'=>"https://mws-eu.amazonservices.com",		
		'CA'=>"https://mws.amazonservices.ca",
		'CN'=>"https://mws.amazonservices.com.cn",
		'ES'=>"https://mws-eu.amazonservices.com",			
		'FR'=>"https://mws-eu.amazonservices.com",
		'IN'=>"https://mws.amazonservices.in",
		'IT'=>"https://mws-eu.amazonservices.com",
		'JP'=>"https://mws.amazonservices.jp",
		'UK'=>"https://mws-eu.amazonservices.com",
		'US'=>"https://mws.amazonservices.com",			
		'MX'=>"https://mws.amazonservices.com.mx",
		'AU'=>"https://mws.amazonservices.com.au",
        'BR'=>"https://mws.amazonservices.com",
        'AE'=>"https://mws.amazonservices.ae",
        'TR'=>"https://mws-eu.amazonservices.com",
		'SG'=>"https://mws-fe.amazonservices.com",
	);
			
	
	static $AMAZON_MWS_SUBMIT_FEED_URL_CONFIG = array(
		'DE'=>"https://mws.amazonservices.de",
		'CA'=>"https://mws.amazonservices.ca",
		'CN'=>"https://mws.amazonservices.com.cn",
		'ES'=>"https://mws.amazonservices.es",
		'FR'=>"https://mws.amazonservices.fr",
		'IN'=>"https://mws.amazonservices.in",
		'IT'=>"https://mws.amazonservices.it",
		'JP'=>"https://mws.amazonservices.jp",
		'UK'=>"https://mws.amazonservices.co.uk",
		'US'=>"https://mws.amazonservices.com",
		'MX'=>"https://mws.amazonservices.com.mx",
		'AU'=>"https://mws.amazonservices.com.au",
        'BR'=>"https://mws.amazonservices.com",
        'AE'=>"https://mws.amazonservices.ae",
        'TR'=>"https://mws-eu.amazonservices.com",
		'SG'=>"https://mws-fe.amazonservices.com",
	);	
	
	
	static $AMAZON_MARKETPLACE_REGION_CONFIG = array(
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
	
	
	static $AMAZON_FEED_CHARSET_CONFIG = array(
		'DE'=>"iso-8859-1",
		'CA'=>"iso-8859-1",
		'CN'=>"UTF-8",
		'ES'=>"iso-8859-1",
		'FR'=>"iso-8859-1",
		'IN'=>"iso-8859-1",
		'IT'=>"iso-8859-1",
		'JP'=>"Shift_JIS",
		'UK'=>"iso-8859-1",
		'US'=>"iso-8859-1",
		'MX'=>"iso-8859-1",
		'AU'=>"iso-8859-1",
        'BR'=>"iso-8859-1",
        'AE'=>"iso-8859-1",
        'TR'=>"iso-8859-1",
		'SG'=>"iso-8859-1",
	);
	/*	'orderurl' => "https://mws-eu.amazonservices.com/Orders/2011-01-01",
		'sumitfeedurl' => "https://mws.amazonservices.de",
		'producturl' => "https://mws-eu.amazonservices.com/Products/2011-10-01",
	 * */
	
	static $AMAZON_REGION;
	static $AMAZON_APPLICATION_NAME;
	static $AMAZON_APPLICATION_VERSION;
	
	static $AMAZON_ACCESS_KEY_ID;
	static $AMAZON_SECRET_ACCESS_KEY;
	
	static $AMAZON_MERCHANT_ID;
	static $AMAZON_MARKETPLACE_ID;
	static $AMAZON_MWS_AUTH_TOKEN;
	
	static $Amazon_SumitFeedUrl;
	static $AMAZON_ORDERURL;
	static $AMAZON_PRODUCTSURL;
	
	static $AMAZON_CONFIG;
	static $AMAZON_SERVICE;
	
	static $AMAZON_INVOICE_CACHE;
	static $AMAZON_ORDERS_LIST;
	static $AMAZON_RETRY_ORDERS_LIST;
	static $AMAZON_ORDERS_NEXTTOKEN;
	static $AMAZON_ORDER_ITEMS_NEXTTOKEN;
	static $AMAZON_FEED_CHARSET;
	
	static $AMAZON_API_CALL_TIMES;
	static $SCRIPT_INIT_CALL_TIMESTAMP;
	static $ALL_REQUEST_PARAMS_STR;
	
/************************************   PUBLIC START  ***************************************/
	static function set_amazon_config($config){
		self::$AMAZON_MERCHANT_ID = $config['merchant_id'];
		self::$AMAZON_MARKETPLACE_ID = $config['marketplace_id'];
		// dzt20190619 amazon要求不能再用这种调用方式
// 		self::$AMAZON_ACCESS_KEY_ID = $config['access_key_id'];
// 		self::$AMAZON_SECRET_ACCESS_KEY = $config['secret_access_key'];
	
		//get the region code by the market place id
		self::$AMAZON_REGION = self::$AMAZON_MARKETPLACE_REGION_CONFIG[$config['marketplace_id']];
		
		// 亚马逊要求执行新授权
		// 要求没有新授权的还跑旧授权
		if(!empty($config['mws_auth_token'])){
		    self::$AMAZON_MWS_AUTH_TOKEN = $config['mws_auth_token'];
		    
		    if(in_array(self::$AMAZON_REGION, array('CA', 'US', 'MX', 'BR'))){
				// us账号
				// TODO proxy dev account @XXX@ 
		        self::$AMAZON_ACCESS_KEY_ID = "@XXX@";
		        self::$AMAZON_SECRET_ACCESS_KEY = "@XXX@";
		    }else if(in_array(self::$AMAZON_REGION, array('DE', 'FR', 'ES', 'IT', 'UK', 'IN', 'TR', 'AE'))){
		        // 欧洲账号
				// TODO proxy dev account @XXX@ 
				self::$AMAZON_ACCESS_KEY_ID = "@XXX@";
		        self::$AMAZON_SECRET_ACCESS_KEY = "@XXX@";
		    }elseif(in_array(self::$AMAZON_REGION, array('JP', 'AU', 'SG'))){
				// TODO proxy dev account @XXX@ 
				self::$AMAZON_ACCESS_KEY_ID = "@XXX@";
		        self::$AMAZON_SECRET_ACCESS_KEY = "@XXX@";
		    }
		}
		
		//set order url and product url accordign to the region
		self::$AMAZON_ORDERURL = self::$AMAZON_MWS_URL_CONFIG[self::$AMAZON_REGION] . "/Orders/2013-09-01";
		self::$AMAZON_PRODUCTSURL = self::$AMAZON_MWS_URL_CONFIG[self::$AMAZON_REGION] . "/Products/2011-10-01";
		self::$Amazon_SumitFeedUrl = self::$AMAZON_MWS_SUBMIT_FEED_URL_CONFIG[self::$AMAZON_REGION] ;
		self::$AMAZON_FEED_CHARSET = self::$AMAZON_FEED_CHARSET_CONFIG[self::$AMAZON_REGION] ;
		
		//Initialize the class level cache 
		self::$AMAZON_ORDERS_LIST = array(); // init;
		self::$AMAZON_RETRY_ORDERS_LIST = array();//init
		self::$AMAZON_INVOICE_CACHE = array();
	}	
	
	
 static function GetAmazonOrderList($GetOrderDate='',$status='', $type = "MFN", $ToDateTime=''){
 	write_log("ST000:Ready to get order list from $GetOrderDate , status=$status ,ToDateTime=$ToDateTime . (GetAmazonOrderList)", "info");
 	self::_AutoIncludeAmazonConfig("Orders");
 	$request = new MarketplaceWebServiceOrders_Model_ListOrdersRequest();
 	$request->setSellerId(self::$AMAZON_MERCHANT_ID);
 	$request->setMWSAuthToken(self::$AMAZON_MWS_AUTH_TOKEN);
 	
 	//$request->setCreatedAfter(new DateTime($GetOrderDate, new DateTimeZone('UTC')));
 	// 传进来已经是utc时间，所以要对DateTime object 设置timezone而不是php默认的timezone�?
 	
 	/**
 	 * the format not right 2018-03-06T15:06:40+0000
 	 * the right format 2018-03-07T03:02:15+00:00
 	 * willage-2018-03-07
 	 */
 	// $fromDateTimeObj = new DateTime($GetOrderDate , new DateTimeZone('UTC')); 
 	// write_log("ST000:Ready to get order list from UTC Time ".$fromDateTimeObj->format(DateTime::ISO8601)."(GetAmazonOrderList)", "info");

 	write_log("ST000:Ready to get order list from UTC Time ".gmdate("c",strtotime($GetOrderDate))."(GetAmazonOrderList)", "info");
 	//$GetOrderDate,$ToDateTime is the start time and end time for fetching order list
 	// ISO 8601  日期和时间的组合表示�?
 	// 合并表示时，要在时间前面加一大写字母T，如要表示北京时�?004�?�?日下�?�?0�?秒，可以写成2004-05-03T17:30:08+08:00�?0040503T173008+08�?
//  	$request->setLastUpdatedAfter('2015-02-20T12:29:00Z'); // ok
//  	$request->setLastUpdatedAfter('2013-10-04T18:12:21');// ok

 	// $request->setLastUpdatedAfter($fromDateTimeObj->format(DateTime::ISO8601));
 	$request->setLastUpdatedAfter(gmdate("c",strtotime($GetOrderDate)));
 	if ($ToDateTime<>''){
 		// $toDateTimeObj = new DateTime($ToDateTime , new DateTimeZone('UTC'));
 		// write_log("ST000:Ready to get order list ToDateTime UTC Time ".$toDateTimeObj->format(DateTime::ISO8601)."(GetAmazonOrderList)", "info");
 		// $request->setLastUpdatedBefore($toDateTimeObj->format(DateTime::ISO8601));

 		write_log("ST000:Ready to get order list ToDateTime UTC Time ".gmdate("c",strtotime($ToDateTime))."(GetAmazonOrderList)", "info");
 		$request->setLastUpdatedBefore(gmdate("c",strtotime($ToDateTime)));
 	}
 	
 	// Set the marketplaces queried in this ListOrdersRequest
 	$request->setMarketplaceId(self::$AMAZON_MARKETPLACE_ID);
 	
 	// Set the Fulfillment Channel for this ListOrdersRequest (optional)
 	$typeArray = explode(",",$type);
 	if(!in_array("MFN", $typeArray) || !in_array("AFN", $typeArray)){
 		$request->setFulfillmentChannel($type);
 	}

 	// Set the order statuses for this ListOrdersRequest (optional)
 	// Unshipped and PartiallyShipped should be used together when filtering by OrderStatus in amazon
 	if (!isset($status) or $status == "" )
 		$status = "Unshipped";

 	$statusArray = explode(",",$status);
 	$request->setOrderStatus($statusArray);
//  	print_r($request);
//  	$request->setMaxResultsPerPage(5); // max = 100
 	
 	//get first page order list
 	$totalRetryCount = 0;
 	$errorCount = 0;
 	write_log("ST001:Ready to invoke _invokeListOrders . (GetAmazonOrderList)", "info");
 	$results = self::_invokeListOrders(self::$AMAZON_SERVICE, $request,false);
  	while(!$results['success'] and $errorCount < 5){// dzt20150721 改成sleep 3�?减少 retry 次数
 		$results = self::_invokeListOrders(self::$AMAZON_SERVICE, $request,false);
 		if (!$results['success']){
 			//lolo2014-07-19--  no need to retry according to errorCode returned from amazon
 			//errorCode:InvalidParameterValue --- that request parameter is wrong.Such as, fromdatetime for getorder is later than now  
 			//errorCode:InvalidAccessKeyId ---The AWS Access Key Id  provided does not exist in amazon
 			$skipRetryErrorCodeArr=array("InvalidParameterValue","InvalidAccessKeyId");
 			if (isset($results['errorCode']) and in_array($results['errorCode'],$skipRetryErrorCodeArr)) { 				
 				break; 
 			}  
 			$errorCount ++;
 			sleep(3);// dzt20150721 �?sleep 1 �?sleep 3 查看请求情况
 		}
 	}
 	
 	if($results['success'])
 		self::$AMAZON_API_CALL_TIMES ++;
 	
 	$totalRetryCount += $errorCount;
 	$errorCount = 0;
 	/* get the rest order list */
 	while(self::$AMAZON_ORDERS_NEXTTOKEN !="" and $errorCount < 15){//and $results['success'] // dzt20150721 改成sleep 3�?减少 retry 次数
 		$NTrequest = new MarketplaceWebServiceOrders_Model_ListOrdersByNextTokenRequest();
 		//set up merchant id
 		$NTrequest->setSellerId(self::$AMAZON_MERCHANT_ID);
 		$NTrequest->setMWSAuthToken(self::$AMAZON_MWS_AUTH_TOKEN);
 		//set up the next token
 		$NTrequest->setNextToken(self::$AMAZON_ORDERS_NEXTTOKEN);
 		// object or array of parameters
 		write_log("ST002:Ready to invoke _invokeListOrdersByNextToken for next token result . (GetAmazonOrderList)", "info");
 		$rtn = self::_invokeListOrdersByNextToken(self::$AMAZON_SERVICE, $NTrequest);
 		if ($rtn['success']){
			self::$AMAZON_API_CALL_TIMES ++;
			$results['order'] = array_merge($results['order'], $rtn['order']);
 		}
 		

 		if (! $rtn['success']) {
 			$errorCount++;
 			write_log("ST002E:Failed to _invokeListOrdersByNextToken for next token result, will retry. (GetAmazonOrderList)", "info");
 			sleep(3);// dzt20150721 �?sleep 1 �?sleep 3 查看请求情况
 		}
 	}
 	
 	$totalRetryCount += $errorCount;
 	$results['retryCount'] = $totalRetryCount;
 	$results['apiCallTimes'] = self::$AMAZON_API_CALL_TIMES;
 	
 	
	if (self::$AMAZON_ORDERS_NEXTTOKEN != '')
		$results['success'] = false;
 	/*
 	 *  _invokeListOrdersByNextToken returns
		array('order'=>$myOrders ,  'message'=>$message,'success'=>$success);
 	 * */
 	self::$AMAZON_ORDERS_LIST = $results['order'];
 	write_log("ST003:Done for listing orders. (GetAmazonOrderList)", "info");
 	
 	// total 运行情况log(除access_key,secret_key外的所有参数，访问Amazon api 成功次数  , totalRetryCount ,total runtime) 
 	// for 统计 (方便grep 不加空格)
 	$finalReportStr = "FinalReport GetOrderList:amazon_api_success_call_times=".self::$AMAZON_API_CALL_TIMES ;
 	$finalReportStr .= ",totalRetryCount=".$totalRetryCount;
 	$finalReportStr .= ",totalRunTime=".(time() - self::$SCRIPT_INIT_CALL_TIMESTAMP);
 	$finalReportStr .= ",orderNums=".count($results['order']);
 	$finalReportStr .= ",isGetSuccess=".($results['success']?1:0);
 	$finalReportStr .= ",allParams:".self::$ALL_REQUEST_PARAMS_STR;
 	$finalReportStr .= ",MerchantID=".self::$AMAZON_MERCHANT_ID;
 	$finalReportStr .= ",MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID;
 	write_log($finalReportStr , "info");
 	return $results;
 } // end of GetAmazonOrderList
  
 

static function GetAmazonOrderItems($orderid){
	
	$dsn = "mysql:host=localhost;dbname=proxy;charset=utf8";
	// TODO proxy mysql account
	$db = new PDO($dsn, "root","");
	
	
	write_log("ST000:Ready to get order item for $orderid . (GetAmazonOrderItems)", "info");
	self::_AutoIncludeAmazonConfig("Orders");
	$retryCount = 0;
	$totalRetryCount = 0;
	
	$rtn['success'] = false;
	$rtn['item'] = null;
	$rtn['message'] = '';
	
	$ItemRequest = new MarketplaceWebServiceOrders_Model_ListOrderItemsRequest();
	$ItemRequest->setSellerId(self::$AMAZON_MERCHANT_ID);
	$ItemRequest->setMWSAuthToken(self::$AMAZON_MWS_AUTH_TOKEN);
	$ItemRequest->setAmazonOrderId($orderid);
	
	write_log("ST001:Start to get order items for $orderid . (GetAmazonOrderItems)", "info");
	$return = self::_invokeListOrderItems(self::$AMAZON_SERVICE, $ItemRequest);
	while (! $return['success'] and $retryCount < 10 ){
		$return = self::_invokeListOrderItems(self::$AMAZON_SERVICE, $ItemRequest);
// 		$rtn = self::_getOrderItems(self::$AMAZON_SERVICE, $orderid);
		//array('item'=>$myOrders ,  'message'=>$message,'success'=>$success);
		
		if (! $return['success']){
			sleep(1);
			$retryCount ++;
		}
	}
	
	if($return['success'])
		self::$AMAZON_API_CALL_TIMES += 1;
	$totalRetryCount += $retryCount;
	$retryCount = 0;
	
	$rtn['item'] = $return['items'];
	$rtn['success'] = $return['success'];
	$rtn['message'] = $return['message'];
	
	/* get the rest order items  */
	while(self::$AMAZON_ORDER_ITEMS_NEXTTOKEN !="" and $retryCount < 30){//and $results['success']
		$NTrequest = new MarketplaceWebServiceOrders_Model_ListOrderItemsByNextTokenRequest();
		//set up merchant id
		$NTrequest->setSellerId(self::$AMAZON_MERCHANT_ID);
		$NTrequest->setMWSAuthToken(self::$AMAZON_MWS_AUTH_TOKEN);
		//set up the next token
		$NTrequest->setNextToken(self::$AMAZON_ORDER_ITEMS_NEXTTOKEN);
		// object or array of parameters
		write_log("ST002:Ready to invoke _invokeListOrderItemsByNextToken for next token result . (GetAmazonOrderItems)", "info");
		$return = self::_invokeListOrderItemsByNextToken(self::$AMAZON_SERVICE, $NTrequest);
		if(!defined('EOL')){
			define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');
		}
		$rtn['message'] .= EOL.$return['message'];
		if ($return['success']){
			self::$AMAZON_API_CALL_TIMES ++;
			$rtn['item'] = array_merge($rtn['item'], $return['items']);
		}
			
	
		if (! $return['success']) {
			$retryCount++;
			write_log("ST002E:Failed to _invokeListOrderItemsByNextToken for next token result, will retry. (GetAmazonOrderItems)", "info");
			sleep(1);
		}
	}
	
	$totalRetryCount += $retryCount;
	$rtn['retryCount'] = $totalRetryCount;
	$rtn['apiCallTimes'] = self::$AMAZON_API_CALL_TIMES;
	
	if (self::$AMAZON_ORDER_ITEMS_NEXTTOKEN != '')
		$rtn['success'] = false;

	write_log("ST003:Done for listing order items. (GetAmazonOrderItems)", "info");
	
	
	if (count($rtn['item'])>=1){
	   foreach($rtn['item'] as &$item){
	   //返回的是160*160的图片，这里作为缩略图来使用
	       list($ret,$smallImageUrl)= AmazonProductAdsAPI::getOneMediumImageUrlByASIN(self::$AMAZON_MARKETPLACE_ID,$item["ASIN"],$db);
	       if ($ret===false){
		       	$item["SmallImageUrl"]="";
		       	write_log("asin:".$item["ASIN"]." marketplaceId:".self::$AMAZON_MARKETPLACE_ID." error_message:".$smallImageUrl, "info");
		       	continue;	       	
	       }
	       write_log("asin:".$item["ASIN"]." image:".$smallImageUrl, "info");
		   write_log(" smallImageUrl:".print_r( $smallImageUrl,true), "info");
	       $item["SmallImageUrl"]=$smallImageUrl;
	  }
	}
	
	
	
	// total 运行情况log(除access_key,secret_key外的所有参数，访问Amazon api 成功次数  , totalRetryCount ,total runtime)
	// for 统计 (方便grep 不加空格)
	$finalReportStr = "FinalReport GetOrderItems:amazon_api_success_call_times=".self::$AMAZON_API_CALL_TIMES ;
	$finalReportStr .= ",totalRetryCount=".$totalRetryCount;
	$finalReportStr .= ",totalRunTime=".(time() - self::$SCRIPT_INIT_CALL_TIMESTAMP);
	$finalReportStr .= ",orderItemNums=".count($rtn['item']);
	$finalReportStr .= ",isGetSuccess=".($rtn['success']?1:0);
	$finalReportStr .= ",allParams:".self::$ALL_REQUEST_PARAMS_STR;
	$finalReportStr .= ",MerchantID=".self::$AMAZON_MERCHANT_ID;
	$finalReportStr .= ",MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID;
	write_log($finalReportStr , "info");
	return $rtn;
}

static function getProductTopOfferByAsin($marketplaceId,$asin,$Operation,$ResponseGroup){
	$ret = AmazonProductAdsAPI::getProductTopOfferByAsin($marketplaceId,$asin,$Operation,$ResponseGroup);
	return $ret;
}

static function getProductAttributesByAsin($marketplaceId,$asin,$Operation,$ResponseGroup){
	$ret = AmazonProductAdsAPI::getProductAttributesByAsin($marketplaceId,$asin,$Operation,$ResponseGroup);
	return $ret;
}


static function getOneOrder($orderid){

	write_log("ST000:Ready to get order for $orderid . (getOneOrder)", "info");
	self::_AutoIncludeAmazonConfig("Orders");
	$retryCount = 0;

	$OrderRequest = new MarketplaceWebServiceOrders_Model_GetOrderRequest();
	$OrderRequest->setSellerId(self::$AMAZON_MERCHANT_ID);
	$OrderRequest->setMWSAuthToken(self::$AMAZON_MWS_AUTH_TOKEN);
	$OrderRequest->setAmazonOrderId($orderid);

	write_log("ST001:Start to get order for $orderid . (getOneOrder)", "info");
	$return = self::_invokeGetOrder(self::$AMAZON_SERVICE, $OrderRequest);
	while (! $return['success'] and $retryCount < 10 ){
		$return = self::_invokeGetOrder(self::$AMAZON_SERVICE, $OrderRequest);
		// 		$rtn = self::_getOrderItems(self::$AMAZON_SERVICE, $orderid);
		//array('item'=>$myOrders ,  'message'=>$message,'success'=>$success);

		if (! $return['success']){
			sleep(1);
			$retryCount ++;
		}
	}

	if($return['success'])
		self::$AMAZON_API_CALL_TIMES += 1;
	$totalRetryCount += $retryCount;
	$retryCount = 0;

	$return['retryCount'] = $totalRetryCount;
	$return['apiCallTimes'] = self::$AMAZON_API_CALL_TIMES;
	write_log("ST003:Done for get order. (getOneOrder)", "info");


	self::$AMAZON_ORDERS_LIST = $return['order'];
	
	// total 运行情况log(除access_key,secret_key外的所有参数，访问Amazon api 成功次数  , totalRetryCount ,total runtime)
	// for 统计 (方便grep 不加空格)
	$finalReportStr = "FinalReport GetOrderList:amazon_api_success_call_times=".self::$AMAZON_API_CALL_TIMES ;
	$finalReportStr .= ",totalRetryCount=".$totalRetryCount;
	$finalReportStr .= ",totalRunTime=".(time() - self::$SCRIPT_INIT_CALL_TIMESTAMP);
	$finalReportStr .= ",orderNums=".count($return['order']);
	$finalReportStr .= ",isGetSuccess=".($return['success']?1:0);
	$finalReportStr .= ",allParams:".self::$ALL_REQUEST_PARAMS_STR;
	$finalReportStr .= ",MerchantID=".self::$AMAZON_MERCHANT_ID;
	$finalReportStr .= ",MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID;
	write_log($finalReportStr , "info");
	return $return;
}

static function ShipAmazonOrder($orderid, $items,$freight="" , $deliveryno="" , $FulfillmentDate){
	$rtn['success'] = false;
 	self::_AutoIncludeAmazonConfig("SubmitFeed");
 	$feed = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>#MerchantId#</MerchantIdentifier>
	</Header>
	<MessageType>OrderFulfillment</MessageType>
	<Message>
		<MessageID>1</MessageID>
		<OperationType>Update</OperationType>
		<OrderFulfillment>
			<AmazonOrderID>#OrderId#</AmazonOrderID>
			<FulfillmentDate>#FulfillmentDate#</FulfillmentDate>
			<FulfillmentData>
				<CarrierName>#Freight#</CarrierName>
				<ShippingMethod>Standard</ShippingMethod>
				<ShipperTrackingNumber>#ShipperTrackingNumber#</ShipperTrackingNumber>
			</FulfillmentData>#Items#
		</OrderFulfillment>
	</Message>
</AmazonEnvelope>
EOD;

 	//this is a formated dummy for items in this package
 	$feed_items_dummy = "
	<Item>
 		<AmazonOrderItemCode>#ItemCode#</AmazonOrderItemCode>
 		<Quantity>#ItemShipQty#</Quantity>
	</Item> 	
";
	//according to the items code and qty passed in , format the XML content 	
 	$feed_items = "";
 	foreach ($items as $anItem){
 		$tempStr =  str_ireplace('#ItemCode#',$anItem['ItemCode'],$feed_items_dummy) ;
 		$tempStr =  str_ireplace('#ItemShipQty#',$anItem['ItemShipQty'],$tempStr) ;
 		$feed_items .= $tempStr;
 	}
 	
 	$marketplaceIdArray = array("Id" => array(self::$AMAZON_MARKETPLACE_ID));
 
 	$feed = str_ireplace('#MerchantId#',self::$AMAZON_MERCHANT_ID,$feed) ;
 	$feed = str_ireplace('#OrderId#',$orderid,$feed) ;
 	$feed = str_ireplace('#Freight#',$freight,$feed) ;
 	$feed = str_ireplace('#FulfillmentDate#',$FulfillmentDate,$feed) ;
 	$feed = str_ireplace('#ShipperTrackingNumber#',$deliveryno,$feed) ;
 	$feed = str_ireplace('#Items#',$feed_items,$feed) ;
 	
 	write_log("ShipAmazonOrder feed:$feed", "info");
 	$feedHandle = @fopen('php://temp', 'rw+');//绑定到流�?
 	fwrite($feedHandle, $feed);//xml 文件写入流中
 	rewind($feedHandle);//将文件内部的位置指针重新指向一个流（数据流/文件）的开�?
 
 	$parameters = array (
 	  'Merchant' => self::$AMAZON_MERCHANT_ID,
 	  'MarketplaceIdList' => $marketplaceIdArray,
 	  'FeedType' => '_POST_ORDER_FULFILLMENT_DATA_', // 订单配送确认上传数�?
 	  'FeedContent' => $feedHandle,
 	  'PurgeAndReplace' => false,
 	  'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
 	);
 	rewind($feedHandle);//将文件内部的位置指针重新指向一个流（数据流/文件）的开�?
 	$request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);
 
 	
 	$retryCount = 0;
 	while (! $rtn['success'] and $retryCount < 10 ){
 		$rtn = self::_invokeSubmitFeed(self::$AMAZON_SERVICE, $request);
 		//array('item'=>$myOrders ,  'message'=>$message,'success'=>$success);
 	
 		if (! $rtn['success']){
 			write_log("Failed to _invokeSubmitFeed for order:".$orderid."message:".$rtn['message'].", will retry. (ShipAmazonOrder)", "info");
 			sleep(1);
 			$retryCount ++;
 		}else{
 			self::$AMAZON_API_CALL_TIMES ++;
 		}
 	}
 	
 	$rtn['apiCallTimes'] = self::$AMAZON_API_CALL_TIMES;
 	$rtn['retryCount'] = $retryCount;
 	$rtn['XMLFeedData'] = $feed;
 	
 	if($retryCount == 10){// dzt20151214 减少retry 次数
 		write_log("Failed to _invokeSubmitFeed for order:".$orderid." retry Count exceed 10 times. return:".print_r($rtn,true) , "error");
 	}
 	
 	// total 运行情况log(除access_key,secret_key外的所有参数，访问Amazon api 成功次数  , totalRetryCount ,total runtime)
	// for 统计 (方便grep 不加空格)
	$finalReportStr = "FinalReport ShipAmazonOrder: order:".$orderid.",submit_id:".$rtn['submit_id'].",amazon_api_success_call_times=".self::$AMAZON_API_CALL_TIMES ;
	$finalReportStr .= ",totalRetryCount=".$retryCount;
	$finalReportStr .= ",totalRunTime=".(time() - self::$SCRIPT_INIT_CALL_TIMESTAMP);
	$finalReportStr .= ",XMLFeedData=".$rtn['XMLFeedData'];
	$finalReportStr .= ",isPostSuccess=".($rtn['success']?1:0);
	$finalReportStr .= ",allParams:".self::$ALL_REQUEST_PARAMS_STR;
	$finalReportStr .= ",MerchantID=".self::$AMAZON_MERCHANT_ID;
	$finalReportStr .= ",MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID;
	write_log($finalReportStr , "info");
 	return $rtn;
} // end of ShipAmazonOrder


static function batchShipAmazonOrders($shipOrders){
	$rtn['success'] = false;
	self::_AutoIncludeAmazonConfig("SubmitFeed");
	$feed = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>#MerchantId#</MerchantIdentifier>
	</Header>
	<MessageType>OrderFulfillment</MessageType>
	#Message#
</AmazonEnvelope>
EOD;
	
$feed_order_dummy = <<<EOD
	<Message>
	<MessageID>#MessageID#</MessageID>
	<OperationType>Update</OperationType>
	<OrderFulfillment>
	<AmazonOrderID>#OrderId#</AmazonOrderID>
	<FulfillmentDate>#FulfillmentDate#</FulfillmentDate>
	<FulfillmentData>
	<CarrierName>#Freight#</CarrierName>
	<ShippingMethod>Standard</ShippingMethod>
	<ShipperTrackingNumber>#ShipperTrackingNumber#</ShipperTrackingNumber>
	</FulfillmentData>#Items#
	</OrderFulfillment>
	</Message>
EOD;
	
	$feed_orders = "";
	$messageId =1;
	foreach ($shipOrders as $order){
		$orderid = $order['order_id'];
		$items = $order['items'];
		$freight = $order['freight'];
		$deliveryno = $order['delivery_no'];
		$FulfillmentDate = $order['ship_date'];
		
		//this is a formated dummy for items in this package
		$feed_items_dummy = "
		<Item>
	 		<AmazonOrderItemCode>#ItemCode#</AmazonOrderItemCode>
	 		<Quantity>#ItemShipQty#</Quantity>
		</Item>
	";
		//according to the items code and qty passed in , format the XML content
		$feed_items = "";
		foreach ($items as $anItem){
			$tempStr =  str_ireplace('#ItemCode#',$anItem['ItemCode'],$feed_items_dummy) ;
			$tempStr =  str_ireplace('#ItemShipQty#',$anItem['ItemShipQty'],$tempStr) ;
			$feed_items .= $tempStr;
		}
		
		$marketplaceIdArray = array("Id" => array(self::$AMAZON_MARKETPLACE_ID));
		
		$tempOdStr = str_ireplace('#MessageID#',$messageId,$feed_order_dummy) ;
		$tempOdStr = str_ireplace('#OrderId#',$orderid,$tempOdStr) ;
		$tempOdStr = str_ireplace('#Freight#',$freight,$tempOdStr) ;
		$tempOdStr = str_ireplace('#FulfillmentDate#',$FulfillmentDate,$tempOdStr) ;
		$tempOdStr = str_ireplace('#ShipperTrackingNumber#',$deliveryno,$tempOdStr) ;
		$tempOdStr = str_ireplace('#Items#',$feed_items,$tempOdStr) ;
		$feed_orders .= $tempOdStr; 
		$messageId++;
	}
	
	$feed = str_ireplace('#MerchantId#',self::$AMAZON_MERCHANT_ID,$feed) ;
	$feed = str_ireplace('#Message#',$feed_orders,$feed) ;
	write_log("ShipAmazonOrder feed:$feed", "info");
	$feedHandle = @fopen('php://temp', 'rw+');//绑定到流�?
	fwrite($feedHandle, $feed);//xml 文件写入流中
	rewind($feedHandle);//将文件内部的位置指针重新指向一个流（数据流/文件）的开�?

	$parameters = array (
			'Merchant' => self::$AMAZON_MERCHANT_ID,
			'MarketplaceIdList' => $marketplaceIdArray,
			'FeedType' => '_POST_ORDER_FULFILLMENT_DATA_', // 订单配送确认上传数�?
			'FeedContent' => $feedHandle,
			'PurgeAndReplace' => false,
			'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
	);
	rewind($feedHandle);//将文件内部的位置指针重新指向一个流（数据流/文件）的开�?
	$request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);


	$retryCount = 0;
	while (! $rtn['success'] and $retryCount < 10 ){
		$rtn = self::_invokeSubmitFeed(self::$AMAZON_SERVICE, $request);
		//array('item'=>$myOrders ,  'message'=>$message,'success'=>$success);

		if (! $rtn['success']){
			write_log("Failed to _invokeSubmitFeed for order:".$orderid."message:".$rtn['message'].", will retry. (ShipAmazonOrder)", "info");
			sleep(1);
			$retryCount ++;
		}else{
			self::$AMAZON_API_CALL_TIMES ++;
		}
	}

	$rtn['apiCallTimes'] = self::$AMAZON_API_CALL_TIMES;
	$rtn['retryCount'] = $retryCount;
	$rtn['XMLFeedData'] = $feed;

	if($retryCount == 10){// dzt20151214 减少retry 次数
		write_log("Failed to _invokeSubmitFeed for order:".$orderid." retry Count exceed 10 times. return:".print_r($rtn,true) , "error");
	}

	// total 运行情况log(除access_key,secret_key外的所有参数，访问Amazon api 成功次数  , totalRetryCount ,total runtime)
	// for 统计 (方便grep 不加空格)
	$finalReportStr = "FinalReport ShipAmazonOrder: order:".$orderid.",submit_id:".$rtn['submit_id'].",amazon_api_success_call_times=".self::$AMAZON_API_CALL_TIMES ;
	$finalReportStr .= ",totalRetryCount=".$retryCount;
	$finalReportStr .= ",totalRunTime=".(time() - self::$SCRIPT_INIT_CALL_TIMESTAMP);
	$finalReportStr .= ",XMLFeedData=".$rtn['XMLFeedData'];
	$finalReportStr .= ",isPostSuccess=".($rtn['success']?1:0);
	$finalReportStr .= ",allParams:".self::$ALL_REQUEST_PARAMS_STR;
	$finalReportStr .= ",MerchantID=".self::$AMAZON_MERCHANT_ID;
	$finalReportStr .= ",MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID;
	write_log($finalReportStr , "info");
	return $rtn;
} // end of ShipAmazonOrder 
 
	
/*============================   refundAmazonOrderProduct  ============================*/	
static function refundAmazonOrderProduct($refund_order_info){	
	self::_AutoIncludeAmazonConfig('SubmitFeed');
	// step 1 init config 
	$i = 0;
	$feedMessage = "";
	
	$currency = $refund_order_info['Currency'];
	$orderid = $refund_order_info['AmazonOrderId'];
	foreach ($refund_order_info['Items'] as $anItem){
		$i++;
		$orderItemId = $anItem['OrderItemId'];
		$itemPrice = $anItem['ItemPrice'];
		$shipingPrice = $anItem['ShippingPrice'];
$feedMessage .= <<<EOD
<Message> 
  <MessageID>$i</MessageID> 
  <OrderAdjustment> 
   <AmazonOrderID>$orderid</AmazonOrderID> 
   <AdjustedItem> 
    <AmazonOrderItemCode>$orderItemId</AmazonOrderItemCode> 
    <AdjustmentReason>CustomerReturn</AdjustmentReason> 
    <ItemPriceAdjustments> 
     <Component> 
      <Type>Principal</Type> 
      <Amount currency="$currency">$itemPrice</Amount> 
     </Component> 
     <Component> 
      <Type>Shipping</Type> 
      <Amount currency="$currency">$shipingPrice</Amount> 
     </Component> 
    </ItemPriceAdjustments> 
   </AdjustedItem> 
  </OrderAdjustment> 
 </Message> 
EOD;
	} //end of  each item to be refunded
		
$feed = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
 <Header> 
  <DocumentVersion>1.01</DocumentVersion> 
  <MerchantIdentifier>#MerchantId#</MerchantIdentifier> 
 </Header> 
 <MessageType>OrderAdjustment</MessageType> 
 $feedMessage
</AmazonEnvelope> 
EOD;

		$marketplaceIdArray = array("Id" => array(self::$AMAZON_MARKETPLACE_ID));
		
		$feed = str_ireplace('#MerchantId#',self::$AMAZON_MERCHANT_ID,$feed) ;	
		$feed = str_ireplace('#OrderId#',$orderid,$feed) ;	
		$feedHandle = @fopen('php://temp', 'rw+');
		fwrite($feedHandle, $feed);
		rewind($feedHandle);
		$parameters = array (
		  'Merchant' => self::$AMAZON_MERCHANT_ID,
		  'MarketplaceIdList' => $marketplaceIdArray,
		  'FeedType' => '_POST_PAYMENT_ADJUSTMENT_DATA_', // 订单盘点上传数据
		  'FeedContent' => $feedHandle,
		  'PurgeAndReplace' => false,
		  'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
		);
		rewind($feedHandle);
		$request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);
 
		$rtn['success'] = false;
		$retryCount = 0;
		while (! $rtn['success'] and $retryCount < 30 ){
			$rtn = self::_invokeSubmitFeed(self::$AMAZON_SERVICE, $request);
			//	result containing array('exception'=>, 'submit_id'=>)
		
			if (! $rtn['success']){
				sleep(1);
				$retryCount ++;
			}
		}
		$rtn['retryCount'] = $retryCount;
		$rtn['XMLFeedData'] = $feed;

		return $rtn;
} //end of refundAmazonOrderProduct

/*============================   CancelEntireAmazonOrder  ============================*/
// must before shipped order
static function cancelEntireAmazonOrder($order_info){
	$orderid = $order_info['AmazonOrderId'];
	self::_AutoIncludeAmazonConfig('SubmitFeed');
	
	$i = 0;
	$Message = "";
	foreach ($order_info['Items'] as $row){
		$i++;
		$orderItemId = $row['OrderItemId'];
		
		//to fix the passed in itme id is 14244, but we need 014244
		while (strlen("".$orderItemId) < 14)
			$orderItemId = "0".$orderItemId;
		
$Message .= <<<EOD
<Message>  
        <MessageID>$i</MessageID>  
        <OrderAcknowledgement>  
           <AmazonOrderID>$orderid</AmazonOrderID>  
           <StatusCode>Failure</StatusCode>  
           <Item>  
               <AmazonOrderItemCode>$orderItemId</AmazonOrderItemCode>  
               <CancelReason>BuyerCanceled</CancelReason>
           </Item>  
        </OrderAcknowledgement>  
</Message> 
EOD;
	} //end of  each item 
		
//StatusCode Success or Failure
$feed = <<<EOD
<?xml version="1.0"?>  
<AmazonEnvelope xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"  
xsi:noNamespaceSchemaLocation="amzn-envelope.xsd">  
<Header>  
        <DocumentVersion>1.01</DocumentVersion>  
        <MerchantIdentifier>M_IDENTIFIER</MerchantIdentifier>  
</Header>  
<MessageType>OrderAcknowledgement</MessageType>  
 $Message
</AmazonEnvelope>
EOD;

	$marketplaceIdArray = array("Id" => array(self::$AMAZON_MARKETPLACE_ID));
		
	$feed = str_ireplace('#MerchantId#',self::$AMAZON_MERCHANT_ID,$feed) ;	
	
	$feedHandle = @fopen('php://temp', 'rw+');
	fwrite($feedHandle, $feed);
	rewind($feedHandle);
	$parameters = array (
	  'Merchant' => self::$AMAZON_MERCHANT_ID,
	  'MarketplaceIdList' => $marketplaceIdArray,
	  'FeedType' => '_POST_ORDER_ACKNOWLEDGEMENT_DATA_', // 订单确认上传数据
	  'FeedContent' => $feedHandle,
	  'PurgeAndReplace' => false,
	  'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
	);
	rewind($feedHandle);
	
	$request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);
	
	
	$rtn['success'] = false;
	$retryCount = 0;
	while (! $rtn['success'] and $retryCount < 30 ){
		$rtn = self::_invokeSubmitFeed(self::$AMAZON_SERVICE, $request); 
		//	result containing array('exception'=>, 'submit_id'=>)
	
		if (! $rtn['success']){
			sleep(1);
			$retryCount ++;
		}
	}
	$rtn['retryCount'] = $retryCount;
	$rtn['XMLFeedData'] = $feed;
	
	return $rtn;
}//end of CancelEntireAmazonOrder
	
	// get order list start .....
 
	static function getAmazonAFNOrder($status , $type = "AFN"  ,$GetOrderDate){
		self::_makeLog(" (".(__function__).")".date("Y-m-d h:i:s"). " :  Step 1  - start get FBA Order  ");
		self::_AutoIncludeAmazonConfig("Orders");
		
		 $request = new MarketplaceWebServiceOrders_Model_ListOrdersRequest();
		 $request->setSellerId(self::$AMAZON_MERCHANT_ID);
		 $request->setMWSAuthToken(self::$AMAZON_MWS_AUTH_TOKEN);
		 
		 // List all orders udpated after a certain date $site[0]['order_dlup']
		 $request->setCreatedAfter(new DateTime($GetOrderDate, new DateTimeZone('UTC')));
		 // Set the marketplaces queried in this ListOrdersRequest
		 $request->setMarketplaceId(self::$AMAZON_MARKETPLACE_ID);
		 
		 // Set the Fulfillment Channel for this ListOrdersRequest (optional)
		 $request->setFulfillmentChannel($type);

		 // Set the order statuses for this ListOrdersRequest (optional)
		 // Unshipped and PartiallyShipped should be used together when filtering by OrderStatus in amazon
		 if ($status == "Unshipped")
			$statusArray = array ($status, "PartiallyShipped");
		 else 
			$statusArray = array ($status);
		 $request->setOrderStatus($statusArray);
		 // $request->setMaxResultsPerPage(100); // max = 100
		 // $orders = self::_invokeListOrders(self::$AMAZON_SERVICE, $request );
		 self::_makeLog("(".(__function__).")".date("Y-m-d h:i:s")." : Step 2  - start get FBA Order ");
		 $orders = self::_invokeListOrders(self::$AMAZON_SERVICE, $request );
		 self::_makeLog("(".(__function__).")".date("Y-m-d h:i:s")." : Step 2.a - after _invokeListOrders ");
			/* get the rest order list */
		 while(self::$AMAZON_ORDERS_NEXTTOKEN !=""){
			$NTrequest = new MarketplaceWebServiceOrders_Model_ListOrdersByNextTokenRequest();
			//set up merchant id 
			$NTrequest->setSellerId(self::$AMAZON_MERCHANT_ID);
			$NTrequest->setMWSAuthToken(self::$AMAZON_MWS_AUTH_TOKEN);
			//set up the next token
			$NTrequest->setNextToken(self::$AMAZON_ORDERS_NEXTTOKEN);
			// object or array of parameters
			self::_invokeListOrdersByNextToken(self::$AMAZON_SERVICE, $NTrequest);
		 }
		 self::_makeLog("(".(__function__).")".date("Y-m-d h:i:s")." : Step 2.b - after _invokeListOrdersByNextToken ");
		$orders = array('order'=>self::$AMAZON_ORDERS_LIST , 
		'invoice'=>self::$AMAZON_INVOICE_CACHE ,
		'retryorder'=>self::$AMAZON_RETRY_ORDERS_LIST);
		 return $orders;
	}// end of getAmazonAFNOrder
	
	static function getFBAOrderInvoice($notInvoicedOrderId)		
{

    $newInvoiceId = $notInvoicedOrderId;
	$invoice =  self::GetAmazonInvoice($newInvoiceId);
	return array('newinvoiceid'=>$newInvoiceId,'invoice'=>$invoice);
}// end of get fba invoice 

	static function getMissOrder($orderlist){
		self::_AutoIncludeAmazonConfig("Orders");
		$request = new MarketplaceWebServiceOrders_Model_GetOrderRequest();
		$request->setSellerId(self::$AMAZON_MERCHANT_ID);
		$request->setMWSAuthToken(self::$AMAZON_MWS_AUTH_TOKEN);

		$request->setAmazonOrderId($orderlist);
		// object or array of parameters
		self::_invokeGetOrder(self::$AMAZON_SERVICE, $request);
		
		$order = array('order'=>self::$AMAZON_ORDERS_LIST , 'invoice'=>self::$AMAZON_INVOICE_CACHE);
		return $order;
	}//end of getMissOrder


	// get order list end .....

 	static function request_report(  $report_type , $startdate ='' , $enddate =''){
 		try{
			self::_AutoIncludeAmazonConfig("SubmitFeed");
			$time_zone = 'Europe/Paris';
			$marketplaceIdArray = array("Id" => array(self::$AMAZON_MARKETPLACE_ID));
			// _GET_FLAT_FILE_OPEN_LISTINGS_DATA_
			$parameters = array (
			  'Merchant' => self::$AMAZON_MERCHANT_ID,
			  'MarketplaceIdList' => $marketplaceIdArray,
			  'ReportType' => $report_type,
			  'ReportOptions' => 'ShowSalesChannel=true',
			);
			
			if (! empty($enddate)){
				$parameters['EndDate'] = new DateTime($enddate, new DateTimeZone($time_zone));
			}
			
	 		if (! empty($startdate)){
				$parameters['StartDate'] = new DateTime($startdate, new DateTimeZone($time_zone));
			}
			//write_log("ST100_1: $report_type $startdate $enddate (request_report)", "info");
			$request = new MarketplaceWebService_Model_RequestReportRequest($parameters);
			$request->setMerchant(self::$AMAZON_MERCHANT_ID);
			$request->setMarketplace(self::$AMAZON_MARKETPLACE_ID);
			$request->setReportType($report_type);
			
			//lzhl 2016-12-15
			if($report_type=='_GET_AFN_INVENTORY_DATA_')
				$request->setMarketplaceIdList($marketplaceIdArray);
			
			$request->setReportOptions($parameters['ReportOptions']);
			$request->setStartDate($parameters['StartDate']);
			$request->setEndDate($parameters['EndDate']);
		 	//print_r(self::$AMAZON_SERVICE);
			$result = self::_invokeRequestReport(self::$AMAZON_SERVICE, $request);
			
			return $result;
 		}catch(Exception $e) {
			echo $e->getMessage() ; 
	    }
	}// end of request_report
	
	static function retrieve_prod_report_id($list , $report_type ){
		self::_AutoIncludeAmazonConfig("SubmitFeed");
		$time_zone = 'Europe/Paris';
		
		foreach($list as $row){
			//step 1 check the request  active 
			$parameters = array (
			  'Merchant' => self::$AMAZON_MERCHANT_ID,
			  'ReportTypeList' => array('Type' => array( $report_type )),
			  'ReportRequestIdList' => array ( 'Id' => array ($row['request_id'])),
			);
			
			$request = new MarketplaceWebService_Model_GetReportRequestListRequest($parameters);
			$CheckRows = self::_invokeGetReportRequestList(self::$AMAZON_SERVICE, $request);
			
			$checkRow = $CheckRows['Info'][0]; 
			
			//status  neither in_progress or submitted  not send request again else resend.
			if ( $checkRow['ReportProcessingStatus'] == "_DONE_" ){
				$parameters = array (
				  'Merchant' => self::$AMAZON_MERCHANT_ID,
				  'AvailableToDate' => new DateTime('now', new DateTimeZone($time_zone)),
				  'AvailableFromDate' => new DateTime('-1 months', new DateTimeZone($time_zone)),
				  'Acknowledged' => false, 
				  'ReportTypeList' => array('Type'=>$report_type), 
				  'ReportRequestIdList' => array ( 'Id' => array ($row['request_id'])),
				);

				$request = new MarketplaceWebService_Model_GetReportListRequest($parameters);
				$rows = self::_invokeGetReportList(self::$AMAZON_SERVICE, $request);
				$result[] = $rows;
			}
		}
		return $result;
	
	}// end of retrieve_prod_all_report_id
	
	static function checkRequestResult( $report_type , $request_id){
		self::_AutoIncludeAmazonConfig("SubmitFeed");
		$parameters = array (
		  'Merchant' => self::$AMAZON_MERCHANT_ID,
		  'ReportTypeList' => array('Type' => array( $report_type )),
		  'ReportRequestIdList' => array ( 'Id' => array ($request_id)),
		);
		
		$request = new MarketplaceWebService_Model_GetReportRequestListRequest($parameters);
		$result = self::_invokeGetReportRequestList(self::$AMAZON_SERVICE, $request);
		return $result;
	}//end of checkRequestResult
	
	static function get_report_id(  $request_id,$report_type=''){
		self::_AutoIncludeAmazonConfig("SubmitFeed");
		$time_zone = 'Europe/Paris';
		$parameters = array (
		  'Merchant' => self::$AMAZON_MERCHANT_ID,
		  'AvailableToDate' => new DateTime('now', new DateTimeZone($time_zone)),
		  'AvailableFromDate' => new DateTime('-1 months', new DateTimeZone($time_zone)),
		  'Acknowledged' => false, 
		  //'ReportTypeList' => array('Type'=>$report_type), 
		  'ReportRequestIdList' => array ( 'Id' => array ($request_id)),
		);

		$request = new MarketplaceWebService_Model_GetReportListRequest($parameters);
		$results = self::_invokeGetReportList(self::$AMAZON_SERVICE, $request);
		return $results;
	}
	
	static function get_amazon_invoice_cache(){
		return self::$AMAZON_INVOICE_CACHE;
	}//end of get_amazon_invoice_cache
	

	static function get_submit_feed_result( $submit_id ){
		self::_AutoIncludeAmazonConfig("SubmitFeed");
		$time_zone = 'Europe/Paris';

		$handle =  @fopen('php://temp', 'rw+');//绑定到流�?     //replaced fopen($filename, 'w+');
			
		$request = new MarketplaceWebService_Model_GetFeedSubmissionResultRequest();
		$request->setMerchant(self::$AMAZON_MERCHANT_ID);
		$request->setFeedSubmissionId($submit_id);
		$request->setFeedSubmissionResult($handle);	 
	
		try {
    		$response = self::$AMAZON_SERVICE->getFeedSubmissionResult($request);
    		rewind($handle);
    		$responseStr = stream_get_contents($handle);
    		fclose($handle);
    		$rtn['data']  = simplexml_load_string($responseStr);
    		$rtn['success'] = true;
		} catch (MarketplaceWebService_Exception $ex) {
    		$rtn['message'] .=("Caught Exception: " . $ex->getMessage() . "<br>");			
    		$rtn['message'] .=("Response Status Code: " . $ex->getStatusCode() . "<br>");
			$rtn['statusCode']=$ex->getStatusCode();
    		$rtn['message'] .=("Error Code: " . $ex->getErrorCode() . "<br>");
			$rtn['errorCode']=$ex->getErrorCode();
    		$rtn['message'] .=("Error Type: " . $ex->getErrorType() . "<br>");
			$rtn['errorType']=$ex->getErrorType();
    		$rtn['message'] .=("Request ID: " . $ex->getRequestId() . "<br>");
    		$rtn['message'] .=("XML: " . $ex->getXML() . "<br>");
    		$rtn['message'] .=("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "<br>");
    		$rtn['data'] = "";
    		$rtn['success'] = false;
		}
		
		if($rtn['success'] == true)
			self::$AMAZON_API_CALL_TIMES ++;
		
		return $rtn;

	}//end of function get submit feed result
		
	/**--------------------------- products start ---------------------------**/
	//retrieve product info through asin from amazon website 
	static function getMatchingProduct($asinList, $return_type='xml'){
		self::_AutoIncludeAmazonConfig("Products");
		/* GetMatchingProductForId start
		$parameters = array(
			'MarketplaceId'=>self::$AMAZON_MARKETPLACE_ID,
			'SellerId'=>self::$AMAZON_MERCHANT_ID,
			'IdType'=>'SellerSKU',
			'IdList'=>array('Id'=>$asinList),
		);
		
		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest($parameters);
		return self::_invokeGetMatchingProductForId(self::$AMAZON_SERVICE, $request);
		GetMatchingProductForId end */
		/*
		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest();
		$request->setSellerId(self::$AMAZON_MERCHANT_ID);
		$request->setIdType('SellerSKU');
		$request->setIdList($skuList);
		*/
		// object or array of parameters
		$parameters = array(
			'MarketplaceId'=>self::$AMAZON_MARKETPLACE_ID,
			'SellerId'=>self::$AMAZON_MERCHANT_ID,
			'ASINList'=>array('ASIN'=>$asinList), 
		);
		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductRequest($parameters);

		if ($return_type == 'json'){
			$response = self::_invokeGetMatchingProduct(self::$AMAZON_SERVICE, $request);
			$response['result'] = parseNamespaceXml($response['result']);
		}else{
			$response = self::_invokeGetMatchingProduct(self::$AMAZON_SERVICE, $request);
		}
		
		return $response;
	
	}//end of getMatchingProduct 
	
	//retrieve product info through asin from amazon website
	// @todo support $return_type
	static function getMatchingProduct2($asinList, $return_type='xml'){
		self::_AutoIncludeAmazonConfig("Products");
		// object or array of parameters
		$parameters = array(
				'MarketplaceId'=>self::$AMAZON_MARKETPLACE_ID,
				'SellerId'=>self::$AMAZON_MERCHANT_ID,
				'ASINList'=>array('ASIN'=>$asinList),
		);
		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductRequest($parameters);
		$result = self::_invokeGetMatchingProduct2(self::$AMAZON_SERVICE, $request);
		
		return $result;
	}
	
	
	//retrieve product info through custom type id  from amazon website 
	static function getMatchingProductForId($IdList, $IdType, $return_type='xml'){
		self::_AutoIncludeAmazonConfig("Products");
		$parameters = array(
			'MarketplaceId'=>self::$AMAZON_MARKETPLACE_ID,
			'SellerId'=>self::$AMAZON_MERCHANT_ID,
			'IdType'=>$IdType,
			'IdList'=>array('Id'=>$IdList),
		);
		
		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest($parameters);
		if ($return_type == 'json'){
			$response = self::_invokeGetMatchingProductForId(self::$AMAZON_SERVICE, $request);
			if(!empty($response['result'])){
				$response['result'] = parseNamespaceXml($response['result']);
			}	
			else{
				$response['result'] = "no data";
			}
		}else{
			$response = self::_invokeGetMatchingProductForId(self::$AMAZON_SERVICE, $request);
		}
		
		
		/*
		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest();
		$request->setSellerId(self::$AMAZON_MERCHANT_ID);
		$request->setIdType('SellerSKU');
		$request->setIdList($skuList);
		*/
		// object or array of parameters
		
		
		return $response;
	
	}//end of getMatchingProductForId 
	
	
	//retrieve product info through custom type id  from amazon website
	// @todo support $return_type
	static function getMatchingProductForId2($IdList, $IdType, $return_type='xml'){
		self::_AutoIncludeAmazonConfig("Products");
		$parameters = array(
				'MarketplaceId'=>self::$AMAZON_MARKETPLACE_ID,
				'SellerId'=>self::$AMAZON_MERCHANT_ID,
				'IdType'=>$IdType,
				'IdList'=>array('Id'=>$IdList),
		);
		
		$request = new MarketplaceWebServiceProducts_Model_GetMatchingProductForIdRequest($parameters);
		$response = self::_invokeGetMatchingProductForId2(self::$AMAZON_SERVICE, $request);
		return $response;
		
	}//end of getMatchingProductForId2
	
	// retrieve the parent product categories that a product belongs to, based on SellerSKU.
	// @todo support $return_type
	static function GetProductCategoriesForSKU($SellerSKU, $return_type='xml'){
	    self::_AutoIncludeAmazonConfig("Products");
	
	    $request = new MarketplaceWebServiceProducts_Model_GetProductCategoriesForSKURequest();
	    $request->setMarketplaceId(self::$AMAZON_MARKETPLACE_ID);
	    $request->setSellerId(self::$AMAZON_MERCHANT_ID);
	    $request->setSellerSKU($SellerSKU);
	    
	    
	    $response = self::_invokeGetProductCategoriesForSKU(self::$AMAZON_SERVICE, $request);
	    return $response;
	
	}//end of getMatchingProductForId2
	
	
	
	/**--------------------------- products end ---------------------------**/
	
	static function GetAmazonInvoice($orderid){
		self::_makeLog("(".(__function__).")".date("Y-m-d h:i:s")." : Final - get Amazon invoice ");
		if (isset(self::$AMAZON_INVOICE_CACHE) && (self::$AMAZON_INVOICE_CACHE != null)){
			foreach (self::$AMAZON_INVOICE_CACHE as $invoice){
				if ($invoice['newinvoiceid'] == $orderid){
					$invoice[extra_discount_amount] = 0;
					$invoice[subtotal] = $invoice[grand_total] - $invoice[shipping_amount] + $invoice[discount_amount];
					// $invoice[grand_total] = $invoice[subtotal] + $invoice[shipping_amount] - $invoice[discount_amount];
					return $invoice;
				}
			}
		}
	
	}//end of GetAmazonInvoice
	
	
	/************************************   PUBLIC END   ***************************************/


	/************************************   PRIVATE START  ***************************************/
	static function _makeLog($data){
		$suffix = date("Ymd");
		$myFile = "ProxyAmazonLog$suffix.txt";
		// $data = date("Y-m-d h:i:s"). " : ".$data."";
		file_put_contents("amazon_log/".$myFile , $data , FILE_APPEND );
	}
	
	
	/*
	 different api needs include different php
	$type Orders default
	SubmitFeed
	Report
	*/
	static function _AutoIncludeAmazonConfig($type="Orders" ){
		// echo "<br>(".(__function__).") start at ".gmdate("Y-m-d H:i:s",time());
		$dir = dirname(__FILE__)."/amazon/amazon_".$type."/";
		self::_AutoIncludeByPath($dir);
	
		$dir = dirname(__FILE__)."/amazon/amazon_".$type."/Model/";
		self::_AutoIncludeByPath($dir);
	
		self::_setupAmazonDefaultConfig($type );
	
	} // end of AutoIncludeAmazonConfig
	
	static function _setupAmazonDefaultConfig($type="Orders"){
	
		self::$AMAZON_APPLICATION_NAME = self::AMAZON_APPLICATION_NAME;
		self::$AMAZON_APPLICATION_VERSION = self::AMAZON_APPLICATION_VERSION;
			
		if ($type == "SubmitFeed"){
			self::$AMAZON_CONFIG = array (
					'ServiceURL' => self::$Amazon_SumitFeedUrl,
					'ProxyHost' => null,
					'ProxyPort' => -1,
					'MaxErrorRetry' => 3,
			);
			self::$AMAZON_SERVICE = new MarketplaceWebService_Client(
					self::$AMAZON_ACCESS_KEY_ID,
					self::$AMAZON_SECRET_ACCESS_KEY,
					self::$AMAZON_CONFIG,
					self::$AMAZON_APPLICATION_NAME,
					self::$AMAZON_APPLICATION_VERSION
			);
	
		}else if ($type == "Orders"){
			self::$AMAZON_API_CALL_TIMES = 0;
			self::$AMAZON_CONFIG = array (
					'ServiceURL' => self::$AMAZON_ORDERURL,
					'ProxyHost' => null,
					'ProxyPort' => -1,
					'MaxErrorRetry' => 3,
			);
	
			self::$AMAZON_SERVICE = new MarketplaceWebServiceOrders_Client(
					self::$AMAZON_ACCESS_KEY_ID,
					self::$AMAZON_SECRET_ACCESS_KEY,
					self::$AMAZON_APPLICATION_NAME,
					self::$AMAZON_APPLICATION_VERSION,
					self::$AMAZON_CONFIG);
		}else if ($type == "Products"){
			self::$AMAZON_CONFIG = array (
					'ServiceURL' => self::$AMAZON_PRODUCTSURL,
					'ProxyHost' => null,
					'ProxyPort' => -1,
					'MaxErrorRetry' => 3,
			);
				
			self::$AMAZON_SERVICE = new MarketplaceWebServiceProducts_Client(
					self::$AMAZON_ACCESS_KEY_ID,
					self::$AMAZON_SECRET_ACCESS_KEY,
					self::$AMAZON_APPLICATION_NAME,
					self::$AMAZON_APPLICATION_VERSION,
					self::$AMAZON_CONFIG);
		}
	}//end of _setupAmazonDefaultConfig
	
	static function _AutoIncludeByPath($dir){
		if (is_dir($dir)) {
			if ($dh = opendir($dir)) {
				while (($file = readdir($dh)) !== false) {
					// not include client.php~
					if ($file!="." && $file!=".." && is_file($dir.$file) && (strrpos($file,'.php~') <= 0 ) ) {
				include_once ($dir.$file);
					}
				}
				closedir($dh);
			}
		}
	} // end of _AutoIncludeByPath
	
	
	static function _invokeSubmitFeed(MarketplaceWebService_Interface $service, $request)
	{	$submit_id='';
		$result['message'] = "";
		try {
			$response = $service->submitFeed($request);
	
			if ($response->isSetSubmitFeedResult()) {
				$submitFeedResult = $response->getSubmitFeedResult();
				if ($submitFeedResult->isSetFeedSubmissionInfo()) {
					 $feedSubmissionInfo = $submitFeedResult->getFeedSubmissionInfo();
                     if ($feedSubmissionInfo->isSetFeedSubmissionId()) 
                      {
                          $submit_id = $feedSubmissionInfo->getFeedSubmissionId()  ;
                      }
				}
			}
			if ($response->isSetResponseMetadata()) {
					
			}
			$result['exception'] =  0;
			$result['success'] = true;
			// echo("            ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "");
		} catch (MarketplaceWebService_Exception $ex) {
			//do not make log if it is banned by service provider
			/*
			echo("Caught Exception: " . $ex->getMessage() . "");
			echo("Response Status Code: " . $ex->getStatusCode() . "");
			echo("Error Code: " . $ex->getErrorCode() . "");
			echo("Error Type: " . $ex->getErrorType() . "");
			echo("Request ID: " . $ex->getRequestId() . "");
			*/
			$result['exception'] =  1;
			$result['success'] = false;
			$result['message'] = "Caught Exception: " . $ex->getMessage();
		}
		$result['submit_id'] = $submit_id;
		return $result;
	} // end of _invokeSubmitFeed
	
	//only update all item
	static function post_prods_inventory($prods ){
		self::_AutoIncludeAmazonConfig("SubmitFeed" );
	//start to format the product message XML containing all product sku and qty
		$i = 0;	
		$prods_message = "";
		$tempc = 0;
		foreach ($prods as $prod){
			$tempc ++;
			$i++;
			$prods_message .= "
			<Message>
			<MessageID>$i</MessageID>
			<OperationType>Update</OperationType>
			<Inventory>
			<SKU>".$prod['sku']."</SKU>
				<Quantity>".$prod['qty']."</Quantity>
				</Inventory>
			</Message>
			";
		}//end of each product
	
		$feed = <<<EOD
<?xml version="1.0" encoding="UTF-8"?>
<AmazonEnvelope xsi:noNamespaceSchemaLocation="amzn-envelope.xsd" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
	<Header>
		<DocumentVersion>1.01</DocumentVersion>
		<MerchantIdentifier>#MerchantId#</MerchantIdentifier>
	</Header>
	<MessageType>Inventory</MessageType>
	$prods_message
</AmazonEnvelope>
EOD;
	
		$marketplaceIdArray = array("Id" => array(self::$AMAZON_MARKETPLACE_ID));
	
		$feed = str_ireplace('#MerchantId#',self::$AMAZON_MERCHANT_ID,$feed) ;
		$feedHandle = @fopen('php://temp', 'rw+');
		fwrite($feedHandle, $feed);
		rewind($feedHandle);
		$parameters = array (
			'Merchant' => self::$AMAZON_MERCHANT_ID,
			'MarketplaceIdList' => $marketplaceIdArray,
			'FeedType' => '_POST_INVENTORY_AVAILABILITY_DATA_', // 库存上传数据
			'FeedContent' => $feedHandle,
			'PurgeAndReplace' => false,
			'ContentMd5' => base64_encode(md5(stream_get_contents($feedHandle), true)),
			);
		rewind($feedHandle);
		$request = new MarketplaceWebService_Model_SubmitFeedRequest($parameters);
		$got_exception = true;
		$retry = 0;
		while ( $got_exception and $retry < 5) {
			// send request
			$result = self::_invokeSubmitFeed(self::$AMAZON_SERVICE, $request);
			//result containing array('exception'=>, 'submit_id'=>)
			$got_exception = $result['exception'];
			if ($got_exception)
			{	sleep(10); // prevent frequent request
				$retry ++;
			}
		} //end of while
		
		$result['success'] =  ! $result['exception'];
		//result containing array('exception'=>, 'submit_id'=>'A1232', 'success'=>true)
		return $result; 
	}// end of _post_one_prod_inventory
	
	
static function _invokeRequestReport(MarketplaceWebService_Interface $service, $request)
{	$result['success'] = true;
	$result['message'] = "";
	try {
		$response = $service->requestReport($request);
		if ($response->isSetRequestReportResult()) {
			$requestReportResult = $response->getRequestReportResult();

			if ($requestReportResult->isSetReportRequestInfo()) {
			
				$reportRequestInfo = $requestReportResult->getReportRequestInfo();
				if ($reportRequestInfo->isSetReportRequestId())
				{
					$result['ReportRequestId'] = $reportRequestInfo->getReportRequestId() ;
				}
				if ($reportRequestInfo->isSetReportType())
				{
					$result['ReportType'] = $reportRequestInfo->getReportType();
				}
				if ($reportRequestInfo->isSetStartDate())
				{
					$result['StartDate'] = $reportRequestInfo->getStartDate()->format("Y-m-d H:i:s");
				}
				if ($reportRequestInfo->isSetEndDate())
				{
					$result['EndDate'] = $reportRequestInfo->getEndDate()->format("Y-m-d H:i:s");
				}
				if ($reportRequestInfo->isSetSubmittedDate())
				{
					$result['SubmittedDate'] = $reportRequestInfo->getSubmittedDate()->format("Y-m-d H:i:s");
				}
				if ($reportRequestInfo->isSetReportProcessingStatus())
				{
					$result['ReportProcessingStatus'] = $reportRequestInfo->getReportProcessingStatus();
				}
			}
		}
		if ($response->isSetResponseMetadata()) {
			$responseMetadata = $response->getResponseMetadata();
			if ($responseMetadata->isSetRequestId())
			{
				$result['RequestId'] = $responseMetadata->getRequestId();
			}
		}
		$result['ResponseHeaderMetadata'] = $response->getResponseHeaderMetadata();
		
	} catch (MarketplaceWebService_Exception $ex) {
		$result['success'] = false ;
		$result['message'] .= ("Caught Exception: " . $ex->getMessage() . "");
		$result['message'] .= ("Response Status Code: " . $ex->getStatusCode() . "");
		$result['message'] .= ("Error Code: " . $ex->getErrorCode() . "");
		$result['message'] .= ("Error Type: " . $ex->getErrorType() . "");
		$result['message'] .= ("Request ID: " . $ex->getRequestId() . "");
		$result['message'] .= ("XML: " . $ex->getXML() . "");
		$result['message'] .= ("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "");
	}
	
	return $result;
}
	
	static function _invokeGetReport(MarketplaceWebService_Interface $service, $request){
		try {
		    ini_set('memory_limit','256M');
			$response = $service->getReport($request);
			$memUsed = floor (memory_get_usage() / 1024 / 1024);
			$comment =  "_invokeGetReport at ".date('Y-m-d H:i:s');
			$comment .= " - RAM Used: ".$memUsed."M";
			write_log($comment,"info");
			if ($response->isSetGetReportResult()) {
				$getReportResult = $response->getGetReportResult();
				if ($getReportResult->isSetContentMd5()) {
					$result['ContentMd5'] = $getReportResult->getContentMd5();
				}
			}
			if ($response->isSetResponseMetadata()) {
				$responseMetadata = $response->getResponseMetadata();
				if ($responseMetadata->isSetRequestId())
				{
					$result['RequestId'] = $responseMetadata->getRequestId();
				}
			}
			$result['Contents'] = stream_get_contents($request->getReport());
			$result['ResponseHeaderMetadata'] = $response->getResponseHeaderMetadata();
			$result['success'] = true;
			
			
			if (self::$AMAZON_FEED_CHARSET != "utf-8")
			$result['Contents'] = iconv(self::$AMAZON_FEED_CHARSET,"utf-8",$result['Contents']);
			
			
			//test kh start
			/*
			$fp=fopen("test_json.txt","w");
		$data=$result['Contents'];
		echo "<br> data : <br>".print_r($data,true);
		fwrite($fp,$data."\n");
		$afterJsonEn=json_encode($data,JSON_UNESCAPED_UNICODE);
		fwrite($fp,$afterJsonEn."\n");
		$afterJsonDe=json_decode($afterJsonEn,true);
		fwrite($fp,$afterJsonDe."\n");
		fclose($fp);
		echo "<br> *********************** <br>";
		*/
		//test kh end
		} catch (MarketplaceWebService_Exception $ex) {
			$result['success'] = false;
			$result['message'] = "";
			$result['message'] .= ("Caught Exception: " . $ex->getMessage() . "");
			$result['message'] .= ("Response Status Code: " . $ex->getStatusCode() . "");
			$result['message'] .= ("Error Code: " . $ex->getErrorCode() . "");
			$result['message'] .= ("Error Type: " . $ex->getErrorType() . "");
			$result['message'] .= ("Request ID: " . $ex->getRequestId() . "");
			$result['message'] .= ("XML: " . $ex->getXML() . "");
			$result['message'] .= ("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "");
		}
		
		return $result;
	}//end of _invokeGetReport
	
	static function _invokeGetReportRequestList(MarketplaceWebService_Interface $service, $request)
	{   $rtn['success'] = true;
		$rtn['message'] = '';
		try {
			$response = $service->getReportRequestList($request);
			if ($response->isSetGetReportRequestListResult()) {
				$getReportRequestListResult = $response->getGetReportRequestListResult();
				if ($getReportRequestListResult->isSetNextToken())
				{
					$rows['NextToken'] = $getReportRequestListResult->getNextToken();
				}
				if ($getReportRequestListResult->isSetHasNext())
				{
					$rows['HasNext'] = $getReportRequestListResult->getHasNext();
				}
				$reportRequestInfoList = $getReportRequestListResult->getReportRequestInfoList();
				foreach ($reportRequestInfoList as $reportRequestInfo) {
					if ($reportRequestInfo->isSetReportRequestId())
					{
						$row['ReportRequestId'] = $reportRequestInfo->getReportRequestId();
					}
					if ($reportRequestInfo->isSetReportType())
					{
						$row['ReportType'] = $reportRequestInfo->getReportType();
					}
					if ($reportRequestInfo->isSetStartDate())
					{
						$row['StartDate'] = $reportRequestInfo->getStartDate()->format("Y-m-d H:i:s");
					}
					if ($reportRequestInfo->isSetEndDate())
					{
						$row['EndDate'] = $reportRequestInfo->getEndDate()->format("Y-m-d H:i:s");
					}
					if ($reportRequestInfo->isSetSubmittedDate())
					{
						$row['SubmittedDate'] = $reportRequestInfo->getSubmittedDate()->format("Y-m-d H:i:s");
					}
					if ($reportRequestInfo->isSetReportProcessingStatus())
					{
						$row['ReportProcessingStatus'] =  $reportRequestInfo->getReportProcessingStatus();
					}
	
					$rows['Info'][] = $row;
				}  // end of foreach
			}
			if ($response->isSetResponseMetadata()) {
				$responseMetadata = $response->getResponseMetadata();
				if ($responseMetadata->isSetRequestId())
				{
					$rows['RequestId'] = $responseMetadata->getRequestId();
				}
			}
			$rows['ResponseHeaderMetadata'] = $response->getResponseHeaderMetadata();
			 
		} catch (MarketplaceWebService_Exception $ex) {
			$rtn['success'] = false;
			$rtn['message'] .= ("Caught Exception: " . $ex->getMessage() . "");
			$rtn['message'] .= ("Response Status Code: " . $ex->getStatusCode() . "");
			$rtn['message'] .= ("Error Code: " . $ex->getErrorCode() . "");
			$rtn['message'] .= ("Error Type: " . $ex->getErrorType() . "");
			$rtn['message'] .= ("Request ID: " . $ex->getRequestId() . "");
			$rtn['message'] .= ("XML: " . $ex->getXML() . "");
			$rtn['message'] .= ("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "");
			$rows['Info'] = '';
		}
		
		$rtn['report'] = $rows['Info'];
		return $rtn;
		
	}//end of _invokeGetReportRequestList
	
	
	static function _invokeGetReportList(MarketplaceWebService_Interface $service, $request)
{		$rtn['success'] = true;
		$rtn['message'] = '';
		try {
			$response = $service->getReportList($request);
			if ($response->isSetGetReportListResult()) {
				$getReportListResult = $response->getGetReportListResult();
				if ($getReportListResult->isSetNextToken())
				{
					$rows['NextToken'] = $getReportListResult->getNextToken();
				}
				if ($getReportListResult->isSetHasNext())
				{
					$rows['HasNext'] = $getReportListResult->getHasNext();
				}
				$reportInfoList = $getReportListResult->getReportInfoList();
				foreach ($reportInfoList as $reportInfo) {
					if ($reportInfo->isSetReportId())
					{
						$row['ReportId'] = $reportInfo->getReportId();
					}
					if ($reportInfo->isSetReportType())
					{
						$row['ReportType'] =  $reportInfo->getReportType();
					}
					if ($reportInfo->isSetReportRequestId())
					{
						$row['ReportRequestId'] = $reportInfo->getReportRequestId();
					}
					if ($reportInfo->isSetAvailableDate())
					{
						$row['AvailableDate'] = $reportInfo->getAvailableDate()->format("Y-m-d H:i:s");
					}
					if ($reportInfo->isSetAcknowledged())
					{
						$row['Acknowledged'] = $reportInfo->getAcknowledged();
					}
					if ($reportInfo->isSetAcknowledgedDate())
					{
						$row['AcknowledgedDate'] = $reportInfo->getAcknowledgedDate()->format("Y-m-d H:i:s");
					}
					$rows['Info'][] = $row;
				} // end of foreach
			}
			if ($response->isSetResponseMetadata()) {
				$responseMetadata = $response->getResponseMetadata();
				if ($responseMetadata->isSetRequestId())
				{
					$rows['RequestId'] = $responseMetadata->getRequestId();
				}
			}
			$rows['ResponseHeaderMetadata'] = $response->getResponseHeaderMetadata();

		} catch (MarketplaceWebService_Exception $ex) {
			$rtn['success'] = false;
			$rtn['message'] .= ("Caught Exception: " . $ex->getMessage() . "");
			$rtn['message'] .= ("Response Status Code: " . $ex->getStatusCode() . "");
			$rtn['message'] .= ("Error Code: " . $ex->getErrorCode() . "");
			$rtn['message'] .= ("Error Type: " . $ex->getErrorType() . "");
			$rtn['message'] .= ("Request ID: " . $ex->getRequestId() . "");
			$rtn['message'] .= ("XML: " . $ex->getXML() . "");
			$rtn['message'] .= ("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "");
			$rows['Info'] = "";
		}
		
		$rtn['report'] = $rows['Info'];
		return $rtn;
		
	}//end of _invokeGetReportList
	
	
	
	
	static function retrieve_report_result($reportId){
		// step 1  init amazon config
		self::_AutoIncludeAmazonConfig("SubmitFeed");
	
		// step 2 initilize return values
		$rtn['success'] = true;
		$rtn['message'] = "";
		
		// step 3 get the reportId
		if (trim($reportId) == "") {
			$rtn['success'] = false;
			$rtn['message'] = "no exist Report ID";
			return $rtn;
		}
		// step 4  retrieve  products' list from amazon
		$parameters = array (
			'Merchant' => self::$AMAZON_MERCHANT_ID,
			'Report' => @fopen('php://memory', 'rw+'),
			'ReportId' => $reportId,
		);
		
	    $request = new MarketplaceWebService_Model_GetReportRequest($parameters);
	    
	    	
	    $result = self::_invokeGetReport(self::$AMAZON_SERVICE, $request);
		
		return $result;
	}//end of _retrieve_prod_all_list

	
	static function array_remove_empty(&$arr, $trim = true){
		foreach ($arr as $key => $value) {
			if (is_array($value)) {
				self::array_remove_empty($arr[$key]);
			} else {
				$value = trim($value);
				if ($value == '') {
					unset($arr[$key]);
				} elseif ($trim) {
					$arr[$key] = $value;
				}
			}
		}
	}  //end of array_remove_empty
	
	static function _invokeGetOrder(MarketplaceWebServiceOrders_Interface $service, $request)
	{
		$message="";
		$success = true;
		$myOrders = array();
		try {
			$response = $service->getOrder($request);
			if ($response->isSetGetOrderResult()) {
				$getOrderResult = $response->getGetOrderResult();
				if ($getOrderResult->isSetOrders()) {
					$orderList = $getOrderResult->getOrders();
// 					$orderList = $orders->getOrder(); // for v2013-09-01 没有这个api 
					foreach ($orderList as $order) {
						//start to format the returned order into arrayInfo format
						$myOrder = self::_formatAmazonOrderInfo($order);
						$myOrders[] = $myOrder;
					}//end of pharsing each order
				}
			}
		} 
		// dzt20160712 comments 添加下面返回
// 		catch (MarketplaceWebServiceOrders_Exception $ex) {
// 			// @todo some error logic
// 			return array();
// 		}
		
		catch (MarketplaceWebServiceOrders_Exception $e){
			$myOrders = array();
			$success = false;
			$message = 'E1002:Failed to call amazon interface listOrders. Amz returns message:'.$e->getMessage()." errorCode:".$e->getErrorCode();
			write_log("ST002_1: $message . (_invokeListOrders) MerchantID=".self::$AMAZON_MERCHANT_ID." MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID ." fromDateTime=".$request->getLastUpdatedAfter()." toDateTime=".$request->getLastUpdatedBefore(), "error");
			write_log("ST002_1: $message . (_invokeListOrders)", "info");
			return array('order'=>$myOrders , 'message'=>$message,'success'=>$success,'errorCode'=>$e->getErrorCode());
		} //lolo2014-07-29 end
		catch (Exception $e){
			$myOrders = array();
			$success = false;
			$message = 'E1002:Failed to call amazon interface listOrders. Amz returns:'.$e->getMessage();
			write_log("ST002_1: $message . (_invokeListOrders) MerchantID=".self::$AMAZON_MERCHANT_ID." MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID ." fromDateTime=".$request->getLastUpdatedAfter()." toDateTime=".$request->getLastUpdatedBefore(), "error");
			write_log("ST002_1: $message . (_invokeListOrders)", "info");
		}
		
		return array('order'=>$myOrders , 'message'=>$message,'success'=>$success);
	}//end of invokeGetOrder


	/**
	 * List Orders Action Sample
	 * ListOrders can be used to find orders that meet the specified criteria.
	 *
	 * @param MarketplaceWebServiceOrders_Interface $service instance of MarketplaceWebServiceOrders_Interface
	 * @param mixed $request MarketplaceWebServiceOrders_Model_ListOrders or array of parameters
	 */
	
	static function _invokeListOrders(MarketplaceWebServiceOrders_Interface $service, $request ,  $IsGetItem=true){
		global $AMAZON_INVOICE_CACHE  ,$AMAZON_Delivery_Total , $AMAZON_Item_Total;
		$message="";
		$success = true;
		$myOrders = array();
		try{
			$response = $service->listOrders($request);
			if ($response->isSetListOrdersResult()) {
				$listOrdersResult = $response->getListOrdersResult();
				if ($listOrdersResult->isSetNextToken())
				{
					self::$AMAZON_ORDERS_NEXTTOKEN = $listOrdersResult->getNextToken()  ;
				}else
					self::$AMAZON_ORDERS_NEXTTOKEN = "";
	
				if ($listOrdersResult->isSetCreatedBefore())
				{
					// echo("                    " . $listOrdersResult->getCreatedBefore() . "<br>");
				}
				if ($listOrdersResult->isSetLastUpdatedBefore())
				{
					// echo("                    " . $listOrdersResult->getLastUpdatedBefore() . "<br>");
				}
				if ($listOrdersResult->isSetOrders()) {
					// 	here is foreach
					$orderList = $listOrdersResult->getOrders();
// 					$orderList = $orders->getOrder(); // for v2013-09-01 没有这个api 

					foreach ($orderList as $order) {
						//start to format the returned order into arrayInfo format
						$myOrder = self::_formatAmazonOrderInfo($order);	
						$myOrders[] = $myOrder;
						 
					} // end of foreach order
				}//end of got orders from result
			} // end of got result from amazon server
		}  //lolo2014-07-29 begin
		catch (MarketplaceWebServiceOrders_Exception $e){
			$myOrders = array();
			$success = false;
			$message = 'E1002:Failed to call amazon interface listOrders. Amz returns message:'.$e->getMessage()." errorCode:".$e->getErrorCode();
			write_log("ST002_1: $message . (_invokeListOrders) MerchantID=".self::$AMAZON_MERCHANT_ID." MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID ." fromDateTime=".$request->getLastUpdatedAfter()." toDateTime=".$request->getLastUpdatedBefore(), "error");
			write_log("ST002_1: $message . (_invokeListOrders)", "info");
			return array('order'=>$myOrders , 'message'=>$message,'success'=>$success,'errorCode'=>$e->getErrorCode());
		} //lolo2014-07-29 end
		catch (Exception $e){
			$myOrders = array();
			$success = false;
			$message = 'E1002:Failed to call amazon interface listOrders. Amz returns:'.$e->getMessage();
			write_log("ST002_1: $message . (_invokeListOrders) MerchantID=".self::$AMAZON_MERCHANT_ID." MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID ." fromDateTime=".$request->getLastUpdatedAfter()." toDateTime=".$request->getLastUpdatedBefore(), "error");
			write_log("ST002_1: $message . (_invokeListOrders)", "info");
		}
		return array('order'=>$myOrders , 'message'=>$message,'success'=>$success);
	}//End of Function
	
	
	static function _invokeListOrdersByNextToken(MarketplaceWebServiceOrders_Interface $service, $request){
		$message="";
		$success = true;
		//sleep(60);
		try {
			$response = $service->listOrdersByNextToken($request);
			 
			if ($response->isSetListOrdersByNextTokenResult()) {
				$listOrdersByNextTokenResult = $response->getListOrdersByNextTokenResult();
				if ($listOrdersByNextTokenResult->isSetNextToken())
				{
					self::$AMAZON_ORDERS_NEXTTOKEN = $listOrdersByNextTokenResult->getNextToken();
				}else{
					self::$AMAZON_ORDERS_NEXTTOKEN = "";
				}
				if ($listOrdersByNextTokenResult->isSetCreatedBefore())
				{
					// echo("                CreatedBefore");
					// echo("                    " . $listOrdersByNextTokenResult->getCreatedBefore() . "");
				}
				if ($listOrdersByNextTokenResult->isSetLastUpdatedBefore())
				{
					// echo("                LastUpdatedBefore");
					// echo("                    " . $listOrdersByNextTokenResult->getLastUpdatedBefore() . "");
				}
				if ($listOrdersByNextTokenResult->isSetOrders()) {
					// here is foreach
					$orderList = $listOrdersByNextTokenResult->getOrders();
// 					$orderList = $orders->getOrder(); // for v2013-09-01 没有这个api 
					$myOrders = array();
					foreach ($orderList as $order) {
						//$myOrder = array();
						//start to format the returned order into arrayInfo format
						$myOrder = self::_formatAmazonOrderInfo($order);
						$myOrders[] = $myOrder;	
					} // end of foreach order
				}//end if got order from api
			}//end if got result	
		} catch (MarketplaceWebServiceOrders_Exception $ex) {
			$myOrders = array();
			$success = false;
			$message =  'E1003:Failed to call amazon interface listOrdersByNextToken. Amz returns:'.$ex->getMessage()." errorCode:".$ex->getErrorCode();
			write_log("ST002_2: $message . (_invokeListOrdersByNextToken) MerchantID=".self::$AMAZON_MERCHANT_ID." MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID ." fromDateTime=".$_REQUEST["fromDateTime"]." toDateTime=".$_REQUEST["toDateTime"], "error");
			write_log("ST002_2: $message . (_invokeListOrdersByNextToken)", "info");
			return array('order'=>$myOrders , 'message'=>$message,'success'=>$success,'errorCode'=>$ex->getErrorCode());
		}
		catch (Exception $e){
			$myOrders = array();
			$success = false;
			$message = 'E1002:Failed to call amazon interface listOrders. Amz returns:'.$e->getMessage();
			write_log("ST002_2: $message . (_invokeListOrdersByNextToken) MerchantID=".self::$AMAZON_MERCHANT_ID." MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID ." fromDateTime=".$_REQUEST["fromDateTime"]." toDateTime=".$_REQUEST["toDateTime"], "error");
			write_log("ST002_2: $message . (_invokeListOrdersByNextToken)", "info");
		}
		return array('order'=>$myOrders , 'message'=>$message,'success'=>$success);
	}// end of _invokeListOrdersByNextToken
	
static function _getOrderItems(MarketplaceWebServiceOrders_Interface $service, $orderid){
	$success = true;
	$message = "";
	try{
		$ItemRequest = new MarketplaceWebServiceOrders_Model_ListOrderItemsRequest();
		$ItemRequest->setSellerId(self::$AMAZON_MERCHANT_ID);
		$ItemRequest->setMWSAuthToken(self::$AMAZON_MWS_AUTH_TOKEN);
		$ItemRequest->setAmazonOrderId($orderid);
	 
		$rt = self::_invokeListOrderItems($service, $ItemRequest);
		$Items = $rt['items'];
		$success = $rt['success'];
		$message .= $rt['message'];
		write_log("ST002:Start to invoke API for items of $orderid . (_getOrderItems)", "info");
	} catch (MarketplaceWebServiceOrders_Exception $ex) {
		$Items = array();
		$success = false;
		$message =   'E1004:Failed to call amazon interface get items. Amz returns:'.$ex->getMessage();
		write_log("ST003:$message - $orderid . (_getOrderItems)", "error");
		write_log("ST003:$message - $orderid . (_getOrderItems)", "info");
	}
	return array('item'=>$Items , 'message'=>$message,'success'=>$success);
}//end of function got order items	

static function _formatAmazonOrderInfo($order){
	$myOrder = array();
	if ($order->isSetAmazonOrderId()){
		// only get Incremental Order case request frequent
		$orderid = $order->getAmazonOrderId();
		$myOrder['AmazonOrderId'] = $order->getAmazonOrderId();
	}
	
	if ($order->isSetPurchaseDate()){
		$myOrder['PurchaseDate'] = $order->getPurchaseDate();
	}
		
	if ($order->isSetLastUpdateDate())//this is used as Order Date
	{
		$myOrder['LastUpdateDate'] = $order->getLastUpdateDate();
	}
		
	if ($order->isSetOrderStatus())
	{
		$myOrder['Status'] = $order->getOrderStatus();
	}
	
	if ($order->isSetSalesChannel())
	{
		$myOrder['SalesChannel'] = $order->getSalesChannel();
	}
	
	if ($order->isSetOrderChannel())
	{
		$myOrder['OrderChannel'] = $order->getOrderChannel();
	}
		
	if ($order->isSetShipServiceLevel())
	{	$myOrder['ShipServiceLevel'] = $order->getShipServiceLevel();
	}
		
	if ($order->isSetShippingAddress()) {
		$shippingAddress = $order->getShippingAddress();
		if ($shippingAddress->isSetName()){
			$myOrder['Name'] =  $shippingAddress->getName();
		}
		
		if ($shippingAddress->isSetAddressLine1()){
			$myOrder['AddressLine1'] =  $shippingAddress->getAddressLine1();
		}
		
		if ($shippingAddress->isSetAddressLine2()){
			$myOrder['AddressLine2'] = " ".$shippingAddress->getAddressLine2();
		}
		
		if ($shippingAddress->isSetAddressLine3()){
			$myOrder['AddressLine3'] = " ".$shippingAddress->getAddressLine3();
		}
	
		if ($shippingAddress->isSetCity()){
			$myOrder['City'] =  $shippingAddress->getCity();
		}
		
		if ($shippingAddress->isSetCounty()){
			$myOrder['County'] =  $shippingAddress->getCounty();
		}
		
		if ($shippingAddress->isSetDistrict()){
			$myOrder['District'] =  $shippingAddress->getDistrict() ;
		}
		
		if ($shippingAddress->isSetStateOrRegion()){
			$myOrder['State'] = $shippingAddress->getStateOrRegion() ;
		}
		
		if ($shippingAddress->isSetPostalCode()){
			$myOrder['PostalCode'] =  $shippingAddress->getPostalCode();
		}
		
		if ($shippingAddress->isSetCountryCode()){
			$myOrder['CountryCode'] = $shippingAddress->getCountryCode();
		}
		
		if ($shippingAddress->isSetPhone()){
			$myOrder['Phone'] =  $shippingAddress->getPhone();
		}
	}//end of isSetShippingAddress
		
	if ($order->isSetOrderTotal()) {
		$orderTotal = $order->getOrderTotal();
		if ($orderTotal->isSetCurrencyCode()){	
			$myOrder['Currency'] = $orderTotal->getCurrencyCode();
		}
		
		if ($orderTotal->isSetAmount()){   
			$myOrder['Amount'] = $orderTotal->getAmount();		
		}
	}
	
	if ($order->isSetPaymentMethod())
	{
		$myOrder['PaymentMethod'] =  $order->getPaymentMethod();
	}
		
	if ($order->isSetBuyerName()){
		$myOrder['BuyerName'] =  $order->getBuyerName();
	}
	
	if ($order->isSetBuyerEmail())
	{
		$myOrder['BuyerEmail'] =  $order->getBuyerEmail();
	}
	
	if ($order->isSetNumberOfItemsShipped()){
		$myOrder['NumberOfItemsShipped'] =  $order->getNumberOfItemsShipped();
	}
	
	if ($order->isSetNumberOfItemsUnshipped()){
		$myOrder['NumberOfItemsUnshipped'] =  $order->getNumberOfItemsUnshipped();
	}
	
	if ($order->isSetShipmentServiceLevelCategory()){
		$myOrder['ShipmentServiceLevelCategory'] =  $order->getShipmentServiceLevelCategory();
	}
	
	if ($order->isSetTFMShipmentStatus()){
		$myOrder['TFMShipmentStatus'] =  $order->getTFMShipmentStatus();
	}
	
	if ($order->isSetEarliestShipDate()){
		$myOrder['EarliestShipDate'] =  $order->getEarliestShipDate();
	}
	
	if ($order->isSetLatestShipDate()){
		$myOrder['LatestShipDate'] =  $order->getLatestShipDate();
	}
	
	if ($order->isSetEarliestDeliveryDate()){
		$myOrder['EarliestDeliveryDate'] =  $order->getEarliestDeliveryDate();
	}
	
	if ($order->isSetLatestDeliveryDate()){
		$myOrder['LatestDeliveryDate'] =  $order->getLatestDeliveryDate();
	}
	
	if ($order->isSetFulfillmentChannel()){
		$myOrder['FulfillmentChannel'] =  $order->getFulfillmentChannel();
	}
	
	// 以下属性貌似有点鸡�?
	if ($order->isSetSellerOrderId()){
		$myOrder['SellerOrderId'] =  $order->getSellerOrderId();
	}
	
	if ($order->isSetPaymentExecutionDetail()){
		$PaymentExecutionDetailItems = $order->getPaymentExecutionDetail();
		$PaymentExecutionDetail = array();
		foreach($PaymentExecutionDetailItems as $PaymentExecutionDetailItem){
			$itemDetail = array();
			if($PaymentExecutionDetailItem->isSetPaymentMethod()){
				$itemDetail['PaymentMethod'] = $PaymentExecutionDetailItem->getPaymentMethod();
			}
			
			if ($PaymentExecutionDetailItem->isSetPayment()) {
				$payment = $PaymentExecutionDetailItem->getPayment();
				if ($payment->isSetCurrencyCode()){	
					$itemDetail['Currency'] = $payment->getCurrencyCode();
				}
				
				if ($payment->isSetAmount()){   
					$itemDetail['Amount'] = $payment->getAmount();		
				}
			}
			
			$PaymentExecutionDetail[] = $itemDetail;
		}
		
		$myOrder['PaymentExecutionDetail'] = $PaymentExecutionDetail;
	}
	
	if ($order->isSetMarketplaceId()){
		$myOrder['MarketplaceId'] =  $order->getMarketplaceId();
	}
	
	if ($order->isSetShippedByAmazonTFM()){
		$myOrder['ShippedByAmazonTFM'] =  $order->getShippedByAmazonTFM();
	}
	
	if ($order->isSetCbaDisplayableShippingLabel()){
		$myOrder['CbaDisplayableShippingLabel'] =  $order->getCbaDisplayableShippingLabel();
	}
	
	if ($order->isSetOrderType()){
		$myOrder['OrderType'] =  $order->getOrderType();
	}
	
	if ($order->isSetIsBusinessOrder()){
		$myOrder['IsBusinessOrder'] =  $order->getIsBusinessOrder();
	}
	
	if ($order->isSetPurchaseOrderNumber()){
		$myOrder['PurchaseOrderNumber'] =  $order->getPurchaseOrderNumber();
	}
	
	return $myOrder;
}//end of function 	


static function _formatAmazonProductInfo($product){
	$productInfo = array();
	
	if($product->isSetIdentifiers()){
		$IdentifierType = $product->getIdentifiers();
		$productInfo['Identifiers'] = null;
		if($IdentifierType->isSetMarketplaceASIN()){
			$ASINIdentifier = $IdentifierType->getMarketplaceASIN();
			$productInfo['Identifiers']['MarketplaceASIN'] = Null;
			if($ASINIdentifier->isSetASIN()){
				$productInfo['Identifiers']['MarketplaceASIN']['ASIN'] = $ASINIdentifier->getASIN();
			}else{
				$productInfo['Identifiers']['MarketplaceASIN']['ASIN'] = null;
			}
				
			if($ASINIdentifier->isSetMarketplaceId()){
				$productInfo['Identifiers']['MarketplaceASIN']['MarketplaceId'] = $ASINIdentifier->getMarketplaceId();
			}else{
				$productInfo['Identifiers']['MarketplaceASIN']['MarketplaceId'] = null;
			}
				
		}
	
		if($IdentifierType->isSetSKUIdentifier()){
			$SellerSKUIdentifier = $IdentifierType->getSKUIdentifier();
				
			$productInfo['Identifiers']['SKUIdentifier'] = null;
			if($SellerSKUIdentifier->isSetMarketplaceId()){
				$productInfo['Identifiers']['SKUIdentifier']['MarketplaceId'] = $SellerSKUIdentifier->getMarketplaceId();
			}else{
				$productInfo['Identifiers']['SKUIdentifier']['MarketplaceId'] = null;
			}
				
			if($SellerSKUIdentifier->isSetSellerId()){
				$productInfo['Identifiers']['SKUIdentifier']['SellerId'] = $SellerSKUIdentifier->getSellerId();
			}else{
				$productInfo['Identifiers']['SKUIdentifier']['SellerId'] = null;
			}
				
			if($SellerSKUIdentifier->isSetSellerSKU()){
				$productInfo['Identifiers']['SKUIdentifier']['SellerSKU'] = $SellerSKUIdentifier->getSellerSKU();
			}else{
				$productInfo['Identifiers']['SKUIdentifier']['SellerSKU'] = null;
			}
				
				
		}
	}
	
	if($product->isSetAttributeSets()){
		$AttributeSetList = $product->getAttributeSets();
		$productInfo['AttributeSets'] = null;
		if($AttributeSetList->isSetAny()){
			$aAnyList = $AttributeSetList->getAny();
			$productInfo['AttributeSets']['Any'] = null;
			foreach ($aAnyList as $anyIndex=>$attrs){
				$productInfo['AttributeSets']['Any'][$anyIndex] = parseNamespaceXml($attrs->ownerDocument->saveXML($attrs));
			}
		}
	}
	
	if($product->isSetRelationships()){
		$Relationships = $product->getRelationships();
		$productInfo['Relationships'] = null;
		if($Relationships->isSetAny()){
			$rAnyList = $Relationships->getAny();
			$productInfo['Relationships']['Any'] = null;
			foreach ($rAnyList as $anyIndex=>$relation){
				$productInfo['Relationships']['Any'][$anyIndex] = parseNamespaceXml($attrs->ownerDocument->saveXML($relation));
			}
		}
	}
	
		
	if($product->isSetSalesRankings()){
		$SalesRankList = $product->getSalesRankings();
		$productInfo['SalesRankings'] = null;
		if($SalesRankList->isSetSalesRank()){
			$SalesRankTypeList = $SalesRankList->getSalesRank();
			
			$productInfo['SalesRankings']['SalesRank'] = null;
			foreach ($SalesRankTypeList as $SalesRankTypeIndex=>$SalesRankType){
				$productInfo['SalesRankings']['SalesRank'][$SalesRankTypeIndex] = null;
				if($SalesRankType->isSetRank()){
					$productInfo['SalesRankings']['SalesRank'][$SalesRankTypeIndex]['Rank'] = $SalesRankType->getRank();
				}else{
					$productInfo['SalesRankings']['SalesRank'][$SalesRankTypeIndex]['Rank'] = null;
				}
					
				if($SalesRankType->isSetProductCategoryId()){
					$productInfo['SalesRankings']['SalesRank'][$SalesRankTypeIndex]['ProductCategoryId'] = $SalesRankType->getProductCategoryId();
				}else{
					$productInfo['SalesRankings']['SalesRank'][$SalesRankTypeIndex]['ProductCategoryId'] = null;
				}
			}
		}
	}
	
	return $productInfo;
	
}//end of function 
	

static function _formatAmazonOrderItemInfo($orderItem){
	$item = array();
	if ($orderItem->isSetASIN())
	{
		$item['ASIN'] = $orderItem->getASIN();
	}
	if ($orderItem->isSetSellerSKU())
	{
		$item['SellerSKU'] = $orderItem->getSellerSKU();
	}
	//亚马逊所定义的订单商品编码�?
	if ($orderItem->isSetOrderItemId())
	{
		$item['OrderItemId'] = $orderItem->getOrderItemId();
	}
	if ($orderItem->isSetTitle())
	{
		$item['Title'] = $orderItem->getTitle() ;
	}
	if ($orderItem->isSetQuantityOrdered())
	{
		$item['QuantityOrdered']  = $orderItem->getQuantityOrdered() ;
	}
	if ($orderItem->isSetQuantityShipped())
	{
		$item['QuantityShipped']  = $orderItem->getQuantityShipped() ;
	}
		
	if ($orderItem->isSetPointsGranted()) {
	/*	// lolo2151206delete --- 当PointsGranted 有数据的时候，这里的读取时有问题的 先读取PointsGranted，然后PointsMonetaryValue，最后是 CurrencyCode�?Amount
	   //  由于eagle那边也没有保存PointsGranted的字段，这里直接不读取好了�?@TODO 以后需要加回去
	$PointsGranted = $orderItem->getPointsGranted();
		if ($PointsGranted->isSetCurrencyCode())
		{}
		if ($PointsGranted->isSetAmount())
			$item['PointsGranted'] = $PointsGranted->getAmount()  ; */
	}
		
	//商品的售价�?
	if ($orderItem->isSetItemPrice()) {
		$itemPrice = $orderItem->getItemPrice();
		if ($itemPrice->isSetCurrencyCode())
		{ }
		if ($itemPrice->isSetAmount())
		{
			if ( isset($item['QuantityOrdered']) && $item['QuantityOrdered'] !=0 )
				$item['ItemPrice'] = $itemPrice->getAmount() / $item['QuantityOrdered'] ;
			else
				$item['ItemPrice'] = 0;
		}
// 		$AMAZON_Item_Total += $itemPrice->getAmount();//$AMAZON_Item_Total dzt20160720 发现报错没有初始化值，而且后面没有使用这个变量所以去掉了
	}
		
	//商品的配送费用�?
	if ($orderItem->isSetShippingPrice()) {
		$shippingPrice = $orderItem->getShippingPrice();
		if ($shippingPrice->isSetCurrencyCode())
		{}
		if ($shippingPrice->isSetAmount())
		{
			$item['ShippingPrice'] = $shippingPrice->getAmount();
// 			$AMAZON_Delivery_Total += $shippingPrice->getAmount();// 发现报错没有初始化值，而且后面没有使用这个变量所以去掉了
		}
	}
		
	// 商品的礼品包装费用�?
	if ($orderItem->isSetGiftWrapPrice()) {
		$giftWrapPrice = $orderItem->getGiftWrapPrice();
		if ($giftWrapPrice->isSetCurrencyCode())
		{}
			
		if ($giftWrapPrice->isSetAmount())
			$item['GiftWrapPrice'] = $giftWrapPrice->getAmount();
	}
	//商品价格所缴税�?
	if ($orderItem->isSetItemTax()) {
		$itemTax = $orderItem->getItemTax();
		if ($itemTax->isSetCurrencyCode())
		{}
		if ($itemTax->isSetAmount())
			$item['ItemTax'] = $itemTax->getAmount();
	}
	//商品配送费用所缴税�?
	if ($orderItem->isSetShippingTax()) {
		$shippingTax = $orderItem->getShippingTax();
		if ($shippingTax->isSetCurrencyCode())
		{}
		if ($shippingTax->isSetAmount())
			$item['ShippingTax'] = $shippingTax->getAmount();
	}
	//礼品包装费用所缴税费�?
	if ($orderItem->isSetGiftWrapTax()) {
		$giftWrapTax = $orderItem->getGiftWrapTax();
		if ($giftWrapTax->isSetCurrencyCode())
		{}
		if ($giftWrapTax->isSetAmount())
			$item['GiftWrapTax'] = $giftWrapTax->getAmount();
	}
	//商品配送费用所享折扣�?
	if ($orderItem->isSetShippingDiscount()) {
		$shippingDiscount = $orderItem->getShippingDiscount();
		if ($shippingDiscount->isSetCurrencyCode())
		{}
		if ($shippingDiscount->isSetAmount())
			$item['ShippingDiscount'] = $shippingDiscount->getAmount();
	}
	//报价中的总促销折扣
	if ($orderItem->isSetPromotionDiscount()) {
		$promotionDiscount = $orderItem->getPromotionDiscount();
		if ($promotionDiscount->isSetCurrencyCode()){}
		if ($promotionDiscount->isSetAmount())
		{
			$item['PromotionDiscount'] = $promotionDiscount->getAmount();
		}
	}
	//商品所使用的促销编码�?
	if ($orderItem->isSetPromotionIds()) {
		$promotionIdList = $orderItem->getPromotionIds();
		// 						$promotionIdList = $promotionIds->getPromotionId();
		$item['PromotionIds'] = implode(',', $promotionIdList);
	}
		
	if ($orderItem->isSetCODFee()) {
		$CODFee = $orderItem->getCODFee();
		if ($CODFee->isSetCurrencyCode())
		{ }
		if ($CODFee->isSetAmount())
		{
			$item['CODFee'] = $CODFee->getAmount()  ;
		}
	}
		
	if ($orderItem->isSetCODFeeDiscount()) {
		$CODFeeDiscount = $orderItem->getCODFeeDiscount();
		if ($CODFeeDiscount->isSetCurrencyCode())
		{}
		if ($CODFeeDiscount->isSetAmount())
			$item['CODFeeDiscount'] = $CODFeeDiscount->getAmount()  ;
	}
		
	if ($orderItem->isSetGiftMessageText())
		$item['GiftMessageText'] =$orderItem->getGiftMessageText();
	
	if ($orderItem->isSetGiftWrapLevel())
		$item['GiftWrapLevel'] =$orderItem->getGiftWrapLevel();
	
	// 适用于中国地�?
	if ($orderItem->isSetInvoiceData()) {
		$invoiceData = $orderItem->getInvoiceData();
		if ($invoiceData->isSetInvoiceRequirement())
			$item['InvoiceRequirement'] = $invoiceData->getInvoiceRequirement();
	
		if ($invoiceData->isSetBuyerSelectedInvoiceCategory())
			$item['BuyerSelectedInvoiceCategory'] = $invoiceData->getBuyerSelectedInvoiceCategory();
	
		if ($invoiceData->isSetInvoiceTitle())
			$item['InvoiceTitle'] = $invoiceData->getInvoiceTitle();
	
		if ($invoiceData->isSetInvoiceInformation())
			$item['InvoiceInformation'] = $invoiceData->getInvoiceInformation();
	}//end of invoice data
	
	if ($orderItem->isSetConditionNote()) {
		$item['ConditionNote'] = $orderItem->getConditionNote();
	}
		
	if ($orderItem->isSetConditionId()) {
		$item['ConditionId'] = $orderItem->getConditionId();
	}
	
	if ($orderItem->isSetConditionSubtypeId()) {
		$item['ConditionSubtypeId'] = $orderItem->getConditionSubtypeId();
	}
		
	if ($orderItem->isSetScheduledDeliveryStartDate()) {
		$item['ScheduledDeliveryStartDate'] = $orderItem->getScheduledDeliveryStartDate();
	}
	
	if ($orderItem->isSetScheduledDeliveryEndDate()) {
		$item['ScheduledDeliveryEndDate'] = $orderItem->getScheduledDeliveryEndDate();
	}
		
	if ($orderItem->isSetPriceDesignation()) {
		$item['PriceDesignation'] = $orderItem->getPriceDesignation();
	}
	
	return $item;
}//end of function 	

/**
 * List Order Items Action Sample
 * This operation can be used to list the items of the order indicated by the
 * given order id (only a single Amazon order id is allowed).
 *
 * @param MarketplaceWebServiceOrders_Interface $service instance of MarketplaceWebServiceOrders_Interface
 * @param mixed $request MarketplaceWebServiceOrders_Model_ListOrderItems or array of parameters
 */
static function _invokeListOrderItems(MarketplaceWebServiceOrders_Interface $service, $request )
{
	global $AMAZON_Delivery_Total , $AMAZON_Item_Total;
	$rtn['success'] = true;
	$rtn['message'] = "";
	try {
		$response = $service->listOrderItems($request);
		if ($response->isSetListOrderItemsResult()) {
			$listOrderItemsResult = $response->getListOrderItemsResult();
			if ($listOrderItemsResult->isSetNextToken()) {
				// echo(" " . $listOrderItemsResult->getNextToken() . "<br>");
				self::$AMAZON_ORDER_ITEMS_NEXTTOKEN = $listOrderItemsResult->getNextToken();
			}else{
				self::$AMAZON_ORDER_ITEMS_NEXTTOKEN = "";
			}
			if ($listOrderItemsResult->isSetAmazonOrderId())
				{}
			if ($listOrderItemsResult->isSetOrderItems()) {
				$orderItemList = $listOrderItemsResult->getOrderItems();
// 				$orderItemList = $orderItems->getOrderItem();
				foreach ($orderItemList as $orderItem) {
					$item = self::_formatAmazonOrderItemInfo($orderItem);//purge the data
					$items[] = $item;
				}  // end of each item
			}//end of got order Items
		}//end of got result
	} catch (MarketplaceWebServiceOrders_Exception $ex) {	
		$rtn['success'] = false;
		$rtn['message'] = $ex->getMessage();
		$items = array();
		$orderid = $request->getAmazonOrderId();
		$message =   'E1004:Failed to call amazon interface get items. Amz returns:'.$ex->getMessage();
		write_log("ST001:$message - $orderid . (_invokeListOrderItems) MerchantID=".self::$AMAZON_MERCHANT_ID." MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID, "error");
		write_log("ST001:$message - $orderid . (_invokeListOrderItems)", "info");
	}
	catch (Exception $ex) {
		$rtn['success'] = false;
		$rtn['message'] = $ex->getMessage();
		$items = array();
		$orderid = $request->getAmazonOrderId();
		$message =   'E1004:Failed to call amazon interface get items. Amz returns:'.$ex->getMessage();
		write_log("ST001:$message - $orderid . (_invokeListOrderItems) MerchantID=".self::$AMAZON_MERCHANT_ID." MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID, "error");
		write_log("ST001:$message - $orderid . (_invokeListOrderItems)", "info");
	}
	$rtn['items'] = $items;
	return $rtn;
} // end of _invokeListOrderItems
	
static function _invokeListOrderItemsByNextToken(MarketplaceWebServiceOrders_Interface $service, $request ){
	global $AMAZON_Delivery_Total , $AMAZON_Item_Total;
	$rtn['success'] = true;
	$rtn['message'] = "";
	try {
		$response = $service->listOrderItemsByNextToken($request);
		if ($response->isSetListOrderItemsResult()) {
			$listOrderItemsResult = $response->getListOrderItemsResult();
			if ($listOrderItemsResult->isSetNextToken()) {
				// echo(" " . $listOrderItemsResult->getNextToken() . "<br>");
				self::$AMAZON_ORDER_ITEMS_NEXTTOKEN = $listOrderItemsResult->getNextToken();
			}else{
				self::$AMAZON_ORDER_ITEMS_NEXTTOKEN = "";
			}
			if ($listOrderItemsResult->isSetAmazonOrderId())
			{}
			if ($listOrderItemsResult->isSetOrderItems()) {
				$orderItemList = $listOrderItemsResult->getOrderItems();
				// 				$orderItemList = $orderItems->getOrderItem();
				foreach ($orderItemList as $orderItem) {
					$item = self::_formatAmazonOrderItemInfo($orderItem);//purge the data
					$items[] = $item;
				}  // end of each item
			}//end of got order Items
		}//end of got result
	} catch (MarketplaceWebServiceOrders_Exception $ex) {
		$rtn['success'] = false;
		$rtn['message'] = $ex->getMessage();
		$items = array();
		$orderid = $request->getAmazonOrderId();
		$message =   'E1005:Failed to call amazon interface get items _invokeListOrderItemsByNextToken. Amz returns:'.$ex->getMessage();
		write_log("ST002:$message - $orderid . (_invokeListOrderItemsByNextToken) MerchantID=".self::$AMAZON_MERCHANT_ID." MarketPlaceId=".self::$AMAZON_MARKETPLACE_ID, "error");
		write_log("ST002:$message - $orderid . (_invokeListOrderItemsByNextToken)", "info");
	}
	$rtn['items'] = $items;
	return $rtn;
}
	
	/**********************************   product start  ***************************************/
				static function _invokeGetMatchingProductForId(MarketplaceWebServiceProducts_Interface $service, $request)
				{
				try {
				$response = $service->GetMatchingProductForId($request);
	
					$dom = new DOMDocument();
					$dom->loadXML($response->toXML());
					$dom->preserveWhiteSpace = false;
					$dom->formatOutput = true;
					$rt['result'] =  $dom->saveXML();
					$rt['success'] = true;
					
					//print_r($rt['result'] );
					 
				} catch (MarketplaceWebServiceProducts_Exception $ex) {
				self::_makeLog(" (".(__function__).")".date("Y-m-d h:i:s"). " :  ".$ex->getMessage()."  ");
					/*
				echo("Caught Exception: " . $ex->getMessage() . "");
				echo("Response Status Code: " . $ex->getStatusCode() . "");
				echo("Error Code: " . $ex->getErrorCode() . "");
				echo("Error Type: " . $ex->getErrorType() . "");
					echo("Request ID: " . $ex->getRequestId() . "");
				echo("XML: " . $ex->getXML() . "");
				echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "");
				*/
					$rt['success'] = false;
					$rt['message'] = $ex->getMessage();
					
				}
				return $rt;
				}//end of _invokeGetMatchingProductForId
	

static function _invokeGetMatchingProductForId2(MarketplaceWebServiceProducts_Interface $service, $request) {
	$rtn['success'] = true;
	$rtn['message'] = "";
	$products = array();
	try {
		$response = $service->GetMatchingProductForId($request);
		write_log(print_r($response,true),"info");
		if($response->isSetGetMatchingProductForIdResult()){
			$GetMatchingProductForIdResult = $response->getGetMatchingProductForIdResult();
			foreach ($GetMatchingProductForIdResult as $rindex=>$result){
				$products[$rindex] = array();
	
				if($result->isSetId()){
					$products[$rindex]['Id'] = $result->getId();
				}
	
				if($result->isSetIdType()){
					$products[$rindex]['IdType'] = $result->getIdType();
				}
	
				if($result->isSetstatus()){
					$products[$rindex]['status'] = $result->getstatus();
				}
	
				if($result->isSetError()){
					$Error = $result->getError();
					$products[$rindex]['Error'] = null;
					if($Error->isSetCode()){
						$products[$rindex]['Error']['Code'] = $Error->getCode();
					}
					if($Error->isSetDetail()){
						$products[$rindex]['Error']['Detail'] = $Error->getDetail();
					}
					if($Error->isSetMessage()){
						$products[$rindex]['Error']['Message'] = $Error->getMessage();
					}
					if($Error->isSetType()){
						$products[$rindex]['Error']['Type'] = $Error->getType();
					}
						
				}
	
				if($result->isSetProducts()){
					$ProductList = $result->getProducts();
					$products[$rindex]['Products'] = null;
					if($ProductList->isSetProduct()){
						$getProducts = $ProductList->getProduct();
						foreach ($getProducts as $pindex=>$product){
							$products[$rindex]['Products'][$pindex] = array();
							$products[$rindex]['Products'][$pindex] = self::_formatAmazonProductInfo($product);
						}
					}
				}
			}
	
		}
	} catch (MarketplaceWebServiceProducts_Exception $ex) {
		self::_makeLog(" (".(__function__).")".date("Y-m-d h:i:s"). " :  ".$ex->getMessage()."  ");
		$rtn['success'] = false;
		$rtn['message'] = $ex->getMessage();
	}
	$rtn['product'] = $products;
	return $rtn;
}//end of _invokeGetMatchingProductForId2	

static function _invokeGetProductCategoriesForSKU(MarketplaceWebServiceProducts_Interface $service, $request){
    $rtn['success'] = true;
    $rtn['message'] = "";
    $productCategory = array();
    try {
        $response = $service->getProductCategoriesForSKU($request);
        if($response->isSetGetProductCategoriesForSKUResult()){
            $result = $response->getGetProductCategoriesForSKUResult();
            if($result->isSetSelf()){
                $categoriesList = $result->getSelf();
                foreach ($categoriesList as $index=>$categories){
                    if($categories->isSetProductCategoryId()){
                        $productCategory[$index]['BrowseNodeId'] = $categories->getProductCategoryId();
                    }
                    
                    if($categories->isSetProductCategoryName()){
                        $productCategory[$index]['Name'] = $categories->getProductCategoryName();
                    }
                    
                    if($categories->isSetParent()){
                        $parent = $categories->getParent();
                        self::_formCategoriesAncestors($productCategory[$index]['Ancestors']['BrowseNode'],$parent);
                    }
                }
                
            }

        }
    
    } catch (MarketplaceWebServiceProducts_Exception $ex) {
        self::_makeLog(" (".(__function__).")".date("Y-m-d h:i:s"). " :  ".$ex->getMessage()."  ");
        $rtn['success'] = false;
        $rtn['message'] = $ex->getMessage();
    }
    
    $rtn['BrowseNodes'] = $productCategory;
    return $rtn;
}// end of _invokeGetProductCategoriesForSKU	


static function _formCategoriesAncestors(&$rtnCatInfo , $apiRtnCatInfo){
    if($apiRtnCatInfo->isSetProductCategoryId()){
        $rtnCatInfo['BrowseNodeId'] = $apiRtnCatInfo->getProductCategoryId();
    }
    
    if($apiRtnCatInfo->isSetProductCategoryName()){
        $rtnCatInfo['Name'] = $apiRtnCatInfo->getProductCategoryName();
    }
    
    if($apiRtnCatInfo->isSetParent()){
        $parent = $apiRtnCatInfo->getParent();
        self::_formCategoriesAncestors($rtnCatInfo['Ancestors']['BrowseNode'],$parent);
    }
}// end of _formCategoriesAncestors	

static function _invokeGetMatchingProduct(MarketplaceWebServiceProducts_Interface $service, $request) {
	try {
		$response = $service->GetMatchingProduct($request);
		$dom = new DOMDocument();
		$dom->loadXML($response->toXML());
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$rtn['result'] =  $dom->saveXML();
		//echo("ResponseHeaderMetadata: " . $response->getResponseHeaderMetadata() . "");
	
	} catch (MarketplaceWebServiceProducts_Exception $ex) {
    	self::_makeLog(" (".(__function__).")".date("Y-m-d h:i:s"). " :  ".$ex->getMessage()."  ");
    	$rtn['success'] = false;
    	$rtn['message'] = $ex->getMessage();
    	/*
    	echo("Caught Exception: " . $ex->getMessage() . "");
    	echo("Response Status Code: " . $ex->getStatusCode() . "");
		echo("Error Code: " . $ex->getErrorCode() . "");
		echo("Error Type: " . $ex->getErrorType() . "");
		echo("Request ID: " . $ex->getRequestId() . "");
		echo("XML: " . $ex->getXML() . "");
		echo("ResponseHeaderMetadata: " . $ex->getResponseHeaderMetadata() . "");
		*/
	}
    return $rtn;
}//end of _invokeGetMatchingProduct
	


static function _invokeGetMatchingProduct2(MarketplaceWebServiceProducts_Interface $service, $request)
{
	$rtn['success'] = true;
	$rtn['message'] = "";
	$products = array();
	
	try {
		$response = $service->GetMatchingProduct($request);
	
		if($response->isSetGetMatchingProductResult()){
			$GetMatchingProductResult = $response->getGetMatchingProductResult();//dzt20150711
			foreach ($GetMatchingProductResult as $rindex=>$result){
				if($result->isSetASIN()){
					$products[$rindex]['ASIN'] = $result->getASIN();
				}
	
				if($result->isSetstatus()){
					$products[$rindex]['status'] = $result->getstatus();
				}
	
				if($result->isSetError()){
					$Error = $result->getError();
					$products[$rindex]['Error'] = null;
					if($Error->isSetCode()){
						$products[$rindex]['Error']['Code'] = $Error->getCode();
					}
					if($Error->isSetDetail()){
						$products[$rindex]['Error']['Detail'] = $Error->getDetail();
					}
					if($Error->isSetMessage()){
						$products[$rindex]['Error']['Message'] = $Error->getMessage();
					}
					if($Error->isSetType()){
						$products[$rindex]['Error']['Type'] = $Error->getType();
					}
	
				}
	
				$products[$rindex]['Product'] = array();
				if($result->isSetProduct()){
					$getProduct = $result->getProduct();
					$products[$rindex]['Product'] = self::_formatAmazonProductInfo($getProduct);
				}
			}
				
		}
	} catch (MarketplaceWebServiceProducts_Exception $ex) {
		self::_makeLog(" (".(__function__).")".date("Y-m-d h:i:s"). " :  ".$ex->getMessage()."  ");
		$rtn['success'] = false;
		$rtn['message'] = $ex->getMessage();
	} catch (Exception $ex) {
		self::_makeLog(" (".(__function__).")".date("Y-m-d h:i:s"). " :  ".$ex->getMessage()."  ");
		$rtn['success'] = false;
		$rtn['message'] = $ex->getMessage();
	}
	$rtn['product'] = $products;
	return $rtn;
}//end of _invokeGetMatchingProduct2

/***   product end  ***/
	
	
	
				/************************************   PRIVATE END   ***************************************/
		
	
}//end of AMAZON_API
?>