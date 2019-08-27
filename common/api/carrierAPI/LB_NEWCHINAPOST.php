<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_Curl;
use yii\base\Exception;
use Qiniu\json_decode;

class LB_NEWCHINAPOST extends BaseCarrierAPI{
	public static $url = null;
	public static $port = null;

	
// 	1获取运单号地址：
// 	http://shipping.11185.cn:9000/produceWeb/barCodesAssgineServlet
// 	2、提交发货信息地址：
// 	http://shipping.11185.cn:8000/mqrysrv/OrderImportMultiServlet
// 	3、获取业务类型参数地址：
// 	http://shipping.11185.cn:8000/mqrysrv/OrderImportGetDataServlet
// 	4.面单下载地址
// 	http://shipping.11185.cn:8000/mqrysrv/LabelPdfDownloadServlet
	
	public function __construct(){
		if(isset(\Yii::$app->params["currentEnv"]) && \Yii::$app->params["currentEnv"]=='production'){
			self::$url = 'https://my.ems.com.cn';
			self::$port = '';
		}else{
			self::$url = 'https://211.156.195.162:';
			self::$port = 443;
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2017/03/09				初始化
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
// 			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);

			//用户在确认页面提交的数据
			$e = $data['data'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$service_carrier_params = $service->carrier_params;
			$account = $info['account'];

			//获取到账户中的地址信息
			$account_address = $info['senderAddressInfo'];

			//英国的简码可能是UK,需要转换为GB
			$tmp_consignee_country_code = $order->consignee_country_code;
			if($tmp_consignee_country_code == 'UK'){
				$tmp_consignee_country_code = 'GB';
			}

			if($tmp_consignee_country_code == 'NC'){
				$tmp_consignee_country_code = 'DF';
			}

			//收件人省份为空直接用城市代替
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($tmpConsigneeProvince)){
				$tmpConsigneeProvince = $order->consignee_city;
			}

			//组织收件人地址信息
			$addressAndPhoneParams = array(
				'address' => array(
					'consignee_address_line1_limit' => 512,
				),
				'consignee_district' => 1,
				'consignee_county' => 1,
				'consignee_company' => 1,
				'consignee_phone_limit' => 20
			);

			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

			//获取到帐号中的认证参数
			$account_api_params = $account->api_params;
			$ecCompanyId = $account_api_params['ecCompanyId'];
			$keyP = $account_api_params['token'];
			$whCode= $account_api_params['whCode'];

			$mailType = 'LITTLEBOSSERP';
			//$ecCompanyId= '90000003465705';
			//$keyP= '25ki31xP481XH68k';
			//$whCode= '23803100';
			//$barCode= $response['barCodeList'][0]['bar_code'];
			$barCode= '0'.$service->shipping_method_code;
			$weight= 0;
			$contents_total_value= 0;
            $declare_source= empty($e['declare_source'])?1:$e['declare_source'];
			$declare_type= empty($e['declare_type'])?2:$e['declare_type'];
			$declare_curr_code= 'USD';
			$forecastshut= $e['forecastshut'];
			$mail_sign= $e['mail_sign'];
			$id_type= $e['id_type'];
			$id_no= $e['id_no'];
			$tmp_order_infos = '';
            
			foreach ($e['EName'] as $k=>$v){
				$weight += (int)($e['productWeight'][$k]) * (int)($e['productQantity'][$k]);
				$contents_total_value += (int)($e['delcarevalue'][$k]) * (int)($e['productQantity'][$k]);
				$tmp_order_infos.= '
					<cargo_no>'.$e['productId'][$k].'</cargo_no>
					<cargo_name>'.$e['CName'][$k].'</cargo_name>
					<cargo_name_en>'.$e['EName'][$k].'</cargo_name_en>
					<cargo_type_name>'.$e['CName'][$k].'</cargo_type_name>
					<cargo_type_name_en>'.$e['EName'][$k].'</cargo_type_name_en>
					<cargo_origin_name></cargo_origin_name>
					<cargo_link></cargo_link>
					<cargo_quantity>'.$e['productQantity'][$k].'</cargo_quantity>
					<cargo_value>'.((int)($e['delcarevalue'][$k])).'</cargo_value>
					<cost>'.((int)($e['delcarevalue'][$k])).'</cost>
					<cargo_currency>USD</cargo_currency>
					<carogo_weight>'.((int)($e['productWeight'][$k])).'</carogo_weight>
					<cargo_description>'.$e['cargo_description'][$k].'</cargo_description>
					<cargo_serial></cargo_serial>
					<unit>个</unit>
					<intemsize></intemsize>
				';
			}



			$xml= '
			<logisticsEventsRequest>
			<logisticsEvent>
				<eventHeader>
					<eventType>YORDERCREATE</eventType>
					<eventTime>'.date("Y-m-d H:i:s").'</eventTime>
					<eventSource>SHIP</eventSource>
					<eventTarget>JDPT</eventTarget>
				</eventHeader>
				<eventBody>
					<order>
						<created_time>'.date("Y-m-d H:i:s").'</created_time>
						<sender_no>'.$ecCompanyId.'</sender_no>
						<wh_code>'.$whCode.'</wh_code>
						<mailType>'.$mailType.'</mailType>
						<logistics_order_no>'.$customer_number.'</logistics_order_no>
						<batch_no></batch_no>
						<biz_product_no>'.$barCode.'</biz_product_no>
						<weight>'.$weight.'</weight>
						<volume></volume>
						<length></length>
						<width></width>
						<height></height>
						<postage_total></postage_total>
						<postage_currency></postage_currency>
						<contents_total_weight>'.$weight.'</contents_total_weight>
						<contents_total_value>'.$contents_total_value.'</contents_total_value>
						<transfer_type></transfer_type>
						<battery_flag></battery_flag>
						<pickup_notes></pickup_notes>
						<insurance_flag></insurance_flag>
						<insurance_amount></insurance_amount>
						<undelivery_option>2</undelivery_option>
						<valuable_flag></valuable_flag>
						<declare_source>'.$declare_source.'</declare_source>
						<declare_type>'.$declare_type.'</declare_type>
						<declare_curr_code>'.$declare_curr_code.'</declare_curr_code>
						<printcode></printcode>
						<barcode></barcode>
						<forecastshut>'.$forecastshut.'</forecastshut>
						<mail_sign>'.$mail_sign.'</mail_sign>
						<sender>
							<name>'.$account_address['shippingfrom']['contact_en'].'</name>
							<company></company>
							<post_code>'.$account_address['shippingfrom']['postcode'].'</post_code>
							<phone>'.$account_address['shippingfrom']['phone'].'</phone>
							<mobile>'.$account_address['shippingfrom']['mobile'].'</mobile>
							<email></email>
							<id_type>'.$id_type.'</id_type>
							<id_no>'.$id_no.'</id_no>
							<nation>CN</nation>
							<province>'.$account_address['shippingfrom']['province_en'].'</province>
							<city>'.$account_address['shippingfrom']['city_en'].'</city>
							<county></county>
							<address>'.$account_address['shippingfrom']['street_en'].'</address>
							<gis></gis>
							<linker>'.$account_address['shippingfrom']['contact_en'].'</linker>
						</sender>
						<receiver>
							<name>'.$order->consignee.'</name>
							<company></company>
							<post_code>'.$order->consignee_postal_code.'</post_code>
							<phone>'.$addressAndPhone['phone1'].'</phone>
							<mobile></mobile>
							<email></email>
							<id_type></id_type>
							<id_no></id_no>
							<nation>'.$tmp_consignee_country_code.'</nation>
							<province>'.$tmpConsigneeProvince.'</province>
							<city>'.$order->consignee_city.'</city>
							<county>'.$tmp_consignee_country_code.'</county>
							<address>'.$addressAndPhone['address_line1'].'</address>
							<gis></gis>
							<linker>'.$order->consignee.'</linker>
						  </receiver>
						<items>
							<item>
								'.$tmp_order_infos.'
							</item>
						</items>
					</order>
				</eventBody>
			</logisticsEvent>
		</logisticsEventsRequest>';

			$data_digest= base64_encode(pack('H*', md5($xml.$keyP)));
			$post_body_data = array();
			$post_body_data['logistics_interface'] = $xml;
			$post_body_data['data_digest'] = $data_digest;
			$post_body_data['msg_type'] = 'B2C_TRADE';
			$post_body_data['ecCompanyId'] = $ecCompanyId;
			$post_body_data['data_type'] = 'XML';

			$response= self::chinaPostCurl( '/pcpErp-web/a/pcp/orderService/OrderReceiveBack', $post_body_data );
			if( !isset( $response['response'] ) ){
				return self::getResult(1, '', '失败原因:'.'未知原因请联系小老板客服');
			}else{
				$result= simplexml_load_string($response['response']);
				if($result->responseItems->response->success == 'true'){
					//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
					$r = CarrierAPIHelper::orderSuccess($order, $service, $customer_number, OdOrder::CARRIER_WAITING_PRINT, $result->responseItems->response->waybillNo);

					return BaseCarrierAPI::getResult(0, $r, '操作成功!跟踪号'.$result->responseItems->response->waybillNo);
				}else{
					$tmp_error = $result->responseItems->response->msg;
					return self::getResult(1, '', '失败原因e2:'.$result->responseItems->response->reason.' '.$tmp_error);
				}


			}
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
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
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
			if(count($data) > 50){
				throw new CarrierException('中国邮政一次只能批量打印50张面单');
			}
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
			//获取到帐号中的认证参数
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;

			$ecCompanyId = $account_api_params['ecCompanyId'];
			$keyP = $account_api_params['token'];
			$tmp_barCode = '';

			foreach ($data as $key => $value) {
				$order = $value['order'];

				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];

				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$order->order_id .' 缺少跟踪号,请检查订单是否已上传' );
				}

