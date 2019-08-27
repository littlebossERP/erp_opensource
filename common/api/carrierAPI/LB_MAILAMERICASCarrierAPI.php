<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use Qiniu\json_decode;
use Jurosh\PDFMerge\PDFMerger;
use yii;
use eagle\modules\util\helpers\PDFMergeHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use common\api\lazadainterface\LazadaInterface_Helper;
use eagle\models\SaasLazadaUser;

class LB_MAILAMERICASCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	public $token = '';
	
	public function __construct(){
		//测试
// 		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' )
// 			self::$wsdl = 'http://shipping.mailamericas.com/api/v1/admission';   //正式环境
// 		else
// 			self::$wsdl = 'http://qa.shipping.mailamericas.com/api/v1/admission';   //测试环境
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/02/23				初始化
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
				
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
				
			//用户在确认页面提交的数据
			$e = $data['data'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];

			//获取到帐号中的认证参数
			$a = $account->api_params;

			$this->token = $a['token'];
			
			$senderAddressInfo=$info['senderAddressInfo'];
			//卖家信息
			$shippingfrom=$senderAddressInfo['shippingfrom'];
			
			//智利国家去除多余的
			if($order->consignee_country_code=='CL'){
				$city=rtrim(ltrim(str_replace('Metropolitana,','',$order->consignee_city)));
			}
			else{
				$city=$order->consignee_city;
			}
			
			//没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $city;
			}
				
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
			
			//这里是怕以后开通其它的服务
			$transportation_services=$service->shipping_method_code;
			if(in_array($service->shipping_method_code, array('registered-test')))
				$transportation_services=substr($service->shipping_method_code, 0,-5);
			
			$total_weight=0;  //kg
			$total_value=0;  
			$items=array();
			foreach ($order->items as $j=>$vitem){				
				$items[]=array(
						'quantity'=>$e['quantity'][$j],
						'sku'=>'',            //$e['sku'][$j]
						'description'=>$e['enname'][$j],
						'net_weight'=>$e['weight'][$j]/1000,
						'declared_value'=>$e['declared_value'][$j],
						'hs_code'=>$e['hs_code'][$j],
				);
				$total_weight+=$e['weight'][$j]/1000*$e['quantity'][$j];
				$total_value+=$e['declared_value'][$j]*$e['quantity'][$j];
			}

			$shipper=array(
					'company'=>$shippingfrom['company_en'],
					'name'=>$shippingfrom['contact_en'],
					'contact_name'=>$shippingfrom['contact_en'],
					'address1'=>$shippingfrom['street_en'],
					'address2'=>'',
					'address3'=>'',
					'district'=>$shippingfrom['district_en'],
					'city'=>$shippingfrom['city_en'],
					'state'=>$shippingfrom['province_en'],
					'postal_code'=>$shippingfrom['postcode'],
					'country'=>$shippingfrom['country'],
					'email'=>$shippingfrom['email'],
					'phone'=>$shippingfrom['phone'],
			);

			//哥伦比亚可默认邮编
			$zipcode=$order->consignee_postal_code;
			if(isset($service->carrier_params['Issetzip']) && $service->carrier_params['Issetzip'] && empty($order->consignee_postal_code) && $order->consignee_country_code=='CO')
				$zipcode='111111';
			
			$buyer=array(
					'name'=>$order->consignee,
					'address1'=>$addressAndPhone['address_line1'],
					'address2'=>$addressAndPhone['address_line2'],
					'address3'=>$addressAndPhone['address_line3'],
					'district'=>$order->consignee_district,
					'city'=>$city,
					'state'=>$tmpConsigneeProvince,
					'postal_code'=>$zipcode,
					'country'=>$order->consignee_country_code,
					'email'=>$order->consignee_email,
					'phone'=>$addressAndPhone['phone1'],
			);
			
			//哥伦比亚需要传新字段2017-10-19
			if($order->consignee_country_code=='CO'){
				$location=$city;
				$buyer['location']=$location;
			}
			
			$package=array(
					'net_weight'=>$total_weight,
					'weight'=>$total_weight,
					'height'=>$e['height'],
					'width'=>$e['width'],
					'length'=>$e['length'],
					'declared_value'=>$total_value,
			);
			
			//以区别来自哪些平台
			$marketplace_code="";
			switch ($order->order_source){
				case "linio" :
					$marketplace_code="MP3292";
					break;
				case "aliexpress":
					$marketplace_code="MP7182";
					break;
				case "amazon":
					$marketplace_code="MP6239";
					break;
				case "eaby":
					$marketplace_code="MP8751";
					break;
				case "wish":
					$marketplace_code="MP1866";
					break;
				default:
					$marketplace_code="";
					break;
			}
			
			//仓库地址
			$origin_warehouse_code=empty($service->carrier_params['OriginWarehouseCode'])?"WH3154":$service->carrier_params['OriginWarehouseCode'];
			
			$request = array(
					'order_id'=>$customer_number,
					'marketplace_code'=>$marketplace_code,
					'sale_date'=>date('c',time()),
					'service'=>$transportation_services,
					'origin_warehouse_code'=>$origin_warehouse_code,   //增加新字段2017-12-14
					'package'=>$package,
					'shipper'=>$shipper,
					'buyer'=>$buyer,
					'items'=>$items,
			);

			$request=json_encode($request);
		
			//判断服务选用不同接口，因为用户可能需要在测试环境上传一张单，然后下载面单来提交给货代开通token在正式环境的权限
			if(in_array($service->shipping_method_code, array('registered-test')))
				self::$wsdl='http://qa.shipping.mailamericas.com/api/v1/admission?access_token='.$this->token;  //测试环境
			else
				self::$wsdl='http://shipping.mailamericas.com/api/v1/admission?access_token='.$this->token;   //正式环境

			//数据组织完成 准备发送
			#########################################################################
			\Yii::info('LB_MAILAMERICAS,puid:'.$puid.',request,order_id:'.$order->order_id.' '.$request,"carrier_api");
			$post_head = array('Content-Type:application/json;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl,$request,$post_head);
			\Yii::info('LB_MAILAMERICAS,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response,"carrier_api");

			if($response=='Forbidden')
				return self::getResult(1,'','1.请检查密钥填写是否正确 2.密钥正确的话请使用测试渠道上传订单，打印标签出来，提供给货代认证一下。认证通过后才能使用正式渠道');
			$channelArr=json_decode($response,true);
			if(empty($channelArr))
				return self::getResult(1,'','返回数据解析错误');
	
			if(isset($channelArr['error']) && $channelArr['error']==false){
				$trackingNo=$channelArr['data']['tracking'];

				$t=$channelArr['data']['label'];
				$labelarr=explode(',', $t);
				$t_j=base64_decode($labelarr[1]);
				$pdfUrl = CarrierAPIHelper::savePDF2($t_j,$puid,$order->order_id.$customer_number."_packageLabel_".time());
				
				$orderLabelPdfPath='';
				if($pdfUrl['error'] == 0){
					$orderLabelPdfPath = $pdfUrl['filePath'];
				}
				
				if(!empty($orderLabelPdfPath)){
					$print_param = array();
					$print_param['carrier_code'] = $service->carrier_code;
					$print_param['api_class'] = 'LB_MAILAMERICASCarrierAPI';
					$print_param['label_api_file_path'] = $orderLabelPdfPath;
					$print_param['run_status'] = 4;
				
					try{
						CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
					}catch (\Exception $ex){
					}
				}

				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$trackingNo,['packageLabelPath'=>$orderLabelPdfPath]);
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号:'.$customer_number.' 追踪条码:'.$trackingNo);
			}
			else{
				if(isset($channelArr['data'])){
					$errdata=$channelArr['data'];
					if(empty($errdata))
						$msg='上传失败e2';
					else{
						foreach ($errdata as $errdataone){
							if(is_array($errdataone))
								$msg=$errdataone[0];
							else
								$msg=$errdataone;
							break;
						}
					}
				}
				else if(isset($channelArr['message'])) 
					$msg=$channelArr['message'];
				else
					$msg='上传失败e1';
				$msg2=TranslateHelper::toChinesePrompt($msg);
				return self::getResult(1,'',$msg2);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}

	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/02/23				初始化
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
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/02/23				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){

		try{
			$pdf = new PDFMerger();
			$user=\Yii::$app->user->identity;
			$puid = $user->getParentUid();

			$order = current($data);
			reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];

			//获取站点键值
			$code2CodeMap = LazadaApiHelper::getLazadaCountryCodeSiteMapping("linio");

			//记录账号和站点
			$lazada_account_site = array();

			foreach ($data as $key => $value){
				$order = $value['order'];

				if (empty($code2CodeMap[strtolower($order->order_source_site_id)]))
					return self::getResult(1,'','订单:'.$order->order_id." 站点" . $order->order_source_site_id . "不是 linio的站点。");



				if(!isset($lazada_account_site[$order->selleruserid])){
					$lazada_account_site[$order->selleruserid] = array();
				}

				if(!isset($lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)])){
					$lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)] = '';
				}

				$tmp_item_ids = $lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)];

				foreach($order->items as $item){
					$tmp_item_ids .= empty($tmp_item_ids) ? $item->order_source_order_item_id : ','.$item->order_source_order_item_id;
				}

				$lazada_account_site[$order->selleruserid][strtolower($order->order_source_site_id)] = $tmp_item_ids;
			}

			//记录返回的base64字符串
			$tmp_base64_str_a = array();

			//循环获取lazada返回的数据
			foreach ($lazada_account_site as $lazada_account_key => $lazada_account_val){
				foreach ($lazada_account_val as $lazada_site_key => $lazada_site_val){
					$SLU = SaasLazadaUser::findOne(['platform_userid' => $lazada_account_key, 'lazada_site' => $lazada_site_key]);

					if (empty($SLU)) {
						return self::getResult(1,'',$lazada_account_key . " 账号不存在" .' '. $lazada_site_key.'站点不存在');
					}

					$lazada_config = array(
						"userId" => $SLU->platform_userid,
						"apiKey" => $SLU->token,
						"countryCode" => $SLU->lazada_site
					);

					$lazada_appParams = array(
						'OrderItemIds' => $lazada_site_val,
						'DocumentType' => 'shippingParcel',
					);

					$result = LazadaInterface_Helper::getOrderShippingLabel($lazada_config, $lazada_appParams);

					if ($result['success'] && $result['response']['success'] == true) { // 成功
						$tmp_base64_str_a[] = $result["response"]["body"]["Body"]['Documents']["Document"]["File"];

					} else {
						return self::getResult(1, '', '打印失败原因：'.$result['message']);
					}
				}
			}

			foreach ($tmp_base64_str_a as $tmp_base64_val){
				$pdf_str = base64_decode($tmp_base64_val);
				$pdfurl=CarrierAPIHelper::savePDF($pdf_str,$puid,$order->order_source_order_id.'_'.$account->carrier_code,0);
				$pdf->addPDF($pdfurl['filePath'],'all');//合并相同运输运输方式的pdf流
			}

			isset($pdfurl)?$pdf->merge('file', $pdfurl['filePath']):$pdfurl['filePath']='';//需要物理地址
			return self::getResult(0,['pdfUrl'=>$pdfurl['pdfUrl']],'连接已生成,请点击并打印');//访问URL地址
