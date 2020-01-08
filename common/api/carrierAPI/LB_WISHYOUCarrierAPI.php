<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\StandardConst;
use Jurosh\PDFMerge\PDFMerger;
use eagle\modules\platform\controllers\WishPostalV2Controller;

// try{
// 	include dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'PDFMerger'.DIRECTORY_SEPARATOR.'PDFMerger.php';
// }catch(\Exception $e){
// }

error_reporting(0);	//wish邮返回有时解释不了xml

class LB_WISHYOUCarrierAPI extends BaseCarrierAPI
{
	static private $wishUrl = '';	// 物流接口
	public $appKey = null;

	public function __construct(){
		//wish邮没有测试环境
		self::$wishUrl = 'http://www.shpostwish.com/';
	}

	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/16				初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();

			//odOrder表内容
			$order = $data['order'];
			$o = $order->attributes;

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
			$service_carrier_params = $service->carrier_params;
			$account = $info['account'];

			if(!isset($service_carrier_params['warehouse_code'])){
				return self::getResult(1, '', 'wish邮添加了:分仓代码请到物流参数那边设置');
			}

			if(empty($info['senderAddressInfo'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			$shippingfrom_enaddress = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息



			//获取到帐号中的认证参数
			$api_params = $account->api_params;
			//取访问令牌
			$ret = WishPostalV2Controller::ReturnAccessToken($account->id);
			if($ret['status'] != 0)
			    return self::getResult(1, '', $ret['msg']);
			$access_token = $ret['data'];

			if(!isset($api_params['doorpickup'])){
				return self::getResult(1, '', 'wish邮添加了揽收方式，请先到账号管理那边设置，再上传。');
			}

// 			if($order->order_source != 'wish'){
// 				return self::getResult(1, '', '订单来源只能为wish');
// 			}

			if (empty($order->consignee_city)){
				return self::getResult(1, '', '城市不能为空');
			}

			//法国没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($tmpConsigneeProvince)){
				$tmpConsigneeProvince = $order->consignee_city;
			}

			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}

			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}

// 			if (empty($order->consignee_country)){
// 				return self::getResult(1, '', '国家信息不能为空');
// 			}

			if (empty($order->consignee_address_line1)){
				return self::getResult(1, '', '地址1不能为空');
			}

			if (empty($e['from_country'])){
				return self::getResult(1, '', '货物原产国不能为空');
			}

			$phoneContact = '';
			if (empty($order->consignee_phone) && empty($order->consignee_mobile)){
				return self::getResult(1, '', '联系方式不能为空');
			}

			if (empty($order->consignee_phone) || empty($order->consignee_mobile)){
				$phoneContact = $order->consignee_phone.$order->consignee_mobile;
			}else{
				$phoneContact = $order->consignee_phone.','.$order->consignee_mobile;
			}

			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 100,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 20
			);

			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			//print_r ($addressAndPhone);



			$phoneSender = '';
			if(empty($shippingfrom_enaddress['phone']) && empty($shippingfrom_enaddress['mobile'])){
				return self::getResult(1, '', '寄件人电话不能为空');
			}

			if(!empty($shippingfrom_enaddress['phone'])){
				$phoneSender = $shippingfrom_enaddress['phone'];
			}else{
				$phoneSender = $shippingfrom_enaddress['mobile'];
			}
			if( empty( $shippingfrom_enaddress['postcode'] ) ){
				return self::getResult(1, '', '寄件人邮编不能为空');
			}else{
				$codeSender= $shippingfrom_enaddress['postcode'];
			}

			//重复发货 添加不同的标识码
