<?php
namespace common\api\overseaWarehouseAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use common\helpers\Helper_xml;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrderShipped;

class LB_EDAOverseaWarehouseAPI extends BaseOverseaWarehouseAPI
{
	private $user = '';
	private $tokey = '';
	
	public function __construct(){

	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function getOrderNO($data){
		try{
			//odOrder表内容
			$order = $data['order'];
			$customer_number = $data['data']['customer_number'];
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			//用户在确认页面提交的数据
			$form_data = $data['data'];
				
			//对当前条件的验证，如果订单已存在，则报错，并返回当前用户Puid
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
			$puid = $checkResult['data']['puid'];
				
			//获取物流商信息、运输方式信息等
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			$this->user = $api_params['user'];
			$this->tokey = $api_params['tokey'];
			
			///获取收件地址街道
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 100,
							'consignee_address_line2_limit' => 100,
							'consignee_address_line3_limit' => 100,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 50
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
						
			$weight="";
			$Product="";
			foreach ($order->items as $j=>$vitem){
				$sku="";
				//查询产品信息
				$channelArr=$this->getProductList($form_data['sku'][$j], $this->user, $this->tokey, $service->third_party_code);
				
				if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
				$channelArr=$channelArr['data'];

				if($channelArr['Ack']!='Success')
					return self::getResult(1,'',$channelArr['Errors']['ErrorMessage']);
				
				//没有
				if(empty($channelArr['GetProductListResponse']['Product'])){
					return self::getResult(1,'','该仓库没有这个商品 sku:'.$vitem['sku']);	
				}
				else{
					$sku=$channelArr['GetProductListResponse']['Product']['SKU'];
					$weight=$channelArr['GetProductListResponse']['Product']['Weight'];
				}				
								
				$messagearr=$this->errmessage($service->third_party_code, $service->shipping_method_code, $order->consignee_country_code, $order->consignee_postal_code,$weight);
				if($messagearr['error']){return self::getResult(1,'',$messagearr['msg']);}
				
				$Product.="<Product>".
						"<Sku>".$sku."</Sku>".   //产品 SKU
						"<Quantity>".$form_data['quantity'][$j]."</Quantity>".     //数量
						"</Product>";
			}

			$request="<AddOrder>".
					"<User>".$this->user."</User>".    //卖家登陆邮箱
					"<RequestTime>".$this->getLaFormatTime('Y-m-d H:i:s',time())."</RequestTime>". //请求时间
					"<AddOrderRequest>".
					"<OrderDetails>".
					"<OrderNo>".$customer_number."</OrderNo>". //订单编号
					"<OrderDate>".date("Y-m-d",$order->create_time)."</OrderDate>".     //订单日期 ,日期格式： Y-m-d
					"<CustomerName>".$order->consignee."</CustomerName>". //客户名称
					"<OrderType>4</OrderType>".    //销售渠道
					"<ShippingName>".$service->shipping_method_code."</ShippingName>".   //派送方式
					"<WarehouseNo>".$service->third_party_code."</WarehouseNo>".    //派送仓库编号
					"<Fax>".$order->consignee_fax."</Fax>". //传真
					"<TaxNo>".$form_data['taxNo']."</TaxNo>".     //税号
					"<Email>".$order->consignee_email."</Email>".     //EMAIL
					"<TransactionId></TransactionId>".     //交易 ID
					"<Registered>".$carrier_params['registered']."</Registered>".      //是否挂号
					"<IsInsure>".$form_data['isInsure']."</IsInsure>".      //是否投保
					"<AliexpressToken></AliexpressToken>".    //速卖通验证码,仅当 OrderType（销售渠道）为6（速卖通）时，存在该字段且为必填项
					"<ShipToAddress>".
					"<CityName>".$order->consignee_city."</CityName>".       //城市
					"<Country>".$order->consignee_country_code."</Country>".    //国家代码
					"<CountryName>".$order->consignee_country."</CountryName>".   //国家名称
					"<Name>".$order->consignee."</Name>".    //收货人名称
					"<Phone>".$addressAndPhone['phone1']."</Phone>".      //电话
					"<PostalCode>".$order->consignee_postal_code."</PostalCode>".    //邮编
					"<StateOrProvince>".$order->consignee_province."</StateOrProvince>".       //省份
					"<Street1>".$addressAndPhone['address_line1']."</Street1>".   //街道一
					"<Street2>".$addressAndPhone['address_line2']."</Street2>".     //街道二
					"</ShipToAddress>".
					$Product.
					"</OrderDetails>".
					"</AddOrderRequest>".
					"</AddOrder>";
			
			\Yii::info('LB_EDAOverseaWarehouse,puid:'.$puid.',request,order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			$channelArr=$this->runxml($request, $this->tokey, $service->third_party_code, "AddOrder");
			\Yii::info('LB_EDAOverseaWarehouse,puid:'.$puid.',response,order_id:'.$order->order_id.' '.json_encode($channelArr),"carrier_api");
			
			if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
			$channelArr=$channelArr['data'];

// 			print_r($channelArr);die;
			if($channelArr['Ack']!='Success')
				return self::getResult(1,'',$channelArr['Errors']['ErrorMessage']." ,或查看国家，邮编，重量，体积等是否符合当前派送方式.");

			if($channelArr['Errors']['ErrorCode']==0 && isset($channelArr['AddOrderResponse'])){
				if($channelArr['AddOrderResponse']['OrderDetails']['Ack']!='Success'){
					return self::getResult(1,'',$channelArr['AddOrderResponse']['OrderDetails']['Errors']['ErrorMessage']);
				}
				
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,null,['ProcessNo'=>$channelArr['AddOrderResponse']['OrderDetails']['ProcessNo']]);
// 				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$channelArr['AddOrderResponse']['OrderDetails']['ProcessNo'],['tracking_number'=>$channelArr['AddOrderResponse']['OrderDetails']['ProcessNo']]);
				return self::getResult(0,$r,'出库成功,处理单号:'.$channelArr['AddOrderResponse']['OrderDetails']['ProcessNo'].';请在已交运界面获取跟踪号');
			}
			else{
				return self::getResult(1,'',$channelArr['Errors']['ErrorMessage']." ,或查看国家，邮编，重量，体积等是否符合当前派送方式.");
			}

		}
		catch(CarrierException $e)
		{
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单交运
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function doDispatch($data){
		return self::getResult(1,'','物流接口不支持交运');
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取跟踪号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function getTrackingNO($data){
// 		return self::getResult(1,'','物流接口申请订单号时就会返回跟踪号');
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$api_params = $account->api_params;
			$this->user = $api_params['user'];   
			$this->tokey = $api_params['tokey'];  
				
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);//print_r($checkResult);die;
			$shipped = $checkResult['data']['shipped'];

			if(isset($shipped['return_no']['ProcessNo'])){
				$temp="<ProcessNo>".(isset($shipped['return_no']['ProcessNo'])?$shipped['return_no']['ProcessNo']:'')."</ProcessNo>".
						"<OrderNo>".(isset($shipped['customer_number'])?$shipped['customer_number']:'')."</OrderNo>";
			}
			else if(isset($shipped['return_no']['tracking_number'])){
				$temp="<ProcessNo>".(isset($shipped['return_no']['tracking_number'])?$shipped['return_no']['tracking_number']:'')."</ProcessNo>".
						"<OrderNo>".(isset($shipped['customer_number'])?$shipped['customer_number']:'')."</OrderNo>";
			}
			else{
				$temp="<ProcessNo>".(isset($shipped['customer_number'])?$shipped['customer_number']:'')."</ProcessNo>".
						"<OrderNo></OrderNo>";
			}	

			$request="<GetOrder>".
					"<User>".$this->user."</User>".
					"<RequestTime>".$this->getLaFormatTime('Y-m-d H:i:s',time())."</RequestTime>".
					"<GetOrderRequest>".$temp.
					"</GetOrderRequest>".
					"</GetOrder>";
			
		
			$channelArr=$this->runxml($request, $this->tokey, $service->third_party_code, "GetOrder");
			
			if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
			$channelArr=$channelArr['data'];

			if($channelArr['Errors']['ErrorCode']==0 && isset($channelArr['GetOrderResponse']['OrderDetails'])){
				$truckNumber=$channelArr['GetOrderResponse']['OrderDetails']['TrackNo'];
				if(empty($truckNumber))
					return self::getResult(1,'','跟踪号没有返回');

				$shipped->tracking_number = $truckNumber;
				$shipped->return_no = array("tracking_number"=>$truckNumber,"ProcessNo"=>$channelArr['GetOrderResponse']['OrderDetails']['ProcessNo']);
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				
				return self::getResult(0, '', '获取成功！跟踪号：'.$truckNumber);
			}
			else{
				return self::getResult(1,'',$channelArr['Errors']['ErrorMessage']);
			}
				
		}
		catch(CarrierException $e)
		{
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单取消
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function cancelOrderNO($data){
		return self::getResult(1,'','物流接口不支持取消已确定的物流单。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单打印
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lgw 	  2016/08/03			初始化
	 +----------------------------------------------------------
	 **/
	function doPrint($data){
		return self::getResult(1,'','该物流商不支持打印订单');
	}
	
	/*
	 * 获取各仓库运输服务
	*/
	function getDeliveryService($WarehouseNo){
// 		$this->user="wtuymqve@163.com";   //用户的账号
// 		$this->tokey="fd128199a2ffe61c4a280dc131911968";
		
		$request="<GetShippingMethodsList>".
				"<User>".$this->user."</User>".
				"<RequestTime>".$this->getLaFormatTime('Y-m-d H:i:s',time())."</RequestTime>".
				"<PageNumber></PageNumber>".
				"<ItemsPerPage></ItemsPerPage>".
				"<GetShippingMethodsListRequest>".
				"<WarehouseNo>".$WarehouseNo."</WarehouseNo>".
				"</GetShippingMethodsListRequest>".
				"</GetShippingMethodsList>";
		
		$channelArr=$this->runxml($request, $this->tokey, $WarehouseNo, "GetShippingMethodsList");
		
		if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
		$channelArr=$channelArr['data'];

		if($channelArr['Ack']!='Success')
			return self::getResult(1,'',$channelArr['Errors']['ErrorMessage']);
// 		print_r($channelArr);die;
		//这个接口只会返回渠道名称
		$result = '';
		if($channelArr['Errors']['ErrorCode']==0 && isset($channelArr['GetShippingMethodsListResponse'])){
			$shiparr=$channelArr['GetShippingMethodsListResponse']['ShippingMethods'];
			foreach ($shiparr as $shiparrone => $val){
				$result .= $val.':'.$val.';';
// 				$result .= "'".$val."'=>'".$val."',<br/>";
			}
		}
		else{
			return self::getResult(1,'',$channelArr['Errors']['ErrorMessage']);
		}
			
		if(empty($result)){
			return self::getResult(1, '', '');
		}else{
			return self::getResult(0, $result, '');
		}
		
	}
	
	//拼接请求报文xml格式字符串
	function arrayTOxml($lastDataArr,$token, $lang = 'zh-CN'){
		$xml = $lastDataArr;//请求报文 xml
		$digest = md5(substr($token, 0, 16) . $xml . substr($token, 16, 16));

		return $digest;
	}
	
	//获取地址
	function checkAPIUrl($WarehouseNo=6,$xml,$jiekou){
		$url='';
		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			if($WarehouseNo=='DE')
				$url = 'http://www.edawms.com/Api/'.$jiekou.'/DataDigest/'.$xml;   //德国仓
			else 
				$url = 'http://info.edaeu.com/Api/'.$jiekou.'/DataDigest/'.$xml;   //其它仓
		}
		else
			$url = 'http://test.edaeu.com/Api/'.$jiekou.'/DataDigest/'.$xml;   //测试环境

		return $url;
	}
	
	//时区转换
	function getLaFormatTime($format,$timestamp){
		$dt=new \DateTime();
		$dt->setTimestamp($timestamp);
		$dt->setTimezone(new \DateTimeZone('Europe/Berlin'));
		$d=$dt->format($format);
		return $d;
	}
	
	//组织数据提交
	function runxml($request,$token,$third_party_code,$jiekou){
		$xml=$this->arrayTOxml($request,$token);
		if(empty($xml))
			return self::getResult(1,'','无法获取数据签名');
			
		$url=$this->checkAPIUrl($third_party_code,$xml,$jiekou);
		
		if(is_null($url))
			return self::getResult(1,'','无法获取渠道地址');
		
		$header[] = "Content-type: text/xml";
		$ch = curl_init(); //初始化
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
		$response = curl_exec($ch);
		
		$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
		
		$errarr=Array
		(
		    'error'=> 0,
		    'data'=> $channelArr,
		    'msg'=> '',
		);
		
		return $errarr;
	}

	//获取商品信息
	function getProductList($sku,$user,$token,$thirdcode){
		$request="<GetProductList>".
				"<User>".$user."</User>".
				"<RequestTime>".$this->getLaFormatTime('Y-m-d H:i:s',time())."</RequestTime>".
				"<PageNumber></PageNumber>".
				"<ItemsPerPage></ItemsPerPage>".
				"<GetProductListRequest>".
				"<ProductIDArray>".
				"<ProductID></ProductID>".
				"</ProductIDArray>".
				"<SKUArray>".
				"<SKU>".$sku."</SKU>".
				"</SKUArray>".
				"</GetProductListRequest>".
				"</GetProductList>";
		
		$channelArr=$this->runxml($request, $token, $thirdcode, "GetProductList");
		
		if($channelArr['error']){return self::getResult(1,'',$channelArr['msg']);}
				
		return $channelArr;
	}
	
	function errmessage($thirdcode,$shippingcode,$countrycode,$postalcode,$weight){
		if($thirdcode=='UK'){
			if($shippingcode=='UK-DPD'){
				if($countrycode!='GB' && $countrycode!='UK')
					return self::getResult(1,'','目的国家不通邮');
			}
			else{
				if($countrycode!='UK')
					return self::getResult(1,'','目的国家不通邮');
			}

			if($shippingcode=='UK-DPD'){
				if(preg_match('/^\d*$/',$postalcode))
					return self::getResult(1,'','邮编格式不正确');
			}
			else if($shippingcode=='uk-test-2'){  //邮编(B/BB)
				if(substr($postalcode,0,1)!='B')
					return self::getResult(1,'','邮编格式不正确');
				if(substr($postalcode,0,1)=='B' && !preg_match('/^\d*$/',substr($postalcode,1,1)) && substr($postalcode,1,1)!='B')
					return self::getResult(1,'','邮编格式不正确');
			}
		}
		else if($thirdcode=='IT'){
			if($shippingcode=='IT-NEXIVE'){
				if($countrycode!='US' && $countrycode!='IT')
					return self::getResult(1,'','目的国家不通邮');
			}
			else{
				if($countrycode!='IT')
					return self::getResult(1,'','目的国家不通邮');
			}
		}
		else if($thirdcode=='FR'){
			$arr=array(
				'BE'=>'BE',
				'DE'=>'DE',
				'LU'=>'LU',
				'NL'=>'NL',
				'AT'=>'AT',
				'DK'=>'DK',
				'UK'=>'UK',
				'IT'=>'IT',
				'LI'=>'LI',
				'SM'=>'SM',
				'CH'=>'CH',
			);

			if(strstr($shippingcode,'境外')){
				if(!in_array($countrycode,$arr))
					return self::getResult(1,'','目的国家不通邮');
			}
			else{
				if($countrycode!='FR')
					return self::getResult(1,'','目的国家不通邮');
			}
		}
		else if($thirdcode=='ES'){
			$arr1=array(
					'HR'=>'HR',
					'SE'=>'SE',
					'RO'=>'RO',
					'NO'=>'NO',
					'LT'=>'LT',
					'LV'=>'LV',
					'IE'=>'IE',
					'IS'=>'IS',
					'FI'=>'FI',
					'BG'=>'BG',
					'SK'=>'SK',
					'PL'=>'PL',
					'HU'=>'HU',
					'UK'=>'UK',
					'NL'=>'NL',
					'IT'=>'IT',
					'DK'=>'DK',
					'CZ'=>'CZ',
					'BE'=>'BE',
					'AT'=>'AT',
					'FR'=>'FR',
					'DE'=>'DE',
			);
			
			$arr2=array(
					'FR'=>'FR',
					'DE'=>'DE',
					'PT'=>'PT',
			);
			
			if($shippingcode=='ES-GLS境外'){
				if(!in_array($countrycode,$arr1))
					return self::getResult(1,'','目的国家不通邮');
			}
			else if($shippingcode=='ES-DHL境外'){
				if(!in_array($countrycode,$arr2))
					return self::getResult(1,'','目的国家不通邮');
			}
			else{
				if($countrycode!='ES')
					return self::getResult(1,'','目的国家不通邮');
			}
		}
		else if($thirdcode=='DE'){
			if($shippingcode=='Hermes-SF'){
				if($countrycode!='CN')
					return self::getResult(1,'','目的国家不通邮');
			}
			else if($shippingcode=='PL-UPS'){
				if($countrycode!='AT' && $countrycode!='BE' && $countrycode!='LU' && $countrycode!='NL')
					return self::getResult(1,'','目的国家不通邮');
			}
			else if($shippingcode=='PL-GLS' || $shippingcode=='境外-GLS'){
				if($countrycode!='CZ' && $countrycode!='DK' && $countrycode!='LU' && $countrycode!='NL' && $countrycode!='PL')
					return self::getResult(1,'','目的国家不通邮');
			}
			else if($shippingcode=='Brief-境外'){
				if($countrycode!='AT' && $countrycode!='FI' && $countrycode!='FR' && $countrycode!='NL')
					return self::getResult(1,'','目的国家不通邮');
			}
			else if($shippingcode=='境外UPS'){
				if($countrycode!='AT')
					return self::getResult(1,'','目的国家不通邮');
			}
			else if($shippingcode=='仓库自提'){
				if($countrycode!='AT' && $countrycode!='DE' && $countrycode!='FR' && $countrycode!='UK')
					return self::getResult(1,'','目的国家不通邮');
			}
			else{
				if($countrycode!='DE')
					return self::getResult(1,'','目的国家不通邮');
			}
		}
		
// 		return self::getResult(1,'','通邮');
	}
	
	/**
	 * 获取海外仓库存列表
	 * 
	 * @param 
	 * 			$data['accountid'] 			表示账号小老板对应的账号id
	 * 			$data['warehouse_code']		表示需要的仓库ID
	 * @return 
	 */
	function getOverseasWarehouseStockList($data = array()){
// 		$data['accountid'] = '';
// 		$data['warehouse_code'] = 'PL';

		$this->user=$data['api_params']['user'];
		$this->tokey=$data['api_params']['tokey'];
		
// 		$this->user='andy@yuntda.com';
// 		$this->tokey='f666620a14404a1d31598c2ce731a523';
// 		$this->user="wtuymqve@163.com";   //用户的账号
// 		$this->tokey="fd128199a2ffe61c4a280dc131911968";
		
		//定义第几页开始
		$pageInt = 0;
		//默认最大页数为1
		$pageMaxInt = 1;
		
		$resultStockList = array();
		
		//循环翻页效果
		while ($pageInt < $pageMaxInt){
			$pageInt++;
			
			$request="<GetStorageList>".
					"<User>".$this->user."</User>".
					"<RequestTime>".$this->getLaFormatTime('Y-m-d H:i:s',time())."</RequestTime>".
					"<PageNumber>".$pageInt."</PageNumber>".
					"<ItemsPerPage>100</ItemsPerPage>".
					"</GetStorageList>";
		
			$response = $this->runxml($request, $this->tokey,$data['warehouse_code'], "GetStorageList");

			if(($response['error'] == 0) && ($response['data']['Ack'] == 'Success')){
				if($response['data']['Pagination']['TotalNumberOfEntries'] > 0){
					
					unset($tmpStorageList);
					
					//返回的结构比较特殊,当 ProductID 元素为1时结构会不同
					if(!isset($response['data']['GetStorageListResponse']['StorageList'][0])){
						$tmpStorageList[] = $response['data']['GetStorageListResponse']['StorageList']; 
					}else{
						$tmpStorageList = $response['data']['GetStorageListResponse']['StorageList'];
					}
					
					foreach ($tmpStorageList as $valList){
						unset($tmpStorageDetails);
						
						if(count($valList['StorageDetails']) > 0){
							if(!isset($valList['StorageDetails'][0])){
								$tmpStorageDetails[] = $valList['StorageDetails'];
							}else{
								$tmpStorageDetails = $valList['StorageDetails'];
							}
							
							foreach ($tmpStorageDetails as $tmpStorageDetailsVal){
								if($data['warehouse_code'] == $tmpStorageDetailsVal['WarehouseNo']){
									$resultStockList[$valList['SKU']] = array(
											'sku'=>$valList['SKU'],
											'productName'=>'',
											'stock_actual'=>$tmpStorageDetailsVal['RealQuantity'],				//实际库存
											'stock_reserved'=>$tmpStorageDetailsVal['RealQuantity']-$tmpStorageDetailsVal['SaleQuantity'],	//占用库存
											'stock_pipeline'=>0,	//在途库存
											'stock_usable'=>$tmpStorageDetailsVal['SaleQuantity'],	//可用库存
											'warehouse_code'=>$tmpStorageDetailsVal['WarehouseNo']		//仓库代码
									);
								}
							}
						}
					}
				}
				
				$pageMaxInt = $response['data']['Pagination']['TotalNumberOfPages'];
			}
		}

		return self::getResult(0, $resultStockList ,'');
	}
	
}
?>