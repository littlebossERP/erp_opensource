<?php
require_once(dirname(__FILE__)."/TimeUtil.php");
require_once(dirname(__FILE__)."/Utility.php");
require_once(dirname(__FILE__)."/AmazonAPI.php"); 
require_once(dirname(__FILE__)."/AmazonService.php");

AmazonAPI::$AMAZON_API_CALL_TIMES = 0;
AmazonAPI::$SCRIPT_INIT_CALL_TIMESTAMP = time();
AmazonAPI::$ALL_REQUEST_PARAMS_STR = '';
foreach ( $_REQUEST as $key => $value ){
	if('config' != $key){
		AmazonAPI::$ALL_REQUEST_PARAMS_STR .= $key."=".$value.",";
	}
}

foreach (  $_GET  as $key => $value ){
	if (! isset ( ${$key} )){
		${$key} = $value;
	}
}

foreach (  $_POST  as $key => $value ){
	if (! isset ( ${$key} )){
		${$key} = $value;
	}
}
	if (! isset($action)) $action = "";
	
//start to setup the amazon helper configuration according to parmaeters passed input via http	
if(isset($config)){
	$config = json_decode($config,true);
	AmazonService::setupAmazonConfig($config);
}
	
//pharse the parameters for this api call
	if (isset($parms) and $parms<>'' and $parms <>null)
		$parms = json_decode($parms,true);
	else
		$parms = array();

//initilize the result
	$results = "Ready to do action ".$action;
	
/******************     refresh order  start      **********************/
	/**
	 +---------------------------------------------------------------------------------------------
	 * GetOrder list according to the from Date time and target statuses
	 +---------------------------------------------------------------------------------------------
	 * @Parameters			$action = "GetOrder"
	 +---------------------------------------------------------------------------------------------
	 * @fromDateTime		UTC format date time, orders modified after this time will be retrieved
	 * 						
	 * @status 				Statuses shall be retrieved. e.g. Unshipped / Unshipped,Shipped
	 *  
	 * Valid Statuses are below:
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
	 *@orderType
	 *	Valid value is 
	 *	1) ""
	 *	2) "MFN" : Manually Fulfillment
	 *	3) "AFN" : Amazon Fulfillment (FBA)
	 *	when ommited or "", default use "MFN"  
	 *  
	 *  请注意： 在此版本的“订单 API”部分中，必须
	 *  	    同时使用未发货和已部分发货。仅使用
	 *		    其中一个状态值，则会返回错误。
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2014/04/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
if (strtolower($action) == strtolower("GetOrder")) { 
	//when status is blank, auto set to unshipped
	$status = ucfirst($status); // change the first letter to upper
	write_log("InitReport GetOrder: Call IP:".$_SERVER["REMOTE_ADDR"]." MerchantID:".AmazonAPI::$AMAZON_MERCHANT_ID." MarketPlaceId:".AmazonAPI::$AMAZON_MARKETPLACE_ID." params:".AmazonAPI::$ALL_REQUEST_PARAMS_STR , "info");
	write_log("Step 0: recieve action $action", "info");
	if (!isset($toDateTime)) $toDateTime="";
	
	if (!isset($orderType) or $orderType=='') $orderType="MFN";
	
	$results =  AmazonService::retrieveAmazoneOrders( $fromDateTime,$status,$orderType,$toDateTime);
	write_log("Step Done: Done action $action ", "info");
}

/**
 * 最多50个 amazon订单id,$AmazonOrderId 是亚马逊所定义的订单编码，格式为 3-7-7
 * 
 * */
if (strtolower($action) == strtolower("GetOneOrder")) {
	//when status is blank, auto set to unshipped
	$status = ucfirst($status); // change the first letter to upper
	write_log("InitReport GetOneOrder: Call IP:".$_SERVER["REMOTE_ADDR"]." MerchantID:".AmazonAPI::$AMAZON_MERCHANT_ID." MarketPlaceId:".AmazonAPI::$AMAZON_MARKETPLACE_ID." params:".AmazonAPI::$ALL_REQUEST_PARAMS_STR , "info");
	write_log("Step 0: recieve action $action for $AmazonOrderId", "info");
	if (!isset($AmazonOrderId)) $AmazonOrderId="";
	
	$results =  AmazonAPI::getOneOrder($AmazonOrderId);
	write_log("Step Done: Done action $action ", "info");
}


