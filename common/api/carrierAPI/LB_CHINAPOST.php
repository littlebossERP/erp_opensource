<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_Curl;
use yii\base\Exception;
use Qiniu\json_decode;

class LB_CHINAPOST extends BaseCarrierAPI{
	public static $url = null;
	public static $port = null;
	
	public $ecCompanyId = null;
	public $token = null;
	
// 	1获取运单号地址：
// 	http://shipping.11185.cn:9000/produceWeb/barCodesAssgineServlet
// 	2、提交发货信息地址：
// 	http://shipping.11185.cn:8000/mqrysrv/OrderImportMultiServlet
// 	3、获取业务类型参数地址：
// 	http://shipping.11185.cn:8000/mqrysrv/OrderImportGetDataServlet
// 	4.面单下载地址
// 	http://shipping.11185.cn:8000/mqrysrv/LabelPdfDownloadServlet
	
	public function __construct(){
		if(isset(\Yii::$app->params["currentEnv"]) && \Yii::$app->params["currentEnv"]=='production'){
			self::$url = 'http://shipping.11185.cn:';
			self::$port = 8000;
		}else{
			self::$url = 'http://219.134.187.38:';
			self::$port = 8089;
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2017/03/09				初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$tmp_mailType = 'LITTLEBOSSERP';
			
			//odOrder表内容
			$order = $data['order'];
			
			//重复发货 添加不同的标识码
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
			
			if(isset($data['data']['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
// 			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
			
			//用户在确认页面提交的数据
			$e = $data['data'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$service_carrier_params = $service->carrier_params;
			$account = $info['account'];
			
			//获取到帐号中的认证参数
			$a = $account->api_params;
			
			//获取到账户中的地址信息
			$account_address = $info['senderAddressInfo'];
			
			$this->ecCompanyId = $a['ecCompanyId'];
			$this->token = $a['token'];
			
			//英国的简码可能是UK,需要转换为GB
			$tmp_consignee_country_code = $order->consignee_country_code;
			if($tmp_consignee_country_code == 'UK'){
				$tmp_consignee_country_code = 'GB';
			}
			
			if($tmp_consignee_country_code == 'NC'){
				$tmp_consignee_country_code = 'DF';
			}
			
			
			//组织条码分配数组
			$bar_code_request_arr = array('order'=>array(
				array(
					'ecCompanyId'=>$this->ecCompanyId,
					'eventTime'=>date('Y-m-d H:i:s'),
					'whCode'=>'',						//4PL_SZ
					'logisticsOrderId'=>$customer_number,
					'tradeId'=>'',
					'LogisticsCompany'=>'POST',
					'LogisticsBiz'=>$service->shipping_method_code,
					'mailType'=>$tmp_mailType,
					'faceType'=>'1',
					'Rcountry'=>$tmp_consignee_country_code
			)
				)
			);
			
			$bar_code_request_json = json_encode($bar_code_request_arr);
	// 		$tmp_data_digest = base64_encode(md5($bar_code_request_json.$this->token));
			$tmp_data_digest = base64_encode(pack('H*', md5($bar_code_request_json.$this->token)));
			
			$post_data = array();
			$post_data['logisticsOrder'] = $bar_code_request_json;
			$post_data['data_digest'] = $tmp_data_digest;
			$post_data['msg_type'] = 'B2C_TRADE';
			$post_data['ecCompanyId'] = $this->ecCompanyId;
			$post_data['version'] = '1.0';
			
			if(self::$port == 8000){
				$tmp_barCodesAssgineServlet_port = 9000;
			}else{
				$tmp_barCodesAssgineServlet_port = self::$port;
			}
			
			$response_json = self::chinaPostCurl('/produceWeb/barCodesAssgineServlet', $post_data, $tmp_barCodesAssgineServlet_port)['response'];
// 			$response_json = '{"return_success":"true","barCodeList":[{"bar_code":"GD004602465CN","logisticsOrderId":"00900111640994094U2"}]}';
			
			$response = json_decode($response_json, true);
			
// 			print_r($response);
// 			exit;
			
// 			file,carrier_api
			\Yii::info('LB_CHINAPOST,puid:'.$puid.',request_barCodesAssgineServlet,order_id:'.$order->order_id.' '.json_encode($post_data),"carrier_api");
			\Yii::info('LB_CHINAPOST,puid:'.$puid.',result_barCodesAssgineServlet,order_id:'.$order->order_id.' '.$response_json,"carrier_api");
			
			if(!isset($response['return_success'])){
				return self::getResult(1, '', '失败原因:'.$response_json);
			}
			
			if($response['return_success'] == 'false'){
				$tmp_error1 = '';
				
				try{
					$tmp_error1 = self::getErrorState('trackingNo', $response['return_msg']);
				}catch (\Exception $exb1){
				}
				
				return self::getResult(1, '', '失败原因:'.$response['return_msg'].' '.$tmp_error1);
			}
			
			if($response['return_success'] == 'true'){
				if(isset($response['barCodeList'][0])){
					//eventSource  中邮小包API对接(2106492589) 11:54:55填mailType这个一样的参数就可以了
					
					$tmp_event_header = 
						'<eventHeader>
							<eventType>LOGISTICS_BATCH_SEND</eventType>
							<eventTime>'.date('Y-m-d H:i:s').'</eventTime>
							<eventSource>'.$tmp_mailType.'</eventSource>
							<eventTarget>CPG</eventTarget>
						</eventHeader>';
					
					
					$tmp_order_infos = '';
					
					//统计总的报关重量和报关金额
					$tmp_Itotleweight_sum = 0;
					$tmp_Itotlevalue_sum = 0;
					
					foreach ($e['EName'] as $k=>$v){
						$tmp_strlen = strlen($e['CName'][$k]);
						if(($tmp_strlen == 0) || ($tmp_strlen >= 60)){
							return self::getResult(1, '', '失败原因 中文报关名:'.(($tmp_strlen==0) ? '必填' : '长度限制为60字节,一个中文字符等于3个字节'));
						}
						
						$tmp_strlen = strlen($e['EName'][$k]);
						if(($tmp_strlen == 0) || ($tmp_strlen >= 60)){
							return self::getResult(1, '', '失败原因 英文报关名:'.(($tmp_strlen==0) ? '必填' : '长度限制为60字节,一个中文字符等于3个字节'));
						}
						
						$tmp_strlen = strlen($e['productId'][$k]);
						if(($tmp_strlen == 0) || ($tmp_strlen >= 50)){
							return self::getResult(1, '', '失败原因 内件商品 ID:'.(($tmp_strlen==0) ? '必填' : '长度限制为50字节,一个中文字符等于3个字节'));
						}
						
						
						$tmp_order_infos .= '<product>'.
							'<productNameCN>'.$e['CName'][$k].'</productNameCN>'.
							'<productNameEN>'.$e['EName'][$k].'</productNameEN>'.
							'<productQantity>'.$e['productQantity'][$k].'</productQantity>'.
							'<productCateCN>'.$e['CName'][$k].'</productCateCN>'.	//内件类目名(中文)	暂时使用中文报关名来代替
							'<productCateEN>'.$e['EName'][$k].'</productCateEN>'.	//内件类目名(英文)	暂时使用英文报关名来代替
							'<productId>'.$e['productId'][$k].'</productId>'.
							'<producingArea>CN</producingArea>'.
// 							'<productWeight>'.((int)($e['productWeight'][$k]) * (int)($e['productQantity'][$k])).'</productWeight>'.
// 							'<productPrice>'.((int)($e['delcarevalue'][$k]) * (int)($e['productQantity'][$k])).'</productPrice>'.
							'<productWeight>'.((int)($e['productWeight'][$k])).'</productWeight>'.
							'<productPrice>'.((int)($e['delcarevalue'][$k])).'</productPrice>'.
							'</product>';
						
						$tmp_Itotleweight_sum += (int)($e['productWeight'][$k]) * (int)($e['productQantity'][$k]);
						$tmp_Itotlevalue_sum += (int)($e['delcarevalue'][$k]) * (int)($e['productQantity'][$k]);
					}
					
// 					print_r($tmp_order_infos);
// 					exit;
					
// 					//英国的简码可能是UK,需要转换为GB
// 					$tmp_consignee_country_code = $order->consignee_country_code;
// 					if($tmp_consignee_country_code == 'UK'){
// 						$tmp_consignee_country_code = 'GB';
// 					}
					
					//收件人省份为空直接用城市代替
					$tmpConsigneeProvince = $order->consignee_province;
					if(empty($tmpConsigneeProvince)){
						$tmpConsigneeProvince = $order->consignee_city;
					}
					
					//组织收件人地址信息
					$addressAndPhoneParams = array(
							'address' => array(
									'consignee_address_line1_limit' => 512,
							),
							'consignee_district' => 1,
							'consignee_county' => 1,
							'consignee_company' => 1,
							'consignee_phone_limit' => 20
					);
					
					$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
					
					$tmp_order_str = 
						'<order>'.
							'<orderInfos>'.$tmp_order_infos.'</orderInfos>'.
							'<ecCompanyId>'.$this->ecCompanyId.'</ecCompanyId>'.
							'<whCode></whCode>'.
							'<logisticsOrderId>'.$customer_number.'</logisticsOrderId>'.
							'<tradeId></tradeId>'.
							'<mailNo>'.$response['barCodeList'][0]['bar_code'].'</mailNo>'.
							'<LogisticsCompany>POST</LogisticsCompany>'.
							'<LogisticsBiz>'.$service->shipping_method_code.'</LogisticsBiz>'.
							'<ReceiveAgentCode>'.'POST'.'</ReceiveAgentCode>'.
							'<Rcountry>'.$tmp_consignee_country_code.'</Rcountry>'.
							'<Rprovince>'.$tmpConsigneeProvince.'</Rprovince>'.
							'<Rcity>'.$order->consignee_city.'</Rcity>'.
							'<Raddress>'.$addressAndPhone['address_line1'].'</Raddress>'.
							'<Rpostcode>'.$order->consignee_postal_code.'</Rpostcode>'.
							'<Rname>'.$order->consignee.'</Rname>'.
							'<Rphone>'.$addressAndPhone['phone1'].'</Rphone>'.
							'<Sname>'.$account_address['shippingfrom']['contact_en'].'</Sname>'.
							'<Sprovince>'.$account_address['shippingfrom']['province_en'].'</Sprovince>'.
							'<Scity>'.$account_address['shippingfrom']['city_en'].'</Scity>'.
							'<Saddress>'.$account_address['shippingfrom']['street_en'].'</Saddress>'.
							'<Sphone>'.$account_address['shippingfrom']['phone'].'</Sphone>'.
							'<Spostcode>'.$account_address['shippingfrom']['postcode'].'</Spostcode>'.
							'<insureValue>'.($e['insureValue'] == '' ? 0 : $e['insureValue']).'</insureValue>'.
							'<insuranceValue>'.($e['insuranceValue'] == '' ? 0 : $e['insuranceValue']).'</insuranceValue>'.
							'<remark>'.$e['remark'].'</remark>'.
							'<channel>'.$service_carrier_params['channel'].'</channel>'.
							'<Itotleweight>'.$tmp_Itotleweight_sum.'</Itotleweight>'.
							'<Itotlevalue>'.$tmp_Itotlevalue_sum.'</Itotlevalue>'.
							'<totleweight>'.$tmp_Itotleweight_sum.'</totleweight>'.
							'<hasBattery>'.$e['hasBattery'].'</hasBattery>'.
							'<country>CN</country>'.
							'<mailKind>'.$service_carrier_params['mailKind'].'</mailKind>'.
							'<mailClass>L</mailClass>'.
							'<batchNo>'.$customer_number.'</batchNo>'.		//
							'<mailType>'.$tmp_mailType.'</mailType>'.
							'<faceType>2</faceType>'.
							'<undeliveryOption>'.$service_carrier_params['undeliveryOption'].'</undeliveryOption>'.
						'</order>';
					
					
					$tmp_data_message = '<logisticsEventsRequest><logisticsEvent>'.$tmp_event_header.'<eventBody>'.$tmp_order_str.'</eventBody>'.'</logisticsEvent></logisticsEventsRequest>';
					
					$tmp_data_digest_body = base64_encode(pack('H*', md5($tmp_data_message.$this->token)));
					
					$post_body_data = array();
					$post_body_data['logistics_interface'] = $tmp_data_message;
					$post_body_data['data_digest'] = $tmp_data_digest_body;
					$post_body_data['msg_type'] = 'B2C_TRADE';
					$post_body_data['ecCompanyId'] = $this->ecCompanyId;
					$post_body_data['version'] = '2.0';
						
					$response_json_body = self::chinaPostCurl('/mqrysrv/OrderImportMultiServlet', $post_body_data, self::$port)['response'];
					
// 					file,carrier_api
					\Yii::info('LB_CHINAPOST,puid:'.$puid.',request_OrderImportMultiServlet,order_id:'.$order->order_id.' '.json_encode($post_body_data),"carrier_api");
					\Yii::info('LB_CHINAPOST,puid:'.$puid.',result_OrderImportMultiServlet,order_id:'.$order->order_id.' '.$response_json_body,"carrier_api");
					
// 					print_r($response_json_body);
// 					exit;
					
					try {
						$result = simplexml_load_string($response_json_body);
					}catch (\Exception $ex){
						return self::getResult(1, '', '失败原因2:'.$response_json_body);
					}
					
					if($result->responseItems->response->success == 'true'){
						//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
						$r = CarrierAPIHelper::orderSuccess($order, $service, $response['barCodeList'][0]['logisticsOrderId'], OdOrder::CARRIER_WAITING_PRINT, $response['barCodeList'][0]['bar_code']);
							
						return BaseCarrierAPI::getResult(0, $r, '操作成功!跟踪号'.$response['barCodeList'][0]['bar_code']);
					}else if($result->responseItems->response->success == 'false'){
						$tmp_error = '';
// 						$tmp_error = self::getErrorState('createOrder', $result->responseItems->response->reason);

						try{
							$tmp_error = $result->responseItems->response->msg;
						}catch (\Exception $exb){
						}
						
						return self::getResult(1, '', '失败原因e2:'.$result->responseItems->response->reason.' '.$tmp_error);
					}
				}
			}
			
			return self::getResult(1, '', '失败原因:'.'未知原因请联系小老板客服');
		}catch(CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消已确定的物流单。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2017/03/10				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			$puid = $user->getParentUid();
			
			if(count($data) > 50)
				throw new CarrierException('中国邮政一次只能批量打印50张面单');
		
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
		
			//获取到帐号中的认证参数
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			$this->ecCompanyId = $account_api_params['ecCompanyId'];
			$this->token = $account_api_params['token'];
			
			$tmp_barCode = '';
		
			foreach ($data as $key => $value) {
				$order = $value['order'];
		
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				
				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$order->order_id .' 缺少跟踪号,请检查订单是否已上传' );
				}
				
				$tmp_barCode .= ($tmp_barCode == '') ? $shipped->tracking_number : ','.$shipped->tracking_number;
			}
			
			$tmp_barCode_Digest = base64_encode(pack('H*', md5($tmp_barCode.$this->token)));
			
			$post_body_data = array();
			$post_body_data['ecCompanyId'] = $this->ecCompanyId;
			$post_body_data['dataDigest'] = $tmp_barCode_Digest;
			$post_body_data['barCode'] = $tmp_barCode;
			$post_body_data['labelType'] = empty($carrier_params['labelType']) ? '5' : $carrier_params['labelType'];
			$post_body_data['version'] = '1.0';
			
			$result = self::chinaPostCurl('/mqrysrv/LabelPdfDownloadServlet', $post_body_data, self::$port);
			
			if($result['http_code'] == 200){
				$pdfUrl = CarrierAPIHelper::savePDF($result['response'], $puid, $account->carrier_code.'_', 0);
				
				return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			}else if(($result['http_code'] != 200) && ($result['content_type'] == 'text/json')){
				return self::getResult(1, '', $result['response']);
			}else{
				return self::getResult(1, '', $result['response']);
			}
		}catch(Exception $e) {
			return self::getResult(1,'',$e->getMessage());
		}
	}
	
	//LogisticsCompany（ 物流公司代码） 字段配置数据获取
	public static function getCompanyConfigurationData(){
		$header=array();
		$header[] = 'application/x-www-form-urlencoded;charset=UTF-8';
		$request['queryType'] = 'queryCompany';
		
		$response = Helper_Curl::post(self::$url.'8000'.'/mqrysrv/OrderImportGetDataServlet?queryType=queryCompany', $request, $header);
		
		$tmp_company_a = json_decode($response, true);
		
		if(isset($tmp_company_a['data'][0])){
			return self::getResult(0, $tmp_company_a['data'][0], '');
		}else{
			return self::getResult(1, '', '返回结构异常，请联系小老板客服!');
		}
	}
	
	/**
	 * 用来获取运输方式
	 * @author		hqw		2017/03/16				初始化
	 * 公共方法
	 **/
	public static function getCarrierShippingServiceStr($account){
		$header=array();
		$header[] = 'application/x-www-form-urlencoded;charset=UTF-8';
		$request['queryType'] = 'queryBusinessType';
		
		$response = Helper_Curl::post(self::$url.'8000'.'/mqrysrv/OrderImportGetDataServlet?queryType=queryBusinessType', $request, $header);
		
		$tmp_logistics_a = json_decode($response, true);
		
		$result = '';
		
		if(isset($tmp_logistics_a['data']['0'])){
			foreach ($tmp_logistics_a['data'] as $tmp_val){
				$result .= $tmp_val['businessCode'].':'.$tmp_val['businessName'].';';
			}
		}
		
		if(empty($result)){
			return self::getResult(1, '', '');
		}else{
			return self::getResult(0, $result, '');
		}
	}
	
	//封装为通用访问中国邮政接口
	public static function chinaPostCurl($request_url, $post_data, $tmp_port){
		$header=array();
		$header[] = 'Content-type:application/x-www-form-urlencoded;charset=utf-8';
		
		$o = "";
		foreach ($post_data as $k => $v){
			$o .= "$k=" . urlencode ( $v ) . "&";
		}
		$tmp_post_data = substr ($o, 0, - 1);
		
// 		http://219.134.187.38:8089/mqrysrv/LabelPdfDownloadServlet
		
		$response_json = Helper_Curl::post(self::$url.$tmp_port.$request_url, $tmp_post_data, $header);
		
// 		print_r(Helper_Curl::$last_post_info);

		$result = array('http_code'=>Helper_Curl::$last_post_info['http_code'], 'content_type'=>Helper_Curl::$last_post_info['content_type'], 'response'=>$response_json);
		
		return $result;
	}
	
	//错误代码
	public static function getErrorState($type, $error_code){
		$errorArr = array();
		
		if($type == 'trackingNo'){
			$errorArr = array('S01'=>'非法的 JSON 格式',
				'S02'=>'非法的授权验证(账号和秘钥)',
				'S03'=>'非法的数字签名',
				'S04'=>'网络超时，请重试',
				'S05'=>'系统异常，请重试',
				'S06'=>'非法版本号 version 不正确',
				'S07'=>'分配条码失败，请检查参数',
				'B01'=>'暂无条码分配',
				'B02'=>'业务类型错误',
				'B03'=>'缺失参数',
				'B04'=>'超出单次获取条码最大数',
				'B05'=>'获取条码过于频繁，请稍后再试',
				'B06'=>'获取条码客户号不一致',
				'B07'=>'参数过长',
				'B08'=>'非法物流公司代码',
				'B09'=>'非法字符(逗号, \n)',
				'B12'=>'寄达国不支持该业务类型',
			);
		}else if($type == 'createOrder'){
			$errorArr = array(
				'S01'=>'非法的 XML/JSON',
				'S02'=>'非法的数字签名',
				'S03'=>'非法的物流公司/仓储公司',
				'S04'=>'非法的通知类型(version 版本信息不正确)',
				'S05'=>'非法的消息类型',
				'S06'=>'系统异常，请重试',
				'S12'=>'非法的电商标识',
				'S14'=>'非法特殊字符，如+,||号等',
				'S15'=>'非法寄达国编码',
				'B00'=>'未知业务错误',
				'B01'=>'订单XML数据不完整(缺失XML节点)',
				'B02'=>'时间格式错误',
				'B03'=>'必填数据为空',
				'B05'=>'业务类型不正确',
				'B06'=>'订单号重复',
				'B07'=>'参数过长',
				'B08'=>'条码重复',
				'B09'=>'条码验证中,请十分钟后再提交!',
				'B10'=>'非本订单所属条码',
				'B11'=>'非平台发货条码',
				'B12'=>'邮件重量错误,不能超过2000g',
				'B13'=>'非法内件或内件类目名称非英文',
				'B14'=>'账户当前余额必须大于设定的阀值',
				'B15'=>'资金账户为禁用或退款中状态',
				'B16'=>'简易小包寄达国错误',
				'B98'=>'数据保存失败',
			);
		}
		
		if(isset($errorArr[$error_code])){
			return $errorArr[$error_code];
		}
		
		return '';
	}
	
}

?>