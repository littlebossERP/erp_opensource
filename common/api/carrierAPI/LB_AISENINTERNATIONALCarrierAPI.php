<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\order\models\OdOrderShipped;
use Jurosh\PDFMerge\PDFMerger;


class LB_AISENINTERNATIONALCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl="112.124.48.96";  //物流服务器
	static private $selectAuthUrl="";  //身份认证接口
	static private $orderUrl="";  //订单接口
	static private $wsdlpf="";  //打印接口
	static private $postOrderUrl="";  //标记发货接口
	static private $getTrackingNumber=""; //申请跟踪号接口
	
	public $customer_userid = null;
	public $customer_id = null;
	
	public function __construct(){
		self::$selectAuthUrl = 'http://'.self::$wsdl.':8082/selectAuth.htm';
		self::$orderUrl = 'http://'.self::$wsdl.':8082/createOrderApi.htm';
		self::$wsdlpf = 'http://'.self::$wsdl.'/order/FastRpt/PDF_NEW.aspx';
		self::$postOrderUrl = 'http://'.self::$wsdl.':8082/postOrderApi.htm';
		self::$getTrackingNumber="http://".self::$wsdl.":8082/getOrderTrackingNumber.htm";
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/26				初始化
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
			
			//身份认证
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';
			$selectAuthUrl = self::$selectAuthUrl.'?username='.$a['username'].'&password='.$a['password'];
			$auth = Helper_Curl::get($selectAuthUrl,[],$header);
			$auth = str_replace('\'', '"', $auth);
			if(!empty($auth)){
				$auth = json_decode($auth);
				if(isset($auth->ack) && $auth->ack=='true'){
					$this->customer_userid = $auth->customer_userid;
					$this->customer_id = $auth->customer_id;
				}
			}else 
				return self::getResult(1, '', '账户验证失败,e001');
			
			if(empty($this->customer_userid) || empty($this->customer_id) )
				return self::getResult(1, '', '账户验证失败,e002');
			
			//判断是否是E邮宝发货
// 			$isEUB = false;
// 			if(stripos($service->shipping_method_name, 'E邮宝')!==false || stripos($service->shipping_method_name, 'E特快')!==false || stripos($service->shipping_method_name, 'E包裹')!==false)
// 				$isEUB = true;
			
// 			if(!empty($e['Name']) && $isEUB){
// 				foreach ($e['Name'] as $CN_name){
// 					if (!preg_match("/[\x7f-\xff]/", $CN_name)) {
// 						return self::getResult(1, '', 'E邮宝/E特快/E包裹 发货时SKU必须含有中文');
// 					}
// 				}
// 			}
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 10000,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 30
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			if(!empty($addressAndPhone['address_line1']))
				$consigneeStreet = $addressAndPhone['address_line1'];
			if(!empty($addressAndPhone['phone1']))
				$phoneContact = $addressAndPhone['phone1'];
	
			//法国没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(($order->consignee_country_code == 'FR') && empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
		
			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}
			
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
			
			if (empty($order->consignee_country_code)){
				return self::getResult(1, '', '国家信息不能为空');
			}
			
			if (empty($order->consignee_address_line1)){
				return self::getResult(1, '', '地址不能为空');
			}
				
			if (empty($order->consignee_phone) && empty($order->consignee_mobile)){
				return self::getResult(1, '', '联系方式不能为空');
			}
			
			foreach($order->items as $k=>$v){
				if(empty($e['DeclaredValue'][$k])){
					return self::getResult(1, '', '申报价值必填');
				}
				
				if(empty($e['DeclarePieces'][$k])){
					return self::getResult(1, '', '件数必填');
				}
				
				if(empty($e['Name'][$k])){
					return self::getResult(1, '', '中文品名必填');
				}
				
				if(empty($e['weight'][$k])){
					return self::getResult(1, '', '单件重必填');
				}
				
				if(empty($e['EName'][$k])){
					return self::getResult(1, '', '英文品名必填');
				}				
				
				//sku如果是e邮宝，e特快，e包裹则传中文品名
// 				if($isEUB)
// 					$invoice_sku = $e['Name'][$k];
// 				else
// 					$invoice_sku = $e['Name'][$k];
				
				$orderInvoiceParam[] = [
					"invoice_amount"=>floatval($e['DeclaredValue'][$k])*floatval($e['DeclarePieces'][$k]),     //申报价值
					"invoice_pcs"=>$e['DeclarePieces'][$k], //件数
					"invoice_title"=>$e['EName'][$k],    //品名
					"invoice_weight"=>floatval($e['weight'][$k])/1000, //单件重
					"item_id"=>"",
					"item_transactionid"=>"",
					"sku"=>$e['Name'][$k],     //sku,如果是e邮宝，e特快，e包裹则传中文品名
					"sku_code"=>$e['sku_code'][$k],    //配货信息
				];
			}
			
			$request=array();
			$request=[
				"buyerid"=>$order->source_buyer_user_id,
				"consignee_address"=>$addressAndPhone['address_line1'],   //收件地址街道
				"consignee_city"=>$order->consignee_city, //城市
				"consignee_mobile"=>$order->consignee_mobile,   
				"consignee_name"=>$order->consignee, //收件人
				"trade_type"=>"ZYXT",
				"consignee_postcode"=>$order->consignee_postal_code, //邮编
				"consignee_state"=>$tmpConsigneeProvince,     //州/省
				"consignee_telephone"=>$order->consignee_phone,  //收件电话
				"country"=>$order->consignee_country_code, //收件国家二字代码
				"customer_id"=>$this->customer_id,  //客户ID
				"customer_userid"=>$this->customer_userid,   //登录人ID
				"orderInvoiceParam"=>$orderInvoiceParam,
				"order_customerinvoicecode"=>$customer_number,
				"product_id"=>$service->shipping_method_code, //运输方式ID
// 				"weight"=>floatval($e['sweight'])/1000,    //总重
// 				"product_imagepath"=>"",   //图片地址，多图片地址用分号隔开
			];
			
			//数据组织完成 准备发送
			#########################################################################
			$requestBody = ['param'=>json_encode($request)];
			$response = Helper_Curl::post(self::$orderUrl,$requestBody);

			if (empty($response)){return self::getResult(1,'','操作失败,返回错误');}
			
			$response = urldecode($response);
			$response=json_decode($response);
