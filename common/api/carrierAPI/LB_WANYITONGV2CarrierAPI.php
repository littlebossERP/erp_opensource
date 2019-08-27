<?php
namespace common\api\carrierAPI;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\order\models\OdEbayTransaction;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\SubmitGate;
use common\helpers\Helper_Array;
use eagle\modules\carrier\helpers\CarrierException;
use \Jurosh\PDFMerge\PDFMerger;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-07-10
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 万邑通ISP
 +------------------------------------------------------------------------------
 * @category	vendors
 * @package		vendors/carrierAPI
 * @subpackage  Exception
 * @author		qfl<772821140@qq.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_WANYITONGV2CarrierAPI extends BaseCarrierAPI 
{
	public $winitAPI = null;

	/*
	 * 帐号 xlbtest    密码888    token  1E14E2D6331300220530E6104B55F0D5
	 */
	public function __construct(){
		$this->winitAPI = new winitAPI();
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			$this->winitAPI->url = 'http://openapi.winit.com.cn/openapi/service';
		}else {
			$this->winitAPI->url = 'http://openapi.demo.winit.com.cn/openapi/service';
		}
	}

	//获取验货仓列表
	public function getWareHouseList($method_code,$accountid){
		try{
			
			$this->setKeyToken(['accountid'=>$accountid]);
			$request = [
				'productCode'=>$method_code,
			];
			return $this->winitAPI->processData($request,"baseData.inspWarehouseList");
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	function getOrderNO($data)
	{
		try{
			//订单对象
			$order = $data['order'];
			
			//重复发货 添加不同的标识码
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
				
			if(isset($data['data']['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}
			
			//表单提交的数据
			$e = $data['data'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
			$puid = $checkResult['data']['puid'];
			
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$tmp_a = $info['account'];
			$api_params = $tmp_a->api_params;
			
			$tmp_ebayTrackingType = empty($service['carrier_params']['ebayTrackingType']) ? 0 : $service['carrier_params']['ebayTrackingType'];
			$tmp_otherTrackingType = empty($service['carrier_params']['otherTrackingType']) ? 0 : $service['carrier_params']['otherTrackingType'];

			//设置帐号和token
			$this->setKeyToken(['accountid'=>$service->carrier_account_id]);
			#######################################################################################
				
			//万邑通规则bug
// 			if(strlen($order->consignee_address_line1)>30)throw new CarrierException('发货地址长度不能超过30');
            if(empty($service['carrier_params']['shipperAddrCode'])){
                return self::getResult(1,'','上传失败，请填写万邑通系统绑定的地址编号');
            }
            
            if(empty($service['carrier_params']['dispatchType'])){
                return self::getResult(1,'','上传失败，请选择相应渠道的发货方式');
            }
            
            if(empty($service['carrier_params']['warehouseCode'])){
                return self::getResult(1,'','上传失败，请填写仓库code');
            }
            
            $addressAndPhoneParams = array(
                'address' => array(
                    'consignee_address_line1_limit' => 100,
                    'consignee_address_line2_limit' => 100,
                ),
                'consignee_district' => 1,
                'consignee_county' => 1,
                'consignee_company' => 1,
                'consignee_phone_limit' => 20
            );
            	
            $addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
            
			$merchandiseList = [];
			foreach($order->items as $k=>$v){
			    if($order->order_source == 'ebay'){
			        $transactionid = $v->order_source_transactionid;
			        $itemid = $v->order_source_itemid;
			    }else{
			        $transactionid = '';
			        $itemid = '';
			    }
				$merchandiseList[] = [
					'transactionID'=>$transactionid,//交易id
					'itemID'=>$itemid,//条目ID
					'declaredValue'=>$e['declaredValue'][$k],//价格
					'declaredNameCn'=>$e['declaredNameCn'][$k],//中文名
					'declaredNameEn'=>$e['declaredNameEn'][$k],//英文名
				    'merchandiseQuantity'=>$e['DeclarePieces'][$k],//申报数量
				];
			}
            if($order->consignee_country_code == 'GB'){
                $country_code = 'UK';
            }else{
                $country_code = $order->consignee_country_code;
            }
			$request = [
				'refNo'=>$customer_number,//卖家订单号
				'pickUpCode'=>$customer_number,//有内容才能打印
				'winitProductCode'=>$service->shipping_method_code,//winit产品编码
				'warehouseCode'=>$service['carrier_params']['warehouseCode'],//仓库Code
				'dispatchType'=>$service['carrier_params']['dispatchType'],//发货方式
				'shipperAddrCode'=>$service['carrier_params']['shipperAddrCode'],//寄件人地址code
				'buyerName'=>$order->consignee,//收件人名字
				'buyerContactNo'=>$addressAndPhone['phone1'],//收件人电话
				'buyerEmail'=>\eagle\modules\carrier\helpers\CarrierOpenHelper::getOrderEmailResults($order->consignee_email),//收件人邮箱
				'buyerZipCode'=>$order->consignee_postal_code,//收件人邮编
				'buyerCountry'=>$country_code,//收件人国家
				'buyerState'=>!empty($order->consignee_province)?$order->consignee_province:$order->consignee_city,//收件人州
				'buyerCity'=>$order->consignee_city,//收件人城市
				'buyerAddress1'=>$addressAndPhone['address_line1'],//收件人地址1
				'buyerAddress2'=>$addressAndPhone['address_line2'],//收件人地址2
				'ebaySellerId'=>$order->selleruserid,//ebay卖家ID（中邮产品必填）
				// 'buyerHouseNo'=>$order->consignee,//收件人门牌号
				'packageList'=>[
					[
						'packageDesc'=>$e['packageDesc'],
						'weight'=>$e['weight']/1000,
						'length'=>intval($e['length']),
						'width'=>intval($e['width']),
						'height'=>intval($e['height']),
						'merchandiseList'=>$merchandiseList,
					],
				],
			];

			Helper_Array::removeEmpty($request);
			$response = $this->winitAPI->processData($request,"isp.order.createOrder");
			
			//添加Log
			\Yii::info('lb_winit,response1,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
			\Yii::info('lb_winit,request1,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			
		    #########################################################################################
			//接受到返回信息 进行处理
			if($response['code'] === '0'){
				if(empty($response['data']['orderNo'])){
					return self::getResult(1,'','上传失败,万邑通返回结构错误,请联系小老板技术e1');
				}
				
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				if($order->order_source == 'ebay'){
					if(empty($tmp_ebayTrackingType)){
						$r = CarrierAPIHelper::orderSuccess($order,$service,$response['data']['orderNo'],OdOrder::CARRIER_WAITING_DELIVERY,$response['data']['orderNo'],['TrackingNo'=>$response['data']['trackingNo']]);
					}else{
						$r = CarrierAPIHelper::orderSuccess($order,$service,$response['data']['orderNo'],OdOrder::CARRIER_WAITING_DELIVERY,$response['data']['trackingNo'],['TrackingNo'=>$response['data']['trackingNo']]);
					}
				}else{
					if(empty($tmp_otherTrackingType)){
						$r = CarrierAPIHelper::orderSuccess($order,$service,$response['data']['orderNo'],OdOrder::CARRIER_WAITING_DELIVERY,$response['data']['trackingNo']);
					}else{
						$r = CarrierAPIHelper::orderSuccess($order,$service,$response['data']['orderNo'],OdOrder::CARRIER_WAITING_DELIVERY,$response['data']['orderNo'],['TrackingNo'=>$response['data']['trackingNo']]);
					}
				}
				
				try{
					//插入PDF队列
					$print_param = array();
					$print_param['carrier_code'] = $service->carrier_code;
					$print_param['api_class'] = 'LB_WANYITONGCarrierAPI';
					$print_param['app_key'] = $api_params['app_key'];
					$print_param['token'] = $api_params['token'];
// 					$print_param['carrier_params'] = $service->carrier_params;
					$print_param['run_status'] = 4;
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $response['data']['orderNo'], $print_param);
				}catch (\Exception $ex){
				}
				
				return self::getResult(0,$r,'上传成功,出库单号:'.$response['data']['orderNo']);
			}else{
			    //针对重复订单处理
			    preg_match('/TrancationId(.*?)ItemID(.*?)信息重复，请核对后下单/i', $response['msg'], $message_match);
			    preg_match('/transactionId(.*?)iteamId(.*?)重复/i', $response['msg'], $message_match1);
			    preg_match('/TransactionId(.*?)iteamId(.*?)重复/i', $response['msg'], $message_match2);
			    if(!empty($message_match)||!empty($message_match1)||!empty($message_match2)){
			        if(!empty($request['packageList'][0]['merchandiseList'])){
			            foreach ($request['packageList'][0]['merchandiseList'] as &$reSetList){
			                $reSetList['transactionID'] = '';
			                $reSetList['itemID'] = '';
			            }
			        }
			        $response = $this->winitAPI->processData($request,"isp.order.createOrder");
			        
			        //添加Log
			        \Yii::info('lb_winit,response2,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
			        \Yii::info('lb_winit,request2,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			        
			        if($response['code'] === '0'){
			        	if(empty($response['data']['orderNo'])){
			        		return self::getResult(1,'','上传失败,万邑通返回结构错误,请联系小老板技术e2');
			        	}
			        	
				        //上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
            			if($order->order_source == 'ebay'){
            				if(empty($tmp_ebayTrackingType)){
            					$r = CarrierAPIHelper::orderSuccess($order,$service,$response['data']['orderNo'],OdOrder::CARRIER_WAITING_DELIVERY,$response['data']['orderNo'],['TrackingNo'=>$response['data']['trackingNo']]);
            				}else{
            					$r = CarrierAPIHelper::orderSuccess($order,$service,$response['data']['orderNo'],OdOrder::CARRIER_WAITING_DELIVERY,$response['data']['trackingNo'],['TrackingNo'=>$response['data']['trackingNo']]);
            				}
            			}else{
            				if(empty($tmp_otherTrackingType)){
            					$r = CarrierAPIHelper::orderSuccess($order,$service,$response['data']['orderNo'],OdOrder::CARRIER_WAITING_DELIVERY,$response['data']['trackingNo']);
            				}else{
            					$r = CarrierAPIHelper::orderSuccess($order,$service,$response['data']['orderNo'],OdOrder::CARRIER_WAITING_DELIVERY,$response['data']['orderNo'],['TrackingNo'=>$response['data']['trackingNo']]);
            				}
            			}
            			
            			try{
            				//插入PDF队列
            				$print_param = array();
            				$print_param['carrier_code'] = $service->carrier_code;
            				$print_param['api_class'] = 'LB_WANYITONGCarrierAPI';
            				$print_param['app_key'] = $api_params['app_key'];
							$print_param['token'] = $api_params['token'];
//             				$print_param['carrier_params'] = $service->carrier_params;
            				$print_param['run_status'] = 4;
            				CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $response['data']['orderNo'], $print_param);
            			}catch (\Exception $ex){
            			}
            			return self::getResult(0,$r,'上传成功,出库单号:'.$response['data']['orderNo']);
            		}else{
            		    return self::getResult(1,'','上传失败,'.$response['msg']);
            		}
			    }else{
			        return self::getResult(1,'','上传失败,'.$response['msg']);
			    }
				
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//交运订单
	function doDispatch($data){
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			// if(!is_array($info))return self::getResult(1,'',$info);
			$service = $info['service'];

			//设置key&token
			$this->setKeyToken(['accountid'=>$service->carrier_account_id]);
			// if($result)return $result;

			//组织数据
			$request = [
				'orderNo'=>$order->customer_number
			];
			//发送
			$response = $this->winitAPI->processData($request,"isp.delivery.confirm");
			###################################################################################
			if($response['code'] == 0){
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return self::getResult(0,'', '确认订单成功！订单号：'.$order->customer_number);
			}else{
				return self::getResult(1,'',$response['msg']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	//获取物流号
	function getTrackingNO($data){
		//重新实现只是为了获取插入打印参数
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
				
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
		
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$a = $info['account'];
			$service = $info['service'];// 运输服务相关信息
			//获取到帐号中的认证参数
			$api_params = $a->api_params;
		
			if(!empty($shipped->tracking_number)){
				$print_param = array();
				$print_param['carrier_code'] = $service->carrier_code;
				$print_param['api_class'] = 'LB_WANYITONGCarrierAPI';
				$print_param['app_key'] = $api_params['app_key'];
				$print_param['token'] = $api_params['token'];
				$print_param['carrier_params'] = $service->carrier_params;
	
				try{
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
				}catch (\Exception $ex){
				}
			}
		}catch(CarrierException $e){}
		
	    return self::getResult(1, '', '物流接口不支持获取跟踪号');//创建订单自动获取跟踪号，且调用会影响ebay的订单
	}

	//用来判断是否支持打印
	public static function isPrintOk(){
		return true;
	}

	/*
	 * 用来确定打印完成后 订单的下一步状态
	 */
	public static function getOrderNextStatus(){
	    return OdOrder::CARRIER_FINISHED;
	}

	//打印物流单
	function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();

			$pdf = new PDFMerger();
			$result = [];
			$order_first = current($data);
			$order = $order_first['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$service = $info['service'];

			//设置key&token
			$this->setKeyToken(['accountid'=>$service->carrier_account_id]);

			foreach($data as $v){
				$order = $v['order'];

				//组织数据
				$request = [
					'orderNo'=>$order->customer_number
				];
				//发送
				$response = $this->winitAPI->processData($request,"winitLable.query");
				###################################################################################
				if($response['code'] == 0){
					//返回的数据为base64编码的数据流，需要进行处理
					$pdf_content = base64_decode($response['data']['files'][0]);

					$pdfUrl = CarrierAPIHelper::savePDF($pdf_content,$puid,$order->customer_number.$service->carrier_code,0);
					$pdf->addPDF($pdfUrl['filePath'],'all');
					//添加订单标签打印时间
// 					$order->is_print_carrier = 1;
					$order->print_carrier_operator = $puid;
					$order->printtime = time();
					$order->save();
				}else{
					$result[$order->order_id] = self::getResult(1, '', $response['msg']);
				}
			}
			//合并多个PDF  这里还需要进一步测试
			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['pdfUrl']='';
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl'],'errors'=>$result],'连接已生成,请点击并打印');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//获取API的打印面单标签
	public function getCarrierLabelApiPdf($SAA_obj, $print_param){
		try{
			$puid = $SAA_obj->uid;
		
			//设置key&token
			$this->winitAPI->app_key = trim($print_param['app_key']);
			$this->winitAPI->token = trim($print_param['token']);
		
			//组织数据
			$request = [
				'orderNo'=>$SAA_obj->customer_number
			];
			
			//发送
			$response = $this->winitAPI->processData($request,"winitLable.query");
			if($response['code'] == 0){
				//返回的数据为base64编码的数据流，需要进行处理
				$pdf_content = base64_decode($response['data']['files'][0]);
	
				$pdfPath = CarrierAPIHelper::savePDF2($pdf_content, $puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
				return $pdfPath;
			}else{
				return ['error'=>1, 'msg'=>$response['msg'], 'filePath'=>''];
			}
		}catch(CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
	
	/**
	 * 查询物流轨迹
	 * @param 	string 	万邑通ISP跟踪号或客户参考号
	 * @return  array
	 */
	public function SyncStatus($data){
	    $res = array();
	    
	    //设置key&token
	    // TODO carrier user account
	    $this->winitAPI->app_key = '';
	    $this->winitAPI->token = '';
	    $request = [
	        'trackingNOs'=>$data[0]
	    ];
	    //发送
	    $response = $this->winitAPI->processData($request,"tracking.getOrderTracking");
	    if ($response['code'] != 0) {
	        $res['error'] = "1";
	        $res['trackContent'] = $response['msg'];
	        $res['referenceNumber']=$data;
	    }else{
	        if($response['code'] == 0&&!empty($response['data'])){
	            $response_array = $response['data'][0];
	            $res['error']="0";
	            $res['referenceNumber'] = $response_array['orderNo'];
	            $res['destinationCountryCode'] = $response_array['destination'];
	            $res['trackingNumber'] = $response_array['trackingNo'];
	            $res['trackContent']=[];
	            if(!empty($response_array['trace'])){//判断是否有轨迹数据
	                foreach($response_array['trace'] as $i=>$t){
	                    $res['trackContent'][$i]['createDate'] = '';
	                    $res['trackContent'][$i]['createPerson'] = '';
	                    $res['trackContent'][$i]['occurAddress'] = empty($t['location'])?'':$t['location'];
	                    $res['trackContent'][$i]['occurDate'] = empty($t['date'])?'':$t['date'];
	                    $res['trackContent'][$i]['trackCode'] = empty($t['eventCode'])?'':$t['eventCode'];
	                    $res['trackContent'][$i]['trackContent'] = empty($t['eventDescription'])?'':$t['eventDescription'];
	                }
	                $res['trackContent'] = array_reverse($res['trackContent']);//时间倒序
	            }
	
	        }else{
	            $res['error'] = "1";
	            $res['trackContent'] = "没有查询到相关数据";
	            $res['referenceNumber'] = $data;
	        }
	
	
	    }
	    return $res;
	}
	//取消出库单
	function cancelOrderNO($data){
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order,0);
			$s = $info['service'];

			$this->setKeyToken(['accountid'=>$s->carrier_account_id]);

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


	/*
	 * 获取发货方式
	 */
	function getCarrierShippingService(){
		$this->winitAPI->url = 'http://openapi.winit.com.cn/openapi/service';
		// TODO carrier user account
		$this->winitAPI->app_key = '';
		$this->winitAPI->token = '';
		$result = $this->winitAPI->processData([''=>''],'winitProduct.list');
		$str = '';
		foreach($result['data'] as $v){
			$str .= $v['productCode'].':'.$v['productName'].';';
		}
		echo $str;die;
	}
	/*
	 *	@params $data accountid 物流商帐号id
	 */
	function setKeyToken($data){
		//物流账号;
		$account = SysCarrierAccount::find()->where(['id'=>$data['accountid'],'is_used'=>1])->one();
		if($account == null)throw new CarrierException("该运输服务没有分配可用的物流商帐号");
		
		$a = $account->attributes;
		//上传订单			
		if(!$a['api_params']['token']){
			//如果用户没有提供token 则通过接口查询
		    throw new CarrierException("该物流商帐号没有填写有效token");
		}
		if(!isset($a['api_params']['app_key'])){
		    throw new CarrierException("该物流商帐号没有填写有效帐号");
		}
		$this->winitAPI->app_key = $a['api_params']['app_key'];
		$this->winitAPI->token = $a['api_params']['token'];
	}
	//获取到保险类型
	function getInsuranceType($data){
		//请求数据
		$request = [
			'deliveryWay'=>$data['deliveryWayID']
		];
		//设置key和token
		$this->setKeyToken(['accountid'=>$data['accountid']]);
		$return = $this->winitAPI->processData($request,'queryDeliveryInsuranceType');
		if($return['code']===0)return $return['data'];
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

    //获取token
    public function getToken($username,$pwd){
    	$data = [
					"action" => "user.getToken",
					"data" => [
								"userName" => $username,
								"passWord" => $pwd
							  ]
				];
		//成功获取到token
		$token = $this->winitAPI->setToken($data);
		return $token;
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
//             $aa = $this->getToken($data['app_key'],$data['password']);
//             if(isset($aa['token'])){
//                 $result['error'] = 0;
//             }
	        $this->winitAPI->url = 'http://openapi.winit.com.cn/openapi/service';
	        $this->winitAPI->app_key = $data['app_key'];
	        $this->winitAPI->token = $data['token'];
	        $check_result = $this->winitAPI->processData([''=>''],'winitProduct.list');
	        if(!empty($check_result['data'])){
	            $result['error'] = 0;
	        }
	    }catch(CarrierException $e){
	    }
	    
        return $result;
    }
    
    //获取运输方式
    public function getCarrierShippingServiceStr_T1($account){
    	//return self::getResult(1, '', '');
        try{
            $this->winitAPI->url = 'http://openapi.winit.com.cn/openapi/service';
			// TODO carrier user account @XXX@
    		$this->winitAPI->app_key = '@XXX@';
    		$this->winitAPI->token = '@XXX@';
    		$result = $this->winitAPI->processData([''=>''],'winitProduct.list');
    		$channelStr = '';
			//print_r ($result);exit;
    		foreach($result['data'] as $v){
    			$channelStr .= $v['productCode'].':'.$v['productName'].';';
    		}
    		
            if(empty($channelStr)){
                return self::getResult(1, '', '');
            }else{
                return self::getResult(0, $channelStr, '');
            }
        }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
	// TODO carrier dev account @XXX@
	protected $client_id = '@XXX@';
	protected $client_secret = '@XXX@';
	/**
	 +----------------------------------------------------------
	 * 初始化接口信息
	 +----------------------------------------------------------
	 */
	function __construct(){
		$this->submitGate = new SubmitGate();
		// 模型初始化
		$this->format = "json";
		// TODO carrier dev account @XXX@
		$this->platform = "@XXX@";//自有ERP客户这里为空，第三方ERP填写ERP的英文名称（需先告知我们添加该信息）
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
			\Yii::info($this->getError());
		}else{
			$sessionName = md5($data['data']['userName'].$data['data']['passWord']);
			$_SESSION[$sessionName] = $returnToken;
		}

		return $returnToken;
	}

	//对数据进行递归排序处理
	public function sortArray($data){
		$arr = [];
		if(is_array($data)){
			Ksort($data);
			foreach($data as $k=>$v){
				if(is_array($v)) $arr[$k] = $this->sortArray($v);
				else $arr[$k] = $v;
			}
			return $arr;
		}
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
		//改良了winit数组的排序功能,改用递归实现
		$data = $this->sortArray($data);
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
		
		try{
			$user=\Yii::$app->user->identity;
	    	$puid = $user->getParentUid();
		}  catch(\Exception $e){
			$puid = 0;
		}
		
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
		};
		$signString = $this->token . $signString . $this->token;
		$sign = strtoupper(md5($signString));


		$client_sign_string = $this->client_secret . $signString . $this->client_secret;
		$client_sign = strtoupper(md5($client_sign_string));
		

		// 生成发送给API的数组
		$signArray = array(
			"sign" => $sign,
			"language" => $this->lang,
			//"client_id"=>$this->client_id,
			//"client_sign"=>$client_sign
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
		$info = $this->submitGate->mainGate($this->url, $data, 'curl','POST');
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
		$json = $this->JSON($data);
		//加入特殊字符判断
		if(json_decode($json)==null)throw new CarrierException('请检查输入数据是否有特殊字符');
		return $json;
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