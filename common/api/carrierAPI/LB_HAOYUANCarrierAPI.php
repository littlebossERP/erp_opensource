<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use Jurosh\PDFMerge\PDFMerger;
use common\helpers\Helper_Curl;

/**
 +------------------------------------------------------------------------------
 * 4px接口业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/4pxCarrierAPI
 * @subpackage  Exception
 * @author		qfl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_HAOYUANCarrierAPI extends BaseCarrierAPI
{
	public $soapClient = null; // SoapClient实例
	public $wsdl = null; // 物流接口

	public $appToken = null;
	public $appKey = null;
	
	function __construct(){
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			$this->wsdl='http://121.40.225.128:8888/webservice/PublicService.asmx?WSDL';
		}else{
			$this->wsdl='http://121.40.225.128:12345/webservice/PublicService.asmx?WSDL';//测试接口
		}
		
		if(is_null($this->soapClient)||!is_object($this->soapClient)){
			try {
				$this->soapClient = new \SoapClient($this->wsdl,array('soap_version' => SOAP_1_2));
			}catch (Exception $e){
				return self::getResult(1,'','网络连接故障'.$e->getMessage());
			}
		}
	}
	
	// 上传订单
	public function getOrderNO($pdata){
		try{
			$order = $pdata['order'];// object OdOrder
			$data = $pdata['data'];
			
			//重复发货 添加不同的标识码
			$extra_id = isset($pdata['data']['extra_id'])?$pdata['data']['extra_id']:'';
			$customer_number = $pdata['data']['customer_number'];
				
			if(isset($pdata['data']['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
			$shipped = $checkResult['data']['shipped']; // object OdOrderShipped
			
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];// object SysCarrierAccount
			$service = $info['service'];// object SysShippingService
			
			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			
			$account_api_params = $account->api_params;//获取到帐号中的认证参数
			$shippingfromaddress = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息
			$normalparams = $service->carrier_params;
			
			
			$this->appToken = isset($account_api_params['appToken'])?$account_api_params['appToken']:'';
			$this->appKey = isset($account_api_params['appKey'])?$account_api_params['appKey']:'';
			
			$param = array();
			$param['reference_no'] = $customer_number; // string required 客户参考号
			$param['shipping_method'] = $service->shipping_method_code; // string required 运输方式
			$param['shipping_method_no'] = $customer_number; // string 服务商单号 , 这时候 传$customer_number 是想测试 返回的服务商单号是否 真的跟踪号。
			
			$param['order_pieces'] = ($data['order_pieces'] == '') ? '1' : $data['order_pieces']; // int 外包装件数,默认 1
	
			$param['mail_cargo_type'] = $data['mail_cargo_type']; // string 包裹申报种类 : 1:Gif 礼品 ; 2:CommercialSample 商品货样 ;3:Document 文件;4:Other 其他 ;默认 4
			$param['return_sign'] = $normalparams['return_sign']; // string 是否需要标识退件 (Y,N)
			$param['buyer_id'] = $order->source_buyer_user_id; // string 买家 ID
			$param['order_info'] = $data['orderNote']; // string 订单备注
			
			// object required  发件人信息
			$shipper = array(
				'shipper_name'		=> $shippingfromaddress['contact'] , // string required 发件人姓名
				'shipper_company'	=> $shippingfromaddress['company'] , // string 发件人公司名
				'shipper_countrycode'=> $shippingfromaddress['country'] , // string required 发件人国家二字码
				'shipper_province'	=> $shippingfromaddress['province'] , // string 发件人省
				'shipper_city'		=> $shippingfromaddress['city'] , // string 发件人城市
				'shipper_street'	=> $shippingfromaddress['street'] , // string required 发件人地址
				'shipper_postcode'	=> $shippingfromaddress['postcode'] , // string 发件人邮编
				'shipper_areacode'	=> $shippingfromaddress['areacode'] , // string 发件人区域代码
				'shipper_telephone'	=> $shippingfromaddress['phone'] , // string 发件人电话
				'shipper_mobile'	=> $shippingfromaddress['mobile'] , // string required 发件人手机
				'shipper_email'		=> $shippingfromaddress['email'] , // string 发件人邮箱
				'shipper_fax'		=> $shippingfromaddress['fax'] , // string 发件人传真
			);
			$param['shipper'] = $shipper; 
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 10000,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
				(empty($order->consignee_district) ? '' : ','.$order->consignee_district);
			
			// object required  收件人信息
			$consignee = array(
				'consignee_name'				=> $order->consignee , // string required 收件人姓名
				'consignee_company'				=> $order->consignee_company , // string 收件人公司名
				// string required 收件人国家代码,此处做特殊处理，英国代码转GB否则不能上传成功
				'consignee_countrycode'			=> (('UK' === $order->consignee_country_code) ? 'GB' : $order->consignee_country_code),
				'consignee_province'			=> $order->consignee_province , // string 收件人城市
				'consignee_city'				=> $order->consignee_city , // string 发件人传真
				'consignee_street'				=> $addressAndPhone['address_line1'], // string required 收件人地址
				'consignee_postcode'			=> $order->consignee_postal_code , // string 收件人邮编
				'consignee_areacode'			=> '' , // string 收件人区域代码
				'consignee_telephone'			=> $order->consignee_phone , // string 收件人电话
				'consignee_mobile'				=> $order->consignee_mobile , // string required 收件人手机
				'consignee_email'				=> $order->consignee_email , // string 收件人邮箱
				'consignee_fax'					=> $order->consignee_company , // string 收件人传真
				'consignee_certificatetype'		=> '' , // string 证件类型:ID:身份证;PP:护照
				'consignee_certificatecode'		=> '' , // string 证件号码
				'consignee_credentials_period'	=> '' , // string 证件有效期
			);
			$param['consignee'] = $consignee; 
			
			$totalWeight = 0;
			// object/array required  海关申报信息
			$invoice = array();
			foreach ($order->items as $j=>$vitem){
				$invoice[$j]=[
					'invoice_enname'	=> $data['EName'][$j] , // string required 海关申报品名
					'invoice_cnname'	=> $data['Name'][$j] , // string 中文海关申报品名
					'invoice_quantity'	=> empty($data['DeclarePieces'][$j])?$vitem->quantity:$data['DeclarePieces'][$j] , // int required 数量
					'unit_code'			=> 'PCE' , // string 单位(MTR(米), PCE(件),SET(套)),默认 PCE   @todo 是否需要添加参数设置？
					'invoice_unitcharge'=> $data['DeclaredValue'][$j] , // float required 单价
					'hs_code'			=> empty($data['Hscode'][$j])?'':$data['Hscode'][$j] , // string 海关协制编号
					'invoice_note'		=> empty($data['invoice_note'][$j])?'':$data['invoice_note'][$j] , // string 配货信息  @todo 是否需要添加为商品参数？ 目前是添加了
					'invoice_url'		=> $vitem->product_url , // string 销售地址
				];
				
				$totalWeight += $data['weight'][$j] * $invoice[$j]['invoice_quantity'];
			}
			$param['invoice'] = $invoice;
			$param['order_weight'] = round($totalWeight / 1000 , 2); // float 订单重量，单位 KG，默认为0.2
			
			
			// @todo 额外服务 是否需要添加为服务的普通参数？
			// object/array 额外服务 : code:cnname;=>10:出口退税;5Y:保险等级1;6P:保险等级2;8Y:保险等级3;G0:DDP;I0:代做发票;T1:海外退件需退回;
			$extra_service = array();
			if(isset($normalparams['extra_service'])){
				$extra_service['extra_servicecode'] = '' ; //  string required 额外服务类型代码
				$extra_service['extra_servicevalue'] = '' ; // string required 额外服务值
				$extra_service['extra_servicenote'] = '' ; // string 备注
			}
			$param['extra_service'] = $extra_service; 
			 
			$response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"createorder", 'paramsJson'=>json_encode($param)));
			
			\Yii::info(print_r($response,true),"file");// 先记下结果，记下refrence_no，这个返回应该与上面提交refrence_no一样。   
			$responseData = json_decode($response->ServiceEntranceResult , true);

			if($responseData['success'] != 1){
				return self::getResult(1, '', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage'] );
			}else{
				//上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态（浩远直接返回物流号但不一定是真的，所以跳到第2步），跟踪号(选填),returnNo(选填)
				$r = CarrierApiHelper::orderSuccess( $order , $service , $responseData['data']['refrence_no'] , OdOrder::CARRIER_WAITING_GETCODE , $responseData['data']['shipping_method_no'] );
				return  self::getResult(0,$r, "物流跟踪号：".$responseData['data']['shipping_method_no']);
			}
			
		}catch (CarrierException $e){
			//print_r ($responseData);exit;
			return self::getResult(1,'',"file:".$e->getFile()." line:".$e->getLine()." ".$e->msg());
		}
	}
	
	// 取消订单
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');
	}
	
	// 交运
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');
	}
	
	// 获取物流号跟踪号
	public function getTrackingNO($data){
		try {

			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];// object SysCarrierAccount
			$service = $info['service'];// object SysShippingService
			
			$account_api_params = $account->api_params;//获取到帐号中的认证参数
			$this->appToken = isset($account_api_params['appToken'])?$account_api_params['appToken']:'';
			$this->appKey = isset($account_api_params['appKey'])?$account_api_params['appKey']:'';
			
			$param = array('reference_no'=>$order->customer_number);
			
			$response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"gettrackingnumber", 'paramsJson'=>json_encode($param)));
			$responseData = json_decode($response->ServiceEntranceResult , true);
			
			if ($responseData['success'] != 1) {
				return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}else{
				$shipped->tracking_number = $responseData['data']['shipping_method_no'];
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				
				return self::getResult( 0 , $responseData['data'] ,  $responseData['cnmessage'] .' 跟踪号：'.$responseData['data']['shipping_method_no']);
			}
		
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
		
	}
	
	//打印物流单
	public function doPrint($data){
		try {
			$pdf = new PDFMerger();
			
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
			
			$this->appToken = isset($account_api_params['appToken'])?$account_api_params['appToken']:'';
			$this->appKey = isset($account_api_params['appKey'])?$account_api_params['appKey']:'';
			
			$param = array();
			$additional_info =  array(
					'lable_print_invoiceinfo'				=> empty($normalparams['lable_print_invoiceinfo'])?'N':$normalparams['lable_print_invoiceinfo'] , // string 标签上是否打印配货信息
					'lable_print_buyerid'					=> empty($normalparams['lable_print_buyerid'])?'N':$normalparams['lable_print_buyerid'], // string 标签上是否打印买家 ID
					'lable_print_datetime'					=> empty($normalparams['lable_print_datetime'])?'N':$normalparams['lable_print_datetime'], // string 标签上是否打印日期
					'customsdeclaration_print_actualweight'	=> empty($normalparams['customsdeclaration_print_actualweight'])?'N':$normalparams['customsdeclaration_print_actualweight'], // string 报关单上是否打印实际重量
			);
			
			// object required 配置信息
			// 这里默认填了lable_paper_type=>2,lable_content_type=>1 以防卖家没有在 运输服务里面选择。
			$configInfo = array(
					'lable_file_type'	=> '2' , // string 标签文件类型  1:image 图片 , 2: pdf 目前测试场隐藏了lable_file_type 没得选文件类型，默认填了pdf 其他物流商都是处理pdf的
					'lable_paper_type'	=> empty($normalparams['lable_paper_type'])?'2':$normalparams['lable_paper_type'] , // string 纸张类型  1:label 标签纸 , 2:a4 A4纸
					'lable_content_type'=> empty($normalparams['lable_content_type'])?'1':$normalparams['lable_content_type'] , // string 标签内容类型代码  打印选项: 1:标签,2:报关单,3:配货单,4:标签+报关单,5:标签+配货单,6:标签+报关单+配货单
					'additional_info'	=> $additional_info , // object 附加配置信息
			);
			
			$param['configInfo'] = $configInfo;
			
			foreach ($data as $v) {
				$listorder = array();// object/array required 订单信息
				$oneOrder = $v['order'];
				
				if(empty($oneOrder->customer_number)){
					return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少客户参考号,请检查订单是否上传' );
				}
				$listorder[]['reference_no'] = $oneOrder->customer_number;// 注意这里文档写的参数是 refrence_no 要改成reference_no 接口才调用成功
// 				$listorder[]['reference_no'] = '68216610157812';
// 				$listorder[]['reference_no'] = '68215411321284';
// 				$listorder[]['reference_no'] = '68220849553138';
// 				$listorder[]['reference_no'] = '68215539530355';

				$param['listorder'] = $listorder;
				
// 				print_r($param);
				$response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"getlabel", 'paramsJson'=>json_encode($param)));
				$responseData = json_decode($response->ServiceEntranceResult , true);
// 				print_r($responseData);
				
				if ($responseData['success'] != 1) {
					return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
				}else{
					foreach ($responseData['data'] as $oneResult){
						$responsePdf = Helper_Curl::get($oneResult['lable_file']);
							
						if(strlen($responsePdf)<1000){
							$oneOrder->carrier_error = $response;
							$oneOrder->save();
							return self::getResult(1, '', $response);
						}
							
						$pdfUrl = CarrierAPIHelper::savePDF($responsePdf,$puid,$account->carrier_code.'_'.$oneOrder->customer_number.'_'. 'PDF', 0 , 'pdf');
						$pdf->addPDF($pdfUrl['filePath'],'all');
					}
				}
			}	
			
			//合并多个PDF  这里还需要进一步测试
			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/*
	 * 用来确定打印完成后 订单的下一步状态
	*/
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}
	
	################################ 浩远其他获取信息接口  #############################################	
	
	// 获取运输方式列表
	public function getshippingmethod(){
		try {
			
// 			$account_api_params = $account->api_params;//获取到帐号中的认证参数
// 			$this->appToken = isset($account_api_params['appToken'])?$account_api_params['appToken']:'';
// 			$this->appKey = isset($account_api_params['appKey'])?$account_api_params['appKey']:'';
			$response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"getshippingmethod", 'paramsJson'=>json_encode('')));
			$responseData = json_decode($response->ServiceEntranceResult , true);
			$channelStr = '';
			
			foreach ($responseData['data'] as $val){
				$channelStr .= $val['code'].':'.$val['cnname'].';';
			}
			
			echo $channelStr;
			
			exit;
			
			
			if ($responseData['success'] != 1) {
				return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}else{
				return self::getResult( 0 , $responseData['data'] , 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
		
	}
	
	// 获取包裹申报种类列表
	public function getmailcargotype(){
		try {
			$response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"getmailcargotype", 'paramsJson'=>json_encode('')));
			$responseData = json_decode($response->ServiceEntranceResult , true);
		
			if ($responseData['success'] != 1) {
				return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}else{
				return self::getResult( 0 , $responseData['data'] , 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	
	}
	
	// 获取国家列表
	public function getcountry(){
		try {
			$response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"getcountry", 'paramsJson'=>json_encode('')));
			$responseData = json_decode($response->ServiceEntranceResult , true);
		
			if ($responseData['success'] != 1) {
				return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}else{
				return self::getResult( 0 , $responseData['data'] , 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	// 获取证件类型列表
	public function getcertificatetype(){
		try {
			$response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"getcertificatetype", 'paramsJson'=>json_encode('')));
			$responseData = json_decode($response->ServiceEntranceResult , true);
		
			if ($responseData['success'] != 1) {
				return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}else{
				return self::getResult( 0 , $responseData['data'] , 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	// 获取申报单位列表
	public function getdeclareunit(){
		try {
			$response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"getdeclareunit", 'paramsJson'=>json_encode('')));
			$responseData = json_decode($response->ServiceEntranceResult , true);
		
			if ($responseData['success'] != 1) {
				return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}else{
				return self::getResult( 0 , $responseData['data'] , 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	// 获取额外服务列表
	public function getextraservice(){
		try {
			$response = $this->soapClient->ServiceEntrance(array('appToken'=>$this->appToken , 'appKey'=>$this->appKey , 'serviceMethod'=>"getextraservice", 'paramsJson'=>json_encode('')));
			$responseData = json_decode($response->ServiceEntranceResult , true);
		
			if ($responseData['success'] != 1) {
				return self::getResult(1,'', 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}else{
				return self::getResult( 0 , $responseData['data'] , 'cnmessage:' . $responseData['cnmessage'] .' '. 'enmessage:'.$responseData['enmessage']);
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}	
	}
	
	
}



?>