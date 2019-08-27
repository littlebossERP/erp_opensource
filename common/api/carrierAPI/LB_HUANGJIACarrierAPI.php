<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;

class LB_HUANGJIACarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	
	public $customerNumber = null;
	public $appKey = null;
	
	public function __construct(){
		//皇家物流没有测试环境
		self::$wsdl = 'http://www.pfcexpress.com/webservice/APIWebService.asmx?WSDL';
	
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/08				初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			//odOrder表内容
			$order = $data['order'];
			$o = $order->attributes;
			
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
			$service_carrier_params = $service->carrier_params;
			$account = $info['account'];
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 100,
							'consignee_address_line2_limit' => 100,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 30
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
				
			//获取到帐号中的认证参数
			$a = $account->api_params;
// 			$shippingfromaddress = $account->address['shippingfrom'];//获取到账户中的地址信息
				
			$this->customerNumber = $a['customerNumber'];
			$this->appKey = $a['APIKey'];
			
			if (empty($order->consignee_province)){
				return self::getResult(1, '', '省份不能为空');
			}
			
			if (empty($order->consignee_city)){
				return self::getResult(1, '', '城市不能为空');
			}
				
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
				
			if (empty($order->consignee_country_code)){
				return self::getResult(1, '', '目的国家不能为空');
			}
				
			if (empty($order->consignee_address_line1)){
				return self::getResult(1, '', '地址1不能为空');
			}
				
			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}
			
			$phoneContact = '';
			if (empty($order->consignee_phone) && empty($order->consignee_mobile)){
				return self::getResult(1, '', '联系方式不能为空');
			}
				
			if (empty($order->consignee_phone) || empty($order->consignee_mobile)){
				$phoneContact = $order->consignee_phone.$order->consignee_mobile;
			}else{
				$phoneContact = $order->consignee_phone.','.$order->consignee_mobile;
			}
			
			$productList = '';
				
			foreach ($order->items as $j=>$vitem){
				if(empty($e['EName'][$j])){
					return self::getResult(1, '', '英文报关名不能为空');
				}
				
				if(empty($e['Name'][$j])){
					return self::getResult(1, '', '中文报关名不能为空');
				}
				
				$productList .= 'MaterialRefNo:'.$vitem->sku.','.'MaterialQuantity:'.(empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]).','.
						'Price:'.$e['DeclaredValue'][$j].','.'Weight:'.($e['weight'][$j] / 1000).','.
						'EnName:'.$e['EName'][$j].','.'WarehouseID:'.','.'ProducingArea:'.$e['ProducingArea'][$j].','.
						'CnName:'.$e['Name'][$j].',;';
			}
				
				
			$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
			(empty($order->consignee_district) ? '' : ','.$order->consignee_district);
				
// 			$orderMain = array(
// 					'Style' => 2,	//订单类型（仓储订单或普通订购单）仓储订单为1，普通订单为2
// 					'GFF_CustomerID' => $this->customerNumber,	//客户ID
// 					'ConsigneeName' => $order->consignee,	//收件人
// 					'Country' => $order->consignee_country_code,	//国家
// 					'Base_ChannelInfoID' => $service->shipping_method_code,	//渠道
// 					'State' => $order->consignee_province,	//州
// 					'City' => $order->consignee_city,	//城市
// 					'OrderStatus' => 3,	//订单状态--(草稿=1),(确认=3)
// 					'Address1' => $order->consignee_address_line1.(empty($order->consignee_address_line1) ? '' : $addressInfo),	//地址1
// 					'Address2' => $order->consignee_address_line2.(empty($order->consignee_address_line2) ? '' : $addressInfo),	//地址2
// 					'CsRefNo' => $customer_number,	//客户参考号
// 					'Zipcode' => $order->consignee_postal_code,	//邮编
// 					'Contact' => $order->consignee_phone,	//联系方式
// 					'CusRemark' => $e['CusRemark'],	//客户订单备注
// 					'TrackingNo' => '',	//跟踪号
// 			);
			
			$orderMain = 'Style:2'.';'.'GFF_CustomerID:'.$this->customerNumber.';'.
				'GFF_ReceiveSendAddressID:;'.
				'ConsigneeName:'.$order->consignee.';'.'Country:'.$order->consignee_country_code.';'.
				'Base_ChannelInfoID:'.$service->shipping_method_code.';'.'State:'.$order->consignee_province.';'.
				'City:'.$order->consignee_city.';'.'OrderStatus:3'.';'.
				'Address1:'.$addressAndPhone['address_line1'].';'.
				'Address2:'.$addressAndPhone['address_line2'].';'.
				'CsRefNo:'.$customer_number.';'.'Zipcode:'.$order->consignee_postal_code.';'.
				'Contact:'.$addressAndPhone['phone1'].';'.'CusRemark:'.$e['CusRemark'].';'.'TrackingNo:;';
			
			//多条商品信息列表
			$request = array(
					'strorderinfo'=>$orderMain,
					'strorderproduct'=>$productList,
					'secretkey'=>$this->appKey,
			);
			
			//数据组织完成 准备发送
			#########################################################################
