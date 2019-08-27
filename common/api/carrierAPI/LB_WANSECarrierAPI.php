<?php
namespace common\api\carrierAPI;


use yii;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use Qiniu\json_decode;

class LB_WANSECarrierAPI extends BaseCarrierAPI
{
	static private $url_aj = '';	// 物流接口
	public $appKey = null;
	
	public function __construct(){
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			//暂时正式环境和测试环境一样，对接成功后才可以更换
			self::$url_aj = 'http://www.shwise.cn/';
		}else{
			self::$url_aj = 'http://www.tophatterexpress.com/';
		}
		
// 		self::$url_aj = 'http://www.shwise.cn/';
	
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2018/05/07				初始化
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
			
			if(empty($info['senderAddressInfo'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			$shippingfrom_enaddress = $info['senderAddressInfo']['shippingfrom'];//获取到账户中的地址信息
			
			//获取到帐号中的认证参数
			$api_params = $account->api_params;
			
			$this->appKey = trim($api_params['api_key']);
			
			//省份为空时，直接用城市来替换
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
				
			if (empty($order->consignee_address_line1)){
				return self::getResult(1, '', '地址1不能为空');
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
			
			$phoneSender = '';
// 			if(empty($shippingfrom_enaddress['phone']) && empty($shippingfrom_enaddress['mobile'])){
// 				return self::getResult(1, '', '寄件人电话不能为空');
// 			}
			
			if(!empty($shippingfrom_enaddress['phone'])){
				$phoneSender = $shippingfrom_enaddress['phone'];
			}else{
				$phoneSender = $shippingfrom_enaddress['mobile'];
			}
			
			$totalQty = 0;
			$totalWeight = 0;
			$totalAmount = 0;
			$enName = '';
			$cnName = '';
			
			foreach ($order->items as $j=>$vitem){
				$enName .= $e['EName'][$j].';';
				$cnName .= $e['CN_Name'][$j].';';
				
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
			
			$recipient_country_short = $order->consignee_country_code;
			
			$tmp_warehouse_code = '';
			
			if($service->shipping_method_code == 5){
				$tmp_warehouse_code = '<warehouse_code>'.$service_carrier_params['warehouse_code_b'].'</warehouse_code>';
			}else if($service->shipping_method_code == 6){
				$tmp_warehouse_code = '<warehouse_code>'.$service_carrier_params['warehouse_code_w'].'</warehouse_code>';
			}
			
			$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
				'<orders>'.
				'<api_key>'.$this->appKey.'</api_key>'.
				'<zx_type>'.$service->shipping_method_code.'</zx_type>'.
				'<bid>1</bid>'.
				'<order>'.
				'<guid>'.$customer_number.'</guid>'.
				'<otype>'.$service_carrier_params['otype'].'</otype>'.
				'<from>'.$shippingfrom_enaddress['contact_en'].'</from>'.
				'<sender_province>'.$shippingfrom_enaddress['province_en'].'</sender_province>'.
				'<sender_city>'.$shippingfrom_enaddress['city_en'].'</sender_city>'.
				'<sender_addres>'.$shippingfrom_enaddress['street_en'].'</sender_addres>'.
				'<sender_phone>'.$phoneSender.'</sender_phone>'.
				'<sender_postcode>'.$shippingfrom_enaddress['postcode'].'</sender_postcode>'.
				'<to>'.$order->consignee.'</to>'.
				'<recipient_country_short>'.$recipient_country_short.'</recipient_country_short>'.
				'<recipient_province>'.$tmpConsigneeProvince.'</recipient_province>'.
				'<recipient_city>'.$order->consignee_city.'</recipient_city>'.
				'<recipient_addres>'.$addressAndPhone['address_line1'].'</recipient_addres>'.
				'<recipient_postcode>'.$order->consignee_postal_code.'</recipient_postcode>'.
				'<recipient_phone>'.$addressAndPhone['phone1'].'</recipient_phone>'.
				'<to_local></to_local>'.
				'<recipient_country_local></recipient_country_local>'.
				'<recipient_province_local></recipient_province_local>'.
				'<recipient_city_local></recipient_city_local>'.
				'<recipient_addres_local></recipient_addres_local>'.
				'<type_no>'.$e['type_no'].'</type_no>'.
				'<from_country>'.'CN'.'</from_country>'.
				'<content>'.$enName.'</content>'.
				'<content_cn>'.$cnName.'</content_cn>'.
				'<hs_code></hs_code>'.
				'<hasBattery>'.$e['has_battery'].'</hasBattery>'.
				'<num>'.$totalQty.'</num>'.
				'<weight>'.round($totalWeight, 3).'</weight>'.
				'<price>'.round($totalAmount, 2).'</price>'.
				'<trande_no>'.$order->order_source_order_id.'</trande_no>'.
				'<trade_amount>'.$e['trade_amount'].'</trade_amount>'.
				'<user_desc>'.str_replace("&", "", $e['user_desc']).'</user_desc>'.
				$tmp_warehouse_code.
// 				'<warehouse_code>'.$service_carrier_params['warehouse_code'].'</warehouse_code>'.
				'</order>'.
				'</orders>';
			
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';
			
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info('LB_WANSECarrierAPI,puid:'.$puid.',request,order_id:'.$order->order_id.' '.$getorder_xml,"carrier_api");
			
			$response = Helper_Curl::post(self::$url_aj.'api_order.asp', $getorder_xml, $header);
			\Yii::info('LB_WANSECarrierAPI,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response, "carrier_api");
			
			$response = self::xml2array($response, 1, '');
			
			if(!isset($response['root']['status']['value'])){
				return self::getResult(1, '', '万色返回结构异常，请联系小老板技术.');
			}
			
			if($response['root']['status']['value'] == 2){
				return self::getResult(1, '', $response['root']['barcode']['attr']['error_message']);
			}
			
			if($response['root']['status']['value'] == 2){
				return self::getResult(1, '', $response['root']['barcode']['attr']['error_message']);
			}
			
			if($response['root']['status']['value'] == 0){
				$OrderSign = array();
				$OrderSign['guid'] = $response['root']['barcode']['attr']['guid'];
				$OrderSign['reference_code'] = $response['root']['barcode']['attr']['reference_code'];
				$OrderSign['barcode'] = isset($response['root']['barcode']['value']) ? $response['root']['barcode']['value'] : '';
				
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填) 
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number, (empty($OrderSign['barcode']) ? OdOrder::CARRIER_WAITING_GETCODE : OdOrder::CARRIER_WAITING_PRINT), $OrderSign['barcode'], $OrderSign);
				
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!运单号'.$OrderSign['barcode']);
			}else{
				return self::getResult(1, '', $response['root']['barcode']['attr']['error_message']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2018/05/07				初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');
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
	    try{
	    	$user=\Yii::$app->user->identity;
	    	if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
	    	$puid = $user->getParentUid();
	    		
	    	//odOrder表内容
	    	$order = $data['order'];
	    
	    	//获取到所需要使用的数据
	    	$info = CarrierAPIHelper::getAllInfo($order);
	    	$service = $info['service'];
	    	$account = $info['account'];
	    	
	    	//获取到帐号中的认证参数
	    	$api_params = $account->api_params;
	    	$this->appKey = trim($api_params['api_key']);
	    	
	    	
	    	$checkResult = CarrierAPIHelper::validate(1,1,$order);
	    	$shipped = $checkResult['data']['shipped'];
	    	$OrderSign=$shipped->return_no;
	    	
	    	if(empty($OrderSign['reference_code'])){
	    		return self::getResult(1, '', '该渠道不支持获取跟踪号');
	    	}
	    	
	    	$tmpBarcodeStr = '<reference_code>'.$OrderSign['reference_code'].'</reference_code>';
	    		
	    	$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
	    			'<root>'.
	    			'<api_key>'.$this->appKey.'</api_key>'.
	    			'<reference_codes>'.
	    			$tmpBarcodeStr.
	    			'</reference_codes>'.
	    			'</root>';
	    		
	    	$header=array();
	    	$header[]='Content-Type:text/xml;charset=utf-8';
	    	
	    	$response = Helper_Curl::post(self::$url_aj.'get_barcode.asp', $getorder_xml, $header);
	    	
	    	$response = self::xml2array($response, 1, '');
	    	$xml = $response['root'];
	    	
	    	if (!isset($xml['status']['value'])){
	    		throw new CarrierException('操作失败,万色物流返回错误');
	    	}
	    		
	    	if($xml['status']['value'] == 0){
	    		if(empty($xml['barcode']['value'])){
	    			return self::getResult(1, '', '暂时没有跟踪号返回，请稍后再试');
	    		}
	    		
	    		$shipped->tracking_number = $xml['barcode']['value'];
	    		$shipped->save();
	    		$order->tracking_number = $shipped->tracking_number;
	    		$order->save();
	    		
	    		return BaseCarrierAPI::getResult(0, '', '获取成功！跟踪号：'.$shipped->tracking_number);
	    	}else{
	    		return self::getResult(1, '', $xml['error_message']['value']);
	    	}
	    	
	    }catch(CarrierException $e){}
	    
	    return BaseCarrierAPI::getResult(0, '', '操作成功！');
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2018/05/07				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			if(count($data) > 50)
				throw new CarrierException('万色物流一次只能批量打印50张面单');
			
			$order = current($data);reset($data);
			$order = $order['order'];
			
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$carrier_params = $service->carrier_params;
			
			//获取到帐号中的认证参数
			$api_params = $account->api_params;
			$this->appKey = trim($api_params['api_key']);
			
			
			$printlang = 0;
			$printcode = 2;
			
			if(!empty($carrier_params['format'])){
				$printcode = $carrier_params['format'];
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
			}
			
			$getorder_xml = "<?xml version='1.0' encoding='UTF-8'?>".
					'<root>'.
					'<api_key>'.$this->appKey.'</api_key>'.
					'<printlang>'.$printlang.'</printlang>'.
					'<printcode>'.$printcode.'</printcode>'.
					'<barcodes>'.
					$tmpBarcodeStr.
					'</barcodes>'.
					'</root>';
			
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';
				
			$response = Helper_Curl::post(self::$url_aj.'get_pdf.asp', $getorder_xml, $header);
			
			$response = self::xml2array($response, 1, '');
			
			$xml = $response['root'];
			
			if (!isset($xml['status']['value'])){
				throw new CarrierException('操作失败,万色物流返回错误');
			}
			
			if($xml['status']['value'] == 0){
				return self::getResult(0,['pdfUrl'=>$xml['PDF_URL']['value']],'连接已生成,请点击并打印');
			}else{
				throw new CarrierException('万色物流返回异常，请联系小老板客服.');
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 * 用来获取运输方式
	 *
	 * @author		hqw		2018/05/07				初始化
	 * 公共方法
	 **/
	public function getCarrierShippingServiceStr($account){
		$url = self::$url_aj.'get_channel.asp';
		$response = Helper_Curl::get($url);
		
		$response = self::xml2array($response, 1, '');
		
		if(!isset($response['root']['status']['value'])){
			return self::getResult(1, '', '返回结构错误e1');
		}
		
		if($response['root']['status']['value'] != 0){
			return self::getResult(1, '', '返回结构错误e2');
		}
		
		if(count($response['root']['zx_name']) == 0){
			return self::getResult(1, '', '返回结构错误e3');
		}
		
		$result = '';
		
		foreach($response['root']['zx_name'] as $tmp_zx_name){
			$result .= $tmp_zx_name['attr']['zx_type'].':'.$tmp_zx_name['value'].';';
		}
		
		if(empty($result)){
			return self::getResult(1, '', '');
		}else{
			return self::getResult(0, $result, '');
		}
	}
	
	//参考 https://blog.csdn.net/jianai0602/article/details/77802107
	public function xml2array($contents, $get_attributes=1, $priority = 'tag')
	{
		if(!$contents) return array();
	
		if(!function_exists('xml_parser_create')) {
			//print "'xml_parser_create()' function not found!";
			return array();
		}
	
		//Get the XML parser of PHP - PHP must have this module for the parser to work
		$parser = xml_parser_create('');
		xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); # http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, trim($contents), $xml_values);
		xml_parser_free($parser);
	
		if(!$xml_values) return;//Hmm...
	
		//Initializations
		$xml_array = array();
		$parents = array();
		$opened_tags = array();
		$arr = array();
	
		$current = &$xml_array; //Refference
	
		//Go through the tags.
		$repeated_tag_index = array();//Multiple tags with same name will be turned into an array
		foreach($xml_values as $data) {
			unset($attributes,$value);//Remove existing values, or there will be trouble
	
			//This command will extract these variables into the foreach scope
			// tag(string), type(string), level(int), attributes(array).
			extract($data);//We could use the array by itself, but this cooler.
	
			$result = array();
			$attributes_data = array();
	
			if(isset($value)) {
				if($priority == 'tag') $result = $value;
				else $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
			}
	
			//Set the attributes too.
			if(isset($attributes) and $get_attributes) {
				foreach($attributes as $attr => $val) {
					if($priority == 'tag') $attributes_data[$attr] = $val;
					else $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
				}
			}
	
			//See tag status and do the needed.
			if($type == "open") {//The starting of the tag '<tag>'
				$parent[$level-1] = &$current;
				if(!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
					$current[$tag] = $result;
					if($attributes_data) $current[$tag. '_attr'] = $attributes_data;
					$repeated_tag_index[$tag.'_'.$level] = 1;
	
					$current = &$current[$tag];
	
				} else { //There was another element with the same tag name
	
					if(isset($current[$tag][0])) {//If there is a 0th element it is already an array
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
						$repeated_tag_index[$tag.'_'.$level]++;
					} else {//This section will make the value an array if multiple tags with the same name appear together
						$current[$tag] = array($current[$tag],$result);//This will combine the existing item and the new item together to make an array
						$repeated_tag_index[$tag.'_'.$level] = 2;
	
						if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
							$current[$tag]['0_attr'] = $current[$tag.'_attr'];
							unset($current[$tag.'_attr']);
						}
	
					}
					$last_item_index = $repeated_tag_index[$tag.'_'.$level]-1;
					$current = &$current[$tag][$last_item_index];
				}
	
			} elseif($type == "complete") { //Tags that ends in 1 line '<tag />'
				//See if the key is already taken.
				if(!isset($current[$tag])) { //New Key
					$current[$tag] = $result;
					$repeated_tag_index[$tag.'_'.$level] = 1;
					if($priority == 'tag' and $attributes_data) $current[$tag. '_attr'] = $attributes_data;
	
				} else { //If taken, put all things inside a list(array)
					if(isset($current[$tag][0]) and is_array($current[$tag])) {//If it is already an array...
	
						// ...push the new element into that array.
						$current[$tag][$repeated_tag_index[$tag.'_'.$level]] = $result;
	
						if($priority == 'tag' and $get_attributes and $attributes_data) {
							$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
						}
						$repeated_tag_index[$tag.'_'.$level]++;
	
					} else { //If it is not an array...
						$current[$tag] = array($current[$tag],$result); //...Make it an array using using the existing value and the new value
						$repeated_tag_index[$tag.'_'.$level] = 1;
						if($priority == 'tag' and $get_attributes) {
							if(isset($current[$tag.'_attr'])) { //The attribute of the last(0th) tag must be moved as well
	
								$current[$tag]['0_attr'] = $current[$tag.'_attr'];
								unset($current[$tag.'_attr']);
							}
	
							if($attributes_data) {
								$current[$tag][$repeated_tag_index[$tag.'_'.$level] . '_attr'] = $attributes_data;
							}
						}
						$repeated_tag_index[$tag.'_'.$level]++; //0 and 1 index is already taken
					}
				}
	
			} elseif($type == 'close') { //End of tag '</tag>'
				$current = &$parent[$level-1];
			}
		}
	
		return($xml_array);
	}
	
}

?>