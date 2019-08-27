<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierException;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-04-13
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 铁三角海外仓物流商API
 +------------------------------------------------------------------------------
 * @category	vendors
 * @package		vendors/overseaWarehouseAPI
 * @subpackage  Exception
 * @author		qfl<772821140@qq.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_TIESANJIAOOverseaWarehouseAPI extends BaseOverseaWarehouseAPI 
{
	public static $service = null;
	public $_client = null;
	public $_appKey = null;
	public $_appToken = null;

	public function __construct(){
		if(empty($this->_client)){
	        $wsdl = 'http://oms.usship.net/default/svc/wsdl?wsdl';
	        $this->_appToken = 'ac9e361db930a76e05b9e7835227ca1b';
	        $this->_appKey = 'bfb401a94eb15fc37f335d4a8ad9a778';
	        
	        $streamContext = stream_context_create(array(
	            'ssl' => array(
	                'verify_peer' => false,
	                'allow_self_signed' => true
	            ),
	            'socket' => array()
	        ));
	        
	        $options = array(
	            "trace" => true,
	            "connection_timeout" => 30,
	            "encoding" => "utf-8"
	        );
	        
	        $this->_client = new \SoapClient($wsdl, $options);
    	}
	}
	function getOrderNO($data)
	{
		try{

			//订单对象
			$order = $data['order'];
			//表单提交的数据
			$e = $data['data'];
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 100,
							'consignee_address_line2_limit' => 100,
							'consignee_address_line3_limit' => 100,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 30
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
				
			$extra_id = isset($e['extra_id'])?$e['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];

			$result = $this->setKeyToken(['accountid'=>$s->carrier_account_id]);
			if($result)return $result;
			#######################################################################################
			// $request = [];
			// self::$service = 'getCountry';
		 //    $response = $this->orderOperate($request);
		 //    var_dump($response);die;
			//上面是获取国家的代码
			
			// echo 's';
			// var_dump($s);
			// echo 'data';
			// var_dump($data);
			// echo 'order';
			// var_dump($order);die;

			$carrier_params = $s->carrier_params;
			$platform = self::getPlatform($order->order_source);

// 			$extra_id = isset($e['extra_id'])?$e['extra_id']:'';
// 			$customer_number = $data['data']['customer_number'];
			
			$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
				(empty($order->consignee_district) ? '' : ','.$order->consignee_district);
				
		    $orderInfo = array(
		        'platform' => $platform,
		        'warehouse_code' => $s->third_party_code,
		        'shipping_method' => $s->shipping_method_code,
		        'reference_no' => $customer_number,
		        'country_code' => $order->consignee_country_code,
		        'province' => $order->consignee_province,
		        'city' => $order->consignee_city,
		        'address1' => $addressAndPhone['address_line1'],
		        'address2' => $addressAndPhone['address_line2'],
		        'address3' => $addressAndPhone['address_line3'],
		        'zipcode' => $order->consignee_postal_code,
		        // 'doorplate' => $order->consignee_province,
		        'name' => $order->consignee,
		        'phone' => $addressAndPhone['phone1'],
		        
		        'verify' => $e['verify'],//是否审核
		        'forceVerify' =>$carrier_params['forceVerify'],
		        'email' => $order->consignee_email
		    );
		    $items = [];
		    $count = count($e['sku']);
		    for($k = 0;$k<$count;$k++){
		    	$items[] = [
		    		'product_sku'=>$e['sku'][$k],
		    		'quantity'=>$e['DeclarePieces'][$k]!=''?$e['DeclarePieces'][$k]:0,
		    	];
		    }
		    $orderInfo['items'] = $items;
		    // var_dump($orderInfo);die;
		    #########################################################################################
		    //数据组织完毕 准备发送
	        self::$service = 'createOrder';
		    $response = $this->orderOperate($orderInfo);
		    $service_codeArr = $s->service_code;
		    if($response['ask'] == 'Success'){
		    	//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				$r = CarrierAPIHelper::orderSuccess($order,$s,$response['order_code'],OdOrder::CARRIER_WAITING_GETCODE);

				return self::getResult(0,$r,'订单已提交并审核,出库单号:'.$response['order_code']);
			}
			if($response['ask']=='Failure'){
				return self::getResult(1,'',$response['message']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//订单交运
	function doDispatch($data){
		return self::getResult(1,'','该物流商不支持交运订单,请在上传订单时选择审核');
	}

	//获取跟踪号
	function getTrackingNO($data){
		try{
			//订单对象
			$order = $data['order'];
			
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];

			$result = $this->setKeyToken(['accountid'=>$s->carrier_account_id]);
			if($result)return $result;
			//判断订单是否存在
			if(empty($order->customer_number))return self::getResult(1,'','操作失败,请确定该订单是否已上传');
			$request = [
				'order_code'=>$order->customer_number
			];
			#########################################################################################
		    //数据组织完毕 准备发送
	        self::$service = 'getOrderByCode';
		    $response = $this->orderOperate($request);
		    if($response['ask'] == 'Success'){
				$r = [
					'order_status'=>$response['data']['order_status'],//订单状态
					'warehouse_code'=>$response['data']['warehouse_code'],//仓库ID
					'fee_details'=>$response['data']['fee_details'],//订单费用
				];
				//将返回回来的数据保存到return_no中方便以后使用
				$shipped = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number])->one();
				$shipped->return_no = $r;
				$status = $this->getStatus($response['data']['order_status']);
				//如果物流商有转运单号返回 则更新系统内的跟踪号
				if(isset($response['data']['tracking_no'])&&$response['data']['tracking_no']){
					$shipped->tracking_number = $response['data']['tracking_no'];
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					return self::getResult(0,'','获取跟踪号成功<br/>跟踪号:'.$shipped->tracking_number.'<br/>'.$status);
				}else{
					return self::getResult(1,'','物流商暂未生成跟踪号<br/>物流订单状态:'.$status);
				}
			}
			if($response['ask']=='Failure'){
				return self::getResult(1,'',$response['message']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	//用来判断是否支持打印
	public static function isPrintOk(){
		return false;
	}

	//订单打印
	function doPrint($data){
		return self::getResult(1,'','该物流商不支持打印订单');
	}

	//根据状态码返回对应的状态
	function getStatus($data){
		$arr = [
			'C'=>'待发货审核',
			'W'=>'待发货',
			'D'=>'已发货',
			'H'=>'暂存',
			'N'=>'异常订单',
			'P'=>'问题件',
			'X'=>'废弃',
			'O'=>'系统无返回',
		];
		if(isset($arr[$data]))return $arr[$data];
		return $arr['O'];
	}

	function cancelOrderNO($data){
		try{

			//订单对象
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];
			$result = $this->setKeyToken(['accountid'=>$s->carrier_account_id]);
			if($result)return $result;
			$request = [
				'order_code'=>$order->customer_number
			];
			#########################################################################################
		    //数据组织完毕 准备发送
	        self::$service = 'cancelOrder';
		    $response = $this->orderOperate($request);
		    if($response['ask'] == 'Success'){
				$orderShippedObj = OdOrderShipped::findOne(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number]);
				$orderShippedObj->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->customer_number = '';
				$order->save();
				return self::getResult(0,'', '成功取消订单'.$order->customer_number);
			}
			if($response['ask']=='Failure'){
				return self::getResult(1,'',$response['message']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	function Recreate($data){
		return self::getResult(1,'','该物流商不支持重新发货');
	}
	
// 	function getOverseasWarehouseStockList(){
// 		$data['accountid'] = '';
		
// 		$result = $this->setKeyToken(['accountid'=>$s->carrier_account_id]);
		
// 		$request = [
// 			'pageSize'=>100,
// 			'page'=>1
// 		];
		
// 		self::$service = 'getProductInventory';
// 		$response = $this->orderOperate($request);
		
// 		print_r($response);
// 	}

	/*
	 *	@params $data accountid 物流商帐号id
	 */
	function setKeyToken($data){
		//物流账号;
		$account = SysCarrierAccount::find()->where(['id'=>$data['accountid'],'is_used'=>1])->one();
		if(empty($account))return BaseCarrierAPI::getResult(1,'','该运输服务没有分配可用的物流商帐号');
		$a = $account->attributes;
		$this->_appKey = $a['api_params']['app_key'];
		$this->_appToken = $a['api_params']['token'];

	}

    public static function getPlatform($source){
    	$arr = [
    		'ebay'=>'EBAY',
    		'aliexpress'=>'ALIEXPRESS',
    		'amazon'=>'AMAZON',
    		'other'=>'OTHER'
    	];
    	if(array_key_exists($source, $arr))return $arr[$source];
    	return $arr['other'];
    }

    /**
    * 对象转数组
    *
    * @param
    *            $obj
    * @return mixed
    */
    public static function objectToArray($obj)
    {
        $arr = '';
        $_arr = is_object($obj) ? get_object_vars($obj) : $obj;
        if(is_array($_arr)){
            foreach($_arr as $key => $val){
                $val = (is_array($val) || is_object($val)) ? self::objectToArray($val) : $val;
                $arr[$key] = $val;
            }
        }
        return $arr;
    }

    /**
     * 调用webservice
     * ====================================================================================
     *
     * @param unknown_type $req            
     * @return Ambigous <mixed, NULL, multitype:, multitype:Ambigous <mixed,
     *         NULL> , StdClass, multitype:Ambigous <mixed, multitype:,
     *         multitype:Ambigous <mixed, NULL> , NULL> , boolean, number,
     *         string, unknown>
     */
    private function callService($req)
    {
        $req['appToken'] = $this->_appToken;
        $req['appKey'] = $this->_appKey;
        $result = $this->_client->callService($req);
        $result = self::objectToArray($result);
        $return = json_decode($result['response']);
        $return = self::objectToArray($return);
        return $return;
    }

    //根据service 执行相关操作
    public function orderOperate($orderInfo)
    {
        $return = array(
            'ask' => 'Failure',
            'message' => ''
        );
        try{
            $req = array(
                'service' => self::$service,
                'paramsJson' => json_encode($orderInfo)
            );
            $return = $this->callService($req);
        }catch(Exception $e){
            $return = self::getResult(1,'',$e->getMessage());
        }
        return $return;
    }
    
    public function getTestList(){
    	$request = [
    		//'warehouseCode'=>'GUANGZHOU'
    	];
    	self::$service = 'getShippingMethod';
    	$response = $this->orderOperate($request);
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
//     public function getVerifyCarrierAccountInformation($data){
//     	$result = array('is_support'=>1,'error'=>1);
    
//     	try{
    
//     		$request = [
//     			'warehouseCode'=>'GUANGZHOU'
//     				];
    		
//     		$req = array(
//     				'service' => 'getShippingMethod',
//     				'paramsJson' => json_encode($request),
//     				'appToken' => $data['appToken'],
//     				'appKey' => $data['appKey']
//     		);
//     		$res = $this->_client->callService($req);
//     		$res = self::objectToArray($res);
//     		$return = json_decode($res['response']);
//     		$return = self::objectToArray($return);
//     		if($return['ask'] == 'Success'){
//     			$result['error'] = 0;
//     		}
    		
//     	}catch(CarrierException $e){
//     	}
    
//     	return $result;
//     }
    
}