if (strtolower($action) == strtolower("GetOrderItem")) {
	write_log("InitReport GetOrderItem: Call IP:".$_SERVER["REMOTE_ADDR"]." MerchantID:".AmazonAPI::$AMAZON_MERCHANT_ID." MarketPlaceId:".AmazonAPI::$AMAZON_MARKETPLACE_ID." params:".AmazonAPI::$ALL_REQUEST_PARAMS_STR , "info");
	write_log("Step 0: recieve action $action", "info");
	//retrieve the items of a particular order
	$results = AmazonService::retrieveAmazoneOrdersItems($orderid);
	write_log("Step Done: Done action $action for $orderid", "info");
}

/******************     refresh order  end      **********************/


/******************     Product Advertising  start      **********************/
if (strtolower($action) == strtolower("GetProductTopOfferByAsin")) {
	//echo "<br><br> GetProductInfoByAsin: Call IP:".$_SERVER["REMOTE_ADDR"]." MerchantID:".AmazonAPI::$AMAZON_MERCHANT_ID." MarketPlaceId:".AmazonAPI::$AMAZON_MARKETPLACE_ID." params:".AmazonAPI::$ALL_REQUEST_PARAMS_STR ;
	if(!isset($asin) && isset($parms['asin']))
		$asin = $parms['asin'];
	$results = AmazonService::GetProductTopOfferByAsin($marketplaceId=AmazonAPI::$AMAZON_MARKETPLACE_ID,$asin,$operation=$parms['operation'],$response_group=$parms['response_group']);
	//print_r(json_encode($results));
	exit(json_encode($results));
}

if (strtolower($action) == strtolower("GetProductAttributesByAsin")) {
	if(!isset($asin) && isset($parms['asin']))
		$asin = $parms['asin'];
	$results = AmazonService::GetProductAttributesByAsin($marketplaceId=AmazonAPI::$AMAZON_MARKETPLACE_ID,$asin,$operation=$parms['operation'],$response_group=$parms['response_group']);
	exit(json_encode($results));
}
/******************     Product Advertising  end      **********************/

/******************     change order start      **********************/
if (strtolower($action)  == strtolower("ShipAmazonOrder")) {
	$results = AmazonService::ShipAmazonOrder($parms['order_id'] ,$parms['items'] , $parms['freight'], $parms['delivery_no'], $parms['ship_date']);
}

if (strtolower($action)  == strtolower("batchShipAmazonOrders")) {
	$results = AmazonService::batchShipAmazonOrders($parms['orders']);
}

if (strtolower($action)  == strtolower("RefundAmazonOrderItem")) {
	$results = AmazonService::RefundAmazonOrderProduct($parms);
}

if (strtolower($action)  == strtolower("CancelEntireAmazonOrder")) {
	$results = AmazonService::CancelEntireAmazonOrder($parms);
}
/******************     change order status end      **********************/

/*----------------------------------------------------------------------------*/
/******************          Report Related Starts       **********************/
/*----------------------------------------------------------------------------*/
/**
 +---------------------------------------------------------------------------------------------
 * (Logic Briefing)Request an Amazon report for product list
 +---------------------------------------------------------------------------------------------
 * @steps 
 +---------------------------------------------------------------------------------------------
 * @Step 1				RequestAmazonReport: 
 * 						Can request multiple reports for the merchant id.
 * 						Then amazon returns a request id 
 * @Step 2 				GetAmazonReportIds:
 * 						Check the report ids generated by this request
 * 						Amazon returns the reports(ids) 
 * @Step 3 				GetStoreAllProduct(report_id): 
 * 						Get the report for this report id and parse its content to 
 * 						generate the array of all products 
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/04/17				初始化
 +---------------------------------------------------------------------------------------------
 **/
/**
 +---------------------------------------------------------------------------------------------
 * Get all product list by parsing amazon report result. AmazonService::GetStoreAllProduct
 +---------------------------------------------------------------------------------------------
 * @param
 * 						report_id
 +---------------------------------------------------------------------------------------------
 * @return 				Array
        				(
    				        [success] => 1
               				[message] => 
               				[products] => Array
       				            (
                       				[0] => Array
                           				(
                               				[sku] => 1520-10.8^^^5200
                               				[asin] => B00IHVE550
                               				[price] => 26.49
                               				[quantity] => 60
                           				)

                       				[1] => Array
                           				(
                               				[sku] => 1520-11.1^^^4800
                               				[asin] => B00IHVE6H2
                               				[price] => 26.49
                               				[quantity] => 5
                           				)
                   				)
           				)
 +---------------------------------------------------------------------------------------------
 **/
