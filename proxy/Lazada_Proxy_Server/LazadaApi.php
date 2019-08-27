<?php

class LazadaApi{
	
	const VERSION="1.0";
	const RET_FORMAT="JSON"; //返回json格式的数据，这里接口文档中没有提到
	const API_URL_PREFIX="http://sellercenter-api.lazada.com.";//跟不同的销售国家需要对应不同的api地址
	static $CONFIG=array("Version"=>"","Format"=>"","UserID"=>"");
	static $API_URL;
	static $API_KEY;
	static $MARKETPLACE;
	
	static $LazadaSite = ['co.id','my','ph','sg','co.th','vn'];
	// 部分站点未开通国际业务，所以不需对接。 lazada貌似越南没有开， Linio有5个，听说jumia只有一个
	static $Lazada_API_URL_CONFIG = array(
		// lazada站点
// 		'co.id'	=>"sellercenter-api.lazada.co.id",// 印尼 
// 		'my'	=>"sellercenter-api.lazada.com.my",// 马来西亚 
// 		'ph'	=>"sellercenter-api.lazada.com.ph",// 菲律宾
// 		'sg'	=>"sellercenter-api.lazada.sg",// 新加坡
// 		'co.th'	=>"sellercenter-api.lazada.co.th",// 泰国
// 		'vn'	=>"sellercenter-api.lazada.vn",// 越南 

		'co.id'	=>"api.sellercenter.lazada.co.id",// 印尼
		'my'	=>"api.sellercenter.lazada.com.my",// 马来西亚
		'ph'	=>"api.sellercenter.lazada.com.ph",// 菲律宾
		'sg'	=>"api.sellercenter.lazada.sg",// 新加坡
		'co.th'	=>"api.sellercenter.lazada.co.th",// 泰国
		'vn'	=>"api.sellercenter.lazada.vn",// 越南
	        
		// linio站点
		'ar'=>'sellercenter-api.linio.com.ar',
		'cl'=>'sellercenter-api.linio.cl',
		'co'=>'sellercenter-api.linio.com.co',// 哥伦比亚
		'ec'=>'sellercenter-api.linio.com.ec',// 厄瓜多尔
		'mx'=>'sellercenter-api.linio.com.mx',// 墨西哥
		'pa'=>'sellercenter-api.linio.com.pa',// 巴拿马
		'pe'=>'sellercenter-api.linio.com.pe',// 秘鲁
		've'=>'sellercenter-api.linio.com.ve',
		// jumia站点
		'dz'=>'sellercenter-api.dz.jumia.com',
		'cm'=>'sellercenter-api.jumia.cm',
		'eg'=>'sellercenter-api.jumia.com.eg',
		'gh'=>'sellercenter-api.jumia.com.gh',
		'ci'=>'sellercenter-api.jumia.ci',
		'ke'=>'sellercenter-api.jumia.co.ke',
		'ma'=>'sellercenter-api.jumia.ma',
		'ng'=>'sellercenter-api.jumia.com.ng',
		'sn'=>'sellercenter-api.jumia.sn',
		'tz'=>'sellercenter-api.jumia.co.tz',
		'ug'=>'sellercenter-api.jumia.ug',
		'za'=>'sellercenter-api.jumia.co.za',
	);
	
	// 以下时区均通过调用站点接口 查看response 返回的Timestamp 来查看时区的
	// Etc/GMT 这种表达是 php定义的，与GMT的+-相反，如南非是GMT+2 ，Etc/GMT-2
	static $LAZADA_SITE_TIMEZONE =array(
			// lazada站点
			'co.id'	=>"Etc/GMT-7",// 印尼
			'my'	=>"Etc/GMT-8",// 马来西亚
			'ph'	=>"Etc/GMT-8",// 菲律宾
			'sg'	=>"Etc/GMT-8",// 新加坡
			'co.th'	=>"Etc/GMT-7",// 泰国
			'vn'	=>"Etc/GMT-7",// 越南
			// linio站点
			'ar'=>"Etc/GMT+3",
			'cl'=>"Etc/GMT+3",// 智利
			'co'=>"Etc/GMT+5",// 哥伦比亚
			'ec'=>"Etc/GMT+5",
			'mx'=>"Etc/GMT+6",// 墨西哥
			'pa'=>"Etc/GMT+5",// 巴拿马
			'pe'=>"Etc/GMT+5",// 秘鲁
			've'=>"",// 委内瑞拉
			// jumia站点
			'dz'=>"Etc/GMT-1",// 安哥拉
			'cm'=>"Etc/GMT-1",// 喀麦隆
			'eg'=>"Etc/GMT-2",// 埃及
			'gh'=>"Etc/GMT+0",// 加纳
			'ci'=>"Etc/GMT+0",// 科特迪瓦
			'ke'=>"Etc/GMT-3",// 肯尼亚
			'ma'=>"Etc/GMT+0",// 摩洛哥
			'ng'=>"Etc/GMT-1",// 尼日利亚
			'sn'=>"Etc/GMT+0",// 塞内加尔
			'tz'=>"Etc/GMT-3",// 坦桑尼亚
			'ug'=>"Etc/GMT-3",// 乌干达
			'za'=>"Etc/GMT-2",// 南非
	);
	
	private static function _callLazada($getParameters=array(),$postParameters=false,$auth=false){
		$rtn['success'] = true;  //跟lazada之间的网站是否ok
		$rtn['message'] = '';
		
		$api_key=self::$API_KEY;
		$url=self::$API_URL."/?";
		
		ksort($getParameters);
// 		write_log("sorted parameters: ".json_encode($getParameters),"info");
		$params = array();
		
		foreach ($getParameters as $name => $value) {
			$params[] = rawurlencode($name) . '=' . rawurlencode($value);
		}
		$strToSign = implode('&', $params);
// 		write_log("string to sign: ".$strToSign,"info");
		// Compute signature and add it to the parameters
		$getParameters['Signature'] = rawurlencode(hash_hmac('sha256', $strToSign, $api_key, false));
// 		write_log("signature ".$getParameters['Signature'],"info");
		// Build Query String
		$queryString = http_build_query($getParameters, '', '&', PHP_QUERY_RFC3986);
// 		write_log("query string:===".$queryString,"info");
// 		echo $url.$queryString."  \n";
		// Open Curl connection
		$ch = curl_init();
		
		$port = null;
		$scheme = "";
		if($auth){
			$scheme = 'https://';
			$port = 443;
		} else {
			$scheme = 'http://';
			$port = 80;
		}
		
		curl_setopt($ch, CURLOPT_URL, $scheme.$url.$queryString);
		curl_setopt($ch, CURLOPT_PORT, $port);
		
		write_log("url:".$url.$queryString,"info");
		if(!empty($postParameters)){
// 			write_log("set post:".$postParameters,"info");
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $postParameters );
		}

		curl_setopt($ch, CURLOPT_TIMEOUT, 100);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15); //连接超时
		
		// Save response to the variable $data
// 		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// 		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$data = curl_exec($ch);
// 		write_log("return from lazada:".print_r($data,true),"info");
		
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		if ($curl_errno > 0) { // network error
			$rtn['message']="cURL Error $curl_errno : $curl_error";
			$rtn['success'] = false ;
			$rtn['response'] = "";
			$rtn['state_code'] = $curl_error;
			write_log("curl_error======== ".$curl_error);
			curl_close($ch);
			return $rtn;
		}
		
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		//echo $httpCode.$response."\n";
		if ($httpCode == '200' ){

			if (strtolower($getParameters['Format']) == 'xml'){	
				$rtn['response'] = json_decode(json_encode((array) simplexml_load_string($data)), true); 
			} else
				 $rtn['response'] = json_decode($data , true);
// 			write_log("response ".json_encode($rtn['response']));
			$rtn['state_code'] = 200;
			if ($rtn['response']==null){
				// json_decode fails
				$rtn['message'] = "Content return from proxy is not in json format.";
				$rtn['success'] = false ;
				$rtn['state_code'] = 500;
			}
		}else{ // network error
			$rtn['message'] = "Failed for ".$getParameters["Action"]." , Got error respond code $httpCode:$curl_error from Proxy";
			$rtn['success'] = false ;
			$rtn['response'] = "";
			$rtn['state_code'] = $httpCode;
		}
		curl_close($ch);// Close Curl connection
		return $rtn;
	}
	
	public static function setConfig($userId,$countryCode,$apiKey){
		self::$CONFIG["Version"]=self::VERSION;
		self::$CONFIG["Format"]=self::RET_FORMAT;
		self::$CONFIG["UserID"]=$userId;
		self::$MARKETPLACE = $countryCode;
// 		self::$API_URL=self::API_URL_PREFIX.$countryCode;
		self::$API_URL= self::$Lazada_API_URL_CONFIG[$countryCode];
        self::$API_KEY=$apiKey;
	}
	
	/**
	 * lazada get order list
	 *  dzt 2015/08/24 
	 */
	public static function getLazadaOrderList($param){
		$successResponse = array();
		$reqParams = array();
		$results = array();
		$results['success'] = true;
		$results['message'] = '';
		$orderList = array();
		$timeMS1=getCurrentTimestampMS();
		try{
			$reqParams["Action"] = 'GetOrders';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));// gmdate("Y-m-d\TH:i:s+0000",1441104684);
// 			self::setRequestDateTimeTimezone($Timestamp);// initial api site timezone
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
		
			// 处理api 接口参数
			// 获取订单List 以前Update Time 作滑动窗口 ，所以fromDateTime 参数是必须的。
			if(!isset($param["CreatedAfter"]) && !isset($param["UpdatedAfter"])){
				return array("success"=>false, "message"=>"CreatedAfter or UpdatedAfter is needed to filter orders.", "body"=>"");
			}
			
			// XXX dzt20161230 Created 和 Updated时间调整时区逻辑去掉，lazada 新接口目前只支持utc时区时间，linio则兼容,只要是ISO 8601时间，什么时区都可以
