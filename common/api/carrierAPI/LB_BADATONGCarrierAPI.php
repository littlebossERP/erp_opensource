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



class LB_BADATONGCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	static $last_post_info=null;
	static $connecttimeout=60;
	static $timeout=500;
	
	public $companyID = '';
	public $pwd = '';
		
	public function __construct(){
		self::$wsdl = 'http://post.8dt.com:8880/wb_lc/cxf/ParcelOprWebService?wsdl';
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/18				初始化
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

			$this->companyID = $a['companyID'];
			$this->pwd = $a['pwd'];
			
			//法国没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			if (empty($tmpConsigneeProvince)){
				return self::getResult(1, '', '省份不能为空');
			}
			
			if (empty($order->consignee_city)){
				return self::getResult(1, '', '城市不能为空');
			}
				
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
				
			if (empty($order->consignee_country_code)){
				return self::getResult(1, '', '目的国家不能为空');
			}
								
			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 255,
							'consignee_address_line2_limit' => 255,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			if (empty($addressAndPhone['address_line1'])){
				return self::getResult(1, '', '地址不能为空');
			}
			
			$parcelList = [];
// 			$parcelList['apTdate'] = date('Y-m-d', $order->create_time);	//交易时间
			$parcelList['apmethod'] = $service->shipping_method_code;	//运输方式
			$parcelList['apname'] = $order->consignee;	//收件人姓名
			$parcelList['apaddress'] = $addressAndPhone['address_line1'];	//地址1
			$parcelList['address2'] = $addressAndPhone['address_line2'];	//地址2
			$parcelList['city'] = $order->consignee_city;	//城市
			$parcelList['province'] = $tmpConsigneeProvince;	//州/省
			$parcelList['apdestination'] = $order->consignee_country_code;	//目的国家
			$parcelList['aplabel'] = $e['apLabel'];	//包裹标签
			$parcelList['aptrackingNumber'] = '';	//追踪条码
			$parcelList['apnote'] = $e['apnote'];	//备注信息
			$parcelList['apBuyerID'] = $order->source_buyer_user_id;	//买家ID
// 			$parcelList['apItemurl'] = '';	//网络地址
			$parcelList['actualWeight'] = strval(empty($e['actualWeight'])?0:floatval($e['actualWeight'])/1000);	//实际重量kg
// 			$parcelList['apTransactionID'] = '';	//买家TransactionID
			$parcelList['apFromEmail'] = $order->consignee_email;	//买家邮箱
			if ($order->order_source == 'ebay')
				$parcelList['ebayID'] = $order->source_buyer_user_id;	//个人EbayID号
			$parcelList['apTel'] = $order->consignee_phone;	//收件人电话
			$parcelList['apTel2'] = $order->consignee_mobile;	//电话2
			$parcelList['zipCode'] = $order->consignee_postal_code;	//邮编
			$parcelList['refNo'] = $customer_number;	//参考号(客户参考号不能重复)
// 			$parcelList['apinsurance'] = '';	//易网邮保险
// 			$parcelList['aptype'] = '';	//物品类型
			$parcelList['apdescriptions'] = '';	//物品信息
			$parcelList['apquantitys'] = '';	//件数
			$parcelList['apweights'] = '';	//重量kg
			$parcelList['apvalues'] = '';	//价值
			$parcelList['customsArticleNames'] = '';	//报关品名信息
			$parcelList['imageUrl']='';      //sku图片地址
			$parcelList['apItemTitle']=$e['apItemTitle'];    //物品详情
		
			$totalWeight = 0;
			$totalAmount = 0;
			
			foreach ($order->items as $j=>$vitem){
				if(empty($e['Name'][$j]))
					return self::getResult(1,'','物品信息不能为空');
				
				if ((strlen($e['Name'][$j]) > 200))
					return self::getResult(1,'','单个物品信息太长，不能大于200个字符');
				if ((strlen($e['EName'][$j]) > 200))
					return self::getResult(1,'','单个报关品名太长，不能大于200个字符');

// 				$parcelList['apTransactionID']=$vitem->order_source_transactionid;   
				$parcelList['apdescriptions'].=$e['Name'][$j].";";
				$parcelList['apquantitys'].=$e['DeclarePieces'][$j].";";
				$parcelList['apweights'].=strval(floatval($e['weight'][$j])/1000).";";
				$parcelList['apvalues'].=$e['DeclaredValue'][$j].";";
				$parcelList['customsArticleNames'].=$e['EName'][$j].";";
				$skuCount[$vitem->sku]=$vitem->sku;
				$parcelList['imageUrl'].=$vitem->photo_primary.";";
				$totalWeight += (($e['weight'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j])) / 1000);
				$totalAmount += ($e['DeclaredValue'][$j] * (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]));
			}
			$parcelList['sku']=implode(';',array_slice(array_filter($skuCount),0,4));   //sku
			$parcelList['parcelstatus'] = '2';
			$parcelList['apweight'] =$totalWeight;
			$parcelList['apvalue'] = $totalAmount;
			$parcelList['apGross'] = $totalAmount;
			
