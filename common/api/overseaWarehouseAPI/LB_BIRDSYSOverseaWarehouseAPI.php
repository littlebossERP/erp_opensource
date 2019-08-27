<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_Curl;
use common\helpers\Helper_Array;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;
use Qiniu\json_decode;

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
 * 鸟系统海外仓物流商API
 +------------------------------------------------------------------------------
 * @category	vendors
 * @package		vendors/overseaWarehouseAPI
 * @subpackage  Exception
 * @author		qfl<772821140@qq.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_BIRDSYSOverseaWarehouseAPI extends BaseOverseaWarehouseAPI 
{
	public $url = null;
	public $company_id = null;
	public $api_key = null;
	public $declarepieces = null;

	public function __construct(){
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			$this->url = 'http://www.birdsystem.co.uk/client/';
		}else{
			$this->url = 'http://test.birdsystem.co.uk/client/';
		}
	}

	function getProductInfo($data,$field='company_ref'){
			$request = [
				'_dc'=>$this->getMicroTime(),
				/* 'field'=>$field,
				'limit'=>1,
				'page'=>1,
				'query'=>$data,
				'selectedFields'=>'id,name,client_ref,company_ref',
				'start'=>0,
				'with_shared'=>false, */
// 				'id'=>$data,
				'client_ref'=>$data,
			];
			$response = $this->doRequest($request,'Product','get',true);
			return $response;
	}

	/*
	 * 检查用户输入的SKU是否存在
	 */
	function checkSku($sku){
		$result = [];
		foreach($sku as $k=>$v){
			//先通过系统id查询 如果没有内容 再通过客户sku进行查询 如果都查询不到 则报错
			$response = $this->getProductInfo($v);
			if(isset($response['message']) && $response['message']=='Login Required!')
				return self::getResult(1,'','请检查物流商帐号中token是否正确');

			if($response['total'] == '0'){
				$response = $this->getProductInfo($v,'client_ref');
				if($response['total'] == '0')
				return self::getResult(1,'',"请检查 商品".$v." SKU是否填写正确,没有查询到该商品信息");
			}
			
			$nums = $this->declarepieces;
			$current_num = $nums[$k];
			//检查库存
			$data = $response['data'];
			$item = $data[0];
			if (empty($item)){
				return self::getResult(1,'',"商品".$v." 未查询到");
			}
			if(isset($item['status']) && $item['status'] != 'ACTIVE')return self::getResult(1,'',"商品".$v." 当前状态不可用,请检查该SKU在鸟系统中的状态");
// 			if( isset($item['status']) && $item['company_product-live_stock'] < $current_num)return self::getResult(1,'',"商品".$v." 当前库存不足");
			$result[] = $item;
		}
		
		return $result;
	}
	
	/*
	 * 查询出订单中所有的商品
	 */
	function getOrderItem($orderId){
		$request = [
			'_dc'=>$this->getMicroTime(),
			'consignment_id'=>$orderId,
			'limit'=>50,
			'page'=>1,
			'selectFields'=>'quantity,unit_product_price,product_id,id',
			'start'=>0,
		];
		return $this->doRequest($request, 'Consignment-Product');
	}

	/*
	 * 删除指定订单内所有的商品
	 */
	function deleteOrderItem($orderId){
		try{
			$result = $this->getOrderItem($orderId);
			if(isset($result->total) && $result->total>0){
				foreach($result->data as $v){
					$request = [
							'_dc'=>$this->getMicroTime(),
							'consignment_id'=>$orderId,
							'selectFields'=>'quantity,unit_product_price,product_id,id',
							];
					$result = $this->doRequest($request, 'Consignment-Product/'.$v->id,'delete',null,2);
					if((isset($result->success) && !$result->success) || empty($result))throw new Exception('鸟系统返回数据错误');
				}
			}
		}catch(Exception $e){return self::getResult(1,'', $e->getMessage());}
	}
	
	//上传订单
	 function getOrderNO($data)
	{
		try{
			// $this->getCarrierCompany(); //获取所有仓库
			// $this->getDeliveryService(1); //获取对应仓库的运输服务params 仓库id号
			
			//订单对象
			$order = $data['order'];
			//表单提交的数据
			$e = $data['data'];
			$this->declarepieces = $e['DeclarePieces'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
// 			$checkResult = CarrierAPIHelper::validate(0,0,$order);
// 			$puid = $checkResult['data']['puid'];
			
			$extra_id = isset($e['extra_id'])?$e['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
			
			//对当前条件的验证，校验是否登录，校验是否已上传过
			$checkResult = CarrierAPIHelper::validate(1, 0, $order,$extra_id,$customer_number);

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];

			$carrier_params = $s->carrier_params;
			//设置api_key和token
			$result = $this->setKeyCompany(['accountid'=>$s->carrier_account_id,'companyid'=>$s->third_party_code]);
			if($result)return $result;
			//先验证用户填写的SKU在系统中是否存在 返回查询到的订单内商品的信息
			 $items = $this->checkSku($e['sku']);
			if(isset($items['error']) && $items['error'])return $items; 
			#######################################################################################
			$customer_number = $data['data']['customer_number'];
				
			$company_type = $this->getCompanyType($s->third_party_code);

			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 35,
							'consignee_address_line2_limit' => 35,
							'consignee_address_line3_limit' => 35,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 50
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$request = [
				'address_line_1'=>$addressAndPhone['address_line1'],
				'address_line_2'=>$addressAndPhone['address_line2'],
				'address_line_3'=>$addressAndPhone['address_line3'],
				// 'arrive_time'=>,//抵达时间
				'business_name'=>$order->consignee_company,	
				'city'=>$order->consignee_city,	
				// 'client_id'=>,
				'contact'=>$order->consignee,	
				'country_iso'=>$order->consignee_country_code=='UK'?'GB':$order->consignee_country_code,
				'county'=>$order->consignee_province,
				'delivery_service_id'=>$s->shipping_method_code,	
				'email'=>$order->consignee_email,	
				// 'id'=>,	
				'is_urgent'=>isset($e['is_urgent'])?$e['is_urgent']:'',	
				// 'neighbour_instruction'=>,//备注2
				'post_code'=>$order->consignee_postal_code,	
				'sales_reference'=>$customer_number,	
				'special_instruction'=>$e['special_instruction'],
				'telephone'=>$addressAndPhone['phone1'],
				'type'=>$company_type,
				'update_time'=>date('Y-m-d H:i:s',time()),
			];

			//添加订单
			if (strlen($order->customer_number)==0){
				$response_order = $this->doRequest($request,'Consignment','post',null,1);
				if (!$response_order->success){
					return self::getResult(1,'',$response_order->message);
				}
				$responseId =isset($response_order->data->id)?number_format($response_order->data->id,0,'',''):'';
			}else{
				$responseId = $order->customer_number;
			}
			//订单创建成功之后 继续向订单内添加商品
			if(strlen($responseId)>0){
				//保存客户订单号
				$order->customer_number = $responseId;
				$order->save();
				//在商品插入之前,需要将订单内已有的商品清空
				$deleteResult = $this->deleteOrderItem($order->customer_number);
				if($deleteResult != null)return $deleteResult;

				foreach($e['sku'] as $k=>$v){
					$request2 = [
						'consignment_id'=>$responseId,//订单号
// 						'product_id'=>$v,//商品id
						'product_id'=>$items[0]['id'],//商品id
						'quantity'=>$e['DeclarePieces'][$k],//发货数量
					];
					$respnose_item = $this->doRequest($request2,'Consignment-Product','post',null,3);
					if (!$respnose_item->success){
						return self::getResult(1,'',$respnose_item->message);
					}
// 					\Yii::info(print_r($respnose_item,1));
				}
				//把添加商品的返回信息记录下来
				#########################################################################################
				//接受到返回信息 进行处理

				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				$r = CarrierAPIHelper::orderSuccess($order,$s,$responseId,OdOrder::CARRIER_WAITING_DELIVERY);

				return self::getResult(0,$r,'发货成功,订单ID号'. $responseId);
			}else{
				return self::getResult(1,'',$response_order->message);
			}    
		}catch(Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

	//交运订单
	function doDispatch($data){
		try{
			$order = $data['order'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];
			
			//设置api_key和token
			$result = $this->setKeyCompany(['accountid'=>$s->carrier_account_id,'companyid'=>$s->third_party_code]);
			if($result)return $result;

			$request = [
				'ids'=>$order->customer_number,
				'status'=>'PENDING',
			];
			$response = $this->doRequest($request,'Consignment/Batch-Update-Status','post');
			###################################################################################
			if(!isset($response->success)){
				return self::getResult(1,'','请检查物流商帐号中token及运输服务中仓库是否有误');
			}else if($response->success){
				$order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
				$order->save();
				return self::getResult(0,'', '确认订单成功！订单状态已更改为[待处理]');
			}else{
				return self::getResult(1,'',$response->message);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//获取跟踪号
	function getTrackingNO($data){
		try{
			$order = $data['order'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];

			//设置api_key和token
			$result = $this->setKeyCompany(['accountid'=>$s->carrier_account_id,'companyid'=>$s->third_party_code]);
			if($result)return $result;

			$request = [
				'id'=>$order->customer_number,
			];
			$response = $this->doRequest($request,'Consignment');

			if($response->total>0){
				$response = $response->data;
				$response = $response[0];
				//如果有跟踪号返回
				if($response->delivery_reference){
					$shipped->tracking_number = $response->delivery_reference;
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					return self::getResult(0,'','获取订单跟踪号成功:'.$response->delivery_reference);
				}else return self::getResult(1,'','物流商暂时还没有跟踪号返回');
			}else{
				return self::getResult(1,'','数据返回错误,请检查订单状态');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//用来判断是否支持打印
	static function isPrintOk(){
		return false;
	}

	//打印物流单
	function doPrint($data){
		return self::getResult(1,'','该物流商不支持打印物流单');
	}



	//取消出库单
	function cancelOrderNO($data){
		try{

			$order = $data['order'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			//设置api_key和token
			$result = $this->setKeyCompany(['accountid'=>$s->carrier_account_id,'companyid'=>$s->third_party_code]);
			if($result)return $result;

			$request = [
				'ids'=>$order->customer_number,
				'status'=>'CANCELLED',
			];
			$response = $this->doRequest($request,'Consignment/Batch-Update-Status','post');
			###################################################################################
			if(!isset($response->success)){
				return self::getResult(1,'','请检查物流商帐号中token及运输服务中仓库是否有误');
			}else if($response->success){
				$shipped->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->customer_number='';
				$order->save();
				return self::getResult(0,'', '该订单已成功取消');
			}else{
				return self::getResult(1,'',$response->message);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//重新发货
	function Recreate($data){
		return self::getResult(1,'','该物流商不支持重新发货');
	}

#########################################################################################################

	/*
	* 获取仓库类型 判断是专线还是本地 
	*/
	 function getCompanyType($id){
		//目前只有11 深圳仓使用专线 如果后期有新的国内仓出现 需要修改这里的判断条件
		if($id == 11)return 'DIRECT';
		return 'LOCAL';
	}

	/*
	* 获取毫秒时间戳
	*/
	 function getMicroTime(){
		$time = microtime();
		$arr = explode(' ',$time);
		$microSec = floor($arr[0]*1000);
		return $arr[1].$microSec;
	}

	

	/*
	* 发送数据
	*/
	 function doRequest($params,$operate,$method='get',$arr=false,$puid=0){
		try{
			$header = [
				'company_id:'.$this->company_id,
				'api_key:'.$this->api_key,
			];
			$url = $this->url.$operate;

			$str = '';
			if($method=='get' || $method == 'delete'){
				foreach($params as $k=>$v){
					if(is_array($v))$str .= $k.'='.json_encode($v).'&';
					else $str .= $k.'='.$v.'&';
				}
				$str = rtrim($str,'&');
				$url .= '?'.$str;
			}
// 			var_dump($url);
// 			var_dump($params);
// 			var_dump($header);die;
			\Yii::info('LB_BIRDSYSOverseaWarehouse,puid:,request,order_id:'.json_encode($params),"carrier_api");
			$response = Helper_Curl::$method($url,$params,$header);
			\Yii::info('LB_BIRDSYSOverseaWarehouse,puid:,response,order_id: '.$response,"carrier_api");
			if(is_null(json_decode($response,$arr)))return $response;
			return json_decode($response,$arr);
		}catch(Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

	//替换页面中的条形码
	function replaceBarCode(&$response,$id,$content){
		$js = '<script>
			$("img[alt=\''.$id.'\']").attr("src","'.$content.'");
			$("img[alt=\'stamp\']").attr("src","");
			</script>
		';
		$response .= $js;

	}

	//通过接口获取条形码
	function getBarCode($id,$puid){
		$request = [
			'barHeight'=>25,
		];
		$response = $this->doRequest($request,'Printer/Barcode/'.$id);
		return CarrierAPIHelper::savePDF($response,$puid,$id,1,'png');
	}

	/*
	 *	@params $data accountid 物流商帐号id
	 */
	function setKeyCompany($data){
		//物流账号;
		$account = SysCarrierAccount::find()->where(['id'=>$data['accountid'],'is_used'=>1])->one();
		if(empty($account))return self::getResult(1,'','该运输服务没有分配可用的物流商帐号');
		$a = $account->attributes;
		//上传订单			
		if(!$a['api_params']['token']){
			return self::getResult(1,'','请在物流商帐号中设置正确的Token值,如有困难请联系客户经理');
		}
		$this->api_key = $a['api_params']['token'];
		$this->company_id = $data['companyid'];
		//判断是否已经登陆过
		// if(!isset($_COOKIE['bs_default_company_id']))
		// if(!$this->loginSystem())return self::getResult(1,'','尝试登陆物流商系统失败,请检查帐号密码');
	}




################################################################################################

    /*
	* 获取物流商仓库
	*/
	 function getCarrierCompany(){// dzt20150915 与飞鸟技术交涉得 这个接口返回的 除了英国二三站和英国O2O 站站外（15，16，19），其他都可以对接
		$params = [
		];
		$result = $this->doRequest($params,'Public/Company-List');
// 		print_r($result);
		$str = '';
		foreach($result->data as $v){
			$str .= '"'.$v->id.'"=>"'.$v->site_name.'",<br/>';
		}
		echo $str;die;
	}

	/*
	* 获取各仓库运输服务
	*/
	 function getDeliveryService($id){
		$this->company_id=$id;//dzt20150915 与飞鸟技术交涉得 测试场 只开了英国一站，其他站都提示login 
		$this->api_key = '1deff2a034285dc72b58e2745f990dc1';
				
		$params = [
			'filter'=>[
				[
					'property'=>'consignment_type',
					'value'=>'DIRECT',// dzt20150915 深圳仓 对应DIRECT ， 其他仓对应Local
				],
				[
					'property'=>'status',// dzt20150915 运输服务的过滤基本上只要 status=>ACTIVE就可以了
					'value'=>'ACTIVE',
				],
				[
					'property'=>'is_internal',
					'value'=>'false',
				],
			],
		];
		$result = $this->doRequest($params,'Delivery-Service');
// 		print_r($result);exit();
		$str = '';
		foreach($result->data as $v){
			if($v->name)$str .= '"'.$v->id.'"=>"'.$v->name.'",<br/>';
		}
		echo $str;die;
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
// 				$this->company_id='1';//dzt20150915 与飞鸟技术交涉得 测试场 只开了英国一站，其他站都提示login 
// 				$this->api_key = $data['token'];
				
// 				$params = [
// 					'filter'=>[
// 						[
// 							'property'=>'consignment_type',
// 							'value'=>'Local',// dzt20150915 深圳仓 对应DIRECT ， 其他仓对应Local
// 						],
// 						[
// 							'property'=>'status',// dzt20150915 运输服务的过滤基本上只要 status=>ACTIVE就可以了
// 							'value'=>'ACTIVE',
// 						],
// 						[
// 							'property'=>'is_internal',
// 							'value'=>'false',
// 						],
// 					],
// 				];
// 				$response = $this->doRequest($params,'Delivery-Service');

// 				if(isset($response->success) && $response->success)
// 					$result['error'] = 0;
// 		}catch(CarrierException $e){
// 		}
	
		return $result;
	}

}