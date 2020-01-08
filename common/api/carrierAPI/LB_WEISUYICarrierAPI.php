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
use eagle\modules\order\models\OdOrderShipped;
use Jurosh\PDFMerge\PDFMerger;

// try{
// 	include dirname(dirname(dirname(__DIR__))).DIRECTORY_SEPARATOR.'eagle'.DIRECTORY_SEPARATOR.'components'.DIRECTORY_SEPARATOR.'PDFMerger'.DIRECTORY_SEPARATOR.'PDFMerger.php';
// }catch(\Exception $e){	
// }

//error_reporting(0);	//返回解释不了xml时启用

class LB_WEISUYICarrierAPI extends BaseCarrierAPI
{
	
	/**
	 * 1身份认证 
	 * http://120.27.39.100:8082/selectAuth.htm?username=test&password=123456
	 * 返回值：{'customer_id':'6581','customer_userid':'6901','ack':'true'}
	 * ack=true则代表认证成功，customer_id和customer_userid用于新建订单
	 * 
	 * 2.渠道列表
	 * http://120.27.39.100:8082/getProductList.htm
	 * 返回 : [{"product_id":"***","product_shortname":"***小包"}]
	 * 
	 * 3添加订单
	 * Url http://120.27.39.100:8082/createOrderApi.htm?param=
	 * Post方式提交:
	 * {"buyerid":"","consignee_address":"收件地址街道，必填","consignee_city":"城市","consignee_mobile":"","consignee_name":"收件人,必填","trade_type":"ZYXT","consignee_postcode":"邮编，有邮编的国家必填","consignee_state":"州/省","consignee_telephone":"收件电话，必填","country":"收件国家二字代码，必填","customer_id":"客户ID，必填","customer_userid":"登录人ID，必填","orderInvoiceParam":[{"invoice_amount":"申报价值，必填","invoice_pcs":"件数，必填","invoice_title":"品名，必填","invoice_weight":"单件重","item_id":"","item_transactionid":"","sku":"sku,如果是e邮宝，e特快，e包裹则传中文品名"},{"invoice_amount":"申报价值，必填","invoice_pcs":"件数，必填","invoice_title":"品名，必填","invoice_weight":"单件重","item_id":"","item_transactionid":"","sku":"中文品名","sku_code":"配货信息"}],"order_customerinvoicecode":"原单号，必填","product_id":"运输方式ID，必填","weight":"总重，选填，如果sku上有单重可不填该项","product_imagepath":"图片地址，多图片地址用分号隔开"}
	 * 返回值 : {"ack":"true","message":"如果未获取到转单号，则该列存放失败原因","reference_number":"参考号","tracking_number":"跟踪号",”order_id”:”xxxxxxx”}
	 * 
	 * 4.标记发货
	 * http://120.27.39.100:8082/postOrderApi.htm?customer_id=**&order_customerinvoicecode=**
	 * 
	 * 5.打印标签
	 * http://120.27.39.100/order/FastRpt/PDF_NEW.aspx?Format=**&PrintType=*&order_id=**
	 * 
	 * 6.轨迹查询
	 * http://120.27.39.100:8082/selectTrack.htm?documentCode=**********
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
		'204'=>["format_id"=>"204","format_name"=>"A4广州平邮小包10*10","format_path"=>"A4_EMS_BGD_NEW_10130741033647565533130741456853913652.frx","print_type"=>"2"],
		'207'=>["format_id"=>"227","format_name"=>"A4广州邮政小包10*10","format_path"=>"A4_EMS_BGD_NEW_10.frx","print_type"=>"2"],
		'19'=>["format_id"=>"19","format_name"=>"A4广州挂号小包10*10","format_path"=>"A4_EMS_BGD_NEW_10.frx","print_type"=>"2"],
		'22'=>["format_id"=>"22","format_name"=>"A4马来西亚平邮","format_path"=>"A4BGD.frx","print_type"=>"1"],
		'2'=>["format_id"=>"2","format_name"=>"A4中邮+报关单10*10","format_path"=>"A4_2_EMS_BGD.frx","print_type"=>"1"],
		'1'=>["format_id"=>"1","format_name"=>"A4中邮+报关单8*9","format_path"=>"A4_EMS_BGD.frx","print_type"=>"1"],
		'43'=>["format_id"=>"43","format_name"=>"A4中邮标签8*9","format_path"=>"A4130618781512626009.frx","print_type"=>"2"],
		'48'=>["format_id"=>"48","format_name"=>"A4江苏小包10*10","format_path"=>"A4_EMS_BGD_JSXB.frx","print_type"=>"2"],
		'248'=>["format_id"=>"248","format_name"=>"A4江苏小包平邮10*10","format_path"=>"A4_EMS_BGD_JSXB387453.frx","print_type"=>"2"],
		'47'=>["format_id"=>"47","format_name"=>"A4佛山小包10*10","format_path"=>"A4_EMS_BGD_BJ_10130622391729659877.frx","print_type"=>"1"],
		'203'=>["format_id"=>"203","format_name"=>"A4佛山平邮小包10*10","format_path"=>"A4_EMS_BGD_NEW_10130741033647565533.frx","print_type"=>"2"],
		'5'=>["format_id"=>"5","format_name"=>"A4俄罗斯小包10*10","format_path"=>"A4_EMS_BGD_RU_10.frx","print_type"=>"1"],
		'4'=>["format_id"=>"4","format_name"=>"A4俄罗斯小包8*9","format_path"=>"A4_EMS_BGD_RU.frx","print_type"=>"1"],
		'13'=>["format_id"=>"13","format_name"=>"A4深圳小包10*10","format_path"=>"A4_EMS_BGD_SZ_10.frx","print_type"=>"1"],
		'231'=>["format_id"=>"231","format_name"=>"A4通用标签+报关单","format_path"=>"A4BGD.frx","print_type"=>"1"],
		'23'=>["format_id"=>"23","format_name"=>"A4通用标签+配货单","format_path"=>"A4INV.frx","print_type"=>"1"],
		'3'=>["format_id"=>"3","format_name"=>"A4通用标签8*9","format_path"=>"A4.frx","print_type"=>"2"],
		'211'=>["format_id"=>"211","format_name"=>"A4通用标签8*9+报关单","format_path"=>"A4BGD971093.frx","print_type"=>"1"],
		'205'=>["format_id"=>"205","format_name"=>"不干胶广州平邮小包10*10","format_path"=>"lbl_10130741015129936385130741457212784178.frx","print_type"=>"1"],
		'40'=>["format_id"=>"40","format_name"=>"不干胶广州邮政小包10*10","format_path"=>"lbl_EMS_BGD_NEW_10.frx","print_type"=>"1"],
		'27'=>["format_id"=>"27","format_name"=>"不干胶中邮+报关单10*10","format_path"=>"lbl_EMS_BGD_10.frx","print_type"=>"1"],
		'26'=>["format_id"=>"26","format_name"=>"不干胶中邮+报关单8*9","format_path"=>"lbl_EMS_BGD_8_9.frx","print_type"=>"1"],
		'207'=>["format_id"=>"207","format_name"=>"不干胶中邮挂号小包10*10","format_path"=>"lbl_EMS_BGD_NEW_10236356.frx","print_type"=>"1"],
		'49'=>["format_id"=>"49","format_name"=>"不干胶江苏小包10*10","format_path"=>"lbl_EMS_BGD_JSXB.frx","print_type"=>"1"],
		'206'=>["format_id"=>"206","format_name"=>"不干胶江苏小包15*10","format_path"=>"lbl_EMS_BGD_10130661925568413828886699.frx","print_type"=>"1"],
		'247'=>["format_id"=>"247","format_name"=>"不干胶江苏小包平邮10*10","format_path"=>"lbl_EMS_BGD_JSXB523591.frx","print_type"=>"1"],
		'46'=>["format_id"=>"46","format_name"=>"不干胶佛山小包10*10","format_path"=>"lbl_EMS_BGD_BJ_10130622373082763336.frx","print_type"=>"1"],
		'202'=>["format_id"=>"202","format_name"=>"不干胶佛山平邮小包10*10","format_path"=>"lbl_10130741015129936385.frx","print_type"=>"1"],
		'33'=>["format_id"=>"33","format_name"=>"不干胶俄罗斯小包10*10","format_path"=>"lbl_EMS_BGD_RU_10.frx","print_type"=>"1"],
		'32'=>["format_id"=>"32","format_name"=>"不干胶俄罗斯小包8*9","format_path"=>"lbl_EMS_BGD_RU_8_9.frx","print_type"=>"1"],
		'220'=>["format_id"=>"220","format_name"=>"不干胶深圳小包10*10","format_path"=>"lbl_EMS_BGD_10220729.frx","print_type"=>"1"],
		'54'=>["format_id"=>"54","format_name"=>"不干胶通用标签+报关单10*10","format_path"=>"lbl_EMS_BGD_10130715661196332051.frx","print_type"=>"1"],
		'45'=>["format_id"=>"45","format_name"=>"不干胶通用标签+报关单8*9","format_path"=>"lbl_EMS_BGD_8_9130618923031930450.frx","print_type"=>"1"],
		'25'=>["format_id"=>"25","format_name"=>"不干胶通用标签10*10","format_path"=>"lbl_10.frx","print_type"=>"1"],
		'24'=>["format_id"=>"24","format_name"=>"不干胶通用标签8*9","format_path"=>"lbl.frx","print_type"=>"1"],
		'222'=>["format_id"=>"222","format_name"=>"A4土耳其小包10*10","format_path"=>"A4_EMS_BGD_NEW_10971591.frx","print_type"=>"2"],
		'223'=>["format_id"=>"223","format_name"=>"A4土耳其平邮10*10","format_path"=>"A4_EMS_BGD_NEW_10971591780961.frx","print_type"=>"2"],
		'15'=>["format_id"=>"15","format_name"=>"A4马来西亚10*10","format_path"=>"A4_MY_10.frx","print_type"=>"2"],
		'55'=>["format_id"=>"55","format_name"=>"A4欧洲小包10*10","format_path"=>"A4_EMS_BGD_SF.frx","print_type"=>"1"],
		'229'=>["format_id"=>"229","format_name"=>"A4香港小包平邮标签10*10","format_path"=>"A4BGD_HK_PY_10.frx","print_type"=>"1"],
		'230'=>["format_id"=>"230","format_name"=>"A4香港小包挂号标签10*10","format_path"=>"A4BGD_HK_GH_10.frx","print_type"=>"1"],
		'214'=>["format_id"=>"214","format_name"=>"A4香港挂号小包10*10/A4*2","format_path"=>"A4香港6.frx","print_type"=>"1"],
		'219'=>["format_id"=>"219","format_name"=>"A4荷兰小包","format_path"=>"A4_EMS_BGD984235484883.frx","print_type"=>"1"],
		'213'=>["format_id"=>"213","format_name"=>"A4荷兰小包+报关单8*9","format_path"=>"A4_EMS_BGD984235.frx","print_type"=>"1"],
		'217'=>["format_id"=>"217","format_name"=>"A4荷兰平邮8*9","format_path"=>"A4BGD828416641931.frx","print_type"=>"1"],
		'12'=>["format_id"=>"12","format_name"=>"A4新加坡小包2","format_path"=>"A4_EMS_BGD_SG_PY.frx","print_type"=>"1"],
		'225'=>["format_id"=>"225","format_name"=>"A4新加坡小包平邮2","format_path"=>"A4_EMS_BGD_SG_PY.frx","print_type"=>"1"],
		'238'=>["format_id"=>"238","format_name"=>"A4新荷兰小包10*10","format_path"=>"A4_NL_XIN.frx","print_type"=>"1"],
		'239'=>["format_id"=>"239","format_name"=>"A4新瑞典小包","format_path"=>"A4_SE_XIN.frx","print_type"=>"2"],
		'52'=>["format_id"=>"52","format_name"=>"A4瑞士挂号10*10","format_path"=>"A4_2_EMS_BGD130700784314461427.frx","print_type"=>"1"],
		'226'=>["format_id"=>"226","format_name"=>"A4顺丰荷兰小包10*10","format_path"=>"A4_EMS_BGD_SF.frx","print_type"=>"1"],
		'221'=>["format_id"=>"221","format_name"=>"不干胶土耳其小包10*10","format_path"=>"lbl_EMS_BGD_JSXB130709437665203313130712106657949447387643.frx","print_type"=>"1"],
		'224'=>["format_id"=>"224","format_name"=>"不干胶土耳其平邮10*10","format_path"=>"lbl_EMS_BGD_JSXB130709437665203313130712106657949447387643108004.frx","print_type"=>"1"],
		'35'=>["format_id"=>"35","format_name"=>"不干胶马来西亚小包10*10","format_path"=>"lbl_MY_10.frx","print_type"=>"1"],
		'209'=>["format_id"=>"209","format_name"=>"不干胶马来西亚平邮10*10","format_path"=>"lbl_EMS_BGD_10130715661196332051996543.frx","print_type"=>"1"],
		'56'=>["format_id"=>"56","format_name"=>"不干胶欧洲小包10*10","format_path"=>"lbl_EMS_BGD_SF_10.frx","print_type"=>"1"],
		'215'=>["format_id"=>"215","format_name"=>"不干胶香港小包10*10","format_path"=>"不干胶香港E.frx","print_type"=>"1"],
		'246'=>["format_id"=>"246","format_name"=>"不干胶香港小包平邮10*10","format_path"=>"不干胶香港E937517.frx","print_type"=>"1"],
		'212'=>["format_id"=>"212","format_name"=>"不干胶荷兰小包加报关单10*10","format_path"=>"lbl_EMS_BGD_10275216.frx","print_type"=>"1"],
		'218'=>["format_id"=>"218","format_name"=>"不干胶荷兰平邮10*10","format_path"=>"lbl_EMS_BGD_10707181348479.frx","print_type"=>"1"],
		'245'=>["format_id"=>"245","format_name"=>"不干胶荷兰挂号小包8*9","format_path"=>"lbl_EMS_BGD_8_9664827.frx","print_type"=>"1"],
		'42'=>["format_id"=>"42","format_name"=>"不干胶新加坡小包10*10","format_path"=>"lbl_SG_10.frx","print_type"=>"1"],
		'44'=>["format_id"=>"44","format_name"=>"不干胶新加坡小包8*9","format_path"=>"lbl_EMS_BGD_8_9130618811307840198.frx","print_type"=>"1"],
		'237'=>["format_id"=>"237","format_name"=>"不干胶新荷兰小包10*10","format_path"=>"lbl_NL_XIN.frx","print_type"=>"1"],
		'240'=>["format_id"=>"240","format_name"=>"不干胶新瑞典小包10*10","format_path"=>"lbl_SE_XIN.frx","print_type"=>"1"],
		'253'=>["format_id"=>"53","format_name"=>"不干胶瑞士挂号10*10","format_path"=>"lbl_EMS_BGD_10130700788419436218.frx","print_type"=>"1"],
		'233'=>["format_id"=>"233","format_name"=>"不干胶顺丰荷兰小包10*10","format_path"=>"lbl_EMS_BGD_SF_10.frx","print_type"=>"1"],
		'235'=>["format_id"=>"235","format_name"=>"发票格式1","format_path"=>"发票格式1.frx","print_type"=>"Invoice"],
		'236'=>["format_id"=>"236","format_name"=>"发票格式2","format_path"=>"发票格式2.frx","print_type"=>"Invoice"],
		'71'=>["format_id"=>"71","format_name"=>"标准发票格式1","format_path"=>"发票格式1.frx","print_type"=>"Invoice"],
		'72'=>["format_id"=>"72","format_name"=>"标准发票格式2","format_path"=>"发票格式2.frx","print_type"=>"Invoice"],
		'0'=>["format_id"=>"","format_name"=>"一键打印10*10","format_path"=>"","print_type"=>"lab10_10"],
		"eA4"=>["format_id"=>"e1","format_name"=>"E邮宝,E特快A4打印"],
		"e10*10"=>["format_id"=>"e2","format_name"=>"E邮宝10*10不干胶打印"],
	];
	
	
	public function __construct(){
		//威速易测试环境
		
		//威速易正式环境
		self::$selectAuthUrl = 'http://www.gdwse.com:8083/selectAuth.htm';
		
		self::$createOrderUrl = 'http://www.gdwse.com:8083/createOrderApi.htm';
		
		self::$postOrderUrl = 'http://www.gdwse.com:8083/postOrderApi.htm';
		
		self::$getProductList = 'http://www.gdwse.com:8083/getProductList.htm';
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2015/11/03			初始化
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
			//$shippingfrom_enaddress = $account->address['shippingfrom_en'];//获取到账户中的地址信息
			
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
				return self::getResult(1, '', '威速易账户验证失败,e001');
			
			if(empty($this->customer_userid) || empty($this->customer_id) )
				return self::getResult(1, '', '威速易账户验证失败,e002');
			
			//判断是否是E邮宝发货
			$isEUB = false;
			
			if(stripos($service->shipping_method_name, 'E邮宝')!==false || stripos($service->shipping_method_name, 'E特快')!==false || stripos($service->shipping_method_name, 'E包裹')!==false)
				$isEUB = true;
			
			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}
				
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
				
			if (empty($order->consignee_country_code)){
				return self::getResult(1, '', '国家信息不能为空');
			}
				
			$tmpConsigneeProvince = $order->consignee_province;
			if (empty($tmpConsigneeProvince) && $isEUB) {
				if($order->consignee_country_code == 'FR'){
					$tmpConsigneeProvince = $order->consignee_city;
				}else{
					return self::getResult(1, '', 'E邮宝发货时收件人 州/省 不能为空');
				}
			}

			$phoneContact = '';
			if (empty($order->consignee_phone) && empty($order->consignee_mobile)){
				return self::getResult(1, '', '联系方式不能为空');
			}
				
			if (empty($order->consignee_phone) && !empty($order->consignee_mobile))
				$phoneContact = $order->consignee_mobile;
			else 
				$phoneContact = $order->consignee_phone;
			
			//重复发货 添加不同的标识码
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
			/*if(!empty($carrierAddressAndPhoneInfo['phone1']))
				$phoneContact = $carrierAddressAndPhoneInfo['phone1'];*/
			