//  		print_r($parcelList);die();
			$request = array(
					'companyID'=>$this->companyID,
					'pwd'=>$this->pwd,
					'parcelList'=>$parcelList,
			);

			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($request,1),"file");
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'AddParcelAndForecastService');
//      print_r($response);die();
			if($response['error']){return self::getResult(1,'',$response['msg']);}
			$response = $response['data']->return;
			$response = json_decode(json_encode($response),true);
						
			if (($response['errorCode'] == 0) && ($response['success'] == 1)){
				$trackingNo=null;       //可能没有返回跟踪号
				if(isset($response['trackingNo'])){
					$trackingNo=$response['trackingNo'];
				}
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$trackingNo,['OrderSign'=>$response['vsnumber']]);				
				
				try{
					$print_param = array();
					$print_param['carrier_code'] = $service->carrier_code;
					$print_param['api_class'] = 'LB_BADATONGCarrierAPI';
					$print_param['userToken'] = json_encode(['companyID' => $this->companyID, 'pwd' => $this->pwd]);
					$print_param['tracking_number'] = empty($trackingNo) ? '' : $trackingNo;
					$print_param['OrderSign'] = $response['vsnumber'];
					$print_param['carrier_params'] = $service->carrier_params;
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
				}catch(\Exception $ex){}
				
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号:'.$response['refNo'].' 追踪条码:'.$trackingNo);
			}
			else{
				if(isset($response['errorMsg']))
					return  BaseCarrierAPI::getResult(1,'',$response['errorMsg']);
				else 
					return  BaseCarrierAPI::getResult(1,'','上传失败');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}

	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/18				初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消已确定的物流单。');
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
				return self::getResult(1,'',$response['msg']);
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
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运。');
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
			
			$this->companyID = $account->api_params['companyID'];
			$this->pwd = $account->api_params['pwd'];
			
			//最终请求提交参数
			$request = array(
					'companyID'=>$this->companyID,
					'pwd'=>$this->pwd,
					'refNos'=>array('0'=>$order->customer_number),
			);
			
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'forecastByRefNoService');
			if($response['error'])return self::getResult(1,'',$response['msg']);
// print_r($response);die();
			$response = $response['data']->return;
			$response = json_decode(json_encode($response),true);
				
			if (($response['errorCode'] == 0) && ($response['success'] == 1)){
				$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
				$order->save();
				if(isset($response['trackingNo'])){
					//保存跟踪号
					$shipped->tracking_number = $response['trackingNo'];
					$shipped->save();
					return BaseCarrierAPI::getResult(0, '', '订单交运成功！已生成追踪条码：'.$response['trackingNo']);
				}
				else{
					return BaseCarrierAPI::getResult(0, '', '订单交运成功！但没有返回追踪条码');
				}
			}else{
				throw new CarrierException($response['errorMsg']);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
			
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
		
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$a = $info['account'];
			$service = $info['service'];// 运输服务相关信息
			//获取到帐号中的认证参数
			$api_params = $a->api_params;
			//将用户token记录下来
			
			try{
				$print_param = array();
				$print_param['carrier_code'] = $service->carrier_code;
				$print_param['api_class'] = 'LB_BADATONGCarrierAPI';
				$print_param['userToken'] = json_encode(['companyID' => $api_params['companyID'], 'pwd' => $api_params['pwd']]);
// 				$print_param['tracking_number'] = empty($trackingNo) ? '' : $trackingNo;
// 				$print_param['OrderSign'] = $response['vsnumber'];
				$print_param['carrier_params'] = $service->carrier_params;
				CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
			}catch(\Exception $ex){}
				
			return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/18				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
	try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
		
			$url = 'http://post.8dt.com/apiLabelPrint/freemarkerPrint';
		
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
		
			$params = $account->api_params;
			$this->companyID = $params['companyID'];
			$this->pwd = $params['pwd'];

			//取得打印尺寸
			$pageType="A4";    //纸张大小
			$abColset="Y";      //是否打印报关单
			$itemTitle="0";    //是否显示物品详情
			$printType="PDF";   //打印输出方式
			$peihuo="0";      //打印配货单
			if(!empty($service['carrier_params']['pageType']))
				$pageType = $service['carrier_params']['pageType'];   
			if(!empty($service['carrier_params']['abColset']))
				$abColset = $service['carrier_params']['abColset']; 
			if(!empty($service['carrier_params']['itemTitle']))
				$itemTitle = $service['carrier_params']['itemTitle']; 
			if(!empty($service['carrier_params']['peihuo']))
				$peihuo = $service['carrier_params']['peihuo'];
			
			//查询出订单操作号
			$package_sn = '';
			foreach ($data as $k => $v) {
				$order = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(0,1,$order);
				$shipped = $checkResult['data']['shipped'];
				if(empty($shipped["tracking_number"])){
					return self::getResult(1,'',"小老板订单号:".$shipped["order_id"]."跟踪号为空");
				}
				
				$package_sn .= $shipped["tracking_number"].",";   //apTrackingNo
				$apitype="apTrackingNo";
// 				$package_sn .= $order->customer_number.",";   //apRefNo
// 				$apitype="apRefNo";
 				
			}
			$package_sn=substr($package_sn,0,-1);
			
			##############################################################################
			//GET提交数据
			$requestData = "?&apUserId=".$this->companyID."&".$apitype."=".$package_sn."&abOrder=&abOrderType=&abColset=".$abColset."&printNumber=6&sellerID=1&pageType=".$pageType."&buyerID=0&printPosition=0&consignor=1&prTime=0&itemTitle=".$itemTitle."&bglabel=1&mergePrint=1&refNo=&sysAccount=1&barcodePrint=0&printType=".$printType."&fontSize=8&peihuo=".$peihuo;
			
			//提交请求
// 			$response = $this->submitGate->mainGate($url, $requestData, 'curl', 'GET');
			//如果CURL提交错误
				$response = self::get($url.$requestData,null,null,false,null,null,true);

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
				if(empty($pdfUrl['pdfUrl']))
					return self::getResult(1, '', "面单还没有生成,请稍后再打印");

				return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'物流单已生成,请点击页面中打印按钮');