// 			$extra_id = isset($data['extra_id'])?$data['extra_id']:'';
// 			$customer_number = CarrierAPIHelper::getCustomerNum($order,$extra_id);

			$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
				(empty($order->consignee_district) ? '' : ','.$order->consignee_district);

			$totalQty = 0;
			$totalWeight = 0;
			$totalAmount = 0;
			$enName = '';
			$cnName = '';
			$product_url = '';

			foreach ($order->items as $j=>$vitem){
				$enName .= $e['EName'][$j].';';
				$cnName .= $e['CN_Name'][$j].';';
				if(!empty($product_url)){
					$product_url .= ';';
				}
				$product_url .= $e['product_url'][$j];

				$totalQty += (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]);
				$totalWeight += ($e['weight'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j])) / 1000;
				$totalAmount += $e['DeclaredValue'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]);
			}

			$enName=substr($enName,0,-1);
			$cnName=substr($cnName,0,-1);

			if (empty($enName)){
				return self::getResult(1, '', '英文报关名不能为空');
			}
			if (empty($cnName)){
				return self::getResult(1, '', '中文报关名不能为空');
			}

			$recipient_country = '';
			if(isset(self::$wishCountry[$order->consignee_country_code])){
				$recipient_country = self::$wishCountry[$order->consignee_country_code];
			}else{
				$recipient_country = $order->consignee_country;
			}

			if (empty($recipient_country)){
				return self::getResult(1, '', '国家信息不能为空');
			}

			//英国作特殊处理
			$recipient_country_short = $order->consignee_country_code;
			if($recipient_country_short == 'UK'){
				$recipient_country_short = 'GB';
			}

			//运输方式 0平邮 1挂号
			$tmpotype = 1;
			if( substr( $service->shipping_method_code, 0, 1) == 'w'){
			    if(isset($service_carrier_params['otype']))
				    $tmpotype = $service_carrier_params['otype'] == '' ? 1 : $service_carrier_params['otype'];
			}
			else if( substr( $service->shipping_method_code, 0, 3) == '300'){
			    $tmpotype = 1;
			}
			else if(in_array($service->shipping_method_code, ['11-', '31-', '21-', '22-', '23-', '36-', '37-', '201-', '300-'])){
				$tmpotype = 1;
			}
			else
			    $tmpotype = 0;

			//运输代码是否添加前缀
			$method_code = '';
			if($service->shipping_method_code != 'wishPostal'){
				$method_code = $service->shipping_method_code;
			}

			//非上海仓中邮需要揽收地址
			$receive_info = '';
			if($api_params['doorpickup'] == 1 || ($service->shipping_method_code == 'wishPostal' && $service_carrier_params['warehouse_code'] != 1)){
				if(empty($info['senderAddressInfo']['pickupaddress'])){
					return self::getResult(1, '', '揽收信息请先这设置');
				}
				$shippingfrom_pickupaddress = $info['senderAddressInfo']['pickupaddress'];//获取到账户中的地址信息

				if (empty($shippingfrom_pickupaddress['contact'])){
					return self::getResult(1, '', '揽收联系人不能为空');
				}

				if (empty($shippingfrom_pickupaddress['province'])){
					return self::getResult(1, '', '揽收省不能为空');
				}

				if (empty($shippingfrom_pickupaddress['city'])){
					return self::getResult(1, '', '揽收城市不能为空');
				}

				if (empty($shippingfrom_pickupaddress['street'])){
					return self::getResult(1, '', '揽收地址不能为空');
				}

				if (empty($shippingfrom_pickupaddress['mobile'])){
					return self::getResult(1, '', '揽收手机不能为空');
				}

				$receive_info = '<receive_from>'.$shippingfrom_pickupaddress['contact'].'</receive_from>'.
					'<receive_province>'.$shippingfrom_pickupaddress['province'].'</receive_province>'.
					'<receive_city>'.$shippingfrom_pickupaddress['city'].'</receive_city>'.
					'<receive_addres>'.$shippingfrom_pickupaddress['street'].'</receive_addres>'.
					'<receive_phone>'.$shippingfrom_pickupaddress['mobile'].'</receive_phone>';
			}
			if( $e['is_wish_order']=='0' ){
				$order_source_order_id= 'nonWishOrder';
			}else{
				$order_source_order_id= $order->order_source_order_id;
			}

			$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
				'<orders>'.
				'<access_token>'.$access_token.'</access_token>'.
				'<mark></mark>'.
				'<bid>1</bid>'.
				'<order>'.
				'<guid>'.$customer_number.'</guid>'.
				'<otype>'.$method_code.$tmpotype.'</otype>'.
				'<from>'.$shippingfrom_enaddress['contact_en'].'</from>'.
				'<sender_addres>'.$shippingfrom_enaddress['street_en'].'</sender_addres>'.
				'<sender_province>'.$shippingfrom_enaddress['province_en'].'</sender_province>'.
				'<sender_city>'.$shippingfrom_enaddress['city_en'].'</sender_city>'.
				'<sender_phone>'.$phoneSender.'</sender_phone>'.
				'<sender_zipcode >'.$codeSender.'</sender_zipcode>'.
				'<to>'.$order->consignee.'</to>'.
				'<to_local></to_local>'.
				'<recipient_addres>'.$addressAndPhone['address_line1'].'</recipient_addres>'.
				'<recipient_addres_local></recipient_addres_local>'.
				'<recipient_country>'.$recipient_country.'</recipient_country>'.
				'<recipient_country_short>'.$recipient_country_short.'</recipient_country_short>'.
				'<recipient_country_local></recipient_country_local>'.
				'<recipient_province>'.$tmpConsigneeProvince.'</recipient_province>'.
				'<recipient_province_local></recipient_province_local>'.
				'<recipient_city>'.$order->consignee_city.'</recipient_city>'.
				'<recipient_city_local></recipient_city_local>'.
				'<recipient_postcode>'.$order->consignee_postal_code.'</recipient_postcode>'.
				'<recipient_phone>'.$addressAndPhone['phone1'].'</recipient_phone>'.
				'<content>'.$enName.'</content>'.
				'<content_chinese>'.$cnName.'</content_chinese>'.
				'<type_no>'.$e['type_no'].'</type_no>'.
				'<weight>'.round($totalWeight, 3).'</weight>'.
				'<num>'.$totalQty.'</num>'.
				'<single_price>'.round($totalAmount, 2).'</single_price>'.
				'<from_country>'.$e['from_country'].'</from_country>'.
				'<user_desc>'.str_replace("&", "", $e['user_desc']).'</user_desc>'.

				'<trande_no>'.$order_source_order_id.'</trande_no>'.

				'<trade_amount>'.$e['trade_amount'].'</trade_amount>'.
				'<doorpickup>'.$api_params['doorpickup'].'</doorpickup>'.
				'<warehouse_code>'.$service_carrier_params['warehouse_code'].'</warehouse_code>'.
				'<has_battery>'.$e['has_battery'].'</has_battery>'.
				'<product_url>'.$product_url.'</product_url>'.
				$receive_info.
				'</order>'.
				'</orders>';

			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';

			//数据组织完成 准备发送
			#########################################################################
			\Yii::info("LB_WISHYOUCarrierAPI getOrderNO request:".$puid.".".$order->order_id.".".print_r($getorder_xml,1),"carrier_api");