// 			//最终生成的HTML
// 			$tmp_html = '';

// 			foreach ($tmp_base64_str_a as $tmp_base64_val){
// 				$tmp_html .= empty($tmp_html) ? base64_decode($tmp_base64_val) : '<hr style="page-break-after: always;border-top: 3px dashed;">'.base64_decode($tmp_base64_val);
// 			}

// 			//LGS 返回的是html代码所以直接输出即可
// 			echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /></head><body style="margin:0px;">'.$tmp_html.''.'</body>';
// 			exit;
		}catch(Exception $e) {
			return self::getResult(1,'',$e->getMessage());
		}
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			$result='registered:registered;';

			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
// 			$request = array(
// 					'data'=>$orderxml,
// 					'token'=>$data['token'],
// 					'loginName'=>$data['loginName'],
// 					'ciphertext'=>md5($orderxml.$data['token'].$data['partnerKey']),
// 			);
// 			$response = Helper_Curl::post(self::$wsdl.'/dailyBillService',$request);
			
// 			$string=Helper_xml::xmlparse($response);
// 			$channelArr=json_decode(json_encode((array) $string), true);

// 			if($channelArr['code'] == '9092400'){	
// 					$result['error'] = 0;
// 			}
// 		}catch(CarrierException $e){
// 		}
	
		return $result;
	}

}
?>