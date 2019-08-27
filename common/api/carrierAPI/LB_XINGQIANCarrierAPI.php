<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\order\models\OdOrderShipped;
use Jurosh\PDFMerge\PDFMerger;

// try{
// 	include dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'PDFMerger'.DIRECTORY_SEPARATOR.'PDFMerger.php';
// }catch(\Exception $e){	
// }

//error_reporting(0);	//返回解释不了xml时启用

class LB_XINGQIANCarrierAPI extends BaseCarrierAPI
{
	
	/**
	 * 1身份认证 
	 * http://121.40.73.213:8082/selectAuth.htm?username=test&password=123456
	 * 返回值：{'customer_id':'6581','customer_userid':'6901','ack':'true'}
	 * ack=true则代表认证成功，customer_id和customer_userid用于新建订单
	 * 
	 * 2.渠道列表
	 * http://121.40.73.213:8082/getProductList.htm
	 * 返回 : [{"product_id":"***","product_shortname":"***小包"}]
	 * 
	 * 3添加订单
	 * Url http://121.40.73.213:8082/createOrderApi.htm?param=
	 * Post方式提交:
	 * {"buyerid":"","consignee_address":"收件地址街道，必填","consignee_city":"城市","consignee_mobile":"","consignee_name":"收件人,必填","trade_type":"ZYXT","consignee_postcode":"邮编，有邮编的国家必填","consignee_state":"州/省","consignee_telephone":"收件电话，必填","country":"收件国家二字代码，必填","customer_id":"客户ID，必填","customer_userid":"登录人ID，必填","orderInvoiceParam":[{"invoice_amount":"申报价值，必填","invoice_pcs":"件数，必填","invoice_title":"品名，必填","invoice_weight":"单件重","item_id":"","item_transactionid":"","sku":"sku,如果是e邮宝，e特快，e包裹则传中文品名"},{"invoice_amount":"申报价值，必填","invoice_pcs":"件数，必填","invoice_title":"品名，必填","invoice_weight":"单件重","item_id":"","item_transactionid":"","sku":"中文品名","sku_code":"配货信息"}],"order_customerinvoicecode":"原单号，必填","product_id":"运输方式ID，必填","weight":"总重，选填，如果sku上有单重可不填该项","product_imagepath":"图片地址，多图片地址用分号隔开"}
	 * 返回值 : {"ack":"true","message":"如果未获取到转单号，则该列存放失败原因","reference_number":"参考号","tracking_number":"跟踪号",”order_id”:”xxxxxxx”}
	 * 
	 * 4.标记发货
	 * http://121.40.73.213:8082/postOrderApi.htm?customer_id=**&order_customerinvoicecode=**
	 * 
	 * 5.打印标签
	 * http://121.40.73.213:8088/order/FastRpt/PDF_NEW.aspx?Format=**&PrintType=*&order_id=**
	 * 
	 * 6.轨迹查询
	 * http://121.40.73.213:8088/selectTrack.htm?documentCode=**********
	 * 
	 * 
	 */
	
	static private $selectAuthUrl = '';		//用户认证接口
	static private $createOrderUrl = '';	//创建物流单接口
	static private $postOrderUrl = '';		//预报物流单接口
	static private $getProductList = '';		//获取渠道接口
	public $customer_userid = null;			//登录人ID
	public $customer_id = null;				//客户ID
	
