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



class LB_17FEIACarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	
	public $AppId = null;
	public $Token = null;
	
	public function __construct(){
		self::$wsdl = 'http://open.17feia.com/serviceport/parser.asmx?WSDL';
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/11/08				初始化
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
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置<a target="_blank" href="http://'.$_SERVER['HTTP_HOST'].'/configuration/carrierconfig/index?tcarrier_code='.$order->default_carrier_code.'#syscarrier_show_div_"'.$order->default_carrier_code.'>地址信息</a>');
			}
			$senderAddressInfo=$info['senderAddressInfo'];

			//获取到帐号中的认证参数
			$a = $account->api_params;
			
			//卖家信息
			$shippingfrom=$senderAddressInfo['shippingfrom'];

			$this->AppId = $a['AppId'];
			$this->Token = $a['Token'];
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 300,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 50
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}

			$TotalWeight=0;

			foreach ($order->items as $j=>$vitem){
				if(empty($e['EName'][$j]))
					return self::getResult(1,'','物品描述不能为空');
				if(empty($e['Weight'][$j]))
					return self::getResult(1,'','申报重量不能为空');
				if(empty($e['Amount'][$j]))
					return self::getResult(1,'','申报金额不能为空');
				if(empty($e['ItemCount'][$j]))
					return self::getResult(1,'','申报数量不能为空');
				
				$PublicPackage[]=[
						'PackageCode'=>'',   //包裹标识
						'ConsignCode'=>'',   //国运快递单号
						'Contact'=>$shippingfrom['contact'],     //联系人
						'Address'=>$shippingfrom['street'],    //发货地址
						'Mobile'=>$shippingfrom['mobile'],     //联系人移动电话
						'Description'=>$e['sku'][$j].' *'.$e['ItemCount'][$j],    //物品描述
						'Weight'=>$e['Weight'][$j]/1000,      //重量KG
						'Height'=>'0',    //高度
						'Width'=>'0',     //宽度
						'Length'=>'0',     //长度
						'Amount'=>'0',    //单包裹费用 
						'Count'=>empty($e['BoxCount'][$j])?'1':$e['BoxCount'][$j],      //包装箱数
						'ItemCount'=>$e['ItemCount'][$j],     //物品个数 
						'Price'=>$e['Amount'][$j],
						'DeclaredValue'=>$e['Amount'][$j]*$e['ItemCount'][$j],     //申明价值
						'InsuranceValue'=>'0',     //保险价值
    				];
				$TotalWeight+=$e['Weight'][$j]/1000*$e['ItemCount'][$j];
			}
			$PublicPackages=array('PublicPackage'=>$PublicPackage);
						
			//判断是否为有效目的地
			$ReginArr=self::getTerminalRegionsStr($account,$order->consignee_country_code);
			if(empty($ReginArr))
				return self::getResult(1,'','不是有效目的地，目的地不派送');
			else{
				$arr = explode(':',$ReginArr);
				$checkResult=self::CheckServiceStr($account, $arr[0], $service->shipping_method_code);
				if(empty($checkResult))
					return self::getResult(1,'','目的地不派送');
			}
			
			if(empty($addressAndPhone['address_line1']))
				return self::getResult(1,'','收货人地址不能为空');
			
			$Orders=array(
					'OrdersCode'=>$customer_number,    //订单标识
					'TotalWeight'=>$TotalWeight,    //总重
					'ExpressServiceId'=>$service->shipping_method_code,
					'QuotationId'=>0,
					'TotalFee'=>0,
					'CreateTime'=>date('Y-m-d',$order->create_time).'T'.date('h:m:s',$order->create_time),
					'RegionId'=>$arr[0], //有效目的 地
					'ReceiverName'=>$order->consignee, //收货人姓名
					'ReceiverCompanyName'=>$order->consignee_company,       //收货人公司名
					'ReceiverZip'=>$order->consignee_postal_code,   //收货人邮编
					'ReceiverProvince'=>$tmpConsigneeProvince,    //收货人地区
					'ReceiverCity'=>$order->consignee_city,    //收货人城市
					'ReceiverAddress'=>$addressAndPhone['address_line1'], //收货人地址
					'ReceiverPhoneCode'=>'',   //收货人电话区号
					'ReceiverPhone'=>$order->consignee_phone,    //收货人电话
					'ReceiverExtNum'=>'',     //收货人分机
					'ReceiverMobile'=>$order->consignee_mobile,    //收货人移动电话
					'ReceiverFax'=>$order->consignee_fax,   //收货人传真
					'CustomerNote'=>$customer_number, //客户参考号
					'ConsigneeName'=>'',    //退货名称
					'ConsigneeAddress'=>'',    //退货地址
					'ConsigneeEmail'=>'',      //退货邮箱
					'PublicPackages'=>$PublicPackages,
			);
			
			$request = array(
					'appid'=>$this->AppId,
					'token'=>$this->Token,
					'serviceId'=>$service->shipping_method_code,    //所选服务
					'marketingId'=>32,  //推广标识，小老板为32
					'orders'=>$Orders,
			);