if (strtolower($action) == strtolower("GetStoreAllProductByReport")) {
	//retrieve the result of a particular report and convert to store all product list
	$results = AmazonService::GetStoreAllProduct($parms['report_id']);
}



/**
 +---------------------------------------------------------------------------------------------
 * Request an Amazon report. AmazonService::RequestAmazonReport
 +---------------------------------------------------------------------------------------------
 * @param				
 * 						report_type
 * 						start_date
 * 						end_date
 +---------------------------------------------------------------------------------------------
 * @return 				Array
 *       				(
 *           				[success] => 1
 *           				[message] => 
 *           				[ReportRequestId] => 8432781584
 *          				[ReportType] => _GET_FLAT_FILE_OPEN_LISTINGS_DATA_            			
 *          				[SubmittedDate] => 2014-04-17 03:53:30
 * 				            [ReportProcessingStatus] => _SUBMITTED_
 *      				)
 *@desc					RequestReport 操作的最大请求限额为 15 个，恢复速率为每分钟 1 个请求
 +---------------------------------------------------------------------------------------------
 **/
if (strtolower($action) == strtolower("RequestAmazonReport")) {
	//retrieve the result of a particular report and convert to store all product list
	$report_options = '';
	if(!empty($parms['report_options']))$ReportOptions = $parms['report_options'];
    
	$results = AmazonService::RequestAmazonReport( $parms['report_type'] , $parms['start_date'] , $parms['end_date'] , $report_options);
}





/**
 +---------------------------------------------------------------------------------------------
 * Get the report ids from a request. AmazonService::GetAmazonReportIds
 +---------------------------------------------------------------------------------------------
 * @param
 * 						request_id
 +---------------------------------------------------------------------------------------------
 * @return 				  Array
 *				 		  (
 * 		        			 [success] => 1
 *				 		     [message] => 
 * 		        			 [report] => Array
 *		 		                  (
 *       				              [0] => Array
 * 	       				                  (
 * 	                       				      [ReportId] => 34292388144
 *		 		                              [ReportType] => _GET_FLAT_FILE_OPEN_LISTINGS_DATA_
 * 	       				                      [AvailableDate] => 2014-04-17 06:17:11
 *		 		                          )	  
 * 	       				          )
 *		 		          )                
 +---------------------------------------------------------------------------------------------
 * @desc				if the report array has no element, the report is not ready,Load later	 		          
 **/
if (strtolower($action) == strtolower("GetAmazonReportIds")) {
	//retrieve the reports id for the report request
	$results = AmazonService::GetAmazonReportIds( $parms['request_id'] );
}

/******************     post inventory end      **********************/

/*****************      products start *****************/
/**
 +---------------------------------------------------------------------------------------------
 * Get product info by asin . AmazonService::getMatchingProduct
 +---------------------------------------------------------------------------------------------
 * @param
 * 						$asinlist		filter by asin 
 * 						$return_type	return type xml or json
 +---------------------------------------------------------------------------------------------
 * @return 				  Array
 *				 		  (
 * 		        			 [success] => 1
 *				 		     [message] => 
 * 		        			 [report] => Array
 *		 		                  (
 *       				              [0] => Array
 * 	       				                  (
 * 	                       				      [ReportId] => 34292388144
 *		 		                              [ReportType] => _GET_FLAT_FILE_OPEN_LISTINGS_DATA_
 * 	       				                      [AvailableDate] => 2014-04-17 06:17:11
 *		 		                          )	  
 * 	       				          )
 *		 		          )                
 +---------------------------------------------------------------------------------------------
 * @desc				get product info by asin ,  asinlist <= 10		          
 **/
if (strtolower($action) == strtolower("get_matching_products")){
	if (! empty($asins)){
		$asinlist = json_decode($asins,true);
		if (empty($return_type)){
			$return_type ='xml';
		}
		
		$results = AmazonService::getMatchingProduct($asinlist,$return_type);
	}
}