	//打印标签参数信息
	static private $print_format_info = [
		"47"=>["format_id"=>47,"format_name"=>"A4中邮(平邮)+报关单10*10","format_path"=>"A4_2_EMS_BGD130644212460432264.frx","print_type"=>"1"],
		"41"=>["format_id"=>41,"format_name"=>"A4中邮(挂号)+报关单+配货单10*10","format_path"=>"A4_2_EMS_BGD130615492181718750.frx","print_type"=>"1"],
		"2"=>["format_id"=>2,"format_name"=>"A4中邮(挂号)+报关单10*10","format_path"=>"A4_2_EMS_BGD.frx","print_type"=>"1"],
		"1"=>["format_id"=>1,"format_name"=>"A4中邮(挂号)+报关单8*9","format_path"=>"A4_EMS_BGD.frx","print_type"=>"1"],
		"46"=>["format_id"=>46,"format_name"=>"不干胶中邮(平邮)+报关单10*10","format_path"=>"lbl_EMS_BGD_10130644210501057264.frx","print_type"=>"1"],
		"25"=>["format_id"=>25,"format_name"=>"不干胶中邮(挂号)+报关单10*10","format_path"=>"lbl_EMS_BGD_10.frx","print_type"=>"1"],
		"24"=>["format_id"=>24,"format_name"=>"不干胶中邮(挂号)+报关单8*9","format_path"=>"lbl_EMS_BGD_8_9.frx","print_type"=>"1"],
		"44"=>["format_id"=>44,"format_name"=>"不干胶中邮10*10(不含报关单)","format_path"=>"lbl_EMS_BGD_10130626929352343750.frx","print_type"=>"1"],
		"29"=>["format_id"=>29,"format_name"=>"不干胶江苏小包10*10","format_path"=>"lbl_EMS_BGD_JSXB.frx","print_type"=>"1"],
		"219"=>["format_id"=>219,"format_name"=>"A4-DHL小包(平邮)目的地为德国","format_path"=>"A4_EMS_BGD_RU_10130756269789843750738163243001.frx","print_type"=>"1"],
		"220"=>["format_id"=>220,"format_name"=>"A4-DHL小包(平邮)目的地其他国家","format_path"=>"A4_EMS_BGD_RU_10130756269789843750738163243001136626.frx","print_type"=>"1"],
		"215"=>["format_id"=>215,"format_name"=>"A4-DHL小包(挂号)目的地其他国家","format_path"=>"A4_EMS_BGD_RU_10130756269789843750738163638271.frx","print_type"=>"1"],
		"214"=>["format_id"=>214,"format_name"=>"A4-DHL小包(挂号)目的地德国","format_path"=>"A4_EMS_BGD_RU_10130756269789843750738163.frx","print_type"=>"1"],
		"203"=>["format_id"=>203,"format_name"=>"A4-中欧专线10*10A","format_path"=>"A4_2_EMS_BGD130615492181718750130751278742031250.frx","print_type"=>"1"],
		"210"=>["format_id"=>210,"format_name"=>"A4-中英专线10*10A","format_path"=>"A4_2_EMS_BGD130615492181718750130751278742031250610030.frx","print_type"=>"1"],
		"225"=>["format_id"=>225,"format_name"=>"A4-澳邮宝10*10","format_path"=>"lbl_NL_10130679411050625000130680207803281250130746875737656250943825.frx","print_type"=>"1"],
		"221"=>["format_id"=>221,"format_name"=>"A4-马来西亚小包(平邮)","format_path"=>"A4BGD638670.frx","print_type"=>"1"],
		"4"=>["format_id"=>4,"format_name"=>"A4-马来西亚小包(挂号)","format_path"=>"A4BGD.frx","print_type"=>"1"],
		"21"=>["format_id"=>21,"format_name"=>"A4瑞典小包","format_path"=>"A4_SE_10.frx","print_type"=>"2"],
		"20"=>["format_id"=>20,"format_name"=>"A4荷兰小包10*10","format_path"=>"A4_NL_10.frx","print_type"=>"2"],
		"48"=>["format_id"=>48,"format_name"=>"A4通用标签+报关单","format_path"=>"A4BGD130644216337932264.frx","print_type"=>"1"],
		"49"=>["format_id"=>49,"format_name"=>"A4通用标签+报关单+配货单10*10","format_path"=>"A4_2_EMS_BGD130615492181718750130644216793869764.frx","print_type"=>"1"],
		"8"=>["format_id"=>8,"format_name"=>"A4通用标签8*9","format_path"=>"A4.frx","print_type"=>"2"],
		"218"=>["format_id"=>218,"format_name"=>"不干胶-DHL小包(平邮)目的地为其他国家","format_path"=>"lbl_10130615455901562500130756182138906250130764798746562500544916466817.frx","print_type"=>"1"],
		"213"=>["format_id"=>213,"format_name"=>"不干胶-DHL小包(平邮)目的地为德国","format_path"=>"lbl_10130615455901562500130756182138906250130764798746562500544916.frx","print_type"=>"1"],
		"217"=>["format_id"=>217,"format_name"=>"不干胶-DHL小包(挂号)目的地为其他国家","format_path"=>"lbl_10130615455901562500130756182138906250130764798746562500880773162574.frx","print_type"=>"1"],
		"216"=>["format_id"=>216,"format_name"=>"不干胶-DHL小包(挂号)目的地为德国","format_path"=>"lbl_10130615455901562500130756182138906250130764798746562500880773.frx","print_type"=>"1"],
		"224"=>["format_id"=>224,"format_name"=>"不干胶-澳邮宝10*10","format_path"=>"lbl_NL_10450533.frx","print_type"=>"1"],
		"223"=>["format_id"=>223,"format_name"=>"不干胶-马来西亚小包(平邮)","format_path"=>"lbl_10130615455901562500130756182138906250130764798746562500880773162574686912331928.frx","print_type"=>"1"],
		"222"=>["format_id"=>222,"format_name"=>"不干胶-马来西亚小包(挂号)","format_path"=>"lbl_10130615455901562500130756182138906250130764798746562500880773162574686912.frx","print_type"=>"1"],
		"55"=>["format_id"=>55,"format_name"=>"不干胶中欧专线10*10","format_path"=>"lbl_NL_10130679411050625000130680207803281250.frx","print_type"=>"1"],
		"51"=>["format_id"=>51,"format_name"=>"不干胶中英小包(平邮)10*10","format_path"=>"lbl_ZX_10130643099586562500130660483887968750.frx","print_type"=>"1"],
		"45"=>["format_id"=>45,"format_name"=>"不干胶中英小包(挂号)10*10","format_path"=>"lbl_ZX_10130643099586562500.frx","print_type"=>"1"],
		"36"=>["format_id"=>36,"format_name"=>"不干胶瑞典小包10*10","format_path"=>"lbl_SE_10.frx","print_type"=>"1"],
		"54"=>["format_id"=>54,"format_name"=>"不干胶荷兰小包(平邮)10*10","format_path"=>"lbl_NL_10130679411050625000.frx","print_type"=>"1"],
		"34"=>["format_id"=>34,"format_name"=>"不干胶荷兰小包(挂号)10*10","format_path"=>"lbl_NL_10.frx","print_type"=>"1"],
		"40"=>["format_id"=>40,"format_name"=>"不干胶通用标签+报关单10*10","format_path"=>"lbl_10130615455901562500.frx","print_type"=>"1"],
		"7"=>["format_id"=>7,"format_name"=>"不干胶通用标签10*10","format_path"=>"lbl_10.frx","print_type"=>"1"],
		"6"=>["format_id"=>6,"format_name"=>"不干胶通用标签8*9","format_path"=>"lbl.frx","print_type"=>"1"],
		"eA4"=>["format_id"=>"e1","format_name"=>"E邮宝,E特快A4打印"],
		"e10*10"=>["format_id"=>"e2","format_name"=>"E邮宝10*10不干胶打印"],
		"yjzx"=>["format_id"=>"yjzx","format_name"=>"一级专线标签"],
	];		
	
	
	public function __construct(){
		//星前测试环境
		
		//星前正式环境
		self::$selectAuthUrl = 'http://121.40.73.213:8082/selectAuth.htm';
		
		self::$createOrderUrl = 'http://121.40.73.213:8082/createOrderApi.htm';
		
		self::$postOrderUrl = 'http://121.40.73.213:8082/postOrderApi.htm';
		
		self::$getProductList = 'http://121.40.73.213:8082/getProductList.htm';
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/13			初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
        	if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
        	$puid = $user->getParentUid();
			//odOrder表内容
			$order = $data['order'];
			//表单提交的数据
			$form_data = $data['data'];

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
				
			//获取到帐号中的认证参数
			$a = $account->api_params;
// 			$shippingfrom_enaddress = $account->address['shippingfrom_en'];//获取到账户中的地址信息
			
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';
			//账号认证
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
				return self::getResult(1, '', '星前账户验证失败,e001');
			
			if(empty($this->customer_userid) || empty($this->customer_id) )
				return self::getResult(1, '', '星前账户验证失败,e002');
			
			//判断是否是E邮宝发货
			$isEUB = false;
			//print_r($service);
			//exit();
			if(stripos($service->shipping_method_name, 'E邮宝')!==false || stripos($service->shipping_method_name, 'E特快')!==false || stripos($service->shipping_method_name, 'E包裹')!==false)
				$isEUB = true;
			
			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}
				
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
			