			if (empty($consigneeStreet)){
				return self::getResult(1, '', '地址信息不能为空');
			}
			
			$postdata = [
					"buyerid" => $order->source_buyer_user_id,//买家ID
					"consignee_address" => $consigneeStreet,//收件地址街道，必填
					"consignee_city" => $order->consignee_city,
					"consignee_mobile" => (strpos($service->shipping_method_name, 'E邮宝') !== false && $order->consignee_mobile == $phoneContact) ? "" : $order->consignee_mobile,
					"consignee_name" => $order->consignee,//收件人,必填
					"trade_type" =>'ZYXT',
					"consignee_postcode" => $order->consignee_postal_code,//邮编，有邮编的国家必填
					"consignee_state" => $tmpConsigneeProvince,//州/省
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
				
				//sku如果是e邮宝，e特快，e包裹则传中文品名
				if($isEUB)
					$invoice_sku = $form_data['CN_Name'][$k];
				else 
					$invoice_sku = $item['sku'];
				
				$orderInvoiceParam[$k]=[
					"invoice_amount" =>$form_data['DeclaredValue'][$k] * $pieces,//申报价值，必填
					"invoice_pcs" =>$pieces,//件数，必填
					"invoice_title" =>empty($form_data['EName'][$k])?'':$form_data['EName'][$k],//品名，必填
					"invoice_weight" =>$form_data['invoice_weight'][$k] / 1000,//单件重(传入的单位为克，接受单位为千克)
					"item_id" =>'',//
					"item_transactionid" =>'',//
					"sku"=>$invoice_sku,
					"sku_code"=>$form_data['DeclareNote'][$k],//配货信息
				];
				$weight_amount += $orderInvoiceParam[$k]['invoice_weight'] * $pieces;
			}
			if(isset($form_data['weight']) && $form_data['weight']!=='' && is_numeric($form_data['weight']))
				$postdata['weight'] = $form_data['weight'];
			else 
				$postdata['weight'] = $weight_amount;
			$postdata['orderInvoiceParam'] = $orderInvoiceParam;
			
