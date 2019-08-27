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
use eagle\modules\util\helpers\TranslateHelper;


class LB_SANTAICarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	static private $wsdl_url='http://www.sendfromchina.com';
		
	public $token =null;
	public $appKey = null;
	public $userId = null;
	
	private $submitGate = null;
	
	public function __construct(){
		self::$wsdl = self::$wsdl_url.'/ishipsvc/web-service?wsdl';
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/05/13				初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = $data['order'];  //object OdOrder
			$e  = $data['data'];
			 
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

			$form_data = $data['data'];
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];//主要获取运输方式的中文名以及相关的运输代码
			
			if($service->carrier_params['shipperAddresstype']==2 && empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			if($service->carrier_params['shipperAddresstype']==2){
				$senderAddressInfo=$info['senderAddressInfo'];
				//卖家信息
				$shippingfrom=$senderAddressInfo['shippingfrom'];
			}

			//认证参数
			$params=$account->api_params;
			$this->appKey=$params['appKey'];
			$this->token=$params['token'];
			$this->userId=$params['userId'];
			
					
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 70,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 16
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
// 			if(empty($order->consignee_postal_code))
// 				return self::getResult(1,'','收件人邮编不能为空');
// 			if(empty($addressAndPhone['address_line1']))
// 				return self::getResult(1,'','收件人地址不能为空');
// 			if(empty($order->consignee_city))
// 				return self::getResult(1,'','收件人城市不能为空');
			
			$sumQuantity=0;
			$sumWorth=0;
			$sumWeight=0;
			$goodsDetails=array();
			foreach ($order->items as $k=>$v){
// 				if(empty($e['Name'][$k]))
// 					return self::getResult(1,'','中文名称不能为空');
// 				if(empty($e['EName'][$k]))
// 					return self::getResult(1,'','英文名称不能为空');
// 				if(empty($e['DeclarePieces'][$k]))
// 					return self::getResult(1,'','数量不能为空');
// 				if(empty($e['DeclaredValue'][$k]))
// 					return self::getResult(1,'','价值不能为空');
				
				$goodsDetails[$k]=[
					'detailDescriptionCN' => $e['Name'][$k],  //中文描述
					'detailDescription' => $e['EName'][$k],     //详细物品描述
					'detailQuantity' => $e['DeclarePieces'][$k],   //数量
					'detailCustomLabel' => $e['detailCustomLabel'][$k],     //自定义标签
					'detailWorth' => $e['DeclaredValue'][$k],  //价值
					'detailWeight' => floatval($e['weight'][$k])/1000,  //重量(kg)
					'hsCode' =>$e['hsCode'][$k], //商品编码
					'detailEbayTxnId'=>$order->order_source=='ebay'?$v->order_source_transactionid:'',  //Ebay 交易事务 ID
					'detailEbayItemId'=>$order->order_source=='ebay'?$v->order_source_itemid:'',    //Ebay Item ID
					'detailEbayUserId'=>$order->order_source=='ebay'?$order->source_buyer_user_id:'',    //Ebay 买家 ID
// 					'orgin' =>'',      //原产地
					'enMaterial'=>$e['enMaterial'][$k],     //英文材质
					'cnMaterial'=>$e['cnMaterial'][$k],   //中文材质
				];
				$sumQuantity+=floatval($e['DeclarePieces'][$k]);
				$sumWorth+=floatval($e['DeclaredValue'][$k])*floatval($e['DeclarePieces'][$k]);
				$sumWeight+=floatval($e['weight'][$k])/1000*floatval($e['DeclarePieces'][$k]);
			}

			$request = array(
					'HeaderRequest'=> array(
							'appKey'=>$this->appKey,
							'token'=>$this->token,
							'userId'=>$this->userId,
					),
					'addOrderRequestInfo'=>array(
							'customerOrderNo' => $customer_number,   //订单标识
							'shipperAddressType' => $service->carrier_params['shipperAddresstype'],     //发货地址类型，1 为用户系统默认地址，2为用户传送的地址信息
							'shipperName' => $service->carrier_params['shipperAddresstype']==1?'':$shippingfrom['contact'],    //发件人姓名
							'shipperEmail' => $service->carrier_params['shipperAddresstype']==1?'':$shippingfrom['email'],    //发件人邮箱
							'shipperPhone'=>$service->carrier_params['shipperAddresstype']==1?'':$shippingfrom['phone'],    //发件人电话
							'shipperAddress' => $service->carrier_params['shipperAddresstype']==1?'':$shippingfrom['street'],  //发件人地址
							'shipperZipCode' => $service->carrier_params['shipperAddresstype']==1?'':$shippingfrom['postcode'], //发件人邮编
							'shipperCompanyName'=>$service->carrier_params['shipperAddresstype']==1?'':$shippingfrom['company_en'],    //发件人公司
							'shippingMethod' => $service->shipping_method_code, //三态提供运输方式
							'recipientCountry' => $order->consignee_country, //三态提供的国家英文名称
							'recipientName' => $order->consignee, //收件人名称
							'recipientAddress' => $addressAndPhone['address_line1'],   //收件人地址
							'recipientZipCode' => $order->consignee_postal_code, //收件人邮编
							'recipientPhone' => $addressAndPhone['phone1'], //收件人电话
							'recipientEmail' => $order->consignee_email,     //收件人电子邮件
							'recipientState' => $tmpConsigneeProvince,     //收件人州或者省份
							'recipientCity' => $order->consignee_city,     //收件人城市
							'goodsDescription' => $e['goodsDescription'],    //物品描述
							'goodsQuantity' => $sumQuantity, //物品数量
							'goodsDeclareWorth' => $sumWorth, //物品价值
							'goodsWeight' => $sumWeight, //物品重量
							'orderStatus' => 'sumbmitted',//上传并交寄
							'ebayIdentify'=>$order->order_source=='ebay'?$order->selleruserid:'',  //Ebay 交易事务 ID
							'evaluate'=>$e['evaluate'], //投保价值
							'taxesNumber'=> $e['taxesNumber'], //税号
							'isRemoteConfirm'=> $service->carrier_params['isRemoteConfirm'], //是否同意收偏远费
							'isReturn' => intval($service->carrier_params['isReturn']), //是否退件
							'withBattery'=>isset($e['withBattery'])?$e['withBattery']:0,  //是否带电池
							'taxType'=>empty($service->carrier_params['taxType'])?'':$service->carrier_params['taxType'], //税号类型
							'isFba'=>$service->carrier_params['isFba'], //是否 FBA 订单
							'warehouseName'=>empty($e['warehouseName'])?'':$e['warehouseName'],  //当 isFba 为 1 时有效,FBA,或other 其它海外仓储.
							'goodsDetails'=>$goodsDetails
					),
					
			);
			#########################################################################
// 			print_r($request);die;
			\Yii::info('LB_SANTAI,puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'addOrder');
// 			print_r($response);die;
			if($response['error']){return self::getResult(1,'',$response['msg']);}
			
			$response=$response['data'];
			if($response->orderActionStatus!='N'){
				$tmp_is_use_mailno = empty($service['carrier_params']['is_use_mailno']) ? '' : $service['carrier_params']['is_use_mailno'];	
				$serviceNumber=$response->trackingNumber; //可能不返回跟踪号
				
				$trackingNumber = $tmp_is_use_mailno=='Y' ? $response->orderCode : $serviceNumber;
				
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$trackingNumber,['OrderSign'=>$response->orderCode,'OrderTrackingNumber'=>$serviceNumber]);
				
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$trackingNumber);
			}
			else{
// 				if(strstr($response->note,'has existed in the system, please contact with SFC staff for identification')!=false)
// 					return self::getResult(1,'','客户参考号重复');
// 				if(strstr($response->note,'Item Description Required')!=false)
// 					return self::getResult(1,'','物品描述不能为空');
// 				if(strstr($response->note,'Item Description Can not Chinese in description')!=false)
// 					return self::getResult(1,'','物品描述不能为中文');
// 				else{
					$msg=TranslateHelper::toChinesePrompt($response->note);
					return self::getResult(1,'',$msg);
// 				}
			}
		}
		catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
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
	 * @author		lgw		2016/05/13				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$url = self::$wsdl_url.'/api/label';
			
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
						
			//取得打印尺寸
			$printTypeArr="0-3";
			$printType="0";    //纸张格式
			$print_type="pdf";   //打印输出方式(pdf, html)
			$printSize=3;   //纸张大小
			if(!empty($service['carrier_params']['printType']))
				$printTypeArr = $service['carrier_params']['printType'];

			//查询出订单号
			$package_sn = '';
			foreach ($data as $k => $v) {
				$order = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				
				$package_sn .= $shipped['return_no']['OrderSign'].",";
			}
			$package_sn=substr($package_sn,0,-1);
			
			$temp_arr=explode('-',$printTypeArr);
			if(isset($temp_arr[0]))
				$printType=$temp_arr[0];
			if(isset($temp_arr[1]))
				$printSize=$temp_arr[1];
			
			//GET提交数据
			$requestData = $url."?orderCodeList=".$package_sn."&printType=".$printType."&print_type=".$print_type."&printSize=".$printSize;
			//提交请求	
			$response=Helper_Curl::get($requestData);
			if(strstr($response, "找不到订单")>-1)
				return self::getResult(1,'',$response);
			
			return self::getResult(0,['pdfUrl'=>$requestData],'');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