			if(strlen($order->consignee)>35){
				return self::getResult(1, '', '收件人姓名长度不能大于35');
			}
				
			if (empty($order->consignee_country_code)){
				return self::getResult(1, '', '国家信息不能为空');
			}
				
			if (empty($order->consignee_province) && $isEUB) {
				return self::getResult(1, '', 'E邮宝/E特快/E包裹 发货时收件人 州/省 不能为空');
			}
			if (empty($order->consignee_address_line1)){
				return self::getResult(1, '', '地址不能为空');
			}
			
			$phoneContact = '';
			if (empty($order->consignee_phone) && empty($order->consignee_mobile)){
				return self::getResult(1, '', '联系方式不能为空');
			}
			
			if(!empty($form_data['CN_Name']) && $isEUB){
				foreach ($form_data['CN_Name'] as $CN_name){
					if (!preg_match("/[\x7f-\xff]/", $CN_name)) {
						return self::getResult(1, '', 'E邮宝/E特快/E包裹 发货时SKU必须含有中文');
					}
				}
			}
				
			if (empty($order->consignee_phone) && !empty($order->consignee_mobile))
				$phoneContact = $order->consignee_mobile;
			else 
				$phoneContact = $order->consignee_phone;
			
			//重复发货 添加不同的标识码	客方参考号不要大于25个字符
// 			$extra_id = isset($data['extra_id'])?$data['extra_id']:'';
// 			$customer_number = CarrierAPIHelper::getCustomerNum($order,$extra_id);
			
			$addAddressInfo = (empty($order->consignee_company)?'':';'.$order->consignee_company).(empty($order->consignee_county) ? '' : ';'.$order->consignee_county).
				(empty($order->consignee_district) ? '' : ';'.$order->consignee_district);
			$consigneeStreet = ''.(empty($order->consignee_address_line1)?'':$order->consignee_address_line1).(empty($order->consignee_address_line2)?'':$order->consignee_address_line2).(empty($order->consignee_address_line3)?'':$order->consignee_address_line3).$addAddressInfo;
			
