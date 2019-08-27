<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-3-23
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use common\helpers\Helper_Array;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use common\helpers\SubmitGate;

/**
 +------------------------------------------------------------------------------
 * 4px接口业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/4pxCarrierAPI
 * @subpackage  Exception
 * @author		qfl 
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_4PXCarrierAPI extends BaseCarrierAPI
{
	public static $soapClient=null;
	public $wsdl = null;
	public $wsdltools = null;
	public static $authtoken=null;
	private static $returnArr = array();
	private static $isMulArray = false;//输出结果以xml对象表示，使用倒更方便
	private $submitGate = null;	// SoapClient实例
	
	//某些服务除了列出的国家，其他都不能上传超过2个产品
	public static $canMoreThanTwoItemsSpecialProductCode = [
		'A6'=>['AU','GB','UK','US','DE','AL','AT','BE','BA','BG','HR','CZ','EE',/*'FR',*/'GR','IS','LI','LT','ES','SE','UA','VA','AD','BY','CY','GI','HU','LU','NO','RO','SK','SJ','DK','FI','LV','MC','MD','SM','SI','IT','NL','IE','MK','PL','PT','CH','MT','ME','RS',],
		'A7'=>['AU','GB','UK','US','DE','AL','AT','BE','BA','BG','HR','CZ','EE',/*'FR',*/'GR','IS','LI','LT','ES','SE','UA','VA','AD','BY','CY','GI','HU','LU','NO','RO','SK','SJ','DK','FI','LV','MC','MD','SM','SI','IT','NL','IE','MK','PL','PT','CH','MT','ME','RS',],
		'DS'=>['AU','GB','UK','US','DE','AL','AT','BE','BA','BG','HR','CZ','EE',/*'FR',*/'GR','IS','LI','LT','ES','SE','UA','VA','AD','BY','CY','GI','HU','LU','NO','RO','SK','SJ','DK','FI','LV','MC','MD','SM','SI','IT','NL','IE','MK','PL','PT','CH','MT','ME','RS',],
		'ED'=>['AU','GB','UK','US','DE','AL','AT','BE','BA','BG','HR','CZ','EE',/*'FR',*/'GR','IS','LI','LT','ES','SE','UA','VA','AD','BY','CY','GI','HU','LU','NO','RO','SK','SJ','DK','FI','LV','MC','MD','SM','SI','IT','NL','IE','MK','PL','PT','CH','MT','ME','RS',],
	
	];
	
	/*
	 * 检测一组运输服务代码 和 到达国CODE，看看是否支持上传超过2个商品
	 * @params	string		$productCode
	 * @params	string(2)	$countryCode
	 * @return	boolean		true:允许超过两个 , false:不允许
	 */
	public static function checkProductCodeCanMoreThanTwoItems($productCode,$countryCode){
		$productCode = strtoupper($productCode);
		$countryCode = strtoupper($countryCode);
		
		$SpecialProductCode = self::$canMoreThanTwoItemsSpecialProductCode;
		if( !in_array($productCode, array_keys($SpecialProductCode) ) )
			return true;
		else{
			if( !empty($SpecialProductCode[$productCode]) ){
				if(in_array($countryCode, $SpecialProductCode[$productCode]))
					return true;
				else 
					return false;
			}else 
				return true;
		}
	}
	
	public function __construct(){
		$a = func_get_args();
		$i = func_num_args();
		
		$this->submitGate = new SubmitGate();
		
		if (method_exists($this,$f='__construct'.$i)) {
			
			call_user_func_array(array($this,$f),$a);
			
		}
	}
	public function __construct1($env=true)
	{
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			//订单操作
			$this->wsdl='http://api.4px.com/OrderOnlineService.dll?wsdl';
			//订单工具
			$this->wsdltools='http://api.4px.com/OrderOnlineToolService.dll?wsdl';
		}else{
			/* 后台登陆的地址是apisandbox.4pxtech.com:8094
			账号DJSH_LICC 密码123456
			token:CD29AD1E6703C0DB57271CA42B87A7D9 */
			//订单操作
			$this->wsdl='http://apisandbox.4pxtech.com:8090/OrderOnline/ws/OrderOnlineService.dll?wsdl';//测试接口
			//订单工具
			$this->wsdltools='http://apisandbox.4pxtech.com:8090/OrderOnlineTool/ws/OrderOnlineToolService.dll?wsdl';
			self::$authtoken='CD29AD1E6703C0DB57271CA42B87A7D9';
		}
		if(is_null(self::$soapClient)||!is_object(self::$soapClient)){
			try {
				self::$soapClient=new \SoapClient($this->wsdl,array(true));
			}catch (Exception $e){
				return self::getResult(1,'','网络连接故障'.$e->getMessage());
			}
		}
	}
	
	public function __construct2($env=true,$env2=true)
	{
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			//订单操作
			$this->wsdl='http://api.4px.com/OrderOnlineService.dll?wsdl';
			//订单工具
			$this->wsdltools='http://api.4px.com/OrderOnlineToolService.dll?wsdl';
		}else{
			//订单操作
			$this->wsdl='http://apisandbox.4pxtech.com:8090/OrderOnline/ws/OrderOnlineService.dll?wsdl';//测试接口
			//订单工具
			$this->wsdltools='http://apisandbox.4pxtech.com:8090/OrderOnlineTool/ws/OrderOnlineToolService.dll?wsdl';
			self::$authtoken='CD29AD1E6703C0DB57271CA42B87A7D9';//CD29AD1E6703C0DB57271CA42B87A7D9
		}
		if(is_null(self::$soapClient)||!is_object(self::$soapClient)){
			try {
				self::$soapClient=new \SoapClient($this->wsdltools,array(true));
			}catch (Exception $e){
				return self::getResult(1,'','网络连接故障'.$e->getMessage());
			}
		}
	}

	public function getOrderNO($data){
		try{
			//订单对象
			$order = $data['order'];
			
			//重复发货 添加不同的标识码
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
			 
			if(isset($data['data']['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}
			
			//表单提交的数据
			$data = $data['data'];

			//对当前条件的验证，校验是否登录，校验是否已上传过
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
			$puid = $checkResult['data']['puid'];

			//获取到所需要使用的数据（运输服务配置信息，物流商配置信息）
			$info = CarrierAPIHelper::getAllInfo($order);
			$s = $info['service'];
			$service_params = $s->carrier_params;
			$account = $info['account'];
			
			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			
			//获取到帐号中的认证参数
			$account_api_params = $account->api_params;
			//获取到账户中的地址信息
			$account_address = $info['senderAddressInfo'];
			
			//将用户token记录下来
			self::$authtoken=$account_api_params['AuthToken'];
			/*
			//4PX的客户参考号需要根据用户去设置生成模式
			$customerRule = $account_api_params['customerRule'];
			if(empty($customerRule)){
				//重复发货标识码
// 				$extra_id = isset($data['extra_id'])?$data['extra_id']:'';
// 				$customer_number = $data['data']['customer_number'];
			}else{
				$customer_number = 'XLB'.rand(100000,999999).(int)$order->order_id;
			}
			*/
			if(strlen($customer_number)>20)
				$customer_number = substr($customer_number, 0, 20);
			$consigneeStreet='';
			if(!empty($order->consignee_address_line1))
				$consigneeStreet = $order->consignee_address_line1;
			if(!empty($order->consignee_address_line2))
				$consigneeStreet .=' '. $order->consignee_address_line2;
			if(!empty($order->consignee_address_line3))
				$consigneeStreet .=' '. $order->consignee_address_line3;
			if(!empty($order->consignee_district))
				$consigneeStreet .=' '. $order->consignee_district;
			if(!empty($order->consignee_county))
				$consigneeStreet .=' '. $order->consignee_county;
			
			$consigneeTelephone = !empty($order->consignee_mobile)?$order->consignee_mobile:'';
			if(empty($consigneeTelephone))
				$consigneeTelephone = !empty($order->consignee_phone)?$order->consignee_phone:'';
			
			$carrierAddressAndPhoneParmas = array(
					'consignee_phone_limit' => 30,//	电话的长度限制	这个只能看接口文档或者问对应的技术，没有长度限制请填10000
					'address' => Array(
							'consignee_address_line1_limit' => 120,
					),
					'consignee_district' => 1,//	是否将收件人区也填入地址信息里面
					'consignee_county' => 1,	//是否将收货人镇也填入地址信息里面
					'consignee_company' => 0,	//是否将收货公司也填入地址信息里面
			);
			$carrierAddressAndPhoneInfo = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $carrierAddressAndPhoneParmas);
			if(!empty($carrierAddressAndPhoneInfo['address_line1']))
				$consigneeStreet = $carrierAddressAndPhoneInfo['address_line1'];
			//4px格式限制：正确格式:以01、02、03、04、06、07开头的10位数字
			/*
			if(!empty($carrierAddressAndPhoneInfo['phone1']))
				$consigneeTelephone = $carrierAddressAndPhoneInfo['phone1'];
			*/
			//4px接口限制 
			//if(strlen($consigneeStreet)>120){
			//	return self::getResult(1,'','收件人地址不能超多120');
			//}
			
			$tmp_shipperAddress = (!empty($account_address['shippingfrom']['province_en'])) ? $account_address['shippingfrom']['province_en'].' '.$account_address['shippingfrom']['city_en'].' '.$account_address['shippingfrom']['district_en'].' '.$account_address['shippingfrom']['street_en'] : $account_address['shippingfrom']['province'].' '.$account_address['shippingfrom']['city'].' '.$account_address['shippingfrom']['district'].' '.$account_address['shippingfrom']['street'];

			//当省份为空，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if($tmpConsigneeProvince == ''){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			$hasBattery  = 'N';//是否带电池
			$postdata = [
					"buyerId" => $order->source_buyer_user_id,//买家ID
					"cargoCode" => $service_params['CargoCode'],//货物类型(默认：P)，参照货物类型表
					"consigneeCompanyName" => $order->consignee_company,//收件人公司名称
					"consigneeEmail" => (strlen($order->consignee_email)<=50)?$order->consignee_email:'',//收件人Email
					"consigneeName" => $order->consignee,//收件人公司名称姓名【***】
					"street" =>$consigneeStreet,//street
					"city" => $order->consignee_city,//city
					"stateOrProvince" => $tmpConsigneeProvince,//StateOrProvince
					"consigneePostCode" => $order->consignee_postal_code,//收件人邮编
					"consigneeTelephone" => $consigneeTelephone,//收件人电话号码
					"destinationCountryCode" =>  $order->consignee_country_code=='UK'?'GB':$order->consignee_country_code,//目的国家二字代码，参照国家代码表
					"initialCountryCode" => $account_address['shippingfrom']['country'],//起运国家二字代码，参照国家代码表【***】
					"orderNo" => $customer_number,//客户订单号码，由客户自己定义【***】
					"note" => $data['orderNote'],//订单备注信息
					"paymentCode" => $service_params['PaymentCode'],//付款类型(默认：P)，参照付款类型表
					"productCode" => $s->shipping_method_code,//产品代码，指DHL、新加坡小包挂号、联邮通挂号等，参照产品代码表 【***】
					"returnSign" => $service_params['ReturnSign'],//小包退件标识 Y: 发件人要求退回 N: 无须退回(默认)
					"shipperAddress" =>$tmp_shipperAddress ,//发件人地址
					"shipperCompanyName" => empty($account_address['shippingfrom']['company_en']) ? $account_address['shippingfrom']['company'] : $account_address['shippingfrom']['company_en'],//发件人公司名称
					"shipperName" => empty($account_address['shippingfrom']['contact_en']) ? $account_address['shippingfrom']['contact'] : $account_address['shippingfrom']['contact_en'],//发件人姓名
					"shipperPostCode" => $account_address['shippingfrom']['postcode'],//发件人邮编
					"shipperTelephone" => $account_address['shippingfrom']['phone'],//发件人电话号码
					"shipperFax" => $account_address['shippingfrom']['fax'],//发件人传真
					'mctCode' => empty($service_params['mctCode'])?'1':$service_params['mctCode'],
			];
			$product=array();
			$pieces = 0;
			foreach ($data['DeclaredValue'] as $k=>$v){
				$product[$k]=[
					"declareNote" =>$data['DeclareNote'][$k],//配货备注
					"declarePieces" =>$data['DeclarePieces'][$k],//件数(默认: 1)
					"declareUnitCode" =>'PCE',//申报单位类型代码(默认:  PCE)，参照申报单位类型代码表
					"eName" =>$data['EName'][$k],//海关申报英文品名
					"cName" =>$data['CName'][$k],//海关申报中文品名
					"name" =>$data['name'][$k],//中文品名配货名
					"unitPrice" =>$data['DeclaredValue'][$k],//单价 0 < Amount <= [10,2]【***】
					"hsCode" =>empty($data['hsCode'][$k])?'':$data['hsCode'][$k],//报关码
					"itemUrl" =>empty($data['itemUrl'][$k])?'':$data['itemUrl'][$k],
				];
				$pieces+=$data['DeclarePieces'][$k];
				
				if(!empty($data['hasBattery'][$k]) && $data['hasBattery'][$k]=='Y')
					$hasBattery='Y';
			}
			$postdata['declareInvoice']=$product;
			
			if(isset($data['total_weight_4px'])){
				$postdata['customerWeight']=empty($data['total_weight_4px'])?'0':round($data['total_weight_4px']/1000,2);
			}else{
				$postdata['customerWeight']=empty($data['total_weight'])?'':round($data['total_weight']/1000,2);
			}
			
			$postdata['pieces']=$pieces;
			$postdata['hasBattery']=$hasBattery;
			//如果用户设置了保险 则上传保险项
			if(isset($data['insurValue'])&&$data['insurValue']!=''){
				$postdata['insurType'] = $service_params['InsurType'];
				$postdata['insurValue'] = $data['insurValue'];
			}
			// echo "<pre>";
			// print_r($postdata);
			// echo '</pre>';die;
			/*==================================================================================*/
			\Yii::info('lb_4px,puid:'.$puid.',order_id:'.$order->order_id.' '.json_encode($postdata),"carrier_api");
				
			$soap=new self(true);
			//$response=$soap->createOrderService($postdata);
			$response=$soap->createAndPreAlertOrderServiceNew($postdata);
			
			\Yii::info('lb_4px,puid:'.$puid.',order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
			
			//用什么号码作为跟踪号
			$tmp_is_use_mailno = empty($s['carrier_params']['is_use_mailno']) ? '0' : $s['carrier_params']['is_use_mailno'];
			
			if($response['ack'] == 'Failure'){
				//组织错误信息
				$error = '错误代码：'.$response['code']."<br>".'错误原因：'.$response['cnMessage']."<br>";
				if (isset($response['cnAction'])){
					$error.= '解决方案：'.$response['cnAction'];
				}
				
				return  BaseCarrierAPI::getResult(1,'', $error);
			}else if($response['ack'] == 'Success'){
				try{
					$print_param = array();
					$print_param['carrier_code'] = $s->carrier_code;
					$print_param['api_class'] = 'LB_4PXCarrierAPI';
					$print_param['AuthToken'] = self::$authtoken;
					$print_param['tracking_number'] = $response['trackingNumber'];
					$print_param['carrier_params'] = $s->carrier_params;
					
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $response['referenceNumber'], $print_param);
				}catch (\Exception $ex){
				}
				
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				$r = CarrierAPIHelper::orderSuccess($order,$s,$response['referenceNumber'],OdOrder::CARRIER_WAITING_PRINT,
						(empty($tmp_is_use_mailno) ? $response['trackingNumber'] : $response['fpxOrderNo']),
						['fpxOrderNo'=>(isset($response['fpxOrderNo']) ? $response['fpxOrderNo'] : ''),
						 'tracking_number'=>$response['trackingNumber']]);
				return  BaseCarrierAPI::getResult(0,$r, "操作成功!客户单号为：".$response['referenceNumber'],OdOrder::CARRIER_WAITING_PRINT,$response['trackingNumber']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
	public function getVerifyCarrierAccountInformation($data){
		$result = array('is_support'=>0,'error'=>0,'msg'=>'');
		return $result;//授权时没有要输入customerId，不能组成有效的验证组合
		try{
			$api_params['customerId'] = $data['customerNumber'];
			$api_params['token'] = $data['AuthToken'];
	
			$params = '?customerId='.$api_params['customerId'].'&token='.$api_params['token'].'&language=en_US';
			//拼接url
			$url ='http://openapi.4px.com/api/service/woms/order/getOrderCarrier'.$params;
			//echo "<br>".$url;

			$header=array();
			$header[]='Content-Type:application/json;charset=utf-8';
			$request['warehouseCode'] = 'UKLH';
			$response = Helper_Curl::post($url,json_encode($request),$header);
			//var_dump($response);
			if(stripos($response,'<errorCode>0055</errorCode>')){
				$result['error'] = 1;
				$result['msg'] = '验证失败!';
			}
		}catch(CarrierException $e){
			$result['error'] = 1;
			$result['msg'] = $e->msg();
		}
	
		return $result;
	}
	
	//确认订单
	public function doDispatch($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			//订单对象
			$order = $data['order'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$a = $info['account'];
			$service = $info['service'];// 运输服务相关信息

			//获取到帐号中的认证参数
			$api_params = $a->api_params;
			//将用户token记录下来
			self::$authtoken=$api_params['AuthToken'];

			$soap=new self(true);
			$response=$soap->preAlertOrderService([$order->customer_number]);
			//如果是错误信息
			if($response['ack'] == 'Failure'){
				return BaseCarrierAPI::getResult(1, '', '结果：'.$response['cnMessage']);
			}else if($response['ack'] == 'Success'){
				//保存跟踪号
				$shipped->tracking_number = $response['trackingNumber'];
				$shipped->save();
				$order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
				$order->save();
				
// 				if(!empty($shipped->tracking_number)){
// 					$print_param = array();
// 					$print_param['carrier_code'] = $service->carrier_code;
// 					$print_param['api_class'] = 'LB_4PXCarrierAPI';
// 					$print_param['AuthToken'] = self::$authtoken;
// 					$print_param['tracking_number'] = $shipped->tracking_number;
// 					$print_param['carrier_params'] = $service->carrier_params;
					
// 					try{
// 						CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
// 					}catch (\Exception $ex){
// 					}
// 				}
				
				return BaseCarrierAPI::getResult(0, '', '订单交运成功！已生成服务商单号：'.$response['trackingNumber']);	
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//获取跟踪号
	public function getTrackingNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$a = $info['account'];
			$service = $info['service'];// 运输服务相关信息
			
			//用什么号码作为跟踪号
			$tmp_is_use_mailno = empty($service['carrier_params']['is_use_mailno']) ? '0' : $service['carrier_params']['is_use_mailno'];
			
			if(empty($tmp_is_use_mailno)){
				//获取到帐号中的认证参数
				$api_params = $a->api_params;
				//将用户token记录下来
				self::$authtoken=$api_params['AuthToken'];
				
				$soap=new self(true,true);
				$response=$soap->findTrackingNumberService([$order->customer_number]);
					
				// 			\Yii::info(print_r($response,1), "carrier_api");
				\Yii::info('lb_4px getTrackingNO,puid:'.$puid.',order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
					
				//如果是错误信息
				if($response['ack'] == 'Failure'){
					return BaseCarrierAPI::getResult(1, '', '获取失败,'.$response['cnMessage']);
				}else if($response['ack'] == 'Success'){
					$is_new = false;
				
					if($shipped->tracking_number != $response['trackingNumber']){
						$is_new = true;
							
						$tmp_return_no = $shipped->return_no;
						if(isset($tmp_return_no['getTrackCount'])){
							$tmp_return_no['getTrackCount']++;
						}else{
							$tmp_return_no['getTrackCount'] = 1;
						}
							
						$old_track_no = $shipped->tracking_number;
							
						$shipped->return_no = $tmp_return_no;
							
						\eagle\modules\util\helpers\OperationLogHelper::log('order',$order->order_id,'获取跟踪号', '递四方', '旧的跟踪号:'.$shipped->tracking_number.'.新的跟踪号:'.$response['trackingNumber']);
					}
				
					$shipped->tracking_number = $response['trackingNumber'];
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
				
					if(!empty($shipped->tracking_number)){
						$print_param = array();
						$print_param['carrier_code'] = $service->carrier_code;
						$print_param['api_class'] = 'LB_4PXCarrierAPI';
						$print_param['AuthToken'] = self::$authtoken;
						$print_param['tracking_number'] = $shipped->tracking_number;
						$print_param['carrier_params'] = $service->carrier_params;
				
						try{
							CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
						}catch (\Exception $ex){
						}
					}
				
					return BaseCarrierAPI::getResult(0, '', '获取物流号成功！物流号：'.(($is_new == true) ? '旧的跟踪号:'.$old_track_no.'.新的跟踪号:'.$response['trackingNumber'] : $response['trackingNumber']));
				}
			}else{
				$tmp_return_no = $shipped->return_no;
				
				if(isset($tmp_return_no['fpxOrderNo'])){
					$shipped->tracking_number = $tmp_return_no['fpxOrderNo'];
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					
					return BaseCarrierAPI::getResult(0, '', '获取物流号成功！物流号：'.$order->tracking_number);
				}else{
					return self::getResult(1,'', '该数据是历史产生的，只有新上传的订单才可以使用该方式获取4px订单号');
				}
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//打印物流单
	public function doPrint($data){
// 		return BaseCarrierAPI::getResult(1, '', '物流接口不支持打印物流单');
		try {
			$order = current($data);reset($data);
			$getAccountInfoOrder = $order['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,1,$getAccountInfoOrder);
			$shipped = $checkResult['data']['shipped'];
			$puid = $checkResult['data']['puid'];
				
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($getAccountInfoOrder);
			$account = $info['account'];// object SysCarrierAccount
			$service = $info['service'];// object SysShippingService
		
			$account_api_params = $account->api_params;//获取到帐号中的认证参数
			$normalparams = $service->carrier_params;
		
			self::$authtoken = $account_api_params['AuthToken'];
		
			$param = array();
			
			$tracking_code_arr = array();
			
			foreach ($data as $v) {
				$oneOrder = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
				$shipped = $checkResult['data']['shipped'];
		
				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}
				
				$tmp_tracking_number = $shipped->tracking_number;
				if(!empty($shipped->return_no['tracking_number'])){
					$tmp_tracking_number = $shipped->return_no['tracking_number'];
				}
				
				$tracking_code_arr[] = $tmp_tracking_number;
			}
			
			//取得打印尺寸

			$tmpxml = '<labelFormat>pdf</labelFormat>';
			$tmpxml .= '<labelType>'.(empty($service['carrier_params']['labelType']) ? 1 : $service['carrier_params']['labelType']).'</labelType>';
			$tmpxml .= '<labelSize>'.(empty($service['carrier_params']['labelSize']) ? 'label_80x90' : $service['carrier_params']['labelSize']).'</labelSize>';
			
			if((!empty($service['carrier_params']['isPrintTime'])) || (!empty($service['carrier_params']['isPrintBuyerId'])) || (!empty($service['carrier_params']['isPrintPeihuo']))){
				$tmplabel = '';
				$tmplabel.='<isPrintTime>'.(empty($service['carrier_params']['isPrintTime']) ? 'N' : $service['carrier_params']['isPrintTime']).'</isPrintTime>';
				$tmplabel.='<isPrintBuyerId>'.(empty($service['carrier_params']['isPrintBuyerId']) ? 'N' : $service['carrier_params']['isPrintBuyerId']).'</isPrintBuyerId>';
				$tmplabel.='<isPrintPeihuo>'.(empty($service['carrier_params']['isPrintPeihuo']) ? 'N' : $service['carrier_params']['isPrintPeihuo']).'</isPrintPeihuo>';
				
				$tmpxml .= '<label>'.$tmplabel.'</label>';
			}
			
			if((!empty($service['carrier_params']['isPrintCustomerWeight']))){
				$tmpxml .= '<declarationLabel><isPrintCustomerWeight>'.(empty($service['carrier_params']['isPrintCustomerWeight']) ? 'N' : $service['carrier_params']['isPrintCustomerWeight']).'</isPrintCustomerWeight></declarationLabel>';
			}
			
			if((!empty($service['carrier_params']['isPrintPeiHuoBarcode']))){
				$tmpxml .= '<peihuoLabel><isPrintPeiHuoBarcode>'.(empty($service['carrier_params']['isPrintPeiHuoBarcode']) ? 'N' : $service['carrier_params']['isPrintPeiHuoBarcode']).'</isPrintPeiHuoBarcode></peihuoLabel>';
			}
			
// 			print_r($tmpxml);
// 			exit;
			
			$response = $this->printOrder($tracking_code_arr, self::$authtoken, $tmpxml);
			
			
			\Yii::info('lb_4px,print,puid:'.$puid.' '.($response),"carrier_api");
			
			
			$response = simplexml_load_string($response);
			
			if($response->success == 'false'){
				return self::getResult(1,'',$response->errorMsg);
			}
			
			$url = $response->datas->data;
			$response = Helper_Curl::get($url,null,null,false,null,null,true);
			if(strlen($response)<1000){
				foreach ($data as $v){
					$oneOrder = $v['order'];
					$oneOrder->carrier_error = $response;
					$oneOrder->save();
				}
				return self::getResult(1, '', $response);
			}
			
			$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$oneOrder->customer_number, 0);
			
			foreach ($data as $v){
				$oneOrder = $v['order'];
// 				$oneOrder->is_print_carrier = 1;
				$oneOrder->print_carrier_operator = $puid;
				$oneOrder->printtime = time();
				$oneOrder->save();
			}
			
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	public function printOrder($tracking_code_arr, $authKey, $xmlstr){
		$urlPath = "http://aeapi.4px.com/label/printOrder";
		//$urlPath = "http://localhost:8080/label/printOrder";
		$request = "<labelRequest>";
		$request.= "<printConfig>";
		$request.= $xmlstr;
		$request.= "</printConfig>";
		$request.= "<trackingNumbers>";
		foreach($tracking_code_arr as $value){
			$request.= "<trackingNumber>".$value."</trackingNumber>";
		}
		$request.= "</trackingNumbers>";
		$request.= "</labelRequest>";
		$headers = array("Content-type:application/x-www-form-urlencoded");
		$params = "request=".$request."&token=".$authKey;
// 		return $this->sendCurl($urlPath, "POST", $headers, $params);
		
		return Helper_Curl::post($urlPath,$params,$headers);
	}
	
// 	public function sendCurl($url, $method, $headers, $params){
// 		$ch = curl_init();
// 		$timeout = 100;
// 		curl_setopt ($ch, CURLOPT_URL, $url); //发贴地址
// 		if($headers!=""){
// 			curl_setopt ($ch, CURLOPT_HTTPHEADER, $headers);
// 		}else {
// 			curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-type: text/json'));
// 		}
// 		curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
// 		curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
// 		switch ($method){
// 			case "GET" : curl_setopt($ch, CURLOPT_HTTPGET, true);break;
// 			case "POST": curl_setopt($ch, CURLOPT_POST,true);
// 			curl_setopt($ch, CURLOPT_POSTFIELDS,$params);break;
// 			case "PUT" : curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "PUT");
// 			curl_setopt($ch, CURLOPT_POSTFIELDS,$params);break;
// 			case "DELETE":curl_setopt ($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
// 			curl_setopt($ch, CURLOPT_POSTFIELDS,$params);break;
// 		}
// 		$file_contents = curl_exec($ch);//获得返回值
// 		if ($file_contents === false) {
// 			echo "cURL Error:".curl_error($ch);
// 			exit;
// 		}
// 		return $file_contents;
// 		curl_close($ch);
// 	}
	
	/*
	 * 用来确定打印完成后 订单的下一步状态
	*
	* 公共方法
	*/
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}

	//用来判断是否支持打印
	public static function isPrintOk(){
		return false;
	}
	
	public function cancelOrderNO($data){
		try{
			//订单对象
			$order = $data['order'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$a = $info['account'];
			//获取到帐号中的认证参数
			$api_params = $a->api_params;
			//将用户token记录下来
			self::$authtoken=$api_params['AuthToken'];

			$soap=new self(true);
			$response=$soap->removeOrderService([$order->customer_number]);
			//如果是错误信息
			if($response['ack'] == 'Failure'){

				return BaseCarrierAPI::getResult(1, '', '操作失败,'.$response['cnMessage']);
			}else if($response['ack'] == 'Success'){
				$shipped->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->save();
				return BaseCarrierAPI::getResult(0, '', '订单已取消!时间:'.$response['timestamp']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
		
	}	

	//重新发货
	public function Recreate(){return BaseCarrierAPI::getResult(1, '', '物流商不支持重新发货');}
	
	/**
	 * 查询物流轨迹 
	 * @param 	string 	4px物流号
	 * @return  array
	 */
	
	public function getOrderCarrier(){
		$api_params['customerId'] = '100800';
		$api_params['token'] = 'oDuCfVi88b40oOuMYQUOcTh2b/T+uJdDBsJ+VOrlG6Q=1';
		
		$params = '?customerId='.$api_params['customerId'].'&token='.$api_params['token'].'&language=en_US';
		//拼接url
		$url ='http://apisandbox.4px.com/api/service/woms/order/getOrderCarrier'.$params;
		//echo "<br>".$url;
		
		$header=array();
		$header[]='Content-Type:application/json;charset=utf-8';
		$request['warehouseCode'] = 'UKLH';
		$response = Helper_Curl::post($url,json_encode($request),$header);
		print_r($response);
		
		exit();
	}
	
	public function SyncStatus($data){
		$res = array();

		// TODO carrier user account
		self::$authtoken='';
		try{
			$soap=new self(true,true);
			
			$response=$soap->cargoTrackingService($data);
			//print_r($response);
			//exit();
			if (is_array($response) && isset($response['ack'])) {
				//当返回结果为单数组时(单个订单查询或者多个订单都查询失败时)
				//失败时，当出现第一个失败Number,后面所有查询都会停止,因此还是一个个查比较稳
				$res['error']	=	"1";
				$res['trackContent']=$response['cnMessage'];
				$res['referenceNumber']=$data;
			}else{
				if($response->ack=='Success'){
					$res['error']="0";
					$res['referenceNumber']=$response->referenceNumber;
					$res['destinationCountryCode']=$response->tracks->destinationCountryCode;
					$res['trackingNumber']=$response->tracks->trackingNumber;
					$res['trackContent']=[];
					if(!empty($response->tracks->trackInfo)){
					    // dzt20190621 修改返回结果只有一个trackInfo的处理
					    if(isset($response->tracks->trackInfo->createDate)){
					        $trackInfo = [$response->tracks->trackInfo];
					    }else{
						$trackInfo = $response->tracks->trackInfo;
					    }
						
						foreach($trackInfo as $i=>$t){
							$res['trackContent'][$i]['createDate'] = empty($t->createDate)?'':$t->createDate;
							$res['trackContent'][$i]['createPerson'] = empty($t->createPerson)?'':$t->createPerson;
							$res['trackContent'][$i]['occurAddress'] = empty($t->occurAddress)?'':$t->occurAddress;
							$res['trackContent'][$i]['occurDate'] = empty($t->occurDate)?'':$t->occurDate;
							$res['trackContent'][$i]['trackCode'] = empty($t->trackCode)?'':$t->trackCode;
							$res['trackContent'][$i]['trackContent'] = empty($t->trackContent)?'':$t->trackContent;
						}
					}
				}else{
					$res['error']="1";
					$res['trackContent']='查询发生错误';
					$res['referenceNumber']=$data;
				}
			}
		}catch(CarrierException $e){
			$res['error']="1";
			$res['trackContent']='查询发生错误'.$e->msg();
			$res['referenceNumber']='';
		}
		return $res;
	}
	
	//呼叫api时api名的魔术处理
	//$inputStructMethodName 调用的接口名
	//$customerParameter 上传的数据
	public function __call($inputStructMethodName,$customerParameter) {
		try {
			
			$tmp = self::$soapClient->__getFunctions();
			if(is_array($tmp)) {
				foreach($tmp as $theValue) {
					$pos = strpos(strtolower($theValue), strtolower($inputStructMethodName));
					if($pos === false) {
						continue;
					} else {
						return self::common($inputStructMethodName, $customerParameter);
					}
				}
	
				//以上没有正常return说明没有找到指定方法
				throw new Exception('当前没有此服务方法，请检查方法名是否有误');
			} else {
				$pos = strpos($tmp, (string)$inputStructMethodName);
				if($pos === false)
					throw new Exception('当前没有此服务方法，请检查方法名是否有误');
				else
					return self::common($inputStructMethodName,$customerParameter);
			}
		} catch (Exception $e) {
			if($this->debug) {
				printf("检查方法时出错：<br />Message = %s",$e->__toString());
			}
			exit();
		}
	}
	
	private static function common($inputStructMethodName,$customerParameter) {
		try {
			$params = call_user_func_array(array(__NAMESPACE__.'\Struct',$inputStructMethodName),$customerParameter);
			$params['arg0']=self::$authtoken;
			$result = self::$soapClient->__soapCall($inputStructMethodName,array($params));
			$arr = self::outputStruct($result);
			if(is_array($arr) && !empty($arr)) {
				//查询轨迹过滤
				if(isset($arr['trackCode'])){
					return $result->return;
				}else{
					return $arr;
				}
				
			} else {
				return false;
			}
				
		} catch (Exception $e) {
			if($this->debug) {
				printf("方法执行错误<br />Message = %s",$e->__toString());
			}
			exit();
		}
	}
	
	public static function outputStruct($objectArr) {//处理输出数据
	
		
		if(self::$isMulArray) {
			$lastData = json_encode($objectArr);
			
			$lastData = json_decode($lastData, true);//强制数组化【json与乱码】
			return $lastData['out'];
		}

		if(gettype($objectArr) == 'object')
			$objectArr = get_object_vars($objectArr);
		
		foreach($objectArr as $key =>$value) {
			
			if(gettype($value) == 'object') {
				self::outputStruct($value);
			}
			elseif(gettype($value) == 'array') {
				self::outputStruct($value);
			}
			else {
				self::$returnArr[$key] = $value;
			}
		}
		
		return self::$returnArr;
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
			self::$authtoken = $print_param['AuthToken'];
				
			$tracking_code_arr[] = $print_param['tracking_number'];
			$normalparams = $print_param['carrier_params'];
				
			$tmpxml = '<labelFormat>pdf</labelFormat>';
			$tmpxml .= '<labelType>'.(empty($normalparams['labelType']) ? 1 : $normalparams['labelType']).'</labelType>';
			$tmpxml .= '<labelSize>label_80x90</labelSize>';
				
			if((!empty($service['carrier_params']['isPrintTime'])) || (!empty($normalparams['isPrintBuyerId'])) || (!empty($normalparams['isPrintPeihuo']))){
				$tmplabel = '';
				$tmplabel.='<isPrintTime>'.(empty($normalparams['isPrintTime']) ? 'N' : $normalparams['isPrintTime']).'</isPrintTime>';
				$tmplabel.='<isPrintBuyerId>'.(empty($normalparams['isPrintBuyerId']) ? 'N' : $normalparams['isPrintBuyerId']).'</isPrintBuyerId>';
				$tmplabel.='<isPrintPeihuo>'.(empty($normalparams['isPrintPeihuo']) ? 'N' : $normalparams['isPrintPeihuo']).'</isPrintPeihuo>';
					
				$tmpxml .= '<label>'.$tmplabel.'</label>';
			}
				
			if((!empty($normalparams['isPrintCustomerWeight']))){
				$tmpxml .= '<declarationLabel><isPrintCustomerWeight>'.(empty($normalparams['isPrintCustomerWeight']) ? 'N' : $normalparams['isPrintCustomerWeight']).'</isPrintCustomerWeight></declarationLabel>';
			}
				
			if((!empty($normalparams['isPrintPeiHuoBarcode']))){
				$tmpxml .= '<peihuoLabel><isPrintPeiHuoBarcode>'.(empty($normalparams['isPrintPeiHuoBarcode']) ? 'N' : $normalparams['isPrintPeiHuoBarcode']).'</isPrintPeiHuoBarcode></peihuoLabel>';
			}
				
			$response = $this->printOrder($tracking_code_arr, self::$authtoken, $tmpxml);
			$response = simplexml_load_string($response);
				
			if($response->success == 'false'){
				return ['error'=>1, 'msg'=>$response->errorMsg, 'filePath'=>''];
			}
				
			$url = $response->datas->data;
			$response = Helper_Curl::get($url,null,null,false,null,null,true);
			if(strlen($response)<1000){
				return ['error'=>1, 'msg'=>print_r($response,true), 'filePath'=>''];
			}
				
			$pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
	
			return $pdfPath;
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
}
?>
<?php 
class Struct extends LB_4PXCarrierAPI{
	//公共静态属性，资源最小
	private static $returnArr = array();
	private static $isMulArray = false;//输出结果以xml对象表示，使用倒更方便

	public static function outputStruct($objectArr) {//处理输出数据


		if(self::$isMulArray) {
			$lastData = json_encode($objectArr);
			$lastData = json_decode($lastData, true);//强制数组化【json与乱码】
			return $lastData['out'];
		}

		if(gettype($objectArr) == 'object')
			$objectArr = get_object_vars($objectArr);

		foreach($objectArr as $key =>$value) {
			if(gettype($value) == 'object') {
				self::outputStruct($value);
			}
			elseif(gettype($value) == 'array') {
				self::outputStruct($value);
			}
			else {
				self::$returnArr[$key] = $value;
			}
		}
		return self::$returnArr;
	}


	protected static function returnStructArr($structArray) {//由数组返回对应的结构体数组
		$reObjArr = array();
		$reObjArr[] = self::returnStruct($structArray);
		return $reObjArr;
	}

	protected static function returnStruct($structArray) {//返回结构体
		$structName = new \stdClass();
		foreach($structArray as $key => $value) {
			$structName->$key = $value;
		}
		return $structName;
	}

	protected static function mergeArray($arrs0, $arrs1) {//将外部数据传入到参数模中【完全覆盖】
		foreach($arrs0 as $key0 => $value0) {
			if(isset($arrs1[$key0]) && !empty($arrs1[$key0])) {
				$arrs0[$key0] = $arrs1[$key0];
			} else {
				unset($arrs0[$key0]);
			}
		}
		return $arrs0;
	}




	/*******在线订单操作********/
	//创建订单
	public static function createOrderService($customerParameter) {//用php基类生成一个结构体【定义参数规则】
		//参数模
		$declareInvoice = array(
				"declareNote" => '',
				"declarePieces" => '',
				"declareUnitCode"=> '',
				"eName" => '',
				"cName" => '',
				"name" => '',
				"unitPrice" => '',
				"hsCode" => '',
				"itemUrl" => '',
		);

		$declareInvoiceArray = array();
		foreach ($customerParameter['declareInvoice'] as $value) {
			$tem_declareInvoice = self::mergeArray($declareInvoice, $value);//接受外部参数处理
			$declareInvoiceArray[] = $tem_declareInvoice;//子数组
		}

		//参数模
		$createOrder = array(
				"buyerId" => '',
				"cargoCode" => '',
				"consigneeCompanyName" => '',
				"consigneeEmail" => '',
				"consigneeName" => '',
				"street" =>'',
				"city" => '',
				"stateOrProvince" => '',
				"consigneePostCode" => '',
				"consigneeTelephone" => '',
				"destinationCountryCode" =>  '',
				"initialCountryCode" => '',
				"orderNo" => '',
				"orderNote" =>'',
				"paymentCode" => '',
				"pieces" => '',
				"productCode" =>'',
				"returnSign" =>'',
				"shipperAddress" =>'',
				"shipperCompanyName" => '',
				"shipperName" => '',
				"shipperPostCode" => '',
				"shipperTelephone" => '',
				"insurType"=> '',
				"insurValue"=> '',
				"declareInvoice"=> '',
				"customerWeight"=> '',
				"mctCode"=>'1',
				"hasBattery"=>'',
		);
		$createOrder = self::mergeArray($createOrder, $customerParameter);//接受外部参数处理
		$createOrder['declareInvoice'] = count($declareInvoiceArray)==1?$declareInvoiceArray[0]:$declareInvoiceArray;
		$temp = array(
				'arg1' => $createOrder
		);
		return $temp;
	}


	//预报订单
	public static function preAlertOrderService($customerParameter) {//用php基类生成一个结构体【定义参数规则】
		$temp = array(
//				'arg0' => self::AUTHTOKEN,
				'arg1' => $customerParameter//self::returnStructArr($customerParameter)
		);
		return $temp;
	}

	//创建并预报订单
	public static function createAndPreAlertOrderServiceNew($customerParameter) {//用php基类生成一个结构体【定义参数规则】
		//参数模
		$declareInvoice = array(
				"declareNote" => '',
				"declarePieces" => '',
				"declareUnitCode"=> '',
				"eName" => '',
				"cName" => '',
				"name" => '',
				"unitPrice" => '',
				"hsCode" => '',
				"itemUrl" => '',
		);
	
		$declareInvoiceArray = array();
		foreach ($customerParameter['declareInvoice'] as $value) {
			$tem_declareInvoice = self::mergeArray($declareInvoice, $value);//接受外部参数处理
			$declareInvoiceArray[] = $tem_declareInvoice;//子数组
		}
	
		//参数模
		$createOrder = array(
				"buyerId" => '',
				"cargoCode" => '',
				"consigneeCompanyName" => '',
				"consigneeEmail" => '',
				"consigneeName" => '',
				"street" =>'',
				"city" => '',
				"stateOrProvince" => '',
				"consigneePostCode" => '',
				"consigneeTelephone" => '',
				"destinationCountryCode" =>  '',
				"initialCountryCode" => '',
				"orderNo" => '',
				"orderNote" =>'',
				"paymentCode" => '',
				"pieces" => '',
				"productCode" =>'',
				"returnSign" =>'',
				"shipperAddress" =>'',
				"shipperCompanyName" => '',
				"shipperName" => '',
				"shipperPostCode" => '',
				"shipperTelephone" => '',
				"insurType"=> '',
				"insurValue"=> '',
				"declareInvoice"=> '',
				"customerWeight"=> '',
				"mctCode"=>'1',
				"hasBattery"=>'',
		);
		$createOrder = self::mergeArray($createOrder, $customerParameter);//接受外部参数处理
		$createOrder['declareInvoice'] = count($declareInvoiceArray)==1?$declareInvoiceArray[0]:$declareInvoiceArray;
		$temp = array(
				'arg1' => $createOrder
		);
		return $temp;
	}

	//创建并预报订单
	public static function createAndPreAlertOrderService($customerParameter) {//用php基类生成一个结构体【定义参数规则】
		//参数模
		$declareInvoice = array(
				"declareNote" => '',
				"declarePieces" => '',
				"declareUnitCode"=> '',
				"eName" => '',
				"cName" => '',
				"name" => '',
				"unitPrice" => '',
				"hsCode" => '',
				"itemUrl" => '',
		);

		$declareInvoiceArray = array();
		foreach ($customerParameter['declareInvoice'] as $value) {
			$tem_declareInvoice = self::mergeArray($declareInvoice, $value);//接受外部参数处理
			$declareInvoiceArray[] = $tem_declareInvoice;//子数组
		}

		//参数模
		$createOrder = array(
				"buyerId" => '',
				"cargoCode" => '',
				"consigneeCompanyName" => '',
				"consigneeEmail" => '',
				"consigneeName" => '',
				"street" =>'',
				"city" => '',
				"stateOrProvince" => '',
				"consigneePostCode" => '',
				"consigneeTelephone" => '',
				"destinationCountryCode" =>  '',
				"initialCountryCode" => '',
				"orderNo" => '',
				"orderNote" =>'',
				"paymentCode" => '',
				"pieces" => '',
				"productCode" =>'',
				"returnSign" =>'',
				"shipperAddress" =>'',
				"shipperCompanyName" => '',
				"shipperName" => '',
				"shipperPostCode" => '',
				"shipperTelephone" => '',
				"insurType"=> '',
				"insurValue"=> '',
				"declareInvoice"=> '',
				"customerWeight"=> '',
				"mctCode"=>'1',
				"hasBattery"=>'',
		);
		$createOrder = self::mergeArray($createOrder, $customerParameter);//接受外部参数处理
		$createOrder['declareInvoice'] = count($declareInvoiceArray)==1?$declareInvoiceArray[0]:$declareInvoiceArray;
		$temp = array(
				'arg1' => $createOrder
		);
		return $temp;
	}


	//删除订单
	public static function removeOrderService($customerParameter) {//用php基类生成一个结构体【定义参数规则
		$temp = array(
				'arg1' => count($customerParameter)<2?$customerParameter[0]:$customerParameter
		);
		return $temp;
	}


	//查询订单 暂时没有用上 将来有需要放出
	public static function findOrderService($customerParameter) {//这个方法没有结构体
		$findOrder = array(
				'orderNo' => '',
				'startTime' => '',//开始时间,默认为创建订单时间结合订单状态(Status)查询
				'endTime' => '',//结束时间,默认为创建订单时间，结合订单状态(Status)查询
				'status' => ''//订单状态，参照订单状态表
		);

		$findOrder = self::mergeArray($findOrder, $customerParameter);//接受外部参数处理
		$temp = array(
				'arg1' => $findOrder//这里只接受数组
		);
		return $temp;
	}



	/*******在线订单操作工具********/
	//运费试算
	public static function chargeCalculateService($customerParameter) {//此方法没有结构体
		$chargeCalculate = array(
				"cargoCode" => '',
				"countryCode" => '',
				"productCode" => array(),
				"displayOrder" => '',
				"postCode" => '',
				"startShipmentId" => '',
				"weight" => '',
				"height" => '',
				"length" => '',
				"width" => ''
		);

		$chargeCalculate = self::mergeArray($chargeCalculate, $customerParameter);//接受外部参数处理

		$temp = array(
				'arg0' => self::AUTHTOKEN,
				'arg1' => $chargeCalculate//这里直接用数组
		);

		self::$isMulArray = false;

		return $temp;
	}


	//查询轨迹
	public static function cargoTrackingService($customerParameter) {//数组
		$temp = array(
// 				'arg0' => self::AUTHTOKEN,
				'arg1' => $customerParameter//这里直接用数组
		);
		self::$isMulArray = false;
		return $temp;
	}


	//申请拦截
	public static function cargoHoldService($customerParameter) {//数组
		$temp = array(
				'arg0' => self::AUTHTOKEN,
				'arg1' => $customerParameter//这里直接用数组
		);
		self::$isMulArray = false;
		return $temp;
	}


	//查询跟踪号referenceNumber
	public static function findTrackingNumberService($customerParameter) {//数组
		$temp = array(
				'arg1' => count($customerParameter)<2?$customerParameter[0]:$customerParameter//这里直接用数组
		);
		return $temp;
	}

	//打印标签
	public static function printLableService($customerParameter) {//此接口学未实现
		$temp = array(
				'arg0' => self::AUTHTOKEN,
				'arg1' => $customerParameter//这里直接用数组
		);
		self::$isMulArray = false;
		return $temp;
	}
}
?>