// 			if(is_null($account)){
// 				return self::getResult(1, '', '');
// 			}

			// TODO carrier user account @XXX@
			$request = array(
					'HeaderRequest'=> array(
							'appKey'=>isset($account->api_params['appKey'])?$account->api_params['appKey']:'@XXX@',
							'token'=>isset($account->api_params['token'])?$account->api_params['token']:'@XXX@',
							'userId'=>isset($account->api_params['userId'])?$account->api_params['userId']:'@XXX@',
					),
			);

			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getShipTypes');

			if($response['error']){return self::getResult(1,'',$response['msg']);}

			if(!isset($response['data']->shiptypes) || empty($response['data']->shiptypes))
				return self::getResult(1,'','获取运输方式失败');
			$response = $response['data']->shiptypes;

			$result = '';
			foreach ($response as $channelVal){
				$result .= $channelVal->method_code.':'.$channelVal->cn_name.';';
			}

			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//返回国家英文
	public function getCountries($account){
		try{
			$request = array(
					'HeaderRequest'=> array(
							'appKey'=>$account->api_params['appKey'],
							'token'=>$account->api_params['token'],
							'userId'=>$account->api_params['userId']
					),
			);
				
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getCountries');
			if($response['error']){return self::getResult(1,'',$response['msg']);}
			
			$response=$response['data'];
			$response = $response->countries;
			return $response;
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
					'HeaderRequest'=> array(
							'appKey'=>$data['appKey'],
							'token'=>$data['token'],
							'userId'=>$data['userId'],
					),
			);
			
			$err=0;
			try{
				$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'getShipTypes');
			}
			catch (\Exception $ex){
				$err = 1;
			}
			
			if($err==0)
				$result['error'] = 0;

		}catch(CarrierException $e){
		}
	
		return $result;
	}
}
?>