			//数据组织完成 准备发送
			#########################################################################
			//\Yii::info(print_r($postdata,1),"file");
			
			$requestBody = ['param'=>json_encode($postdata)];
			$response = Helper_Curl::post(self::$createOrderUrl,$requestBody);
			
			if (empty($response)){
				return self::getResult(1,'','操作失败,威速易返回错误');
			}
			
			\Yii::info('LB_WEISUYICarrierAPI1 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($postdata,1), "carrier_api");
			//$response = urldecode($response);
			$ret = json_decode($response,true);
			$message = urldecode( $ret['message']);
			//var_dump($ret);
			//exit();
			//分析返回结果###############################################################
			//无异常
			//返回的order_id为物流商内部标识，需要在打印物流单时用到，暂时保存于CarrierAPIHelper::orderSuccess的return_no参数中；
			if(strtolower($ret['ack'])=='true' && (strtolower($message)=='success'||$message=='') && !empty($ret['tracking_number'])){
				$r = CarrierAPIHelper::orderSuccess($order,$service,$ret['reference_number'],OdOrder::CARRIER_WAITING_PRINT,$ret['tracking_number'],['delivery_orderId'=>$ret['order_id']]);
				
				$print_param = array();
				$print_param['delivery_orderId'] = $ret['order_id'];
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_WEISUYICarrierAPI';
                $print_param['userToken'] = json_encode(['username'=>$a['username'], 'password'=>$a['password']]);
                $print_param['tracking_number'] = $ret['tracking_number'];
                $print_param['carrier_params'] = $service->carrier_params;
                
                try{
                	CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $ret['reference_number'], $print_param);
                }catch (\Exception $ex){
                }
				
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$ret['reference_number'].',运单号:'.$ret['tracking_number'].'');
			}
			//返回异常，再判断是否属于无法立即获得运单号的运输服务(如DHL,UPS之类)
			else
			{
				if( strtolower( $ret['ack']) == 'true' && stripos(' '.$message, '无法获取转单号') != false)
				{
					$r = CarrierAPIHelper::orderSuccess($order,$service,$ret['reference_number'],OdOrder::CARRIER_WAITING_DELIVERY,'',['delivery_orderId'=>$ret['order_id']]);
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$ret['reference_number'].',该货代的此种运输服务无法立刻获取运单号,需要在 "物流模块->物流操作状态->待交运" 中确认交运');
				}
				else{
					return  BaseCarrierAPI::getResult(1,'','上传失败！可再次编辑后重新上传直至返货成功提示。'.'<br>错误信息：'.$message.'<br>该单已经存于威速易后台的“草稿单”中,客户单号为:'.$ret['reference_number'].'你也可到威速易后台完善订单');
				}
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
				return self::getResult(1, '', '威速易账户验证失败,e001');
				
			if(empty($this->customer_userid) || empty($this->customer_id) )
				return self::getResult(1, '', '威速易账户验证失败,e002');
			
			$order_customerinvoicecode = $order->customer_number;
			$postOrderUrl = self::$postOrderUrl.'?customer_id='.$this->customer_id.'&order_customerinvoicecode='.$order_customerinvoicecode;

			$response = Helper_Curl::get($postOrderUrl,[],$header);

			//如果是错误信息
			if($response == 'false'){
				return BaseCarrierAPI::getResult(1, '', '结果：交运失败');
			}else if($response == 'true'){
				/*
				$N=OdOrderShipped::find()->where(['order_id'=>$order->order_id,'customer_number'=>$order->customer_number])->one();
				if($N<>null && $N->tracking_number!==$N->customer_number){//我们平台判断威速易tracking_number是否有效：tracking_number!==customer_number
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
		//return BaseCarrierAPI::getResult(1, '', '物流接口不支持申请跟踪号');
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

			$url = 'http://www.gdwse.com:8083/getOrderTrackingNumber.htm?documentCode='.$documentCode;
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';

			$response = Helper_Curl::post($url,[],$header);
			if (empty($response)){
				return self::getResult(1,'','操作失败,威速易返回错误');
			}
			$ret = json_decode($response,true);
		
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
                $print_param['api_class'] = 'LB_WEISUYICarrierAPI';
                $print_param['userToken'] = json_encode(['username'=>$a['username'], 'password'=>$a['password']]);
                $print_param['tracking_number'] = $ret['order_serveinvoicecode'];
                $print_param['carrier_params'] = $service->carrier_params;
                
                try{
                	CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
                }catch (\Exception $ex){
                }
				
				return BaseCarrierAPI::getResult(0, '', '获取物流号成功！物流号：'.$ret['order_serveinvoicecode']);
			}else{
				return BaseCarrierAPI::getResult(1, '', '威速易物流还没有返回物流号');
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
	public function doPrint($data)
	{
		try
		{
			$pdf = new PDFMerger();
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$returnNo = '';
			foreach ($data as $k=>$v) 
			{//拼接订单号
				$order = $v['order'];
			
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
			
				if($shipped->return_no['delivery_orderId']){
					$returnNo .= $shipped->return_no['delivery_orderId'].',';//获取物流商返回的order_id
				}
// 				$order->is_print_carrier = 1;
				$order->print_carrier_operator = $puid;
				$order->printtime = time();
				$order->save();
			
			}
			if(empty($returnNo)){
				throw new CarrierException('操作失败,订单不存在');
			}else {
				$returnNo = rtrim($returnNo,',');
			}
				
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$service_params = $service->carrier_params; //获取打印方式
			
			//默认 一键打印10*10
			//'0'=>["format_id"=>"","format_name"=>"一键打印10*10","format_path"=>"","print_type"=>"lab10_10"],
			$format_name = "一键打印10*10";
			$format_path = "";
			$print_type = "lab10_10";
		
			if(!empty($service_params['format'])){
				$format_id = (string)$service_params['format'];
				if(isset(self::$print_format_info[$format_id])){
					$format = self::$print_format_info[$format_id];
					$format_id = $format['format_id'];
					$format_name = $format['format_name'];
					if(isset($format['format_path'])) $format_path = $format['format_path'];
					if(isset($format['print_type'])) $print_type = $format['print_type'];
				}
			}
			
			//判断是否为E邮宝，E特快打印连接特殊处理
			if(!empty($format_id) && $format_id == 'e1'){
				//E邮宝，E特快A4打印，返回pdf路径
				$PDF_URL = 'http://www.gdwse.com:8083/getEUBPrintPath.htm?order_id='.$returnNo.'&format=A4';
				$PDF_URL = Helper_Curl::post($PDF_URL);
			}
			elseif(!empty($format_id) && $format_id == 'e2'){
				//E邮宝10*10不干胶打印，返回pdf路径
				$PDF_URL = 'http://www.gdwse.com:8083/getEUBPrintPath.htm?order_id='.$returnNo.'&format=10*10';
				$PDF_URL = Helper_Curl::post($PDF_URL);
			}
			/*
			 elseif ($format_id == 'yjzx'){//一级专线
			$PDF_URL = 'http://121.40.73.213:8082/downloadOneWorldLabel.htm?order_id='.$delivery_orderId;
			}*/
			else
			{
				$PDF_URL = 'http://www.gdwse.com/order/FastRpt/PDF_NEW.aspx?Format='.$format_path.'&PrintType='.$print_type.'&order_id='.$returnNo;
			
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
			}
			
			if(!preg_match('/\.pdf$/', $PDF_URL))
			{
				$order->carrier_error = '没有获取到正确的PDF连接';
				$order->save();
				return self::getResult(1,'','订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')没有获取到正确的PDF连接');
			}
			
			if(!empty($PDF_URL)){
				$responsePdf = Helper_Curl::get($PDF_URL);
				if(strlen($responsePdf)<1000){
					$order->carrier_error = '接口返回内容不是一个有效的PDF';
					$order->save();
					return self::getResult(1,'','订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')接口返回内容不是一个有效的PDF');
				}
				
				$pdfUrl = CarrierAPIHelper::savePDF($responsePdf,$puid,$account->carrier_code.'_'.$order['customer_number'], 0);
				$pdf->addPDF($pdfUrl['filePath'],'all');
			}else{
				$order->carrier_error = '获取用于打印的PDF的连接失败';
				$order->save();
				return self::getResult(1,'','订单'.$shipped->order_id.'原单号('. $shipped->order_source_order_id.')获取用于打印的PDF的连接失败');
			}
				
			if(isset($pdfUrl))
			{
				$pdf->merge('file', $pdfUrl['filePath']);
				
// 				$order->is_print_carrier = 1;
				$order->print_carrier_operator = $puid;
				//$order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
				$order->printtime = time();
				$order->save();
				
				return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印,订单已转到"待获取运单号"状态');
			}else 
				return self::getResult(1,['pdfUrl'=>''],'连接生成失败');
			
		}
		catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
			$format_id = '54';
			$format_path = 'lbl_EMS_BGD_10130715661196332051.frx';
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
				$PDF_URL = 'http://www.gdwse.com:8083/getEUBPrintPath.htm?order_id='.$delivery_orderId.'&format=A4';
			}
			elseif($format_id == 'e2'){
				//E邮宝10*10不干胶打印，返回pdf路径
				$PDF_URL = 'http://www.gdwse.com:8083/getEUBPrintPath.htm?order_id='.$delivery_orderId.'&format=10*10';
			}
			else{
				$PDF_URL = 'http://www.gdwse.com/order/FastRpt/PDF_NEW.aspx?Format='.$format_path.'&PrintType='.$print_type.'&order_id='.$delivery_orderId;
			}
			
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
					return ['error'=>1, 'msg'=>'打印失败！错误信息：'.returnMsg, 'filePath'=>''];
				}else{
					//$pdfUrl = CarrierAPIHelper::savePDF($responsePdf,$puid,$account->carrier_code.'_'.$order['customer_number'], 0);
					//$pdf->addPDF($pdfUrl['filePath'],'all');
					$pdfPath = CarrierAPIHelper::savePDF2($responsePdf,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
					return $pdfPath;
				}
			}else{
				$returnMsg .= '获取用于打印的PDF的连接失败';
				return ['error'=>1, 'msg'=>'打印失败！错误信息：'.returnMsg, 'filePath'=>''];
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