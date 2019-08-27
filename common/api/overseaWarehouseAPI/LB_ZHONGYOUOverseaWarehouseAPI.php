<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_xml;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrderShipped;
use common\helpers\SubmitGate;
use Qiniu\json_decode;

class LB_ZHONGYOUOverseaWarehouseAPI extends BaseOverseaWarehouseAPI
{
	private $appKey = '';
	private $appToken = '';
	static private $wsdl = '';	// 物流接口
	
	public function __construct(){
// 		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			self::$wsdl = 'http://cpws.ems.com.cn/default/svc/wsdl';			
// 		}else{
// 			self::$wsdl = 'http://202.104.134.94:6280/default/svc/wsdl';
// 		}
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2016/08/03			初始化
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

			$params=array(
					'platform' => 'OTHER', //平台，默认OTHER
					'reference_no'=>$customer_number,      //订单参考号
					'shipping_method'=>$service->shipping_method_code,      //配送方式
					'remark'=>$form_data['remark'],      //备注
					'warehouse_code'=>$service->third_party_code, //配送仓库
					'country_code'=>$order->consignee_country_code, //收件人国家
					'province'=>$tmpConsigneeProvince,      //省
					'city'=>$order->consignee_city,     //城市
					'address1'=>$addressAndPhone['address_line1'],
					'address2'=>$addressAndPhone['address_line2'],
					'address3'=>$addressAndPhone['address_line3'],
					'zipcode'=>$order->consignee_postal_code,     //邮编
					'name'=>$order->consignee,   //收件人姓名
					'phone'=>$addressAndPhone['phone1'], //收件人联系方式
					'email'=>$order->consignee_email,
					'verify'=>'1',           //是否审核,0新建不审核(草稿状态)，1新建并审核， 默认为0， 审核通过之后，不可编辑
					'insurance_value'=>isset($form_data['insurance_value'])?$form_data['insurance_value']:'',   //保额
					'is_insurance'=>isset($carrier_params['is_insurance'])?$carrier_params['is_insurance']:'0',   //保险服务
					'is_signature'=>isset($carrier_params['is_signature'])?$carrier_params['is_signature']:'0',     //签名服务
					'items'=>$items,
			);

			$response = self::runxml($params, $this->appToken, $this->appKey, 'createOrder',$puid,$order->order_id);
			$response=$response['data'];
			$response_arr=json_decode($response->response,true);

			if($response_arr['ask']=='Success'){
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,null,['OrderSign'=>$response_arr['order_code']]);
				return self::getResult(0,$r,'订单提交成功,订单号:'.$response_arr['order_code']);
			}
			else{
				$msg=$response_arr['message'];
				return self::getResult(1,'',$msg);
			}
		}
		catch(CarrierException $e)
		{
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单交运
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2016/08/03			初始化
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
	 * @author		lgw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function getTrackingNO($data){
// 		return self::getResult(1,'','物流接口申请订单号时就会返回跟踪号');
		
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
		
			//对当前条件的验证
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$puid = $checkResult['data']['puid'];
		
			$account = $info['account'];
			$api_params = $account->api_params;
			
			$this->appToken = trim($api_params['appToken']);
			$this->appKey = trim($api_params['appKey']);

			$shipped = $checkResult['data']['shipped'];
			$returnNo = $shipped->return_no;
		
			if(empty($shipped->customer_number))
				return self::getResult(1, '', '没有处理号，请检查订单是否已上传');

			$params=array(
					'pageSize' => 1, //每页数据长度，最大值100
					'page'=>1,      //当前页
					'order_code'=>empty($returnNo)?$shipped->customer_number:$returnNo['OrderSign'],
// 					'order_code'=>"2043-180126-0009",
			);
			
			$response = self::runxml($params, $this->appToken, $this->appKey, 'getOrderList',$puid,$order->order_id);
// 			$response = self::runxml($params, "7048dd1d9fdb8f0a3df5441c2521c850", "95672ccc20f679e8d033a1b93dc016f8", 'getOrderList',$puid,$order->order_id);
			$response=$response['data'];
			$response_arr=json_decode($response->response,true);

			if(empty($response_arr['data'][0]['tracking_no']))
				return self::getResult(1, '', '获取跟踪号失败');
			
			$TrackingNumber=$response_arr['data'][0]['tracking_no'];
			
			$shipped->tracking_number = $TrackingNumber;
			$shipped->save();
			$order->tracking_number = $TrackingNumber;
			$order->save();
			return self::getResult(0,'', '获取物流号成功！<br/>跟踪号:'.$TrackingNumber);		
		
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单取消
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2016/08/03			初始化
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
	 * @author		lgw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function doPrint($data){
		return self::getResult(1,'','该物流商不支持打印订单');
	}
	
	//获取仓库
	function getWarehouse(){
		$params=array(
				'pageSize' => '',
				'page' => ''
		);
		
		$appToken=empty($this->appToken)?'7048dd1d9fdb8f0a3df5441c2521c850':$this->appToken;
		$appKey=empty($this->appKey)?'95672ccc20f679e8d033a1b93dc016f8':$this->appKey;
				
		$response = self::runxml($params, $appToken, $appKey, 'getWarehouse');
		if($response['error']){return self::getResult(1,'',$response['msg']);}

		$response_arr=json_decode($response['data']->response,true);
		if($response_arr['ask']=='Failure')
			return $response_arr['message'];
		else
			return $response_arr;
	}
	/*
	 * 获取各仓库运输服务
	*/
	function getDeliveryService($WarehouseNo){
		$params=array(
				'warehouseCode' => $WarehouseNo,
		);
	
		$appToken=empty($this->appToken)?'7048dd1d9fdb8f0a3df5441c2521c850':$this->appToken;
		$appKey=empty($this->appKey)?'95672ccc20f679e8d033a1b93dc016f8':$this->appKey;
		
		$response = self::runxml($params, $appToken, $appKey, 'getShippingMethod');
		if($response['error']){return self::getResult(1,'',$response['msg']);}

		$response_arr=json_decode($response['data']->response,true);
		if($response_arr['ask']=='Failure')
			return $response_arr['message'];
		else
			return $response_arr;
	}
	
	//时区转换
	function getLaFormatTime($format,$timestamp){
		$dt=new \DateTime();
		$dt->setTimestamp($timestamp);
		$dt->setTimezone(new \DateTimeZone('Europe/Berlin'));
		$d=$dt->format($format);
		return $d;
	}
	
	//组织数据提交
	function runxml($params,$appToken,$appKey,$jiekou,$puid='',$orderid=''){
		$request=array(
				'appToken'=>$appToken,
				'appKey'=>$appKey,
				'service'=>$jiekou,
				'paramsJson'=>json_encode($params),
		);

		\Yii::info('LB_ZHONGYOU,puid:'.$puid.',request,order_id:'.$orderid.' '.json_encode($request),"carrier_api");
		$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'callService');
		$log=isset($response['data']->response)?$response['data']->response:json_encode($response);
		\Yii::info('LB_ZHONGYOU,puid:'.$puid.',response,order_id:'.$orderid.' '.$log,"carrier_api");
		
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
// 		$data['accountid'] = '';
// 		$data['warehouse_code'] = 'USWE';

		$this->appToken=$data['api_params']['appToken'];
		$this->appKey=$data['api_params']['appKey'];
		
// 		$this->appToken='7048dd1d9fdb8f0a3df5441c2521c850';
// 		$this->appKey='95672ccc20f679e8d033a1b93dc016f8';
		
		//定义第几页开始
		$pageInt = 0;
		//默认最大页数为1
		$pageMaxInt = 100;
		
		$resultStockList = array();
		$errcode=0;
		$errmsg='';
		//循环翻页效果
		while ($pageInt < $pageMaxInt){
			$pageInt++;
			
			$request=array(
					'pageSize'=>100,
					'page'=>$pageInt,
					'warehouse_code'=>$data['warehouse_code'],
			);
			
			$response = $this->runxml($request,$this->appToken,$this->appKey,'getProductInventory');
			
			if($response['error'] == 0){
				$response=json_decode($response['data']->response,true);
				
				if($response['ask']=='Success'){
					if($response['count'] > 0){
							
						unset($tmpStorageList);
							
						$tmpStorageList = $response['data'];

						if(!empty($tmpStorageList) && $tmpStorageList!='无数据'){ 
								foreach ($tmpStorageList as $valList){
									unset($tmpStorageDetails);
									if($data['warehouse_code'] == $valList['warehouse_code']){
										$resultStockList[$valList['product_sku']] = array(
												'sku'=>$valList['product_sku'],
												'productName'=>'',
												'stock_actual'=>$valList['sellable']+$valList['reserved'],				//实际库存
												'stock_reserved'=>$valList['reserved'],	//占用库存
												'stock_pipeline'=>$valList['onway'],	//在途库存
												'stock_usable'=>$valList['sellable'],	//可用库存
												'warehouse_code'=>$valList['warehouse_code']		//仓库代码
										);
									}
								}
						}
					}
					
					$pageMaxInt = $response['count'];
				}
				else{
					$errcode=1;
					$errmsg=$errmsg.$response['message'].';';
					break;
				}
			}
		}

		return self::getResult($errcode, $resultStockList ,$errmsg);
	}
	
}
?>