// 			CreatedAfter:	DateTime
// 			Limites the returned order list to those created after or on a specified date, given in ISO 8601 date format. Either CreatedAfter or UpdatedAfter are mandatory or else an error 'E018: Either CreatedAfter or UpdatedAfter is mandatory' wil be returned.
			if(isset($param["CreatedAfter"])){
				$CreatedAfterDateTime = new DateTime($param["CreatedAfter"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				
// 				self::setRequestDateTimeTimezone($CreatedAfterDateTime);// initial api site timezone
				$reqParams["CreatedAfter"] = $CreatedAfterDateTime->format(DateTime::ISO8601);
				
// 				CreatedBefore:	DateTime
				if(isset($param["CreatedBefore"])){
					$CreatedBeforeDateTime = new DateTime($param["CreatedBefore"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
// 					self::setRequestDateTimeTimezone($CreatedBeforeDateTime);// initial api site timezone
					$reqParams["CreatedBefore"] = $CreatedBeforeDateTime->format(DateTime::ISO8601);
				}
			}else if(isset($param["UpdatedAfter"])){
// 				UpdatedAfter:	DateTime
				$UpdatedAfterDateTime = new DateTime($param["UpdatedAfter"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
// 				self::setRequestDateTimeTimezone($UpdatedAfterDateTime);// initial api site timezone
				$reqParams["UpdatedAfter"] = $UpdatedAfterDateTime->format(DateTime::ISO8601);
				
// 				UpdatedBefore:	DateTime
				if(isset($param["UpdatedBefore"])){
					$UpdatedBeforeDateTime = new DateTime($param["UpdatedBefore"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
// 					self::setRequestDateTimeTimezone($UpdatedBeforeDateTime);// initial api site timezone
					$reqParams["UpdatedBefore"] = $UpdatedBeforeDateTime->format(DateTime::ISO8601);
				}
			}			
			
			// 	Status:	String
			// 	When set, limits the returned set of orders to loose orders, which Return only entries which fit the status provided. Possible values are pending, canceled, ready_to_ship, delivered, returned, shipped and failed.
			if (isset($param["status"]) && ''!=$param["status"])// else get all status
				$reqParams["Status"] = $param["status"];// 只能 filter 一个status
			
			if(isset($param['offset'])){
			    $reqParams['Offset'] = $param['offset'];
			    $offset = $param['offset'];
			    $usingOffset = true;
			}else{
			    $reqParams['Offset'] = 0;
			    $offset = 0;
			    $usingOffset = false;
			}
			
			$page = 1;
			$pageSize = 100;
			$totalCount = -1;//防止 第一次访问错误时跳出while 循环
			$errorCount = 0; // 失败尝试
			$counter = 0;
			$maxCount = 10;
			$hasGotAll = false;
			do{
				// 	Limit:	Integer
				// 	The maximum number of orders that should be returned.
				$reqParams["Limit"] = $pageSize;
					
				// 	Offset:	Integer
				// 	Numer of orders to skip at the beginning of the list (i.e., an offset into the result set; toghether with the Limit parameter, simple resultset paging is possible; if you do page through results, note that the list of orders might change during paging).
				
				$timeMS11=getCurrentTimestampMS();
				write_log("ST001:Get order list. (getLazadaOrderList) params:".json_encode($reqParams), "info");
				$response = self::_callLazada($reqParams);
				$timeMS12=getCurrentTimestampMS();
				
				write_log("t12_11 memory usage:".round(memory_get_usage()/1024/1024)."M. (getLazadaOrderList) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					$results['message'] = $response["message"];
					write_log("ST001 : ".$response["message"]." . (getLazadaOrderList) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					//errorCode: 1 --- E001: Parameter %s is mandatory
					//errorCode: 2 --- E002: Invalid Version
					//errorCode: 3 --- E003: Timestamp has expired
					//errorCode: 4 --- E004: Invalid Timestamp format
					//errorCode: 5 --- E005: Invalid Request Format
					//errorCode: 6 --- E006: Unexpected internal error
					//errorCode: 7 --- E007: Login failed. Signature mismatching
					//errorCode: 8 --- E008: Invalid Action
					//errorCode: 9 --- E009: Access Denied
					//errorCode: 10 --- E010: Insecure Channel
					//errorCode: 11 --- E011: Request too Big
					//errorCode: 429 --- E429: Too many requests
					//errorCode: 1000 --- E1000： Internal Application Error
					//errorCode: 30 --- E030: Empty Request
			
					//errorCode: 14 --- E014: "%s" Invalid Offset
					//errorCode: 17 --- E017: "%s" Invalid Date Format
					//errorCode: 19 --- E019: "%s" Invalid Limit
					//errorCode: 36 --- E036: Invalid status filter
					//errorCode: 74 --- E074: Invalid sort direction.
					//errorCode: 75 --- E075: Invalid sort filter.
					$skipRetryErrorCodeArr = array(1,2,3,4,5,6,7,8,9,10,11,14,17,19,30,36,74,75,429,1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['success'] = false;
						$results['message'] = $errorMessage;
						break;
					}
					write_log("ST001 : $errorMessage . (getLazadaOrderList) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
				// $results['response'] = $successResponse;
				$totalCount = $response["response"]["SuccessResponse"]["Head"]["TotalCount"];
				
				// dzt20161116 lazada貌似改变了返回结构["Body"]["Orders"]["Order"] 变成["Body"]["Orders"]
				if (isset($successResponse["Body"]["Orders"]["Order"]["OrderId"])){// 只有一个订单时，返回结构不一样
					$orderList = array_merge($orderList,array($successResponse["Body"]["Orders"]["Order"]));
				}elseif(!empty($successResponse["Body"]["Orders"]["Order"])){
					$orderList = array_merge($orderList,$successResponse["Body"]["Orders"]["Order"]);
				}elseif(isset($successResponse["Body"]["Orders"])){// dzt20161116 现在返回尽管只有一个产品也是数组的
				    if(!empty($successResponse["Body"]["Orders"]))
				        $orderList = array_merge($orderList,$successResponse["Body"]["Orders"]);
				}else{
				    $errorCount++;
					$results['message'] = "Reture data format has changed,cannot get order info.";
					write_log("ST001 : ".$results["message"]." . (getLazadaOrderList) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
				
				//最后一页了,不需要再翻页
				if (count($orderList) < $reqParams['Limit'] * $page){
				    $hasGotAll = true;
				}
				
				$page++;
				$reqParams["Offset"] = ($page-1) * $reqParams['Limit'] + $offset;
				
				// dzt20161129 使用offset与否，判断终止循环条件不一样，使用offset循环最多run10次，否则直到拉完订单再结束循环
				if($usingOffset){
				    $counter++;
				}else{
				    $counter = count($orderList);
				    $maxCount = $totalCount;
				}
				
				// dzt20161129 使用offset ，开始-结束时间的订单结果可能分多次返回
				// 这样由于请求可能隔了一段时间再继续获取，所以TotalCount 可能在带offset再请求的时候会有变化（未证实）
// 			}while (($totalCount == -1 || count($orderList) < $totalCount) && $errorCount < 15);
			}while (($totalCount == -1 || $counter < $maxCount) && $hasGotAll != true && $errorCount < 15);
			
			
			if($totalCount == -1){// || count($orderList) < $totalCount
				$results['success'] = false;
			}

			if($results['success']){
				$results['message'] = "";
				
				// 转换时间
				$responseTime = new DateTime($successResponse['Head']['Timestamp']);
				foreach ($orderList as &$oneOrder){
					if(!empty($oneOrder['CreatedAt'])){
						$createAtTime = new DateTime($oneOrder['CreatedAt'] , $responseTime->getTimezone());
						$oneOrder['CreatedAt'] = $createAtTime->format(DateTime::ISO8601);
					}
						
					if(!empty($oneOrder['UpdatedAt'])){
						$UpdatedAtTime = new DateTime($oneOrder['UpdatedAt'] , $responseTime->getTimezone());
						$oneOrder['UpdatedAt'] = $UpdatedAtTime->format(DateTime::ISO8601);
					}
					
					if(!empty($oneOrder['PromisedShippingTime'])){
						$UpdatedAtTime = new DateTime($oneOrder['PromisedShippingTime'] , $responseTime->getTimezone());
						$oneOrder['PromisedShippingTime'] = $UpdatedAtTime->format(DateTime::ISO8601);
					}
					
					if(!empty($oneOrder['AddressUpdatedAt'])){
						$UpdatedAtTime = new DateTime($oneOrder['AddressUpdatedAt'] , $responseTime->getTimezone());
						$oneOrder['AddressUpdatedAt'] = $UpdatedAtTime->format(DateTime::ISO8601);
					}
				}
			}
			
			$timeMS2=getCurrentTimestampMS();
			write_log("getLazadaOrderList UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE.",TotalCount:$totalCount ,t2_1=".($timeMS2-$timeMS1),"info");
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
			// 		$results['apiCallTimes'] = self::$API_CALL_TIMES;
			$results['orders'] = $orderList;
			if($usingOffset){
			    $results['offset'] = $reqParams["Offset"];// 返回下次请求的offset
			    $results['hasGotAll'] = ($hasGotAll == true)?1:0;
			}
			return $results;
		}catch (Exception $e){
			$results['success'] = false;
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * lazada get order items
	 * dzt 2015/08/26
	 */
	public function getLazadaOrderItems($param){
		$items = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
			$reqParams["Action"] = 'GetMultipleOrderItems';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
				
			// 处理api 接口参数
			if(!isset($param["OrderId"])){
				return array("success"=>false, "message"=>"'OrderId' is needed to get a list of order items.", "body"=>"");
			}
			
			// 可以提交多个 OrderId ， 未知上限。格式为： [orderid1,orderid2]的字符串。 OrderId 为 getOrders 接口返回的OrderId , 与 sellercenter 显示的订单号无关 , sellercenter 对应的是getOrders 接口的 OrderNumber
			$reqParams['OrderIdList'] = json_encode(explode(',',$param["OrderId"]));
			
			write_log("ST001:Start to get order items:OrderIdList(".$reqParams['OrderIdList']."). (getLazadaOrderItems) ","info");
			
			$timeMS1=getCurrentTimestampMS();
			do{
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					$results['message'] = $response["message"];
					write_log("ST001 : ".$response["message"]." . - ".$param["OrderId"]." . (getLazadaOrderItems) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . - ".$param["OrderId"]." . (getLazadaOrderItems) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					
					//errorCode: 16 --- E016: Invalid Order ID
					//errorCode: 37 --- E037: One or more order id in the list are incorrect
					//errorCode: 38 --- E038: Too many orders were requested
					//errorCode: 39 --- E039: No orders were found
					//errorCode: 56 --- E056: Invalid OrdersIdList format. Must use array format [1,2]
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000, 16, 37, 38, 39, 56);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['message'] = $errorMessage;
						break;
					}
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
				// $results['response'] = $successResponse;
				$orders = array();
				if (isset($successResponse["Body"]["Orders"]["Order"]["OrderId"])){// 只有一个订单时，返回结构不一样
					$orders = array($successResponse["Body"]["Orders"]["Order"]);
				}elseif(!empty($successResponse["Body"]["Orders"]["Order"])){// dzt20161116
					$orders = array_merge($orders,$successResponse["Body"]["Orders"]["Order"]);
				}elseif(isset($successResponse["Body"]["Orders"])){
				    if(!empty($successResponse["Body"]["Orders"]))
				        $orders = array_merge($orders,$successResponse["Body"]["Orders"]);
				}else{
				    $errorCount++;
					$results['message'] = "Reture data format has changed,cannot get order info.";
					write_log("ST001 : ".$results["message"]." . (getLazadaOrderItems) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
				
				foreach ($orders as $oneOrder){
					if(isset($oneOrder["OrderItems"]["OrderItem"]["OrderId"])){// 订单只有一个item 与多个items是结构不一样
						$items[$oneOrder["OrderId"]] = array($oneOrder["OrderItems"]["OrderItem"]);
					}elseif(!empty($oneOrder["OrderItems"]["OrderItem"])){// dzt20161116
    					$items[$oneOrder["OrderId"]] = $oneOrder["OrderItems"]["OrderItem"];
    				}else{
    				    $items[$oneOrder["OrderId"]] = $oneOrder["OrderItems"];
    				}
				}
				break;
					
			}while ($errorCount < 15);
			$timeMS2=getCurrentTimestampMS();
			
			// ST002 get product photos
			write_log("ST002:get product photos. - ".$param["OrderId"]."(getLazadaOrderItems)", "info");
			$dsn = "mysql:host=localhost;dbname=proxy;charset=utf8";// @todo 转移proxy 要创建数据库和table
			// TODO proxy mysql account
			$db = new PDO($dsn, "root","");
			if (count($items) >= 1){
				$results['success'] = true;
				$responseTime = new DateTime($successResponse['Head']['Timestamp']);
				foreach($items as &$orderItems){
					foreach ($orderItems as &$orderItem){
						// 返回的是160*160的图片，这里作为缩略图来使用
						list($ret,$smallImageUrl,$productUrl)= self::_getLazadaProductMainImage($orderItem['ShopSku'],$orderItem['Sku'],$db);
						if ($ret===false){
							$orderItem["SmallImageUrl"]="";
							write_log("Sku:".$orderItem["Sku"]." marketplaceId:".self::$MARKETPLACE." error_message:".$smallImageUrl, "info");
							continue;
						}
						write_log("Sku:".$orderItem["Sku"]." image:".$smallImageUrl, "info");
						write_log("smallImageUrl:".print_r( $smallImageUrl,true), "info");
						$orderItem["SmallImageUrl"] = $smallImageUrl;
						$orderItem["ProductUrl"] = $productUrl;
						
						if(!empty($orderItem['CreatedAt'])){
							$createAtTime = new DateTime($orderItem['CreatedAt'] , $responseTime->getTimezone());
							$orderItem['CreatedAt'] = $createAtTime->format(DateTime::ISO8601);
						}
						if(!empty($orderItem['UpdatedAt'])){
							$UpdatedAtTime = new DateTime($orderItem['UpdatedAt'] , $responseTime->getTimezone());
							$orderItem['UpdatedAt'] = $UpdatedAtTime->format(DateTime::ISO8601);
						}
					}
				}
			}
			
			$timeMS3=getCurrentTimestampMS();
			write_log("getLazadaOrderItems Done - ".$param["OrderId"]." t2_1=".($timeMS2-$timeMS1)."t3_2=".($timeMS3-$timeMS2)."t3_1=".($timeMS3-$timeMS1),"info");
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			
			$results['items'] = $items;
// 			if($totalCount == -1 || count($orderList) < $totalCount){
// 				$results['success'] = false;
// 			}
			
			return $results;
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * lazada get order item image
	 * dzt 2016/07/15
	 */
	public function getLazadaOrderItemImage($param){
		$dsn = "mysql:host=localhost;dbname=proxy;charset=utf8";
		// TODO proxy mysql account
		$db = new PDO($dsn, "root","");
		
		if(empty($param['ShopSku'])){
			return array("success"=>false, "message"=>"ShopSku is needed to get product image.", "body"=>"");
		}
		
		if(empty($param['SellerSku'])){
			return array("success"=>false, "message"=>"SellerSku is needed to get product image.", "body"=>"");
		}
		
		if(empty($param['purge']))
			$param['purge'] = false;
		
		$rtn['success'] = false;
		$rtn['SmallImageUrl'] = '';
		$rtn['ProductUrl'] = '';
		$rtn['message'] = '';
		list($ret,$smallImageUrl,$productUrl) = self::_getLazadaProductMainImage($param['ShopSku'] , $param['SellerSku'] , $db , $param['purge']);
		if ($ret===false){
			write_log("getLazadaOrderItemImage Sku:".$param["Sku"].",ShopSku:".$param["ShopSku"].",marketplaceId:".self::$MARKETPLACE.",error_message:".$smallImageUrl, "info");
			$rtn['message'] = "Sku:".$param["Sku"].",marketplaceId:".self::$MARKETPLACE.",error_message:".$smallImageUrl;
		}else{
			write_log("getLazadaOrderItemImage Sku:".$param["Sku"].",ShopSku:".$param["ShopSku"].",marketplaceId:".self::$MARKETPLACE.",purge:".$param['purge'].",image:".$smallImageUrl, "info");
			$rtn['success'] = true;
			$rtn["SmallImageUrl"] = $smallImageUrl;
			$rtn["ProductUrl"] = $productUrl;
		}
		
		return $rtn;
	}
	
	/**
	 * lazada ship order
	 *  dzt 2015/08/28
	 */
	public static function shipLazadaOrder($param){
		$items = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
			$reqParams["Action"] = 'SetStatusToReadyToShip';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
		
// 			测试例子
// 			'DeliveryType'=>'dropship',// 貌似我们系统发送请求的话，都是填 dropship
// 			'ShippingProvider'=>'AS-4PX-Postal-Singpost',
// 			'OrderItemIds'=>'[2969669]',
// 			'TrackingNumber'=>'LN241716246SG',
			
			// 处理api 接口参数
			// Order items must be from the same order
			if(!isset($param["OrderItemIds"])){
				return array("success"=>false, "message"=>"'OrderItemIds' is needed to ship a list of order items.", "body"=>"");
			}
			// E029: Order items must be from the same order
			$reqParams['OrderItemIds'] = json_encode(explode(',',$param["OrderItemIds"]));// 订单下的多个 OrderItemIds。格式为： [OrderItemIds1,OrderItemIds2]的字符串
			
// 			DeliveryType:One of the following: 'dropship' - The seller will send out the package on his own; 'pickup' - Shop should pick up the item from the seller (cross-docking); 'send_to_warehouse' - The seller will send the item to the warehouse (crossdocking).
			$reqParams['DeliveryType'] = $param['DeliveryType'];
			$reqParams['ShippingProvider'] = $param['ShippingProvider'];
			$reqParams['TrackingNumber'] = $param['TrackingNumber'];
			
			write_log("ST001:Start to ship order . (shipLazadaOrder) params:".print_r($reqParams,true),"info");
			do{
				$timeMS1=getCurrentTimestampMS();
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$results['message'] = $response["message"];
					$errorCount++;
					write_log("ST001 : ".$response["message"]." . - ".$param["OrderItemIds"]." . (shipLazadaOrder) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . - ".$param["OrderItemIds"]." . (shipLazadaOrder) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
						
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
						
					//errorCode: 20 --- E020: "%s" Invalid Order Item ID
					//errorCode: 21 --- E021: OMS Api Error Occurred
					//errorCode: 23 --- E023: "%s" Invalid Order Item IDs
					//errorCode: 24 --- E024: "%s" Invalid Delivery Type
					//errorCode: 25 --- E025: "%s" Invalid Shipping Provider
					//errorCode: 26 --- E026: "%s" Invalid Tracking Number
					//errorCode: 29 --- E029: Order items must be from the same order
					//errorCode: 31 --- E031: Tracking ID incorrect. Example tracking ID: "%s"
					//errorCode: 73 --- E073: All order items must have status Pending or Ready To Ship. (%s)
					//errorCode: 63 --- E063: The tracking code %s has already been used
								
					//errorCode: 80 --- E080: Not allowed to change the preselected shipment provider
					//errorCode: 91 --- E091: You are not allowed to set the shipment provider and tracking number and the delivery type is wrong. Please use send_to_warehouse
					//errorCode: 94 --- E094: Serial numbers specified incorrectly.Serial numbers were not specified according to one of the accepted formats for the SerialNumber parameter.
					//errorCode: 95 --- E095: Invalid serial number format (%s).Serial numbers must be 1 to 26 characters; only latin letters and digits allowed.
					//errorCode: 96 --- E096: Duplicate serial number among order items (%s).Two or more items in the order would share a serial number.
					//errorCode: 119 --- E119: Some order items are not yet ready to be processed, please try again later. (%s).One or more order items are not fully handled by Seller Center, so they cannot be processed yet.
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000, 20, 21, 23, 24, 25, 26, 29, 31, 63, 73, 80, 91, 94, 95, 96, 119);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						
						break;
					}
					sleep(5);
					continue;
				}
				
				$successResponse = $response["response"]["SuccessResponse"];
				$results["body"] = $successResponse;
				$results['success'] = true;
				break;
				
			}while ($errorCount < 15);
			
			// for 未准备errorCode 返回$errorMessage
			if(!$results['success'] && !empty($errorMessage) && empty($results['message']))
			    $results['message'] = $errorMessage;
			
			$timeMS2=getCurrentTimestampMS();
			write_log("shipLazadaOrder UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			return $results;
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * lazada get Document.
	 * Retrieve order-related documents: Invoices, Shipping Labels and Shipping Parcels.
	 * 
	 * dzt 2016/10/24
	 */
	public static function getDocument($param){
		$items = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
			$reqParams["Action"] = 'GetDocument';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);

			// 处理api 接口参数
			// One of 'invoice', 'shippingLabel', 'shippingParcel', 'carrierManifest', or "serialNumber". Mandatory. 
			// If omitted, or if an unsupported document type is supplied, an error 'E032: Document type "[supplied type]" is not valid'.
			if(!isset($param["DocumentType"])){
				return array("success"=>false, "message"=>"'DocumentType' is needed to get a document.", "body"=>"");
			}
			$reqParams['DocumentType'] = $param['DocumentType'];
			
			// Order items must be from the same order
			if(!isset($param["OrderItemIds"])){
				return array("success"=>false, "message"=>"'OrderItemIds' is needed to get a document.", "body"=>"");
			}
			// E029: Order items must be from the same order
			$reqParams['OrderItemIds'] = json_encode(explode(',',$param["OrderItemIds"]));// 订单下的多个 OrderItemIds。格式为： [OrderItemIds1,OrderItemIds2]的字符串
				
			write_log("ST001:Start to ship order . (getDocument) params:".json_encode($reqParams),"info");
			do{
				$timeMS1=getCurrentTimestampMS();
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$results['message'] = $response["message"];
					$errorCount++;
					write_log("ST001 : ".$response["message"]." . - ".$param["OrderItemIds"]." . (getDocument) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . - ".$param["OrderItemIds"]." . (getDocument) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
		
					//dzt 2016-10-24--  no need to retry according to errorCode returned from lazada
					//errorCode: 20 --- E020: "%s" Invalid Order Item ID
					//errorCode: 21 --- E021: OMS Api Error Occurred
					//errorCode: 32 --- E032: Document type "%s" is not valid
					//errorCode: 34 --- E034: Order Item must be packed. Please call setStatusToReadyToShip before
					//errorCode: 35 --- E035: "%s" was not found
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000, 20, 21, 32, 34, 35);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['message'] = $errorMessage;
						break;
					}
					sleep(5);
					continue;
				}
		
				$successResponse = $response["response"]["SuccessResponse"];
				$results["body"] = $successResponse;
				$results['success'] = true;
				break;
		
			}while ($errorCount < 15);
				
			$timeMS2=getCurrentTimestampMS();
			write_log("getDocument UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
			// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			return $results;
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * lazada get products.
	 * Get all or a range of Lazada products.
	 * dzt 2015/10/23
	 */
	public static function getLazadaProducts($param){
		$products = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
			$reqParams["Action"] = 'GetProducts';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
			
			// 处理api 接口参数
// 			CreatedAfter:	DateTime
// 			Limites the returned product list to those created after or on a specified date, given in ISO 8601 date format. Optional
			if(isset($param["CreatedAfter"])){
// 				$reqParams['CreatedAfter'] = $param["CreatedAfter"];
				$CreatedAfterDateTime = new DateTime($param["CreatedAfter"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($CreatedAfterDateTime);// initial api site timezone
				$reqParams["CreatedAfter"] = $CreatedAfterDateTime->format(DateTime::ISO8601);
			}

// 			CreatedBefore:	DateTime
// 			Limites the returned product list to those created before or on a specified date, given in ISO 8601 date format. Optional
			if(isset($param["CreatedBefore"])){
// 				$reqParams['CreatedBefore'] = $param["CreatedBefore"];
				$CreatedBeforeDateTime = new DateTime($param["CreatedBefore"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($CreatedBeforeDateTime);// initial api site timezone
				$reqParams["CreatedBefore"] = $CreatedBeforeDateTime->format(DateTime::ISO8601);
			}
			
// 			Search:	String
// 			Returns those products where the search string is contained in the product's name and/or SKU.
			if(isset($param["Search"])){
				$reqParams['Search'] = $param["Search"];
			}
			
// 			Filter:	Stringall
// 			Return only those products whose state matches this parameter. Possible values are all, live, inactive, deleted, image-missing, pending, rejected, sold-out. Mandatory.
			if(isset($param["Filter"])){
				$reqParams['Filter'] = $param["Filter"];//只能过滤一个状态
			}
			
// 			Limit:	Integer1000
// 			The maximum number of products that should be returned. If you omit this parameter, the default of 1000 is used. The largest number of products you can request with a single call is 5000 (this is the default hard limit, which can be changed per instance). If you need to retrieve more products than that, you have to page through the result set using the Offset parameter.
			if(isset($param["Limit"])){
				$reqParams['Limit'] = $param["Limit"];
			}
			
// 			Offset:	Integer
// 			Numer of products to skip (i.e., an offset into the result set; toghether with the Limit parameter, simple resultset paging is possible; if you do page through results, note that the list of products might change during paging).
			if(isset($param["Offset"])){
				$reqParams['Offset'] = $param["Offset"];
			}
			
// 			SkuSellerList : sku1,sku2,sku3
// 			SkuSellerList:	Array of Strings
// 			Only products whose SKU is contained in this list will be returned. Can either be a JSON array (e.g., [“E213”,”KI21”,”HT0”]) or a serialized PHP array (e.g., a:3:{i:0;s:4:”E213”;i:1;s:4:”KI21”;i:2;s:3:”HT0”;}).
			if(isset($param["SkuSellerList"])){
				$reqParams['SkuSellerList'] = json_encode(explode(',',$param["SkuSellerList"]));
			}
			
// 			UpdatedAfter:	DateTime
// 			Limits the returned product list to those updated after or on a specified date, given in ISO 8601 date format. Optional
			if(isset($param["UpdatedAfter"])){
// 				$reqParams['UpdatedAfter'] = $param["UpdatedAfter"];
				$CreatedAfterDateTime = new DateTime($param["UpdatedAfter"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($CreatedAfterDateTime);// initial api site timezone
				$reqParams["UpdatedAfter"] = $CreatedAfterDateTime->format(DateTime::ISO8601);
			}
			
// 			UpdatedBefore:	DateTime
// 			Limits the returned product list to those updated before or on a specified date, given in ISO 8601 date format. Optional
			if(isset($param["UpdatedBefore"])){
// 				$reqParams['UpdatedBefore'] = $param["UpdatedBefore"];
				$CreatedBeforeDateTime = new DateTime($param["UpdatedBefore"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($CreatedBeforeDateTime);// initial api site timezone
				$reqParams["UpdatedBefore"] = $CreatedBeforeDateTime->format(DateTime::ISO8601);
			}
			
			write_log("ST001:Start to get Products (getLazadaProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
			do{
				$timeMS1=getCurrentTimestampMS();
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$results['message'] = $response["message"];
					$errorCount++;
					write_log("ST001 : ".$response["message"]. ". (getLazadaProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . (getLazadaProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
		
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
		
					//errorCode: 17 --- E017: "%s" Invalid Date Format
					//errorCode: 19 --- E019: "%s" Invalid Limit
					//errorCode: 14 --- E014: "%s" Invalid Offset
					//errorCode: 36 --- E036: Invalid status filter
					//errorCode: 70 --- E070: You have corrupt data in your sku seller list.
					
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000, 14, 17, 19, 36, 70);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['message'] = $errorMessage;
						break;
					}
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
// 				$results['response'] = $successResponse;
				if (isset($successResponse['Body']['Products']['Product']['SellerSku'])){// 只有一个Attribute时，返回结构可能不一样 ，未测试，根据lazada其他数据结构推测
					$products = array($successResponse['Body']['Products']['Product']);
				}elseif(!empty($successResponse['Body']['Products']['Product'])){
					$products = $successResponse['Body']['Products']['Product'];
				}elseif(isset($successResponse["Body"]["Products"])){// dzt20161116 现在返回尽管只有一个产品也是数组的
				    if(!empty($successResponse["Body"]["Products"]))
				        $products = $successResponse['Body']['Products'];
				}else{
				    $errorCount++;
					$results['message'] = "Reture data format has changed,cannot get Products info.";
					write_log("ST001 : ".$results["message"]." . (getLazadaProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
				
				$results['success'] = true;
				break;
			}while ($errorCount < 15);
		
			if($results['success']){
				// ST002 转换时间
				$responseTime = new DateTime($successResponse['Head']['Timestamp']);
				if (count($products) >= 1){
					foreach($products as &$oneProduct){
						if(!empty($oneProduct['SaleStartDate'])){
							$saleStartDate = new DateTime($oneProduct['SaleStartDate'] , $responseTime->getTimezone());
							$oneProduct['SaleStartDate'] = $saleStartDate->format(DateTime::ISO8601);
						}
							
						if(!empty($oneProduct['SaleEndDate'])){
							$saleEndDate = new DateTime($oneProduct['SaleEndDate'] , $responseTime->getTimezone());
							$oneProduct['SaleEndDate'] = $saleEndDate->format(DateTime::ISO8601);
						}
					}
				}
			}
			
			$timeMS2=getCurrentTimestampMS();
			write_log("getLazadaProducts UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
				
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			$results['products'] = $products;
			return $results;
		}catch (Exception $e){
			$results['success'] = false;
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * @todo 此function 获取1000个产品占3M 内存，未查到那里导致多占内存，还是本来就要这么多内存。
	 * lazada get products by fiter status and map the products with each status.
	 * Get all or a range of Lazada products.
	 * dzt 2015/12/14
	 */
	public static function getLazadaFilterProducts($param){
		set_time_limit(0);
		$products = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		$totalGotAll = true;
		$totalFinalPage = 0;
		$totalOffset = 0;
		
		// 处理api 接口参数
		if(!isset($param["filterStatus"])){
			return array("success"=>false, "message"=>"filterStatus is needed to filter products.", "body"=>"");
		}
		
		write_log("ST001:Start to filter Products (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
		$timeMS1=getCurrentTimestampMS();
		try{
			$reqParams["Action"] = 'GetProducts';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
			
// 			CreatedAfter:	DateTime
// 			Limites the returned product list to those created after or on a specified date, given in ISO 8601 date format. Optional
			if(isset($param["CreatedAfter"])){
// 				$reqParams['CreatedAfter'] = $param["CreatedAfter"];
				$CreatedAfterDateTime = new DateTime($param["CreatedAfter"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($CreatedAfterDateTime);// initial api site timezone
				$reqParams["CreatedAfter"] = $CreatedAfterDateTime->format(DateTime::ISO8601);
			}
			
// 			CreatedBefore:	DateTime
// 			Limites the returned product list to those created before or on a specified date, given in ISO 8601 date format. Optional
			if(isset($param["CreatedBefore"])){
				// 				$reqParams['CreatedBefore'] = $param["CreatedBefore"];
				$CreatedBeforeDateTime = new DateTime($param["CreatedBefore"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($CreatedBeforeDateTime);// initial api site timezone
				$reqParams["CreatedBefore"] = $CreatedBeforeDateTime->format(DateTime::ISO8601);
			}
				
// 			Search:	String
// 			Returns those products where the search string is contained in the product's name and/or SKU.
			if(isset($param["Search"])){
				$reqParams['Search'] = $param["Search"];
			}
			
// 			UpdatedAfter:	DateTime
// 			Limits the returned product list to those updated after or on a specified date, given in ISO 8601 date format. Optional
			if(isset($param["UpdatedAfter"])){
// 				$reqParams['UpdatedAfter'] = $param["UpdatedAfter"];
				$UpdatedAfterDateTime = new DateTime($param["UpdatedAfter"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($UpdatedAfterDateTime);// initial api site timezone
				$reqParams["UpdatedAfter"] = $UpdatedAfterDateTime->format(DateTime::ISO8601);
			}
				
// 			UpdatedBefore:	DateTime
// 			Limits the returned product list to those updated before or on a specified date, given in ISO 8601 date format. Optional
			if(isset($param["UpdatedBefore"])){
// 				$reqParams['UpdatedBefore'] = $param["UpdatedBefore"];
				$UpdatedBeforeDateTime = new DateTime($param["UpdatedBefore"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($UpdatedBeforeDateTime);// initial api site timezone
				$reqParams["UpdatedBefore"] = $UpdatedBeforeDateTime->format(DateTime::ISO8601);
			}
			
			// 此接口已负责 翻页
			if(isset($param["limit"])){
				$reqParams['Limit']=$param["limit"];
			}else{
				$reqParams['Limit'] = 1000;
			}
// 			write_log("memory usage1:".(memory_get_usage()/1024/1024)."M. (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");

			// 内存爆了，1000 条记录 3M+ ，30000+ 的total 200+M ，怎么unset 都没办法，只能针对所有状态控制一次不拿超过10页
			// 如果其他状态极端情况下10 页最多占 30+ * 所有status 个数 M 即大概 7*30=210m 所以现在其他状态只拿5页
			// 现在ealge 那边是先 get all , 后面再请求其他状态
			// 其他状态第一次只能拿5页,不然内存还是爆
			// dzt20160427 limit 400 my 一次60+s  页数太多总时间长导致 eagle 收到504 
			
			// dzt20160331 由于Lazada Listing内容增多1W 记录可占90M ，调整$maxCount
			// 根据filterStatus 调整Limit的话会导致 offset计算不准
// 			$maxCount = 5;
			$maxCount = 1;
// 			if(in_array('all', $param["filterStatus"]) || (!empty($param['page']) && $param['page'] != 1)){
			if(in_array('all', $param["filterStatus"])){
// 				$maxCount = 2;
// 			}else if(count($param["filterStatus"]) == 2){
			}else if(count($param["filterStatus"]) >= 2){
// 				$maxCount = 10;
// 				$maxCount = 2;
				$reqParams['Limit'] = 500;
			}else if(count($param["filterStatus"]) == 1){
// 				$maxCount = 20;
// 				$maxCount = 2;
			}
			
			// 循环获取 各种状态产品
			foreach ($param["filterStatus"] as $status){// 由于控制了只能获取固定页数，所以其他状态需要过滤 all拿下的sku,所以eagle 传过来最好安排all 在最前
				$counter = 0;
				write_log("$status _1 memory usage:".(memory_get_usage()/1024/1024)."M. (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
					
				$reqParams['Filter'] = $status;
				
				// dzt20160428 由于想引入动态改Limit的逻辑，这个通过page计算offset就不准了，所以直接 发送/返回 offset 不再发送page 
// 				if(!empty($param['page'])){
// 					$page = $param['page'];
// 					$reqParams['Offset'] = ($page-1) * $reqParams['Limit'];
// 				}else{
// 					$page = 1;
// 					$reqParams['Offset'] = 0;
// 				}
				
				$page = 1;
				if(!empty($param['offset'])){
					$reqParams['Offset'] = $param['offset'];
					$offset = $param['offset'];
				}else{
					$reqParams['Offset'] = 0;
					$offset = 0;
				}
				
				$products[$status] = array();
				$hasGotAll = false;
				
				do{
					$timeMS11=getCurrentTimestampMS();
					$response = self::_callLazada($reqParams);
					$timeMS12=getCurrentTimestampMS();
// 					write_log("$status _2 memory usage:".(memory_get_usage()/1024/1024)."M. (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
					
					write_log("getLazadaFilterProducts t12_11=".($timeMS12-$timeMS11) ." status:$status Offset:".$reqParams['Offset'] ,"info");
					// network error or json decode error
					if($response["success"] == false){
						$results['message'] = $response["message"]." status:$status";
						$errorCount++;
						write_log("ST001 : ".$response["message"]. ". (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
						sleep(1);
						continue;
					}
						
					// api error
					if(isset($response["response"]["ErrorResponse"])){
						$errorResponse = $response["response"]["ErrorResponse"];
						$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
						$errorCount++;
						write_log("ST001 : $errorMessage . (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
				
						//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
						//errorCode: 17 --- E017: "%s" Invalid Date Format
						//errorCode: 19 --- E019: "%s" Invalid Limit
						//errorCode: 14 --- E014: "%s" Invalid Offset
						//errorCode: 36 --- E036: Invalid status filter
						//errorCode: 70 --- E070: You have corrupt data in your sku seller list.
				
						$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000, 14, 17, 19, 36, 70);
						if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
							$results['message'] = $errorMessage." status:$status";
							break;
						}
						
						// 其他错误 允许重试
						sleep(1);
						continue;
					}
					
					$successResponse = $response["response"]["SuccessResponse"];
// 					write_log("dzttest:".json_encode($successResponse['Body']).". (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
						
					$filterProducts = array();
					if(!empty($successResponse['Body']['Products'])){
						if (isset($successResponse['Body']['Products']['Product']['SellerSku'])){
							$filterProducts = array($successResponse['Body']['Products']['Product']);
						}elseif(!empty($successResponse['Body']['Products']['Product'])){
        					$filterProducts = $successResponse['Body']['Products']['Product'];
        				}elseif($successResponse["Body"]["Products"]){// dzt20161116 现在返回尽管只有一个产品也是数组的
        				    if(!empty($successResponse["Body"]["Products"]))
        				        $filterProducts = $successResponse['Body']['Products'];
        				}else{
        				    $errorCount++;
        					$results['message'] = "Reture data format has changed,cannot get Products info.";
        					write_log("ST001 : ".$results["message"]." . (getLazadaProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
        					sleep(1);
        					continue;
        				}
					}
					
					write_log("count:get products:".count($filterProducts).",total products:".count($products[$status]).". (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
					write_log("total runtime:".(getCurrentTimestampMS()-$timeMS1).". (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
// 					write_log("$status _3 memory usage:".(memory_get_usage()/1024/1024)."M. (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
					
					// ST002 转换时间
					$responseTime = new DateTime($successResponse['Head']['Timestamp']);
					if (is_array($filterProducts) && count($filterProducts) >= 1){
						foreach($filterProducts as &$oneProduct){
							// dzt20160427 unset detail 查看内存
							// 还有ShortDescription 等等可以用编辑框的字段
							// 测试得 1000+ 产品 不unset Description 保存到文本文件使用 5-6M unset 了Description 保存到文本文件只要 1M多一点
							// json decode之前 只用1M多内存，主要是字符内容，decode 之后之后要8M ，即不含Description 的数组占了7M  
							unset($oneProduct['Description']);// @todo 后面要的时候要去掉
							
							if(!empty($oneProduct['SaleStartDate'])){
								$saleStartDate = new DateTime($oneProduct['SaleStartDate'] , $responseTime->getTimezone());
								$oneProduct['SaleStartDate'] = $saleStartDate->format(DateTime::ISO8601);
							}
					
							if(!empty($oneProduct['SaleEndDate'])){
								$saleEndDate = new DateTime($oneProduct['SaleEndDate'] , $responseTime->getTimezone());
								$oneProduct['SaleEndDate'] = $saleEndDate->format(DateTime::ISO8601);
							}
						}
						
						if(!empty($products[$status])){
							$products[$status] = array_merge($products[$status],$filterProducts);
						}else{
							$products[$status] = $filterProducts;
						}
					}
					
					//没有结果了
					if (empty($filterProducts)) {
						$hasGotAll = true;
					}
					//最后一页了,不需要再翻页
					if (count($filterProducts) < $reqParams['Limit']){
						$hasGotAll = true;
					}
					
// 					unset($filterProducts);
// 					unset($successResponse);
// 					unset($response["response"]);
					
// 					write_log("$status _4 memory usage:".(memory_get_usage()/1024/1024)."M. (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
						
					$results['success'] = true;
					
					// 获取下一页页码
					$page++;
					$counter++;
					$reqParams["Offset"] = ($page-1) * $reqParams['Limit'] + $offset;
// 					$reqParams["Offset"] = ($page-1) * $reqParams['Limit'];
				}while ($errorCount < 15 && $hasGotAll != true && $counter < $maxCount);
				
				if($totalGotAll){// 记录全部状态是否已经获取完成
					$totalGotAll = $hasGotAll;
				}
				
// 				if($tatolFinalPage < ($page-1)){// 记录所有状态的总体翻页情况
// 					$tatolFinalPage = ($page-1);
// 				}
				
				if($totalOffset < $reqParams["Offset"]){// 记录所有状态的总体翻页情况
					$totalOffset = $reqParams["Offset"];
				}
				
				if($errorCount == 15){
					$results['success'] = false;
					write_log("getLazadaFilterProducts over errorCount. status:$status UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
				}
			}// end of $param["filterStatus"] foreach
			
			$timeMS2 = getCurrentTimestampMS();
			write_log("getLazadaFilterProducts UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
			write_log("all memory usage:".(memory_get_usage()/1024/1024)."M. (getLazadaFilterProducts) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
			
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			$results['allProducts'] = $products;
// 			$results['page'] = $totalFinalPage;
			$results['offset'] = $totalOffset;
			$results['hasGotAll'] = ($totalGotAll == true)?1:0;
// 			unset($products);
			return $results;
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * Get Lazada product QcStatus.
	 * dzt 2016/11/11
	 */
	public static function getLazadaQcStatus($param){
	    $qcStatus = array();
	    $reqParams = array();
	    $results = array();
	    $results['success'] = false;
	    $results['message'] = '';
	    $errorCount = 0; // 失败尝试
	
	    try{
	        $reqParams["Action"] = 'GetQcStatus';
	        $Timestamp = new DateTime();
	        $Timestamp->setTimezone ( new DateTimeZone('UTC'));
	        $reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
	        $reqParams = array_merge($reqParams,self::$CONFIG);
	        	
	        // 处理api 接口参数
// 			Limit:	Integer1000
// 			The maximum number of products that should be returned. If you omit this parameter, the default of 1000 is used. The largest number of products you can request with a single call is 5000 (this is the default hard limit, which can be changed per instance). If you need to retrieve more products than that, you have to page through the result set using the Offset parameter.
	        if(isset($param["Limit"])){
	            $reqParams['Limit'] = $param["Limit"];
	        }
	        	
// 			Offset:	Integer
// 			Numer of products to skip (i.e., an offset into the result set; toghether with the Limit parameter, simple resultset paging is possible; if you do page through results, note that the list of products might change during paging).
	        if(isset($param["Offset"])){
	            $reqParams['Offset'] = $param["Offset"];
	        }
	        	
// 			SkuSellerList : sku1,sku2,sku3
// 			SkuSellerList:	Array of Strings
// 			Only products whose SKU is contained in this list will be returned. Can either be a JSON array (e.g., [“E213”,”KI21”,”HT0”]) or a serialized PHP array (e.g., a:3:{i:0;s:4:”E213”;i:1;s:4:”KI21”;i:2;s:3:”HT0”;}).
	        if(isset($param["SkuSellerList"])){
	            $reqParams['SkuSellerList'] = json_encode(explode(',',$param["SkuSellerList"]));
	        }
	        	
	        write_log("ST001:Start to get Products (getLazadaQcStatus) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"info");
	        do{
	            $timeMS1=getCurrentTimestampMS();
	            $response = self::_callLazada($reqParams);
	            	
	            // network error or json decode error
	            if($response["success"] == false){
	                $results['message'] = $response["message"];
	                $errorCount++;
	                write_log("ST001 : ".$response["message"]. ". (getLazadaQcStatus) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
	                sleep(1);
	                continue;
	            }
	            	
	            // api error
	            if(isset($response["response"]["ErrorResponse"])){
	                $errorResponse = $response["response"]["ErrorResponse"];
	                $errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
	                $errorCount++;
	                write_log("ST001 : $errorMessage . (getLazadaQcStatus) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
	                //dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
	
	                //errorCode: 19 --- E019: "%s" Invalid Limit
	                //errorCode: 14 --- E014: "%s" Invalid Offset
	                //errorCode: 70 --- E070: You have corrupt data in your sku seller list.
	                $skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000, 14, 19, 70);
	                if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
	                    $results['message'] = $errorMessage;
	                    break;
	                }
	                sleep(1);
	                continue;
	            }
	            	
	            $successResponse = $response["response"]["SuccessResponse"];
	            if (isset($successResponse['Body']['Status']['State']['SellerSKU'])){// 只有一个Attribute时，返回结构可能不一样 ，未测试，根据lazada其他数据结构推测
	                $qcStatus = array($successResponse['Body']['Status']['State']);
	            }else{
	                $qcStatus = $successResponse['Body']['Status']['State'];
	            }
	            $results['success'] = true;
	            break;
	        }while ($errorCount < 15);
	
	        $timeMS2=getCurrentTimestampMS();
	        write_log("getLazadaQcStatus UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
	
	        $results['errorCount'] = $errorCount;
	        $results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
	        $results['products'] = $qcStatus;
	        return $results;
	    }catch (Exception $e){
	        $results['success'] = false;
	        $results['message'] = $e->getMessage();
	        $results['body'] = print_r($e,true);
	        return $results;
	    }
	}
	
	/**
	 * lazada product create
	 * dzt 2015/10/23
	 */
	public static function lazadaProductCreate($param){
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
		    // dzt20161117 从lazada 卖家后端查看接口例子来看，产品创建action改为"CreateProduct"
// 		    if(in_array(self::$MARKETPLACE,self::$LazadaSite)){
// 		        $reqParams["Action"] = 'CreateProduct';
// 		    }else{
// 		        $reqParams["Action"] = 'ProductCreate';
// 		    }

		    // dzt20161214 lazada走新接口，恢复接口不影响linio和jumia
		    $reqParams["Action"] = 'ProductCreate';
		    
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
// 			$reqParams['Format'] = 'XML';
			
			// 处理api 接口参数
			if(!isset($param["products"])){
				return array("success"=>false, "message"=>"'products' array is needed to create a list of products.", "body"=>"");
			}
			
			$timeMS1=getCurrentTimestampMS();
			do{
				
				$feed =  <<<EOD
<?xml version="1.0" encoding="UTF-8" ?>
<Request>
#Request#
</Request>
EOD;
				$feed_products_dummy = "<Product>#Product#</Product>";
				$feed_products = "";
				if(isset($param["products"]['SellerSku'])){//以防json解释问题，一个product时不是produc数组形式传递。可能是测试脚本xml转化为json问题
					$param["products"] = array($param["products"]);
				}
				
				foreach ($param["products"] as $oneProduct){
					$feed_oneProduct = "";
					foreach($oneProduct as $attrName=>$attrVal){
						if(!is_array($attrVal)){
							$feed_oneProduct .= "<$attrName>".$attrVal."</$attrName>";
						}else{// 属性应该只有第二层（就是ProductData包着的） 没有第三层的了，以防万一有多层，写个递归
							$feed_oneProduct .= "<$attrName>";
							$feed_oneProduct .= self::getXmlFromArray($attrVal);
							$feed_oneProduct .= "</$attrName>";
						}
					}
					
					$feed_products .=  str_ireplace('#Product#',$feed_oneProduct,$feed_products_dummy) ;
				}
				
				$feed = str_ireplace('#Request#',$feed_products,$feed) ;
				
				write_log("ST002:before call _callLazada. (lazadaProductCreate) param:".print_r($reqParams,true),"info");
				write_log("feed:".$feed,"info");
				$response = self::_callLazada($reqParams,$feed,true);
				write_log("ST002.1: (lazadaProductCreate) response:".print_r($response,true),"info");
				// network error or json decode error
				if($response["success"] == false){
					$results['message'] = $response["message"];
					write_log("ST003 : ".$response["message"]." . (lazadaProductCreate) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");

					// 请求太快平台返回 httpcode 429 too many request问题
					if(isset($response['state_code']) && 429 == $response['state_code']){
						if($errorCount < 3){// 只重试一次
							$errorCount = 3;
							sleep(40);
						}			 	
					} 
						
					$errorCount++;
					sleep(5);
										
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST003 : $errorMessage . (lazadaProductCreate) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					$results['message'] = $errorMessage;
					
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
						
// 					1000 Could not save product: %s
// 					1000 Format Error Detected
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						break;
					}
					sleep(5);
					continue;
				}
				
				$results['success'] = true;
				$successResponse = $response["response"]["SuccessResponse"];
				$results['body'] = $successResponse;
				break;
				
			} while ($errorCount < 5);
			$timeMS2=getCurrentTimestampMS();
			write_log("lazadaProductCreate UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
				
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			return $results;
			
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * Update the attributes of one or more existing products.
	 * dzt 2015/10/30
	 */
	public function lazadaProductUpdate($param){
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
			// dzt20161117 从lazada 卖家后端查看接口例子来看，产品创建action改为"UpdateProduct"
// 			if(in_array(self::$MARKETPLACE,self::$LazadaSite)){
// 			    $reqParams["Action"] = 'UpdateProduct';
// 			}else{
// 			    $reqParams["Action"] = 'ProductUpdate';
// 			}

		    // dzt20161214 lazada走新接口，恢复接口不影响linio和jumia
		    $reqParams["Action"] = 'ProductUpdate';
			
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
			// 			$reqParams['Format'] = 'XML';
				
			// 处理api 接口参数
			if(!isset($param["products"])){
				return array("success"=>false, "message"=>"'products' array is needed to update a list of products info.", "body"=>"");
			}
				
			$timeMS1=getCurrentTimestampMS();
			do{
		
				$feed =  <<<EOD
<?xml version="1.0" encoding="UTF-8" ?>
<Request>
#Request#
</Request>
EOD;
				$feed_products_dummy = "<Product>#Product#</Product>";
				$feed_products = "";
				// E005: Invalid Request Format,Invalid Data, this usually when the long description you have added has invalid HTML tags 
				foreach ($param["products"] as $oneProduct){
					$feed_oneProduct = "";
					foreach($oneProduct as $attrName=>$attrVal){
						if(!is_array($attrVal)){
							$feed_oneProduct .= "<$attrName>".$attrVal."</$attrName>";
						}else{// 属性应该只有第二层（就是ProductData包着的） 没有第三层的了，以防万一有多层，写个递归
							$feed_oneProduct .= "<$attrName>";
							$feed_oneProduct .= self::getXmlFromArray($attrVal);
							$feed_oneProduct .= "</$attrName>";
						}
					}
						
					$feed_products .=  str_ireplace('#Product#',$feed_oneProduct,$feed_products_dummy) ;
				}
		
				$feed = str_ireplace('#Request#',$feed_products,$feed) ;
		
				write_log("ST002:before call _callLazada. (lazadaProductUpdate) param:".print_r($reqParams,true),"info");
				$response = self::_callLazada($reqParams,$feed,true);
		
				write_log("ST002.1: (lazadaProductUpdate) response:".print_r($response,true),"info");
				// network error or json decode error
				if($response["success"] == false){
					$results['message'] = $response["message"];
					$errorCount++;
					write_log("ST003 : ".$response["message"]." . (lazadaProductUpdate) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST003 : $errorMessage . (lazadaProductUpdate) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					$results['message'] = $errorMessage;
					
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						break;
					}
					sleep(5);
					continue;
				}
		
				$results['success'] = true;
				$successResponse = $response["response"]["SuccessResponse"];
				$results['body'] = $successResponse;
				break;
		
			} while ($errorCount < 5);
			$timeMS2=getCurrentTimestampMS();
			write_log("lazadaProductUpdate UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
				
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
			// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			return $results;
				
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * Removes one or more products
	 * dzt 2015/10/30
	 */
	public function lazadaProductDelete($param){
		$attributes = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
			$reqParams["Action"] = 'ProductRemove';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
				
			// 处理api 接口参数
			if(!isset($param["SellerSkus"])){
				return array("success"=>false, "message"=>"'SellerSkus' is needed to remove a list of products.", "body"=>"");
			}
		
			$feed = <<<EOD
<?xml version="1.0" encoding="UTF-8" ?>
<Request>#Request#</Request>
EOD;
			//this is a formated dummy for images in this package
			$feed_rmProd_dummy = "<Product><SellerSku>#SellerSku#</SellerSku></Product>";
			$feed_rmProds = "";
			foreach ($param["SellerSkus"] as $SellerSkus){
				$tempStr =  str_ireplace('#SellerSku#',$SellerSkus,$feed_rmProd_dummy) ;
				$feed_rmProds .= $tempStr;
			}
				
			$feed = str_ireplace('#Request#',$feed_rmProds,$feed) ;
			$timeMS1=getCurrentTimestampMS();
			do{
				$response = self::_callLazada($reqParams,$feed,true);
					
				// network error or json decode error
				if($response["success"] == false){
					$results['message'] = $response["message"];
					$errorCount++;
					write_log("ST001 : ".$response["message"]. " . SellerSkus - ".join(',', $param["SellerSkus"])." (lazadaProductDelete) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . SellerSkus - ".join(',', $param["SellerSkus"])."  (lazadaProductDelete) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					$results['message'] = $errorMessage;
					
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						break;
					}
					sleep(5);
					continue;
				}
				
				$results['success'] = true;
				$successResponse = $response["response"]["SuccessResponse"];
				$results['body'] = $successResponse;
				break;
		
			}while ($errorCount < 5);
			$timeMS2=getCurrentTimestampMS();
			write_log("lazadaProductDelete UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." SellerSkus - ".join(',', $param["SellerSkus"])." t2_1=".($timeMS2-$timeMS1),"info");
				
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
			// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			return $results;
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * upload images to a product.
	 * 
	 * Lazada set the Images for a Product, by associating one or more URLs with it.
	 * It is the caller's responsibility to host the images.
	 * The first image passed in becomes the product's default image.
	 * Upon calling this endpoint, all previously associated images are disassociated.
	 * There is a hard limit of at most 8 images per product.
	 * dzt 2015/10/27
	 */
	public function uploadLazadaProductImage($param){
		$attributes = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
		    // dzt20161117
		    /*
		    if(in_array(self::$MARKETPLACE,self::$LazadaSite)){
		        $reqParams["Action"] = 'SetImages';
		        $feed = <<<EOD
<?xml version="1.0" encoding="UTF-8" ?>
<Request>
<Product><Skus><Sku>
<SellerSku>#SellerSku#</SellerSku>
<Images>
#Images#
</Images>
</Sku></Skus></Product>
</Request>
EOD;
		    }else{
		        $reqParams["Action"] = 'Image';
		        $feed = <<<EOD
<?xml version="1.0" encoding="UTF-8" ?>
<Request>
<ProductImage>
<SellerSku>#SellerSku#</SellerSku>
<Images>
#Images#
</Images>
</ProductImage>
</Request>
EOD;
		    }
*/	
		    // dzt20161214 lazada走新接口，恢复接口不影响linio和jumia
		    $reqParams["Action"] = 'Image';
		    $feed = <<<EOD
<?xml version="1.0" encoding="UTF-8" ?>
<Request>
<ProductImage>
<SellerSku>#SellerSku#</SellerSku>
<Images>
#Images#
</Images>
</ProductImage>
</Request>
EOD;

			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
			
			// 处理api 接口参数
			if(!isset($param["SellerSku"])){
				return array("success"=>false, "message"=>"'SellerSku' is needed to upload a list of product images.", "body"=>"");
			}
			
			if(empty($param["Images"])){
				return array("success"=>false, "message"=>"'Images' is needed to upload a list of product images.", "body"=>"");
			}


			//this is a formated dummy for images in this package
			$feed_images_dummy = "<Image>#url#</Image>";
			$feed_images = "";
			foreach ($param["Images"] as $url){
				if(!empty($url)){
					$tempStr =  str_ireplace('#url#',$url,$feed_images_dummy) ;
					$feed_images .= $tempStr;
				}
			}
			
			$feed = str_ireplace('#SellerSku#',$param["SellerSku"],$feed) ;
			$feed = str_ireplace('#Images#',$feed_images,$feed) ;
			$timeMS1=getCurrentTimestampMS();
			do{
				$response = self::_callLazada($reqParams,$feed,true);
					
				// network error or json decode error
				if($response["success"] == false){
					$results['message'] = $response["message"];
					$errorCount++;
					write_log("ST001 : ".$response["message"]. " . SellerSku - ".$param["SellerSku"]." (uploadLazadaProductImage) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . SellerSku - ".$param["SellerSku"]."  (uploadLazadaProductImage) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					$results['message'] = $errorMessage;
					
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
		
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						break;
					}
					sleep(5);
					continue;
				}
				
				$results['success'] = true;
				$successResponse = $response["response"]["SuccessResponse"];
				$results['body'] = $successResponse;
				break;
				
			}while ($errorCount < 5);
			$timeMS2=getCurrentTimestampMS();
			write_log("uploadLazadaProductImage UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." SellerSku - ".$param["SellerSku"]." t2_1=".($timeMS2-$timeMS1),"info");
				
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			return $results;
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}		
	}
	
	/**
	 * upload images to a product.
	 * 
	 * Lazada set the Images for a Product, by associating one or more URLs with it.
	 * It is the caller's responsibility to host the images.
	 * The first image passed in becomes the product's default image.
	 * Upon calling this endpoint, all previously associated images are disassociated.
	 * There is a hard limit of at most 8 images per product.
	 * dzt 2015/10/27
	 */
	public function uploadLazadaProductsImage($param){
		$attributes = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
 
		    $reqParams["Action"] = 'Image';
		    $feed = <<<EOD
<?xml version="1.0" encoding="UTF-8" ?>
<Request>
#Request#
</Request>
EOD;

			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
			
			// 处理api 接口参数
			if(!isset($param["products"])){
				return array("success"=>false, "message"=>"'products' array is needed to update a list of products info.", "body"=>"");
			}


			foreach ($param["products"] as $oneProduct){
				$feed_oneProduct = "";
				 //this is a formated dummy for images in this package
				$feed_prodImage_dummy = <<<EOD
<ProductImage>
<SellerSku>#SellerSku#</SellerSku>
<Images>#Images#</Images>
</ProductImage>
EOD;
				
				$feed_images_dummy = "<Image>#url#</Image>";
				$feed_images = "";
				foreach ($oneProduct["Images"] as $url){
					if(!empty($url)){
						$tempStr =  str_ireplace('#url#',$url,$feed_images_dummy);
						$feed_images .= $tempStr;
					}
				}
				
				$feed_prodImage_dummy = str_ireplace('#SellerSku#',$oneProduct["SellerSku"],$feed_prodImage_dummy);
				$feed_prodImage_dummy = str_ireplace('#Images#',$feed_images,$feed_prodImage_dummy);
				
				$feed_products .=  $feed_prodImage_dummy;
			}
				
			$feed = str_ireplace('#Request#',$feed_products,$feed) ;
 
			$timeMS1=getCurrentTimestampMS();
			do{
				write_log("ST001:before call _callLazada. (uploadLazadaProductsImage) param:".print_r($reqParams,true),"info");
				$response = self::_callLazada($reqParams,$feed,true);
				write_log("ST001.1: (uploadLazadaProductsImage) response:".print_r($response,true),"info");	
					
				// network error or json decode error
				if($response["success"] == false){
					$results['message'] = $response["message"];
					$errorCount++;
					write_log("ST002 : ".$response["message"]. " (uploadLazadaProductsImage) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST002 : $errorMessage (uploadLazadaProductsImage) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					$results['message'] = $errorMessage;
					
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
		
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						break;
					}
					sleep(5);
					continue;
				}
				
				$results['success'] = true;
				$successResponse = $response["response"]["SuccessResponse"];
				$results['body'] = $successResponse;
				break;
				
			}while ($errorCount < 5);
			$timeMS2=getCurrentTimestampMS();
			write_log("ST003 uploadLazadaProductsImage UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
				
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			return $results;
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}		
	}
	
	/**
	 * get lazada category tree.
	 * dzt 2015/10/27
	 */
	public function getLazadaCategoryTree($param){
		$categories = array();
		$reqParams = array();
		$results = array();
		$results['success'] = true;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
			$reqParams["Action"] = 'GetCategoryTree';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
			
			$timeMS1=getCurrentTimestampMS();
			do{
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					write_log("ST001 : ".$response["message"]. " . (getLazadaCategoryTree) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . (getLazadaCategoryTree) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
		
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
		
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['success'] = false;
						$results['message'] = $errorMessage;
						break;
					}
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
		
				if (isset($successResponse['Body']['Categories']['Category']['Name'])){// 只有一个Category时，返回结构可能不一样 ，未测试，根据lazada其他数据结构推测
					$categories = array($successResponse['Body']['Categories']['Category']);
				}else{
					$categories = $successResponse['Body']['Categories']['Category'];
				}
		
				break;
			}while ($errorCount < 15);
			
			$timeMS2=getCurrentTimestampMS();
			write_log("getLazadaCategoryTree UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
				
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			$results['categories'] = $categories;
			return $results;
		}catch (Exception $e){
			$results['success'] = false;
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}	
	}
	
	/**
	 * get lazada category attributes.
	 * Lazada returns a list of attributes with options for a given category. It will also display attributes for TaxClass and ShipmentType, with their possible values listed as options.
	 * dzt 2015/10/27
	 */
	public function getLazadaCategoryAttributes($param){
		$attributes = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
			$reqParams["Action"] = 'GetCategoryAttributes';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
		
			// 处理api 接口参数
			if(!isset($param["PrimaryCategory"])){
				return array("success"=>false, "message"=>"'PrimaryCategory' is needed to get a list of category attributes.", "body"=>"");
			}
				
			$reqParams['PrimaryCategory'] = $param["PrimaryCategory"];
			$timeMS1=getCurrentTimestampMS();
			do{
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					$results['message'] = $response["message"];
					write_log("ST001 : ".$response["message"]. " . PrimaryCategory - ".$param["PrimaryCategory"]." (getLazadaCategoryAttibutes) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . PrimaryCategory - ".$param["PrimaryCategory"]."  (getLazadaCategoryAttibutes) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
		
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
		
					//errorCode: 57 --- E057: No attribute sets linked to that category.
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000,57);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['message'] = $errorMessage;
						break;
					}
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
				// $results['response'] = $successResponse;
				if (isset($successResponse['Body']['Attribute']['Label'])){// 只有一个Attribute时，返回结构可能不一样 ，未测试，根据lazada其他数据结构推测
					$attributes = array($successResponse['Body']['Attribute']);
				}elseif(!empty($successResponse['Body']['Attribute'])){// dzt20161116
					$attributes = $successResponse['Body']['Attribute'];
				}else{
				    $attributes = $successResponse['Body'];
				}
				$results['success'] = true;
				break;
			}while ($errorCount < 15);
			$timeMS2=getCurrentTimestampMS();
			write_log("getLazadaCategoryAttibutes UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." PrimaryCategory - ".$param["PrimaryCategory"]." t2_1=".($timeMS2-$timeMS1),"info");
				
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			$results['attributes'] = $attributes;
			return $results;
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * Returns a list of all active shipping providers.
	 * Needed when working with SetStatusToShipped.
	 * dzt 2015/11/03
	 */
	public function getLazadaShipmentProviders($param){
		$shipments = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
	
		try{
			$reqParams["Action"] = 'GetShipmentProviders';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
			$timeMS1=getCurrentTimestampMS();
			do{
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					$results['message'] = $response["message"];
					write_log("ST001 : ".$response["message"]. " . (getLazadaShipmentProviders) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . (getLazadaShipmentProviders) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
	
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['message'] = $errorMessage;
						break;
					}
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
// 				$results['response'] = $successResponse;
				if (isset($successResponse['Body']['ShipmentProviders']['ShipmentProvider']['Name'])){// 只有一个ShipmentProvider时，返回结构不一样
					$shipments = array($successResponse['Body']['ShipmentProviders']['ShipmentProvider']);
				}elseif(!empty($successResponse['Body']['ShipmentProviders']['ShipmentProvider'])){
				    $shipments = $successResponse['Body']['ShipmentProviders']['ShipmentProvider'];
				}elseif(isset($successResponse['Body']['ShipmentProviders'])){// dzt20161116 现在返回尽管只有一个产品也是数组的
				    if(!empty($successResponse["Body"]["ShipmentProviders"]))
				        $shipments = $successResponse['Body']['ShipmentProviders'];
				}else{
				    $errorCount++;
				    $results['message'] = "Reture data format has changed,cannot get ShipmentProviders info.";
				    write_log("ST001 : ".$results["message"]." . (getLazadaShipmentProviders) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
				    sleep(1);
				    continue;
				}
				
				$results['success'] = true;
				break;
			}while ($errorCount < 15);
			$timeMS2=getCurrentTimestampMS();
			write_log("getLazadaShipmentProviders UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			$results['shipments'] = $shipments;
			return $results;
		}catch (Exception $e){
			$results['success'] = false;
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * get lazada feed list .
	 * Lazada returns all feeds created in the last 30 days.
	 * dzt 2015/10/27
	 */
	public function getLazadaFeedList($param){
		$feeds = array();
		$reqParams = array();
		$results = array();
		$results['success'] = true;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
		
		try{
			$reqParams["Action"] = 'FeedList';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
				
			write_log("ST001:Start to get FeedList (getLazadaFeedList) ","info");
			do{
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					write_log("ST001 : ".$response["message"]. " . (getLazadaFeedList) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
				
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . (getLazadaFeedList) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
						
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['success'] = false;
						$results['message'] = $errorMessage;
						break;
					}
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
		
				if (isset($successResponse["Body"]["Feed"]["Feed"])){// 只有一个feed时，返回结构可能不一样 ，未测试，根据lazada其他数据结构推测
					$feeds = array($successResponse["Body"]["Feed"]);
				}else{
					$feeds = $successResponse["Body"]["Feed"];
				}
				
				break;
					
			}while ($errorCount < 15);
				
			$responseTime = new DateTime($successResponse['Head']['Timestamp']);
			
			// ST002 转换时间
			if (count($feeds) >= 1){
				foreach($feeds as &$oneFeed){
					if(!empty($oneFeed['CreationDate'])){
						$creationDate = new DateTime($oneFeed['CreationDate'] , $responseTime->getTimezone());
						$oneFeed['CreationDate'] = $creationDate->format(DateTime::ISO8601);
					}
					
					if(!empty($oneFeed['UpdatedDate'])){
						$updatedDate = new DateTime($oneFeed['UpdatedDate'] , $responseTime->getTimezone());
						$oneFeed['UpdatedDate'] = $updatedDate->format(DateTime::ISO8601);
					}
				}
			}
				
			write_log("ST100:Done. (getLazadaFeedList)", "info");
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			$results['feeds'] = $feeds;
			return $results;
		}catch (Exception $e){
			$results['success'] = false;
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}	
	}
	
	/**
	 * get all or a subset of lazada feed list.
	 * Returns all or a subset of all feeds created in the last 30 days.
	 * dzt 2015/11/11
	 */
	public static function getLazadaFeedOffsetList($param){
		$feeds = array();
		$reqParams = array();
		$results = array();
		$results['success'] = true;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
	
		try{
			$reqParams["Action"] = 'FeedOffsetList';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
				
			// 处理api 接口参数
// 			Offset:	String
// 			Zero-based offset into the list of all feeds. Optional
			if(isset($param["Offset"])){
				$reqParams['Offset'] = $param["Offset"];
			}
			
// 			PageSize:	Integer
// 			The number of entries to retrieve, i.e. the page size. Optional
			if(isset($param["PageSize"])){
				$reqParams['PageSize'] = $param["PageSize"];
			}
			
// 			Status:	String
// 			If supplied, only feeds with this status are returned.
			if(isset($param["Status"])){
				$reqParams['Status'] = $param["Status"];
			}
			
// 			CreationDate:	DateTime
// 			If supplied, only feeds created after this date will be included in the result.
			if(isset($param["CreationDate"])){
				$CreationDateTime = new DateTime($param["CreationDate"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($CreationDateTime);// initial api site timezone
				$reqParams["CreationDate"] = $CreationDateTime->format(DateTime::ISO8601);
			}
			
// 			UpdatedDate:	DateTime
// 			If supplied, only feeds updated after this date will be included in the result.
			if(isset($param["UpdatedDate"])){
				$UpdatedDateTime = new DateTime($param["UpdatedDate"]);// proxy接收到的date time string 都必须是已经转为utc 时间的
				self::setRequestDateTimeTimezone($UpdatedDateTime);// initial api site timezone
				$reqParams["UpdatedDate"] = $UpdatedDateTime->format(DateTime::ISO8601);
			}
				
			write_log("ST001:Start to get FeedList (getLazadaFeedOffsetList) ","info");
			do{
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					write_log("ST001 : ".$response["message"]. ". (getLazadaFeedOffsetList) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . (getLazadaFeedOffsetList) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
	
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					//errorCode: 13 --- E013: Invalid Feed Status
					//errorCode: 14 --- E014: Invalid Offset
					//errorCode: 15 --- E015: Invalid PageSize
					//errorCode: 46 --- E046: Invalid CreationDate value
					//errorCode: 47 --- E047: Invalid UpdatedDate value
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000, 13, 14, 15, 46, 47);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['success'] = false;
						$results['message'] = $errorMessage;
						break;
					}
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
		
				if (isset($successResponse["Body"]["Feed"]["Feed"])){// 只有一个feed时，返回结构可能不一样 ，未测试，根据lazada其他数据结构推测
					$feeds = array($successResponse["Body"]["Feed"]);
				}else{
					$feeds = $successResponse["Body"]["Feed"];
				}
	
				break;
			}while ($errorCount < 15);
	
			$responseTime = new DateTime($successResponse['Head']['Timestamp']);
			
			// ST002 转换时间
			if (count($feeds) >= 1){
				foreach($feeds as &$oneFeed){
					if(!empty($oneFeed['CreationDate'])){
						$creationDate = new DateTime($oneFeed['CreationDate'] , $responseTime->getTimezone());
						$oneFeed['CreationDate'] = $creationDate->format(DateTime::ISO8601);
					}
						
					if(!empty($oneFeed['UpdatedDate'])){
						$updatedDate = new DateTime($oneFeed['UpdatedDate'] , $responseTime->getTimezone());
						$oneFeed['UpdatedDate'] = $updatedDate->format(DateTime::ISO8601);
					}
				}
			}
				
			write_log("ST100:Done. (getLazadaFeedOffsetList)", "info");
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			$results['feeds'] = $feeds;
			return $results;
		}catch (Exception $e){
			$results['success'] = false;
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * Returns detailed information about a specified feed.
	 * dzt 2015/08/26
	 */
	public function getLazadaFeedDetail($param){
		$feeds = array();
		$reqParams = array();
		$results = array();
		$results['success'] = true;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
	
		try{
			$reqParams["Action"] = 'FeedStatus';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
	
			// 处理api 接口参数
			if(!isset($param["FeedID"])){
				return array("success"=>false, "message"=>"'FeedID' is needed to get a specified feed.", "body"=>"");
			}
// 			FeedID:	String
// 			The identifier (UUID) of the feed.
			$reqParams['FeedID'] = $param["FeedID"];
				
			write_log("ST001:Start to get feed detail . - ".$reqParams['FeedID']." . (getLazadaFeedDetail) ","info");
			do{
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					write_log("ST001 : ".$response["message"]." . - ".$param["FeedID"]." . (getLazadaFeedDetail) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . - ".$param["FeedID"]." . (getLazadaFeedDetail) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
						
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					//errorCode: 12 --- E012: Invalid Feed ID
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000,12);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['success'] = false;
						$results['message'] = $errorMessage;
						break;
					}
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
		
				if (isset($successResponse["Body"]["FeedDetail"]["Feed"])){// 只有一个feed时，返回结构可能不一样 ，未测试，根据lazada其他数据结构推测
					$feeds = array($successResponse["Body"]["FeedDetail"]);
				}else{
					$feeds = $successResponse["Body"]["FeedDetail"];
				}
				break;
					
			}while ($errorCount < 15);
				
			$responseTime = new DateTime($successResponse['Head']['Timestamp']);
				
			// ST002 转换时间
			if (count($feeds) >= 1){
				foreach($feeds as &$oneFeed){
					if(!empty($oneFeed['CreationDate'])){
						$creationDate = new DateTime($oneFeed['CreationDate'] , $responseTime->getTimezone());
						$oneFeed['CreationDate'] = $creationDate->format(DateTime::ISO8601);
					}
					
					if(!empty($oneFeed['UpdatedDate'])){
						$updatedDate = new DateTime($oneFeed['UpdatedDate'] , $responseTime->getTimezone());
						$oneFeed['UpdatedDate'] = $updatedDate->format(DateTime::ISO8601);
					}
				}
			}
				
			write_log("ST100:Done. (getLazadaFeedDetail)", "info");
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
				
			$results['feeds'] = $feeds;
			return $results;
		}catch (Exception $e){
			$results['success'] = false;
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * Get all or a range of product brands.
	 * dzt 2015/11/06
	 */
	public function getLazadaBrands($param){
		$brands = array();
		$reqParams = array();
		$results = array();
		$results['success'] = false;
		$results['message'] = '';
		$errorCount = 0; // 失败尝试
	
		try{
			$reqParams["Action"] = 'GetBrands';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
	
			$timeMS1=getCurrentTimestampMS();
			do{
				$response = self::_callLazada($reqParams);
					
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					$results['message'] = $response["message"];
					write_log("ST001 : ".$response["message"]. " . (getLazadaBrands) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
	
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST001 : $errorMessage . (getLazadaBrands) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
	
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						$results['message'] = $errorMessage;
						break;
					}
					sleep(1);
					continue;
				}
					
				$successResponse = $response["response"]["SuccessResponse"];
	
				if (isset($successResponse["Body"]["Brands"]["Brand"]['Name'])){// 只有一个brand时，返回结构可能不一样 ，未测试，根据lazada其他数据结构推测
					$brands = array($successResponse["Body"]["Brands"]["Brand"]);
				}else{
					$brands = $successResponse["Body"]["Brands"]["Brand"];
				}
				$results['success'] = true;
				break;
					
			}while ($errorCount < 15);
			$timeMS2=getCurrentTimestampMS();
			write_log("getLazadaBrands UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE." t2_1=".($timeMS2-$timeMS1),"info");
				
			$results['errorCount'] = $errorCount;
			$results['state_code'] = $response['state_code'];
			// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;
			$results['brands'] = $brands;
			return $results;
		}catch (Exception $e){
			$results['message'] = $e->getMessage();
			$results['body'] = print_r($e,true);
			return $results;
		}
	}
	
	/**
	 * 根据订单item 查找 product 主图
	 * 由于不想加字段同时不清楚seller sku是不是整个站点唯一，所以这里用ShopSku保存，而搜索产品要用seller sku才可以，所以需要两个sku传入。
	 * @param $shopSku 
	 * @param $sellerSku
	 * @param $db db conection.
	 * @param $purge 是否清除缓存图片
	 * 
	 * @return array(boolean , string ) => boolean: 是否获取成功 ; string : url | 错误信息
	 * 
	 **/
	private static function _getLazadaProductMainImage($shopSku,$sellerSku,$db,$purge=false){
		$items = array();
		$reqParams = array();
		$errorCount = 0; // 失败尝试
		
		// Order items must be from the same order
		if(empty($shopSku)){
			return array(false, "'ShopSku' is needed to get order item image.","");
		}
		
		$hasRecord=false;
		if(!$purge){
    		//1.由于ShopSku在lazada shop上唯一存在的，所以这里需要把marketplace 也作为查询条件。
    		$rs = $db->query("SELECT * FROM lazada_product_image where ShopSku='".$shopSku."' and marketplace='".self::$MARKETPLACE."'");
    		$rows = $rs->fetchAll();
    			
    		foreach($rows as $row){
    			$hasRecord=true;
    			if ($row["ProductUrl"]<>NULL and $row["ProductUrl"]<>""){
    				return array(true,$row["MainImage"],$row["ProductUrl"]);
    			}
    			
    		}
		}else{
		    $ret=$db->exec('delete from lazada_product_image where ShopSku="'.$shopSku.'" and  marketplace="'.self::$MARKETPLACE.'"');
		}
		
		//2. 通过访问lazada的 getGetProducts api获取对应的图片url
		try{
			$reqParams["Action"] = 'GetProducts';
			$Timestamp = new DateTime();
			$Timestamp->setTimezone ( new DateTimeZone('UTC'));
			$reqParams["Timestamp"]=$Timestamp->format(DateTime::ISO8601);
			$reqParams = array_merge($reqParams,self::$CONFIG);
		
			// 处理api 接口参数
// 			CreatedAfter:	DateTime .Limites the returned product list to those created after or on a specified date, given in ISO 8601 date format. Optional
// 			CreatedBefore:	DateTime .Limites the returned product list to those created before or on a specified date, given in ISO 8601 date format. Optional
// 			Search:	String .Returns those products where the search string is contained in the product's name and/or SKU.
// 			Filter:	String all .Return only those products whose state matches this parameter. Possible values are all, live, inactive, deleted, image-missing, pending, rejected, sold-out. Mandatory.
// 			Limit:	Integer 1000 .The maximum number of products that should be returned. If you omit this parameter, the default of 1000 is used. The largest number of products you can request with a single call is 5000 (this is the default hard limit, which can be changed per instance). If you need to retrieve more products than that, you have to page through the result set using the Offset parameter.
// 			Offset:	Integer .Numer of products to skip (i.e., an offset into the result set; toghether with the Limit parameter, simple resultset paging is possible; if you do page through results, note that the list of products might change during paging).
// 			SkuSellerList:	Array of Strings. Only products whose SKU is contained in this list wil be returned. Can either be a JSON array (e.g., [“E213”,”KI21”,”HT0”]) or a serialized PHP array (e.g., a:3:{i:0;s:4:”E213”;i:1;s:4:”KI21”;i:2;s:3:”HT0”;}).
			
			// Order items must be from the same order
			if(empty($sellerSku)){
				return array(false, "'Sku' is needed to get product.","");
			}
			
			$reqParams['SkuSellerList'] = json_encode(array($sellerSku));
			write_log("ST200:Start to get Lazada Product: sellerSku(".$sellerSku."). (_getLazadaProductMainImage)","info");
			
			do{
				$response = self::_callLazada($reqParams);
// 				write_log("ST300:Start to get Lazada Product: sellerSku(".$sellerSku."). (_getLazadaProductMainImage) response:".print_r($response,true),"info");
				// network error or json decode error
				if($response["success"] == false){
					$errorCount++;
					write_log("ST201 : ".$response["message"]." . - ".$sellerSku." . (_getLazadaProductMainImage) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
					sleep(1);
					continue;
				}
					
				// api error
				if(isset($response["response"]["ErrorResponse"])){
					$errorResponse = $response["response"]["ErrorResponse"];
					$errorMessage = "RequestAction:".$errorResponse["Head"]["RequestAction"].",:".$errorResponse["Head"]["ErrorType"].",ErrorCode:".$errorResponse["Head"]["ErrorCode"].",ErrorMessage:".$errorResponse["Head"]["ErrorMessage"];
					$errorCount++;
					write_log("ST201 : $errorMessage . - ".$sellerSku." . (_getLazadaProductMainImage) UserID=".self::$CONFIG["UserID"]." MarketPlace=".self::$MARKETPLACE,"error");
		
					//dzt 2015-08-24--  no need to retry according to errorCode returned from lazada
					//errorCode: 1 --- E001: Parameter %s is mandatory
					//errorCode: 2 --- E002: Invalid Version
					//errorCode: 3 --- E003: Timestamp has expired
					//errorCode: 4 --- E004: Invalid Timestamp format
					//errorCode: 5 --- E005: Invalid Request Format
					//errorCode: 6 --- E006: Unexpected internal error
					//errorCode: 7 --- E007: Login failed. Signature mismatching
					//errorCode: 8 --- E008: Invalid Action
					//errorCode: 9 --- E009: Access Denied
					//errorCode: 10 --- E010: Insecure Channel
					//errorCode: 11 --- E011: Request too Big
					//errorCode: 429 --- E429: Too many requests
					//errorCode: 1000 --- E1000： Internal Application Error
					//errorCode: 30 --- E030: Empty Request
					
					//errorCode: 17 --- E017: "%s" Invalid Date Format
					//errorCode: 19 --- E019: "%s" Invalid Limit
					//errorCode: 14 --- E014: "%s" Invalid Offset
					//errorCode: 36 --- E036: Invalid status filter
					//errorCode: 70 --- E070: You have corrupt data in your sku seller list.
					$skipRetryErrorCodeArr = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 30, 429, 1000, 17, 19, 14, 36, 70);
					if (isset($errorResponse["Head"]["ErrorCode"]) and in_array($errorResponse["Head"]["ErrorCode"],$skipRetryErrorCodeArr)) {
						return array(false , $errorMessage,"");
					}
					sleep(1);
					continue;
				}
		
				if(isset($response["response"]["SuccessResponse"])){
					$successResponse = $response["response"]["SuccessResponse"];
					break;
				}
		
			}while ($errorCount < 15);
		
			write_log("ST300:Done for listing orders. (_getLazadaProductMainImage)", "info");
// 			$results['apiCallTimes'] = self::$API_CALL_TIMES;

			$isNewDataFormat = false;
			if(!empty($successResponse['Body']['Products']['Product']['SellerSku']) || !empty($successResponse['Body']['Products'][0]['PrimaryCategory']) && $isNewDataFormat = true){
			   
			    $productUrl = "";
			    $mainImageUrl = "";
			    if($isNewDataFormat){
// 			        write_log("ST400: New Data Format. (_getLazadaProductMainImage)", "info");
			        if(!empty($successResponse['Body']['Products'][0]['Skus'][0]['Images'][0]))
			             $mainImageUrl = $successResponse['Body']['Products'][0]['Skus'][0]['Images'][0];
			        
			        if (!empty($successResponse['Body']['Products'][0]['Skus'][0]['Url']))
			            $productUrl = $successResponse['Body']['Products'][0]['Skus'][0]['Url'];
			        
			    }else{
// 			        write_log("ST400: old Data Format. (_getLazadaProductMainImage)", "info");
			        $mainImageUrl = "";
			        if(!empty($successResponse['Body']['Products']['Product']['MainImage']))
			            $mainImageUrl = $successResponse['Body']['Products']['Product']['MainImage'];
			        elseif (!empty($successResponse['Body']['Products']['Product']['Images']['Image'])){
			            if(is_array($successResponse['Body']['Products']['Product']['Images']['Image'])){
			                $mainImageUrl = $successResponse['Body']['Products']['Product']['Images']['Image'][0];
			            }else{
			                $mainImageUrl = $successResponse['Body']['Products']['Product']['Images']['Image'];
			            }
			        }
			            
			        if(empty($mainImageUrl))
			            throw new Exception("no image",400);
			            
			        if (!empty($successResponse['Body']['Products']['Product']['Url'])){
			            $productUrl = $successResponse['Body']['Products']['Product']['Url'];
			        }
			    }
			    
				
				$nowTime=time();
				// 保存ShopSku和图片url的信息到数据库
				if ($hasRecord===false){
				
					$ret=$db->exec('INSERT INTO `lazada_product_image`(`ShopSku`, `MainImage`, `ProductUrl`, `marketplace`, `update_time`) '.
							' VALUES ("'.$shopSku.'","'.$mainImageUrl.'","'.$productUrl.'","'.self::$MARKETPLACE.'",'.$nowTime.')');
					if ($ret===false) { }
					return array(true ,$mainImageUrl,$productUrl);
				}else{
					$ret=$db->exec('UPDATE `lazada_product_image`  SET  MainImage="'.$mainImageUrl.'",ProductUrl="'.$productUrl.'",marketplace="'.
							self::$MARKETPLACE.'",update_time='.$nowTime.'  where ShopSku="'.$shopSku.'"'); 

					if ($ret===false) { }
					return array(true ,$mainImageUrl,$productUrl);
						
						//	(`ShopSku`, `MainImage`, `ProductUrl`, `marketplace`, `update_time`) '.
						//	' VALUES ("'.$orderIttem['Sku'].'","'.$mainImageUrl.'","'.$productUrl.'","'.self::$MARKETPLACE.'",'.$nowTime.')');
						
				}
				
			}else{			
				return array(false,"no image","");
			}
		}catch (Exception $e){
			return array(false , $e->getMessage(),"");
		}
	}
	
	/**
	 * return the xml string with given array, if value is not array ,then value would wrap by it's key like this <$key>".$val."</$key> to format a tag.
	 * @param array $info
	 * @return string
	 */
	private static function getXmlFromArray($info){
		$xmlStr = '';
		foreach($info as $attrName=>$attrVal){
			if(!is_array($attrVal)){
				$xmlStr .= "<$attrName>".$attrVal."</$attrName>";
			}else{
				$xmlStr .= "<$attrName>";
				$xmlStr .= self::getXmlFromArray($attrVal);
				$xmlStr .= "</$attrName>";
			}
		}
		
		return $xmlStr;
	}

	/**
	 * 修改请求时间DateTime的 timezone为  站点api timezone 
	 * @param unknown $info
	 */
	private static function setRequestDateTimeTimezone(&$Timestamp){
		$Timestamp->setTimezone ( new DateTimeZone(self::$LAZADA_SITE_TIMEZONE[self::$MARKETPLACE]));
	}
}