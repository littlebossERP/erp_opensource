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

class LB_JIEMAISONGCarrierAPI extends BaseCarrierAPI
{
// 	static private $url="http://115.28.55.157/szyys";  //打印接口
// 	static private $url="http://115.28.55.157/jms-api";  //打印接口
	static private $url="http://121.42.239.142/jms-api";  //打印接口
	static private $wsdl = '';	// 物流接口
	static $last_post_info=null;
	static $connecttimeout=60;
	static $timeout=500;
	
	public $userName=null;
	public $token=null;
	
	public $soapClient = null;
	
	//错误信息
	private static	$res_err_arr1=array(
			'0'=>'成功',
			'10'=>'用户名或密码错误',
			'11'=>'数据异常,请检查格式',
			'12'=>'空数据,已自动忽略',
			'13'=>'系统错误',
			'14'=>'接口废弃,请联系技术人员',
			'100'=>'客户渠道不存在',
			'10000'=>'数据异常,请检查格式',
			'10001'=>'订单的渠道不存在,请检查',
			'10002'=>'订单的渠道禁用,请检查',
			'10003'=>'订单的原单号为空，转单号不为空，请检查',
			'10004'=>'原单号不可填写或不可用',
			'10005'=>'转单号不可填写或不可用',
			'10006'=>'国家不存在',
			'10007'=>'渠道下该国家不通邮',
			'10008'=>'订单收件人不存在或不符合要求,请检查',
			'10009'=>'订单收件人地址不存在或不符合要求,请检查',
			'10010'=>'订单收件人邮编不存在或不符合要求,请检查',
			'10011'=>'订单收件人城市不存在,请检查',
			'10012'=>'订单收件人州省不存在或不符合要求,请检查',
			'10013'=>'订单发件人不存在,请检查',
			'10014'=>'订单发件人电话不存在,请检查',
			'10015'=>'订单发件人公司不存在,请检查',
			'10016'=>'订单发件人地址不存在,请检查',
			'10017'=>'订单发件人邮编不存在,请检查',
			'10018'=>'订单发件人国家不存在,请检查',
			'10019'=>'物品名称(中文名称)为空或不符合要求',
			'10020'=>'物品数量为空或物品数量小于等于0',
			'10021'=>'物品价格为空或小于等于0或超出限制',
			'10022'=>'物品重量为空或小于等于0或超出限制',
			'10023'=>'所有物品价格单位不一致',
			'10024'=>'订单收件人公司为空或长度大于50',
			'10025'=>'订单收件人电话为空或长度大于30',
			'10026'=>'订单客户参考号为空或不可用',
			'10027'=>'订单物品为空',
			'10028'=>'海关编号不能包含中文',
			'10029'=>'物品价格单位不正确',
			'10030'=>'物品货号为空或长度大于限制',
			'10031'=>'物品原产国为空或不可用',
			'10032'=>'物品英文名称必须全部是英文加半角标点符号',
			'20000'=>'数据异常,请检查格式',
			'20001'=>'物品所属订单不存在',
			'20003'=>'物品所属订单状态不正确',
			'30000'=>'数据异常,请检查格式',
			'40000'=>'数据异常,请检查格式',
			'40001'=>'订单物品信息为空',
			'40002'=>'订单状态不正确',
			'40003'=>'暂不支持自动生成单号',
			'40004'=>'未发现可分配单号',
			'40005'=>'单号保存异常',
			'40006'=>'请求国际单号异常',
			'40007'=>'未发现原单号的存在',
			'50000'=>'数据异常,请检查格式',
			'50001'=>'订单所属商户错误',
			'50002'=>'跟踪单号必须存在',
			'50003'=>'订单状态必须为下单,物品信息必填',
			'60000'=>'数据异常,请检查格式',
			'60001'=>'订单所属商户错误',
			'60002'=>'订单状态必须为预报',
			'120001'=>'订单不存在或状态不正确',
			'120002'=>'修改订单批次里有订单重复',
			'120003'=>'订单已有跟踪号，不能修改',
			'120014'=>'收件人州省为空或不正确，请检查',
			'180000'=>'数据异常,请检查格式',
			'180002'=>'打印订单的单号未产生',
			'180003'=>'打印订单的渠道不一致',
			'180004'=>'打印类型错误',
			'10033'=>'订单收件人电话为空或长度大于20',
			'310001'=>'excel文件读取失败,请检查',
			'310002'=>'excel是个空文档,请检查',
			'310003'=>'模板类型不存在,请检查',
			'310004'=>'客户查询出现问题,请重试',
			'310005'=>'文档数据不存在,请检查',
			'310006'=>'数据保存异常,请重试',
			'310007'=>'导入的数据不符合要求标准,请检查数据并修改重新导入',
			'310008'=>'试图使用已经被使用过的单号,终止此次导入',
			'110000'=>'数据格式错误',
			'110001'=>'查无此单号',
			'10034'=>'订单收件人邮编不存在',
			'10035'=>'订单收件人邮编中间没有空格,不符合英国标准邮编格式',
			'10036'=>'邮编不正确导致无法查找中转信息',
			'10037'=>'地址二的长度不能超过80',
			'10038'=>'原单号规则不正确',
			'10039'=>'转单号规则不正确',
			'10040'=>'地址一的长度不能超过100',
			'10041'=>'邮编的长度不能超过17',
			'10042'=>'物品重量超过2KG',
			'20004'=>'订单已出账单,禁止修改',
			'120015'=>'已出账单,请删除账单后修改',
			'10043'=>'发件人名称不符合规范,请检查是否包含不通邮字符',
			'10044'=>'发件人地址不符合规范,请检查是否包含不通邮字符',
			'10045'=>'发件人州省不符合规范,请检查是否包含不通邮字符',
			'10046'=>'发件人城市不符合规范,请检查是否包含不通邮字符',
			'10047'=>'发件人邮编不符合规范,请检查是否包含不通邮字符',
			'10048'=>'中文品名必须存在中文.中文品名为空使用英文品名但也没有发现中文信息',
	);
	