			/*
			$enName=substr($enName,0,-1);
			
			if (empty($enName)){
				return self::getResult(1, '', '英文报关名不能为空');
			}
			*/
			$carrierAddressAndPhoneParmas = array(
					'consignee_phone_limit' => 30,//	电话的长度限制	这个只能看接口文档或者问对应的技术，没有长度限制请填10000
					'address' => Array(
							'consignee_address_line1_limit' => 10000,
					),
					'consignee_district' => 1,//	是否将收件人区也填入地址信息里面
					'consignee_county' => 1,	//是否将收货人镇也填入地址信息里面
					'consignee_company' => 1,	//是否将收货公司也填入地址信息里面
			);
			$carrierAddressAndPhoneInfo = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $carrierAddressAndPhoneParmas);
			if(!empty($carrierAddressAndPhoneInfo['address_line1']))
				$consigneeStreet = $carrierAddressAndPhoneInfo['address_line1'];
			if(!empty($carrierAddressAndPhoneInfo['phone1'])){
				$phoneContact = $carrierAddressAndPhoneInfo['phone1'];
			}
			$mobile = $order->consignee_mobile;
			//个别渠道，整理后的电话不能超过15位，并且只能传输一个手机号
			if(strpos($service->shipping_method_name, '法国一级专线') !== false || strpos($service->shipping_method_name, '英国一级专线') !== false || strpos($service->shipping_method_name, '德国一级专线') !== false || strpos($service->shipping_method_name, '意大利一级专线') !== false || strpos($service->shipping_method_name, '西班牙一级专线') !== false){
				if(strlen($phoneContact) > 15){
					return self::getResult(1, '', '此渠道电话+手机信息不能超过15个字符！');
				}
				$mobile = '';
			}
			
			if(in_array($service->shipping_method_code,array('2901','3121'))){
				if(($order->consignee_country_code != 'FR'))
					return self::getResult(1, '', '目的地不是法国的不能使用该运输服务');
			}
			
			$postdata = [
					"buyerid" => $order->source_buyer_user_id,//买家ID
					"consignee_address" => $consigneeStreet,//收件地址街道，必填
					"consignee_city" => $order->consignee_city,
					"consignee_mobile" => $mobile,
					"consignee_name" => $order->consignee,//收件人,必填
					"trade_type" =>'ZYXT',
					"consignee_postcode" => $order->consignee_postal_code,//邮编，有邮编的国家必填
					"consignee_state" => $order->consignee_province,//州/省
					"consignee_telephone" => $phoneContact,//收件电话，必填
					"country" => $order->consignee_country_code=='UK'?'GB':$order->consignee_country_code,//收件国家二字代码，必填
					"customer_id" => $this->customer_id,//客户ID，必填
					"customer_userid" => $this->customer_userid,//登录人ID，必填
					"order_customerinvoicecode" =>$customer_number,//原单号，必填
					"product_id" =>$service->shipping_method_code,//运输方式ID，必填
					//"weight" => '',//总重，选填，如果sku上有单重可不填该项
					//"product_imagepath" => '',//图片地址，多图片地址用分号隔开
			];

			$weight_amount = 0;
			$orderInvoiceParam=[];
			
			foreach ($order->items as $k=>$item){
				$pieces = 0;
				if(isset($form_data['DeclarePieces'][$k]) && $form_data['DeclarePieces'][$k]!=='' && is_numeric($form_data['DeclarePieces'][$k]) )
					$pieces = floatval($form_data['DeclarePieces'][$k]);
				else 
					$pieces = $item['quantity'];
				
				if(empty($pieces)){
					return self::getResult(1, '', '订单出货数量不能为0');
				}
				
				if(empty($form_data['DeclaredValue'][$k])){
					return self::getResult(1, '', '申报价值必填');
				}
				
				if (!is_numeric($form_data['DeclaredValue'][$k])){
					return self::getResult(1, '', '申报价值必须是数值类型');
				}
				
				if(($service->shipping_method_code == '3121') && ((floatval($form_data['DeclaredValue'][$k]) * $pieces)>=22)){
					return self::getResult(1, '', '法国一级专线(有跟踪不带签收)-XBPY：申报价值大于22USD，禁止发货，请修改申报!');
				}
				
				if(empty($form_data['EName'][$k])){
					return self::getResult(1, '', '报关英文名必填');
				}
				
				if(empty($form_data['CN_Name'][$k])){
					return self::getResult(1, '', '报关中文名必填');
				}
				
				if (!preg_match("/[\x7f-\xff]/", $form_data['CN_Name'][$k])) {
					return self::getResult(1, '', '发货时必须含有中文');
				}
				
				if(empty($form_data['invoice_weight'][$k])){
					return self::getResult(1, '', '重量必填');
				}
				
				if (!is_numeric($form_data['invoice_weight'][$k])){
					return self::getResult(1, '', '重量必须是数值类型');
				}
				
				if(strlen($form_data['EName'][$k])>100){
					return self::getResult(1, '', '报关英文名不能大于100');
				}
				
				if(strlen($form_data['CN_Name'][$k])>200){
					return self::getResult(1, '', '报关中文名不能大于200');
				}
				
				//sku如果是e邮宝，e特快，e包裹则传中文品名
// 				if($isEUB)
					$invoice_sku = $form_data['CN_Name'][$k];
// 				else 
// 					$invoice_sku = $item['sku'];
				
				$orderInvoiceParam[$k]=[
					"invoice_amount" =>floatval($form_data['DeclaredValue'][$k]) * $pieces,//申报价值，必填
					"invoice_pcs" =>$pieces,//件数，必填
					"invoice_title" =>empty($form_data['EName'][$k])?'':$form_data['EName'][$k],//品名，必填
					"invoice_weight" =>floatval($form_data['invoice_weight'][$k]) / 1000,//单件重(传入的单位为克，接受单位为千克)
					"item_id" =>'',//
					"item_transactionid" =>'',//
					"sku"=>$invoice_sku,
					"sku_code"=>$form_data['DeclareNote'][$k],//配货信息
				];
				$weight_amount += $orderInvoiceParam[$k]['invoice_weight'] * $pieces;
			}
