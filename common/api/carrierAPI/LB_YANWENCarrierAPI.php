<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Author: qfl<772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015/3/26
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use common\helpers\Helper_Curl;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrderItem;
/**
 +------------------------------------------------------------------------------
 * 燕文物流接口业务逻辑类
 +------------------------------------------------------------------------------
 * @author	million<88028624@qq.com>
 +------------------------------------------------------------------------------
 */
class LB_YANWENCarrierAPI extends BaseCarrierAPI
{
	/**
	 * 用户ID
	 * @var string
	 */
	public $userID;
	/**
	 * 用户 token
	 * @var long string
	 */
	public $AuthToken;
	/**
	 * 请求地址
	 *
	 */
	public $serverUrl;
	/**
	 * 基础请求地址
	 *
	 */
	public $baseUrl;
	public $requestBody;
	
	public function __construct()
	{
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			$this->baseUrl="http://Online.yw56.com.cn/service"; //账号 100000 密码100001
		}else{
			$this->baseUrl="http://Online.yw56.com.cn/service_sandbox";
		}
	}


	/**
	 +----------------------------------------------------------
	 * 上传订单
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
		    $user=\Yii::$app->user->identity;
		    if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
		    $puid = $user->getParentUid();
		    
			//获取平台发货方式代码 可以定期执行 更新后台物流参数
			// self::getChannels();
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
			
			//用户输入的内容
			$data = $data['data'];
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
// 			$puid = $checkResult['data']['puid'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$s = $info['service'];
			$account = $info['account'];
			$a = $account->attributes;
			#######################################################################################
			//上传订单			
			$userid = $a['api_params']['userid'];
			$token = $a['api_params']['token'];
			//商品信息
			$quantity = 0;
			$MoreGoodsName ='';
			$product = array('Weight'=>0,'DeclaredValue'=>0);
// 			$GetItems = OdOrderItem::find()->where(['order_source_order_id'=>$order->order_source_order_id])->asArray()->all();//取代$order->items
			for($key = 0;$key < count($data['DeclarePieces']);$key++){
// 				$quantity+=empty($data['DeclarePieces'][$key])?$item['quantity']:$data['DeclarePieces'][$key];
// 				$q = empty($data['DeclarePieces'][$key])?$item['quantity']:$data['DeclarePieces'][$key];
				$quantity += $data['DeclarePieces'][$key];
				$q = $data['DeclarePieces'][$key];
				if ($key==0){
				    if(strlen($data['Name'][$key])>64){
				        return self::getResult(1,'','商品中文品名长度超出长度限制64');
				    }else{
				        $product['NameCh']=$data['Name'][$key];
				    }
				   
				    if(strlen($data['EName'][$key])>64){
				        return self::getResult(1,'','商品英文品名长度超出长度限制64');
				    }elseif(stripos($data['EName'][$key], "&") !== false){
				        return self::getResult(1,'','商品英文名不能包含特殊字符“&”');
				    }else{
				        $product['NameEn']=$data['EName'][$key];
				    }
					//$product['Weight']+=$data['weight'][$key]*$q;
// 					$product['DeclaredValue']=empty($data['DeclaredValue'][$key])?$item['price']:$data['DeclaredValue'][$key];
					$product['DeclaredCurrency']=$data['Currency'];
					if (!empty($data['HsCode'][$key])){
						$product['HsCode']=$data['HsCode'][$key];
					}
					//中俄SPSR
					if(isset($data['ProductBrand'][$key])){
					    $product['ProductBrand']=$data['ProductBrand'][$key];
					}else{
					    $product['ProductBrand'] = '';
					}
					
					if(isset($data['ProductSize'][$key])){
					    $product['ProductSize']=$data['ProductSize'][$key];
					}else{
					    $product['ProductSize'] = '';
					}
					
					if(isset($data['ProductColor'][$key])){
					    $product['ProductColor']=$data['ProductColor'][$key];
					}else{
					    $product['ProductColor'] = '';
					}
					
					if(isset($data['ProductMaterial'][$key])){
					    $product['ProductMaterial']=$data['ProductMaterial'][$key];
					}else{
					    $product['ProductMaterial'] = '';
					}
				}
// 				$Value = empty($data['DeclaredValue'][$key])?$item['price']:$data['DeclaredValue'][$key];
				$Value = $data['DeclaredValue'][$key];
				$product['DeclaredValue']+=$Value*$q;
				$product['Weight']+=$data['weight'][$key]*$q;
				$MoreGoodsName.=$data['Name'][$key].";";
			}
// 			$extra_id = isset($data['extra_id'])?$data['extra_id']:'';
			$carrier_params = $s->carrier_params;
// 			$phone = strlen($order->consignee_phone)>4?$order->consignee_phone:$order->consignee_mobile;
// 			$customer_number = CarrierAPIHelper::getCustomerNum($order,$extra_id);
// 			if ($s->shipping_method_code == '5' || $s->shipping_method_code=='45'){
// 				$epcode = rand(1,9).str_pad($order->order_id, 8,0,STR_PAD_LEFT);
// 			}else{
// 				$epcode = $customer_number;
// 			}
// 			$address1=!empty($order->consignee_address_line1)?$order->consignee_address_line1:(!empty($order->consignee_address_line2)?$order->consignee_address_line2:$order->consignee_address_line3);
// 			if(empty($address1)){
// 			    return self::getResult(1,'','发货地址不能为空');
// 			}else{
// 			    if(!empty($order->consignee_address_line1)&&$order->consignee_address_line1 != $address1){
// 			        $address2 = $order->consignee_address_line1;
// 			    }else if(!empty($order->consignee_address_line2)&&$order->consignee_address_line2 != $address1){
// 			        $address2 = $order->consignee_address_line2;
// 			    }else{
// 			        $address2 = ($order->consignee_address_line3 == $address1)?'':$order->consignee_address_line3;
// 			    }
// 			}
            $Germany = array();
            $Germany = ['103','104','214','215','230','231','232','233'];
            $Yan_you_bao = array();
            $Yan_you_bao = ['140','141'];
            if(in_array($s->shipping_method_code, $Germany)){//德国的邮政限制长度为50
                $limit = 50;
            }else if(in_array($s->shipping_method_code, $Yan_you_bao)&&$order->consignee_country_code == 'AU'){//燕邮宝澳洲
                $limit = 150;
            }else{
                $limit = 500;
            }
			$addressAndPhoneParams = array(
			    'address' => array(
			        'consignee_address_line1_limit' => $limit,
			        'consignee_address_line2_limit' => $limit,
			    ),
			    'consignee_district' => 1,
			    'consignee_county' => 1,
			    'consignee_company' => 1,
			    'consignee_phone_limit' => 100
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			if($limit == 50){//德国的邮政限制长度为50
			    if(strlen($addressAndPhone['address_line1'])>50 || strlen($addressAndPhone['address_line2'])>50){
			        return self::getResult(1,'','地址信息超出本运输渠道的长度限制50');
			    }
			}else if($limit == 150){//燕邮宝澳洲
			    if(strlen($addressAndPhone['address_line1'])>150 || strlen($addressAndPhone['address_line2'])>150){
			        return self::getResult(1,'','地址信息超出本运输渠道的长度限制150');
			    }
			}
			//法国没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
			    $tmpConsigneeProvince = $order->consignee_city;
			}
			if($order->consignee_country_code == "UK"){
			    $countryCode = "GB";
			}else{
			    $countryCode = $order->consignee_country_code;
			}
			//组织数据
			$_paramsArray['ExpressType']=array(
					'Epcode'=>'',//'W'.$customer_number,可以不填,//运单号
					'Userid'=>$userid,//客户号
					'Channel'=>$s->shipping_method_code,//发货方式
					'UserOrderNumber'=>$customer_number,//客户订单号
					'SendDate'=>self::dateTime(time()),//发货日期
					// 'PackageNo'=>$puid.$order->order_id,//包裹号
					'Insure'=>$carrier_params['Insure'],
					'Receiver'=>array(
							'Userid'=>$userid,
					        // dzt20190918 客户上传创建订单失败，燕文那边要求去掉CDATA
// 							'Name'=>'<![CDATA['.$order->consignee.']]>',
// 							'Phone'=>'<![CDATA['.$order->consignee_phone.']]>',
// 					        'Mobile'=>'<![CDATA['.$order->consignee_mobile.']]>',
// 							'Email'=>'<![CDATA['.$order->consignee_email.']]>',
// 							'Company'=>'<![CDATA['.$order->consignee_company.']]>',
// 							'Country'=>'<![CDATA['.$countryCode.']]>',
// 							'Postcode'=>'<![CDATA['.$order->consignee_postal_code.']]>',
// 							'State'=>'<![CDATA['.$tmpConsigneeProvince.']]>',
// 							'City'=>'<![CDATA['.$order->consignee_city.']]>',
// 							'Address1'=>'<![CDATA['.$addressAndPhone['address_line1'].']]>',
// 							'Address2'=>'<![CDATA['.$addressAndPhone['address_line2'].']]>',
					        'Name'=>$order->consignee,
					        'Phone'=>$order->consignee_phone,
					        'Mobile'=>$order->consignee_mobile,
					        'Email'=>$order->consignee_email,
					        'Company'=>$order->consignee_company,
					        'Country'=>$countryCode,
					        'Postcode'=>$order->consignee_postal_code,
					        'State'=>$tmpConsigneeProvince,
					        'City'=>$order->consignee_city,
					        'Address1'=>$addressAndPhone['address_line1'],
					        'Address2'=>$addressAndPhone['address_line2'],
					),
					'Memo'=>$data['orderNote'],
			        'MRP'=>$data['MRP'],//申请建议零售价
			        'ExpiryDate'=>$data['ExpiryDate'],//产品使用到期日
					'Quantity'=>$quantity,
					'GoodsName'=>array(
							'Userid'=>$userid,
// 							'NameCh'=>'<![CDATA['.$product['NameCh'].']]>',
// 							'NameEn'=>'<![CDATA['.$product['NameEn'].']]>',
					        'NameCh'=>$product['NameCh'],
					        'NameEn'=>$product['NameEn'],
							'Weight'=>$product['Weight'],
							'DeclaredValue'=>$product['DeclaredValue'],
							'DeclaredCurrency'=>$product['DeclaredCurrency'],
// 					        'DeclaredCurrency'=>'USD',
// 					        'MoreGoodsName'=>'<![CDATA['.$MoreGoodsName.']]>',
							'MoreGoodsName'=>$MoreGoodsName,
    					    'ProductBrand'=>$product['ProductBrand'],
    					    'ProductSize'=>$product['ProductSize'],
    					    'ProductColor'=>$product['ProductColor'],
    					    'ProductMaterial'=>$product['ProductMaterial'],
					),
			);
			if (isset($product['HsCode'])){
				$_paramsArray['ExpressType']['GoodsName']['HsCode'] = $product['HsCode'];
			}
			#############################################################################
			\Yii::info('YANWEN params,puid:'.$puid.',token:'.$this->AuthToken=$token.',orderId:'.$order->order_source_order_id.' '.json_encode($_paramsArray),"carrier_api");
	        $this->serverUrl=$this->baseUrl."/Users/".$userid."/Expresses";
	        $this->AuthToken=$token;
// 	        echo '<pre>';
// 	        print_r($_paramsArray);
// 	        print_r($this->serverUrl);
// 	        print_r($this->AuthToken);
// 	        echo '</pre>';die;
	        //请求类型
	        $this->setRequestBody($_paramsArray);
	        $response=$this->sendRequest(0);
	        //记录返回数据到log文件
	        \Yii::info('YANWEN response,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.':'.print_r($response,true),"carrier_api");
	        //这个是非正常的返回 当数据不全的时候会出现这样的报错 所以给用户这样一个提示 
	        if(isset($response['head']) && isset($response['body'])){
	            \Yii::info('YANWEN error1,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.' '.json_encode($response),"carrier_api");
	        	return self::getResult(1,'','物流商数据错误,请检查帐号是否填写完整或物流商设置');
	        }
	        if (isset($response['CallSuccess']) && ($response['CallSuccess'] == 'true')){
	        	//获取跟踪号
	            \Yii::info('YANWEN success,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.' '.json_encode($response),"carrier_api");
	        	$Epcode = $response['CreatedExpress']['Epcode'];
	        	if (isset($response['CreatedExpress']['ReferenceNo'])){
		        	if (is_array($response['CreatedExpress']['ReferenceNo'])){
		        		if (empty($response['CreatedExpress']['ReferenceNo'])){
		        			$ReferenceNo = '';
		        		}else{
		        			$ReferenceNo = $response['CreatedExpress']['ReferenceNo'][0];
		        		}
		        	}else{
		        		$ReferenceNo = $response['CreatedExpress']['ReferenceNo'];
		        	}
	        	}else{
	        		$ReferenceNo = '';
	        	}
	        	if ($carrier_params['get_trackNo_set'] == 'Epcode'){
	        		$tracking_number = $Epcode;
	        	}elseif($carrier_params['get_trackNo_set'] == 'ReferenceNo'){
	        			$tracking_number = $ReferenceNo;
	        	}elseif ($carrier_params['get_trackNo_set'] == 'notrackNo'){
	        		$tracking_number = '';
	        	}else{
	        		if (strlen($ReferenceNo)>0){
	        			$tracking_number = $ReferenceNo;
	        		}else{
	        			$tracking_number = $Epcode;
	        		}
	        	}
	        	//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				$r = CarrierAPIHelper::orderSuccess($order,$s,$Epcode,OdOrder::CARRIER_WAITING_PRINT,$tracking_number);
				
			    $print_param = array();
			    $print_param['carrier_code'] = $s->carrier_code;
			    $print_param['api_class'] = 'LB_YANWENCarrierAPI';
			    $print_param['userid'] = $userid;
			    $print_param['userToken'] = $token;
			    $print_param['epcode'] = $Epcode;
			    $print_param['carrier_params'] = $s->carrier_params;
			    
			    try{
			        
			        CarrierAPIHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $Epcode, $print_param);
			    }catch (\Exception $ex){
			    }
				
				
	        	return self::getResult(0,$r,"上传成功，客户单号：". $Epcode);
	        }else{
	            if(is_array($response)){
	                if (isset($response['Message'])){
	                    $error = $response['Message'];
	                }elseif (isset($response['Response']['ReasonMessage'])){
	                    $error = $response['Response']['ReasonMessage'];
	                }else{
	                    $error = '创建订单失败!'.self::$errors[$response['Response']['Reason']];
	                }
	                \Yii::info('YANWEN error,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.' '.json_encode($response),"carrier_api");
	            }else{
	                $error = '创建订单失败!'.$response;
	                \Yii::info('YANWEN error,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.' '.$response,"carrier_api");
	            }
	        	return self::getResult(1, '', $error);
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
	**/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			$order = current($data);reset($data);
			$order = $order['order'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$shippingService = $info['service'];
			//取得用户打印的尺寸
			$params = $shippingService->carrier_params;
			$label = !empty($params['label'])?$params['label']:"A4L";

			$account = $info['account'];
			$params = $account->api_params;
			$userid = isset($params['userid'])?$params['userid']:'';
			$token = isset($params['token'])?$params['token']:'';
			//组织数据 epcode
			$customer_number_arr = [];
			foreach ($data as $v) {
				$order = $v['order'];
				// $_paramsArray['string'].=$order->customer_number.',';
				$customer_number_arr[] = $order->customer_number;
			}
			
			$_paramsArray['string'] = implode(',', $customer_number_arr);
			
			#############################################################################
			$loginfo['request'] = $_paramsArray;
			$loginfo['userid'] = $userid;
			$loginfo['token'] = $token;
			
			$this->serverUrl=$this->baseUrl."/Users/".$userid."/Expresses/".$label."Label";
	        $this->AuthToken=$token;
	        //请求类型
			$this->setRequestBody($_paramsArray);
			
			\Yii::info('YANWEN print loginfo,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.print_r($loginfo,true).PHP_EOL."url:".$this->serverUrl,"carrier_api");
			
			$response=$this->sendRequest(1);
			if(strpos($response,'<title>Request Error</title>') || empty($response)){
			    if(is_array($response)){
			        \Yii::info('YANWEN print error1,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.' '.json_encode($response),"carrier_api");
			    }else{
			        \Yii::info('YANWEN print error2,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.' '.print_r($response,true),"carrier_api");
			    }
				return self::getResult(1, '', '请检查订单是否已经上传');
			}
			if (strlen($response)>1000){
				foreach ($data as $v) {
					$order = $v['order'];
// 					$order->is_print_carrier = 1;
					$order->print_carrier_operator = $puid;
					$order->printtime = time();
					$order->carrier_error = '';
					$order->save();
				}
				$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code);
				return self::getResult(0,['pdfUrl'=>$pdfUrl],'连接已生成,请点击并打印');
			}else{
			    \Yii::info('YANWEN print error2,puid:'.$puid.',result,orderId:'.$order->order_source_order_id.' '.json_encode($response),"carrier_api");
				foreach($data as $v){
					$order = $v['order'];
					$order->carrier_error = '打印订单失败,请重试';
					$order->save();
				}
				return self::getResult(1, '', '打印运单失败,请检查订单后重试');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
	        $PrintParam = array();
	        $PrintParam = [
	            'A4L'=>'A10x10L',
	            'A4LI'=>'A10x10LI',
	            'A4LC'=>'A10x10LC',
	            'A4LCI'=>'A10x10LCI',
	            'A6L'=>'A10x10L',
	            'A6LI'=>'A10x10LI',
	            'A6LC'=>'A10x10LC',
	            'A6LCI'=>'A10x10LCI',
	            'A10x10L'=>'A10x10L',
	            'A10x10LI'=>'A10x10LI',
	            'A10x10LC'=>'A10x10LC',
	            'A10x10LCI'=>'A10x10LCI',
	        ];//10*10打印格式
	        
	        $puid = $SAA_obj->uid;
	
	        //组织数据 epcode
	        $_paramsArray['string'] = '';
            $_paramsArray['string'] = $print_param['epcode'];
	        
	        $loginfo['request'] = $_paramsArray;
	        $loginfo['userid'] = $print_param['userid'];
	        $loginfo['token'] = $print_param['userToken'];
	        \Yii::info(print_r($loginfo,true));
	        
	        $userid = $print_param['userid'];
	        $token = $print_param['userToken'];
	        
	        $normal_params = empty($print_param['carrier_params']) ? array() : $print_param['carrier_params']; ////获取打印方式
	        $label = empty($normal_params['label']) ?'A10x10L':$normal_params['label'];
	        
	        if(isset($PrintParam[$label])){
	            $label = $PrintParam[$label];
	        }
	        
	        $this->serverUrl=$this->baseUrl."/Users/".$userid."/Expresses/".$label."Label";
	        $this->AuthToken=$token;
	        //请求类型
	        $this->setRequestBody($_paramsArray);
	        $response=$this->sendRequest(1);
	        if(strpos($response,'<title>Request Error</title>') || empty($response)){
	            return self::getResult(1, '', '请检查订单是否已经上传');
	        }
	        if (strlen($response)>1000){
// 	            $pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code);
	            $pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
	            return $pdfPath;
	        }else{
	            return ['error'=>1, 'msg'=>'打印运单失败,请检查订单后重试', 'filePath'=>''];
	        }
	        
	    }catch (CarrierException $e){
	        return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
	    }
	}
	/*
	 * 用来确定打印完成后 订单的下一步状态
	 */
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 * 燕文接口不支持
	 +----------------------------------------------------------
	**/
	public function doDispatch($data){
		return self::getResult(1, '', '结果：该物流商API不支持交运订单功能');
	}
	/**
	 +----------------------------------------------------------
	 * 取消订单
	 * 燕文接口不支持
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return self::getResult(1, '', '结果：该物流商API不支持取消订单功能');
	}

	//重新发货
	public function Recreate(){return self::getResult(1, '', '结果：物流商不支持重新发货');}

	/**
	 +----------------------------------------------------------
	 * 获取跟踪号
	 * 
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$shippingService = $info['service'];
			$carrier_params = $shippingService->carrier_params;
			$trackNo_set = $carrier_params['get_trackNo_set'];
			if(empty($order->customer_number))return self::getResult(1,'','请检查该订单是否上传');
			if($trackNo_set=='Epcode'){
// 				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return self::getResult(0,'','运单号:'.$order->customer_number);
			}
			else return self::getResult(1,'','暂不支持查询转运单号,请在[物流][运输服务管理]中将设置[物流号选择]修改为 运单号');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	function xml_to_array( $xml )
	{
	    $reg = "/<(\\w+)[^>]*?>([\\x00-\\xFF]*?)<\\/\\1>/";
	    if(preg_match_all($reg, $xml, $matches))
	    {
	        $count = count($matches[0]);
	        $arr = array();
	        for($i = 0; $i < $count; $i++)
	        {
	        $key= $matches[1][$i];
	        $val = self::xml_to_array( $matches[2][$i] );  // 递归
	        if(array_key_exists($key, $arr))
	        {
	        if(is_array($arr[$key]))
	            {
	            if(!array_key_exists(0,$arr[$key]))
	                {
	                $arr[$key] = array($arr[$key]);
	                }
	                }else{
	                $arr[$key] = array($arr[$key]);
	            }
	                $arr[$key][] = $val;
	        }else{
	                $arr[$key] = $val;
	    }
	    }
	    return $arr;
	    }else{
	        return $xml;
	    }
	}
	/*
	* 获取平台发货方式代码
	*/
	public static function getChannels(){
	    $post_header = array('Authorization: basic MjA2Mzg4OjU5MDI2NzI5', 'Content-Type: text/xml; charset=utf-8');
		$post_data = '';

		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'http://Online.yw56.com.cn/service/Users/206388/GetChannels');
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		// curl_setopt($curl, CURLOPT_POST, 1);
		curl_setopt($curl, CURLOPT_HEADER, 1);
		curl_setopt($curl, CURLOPT_VERBOSE, 1);
		
		curl_setopt($curl, CURLOPT_HTTPHEADER, $post_header);
		// curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);

		$result = curl_exec($curl);
		preg_match('/\<\?xml.*/', $result,$str);
		$a = simplexml_load_string($str[0]);
		$a = self::obj2ar($a);
		$return = '';
		foreach($a['ChannelCollection']['ChannelType'] as $v){
			if($v['Status']=='true')$return .= $v['Id'].':'.$v['Name'].';';
		}
		echo $return;die;

	}

	/**
	 * 设置请求内容
	 *
	 * @param mixed $dataArr 内容数组
	 */
	function setRequestBody($dataArr){
		$this->requestBody=$dataArr;
	}
	/**
	 * 发送请求
	 *
	 * @param boolean $returnXml 是否返回源xml，默认返回数组
	 * @return array|xml
	 */
	function sendRequest($returnXml=0,$method='post'){
		$xmlArr=$this->requestBody;
		$xmlArr=self::simpleArr2xml($xmlArr,0);
		return $this->sendHttpRequest($xmlArr,$returnXml,$method);
	}
	/**
	 * 发送类
	 * 	 $sendXmlArr : 数据 数组 ,会被组织成 Xml
	 * 	 	如果 本来就是 字符串的 ,就认为本来就是  Xml
	 * 	 $returnXml : 返回 值是 数组 或 原生 xml .默认 是 数组
	 */
	function sendHttpRequest($sendXmlArr,$isreturnXml=0,$method='post'){
		session_write_close();
		// 1,body 部分
		if(is_array($sendXmlArr)){
			$requestBody=self::simpleArr2xml($sendXmlArr);
		}elseif(is_string($sendXmlArr)){
			$requestBody=$sendXmlArr;
		}else{
			$response = Array
				(
						'CallSuccess' => 'false',
						'Response' => Array
						(
								'Success' => 'false',
								'ReasonMessage' => '小老板内部错误，提交数据格式不对'
						)
				
				);
			return $response;
		}
		$headers = array (
				'Authorization: basic '.$this->AuthToken,
				'Content-Type: text/xml; charset=utf-8',
		);
// 		echo '<pre>';
// 		print_r($this->serverUrl);
// 		print_r($requestBody);
// 		print_r($headers);
// 		echo '</pre>';
		try {

			$response = Helper_Curl::$method($this->serverUrl,$requestBody,$headers,false);
		}catch (Exception $ex){
			$response = Array
			(
					'CallSuccess' => 'false',
					'Response' => Array
					(
							'Success' => 'false',
							'ReasonMessage' => $ex->getMessage()
					)
			
			);
			return $response;
		}
// 		\Yii::log(print_r($response,true));
		//返回 数组
		if($isreturnXml){
			return $response;
		}else{
			try {
				$responseobj = simplexml_load_string($response);
			}catch (\Exception $ex){
				\Yii::info('LB_YANWEN:'.$requestBody.' .response: '.$response,"carrier_api");
				$responseobj = array();
			}
			return self::obj2ar($responseobj);
		}
	}
	
	static function simpleArr2xml($arr,$header=1){
		if($header){
			$str='<?xml version="1.0" encoding="utf-8" ?>';
		}else{
			$str='';
		}
		if(is_array($arr)){
			$str.="\r\n";
			foreach($arr as $k=>$v){
				$n=$k;
				if(($b=strpos($k,' '))>0){
					$f=substr($k,0,$b);
				}else{
			        $f=$k;
				}
				if(is_array($v)&&is_numeric(implode('',array_keys($v)))){
				// 就是为 Array 为适应 Xml 的可以同时有多个键 所做的 变通
					foreach($v as $cv){
					$str.="<$n>".self::simpleArr2xml($cv,0)."</$f>\r\n";
					}
				}elseif ($v instanceof SimpleXMLElement ){
					$xml = $v->asXML();/*<?xml version="1.0"?>*/
					$xml =preg_replace('/\<\?xml(.*?)\?\>/is','',$xml);
					$str.=$xml;
				}else{
						$str.="<$n>".self::simpleArr2xml($v,0)."</$f>\r\n";
				}
			}
		}else{
			$str.=$arr;
		}
		return $str;
	}
	
	
	static function dateTime($timestamp=null){
		return gmdate('Y-m-dGMTH:i:s',$timestamp);
	}
	//对象转数组
	static private function obj2ar($obj) {
		if(is_object($obj)) {
			$obj = (array)$obj;
			$obj = LB_YANWENCarrierAPI::obj2ar($obj);
		} elseif(is_array($obj)) {
			foreach($obj as $key => $value) {
				$obj[$key] = LB_YANWENCarrierAPI::obj2ar($value);
			}
		}
		return $obj;
	}
	
	public static $errors = [
			'None'=>'没有错误;',
			'V000'=>'对象为空;',
			'V001'=>'用户验证失败;',
			'V100'=>'快递单号不可以为空;',
			'V101'=>'渠道不正确;',
			'V102'=>'此国家不能走欧洲[挂号]小包;',
			'V103'=>'运单编号不可修改;',
			'V104'=>'运单编号已经存在;',
			'V105'=>'此国家不能走HDNL英国;',
			'V106'=>'此国家不能走澳洲专线;',
			'V107'=>'此国家不能走燕文美国专线;',
			'V108'=>'此国家不能走该渠道;',
			'V109'=>'转运单号已用完，请联系我司客服',
			'V110'=>'此邮编不能走该渠道',
			'V111'=>'快递单号不允许存在特殊字符',
			'V112'=>'低于7位的纯数字快递单号不可录入',
			'V113'=>'订单号不可为空',
			'V114'=>'转运单号不可为空',
			'V115'=>'该发货方式尚未开放',
			'V116'=>'该发货方式已取消',
			'V117'=>'运单号长度不可超过50个字符',
			'V118'=>'订单号长度不可超过50个字符',
			'V119'=>'转运单号长度不可超过50个字符',
			'V120'=>'您所在的地区尚未开通此渠道',
			'V121'=>'您的订单号不可重复',
    	    'V123'=>'该国家暂停收寄',
    	    'V124'=>'禁止客户自己填写“Y”字母起始的运单号',
    	    'V125'=>'订单单号只允许使用字母、数字和"-"、"_"字符',
    	    'V126'=>'该客户号所在地区不允许使用此渠道',
			'V199'=>'该快件不存在;',
			'V300'=>'编号不可以为空;',
			'V301'=>'中文品名不可以为空;',
			'V302'=>'英文品名不可以为空;',
			'V303'=>'中文品名已经存在;',
			'V304'=>'英文品名已经存在;',
			'V305'=>'货币类型不正确;',
			'V306'=>'申报价值格式不正确;',
			'V307'=>'申报重量格式不正确;',
			'V308'=>'申报物品数量格式不正确;',
			'V309'=>'该渠道下选择的货币类型不正确;',
			'V310'=>'多品名不可为空;',
			'V311'=>'此渠道下申报价值不能超过500人民币',
			'V312'=>'此渠道下重量不可超过750g',
			'V313'=>'此渠道下重量不可超过2000g',
			'V314'=>'此渠道下重量不可超过1000g',
			'V315'=>'此渠道下商品海关编码不可以为空;',
			'V316'=>'此渠道下中(英)文品名长度不可超过60个字符',
			'V317'=>'此渠道下中(英)文品名长度不可超过50个字符',
			'V318'=>'此渠道下重量不可超过3000g',
			'V319'=>'中(英)文品名长度不可超过200个字符',
			'V320'=>'此渠道下申报价值不能超过1000澳元',
			'V321'=>'此渠道下邮编与城市不匹配,请检查您的数据',
			'V322'=>'此渠道下重量不可低于500g',
	        'V323'=>'此渠道邮递至欧盟国家申报价值过高',
    	    'V324'=>'此渠道下重量不可超过30000g',
    	    'V325'=>'此渠道下收件人姓名不能包含俄文',
    	    'V327'=>'燕邮宝澳洲供应商服务器返回错误，以供应商实际返回错误为准。（例如城市与邮编不对应则提示为：Australia Post does not deliver to [Home wood] and postcode [6799]!）',
    	    'V328'=>'此渠道下申报价值字符长度不能超过10个字符',
    	    'V329'=>'此渠道下收件人城市长度不能超过30个字符',
    	    'V330'=>'此渠道下重量不可超过31500g',
    	    'V331'=>'此渠道下收件人地址长度不能超过50个字符',
    	    'V332'=>'此渠道下州与城市不匹配,请检查您的数据',
    	    'V333'=>'此渠道下申报价值不能超过3000美元',
    	    'V334'=>'此渠道下客户必须提供收件人护照编码',
    	    'V335'=>'此渠道下客户必须真实地提供发件人具体店名',
    	    'V336'=>'此渠道下收件人地址只能包含英文、数字、空格',
    	    'V337'=>'此渠道下到该国家申报价值不能超过150美元',
    	    'V338'=>'此渠道下中(英)文品名长度不可超过90个字符',
    	    'V339'=>'此渠道下收件人地址长度不能超过300个字符',
    	    'V340'=>'此渠道下申报价值不能超过75',
    	    'V341'=>'线上数据北京仓中英文品名，价值，币种至少有一组不可为空。',
    	    'V342'=>'线上数据中文品名不能为空',
    	    'V343'=>'线上数据英文品名不能为空',
    	    'V344'=>'线上数据申报价值不能为空',
    	    'V345'=>'线上数据币种不正确',
    	    'V346'=>'该渠道下到该国家申报价值美元不能超过15，加币不能超过20',
    	    'V347'=>'此渠道下重量不可超过500g',
    	    'V348'=>'此渠道下申报价值不能超过15',
			'V399'=>'该品名不存在',
			'V400'=>'编号不可以为空',
			'V401'=>'姓名不可以为空',
			'V402'=>'电话不可以为空',
			'V403'=>'Email不可以为空',
			'V404'=>'国家不可以为空',
			'V405'=>'邮编不可以为空',
			'V406'=>'州编码/州不可以为空',
			'V407'=>'城市不可以为空',
			'V408'=>'地址不可以为空',
			'V409'=>'不符合美国邮编格式',
			'V499'=>'该收件人不存在',
			'V410'=>'收件人姓名已经存在',
			'V411'=>'此渠道邮编必须为6位数字',
			'V412'=>'此渠道电话必须为数字,不能超过11位,不能填写特殊符号',
			'V413'=>'此渠道下邮编不正确',
			'V414'=>'州编号长度不可超过50个字符',
			'V415'=>'手机号码不可以为空',
			'V416'=>'电话，手机号码长度不可超过50个字符',
			'V417'=>'此渠道国家邮编必须为4位数字',
			'V418'=>'此渠道国家邮编必须为数字',
			'V419'=>'此渠道国家邮编首位必须为数字',
    	    'V420'=>'此渠道邮编必须为6位数字且首位6开头',
    	    'V421'=>'电话长度不可超过25个字符',
    	    'V422'=>'此邮编所在地区暂停收寄',
    	    'V425'=>'此渠道下不允许使用"BT"起始的邮编',
    	    'V426'=>'所选发货渠道对此邮编属偏远地区没有服务，请更换其他渠道',
    	    'V427'=>'所选发货渠道至俄罗斯地区限制件数为5件',
    	    'V428'=>'不符合邮箱格式',
    	    'V429'=>'邮箱不可为空',
    	    'V430'=>'此邮编不符合规范【巴西的邮编有两种格式： 1. 5位数字+短横线+3位数字 2. 8位数字】',
    	    'V431'=>'此邮编不符合规范【美国的邮编有两种格式： 1. 5位数字+短横线+3位数字 2. 5位数字】',
    	    'V432 '=>'此渠道邮编必须为5位数字',
    	    'V433'=>'此渠道邮编长度必须小于16',
    	    'V434'=>'州编号长度不可超过80字符',
    	    'V435'=>'收货地址长度不可超过80字符',
    	    'V436'=>'HsCode长度不可超过20字符',
    	    'V437'=>'海关编码不可为空',
    	    'V438'=>'该渠道下选择的货币类型不正确,请选择美元USD或者加币CAD',
    	    'V439'=>'该渠道下美元不得超过15',
    	    'V440'=>'此渠道邮编必须为【字母+数字+字母+数字+字母+数字】',
    	    'V441'=>'州编码只能为字母',
    	    'V442'=>'邮编不能以971，972，973，974，975，976，984，986，987，988做开头',
    	    'V443'=>'此渠道下申报价值不能超过22',
    	    'V444'=>'Email里面不能出现PO BOX',
    	    'V445'=>'该渠道下选择的货币类型不正确,请选择美元USD或欧元EUR或英镑GBP',
    	    'V447'=>'所选发货渠道必须填写商品品牌、商品材质、商品尺寸、商品材质',
    	    'V448'=>'收件人地址长度不可超过47字符',
    	    'V449'=>'收件人姓名长度不可超过47字符',
    	    'V450'=>'该渠道收件人电话号码如需填写必须为10位纯数字',
    	    'V500'=>'此渠道没有对应的验证数据',
			'V703'=>'登录失败，请联系您的销售',
			'S1'=>'系统错误',
			'D1'=>'数据处理错误',
			'D2'=>'XML不符合规范，请检查是否存在&字符',
			'D3'=>'Header不符合规范，请检查charset: utf-8',
			'D4'=>'您在我司中邮挂号(北京)的单号用量已超过额度，请与我司销售或客服联系。',
    	    'D5'=>'您在我司中没有发送此渠道的额度，请与我司销售或客服联系。',
    	    'D6'=>'供应商API服务器错误，请相关负责人联系供应商',
	];
	
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
	        $post_header = array('Authorization: basic '.$data['token'], 'Content-Type: text/xml; charset=utf-8');
	        
	        $curl = curl_init();
	        curl_setopt($curl, CURLOPT_URL, 'http://Online.yw56.com.cn/service/Users/'.$data['userid'].'/GetChannels');
	        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	        curl_setopt($curl, CURLOPT_HEADER, 1);
	        curl_setopt($curl, CURLOPT_VERBOSE, 1);
	        
	        curl_setopt($curl, CURLOPT_HTTPHEADER, $post_header);
	        
	        $url_result = curl_exec($curl);
	        preg_match('/GetChannelCollectionResponseType/', $url_result, $str);

			if( isset( $str[0] ) && $str[0]=='GetChannelCollectionResponseType' ){
				$result['error'] = 0;
			}/**
	        $a = self::xml_to_array($str[0]);
	        if(isset($a['GetChannelCollectionResponseType'])){
	            $result['error'] = 0;
	        }**/
	    }catch(CarrierException $e){
	    }
	
	    return $result;
	}
	
	//获取运输方式
	public function getCarrierShippingServiceStr($account){
	    try{
	        $api_params= $account->api_params;
			$token= $api_params['token'];
			$userId= $api_params['userid'];
	        
	        $post_header = array('Authorization: basic '.$token, 'Content-Type: text/xml; charset=utf-8');
	        $post_data = '';
	        
	        $curl = curl_init();
	        curl_setopt($curl, CURLOPT_URL, 'http://Online.yw56.com.cn/service/Users/'.$userId.'/GetChannels');
	        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
	        // curl_setopt($curl, CURLOPT_POST, 1);
	        curl_setopt($curl, CURLOPT_HEADER, 0);
	        curl_setopt($curl, CURLOPT_VERBOSE, 1);
	        
	        curl_setopt($curl, CURLOPT_HTTPHEADER, $post_header);
	        // curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
	        
	        $result = curl_exec($curl);
	        try{
	        	$a = simplexml_load_string($result);
	        }catch(\Exception $e){
	        	return self::getResult(1, '', '接口异常');
	        }
	        
	        $a = self::obj2ar($a);
	        $channelStr="";
	        foreach($a['ChannelCollection']['ChannelType'] as $v){
	            if($v['Status']=='true')$channelStr .= $v['Id'].':'.$v['Name'].';';
	        }
	        
	        if(empty($channelStr)){
	            return self::getResult(1, '', '');
	        }else{
	            return self::getResult(0, $channelStr, '');
	        }
	    }catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
}