// 			print_r($getorder_xml);
// 			exit;

			$response = Helper_Curl::post('https://www.wishpost.cn/api/v2/create_order',$getorder_xml,$header);
			\Yii::info("LB_WISHYOUCarrierAPI getOrderNO response:".$puid.".".$order->order_id.".".$response,"carrier_api");

			$xml = simplexml_load_string($response);//将xml转化为对象
			$xml = (array)$xml;

			if (!isset($xml['status'])){
				throw new CarrierException('操作失败,wish邮返回错误');
			}

			if($xml['status'] != 0)
			{
			    if( empty($xml['error_message']))
    				throw new CarrierException($xml['error-message']);
			    else
			        throw new CarrierException($xml['error_message']);
			}

			if(!empty($xml['barcode'])){
			    $OrderSign = array();
			    $OrderSign['OrderSign'] = $xml['mark'];
			    if($method_code == '9-' || $method_code == '10-')
			    {
    			    $OrderSign['c_code'] = $xml['c_code'];
    			    $OrderSign['q_code'] = $xml['q_code'];
    			    $OrderSign['y_code'] = $xml['y_code'];
			    }
				$OrderSign['wish_standard_tracking_id']= $xml['wish_standard_tracking_id'];

				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
				if( is_string( $xml['barcode'] )===true ){
					$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$xml['barcode'],$OrderSign);
				}else{
					//date 2018年9月5日10:47:34 wish接口改动
					$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_GETCODE,'',$OrderSign);
				}




				$print_param = array();
				$print_param['carrier_code'] = $service->carrier_code;
				$print_param['api_class'] = 'LB_WISHYOUCarrierAPI';
				$print_param['APIKey'] = $this->appKey;
				$print_param['access_token'] = $access_token;
				if( is_string( $xml['barcode'] )===true ) {
					$print_param['tracking_number'] = $xml['barcode'];
				}else{
					$print_param['tracking_number'] = '';
				}
				$print_param['account_id'] = $account->id;
				$print_param['carrier_params'] = $service->carrier_params;

				try{
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);

				}catch (\Exception $ex){
				}
				
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!物流订单号'.$xml['wish_standard_tracking_id']);
			}

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消跟踪号');
	}

	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运');
	}

	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
	    try{
	    	$user=\Yii::$app->user->identity;
	    	if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
	    	$puid = $user->getParentUid();

	    	//odOrder表内容
	    	$order = $data['order'];

			$checkResult = CarrierAPIHelper::validate(0, 1, $order);
			$shipped = $checkResult['data']['shipped'];
			$return_no= $shipped->return_no;
			if( empty( $return_no ) || !isset( $return_no['wish_standard_tracking_id'] ) ){
				return self::getResult(1, '', '交运信息或者物流订单号缺失');
			}
			$wish_standard_tracking_id= $return_no['wish_standard_tracking_id'];

	    	//获取到所需要使用的数据
	    	$info = CarrierAPIHelper::getAllInfo($order);
	    	$service = $info['service'];
	    	$account = $info['account'];

	    	//取访问令牌
	    	$ret = WishPostalV2Controller::ReturnAccessToken($account->id);
	    	if($ret['status'] != 0)
	    		return self::getResult(1, '', $ret['msg']);
	    	$access_token = $ret['data'];

			$api_url= "https://www.wishpost.cn/api/v3/order_status";

			$post_str= array(
				'access_token'=>$access_token,
				'wish_standard_tracking_ids'=>array($wish_standard_tracking_id)
			);

			$result= Helper_Curl::post( $api_url,json_encode($post_str) );

			$result_arr= json_decode( $result,true );
			if( isset( $result_arr['code'] )  && $result_arr['code']==0  ){
				if( isset( $result_arr['orders'][0]['logistics_order_code'] ) ){
					$tracking_number= $result_arr['orders'][0]['logistics_order_code'];
					if( $tracking_number!='' ){
						$shipped->tracking_number = $tracking_number;
						$shipped->save();
						$order->tracking_number = $tracking_number;
						$order->carrier_step= OdOrder::CARRIER_WAITING_PRINT;
						$order->save();
					}else{
						return self::getResult(1, '', '物流商返回跟踪号为空,'.$result_arr['message'].',物流订单号:'.$wish_standard_tracking_id);
					}
				}else{
					return self::getResult(1, '', '物流商订单号未查询到相关订单信息');
				}
			}else{
				return self::getResult(1, '', '物流商订单接口返回失败');
			}

			$print_param = array();
    		$print_param['carrier_code'] = $service->carrier_code;
    		$print_param['api_class'] = 'LB_WISHYOUCarrierAPI';
    		$print_param['APIKey'] = $this->appKey;
    		$print_param['access_token'] = $access_token;
    		$print_param['tracking_number'] = $tracking_number;
    		$print_param['account_id'] = $account->id;
    		$print_param['carrier_params'] = $service->carrier_params;

    		try{
    			CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
    		}catch (\Exception $ex){
    		}

	    }catch(CarrierException $e){}

	    return BaseCarrierAPI::getResult(0, '', '操作成功！');
	}

	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/19				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			if(count($data) > 50)
				throw new CarrierException('wish邮一次只能批量打印50张面单');

			$order = current($data);reset($data);
			$order = $order['order'];

			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();

			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$carrier_params = $service->carrier_params;

			//取访问令牌
			$ret = WishPostalV2Controller::ReturnAccessToken($account->id);
			if($ret['status'] != 0)
				return self::getResult(1, '', $ret['msg']);
			$access_token = $ret['data'];

			$printlang = 1;
			$printcode = 1;

			if(!empty($carrier_params['format'])){
				switch ($carrier_params['format']){
					case 'enA4':
						$printlang = 1;
						$printcode = 1;
						break;
					case 'en4x4':
						$printlang = 1;
						$printcode = 2;
						break;
					case 'toA4':
						$printlang = 2;
						$printcode = 1;
						break;
					case 'to4x4':
						$printlang = 2;
						$printcode = 2;
						break;
					default:
						$printlang = 1;
						$printcode = 1;
				}
			}

			$tmpBarcodeStr = '';

			foreach ($data as $k=>$v) {
				$order = $v['order'];

				$checkResult = CarrierAPIHelper::validate(1, 1, $order);
				$shipped = $checkResult['data']['shipped'];

				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$order->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}

				$tmpBarcodeStr .= '<barcode>'.$shipped['tracking_number'].'</barcode>';

				//添加订单标签打印时间