	public function __construct(){
// 		self::$wsdl = self::$url.'/webservice/ordersOpenServiceHeader?wsdl';
		self::$wsdl = 'http://api.buylogic.cc/webservice/ordersOpenServiceHeader?wsdl';
		$this->soapClient = new \SoapClient(self::$wsdl);
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 * 物流的订单建立基本流程是：添加订单主体-->添加物品明细-->生成原单号-->（打印订单）-->预报订单
	 * 添加订单主体和添加物品明细为不同接口
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/05/23				初始化
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
						
			$senderAddressInfo=$info['senderAddressInfo'];
			$shippingfrom=$senderAddressInfo['shippingfrom'];
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 255,
							'consignee_address_line2_limit' => 255,
							'consignee_address_line3_limit' => 255,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

			if(empty($addressAndPhone['address_line1']))
				return self::getResult(1,'','收件人地址1不能为空');
			if(empty($order->consignee_country_code))
				return self::getResult(1,'','国家简码不能为空');
			
			foreach ($order->items as $j=>$vitem){
// 				if(empty($e['Name'][$j]))
// 					return self::getResult(1,'','中文名称不能为空');
// 				if(strlen($e['Name'][$j])>100)
// 					return self::getResult(1,'','中文名称不能大于100');
				if(empty($e['EName'][$j]))
					return self::getResult(1,'','英文名称不能为空');
				if(strlen($e['EName'][$j])>100)
					return self::getResult(1,'','英文名称不能大于100');
				if(empty($e['weight'][$j]))
					return self::getResult(1,'','重量不能为空');
				if(empty($e['quantity'][$j]))
					return self::getResult(1,'','数量不能为空');
				if(empty($e['DeclaredValue'][$j]))
					return self::getResult(1,'','价值不能为空');
// 				if(empty($e['originCountryCode'][$j]))
// 					return self::getResult(1,'','原产地不能为空');
// 				if(empty($e['originCountryName'][$j]))
// 					return self::getResult(1,'','原产地名称不能为空');
			}
			
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			$requestArr=[
				'shipmentId'=>$service->shipping_method_code,    //运输方式代号 (必填,long)
				'countryIso'=>$order->consignee_country_code,    //国家二字代码 (必填,string)
				'customerCode'=>$customer_number,  //客户自定义编号 (选填,string)
				'paymentTypeId'=>"1",    //结算方式 (选填,默认值为1,long)
				'packTypeId'=>"1",       //包裹类型 (选填，默认值为 1,long)
				'notes'=>$e['notes'],    //备注 (选填,string)
				'buyerId'=>$order->source_buyer_user_id, //买家Id (选填,string)
				'sellerId'=>$order->selleruserid, //卖家Id (选填,string)
				'company'=>$order->consignee_company, //收件人公司 (选填,string)
				'recipients' =>$order->consignee, //收件人(必填,string)
				'addr1' =>$addressAndPhone['address_line1'],  //地址1  (必填,string)
				'addr2' =>$addressAndPhone['address_line2'],  //地址2  (选填,string)
				'addr3' =>$addressAndPhone['address_line3'],  //地址3  (选填,string)
				'city' =>$order->consignee_city, //城市 (选填,string)
				'state' =>$tmpConsigneeProvince, //省份 (选填,string)
				'zip' =>$order->consignee_postal_code, //邮编 (选填,string)
				'tel' =>$addressAndPhone['phone1'], //电话 (选填,string)
				'email' =>$order->consignee_email, //email (选填,string)
				'passport' =>$e['passport'], //护照/税号 (选填,string)
				'senderCompany'=>$shippingfrom['company'], //发件人公司 (选填,string)
				'sender'=>$shippingfrom['contact'], //发件人 (选填,string)
				'senderAddr'=>$shippingfrom['street'], //发件人地址(选填,string)
				'senderTel'=>$shippingfrom['phone'], //发件人电话(选填,string)
				'senderZip'=>$shippingfrom['postcode'] //发件人邮编(选填,string)
			];

	// 		#########################################################################
			$request='['.json_encode($requestArr).']';
			$this->checkHeader($account);

			//订单头上传
			\Yii::info('LB_JIEMAISONG1,puid:'.$puid.',request,order_id:'.$order->order_id.' '.$request,"carrier_api");
			$response = $this->soapClient->addOrders(array('arg0'=>$request));
			if(!isset($response->return))
				return self::getResult(1,'','接口连接失败');
			\Yii::info('LB_JIEMAISONG1,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response->return,"carrier_api");
			$responseArr=json_decode($response->return);
			
			if($responseArr[0]->statue=="error"){
				$errorCode=$responseArr[0]->errorCode[0];
				if(empty($responseArr[0]->errorMsg[0])){
					return self::getResult(1,'',$this->getResultErr($errorCode,'订单上传失败'));
				}
				else{
					return self::getResult(1,'',$responseArr[0]->errorMsg[0]);
				}
			}
			else{
				if(empty($responseArr[0]->ordersId))
					return self::getResult(1,'','上传失败，没有返回订单号');
				//订单id
				$ordersId=$responseArr[0]->ordersId;
			}
// 			$ordersId='182';

			$requestItemArr=array();      //物品明细
			$requestClearanceArr=array();       //申报明细
			foreach ($order->items as $j=>$vitem){
				$requestItemArr[]=[
				'ordersId'=>$ordersId,		//订单唯一号,调用添加订单主体后返回的唯一订单编码 (必填,long)
				'name'=>$e['EName'][$j],			//物品明细名字 (必填,String)
				//'modelName'=>'',	//平台上名称 (选填,String)
				'price'=>floatval($e['DeclaredValue'][$j])*floatval($e['quantity'][$j]),		//价格 (必填,double)
				'unitId'=>'32',		//价格单位 (选填,默认为32,long)
				'platformId'=>'',	//所属平台 (选填,long)
				'platformCategory'=>'',	//平台类型 (选填,string)
				'platformOrdersId'=>$order->order_source_order_id,		//平台订单号 (选填,String)
				'transactionId'=>$vitem->order_source_transactionid,		//平台交易号 (选填,string)
				'soldPrice'=>'',		//售价 (选填,Double)
				'soldQuantity'=>$e['quantity'][$j],			//数量 (必填,integer)
				'colorId'=>'',			//颜色 (选填,long)
				'sizeId'=>'',				// 尺寸(选填,long)
				'weight'=>floatval($e['weight'][$j])/1000,			// 重量 (必填,double)
				'weightUnitId'=>'2',				//重量单位 (选填,默认值为2,long)
				'sku'=>$e['sku'][$j],				//货号 (选填,string)
				'storage'=>'',				//仓位 (选填,string)
				'hsCode'=>$e['hscode'][$j],	// 海关编号(选填,String)
				'nativeName'=>$e['Name'][$j],	// 中文报关名称(选填,string)
				'originCountryCode'=>$e['originCountryCode'][$j],	// 原产地 (选填,string)
				];
				
// 				$requestClearanceArr[]=[
// 				'ordersId'=>$ordersId,   //订单唯一号, 调用添加订单主体后返回的唯一订单编码(必填,long)
// 				'clearanceName'=>$e['EName'][$j],	// 申报明细名字 (必填,String)
// 				'hscode'=>$e['hscode'][$j],	// 海关编号(选填,String)
// 				'price'=>$e['DeclaredValue'][$j],	// 申报价格 (必填,double)
// 				'quanity'=>$e['quantity'][$j],	// 申报数量 (选填,Integer)
// 				'clearanceUnitId'=>'6',	// 申报数量单位 (选填, 默认为6,long)
// 				'clearancesWeight'=>floatval($e['weight'][$j])/1000,	// 申报重量 (必填,double)
// 				'clearancesCNname'=>$e['Name'][$j],	// 中文报关名称(必填,string)
// 				'clearancesENname'=>$e['EName'][$j],	// 英文报关名称(必填,string)
// 				'originCountryCode'=>$e['originCountryCode'][$j],	// 原产地 (必填,string)
// 				'originCountryName'=>$e['originCountryName'][$j],	// 原产地名称 (必填,string)
// 				];
			}
			$requestItem=json_encode($requestItemArr);
// 			$requestClearance=json_encode($requestClearanceArr);

			//上传订单明细
			\Yii::info('LB_JIEMAISONG2,puid:'.$puid.',request,order_id:'.$order->order_id.' '.$requestItem,"carrier_api");
			$response = $this->soapClient->addOrdersSoldItems(array('arg0'=>$requestItem));
			if(!isset($response->return))
				return self::getResult(1,'','接口连接失败');
			\Yii::info('LB_JIEMAISONG2,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response->return,"carrier_api");
			$responseArr=json_decode($response->return);	
			if($responseArr[0]->statue=="error"){
				$errorCode=$responseArr[0]->errorCode[0];
				if(empty($responseArr[0]->errorMsg[0])){
					return self::getResult(1,'',$this->getResultErr($errorCode,'订单明细上传失败'));
				}
				else{
					return self::getResult(1,'',$responseArr[0]->errorMsg[0]);
				}
			}
			
			// dzt20191220 捷买送反馈，soldItemId 这个字段没用了
// 			if(empty($responseArr[0]->soldItemId))
// 				return self::getResult(1,'','没有返回明细id');
			
			//上传申报明细
// 			$response2 = $this->soapClient->addOrdersClearances(array('arg0'=>$requestClearance));
// 			if(!isset($response2->return))
// 				return self::getResult(1,'','接口连接失败');
// 			$requestClearanceArr=json_decode($response2->return);
// 			if($requestClearanceArr[0]->statue=="error"){
// 				$errorCode=$requestClearanceArr[0]->errorCode[0];
// 				return self::getResult(1,'',$this->getResultErr($errorCode,'申报明细上传失败'));
// 			}
// 			if(empty($requestClearanceArr[0]->clearanceId))
// 				return self::getResult(1,'','没有返回申报明细id');

			//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
			$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_DELIVERY,null,['delivery_orderId'=>$ordersId]);
			return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$customer_number.',该货代无法立刻获取运单号,需要在 "物流模块->物流操作状态->待交运" 中获取跟踪号后再确认交运');

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
	 * @author		lgw		2016/05/23				初始化
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			$request='['.$shipped['return_no']['delivery_orderId'].']';
			
			$tracking_number=$shipped['tracking_number'];	
			if(empty($tracking_number)){
				return BaseCarrierAPI::getResult(1,'','跟踪号为空，无法交运，请先获取跟踪号');
			}
			
			$this->checkHeader($account);
			$response = $this->soapClient->foreOrders(array('arg0'=>$request));
			if(!isset($response->return))
				return self::getResult(1,'','接口连接失败');
			$responseArr=json_decode($response->return);

			if($responseArr[0]->statue=="error"){
				$errorCode=$responseArr[0]->errorCode[0];
				return self::getResult(1,'',$this->getResultErr($errorCode,'交运失败'.$errorCode));
			}
			else{
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return BaseCarrierAPI::getResult(0, '', '订单交运成功！追踪条码：'.$tracking_number);
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/05/23				初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		try{
				$order = $data['order'];
				//获取到所需要使用的数据
				$info = CarrierAPIHelper::getAllInfo($order);
				$account = $info['account'];
				
				//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
				$checkResult = CarrierAPIHelper::validate(0,1,$order);
				$shipped = $checkResult['data']['shipped'];
				
				$request='['.$shipped['return_no']['delivery_orderId'].']';
				
				$this->checkHeader($account);
				$response = $this->soapClient->madeTrackingNumber(array('arg0'=>$request));

				if(!isset($response->return))
					return self::getResult(1,'','接口连接失败');
				$responseArr=json_decode($response->return);
				
				if($responseArr[0]->statue=="error"){
					$errorCode=$responseArr[0]->errorCode[0];
					return self::getResult(1,'',$this->getResultErr($errorCode,'获取跟踪号失败'));
				}
				else{
// 					if(empty($responseArr[0]->trackIngNumber))
// 						return self::getResult(1,'','跟踪号没有返回');
					
					$shipped->tracking_number = $responseArr[0]->trackIngNumber;
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					
					return BaseCarrierAPI::getResult(0, '', '获取成功！跟踪号：'.$responseArr[0]->trackIngNumber);
				}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/05/23				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
						
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
			
			//取得打印类型
			$printType="buylogicLabel10Pdf";    
			if(!empty($service['carrier_params']['printType']))
				$printType = $service['carrier_params']['printType'];
			
			$package_sn = '';
			foreach ($data as $k => $v) {
				$order = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				$package_sn .= $shipped['return_no']['delivery_orderId'].",";
			}
			$package_sn='['.substr($package_sn,0,-1).']';

			$this->checkHeader($account);
			$response = $this->soapClient->printLabels(array('arg0'=>$package_sn,'arg1'=>$printType));	//print_r($response);die;		
			usleep(300000);
			if(!isset($response->return))
				return self::getResult(1,'','接口连接失败');
			$responseArr=json_decode($response->return);

			if($responseArr[0]->statue=="error"){
				$errorCode=$responseArr[0]->errorCode[0];
				return self::getResult(1,'',$this->getResultErr($errorCode,'打印失败'));
			}
			else{
				if(empty($responseArr[0]->URL))
					return self::getResult(1,'','没有返回打印路径');
				$pdfurl=self::$url.$responseArr[0]->URL;
				$response = self::get($pdfurl,null,null,false,null,null,true);
				if(strlen($response)<1000){
					foreach ($data as $v){
						$order = $v['order'];
						$order->carrier_error = $response;
						$order->save();
					}
					return self::getResult(1, '', $response);
				}
				$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$order->customer_number, 0);
				foreach ($data as $v){
					$order = $v['order'];
// 					$order->is_print_carrier = 1;
					$order->print_carrier_operator = $puid;
					$order->printtime = time();
					$order->save();
				}
				return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			$this->checkHeader($account);
			$response = $this->soapClient->getShipments();
			$response=json_decode($response->return);
			$result="";
			foreach ($response as $responseone){
				$result=$result.$responseone->shipmentId.":".$responseone->name.";";
			}

			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}
	
	//用来获取打印标签格式
	public function getLabelStr($account){
		try{
			$this->checkHeader($account);
			$response = $this->soapClient->getShipments();
			$response=json_decode($response->return);
			$result="";
			foreach ($response as $responseone){
				$printType=$responseone->printType;
				foreach ($printType as $printTypeone){
						$result=$result.$printTypeone->value.":".$printTypeone->name.";";
				}
			}
	
			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

	//验证soap头
	private function checkHeader($account){
		try{
			$username=is_null($account)?'XIAOLAOBAN':$account->api_params['userName'];
			$token=is_null($account)?'33e66991aaa09ba9228d63ded6db5d1a1l7utxza':$account->api_params['token'];

// 			header('Content-Type: text/xml; charset=utf-8');
			$header='<Header><userName>'.$username.'</userName><token>'.$token.'</token></Header>';
			$objVar_Session_Inside = new \SoapVar($header, XSD_ANYXML, null, null, null);
			$u = new \SoapHeader('yysOrdersWebService','header',$objVar_Session_Inside);

			$this->soapClient->__setSoapHeaders($u);
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//根据物流的错误信息直接返回
	private function getResultErr($errCode,$errstr='未知错误'){
		foreach(self::$res_err_arr1 as $k=>$r){
			if($errCode==$k){
				$errstr=$r;break;
			}
		}
		return $errstr;
	}
	
	/**
	 * 发起请求
	 *
	 * @param string $url
	 * @param string $requestBody
	 * @param string $requestHeader
	 * @param bool $justInit	是否只是初始化，用于并发请求
	 * @param string $responseSaveToFileName	结果保存到文件，函数只返回true|false
	 * @param bool $requestFollowlocation	启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
	 * @return bool|string
	 */
	public static function get($url,$requestBody=null,$requestHeader=null,$justInit=false,$responseSaveToFileName=null,$http_version=null,$requestFollowlocation=false){
		$connection = curl_init();
	
		curl_setopt($connection, CURLOPT_URL,$url);
		curl_setopt($connection, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($connection, CURLOPT_SSL_VERIFYHOST, 0);
		if (!is_null($requestHeader)){
			curl_setopt($connection, CURLOPT_HTTPHEADER, $requestHeader);
		}
		if (!is_null($http_version)){
			curl_setopt($connection, CURLOPT_HTTP_VERSION, $http_version);
		}
		if (!is_null($responseSaveToFileName)){
			$fp=fopen($responseSaveToFileName,'w');
			curl_setopt($connection, CURLOPT_FILE, $fp);
		}else {
			curl_setopt($connection, CURLOPT_RETURNTRANSFER, 1);
		}
		curl_setopt($connection, CURLOPT_CONNECTTIMEOUT,self::$connecttimeout);
		curl_setopt($connection, CURLOPT_TIMEOUT,self::$timeout);
		if ($justInit){
			return $connection;
		}
		if ($requestFollowlocation){
			//启用时会将服务器服务器返回的"Location: "放在header中递归的返回给服务器，使用CURLOPT_MAXREDIRS可以限定递归返回的数量。
			curl_setopt($connection, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($connection, CURLOPT_MAXREDIRS, 3);
		}
		
		$response = curl_exec($connection);
		usleep(500000);
		self::$last_post_info=curl_getinfo($connection);
		$error=curl_error($connection);
		curl_close($connection);
		if (!is_null($responseSaveToFileName)){
			fclose($fp);
		}
		if ($error){
			throw new CurlExcpetion_Connection_Timeout('curl_error:'.(print_r($error,1)).'URL:'.$url.'DATA:'.$requestBody);
		}
		return $response;
	}
	
}?>