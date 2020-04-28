<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;

class LB_YITONGGUANCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	static $last_post_info=null;
	static $connecttimeout=60;
	static $timeout=500;
	
	public $companyID = null;
	public $pwd = null;
		
	public function __construct(){
		//易通关
		self::$wsdl = 'http://sys.etg56.com:8880/wb_lc/cxf/ParcelOprWebService?WSDL';
		
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/12				初始化
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
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 254,
							'consignee_address_line2_limit' => 254,
							// 							'consignee_address_line3_limit' => 100,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
			
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
				
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
// 			$shippingfromaddress = $account->address['shippingfrom'];//获取到账户中的地址信息
				
			$this->companyID = $a['companyID'];
			$this->pwd = $a['pwd'];
			
			//法国没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(($order->consignee_country_code == 'FR') && empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
// 			if (empty($order->consignee_province)){
// 				return self::getResult(1, '', '省份不能为空');
// 			}
				
			if (empty($order->consignee_city)){
				return self::getResult(1, '', '城市不能为空');
			}
			
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
			
			if (empty($order->consignee_country_code)){
				return self::getResult(1, '', '目的国家不能为空');
			}
			
			if (empty($addressAndPhone['address_line1'])){
				return self::getResult(1, '', '地址不能为空');
			}
			
			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}
				
				
			
			$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
				(empty($order->consignee_district) ? '' : ','.$order->consignee_district);
			
			$parcelList = [];
			
			$parcelList['apmethod'] = $service->shipping_method_code;	//运输方式
			$parcelList['apname'] = $order->consignee;	//收件人姓名
			$parcelList['apaddress'] = $addressAndPhone['address_line1'];	//地址1
			$parcelList['apdestination'] = $order->consignee_country_code;	//目的国家
			$parcelList['aplabel'] = $e['aplabel'];	//包裹标签
			
// 			$parcelList['aptrackingNumber'] = '';	//追踪条码
			$parcelList['apnote'] = $e['apnote'];	//备注信息
			$parcelList['apBuyerID'] = $order->source_buyer_user_id;	//买家ID
// 			$parcelList['apItemurl'] = '';	//网络地址
			$parcelList['apItemTitle'] = $e['apItemTitle'];	//物品详情
// 			$parcelList['apTransactionID'] = '';	//买家TransactionID
			$parcelList['apFromEmail'] = $order->consignee_email;	//买家邮箱
			if ($order->order_source == 'ebay')
				$parcelList['ebayID'] = $order->source_buyer_user_id;	//个人EbayID号
			$parcelList['apTel'] = $addressAndPhone['phone1'];	//收件人电话
			$parcelList['zipCode'] = $order->consignee_postal_code;	//邮编
			$parcelList['refNo'] = $customer_number;	//参考号(客户参考号不能重复)
// 			$parcelList['apinsurance'] = '';	//易网邮保险
// 			$parcelList['aptype'] = '';	//物品类型
			$parcelList['city'] = $order->consignee_city;	//城市
			$parcelList['province'] = $tmpConsigneeProvince;	//州/省
			$parcelList['address2'] = $addressAndPhone['address_line2'];	//地址2(地址2相当于是Street2(街道2)
			$parcelList['apTel2'] = $addressAndPhone['phone2'];	//电话2
			
			$parcelList['apdescriptions'] = '';	//物品信息:多个物品，采用分号分隔 例：物品1;物品2;物品3;物品4
			$parcelList['apquantitys'] = '';	//件数:多个件数，采用分号分隔 例：件数 1;件数 2;件数 3;件数 4）
			$parcelList['apweights'] = '';	//重量0 < apweight<= [10,3] 多个重量，采用分号分隔.例：重量 1;重量2;重量3;重量4
			$parcelList['apvalues'] = '';	//价值 (每个物品价格)0 < apweight<= [10,2].多个价值，采用分号分隔.例：价值 1;价值2;价值3;价值4
			$parcelList['customsArticleNames'] = '';	//报关品名信息:多个报关品名，采用分号分隔.例：报关品名1; 报关品名2; 报关品名3; 报关品名4
			$parcelList['imageUrl']='';      //sku图片地址
			$parcelList['taxNumber'] = $e['taxNumber'];	//税号
			
			$totalWeight = 0;
			$totalAmount = 0;
			
			foreach ($order->items as $j=>$vitem){
				if ((strlen($e['Name'][$j]) > 200))
					return self::getResult(1,'','单个物品信息太长，不能大于200个字符');
				if ((strlen($e['EName'][$j]) > 100))
					return self::getResult(1,'','单个报关名太长，不能大于100个字符');
				
				$parcelList['apdescriptions'] .= $e['Name'][$j].';';
				$parcelList['apquantitys'] .= (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]).';';
				$parcelList['apweights'] .= (($e['weight'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j])) / 1000).';';
				$parcelList['apvalues'] .= ($e['DeclaredValue'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j])).';'; 
				$parcelList['customsArticleNames'] .= $e['EName'][$j].';';
				
				$parcelList['hsCodes'] .= $e['hsCode'][$j].';';
				
				$skuCount[$vitem->sku]=$e['sku'];//$vitem->sku;
				$parcelList['imageUrl'].=$vitem->photo_primary.";";
				$totalWeight += (($e['weight'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j])) / 1000);
				$totalAmount += ($e['DeclaredValue'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]));
				
			}
			$parcelList['sku']=implode(';',array_slice(array_filter($skuCount),0,4));   //sku
			$parcelList['parcelstatus'] = '1';
			$parcelList['apweight'] = $totalWeight;
			$parcelList['apvalue'] = $totalAmount;
			$parcelList['apGross'] = $totalAmount;

			//多条商品信息列表
			$request = array(
					'parcelList'=>$parcelList,
					'companyID'=>$this->companyID,
					'pwd'=>$this->pwd,
			);
			
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($request,1),"file");
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'addParcelService');
			
			if($response['error']){return $response;}
			
			$response = $response['data']->return;
			$response = json_decode(json_encode($response),true);

			if ($response['errorCode'] == 0){
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
				$r = CarrierAPIHelper::orderSuccess($order,$service,$response['refNo'],OdOrder::CARRIER_WAITING_DELIVERY,null,['OrderSign'=>$response['vsnumber']]);
				
				//组织数据start，供getCarrierLabelApiPdf使用
				$print_param = array();
				$print_param['carrier_code'] = $service->carrier_code;
				$print_param['api_class'] = 'LB_YITONGGUANCarrierAPI';
				$print_param['companyID'] = $a['companyID'];
				$print_param['abColset'] = $service['carrier_params']['abColset'];
				$print_param['itemTitle'] = $service['carrier_params']['itemTitle'];
				
				try{
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $response['refNo'], $print_param);
				}catch (\Exception $ex){
				}
				//组织数据end
