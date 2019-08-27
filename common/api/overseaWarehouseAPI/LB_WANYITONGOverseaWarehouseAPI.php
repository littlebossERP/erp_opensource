<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\SubmitGate;
use common\helpers\Helper_Array;
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
 * 万邑通海外仓物流商API
 +------------------------------------------------------------------------------
 * @category	vendors
 * @package		vendors/overseaWarehouseAPI
 * @subpackage  Exception
 * @author		qfl<772821140@qq.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_WANYITONGOverseaWarehouseAPI extends BaseOverseaWarehouseAPI 
{
	public $winitAPI = null;
	public static $is_delivery = 0;
	
	public function __construct(){
		$this->winitAPI = new winitAPI();
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			$this->winitAPI->url = 'http://api.winit.com.cn/ADInterface/api';
		}else {
			$this->winitAPI->url = 'http://erp.sandbox.winit.com.cn/ADInterface/api';	//新的测试接口
		}
	}
	
	function getOrderNO($data)
	{
		try{
			//订单对象
			$order = $data['order'];
			//表单提交的数据
			$e = $data['data'];
			
			$extra_id = isset($e['extra_id'])?$e['extra_id']:'';
			$customer_number = $data['data']['customer_number'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
			
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];

			$result = $this->setKeyToken(['accountid'=>$s->carrier_account_id]);
			if($result)return $result;

            $addressAndPhoneParams = array(
                'address' => array(
                    'consignee_address_line1_limit' => 50,
                    'consignee_address_line2_limit' => 100,
                    'consignee_address_line3_limit' => 100,
                ),
                'consignee_district' => 1,
                'consignee_county' => 1,
                'consignee_company' => 1,
                'consignee_phone_limit' => 100
            );
            $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

			$request = [
                "address1" => isset($e['receiver_address1']) ? $e['receiver_address1'] : $addressAndPhone['address_line1'],
                "address2" => isset($e['receiver_address2']) ? $e['receiver_address2'] : $addressAndPhone['address_line2'],
                "city" => $order->consignee_city, //市
		        "deliveryWayID" => $s->shipping_method_code, //最后一公里派送方式ID。最后一公里派送方式ID不可为空。 可查询
		        "insuranceTypeID" => empty($e['insuranceTypeID'])?1000000:$e['insuranceTypeID'], //最后一公里派送方式所用保险类型ID。保险类型ID不可为空。
                "eBayOrderID" => $order->order_source=='ebay'?$order->order_source_order_id:'',
		        "emailAddress" => \eagle\modules\carrier\helpers\CarrierOpenHelper::getOrderEmailResults($order->consignee_email), //邮箱
		        "phoneNum" => strlen($order->consignee_phone)>4?$order->consignee_phone:$order->consignee_mobile, //电话
		        "recipientName" => $order->consignee, //收货人姓名
		        "region" => $order->consignee_province, //州
		        "repeatable" => "N", //用户授权此海外仓出库单是否可重复。是否允许重复不可为空。
		        "sellerOrderNo" => $customer_number, //卖家订单号
		        "state" => $order->consignee_country_code?$order->consignee_country_code:$order->consignee_country, //国家
		        "warehouseID" => $s->third_party_code, //万邑通海外仓ID，唯一，可查询。
		        "zipCode" => $order->consignee_postal_code,//邮编
                "doorplateNumbers" => empty($e['doorplateNumbers'])?'':$e['doorplateNumbers'], //门牌号
			];
			
			$oet = OdEbayTransaction::find()->select(['transactionid'])->where(['order_id'=>$order->order_id])->one();
			$transactionid = $oet?$oet->transactionid:'';
			foreach($order->items as $k=>$v){
				$request['productList'][$k] = [
		            "eBayBuyerID" => $order->order_source=='ebay'?$order->source_buyer_user_id:'',
		            "eBayItemID" => $order->order_source=='ebay'?$v->order_source_itemid:'',
                    "eBaySellerID" => $order->order_source=='ebay'?$order->selleruserid:'',
		            "eBayTransactionID" => $order->order_source=='ebay'?(($v->order_source_transactionid == '')?$transactionid:$v->order_source_transactionid):'',
		            "productCode" => $e['sku'][$k], //产品编码SKU
		            "productNum" => $e['DeclarePieces'][$k], //一个海外仓出库单所包含的某个产品的数量。产品对应的单品数量必须>0
				];
				if($e['specification'][$k])$request['productList'][$k]['specification'] = $e['specification'][$k];
			};
			Helper_Array::removeEmpty($request);
			
			//万邑通新添加了直接创建订单马上交运接口
			if(self::$is_delivery == 1){
				$request['isShareOrder'] = 'N';
				$request['fromBpartnerId'] = '';
				
				$response = $this->winitAPI->processData($request,"createOutboundOrder");
			}else{
				$response = $this->winitAPI->processData($request,"createOutboundInfo");
			}
			
		    #########################################################################################

			if(isset($response['code'])){
				//接受到返回信息 进行处理
				if($response['code'] == 0){
					//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
					if(self::$is_delivery == 1){
						$r = CarrierAPIHelper::orderSuccess($order,$s,$response['data']['outboundOrderNum'],OdOrder::CARRIER_WAITING_GETCODE);
					}else{
						$r = CarrierAPIHelper::orderSuccess($order,$s,$response['data']['outboundOrderNum'],OdOrder::CARRIER_WAITING_DELIVERY);
					}
					return self::getResult(0,$r,'出库成功,出库单号:'.$response['data']['outboundOrderNum']);
				}else{
					return self::getResult(1,'',$response['msg']);
				}
			}else{
				return self::getResult(1,'','物流商返回异常');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//交运订单
	function doDispatch($data){
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];
			$result = $this->setKeyToken(['accountid'=>$s->carrier_account_id]);
			if($result)return $result;
			$request = [
				'outboundOrderNum'=>$order->customer_number
			];
			$response = $this->winitAPI->processData($request,"confirmOutboundOrder");
			###################################################################################
			if($response['code'] == 0){
				$order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
				$order->save();
				$success = isset($response['data']['outboundOrderNum'])&&$response['data']['outboundOrderNum']?'出库单号:'.$response['data']['outboundOrderNum']:'';
				return self::getResult(0,'', '确认订单成功！'.$success);
			}else{
				if(($response['msg'] == '系统错误') || ($response['msg'] == '出库单不是草稿状态')){
					unset($request);
					$request = [
						'outboundOrderNum'=>$order->customer_number,
					];
					
					$responseQorder = $this->winitAPI->processData($request,"queryOutboundOrder");
					
					if($responseQorder['code'] == 0){
						$list = $responseQorder['data']['list'][0];
						$r = [
						'carrier'=>$list['carrier'],//派送公司名称
						'deliveryCosts'=>$list['deliveryCosts'],//派送费用
						'ebayName'=>$list['ebayName'],//ebay上显示的快递公司名称
						'deliveryCompletionStatus'=>$list['deliveryCompletionStatus'],//派送完成情况
						];
						//将返回回来的数据保存到return_no中方便以后使用
						$shipped = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number])->one();
						$shipped->return_no = $r;
						//如果物流商有转运单号返回 则更新系统内的跟踪号
						/** start 万邑通增加参数winitTrackingNo 这个参数不为空，则按它做标记发货。为空，则按原来的trackingNum做为标记发货。2个都为空，则不传*/
						if(!empty($list['winitTrackingNo'])){
							$shipped->tracking_number = $list['winitTrackingNo'];
							$shipped->save();
							$order->save();
							$success = empty($list['carrier'])?'':'派送公司:'.$list['carrier'];
							return self::getResult(0,'','获取跟踪号成功<br/>跟踪号:'.$shipped->tracking_number.'<br/>'.$success);
						}
						else{
							if(isset($list['trackingNum'])&&$list['trackingNum']){
								$shipped->tracking_number = $list['trackingNum'];
								$shipped->save();
								$order->save();
								$success = empty($list['carrier'])?'':'派送公司:'.$list['carrier'];
								return self::getResult(0,'','获取跟踪号成功<br/>跟踪号:'.$shipped->tracking_number.'<br/>'.$success);
							}
							elseif(empty($list['trackingNum'])){
								return self::getResult(1,'','物流商暂未生成跟踪号');
							}
						}
						/** end 万邑通增加参数winitTrackingNo 这个参数不为空，则按它做标记发货。为空，则按原来的trackingNum做为标记发货。2个都为空，则不传*/
					}
				}
				
				return self::getResult(1,'',$response['msg']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//获取跟踪号
	function getTrackingNO($data){
		try{

			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];
			$result = $this->setKeyToken(['accountid'=>$s->carrier_account_id]);
			if($result)return $result;
			$request = [
				'outboundOrderNum'=>$order->customer_number?$order->customer_number:"",
			];

			$response = $this->winitAPI->processData($request,"queryOutboundOrder");
			// echo '<pre>';
			// print_r($response);
			// echo '</pre>';die;
			###################################################################################
			if($response['code'] == 0){
				$list = $response['data']['list'][0];
				$r = [
					'carrier'=>$list['carrier'],//派送公司名称
					'deliveryCosts'=>$list['deliveryCosts'],//派送费用
					'ebayName'=>$list['ebayName'],//ebay上显示的快递公司名称
					'deliveryCompletionStatus'=>$list['deliveryCompletionStatus'],//派送完成情况
				];
				//将返回回来的数据保存到return_no中方便以后使用
				$shipped = OdOrderShipped::find()->where(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number])->one();
				$shipped->return_no = $r;
				//如果物流商有转运单号返回 则更新系统内的跟踪号
                /** start 万邑通增加参数winitTrackingNo 这个参数不为空，则按它做标记发货。为空，则按原来的trackingNum做为标记发货。2个都为空，则不传*/
                if(!empty($list['winitTrackingNo'])){
                    $shipped->tracking_number = $list['winitTrackingNo'];
                    $shipped->save();
//                     $order->carrier_step = OdOrder::CARRIER_FINISHED;
                    $order->save();
                    $success = empty($list['carrier'])?'':'派送公司:'.$list['carrier'];
                    return self::getResult(0,'','获取跟踪号成功<br/>跟踪号:'.$shipped->tracking_number.'<br/>'.$success);

                }
                else{
                    if(isset($list['trackingNum'])&&$list['trackingNum']){
                        $shipped->tracking_number = $list['trackingNum'];
                        $shipped->save();
                        $order->tracking_number = $shipped->tracking_number;
                        $order->save();
                        $success = empty($list['carrier'])?'':'派送公司:'.$list['carrier'];
                        return self::getResult(0,'','获取跟踪号成功<br/>跟踪号:'.$shipped->tracking_number.'<br/>'.$success);
                    }
                    elseif(empty($list['trackingNum'])){
                        return self::getResult(1,'','物流商暂未生成跟踪号');
                    }
                }
                /** end 万邑通增加参数winitTrackingNo 这个参数不为空，则按它做标记发货。为空，则按原来的trackingNum做为标记发货。2个都为空，则不传*/


			}else if($response['code']=='400006'){
				return self::getResult(1,'','请检查订单是否上传或已删除');
			}else {
				return self::getResult(1,'',$response['msg']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//用来判断是否支持打印
	public static function isPrintOk(){
		return false;
	}

	//打印物流单
	function doPrint($data){
		return self::getResult(1,'','该物流商不支持打印物流单功能');
	}

	//取消出库单
	function cancelOrderNO($data){
		try{

			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			if(!is_array($info))return self::getResult(1,'',$info);
			$s = $info['service'];
			$result = $this->setKeyToken(['accountid'=>$s->carrier_account_id]);
			if($result)return $result;
			$request = [
				'outboundOrderNum'=>$order->customer_number
			];
			$response = $this->winitAPI->processData($request,"voidOutboundOrder");
			###################################################################################
			if($response['code'] == 0){
				$orderShippedObj = OdOrderShipped::findOne(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number]);
				$orderShippedObj->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->customer_number = '';
				$order->save();
				return self::getResult(0,'', '订单取消成功！');
			}else{
				return self::getResult(1,'',$response['msg']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	function Recreate($data){
		return self::getResult(1,'','该物流商不支持重新发货');
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
// 		$data['warehouse_code'] = '1000001';
		
		$pageNum = 1;
		$resultStockList = array();
		while($pageNum < 20){
			$request = [
				'warehouseID'=>$data['warehouse_code'],
				'pageSize'=>100,
				'pageNum'=>$pageNum
			];
			
			$this->winitAPI->app_key = $data['api_params']['app_key'];
			$this->winitAPI->token = $data['api_params']['token'];
			$response = $this->winitAPI->processData($request,"queryWarehouseStorage");
	
			if($response['code'] == 0){
	// 			if($response['data']['total'] > $response['data']['currentPageSize'])
				
				if(isset($response['data']['list'])){
					if(is_array($response['data']['list'])){
						foreach ($response['data']['list'] as $valList){
							$resultStockList[$valList['productCode']] = array(
									'sku'=>$valList['productCode'],
									'productName'=>$valList['productName'],
									'stock_actual'=>$valList['inventory'],				//实际库存
									'stock_reserved'=>$valList['reservedInventory'],	//占用库存
									'stock_pipeline'=>$valList['pipelineInventory'],	//在途库存
									'stock_usable'=>$valList['inventory']-$valList['reservedInventory'],	//可用库存
									'warehouse_code'=>$data['warehouse_code']		//仓库代码
							);
						}
					}
				}
				else
					break;
			}
			else
				break;
			
			$pageNum++;
		}
		
		return self::getResult(0, $resultStockList ,'');
	}

	/*
	 * 获取发货方式
	 */
	function getCarrierShippingService($data){
		//传入仓库ID 查询对应的发货方式
		$request = ['warehouseID'=>$data];
		$response = $this->winitAPI->processData($request,"queryDeliveryWay");
        $str = '';
		foreach($response['data'] as $v){
			$str .= $v['deliveryID'].':'.$v['deliveryWay'].';';
		}
		return $str;


        ########################################
		//仓库ID
		// 'warehouseName' => string 'AU Warehouse' (length=12)
  //       'warehouseID' => int 1000001

		// 'warehouseName' => string 'USWC Warehouse' (length=14)
  //       'warehouseID' => int 1000008

  //       'warehouseName' => string 'UK Warehouse' (length=12)
  //       'warehouseID' => int 1000069

  //       'warehouseName' => string 'DE Warehouse' (length=12)
  //       'warehouseID' => int 1000089
	}
	/*
	 *	@params $data accountid 物流商帐号id
	 */
	function setKeyToken($data){
		//物流账号;
		$account = SysCarrierAccount::find()->where(['id'=>$data['accountid'],'is_used'=>1])->one();
		if(empty($account))return self::getResult(1,'','该运输服务没有分配可用的物流商帐号');
		$a = $account->attributes;
		//上传订单			
		if(!$a['api_params']['token']){
//			//如果用户没有提供token 则通过接口查询
//			$data = [
//						"action" => "getToken",
//						"data" => [
//									"userName" => $a['api_params']['app_key'],
//									"passWord" => $a['api_params']['password']
//								  ]
//					];
//			//成功获取到token
//			$token = $this->winitAPI->setToken($data);
//			//将获取到的token存入用户账户中 下次就不用重复获取
//			$a['api_params']['token'] = $token;
//			$account->api_params = $a['api_params'];
//			$account->save();
//			$this->winitAPI->token = $token;

            return self::getResult(1,'','请填写物流账号token值！');

		}
		$this->winitAPI->app_key = $a['api_params']['app_key'];
		$this->winitAPI->token = $a['api_params']['token'];

	}
	//获取到保险类型
	function getInsuranceType($data){
// 		//请求数据
// 		$request = [
// 			'deliveryWay'=>$data['deliveryWayID']
// 		];
// 		//设置key和token
// 		$this->setKeyToken(['accountid'=>$data['accountid']]);
// 		$return = $this->winitAPI->processData($request,'queryDeliveryInsuranceType');
// 		if($return['code']===0)return $return['data'];
		return false;
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
            $return['message'] = $e->getMessage();
        }
        return $return;
    }

}




// +----------------------------------------------------------------------
// | Winit [ 万邑通信息科技有限公司 ] 
// +----------------------------------------------------------------------
// | Copyright © 2013 Winit Corp http://www.winit.com.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed v1.0
// +----------------------------------------------------------------------
// | Author: Winit
// +----------------------------------------------------------------------

class winitAPI{
	
	// 接口名参数
	protected $action;
	protected $url;
	protected $lang = 'zh_CN';
	protected $token;
	protected $app_key;
	protected $format;
	protected $platform;
	protected $sign_method;
	protected $timestamp;
	protected $version;
	protected $sign;
	protected $data;
	protected $submitGate;
	
	/**
	 +----------------------------------------------------------
	 * 初始化接口信息
	 +----------------------------------------------------------
	 */
	function __construct(){
		$this->submitGate = new SubmitGate();
		// 模型初始化
		$this->format = "json";

		$this->platform = "SELLERERP";//自有ERP客户这里为空，第三方ERP填写ERP的英文名称（需先告知我们添加该信息）
//         $this->platform = "XLB";//万邑通设置每个ERP分别不同的platform code；2016.11.14 新规定

        $this->sign_method = "md5";
		$this->timestamp = date("Y-m-d H:i:s");
		$this->version = "1.0";
		
		if(!function_exists('curl_init')){
			die("服务器不支持Curl");
		}
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取token
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param array $data 账号信息
	 +----------------------------------------------------------
	 * @return 提交成功或失败信息
	 +----------------------------------------------------------
	 * @throws
	 +----------------------------------------------------------
	 */
	public function setToken($data){
		
		$returnToken = $this->getToken($data);

		if ($returnToken === false) {
//			echo $this->getError();
		}else{
			$sessionName = md5($data['data']['userName'].$data['data']['passWord']);
			$_SESSION[$sessionName] = $returnToken;
		}

		return $returnToken;
	}
	
	/**
	 +----------------------------------------------------------
	 * 提交出库单
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param array $data 入参
	 * @param string $action 接口名称
	 +----------------------------------------------------------
	 * @return 提交成功或失败返回信息
	 +----------------------------------------------------------
	 * @throws
	 +----------------------------------------------------------
	 */
	public function processData($data,$action){
		//2014-09-03 --- 加入对data排序功能 ---begin ---
		//echo "<>/>=========================<br/>";
		//print_r($data);
		if(is_array($data)){
			Ksort($data);
			//echo "<br/>=========================<br/>";
			foreach ($data as $key => $value) {
				//if($key == 'productList'){
				//}
				if(is_array($value)){
					Ksort($value);
					//print_r($value);
			        //echo "<br/>=========================<br/>";
					foreach ($value as $k => $v) {
						//print_r($v);
			            //echo "<br/>==============11111===========<br/>";
						 if(is_array($v)){
							 Ksort($v);					 
							 $value[$k] = $v;
							 //print_r($v);
			                 //echo "<br/>=========22222================<br/>";
						 }
					}
					$data[$key] = $value;
				}
				
			}
		}
		
		//print_r($data);
		//echo "<br/>=========================<br/>";
		//2014-09-03 --- 加入对data排序功能 ---end ---



		$this->action = $action;
		$return = $this->process($data);
		
		if ($return === false) {
			return $this->getError();
		}else{
			return $return;
		}
		
	}
	###################################################################################################
	public function __set($key, $var) {  
        $this->$key = $var;  
    }  

	/**
	 +----------------------------------------------------------
	 * 生成令牌
	 +----------------------------------------------------------
	 */
	private function sign(){
		
		$dataArray = array (
	        'action' => $this->action,
			"app_key" => $this->app_key,
			"data" => $this->data,
			"format" => $this->format,
			"platform" => $this->platform,
			"sign_method" => $this->sign_method,
			"timestamp" => $this->timestamp,
			"version" => $this->version
		);
		// 获取令牌字符串，并用MD5加密
		$signString = "";
		foreach ($dataArray as $key=>$val){
			if(is_array($val)){
				$signString .= $key.$this->jsonEncode($val);
			}else{
				$signString .= $key.$val;
			}
		}
		$signString = $this->token . $signString . $this->token;
		$sign = strtoupper(md5($signString));
		
		// 生成发送给API的数组
		$signArray = array(
			"sign" => $sign,
			"language" => $this->lang
		);
		
		$data = array_merge($dataArray,$signArray);
		$returnJson = $this->jsonEncode($data);
		
		return $returnJson;
		
	}
	
	/**
	 +----------------------------------------------------------
	 * 执行接口，返回接口信息
	 +----------------------------------------------------------
	 */
	public function process($data) {
		
		$this->data = $data;
		return $this->getData();
	}


	/**
	 +----------------------------------------------------------
	 * 获取API返回数据
	 +----------------------------------------------------------
	 */
	private function getData() {
		// 获取令牌
		$data = $this->sign();
		
		\Yii::info('LB_WANYITONGOversea,puid:'.',result,order_id:'.' '.$data,"carrier_api");
		
		$info = $this->submitGate->mainGate($this->url, $data, 'curl','POST');
		\Yii::info('winitAPIo,request,'.' '.json_encode($info),"carrier_api");
		
		$info = $this->jsonDecode($info['data']);
		// 返回数据失败
		if($info['code'] > 0){
			$this->error = $info;
    		return false;
		}
		
    	return $info;

	}
	
	/**
	 +----------------------------------------------------------
	 * 获取用户Token
	 +----------------------------------------------------------
	 */
	public function getToken($data){
		$data = $this->jsonEncode($data);
		$response = $this->submitGate->mainGate($this->url, $data, 'curl','POST');
		$token = $this->jsonDecode($response['data']);
		
		// 返回数据失败
		if($token['code']>0 ){
			$this->error = $token['msg'];
    		return false;
		}elseif(empty($token['data'])){
			$this->error = "获取token失败，请联系管理员！";
    		return false;
		}
		
		return $token['data'];
	    	
	}
	
	/**
	 +----------------------------------------------------------
	 * Json转换成数组
	 +----------------------------------------------------------
	 */
	public function jsonDecode($response){
		return json_decode($response,true);
	}
		
	/**
	 +----------------------------------------------------------
	 * 数组转换成json
	 +----------------------------------------------------------
	 */
	public function jsonEncode($data){
		return $this->JSON($data);
	}
	
	/**
	 +----------------------------------------------------------
	 * 取得错误讯息
	 +----------------------------------------------------------
	 */
	public function getError() {
		return $this->error;
	}

	/**************************************************************
	 *
	 *	使用特定function对数组中所有元素做处理
	 *	@param	string	&$array		要处理的字符串
	 *	@param	string	$function	要执行的函数
	 *	@return boolean	$apply_to_keys_also		是否也应用到key上
	 *	@access public
	 *
	 *************************************************************/
	public function arrayRecursive(&$array, $function)
	{
		static $recursive_counter = 0;
		if (++$recursive_counter > 1000) {
			die('possible deep recursion attack');
		}
		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$this->arrayRecursive($array[$key], $function);
			} else {
				$array[$key] = $function($value);
			}
		}
		$recursive_counter--;
	}
	 
	/**************************************************************
	 *
	 *	将数组转换为JSON字符串（兼容中文）
	 *	@param	array	$array		要转换的数组
	 *	@return string		转换得到的json字符串
	 *	@access public
	 *
	 *************************************************************/
	public function JSON($array) {
		$this->arrayRecursive($array, 'urlencode');
		$json = json_encode($array);
		return urldecode($json);
	}
	

}