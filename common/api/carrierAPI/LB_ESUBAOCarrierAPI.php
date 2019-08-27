<?php

namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_Curl;
use yii\base\Exception;
use Qiniu\json_decode;

class LB_ESUBAOCarrierAPI extends BaseCarrierAPI{
	public static $url = null;
	public $usercode = null;
	public $userpwd = null;
	public $token = null;
	
	public function __construct(){
		if(isset(\Yii::$app->params["currentEnv"]) && \Yii::$app->params["currentEnv"]=='production'){
			self::$url = 'http://cpws.ems.com.cn';
		}else{
			self::$url = 'http://202.104.134.94:61080';
		}
	}
	
	public static function getEtoken($t_usercode, $t_userpwd){
		$result = array('success'=>false, 'token'=>'', 'msg'=>'');
		
		$url = self::$url.'/shipping/default/api/get-token';
		
		$header=array();
		$header[]='Content-Type:application/json;charset=utf-8';
		$request = array();
		$request['usercode'] = $t_usercode;
		$request['userpwd'] = urlencode(base64_encode($t_userpwd));
		
		$response = Helper_Curl::post($url,json_encode($request),$header);
		$response = json_decode($response, true);
		
		if(isset($response['ret']) && ($response['ret'] == 0)){
			if(!empty($response['data'])){
				$result['token'] = $response['data'];
				$result['success'] = true;
				return $result;
			}
		}else{
			if(!empty($response['msg'])){
				$result['msg'] = $response['msg'];
			}else{
				$result['msg'] = 'E速宝异常，请联系小老板技术';
			}
		}
		
		return $result;
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2018/03/15				初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
				
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
				
			//用户在确认页面提交的数据
			$e = $data['data'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$service_carrier_params = $service->carrier_params;
			$account = $info['account'];
				
			//获取到帐号中的认证参数
			$a = $account->api_params;
				
			//获取到账户中的地址信息
			$account_address = $info['senderAddressInfo'];
				
			$this->usercode = $a['usercode'];
			$this->userpwd = $a['userpwd'];
			
			$token_arr = self::getEtoken($this->usercode, $this->userpwd);
			
			if($token_arr['success'] == false){
				return self::getResult(1, '', '失败原因:'.$token_arr['msg']);
			}
			
			$this->token = $token_arr['token'];
			
			//英国的简码可能是UK,需要转换为GB
			$tmp_consignee_country_code = $order->consignee_country_code;
			if($tmp_consignee_country_code == 'UK'){
				$tmp_consignee_country_code = 'GB';
			}
			
			//收件人省份为空直接用城市代替
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($tmpConsigneeProvince)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			//组织收件人地址信息
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 36,
							'consignee_address_line2_limit' => 36,
							'consignee_address_line3_limit' => 36,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 0,
					'consignee_phone_limit' => 25
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$tmp_post_invoice = array();
			
			//统计总的报关重量和报关金额
			$tmp_Itotleweight_sum = 0;
			$tmp_Itotlevalue_sum = 0;
				
			foreach ($e['EName'] as $k=>$v){
				$tmp_strlen = strlen($e['CName'][$k]);
				if(($tmp_strlen == 0) || ($tmp_strlen >= 60)){
					return self::getResult(1, '', '失败原因 中文报关名:'.(($tmp_strlen==0) ? '必填' : '长度限制为60字节,一个中文字符等于3个字节'));
				}
			
				$tmp_strlen = strlen($e['EName'][$k]);
				if(($tmp_strlen == 0) || ($tmp_strlen >= 60)){
					return self::getResult(1, '', '失败原因 英文报关名:'.(($tmp_strlen==0) ? '必填' : '长度限制为60字节,一个中文字符等于3个字节'));
				}
			
				$tmp_strlen = strlen($e['productId'][$k]);
				if(($tmp_strlen == 0) || ($tmp_strlen >= 50)){
					return self::getResult(1, '', '失败原因 内件商品 ID:'.(($tmp_strlen==0) ? '必填' : '长度限制为50字节,一个中文字符等于3个字节'));
				}
				
				$tmp_post_invoice[] = array(
						'invoice_enname'=>$e['EName'][$k],
						'invoice_cnname'=>$e['CName'][$k],
						'invoice_quantity'=>$e['productQantity'][$k],
						'invoice_unitcharge'=>$e['delcarevalue'][$k],
						'invoice_weight'=>(($e['productWeight'][$k]) / 1000),
						'invoice_note'=>$e['invoice_note'][$k],
						'invoice_url'=>$e['invoice_url'][$k],
// 						'unit_code'=>'',
						'sku'=>$e['productId'][$k],
						'hs_code'=>$e['hsCode'][$k],
				);
				
				$tmp_Itotleweight_sum += ($e['productWeight'][$k] / 1000) * (int)($e['productQantity'][$k]);
				$tmp_Itotlevalue_sum += (int)($e['delcarevalue'][$k]) * (int)($e['productQantity'][$k]);
			}
			
			$tmp_post_order = array(
					'refer_hawbcode'=>$customer_number,
					'order_weight'=>$tmp_Itotleweight_sum,
					'order_pieces'=>1,
					'order_length'=>10,
					'order_width'=>10,
					'order_height'=>10,
					'mail_cargo_type'=>$service_carrier_params['mail_cargo_type'],
					'product_code'=>$service->shipping_method_code,
					'country_code'=>$tmp_consignee_country_code,
					'battery'=>$e['battery']
			);
			
			$tmp_post_shipper = array(
					'shipper_company'=>$account_address['shippingfrom']['company_en'],
					'shipper_name'=>$account_address['shippingfrom']['contact_en'],
					'shipper_countrycode'=>$account_address['shippingfrom']['country'],
					'shipper_province'=>$account_address['shippingfrom']['province_en'],
					'shipper_street'=>$account_address['shippingfrom']['street_en'],
					'shipper_telephone'=>$account_address['shippingfrom']['phone'],
					'shipper_city'=>$account_address['shippingfrom']['city_en'],
					'shipper_postcode'=>$account_address['shippingfrom']['postcode'],
			);
			
			$tmp_post_consignee = array(
					'consignee_company'=>$order->consignee_company,
					'consignee_name'=>$order->consignee,
					'consignee_province'=>$tmpConsigneeProvince,
					'consignee_email'=>'',
					'consignee_street'=>$addressAndPhone['address_line1'],
					'consignee_street2'=>$addressAndPhone['address_line2'],
					'consignee_street3'=>$addressAndPhone['address_line3'],
					'consignee_telephone'=>$order->consignee_phone,
					'consignee_city'=>$order->consignee_city,
					'consignee_mobile'=>$order->consignee_mobile,
					'consignee_postcode'=>$order->consignee_postal_code,
			);
			
			
			$post_data = array();
			$post_data['usercode'] = $this->usercode;
			$post_data['versions'] = 1;
			$post_data['service'] = 'create';
			$post_data['token'] = $this->token;
			
			$post_data['order'] = $tmp_post_order;
			$post_data['consignee'] = $tmp_post_consignee;
			$post_data['shipper'] = $tmp_post_shipper;
			$post_data['invoice'] = $tmp_post_invoice;
			
			$url = self::$url.'/shipping/default/api/rto-do';
			$header=array();
			$header[]='Content-Type:application/json;charset=utf-8';
			
			\Yii::info('LB_ESUBAOCarrierAPI,puid:'.$puid.',order_id:'.$order->order_id.' '.json_encode($post_data),"carrier_api");
			
			$response = Helper_Curl::post($url,json_encode($post_data),$header);
			
			\Yii::info('LB_ESUBAOCarrierAPI,response,puid:'.$puid.',order_id:'.$order->order_id.' '.($response),"carrier_api");
			
			$response = json_decode($response, true);
			
			if(!isset($response['ret'])){
				throw new CarrierException('E速宝返回异常,请联系小老板客服。');
			}
			
			if($response['ret'] != 0){
				$tmp_error = '';
				
				if(isset($response['err_arr'])){
					$tmp_error = implode(',', $response['err_arr']);
				}
				
				return self::getResult(1, '', $response['msg'].$tmp_error);
			}
			
			if(empty($response['data'])){
				return self::getResult(1, '', 'E速宝没有返回跟踪号');
			}
			
			//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
			$r = CarrierAPIHelper::orderSuccess($order,$service,$response['refer_hawbcode'],
					OdOrder::CARRIER_WAITING_GETCODE, '', ['T_NUM'=>$response['data']]);
				
			return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$response['data']);
		}catch(CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消已确定的物流单。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		try{
            $order = $data['order'];
            //对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
            $checkResult = CarrierAPIHelper::validate(0,1,$order);
            $shipped = $checkResult['data']['shipped'];

            //获取到所需要使用的数据
            $info = CarrierAPIHelper::getAllInfo($order);
            $account = $info['account'];// object SysCarrierAccount
            $service = $info['service'];// object SysShippingService
            $carrier_params = $service->carrier_params;
            
            $this->usercode = $account->api_params['usercode'];
            $this->userpwd = $account->api_params['userpwd'];
            
            $token_arr = self::getEtoken($this->usercode, $this->userpwd);
            
            if($token_arr['success'] == false){
            	return self::getResult(1,'', '失败原因:'.$token_arr['msg']);
            }
            	
            $this->token = $token_arr['token'];
            
            $order = $data['order'];
            
            $post_data = array();
            
            $tmp_getTransferNumber = false;
            if(!empty($carrier_params['getTransferNumber'])){
            	$tmp_getTransferNumber = true;
            }
            
            $post_data['usercode'] = $this->usercode;
            $post_data['versions'] = 1;
            $post_data['service'] = '';
            $post_data['token'] = $this->token;
            
            if($tmp_getTransferNumber == false){
            	$post_data['service'] = 'getLabel';
            	$post_data['order_num'] = $shipped->return_no['T_NUM'];
            }else{
            	$post_data['service'] = 'batchGetTrackingNumber';
            	$post_data['order_num'] = array($shipped->return_no['T_NUM'],$shipped->return_no['T_NUM']);
            }
            
            $url = self::$url.'/shipping/default/api/rto-do';
            
			$header=array();
			$header[]='Content-Type:application/json;charset=utf-8';
			
			$response = Helper_Curl::post($url,json_encode($post_data),$header);
			$response = json_decode($response, true);
			
			if(!isset($response['ret'])){
				return self::getResult(1,'', 'E速宝返回异常,请联系小老板客服。' );
			}
			
			if($response['ret'] != 0){
				$tmp_error = '';
				
				if(isset($response['fail'][$shipped->return_no['T_NUM']])){
					$tmp_error = $response['fail'][$shipped->return_no['T_NUM']];
				}
				return self::getResult(1, '', $tmp_error);
			}
			
			if($tmp_getTransferNumber == false){
				if(empty($response['TrackingNumber'])){
					return self::getResult(1, '', 'E速宝没有返回跟踪号');
				}
				
				$shipped->tracking_number = $response['TrackingNumber'];
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				return self::getResult(0, '',  '物流跟踪号:' . $response['TrackingNumber']);
			}else{
				if(empty($response['data'][$shipped->return_no['T_NUM']])){
					return self::getResult(1, '', 'E速宝暂时没有返回跟踪号');
				}
				
				$shipped->tracking_number = $response['data'][$shipped->return_no['T_NUM']];
				$shipped->save();
				$order->tracking_number = $response['data'][$shipped->return_no['T_NUM']];
				$order->save();
				
				return self::getResult(0, '',  '物流转单号:' . $response['data'][$shipped->return_no['T_NUM']]);
			}
        }catch (CarrierException $e){
            return self::getResult(1,'',$e->msg());
        }
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2017/03/10				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			$puid = $user->getParentUid();
				
			if(count($data) > 100)
				throw new CarrierException('E速宝一次只能批量打印100张面单');
	
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
	
			//获取到帐号中的认证参数
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			$this->usercode = $account_api_params['usercode'];
			$this->userpwd = $account_api_params['userpwd'];
			
			$token_arr = self::getEtoken($this->usercode, $this->userpwd);
				
			if($token_arr['success'] == false){
				throw new CarrierException('失败原因:'.$token_arr['msg']);
			}
			
			$this->token = $token_arr['token'];
			
			$post_data = array();
			$post_data['usercode'] = $this->usercode;
			$post_data['versions'] = 1;
			$post_data['service'] = '';
			$post_data['token'] = $this->token;
			
			if(count($data) == 1){
				$post_data['service'] = 'getLabel';
				
				$tmp_order_num = '';
				
				foreach ($data as $key => $value) {
					$order = $value['order'];
				
					$checkResult = CarrierAPIHelper::validate(1,1,$order);
					$shipped = $checkResult['data']['shipped'];
				
					if(empty($shipped->return_no['T_NUM'])){
						return self::getResult(1,'', 'order:'.$order->order_id .' 缺少跟踪号,请检查订单是否已上传' );
					}
						
					$tmp_order_num = $shipped->return_no['T_NUM'];
				}
				
				$post_data['order_num'] = $tmp_order_num;
				
				$url = self::$url.'/shipping/default/api/rto-do';
				$header=array();
				$header[]='Content-Type:application/json;charset=utf-8';
					
				$response = Helper_Curl::post($url,json_encode($post_data),$header);
				$response = json_decode($response, true);
				
				if(!isset($response['ret'])){
					return self::getResult(1,'', 'E速宝返回异常,请联系小老板客服。' );
				}
				
				if($response['ret'] != 0){
					$tmp_error = $response['msg'];
					return self::getResult(1, '', $tmp_error);
				}
				
				$tmpPath = array();
				$pdfUrl = CarrierAPIHelper::savePDF(base64_decode($response['data']),$puid,$account->carrier_code.'_'.$order['customer_number'], 0);
				$tmpPath[] = $pdfUrl['filePath'];
				
				return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			}else{
				$post_data['service'] = 'batchGetLabel';
				
				$tmp_order_num = array();
				
				foreach ($data as $key => $value) {
					$order = $value['order'];
				
					$checkResult = CarrierAPIHelper::validate(1,1,$order);
					$shipped = $checkResult['data']['shipped'];
				
					if(empty($shipped->return_no['T_NUM'])){
						return self::getResult(1,'', 'order:'.$order->order_id .' 缺少跟踪号,请检查订单是否已上传' );
					}
					
					$tmp_order_num[] = $shipped->return_no['T_NUM'];
				}
				
				$post_data['order_num'] = $tmp_order_num;
				
				$url = self::$url.'/shipping/default/api/rto-do';
				$header=array();
				$header[]='Content-Type:application/json;charset=utf-8';
				
				$response = Helper_Curl::post($url,json_encode($post_data),$header);
				$response = json_decode($response, true);
				
				if(!isset($response['ret'])){
					return self::getResult(1,'', 'E速宝返回异常,请联系小老板客服。' );
				}
				
				if($response['ret'] != 0){
					$tmp_error = '';
						
					if(isset($response['fail'])){
						foreach($response['fail'] as $tmp_fail_key => $tmp_fail_val){
							$tmp_error .= $tmp_fail_key.':'.$tmp_fail_val.';';
						}
					}
						
					return self::getResult(1, '', $tmp_error);
				}
				
				$tmpPath = array();
				$pdfUrl = CarrierAPIHelper::savePDF(base64_decode($response['data']),$puid,$account->carrier_code.'_'.$order['customer_number'], 0);
				$tmpPath[] = $pdfUrl['filePath'];
				
				return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			}
		}catch(Exception $e) {
			return self::getResult(1,'',$e->getMessage());
		}
	}
}

?>