// 				$order->is_print_carrier = 1;
				$order->print_carrier_operator = $puid;
				$order->printtime = time();
				$order->save();
			}

			$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
					'<root>'.
					'<access_token>'.$access_token.'</access_token>'.
					'<printlang>'.$printlang.'</printlang>'.
					'<printcode>'.$printcode.'</printcode>'.
					'<barcodes>'.
					$tmpBarcodeStr.
					'</barcodes>'.
					'</root>';

			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';

			$response = Helper_Curl::post('https://www.wishpost.cn/api/v2/generate_label',$getorder_xml,$header);

			\Yii::info("LB_WISHYOUCarrierAPI doPrint:".$puid.".".$order->order_id.".".$response,"carrier_api");
			
			$xml = simplexml_load_string($response);//将xml转化为对象
			$xml = (array)$xml;

			if (!isset($xml['status'])){
				throw new CarrierException('操作失败,wish邮返回错误');
			}

			if($xml['status'] == 0){
// 				return self::getResult(0,['pdfUrl'=>$xml['PDF_URL']],'连接已生成,请点击并打印');

			    $response = Helper_Curl::get($xml['PDF_URL'],null,null,false,null,null,true);
			    if($response === false)
			        return self::getResult(1,'','Wish邮返回打印超时！');
			     
			    if(strlen($response)<1000){
			        return self::getResult(1, '', '接口返回内容不是一个有效的PDF！');
			    }
			     
			    $pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$order->customer_number, 0);
			    return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			}else
			{
				if( empty($xml['error_message']))
					throw new CarrierException($xml['error-message']);
				else
					throw new CarrierException($xml['error_message']);
			}

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	/*
	 * 用来确定打印完成后 订单的下一步状态
	*
	* 公共方法
	*/
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
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
			$puid = $SAA_obj->uid;

			//设置访问令牌
			$access_token = $print_param['access_token'];

			$carrier_params = $print_param['carrier_params'];
			$printlang = 1;

			if(!empty($carrier_params['format'])){
				switch ($carrier_params['format']){
					case 'enA4':
						$printlang = 1;
						break;
					case 'en4x4':
						$printlang = 1;
						break;
					case 'toA4':
						$printlang = 2;
						break;
					case 'to4x4':
						$printlang = 2;
						break;
					default:
						$printlang = 1;
				}
			}

			$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
					'<root>'.
					'<access_token>'.$access_token.'</access_token>'.
					'<printlang>'.$printlang.'</printlang>'.
					'<printcode>2</printcode>'.
					'<barcodes>'.
					'<barcode>'.$print_param['tracking_number'].'</barcode>'.
					'</barcodes>'.
					'</root>';

			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';

			$response = Helper_Curl::post('https://www.wishpost.cn/api/v2/generate_label',$getorder_xml,$header);

			$xml = simplexml_load_string($response);//将xml转化为对象
			$xml = (array)$xml;

			if (!isset($xml['status'])){
				return ['error'=>1, 'msg'=>'操作失败,wish邮返回错误', 'filePath'=>''];
			}

			if($xml['status'] == 0){
				$responsePdf = Helper_Curl::post($xml['PDF_URL']);
				$pdfPath = CarrierAPIHelper::savePDF2($responsePdf,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());

				return $pdfPath;
			}else
			{
		    	if( empty($xml['error_message']))
		    		return ['error'=>1, 'msg'=>$xml['error-message'], 'filePath'=>''];
		    	else
		    		return ['error'=>1, 'msg'=>$xml['error_message'], 'filePath'=>''];
			}
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
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
	public function getVerifyCarrierAccountInformation($data)
	{
		/*$result = array('is_support'=>1,'error'=>1);

		try
		{
			$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
					'<root>'.
					'<api_key>'.$data['APIKey'].'</api_key>'.
					'<printlang>1</printlang>'.
					'<printcode>1</printcode>'.
					'<barcodes>'.
					'<barcode>00001</barcode>'.
					'</barcodes>'.
					'</root>';

			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';

			$response = Helper_Curl::post(self::$wishUrl.'get_pdf.asp',$getorder_xml,$header);
			$xml = simplexml_load_string($response);//将xml转化为对象
			$xml = (array)$xml;

			if(isset($xml['status']) && $xml['status'] == 0)
				$result['error'] = 0;
		}
		catch(CarrierException $e){}*/

		return array('is_support'=>0,'error'=>0);
	}

	public static $wishCountry = array(
			'EG'=>'Egypt',
			'AI'=>'Anguilla',
			'AG'=>'Antigua And Barbuda',
			'PR'=>'Puerto rico',
			'BW'=>'Botswana',
			'GQ'=>'Equatorial Guinea',
			'GL'=>'Greenland',
			'GE'=>'Georgia',
			'KZ'=>'Kazakhstan',
			'AN'=>'Netherands Antilles',
			'GW'=>'Guinea Bissau',
			'CI'=>'Ivory Coast',
			'HR'=>'Croatia',
			'RO'=>'Romania',
			'FK'=>'Malvinas Islands',
			'YT'=>'Mayotte',
			'SK'=>'Slovakia',
			'TJ'=>'Tajikistan',
			'AM'=>'Armenia',
			'AL'=>'Albania',
			'DZ'=>'Algeria',
			'AF'=>'Afghanistan',
			'AR'=>'Argentina',
			'AE'=>'Arab Emirates',
			'AW'=>'Aruba',
			'OM'=>'Oman',
			'AZ'=>'Azerbaijan',
			'ET'=>'Ethiopia',
			'IE'=>'Ireland',
			'EE'=>'Estonia',
			'AD'=>'Andorra',
			'AO'=>'Angola',
			'AT'=>'Austria',
			'AU'=>'Australia',
			'BB'=>'Barbados',
			'PY'=>'Paraguay',
			'PG'=>'Papua New Guinea',
			'BS'=>'Bahamas',
			'PK'=>'Pakistan',
			'PS'=>'Palestine',
			'BH'=>'Bahrain',
			'PA'=>'Panama',
			'BR'=>'Brazil',
			'BY'=>'Belarus',
			'BM'=>'Bermuda',
			'BG'=>'Bulgaria',
			'BJ'=>'Benin',
			'BE'=>'Belgium',
			'IS'=>'Iceland',
			'BA'=>'Bosnia And Herzegovina',
			'PL'=>'Poland',
			'BO'=>'Bolivia',
			'BZ'=>'Belize',
			'BT'=>'Bhutan',
			'BF'=>'Burkina Faso',
			'BI'=>'Burundi',
			'KP'=>'North Korea',
			'DK'=>'Denmark',
			'DE'=>'Germany',
			'TP'=>'Timor-Leste',
			'TG'=>'Togo',
			'DO'=>'Dominican',
			'DM'=>'Dominica',
			'RU'=>'Russia',
			'EC'=>'Ecuador',
			'FR'=>'France',
			'PF'=>'French Polynesia',
			'GF'=>'French Guiana',
			'VA'=>'Vatican',
			'PH'=>'Philippines',
			'FJ'=>'Fiji',
			'FI'=>'Finland',
			'CV'=>'Cape Verde',
			'FK'=>'Falkland Islands',
			'GM'=>'Gambia',
			'CG'=>'Congo',
			'CO'=>'Colombia',
			'CR'=>'Costa Rica',
			'GD'=>'Grenada',
			'CU'=>'Cuba',
			'GP'=>'Guadeloupe',
			'GU'=>'Guam',
			'GY'=>'Guyana',
			'HT'=>'Haiti',
			'KR'=>'Korea',
			'NL'=>'Netherland',
			'ME'=>'Montenegro',
			'HN'=>'Honduras',
			'KI'=>'Kiribati',
			'DJ'=>'Djibouti',
			'KG'=>'Kirghizstan',
			'GN'=>'Guinea',
			'CA'=>'Canada',
			'GH'=>'Ghana',
			'GA'=>'Gabon',
			'KH'=>'Cambodia',
			'CZ'=>'Czech',
			'ZW'=>'Zimbabwe',
			'CM'=>'Cameroon',
			'QA'=>'Qatar',
			'KY'=>'Cayman Islands',
			'KW'=>'Kuwait',
			'KE'=>'Kenya',
			'LV'=>'Latvia',
			'LS'=>'Lesotho',
			'LA'=>'Laos',
			'LB'=>'Lebanon',
			'LT'=>'Lithuania',
			'LR'=>'Liberia',
			'LI'=>'Liechtenstein',
			'RE'=>'Reunion',
			'LU'=>'Luxembourg',
			'RW'=>'Rwanda',
			'MG'=>'Madagascar',
			'MV'=>'Maldives',
			'MT'=>'Malta',
			'MY'=>'Malaysia',
			'ML'=>'Mali',
			'MK'=>'Macedonia',
			'MQ'=>'Martinique ',
			'MU'=>'Mauritius',
			'MR'=>'Mauritania',
			'US'=>'United States',
			'VI'=>'Us Virgin Island',
			'MN'=>'Mongolia',
			'BD'=>'Bangladesh',
			'PE'=>'Peru',
			'MM'=>'Myanmar',
			'MD'=>'Moldova',
			'MC'=>'Monaco',
			'MA'=>'Morocco',
			'MZ'=>'Mozambique',
			'MX'=>'Mexico',
			'NA'=>'Namibia',
			'ZA'=>'South Africa',
			'NR'=>'Nauru',
			'NP'=>'Nepal',
			'NI'=>'Nicaragua',
			'NE'=>'Niger',
			'NG'=>'Nigeria',
			'NO'=>'Norway',
			'PW'=>'Palau',
			'PT'=>'Portugal',
			'JP'=>'Japan',
			'SE'=>'Sweden',
			'CH'=>'Switzerland',
			'SV'=>'Salvador',
			'RS'=>'Serbia',
			'SL'=>'Sierra Leone',
			'SN'=>'Senegal',
			'CY'=>'Cyprus',
			'SC'=>'Seychelles',
			'SA'=>'Saudi Arabia',
			'LK'=>'Sri Lanka',
			'SI'=>'Slovenia',
			'SD'=>'Sudan',
			'SR'=>'Suriname',
			'SB'=>'Solomon Islands',
			'TH'=>'Thailand',
			'TZ'=>'Tanzania',
			'TO'=>'Tonga',
			'TT'=>'Trinidad And Tobago',
			'TN'=>'Tunisia',
			'TV'=>'Tuvalu',
			'TR'=>'Turkey',
			'TM'=>'Turkmenistan',
			'VU'=>'Vanuatu',
			'GT'=>'Guatemala',
			'VE'=>'Venezuela',
			'BN'=>'Brunei Darussalam',
			'UG'=>'Uganda ',
			'UA'=>'Ukraine',
			'UY'=>'Uruguay',
			'UZ'=>'Uzbekistan',
			'ES'=>'Spain',
			'GR'=>'Greece',
			'SG'=>'Singapore',
			'NC'=>'New Caledonia',
			'NZ'=>'New Zealand',
			'HU'=>'Hungary',
			'SY'=>'Syria',
			'JM'=>'Jamaica',
			'IQ'=>'Iraq ',
			'IR'=>'Iran ',
			'IL'=>'Israel',
			'IT'=>'Italy',
			'IN'=>'India',
			'ID'=>'Indonesia',
			'GB'=>'United Kingdom',
			'VG'=>'British Virgin',
			'JO'=>'Jordan',
			'VN'=>'Vietnam',
			'ZM'=>'Zambia ',
			'ZR'=>'Zaire',
			'TD'=>'Chad',
			'GI'=>'Gibraltar',
			'CL'=>'Chile',
			'CF'=>'Central Africa',
			'SZ'=>'swaziland',
			'PM'=>'Saint Pierre and Miquelon',
			'YE'=>'Yemen',
			'FO'=>'Faroe Islands',
			'SM'=>'San Marino',
			'KN'=>'Saint Kitts and nevis',
			'LC'=>'Saint lucia',
			'AS'=>'American Samoa',
			'CK'=>'Cook Islands',
			'SRB'=>'Serbia',
			'MNE'=>'Montenegro',
			'MW'=>'Malawi',
			'ASC'=>'Ascension Island',
			'CC'=>'Cocos Island',
			'CX'=>'Christmas Island',
			'EH'=>'Western Sahara',
			'ER'=>'Eritrea',
			'FM'=>'Micronesia',
			'GG'=>'Guernsey',
			'JU'=>'Yugoslavia',
			'KM'=>'Comoros',
			'MH'=>'Marshall Island',
			'MP'=>'Mariana lslands',
			'MS'=>'Montserrat',
			'NF'=>'Norfolk Island',
			'NU'=>'Niue',
			'SGS'=>'South Georgia and The South Sandwich Islands',
			'SH'=>'Saint Helena',
			'SJ'=>'The Svalbard archipelago',
			'SS'=>'Sudan',
			'ST'=>'Sao Tome and Principe',
			'TC'=>'The Turks and Caicos Islands',
			'TK'=>'Tokelau',
			'TL'=>'Timor-Leste',
			'UK'=>'United Kingdom',
			'UM'=>'United States Minor Outlying Islands',
			'VC'=>'Saint Vincent',
			'WF'=>'Wallis and Futuna',
			'WS'=>'The Independent State of Samoa',
			'XM'=>'Sint Maarten',
			'XN'=>'Federation of Saint Christopher and Nevis',
			'CRO'=>'Croatia',
			'CS'=>'Czecj',
			'KT'=>'Ivory Coast',
			'MDV'=>'Maldives',
	);

}

?>