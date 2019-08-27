<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;

// try{
// 	include dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'PDFMerger'.DIRECTORY_SEPARATOR.'PDFMerger.php';
// }catch(\Exception $e){
// }

class LB_ZHONGHUANYUNCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	
	public $customerNumber = null;
	public $appKey = null;
	
	public function __construct(){
		//中环运没有测试环境
		self::$wsdl = 'http://losapi.zhy-sz.com/LosAPIService.asmx?WSDL';
		
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/12				初始化
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
			
			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			
			//获取到帐号中的认证参数
			$a = $account->api_params;
			$shippingfromaddress = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息
			
			$this->customerNumber = $a['customerNumber'];
			$this->appKey = $a['APIKey'];
			
			//法国没有省份，直接用城市来替换
	        $tmpConsigneeProvince = $order->consignee_province;
	        if(($order->consignee_country_code == 'FR') && empty($order->consignee_province)){
	        	$tmpConsigneeProvince = $order->consignee_city;
	        }
				
			if (empty($order->consignee_city)){
				return self::getResult(1, '', '城市不能为空');
			}
			
			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}
			
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
			
			if (empty($order->consignee_country_code)){
				return self::getResult(1, '', '国家信息不能为空');
			}
			
			if (empty($order->consignee_address_line1)){
				return self::getResult(1, '', '地址1不能为空');
			}
			
// 			$phoneContact = (empty($order->consignee_phone) ? $order->consignee_mobile : $order->consignee_phone);
			
			$addressAndPhoneParams = array(
				'address' => array(
						'consignee_address_line1_limit' => 100,
						'consignee_address_line2_limit' => 100,
						'consignee_address_line3_limit' => 100,
				),
				'consignee_district' => 1,
				'consignee_county' => 1,
				'consignee_company' => 1,
				'consignee_phone_limit' => 100
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

			if (empty($addressAndPhone['phone1'])){
				return self::getResult(1, '', '联系方式不能为空');
			}
			
			$totalWeight = 0;
			$totalAmount = 0;
			$isBattery = 0; //是否含电池 0：否；1是；默认为0
			// object/array required  海关申报信息
			$productList = [];
			
			foreach ($order->items as $j=>$vitem){
				$productList[$j]=[
					'MaterialRefNo'=>$vitem->sku,	//产品sku
					'MaterialQuantity'=>(empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]),	//数量
					'Price'=>$e['DeclaredValue'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]),	//文档写单价  实际上是金额
					'CnName'=>$e['Name'][$j],	//产品中文名称
					'EnName'=>$e['EName'][$j],	//产品英文名称
					'CustomcCode'=>'',	//海关编码
					'ProducingArea'=>'',	//原产地
					'Weight'=>($e['weight'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j])) / 1000,	//重量  物流商用的是KG，eagle系统用的是g
					'Length'=>$e['Length'][$j],	//长
					'Width'=>$e['Width'][$j],	//宽
					'High'=>$e['High'][$j],		//高
					'Field1'=>$e['Name'][$j],	//报关中文名称
				];
				
				if (($isBattery == 0) && ((empty($e['BatteryFlag'][$j]) ? 'N' : $e['BatteryFlag'][$j]) == 'Y') )
					$isBattery=1;
			
				$totalWeight += $productList[$j]['Weight'];
				$totalAmount += $productList[$j]['Price'] * $productList[$j]['MaterialQuantity'];
			}
			
			
			$orderMain = array(
					'Sender'=>$shippingfromaddress['contact'],	//发件人
					'SendAddress'=>$shippingfromaddress['street'],	//发件人地址
					'SendPhone'=>$shippingfromaddress['phone'],	//发件人电话
					'SendEmail'=>$shippingfromaddress['email'],	//发件人电子邮箱
					'SendCompany'=>$shippingfromaddress['company'],	//发件人公司
					'Base_ChannelInfoID'=>$service->shipping_method_code,	//渠道ID，来自渠道信息
					'CsRefNo'=>$customer_number,	//客户参考号
					'ConsigneeName'=>$order->consignee,	//收件人姓名
					'CountryID'=>$order->consignee_country_code,	//国家二字代码，来自国家信息
					'Address1'=>$addressAndPhone['address_line1'],	//地址1
					'Address2'=>$addressAndPhone['address_line2'],	//地址2
					'Address3'=>$addressAndPhone['address_line3'],	//地址3
					'State'=>$tmpConsigneeProvince,	//省/州
					'City'=>$order->consignee_city,		//城市
					'Zipcode'=>$order->consignee_postal_code,	//邮编
					'ConsigneeEmail'=>$order->consignee_email,	//收件人Email
					'Contact'=>$addressAndPhone['phone1'],	//联系方式:1)$order->consignee_phone:收件人电话,2)$order->consignee_mobile:收件人手机
					'CompanyName'=>$order->consignee_company,	//公司名称 
					'PackageStyle'=>'1',	//包裹类型，1：包裹；2：文件；默认为1
					'BatteryFlag'=>$isBattery,		//是否带电池，0：否；1是；默认为0
					'PlanQuantity'=>$totalWeight,	//总重量
					'TotalAmount'=>$totalAmount,	//总金额
					'CusRemark'=>$e['CusRemark'],	//订单备注
			);
			
