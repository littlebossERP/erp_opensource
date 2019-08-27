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


class LB_LIAONINGKUAJINGCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	static private $wsdlpf = '';	// 物流打单接口
	private $submitGate = null;	// SoapClient实例
	
	public $secretkey = null;
	public $customerID=null;
	
	public function __construct(){
		self::$wsdl = 'http://www.lncbp.com//webservice/APIWebService.asmx?wsdl';
		self::$wsdlpf='http://www.lncbp.com/Manage/PrintPage/Print_PDF.aspx';
		
// 		self::$wsdl = 'http://202.110.49.124:85/webservice/APIWebService.asmx?wsdl';
// 		self::$wsdlpf='http://202.110.49.124:85/Manage/PrintPage/Print_PDF.aspx';
		
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/13				初始化
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

			//获取到帐号中的认证参数
			$a = $account->api_params;

			$this->secretkey = $a['secretkey'];
			$this->customerID=$a['customerID'];
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 1000,
							'consignee_address_line2_limit' => 1000,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
					
			$strorderproduct ='';
			foreach ($order->items as $j=>$vitem){
				if(empty($e['EName'][$j]))
					return self::getResult(1,'','产品英文名不能为空');
				if(empty($e['CN_Name'][$j]))
					return self::getResult(1,'','产品中文名不能为空');
				if(empty($e['weight'][$j]))
					return self::getResult(1,'','重量不能为0');
				
				$weight=empty($e['weight'][$j])?0:$e['weight'][$j]/1000;
				$quantity=empty($e['quantity'][$j])?0:$e['quantity'][$j];
				$value=empty($e['DeclaredValue'][$j])?0:$e['DeclaredValue'][$j];
				$strorderproduct .= 'MaterialRefNo:'.$e['sku'][$j].','        //产品编号
				 				.'MaterialQuantity:'.$quantity.','         //数量
				 				.'Price:'.$value.','           //单位价值（美元）
				 				.'Weight:'.$weight.','          //重量（KG）
				 				.'EnName:'.$e['EName'][$j].','        //产品英文名
				 				.'WarehouseID:,'    //仓储ID  '.$service['carrier_params']['warehouseID'].'
				 				.'ProducingArea:,'      //原产地
				 				.'CnName:'.$e['CN_Name'][$j].';';		 //产品中文名
			}

// 			if(empty($order->consignee_postal_code))
// 				return self::getResult(1,'','邮编不能为空');
			if(empty($addressAndPhone['address_line1']))
				return self::getResult(1,'','地址1不能为空');
			if(empty($addressAndPhone['phone1']))
				return self::getResult(1,'','电话不能为空');
			if(empty($order->consignee))
				return self::getResult(1,'','收件人不能为空');
			
			$country_code=$order->consignee_country_code;
			if($country_code=='UK')
				$country_code='GB';
		
			$request ="";
			$request.='Style:2;'       //订单类型（仓储订单或普通订购单）仓储订单为1，普通订单为2
					.'GFF_CustomerID:'.$this->customerID.';' //客户ID
					.'GFF_ReceiveSendAddressID:'.$order->selleruserid.';'    //发件人ID
					.'ConsigneeName:'.$order->consignee.';'   //收件人
					.'Country:'.$country_code.';' //国家
					.'Base_ChannelInfoID:'.$service->shipping_method_code.';'       //渠道
					.'State:'.$order->consignee_province.';'      //州
					.'City:'.$order->consignee_city.';'       //城市
					.'OrderStatus:3;'         //订单状态--(草稿=1),(确认=3)
					.'Address1:'.$addressAndPhone['address_line1'].';'         //收件人地址行 1
					.'Address2:'.$addressAndPhone['address_line2'].';' //收件人地址行 2
					.'CsRefNo:'.$customer_number.';'        //客户参考号
					.'Zipcode:'.$order->consignee_postal_code.';'      //邮编
					.'Contact:'.$addressAndPhone['phone1'].';'    //联系方式
					.'CusRemark:'.$e['remark'].';'     //客户订单备注
					.'TrackingNo:;';      //跟踪号

			$requestarr=array(
					'strorderinfo'=>$request,
					'strorderproduct'=>$strorderproduct,
					'stradd'=>'',
					'secretkey'=>$this->secretkey,
			);
			

			//数据组织完成 准备发送
			#########################################################################
			\Yii::info('LB_LIAONINGKUAJING,puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($requestarr),"carrier_api");
			$response = $this->submitGate->mainGate(self::$wsdl, $requestarr, 'soap', 'InsertUpdateOrder');
			\Yii::info('LB_LIAONINGKUAJING,puid:'.$puid.',response,order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
// 			print_r($response);die;
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			$response=$response['data']->InsertUpdateOrderResult;

			if(strstr($response,'保存失败') || strstr($response,'预报失败')){ 
				$err=substr($response,stripos($response, '错误信息'));
				return self::getResult(1,'',$err);
			}
			if(strstr($response,'生成订单出错')){
				return self::getResult(1,'',$response);
			}
			if(strstr($response,'发件人无效')){
				return self::getResult(1,'',$response.'或检查物流账号的登录用户是否正确');
			}
			
			if(strstr($response,'订单保存并提交成功')){
// 							print_r($response);die;
				$arr=explode('-',$response);
				$tr=$arr[1];
					
				if(!strstr($tr,';'))
					$trackingnumber='';
				else{
					$arr=explode(';',$tr);
					$trackingnumber=$arr[0];
				}
					
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$trackingnumber,['delivery_orderId'=>$arr[1]]);
				// 			if(empty($trackingnumber))
					// 				return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$customer_number.',该货代无法立刻获取跟踪号,需要获取跟踪号后再确认发货完成');
					// 			else
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$customer_number.'物流跟踪号:'.$trackingnumber);
			}
			else{
				return self::getResult(1,'',$response);
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 * 接口只能删除草稿的订单，所以该方法暂时没有用
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/13				初始化
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
// 		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
		
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
		
			if(empty($shipped['return_no']['delivery_orderId']))
				return self::getResult(1,'','获取跟踪号失败');
			
			$request=array(
					'orderNO'=>$shipped['return_no']['delivery_orderId'],
					'customerid'=>$account['api_params']['customerID'],
					'secretkey'=>$account['api_params']['secretkey'],
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getPackage');
			
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			$response=$response['data'];
			if($response->getPackageResult=='你的密钥和客户编码不匹配！'){
				return self::getResult(1,'',$response->getPackageResult);
			}
			else{
				$arr=json_decode($response->getPackageResult);
// 				print_r($arr);die;
				
				$response=$arr[0];
				if(empty($response->TrackingNo))
					return BaseCarrierAPI::getResult(1, '', '没有跟踪号返回');
				
				$shipped->tracking_number = $response->TrackingNo;
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				return BaseCarrierAPI::getResult(0, '', '获取成功！跟踪号：'.$response->TrackingNo);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/13				初始化
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
			
			$format="A4";
			if(!empty($carrier_params['type'])){
				$format=$carrier_params['type'];
				
				if($carrier_params['type']=='remin')
					$format='';
			}
			
			$package_sn = '';
			foreach ($data as $k => $v) {
				$order = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				$package_sn .= $shipped['return_no']['delivery_orderId'].",";
			}
			$package_sn=substr($package_sn,0,-1);
			
			$url=self::$wsdlpf."?OrderNo=".$package_sn.',&type='.$format;
			$response = $this->submitGate->mainGate($url, null, 'curl', 'get');
// 			print_r($response);die;
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			if(!strstr($response['data'],'</div>'))
				return self::getResult(1,'','没有找到订单');
			if(strlen($response['data'])<830)
				return self::getResult(1,'',' 物流不支持该渠道接口打印，请到物流后台打印');
			
			return self::getResult(0,['pdfUrl'=>$url],'连接已生成,请点击并打印');
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//获取仓库信息
	public function getWarehouse(){
		try{
			// TODO carrier user account @XXX@
			$request = array(
					'secretkey'=>'@XXX@',
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getWarehouse');

			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			$response=$response['data'];
			if(!isset($response->getWarehouseResult) || empty($response->getWarehouseResult))
				return self::getResult(1,'','获取仓库失败');
			$response = $response->getWarehouseResult;
			$channelArr=json_decode($response);
			
			$result = '';
			foreach ($channelArr as $key=>$channelVal){
				if($key==0 || $channelArr[$key-1]->Base_StyleInfoID!=$channelVal->Base_StyleInfoID)
					$result .= $channelVal->Base_StyleInfoID.':'.$channelVal->FullName.';';
			}
			
			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			// TODO carrier user account @XXX@
			$request = array(
					'secretkey'=>empty($account->api_params['secretkey'])?'@XXX@':$account->api_params['secretkey'],
			);

			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getChannel');

			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			$response=$response['data'];
			if(!isset($response->getChannelResult) || empty($response->getChannelResult))
				return self::getResult(1,'','获取运输方式失败');
			$response = $response->getChannelResult;
			$channelArr=json_decode($response);

			$result = '';
			foreach ($channelArr as $channelVal){
				$result .= $channelVal->ChannelCode.':'.$channelVal->CnName.';';
			}

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
		
		try{
			$request = array(
					'secretkey'=>$data['secretkey'],
			);
		
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getChannel');
		
			if($response['error'] == 0){
				$response = $response['data']->getChannelResult;
				$channelArr=json_decode($response);
		
				if(!empty($channelArr) && $response!='你的密钥不正确!')
					$result['error'] = 0;
			}
		}catch(CarrierException $e){
		}
		
		return $result;
		
	}
}
?>