// 				return  BaseCarrierAPI::getResult(1,'','test');
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$response['vsnumber']);
			}else{
				return  BaseCarrierAPI::getResult(1,'',$response['errorMsg']);
				throw new CarrierException($response['errorMsg']);
			}
				
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/12				初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		try{
			$order = $data['order'];
		
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
		
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
		
			$params = $account->api_params;
			
			$this->companyID = $params['companyID'];
			$this->pwd = $params['pwd'];
			$request = array(
					'companyID' => $this->companyID,
					'pwd' => $this->pwd,
					'refNos' => array(0=>$shipped->customer_number),
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'deleteParcelByRefNoService');
			
			if($response['error']){
				return $response;
			}
			$response = $response['data']->return;
			$response = json_decode(json_encode($response),true);
			
			if (($response['errorCode'] == 0) && ($response['success'] == 1)){
				$shipped->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->save();
				return BaseCarrierAPI::getResult(0, '', '订单已取消!时间:'.date('Ymd His',time()));
			}else{
				throw new CarrierException($response['errorMsg']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
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

			//设置companyID 和 pwd
			$this->companyID = $account->api_params['companyID'];
			$this->pwd = $account->api_params['pwd'];

			//最终请求提交参数
			$request = array(
					'companyID'=>$this->companyID,
					'pwd'=>$this->pwd,
					'refNos'=>array('0'=>$order->customer_number),
			);

			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'forecastByRefNoService');
			if($response['error'])return $response;

			$response = $response['data']->return;
			$response = json_decode(json_encode($response),true);
			
			if (($response['errorCode'] == 0) && ($response['success'] == 1)){
				//保存跟踪号
				$shipped->tracking_number = $response['trackingNo'];
				$shipped->save();
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				return BaseCarrierAPI::getResult(0, '', '订单交运成功！已生成服务商单号：'.$response['trackingNo']);
			}else{
				if(isset($response['errorMsg']))
					throw new CarrierException($response['errorMsg']);
				else
					throw new CarrierException('交运失败');
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	**/
	public function getTrackingNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口交运时就会返回跟踪号');
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/12				初始化
	 +----------------------------------------------------------
	**/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
		
			$url = 'http://sys.etg56.com/apiLabelPrint/freemarkerPrint';
			$eurl='http://sys.etg56.com/lineUnderEub/fastPrintEub';
		
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
		
			$params = $account->api_params;
			$this->companyID = $params['companyID'];
			$this->pwd = $params['pwd'];
			
			$peihuo="0";      //打印配货单
			
			//取得打印尺寸
			$printFormat='A4';
			$abColset ='N';
			$itemTitle ='0';
			$printcode='00';
			
			if(!empty($service['carrier_params']['printFormat']))
				$printFormat = $service['carrier_params']['printFormat'];
			if(!empty($service['carrier_params']['abColset']))
				$abColset = $service['carrier_params']['abColset'];
			if(!empty($service['carrier_params']['itemTitle']))
				$itemTitle = $service['carrier_params']['itemTitle'];
			if(!empty($service['carrier_params']['peihuo']))
				$peihuo = $service['carrier_params']['peihuo'];
						
			if($printFormat=='Label_100_100')
				$printcode='01';
			else if($printFormat=='A4')
				$printcode='00';
			
			//查询出订单操作号
			$package_sn = '';
			$package_sn_t='';
			foreach ($data as $k => $v) {
				$order = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				$package_sn .= '&apRefNo='.$order->customer_number;
				$package_sn_t .= $shipped->tracking_number.',';
			}

			$package_sn_t=substr($package_sn_t,0,-1);
			##############################################################################
			//GET提交数据
			/**
			if(strrchr($service['shipping_method_code'],'EUB')=='EUB'){  //e邮宝接口
				$requestData = $eurl."?shipCode=".$service['shipping_method_code']."&printcode=".$printcode."&trackingNos=".$package_sn_t;
				
				return self::getResult(0,['pdfUrl'=>$requestData, 'type'=>'1'],'物流单已生成,请点击页面中打印按钮');
			}
			else{
				$requestData = $url."?apUserId=$this->companyID&pageType=$printFormat&abColset=$abColset&itemTitle=$itemTitle&printType=pdf".$package_sn."&peihuo=".$peihuo;
			}
			**/

			$requestData = $url."?apUserId=$this->companyID&pageType=$printFormat&abColset=$abColset&itemTitle=$itemTitle&printType=pdf".$package_sn."&peihuo=".$peihuo;

			$response = self::get($requestData,null,null,false,null,null,true);
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
// 				$order->is_print_carrier = 1;
				$order->print_carrier_operator = $puid;
				$order->printtime = time();
				$order->save();
			}
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'物流单已生成,请点击页面中打印按钮');
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 查询所有可走货渠道
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/14				初始化
	 +----------------------------------------------------------
	 **/
	public function getShipType(){
		try{
			$request = array(
					'companyID'=>$this->companyID,
					'pwd'=>$this->pwd,
			);
		
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'queryShipType');
			$response = json_decode(json_encode($response),true);
			$response = $response['data']['return'];
			
			return self::getResult(0, $response, '');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 * 按客户参考号获取订单信息
	 * 
	 * @param $refNosStr 客户参考号
	 */
	public function getParcelByRefNoService($refNosStr){
		try{
			$request = array(
					'companyID'=>$this->companyID,
					'pwd'=>$this->pwd,
					'refNos'=>array(0=>$refNosStr),
			);
		
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'queryParcelByRefNoService');
			$response = json_decode(json_encode($response),true);
			
			$response = $response['data']['return'];
				
			return self::getResult(0, $response, '');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
		
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
			$puid = $SAA_obj->uid;
	
			$url = 'http://sys.etg56.com/apiLabelPrint/freemarkerPrint';
			
			$printFormat = 'Label_100_100';
			//GET提交数据
			$requestData = "?apUserId=".$print_param['companyID']
							."&pageType=".$printFormat
							."&abColset=".$print_param['abColset']
							."&itemTitle=".$print_param['itemTitle']
							."&printType=pdf"
							."&apRefNo=".$SAA_obj->customer_number;
			
			$response = $this->submitGate->mainGate($url, $requestData, 'curl', 'GET');
			
			//如果CURL提交错误
			if($response['error'])
				return ['error'=>1, 'msg'=>'打印失败！错误信息：'.$response['msg'], 'filePath'=>''];
			
			//易通关返回数据
			$response = $response['data'];
			if(strlen($response)<1000){
				$response = json_decode($response,true);
				return ['error'=>1, 'msg'=>'打印失败！错误信息：'.$response['meta']['description'], 'filePath'=>''];
			}else{
				//如果成功返回pdf 则保存到本地
				$pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
				return $pdfPath;
			}
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
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
			$request = array(
					'companyID'=>$data['companyID'],
					'pwd'=>$data['pwd'],
			);
	
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'queryShipType');
	
			if($response['error'] == 0){
				$response = json_decode(json_encode($response),true);
				$response = $response['data'];
	
				if(!empty($response))
					$result['error'] = 0;
					
			}
		}catch(CarrierException $e){
		}
	
		return $result;
	}

	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			// TODO carrier user account @XXX@
			$request = array(
					'companyID'=>is_null($account)?'@XXX@':$account->api_params['companyID'],
					'pwd'=>is_null($account)?'@XXX@':$account->api_params['pwd']
			);
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'queryShipType');
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			if(!isset($response['data']->return) || empty($response['data']->return))
				return self::getResult(1,'','获取运输方式失败');
			$response = $response['data']->return;
	
			$result = '';
			foreach ($response as $channelVal){
				$result .= $channelVal->shipCode.':'.$channelVal->shipName.';';
			}

			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
}

?>