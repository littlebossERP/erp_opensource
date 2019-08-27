<?php
namespace common\api\overseaWarehouseAPI;


use eagle\modules\carrier\helpers\CarrierAPIHelper;
use common\helpers\Helper_Curl;
use Qiniu\json_decode;
use eagle\modules\order\models\OdOrder;

class LB_YUNTUOverseaWarehouseAPI extends BaseOverseaWarehouseAPI{
	public $url = null;
	private $orgCode = '';
	private $seller_name = '';
	
	public function __construct(){
 		$this->url = 'http://api.canhold.com.cn/';
		//$this->url = 'http://api.81656.cn/';
		//http://api.canhold.com.cn
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function getOrderNO($data){
		try{
			//odOrder表内容
			$order = $data['order'];
			$customer_number = $data['data']['customer_number'];
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			//用户在确认页面提交的数据
			$form_data = $data['data'];
		
			//对当前条件的验证，如果订单已存在，则报错，并返回当前用户Puid
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
			$puid = $checkResult['data']['puid'];
		
			//获取物流商信息、运输方式信息等
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			$this->orgCode = trim($api_params['orgCode']);
			$this->seller_name =  trim($api_params['seller_name']);
			
			///获取收件地址街道
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 1000,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
		
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
				
			$items = array();
		
			foreach ($order->items as $j=>$vitem){
				$items[]=array(
						'stockSku'=>$form_data['oversea_sku'][$j],  //SKU
						'quantity'=>$form_data['quantity'][$j],      //数量
				);
			}
		
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			$params = array(
					'orgCode'=>$this->orgCode,
					'orders'=>array(
						array(
							'platformOrderId'=>$order->order_source_order_id,			//客户在电商平台订单号
							'receiverName'=>$order->consignee,				//收件人姓名
							'receiverMobile'=>$order->consignee_mobile,		//收件人手机
							'receiverTel'=>$order->consignee_phone,			//收件人电话
							'receiverProvince'=>$tmpConsigneeProvince,		//收件人省
							'receiverFullAddress'=>$addressAndPhone['address_line1'].','.$order->consignee_city.','.$tmpConsigneeProvince,	//完整收件地址
							'seller'=>$this->seller_name,					//店铺名称
							'receiverPostCode'=>$order->consignee_postal_code,	//邮编
							'receiverCity'=>$order->consignee_city,			//收件人市
							'receiverAddress'=>$addressAndPhone['address_line1'],	//收件人地址
							'receiverEmail'=>$order->consignee_email,		//收件人email
							'orderId'=>$customer_number,					//客户订单号
							'orderItem'=>$items
						)
					),
			);
			
			\Yii::info('LB_YUNTUOverseaWarehouseAPI,puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($params),"carrier_api");
				
			$url = $this->url.'api/Erp/PostOutStoreOrder';
			
			$header=array();
			$header[]='Content-Type:application/json;charset=utf-8';
			$response = Helper_Curl::post($url,json_encode($params),$header);
			
			\Yii::info('LB_YUNTUOverseaWarehouseAPI,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response,"carrier_api");
			
			$response_arr = json_decode($response, true);
			
			if($response_arr['errcode'] == 0){
				if(!isset($response_arr['data'][0])){
					$msg=$response_arr['errmsg'];
					return self::getResult(1,'',$msg.' 请确定该订单是否存在于云图海外仓后台');
				}
				
				$r = CarrierAPIHelper::orderSuccess($order,$service,$response_arr['data'][0]['expressCode'],OdOrder::CARRIER_WAITING_GETCODE,'',['create_time'=>time()]);
				return self::getResult(0,$r,'订单提交成功,订单号:'.$response_arr['data'][0]['expressCode']);
			}else{
				$msg=$response_arr['errmsg'];
				return self::getResult(1,'',$msg);
			}
		}
		catch(CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单交运
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function doDispatch($data){
		return self::getResult(1,'','物流接口不支持交运');
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取跟踪号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function getTrackingNO($data){
		try{
			//订单对象
			$order = $data['order'];
			
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
				
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
				
			//获取到所需要使用的数据
			$api_params = $account->api_params;
				
			$this->orgCode = trim($api_params['orgCode']);
				
			$params = array();
			$params['orgCode'] = $this->orgCode;
			$params['BeginTime'] = date('Y-m-d H:i:s', $shipped->return_no['create_time']-50);
			$params['EndTime'] = date('Y-m-d H:i:s', $shipped->return_no['create_time']+50);
			
			$url = $this->url.'api/Erp/QueryCourierNumber';
			
			$header=array();
			$header[]='Content-Type:application/json;charset=utf-8';
			$response = Helper_Curl::post($url,json_encode($params),$header);
			
			\Yii::info('LB_YUNTUOverseaWarehouseAPI_getTrackingNO,puid:'.',response,order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
				
			$response_arr = json_decode($response, true);
			
// 			{"errcode":0,"errmsg":"查询成功","success":true,"data":[{"Code":"1212000073452","SrcCode":"J001BBN","ReceiverDeliverCode":""}]}
			
			if($response_arr['errcode'] == 0){
				$tmp_response = $response_arr['data'];
				
				if(count($tmp_response) > 0){
					foreach ($tmp_response as $tmp_responseVal){
						if($order->customer_number == $tmp_responseVal['Code']){
							if(empty($tmp_responseVal['ReceiverDeliverCode'])){
								return self::getResult(1, '', '暂时没有跟踪号返回e1');
							}else{
								$shipped->tracking_number = $tmp_responseVal['ReceiverDeliverCode'];
								//由于该货代没有渠道,可以直接返回通知平台的方法
								if(!empty($tmp_responseVal['LogisticsChannel'])){
									$shipped->shipping_method_code = $tmp_responseVal['LogisticsChannel'];
								}
								
								$shipped->save();
								$order->tracking_number = $tmp_responseVal['ReceiverDeliverCode'];
								$order->save();
								
								return self::getResult(0,'','获取订单跟踪号成功:'.$tmp_responseVal['ReceiverDeliverCode']);
							}
						}else if($order->customer_number == $tmp_responseVal['ReceiverDeliverCode']){
							if(empty($tmp_responseVal['ReceiverDeliverCode'])){
								return self::getResult(1, '', '暂时没有跟踪号返回e4');
							}else{
								$shipped->tracking_number = $tmp_responseVal['ReceiverDeliverCode'];
								//由于该货代没有渠道,可以直接返回通知平台的方法
								if(!empty($tmp_responseVal['LogisticsChannel'])){
									$shipped->shipping_method_code = $tmp_responseVal['LogisticsChannel'];
								}
							
								$shipped->save();
								$order->tracking_number = $tmp_responseVal['ReceiverDeliverCode'];
								$order->save();
							
								return self::getResult(0,'','获取订单跟踪号成功:'.$tmp_responseVal['ReceiverDeliverCode']);
							}
						}
					}
					return self::getResult(1, '', '暂时没有跟踪号返回e3');
				}else{
					return self::getResult(1, '', '暂时没有跟踪号返回e2');
				}
			}else{
				$msg=$response_arr['errmsg'];
				return self::getResult(1,'',$msg);
			}
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单取消
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function cancelOrderNO($data){
		return self::getResult(1,'','物流接口不支持取消已确定的物流单。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单打印
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		hqw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function doPrint($data){
		return self::getResult(1,'','该物流商不支持打印订单');
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
		$url = $this->url.'api/Erp/QueryAllSkuStorage';
		
		$params['orgCode'] = $data['api_params']['orgCode'];
		
		$header=array();
		$header[]='Content-Type:application/json;charset=utf-8';
		$response = Helper_Curl::post($url,json_encode($params),$header);
		
		$response_arr = json_decode($response, true);
		
		$resultStockList = array();
		
		if($response_arr['errcode'] == 0){
			$tmp_response = $response_arr['data'];
		
			if(count($tmp_response) > 0){
				foreach ($tmp_response as $valList){
					$resultStockList[$valList['sku']] = array(
							'sku'=>$valList['sku'],
							'productName'=>'',
							'stock_actual'=>$valList['count'],				//实际库存
							'stock_reserved'=>$valList['lockCount'],	//占用库存
							'stock_pipeline'=>0,	//在途库存
							'stock_usable'=>$valList['count'] - $valList['lockCount'],	//可用库存
							'warehouse_code'=>$data['warehouse_code']		//仓库代码
					);
				}
				return self::getResult(0, $resultStockList ,'');
			}else{
				return self::getResult(1,'','没有SKU');
			}
		}else{
			$msg = $response_arr['errmsg'];
			return self::getResult(1,'',$msg);
		}
	}
	
}