if (strtolower($action) == strtolower("get_matching_products2")){
	if (! empty($asins)){
		$asinlist = json_decode($asins,true);
		if (empty($return_type)){
			$return_type ='xml';
		}

		$results = AmazonService::getMatchingProduct2($asinlist,$return_type);
	}
}

//GetMatchingProductForId	max = 20 个请求	每秒钟 5 件商品
if (strtolower($action) == strtolower("GetMatchingProductForId")){
	if (! empty($parms['idList'])){
		
		if (empty($parms['return_type'])){
			$return_type ='xml';
		}else{
			$return_type = $parms['return_type'];
		}
		
		$results = AmazonService::GetMatchingProductForId($parms['idList'],$parms['idType'],$return_type);
	}
}

//GetMatchingProductForId2	max = 20 个请求	每秒钟 5 件商品
if (strtolower($action) == strtolower("GetMatchingProductForId2")){

	if (! empty($idList)){
		if (empty($return_type)){
			$return_type ='xml';
		}

		$results = AmazonService::GetMatchingProductForId2(json_decode($idList,true),$idType,$return_type);
	}
}

if (strtolower($action) == strtolower("GetProductCategoriesForSKU")){
    
    if (empty($return_type)){
        $return_type ='xml';
    }
    $results = AmazonService::GetProductCategoriesForSKU($SellerSKU,$return_type);
}


//GetProductSmallImage
if (strtolower($action) == strtolower("GetProductSmallImage")){
	if (!empty($ASIN)&&!empty($MARKETPLACE)){
		if (!empty($purge)){
			$results = AmazonService::GetProductSmallImage($MARKETPLACE,$ASIN,true);
		}else{
			$results = AmazonService::GetProductSmallImage($MARKETPLACE,$ASIN,false);
		}
	}
}

/*****************      products end *****************/

/*****************      reports start *****************/
if (strtolower($action) == strtolower("GetAmazonSubmitFeedResult")){
	$results = AmazonService::GetSubmitFeedResult($parms['submit_id']);
	$results['apiCallTimes'] = AmazonAPI::$AMAZON_API_CALL_TIMES;
}

/**
 +---------------------------------------------------------------------------------------------
 * Get the report result by  reportID. AmazonService::GetAmazonReportIds
 +---------------------------------------------------------------------------------------------
 * @param
 * 						report_id , 
 * 						field_name
 +---------------------------------------------------------------------------------------------
 * @return 				 
 * 		eg.(merchant list data report) : Array
 *				 		  (
 * 		        			 [success] => true
 *				 		     [message] => 
 *							 [ResponseHeaderMetadata]=>
 *							 [report_id]=> (string)report id
 *							 [ContentMd5]=>
 *							 [RequestId]=>
 *
 * 		        			 [Contents] =>  Array
 *		 		                  (
 *       				              
 *       								[0] => Array
 * 	       				                  (
 * 	                       				      [item-name] => 
 *		 		                              [listing-id] => 
 * 	       				                      [seller-sku] => 
 * 											  [price]=>	
 *  										  [price]=>	
 * 	                       				      [quantity]=>	
 * 	                       				      [open-date]=>	
 * 	                       				      [product-id-type]=>	
 * 	                       				      [item-note]=>	
 * 	                       				      [item-condition]=>	
 * 	                       				      [will-ship-internationally]=>	
 * 	                       				      [expedited-shipping]=>	
 * 	                       				      [product-id]=>	
 * 	                       				      [pending-quantity]=>
 * 	                       				      [fulfillment-channel]=>
 *		 		                          )	,.....
 *						  
 * 	       				          )
 *		 		          )                
 +---------------------------------------------------------------------------------------------
 * @desc				if the report array has no element, the report is not ready,Load later	 		          
 **/
if ($action=="get_report_result"){
	if (empty($parms['field_name'])){
		$field_name = '';
	}else{
		$field_name = $parms['field_name'];
	}
	$results = AmazonService::retrieve_report_result($parms['report_id'] , $field_name);
}

/*****************      reports end *****************/

/*****************      fba start *****************/
if (strtolower($action) == strtolower("retrieve_amz_fba_prod_inventory")){
	$results = AmazonService::retrieve_report_result($parms['report_id'] , $field_name);
}
/*****************      fba end *****************/
if ($action == ""){
	echo " no api action is required ! <br>";
}
else{
	if (! empty($results)){
		echo json_encode($results);
 

	}else{
		echo "result is empty !";
	}
}
?>

