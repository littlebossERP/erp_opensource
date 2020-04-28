<?php 
namespace common\api\carrierAPI;

use yii;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use common\helpers\SubmitGate;
use Jurosh\PDFMerge\PDFMerger;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\PDFMergeHelper;

class LB_DHLCarrierAPI extends BaseCarrierAPI
{
	static private $url = '';
	
	private $submitGate = null;
	public $clientId = null;    //登录人ID
	public $password = null;        //客户ID
	
	public function __construct(){
		//正式环境
 		self::$url = 'https://api.dhlecommerce.dhl.com';
		//测试环境
		//self::$url = 'https://sandbox.dhlecommerce.asia';
		//测试环境，压缩
		//self::$url = 'https://apitest2.dhlecommerce.asia';
		
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取TOKEN
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/03/01  			初始化
	 +----------------------------------------------------------
	 **/
	public function getToken($account)
	{
	    //检测token是否有效，当未失效，则直接返回
        $api_params = $account->api_params;
        if(!empty($api_params['token']) && !empty($api_params['last_time'])){
            $now = strtotime(date("Y-m-d H:i:s",time()));
            if($now < $api_params['last_time']){
                return($api_params['token']);
            }
        }
	    
	    //获取token
		$url = self::$url.'/rest/v1/OAuth/AccessToken?';	
		$url_params = 'clientId='.$this->clientId.'&password='.$this->password.'&returnFormat=json ';
		$response = $this->submitGate->mainGate($url, $url_params, 'curl', 'GET');
		
		if($response['error']){return false;}
		
		$last_time = strtotime(date("Y-m-d H:i:s",time()));
		$token_dhl = json_decode($response['data'],true);
		if(!empty($token_dhl['accessTokenResponse']['expires_in_seconds'])){
			if($token_dhl['accessTokenResponse']['expires_in_seconds'] > 3600 * 2){
				$last_time = $last_time + $token_dhl['accessTokenResponse']['expires_in_seconds'] - 3600 * 2;
			}
		}
		
		if(!empty($token_dhl['accessTokenResponse']['token'])){
		    //保存token信息
		    $api_params['token'] = $token_dhl['accessTokenResponse']['token'];
		    $api_params['last_time'] = $last_time;
		    $account->api_params = $api_params;
		    $account->save(false);
		    
		    return($token_dhl['accessTokenResponse']['token']);
		}
		else
		    return('');
	}	


	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/03/01  			初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data)
	{
		try{
		    //验证用户登录
			$user=\Yii::$app->user->identity;
			if(empty($user))
			    return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			//odOrder表内容
			$order = $data['order'];
			//表单提交的数据
			$form_data = $data['data'];

			//获取物流商信息、运输方式信息等
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$service_carrier_params = $service->carrier_params;   //参数键值对
			$account = $info['account'];
			
			//获取物流商 账号 的认证参数
			$api_params = $account->api_params;
			$this->clientId = $api_params['clientId'];
			$this->password = $api_params['password'];
			$clientPrefix = $api_params['clientPrefix'];
			$is_lb_enable = empty($api_params['is_lb_enable']) ? 1 : $api_params['is_lb_enable'];
			$description_type = empty($api_params['description_type']) ? '' : $api_params['description_type'];
			if(empty($clientPrefix)){
				return self::getResult(1, '', '客户前缀，不能为空');
			}
			
			if(empty($form_data['customer_number'])){
				return self::getResult(1, '', '客户参考号，不能为空');
			}
			//整理跟踪号
			$customer_number = strtoupper($clientPrefix.$form_data['customer_number']);
			//过滤特殊字符
			$customer_number = preg_replace("/[^a-zA-Z0-9]+/","", $customer_number);
			//判断是否只由数字、字母组成
			if(!preg_match("/^[a-z0-9]+$/i",$customer_number)){
				return self::getResult(1, '', '客户参考号只能由数字、字母组成');
			}
			if(strlen($customer_number) > 25){
				return self::getResult(1, '', '客户参考号不可大于20位');
			}
			
			//重复发货，添加不同的标识码
			$extra_id = isset($data['data']['extra_id']) ? $data['data']['extra_id'] : '';
			if(isset($data['data']['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}
			
			//对当前条件的验证，如果订单已存在，则报错
			$checkResult = CarrierAPIHelper::validate(0, 0, $order, $extra_id, $customer_number);

			//由于DHL请求信息中，要求参数不能为""，要为null值
			foreach ($info['senderAddressInfo'] as $lv1_key => $lv1_value) {
				foreach ($info['senderAddressInfo'][$lv1_key] as $lv2_key => $lv2value) {
    				if ($lv2value == "") {
    					$info['senderAddressInfo'][$lv1_key][$lv2_key] = null;
    				}
				}
			}
			
			//整合地址电话信息（由于很多地址、电话不统一，需要转换）
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 50,
							'consignee_address_line2_limit' => 50,
							'consignee_address_line3_limit' => 30,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			foreach ($addressAndPhone as $ad_ph_key => $ad_ph_value) {
				if ($ad_ph_value == "") {
					$addressAndPhone[$ad_ph_key] = null;
				}	
			}
			//自动拆分收货地址
			if(!empty($api_params['address_split']) && $api_params['address_split'] == 2){
				if(!empty($addressAndPhone['address_line1']) && strlen($addressAndPhone['address_line1']) > 50){
					$addressAndPhone['address_line2'] = substr($addressAndPhone['address_line1'], 50).$addressAndPhone['address_line2'];
					$addressAndPhone['address_line1'] = substr($addressAndPhone['address_line1'], 0, 50);
				}
				if(!empty($addressAndPhone['address_line2']) && strlen($addressAndPhone['address_line2']) > 50){
					$addressAndPhone['address_line3'] = substr($addressAndPhone['address_line2'], 50).$addressAndPhone['address_line3'];
					$addressAndPhone['address_line2'] = substr($addressAndPhone['address_line2'], 0, 50);
				}
			}
			
            //获取 DHL TOKEN
			$token_dhl = $this->getToken($account);
			if ($token_dhl == false || $token_dhl == '') {
				return self::getResult(1, '', '获取DHL token失败，请重试！');
			}
			
			$header_req = array(
				'messageType' => "LABEL", 
				'messageDateTime' => date("c"),
				'accessToken' => $token_dhl,
				'messageVersion' => "1.4",
			    'messageLanguage' => "en"
			);
			
			$pickupAddress = array(
				'companyName' =>  $info['senderAddressInfo']['pickupaddress']['company'], 
				'name' =>         $info['senderAddressInfo']['pickupaddress']['contact'], 
				'address1' =>     $info['senderAddressInfo']['pickupaddress']['street'], 
				'address2' =>     null, 
				'address3' =>     null, 
				'city' =>         $info['senderAddressInfo']['pickupaddress']['city'], 
				'state' =>        $info['senderAddressInfo']['pickupaddress']['province'], 
				'district' =>     $info['senderAddressInfo']['pickupaddress']['district'], 
				'country' =>      $info['senderAddressInfo']['pickupaddress']['country'], 
				'postCode' =>     $info['senderAddressInfo']['pickupaddress']['postcode'], 
				'phone' =>        empty($info['senderAddressInfo']['pickupaddress']['phone']) ? (empty($info['senderAddressInfo']['pickupaddress']['mobile']) ? null : $info['senderAddressInfo']['pickupaddress']['mobile']) : $info['senderAddressInfo']['pickupaddress']['phone'], 
				'email' =>        $info['senderAddressInfo']['pickupaddress']['email'], 
			);	
			$shipperAddress = array(
				'companyName' =>  $info['senderAddressInfo']['shippingfrom']['company_en'], 
				'name' =>         $info['senderAddressInfo']['shippingfrom']['contact_en'], 
				'address1' =>     $info['senderAddressInfo']['shippingfrom']['street_en'], 
				'address2' =>     empty($info['senderAddressInfo']['shippingfrom']['street_en2']) ? null : $info['senderAddressInfo']['shippingfrom']['street_en2'], 
				'address3' =>     null, 
				'city' =>         $info['senderAddressInfo']['shippingfrom']['city_en'], 
				'state' =>        $info['senderAddressInfo']['shippingfrom']['province_en'], 
				'district' =>     $info['senderAddressInfo']['shippingfrom']['district_en'], 
				'country' =>      $info['senderAddressInfo']['shippingfrom']['country'], 
				'postCode' =>     $info['senderAddressInfo']['shippingfrom']['postcode'], 
				'phone' =>        empty($info['senderAddressInfo']['shippingfrom']['phone']) ? (empty($info['senderAddressInfo']['shippingfrom']['mobile']) ? null : $info['senderAddressInfo']['shippingfrom']['mobile']) : $info['senderAddressInfo']['shippingfrom']['phone'], 
				'email' =>        $info['senderAddressInfo']['shippingfrom']['email'], 
			);
			
			$consigneeAddress = array(
					'companyName' =>  empty($order['consignee_company']) ? null : $order['consignee_company'],
					'name' =>         empty($order['consignee']) ? null : $order['consignee'],
					'address1' =>     $addressAndPhone['address_line1'], 
					'address2' =>     $addressAndPhone['address_line2'], 
					'address3' =>     $addressAndPhone['address_line3'], 
					'city' =>         empty($order['consignee_city']) ? null : $order['consignee_city'],
					'state' =>        empty($order['consignee_province']) ? null : $order['consignee_province'],
					'district' =>     empty($order['consignee_district']) ? null : $order['consignee_district'],
					'country' =>      empty($order['consignee_country_code']) ? null : $order['consignee_country_code'], 
					'postCode' =>     empty($order['consignee_postal_code']) ? null : $order['consignee_postal_code'],
					'phone' =>        empty($order['consignee_phone']) ? (empty($order['consignee_mobile']) ? null : $order['consignee_mobile']) : $order['consignee_phone'], 
					'email' =>        empty($order['consignee_email']) ? null : $order['consignee_email'],
					'idNumber' => null,
					'idType' => null,
			);
			
			$returnAddress = array(
					'companyName' =>  $info['senderAddressInfo']['returnaddress']['company'], 
					'name' =>         $info['senderAddressInfo']['returnaddress']['contact'], 
					'address1' =>     $info['senderAddressInfo']['returnaddress']['street'], 
					'address2' =>     null, 
					'address3' =>     null, 
					'city' =>         $info['senderAddressInfo']['returnaddress']['city'], 
					'state' =>        $info['senderAddressInfo']['returnaddress']['province'], 
					'district' =>     $info['senderAddressInfo']['returnaddress']['district'], 
					'country' =>      $info['senderAddressInfo']['returnaddress']['country'], 
					'postCode' =>     $info['senderAddressInfo']['returnaddress']['postcode'], 
					'phone' =>        empty($info['senderAddressInfo']['returnaddress']['phone']) ? (empty($info['senderAddressInfo']['returnaddress']['mobile']) ? null : $info['senderAddressInfo']['returnaddress']['mobile']) : $info['senderAddressInfo']['returnaddress']['phone'], 
					'email' =>        $info['senderAddressInfo']['returnaddress']['email'], 
			);
			
			$tovaltmp = 0.00;
			$descriptionExport = '';
			$shipmentContents = array();
			foreach ($order['items'] as $key => $vitem){
				$description = substr($vitem['product_name'], 0, 50);
				//整理商品描述
				if($description_type != ''){
					switch ($description_type){
						case 'product_name':
							$description = substr($vitem['product_name'], 0, 50);
							break;
						case 'sku':
							$description = $vitem['sku'];
							break;
						case 'sku_qty':
							$description = $vitem['sku'].' * '.$vitem['quantity'];
							break;
						case 'EName':
							$description = $form_data['EName'][$key];
							break;
						case 'EName_qty':
							$description = $form_data['EName'][$key].' * '.$vitem['quantity'];
							break;
					}
				}
				
				$contentIndicator = null;
				if($is_lb_enable == 2){
					$contentIndicator = $form_data['contentIndicator'][$key];
					if(empty($contentIndicator)){
						return self::getResult(1, '', '请选择内容提示！');
					}
					if($contentIndicator == 1){
						$contentIndicator = '00';
					}
					else if($contentIndicator == 2){
						$contentIndicator = '04';
					}
				}
				
				$shipmentContents[] = [
					'skuNumber' => $vitem['sku'],	
					'description' => $description,	
					'descriptionImport' => $form_data['EName'][$key],	
					'descriptionExport' => $form_data['Name'][$key],
					'itemValue' => (float)$form_data['DeclaredValue'][$key],
					'itemQuantity' => (int)$vitem['quantity'],	
					'grossWeight' => null,	
					//'net_weight' => "",	
					//'weight_uom' => "G",	
					'contentIndicator' => $contentIndicator,	
					'countryOfOrigin' => "CN",	
					'hsCode' => $form_data['Hscode'][$key] == '' ? null : $form_data['Hscode'][$key],
				];
				$tovaltmp = $tovaltmp + (float)$form_data['DeclaredValue'][$key];
				$descriptionExport = empty($descriptionExport) ? $form_data['Name'][$key] : $descriptionExport;
			}
			
			$shipmentItems =[ array(
				'consigneeAddress' => $consigneeAddress,
				'returnAddress' => $returnAddress,
				"shipmentID" => $customer_number,
				"deliveryConfirmationNo" => null,
			    'packageDesc' => empty($form_data['package_desc']) ? $descriptionExport : $form_data['package_desc'],
				"totalWeight"=> $form_data['total_weight']== '' ? 1 : (int)$form_data['total_weight'],
				"totalWeightUOM"=> "G",
				"dimensionUOM"=> "cm",
				"height"=> $form_data['height'] == '' ? 0.0 : (float)$form_data['height'],
				"length"=> $form_data['length'] == '' ? 0.0 : (float)$form_data['length'],
				"width"=> $form_data['width'] == '' ? 0.0 : (float)$form_data['width'],
				"customerReference1"=> null,
				"customerReference2"=> null,
// 				"productCode"=> empty($service_carrier_params['productCode']) ? 'PKD' : $service_carrier_params['productCode'],
		        "productCode"=> $service->shipping_method_code,// dzt20200417 DHL加了几个运输方式，允许输入
				"incoterm"=> (!empty($form_data['incoterm']) && $form_data['incoterm'] != '1') ? $form_data['incoterm'] : null,
				"contentIndicator"=> null,
				"codValue"=> null,
				"insuranceValue"=> null,
				"freightCharge"=> 0.0,
				"totalValue"=> $tovaltmp,
				"currency"=> $order['currency'],
			    "insuranceValue" => null,
			    "freightCharge" => null,
				"shipmentContents"=> $shipmentContents,
			)];
			
			$body_req = array(
					'customerAccountId' => null,
					'pickupAccountId' => $api_params['pickupAccountId'],
					'soldToAccountId' => $api_params['soldToAccountId'],
					'pickupDateTime' => date("c"),
					'pickupAddress' => $pickupAddress,
					'shipperAddress' => $shipperAddress,
			        'shipmentItems' => $shipmentItems,
			        'label' =>  array(
        				'pageSize' => "400x600",
        				'format' => "PDF",
        				'layout' => empty($service_carrier_params['layout']) ? "1x1" : $service_carrier_params['layout'],	
        			),
			);
			
			//=== header + body = request message
			$request_label['labelRequest'] = array(
				'hdr' => $header_req, 
				'bd' => $body_req,
			);
			
			$request_label = json_encode($request_label);
			//特殊参数转换为float
			$trans_col = 'height,length,width,deliveryConfirmationNo,productCode,dimensionUOM,totalValue';
			$trans_arr = explode(',', $trans_col);
			foreach ($trans_arr as $v){
			    $request_label = $this->accordingArrayToModifyJson($body_req['shipmentItems'][0][$v],$v,$request_label,".00");
			}
            foreach ($data['order']['items'] as $key=>$vitem){ 
            	$request_label = $this->accordingArrayToModifyJson($body_req['shipmentItems'][0]['shipmentContents'][$key]['itemValue'],"itemValue",$request_label,".00");
            }
            
            \Yii::info('LB_DHLCarrierAPI1 puid:'.$puid.'  order_id:'.$order['order_id'].'  '.$request_label, "carrier_api");
            //发送信息
			$post_head = array();
			$post_head[] = "Content-Type: application/json;charset=UTF-8";
			$post_head[] = "Expect:";//去掉默认值100-countinue
			$response = Helper_Curl::post(self::$url.'/rest/v2/Label',$request_label,$post_head);
			/*//压缩测试
    		$post_head[] = "Content-type: application/json";
    		$post_head[] = "Accept-Encoding: gzip,deflate";
    		$response = self::post(self::$url.'/rest/v2/Label',$request_label,$post_head);
    		*/
    		
    		//===处理发送异常，没有响应回来	
			if($response == false){throw new CarrierException(print_r($response));}
			
			$response_label = json_decode($response, true);
			\Yii::info('LB_DHLCarrierAPI2 puid:'.$puid.'  order_id:'.$order['order_id'].'  '.$response, "carrier_api");
			if ($response_label['labelResponse']['bd']['responseStatus']['message'] == 'SUCCESS') {
			//===因为该物流直接返回base64打印，所以需要直接保存到我们服务器做备用
				$printLabelPdfPathSave = CarrierAPIHelper::savePDF2(base64_decode($response_label['labelResponse']['bd']['labels'][0]['content']),$puid,$order['order_id'].$customer_number."_printLabel_".time(),'pdf');
		
				$printLabelPdfPath = '';
				if($printLabelPdfPathSave['error'] == 0){
					$printLabelPdfPath = $printLabelPdfPathSave['filePath'];
				}
				if(!empty($printLabelPdfPath)){
					$print_param = array();
					$print_param['carrier_code'] = $info['service']['carrier_code'];
					$print_param['api_class'] = 'LB_DHLCarrierAPI';
					$print_param['label_api_file_path'] = $printLabelPdfPath;
					$print_param['run_status'] = 4;
					
					try{
						CarrierApiHelper::saveCarrierUserLabelQueue($puid, $data['order']['order_id'], $customer_number, $print_param);
					}catch (\Exception $ex){

					}
				}
				
				//判断使用跟踪号的类型
				$deliveryConfirmationNo = '';
				if(!empty($service_carrier_params['tracking_number_type']) && $service_carrier_params['tracking_number_type'] == 2){
					if(!empty($response_label['labelResponse']['bd']['labels'][0]['deliveryConfirmationNo'])){
						$tracking_number = $response_label['labelResponse']['bd']['labels'][0]['deliveryConfirmationNo'];
						$deliveryConfirmationNo = $tracking_number;
					}
					else 
						$tracking_number = $response_label['labelResponse']['bd']['labels'][0]['shipmentID'];
				}
				else{
					$tracking_number = $response_label['labelResponse']['bd']['labels'][0]['shipmentID'];
				}
				
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
				$r = CarrierAPIHelper::orderSuccess($data['order'],$info['service'],$customer_number,OdOrder::CARRIER_WAITING_PRINT,
						$tracking_number,
						['PrintSign'=>$response_label['labelResponse']['bd']['labels'][0]['shipmentID'],
						 'deliveryConfirmationNo'=>$deliveryConfirmationNo,
						 'printLabelPdfPath'=>$printLabelPdfPath]);

				return  BaseCarrierAPI::getResult(0,$r,'操作成功!物流跟踪号:'.$tracking_number);
			}else{
			    $message = '';
			    if(!empty($response_label['labelResponse']['bd']['labels'])){
    			    foreach ($response_label['labelResponse']['bd']['labels'][0]['responseStatus']['messageDetails'] as $v){
    			        $message .= $message == '' ? $v['messageDetail'] : $v['messageDetail']."<br/>";
			        }   
			    }
			    else{
			    	if(is_array($response_label['labelResponse']['bd']['responseStatus']['messageDetails'])){
				        foreach ($response_label['labelResponse']['bd']['responseStatus']['messageDetails'] as $v){
				        	$message .= $message == '' ? $v['messageDetail'] : $v['messageDetail']."<br/>";
				        }
			    	}
			    	else{
			    		$message = $response_label['labelResponse']['bd']['responseStatus']['messageDetails'];
			    	}
			    }
				return self::getResult(1, '', $message);
				
			}

			return self::getResult(1, '', '操作异常');

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
		
	}
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/03/02                                           初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data)
	{		
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消已确定的物流单');
	}
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/03/03                                           初始化  
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data)
	{
		//DHL要求关掉这一步骤
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运');
		
	    try
	    {
	        //验证用户登录
	        $user=\Yii::$app->user->identity;
	        if(empty($user))
	        	return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
	        $puid = $user->getParentUid();
	        	
	        //odOrder表内容
	        $order = $data['order'];
	        
	        //对当前条件的验证 ，     订单不存在   则报错
	        $checkResult = CarrierAPIHelper::validate(0, 1, $order);
	        $shipped = $checkResult['data']['shipped'];
	        
	        if(!empty($shipped['return_no']['PrintSign'])){
	        	$shipmentID = $shipped['return_no']['PrintSign'];
	        }
	        else{
	        	return self::getResult(1, '', 'shipmentID丢失');
	        }
	        
	        //获取物流商信息、运输方式信息等
	        $info = CarrierAPIHelper::getAllInfo($order);
	        $account = $info['account'];
	        //获取物流商 账号 的认证参数
	        $api_params = $account->api_params;
	        $this->clientId = $api_params['clientId'];
	        $this->password = $api_params['password'];
	        
	        //获取 DHL TOKEN
	        $token_dhl = $this->getToken($account);
	        if ($token_dhl == false || $token_dhl == '') {
	        	return self::getResult(1, '', '获取DHL token失败，请重试！');
	        }
	        	
	        $header_req = array(
	        		'messageType' => "CLOSEOUT",
	        		'messageDateTime' => date("c"),
	        		'accessToken' => $token_dhl,
	        		'messageVersion' => "1.3",
	        		'messageLanguage' => "en"
	        );
	        	
	        $body_req = array(
	        		'customerAccountId' => null,
	        		'pickupAccountId' => $api_params['pickupAccountId'],
	        		'soldToAccountId' => $api_params['soldToAccountId'],
	        		'handoverID' => null,
	        		'generateHandover' => "Y",
	        		'handoverMethod' => 1,
	        		'shipmentItems' => [array(
	        				'shipmentID' => $shipmentID,
	        		)],
	        );
	        	
	        //=== header + body = request message
	        $request_label['closeOutRequest'] = array(
	        		'hdr' => $header_req,
	        		'bd' => $body_req,
	        );
	        
	        $request_label = json_encode($request_label);
	        
	        \Yii::info('LB_DHLCarrierAPI3 puid:'.$puid.'  order_id:'.$order->order_id.'  '.$request_label, "carrier_api");
	        //发送信息
	        $post_head = array();
	        $post_head[] = "Content-Type: application/json;charset=UTF-8";
	        $post_head[] = "Expect:";//去掉默认值100-countinue
	        $response = Helper_Curl::post(self::$url.'/rest/v2/Order/Shipment/CloseOut',$request_label,$post_head);
	        /*//压缩测试
	        $post_head[] = "Content-type: application/json";
	        $post_head[] = "Accept-Encoding: gzip,deflate";
	        $response = self::post(self::$url.'/rest/v2/Order/Shipment/CloseOut',$request_label,$post_head);*/
	        //===处理发送异常，没有响应回来
	        if($response == false){throw new CarrierException(print_r($response));}
	        
	        $response_label = json_decode($response, true);
	        \Yii::info('LB_DHLCarrierAPI4 puid:'.$puid.'  order_id:'.$order->order_id.'  '.$response, "carrier_api");
	        
	        if ($response_label['closeOutResponse']['bd']['responseStatus']['message'] == 'SUCCESS') {
	            $order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
	            $order->save();
	            return self::getResult(0, '', '订单交运成功！'.(!empty($shipped->tracking_number) ? '已生成运单号：'.$shipped->tracking_number : ''));
	        }else{
	            $message = '';
	            $messagedetails = $response_label['closeOutResponse']['bd']['responseStatus']['messageDetails'];
	            if(is_array($messagedetails)){
	                foreach ($response_label['closeOutResponse']['bd']['responseStatus']['messageDetails'] as $v){
	                	$message .= $message == '' ? $v['messageDetail'] : $v['messageDetail']."<br/>";
	                }
	            }
	            else{
	                $message = $messagedetails;
	            }
            	
	            return self::getResult(1, '', '交运失败：'.$message);
	    	}
	    }
	    catch( CarrierException $e)
	    {
	        return self::getResult(1, '', $e->msg());
	    }
	}

	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/03/02                                           初始化  
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data)
	{
		try
		{
			$user = \Yii::$app->user->identity;
			if( empty($user))
			    return ['error'=>1, 'data'=>'', 'msg'=>'用户登录信息缺失，请重新登录'];
			$puid = $user->getParentUid();
			
			$order = $data['order'];
			
			//对当前条件的验证 ，     订单不存在   则报错
			$checkResult = CarrierAPIHelper::validate(0, 1, $order);
			$shipped = $checkResult['data']['shipped'];
			
			if(!empty($shipped['return_no']['PrintSign'])){
				$shipmentID = $shipped['return_no']['PrintSign'];
			}
			else{
				return self::getResult(1, '', 'shipmentID丢失');
			}
				
			//获取物流商信息、运输方式信息等
	        $info = CarrierAPIHelper::getAllInfo($order);
	        $account = $info['account'];
	        //获取物流商 账号 的认证参数
	        $api_params = $account->api_params;
	        $this->clientId = $api_params['clientId'];
	        $this->password = $api_params['password'];
	        
	        //获取 DHL TOKEN
	        $token_dhl = $this->getToken($account);
	        if ($token_dhl == false || $token_dhl == '') {
	        	return self::getResult(1, '', '获取DHL token失败，请重试！');
	        }
	        	
	        $header_req = array(
	        		'messageType' => "LABELREPRINT",
	        		'messageDateTime' => date("c"),
	        		'accessToken' => $token_dhl,
	        		'messageVersion' => "1.1",
	        		'messageLanguage' => "en"
	        );
	        	
	        $body_req = array(
	        		'pickupAccountId' => $api_params['pickupAccountId'],
	        		'soldToAccountId' => $api_params['soldToAccountId'],
	        		'shipmentItems' => [array(
	        				'shipmentID' => $shipmentID,
	        		)],
	        );
	        	
	        //=== header + body = request message
	        $request_label['labelReprintRequest'] = array(
	        		'hdr' => $header_req,
	        		'bd' => $body_req,
	        );
	        
	        $request_label = json_encode($request_label);
	        
	        \Yii::info('LB_DHLCarrierAPI7 puid:'.$puid.'  order_id:'.$order->order_id.'  '.$request_label, "carrier_api");
	        //发送信息
	        $post_head = array();
	        $post_head[] = "Content-Type: application/json;charset=UTF-8";
	        $post_head[] = "Expect:";//去掉默认值100-countinue
	        $response = Helper_Curl::post(self::$url.'/rest/v2/Label/Reprint',$request_label,$post_head);
	        //===处理发送异常，没有响应回来
	        if($response == false){throw new CarrierException(print_r($response));}
	        
	        $response_label = json_decode($response, true);
	        \Yii::info('LB_DHLCarrierAPI8 puid:'.$puid.'  order_id:'.$order->order_id.'  '.$response, "carrier_api");
	        
	        if ($response_label['labelReprintResponse']['bd']['responseStatus']['message'] == 'SUCCESS') {
	            $printLabelPdfPathSave = CarrierAPIHelper::savePDF2(base64_decode($response_label['labelReprintResponse']['bd']['shipmentItems'][0]['content']),$puid,$order->order_id.$order->customer_number."_printLabel_".time(),'pdf');
		
				$printLabelPdfPath = '';
				if($printLabelPdfPathSave['error'] == 0){
					$printLabelPdfPath = $printLabelPdfPathSave['filePath'];
				}
				if(!empty($printLabelPdfPath)){
					$return_no = $shipped->return_no;
					$return_no['printLabelPdfPath'] = $printLabelPdfPath;
					$shipped->return_no = $return_no;
					$shipped->save();
				
					$print_param = array();
					$print_param['carrier_code'] = $info['service']['carrier_code'];
					$print_param['api_class'] = 'LB_DHLCarrierAPI';
					$print_param['label_api_file_path'] = $printLabelPdfPath;
					$print_param['run_status'] = 4;
					
					try{
						CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
					}catch (\Exception $ex){

					}
				}
	            
	            return ['error'=>0, 'data'=>'', 'msg'=>'获取面单成功！'];
	            
	        }else{
	            $message = '';
	            $messagedetails = $response_label['labelReprintResponse']['bd']['responseStatus']['messageDetails'];
	            if(is_array($messagedetails)){
	                foreach ($response_label['labelReprintResponse']['bd']['responseStatus']['messageDetails'] as $v){
	                	$message .= $message == '' ? $v['messageDetail'] : $v['messageDetail']."<br/>";
	                }
	            }
	            else{
	                $message = $messagedetails;
	            }
            	
	            return self::getResult(1, '', '获取面单失败：'.$message);
	    	}
		}
		catch( CarrierException $e)
		{
		    return ['error'=>1, 'data'=>'', 'msg'=>$e->msg()];
		}
	}

	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/03/03                                         初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data)
	{
		try {
		    $order = current($data);reset($data);
		    $order = $order['order'];
		    
		    //验证用户登录
		    $user=\Yii::$app->user->identity;
		    if(empty($user))
		    	return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
		    $puid = $user->getParentUid();
		    
		    $info = CarrierAPIHelper::getAllInfo($order);
		    $account = $info['account'];
		    
		    $pdf = new PDFMerger();
		    $basepath = Yii::getAlias('@webroot');
		    $tmpPath = array();
		    
		    foreach ($data as $k=>$v) {
		    	$oneOrder = $v['order'];
		    		
		    	$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
		    	$shipped = $checkResult['data']['shipped'];
		    	$tmpPath[] = $shipped->return_no['printLabelPdfPath'];
		    		
		    	/*$oneOrder->is_print_carrier = 1;
		    	$oneOrder->print_carrier_operator = $puid;
		    	$oneOrder->printtime = time();
		    	$oneOrder->save();*/
		    }
		    
		    if(empty($tmpPath)){
		    	return self::getResult(1,'','查询打印路劲失败');
		    }
		    
		    if(count($tmpPath) == 1){
		    	return self::getResult(0,['pdfUrl'=>Yii::$app->request->hostinfo.$tmpPath[0]],'连接已生成,请点击并打印');
		    }else{
		    	foreach ($tmpPath as $tmp_key => $tmp_val){
		    		$tmpPath[$tmp_key] = $basepath.$tmp_val;
		    	}
		    
		    	$pdfUrl = CarrierAPIHelper::savePDF('1',$puid,$account->carrier_code.'_'.$order['customer_number'].'_summerge_', 0);
		    	$pdfmergeResult = PDFMergeHelper::PDFMerge($pdfUrl['filePath'] , $tmpPath);
		    		
		    	if($pdfmergeResult['success'] == true){
		    		return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
		    	}else{
		    		return self::getResult(1,'',$pdfmergeResult['message']);
		    	}
		    }
		    
		    return self::getResult(1,'',"打印异常");
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}	
	/**
	 +----------------------------------------------------------
	 * 根据数组修改json文件
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2017/03/02                                           初始化  			
	 +----------------------------------------------------------
	 **/
	private function accordingArrayToModifyJson($array_val,$array_key,$json_src,$adjust_val)
	{
		$ret_json = $json_src;
		//定位到唯一的key（并不完善）
		$temp1 = strstr($json_src,"\"".$array_key."\"");
		$temp2 = strchr($json_src,"\"".$array_key."\"");
		if (strcmp($temp1,$temp2) == 0) {
			//判断是否存在例如 5 == 5.00，前提是为float或者 double
			if ((ceil($array_val) == $array_val) && (is_float($array_val) || is_double($array_val))) {
				if($array_key == 'itemValue'){
					//预防重复出现值
					$src_val = "\"".$array_key."\":".$array_val.",";
					$des_val = rtrim($src_val,',').$adjust_val.",";
				}
				else{
					$src_val = "\"".$array_key."\":".$array_val;
					$des_val = $src_val.$adjust_val;
				}
				
				$ret_json = str_replace($src_val,$des_val,$ret_json);
			}				
		}
		return $ret_json;
	}
	
	/**
	 * 用于验证物流账号信息是否真实
	 * $data 用于记录所需要的认证信息
	 *
	 * return array(is_support,error,msg)
	 * 			is_support:表示该货代是否支持账号验证  1表示支持验证，0表示不支持验证
	 * 			error:表示验证是否成功	1表示失败，0表示验证成功
	 * 			msg:成功或错误详细信息
	 */
	public function getVerifyCarrierAccountInformation($data)
	{
		$result = array('is_support'=>1,'error'=>1);
	
		try
		{
		    //获取token
		    $url = self::$url.'/rest/v1/OAuth/AccessToken?';
		    $url_params = 'clientId='.$data['clientId'].'&password='.$data['password'].'&returnFormat=json ';
		    $response = $this->submitGate->mainGate($url, $url_params, 'curl', 'GET');
		    
		    if($response['error']){
		        return $result;
		    }
		    else {
    		    $token_dhl = json_decode($response['data'],true);
    		    if(!empty($token_dhl['accessTokenResponse']['token'])){
    		    	$result['error'] = 0;
    		    }
		    }
		}
		catch(CarrierException $e){}
	
		return $result;
	}
	
	//重写post请求
	public function post($url,$requestBody=null,$requestHeader=null,$justInit=false,$responseSaveToFileName=null){
		$connecttimeout=60;
		$timeout=500;
		$last_post_info=null;
		$last_error =null;
		
		$connection = curl_init();
	
		curl_setopt($connection, CURLOPT_URL,$url);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		if (!is_null($requestHeader)){
			curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
		}
		curl_setopt($connection, CURLOPT_POST, 1);
		curl_setopt($connection, CURLOPT_POSTFIELDS, $requestBody);
		if (!is_null($responseSaveToFileName)){
			$fp=fopen($responseSaveToFileName,'w');
			curl_setopt($connection, CURLOPT_FILE, $fp);
		}else {
			curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		}
		curl_setopt($connection, CURLOPT_CONNECTTIMEOUT,$connecttimeout);
		curl_setopt($connection, CURLOPT_TIMEOUT,$timeout);
		curl_setopt($connection, CURLOPT_ENCODING, "gzip"); //转换压缩内容
		if ($justInit){
			return $connection;
		}
	
		$response = curl_exec($connection);
		
		$last_post_info=curl_getinfo($connection);
		$error=curl_error($connection);
		curl_close($connection);
		if (!is_null($responseSaveToFileName)){
			fclose($fp);
		}
		if ($error){
			//throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url.'DATA:'.$requestBody);
			$e = 'curl_error:'.(print_r($error, true)).'URL:'.$url.'DATA:'.$requestBody;
			\Yii::error($e, "file");
			return false;
		}
		return $response;
	}
}

 ?>