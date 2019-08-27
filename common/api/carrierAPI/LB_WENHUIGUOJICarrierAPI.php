<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\carrier\models\SysCarrierAccount;
use Qiniu\json_decode;
use yii;



class LB_WENHUIGUOJICarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	
	public $token = null;
	public $Pwd = null;
	
	public function __construct(){   
		self::$wsdl = 'http://120.76.152.245/default/svc/web-service';
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/12/20				初始化
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
			$carrier_params = $service->carrier_params;
			$account = $info['account'];

			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			$senderAddressInfo=$info['senderAddressInfo'];

			//获取到帐号中的认证参数
			$a = $account->api_params;
			
			//卖家信息
			$shippingfrom=$senderAddressInfo['shippingfrom'];

			$this->token = $a['token'];
			$this->Pwd = $a['Pwd'];
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 1000,
							'consignee_address_line2_limit' => 1000,
							'consignee_address_line3_limit' => 1000,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

			$ItemArr=array();
			$totalweight=0;
			$totalqty=0;
			foreach ($order->items as $j=>$vitem){	
				$ItemArrone=array(
						'invoice_enname'=>$e['EName'][$j],           //海关申报品名
						'invoice_cnname'=>$e['CName'][$j],     //中文海关申报品名
						'invoice_weight'=>$e['Weight'][$j]/1000,            //申报重量，单位KG, 精确到三位小数。
						'invoice_quantity'=>$e['quantity'][$j],             //数量
						'invoice_unitcharge'=>$e['DeclaredValue'][$j],            //单价
				);
				$ItemArr[]=$ItemArrone;
				
				$totalweight+=$e['Weight'][$j]/1000*$e['quantity'][$j];
				$totalqty+=$e['quantity'][$j];
			}
			
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			$Consignee=array(
				'consignee_company'=>$order->consignee_company,      //收件人公司名
				'consignee_province'=>$tmpConsigneeProvince,      //收件人省
				'consignee_city'=>$order->consignee_city,     //收件人城市
				'consignee_street'=>$addressAndPhone['address_line1'],      //收件人地址1
				'consignee_street2'=>$addressAndPhone['address_line2'],        //收件人地址2
				'consignee_street3'=>$addressAndPhone['address_line3'],        //收件人地址3
				'consignee_postcode'=>$order->consignee_postal_code,        //收件人邮编
				'consignee_name'=>$order->consignee,      //收件人姓名
				'consignee_telephone'=>$order->consignee_phone,       //收件人电话
				'consignee_mobile'=>$order->consignee_mobile,          //收件人手机
				'consignee_email'=>$order->consignee_email,                //收件人邮箱
			);

			$Shipper=array(
					'shipper_company'=>$shippingfrom['company_en'],      //发件人公司名
					'shipper_countrycode'=>$shippingfrom['country'],     //发件人国家二字码
					'shipper_province'=>$shippingfrom['province_en'],     //发件人省
					'shipper_city'=>$shippingfrom['city_en'],        //发件人城市
					'shipper_street'=>$shippingfrom['street_en'],       //发件人地址
					'shipper_postcode'=>$shippingfrom['postcode'],       //发件人邮编
					'shipper_name'=>$shippingfrom['contact_en'],            //发件人姓名
					'shipper_telephone'=>$shippingfrom['phone'],      //发件人电话
					'shipper_mobile'=>$shippingfrom['mobile'],        //发件人手机
					'shipper_email'=>$shippingfrom['email'],             //发件人邮箱
					'shipper_fax'=>$shippingfrom['fax'],        //发件人传真
			);

			$is_return="1";    //是否退回
			if(!empty($carrier_params['is_return']))
				$is_return = $carrier_params['is_return'];
			
			$params=array(
					'reference_no'=>$customer_number,       //客户参考号
					'shipping_method'=>$service->shipping_method_code,     //配送方式
					'country_code'=>$order->consignee_country_code,     //收件人国家二字码
					'order_weight'=>$totalweight,  //订单重量，单位KG，最多3位小数
					'order_pieces'=>$totalqty,   //外包装件数
					'insurance_value'=>$e['insurance_value'],       //投保金额，默认RMB
					'length'=>(empty($e['length'])?'1':$e['length']),            //包裹长
					'width'=>(empty($e['width'])?'1':$e['width']),           //包裹宽
					'height'=>(empty($e['height'])?'1':$e['height']),            //包裹高
					'is_return'=>$is_return,          //是否退回,包裹无人签收时是否退回，1-退回，0-不退回
					'Consignee'=>$Consignee,    //收件人信息
					'Shipper'=>$Shipper,       //发件人信息
					'ItemArr'=>$ItemArr,           //海关申报信息
			);

			$paramsJson=json_encode($params);
			
			$request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ship="http://www.example.org/Ec/">
							<soapenv:Body>
								<ship:callService>
									<paramsJson>'.$paramsJson.'</paramsJson>
									<appToken>'.$account->api_params['token'].'</appToken>
									<appKey>'.$account->api_params['Pwd'].'</appKey>
									<service>createOrder</service>
								</ship:callService>
							</soapenv:Body>
						</soapenv:Envelope>';

// 			print_r($request);die();
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($request,1),"file");
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl,$request,$post_head);
			
			$doc = new \DOMDocument();
			$doc->loadXML($response); //读取xml文件
			$channelArr=json_decode($doc->textContent,true);