// 			\Yii::info(print_r($request,1),"file");
			
			\Yii::info('LB_HUANGJIACarrierAPI,request,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'InsertUpdateOrder');
			
			\Yii::info('LB_HUANGJIACarrierAPI,response,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
			
// 			print_r($response);
// 			exit;
			
			
			if($response['error']){return $response;}
			
			$response = $response['data'];
			$response = $response->InsertUpdateOrderResult;
			$responseArr = explode("-", $response);
			
			if(!isset($responseArr[1])){
				throw new CarrierException(print_r($response,true));
			}
			
			$tmpOrderNo = '';
			$tmpTrackingNo = '';
			$tmpOrderStatus = '';
			
			$tmpNoArr = explode(';', $responseArr[1]);
			if (count($tmpNoArr)>1){
				$tmpTrackingNo = $tmpNoArr[0];
				$tmpOrderNo = $tmpNoArr[1];
				$tmpOrderStatus = OdOrder::CARRIER_WAITING_PRINT;
			}else{
				$tmpOrderNo = $tmpNoArr[0];
				$tmpOrderStatus = OdOrder::CARRIER_WAITING_GETCODE;
			}
			
			//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
			$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,$tmpOrderStatus,$tmpTrackingNo,['OrderSign'=>$tmpOrderNo]);
// 			return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$tmpOrderNo.(empty($tmpTrackingNo) ? '' : '物流跟踪号:'.$tmpTrackingNo));

			if (strpos(strtoupper($response), "预报失败") !== false){
				$response .= "<br><font color='red'>错误:请用户登录皇家物流网站修改物流订单并提交到确认后再到eagle系统获取跟踪号。</font>";
			}
			
			return  BaseCarrierAPI::getResult(0,$r, $response);
				
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	**/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消订单。');
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
	 * @author		hqw		2015/10/16				初始化
	 +----------------------------------------------------------
	**/
	public function getTrackingNO($data){
		try{
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
			 
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			//认证参数
			$params=$account->api_params;
			$this->customerNumber = $params['customerNumber'];
			$this->appKey = $params['APIKey'];
		
			$tmpOrderNo = '';
			
			if($shipped->return_no['OrderSign'])
				$tmpOrderNo = $shipped->return_no['OrderSign'];
			
			if(empty($tmpOrderNo)){
				throw new CarrierException("请检查该订单是否已经上传到该物流商！");
			}
			
			$request = array(
					'orderNO'=>$tmpOrderNo,
					'customerid'=>$this->customerNumber,
					'secretkey'=>$this->appKey,
					'Remark'=>'',
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getPackage');
			$response = $response['data']->getPackageResult;
			$response = json_decode($response, true);
			
			if(!isset($response[0]['TrackingNo'])){
				throw new CarrierException("上传数据失败");
			}
			
			if(empty($shipped->tracking_number) && !empty($response[0]['TrackingNo'])){
				$shipped->tracking_number=$response[0]['TrackingNo'];
				$shipped->save();
			}
			
			if(!empty($response[0]['TrackingNo'])){//有跟踪号的前提
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				return  BaseCarrierAPI::getResult(0,'','查询成功成功!跟踪号'.$response[0]['TrackingNo']);
			}else {//没有跟踪号
				throw new CarrierException('暂时没有跟踪号');
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/16				初始化
	 +----------------------------------------------------------
	**/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
		
			$url = 'http://www.pfcexpress.com/Manage/PrintPage/Print_PDF.aspx';
		
			$order = current($data);
			reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
		
			$params = $account->api_params;
			$this->customerNumber = $params['customerNumber'];
			$this->appKey = $params['APIKey'];
			
			//取得打印尺寸
			$printFormat = $service['carrier_params']['format'];
			if($printFormat == 'rm')
				$printFormat = '';
			
			//查询出订单操作号
			$package_sn = '';
			foreach ($data as $k => $v) {
				$order = $v['order'];
// 				$order->is_print_carrier = 1;
				$order->print_carrier_operator = $puid;
				$order->printtime = time();
				$order->save();
				
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				
				if($shipped->return_no['OrderSign'])
					$package_sn .= $shipped->return_no['OrderSign'].',';
			}
			
			$package_sn = '&OrderNo='.$package_sn;
			##############################################################################
			$requestData = "?type=$printFormat".$package_sn;
			
			return self::getResult(0,['pdfUrl'=>$url.$requestData],'连接已生成,请点击并打印');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/*
	 * 用来确定打印完成后 订单的下一步状态
	*
	* 公共方法
	*/
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取渠道信息列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/08				初始化
	 +----------------------------------------------------------
	 **/
	public function getChannelList(){
		try{
			$request = array(
					'secretkey'=>$this->appKey,
			);
	
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getChannel');
			
			$response = $response['data']->getChannelResult;
			$channelArr =  json_decode($response ,true);
			
			$channelStr = '';
	
			foreach ($channelArr as $channelVal){
				$channelStr .= $channelVal['ChannelCode'].':'.$channelVal['CnName'].';';
			}
			
			return self::getResult(0, $channelStr, '');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	//获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			// TODO carrier user account @XXX@	
			$request = array(
					'secretkey'=>'@XXX@',//$account['APIKey'],
			);
	
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getChannel');
			
			$response = $response['data']->getChannelResult;
			$channelArr =  json_decode($response ,true);
			
			if($response == '你的密钥不正确!'){
				return self::getResult(1, '', $response);
			}
			
			$channelStr = '';
	
			foreach ($channelArr as $channelVal){
				$channelStr .= $channelVal['ChannelCode'].':'.$channelVal['CnName'].';';
			}
				
			if(empty($channelStr)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $channelStr, '');
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
// 	public function getVerifyCarrierAccountInformation($data){
// 		$result = array('is_support'=>1,'error'=>1);
	
// 		try{
			 
// 			$request = array(
// 					'secretkey'=>$data['APIKey'],
// 			);
			
// 			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getChannel');
			
// 			if($response['data']->getChannelResult != '你的密钥不正确!'){
// 				$result['error'] = 0;
// 			}
			
// 		}catch(CarrierException $e){
// 		}
	
// 		return $result;
// 	}
}

?>