// 			if($response['error'])return self::getResult(1,'',$response['msg']);
// 			$response = $response['data'];
// 			//如果成功返回pdf 则保存到本地
// 			foreach($data as $v){
// 					$order = $v['order'];
// 					$order->is_print_carrier = 1;
// 					$order->print_carrier_operator = $puid;
// 					$order->printtime = time();
// 					$order->save();
// 			}
// 			$pdfurl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code);
// 			return self::getResult(0,['pdfUrl'=>$pdfurl],'物流单已生成,请点击页面中打印按钮');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	

	/**
	 +----------------------------------------------------------
	 * 获取运输渠道信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/18				初始化
	 +----------------------------------------------------------
	 **/
	public function queryShipType(){
		try{
			$request = array(
					'companyID'=>$this->companyID, 
					'pwd'=>$this->pwd,
			);
	
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'queryShipType');
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}		
			$response = $response['data']->return;

			return self::getResult(0, $response, '');
	
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
			$url = 'http://post.8dt.com/apiLabelPrint/freemarkerPrint';
			
			$tmp_authParams = json_decode($print_param['userToken'], true);
			
			$this->companyID = $tmp_authParams['companyID'];
			$this->pwd = $tmp_authParams['pwd'];
			
			//取得打印尺寸
			$pageType="Label_100_100";    //纸张大小
			$abColset="Y";      //是否打印报关单
			$itemTitle="0";    //是否显示物品详情
			$printType="PDF";   //打印输出方式
			$peihuo="0";      //打印配货单
			
			if(!empty($print_param['carrier_params']['pageType']))
				$pageType = $print_param['carrier_params']['pageType'];
			if(!empty($print_param['carrier_params']['abColset']))
				$abColset = $print_param['carrier_params']['abColset'];
			if(!empty($print_param['carrier_params']['itemTitle']))
				$itemTitle = $print_param['carrier_params']['itemTitle'];
			
			if($pageType == 'A4'){
				$pageType="Label_100_100";
			}
			
			//查询出订单操作号
			$package_sn = $SAA_obj->customer_number;
			
			//GET提交数据
			$requestData = "?&apUserId=".$this->companyID."&apRefNo=".$package_sn."&abOrder=&abOrderType=&abColset=".$abColset."&printNumber=6&sellerID=1&pageType=".$pageType."&buyerID=0&printPosition=0&consignor=1&prTime=0&itemTitle=".$itemTitle."&bglabel=1&mergePrint=1&refNo=&sysAccount=1&barcodePrint=0&printType=".$printType."&fontSize=8&peihuo=".$peihuo;
		
			//如果CURL提交错误
			$response = self::get($url.$requestData,null,null,false,null,null,true);
			if(strlen($response)<1000){
				return ['error'=>1, 'filePath'=>'', 'msg'=>'打印失败！错误信息：接口返回内容不是一个有效的PDF'];
			}
			
			$pdfPath = CarrierAPIHelper::savePDF2($response, $SAA_obj->uid, $SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
			return $pdfPath;
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
}
?>