// 			print_r($orderMain);
// 			print_r($productList);
// 			exit;
			
			//多条商品信息列表
			$request = array(
					'odr_OrderMain'=>$orderMain,
					'orderDetails'=>$productList,
					'customerNumber'=>$this->customerNumber,
					'APIKey'=>$this->appKey,
			);
			
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($request,1),"file"); 
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'AddOrderMainToConfirm');
			if($response['error']){return $response;}
			
			$response = $response['data'];
			
			$response = explode("|",$response->AddOrderMainToConfirmResult);
			
			if($response[0] == 'FAIL'){
				if (count($response) == 3)
					throw new CarrierException($response[2]);
				else
				if (count($response) == 2)
					throw new CarrierException($response[1]);
				else
					throw new CarrierException(print_r($response,true));
			}
			if($response[0] == 'SUCCESS'){
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$response[2],['OrderSign'=>$response[1]]);
				
				$print_param = array();
				$print_param['carrier_code'] = $service->carrier_code;
				$print_param['api_class'] = 'LB_ZHONGHUANYUNCarrierAPI';
				$print_param['APIKey'] = $this->appKey;
				$print_param['customerNumber'] = $this->customerNumber;
				$print_param['orderNoList'] = $response[1];
				$print_param['carrier_params'] = $service->carrier_params;
				
				try{
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
				}catch (\Exception $ex){
				}
				
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$response[1].'物流跟踪号:'.$response[2]);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 * 接口只能删除草稿的订单，所以该方法暂时没有用
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/12				初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消已确定的物流单。');
		
		try{
			$order = $data['order'];
			
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
			
			$this->customerNumber = $account->api_params['customerNumber'];
			$this->appKey = $account->api_params['APIKey'];
			
			$request = array(
					'orderNo' => $shipped->return_no['OrderSign'],
					'customerNumber' => $this->customerNumber,
					'APIKey' => $this->appKey,
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'LogicDeleteOrder');
			
			print_r($response);
			
			if($response['error']){return $response;}
			$response = $response['data'];
			
			//接口提示需要先逻辑删除，再到物理删除，这里执行逻辑删除
			$response = explode("|",$response->LogicDeleteOrderResult);
				
			if($response[0] == 'FAIL'){
				return BaseCarrierAPI::getResult(1, '', '操作失败,'.$response['1']);
			}
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'DeleteOrder');
			
			if($response['error']){return $response;}
			$response = $response['data'];
				
			//这里执行物理删除
			$response = explode("|",$response->DeleteOrderResult);
			
			if($response[0] == 'FAIL'){
				return BaseCarrierAPI::getResult(1, '', '操作失败,'.$response['1']);
			}
			if($response[0] == 'SUCCESS'){
				$shipped->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->save();
				return BaseCarrierAPI::getResult(0, '', '订单已取消!时间:'.date('Ymd His',time()));
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	**/
	public function doDispatch($data){
// 		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');

		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
		
			$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
			$order->save();
			return BaseCarrierAPI::getResult(0, '', '订单交运成功！');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
	 * @author		hqw		2015/08/12				初始化
	 +----------------------------------------------------------
	**/
	public function doPrint($data){
		try{
			$returnNo = '';
			foreach ($data as $k=>$v) {
				$order = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
		
				$user=\Yii::$app->user->identity;
				if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
				$puid = $user->getParentUid();
		
				$info = CarrierAPIHelper::getAllInfo($order);
				$service = $info['service'];
				$account = $info['account'];
				$account_api_params = $account->api_params;
				$carrier_params = $service->carrier_params;
				
				$this->customerNumber = $account_api_params['customerNumber'];
				$this->appKey = $account_api_params['APIKey'];
				
				if($shipped->return_no['OrderSign'])$returnNo .= $shipped->return_no['OrderSign'].',';
				//中环运无法判断是否打印成功 所以只要调用 默认认为成功 保存信息 进行下一步
// 				$order->is_print_carrier = 1;
				$order->print_carrier_operator = $puid;
				$order->printtime = time();
				$order->save();
			}
			if(empty($returnNo))throw new CarrierException('操作失败,订单不存在');
			$returnNo = rtrim($returnNo,',');
			
			$request = array(
					'orderNoList' => $returnNo,
					'format' => empty($carrier_params['format']) ? '1' : $carrier_params['format'],
					'customerNumber' => $this->customerNumber,
					'APIKey' => $this->appKey,
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'GetPDFPrintLable');
			
			$response = $response['data'];
			$response = explode("|",$response->GetPDFPrintLableResult);
			
			if($response[0] == 'FAIL'){
				return BaseCarrierAPI::getResult(1, '', '操作失败,'.$response['1']);
			}
			if($response[0] == 'SUCCESS'){
				//中环运的订单打印比较特殊 打开他们的下载页面就可以
				return self::getResult(0,['pdfUrl'=>$response[1]],'连接已生成,请点击并打印');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取渠道信息列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/12				初始化
	 +----------------------------------------------------------
	 **/
	public function getCarrierShippingServiceStr(){
		try{
// 			$request = array(
// 					'customerNumber'=>$this->customerNumber,
// 					'APIKey'=>$this->appKey,
// 			);
			$request = array(
					'customerNumber'=>'',
					'APIKey'=>'',
			);
		
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'GetChannelList');
			print_r($response);
			if(!isset($response['error'])){
				return self::getResult(1,'','接口返回失败e1');
			}
			
			if($response['error'] != 0){
				return self::getResult(1,'',(empty($response['msg']) ? '接口返回失败e2' : $response['msg']));
			}
			
			$response =  json_decode(json_encode( $response),true);
			$channelArr = $response['data']['GetChannelListResult']['EyBase_ChannelInfo'];
			
			$channelStr = '';
		
			foreach ($channelArr as $channelVal){
				$channelStr .= $channelVal['Base_ChannelInfoID'].':'.$channelVal['CnName'].';';
			}
			
			return self::getResult(0, $channelStr, '');
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取国家信息列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/12				初始化
	 +----------------------------------------------------------
	 **/
	public function getPlaceList(){
		try{
			$request = array(
					'customerNumber'=>$this->customerNumber,
					'APIKey'=>$this->appKey,
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'GetPlaceList');
			
			$response = $response['data']->GetPlaceListResult;
			$response =  json_decode(json_encode( $response),true);
			
			return self::getResult(0, $response['EyBase_Place'], '');
		
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
			$this->customerNumber = $print_param['customerNumber'];
			$this->appKey = $print_param['APIKey'];
			$format = empty($print_param['carrier_params']['format']) ? 6 : $print_param['carrier_params']['format']; ////获取打印方式
			
			//将A4格式的标签转为热敏纸格式
			switch ($format){
				case 1:
					$format = 6;
					break;
				case 2:
					$format = 5;
					break;
				case 3:
					$format = 11;
					break;
				case 26:
					$format = 25;
					break;
				case 28:
					$format = 27;
					break;
			}
			
			$request = array(
					'orderNoList' => $print_param['orderNoList'],
					'format' => $format,
					'customerNumber' => $this->customerNumber,
					'APIKey' => $this->appKey,
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'GetPDFPrintLable');
			
			$response = $response['data'];
			$response = explode("|",$response->GetPDFPrintLableResult);
			
			if($response[0] == 'FAIL'){
				return ['error'=>1, 'msg'=>'操作失败,'.$response['1'], 'filePath'=>''];
			}
			if($response[0] == 'SUCCESS'){
				$pdf_respond = Helper_Curl::get($response[1]);
				
				if(strlen($pdf_respond)<1000){
					return ['error'=>1, 'msg'=>'打印失败！错误信息：'.print_r($pdf_respond,true), 'filePath'=>''];
				}
				
				$pdfPath = CarrierAPIHelper::savePDF2($pdf_respond,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
				
				return $pdfPath;
			}else{
				return ['error'=>1, 'msg'=>'操作失败e01', 'filePath'=>''];
			}
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
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
// 					'customerNumber'=>$data['customerNumber'],
// 					'APIKey'=>$data['APIKey'],
// 					'orderNo'=>'',
// 			);
				
// 			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'LogicDeleteOrder');
				
// 			if($response['error'] == 0){
// 				$response = $response['data']->LogicDeleteOrderResult;
				
// 				$tmparr = explode("|",$response);
				
// 				if(count($tmparr) == 2){
// 					if($tmparr[1] == '订单不存在;'){
// 						$result['error'] = 0;
// 					}
// 				}
// 			}
// 		}catch(CarrierException $e){
// 		}
		
// 		return $result;
// 	}
}

?>