				$tmp_barCode .= ($tmp_barCode == '') ? $shipped->tracking_number : ','.$shipped->tracking_number;
			}

			$tmp_barCode_Digest = base64_encode(pack('H*', md5($tmp_barCode.$keyP)));

			$post_body_data = array();
			$post_body_data['ecCompanyId'] = $ecCompanyId;
			$post_body_data['dataDigest'] = $tmp_barCode_Digest;
			$post_body_data['barCode'] = $tmp_barCode;
			//$post_body_data['labelType'] = empty($carrier_params['labelType']) ? '5' : $carrier_params['labelType'];
			$post_body_data['version'] = '1.0';

			$result = self::chinaPostCurl('/pcpErp-web/a/pcp/surface/download ', $post_body_data);

			if($result['http_code'] == 200){

				$pdfUrl = CarrierAPIHelper::savePDF($result['response'], $puid, $account->carrier_code.'_', 0);

				return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			}else if(($result['http_code'] != 200) && ($result['content_type'] == 'text/json')){
				return self::getResult(1, '', $result['response']);
			}else{
				return self::getResult(1, '', $result['response']);
			}

		}catch(Exception $e) {
			return self::getResult(1,'',$e->getMessage());
		}
	}

	
	/**
	 * 用来获取运输方式
	 * @author		hqw		2017/03/16				初始化
	 * 公共方法
	 **/
	public static function getCarrierShippingServiceStr($account){
		return BaseCarrierAPI::getResult(0, '', '');
	}


	//封装为通用访问中国邮政接口
	public static function chinaPostCurl($request_url, $post_data){
		$header=array();
		$header[] = 'Content-type:application/x-www-form-urlencoded;charset=utf-8';

		$o = "";
		foreach ($post_data as $k => $v){
			$o .= "$k=" . urlencode ( $v ) . "&";
		}
		$tmp_post_data = substr ($o, 0, - 1);
		$response_json = Helper_Curl::post(self::$url.self::$port.$request_url, $tmp_post_data, $header);
// 		print_r(Helper_Curl::$last_post_info);

		$result = array('http_code'=>Helper_Curl::$last_post_info['http_code'], 'content_type'=>Helper_Curl::$last_post_info['content_type'], 'response'=>$response_json);

		return $result;
	}

}

?>