// 			print_r($response);die;

			if(strtolower($response->ack)=='true' && !empty($response->tracking_number)){
				if(stripos($response->message,'目的国家不通邮')!==false){
					return self::getResult(1,'',$response->message);
				}
				
				if(stripos($response->message,'无法获取转单号')!==false && stripos($response->message,'错误信息为')===false){
					$r = CarrierAPIHelper::orderSuccess($order,$service,$response->reference_number,OdOrder::CARRIER_WAITING_DELIVERY,null,['delivery_orderId'=>$response->order_id]);
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$response->reference_number.',该货代的此种运输服务无法立刻获取运单号,需要在 "物流模块->物流操作状态->待交运" 中确认交运');
				}
				
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
				$r = CarrierAPIHelper::orderSuccess($order,$service,$response->reference_number,OdOrder::CARRIER_WAITING_PRINT,$response->tracking_number,['delivery_orderId'=>$response->order_id]);	
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$response->reference_number.',运单号:'.$response->tracking_number);
			}
			else{
				return  BaseCarrierAPI::getResult(1,'',$response->message);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消已确定的物流单。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/26				初始化
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
// 		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运。');
		try{
			//订单对象
			$order = $data['order'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];

			//获取到帐号中的认证参数
			$a = $account->api_params;
	
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';
			//账号认证,获取账号信息
			$selectAuthUrl = self::$selectAuthUrl.'?username='.$a['username'].'&password='.$a['password'];
			$auth = Helper_Curl::get($selectAuthUrl,[],$header);
			$auth = str_replace('\'', '"', $auth);
			if(!empty($auth)){
				$auth = json_decode($auth);
				if(isset($auth->ack) && $auth->ack=='true'){
					$this->customer_userid = $auth->customer_userid;
					$this->customer_id = $auth->customer_id;
				}
			}else
				return self::getResult(1, '', '账户验证失败,e001');
				
			if(empty($this->customer_userid) || empty($this->customer_id) )
				return self::getResult(1, '', '账户验证失败,e002');
			
			$order_customerinvoicecode = $order->customer_number;
			$postOrderUrl = self::$postOrderUrl.'?customer_id='.$this->customer_id.'&order_customerinvoicecode='.$order_customerinvoicecode;

			$response = Helper_Curl::get($postOrderUrl,[],$header);
			
			//如果是错误信息
			if($response == 'false'){
				return BaseCarrierAPI::getResult(1, '', '结果：交运失败');
			}else if($response == 'true'){
// 				$N=OdOrderShipped::findOne(['order_id'=>$order->order_id]);
// 				$tracking_number = $N->tracking_number;
// 				$shipped->tracking_number = $tracking_number;
// 				$shipped->save();
// 				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
// 				$order->save();
// 				return BaseCarrierAPI::getResult(0, '', '订单交运成功！已生成运单号：'.$tracking_number);
				
				$order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
				$order->save();
				return BaseCarrierAPI::getResult(0, '', '订单交运成功!'.((($shipped->tracking_number!==$shipped->customer_number) && !empty($shipped->tracking_number)) ? '已生成运单号：'.$shipped->tracking_number : ''));
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/26				初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
// 		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = $data['order'];
	
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];// object SysCarrierAccount
			$service = $info['service'];// object SysShippingService
			$a = $account->api_params;
			
			$documentCode = $order->customer_number;
			if(empty($documentCode))
				return BaseCarrierAPI::getResult(1, '', '获取物流客户产考号失败');
			
			$url = self::$getTrackingNumber.'?documentCode='.$documentCode;
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';
	
			$response = Helper_Curl::post($url,[],$header);
			if (empty($response)){
				return self::getResult(1,'','操作失败,物流返回错误');
			}
			$ret = json_decode($response,true);
			if(!isset($ret['order_id'])){
				return self::getResult(1,'','获取跟踪号失败请检查该订单是否正确e02');
			}
			
			if(empty($ret['order_id'])){
				return self::getResult(1,'','获取跟踪号失败请检查该订单是否正确e01');
			}
// 			print_r($ret);die;
			if($ret['order_customerinvoicecode'] != $ret['order_serveinvoicecode']){
				$shipped->tracking_number = $ret['order_serveinvoicecode'];
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				
				return BaseCarrierAPI::getResult(0, '', '获取物流号成功！物流号：'.$ret['order_serveinvoicecode']);
			}else{
				return BaseCarrierAPI::getResult(1, '', '还没有返回物流号');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/26				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$order = current($data);reset($data);
			$order = $order['order'];
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$returnMsg="";
				
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			$delivery_orderId="";
			foreach ($data as $k=>$v) {
				$order = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				
				$delivery_orderId.= $shipped->return_no['delivery_orderId'].",";//物流商内部订单id
			}	
			$delivery_orderId=substr($delivery_orderId,0,-1);
			
			//获取打印格式
			$format="302";
			$format_path = 'A4BGD130644216337932264.frx';
			$print_type = '1';
			if(!empty($carrier_params['format'])){
				$format=$carrier_params['format'];
			}

			if($format == 'e1'){
				$PDF_URL="http://".self::$wsdl.":8082/getEUBPrintPath.htm?order_id=".$delivery_orderId."&format=A4";
			}
			else if($format == 'e2'){
				$PDF_URL="http://".self::$wsdl.":8082/getEUBPrintPath.htm?order_id=".$delivery_orderId."&format=10*10";
			}
			else{
				$formatstr=$this->selectLabelType($format);
				
				$formatarr=explode(':', substr($formatstr,0,-1));
				if(!empty($formatarr[0])){
					$format_path=$formatarr[0];
				}
				if(!empty($formatarr[1])){
					$print_type=$formatarr[1];
				}
				
				$PDF_URL=self::$wsdlpf.'?Format='.$format_path.'&PrintType='.$print_type.'&order_id='.$delivery_orderId;
			}

			//物流系统的物流普通标签打印是通过url跳转获得pdf连接的
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $PDF_URL);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 302 redirect

			$response = curl_exec($ch);
// 			print_r($response);die;
			$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);	
			$responseHeaders = curl_getinfo($ch);
			curl_close($ch);
			
// 			if(strlen($response)<1000){
// 				if(stripos($response,'没有找到数据')!==false)
// 					return self::getResult(1,'','订单不存在');
// 				if(stripos($response,'未将对象引用设置到对象的实例')!==false)
// 					return self::getResult(1,'','获取用于打印的PDF的连接失败');
// 				if(stripos($response,'未能找到文件')!==false || stripos($response,'未能找到路径')!==false)
// 					return self::getResult(1,'','获取用于打印的PDF的格式失败');
// 				else{
// 					return self::getResult(1,'',$response);
// 				}
// 			}
			
			if ($response != $responseHeaders)
				$PDF_URL = $responseHeaders["url"];
			
			if(!preg_match('/\.pdf$/', $PDF_URL)){
				$returnMsg .= '订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')没有获取到正确的PDF连接';
				return self::getResult(1,'','连接生成失败,原因：'.$returnMsg);
			}

			if(!empty($PDF_URL)){
				return self::getResult(0,['pdfUrl'=>$PDF_URL],'连接已生成,请点击并打印');
			}
			else{
				$returnMsg .= '订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')获取用于打印的PDF的连接失败';
				return self::getResult(1,'','连接生成失败,原因：'.$returnMsg);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			$response = Helper_Curl::get('http://'.self::$wsdl.':8082/getProductList.htm');
			$response=mb_convert_encoding($response, "UTF-8", "GBK");	
			$channelArr=json_decode($response);

			if(empty($channelArr) || !is_array($channelArr) || json_last_error()!=false){
				return self::getResult(1,'','获取运输方式失败');
			}

			$channelStr="";
			foreach ($channelArr as $countryVal){
				$channelStr.=$countryVal->product_id.":".$countryVal->product_shortname.";";
			}

			if(empty($channelStr)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $channelStr, '');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//获取打印方式
	public function selectLabelType($id){
		try{
			$header=array();
			$header[]='Content-Type: application/json;charset=UTF-8';
			$response = Helper_Curl::get('http://'.self::$wsdl.':8082/selectLabelType.htm',null,$header);
			$response=mb_convert_encoding($response, "UTF-8", "GBK");
			$channelArr=json_decode($response);

			$channelStr="";
			foreach ($channelArr as $countryVal){
				if($id==$countryVal->format_id){
					$channelStr=$countryVal->format_path.":".$countryVal->print_type.";";
					break;
				}
// 				$channelStr.=$countryVal->format_id.":".$countryVal->format_name.";";
			}
			return $channelStr;
// 			return self::getResult(0, $channelStr, '');
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
		$result = array('is_support'=>1,'error'=>1);
	
		try{
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';
			$selectAuthUrl = self::$selectAuthUrl.'?username='.$data['username'].'&password='.$data['password'];
			$auth = Helper_Curl::get($selectAuthUrl,[],$header);
			$auth = str_replace('\'', '"', $auth);
			if(!empty($auth)){
				$auth = json_decode($auth);
				if(isset($auth->ack) && $auth->ack=='true')
					$result['error'] = 0;
			}

		}catch(CarrierException $e){
		}
	
		return $result;
	}
	
}
?>