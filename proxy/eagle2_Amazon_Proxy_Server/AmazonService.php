<?php
class AmazonService{

//Amazon European proxy server machine
	const	Proxy_Server='http://amazon-proxy.huayultd.com/api.php';
	static $config = null;
	
public static function setupAmazonConfig($cfg){
	self::$config = $cfg;
	AmazonAPI::set_amazon_config(self::$config);
}	
	


/**
 +---------------------------------------------------------------------------------------------
 * 获取Amazon Unshipped 的 某个时间以后的 orders
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param fromDateTime 		从某个时间以后的Orders将会被获取，该时间已使用UTC时间，系统会自动转成ISO 8601格式
 +---------------------------------------------------------------------------------------------
 * @return				返回是否成功。错误message 以及成功插入或者更新过的 order id array
 * 							array( 'success' = true,'message'='......' , 
 * 								'orders'= array(...)
 * 							     )
 * @description			获取Amazon Unshipped 的 某个时间以后的 orders
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/03/20				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function retrieveAmazoneOrders($fromDateTime = '',$status='',$type='MFN', $toDateTime=''){
	//default status blank is unshipped orders
	if ($fromDateTime == '')
		$fromDateTime = TimeUtil::getNowDate();

	//ys add log starts
	self::makeLog("Try to retrieve Orders for fromDateTime:$fromDateTime, status=$status, type=$type, toDateTime=$toDateTime ");
	
	$results = AmazonAPI::GetAmazonOrderList($fromDateTime,$status, $type, $toDateTime);
	return $results;
}//end of function retrieveAmazoneOrders

/**
 +---------------------------------------------------------------------------------------------
 * 获取Amazon Order Items Detail
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param orderid 		从某个Amaozn Order的 item detail
 +---------------------------------------------------------------------------------------------
 * @return				返回是否成功。以及改order items 的array 
 * 							array( 'success' = true,'message'='......' ,
 * 								'item'= array(...)
 * 							     )
 * @description			获取Amazon Order Items Detail
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/03/20				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function retrieveAmazoneOrdersItems($orderid){
	//ys add log starts
	self::makeLog("Try to retrieve Orders items for $orderid");
	
	$results = AmazonAPI::GetAmazonOrderItems($orderid );
	return $results;
}//end of function retrieveAmazoneOrders



public static function GetProductTopOfferByAsin($marketplaceId,$asin,$operation,$response_group){
	//var_dump($marketplaceId);
	//var_dump($asin);
	//var_dump($response_group);
	$results = AmazonAPI::getProductTopOfferByAsin($marketplaceId,$asin,$operation,$response_group);
	return $results;
}//end of function GetProductTopOfferByAsin

public static function GetProductAttributesByAsin($marketplaceId,$asin,$operation,$response_group){
	$results = AmazonAPI::getProductAttributesByAsin($marketplaceId,$asin,$operation,$response_group);
	return $results;
}//end of function GetProductAttributesByAsin


/*
public static function getAmazoneAFNOrders($config=''){
	$config=AmazonConst::$config;
	AmazonAPI::set_amazon_config($config);	
	$order = array();
	$status = ucfirst($status); // change the first letter to upper
	$order = AmazonAPI::getAmazonAFNOrder($status, 'AFN' ,$storeview ,$dlup);
	echo print_r($order,true);
}

public static function getOrderInvoice($config=''){
	$config=AmazonConst::$config;
	AmazonAPI::set_amazon_config($config);	
	$cache = AmazonAPI::get_amazon_invoice_cache();
	echo json_encode($cache);
}	

public static function getMissingOrders($config=''){
	$config=AmazonConst::$config;
	AmazonAPI::set_amazon_config($config);	
	$orderArr = json_decode($orderlist,true);
	$order = AmazonAPI::getMissOrder($orderArr,$storeview);
	
	echo json_encode($order);
}
*/
/******************     refresh order  end      **********************/


/******************     change order status start      **********************/
/**
 +---------------------------------------------------------------------------------------------
 * 修改 Amazon Order 发货递送信息，
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param order id 			Amazon Order id
 * @param items				array of Items of sku and qty in this package: 
 * 							e.g.: array( array('ItemCode'=>12313423556, 'ItemShipQty'=>2) , 
 * 										 array('ItemCode'=>78913422432, 'ItemShipQty'=>1)
 * 									)
 * @param Delivery Number 	递送物流号，如果填写了非空的值，amazon 会自动把订单变成 Shipped 状态
 * @param Frieght			物流递送公司
 * @param Ship date			递送时间，normal 的格式即可，系统会自动变成 UTC 格式，如果忽略，默认使用当前时间
 +---------------------------------------------------------------------------------------------
 * @return				array ( 'exception' => 0 'submit_id' => 8112829778, success='1', retryCount=0 )
 * @description			修改 Amazon Order 信息，
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/03/20				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function ShipAmazonOrder($orderid , $items, $freight='',   $deliveryno ='', $shipdate=''){
	//ys add log starts
	self::makeLog("Try to ship order $orderid, freight: $freight ,deliveryno: $deliveryno , shipdate: $shipdate ");
	
	return AmazonAPI::ShipAmazonOrder($orderid , $items, $freight,  $deliveryno ,  $shipdate);
}//end of function ChangeAmazonOrder

/**
 +---------------------------------------------------------------------------------------------
 * 批量修改 Amazon Order 发货递送信息，
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param $orders			array of orders to shipped
 +---------------------------------------------------------------------------------------------
 * @return				array ( 'exception' => 0 'submit_id' => 8112829778, success='1', retryCount=0 )
 * @description			修改 Amazon Order 信息，
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		dzt		2015/12/14				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function batchShipAmazonOrders($orders){
	//ys add log starts
	self::makeLog("Try to ship orders:".print_r($orders,true));

	return AmazonAPI::batchShipAmazonOrders($orders);
}//end of function ChangeAmazonOrder

/**
 +---------------------------------------------------------------------------------------------
 * Refund amazon Order 中的某一款或者多款产品，每款产品只能refund全部数量，不能 refund 其中的部分数量
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param order id 			Amazon Order id
 * @param items_sku_array 	array of 要退货的产品sku， array('SK1','SK2')
 +---------------------------------------------------------------------------------------------
 * @return				array ( 'exception' => 0 'submit_id' => 8112829778, success='1', retryCount=0 )
 * @description			Refund amazon Order 中的某一款或者多款产品，每款产品只能refund全部数量，不能 refund 其中的部分数量
 * 						调用本方法以后，amazon 服务器端会自动完成退款，客户无需再手动操作Amazon或者paypal退款
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/03/20				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function RefundAmazonOrderProduct($refund_order_info){	
	//ys add log starts
	self::makeLog("Try to RefundAmazonOrderProduct for ".$refund_order_info['AmazonOrderId']);
	
	return AmazonAPI::refundAmazonOrderProduct($refund_order_info);
}//end of function refundAmazonOrderProduct

/**
 +---------------------------------------------------------------------------------------------
 * Refund entire amazon Order
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param order id 			Amazon Order id
 +---------------------------------------------------------------------------------------------
 * @return				array ( 'exception' => 0 'submit_id' => 8112829778 )
 * @description			Refund entire amazon Order
 * 						调用本方法以后，amazon 服务器端会自动完成退款，客户无需再手动操作Amazon或者paypal退款
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/03/20				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function CancelEntireAmazonOrder($refund_order_info){
	//ys add log starts
	self::makeLog("Try to CancelEntireAmazonOrder for ".$refund_order_info['AmazonOrderId']);
	
	return AmazonAPI::cancelEntireAmazonOrder($refund_order_info);
}//end of function cancelEntireAmazonOrder


/**
 +---------------------------------------------------------------------------------------------
 * Get Submit Feed Result
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param submit id 	submit id
 +---------------------------------------------------------------------------------------------
 * @return				array ( 'success' => 0 'ProcessingReport' => array )
 * @description			获取某个Submit feed 的result，里面包换该submit feed 是否已经被成功执行
 * 						如果submit feed 没有被成功执行，返回error message包含在ProcessingReport 里面
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/03/20				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function GetSubmitFeedResult($submit_id){
	$result = AmazonAPI::get_submit_feed_result( $submit_id );
	$xmlData = $result['data'];
	//$data1 = self::fixArray($data);
	
	//ys add log starts
	self::makeLog("Try to GetSubmitFeedResult for submit id:".$submit_id);
	
	if ( $result['success'] and 
		isset($xmlData->Message->ProcessingReport->StatusCode) and 
		$xmlData->Message->ProcessingReport->StatusCode == 'Complete' 
		and  (isset( $xmlData->Message->ProcessingReport->ProcessingSummary->MessagesWithError ) and
		$xmlData->Message->ProcessingReport->ProcessingSummary->MessagesWithError == 0   )
	)
		$rtn['success'] = true;
	else{
		$rtn['success'] = false;
		$rtn['message'] = isset( $xmlData->Message->ProcessingReport->Result->ResultDescription )?$xmlData->Message->ProcessingReport->Result->ResultDescription:"";
	}
	
	if (isset($xmlData->Message->ProcessingReport->StatusCode))
		$rtn['ProcessingReport']['status'] = $xmlData->Message->ProcessingReport->StatusCode;
	else 
		$rtn['ProcessingReport'] =  $xmlData->Message->ProcessingReport;

	//if Failed to get report, show the error message
	if (!$result['success'] ){
	
		$rtn['message'] = $result['message'];
		if (isset($result['statusCode'])) $rtn['statusCode'] = $result['statusCode'];
		if (isset($result['errorCode'])) $rtn['errorCode'] = $result['errorCode'];
		if (isset($result['errorType'])) $rtn['errorType'] = $result['errorType'];
		
	}
	
 
	
	return $rtn;
}

public static function GetStoreAllProduct($report_id){	
	//ys add log starts
	self::makeLog("Try to GetStoreAllProduct for report_id:".$report_id);
	
	$rtn['success'] = true;
	$rtn['message'] = "";
	
	$result = AmazonAPI::retrieve_report_result($report_id);
	if (!($result['success'])){
		$rtn['success'] = false;
		$rtn['message'] = $result['message'];
		$rtn['products'] = array();
		return $rtn;
	}
	
	//parse the content and convert it to format of products
	$lines = explode("\n",$result['Contents']);
	$items = array();
	$totalCount = 0;
	foreach ($lines as $line ){
		if ($totalCount > 10) continue; //ystest return 10 results is OK for test
		$item = explode("\t",$line);
		if (strcasecmp($item[0] , "sku") == 0)
			$col_name = $item;
	
		AmazonAPI::array_remove_empty($col_name);
		
		if ($item[0] != 'sku' && $item[0] != '' ){
			if ($col_name[0] != "") $item = array_combine($col_name , $item);
			$items[] = $item;
		}
		$totalCount++;
	}//end of each line
	
	$rtn['products'] = $items;
	//the original contents are huge, do not pass it
	//$rtn['Contents'] = $result['Contents'];
	return $rtn;
}

public static function fixArray($arr){
	$arr1 = array();
	foreach ($arr as $k=>$v){
		if (is_array($v))
			$arr1[$k] = self::fixArray($v) ;
		else			
			$arr1[$k] = $v;
	}
	return $arr1;
}

public static function makeLog($log){
	//ys add log starts
	$nowDate = TimeUtil::getNow();
	$fp=fopen(dirname(__FILE__) . DIRECTORY_SEPARATOR."amazon_proxy_".substr($nowDate,0,10).".log","a");
	fwrite($fp,"$nowDate: Call IP:".$_SERVER["REMOTE_ADDR"]." MerchantID:".AmazonAPI::$AMAZON_MERCHANT_ID." MarketPlaceId:".AmazonAPI::$AMAZON_MARKETPLACE_ID." $log \n");
	//ys add log ends
}

/******************     change order status end      **********************/

/******************     post inventory start      **********************/
/**
 +---------------------------------------------------------------------------------------------
 * Post Amazon Product Inventory Info
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param $prods 		array(  array ('sku'=>'SKES1','qty'=>100),array ('sku'=>'SKES2','qty'=>50),...
 * 							 )
 +---------------------------------------------------------------------------------------------
 * @return				array ( 'exception' => 0, 'success' => true, 'submit_id'=>'A1232' )
 * @description			Post prods qty to amazon shop. 
 * 						出入参数支持该网点的所有 产品同时传入，那么一次提交即可。
 * 						请注意，这是Amazon异步提交 submit feed，需要等候amazon 服务器执行，一般需要等待5 - 30 分钟才会生效。
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/03/28				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function postAmazonProductInventory($prods){
	//ys add log starts
	self::makeLog("Try to postAmazonProductInventory ");
	
	
	$result = AmazonAPI::post_prods_inventory($prods);
	//result containing array('exception'=>, 'submit_id'=>'A1232', 'success'=>true)
	return $result;
}


public static function RequestAmazonReport( $report_type , $start_date , $end_date , $report_options){
	$result = AmazonAPI::request_report( $report_type , $start_date , $end_date , $report_options);
	return $result;
}

//below function CheckRequestResult is obsoleted
public static function CheckRequestResult($report_type , $requst_id){
	return AmazonAPI::checkRequestResult( $report_type , $requst_id);
}

public static function GetAmazonReportIds($request_id){
	//ys add log starts
	self::makeLog("Try to GetAmazonReportIds $request_id");
	
	return AmazonAPI::get_report_id( $request_id);
}

/*
public static function checkAmazonRequest($config=''){
	$config=AmazonConst::$config;
	AmazonAPI::set_amazon_config($config);
	$result = AmazonAPI::checkRequestResult($storeview , $report_type , $request_id);
	echo json_encode($result);
}



public static function getAmazonProductList($config=''){
	$config=AmazonConst::$config;
	AmazonAPI::set_amazon_config($config);
	$result = AmazonAPI::retrieve_prod_list($storeview,$reportid);
	echo json_encode($result);
}
*/


/******************     post inventory end      **********************/

/*****************      products start *****************/
/**
 +---------------------------------------------------------------------------------------------
 * get Amazon Product Info by asin
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param $asinlist 		array('asin1' , 'asin2',...
 * 							 )
 * 		  $return_type		xml or json
 +---------------------------------------------------------------------------------------------
 * @return				array ( 'exception' => 0, 'success' => true, 'submit_id'=>'A1232' )
 * @description			get product info from amazon shop. 
 * 						请注意，这里asin最多 10个。
 * 						限制 ：GetMatchingProduct	20 个请求	每秒钟 2 件商品
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		lkh		2014/11/13				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function getMatchingProduct($asinlist, $return_type='xml'){
	$result = AmazonAPI::getMatchingProduct($asinlist,$return_type);
	return $result;
}

/**
 +---------------------------------------------------------------------------------------------
 * get Amazon Product Info by asin
 +---------------------------------------------------------------------------------------------
 * @description			基本同上，只是根据amazon api返回重新 format个 返回数据
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		dzt		2015/07/15				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function getMatchingProduct2($asinlist, $return_type='xml'){
	$result = AmazonAPI::getMatchingProduct2($asinlist,$return_type);
	return $result;
}

/**
 +---------------------------------------------------------------------------------------------
 * get Amazon Product Info by custom id 
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param $IdList 		array('asin1' , 'asin2',...
 * 							 )
 * 		  $IdType		ASIN、GCID、SellerSKU、UPC、EAN、ISBN 和 JAN
 * 		  $return_type	xml or json
 +---------------------------------------------------------------------------------------------
 * @return				array ( 'exception' => 0, 'success' => true, 'result'=>array() )
 * @description			get product info from amazon shop. 
 * 						请注意，这里idlist最多 5个。
 * 						限制 ： GetMatchingProductForId	20 个请求	每秒钟 5 件商品
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		lkh		2014/11/13				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function getMatchingProductForId($IdList, $IdType, $return_type='xml'){
	$result = AmazonAPI::getMatchingProductForId($IdList, $IdType, $return_type);
	return $result;
}


/**
 +---------------------------------------------------------------------------------------------
 * get Amazon Product Info by custom id 
 +---------------------------------------------------------------------------------------------
 * @description			基本同上，只是根据amazon api返回重新 format个 返回数据
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		dzt		2015/07/15				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function getMatchingProductForId2($IdList, $IdType, $return_type='xml'){
	$result = AmazonAPI::getMatchingProductForId2($IdList, $IdType, $return_type);
	return $result;
}

/**
 +---------------------------------------------------------------------------------------------
 * Returns the parent product categories that a product belongs to, based on SellerSKU.
 +---------------------------------------------------------------------------------------------
 * @description			基本同上，只是根据amazon api返回重新 format个 返回数据
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		dzt		2017/03/01				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function GetProductCategoriesForSKU($SellerSKU, $return_type='xml'){
    $result = AmazonAPI::GetProductCategoriesForSKU($SellerSKU, $return_type);
    return $result;
}

/**
 +---------------------------------------------------------------------------------------------
 * get Amazon Product small image by aws
 +---------------------------------------------------------------------------------------------
 * @description			根据产品asin码获取 产品小图
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		dzt		2016/05/11				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function GetProductSmallImage($MARKETPLACE,$ASIN,$purge=false){
	$dsn = "mysql:host=localhost;dbname=proxy;charset=utf8";
	// TODO proxy mysql account
	$db = new PDO($dsn, "root","");
	
	$rtn['success'] = false;
	$rtn['SmallImageUrl'] = '';
	$rtn['message'] = '';
	list($ret,$smallImageUrl)= AmazonProductAdsAPI::getOneMediumImageUrlByASIN($MARKETPLACE,$ASIN,$db,$purge);
	if ($ret===false){
		write_log("GetProductSmallImage asin:".$ASIN." marketplaceId:".$MARKETPLACE." error_message:".$smallImageUrl
		        ." MERCHANT_ID:".AmazonAPI::$AMAZON_MERCHANT_ID." ACCESS_KEY_ID:".AmazonAPI::$AMAZON_ACCESS_KEY_ID
		        ." ACCESS_KEY_ID:".AmazonAPI::$AMAZON_SECRET_ACCESS_KEY, "info");
		$rtn['message'] = "asin:".$ASIN." marketplaceId:".$MARKETPLACE.",purge:".$purge." error_message:".$smallImageUrl;
	}else{
		write_log("GetProductSmallImage asin:".$ASIN.",marketplaceId:".$MARKETPLACE.",purge:".$purge.",image:".$smallImageUrl, "info");
		$rtn['success'] = true;
		$rtn["SmallImageUrl"] = $smallImageUrl;
	}
	
	return $rtn;
}

/*****************      products end *****************/

/*****************      reports start *****************/
/*
public static function testAmazonReport($config=''){
	$config=AmazonConst::$config;
	AmazonAPI::set_amazon_config($config);
	$skulist = json_decode($skus,true);
	// $report_id = '29884579244';
	$result = AmazonAPI::_retrieve_report_data($storeview,$report_id , $field_name);
	// echo json_encode($result);
	echo print_r($result,true);
}
*/
/*****************      reports end *****************/

/*****************      fba start *****************/
/*
public static function retrieveAmazonFBAProductInventory($config=''){
	$config=AmazonConst::$config;
	AmazonAPI::set_amazon_config($config);
	$report_id = $reportid;
	$result = AmazonAPI::_retrieve_report_data($storeview,$report_id , $field_name);
	echo json_encode($result);
}
*/
/*****************      fba end *****************/
 

////////////////////////////////  Private functions   /////////////////////
/**
 +---------------------------------------------------------------------------------------------
 * 把Amazon 数据 写入到 Amazon Order 表中
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param data 		array 中包含所有amaozn order 的信息
 +---------------------------------------------------------------------------------------------
 * @return				返回数据写入的结果
 * 						array( 'success' = true,'message'='......' ,
 * 								'ignored'= false
 * 							  )
 * @description			把Amazon 数据 写入到 Amazon Order 表中
 * 		  				如果该Order已经存在表中，首先判断是否数据和 parm 中一致，如果一致，ignored 返回 true，否则，修改
 * 						同时Order Details 也会写入相应的表中，如果已存在的Order的Order Items数量和 parm 中的数据不一样，
 *                      会以新的数据写入一次。并且返回的 ignored 是 false
 * 
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/03/20				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function _insertAmazonOrder($data){
	$rtn['message']="";
	$rtn['success'] = true;
	$rtn['ignored'] = false;
	$needUpdate = false;
	$needCreate = false;
	//step 1:save the order header
	//step 1.1 if this order is existing, check some key field , if changed, upadte, otherwise, skip order header
	$model = AmzOrder::model()->findByPk($data['AmazonOrderId']);
	if (isset($model) and $model <> null){
		if ($model->BuyerEmail <> $data['BuyerEmail'] or $model->Amount <> $data['Amount'])
			$needUpdate = true ;
		else
			$rtn['ignored'] = true;
	}//end of order existing
	else {
		//step 1.2, if not existing, create it
		$model=new AmzOrder();
		$needCreate = true;
	}

	if ($needCreate or $needUpdate){
		$model->attributes = $data; //put the $data field values into model
		$model->create_time = TimeUtil::getNowTimestamp() ;
		 
		if ( $model->save() ){//save successfull
			$rtn['success'] = true;
		}else{
			$rtn['success'] = false;
			foreach ($model->errors as $k => $anError){
				$rtn['message'] .= "E_IstAmzOrder_001 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}
		}//end of save failed
	}//end of create or update order header

	return $rtn;
}

/**
 +---------------------------------------------------------------------------------------------
 * 把Amazon items数据 写入到 Amazon Order Items 表中
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param data 		array 中包含所有amaozn order items 的信息
 +---------------------------------------------------------------------------------------------
 * @return				返回数据写入的结果
 * 						array( 'success' = true,'message'='......' ,
 * 								'ignored'= false
 * 							  )
 * @description			把Amazon 数据 写入到 Amazon Order Details表中
 * 		  				如果已存在的Order的Order Items数量和 parm 中的数据不一样，
 *                      会以新的数据写入一次。并且返回的 ignored 是 false
 *                      如果已存在Order Items 并且record数量一致，则不进行数据更新，返回 ignored = true
 *
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/03/20				初始化
 +---------------------------------------------------------------------------------------------
 **/
public static function _insertAmazonOrderItems($orderid,$itemsData){
	$rtn['message']="";
	$rtn['success'] = true;
	$rtn['ignored'] = false;
	//check if there is existing items for this order already
	$existingItems_models = AmzOrderDetail::model()->findAll(
			'AmazonOrderId=:AmazonOrderId',
			array(':AmazonOrderId'=>$orderid ));
	if ( count($existingItems_models) <> count($itemsData) ){
		//purge the existing order items
		AmzOrderDetail::model()->deleteAll(
		'AmazonOrderId=:AmazonOrderId',
		array(':AmazonOrderId'=>$data['AmazonOrderId']));
		$rtn['ignored'] = false;
	}else
		$rtn['ignored'] = true;
	
	//loop to insert each item
	if (! $rtn['ignored']){
		foreach ($itemsData as $anItem){
			$anItem['AmazonOrderId'] = $orderid;
			$model=new AmzOrderDetail();
			$model->attributes = $anItem; //put the $data field values into model
		
			if ( $model->save() ){//save successfull
				$rtn['success']=true;
			}else{
				$rtn['success']=false;
				foreach ($model->errors as $k => $anError){
					$rtn['message'] .= "E_IstAmzOrderItem_101 ". ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
				}
			}//end of save failed
		}//end of each item sku
	}
	return $rtn;
}//end of function

/**
 +---------------------------------------------------------------------------------------------
 * 获取amazon　report 数据 
 +---------------------------------------------------------------------------------------------
 * @access static
 +---------------------------------------------------------------------------------------------
 * @param data 		array 中包含所有amaozn order items 的信息
 +---------------------------------------------------------------------------------------------
 * @return 				  Array
 *				 		  (
 * 		        			 [success] => true
 *				 		     [message] => 
 *							 [ResponseHeaderMetadata]=>
 *							 [report_id]=> (string)report id
 *							 [ContentMd5]=>
 *							 [RequestId]=>
 *
 * 		        			 [Contents] => Array
 *		 		                  (
 *       				              [0] => Array
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
 * @description			根据report id 获取 amazon 的报告 
 *
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		lkh		2014/11/06				初始化
 +---------------------------------------------------------------------------------------------
 **/
static function retrieve_report_result($report_id , $field_name){
	//check up report id 
	if (empty($report_id)) {
		return array('success' => false , 'message'=>"no exist Report ID" );
	}
	
	$row = AmazonAPI::retrieve_report_result($report_id);
	$row['report_id']=$report_id;
	
	$lines = explode("\n",$row['Contents']);
	$tempc = -1;
	foreach ($lines as $line ){
		//$item = explode("\t",urlencode(trim($line)));
		$tempc++;
		$item = explode("\t",trim($line));
		if (empty($field_name)){
			if ($tempc == 0){
				//get column header
				$col_name = $item;
				
				continue;
			}
			
			if ($item[0] != '' ){
				//redefine data format
				if ($col_name[0] != "") $item = array_combine($col_name , $item);
				$items[] = $item; 
				//echo "$tempc Got items for $storeview :".print_r($item,true)."<br>"; //ystest
			}
		}else{
			if (strcasecmp($item[0] , $field_name) == 0)
				$col_name = $item; 
				
			if ($item[0] != $field_name && $item[0] != '' ){
				if ($col_name[0] != "") $item = array_combine($col_name , $item);
				$items[] = $item; 
				$tempc++;
				//echo "$tempc Got items for $storeview :".print_r($item,true)."<br>"; //ystest
			}
		}
	}
	$row['Contents'] = $items;
	return $row;
} // end of retrieve_report_result
 

static function _saveXML($data){
	$suffix = date("YmdHi");
	$myFile = "ProxyAmazonXML$suffix.txt";
	// $data = date("Y-m-d h:i:s"). " : ".$data."\n";
	file_put_contents("amazon_xml/".$myFile , $data , FILE_APPEND );
}

}//end of class


?>

