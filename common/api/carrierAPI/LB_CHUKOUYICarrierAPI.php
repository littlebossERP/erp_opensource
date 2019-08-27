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
| Create Date: 2014-08-11
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierException;
use Qiniu\json_decode;
use common\helpers\Helper_Curl;

/**
 +------------------------------------------------------------------------------
 * 出口易物流接口对接
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		common\api\carrierAPI
 * @subpackage  Exception
 * @author		qfl <772821140@qq.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_CHUKOUYICarrierAPI extends BaseCarrierAPI
{
	/*
	* api_key = wr5qjqh4gj
	* token = 887E99B5F89BB18BEA12B204B620D236
	*/
	static private $ErpToken = '';// 旧版本接口用到 已废弃
	static private $token = '887E99B5F89BB18BEA12B204B620D236';
	static private $wsdl = ''; //提交订单
	static private $wsdl2 = '';   //打印
	static private $wsdl3 = '';  //获取订单状态
	static private $AccessToken = '';
	private $submitGate = null;
	public function __construct(){		
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			self::$wsdl = 'https://openapi.chukou1.cn/v1/directExpressOrders';   
			self::$wsdl2 = 'https://openapi.chukou1.cn/v1/directExpressOrders/label';
			self::$wsdl3 = 'https://openapi.chukou1.cn/v1/directExpressOrders/{t}/status';
			self::$ErpToken = '';
		}else{
// 			self::$wsdl = 'http://demo.chukou1.cn/client/ws/v2.1/ck1.asmx?WSDL';
			self::$wsdl = 'https://openapi-release.chukou1.cn/v1/directExpressOrders';
			self::$wsdl2 = 'https://openapi-release.chukou1.cn/v1/directExpressOrders/label';
			self::$wsdl3 = 'https://openapi-release.chukou1.cn/v1/directExpressOrders/{t}/status';
			self::$ErpToken = '';
		}
		$this->submitGate = new SubmitGate();
		
	}
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param $data		包裹及物流商相关参数(多个包裹)
	 +----------------------------------------------------------
	 * @return	apiResult 
	 * array(
	 *	'key为shipping_method' => array(
	 *		'key为delivery_id' => self::getResult(0, '此处为获取的tracking_no', ''),
	 *		'key为delivery_id' => self::getResult(非0的errorCode, '', '错误信息')
	 *	)
	 * )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/08/07				初始化
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
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 100,
							'consignee_address_line2_limit' => 100,
							// 							'consignee_address_line3_limit' => 100,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 30
			);
	
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
	
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
	
			//用户在确认页面提交的数据
			$e = $data['data'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$service_carrier_params = $service->carrier_params;
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

			$totalWeight = 0;
			$productList = [];
			$total = count($e['DeclarePieces']);
			for($k=0;$k<$total;$k++){
				$weight = empty($e['weight'][$k])?200:$e['weight'][$k];
				$quantity = empty($e['DeclarePieces'][$k])?0:intval($e['DeclarePieces'][$k]);				
				$Skus[]=array(
						// 'SKU' =>$v['sku'];
						'DeclareValue' => empty($e['DeclaredValue'][$k])?0:$e['DeclaredValue'][$k],// 商品申报价 报关信息
						'Weight' => $weight, //商品重量,
						'Quantity' => $quantity,
						'DeclareNameEn' => $e['EName'][$k],// 商品申报 英文名
						'DeclareNameCn' => $e['Name'][$k],// 商品申报 中文名
// 						'ProductName'=>$e['EName'][$k],
// 						'Price'=>empty($e['DeclaredValue'][$k])?0:$e['DeclaredValue'][$k],
				);
				
				//循环算出所有商品的总重量
				$totalWeight += $weight*$quantity;
			}
	
			$packing_empty_count = 0;
			foreach ($e['packing'] as $k=>$value) {
				if(empty($value)){
					$packing_empty_count++;
					continue;
				}
				$e['packing'][$k] = (int)$value;
			}
			//长、宽、高没有填写完整的话，写默认值1，出口易将重新核实
			if($packing_empty_count != 0){
				$e['packing']['Length'] = $e['packing']['Width'] = $e['packing']['Height'] = 1;
			}
			
			$ShipToAddress=array(
					"Country"=>$o['consignee_country_code'],	//国家
					"Province"=>$o['consignee_province'],	//省份
					"City"=>$o['consignee_city'],	//城市
					"Street1"=>$addressAndPhone['address_line1'],	//街道1
					"Postcode"=>$o['consignee_postal_code'],	//邮政编码,对格式有验证，必须为真实的邮政编码！
					"Contact"=>$o['consignee'],		//联系人
					"Phone"=>$addressAndPhone['phone1'],	//手机
					"Email"=>$o['consignee_email'],	//电子邮箱
			);
			
			$Package=array(
					"PackageId"=>$customer_number,
					"ServiceCode"=>$service->shipping_method_code,
					"ShipToAddress"=>$ShipToAddress,
					"Weight"=>$totalWeight, //商品重量
					"Length"=>$e['packing']['Length'],
					"Width"=>$e['packing']['Width'],
					"Height"=>$e['packing']['Height'],
					"Skus"=>$Skus,
// 					"SellPrice"=>"",
// 					"SellPriceCurrency"=>"",
// 					"SalesPlatform"=>"",
					"ImportTrackingNumber"=>$e['trackcode'],
					"Custom"=>$e['custom'],
					"Remark"=>$e['DeclareNote'],
			);

			$request=array(
					"MerchantId"=>"",  //所属商家Id
					"Location"=>$service_carrier_params['location'], //处理点 如不填则使用商家默认
					"Package"=>$Package,
					"Remark"=>"",
			);

			//数据组织完成 准备发送
			#########################################################################
			\Yii::info('LB_CHUKOUYI,puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			$header = array();
			$header[] = 'Content-Type: application/json; charset=utf-8';
			$header[] = 'Authorization: Bearer '.self::$AccessToken;
			$response2=Helper_Curl::post(self::$wsdl,json_encode($request), $header);
			$response=Helper_Curl::$last_post_info;
			\Yii::info('LB_CHUKOUYI,puid:'.$puid.',response,order_id:'.$order->order_id.' '.json_encode($response).' message:'.json_encode($response2),"carrier_api");

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
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_DELIVERY,null,['OrderSign'=>$customer_number]);
								
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!请获取跟踪号.参考号:'.$customer_number);
			}
			else{
				return self::getResult(1,'',Helper_Curl::$last_error);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//旧接口
	public function getOrderNO2($data){
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
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 100,
							'consignee_address_line2_limit' => 100,
// 							'consignee_address_line3_limit' => 100,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 30
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
				
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);

			//用户在确认页面提交的数据
			$e = $data['data'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$service_carrier_params = $service->carrier_params;
			$account = $info['account'];
			//获取到帐号中的认证参数
			$a = $account->api_params;

			$totalWeight = 0;
			$productList = [];
			$total = count($e['DeclarePieces']);
			for($k=0;$k<$total;$k++){
				$weight = empty($e['weight'][$k])?200:$e['weight'][$k];
				$quantity = empty($e['DeclarePieces'][$k])?0:intval($e['DeclarePieces'][$k]);
				$productList[] = array(
					// 'SKU' =>$v['sku'];
					'DeclareValue' => empty($e['DeclaredValue'][$k])?0:$e['DeclaredValue'][$k],// 商品申报价 报关信息
					'Weight' => $weight, //商品重量,
					'Quantity' => $quantity,
					'CustomsTitleEN' => $e['EName'][$k],// 商品申报 英文名
					'CustomsTitleCN' => $e['Name'][$k],// 商品申报 中文名
					// 'TrackCode' =>$puid.$o['order_id']
				);
				//循环算出所有商品的总重量
				$totalWeight += $weight*$quantity;
			}
				
			$packing_empty_count = 0;
			foreach ($e['packing'] as $k=>$value) {
				if(empty($value)){
					$packing_empty_count++;
					continue;
				}
				$e['packing'][$k] = (int)$value;
			}
			//长、宽、高没有填写完整的话，写默认值1，出口易将重新核实
			if($packing_empty_count != 0){
				$e['packing']['Length'] = $e['packing']['Width'] = $e['packing']['Height'] = 1;
			}
			
			/*//多条商品信息列表  ExpressAddPackageNew 接口会将包裹添加到已经存在的还没提审的订单中，就会出现几张包裹在一个订单里面
			$request = array(
				'request' => array(
					'Token'          => $a['token'].':'.self::$ErpToken,//v2是在原来放客户Token的参数上，增加ErpToken，格式如下：{客户Token}:{ErpToken}
					'UserKey'        => $a['userkey'],
					'ExpressType'    => 'UNKNOWN',//固定值
					'ExpressTypeNew' => $service->shipping_method_code,
					'IsTracking'     => True,//固定值
					'Location'       => $service_carrier_params['location'],//处理点: 广州’GZ’, 深圳’SZ’, 上海’SH’,不填为默认处理点
					// 'Point4Delivery' => $shippInfo['Location'],//$carrierInfo['Point4Delivery'],//交货点: 广州’GZ’, 深圳’SZ’, 上海’SH’, 不填为默认交货点
					'PickupType'     => 0,//固定值
					'PackageDetail'  => array(
						'Custom'		=>$customer_number,
						'Status'        => 'Initial',
						'TrackCode'		=> $e['trackcode'],
						'Weight'        => $totalWeight, //商品重量
						'ProductList'   => $productList,
						'Packing'       => $e['packing'],//包装规格
						'ShipToAddress' => array(
							'City'            => $o['consignee_city'],	//城市	
							'Contact'         => $o['consignee'],		//联系人
							'Country'         => $o['consignee_country_code'],	//国家
							'Email'           => $o['consignee_email'],	//电子邮箱
							'Phone'           => $addressAndPhone['phone1'],	//手机
							'PostCode'        => $o['consignee_postal_code'],	//邮政编码,对格式有验证，必须为真实的邮政编码！
							'Province'        => $o['consignee_province'],	//省份
							'Street1'         => $addressAndPhone['address_line1'],	//街道1
							'Street2'         => $addressAndPhone['address_line2']		//街道2
						),
						'Remark'	=> $e['DeclareNote']
					)
				)
			);*/

			
			//多条商品信息列表新
			$PackageLst[] = array(
					'Custom'		=>$customer_number,
					'ShipToAddress' => array(
							'City'            => $o['consignee_city'],	//城市
							'Contact'         => $o['consignee'],		//联系人
							'Country'         => $o['consignee_country_code'],	//国家
							'Email'           => $o['consignee_email'],	//电子邮箱
							'Phone'           => $addressAndPhone['phone1'],	//手机
							'PostCode'        => $o['consignee_postal_code'],	//邮政编码,对格式有验证，必须为真实的邮政编码！
							'Province'        => $o['consignee_province'],	//省份
							'Street1'         => $addressAndPhone['address_line1'],	//街道1
							'Street2'         => $addressAndPhone['address_line2']		//街道2
					),
					'Packing'       => $e['packing'],//包装规格
					'Weight'        => $totalWeight, //商品重量
					'Status'        => 'Initial',
					'Remark'	=> $e['DeclareNote'],
					'ProductList'   => $productList,
			);
			
			$request = array(
					'request' => array(
							'Token'          => $a['token'].':'.self::$ErpToken,//v2是在原来放客户Token的参数上，增加ErpToken，格式如下：{客户Token}:{ErpToken}
							'UserKey'        => $a['userkey'],
							'ExpressTypeNew' => $service->shipping_method_code,
							'Submit'=>False,
							'OrderDetail'  => array(
									'ExpressType'    => 'UNKNOWN',//固定值
									'PickupType'=>'0',   //已弃用，填0即可
									'Location'       => $service_carrier_params['location'],//处理点: 广州’GZ’, 深圳’SZ’, 上海’SH’,不填为默认处理点
									'IsTracking'     => 'true',//固定值
									'PackageList' => $PackageLst,
									),
							),
			);			
			
			
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info('LB_CHUKOUYI,puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			//$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'ExpressAddPackageNew');
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'ExpressAddOrderNew');
			\Yii::info('LB_CHUKOUYI,puid:'.$puid.',response,order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");

			if($response['error']){return $response;}
			$response = $response['data'];
			$response = $response->ExpressAddOrderNewResult;
			if($response->Ack == 'Failure'){
				throw new CarrierException($response->Message);
			}
			if($response->Ack == 'Success'){
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				$ItemSign=$response->Packages->FeedBackItem->ItemSign;
				$r = CarrierAPIHelper::orderSuccess($order,$service,$ItemSign,OdOrder::CARRIER_WAITING_DELIVERY,null,['OrderSign'=>$response->OrderSign,'ItemSign'=>$ItemSign]);

				$print_param = array();
				$print_param['carrier_code'] = $service->carrier_code;
				$print_param['api_class'] = 'LB_CHUKOUYICarrierAPI';
				$print_param['token'] = $a['token'];
				$print_param['userkey'] = $a['userkey'];
				$print_param['customer_number'] = $ItemSign;
				$print_param['carrier_params'] = $service->carrier_params;
				
				try{
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $ItemSign, $print_param);
				}catch (\Exception $ex){
				}
				
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$response->OrderSign.',处理号'.$ItemSign);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param $data		包裹及物流商相关参数(多个包裹)
	 +----------------------------------------------------------
	 * @return	apiResult 
	 *|array(
	 *	'key为shipping_method' => array(
	 *		'key为delivery_id' => self::getResult(0, '此处为获取的tracking_no', ''),
	 *		'key为delivery_id' => self::getResult(非0的errorCode, '', '错误信息')
	 *	)
	 * )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/08/07				初始化
	 +----------------------------------------------------------
	**/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消订单。');
		try{
			if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
				$url = 'http://api.chukou1.cn/v3/direct-express/package/delete';
			}else{
				$url = 'http://demo.chukou1.cn/v3/direct-express/package/delete';
			}
			$order = $data['order'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			$returnNo = $shipped->return_no;
			$params = $account->api_params;
			$token = $params['token'];
			$userKey = $params['userkey'];
				$request = array(
					'token' => $token,
					'user_key' => $userKey,
					'package_sn' => $shipped->customer_number,
					'ErpToken' => self::$ErpToken,	//v3是直接加一个ErpToken参数，其他不变；
				);
				$response = $this->submitGate->mainGate($url, $request, 'restfull', 'POST');
				if($response['error']){
					return $response;
				}	
				$response = $response['data'];
				$response = json_decode($response, true);
				if($response['meta']['code'] == '200'){
					$shipped->delete();
					$order->carrier_step = OdOrder::CARRIER_CANCELED;
					$order->save();
					return BaseCarrierAPI::getResult(0, '', '订单已取消!时间:'.date('Ymd His',time()));
				}
				else{
					throw new CarrierException($response['meta']['description']);
				}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
		
	}
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param $data		包裹及物流商相关参数(多个包裹)
	 +----------------------------------------------------------
	 * @return	apiResult 
	 *|array(
	 *	'key为shipping_method' => array(
	 *		'key为delivery_id' => self::getResult(0, '此处为获取的tracking_no', ''),
	 *		'key为delivery_id' => self::getResult(非0的errorCode, '', '错误信息')
	 *	)
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/08/07				初始化
	 +----------------------------------------------------------
	**/
	public function doDispatch($data){
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
	private function doDispatch2($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
			$returnNo = $shipped->return_no;

			//设置userkey 和 token
			$params = $account->api_params;
			$submit = self::checkSubmit($params['token'], $params['userkey'], $returnNo['ItemSign']);
			if($submit){
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return self::getResult(0,'', '该订单曾交运成功！');
			}
			//最终请求提交参数
			$request = array(
				'request' => array(
					'Token' => $params['token'].':'.self::$ErpToken,	//系统验证字符串+//v2是在原来放客户Token的参数上，增加ErpToken，格式如下：{客户Token}:{ErpToken}
					'UserKey' => $params['userkey'],	//第三方验证字符串
					'ActionType' =>	'Submit',	//操作类型
					'OrderSign' => $returnNo['OrderSign'],	//出库单单号，形如ETST12040600005 ***********
				)
			);
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'ExpressCompleteOrder');
			if($response['error'])return $response;
			$response = $response['data'];
			$response = $response->ExpressCompleteOrderResult;
			if($response->Ack == 'Failure'){
				throw new CarrierException($response->Message);
			}
			if($response->Ack == 'Success'){
				//暂时没有跟踪号返回
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return self::getResult(0,'', '订单交运成功！');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param $data		包裹及物流商相关参数(多个包裹)
	 +----------------------------------------------------------
	 * @return	apiResult 
	 * array(
	 *	'key为shipping_method' => array(
	 *		'key为delivery_id' => self::getResult(0, '此处为获取的tracking_no', ''),
	 *		'key为delivery_id' => self::getResult(非0的errorCode, '', '错误信息')
	 *	)
	 * )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/08/07				初始化
	 +----------------------------------------------------------
	**/
	public function getTrackingNO($data){		
		try{
			$order = $data['order'];
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
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

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
			$returnNo = $shipped->return_no;

			if(empty($shipped->customer_number))
				return self::getResult(1, '', '没有处理号，请检查订单是否已上传');

			$url=str_replace("{t}", $shipped->customer_number, self::$wsdl3);
			$header[] = 'Content-Type: application/json; charset=utf-8';
			$header[] = 'Authorization: Bearer '.self::$AccessToken;
			$response_json=Helper_Curl::get($url,null, $header);
			$response=json_decode($response_json,true);
						
			if(isset($response['Status']) && $response['Status']=="Created"){
				$return_no=$shipped->return_no;
				$return_no['ItemSign']=empty($response['Ck1PackageId'])?'':$response['Ck1PackageId'];
				$shipped->return_no=$return_no;
				$shipped->save();
				
				$user=\Yii::$app->user->identity;
				$puid = $user->getParentUid();
				$print_param = array();
				$print_param['carrier_code'] = $service->carrier_code;
				$print_param['api_class'] = 'LB_CHUKOUYICarrierAPI';
				$print_param['customer_number'] = $shipped->customer_number;
				$print_param['ItemSign'] = $response['Ck1PackageId'];
				$print_param['carrier_params'] = $service->carrier_params;
				$print_param['AccessToken'] = self::$AccessToken;
				
				try{
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $shipped->customer_number, $print_param);
				}catch (\Exception $ex){
				}
					
				if(empty($response['TrackingNumber'])){
					return self::getResult(1, '', "出口易包裹 {$returnNo['OrderSign']} 未生成跟踪号，请稍后再试或联系出口易客服");
				}
				else{					
					$TrackingNumber=$response['TrackingNumber'];
					$shipped->tracking_number = $TrackingNumber;
					$shipped->save();
					$order->tracking_number = $TrackingNumber;
					$order->save();
										
					return self::getResult(0,'', '获取物流号成功！<br/>跟踪号:'.$TrackingNumber);
				}
			}
			else if(isset($response['Status']) && $response['Status']=="Creating"){
				return self::getResult(1,'','物流处理订单中，请稍后再试或联系出口易客服');
			}
			else{
				if(!empty($response['CreateFailedReason']['ExtendMessage'])){
					return self::getResult(1,'',$response['CreateFailedReason']['ExtendMessage']);
				}
				else if(!empty($response['UnShippedReason']['ExtendMessage'])){
					return self::getResult(1,'',$response['UnShippedReason']['ExtendMessage']);
				}
				else{
					if(empty($response['Errors'][0]['Message']))
						return self::getResult(1,'',"操作失败，请联系客服e1");
					else{
						if(strpos($response['Errors'][0]['Message'],'直发包裹')!== false && strpos($response['Errors'][0]['Message'],'不存在')!== false){
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
							return self::getResult(1,'',$response['Errors'][0]['Message']);
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
			$url="https://openapi.chukou1.cn/v1/directExpressOrders/Ck1PackageId";
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
	private function getTrackingNO2($data){
		try{
			if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
				$url = 'http://api.chukou1.cn/v3/system/tracking/get-tracking';
			}else{
				$url = 'http://demo.chukou1.cn/v3/system/tracking/get-tracking';
			}
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
	
			$params = $account->api_params;
			$token = $params['token'];
			$userKey = $params['userkey'];
	
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
				
			$returnNo = $shipped->return_no;
			// 			v3是直接加一个ErpToken参数，其他不变；
			$request = "?token=$token&ErpToken=".self::$ErpToken."&user_key=$userKey&Package_sn=".$shipped->customer_number;
			// 			$request = "?token=$token&user_key=$userKey&Package_sn=".$shipped->customer_number;
			$response = $this->submitGate->mainGate($url, $request, 'curl', 'GET');
			if($response['error'])return $response;
			$response = $response['data'];
			$response = json_decode($response, true);
			if($response['meta']['code'] == '200' && $response['body']['track_no']){
				$shipped->tracking_number = $response['body']['track_no'];
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				return self::getResult(0,'', '获取物流号成功！<br/>跟踪号:'.$response['body']['track_no']);
			}
			else if($response['meta']['code'] == '200'){
				return self::getResult(1, '', "出口易包裹 {$shipped->customer_number} 未生成跟踪号，请等待或联系出口易客服");
			}
			else{
				return self::getResult(1, '', $response['meta']['description']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//用来判断是否支持打印
	public static function isPrintOk(){
		return true;
	}


	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param $data		包裹及物流商相关参数(多个包裹)(不同国家要
	 *					分开打印, 最多80个)
	 +----------------------------------------------------------
	 * @return	apiResult 
	 *|array(
	 *	'key为shipping_method' => array(
	 *		'key为delivery_id' => self::getResult(0, '此处为获取的tracking_no', ''),
	 *		'key为delivery_id' => self::getResult(非0的errorCode, '', '错误信息')
	 *	)
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hxl		2014/08/07				初始化
	 +----------------------------------------------------------
	**/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆'); 
			$puid = $user->getParentUid();
			
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
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
			
			$service = $info['service'];
			$printFormat = $service['carrier_params']['printFormat'];
			$printContent = $service['carrier_params']['content'];
			
			//兼容新旧打印格式
			if($printFormat=="classic_a4")
				$printFormat="ClassicA4";
			else if($printFormat=="classic_label")
				$printFormat="ClassicLabel";
			
			if($printContent=="address")
				$printContent="Address";
			else if($printContent=="address_costoms")
				$printContent="AddressCostoms";
			else if($printContent=="address_costoms_split")
				$printContent="AddressCostomsSplit";
			else if($printContent=="address_remark")
				$printContent="AddressRemark";
			else if($printContent=="address_customs_remark_split")
				$printContent="AddressCustomsRemarkSplit";
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			//用于兼容旧订单
			$PackageIds=array();
			foreach ($data as $k => $v) {
				$order_ = $v['order'];
				$checkResult = CarrierAPIHelper::validate(0,1,$order_);
				$shipped = $checkResult['data']['shipped'];
				$returnNo = $shipped->return_no;
				if(isset($returnNo['ItemSign']) && isset($returnNo['OrderSign'])){
					$IdType="Ck1PackageId";
					$PackageIds[]=$returnNo['ItemSign'];
				}
				else{
					$IdType="PackageId";
					$PackageIds[]=$order->customer_number;
				}
			}
			
			$request=array(
					"MerchantId"=>"",
					"PackageIds"=>$PackageIds,
					"PrintFormat"=>$printFormat,
					"PrintContent"=>$printContent,
					"CustomPrintOptions"=>array("Custom"),
					"IdType"=>$IdType,
			);

			$header[] = 'Content-Type: application/json; charset=utf-8';
			$header[] = 'Authorization: Bearer '.self::$AccessToken;
			$response_json=Helper_Curl::post(self::$wsdl2,json_encode($request), $header);
			
			$response=json_decode($response_json,true);
			
			if(!empty($response['Errors'][0]['Code']))
				return self::getResult(1,null,$response['Errors'][0]['Message']);

			$response = base64_decode($response['Label']);
			if(strlen($response)<1000){
				$response = json_decode($response,true);
				foreach($data as $v){
					$order = $v['order'];
					$order->carrier_error = "获取打印面单失败";
					$order->save();
				}
				return self::getResult(1, '', "获取打印面单失败");
			}else{
				//如果成功返回pdf 则保存到本地
				foreach($data as $v){
					$order = $v['order'];
					// 					$order->is_print_carrier = 1;
					$order->print_carrier_operator = $puid;
					$order->printtime = time();
					$order->save();
				}
				$pdfurl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code);
				return self::getResult(0,['pdfUrl'=>$pdfurl],'物流单已生成,请点击页面中打印按钮');
			}

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//旧接口
	private function doPrint2($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
	
			if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
				$url = 'http://api.chukou1.cn/v3/direct-express/package/print-label';
			}else{
				$url = 'http://demo.chukou1.cn/v3/direct-express/package/print-label';
			}
	
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
	
			$params = $account->api_params;
			$token = $params['token'];
			$userKey = $params['userkey'];
			//取得打印尺寸
			$printFormat = $service['carrier_params']['printFormat'];
			$printContent = $service['carrier_params']['content'];
			//查询出订单操作号
			$package_sn = '';
			foreach ($data as $k => $v) {
				$order = $v['order'];
				$package_sn .= '&package_sn='.$order->customer_number;
			}
			##############################################################################
			//GET提交数据
			// 			v3是直接加一个ErpToken参数，其他不变；
			$requestData = "?token=$token&ErpToken=".self::$ErpToken."&user_key=$userKey&format=$printFormat&content=$printContent".$package_sn;
			// 			$requestData = "?token=$token&user_key=$userKey&format=$printFormat&content=$printContent".$package_sn;
			//提交请求
			$response = $this->submitGate->mainGate($url, $requestData, 'curl', 'GET');
			//如果CURL提交错误
			if($response['error'])return $response;
			//出口易返回数据
			$response = $response['data'];
			if(strlen($response)<1000){
				$response = json_decode($response,true);
				foreach($data as $v){
					$order = $v['order'];
					$order->carrier_error = $response['meta']['description'];
					$order->save();
				}
				return self::getResult(1, '', $response['meta']['description']);
			}else{
				//如果成功返回pdf 则保存到本地
				foreach($data as $v){
					$order = $v['order'];
					// 					$order->is_print_carrier = 1;
					$order->print_carrier_operator = $puid;
					$order->printtime = time();
					$order->save();
				}
				$pdfurl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code);
				return self::getResult(0,['pdfUrl'=>$pdfurl],'物流单已生成,请点击页面中打印按钮');
			}
			}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
		}
	
	/*
	 * 用来确定打印完成后 订单的下一步状态
	 */
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_WAITING_GETCODE;
	}

	//订单重新发货
	public function Recreate($data){return self::getResult(1,'', '该物流商不支持此操作');}

	//获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			// TODO carrier user account @XXX@
			$AccessToken='@XXX@';
			$a = $account->api_params;
			\Yii::info('LB_CHUKOUYI,,account: '.json_encode($a),"carrier_api");
			//验证token
			if(empty($a['AccessToken']) || strtotime(date("Y-m-d H:i:s"))>$a['next_time']){
				//重新获取token
				$result=CarrierAPIHelper::getChukouyiAccesstoken($account);
				if($result['success']){
					$AccessToken=$result['data']['AccessToken'];
				}
				else{
					return self::getResult(1,'',$result['message']);
				}
			}
			else
				$AccessToken=$a['AccessToken'];
			$token=$AccessToken;
			
			$header[] = 'Content-Type: application/json; charset=utf-8';
			$header[] = 'Authorization: Bearer '.$token;
			$response_json=Helper_Curl::get('https://openapi.chukou1.cn/v1/directExpressServices',null, $header);

			$response=json_decode($response_json,true);

			$channelStr="";
			foreach ($response as $responseone){
				$channelStr .= $responseone['ServiceCode'].':'.$responseone['ServiceName'].';';
			}

			if(empty($channelStr)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $channelStr, '');
			}
// 			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//列出所有可用专线服务
	public function listAllService(){
		$url = 'http://demo.chukou1.cn/v3/direct-express/misc/list-all-service';
		//$url = 'http://api.chukou1.cn/v3/direct-express/misc/list-all-service';
		$token = self::$token;
		$user_key = 'wr5qjqh4gj';
// 		v3是直接加一个ErpToken参数，其他不变；
		$requestData = ['token'=>$token , 'ErpToken' => self::$ErpToken ,'user_key'=>$user_key];
// 		$requestData = "?token=$token&user_key=$user_key";
		$response = $this->submitGate->mainGate($url, $requestData, 'restfull');
		if($response['error']) return $response;
		$response = $response['data'];
		$response = json_decode($response, true);
		//print_r($response);exit;//输出所有
		foreach ($response['body'] as $res){//获取可用来更新运输服务格式的信息
			echo $res['symbol_code'].':'.$res['name'].';';
		}
	}
	//列出所有的处理点
	public function listLocation(){
		$url = 'http://demo.chukou1.cn/v3/system/geography/list-location';
		$token = '887E99B5F89BB18BEA12B204B620D236';
		$user_key = 'wr5qjqh4gj';
// 		v3是直接加一个ErpToken参数，其他不变；
		$requestData = "?token=$token&ErpToken=".self::$ErpToken."&user_key=$user_key";
// 		$requestData = "?token=$token&user_key=$user_key";
		$response = $this->submitGate->mainGate($url, $requestData, 'curl', 'GET');
		if($response['error']) return $response;
		$response = $response['data'];
		$response = json_decode($response, true);
		$str = '';
		foreach($response['body'] as $v){
			$str  .= $v['code'].':'.$v['name'].';';
		}
		echo $str;//die;
	}
	public function checkSubmit($token,$user_key,$sn){
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			$url = 'http://api.chukou1.cn/v3/system/tracking/get-tracking';
		}else{
			$url = 'http://demo.chukou1.cn/v3/system/tracking/get-tracking';
		}
		$requestData = "?token=$token&ErpToken=".self::$ErpToken."&user_key=$user_key&Package_sn=".$sn;
	
		$response = $this->submitGate->mainGate($url, $requestData, 'curl', 'GET');
		if($response['error']) return false;
		$response = json_decode($response['data'], true);
		$code = $response['meta']['code'];
		if($code == '400'){
			return false;
		}
		return true;
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

			self::$AccessToken=empty($print_param['AccessToken'])?"":$print_param['AccessToken'];
				
			$printFormat="ClassicLabel";
			$printContent = empty($print_param['carrier_params']['content']) ? 'Address' : $print_param['carrier_params']['content'];
			if($printContent=="address")
				$printContent="Address";
			else if($printContent=="address_costoms")
				$printContent="AddressCostoms";
			else if($printContent=="address_costoms_split")
				$printContent="AddressCostomsSplit";
			else if($printContent=="address_remark")
				$printContent="AddressRemark";
			else if($printContent=="address_customs_remark_split")
				$printContent="AddressCustomsRemarkSplit";

			$IdType="Ck1PackageId";
			$PackageIds=$print_param['ItemSign'];

				
			$request=array(
					"MerchantId"=>"",
					"PackageIds"=>array($PackageIds),
					"PrintFormat"=>$printFormat,
					"PrintContent"=>$printContent,
					"CustomPrintOptions"=>array("Custom"),
					"IdType"=>$IdType,
			);
			
			$header[] = 'Content-Type: application/json; charset=utf-8';
			$header[] = 'Authorization: Bearer '.self::$AccessToken;
			$response_json=Helper_Curl::post(self::$wsdl2,json_encode($request), $header);
				
			$response=json_decode($response_json,true);
			
			if(!empty($response['Errors'][0]['Code']))
				return self::getResult(1,null,$response['Errors'][0]['Message']);
			
			//出口易返回数据
			$response = base64_decode($response['Label']);

			if(strlen($response)<1000){
				$response = json_decode($response,true);
				return ['error'=>1, 'msg'=>$response['meta']['description'], 'filePath'=>''];
			}else{
				$pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
				return $pdfPath;
			}
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
	//旧接口
	public function getCarrierLabelApiPdf2($SAA_obj, $print_param){
		try {
			if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
				$url = 'http://api.chukou1.cn/v3/direct-express/package/print-label';
			}else{
				$url = 'http://demo.chukou1.cn/v3/direct-express/package/print-label';
			}
			
			$puid = $SAA_obj->uid;
	
			//设置用户密钥Token
			$token = $print_param['token'];
			$userKey = $print_param['userkey'];
			
			//取得打印尺寸
			$printFormat = 'classic_label';
			$printContent = empty($print_param['carrier_params']['content']) ? 'address' : $print_param['carrier_params']['content'];
			
			$package_sn = '&package_sn='.$print_param['customer_number'];
			
			$requestData = "?token=$token&ErpToken=".self::$ErpToken."&user_key=$userKey&format=$printFormat&content=$printContent".$package_sn;
			
			//提交请求
			$response = $this->submitGate->mainGate($url, $requestData, 'curl', 'GET');
			//如果CURL提交错误
			if($response['error']){
				return ['error'=>1, 'msg'=>$response, 'filePath'=>''];
			}
			//出口易返回数据
			$response = $response['data'];
			if(strlen($response)<1000){
				$response = json_decode($response,true);
				return ['error'=>1, 'msg'=>$response['meta']['description'], 'filePath'=>''];
			}else{
				$pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
				return $pdfPath;
			}
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
	/**
	 * 用户通过调用该方法获取指定专线（直发）订单的包裹信息。
	 * @param String $token 开发者验证字符串
	 * @param String $userkey 用户验证字符串
	 * @param String $custom 客户参考号
	 * @param String $itemSign 包裹处理号
	 * @return Ambigous <\common\helpers\$result, multitype:错误代码 unknown 错误消息 , multitype:>
	 */
	public function ExpressGetPackage($token,$userkey,$custom,$itemSign){
// 		//本地是测试环境，转换为正式环境
// 		self::$wsdl = 'http://yewu.chukou1.cn/client/ws/v2.1/ck1.asmx?WSDL';
// 		self::$ErpToken = 'F16F32207ED395BC51F11FB05BF1542830A16D29074FB133';
// 		
		$request = array(
				'request' => array(
						'Token' => $token.':'.self::$ErpToken,	//系统验证字符串+//v2是在原来放客户Token的参数上，增加ErpToken，格式如下：{客户Token}:{ErpToken}
						'UserKey' => $userkey,	//第三方验证字符串
						'Custom'=>$custom,
						'ItemSign'=>$itemSign,
				)
		);
		$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'ExpressGetPackage');
		return $response;
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
			
// 			if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
// 				$url = 'http://api.chukou1.cn/v3/direct-express/package/delete';
// 			}else{
// 				$url = 'http://demo.chukou1.cn/v3/direct-express/package/delete';
// 			}
// 			$requestData = ['token'=>$data['token'] , 'ErpToken' => self::$ErpToken ,'user_key'=>$data['user_key']];
// 			// 		$requestData = "?token=$token&user_key=$user_key";
// 			$response = $this->submitGate->mainGate($url, $requestData, 'restfull');
// // 			if($response['error']) return $response;
			
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
	
// 		return $result;
// 	}

	
}