// 			print_r($channelArr);die();
			
			if($channelArr['ask']=='Success'){
				$serviceNumber=$channelArr['shipping_method_no'];

				$print_param = array();
				$print_param['delivery_orderId'] = $channelArr['order_code'];
				$print_param['carrier_code'] = $service->carrier_code;
				$print_param['api_class'] = 'LB_WENHUIGUOJICarrierAPI';
				$print_param['tracking_number'] = $channelArr['shipping_method_no'];
				$print_param['carrier_params'] = $service->carrier_params;
						
				try{
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
				}catch (\Exception $ex){
				}
				
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$serviceNumber,['delivery_orderId'=>$channelArr['order_code']]);
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$serviceNumber);
			}
			else{	
				$errcode='';
				$errmsg=$channelArr['message'];
				if(isset($channelArr['Error'])){
					$errcode=$channelArr['Error']['errCode'];
					$errmsg=$channelArr['Error']['errMessage'];
				}
				return self::getResult(1,'',$errcode.': '.$errmsg);
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/12/20				初始化
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
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/12/20				初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){		
	try{
				$order = $data['order'];
				//获取到所需要使用的数据
				$info = CarrierAPIHelper::getAllInfo($order);
				$account = $info['account'];
				
				//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
				$checkResult = CarrierAPIHelper::validate(0,1,$order);
				$shipped = $checkResult['data']['shipped'];

				$params=array(
						'reference_no'=>array($shipped['customer_number']),
				);
				
				$request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ship="http://www.example.org/Ec/">
					<soapenv:Body>
						<ship:callService>
				<paramsJson>'.json_encode($params).'</paramsJson>
							<appToken>'.$account->api_params['token'].'</appToken>
							<appKey>'.$account->api_params['Pwd'].'</appKey>
							<service>getTrackNumber</service>
						</ship:callService>
					</soapenv:Body>
				</soapenv:Envelope>';
				
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl,$request,$post_head);
			$doc = new \DOMDocument();
			$doc->loadXML($response); //读取xml文件
			$channelArr=json_decode($doc->textContent,true);
// 			print_r($channelArr);die;
			
			if($channelArr['ask']=='Success'){
				$response_data=$channelArr['data'];
				foreach ($response_data as $response_dataone){
					$shipped->tracking_number = $response_dataone['TrackingNumber'];
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					return BaseCarrierAPI::getResult(0, '', '获取成功！跟踪号：'.$response_dataone['TrackingNumber']);
					break;
				}
			}
			else{
				$errcode='';
				$errmsg='';
				if(isset($channelArr['Error'])){
					$errcode=$channelArr['Error']['errCode'];
					$errmsg=$channelArr['Error']['errMessage'];
				}
				return self::getResult(1,'',$errmsg);
			}
				
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/12/20				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{			
			$order = current($data);reset($data);
			$order = $order['order'];
			$checkResult = CarrierAPIHelper::validate(1,1,$order);
			$shipped = $checkResult['data']['shipped'];
			$puid = $checkResult['data']['puid'];
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			//获取请求信息
			$this->token = $account_api_params['token'];
			$this->Pwd = $account_api_params['Pwd'];
			
			$label_type='1';
			if(!empty($service['carrier_params']['label_type']))
				$label_type = $service['carrier_params']['label_type'];
						
			$params=array();
			$reference_no=array();
			foreach ($data as $k=>$v) {
				$oneOrder = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
				$shipped = $checkResult['data']['shipped'];
// 				if(empty($shipped->tracking_number)){
// 					return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少追踪号,请检查订单是否已上传' );
// 				}

				$reference_no[]=$shipped['return_no']['delivery_orderId'];
			}
			
			$params=array(
					'codes'=>$reference_no,
// 					'label_type'=>$label_type,
// 					'order_type'=>'1',
			);
			
			$request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ship="http://www.example.org/Ec/">
					<soapenv:Body>
						<ship:callService>
				<paramsJson>'.json_encode($params).'</paramsJson>
							<appToken>'.$account->api_params['token'].'</appToken>
							<appKey>'.$account->api_params['Pwd'].'</appKey>
							<service>batchGetLabel</service>
						</ship:callService>
					</soapenv:Body>
				</soapenv:Envelope>';
			
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl,$request,$post_head);
			$doc = new \DOMDocument();
			$doc->loadXML($response); //读取xml文件
			$channelArr=json_decode($doc->textContent,true);
// 			print_r($channelArr);die;
			
			if($channelArr['ask']=='Success'){
				return self::getResult(0,['pdfUrl'=>$channelArr['result'][0]['url']],'连接已生成,请点击并打印');
			}
			else{
				$errcode='';
				$errmsg=$channelArr['message'];
				if(isset($channelArr['Error'])){
					$errcode=$channelArr['Error']['errCode'];
					$errmsg=$channelArr['Error']['errMessage'];
				}
				return self::getResult(1,'',$errmsg);
			}

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			// TODO carrier user account @XXX@
			$request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ship="http://www.example.org/Ec/">
	<soapenv:Body>
		<ship:callService>
<paramsJson>{"country_code":""}</paramsJson>
			<appToken>'.(is_null($account)?'@XXX@':$account->api_params['token']).'</appToken>
			<appKey>'.(is_null($account)?'@XXX@':$account->api_params['Pwd']).'</appKey>
			<service>getShippingMethod</service>
		</ship:callService>
	</soapenv:Body>
</soapenv:Envelope>';
						
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl,$request,$post_head);
			$doc = new \DOMDocument();
			$doc->loadXML($response); //读取xml文件 			
			$channelArr=json_decode($doc->textContent);
			
			if($channelArr->ask=='Failure'){
				return null;
			}
			
			$channelArr=$channelArr->data;
// 			print_r($response);die;
			
			$result = '';
			foreach ($channelArr as $channelArrone){
				$result.=$channelArrone->code.':'.$channelArrone->cn_name.';';
			}

			return self::getResult(0, $result, '');
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
		
		try{
			$request = '<soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ship="http://www.example.org/Ec/">
	<soapenv:Body>
		<ship:callService>
<paramsJson>{"reference_no":"a"}</paramsJson>
			<appToken>'.$data['token'].'</appToken>
			<appKey>'.$data['Pwd'].'</appKey>
			<service>getOrder</service>
		</ship:callService>
	</soapenv:Body>
</soapenv:Envelope>';
		
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl,$request,$post_head);
			$doc = new \DOMDocument();
			$doc->loadXML($response); //读取xml文件 			
			$channelArr=json_decode($doc->textContent,true);
		
			if($channelArr['message'] != 'appToken/appKey非法'){
				$result['error'] = 0;
			}
		}catch(CarrierException $e){
		}
		
		return $result;
		
	}
}
?>