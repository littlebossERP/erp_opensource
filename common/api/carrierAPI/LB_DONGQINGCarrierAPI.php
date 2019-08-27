<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_xml;
use common\helpers\Helper_Curl;



class LB_DONGQINGCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	
	public $token = '';
	public $loginName = '';
	public $partnerKey = '';
		
	public function __construct(){
// 		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' )
			self::$wsdl = 'http://59.57.249.2/index';   //正式环境
// 		else
// 			self::$wsdl = 'http://59.57.249.13/index';   //测试环境
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/03/01				初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
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
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
				
			//用户在确认页面提交的数据
			$e = $data['data'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];

			//获取到帐号中的认证参数
			$a = $account->api_params;

			$this->token = $a['token'];
			$this->loginName = $a['loginName'];
			$this->partnerKey = $a['partnerKey'];
			
			$senderAddressInfo=$info['senderAddressInfo'];
			//卖家信息
			$shippingfrom=$senderAddressInfo['shippingfrom'];
			
			//没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 100,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$total_weight=0;  //kg
			$lstDeclareInvoiceDto='';
			foreach ($order->items as $j=>$vitem){
// 				if(empty($e['di_Name'][$j]))
// 					return self::getResult(1,'','中文报关名称不能为空');
// 				if(empty($e['di_EName'][$j]))
// 					return self::getResult(1,'','英文报关名称不能为空');
// 				if(empty($e['di_pcs'][$j]))
// 					return self::getResult(1,'','件数不能为空');
// 				if(empty($e['di_unitprice'][$j]))
// 					return self::getResult(1,'','申报金额不能为空');
// 				if(empty($e['di_hscode'][$j]))
// 					return self::getResult(1,'','海关货物编号不能为空');
// 				if(empty($e['weight'][$j]))
// 					return self::getResult(1,'','重量不能为空');
				
				$lstDeclareInvoiceDto.="<declareInvoice>
										<di_Name>".$e['di_Name'][$j]."</di_Name>
										<di_EName>".$e['di_EName'][$j]."</di_EName>
										<du_Code>PCE</du_Code>
										<di_pcs>".$e['di_pcs'][$j]."</di_pcs>
										<di_unitprice>".$e['di_unitprice'][$j]."</di_unitprice>
										<di_note>".$e['di_note'][$j]."</di_note>
										<di_hscode>".$e['di_hscode'][$j]."</di_hscode>
										</declareInvoice>";
				$total_weight+=$e['weight'][$j]/1000*$e['di_pcs'][$j];
			}
			
			if(empty($order->consignee))
				return self::getResult(1,'','买家姓名不能为空');
			if(empty($addressAndPhone['phone1']))
				return self::getResult(1,'','电话不能为空');
			if(empty($order->consignee_country_code))
				return self::getResult(1,'','收件人国家不能为空');

			$orderxml="<?xml version='1.0' encoding='utf-8'?>
					<requestData>
					<order>
					<orderNo><![CDATA[".$customer_number."]]></orderNo>
					<pk_code>".$service->shipping_method_code."</pk_code>
					<ct_code_destination>".$order->consignee_country_code."</ct_code_destination>
					<buyerId></buyerId>
					<consigneeCompanyName><![CDATA[".(empty($order->consignee_company)?$order->consignee:$order->consignee_company)."]]></consigneeCompanyName>
					<consigneeName><![CDATA['.$order->consignee.']]></consigneeName>
					<consigneeTel>".$addressAndPhone['phone1']."</consigneeTel>
					<consigneePostCode>".$order->consignee_postal_code."</consigneePostCode>
					<shipperCompanyName><![CDATA[".(empty($shippingfrom['company_en'])?$shippingfrom['contact_en']:$shippingfrom['company_en'])."]]></shipperCompanyName>
					<shipperName><![CDATA[".$shippingfrom['contact']."]]></shipperName>
					<shipperAddress><![CDATA[".$shippingfrom['street_en']."]]></shipperAddress>
					<shipperTel>".$shippingfrom['phone']."</shipperTel>
					<lstDeclareInvoiceDto>".$lstDeclareInvoiceDto."</lstDeclareInvoiceDto>
					<pkName>".$service->shipping_method_name."</pkName>
					<ctName>".$order->consignee_country."</ctName>
					<ups_code></ups_code>
					<consigneeAddress_state>".$tmpConsigneeProvince."</consigneeAddress_state>
					<consigneeAddress_city><![CDATA[".$order->consignee_city."]]></consigneeAddress_city>
					<consigneeAddress_info><![CDATA[".$addressAndPhone['address_line1']."]]></consigneeAddress_info>
					<returnsign>".$service['carrier_params']['returnsign']."</returnsign>
					<customer_weight>".$total_weight."</customer_weight>
					<shipperProvince><![CDATA[".$shippingfrom['province_en']."]]></shipperProvince>
					<shipperCity><![CDATA[".$shippingfrom['city_en']."]]></shipperCity>
					<mctCode></mctCode>
					<cm_id_platform>0</cm_id_platform>
					</order>
					</requestData>";
			
			$request = array(
					'data'=>$orderxml,
					'token'=>$this->token,
					'loginName'=>$this->loginName,
					'ciphertext'=>md5($orderxml.$this->token.$this->partnerKey),
			);
// 			print_r($request);print_r($this->partnerKey);die();
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info('LB_DONGQING,puid:'.$puid.',request,order_id:'.$order->order_id.' '.$orderxml,"carrier_api");
			$response = Helper_Curl::post(self::$wsdl.'/createOrderService',$request);
			\Yii::info('LB_DONGQING,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response,"carrier_api");
			$string=Helper_xml::xmlparse($response);
			$channelArr=json_decode(json_encode((array) $string), true);
			
// 			print_r($channelArr);die();
			
			if($channelArr['code']=='9092400'){
				$trackingNo=$channelArr['data']['orderInfoList']['orderInfo']['trackingNumber'];
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$trackingNo,['OrderSign'=>$trackingNo]);
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号:'.$customer_number.' 追踪条码:'.$trackingNo);
			}
			else{
				if(empty($channelArr['data']))
					$msg=$channelArr['msg'];
				else{
					$msg=empty($channelArr['data']['orderInfoList']['orderInfo']['errors'])?'':$channelArr['data']['orderInfoList']['orderInfo']['errors'];
				}
				return self::getResult(1,'',$msg);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}

	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/03/01				初始化
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
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运。');
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
	 * @author		lgw		2017/03/01				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持打印，请查看<a href="http://www.littleboss.com/word_list_96_256.html" target="_blank">帮助文档</a>，设置<a href="/configuration/carrierconfig/carrier-custom-label-list-new" target="_blank">物流标签自定义(新)</a>打印');
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			$result='AC4:香港邮政挂号小包(HONGKONG POST REGISTERED);';

			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
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
		$result = array('is_support'=>1,'error'=>1);
	
		$orderxml="<?xml version='1.0' encoding='utf-8'?>
					<requestData>
					<dailyBill>
				<billStartDate>".date('Y-m-d',time())."</billStartDate>
				<billEndDate>".date('Y-m-d',time())."</billEndDate>
				</dailyBill></requestData>";
		
		try{
			$request = array(
					'data'=>$orderxml,
					'token'=>$data['token'],
					'loginName'=>$data['loginName'],
					'ciphertext'=>md5($orderxml.$data['token'].$data['partnerKey']),
			);
			$response = Helper_Curl::post(self::$wsdl.'/dailyBillService',$request);

			$string=Helper_xml::xmlparse($response);
			$channelArr=json_decode(json_encode((array) $string), true);

			if($channelArr['code'] == '9092400'){	
					$result['error'] = 0;
			}
		}catch(CarrierException $e){
		}
	
		return $result;
	}

}
?>