<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\carrier\models\SysCarrierAccount;
use Qiniu\json_decode;


class LB_QFHCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	
	public function __construct(){
			self::$wsdl = 'http://xlb.qfh56.com:9696/api';   //正式
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/09/02				初始化
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

			$a = $account->api_params;
			$token=  $a['userToken'];
			$logisticsId = $service['shipping_method_code'];

			$addressAndPhoneParams = array(
				'address' => array(
					'consignee_address_line1_limit' => 1000,
				),
				'consignee_district' => 1,
				'consignee_county' => 1,
				'consignee_company' => 1,
				'consignee_phone_limit' => 100
			);
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

			if( $e['charged'][0]=='' ){
				return self::getResult(1, '', '是否带电没选');
			}
			if( $e['itemType'][0]=='' ){
				return self::getResult(1, '', '物品类型没选');
			}
			$request= array();
			$request['charged']= $e['charged'][0];
			$request['itemType']= $e['itemType'][0];
			$request['logisticsId']= $service->shipping_method_code;
			$request['orderNo']= $e['customer_number'];
			$request['pracelType']= $e['pracelType'];
			$request['weight']= $e['weight'];
			$invoice = array();

			foreach ($order->items as $j=>$vitem){
				$invoice[$j]= array(
					'currency'=>$e['currency'][$j],
					'nameCN'=>$e['nameCN'][$j],
					'nameEN'=>$e['nameEN'][$j],
					'price'=>$e['price'][$j],
					'qty'=>$e['qty'][$j],
					'weight'=>$e['item_weight'][$j]/1000
				);
			}
			$request['declareInfos']= $invoice;

			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','请设置好发货地址！');
			}
			if( $order->consignee_phone=='' || $order->consignee_mobile=='' ){
				return self::getResult(1,'','收货电话和手机是必填的');
			}
			$shippingfromaddress = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息
			//发件人啊
			$sender= array(
				'address1'=>$shippingfromaddress['contact'],
				'city'=>$shippingfromaddress['city_en'],
				'country'=>$shippingfromaddress['country'],
				'mobile'=>$shippingfromaddress['mobile'],
				'name'=>$shippingfromaddress['contact'],
				'postcode'=>$shippingfromaddress['postcode'],
				'province'=>$shippingfromaddress['province_en'],
				'tel'=>$shippingfromaddress['phone']
			);
			$request['sender']= $sender;

			$recipient_country_code= (('UK' === $order->consignee_country_code) ? 'GB' : $order->consignee_country_code);
			//收件人啊
			$recipient= array(
				'address'=>$addressAndPhone['address_line1'],
				'address2'=>'',
				'address3'=>'',
				'city'=>$order->consignee_city,
				'contact_person'=>$order->consignee,
				'country_code'=>$recipient_country_code,
				'email'=>$order->consignee_email,
				'mobile_no'=>$order->consignee_mobile,
				'province'=>$order->consignee_province,
				'tel_no'=>$order->consignee_phone,
				'zip'=>$order->consignee_postal_code,
			);

			$request['recipient']= $recipient;

			$header = array();
			$header[] = 'Accept:application/json';
			$header[] = 'userToken:'.$token;
			$header[]= 'sign:'.strtoupper( md5($token.$logisticsId.$e['customer_number'].$order->consignee.$recipient_country_code.$order->consignee_province.$order->consignee_city.$addressAndPhone['address_line1'].$order->consignee_phone.$order->consignee_mobile.$order->consignee_postal_code) );

			$header[] = 'Content-type:application/json';

			//print_r (json_encode($request));exit;
			//print_r (($token.$logisticsId.$e['customer_number'].$order->consignee.$recipient_country_code.$order->consignee_province.$order->consignee_city.$addressAndPhone['address_line1'].$order->consignee_phone.$order->consignee_mobile.$order->consignee_postal_code));exit;
			$result= Helper_Curl::post( self::$wsdl.'/order/createOrder',json_encode($request),$header );
			$response=json_decode($result,true);
			if( isset( $response['success'] ) && $response['success']===true ){
				//操作成功啊
				//print_r ($response);exit;
				if( $response['isCallBack']==0 ){
					$order_status= OdOrder::CARRIER_WAITING_PRINT;
					$msg= "上传订单成功！追中条码：".$response['logistics_no'];
				}else{
					$order_status= OdOrder::CARRIER_WAITING_GETCODE;
					$msg= "上传订单成功！物流订单号：".$response['order_no'].'，请获取跟踪号.';
				}

				$r = CarrierApiHelper::orderSuccess( $order , $service , $customer_number,$order_status, $response['logistics_no'] ,['order_no'=>$response['order_no'],'logisticsId'=>$response['logisticsId']]);
				return  self::getResult(0,$r,$msg );

			}else{
				if( isset( $response['msg'] ) ){
					$msg= $response['msg'];
				}else{
					$msg= '系统错误';
				}
				return self::getResult(1, '', '上传订单失败！错误信息：' . $msg );
			}
			//print_r ($response);exit;

		}catch(CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}

	/**
	 * 取消订单
	 * @param $data
	 */
	public function cancelOrderNO($data){
		return self::getResult(1, '', '不支持此操作');
	}

	/**
	 * 交运订单
	 * @param $data
	 */
	public function doDispatch($data){
		return self::getResult(1, '', '不支持此操作');
	}

	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/09/02				初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		//return self::getResult(1, '', '不支持此操作');
		$order = $data['order'];

		$user=\Yii::$app->user->identity;
		if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
		$puid = $user->getParentUid();

		$info = CarrierAPIHelper::getAllInfo($order);
		$service = $info['service'];
		$account = $info['account'];


		$a = $account->api_params;
		$token=  $a['userToken'];
		$logisticsId = $service['shipping_method_code'];
		$checkResult = CarrierAPIHelper::validate(1, 1, $data['order']);
		$shipped = $checkResult['data']['shipped'];
		$return_info= $shipped['return_no'];
		if(empty($return_info))
		    self::getResult(1,'','订单：'.$order->order_id." 缺少部分物流商信息，请重新操作上传订单。");
		
		$orderNo= $return_info['order_no'];


		$header = array();
		$header[] = 'Accept:application/json';
		$header[] = 'userToken:'.$token;
		$header[]= 'sign:'.strtoupper( md5( $token.$logisticsId.$orderNo ) );
		$header[] = 'Content-type:application/json';

		//组装参数
		$request= array(
			'logisticsId'=>$logisticsId,
			'orderNo'=>$orderNo,

		);
		//print_r ($request);exit;
		$result= Helper_Curl::post( self::$wsdl.'/order/orderCallback',json_encode($request),$header );
		$arr= json_decode( $result,true );
		if( isset( $arr['success'] ) && $arr['success']==true ){
			$customer_number = $data['data']['customer_number'];

			$shipped->tracking_number = $arr['logistics_no'];
			$shipped->save();
			$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
			$order->tracking_number = $arr['logistics_no'];
			$order->save();
			return BaseCarrierAPI::getResult(0, '', '获取成功！追踪条码：'.$arr['logistics_no']);
		}else{
			return BaseCarrierAPI::getResult(1, '', '货代返回错误:'.$arr['msg']);
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/09/02				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{

			$order = current($data);reset($data);
			$order = $order['order'];

			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();

			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];


			$a = $account->api_params;
			$token=  $a['userToken'];

			$carrier_params = $service->carrier_params;
			$size= $carrier_params['size'];
			//渠道
			$logisticsId = $service['shipping_method_code'];

			$trackNoArr= array();
			$orderNoArr= array();
			foreach( $data as $vd ){
				$checkResult = CarrierAPIHelper::validate(1, 1, $vd['order']);
				$shipped = $checkResult['data']['shipped'];
				
				$trackNoArr[] = $shipped['tracking_number'];
				$return_info= $shipped['return_no'];
				if(empty($return_info))
				    throw new CarrierException('订单：'.$vd['order']->order_id." 缺少部分物流商信息，请重新操作上传订单。");
				
				$orderNoArr[]= $return_info['order_no'];
			}


			//获取收货人的国家代码
			$country_code= (('UK' === $order->consignee_country_code) ? 'GB' : $order->consignee_country_code);
			//组装头部
			$header = array();
			$header[] = 'Accept:application/json';
			$header[] = 'userToken:'.$token;
			$header[]= 'sign:'.strtoupper( md5( $token.$logisticsId.implode(',',$orderNoArr).implode(',',$trackNoArr) ) );
			$header[] = 'Content-type:application/json';
			
			//组装参数
			$request= array(
				'countryCode'=>$country_code,
				'logisticsId'=>$logisticsId,
				'orderNo'=>implode(',',$orderNoArr),
				'size'=>$size,
				'trackNo'=>implode(',',$trackNoArr)
			);
			//print_r ($request);exit;
			$result= Helper_Curl::post( self::$wsdl.'/order/printOrder',json_encode($request),$header );
			$arr= json_decode( $result,true );
			//print_r ($arr);exit;
			if( $arr['success']===true ){
				//print_r ($arr);exit;
				if( $arr['type']=='1' ){
					$url= $arr['url'];
					$response = Helper_Curl::get( $url );
				}
				if( $arr['type']=='0' ){
					$response= base64_decode($arr['base64']);
				}
				//echo $url;exit;
				//echo $response;exit;
				$pdfPath = CarrierAPIHelper::savePDF2($response, $puid, $order->order_id.$order->customer_number."_api_".time());
				//print_r ($pdfPath);exit;
				return self::getResult(0,['pdfUrl'=>$pdfPath['filePath']],'连接已生成,请点击并打印');
			}
		}catch ( CarrierException $e ){
			return self::getResult(1,'',$e->msg());
		}
	}
		

	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			$a = $account->api_params;
			$token=  $a['userToken'];

			$header = array();
			$header[] = 'Accept:application/json';
			$header[] = 'userToken:'.$token;
			$header[] = 'Content-type:application/json';
			$request= array(
				'a'=>'11'
			);

			//print_r ($request);exit;
			$result= Helper_Curl::post( self::$wsdl.'/order/getLogisticsChannel',$request,$header );
			$response=json_decode($result,true);

			if( isset( $response['success'] ) && $response['success']===true ){
				$channelInfos= $response['channelInfos'];
				$msg= '';
				foreach( $channelInfos as $vs ){
					$msg.= $vs['code'].':'.$vs['cnName'].';';
				}
				return self::getResult(0, $msg, '');
			}else{
				return self::getResult(1,'','运输方式更新失败啊');
			}
		}catch(CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}

	}
	//end function



	
}?>