// 			if(isset($form_data['weight']) && $form_data['weight']!=='' && is_numeric($form_data['weight']))
// 				$postdata['weight'] = $form_data['weight'];
// 			else 
	
			$postdata['weight'] = $weight_amount;
			$postdata['orderInvoiceParam'] = $orderInvoiceParam;
			
			//因为星前物流的it请假所以当判断到该运输方式时把测试到的问题统一放在我们系统去处理
			if(($service->shipping_method_code == '3121') && ($weight_amount)>2){
				return self::getResult(1, '', '法国一级专线(有跟踪不带签收)-XBPY：的允许重量段的范围是 1g-2000g,请选择其它产品发货');
			}
			
			//数据组织完成 准备发送
			#########################################################################
			//\Yii::info(print_r($postdata,1),"file");
			
// 			print_r(($postdata));
// 			exit;
			\Yii::info('LB_XINGQIANCarrierAPI1 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($postdata,1), "file");
			
			$requestBody = ['param'=>json_encode($postdata)];
			$response = Helper_Curl::post(self::$createOrderUrl,$requestBody);
			
			if (empty($response)){
				return self::getResult(1,'','操作失败,星前返回错误');
			}
			
			\Yii::info('LB_XINGQIANCarrierAPI2 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($response,1), "file");
			
			$response = urldecode($response);
			$ret = json_decode($response,true);
			
// 			print_r($ret);
// 			exit;
			
			//var_dump($ret);
			//exit();
			//分析返回结果###############################################################
			//无异常
			//返回的order_id为物流商内部标识，需要在打印物流单时用到，暂时保存于CarrierAPIHelper::orderSuccess的return_no参数中；
			if(strtolower($ret['ack'])=='true' && !empty($ret['tracking_number'])){	//&& strtolower($ret['message'])=='success'  第一次上传成功时message并没有任何信息
				$r = CarrierAPIHelper::orderSuccess($order,$service,$ret['reference_number'],OdOrder::CARRIER_WAITING_PRINT,$ret['tracking_number'],['delivery_orderId'=>$ret['order_id']]);
				
				$print_param = array();
				$print_param['delivery_orderId'] = $ret['order_id'];
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_XINGQIANCarrierAPI';
                $print_param['userToken'] = json_encode(['username'=>$a['username'], 'password'=>$a['password']]);
                $print_param['tracking_number'] = $ret['tracking_number'];
                $print_param['carrier_params'] = $service->carrier_params;
                
                try{
                	CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $ret['reference_number'], $print_param);
                }catch (\Exception $ex){
                }
				
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$ret['reference_number'].',运单号:'.$ret['tracking_number']."<br><font color='red'>".$ret['message']."</font>");
			}
			else{
				if(empty($ret['order_id'])){
					return  BaseCarrierAPI::getResult(1,'','上传失败!客户单号为:'.$ret['reference_number']."<br><font color='red'>".$ret['message']."</font>");
				}
				
				//返回异常，再判断是否属于无法立即获得运单号的运输服务(如DHL,UPS之类)
				
				if(stripos($ret['message'],'0x100005')!==false){
					$r = CarrierAPIHelper::orderSuccess($order,$service,$ret['reference_number'],OdOrder::CARRIER_WAITING_DELIVERY,(empty($ret['tracking_number']) ? '' : $ret['tracking_number']),['delivery_orderId'=>$ret['order_id']]);
					return  BaseCarrierAPI::getResult(0,$r,'修改成功!客户单号为:'.$ret['reference_number'].(empty($ret['tracking_number']) ? '' : ',运单号:'.$ret['tracking_number']).$ret['reference_number'].
							"<br><font color='red'>".$ret['message']."</font>");
				}else if(stripos($ret['message'],'无法获取转单号')!==false  && stripos($ret['message'],'错误信息为')===false){
					//UPS
					$r = CarrierAPIHelper::orderSuccess($order,$service,$ret['reference_number'],OdOrder::CARRIER_WAITING_DELIVERY,'',['delivery_orderId'=>$ret['order_id']]);
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$ret['reference_number'].',该货代的此种运输服务无法立刻获取运单号,需要在 "物流模块->物流操作状态->待交运" 中确认交运'.
							"<br><font color='red'>".$ret['message']."</font>");
				}else if(stripos($ret['message'],'等待分配单号')!==false){
					//德国一级专线,英国一级专线
					$r = CarrierAPIHelper::orderSuccess($order,$service,$ret['reference_number'],OdOrder::CARRIER_WAITING_DELIVERY,'',['delivery_orderId'=>$ret['order_id']]);
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$ret['reference_number'].',该货代的此种运输服务无法立刻获取运单号,需要在 "物流模块->物流操作状态->待交运" 中确认交运'.
							"<br><font color='red'>".$ret['message']."</font>");
				}else{
					return  BaseCarrierAPI::getResult(1,'','上传失败！该单已经存于星前后台的“草稿单”中,客户单号为:'.$ret['reference_number'].'。<br>可再次编辑后重新上传直至返货成功提示。'.
							'<br>'."<font color='red'>".$ret['message']."</font>");
				}
				
