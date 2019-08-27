<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use common\helpers\SubmitGate;
use common\helpers\Helper_Curl;
use yii\base\Exception;

class LB_ETONGSHOUCarrierAPI extends BaseCarrierAPI{
	public static $wsdl = null;		//订单操作
	public static $wsdltools = null;	//订单工具
	public $authToken=null;
	
	public function __construct(){
		self::$wsdl = 'http://api.ets-express.com/OrderOnline/ws/OrderOnlineServiceExt.dll?wsdl';
		self::$wsdltools = 'http://api.ets-express.com/OrderOnlineTool/ws/OrderOnlineToolService.dll?wsdl';
		
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2017/04/07				初始化
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
			$this->authToken = $a['authToken'];
			
			//获取到账户中的地址信息
			$account_address = $info['senderAddressInfo'];
			
			//组织地址信息
			$carrierAddressAndPhoneParmas = array(
					'consignee_phone_limit' => 30,//	电话的长度限制	这个只能看接口文档或者问对应的技术，没有长度限制请填10000
					'address' => Array(
							'consignee_address_line1_limit' => 180,
					),
					'consignee_district' => 1,//	是否将收件人区也填入地址信息里面
					'consignee_county' => 1,	//是否将收货人镇也填入地址信息里面
					'consignee_company' => 0,	//是否将收货公司也填入地址信息里面
			);
			$carrierAddressAndPhoneInfo = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $carrierAddressAndPhoneParmas);
			
			//组织数据
			$postdata = array();
			$postdata['orderNo'] = $customer_number;										//客户订单号码，由客户自己定义
			$postdata['productCode'] = $service->shipping_method_code;						//产品代码，指陆运挂号、平邮等
			$postdata['cargoCode'] = 'P';													//货物类型
			$postdata['paymentCode'] = $service_params['paymentCode'];						//付款类型
			$postdata['initialCountryCode'] = $account_address['shippingfrom']['country'];	//起运国家二字代码
			$postdata['destinationCountryCode'] = $order->consignee_country_code=='UK'?'GB':$order->consignee_country_code;	//目的国家二字代码
			
			//declareInvoice
			$product=array();
			$pieces = 0;
			foreach ($data['DeclaredValue'] as $k=>$v){				
				$product[$k]=[
					'eName' => $data['EName'][$k],	//海关申报英文品名
					'name' =>  $data['CName'][$k],	//海关申报中文品名
					'declareUnitCode' => 'PCE',		//申报单位类型代码(默认: PCE)，参照申报单位类型代码表
					'declarePieces' => $data['DeclarePieces'][$k],	//件数(默认: 1)
					'unitPrice' => $data['DeclaredValue'][$k],		//单价
					'declareNote' => $data['DeclareNote'][$k],		//配货备注
				];
				
				$pieces += $data['DeclarePieces'][$k];
			}
			
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			$postdata['pieces'] = $pieces;			//货物件数
			$postdata['buyerId'] = $order->source_buyer_user_id;	//买家ID
			$postdata['returnSign'] = $service_params['ReturnSign'];	//小包退件标识
			$postdata['customerWeight'] = round($data['total_weight_sum']/1000,2);	//客户自己称的重量
			$postdata['shipperCompanyName'] = $account_address['shippingfrom']['company_en'];		//发件人公司名称
			$postdata['shipperName'] = $account_address['shippingfrom']['contact_en'];				//发件人姓名
			$postdata['shipperStateOrProvince'] = $account_address['shippingfrom']['province_en'];	//发件人省
			$postdata['shipperCity'] = $account_address['shippingfrom']['city_en'];					//发件人城市
			$postdata['shipperAddress'] = $account_address['shippingfrom']['street_en'];			//发件人地址
			$postdata['shipperTelephone'] = $account_address['shippingfrom']['phone'];				//发件人电话号码
			$postdata['shipperFax'] = $account_address['shippingfrom']['fax'];						//发件人传真号码
			$postdata['shipperPostCode'] = $account_address['shippingfrom']['postcode'];			//发件人邮编
			
			$postdata['consigneeCompanyName'] = $order->consignee_company;							//收件人公司名称
			$postdata['consigneeName'] = $order->consignee;											//收件人姓名
			$postdata['street'] = $carrierAddressAndPhoneInfo['address_line1'];						//街道
			$postdata['city'] = $order->consignee_city;												//城市
			$postdata['stateOrProvince'] = $tmpConsigneeProvince;								//州/省
			$postdata['consigneeTelephone'] = $carrierAddressAndPhoneInfo['phone1'];				//收件人电话号码
			$postdata['consigneeFax'] = $order->consignee_fax;										//收件人传真号码
			$postdata['consigneePostCode'] = $order->consignee_postal_code;							//收件人邮编
			$postdata['consigneeEmail'] = (strlen($order->consignee_email)<=50) ? $order->consignee_email : '';	//收件人Email
			
			$postdata['mctCode'] = $service_params['mctCode'];	//货物类型
			$postdata['note'] = $data['orderNote'];				//订单备注信息
			
			$postdata['declareInvoice'] = $product;
			
			$request = array(
					'arg0' => $this->authToken,
					'arg1' => $postdata,
			);
			
			\Yii::info('lb_etongshou,request,puid:'.$puid.',order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			
			//数据组织完成 准备发送
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'createAndPreAlertOrderService');
		
			\Yii::info('lb_etongshou,response,puid:'.$puid.',order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
	
			if(isset($response['data'])){
				if($response['data']->return->ack == 'Success'){
					//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
					$r = CarrierAPIHelper::orderSuccess($order, $service, $customer_number, OdOrder::CARRIER_WAITING_PRINT, $response['data']->return->trackingNumber,['channelNumber'=>$response['data']->return->channelNumber,'mctCode'=>$service_params['mctCode']]);
					return  BaseCarrierAPI::getResult(0, $r, "操作成功!客户单号为：".$response['data']->return->referenceNumber.' 跟踪号:'.$response['data']->return->trackingNumber);
				}else if($response['data']->return->ack == 'Failure'){
					//组织错误信息
					$error = '错误代码：'.$response['data']->return->errors->code."<br>".'错误原因：'.$response['data']->return->errors->cnMessage."<br>";
					if (isset($response['data']->return->errors->cnAction)){
						$error.= '解决方案：'.$response['data']->return->errors->cnAction;
					}
					
					return  BaseCarrierAPI::getResult(1,'', $error);
				}else{
					return BaseCarrierAPI::getResult(1,'', (isset($response['msg'])) ? $response['msg'] : '未知错误,请联系小老板客服');
				}
			}else{
				return BaseCarrierAPI::getResult(1,'', (isset($response['msg'])) ? $response['msg'] : '未知错误,请联系小老板客服');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
	 * @author		lgw		2017/04/07				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持打印物流单');
	}
	
}

?>