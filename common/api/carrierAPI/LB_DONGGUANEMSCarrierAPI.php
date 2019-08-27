<?php
namespace common\api\carrierAPI;


use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;

class LB_DONGGUANEMSCarrierAPI extends BaseCarrierAPI
{
	
	public $soapClient = null;            // SoapClient实例
	public $wsdl = null;                  // 物流接口
	public $userToken = null;             //用户唯一标识
	
	public $autoFetchTrackingNo = null;   //运输方式中的自动提取运单号
	public $trackingNoRuleMemo = null;    //运输方式中的运单号编码规则描述
	public $trackingNoRuleRegex = null;   //运输方式中的跟踪单号编码规则（采用正则表达式）
	public $trackingNo = null;            //用于订单提交的服务商跟踪号码
	
	static $connecttimeout=60;
	static $timeout=500;
	static $last_post_info=null;
	static $last_error =null;
	
	function __construct(){
		$this->wsdl='http://www.dggjems.com:8087/xms/services/order?wsdl';
	
		if(is_null($this->soapClient)||!is_object($this->soapClient)){
			try {
				$this->soapClient = new \SoapClient($this->wsdl,array('soap_version' => SOAP_1_1));
			}catch (Exception $e){
				return self::getResult(1,'','网络连接故障'.$e->getMessage());
			}
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			 
			//重复发货 添加不同的标识码
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			$customer_number =$data['data']['customer_number'];
		
			if(isset($data['data']['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}
			
			$order = $data['order'];// object OdOrder 订单对象
			$data = $data['data'];  // 表单提交的数据
		
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
			$shipped = $checkResult['data']['shipped']; // object OdOrderShipped
		
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];// 客户相关信息
			$service = $info['service'];// 运输服务相关信息
		
			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
		
			$account_api_params = $account->api_params;//获取到帐号中的认证参数
			$shippingfrom_address = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息(shppingfrom是属于客户填“发货地址”的信息)
		
			//设置用户密钥Token
			$this->userToken = isset($account_api_params['userToken']) ? trim($account_api_params['userToken']):'';
		
			############# start 判断是否需要填写运单号来提交订单 ################
			/**
			 * 在所有运输方式中搜索用户使用的对应运输方式代码，找出对应运输方式的autoFetchTrackingNo字段：
			 * 若为Ｙ，则不填，统一将物流商返回的运单号作为真正运单号（不然trackingNoRuleRegex字段有规则的话，则按用户根据规则自定义的运单号作为真正运单号）；
			 * 若为Ｎ，且trackingNoRuleRegex为空的话，用$customer_number作为用户自定义随机的运单号提交订单，来作为这订单的返回运单号。否则trackingNoRuleRegex不为空的话，则按照规则来输入运单号。
			 */
			$all_shipping_method_data = $this->soapClient->getTransportWayList($this->userToken);
		
			if($all_shipping_method_data->success == false){
				return self::getResult(1, '', '操作失败！错误信息：'.$all_shipping_method_data->error->errorInfo);
			}
		
			$shipping_method_Arr = $all_shipping_method_data->transportWays;
		
			foreach($shipping_method_Arr as $shipping_method){
				if($shipping_method->code == $service->shipping_method_code) {
					$this->autoFetchTrackingNo = $shipping_method->autoFetchTrackingNo;
					$this->trackingNoRuleRegex = '/'.$shipping_method->trackingNoRuleRegex.'/';
					$this->trackingNoRuleMemo = $shipping_method->trackingNoRuleMemo;
					break;
				}
			}
			
			if($this->autoFetchTrackingNo=='N'){
				if(empty($this->trackingNoRuleRegex)){
					$trackingNo = $customer_number;
				}else{
					if(!preg_match($this->trackingNoRuleRegex, $data['trackingNo'])){
						return self::getResult(1, "", "该运输服务中，服务商跟踪号码为必填项" . "<br>请根据规则：“".$this->trackingNoRuleMemo."”填写服务商跟踪号作为该订单的运单号（跟踪号）");
					}
					else{
						$trackingNo =  $data['trackingNo'];
					}
				}
			}else if($this->autoFetchTrackingNo=='Y') {
				$trackingNo = '';
			}
			########### end 判断是否需要填写运单号来提交订单 ####################
		
			$addressAndPhoneParams = array(
				'address' => array(
					'consignee_address_line1_limit' => 200,
					'consignee_address_line2_limit' => 1,
					'consignee_address_line3_limit' => 1,
				),
				'consignee_district' => 60,
				'consignee_county' => 60,
                'consignee_company' => 100,
				'consignee_phone_limit' => 100
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

			//订单参数
			$createOrderRequest  = array(
				'orderNo'=>$customer_number,  //客户单号。length <= 32
				'trackingNo'=>$trackingNo, //服务商跟踪号码
				'transportWayCode'=>$service->shipping_method_code,   //运输方式代码
				'cargoCode'=>$data['cargoCode'],       //(必填)货物类型
				'originCountryCode'=>$shippingfrom_address['country'],  //起运国家二字简码
				'destinationCountryCode'=>$order->consignee_country_code,   //(必填)目的国家二字简码
				'shipperCompanyName'=>$shippingfrom_address['company'],  //发件人公司名称,
				'shipperName'=>$shippingfrom_address['contact'],    //发件人姓名
				'shipperAddress'=>$shippingfrom_address['street'],    //发件人地址
				'shipperTelephone'=>$shippingfrom_address['phone'],    //发件人电话号码
				'shipperMobile'=>$shippingfrom_address['mobile'],    //发件人手机号码
				'shipperPostcode'=>$shippingfrom_address['postcode'],    //发件人邮编
				'consigneeCompanyName'=>$order->consignee_company,    //收件人公司名称
				'consigneeName'=>$order->consignee,   //(必填)收件人姓名
				'street'=>$addressAndPhone['address_line1'],     //(必填)街道
				'city'=>$order->consignee_city,    //(必填)城市
				'province'=>$order->consignee_province,     //(必填)州/省
				'consigneePostcode'=>$order->consignee_postal_code,     //收件人邮编
                'consigneeTelephone'=>$order->consignee_phone,    //收件人电话号码
		        'consigneeMobile'=>$order->consignee_mobile,     //收件人手机号码
		        'insured'=>$data['insured'],    //(必填)购买保险(投保：Y，不投保：N)
                'goodsCategory'=>$data['goodsCategory'],   //(必填)物品类别
                'goodsDescription'=>$data['goodsDescription'],    //物品类别内容
		        'memo'=>$order->desc,    //备注
		    );
		
            $totalWeight = 0;  //订单总重量
		    $totalPieces = 0;  //订单总物品数量
		    $declareItems = array();	//配货信息
		    
		    foreach($order->items as $j=>$vitem){
		    	if(empty($data['name'][$j])){
		    		return self::getResult(1, '', '错误信息：英文申报品名必填！');
		    	}
		    	if(empty($data['cnName'][$j])){
		    		return self::getResult(1, '', '错误信息：中文申报品名必填！');
		    	}
		    	if(empty($data['pieces'][$j])){
		    		return self::getResult(1, '', '错误信息：件数必填！');
		    	}
		    	if(empty($data['netWeight'][$j])){
		    		return self::getResult(1, '', '错误信息：净重必填！');
		    	}
		    	if(empty($data['unitPrice'][$j])){
		    		return self::getResult(1, '', '错误信息：单价必填！');
		    	}
		    
		    	if(preg_match("/[\x7f-\xff]/", $data['name'][$j]) || mb_strlen($data['name'][$j])>100) {
		    		return self::getResult(1, '', '错误信息：英文申报品名不能含有中文且不能大于100');
		    	}
		    	if(!preg_match("/[\x7f-\xff]/", $data['cnName'][$j]) || mb_strlen($data['cnName'][$j])>100) {
		    		return self::getResult(1, '', '错误信息：中文申报品名必须含有中文且不能大于100');
		    	}
		    	if(!is_numeric($data['pieces'][$j]) || $data['pieces'][$j]<1 || $data['pieces'][$j]>1000000){
		    		return self::getResult(1, '', '错误信息：件数必须是数值类型且数值须介于1~1000000之间');
		    	}
		    	if(!is_numeric($data['netWeight'][$j]) || $data['netWeight'][$j]<0 || $data['netWeight'][$j]>1000000){
		    		return self::getResult(1, '', '错误信息：净重必须是数值类型且数值须介于0~1000000之间');
		    	}
		    	if(!is_numeric($data['unitPrice'][$j]) || $data['unitPrice'][$j]<0 || $data['unitPrice'][$j]>100000000){
		    		return self::getResult(1, '', '错误信息：单价必须是数值类型且数值须介于0~100000000之间');
		    	}
		    
		    	$declareItems[$j] = [
			    	'name'=>empty($data['name'][$j]) ? '' : $data['name'][$j],       //（必填）英文申报品名
			    	'cnName'=>empty($data['cnName'][$j]) ? '': $data['cnName'][$j],    //（自己设为必填）中文申报品名（注：运输方式为“线下E邮宝”时，需要填写。所以干脆设为必填）
			    	'pieces'=>empty($data['pieces'][$j]) ? '':$data['pieces'][$j] ,    //（必填）件数
			    	'netWeight'=>empty($data['netWeight'][$j]) ? '' : round(($data['netWeight'][$j])/1000,2),//（必填）净重填入的是(G)，需要转换为KG，因为物流商接受的数据是KG
			    	'unitPrice'=>empty($data['unitPrice'][$j]) ? '': $data['unitPrice'][$j],   //（必填）单价
			    	'productMemo'=>empty($data['productMemo'][$j]) ? '': $data['productMemo'][$j],
			    	'customsNo'=>empty($data['customsNo'][$j]) ? '': $data['customsNo'][$j],
		    	];
		    
		    	$totalPieces += $declareItems[$j]['pieces'] ;   //总件数
		    	$totalWeight += round(($data['netWeight'][$j])/1000,2) * $declareItems[$j]['pieces'];  //总重量
		    }
		
			$createOrderRequest['declareItems'] = $declareItems;
			$createOrderRequest['pieces'] = $totalPieces;  //(必填)订单总货物件数
			$createOrderRequest['weight'] = $totalWeight;  //(必填)订单总货物重量(KG)
		
			if($totalWeight>1000){
				return self::getResult(1, '', '错误信息：订单总重量必须介于0~1000KG之间');
			}
		
			if(strlen($addressAndPhone['address_line1'])>195){
				return self::getResult(1, "", "收件信息中：<br>地址1+地址2+地址3+区+镇+公司名（没填项则不占符）<br>以上总长度不能超过195字符");
			}
		
			$responseData = $this->soapClient->createAndAuditOrder($this->userToken,$createOrderRequest);
			
			if($responseData->success==1){
				$trackingNo = empty($responseData->trackingNo)?$responseData->id:$responseData->trackingNo;
				$tracking_msg = empty($responseData->trackingNo)?'':'<br>服务商跟踪号：'.$responseData->trackingNo;
				
				//$this->carrierOrderId =$responseData->id;//订单编号，提供给标签打印使用
				//上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态（浩远直接返回物流号但不一定是真的，所以跳到第2步），跟踪号(选填),returnNo(选填)
				$r = CarrierApiHelper::orderSuccess( $order , $service , $responseData->id , OdOrder::CARRIER_WAITING_PRINT , $trackingNo);
			
				try{
					$print_param = array();
					$print_param['carrier_code'] = $service->carrier_code;
					$print_param['api_class'] = 'LB_DONGGUANEMSCarrierAPI';
					$print_param['userToken'] = $this->userToken;
					$print_param['tracking_number'] = $trackingNo;
					$print_param['carrier_params'] = $service->carrier_params;
					
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $responseData->id, $print_param);
				}catch (\Exception $ex){}
				
				return  self::getResult(0,$r, "操作成功! 订单编号：".$responseData->id .$tracking_msg);
			}else{
				/**检查物流商后台是否已存在此订单，有则上传；没则提示原来的错误*/
				if($responseData->error->errorCode == 2001){
					$OrderInfoResponse = $this->soapClient->lookupOrder($this->userToken,array('orderNo'=>$customer_number));
					
					if($OrderInfoResponse->success == 1 && !empty($OrderInfoResponse->order)){
						$trackingNo = empty($OrderInfoResponse->order->trackingNo)?$OrderInfoResponse->order->orderId:$OrderInfoResponse->order->trackingNo;
						$tracking_msg = empty($OrderInfoResponse->order->trackingNo)?'':'<br>服务商跟踪号：'.$OrderInfoResponse->order->trackingNo;
						
						$r = CarrierApiHelper::orderSuccess( $order , $service , $OrderInfoResponse->order->orderId , OdOrder::CARRIER_WAITING_PRINT , $trackingNo);
						return  self::getResult(0,$r, "操作成功! 订单编号：".$OrderInfoResponse->order->orderId .$tracking_msg);
					}
					else{
						return self::getResult(1, '', '操作失败！错误信息：'.$responseData->error->errorInfo);
					}
				}
				return self::getResult(1, '', '操作失败！错误信息：'.$responseData->error->errorInfo);
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',"line:".$e->getLine()." ".$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号成功后就会返回跟踪号');
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try {
			$user=\Yii::$app->user->identity;
		
			if(empty($user))return  self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
		
			$all_message = current($data);reset($data);//打印时是逐个运输方式的多张订单传入，所以获取一次account、service的信息就可以了
			$order_object=$all_message['order'];//获取订单的对象
		
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,1,$order_object);
		
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order_object);
			$account = $info['account'];
			$service = $info['service'];
		
			$account_api_params = $account->api_params;//获取到帐号中的认证参数
			$normal_params = $service->carrier_params; ////获取打印方式
		
			//设置用户密钥Token
			$this->userToken = isset($account_api_params['userToken']) ? trim($account_api_params['userToken']):'';
		
			$tmp_printSelect = empty($normal_params['printSelect']) ?'2':$normal_params['printSelect'];  //选择打印样式
			$tmp_pageSizeCode = isset($normal_params['pageSizeCode']) ? $normal_params['pageSizeCode'] : '6' ;    //纸张尺寸
		
			/** start 查询出订单/多订单跟踪号列表*/
			$tracking_number_list = '';
			foreach($data as $v){
				$oneOrder = $v['order'];
		
				$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
				$shipped = $checkResult['data']['shipped'];//获取订单和运输方式相应部分信息
		
				if(!empty($shipped->tracking_number)){
					$tracking_number_list .= $shipped->tracking_number.',';//服务商跟踪号码列表
				}else{  //这个可不做判断，因为在这个物流商里肯定都有跟踪号返回，不然订单申请为失败，跳转不到标签打印这一模块
					return self::getResult(1, '', '订单编号为：'.$shipped->customer_number.'缺少服务商跟踪号，请检查订单是否上传!');
				}
			}
			/** end 查询出单个订单/多订单跟踪号列表*/
			
			$print_url = 'http://www.dggjems.com/xms/client/order_online!print.action?userToken='.$this->userToken.'&trackingNo='.rtrim($tracking_number_list,',').
				'&pageSizeCode='.$tmp_pageSizeCode.'&printSelect='.$tmp_printSelect;
			
			return self::getResult(0,['pdfUrl'=>$print_url, 'type'=>'1'],'连接已生成,请点击并打印');
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	// 获取运输方式
	public function getCarrierShippingServiceStr($account){
		try {
			// TODO carrier user account @XXX@
			$tokenOfficial = '@XXX@';    //正式环境
			$response = $this->soapClient->getTransportWayList($tokenOfficial);
			$channelArr = $response->transportWays;
	
			if(empty($channelArr) || !is_array($channelArr) || json_last_error()!=false){
				return self::getResult(1,'','获取运输方式失败');
			}
	
			$channelStr="";
			foreach ($channelArr as $countryVal){
				$channelStr.=$countryVal->code.":".$countryVal->name.";";
			}
	
			if(empty($channelStr)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $channelStr, '');
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 * 用于验证物流账号信息是否真实
	 * $data 用于记录所需要的认证信息
	 *
	 * 【用趣物流的“删除订单接口”来间接验证账号的真实性】
	 *
	 * return array(is_support,error,msg)
	 * 			is_support:表示该货代是否支持账号验证  1表示支持验证，0表示不支持验证
	 * 			error:表示验证是否成功	1表示失败，0表示验证成功
	 * 			msg:成功或错误详细信息
	 */
	public function getVerifyCarrierAccountInformation($data){
		$result = array('is_support'=>1,'error'=>1);
		try{
			$responseData = $this->soapClient->deleteOrder($data['userToken'],'');
			if($responseData->error->errorCode == 2001){    //2001错误：订单号不存在
				$result['error'] = 0;
			}
		}catch(CarrierException $e){}
		return $result;
	}
	
	/**
	 * 获取API的打印面单标签
	 * 这里需要调用接口货代的接口获取10*10面单的格式
	 *
	 * @param $SAA_obj			表carrier_user_label对象
	 * @param $print_param		相关api打印参数
	 * @return array()
	 * Array
	 (
	 [error] => 0	是否失败: 1:失败,0:成功
	 [msg] =>
	 [filePath] => D:\wamp\www\eagle2\eagle/web/tmp_api_pdf/20160316/1_4821.pdf
	 )
	 */
	public function getCarrierLabelApiPdf($SAA_obj, $print_param){
		try {
			$puid = $SAA_obj->uid;
			
			//设置用户密钥Token
			$this->userToken = trim($print_param['userToken']);
			$normal_params = empty($print_param['carrier_params']) ? array() : $print_param['carrier_params']; ////获取打印方式
			
			$tmp_printSelect = empty($normal_params['printSelect']) ?'2':$normal_params['printSelect'];  //选择打印样式
			$tmp_pageSizeCode = isset($normal_params['pageSizeCode']) ? $normal_params['pageSizeCode'] : '6' ;    //纸张尺寸
			
			$tracking_number_list = $print_param['tracking_number'];
			
			$print_url = 'http://www.dggjems.com/xms/client/order_online!print.action?userToken='.$this->userToken.'&trackingNo='.$tracking_number_list.
				'&pageSizeCode='.$tmp_pageSizeCode.'&printSelect='.$tmp_printSelect;
			
			$pdf_respond = self::get($print_url, null, null, false, null, null, true);
			$pdfPath = CarrierAPIHelper::savePDF2($pdf_respond,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
			return $pdfPath;
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
	
	//修改过的Curl的GET方法（处理了$requestFollowlocation这个参数）
	public static function get($url,$requestBody=null,$requestHeader=null,$justInit=false,$responseSaveToFileName=null,$http_version=null,$requestFollowlocation=false){
		$connection = curl_init();
	
		curl_setopt($connection, CURLOPT_URL,$url);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		if (!is_null($requestHeader)){
			curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
		}
		if (!is_null($http_version)){
			curl_setopt($connection, CURLOPT_HTTP_VERSION, $http_version);
		}
		if (!is_null($responseSaveToFileName)){
			$fp=fopen($responseSaveToFileName,'w');
			curl_setopt($connection, CURLOPT_FILE, $fp);
		}else {
			curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		}
		curl_setopt($connection, CURLOPT_CONNECTTIMEOUT,self::$connecttimeout);
		curl_setopt($connection, CURLOPT_TIMEOUT,self::$timeout);
		if ($justInit){
			return $connection;
		}
		if ($requestFollowlocation){
			//启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
			curl_setopt($connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($connection, CURLOPT_MAXREDIRS, 3);
		}
	
		$response = curl_exec($connection);
		self::$last_post_info=curl_getinfo($connection);
		$error=curl_error($connection);
		curl_close($connection);
		if (!is_null($responseSaveToFileName)){
			fclose($fp);
		}
		if ($error){
			throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url.'DATA:'.$requestBody);
		}
		return $response;
	}
	
}