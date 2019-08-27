<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.littleboss.com All rights reserved.
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-3-12
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use common\api\carrierAPI\BaseCarrierAPI;
use common\api\ebayinterface\config;
use common\helpers\SubmitGate;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\models\SaasEbayUser;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierException;
use Jurosh\PDFMerge\PDFMerger;
use Qiniu\json_decode;
// try{
// 	include dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'PDFMerger'.DIRECTORY_SEPARATOR.'PDFMerger.php';
// }catch(\Exception $e){	
// }
/**
 +------------------------------------------------------------------------------
 * ebay订单E邮宝接口业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		vendors/EpacketCarrierAPI
 * @subpackage  Exception
 * @author		qfl <772821140@qq.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_EPACKETCarrierAPI extends BaseCarrierAPI
{
	//SoapClient实例
	public $soap = null;
	//物流接口
	static public $wsdl = "https://api.apacshipping.sandbox.ebay.com.hk/aspapi/v4/ApacShippingService?wsdl";
	//EBAY验证信息
	static public $pubinfo = array();
	//初始化
	public function __construct(){
		$this->submitGate = new SubmitGate();
		//取得EBAY验证信息
	 	if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			$ebayConfig = config::$token;
			self::$wsdl = 'https://api.apacshipping.ebay.com.hk/aspapi/v4/ApacShippingService?wsdl';
		}else{
			$ebayConfig = config::$tokenSandbox;
			self::$wsdl = 'https://api.apacshipping.sandbox.ebay.com.hk/aspapi/v4/ApacShippingService?wsdl';
		}
		self::$pubinfo['APIDevUserID'] = $ebayConfig['devID'];
		self::$pubinfo['AppID'] = $ebayConfig['appID'];
		self::$pubinfo['AppCert'] = $ebayConfig['certID'];
		self::$pubinfo['Version'] = '4.0.0';
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
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
			$puid = $checkResult['data']['puid'];

			//获取到所需要使用的数据
			$e = $data['data'];
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$a = $account->api_params;
			// file_put_contents('1.txt', print_r($service,1).PHP_EOL.print_r($account,1).PHP_EOL.print_r($e,1).PHP_EOL.print_r($order,1));die;
			
			if(empty($info['senderAddressInfo'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}

			//买家发货及物流取货地址信息
			$OrderDetail = self::getSendAddress($info['senderAddressInfo']);
			$OrderDetail['EMSPickUpType'] = $service['carrier_params']['EMSPickUpType'];
			//上传e邮宝公共信息
			$publicInfo = self::$pubinfo;
			//selleruserid在本地已绑定数据中获取
			$ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$order->selleruserid,'uid'=>$puid])->one();
// 			$ebayuser = SaasEbayUser::find()->one();
			//如果在系统中查询不到绑定信息 则使用用户输入的帐号和token
			if(!$ebayuser)throw new CarrierException('小老板系统中没有绑定该账户，无法获取到token值');
			
			//更改读取开发者信息
			$tmpApiInfo = config::getProductConfig($ebayuser['DevAcccountID']);
			
			$publicInfo['APIDevUserID'] = $tmpApiInfo['devID'];
			$publicInfo['AppID'] = $tmpApiInfo['appID'];
			$publicInfo['AppCert'] = $tmpApiInfo['certID'];
			$publicInfo['Version'] = '4.0.0';
			
			$publicInfo['APISellerUserID'] = $ebayuser->selleruserid;
			$publicInfo['APISellerUserToken'] = $ebayuser->token;
			$publicInfo['MessageID'] = self::getMessageid();
			$publicInfo['Carrier'] = $service['carrier_params']['Carrier'];
			$publicInfo['Service'] = $service->shipping_method_code;
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 126,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 32
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			//用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			//收货人地址信息
			$OrderDetail['ShipToAddress'] = array(
				'Contact'    => $order->consignee,//收货人
				'Company'    => $order->consignee_company,//收货人公司
				'Street'     => $addressAndPhone['address_line1'],
				'District'	 => $order->consignee_district,//区
				'City'       => $order->consignee_city,//城市
				'Province'   => $tmpConsigneeProvince,//省
				'CountryCode'=> $order->consignee_country_code,//国家代码
				'Postcode'   => $order->consignee_postal_code,//邮编
				'Phone'      => $addressAndPhone['phone1'],//电话
				'Email'      => $order->consignee_email//邮箱
			);
			//e邮宝不需要填写 包裹长宽高
			if($service['carrier_params']['Carrier'] != 'CNPOST' ){		
				$OrderDetail['ShippingPackage'] = self::getShippingPackage($service['carrier_params']['Carrier']);
				$OrderDetail['ShippingPackage']['Incoterms'] = isset($service['carrier_params']['Incoterms'])?$s['carrier_params']['Incoterms']:'DDP';
			}
			$oet = OdEbayTransaction::find()->select(['transactionid','itemid'])->where(['order_id'=>$order->order_id])->one();
			$transactionid = isset($oet->transactionid)?$oet->transactionid:'0';
			
			//多条商品信息列表
			foreach($order->items as $k=>$item){
				$tmp_EBayTransactionID = $item['order_source_transactionid'];
				
				if($tmp_EBayTransactionID == ''){
					$tmp_EBayTransactionID = $transactionid;
				}
				
				$tmpItem = array();
				$tmpItem['EBayItemID'] = $item['source_item_id']; //ebay物品号
				$tmpItem['EBayTransactionID'] = $tmp_EBayTransactionID; //交易号,拍卖的商品为0
				$tmpItem['EBayBuyerID'] = $order->source_buyer_user_id; //买家ID
				$tmpItem['SoldQTY'] = $item['ordered_quantity'];//卖出数量
				$tmpItem['PostedQTY'] = $item['quantity'];//寄货数量 不能为0
				$tmpItem['SalesRecordNumber'] = 0; //用户从eBay 上下载的时eBay 销售编号				
				// $tmpItem['SalesRecordNumber'] = $item['order_source_srn']; //用户从eBay 上下载的时eBay 销售编号				
				$tmpItem['OrderSalesRecordNumber'] = 0;
				$tmpItem['EBaySiteID'] = $order->order_source_site_id;//$item['ebayTransaction']['transactionsiteid'];
				$tmpItem['EBayMessage'] = $order->user_message;
				$tmpItem['PaymentDate'] = date('Y-m-d', $order->paid_time);//买家付款日期
				$tmpItem['SoldDate'] = date('Y-m-d', $order->order_source_create_time); //卖出日期
				$tmpItem['SoldPrice'] = $order->grand_total;//卖出总价
				$tmpItem['ReceivedAmount'] = $order->grand_total;//收到金额
				
				//SKU信息
				$tmpItem['SKU']['DeclaredValue'] = $e['DeclaredValue'][$k];// 商品申报价 报关信息
				$tmpItem['SKU']['Weight'] = empty($e['weight'][$k])?'':intval($e['weight'][$k])/1000; //商品重量 KG
				$tmpItem['SKU']['CustomsTitle'] = $e['Name'][$k];// 商品申报 中文名
				$tmpItem['SKU']['CustomsTitleEN'] = $e['EName'][$k];// 商品申报 英文名
				
				
				$tmpItem['SKU']['OriginCountryCode'] = 'CN';//缺
				$tmpItem['SKU']['OriginCountryName'] = 'China';//缺
				
				if(count($order->items)>1){
					if(!isset($OrderDetail['ItemList']['Item'])){
						$OrderDetail['ItemList']['Item'] = array();
					}
					$OrderDetail['ItemList']['Item'][] = $tmpItem;
				}else{
					$OrderDetail['ItemList']['Item'] = $tmpItem;
				}
			}
			$orderDetail = array('OrderDetail'=>$OrderDetail);
			//请求接口数组
			$request['AddAPACShippingPackageRequest'] = $publicInfo + $orderDetail;

			/*==============================================================================*/
			//提交数据
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'AddAPACShippingPackage');
			\Yii::info('LB_EPACKET,puid:'.$puid.',order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
			
			$response_json = json_encode($response);
// 			$response_json = '{"error":0,"data":{"AddAPACShippingPackageResult":{"Version":"4.0.0","Ack":"Success","Message":"AddAPACShippingPackage succeeded","Timestamp":"2017-03-20T19:13:22.888-07:00","InvocationID":"DENGON57I801CSGJ8W024854ZB","TrackCode":"LK194740451CN"}},"msg":""}';
// 			$response_json = '{"error":0,"data":{"AddAPACShippingPackageResult":{"Version":"4.0.0","Ack":"Failure","NotificationList":{"Notification":{"Severity":"Failure","Code":"E16","Message":"Invalid item property (Item with ItemId: 171475474164 TransactionId: 1673441925007 record Already exists)"}},"Timestamp":"2017-03-20T19:13:37.084-07:00","InvocationID":"DENGON57IM00ADQOK603DVEE1I"}},"msg":""}';
			unset($response);
			$response = json_decode($response_json, true);

			if(!isset($response['error'])){
// 				return $response;
				return self::getResult(1,'','请联系小老板客服');
			}
			
			//添加异常判断
			if($response['data'] == ''){
				return self::getResult(1,'',''.(isset($response['msg']) ? $response['msg'] : ''));
			}
			
			$response = $response['data'];
			$response = $response['AddAPACShippingPackageResult'];
			if($response['Ack'] == 'Failure'){
				//假如获取到错误关于亚太平台订单存在相关订单则再试调用接口
				$is_echo_track_code = false;
				
				$str = '';
				if(isset($response['NotificationList']['Notification'][0])){
					foreach($response['NotificationList']['Notification'] as $k=>$v){
						$str .= $v['Message'].'<br/>';
						
						if(isset($v['Code']) && ($v['Code'] == 'E16')){
							$is_echo_track_code = true;
						}
					}
				}else{
					$str = $response['NotificationList']['Notification']['Message'];
					
					if(isset($response['NotificationList']['Notification']['Code']) && ($response['NotificationList']['Notification']['Code'] == 'E16')){
						$is_echo_track_code = true;
					}
				}
				
				if($is_echo_track_code == true){
					$dataGetTrackCode = array();
					
					$dataGetTrackCode['DevAcccountID'] = $ebayuser['DevAcccountID'];
					$dataGetTrackCode['selleruserid'] = $ebayuser->selleruserid;
					$dataGetTrackCode['token'] = $ebayuser->token;
					$dataGetTrackCode['Carrier'] = $service['carrier_params']['Carrier'];
					$dataGetTrackCode['EBayItemID'] = $tmpItem['EBayItemID'];
					$dataGetTrackCode['EBayTransactionID'] = $tmpItem['EBayTransactionID'];
					
// 					$resultGetTrackCode = $this->getAPACShippingTrackCode($dataGetTrackCode);
					
// 					if($resultGetTrackCode['error'] == 0){
// 						$str .= ' <br>亚太平台后台的跟踪号为：'.$resultGetTrackCode['data'].' <br>请到亚太平台后台确认订单是否正常，亚太平台不支持已出库订单补发。如果想上传成功必须到亚太平台后台删除对应的订单。';
// 					}
				}
				
				return self::getResult(1, '', $str);
			}
			if($response['Ack'] == 'Success'){
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				$r = CarrierAPIHelper::orderSuccess($order,$service,$response['TrackCode'],OdOrder::CARRIER_WAITING_DELIVERY,$response['TrackCode']);
				
				$print_param = array();
				$print_param['carrier_code'] = $service->carrier_code;
				$print_param['api_class'] = 'LB_EPACKETCarrierAPI';
				$print_param['selleruserid'] = $order->selleruserid;
				$print_param['customer_number'] = $response['TrackCode'];
				
				try{
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $response['TrackCode'], $print_param);
				}catch (\Exception $ex){
				}

				return self::getResult(0, $r, "物流跟踪号：".$response['TrackCode']);
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
		try{
			$publicInfo = array();
			//odOrder表内容
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,1,$order);
			$puid = $checkResult['data']['puid'];
			$shipped = $checkResult['data']['shipped'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];

			$ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$order->selleruserid,'uid'=>$puid])->one();
			if(!$ebayuser)return self::getResult(1,'','该订单卖家帐号系统内没有绑定');
			############################################################################################
			$publicInfo = self::$pubinfo;
			$publicInfo['APISellerUserID'] = $ebayuser->selleruserid;
			$publicInfo['APISellerUserToken'] =$ebayuser->token;
			$publicInfo['MessageID'] = self::getMessageid();
			$publicInfo['Carrier'] = $service['carrier_params']['Carrier'];
			//包裹跟踪号使用customer_number
			$publicInfo['TrackCode'] = $order->customer_number;
			$request['CancelAPACShippingPackageRequest'] = $publicInfo;
			//返回数据处理
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'CancelAPACShippingPackage');
			if($response['error']){
				return $response;
			}
			$response = $response['data'];
			$response = $response->CancelAPACShippingPackageResult;
			//如果是错误信息
			if($response->Ack == 'Failure'){
				$str = '';
				if(count($response->NotificationList->Notification) > 1){
					foreach($response->NotificationList->Notification as $k=>$v){
						$str .= $v->Message.'<br/>';
					}
				}else{
					$str = $response->NotificationList->Notification->Message;
				}
				return self::getResult(1,'', $str);
			}
			if($response->Ack == 'Success'){
				$shipped->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->save();
				return self::getResult(0, '', '结果：订单已取消!时间:'.date('Y-m-d H:i:s',time()));
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
			$publicInfo = [];
			//odOrder表内容
			$order = $data['order'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,1,$order);
			$puid = $checkResult['data']['puid'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];

			$ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$order->selleruserid,'uid'=>$puid])->one();
			if(!$ebayuser)throw new CarrierException('该订单卖家帐号系统内没有绑定');
			############################################################################################
			$publicInfo = self::$pubinfo;
			
			//更改读取开发者信息
			$tmpApiInfo = config::getProductConfig($ebayuser['DevAcccountID']);
				
			$publicInfo['APIDevUserID'] = $tmpApiInfo['devID'];
			$publicInfo['AppID'] = $tmpApiInfo['appID'];
			$publicInfo['AppCert'] = $tmpApiInfo['certID'];
			$publicInfo['Version'] = '4.0.0';
			
			$publicInfo['APISellerUserID'] =  $ebayuser->selleruserid;
			$publicInfo['APISellerUserToken'] = $ebayuser->token;
			$publicInfo['MessageID'] = self::getMessageid();
			$publicInfo['Carrier'] = $service['carrier_params']['Carrier'];
			//包裹跟踪号使用customer_number
			$publicInfo['TrackCode'] = $order->customer_number;
			$request['ConfirmAPACShippingPackageRequest'] = $publicInfo;

			/*=====================================================================================*/
			//数据组织完毕，开始发送
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'ConfirmAPACShippingPackage');
			
			try {
				\Yii::info('EbayEub,puid:'.$puid.',request,order_id:'.$order->order_id.' '.(json_encode($request)),"carrier_api");
				\Yii::info('EbayEub,puid:'.$puid.',response,order_id:'.$order->order_id.' '.(json_encode($response)),"carrier_api");
			}catch (\Exception $ex){
			}
			
			if($response['error']){
				return $response;
			}
			$response = $response['data'];
			$response = $response->ConfirmAPACShippingPackageResult;
			//如果是错误信息
			if($response->Ack == 'Failure'){
				$str = '';
				if(count($response->NotificationList->Notification) > 1){
					foreach($response->NotificationList->Notification as $k=>$v){
						$str .= $v->Message.'<br/>';
					}
				}else{
					$str = $response->NotificationList->Notification->Message;
				}
				throw new CarrierException($str);
			}
			if($response->Ack == 'Success'){
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return BaseCarrierAPI::getResult(0, '', '结果：预报订单成功！跟踪号:'.$order->customer_number);
			}
			if($response->Ack == 'Warning'){
				if(isset($response->NotificationList->Notification->Code)){
					if($response->NotificationList->Notification->Code == 'W64'){
						$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
						$order->save();
						return BaseCarrierAPI::getResult(0, '', '结果：预报订单成功！跟踪号:'.$order->customer_number);
					}
					else{
						return BaseCarrierAPI::getResult(1, '', '结果！'.(empty($response->NotificationList->Notification->Message) ? 'e01' : $response->NotificationList->Notification->Message));
					}
				}
				return BaseCarrierAPI::getResult(1, '', '结果！e02');
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
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = $data['order'];
			if(empty($order->customer_number))return self::getResult(1,'','该订单状态异常，请检查是否手动移动到本状态');
// 			$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
// 			$order->save();

			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];// object SysShippingService

			$print_param = array();
			$print_param['carrier_code'] = $service->carrier_code;
			$print_param['api_class'] = 'LB_EPACKETCarrierAPI';
			$print_param['selleruserid'] = $order->selleruserid;
			$print_param['customer_number'] = $order->customer_number;
			
			try{
				CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
			}catch (\Exception $ex){
			}
			
			return BaseCarrierAPI::getResult(0, '', '结果：获取物流号成功！物流号：'.$order->customer_number);
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();

			$pdf = new PDFMerger();
			$result = [];
			//对每一条数据进行打印
			foreach($data as $v){
				$order = $v['order'];

				//获取到所需要使用的数据
				$info = CarrierAPIHelper::getAllInfo($order);
				$service = $info['service'];

				$publicInfo = array();
				//用户账户表
				$ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$order->selleruserid,'uid'=>$puid])->one();
				if(!$ebayuser)throw new CarrierException('该订单卖家帐号系统内没有绑定');
				############################################################################################
				$publicInfo = self::$pubinfo;
				
				//更改读取开发者信息
				$tmpApiInfo = config::getProductConfig($ebayuser['DevAcccountID']);
					
				$publicInfo['APIDevUserID'] = $tmpApiInfo['devID'];
				$publicInfo['AppID'] = $tmpApiInfo['appID'];
				$publicInfo['AppCert'] = $tmpApiInfo['certID'];
				$publicInfo['Version'] = '4.0.0';
				
				$publicInfo['APISellerUserID'] = $ebayuser->selleruserid;
				$publicInfo['APISellerUserToken'] =$ebayuser->token;
				$publicInfo['MessageID'] = self::getMessageid();
				$publicInfo['Carrier'] = $service['carrier_params']['Carrier'];
				$publicInfo['PageSize'] = $service['carrier_params']['PageSize'];
				//包裹跟踪号使用customer_number
				$publicInfo['TrackCode'] = $order->customer_number;
				$request['GetAPACShippingLabelRequest'] = $publicInfo;
				##############################################################################################
				//数据组织完成，进行发送
				$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'GetAPACShippingLabel');
				if($response['error']){
					$result[$order->order_id] = $response;
					continue;
				}
				$response = $response['data'];
				$response = $response->GetAPACShippingLabelResult;
				//如果是错误信息
				if($response->Ack == 'Failure'){
					$str = '';
					if(count($response->NotificationList->Notification) > 1){
						foreach($response->NotificationList->Notification as $k=>$v){
							$str .= $v->Message.' ';
						}
					}else{
						$str = $response->NotificationList->Notification->Message;
					}
					$result[$order->order_id] = self::getResult(1, '', $str);
					continue;
				}
				//如果是正确信息
				if($response->Ack == 'Success' || $response->Ack == 'Warning'){
					$pdfUrl = CarrierAPIHelper::savePDF($response->Label,$puid,$service->carrier_code.$order->customer_number.$response->InvocationID,0);
					$pdf->addPDF($pdfUrl['filePath'],'all');
					//添加订单标签打印时间
// 					$order->is_print_carrier = 1;
					$order->print_carrier_operator = $puid;
					$order->printtime = time();
					$order->save();
				}
			}
			//合并多个PDF  这里还需要进一步测试
			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl'],'errors'=>$result],'连接已生成,请点击并打印');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	/*
	 * 用来确定打印完成后 订单的下一步状态
	 */
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}

	/**
	+----------------------------------------------------------
	 * 
	+----------------------------------------------------------
	 * @access static
	+----------------------------------------------------------
	 * @return				随机唯一字符串
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		qfl		2014/01/30				初始化
	+----------------------------------------------------------
	**/
	public static function getMessageid() {
		$pre='DENG'.base_convert(time(),10,36);
		return strtoupper($pre.'0'.sprintf('%015s',base_convert(mt_rand(1000000,9999999999),10,36).'0'.base_convert(mt_rand(1000000,9999999999),10,36)));
	}



	/**
	+----------------------------------------------------------
	 * 获取地址信息
	+----------------------------------------------------------
	 * @access static
	+----------------------------------------------------------
	 * @return				地址列表
	+----------------------------------------------------------
	 * log			name	date					note
	 * @author		qfl		2015/03/24				初始化
	+----------------------------------------------------------
	**/
	private static function getSendAddress($carrierData)
	{	
		/*******************组织地址相关数据****************************************************************************/
		$OrderDetail = array();//申请单个跟踪号时，提交信息的数组
		//揽收地址
		$OrderDetail['PickUpAddress']['CountryCode'] = $carrierData['pickupaddress']['country'];
		unset($carrierData['pickupaddress']['country']);
		unset($carrierData['pickupaddress']['fax']);
		foreach($carrierData['pickupaddress'] as $k=>$v){
			$OrderDetail['PickUpAddress'][ucfirst($k)] = $v;
		}	
		
		//线上EUB的 区/县/镇 东莞/中山 换了揽收地址代码，历史用户不用在修改地址信息
		if($OrderDetail['PickUpAddress']['District'] == '441900'){
			$OrderDetail['PickUpAddress']['District'] == '441901';
		}
		
		if($OrderDetail['PickUpAddress']['District'] == '442000'){
			$OrderDetail['PickUpAddress']['District'] == '442001';
		}
		
		//发货地址
		$OrderDetail['ShipFromAddress']['CountryCode'] = $carrierData['shippingfrom']['country'];
		$OrderDetail['ShipFromAddress']['Contact'] = $carrierData['shippingfrom']['contact_en'];
        $OrderDetail['ShipFromAddress']['Company'] = $carrierData['shippingfrom']['company_en'];
        $OrderDetail['ShipFromAddress']['Province'] = $carrierData['shippingfrom']['province_en'];
        $OrderDetail['ShipFromAddress']['City'] = $carrierData['shippingfrom']['city_en'];
        $OrderDetail['ShipFromAddress']['District'] = $carrierData['shippingfrom']['district_en'];
        $OrderDetail['ShipFromAddress']['Street'] = $carrierData['shippingfrom']['street_en'];
        unset($carrierData['shippingfrom']['country']);
        unset($carrierData['shippingfrom']['contact']);
        unset($carrierData['shippingfrom']['contact_en']);
        unset($carrierData['shippingfrom']['company']);
        unset($carrierData['shippingfrom']['company_en']);
        unset($carrierData['shippingfrom']['province']);
        unset($carrierData['shippingfrom']['province_en']);
        unset($carrierData['shippingfrom']['city']);
        unset($carrierData['shippingfrom']['city_en']);
        unset($carrierData['shippingfrom']['district']);
        unset($carrierData['shippingfrom']['district_en']);
        unset($carrierData['shippingfrom']['street']);
        unset($carrierData['shippingfrom']['street_en']);
        unset($carrierData['shippingfrom']['fax']);
		foreach($carrierData['shippingfrom'] as $k=>$v){
			$OrderDetail['ShipFromAddress'][ucfirst($k)] = $v;
		}	
		//退货地址	
		$OrderDetail['ReturnAddress']['CountryCode'] = $carrierData['returnaddress']['country'];
		unset($carrierData['returnaddress']['country']);
		unset($carrierData['returnaddress']['fax']);
		foreach($carrierData['returnaddress'] as $k=>$v){
			$OrderDetail['ReturnAddress'][ucfirst($k)] = $v;
		}
		return $OrderDetail;
	}
	
	/**
	 +----------------------------------------------------------
	 * TNT或fedEx包裹信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @return				包裹信息数组
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		qfl		2015/03/24				初始化
	 +----------------------------------------------------------
	 **/
	private static function getShippingPackage($carrier){
		$ShippingPackage = array();
		if($carrier == 'BPOST'){
			$ShippingPackage['Length'] = 1.6;
			$ShippingPackage['Height'] = 1;
			$ShippingPackage['Width'] = 1.1;
		}else{
			$ShippingPackage['Length'] = 0.3;
			$ShippingPackage['Height'] = 0.3;
			$ShippingPackage['Width'] = 0.3;
		}
		return $ShippingPackage;
	}

	private static function getService($methodCode){
		if($methodCode == 'CNPOST') return 'EPACK';
		if($methodCode == 'TNT') return 'EXPR_15N';
		if($methodCode == 'FEDEX') return 'INT_EC';
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
			$publicInfo = array();
			//用户账户表
			$ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$print_param['selleruserid'],'uid'=>$puid])->one();
			if(!$ebayuser){
				return ['error'=>1, 'msg'=>'该订单卖家帐号系统内没有绑定', 'filePath'=>''];
			}
			
			$publicInfo = self::$pubinfo;
			
			//更改读取开发者信息
			$tmpApiInfo = config::getProductConfig($ebayuser['DevAcccountID']);
				
			$publicInfo['APIDevUserID'] = $tmpApiInfo['devID'];
			$publicInfo['AppID'] = $tmpApiInfo['appID'];
			$publicInfo['AppCert'] = $tmpApiInfo['certID'];
			$publicInfo['Version'] = '4.0.0';
			
			$publicInfo['APISellerUserID'] = $ebayuser->selleruserid;
			$publicInfo['APISellerUserToken'] =$ebayuser->token;
			$publicInfo['MessageID'] = self::getMessageid();
			$publicInfo['Carrier'] = 'CNPOST';
			$publicInfo['PageSize'] = 1;
			//包裹跟踪号使用customer_number
			$publicInfo['TrackCode'] = $print_param['customer_number'];
			$request['GetAPACShippingLabelRequest'] = $publicInfo;
			
			
			//数据组织完成，进行发送
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'GetAPACShippingLabel');
			if($response['error']){
				return ['error'=>1, 'msg'=>print_r($response, true), 'filePath'=>''];
			}
			
			$response = $response['data'];
			$response = $response->GetAPACShippingLabelResult;
			//如果是错误信息
			if($response->Ack == 'Failure'){
				$str = '';
				if(count($response->NotificationList->Notification) > 1){
					foreach($response->NotificationList->Notification as $k=>$v){
						$str .= $v->Message.' ';
					}
				}else{
					$str = $response->NotificationList->Notification->Message;
				}
				return ['error'=>1, 'msg'=>$str, 'filePath'=>''];
			}
			
			//如果是正确信息
			if($response->Ack == 'Success' || $response->Ack == 'Warning'){
				$pdfPath = CarrierAPIHelper::savePDF2($response->Label,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
				return $pdfPath;
			}
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
	
	//亚太平台根据ebay订单信息获取订单号
	public function getAPACShippingTrackCode($data){
// 		$data['DevAcccountID'];
// 		$data['selleruserid'];
// 		$data['token'];
// 		$data['Carrier'];
// 		$data['EBayItemID'];
// 		$data['EBayTransactionID'];


		$result = array('error'=>0, 'msg'=>'', 'data'=>'');
		
		try{
			$publicInfo = [];
			
			$publicInfo = self::$pubinfo;
				
			//更改读取开发者信息
			$tmpApiInfo = config::getProductConfig($data['DevAcccountID']);
	
			$publicInfo['APIDevUserID'] = $tmpApiInfo['devID'];
			$publicInfo['AppID'] = $tmpApiInfo['appID'];
			$publicInfo['AppCert'] = $tmpApiInfo['certID'];
			$publicInfo['Version'] = '4.0.0';
				
			$publicInfo['APISellerUserID'] =  $data['selleruserid'];
			$publicInfo['APISellerUserToken'] = $data['token'];
			$publicInfo['MessageID'] = self::getMessageid();
			$publicInfo['Carrier'] = $data['Carrier'];
			
			$publicInfo['EBayItemID'] = $data['EBayItemID'];
			$publicInfo['EBayTransactionID'] = $data['EBayTransactionID'];
			
			$request['GetAPACShippingTrackCodeRequest'] = $publicInfo;
	
			//数据组织完毕，开始发送
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'GetAPACShippingTrackCode');
			
			if(!isset($response['error'])){
				$result['error'] = 1;
				$result['msg'] = json_encode($response);
				return $result;
			}
			
			if($response['error'] != 0){
				$result['error'] = 1;
				$result['msg'] = $response['msg'];
				return $result;
			}
			
			$response = $response['data'];
			$response = $response->GetAPACShippingTrackCodeResult;
			
			if($response->Ack == 'Success'){
				\Yii::info('LB_EPACKET_APACShippingTrackCode1, '.json_encode($data),"carrier_api");
				\Yii::info('LB_EPACKET_APACShippingTrackCode2, '.json_encode($response),"carrier_api");
				
				if(isset($response['TrackCode'])){
					$result['data'] = $response['TrackCode'];
					return $result;
				}
				
				$result['error'] = 1;
				$result['msg'] = json_encode($response);
				return $result;
			}
			
			$result['error'] = 1;
			$result['msg'] = json_encode($response);
			return $result;
		}catch(\Exception $e){
			$result['error'] = 1;
			$result['msg'] = $e->msg();
			return $result;
		}
	}
}
