<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use common\helpers\SubmitGate;
use common\helpers\Helper_Curl;
use yii\base\Exception;

class  	LB_FCKCarrierAPI extends BaseCarrierAPI{
	public static $wsdl = null;		//订单操作
	public static $wsdltools = null;	//订单工具
	public $authToken=null;
	
	public function __construct(){
		self::$wsdl = 'http://flystar.vekinerp.com/ois_server.php?wsdl';

		$this->submitGate = new SubmitGate();

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
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
	
			//用户在确认页面提交的数据
			$data = $data['data'];
	
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			
			$service_params = $service->carrier_params;
	
			//获取到帐号中的认证参数
			$a = $account->api_params;
			$this->authToken = $a['token'];

			//获取到账户中的地址信息
			$account_address = $info['senderAddressInfo'];

			//组织地址信息
			$addressAndPhoneParams = array(
				'address' => array(
					'consignee_address_line1_limit' => 500,
				),
				'consignee_district' => 1,
				'consignee_county' => 1,
				'consignee_company' => 1,
				'consignee_phone_limit' => 100
			);

			$carrierAddressAndPhoneInfo = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);


			$shipmentInfo= array();
			$shipmentInfo['paypalid']= $customer_number;
			$shipmentInfo['consignee']= $order->consignee;
			$shipmentInfo['street1']= $carrierAddressAndPhoneInfo['address_line1'];
			$shipmentInfo['city']= str_replace( '-',' ',$order->consignee_city );
			$shipmentInfo['country']= $order->consignee_country;
			$shipmentInfo['CountryCode']= (('UK' === $order->consignee_country_code) ? 'GB' : $order->consignee_country_code);
			$shipmentInfo['zipcode']= $order->consignee_postal_code;
			if( $order->consignee_mobile=='' ){
				$shipmentInfo['tel']= $order->consignee_mobile;
			}else{
				$shipmentInfo['tel']= $order->consignee_phone;
			}
			$shipmentInfo['ShippingService']= $service->shipping_method_code;

			$shipmentInfo['userid']= '';
			$shipmentInfo['email']= '';
			$shipmentInfo['street2']= '';
			$shipmentInfo['state']= '';
			$shipmentInfo['pay_note']= '';
			$shipmentInfo['print_packup_info']= 'Y';
			$shipmentInfo['length']= '';
			$shipmentInfo['width']= '';
			$shipmentInfo['height']= '';
			$shipmentInfo['weight']= '';


			$shipmentGoodsInfo= array();
			foreach ($order->items as $j=>$vitem){
				$shipmentGoodsInfo[$j]=[
					'goods_sn'=>$vitem->sku,
					'goods_qty'=>empty($data['quantity'][$j])?$vitem->quantity:$data['quantity'][$j] ,
					'goods_price'=>empty($data['goods_price'][$j])?$vitem->price:$data['goods_price'][$j] ,
					'goods_real_name'=>$data['goods_real_name'][$j],
					'goods_name_cn'=>$data['goods_name_cn'][$j],
					'goods_name_en'=>$data['goods_name_en'][$j],
					'goods_weight'=>$data['goods_weight'][$j],
					'goods_dec_price'=>$data['goods_dec_price'][$j],
					'goods_level'=>$data['goods_level'][$j],
					'goods_name'=>'',
					'hs_code'=>'',
				];
			}


			$client = new \SOAPClient(self::$wsdl);
			$response = $client->addOrder($this->authToken, $shipmentInfo,$shipmentGoodsInfo);
			if( !empty( $response )  ){
				$result= json_decode( $response,true );
				if( isset( $result['ack'] ) && $result['ack']=='True' ) {
					$track_no= $result['track_no'];
					$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$track_no,null);

					return  BaseCarrierAPI::getResult(0,$r,'操作成功!物流跟踪号:'.$track_no);
				}else{
					return self::getResult(1,'',$result['message']);
				}
			}else{
				return self::getResult(1,'','交运失败,接口返回失败');
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
	 * @author		hqw		2017/03/09				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try {
			if(count($data) > 50){
				return self::getResult(1,'','一次最多打印50张订单！');
			}
			
			$order = current($data);reset($data);
			$getAccountInfoOrder = $order['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,1,$getAccountInfoOrder);
			$shipped = $checkResult['data']['shipped'];
			$puid = $checkResult['data']['puid'];
		
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($getAccountInfoOrder);
			$account = $info['account'];// object SysCarrierAccount
			$service = $info['service'];// object SysShippingService
		
			$account_api_params = $account->api_params;//获取到帐号中的认证参数
			$normalparams = $service->carrier_params;
		
			$this->authToken = $account_api_params['token'];
		
			$param = array();
				
			$customer_number_str = '';
			
			foreach ($data as $v) {
				$oneOrder = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
				$shipped = $checkResult['data']['shipped'];
		
				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}
				$customer_number_str .= empty($customer_number_str) ? $oneOrder->customer_number : ','.$oneOrder->customer_number;
			}

			$client = new \SOAPClient(self::$wsdl);
			$response = $client->getLabels($this->authToken, $customer_number_str);
			if( !empty( $response ) ){
				$result= json_decode($response,true);
				if( isset( $result['ack'] ) && $result['ack']=='True' ){
					return self::getResult(0,['pdfUrl'=>$result['label']],'物流单已生成,请点击页面中打印按钮');
				}else{
					return self::getResult(1,'',$result['message']);
				}
			}else{
				return self::getResult(1,'','打印失败,接口返回失败');
			}
			//print_r ($response);exit;

		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
}

?>