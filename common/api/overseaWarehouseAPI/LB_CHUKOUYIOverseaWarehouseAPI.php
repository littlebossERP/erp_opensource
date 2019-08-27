<?php
namespace common\api\overseaWarehouseAPI;
use common\helpers\SubmitGate;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_xml;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrderShipped;
use Qiniu\json_decode;
use common\helpers\Helper_Curl;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-05-20
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 出口易海外仓物流商API
 +------------------------------------------------------------------------------
 * @category	vendors
 * @package		vendors/overseaWarehouseAPI
 * @subpackage  Exception
 * @author		qfl<772821140@qq.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_CHUKOUYIOverseaWarehouseAPI extends BaseOverseaWarehouseAPI 
{
	static private $ErpToken = '';
	static private $wsdl = '';   //提交路径
	static private $wsdl3 = '';   //获取订单状态
	private $submitGate = null;
	static private $AccessToken = '';

	public function __construct(){
	if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			self::$wsdl = 'https://openapi.chukou1.cn/v1/outboundOrders';
			self::$wsdl3 = 'https://openapi.chukou1.cn/v1/outboundOrders/{t}/status';
			self::$ErpToken = 'F16F32207ED395BC51F11FB05BF1542830A16D29074FB133';
		}else{
			self::$wsdl = 'https://openapi-release.chukou1.cn/v1/outboundOrders';
			self::$wsdl3="https://openapi-release.chukou1.cn/v1/outboundOrders/{t}/status";
			self::$ErpToken = 'F16F32207ED395BCE55DC92D8C3B35CB6C362F7D78412EE2';
		}
		$this->submitGate = new SubmitGate();
	}
	//获取所有仓库
	function getWarehouse($account){		
		
		$header = array();
		$header[] = 'Content-Type: application/json; charset=utf-8';
		$header[] = 'Authorization: Bearer Zjk1M2RlOWEtZjFjMC00ODcyLWE5ZDEtYjliM2U2ZmU0YjFi';
		$data=Helper_Curl::get(self::$wsdl."warehouses",null, $header);
		$data=json_decode($data,true);
		print_r($data);die;
		foreach ($data as $res){
			$result .= $res['WarehouseId'].':'.$res['WarehouseName'].';';
		}
		return $output = [
			'data'=>$data,
			'string'=>$result,
		];
	}
	//获取仓库下所有运输服务
	function getService($account){

		$header = array();
		$header[] = 'Content-Type: application/json; charset=utf-8';
		$header[] = 'Authorization: Bearer Zjk1M2RlOWEtZjFjMC00ODcyLWE5ZDEtYjliM2U2ZmU0YjFi';
		$data=Helper_Curl::get(self::$wsdl."outboundServices/UK",null, $header);
		$data=json_decode($data,true);
		print_r($data);die;

	}
	
	function getOrderNO($data)
	{
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			//订单对象
			$order = $data['order'];
			//表单提交的数据
			$e = $data['data'];
			//对当前条件的验证
			// 			$checkResult = CarrierAPIHelper::validate(0,0,$order);
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$s = $info['service'];
			$a = $info['account'];
			#######################################################################################
	
			$carrier_params = $s->carrier_params;

			$extra_id = isset($e['extra_id'])?$e['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
	
			//对当前条件的验证，校验是否登录，校验是否已上传过
			$checkResult = CarrierAPIHelper::validate(1, 0, $order,$extra_id,$customer_number);
			
			//帐号token信息
			$account_params = $a->api_params;
			//验证token
			if(empty($account_params['AccessToken']) || strtotime(date("Y-m-d H:i:s"))>$account_params['next_time']){
				//重新获取token
				$result=CarrierAPIHelper::getChukouyiAccesstoken($a);
				if($result['success']){
					self::$AccessToken=$result['data']['AccessToken'];
				}
				else{
					return self::getResult(1,'',$result['message']);
				}
			}
			else
				self::$AccessToken=$account_params['AccessToken'];
			
			$total = count($order->items);
			/*
			for($k = 0;$k<$total;$k++){
				//产品信息
				$ProductList[] =[
					'Quantity'=>$e['DeclarePieces'][$k],//数量
					'Sku'=>$e['sku'][$k],//产品名称
					// 'StorageNo'=>,//库存编码 若为M2C则必须提供
					'ProductName'=>$e['DeclareName'][$k],//发非本国时必须，用于清关。目前若不填则为空
					'Price'=>$e['DeclareValue'][$k],//发非本国时必须，用于清关。目前若不填则为0
				];
			}
			*/
			
			//产品信息 添加ebay订单信息的判断
			foreach($order->items as $k=>$v){
				if($order->order_source == 'ebay'){
					$transactionid = $v->order_source_transactionid;
					$itemid = $v->order_source_itemid;
				}else{
					$transactionid = '';
					$itemid = '';
				}
				
				$ProductList[] =[
					'Quantity'=>$e['DeclarePieces'][$k],//数量
					'Sku'=>$e['sku'][$k],//产品名称
					'ProductName'=>$e['DeclareName'][$k],//发非本国时必须，用于清关。目前若不填则为空
					'Price'=>$e['DeclareValue'][$k],//发非本国时必须，用于清关。目前若不填则为0
					'PlatformItemId'=>$itemid,			//ebay物品ID
					'PlatformTransactionId'=>$transactionid		//ebay平台交易号
				];
			}

			$ShipToAddress=array(
					"Country"=>$order->consignee_country_code,
					"Province"=>$order->consignee_province,
					"City"=>$order->consignee_city,
					"Street1"=>$order->consignee_address_line1,
					"Street2"=>$order->consignee_address_line2.$order->consignee_address_line3,
					"Postcode"=>$order->consignee_postal_code,
					"Contact"=>$order->consignee,
					"Phone"=>strlen($order->consignee_phone)>4?$order->consignee_phone:$order->consignee_mobile,
					"Email"=>$order->consignee_email,
			);
			
			$Package=array(
					"PackageId"=>$customer_number,//客户参考号
					"ServiceCode"=>$s->shipping_method_code,//运输方式
					"ShipToAddress"=>$ShipToAddress,
					"Skus"=>$ProductList,
// 					"SellPrice"=>"",
// 					"SellPriceCurrency"=>"",
					"Remark"=>$e['remark'],//备注信息
					"SalesPlatform"=>"None",
			);		
			
			$request=array(
					"MerchantId"=>"",
					"WarehouseId"=>$s->third_party_code,//出库仓库代码如 US、NJ、AU、UK、DE
					"Package"=>$Package,
					"Remark"=>"",
			);


			#########################################################################################
			//数据组织完毕 准备发送
			\Yii::info('LB_CHUKOUYIOverseaWarehouse,puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			$header = array();
			$header[] = 'Content-Type: application/json; charset=utf-8';
			$header[] = 'Authorization: Bearer '.self::$AccessToken;
			$response2=Helper_Curl::post(self::$wsdl,json_encode($request), $header);
			$response=Helper_Curl::$last_post_info;
			\Yii::info('LB_CHUKOUYIOverseaWarehouse,puid:'.$puid.',response,order_id:'.$order->order_id.' '.json_encode($response).' message:'.json_encode($response2),"carrier_api");

			if($response['http_code']=="200"){return self::getResult(1,'','订单已存在');}
			else if($response['http_code']=="400"){
				$response2_arr=json_decode($response2,true);
				
				if(empty($response2_arr['Errors'])){
					return self::getResult(1,'','提交的数据有误，请检查');
				}
				else{
					return self::getResult(1,'',$response2_arr['Errors'][0]['Message']);
				}
			}
			else if($response['http_code']=="201"){
				$r = CarrierAPIHelper::orderSuccess($order,$s,$customer_number,OdOrder::CARRIER_WAITING_DELIVERY,null,['OrderSign'=>$customer_number]);
				return self::getResult(0,$r,'订单提交到已交运,请稍后获取跟踪号.参考号:'.$customer_number);

			}
			else{
				return self::getResult(1,'',Helper_Curl::$last_error);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//旧接口
	function getOrderNO2($data)
	{
		try{
			//订单对象
			$order = $data['order'];
			//表单提交的数据
			$e = $data['data'];
			//对当前条件的验证
// 			$checkResult = CarrierAPIHelper::validate(0,0,$order);
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$s = $info['service'];
			$a = $info['account'];
			#######################################################################################

			$carrier_params = $s->carrier_params;
			$OrderDetailNew = [
				// 'OrderSign'=>,//订单号。如果指定，则这批包裹将累加到此订单中。
				'WarehouseNew'=>$s->third_party_code,//出库仓库代码如 US、NJ、AU、UK、DE
				'Warehouse'=>'None',//弃用，统一传None
				'Remark'=>$e['remark'],//备注信息
			];
			$extra_id = isset($e['extra_id'])?$e['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
				
			//对当前条件的验证，校验是否登录，校验是否已上传过
			$checkResult = CarrierAPIHelper::validate(1, 0, $order,$extra_id,$customer_number);
			
			$PackageListNew = [
				'Custom'=>$customer_number,//客户参考号
				'ShippingNew'=>$s->shipping_method_code,//运输方式
				// 'Remark'=>$puid,//备注信息
				'BuyerID'=>$order->source_buyer_user_id,//买家id
				// 'ItemID'=>'',
				'Shipping' => 'None',               //填None即可
	            'ShippingV2_1' => 'None',           //填None即可

				//送货地址
				'ShipToAddress'=>[
					'Contact'=>$order->consignee,
					'Street1'=>$order->consignee_address_line1,
					'Street2'=>$order->consignee_address_line2.$order->consignee_address_line3,
					'City'=>$order->consignee_city,
					'Province'=>$order->consignee_province,
					'Country'=>$order->consignee_country_code,
					'PostCode'=>$order->consignee_postal_code,
					'Phone'=>strlen($order->consignee_phone)>4?$order->consignee_phone:$order->consignee_mobile,
					'Email'=>$order->consignee_email,
				],
			];
			//是否保价
			if($e['InsuredRate']){
				$PackageListNew['IsInsured'] = 'True';//是否保价，只有挂号的包裹才可以进行保价
				$PackageListNew['InsuredRate'] = $e['InsuredRate'];//保价系数，支持小数点后1位，基数为申报价值，数值范围是1.0-3.0，详细保价条款请与业务人员了解
			}
			$total = count($order->items);
			for($k = 0;$k<$total;$k++){
				//产品信息
				$ProductList[] =[
					'Quantity'=>$e['DeclarePieces'][$k],//数量
					'SKU'=>$e['sku'][$k],//产品名称
					// 'StorageNo'=>,//库存编码 若为M2C则必须提供
					'DeclareName'=>$e['DeclareName'][$k],//发非本国时必须，用于清关。目前若不填则为空
					'DeclareValue'=>$e['DeclareValue'][$k],//发非本国时必须，用于清关。目前若不填则为0
				];	
			}
			$PackageListNew['ProductList'] = $ProductList;
			$OrderDetailNew['PackageListNew'][] = $PackageListNew;//装箱清单

			//帐号token信息
			$account_params = $a->api_params;
			$request['request'] = [
				'Token'=>trim($account_params['token']).':'.self::$ErpToken,//v2是在原来放客户Token的参数上，增加ErpToken，格式如下：{客户Token}:{ErpToken},
				'UserKey'=>$account_params['user_key'],
				'Submit'=>false,//是否提交审核
				'OrderDetailNew'=>$OrderDetailNew
			];

// 			echo '<pre>';
// 			print_r($request);
// 			echo '</pre>';die;

			#########################################################################################
		    //数据组织完毕 准备发送
		    $response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'OutStoreAddOrderNew');
		    if(isset($response['error']) && $response['error'])return $response;
// 		    print_r($response);exit;
		    $response = $response['data'];
		    $response = $response->OutStoreAddOrderNewResult;
		    if($response->Ack == 'Success'){
		    	//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				$r = CarrierAPIHelper::orderSuccess($order,$s,$response->OrderSign,OdOrder::CARRIER_WAITING_DELIVERY);
				return self::getResult(0,$r,'出库成功,出库单号:'.$response->OrderSign);
			}
			if($response->Ack=='Failure'){
				return self::getResult(1,'',$response->Message);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//订单交运
	function doDispatch($data){
	
		try{
			$order = $data['order'];

			//对当前条件的验证
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			//获取到帐号中的认证参数
			$a = $account->api_params;
			//验证token
			if(empty($a['AccessToken']) || strtotime(date("Y-m-d H:i:s"))>$a['next_time']){
				//重新获取token
				$result=CarrierAPIHelper::getChukouyiAccesstoken($account);
				if($result['success']){
					self::$AccessToken=$result['data']['AccessToken'];
				}
				else{
					return self::getResult(1,'',$result['message']);
				}
			}
			else
				self::$AccessToken=$a['AccessToken'];
				
			$shipped = $checkResult['data']['shipped'];
	
			if(empty($shipped->tracking_number))
				return self::getResult(1, '', "请获取跟踪号");
				
			$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
			$order->save();
			return self::getResult(0,'', '结果：订单交运成功！');
	
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//旧接口
	function doDispatch2($data){

		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			//对当前条件的验证
			$checkResult = CarrierAPIHelper::validate(0,1,$order);

			$params = $account->api_params;
			
			
			
			
			//最终请求提交参数
			$request = array(
				'request' => array(
					'Token' => $params['token'].':'.self::$ErpToken,//v2是在原来放客户Token的参数上，增加ErpToken，格式如下：{客户Token}:{ErpToken},	//系统验证字符串
					'UserKey' => $params['user_key'],	//第三方验证字符串
					'ActionType' =>	'Submit',	//操作类型
					'OrderSign' => $order->customer_number,	//出库单单号，形如ETST12040600005 ***********
				)
			);
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'OutStoreCompleteOrder');
			$user=\Yii::$app->user->identity;
			$puid = $user->getParentUid();
			\Yii::info('CHUKOUYIOversea,doDispatch,puid:'.$puid.json_encode($response), "file");
			// echo '<pre>';
			// print_r($response);
			// echo '</pre>';die;
			if(isset($response['error']) && $response['error'])return self::getResult(0,'', json_encode($response));
			$response = @$response['data'];
			$response = @$response->OutStoreCompleteOrderResult;
			if(empty($response) || !isset($response->Ack)){
				return self::getResult(0,'', '网络异常，请稍后再试！error001');
			}
			if($response->Ack == 'Failure'){
				return self::getResult(1, '', $response->Message);
			}
			if($response->Ack == 'Success'){
				//订单交运 没有跟踪号返回
				$order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
				$order->save();
				return self::getResult(0,'', '结果：订单交运成功！');
			}
			return self::getResult(0,'', '网络异常，请稍后再试！error002');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//获取跟踪号
	function getTrackingNO($data){
		try{			
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];

			//对当前条件的验证
			$checkResult = CarrierAPIHelper::validate(0,1,$order);

			$account = $info['account'];
			$params = $account->api_params;
			
			//验证token
			if(empty($params['AccessToken']) || strtotime(date("Y-m-d H:i:s"))>$params['next_time']){
				//重新获取token
				$result=CarrierAPIHelper::getChukouyiAccesstoken($account);
				if($result['success']){
					self::$AccessToken=$result['data']['AccessToken'];
				}
				else{
					return self::getResult(1,'',$result['message']);
				}
			}
			else
				self::$AccessToken=$params['AccessToken'];
			
			$shipped = $checkResult['data']['shipped'];
			$returnNo = $shipped->return_no;

			if(empty($shipped->customer_number))
				return self::getResult(1, '', '没有处理号，请检查订单是否已上传');
			
			
			$response_status=self::selectStatus($shipped->customer_number, self::$wsdl3, self::$AccessToken);

			if(isset($response_status['Status']) && $response_status['Status']=="Created"){
				$return_no=$shipped->return_no;
				$return_no['ItemSign']=empty($response_status['Ck1PackageId'])?'':$response_status['Ck1PackageId'];
				$shipped->return_no=$return_no;
				$shipped->save();
					
				if(empty($response_status['TrackingNumber'])){
					return self::getResult(1, '', "出口易包裹 {$returnNo['OrderSign']} 未生成跟踪号，请等待或联系出口易客服");
				}
				else{
					$TrackingNumber=$response_status['TrackingNumber'];
					$shipped->tracking_number = $TrackingNumber;
					$shipped->save();
					$order->tracking_number = $TrackingNumber;
					$order->save();
					return self::getResult(0,'', '获取物流号成功！<br/>跟踪号:'.$TrackingNumber);
				}
			}
			else if(isset($response_status['Status']) && $response_status['Status']=="Creating"){
				return self::getResult(1,'','物流处理订单中，请等待或联系出口易客服');
			}
			else{
				if(!empty($response_status['CreateFailedReason']['ExtendMessage'])){
					return self::getResult(1,'',$response_status['CreateFailedReason']['ExtendMessage']);
				}
				else if(!empty($response_status['UnShippedReason']['ExtendMessage'])){
					return self::getResult(1,'',$response_status['UnShippedReason']['ExtendMessage']);
				}
				else{
					if(empty($response_status['Errors'][0]['Message']))
						return self::getResult(1,'',"操作失败，请联系客服e1");
					else{
						if(strpos($response_status['Errors'][0]['Message'],'直发包裹')!== false && strpos($response_status['Errors'][0]['Message'],'不存在')!== false){
							//兼容旧数据调用新接口查询订单讯息
							$TrackingNumber=self::getTrackingNO3($shipped->customer_number);
							if($TrackingNumber['error'])
								return self::getResult(1,'', $TrackingNumber['msg']);
					
							$shipped->tracking_number = $TrackingNumber['TrackingNumber'];
							$shipped->save();
							$order->tracking_number = $TrackingNumber['TrackingNumber'];
							$order->save();
							return self::getResult(0,'', '获取物流号成功！<br/>跟踪号:'.$TrackingNumber['TrackingNumber']);
						}
						else
							return self::getResult(1,'',$response_status['Errors'][0]['Message']);
					}
				}
			}

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	public function getTrackingNO3($customer_number){
		$data=array(
				"error"=>0,
				"msg"=>"",
				"TrackingNumber"=>"",
		);
	
		try{
			//旧接口
			$url="https://openapi.chukou1.cn/v1/outboundOrders/Ck1PackageId";
			$header[] = 'Content-Type: application/json; charset=utf-8';
			$header[] = 'Authorization: Bearer '.self::$AccessToken;
			$Ids=array("Ids"=>array($customer_number));
			$response_json2=Helper_Curl::post($url,json_encode($Ids), $header);
			$response=json_decode($response_json2,true);
	
			if(empty($response)){
				$data['error']=1;
				$data['msg']="操作失败，请联系客服e2";
	
				return $data;
			}
				
			if(empty($response[0]['TrackingNumber'])){
				$data['error']=1;
				$data['msg']="出口易包裹未生成跟踪号，请等待或联系出口易客服";
	
				return $data;
			}
			else{
				$data['TrackingNumber']=$response[0]['TrackingNumber'];
				return $data;
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//旧接口
	function getTrackingNO2($data){
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
	
			//对当前条件的验证
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
	
			$params = $account->api_params;
			//最终请求提交参数
			$request = array(
					'request' => array(
							'Token' => $params['token'].':'.self::$ErpToken,//v2是在原来放客户Token的参数上，增加ErpToken，格式如下：{客户Token}:{ErpToken},	//系统验证字符串
							'UserKey' => $params['user_key'],	//第三方验证字符串
							'OrderSign' => $order->customer_number,	//出库单单号，形如ETST12040600005 ***********
					)
			);
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'OutStoreGetOrderNew');
			$user=\Yii::$app->user->identity;
			$puid = $user->getParentUid();
			\Yii::info('CHUKOUYIOversea,getTrackingNO,puid:'.$puid.json_encode($response), "file");
			// 			echo '<pre>';
			// 			print_r($response);
			// 			echo '</pre>';die;
			if(isset($response['error']) && $response['error'])return self::getResult(0,'', json_encode($response));
			$response = @$response['data'];
			$response = @$response->OutStoreGetOrderNewResult;
			if(empty($response) || !isset($response->Ack)){
				return self::getResult(0,'', '网络异常，请稍后再试！error003');
			}
			if($response->Ack == 'Failure'){
				return self::getResult(1, '', $response->Message);
			}
			if($response->Ack == 'Success'){
				$tracking_number = $response->OrderDetail->PackageListNew->OutStorePackageNew->TrackingNumber;
				if($tracking_number){
					$shipped = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number])->one();
					if(empty($shipped)){
						return self::getResult(1, '', '网络异常，请稍后再试！error005');
					}
					$shipped->return_no = ['Sign'=>$response->OrderDetail->PackageListNew->OutStorePackageNew->Sign];
					$shipped->tracking_number = $tracking_number;
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					return self::getResult(0,'', '获取物流号成功！<br/>跟踪号:'.$tracking_number);
				}
				//订单交运 没有跟踪号返回
				return self::getResult(1,'', "出口易包裹 {$order->customer_number} 未生成跟踪号，请等待或联系出口易客服");
			}
			return self::getResult(0,'', '网络异常，请稍后再试！error004');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//订单打印
	function doPrint($data){
		foreach($data as $v){
			$order = $v['order'];
			$order->carrier_error = '物流接口不支持打印物流单';
			$order->save();
			$result[] = [
				'isInterfaceError'=>1,
				'物流接口不支持打印物流单',
			];
		}
		return self::getResult(1,'','该物流商不支持打印订单');
	}

	//用来判断是否支持打印
	public static function isPrintOk(){
		return false;
	}

	//订单取消
	function cancelOrderNO($data){
		return self::getResult(1,'','该物流商不支持订单取消');
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			//查询出处理号
			// $shipped = OdOrderShipped::find()->select(['return_no'])->where(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number])->one();
			// if(empty($shipped))return self::getResult(1,'','该订单没有上传数据,请检查是否已上传订单');


			//对当前条件的验证
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			$params = $account->api_params;
			//最终请求提交参数
			$request = array(
				'request' => array(
					'Token' => $params['token'].':'.self::$ErpToken,//v2是在原来放客户Token的参数上，增加ErpToken，格式如下：{客户Token}:{ErpToken},	//系统验证字符串
					'UserKey' => $params['user_key'],	//第三方验证字符串
					'ActionType' =>	'Cancel',	//操作类型
					'OrderSign' => $order->customer_number,	//出库单单号，形如ETST12040600005 ***********
				)
			);
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'OutStoreCompleteOrder');
			if(isset($response['error']) && $response['error'])return $response;
			$response = $response['data'];
			$response = $response->OutStoreCompleteOrderResult;
			if($response->Ack == 'Failure'){
				return self::getResult(1, '', $response->Message);
			}
			if($response->Ack == 'Success'){
				$shipped->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->save();
				return self::getResult(0, '', '订单已取消!时间:'.date('Ymd His',time()));
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//重新发货
	function Recreate($data){
		return self::getResult(1,'','该物流商不支持重新发货');
	}
	
	//查询订单状态
	private function selectStatus($packageId,$url,$AccessToken){
		$url=str_replace("{t}", $packageId, $url);
		$header[] = 'Content-Type: application/json; charset=utf-8';
		$header[] = 'Authorization: Bearer '.$AccessToken;
		$response_json=Helper_Curl::get($url,null, $header);
		$response=json_decode($response_json,true);
		
		return $response;
	}
	
	/**
	 * 获取海外仓库存列表
	 * 
	 * @param 
	 * 			$data['accountid'] 			表示账号小老板对应的账号id
	 * 			$data['warehouse_code']		表示需要的仓库ID
	 * @return 
	 */
	function getOverseasWarehouseStockList($data = array()){
		$PageIndex = 0;   //当前页码
		$PageSize = 10;    //分页大小
		$warehouseId = $data['warehouse_code'];
		$resultStockList = array();
		
		//帐号token信息
		$account_params = $data['api_params'];
		//验证token
		if(empty($account_params['AccessToken']) || strtotime(date("Y-m-d H:i:s"))>$account_params['next_time']){
			//重新获取token
			$account = SysCarrierAccount::findOne(['id'=>$data['accountid']]);
			$result=CarrierAPIHelper::getChukouyiAccesstoken($account);
			if($result['success']){
				self::$AccessToken=$result['data']['AccessToken'];
			}
			else{
				return self::getResult(1,'',$result['message']);
			}
		}
		else
			self::$AccessToken=$account_params['AccessToken'];
		
		//循环翻页效果
		while (true){
			$header = array();
			$header[] = 'Content-Type: application/json; charset=utf-8';
			$header[] = 'Authorization: Bearer '.self::$AccessToken;
			$ret = Helper_Curl::get("https://openapi.chukou1.cn/v1/storageSkus?warehouseId=$warehouseId&PageIndex=$PageIndex&PageSize=$PageSize",null, $header);
			$ret = json_decode($ret, true);
			
			if(empty($ret['Skus']))
				break;
			if($PageIndex>100)
				break;
			$PageIndex++;
			
			foreach ($ret['Skus'] as $valList){
				$resultStockList[$valList['Sku']] = array(
					'sku'=>$valList['Sku'],
					'productName'=>$valList['DeclareName'],
					'stock_actual'=>intval($valList['DeclareValue']),	//实际库存
					'stock_reserved'=>0,	//占用库存
					'stock_pipeline'=>0,	//在途库存
					'stock_usable'=>intval($valList['DeclareValue']),	//可用库存
					'warehouse_code'=>$warehouseId		//仓库代码
				);
			}
		}
		
		return self::getResult(0, $resultStockList ,'');
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
		$result = array('is_support'=>0,'error'=>1);
	
// 		try{
	
// // 			$url = self::$wsdl3."outbound/misc/list-all-warehouse";
// 			$url = self::$wsdl."/warehouses";
// // 			$request = array(
// // 					'token' => $data['token'],//'887E99B5F89BB18BEA12B204B620D236',
// // 					'ErpToken' => self::$ErpToken,	//v3是直接加一个ErpToken参数，其他不变；
// // 					'user_key' => $data['user_key'],//'wr5qjqh4gj',
// // 			);
// 			$response = $this->submitGate->mainGate($url, null, 'restfull', 'GET');
			
// 			if($response['error'] == 0){
// 				$response = $response['data'];
// 				if(!empty($response)){
// 					$response = json_decode($response, true);
// 					$response = $response['meta']['code'];
// 					if($response == '200'){
// 						$result['error'] = 0;
// 					}
// 				}
// 			}
			
// 		}catch(CarrierException $e){
// 		}
	
		return $result;
	}
}