// 				$r = CarrierAPIHelper::orderSuccess($order,$service,$ret['reference_number'],OdOrder::CARRIER_WAITING_DELIVERY,(empty($ret['tracking_number']) ? '' : $ret['tracking_number']),['delivery_orderId'=>$ret['order_id']]);
// 				return  BaseCarrierAPI::getResult(0,$r,'上传异常!客户单号为:'.$ret['reference_number'].(empty($ret['tracking_number']) ? '' : ',运单号:'.$ret['tracking_number']).
// 						"<br><font color='red'>".$ret['message']."</font>"."<br>请根据对应的错误信息来判断是否需要到星前物流系统修改对应的物流订单！");
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
	 * 交运(预报订单)
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/03			初始化
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		try{
			//订单对象
			$order = $data['order'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];

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
				return self::getResult(1, '', '星前账户验证失败,e001');
				
			if(empty($this->customer_userid) || empty($this->customer_id) )
				return self::getResult(1, '', '星前账户验证失败,e002');
			
			$order_customerinvoicecode = $order->customer_number;
			
			//一级专线渠道，需先验证是否有跟踪号，有跟踪号才可预报
			if(strpos($service->shipping_method_name, '法国一级专线') !== false || strpos($service->shipping_method_name, '英国一级专线') !== false || strpos($service->shipping_method_name, '德国一级专线') !== false || strpos($service->shipping_method_name, '意大利一级专线') !== false || strpos($service->shipping_method_name, '西班牙一级专线') !== false){
				//判断是否有跟踪号
				if(empty($shipped->tracking_number)){
					//尝试获取跟踪号
					self::getTrackingNO($data);
					
					//再次验证是否有跟踪号
					$checkResult = CarrierAPIHelper::validate(0,1,$order);
					$shipped = $checkResult['data']['shipped'];
					
					if(empty($shipped->tracking_number)){
						return self::getResult(1, '', '跟踪号为空，请先重新获取跟踪号！');
					}
				}
				
			}
			
			$postOrderUrl = self::$postOrderUrl.'?customer_id='.$this->customer_id.'&order_customerinvoicecode='.$order_customerinvoicecode;

			$response = Helper_Curl::get($postOrderUrl,[],$header);

			//如果是错误信息
			if($response == 'false'){
				return BaseCarrierAPI::getResult(1, '', '结果：交运失败');
			}else if($response == 'true'){
				/*
				$N=OdOrderShipped::findOne(['order_id'=>$order->order_id]);
				if($N<>null && $N->tracking_number!==$N->customer_number){//我们平台判断星前物流tracking_number是否有效：tracking_number!==customer_number
					$tracking_number = $N->tracking_number;
					$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
					$order->save();
					return BaseCarrierAPI::getResult(0, '', '订单交运成功！已生成运单号：'.$tracking_number);
				}else{
					$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
					$order->save();
					return BaseCarrierAPI::getResult(0, '', '订单交运成功！该货代的此种运输服务无法立刻获取运单号,您可以打印标签交付货代后,待确认到货代发货后获取正确运单号');
				}
				*/
				
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
	 **/
	public function getTrackingNO($data){
		//return BaseCarrierAPI::getResult(1, '', '物流接口不支持申请跟踪号。<br>如果上传的时候获取到了正确的跟踪号，可以直接将此订单移动到"待打印物流单"。<br>如果上传的时候获取不到正确的跟踪号，则需要您通过物流后台获取到跟踪号之后通过excle导入到小老板。');
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
			
// 			http://121.40.73.213:8082/getOrderTrackingNumber.htm?documentCode=单号
// 			$url = 'http://121.40.73.213:8082/selectTrack.htm?documentCode='.$documentCode;
	
			$url = 'http://121.40.73.213:8082/getOrderTrackingNumber.htm?documentCode='.$documentCode;
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';
	
			$response = Helper_Curl::post($url,[],$header);
			if (empty($response)){
				return self::getResult(1,'','操作失败,星前物流返回错误');
			}
			$ret = json_decode($response,true);
			
// 			print_r($ret);
// 			exit;
			
			if(!isset($ret['order_id'])){
				return self::getResult(1,'','获取跟踪号失败请检查该订单是否正确e02');
			}
			
			if(empty($ret['order_id'])){
				return self::getResult(1,'','获取跟踪号失败请检查该订单是否正确e01');
			}
			
			if($ret['order_customerinvoicecode'] != $ret['order_serveinvoicecode']){
				$shipped->tracking_number = $ret['order_serveinvoicecode'];
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				
				$print_param = array();
				$print_param['delivery_orderId'] = $ret['order_id'];
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_XINGQIANCarrierAPI';
                $print_param['userToken'] = json_encode(['username'=>$a['username'], 'password'=>$a['password']]);
                $print_param['tracking_number'] = $ret['order_serveinvoicecode'];
                $print_param['carrier_params'] = $service->carrier_params;
                
                try{
                	CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
                }catch (\Exception $ex){
                }
				
				return BaseCarrierAPI::getResult(0, '', '获取物流号成功！物流号：'.$ret['order_serveinvoicecode']);
			}else{
				return BaseCarrierAPI::getResult(1, '', '星前物流还没有返回物流号');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/07			初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){		
		try{
			$pdf = new PDFMerger();
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$returnErr = '';
			$returnMsg = '';
			
			foreach ($data as $k=>$v) {
				$order = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
			
				if(empty($shipped->return_no['delivery_orderId'])){
					$returnErr .= '订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')获取物流商对应的订单id失败;<br>';
					continue;
				}
			}
			if(!empty($returnErr))
				return self::getResult(1,'',$returnErr);
			
			foreach ($data as $k=>$v) {
				$order = $v['order'];
		
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				
				$info = CarrierAPIHelper::getAllInfo($order);
				$service = $info['service'];
				$account = $info['account'];
				$carrier_params = $service->carrier_params;
					
				$delivery_orderId = $shipped->return_no['delivery_orderId'];//物流商内部订单id
				
				$shipping_method_name = $shipped->shipping_method_name;

				//判断是否获得了正确的tracking_number
				$isGetTrackingNumber=false;
				if(!empty($shipped->tracking_number) && !empty($shipped->customer_number)){
					if($shipped->tracking_number!==$shipped->customer_number)
						$isGetTrackingNumber=true;
				}
				
				//默认 A4通用标签+报关单
				$format_id = 4;
				$format_name = 'A4通用标签+报关单';
				$format_path = 'A4BGD130644216337932264.frx';
				$print_type = '1';

				if(!empty($carrier_params['format'])){
					$format_id = (string)$carrier_params['format'];
					if(isset(self::$print_format_info[$format_id])){
						$format = self::$print_format_info[$format_id];
						$format_id = $format['format_id'];
						$format_name = $format['format_name'];
						if(isset($format['format_path'])) $format_path = $format['format_path'];
						if(isset($format['print_type'])) $print_type = $format['print_type'];
					}
				}
				
				//判断是否为E邮宝，E特快打印连接特殊处理
				if($format_id == 'e1'){
					//E邮宝，E特快A4打印，返回pdf路径
					$PDF_URL = 'http://121.40.73.213:8082/getEUBPrintPath.htm?order_id='.$delivery_orderId.'&format=A4';
				}
				elseif($format_id == 'e2'){
					//E邮宝10*10不干胶打印，返回pdf路径
					$PDF_URL = 'http://121.40.73.213:8082/getEUBPrintPath.htm?order_id='.$delivery_orderId.'&format=10*10';
				}
				elseif ($format_id == 'yjzx'){//一级专线
					$PDF_URL = 'http://121.40.73.213:8082/downloadOneWorldLabel.htm?order_id='.$delivery_orderId;
				}
				else{
					$PDF_URL = 'http://121.40.73.213:8088/order/FastRpt/PDF_NEW.aspx?Format='.$format_path.'&PrintType='.$print_type.'&order_id='.$delivery_orderId;
				}
				
				if($format_id == 'yjzx'){
					$response = Helper_Curl::get($PDF_URL,'');
					if(strlen($response)<1000){
						$order->carrier_error = '接口返回内容不是一个有效的PDF';
						$order->save();
						$returnMsg .= '订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')接口返回内容不是一个有效的PDF';
						continue;
					}
					$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$order['customer_number'], 0);
					$pdf->addPDF($pdfUrl['filePath'],'all');
					
				}
				else{
					//华磊系统的物流普通标签打印是通过url跳转获得pdf连接的
					$ch = curl_init();
					curl_setopt($ch, CURLOPT_URL, $PDF_URL);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
					curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 302 redirect
					$response = curl_exec($ch);
					$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
					
					$responseHeaders = curl_getinfo($ch);
					
					curl_close($ch);
					if ($response != $responseHeaders)
						$PDF_URL = $responseHeaders["url"];
					//var_dump($PDF_URL);
					if(!preg_match('/\.pdf$/', $PDF_URL)){
						$order->carrier_error = '没有获取到正确的PDF连接';
						$order->save();
						$returnMsg .= '订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')没有获取到正确的PDF连接';
						continue;
					}
					
					if(!empty($PDF_URL)){
						$responsePdf = Helper_Curl::get($PDF_URL);
						if(strlen($responsePdf)<1000){
							$order->carrier_error = '接口返回内容不是一个有效的PDF';
							$order->save();
							$returnMsg .= '订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')接口返回内容不是一个有效的PDF';
							continue;
						}
						
						$pdfUrl = CarrierAPIHelper::savePDF($responsePdf,$puid,$account->carrier_code.'_'.$order['customer_number'], 0);
						$pdf->addPDF($pdfUrl['filePath'],'all');
					}else{
						$order->carrier_error = '获取用于打印的PDF的连接失败';
						$order->save();
						$returnMsg .= '订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')获取用于打印的PDF的连接失败';
						continue;
					}
				}
				if(isset($pdfUrl)){
// 					$order->is_print_carrier = 1;
					$order->print_carrier_operator = $puid;
					$order->printtime = time();
					if($isGetTrackingNumber){
// 						$order->carrier_step = OdOrder::CARRIER_FINISHED;
						$order->save();
					}else{
// 						$order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
						$order->save();
					}
				}
			}//end foreach
			
			if(isset($pdfUrl)){
				$pdf->merge('file', $pdfUrl['filePath']);
				return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印。(未正确获取运单号的订单会转到"待获取运单号")');
			}else
				return self::getResult(1,['pdfUrl'=>''],'连接生成失败,原因：'.$returnMsg);	
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
//     		$pdf = new PDFMerger();
    		$puid = $SAA_obj->uid;

			$returnMsg = '';
			
			//默认 不干胶通用标签+报关单10*10
			$format_name = '不干胶通用标签+报关单10*10';
			$format_id = '40';
			$format_path = 'lbl_10130615455901562500.frx';
			$print_type = '1';
			
			$carrier_params = $print_param['carrier_params'];
			$delivery_orderId = $print_param['delivery_orderId'];
			
			if(!empty($carrier_params['format'])){
				$format_id = (string)$carrier_params['format'];
				if(isset(self::$print_format_info[$format_id])){
					$format = self::$print_format_info[$format_id];
					$format_id = $format['format_id'];
				}
			}
			//判断是否为E邮宝，E特快打印连接特殊处理
			if($format_id == 'e1'){
				//E邮宝，E特快A4打印，返回pdf路径
				$PDF_URL = 'http://121.40.73.213:8082/getEUBPrintPath.htm?order_id='.$delivery_orderId.'&format=A4';
			}
			elseif($format_id == 'e2'){
				//E邮宝10*10不干胶打印，返回pdf路径
				$PDF_URL = 'http://121.40.73.213:8082/getEUBPrintPath.htm?order_id='.$delivery_orderId.'&format=10*10';
			}
			elseif ($format_id == 'yjzx'){//一级专线
				$PDF_URL = 'http://121.40.73.213:8082/downloadOneWorldLabel.htm?order_id='.$delivery_orderId;
			}
			else{
				$PDF_URL = 'http://121.40.73.213:8088/order/FastRpt/PDF_NEW.aspx?Format='.$format_path.'&PrintType='.$print_type.'&order_id='.$delivery_orderId;
			}
			
			if($format_id == 'yjzx'){
				$response = Helper_Curl::get($PDF_URL,'');
				if(strlen($response)<1000){
					$returnMsg .= '接口返回内容不是一个有效的PDF';
				}else{
					$pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
					return $pdfPath;
				}
			}
			else{
				//华磊系统的物流普通标签打印是通过url跳转获得pdf连接的
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $PDF_URL);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // 302 redirect
				$response = curl_exec($ch);
				$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				
				$responseHeaders = curl_getinfo($ch);
				
				curl_close($ch);
				if ($response != $responseHeaders)
					$PDF_URL = $responseHeaders["url"];
				//var_dump($PDF_URL);
				if(!preg_match('/\.pdf$/', $PDF_URL)){
					$returnMsg .= '没有获取到正确的PDF连接';
					return ['error'=>1, 'msg'=>'打印失败！错误信息：'.returnMsg, 'filePath'=>''];
				}
				
				if(!empty($PDF_URL)){
					$responsePdf = Helper_Curl::get($PDF_URL);
					if(strlen($responsePdf)<1000){
						$returnMsg .= '接口返回内容不是一个有效的PDF';
					}else{
						$pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
						return $pdfPath;
					}
				}else{
					$returnMsg .= '获取用于打印的PDF的连接失败';
					return ['error'=>1, 'msg'=>'打印失败！错误信息：'.returnMsg, 'filePath'=>''];
				}
			}
    	}catch (CarrierException $e){
    		return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
    	}
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
		$result = array('is_support'=>1,'error'=>1);
	
		try
		{
			$header = array();
			$header[] = 'Content-Type:text/xml;charset=utf-8';
			//账号认证
			$login_link = self::$selectAuthUrl.'?username='.$data['username'].'&password='.$data['password'];
			$response = Helper_Curl::get($login_link, [], $header);
				
			if(!empty($response))
			{
				$response = str_replace('\'', '"', $response);
				$response = json_decode($response);
				if(isset($response->ack) && $response->ack == 'true')
				{
					$result['error'] = 0;
				}
			}
		}
		catch(CarrierException $e){}
	
		return $result;
	}
	
	//获取运输服务
	public function getCarrierShippingServiceStr($account)
	{
		$header = array();
		$header[] = 'Content-Type:text/xml;charset=utf-8';
		$response = Helper_Curl::post( self::$getProductList,$header);
		if( empty($response))
			return ['error'=>1, 'data'=>'', 'msg'=>'获取运输服务失败'];
		 
		//解决中文乱码问题
		$response = mb_check_encoding($response, 'UTF-8') ? $response : mb_convert_encoding($response, 'UTF-8', 'gbk');
		$ret = json_decode($response,true);
		 
		$str = '';
		foreach ($ret as $val)
		{
			$str .= $val['product_id'].':'.$val['product_shortname'].';';
		}
		 
		if($str == '')
			return ['error'=>1, 'data'=>'', 'msg'=>'获取运输服务失败'];
		else
			return ['error'=>0, 'data'=>$str, 'msg'=>''];
	}
}

?>