// 			print_r($request);die();
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($request,1),"file");
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'SubmitOrders');
//     print_r($response);die();
			if($response['error']){return self::getResult(1,'',$response['msg']);}
			
			$response=$response['data']->SubmitOrdersResult;
						
			if($response->ResponseCode==200){
				$serviceNumber=$response->ConsignCode;
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$serviceNumber,null);
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$serviceNumber);
			}
			else{
				return self::getResult(1,'',$response->ResponseDesc.',请检查地址信息和报关信息是否正确');
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 * 接口只能删除草稿的订单，所以该方法暂时没有用
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/11/08				初始化
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
	 **/
	public function getTrackingNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');

	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/11/08				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$order = current($data);reset($data);
			$order = $order['order'];
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			$format="0"; //是否有配货单
			if(!empty($carrier_params['format'])){
				$format=$carrier_params['format'];
			}
			//获取请求信息
			$this->AppId = $account_api_params['AppId'];
			$this->Token = $account_api_params['Token'];
			
			$tmpBarcodeStr = '';
			
			//获取追踪号
			foreach ($data as $k=>$v) {
				$order = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];

				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$order->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}
				
				$tmpBarcodeStr.=$shipped['tracking_number'].",";
			}
			$tmpBarcodeStr=substr($tmpBarcodeStr,0,-1);

			$request=array(
					'appid'=>$this->AppId,
					'token'=>$this->Token,
					'consignCodes'=>$tmpBarcodeStr,
					'withItems'=>$format,
			);	
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'GenerateLabel');
// 			print_r($response);die;
			if($response['error']){return self::getResult(1,'',$response['msg']);}
				
			$response=$response['data']->GenerateLabelResult;
				
			if($response->ResponseCode==200){
				return self::getResult(0,['pdfUrl'=>$response->ResponseDesc],'物流单已生成,请点击页面中打印按钮');
			}
			else
				return self::getResult(1,'',$response->ResponseDesc);
			

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			// TODO carrier user account @XXX@
			$request = array(
					'appid'=>is_null($account)?'@XXX@':$account->api_params['AppId'],
					'token'=>is_null($account)?'@XXX@':$account->api_params['Token'],
					'departureRegionId'=>0,
					'terminalRegionId'=>0,
			);

			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'ReadValidServicesJson');
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			$response=json_decode($response['data']->ReadValidServicesJsonResult);
// 			print_r($response);die;
			if(empty($response->ServiceList))
				return self::getResult(1,'','获取运输方式失败');
			
			$channelArr=$response->ServiceList;
			
			$result = '';
			foreach ($channelArr as $channelVal){
				$arr = explode('-',$channelVal->ServiceName);
				$result .= $channelVal->ServiceId.':'.$arr[0].';';
			}

			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//判断目的地是否为有效目的地
	public function CheckServiceStr($account,$RegionID,$ServerId){
		try{
			$request = array(
					'appid'=>$account->api_params['AppId'],
					'token'=>$account->api_params['Token'],
					'departureRegionId'=>0,
					'terminalRegionId'=>0,
			);
		
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'ReadValidServicesJson');
			if($response['error']){
				return 0;
			}
			$response=json_decode($response['data']->ReadValidServicesJsonResult);
				
			if(empty($response->ServiceList))
				return 0;
				
			$channelArr=$response->ServiceList;
				
			foreach ($channelArr as $channelVal){
				if($channelVal->ServiceId==$ServerId){
					$temp=str_replace('</p>','',str_replace('<p>','',$channelVal->SuppliedRegionIds));
					$arr = explode(',',$temp);
					if(in_array($RegionID,$arr))
						return 1;
				}
			}
			
			return 0;
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//获取有效目的地
	public function getTerminalRegionsStr($account,$Code){
		$request = array(
				'appid'=>$account->api_params['AppId'],
				'token'=>$account->api_params['Token'],
		);
		
		$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'ReadValidTerminalRegionsJson');
		if($response['error']){
			return self::getResult(1,'',$response['msg']);
		}
		
		$response=json_decode($response['data']->ReadValidTerminalRegionsJsonResult);
		
		if(empty($response->RegionList))
			return 0;
		
		$channelArr=$response->RegionList;
// 		print_r($channelArr);die;
		$result = '';
		foreach ($channelArr as $channelVal){
			if($channelVal->RegionCode===$Code){
				$result=$channelVal->RegionId.':'.$channelVal->RegionName;
				break;
			}
		}
		
		return $result;
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
			$request = array(
					'appid'=>$data['AppId'],
					'token'=>$data['Token'],
			);
		
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'CheckAccount');
		
			if($response['error'] == 0){
				$response = $response['data']->CheckAccountResult;
				if($response)
					$result['error'] = 0;
			}
		}catch(CarrierException $e){
		}
		
		return $result;
		
	}
}
?>