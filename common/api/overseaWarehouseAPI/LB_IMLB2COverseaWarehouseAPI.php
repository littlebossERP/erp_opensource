<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\SubmitGate;
use Qiniu\json_decode;

class LB_IMLB2COverseaWarehouseAPI extends BaseOverseaWarehouseAPI
{
	private $appToken = '';
	private $appKey = '';
	static private $wsdl = '';	// 物流接口
	
	public function __construct(){
		self::$wsdl = 'http://api.imlb2c.com/default/svc/wsdl';
		$this->submitGate = new SubmitGate();
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
			
			$this->appToken = trim($api_params['appToken']);
			$this->appKey = trim($api_params['appKey']);
			
			///获取收件地址街道
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
			
			$items = array();
		
			$weight="";
			$Product="";
			foreach ($order->items as $j=>$vitem){
				$items[]=array(
						'product_sku'=>$form_data['oversea_sku'][$j],  //SKU
						'quantity'=>$form_data['quantity'][$j],      //数量
				);
			}
				
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			$tmp_carrier_platform = 'OTHER';
			switch ($order->order_source){
				case 'aliexpress':
					$tmp_carrier_platform = 'ALIEXPRESS';
					break;
				case 'amazon':
					$tmp_carrier_platform = 'AMAZON';
					break;
				case 'ebay':
					$tmp_carrier_platform = 'EBAY';
					break;
				default :
					$tmp_carrier_platform = 'OTHER';
					break;
			}
		
			$params=array(
					'reference_no'=>$customer_number,      //订单参考号
					'platform' => $tmp_carrier_platform, //平台，默认OTHER
					'shipping_method'=>$service->shipping_method_code,      //配送方式
					'warehouse_code'=>$service->third_party_code, //配送仓库
					'country_code'=>$order->consignee_country_code, //收件人国家
					'province'=>$tmpConsigneeProvince,      //省
					'city'=>$order->consignee_city,     //城市
					'address1'=>$addressAndPhone['address_line1'],
					'address2'=>$addressAndPhone['address_line2'],
					'address3'=>$addressAndPhone['address_line3'],
					'zipcode'=>$order->consignee_postal_code,     //邮编
					'doorplate'=>'',	//门牌号
					'name'=>$order->consignee,   //收件人姓名
					'phone'=>$addressAndPhone['phone1'], //收件人联系方式
					'email'=>$order->consignee_email,
					'platform_shop'=>'',	//平台店铺
					'order_desc'=>$form_data['order_desc'],		//订单说明
					'verify'=>'1',           //是否审核,0新建不审核(草稿状态)，1新建并审核， 默认为0， 审核通过之后，不可编辑
// 					'forceVerify'=>0,		//是否强制审核(如欠费，缺货时是否审核到仓配系统),0不强制，1强制，默认为0 当verify==1时生效
					'items'=>$items,
			);
		
			$request = array(
					'paramsJson'=>json_encode($params),
					'appToken'=>$this->appToken,
					'appKey'=>$this->appKey,
					'service'=>'createOrder'
			);
			
			\Yii::info('LB_IMLB2COverseaWarehouseAPI,puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'callService');
			
			\Yii::info('LB_IMLB2COverseaWarehouseAPI,puid:'.$puid.',response,order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
			
			$response = $response['data'];
			$response_arr=json_decode($response->response,true);
		
			if($response_arr['ask']=='Success'){
				$r = CarrierAPIHelper::orderSuccess($order,$service,$response_arr['order_code'],OdOrder::CARRIER_WAITING_PRINT,$response_arr['tracking_no']);
				return self::getResult(0,$r,'订单提交成功,订单号:'.$response_arr['order_code']);
			}else{
				$msg=$response_arr['message'];
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
// 		return self::getResult(1,'','物流接口申请订单号时就会返回跟踪号');
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
			
			$this->appToken = trim($api_params['appToken']);
			$this->appKey = trim($api_params['appKey']);
			
			$params['order_code'] = $order->customer_number;
			
			$request = array(
					'paramsJson'=>json_encode($params),
					'appToken'=>$this->appToken,
					'appKey'=>$this->appKey,
					'service'=>'getOrderByCode'
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'callService');
			
			\Yii::info('LB_IMLB2COverseaWarehouseAPI_getTrackingNO,puid:'.$puid.',response,order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
			
			$response = $response['data'];
			$response_arr=json_decode($response->response,true);
			
			if($response_arr['ask']=='Success'){
				$tmp_response = $response_arr['data'];
				
				$shipped->tracking_number = $tmp_response['tracking_no'];
				$shipped->save();
				$order->tracking_number = $tmp_response['tracking_no'];
				$order->save();
				return self::getResult(0,'','获取订单跟踪号成功:'.$tmp_response['tracking_no']);
			}else{
				$msg=$response_arr['message'];
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
	
	//获取仓库
	function getWarehouse(){
		$request = array(
				'paramsJson'=>'',
				'appToken'=>'62a14e70bd91b3fbd935d06574945002',
				'appKey'=>'7c9607f196b909cc85fc5f3b92bee13c',
				'service'=>'getWarehouse'
		);
		
		$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'callService');
		
		return $response;
	}
	
	/*
	 * 获取各仓库运输服务
	*/
	function getDeliveryService(){
		$request = array(
				'paramsJson'=>'',
				'appToken'=>'62a14e70bd91b3fbd935d06574945002',
				'appKey'=>'7c9607f196b909cc85fc5f3b92bee13c',
				'service'=>'getShippingMethod'
		);
		
		$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'callService');
		
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
		$resultStockList = array();
		
		$params = array();
		$params['pageSize'] = 100;
		$params['page'] = 1;
		$params['warehouse_code'] = $data['warehouse_code'];
		
		$data['appToken'] = $data['api_params']['appToken'];
		$data['appKey'] = $data['api_params']['appKey'];
		
		$request = array(
				'paramsJson'=>json_encode($params),
				'appToken'=>$data['appToken'],
				'appKey'=>$data['appKey'],
				'service'=>'getProductInventory'
		);
			
		$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'callService');
		$response = $response['data'];
		$response_arr=json_decode($response->response,true);
		
		if($response_arr['ask']=='Success'){
			$tmp_while = true;
			
			do{
				foreach ($response_arr['data'] as $valList){
					if($data['warehouse_code'] == $valList['warehouse_code']){
						$resultStockList[$valList['product_sku']] = array(
								'sku'=>$valList['product_sku'],
								'productName'=>'',
								'stock_actual'=>$valList['reserved']+$valList['sellable'],		//实际库存
								'stock_reserved'=>$valList['reserved'],	//占用库存
								'stock_pipeline'=>$valList['onway'],	//在途库存
								'stock_usable'=>$valList['sellable'],	//可用库存
								'warehouse_code'=>$valList['warehouse_code']		//仓库代码
						);
					}
				}
				
				if($response_arr['nextPage'] == 'false'){
					$tmp_while = false;
				}else{
					$params['page']++;
					
					$request = array(
							'paramsJson'=>json_encode($params),
							'appToken'=>$data['appToken'],
							'appKey'=>$data['appKey'],
							'service'=>'getProductInventory'
					);
						
					$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'callService');
					$response = $response['data'];
					unset($response_arr);
					$response_arr=json_decode($response->response,true);
					
					if($response_arr['ask']=='Success'){
					}else{
						$tmp_while = false;
					}
				}
			}while($tmp_while);
		}else{
			$msg=$response_arr['message'];
			return self::getResult(1,'',$msg);
		}
	
		return self::getResult(0, $resultStockList ,'');
	}
	
}