<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use common\helpers\Helper_Array;

class LB_HUAHANCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = 'http://www.hh-exp.com/cgi-bin/GInfo.dll';	// 物流接口
	static private $canGetTrack = array(//可以获取跟踪号的渠道
			'俄速通'=>1,
			'瑞典邮政挂号'=>1,
			'翰俄宝'=>1,
			'荷兰邮政挂号'=>1,
// 			'济南邮政挂号'=>1,//已被暂停
			'南京邮政挂号'=>1,
			'南京邮政平邮'=>1,
			'深圳挂号'=>1,
			'厦门挂号'=>1,
			'深圳挂号BA'=>1,
			'北京--挂号'=>1,
			'深圳平邮BA'=>1,
			'北京平邮'=>1,
			'DHL-挂号小包'=>1,
			'DHL-平邮小包'=>1,
			'翰俄宝平邮'=>1,
			'速邮通'=>1,
			'速邮通平邮'=>1,
			'欧洲专线小包'=>1,
			'中山挂号'=>1,
	);
	private $submitGate = null;	// SoapClient实例
	
	public $customerNumber = null;
	public $appKey = null;
	
	public function __construct(){
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/11				初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$w = 'http://www.hh-exp.com/cgi-bin/GInfo.dll?EmsApi';
			//odOrder表内容
			$order = $data['order'];
			$o = $order->attributes;
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 254,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 62
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
				
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
			
			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			
			//获取到帐号中的认证参数
			$a = $account->api_params;
			$shippingfromaddress = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息

			$this->ICID = $a['ICID'];
			$this->IDkey = $a['IDkey'];
			
			//获取国家简码/中文名对应关系信息
			$countryList = Helper_Array::toHashmap(\eagle\models\SysCountry::find()->select(['country_zh','country_code'])->asArray()->all(), 'country_code','country_zh');
			//获取跟踪号
			$trackNo = '';
			if(isset(self::$canGetTrack[$service->shipping_method_code])){//可获取跟踪号
				//获取跟踪号
				$trackNoRes = self::getTrackNum($a['ICID'],$a['IDkey'],$service->shipping_method_code);
				if($trackNoRes['data'][0] == '-'){
					switch ($trackNoRes['data'][1]){
						case '3': return self::getResult(1,'','提取运单号时出现错误 : '.'快递类别不支持或单号已用尽或排队等待超时（太多的请求）,请重试上传！');break;
						case '4': return self::getResult(1,'','提取运单号时出现错误 : '.'系统未获有效注册');break;
						case '5': return self::getResult(1,'','提取运单号时出现错误 : '.'服务器版本类型不支持');break;
						case '6': return self::getResult(1,'','提取运单号时出现错误 : '.'时间戳验证失败,请重试上传！');break;
						case '7': return self::getResult(1,'','提取运单号时出现错误 : '.'MD5验证失败');break;
						case '9': return self::getResult(1,'','提取运单号时出现错误 : '.'数据库内部错误');break;
						default : return self::getResult(1,'','提取运单号时出现未知错误!!');break;
					}
				}
	// 			print_r($trackNoRes['data']);
				$trackNo = explode(',', $trackNoRes['data'])[1];
// 				print_r($trackNo);
// 				exit;
			}
			if(trim($a['PAYWAY']) == ""){
				return self::getResult(1, '', '认证参数中：付款方式不能为空！'.$a['PAYWAY']);
			}
			if(empty($a['PAYDIR'])){
				return self::getResult(1, '', '认证参数中：结算指示不能为空！');
			}
			if(empty($a['MONEY'])){
				return self::getResult(1, '', '认证参数中：计价币种不能为空！');
			}
			if (strlen($shippingfromaddress['contact'])>30){
				return self::getResult(1, '', '发件人姓名长度不能大于30');
			}
			if (strlen($shippingfromaddress['company'])>30){
				return self::getResult(1, '', '发件人所属部门长度不能大于30');
			}
			if (strlen($shippingfromaddress['phone'])>62){
				return self::getResult(1, '', '发件电话长度不能大于62');
			}
			if (strlen($shippingfromaddress['postcode'])>15){
				return self::getResult(1, '', '发件邮编长度不能大于15');
			}
			if (strlen($shippingfromaddress['country'])>63){
				return self::getResult(1, '', '发件国家长度不能大于63');
			}
			if (strlen($shippingfromaddress['city'])>63){
				return self::getResult(1, '', '发件城市长度不能大于63');
			}
			if (strlen($shippingfromaddress['province'])>30){
				return self::getResult(1, '', '发件省州长度不能大于30');
			}
			if (strlen($shippingfromaddress['mobile'])>63){
				return self::getResult(1, '', '发件手机号码长度不能大于63');
			}
			if (strlen($shippingfromaddress['email'])>63){
				return self::getResult(1, '', '发件电子信箱长度不能大于63');
			}
			if (strlen($shippingfromaddress['city'])>63){
				return self::getResult(1, '', '发件城市长度不能大于63');
			}
			if (strlen($order->consignee)>62){
				return self::getResult(1, '', '收件人姓名长度不能大于62');
			}
			if (strlen($order->consignee_phone)>62){
				return self::getResult(1, '', '收件电话长度不能大于62');
			}
			if (strlen($order->consignee_company)>254){
				return self::getResult(1, '', '收件公司长度不能大于254');
			}
			if (strlen($order->consignee_postal_code)>15){
				return self::getResult(1, '', '收件邮编长度不能大于15');
			}
			if (strlen($order->consignee_city)>128){
				return self::getResult(1, '', '收件城市长度不能大于128');
			}
			if (strlen($order->consignee_province)>62){
				return self::getResult(1, '', '收件省州长度不能大于62');
			}
			if (strlen($order->consignee_mobile)>22){
				return self::getResult(1, '', '收件人手机号码长度不能大于22');
			}
			if (strlen($order->consignee_email)>63){
				return self::getResult(1, '', '收件电子信箱长度不能大于63');
			}
			if (strlen($e['MARK'])>15){
				return self::getResult(1, '', '标签长度不能大于15');
			}
			if (strlen($e['MEMO'])>254){
				return self::getResult(1, '', '备注长度不能大于254');
			}
			if (strlen($e['SIZE']['Length'])>30){
				return self::getResult(1, '', '体积‘长’长度不能大于30');
			}
			if (strlen($e['SIZE']['Width'])>20){
				return self::getResult(1, '', '体积‘宽’长度不能大于20');
			}
			if (strlen($e['SIZE']['Height'])>50){
				return self::getResult(1, '', '体积‘高’长度不能大于50');
			}
			if (strlen($e['PACKING'])>15){
				return self::getResult(1, '', '包装长度不能大于15');
			}
			
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
				
			if (empty($order->consignee_country_code)){
				return self::getResult(1, '', '目的国家不能为空');
			}
				
			if (empty($order->consignee_address_line1)){
				return self::getResult(1, '', '地址1不能为空');
			}
			
			$phoneContact = '';
				
			if (empty($order->consignee_phone) || empty($order->consignee_mobile)){
				$phoneContact = $order->consignee_phone.$order->consignee_mobile;
			}else{
				$phoneContact = $order->consignee_phone.','.$order->consignee_mobile;
			}
			//参数初始化
			$TITEM = 0;//总数量
			$WEIGHTT = 0;//总重量
			$GOODS = "";
			//获取物品列表
// 			for($myi = 0; $myi < 3; $myi++)
			foreach ($order->items as $j=>$vitem){
				
				if(empty($e['GNAME'][$j])){
					return self::getResult(1, '', '物品名不能为空');
				}
				if(strlen($e['GNAME'][$j])>61){
					return self::getResult(1, '', '物品名长度不能大于61');
				}
				if(strlen($e['GOODSA'][$j])>62){
					return self::getResult(1, '', '物品别名长度不能大于62');
				}
				$GOODS .= '<GNAME>'.$e['GNAME'][$j].'</GNAME>'
							.'<GQUANTITY>'.$e['GQUANTITY'][$j].'</GQUANTITY>'
							.'<GPRICE>'.$e['GPRICE'][$j].'</GPRICE>'
							.'<GOODSA>'.$e['GOODSA'][$j].'</GOODSA>'
							.'<GCUSTOM>'.$e['GCUSTOM'][$j].'</GCUSTOM>'
							.'<GCRATE>'.$e['GCRATE'][$j].'</GCRATE>';
				$TITEM += $e['GQUANTITY'][$j];
				$WEIGHTT += $e['WEIGHTT'][$j];
			}
			while (strlen($customer_number) < 7){//内单号不能少于7位
				$customer_number .= 'a';
			}
			
			$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
			(empty($order->consignee_district) ? '' : ','.$order->consignee_district);
			//多条商品信息列表
			$req = '<ICID>'.$a['ICID'].'</ICID>'
					.'<NUM>'.$customer_number.'</NUM>'
					.'<CRNO>'.$customer_number.'</CRNO>'
					.'<ITEMTYPE>'.$e['ITEMTYPE'].'</ITEMTYPE>'
					.'<PAYWAY>'.$a['PAYWAY'].'</PAYWAY>'
					.'<INPUT>'.'小老板-API直传'.'</INPUT>'
					.'<EMSKIND>'.$service->shipping_method_code.'</EMSKIND>'
					.'<DES>'.$order->consignee_country_code.'</DES>'
					.'<TITEM>'.$TITEM.'</TITEM>'
					.'<WEIGHTT>'.($WEIGHTT/1000).'</WEIGHTT>'
					.'<SENDER>'.$shippingfromaddress['contact'].'</SENDER>'
					.'<DEPART>'.$shippingfromaddress['company'].'</DEPART>'
					.'<SPHONE>'.$shippingfromaddress['phone'].'</SPHONE>'
					.'<SUINTNAME>'.$shippingfromaddress['company'].'</SUINTNAME>'
					.'<SPOSTCODE>'.$shippingfromaddress['postcode'].'</SPOSTCODE>'
					.'<SADDR>'.$shippingfromaddress['street'].'</SADDR>'
					.'<SCOUNTRY>'.(isset($countryList[$shippingfromaddress['country']])?$countryList[$shippingfromaddress['country']]:$shippingfromaddress['country']).'</SCOUNTRY>'
					.'<SCITY>'.$shippingfromaddress['city'].'</SCITY>'
					.'<SPROVINCE>'.$shippingfromaddress['province'].'</SPROVINCE>'
					.'<SSMS>'.$shippingfromaddress['mobile'].'</SSMS>'
					.'<SEMAIL>'.$shippingfromaddress['email'].'</SEMAIL>'
					.'<RECEIVER>'.$order->consignee.'</RECEIVER>'
					.'<RPHONE>'.$addressAndPhone['phone1'].'</RPHONE>'
					.'<RUINTNAME>'.$order->consignee_company.'</RUINTNAME>'
					.'<RPOSTCODE>'.$order->consignee_postal_code.'</RPOSTCODE>'
					.'<RADDR>'.$addressAndPhone['address_line1'].'</RADDR>'
					.'<RCOUNTRY>'.$order->consignee_country_code.'</RCOUNTRY>'
					.'<RCITY>'.$order->consignee_city.'</RCITY>'
					.'<RPROVINCE>'.$order->consignee_province.'</RPROVINCE>'
					.'<RSMS>'.$order->consignee_mobile.'</RSMS>'
					.'<REMAIL>'.$order->consignee_email.'</REMAIL>'
					.'<MARK>'.$e['MARK'].'</MARK>'
					.'<MEMO>'.$e['MEMO'].'</MEMO>'
					.'<SIZE>'.$e['SIZE']['Length']."*".$e['SIZE']['Width']."*".$e['SIZE']['Height'].'</SIZE>'
					.'<WEIGHTB>'.($WEIGHTT/1000).'</WEIGHTB>'
					.'<GOODS>'.$GOODS.'</GOODS>'
					.'<PACKING>'.$e['PACKING'].'</PACKING>'
					.'<DVALUE>'.$e['DVALUE'].'</DVALUE>'
					.'<IVALUE>'.$e['IVALUE'].'</IVALUE>'
					.'<MONEY>'.$a['MONEY'].'</MONEY>'
					.'<ORIGIN>'.'CN'.'</ORIGIN>'
					.'<PAYDIR>'.$a['PAYDIR'].'</PAYDIR>'
					.'<FGOODST>'.$e['FGOODST'].'</FGOODST>'
					.'<FGOODSC>'.$e['FGOODSC'].'</FGOODSC>';
			if(!empty($trackNo)){
				$req .= '<CNNO>'.$trackNo.'</CNNO>';
			}
			$md5 = md5($req.$a['IDkey']);
			$req .= '<MD5>'.$md5.'</MD5>';
// 			echo $req;exit;
// 			$req .= '<CODEPAGE>950</CODEPAGE>';//*如果数据字符编码不是默认定义的编码（快递公司确定）,可在打包数据中提供编码类型。比如Big5：<CODEPAGE>950</CODEPAGE>
// 												//*目前支持的编码有：65001(UTF-8),936(GBK),950(BIG5),932(SHIFT-JIS)。
			

			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($req,1),"file");
			$response = $this->submitGate->mainGate($w, $req, 'curl', 'POST');

			if($response['error']){return $response;}
			
			$response = $response['data'];
			if($response[0] == '-'){
				$response_result = '未知错误！';
				switch ($response[1]){
					case 1: $response_result='客户不存在，没有为客户建立档案，或者客户ID不正确';break;
					case 2: $response_result='运单号重复，< NUM >定义的运单号在系统中已经存在';break;
					case 3: $response_result='GInfo系统未能读取初始化数据定义，不支持';break;
					case 4: $response_result='GInfo系统版本错误，不是授权的快递专业版';break;
					case 6: $response_result='没有解析到<MD5>标记数据';break;
					case 7: $response_result='MD5签名校验失败，请注意密钥的统一！';break;
					case 9: $response_result='数据库错误，GInfo平台问题';break;
					case 11: $response_result='客户ID错误，没有定义默认客户ID或者<ICID>数据有问题';break;
					case 14: $response_result='运单号数据错误< NUM >数据有问题(长度7-30 ASCII码字符)';break;
					case 15: $response_result='快递类别(EMSKIND)错误,可以设置默认值(2.5)以避免此类错误';break;
					default: $response_result='未知错误！';break;
				}
				return self::getResult(1,'',$response_result);
			}
			
			$response = explode("\r\n",$response);//"<br />"作为分隔切成数组
			
 			if(isset(self::$canGetTrack[$service->shipping_method_code])){//可获取跟踪号
// 				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（）
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_FINISHED,$trackNo,['num'=>$customer_number,'trackNo'=>$trackNo]);
 				return  BaseCarrierAPI::getResult(0,$r, '成功：运单号：'.$response[1].'。转单号：'.$trackNo);
 			}
 			else{//不可获取跟踪号
// 				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（）
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_FINISHED,'',['num'=>$customer_number]);
 				return  BaseCarrierAPI::getResult(0,$r, '成功：运单号：'.$response[1].'。转单号：此运输服务不支持提取');
 			}
 			


			return  BaseCarrierAPI::getResult(0,$r, '成功：内单号：'.$response[1]);

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	**/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消订单。');
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
	 * log			name	date					note
	 * @author		zwd		2015/12/11				初始化
	 +----------------------------------------------------------
	**/
	public function getTrackingNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持申请跟踪号');
		try{
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			//获取到帐号中的认证参数
			$a = $account->api_params;
			$this->ICID = $a['ICID'];
			$this->IDkey = $a['IDkey'];
			
			$tmpOrderNo = '';
				
			if($shipped->return_no['num'])
				$tmpOrderNo = $shipped->return_no['num'];
			
			$MD5 = md5($tmpOrderNo.$this->IDkey);
			$w = 'http://www.hh-exp.com/cgi-bin/GInfo.dll?EmsApiQuery'.
				'&icid='.$this->ICID.
				'&cno='.$tmpOrderNo.
				'&md5='.$MD5.
				'&ntype=10000';


			$response = $this->submitGate->mainGate($w, null, 'curl', 'get');
// 			print_r($response);exit;
			if($response['error']){return $response;}
			if(isset($response['data']['ReturnValue'])){
				switch($response['data']['ReturnValue'][1]){
					case 3: $response_result='运单号长度不对(7-30)';break;
					case 4: $response_result='系统未获有效注册';break;
					case 5: $response_result='服务器版本类型不支持';break;
					case 7: $response_result='MD5验证失败';break;
					case 9: $response_result='数据库内部错误';break;
					default: $response_result='未知错误！';break;
				}
			}
			print_r($response);exit;
			if(empty($response['data']['array_rst']['cNo'])){
				throw new CarrierException('暂时没有跟踪号');
			}
			else{
				if(empty($shipped->tracking_number)){
					$shipped->tracking_number=$response['data']['array_rst']['cNo'];
					$shipped->save();
				}
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				return BaseCarrierAPI::getResult(0,'','查询成功成功!跟踪号'.$response['data']['array_rst']['cNo']);
			}

		}
		catch(CarrierException $e){
			return BaseCarrierAPI::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	**/
	public function doPrint($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持打单');
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
	 +----------------------------------------------------------
	 * 获取渠道信息列表
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/11				初始化
	 *              lgw     2016-07-25                            修改
	 +----------------------------------------------------------
	 **/
	public function getCarrierShippingServiceStr($account){
		try{
// 			$w = 'http://www.hh-exp.com/cgi-bin/GInfo.dll?ajxEmsQueryEmsKind&w='.$this->IDkey;
			$w = 'http://www.hh-exp.com/cgi-bin/GInfo.dll?ajxEmsQueryEmsKind';
			$response = $this->submitGate->mainGate($w, null, 'curl', 'post');
			
			$response = $response['data'];
			$channelArr =  json_decode($response ,true);
			
			$channelStr = '';
	
			foreach ($channelArr['List'] as $channelVal){
				$channelStr .= $channelVal['oName'].':'.$channelVal['cName'].';';
			}

			if(empty($channelStr)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $channelStr, '');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	/**
	 +----------------------------------------------------------
	 * 运单号提取
	 * +----------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2015/12/11				初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackNum($ICID,$IDkey,$CEMSKIND){
		try{
// 			$CEMSKIND = ('济南邮政挂号');
			$time = number_format(microtime(true),3,'','');
			list($t1, $t2) = explode(' ', microtime());
			$time = $t2.ceil(($t1 * 1000));
			$md5 = md5($CEMSKIND.$time.$IDkey);
			$w = 'http://www.hh-exp.com/cgi-bin/GInfo.dll?EmsApiGetNo'
					.'&icid='.$ICID.'&cemskind='.urlencode($CEMSKIND).'&timestamp='.$time.'&cp=65001&md5='.$md5;
			$response = $this->submitGate->mainGate($w, null, 'curl', 'get');
			return $response;
		}catch(CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
		
		//测试全部可提取的渠道
// 		try{
// 			foreach (self::$canGetTrack as $k=>$v){
// 				$CEMSKIND = $k;
// 				$time = number_format(microtime(true),3,'','');
// 				list($t1, $t2) = explode(' ', microtime());
// 				$time = $t2.ceil(($t1 * 1000));
// 				$md5 = md5($CEMSKIND.$time.$IDkey);
// 				$w = 'http://www.hh-exp.com/cgi-bin/GInfo.dll?EmsApiGetNo'
// 						.'&icid='.$ICID.'&cemskind='.urlencode($CEMSKIND).'&timestamp='.$time.'&cp=65001&md5='.$md5;
// 				$response = $this->submitGate->mainGate($w, null, 'curl', 'get');
// 				if($response['data'][0] == '-'){
// 					echo $k.'+'.$response['data'].'<!-- -->';
// 				}
// 				// 			print_r($response['data']);
// 			}exit;
// 			return $response;
// 		}catch(CarrierException $e){
// 			return self::getResult(1,'',$e->msg());
// 		}
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
			$md5 = md5('086010007473'.$data['IDkey']);
			$w = 'http://www.hh-exp.com/cgi-bin/GInfo.dll?EmsApiQuery'
					.'&icid='.$data['ICID'].'&cno=086010007473&md5='.$md5;
			$response = $this->submitGate->mainGate($w, null, 'curl', 'get');
			$response=$response['data'];
			
			if(strstr($response, ','))
				$result['error'] = 0;
									
		}catch(CarrierException $e){
		}
	
		return $result;
	}
}

?>