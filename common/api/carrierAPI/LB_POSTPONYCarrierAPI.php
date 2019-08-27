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
use Jurosh\PDFMerge\PDFMerger;



class LB_POSTPONYCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	
	public $Key = null;
	public $Pwd = null;
// 	public $AuthorizedKey = 's0kTzXfT0Xd3vHHAt';   //第三方授权key（测试）
	public $AuthorizedKey = 'V3ic89n3dGkw';   //第三方授权key（正式）
	
	
	public function __construct(){
// 		self::$wsdl = 'https://apitest.postpony.com/api/';   //测试
		self::$wsdl = 'https://api.postpony.com/api/';       //正式
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/11/15				初始化
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
			$carrier_params = $service->carrier_params;
			$account = $info['account'];

			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			$senderAddressInfo=$info['senderAddressInfo'];

			//获取到帐号中的认证参数
			$a = $account->api_params;
			
			//卖家信息
			$shippingfrom=$senderAddressInfo['shippingfrom'];

			$this->Key = $a['Key'];
			$this->Pwd = $a['Pwd'];
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 47,
							'consignee_address_line2_limit' => 47,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 20
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$PublicPackage='';
			$totalweight=0;
			$totalPrice=0;
			foreach ($order->items as $j=>$vitem){	
				if(empty($e['postponyweight'][$j]))
					return self::getResult(1,'','重量不能为空');
				if(empty($e['Selectweight'][$j]))
					return self::getResult(1,'','请选择重量单位');
				
				//货代的重量标准单位是磅
				$CustomsWeight=$e['postponyweight'][$j];
				if($e['Selectweight'][$j]=='oz')
					$CustomsWeight=$CustomsWeight*0.0625;
				else if($e['Selectweight'][$j]=='g')
					$CustomsWeight=$CustomsWeight*0.0022046;
							
				$PublicPackage.='<CustomsItem>
									<Quantity>'.$e['quantity'][$j].'</Quantity>
									<UnitPrice>'.$e['DeclaredValue'][$j].'</UnitPrice>
									<Description><![CDATA['.$e['EName'][$j].']]></Description>
									<Weight>'.$CustomsWeight.'</Weight>
									<CustomsValue>'.($e['DeclaredValue'][$j]*$e['quantity'][$j]).'</CustomsValue>
									<CountryOfOrigin></CountryOfOrigin>
								</CustomsItem>';
				$totalweight+=$CustomsWeight*$e['quantity'][$j];
				$totalPrice+=$e['DeclaredValue'][$j]*$e['quantity'][$j];
			}
						
			//判断城市会否含有邮编,有就替换为空
			$consigneecity=$order->consignee_city; //收件人城市
			if(strstr($consigneecity,$order->consignee_postal_code))
				$consigneecity=str_replace($order->consignee_postal_code,'',$consigneecity);
			$shippingfromcity=$shippingfrom['city_en'];   //发件人城市
			if(strstr($shippingfromcity,$shippingfrom['postcode']))
				$shippingfromcity=str_replace($shippingfrom['postcode'],'',$shippingfromcity);
			

			if(empty($addressAndPhone['address_line1']))
				return self::getResult(1,'','收货人地址不能为空');
			if(empty($consigneecity))
				return self::getResult(1,'','收货人城市不能为空');
			if(empty($order->consignee_postal_code))
				return self::getResult(1,'','收货人邮编不能为空');
			if($shippingfrom['country']!='US')
				return self::getResult(1,'','发件国家必须为美国');
			if($totalPrice<(empty($e['Insurance'])?'0':$e['Insurance']))
				return self::getResult(1,'','保险费需小于等于申报金额');
			
			$is_domestic=1;   //判断是否为国内
			$FTRCode=isset($e['FTRCode'])?$e['FTRCode']:'30.37(a)';
			$ElectronicExportType=isset($e['ElectronicExportType'])?$e['ElectronicExportType']:'NoEEISED';
			$StateOrProvinceCode_in=self::getState($shippingfrom['province_en']);  //发件人州缩写
			$StateOrProvinceCode_out=self::getState($order->consignee_province);    //收件人州缩写

			$consigneephone=str_replace(' ','',$order->consignee_phone);   //收件人电话
			$shippingfromphone=str_replace(' ','',$shippingfrom['phone']);   //发件人电话
			
			if(empty($StateOrProvinceCode_in))
				return self::getResult(1,'','发件人州不能为空,请填写完整的州名');
			
			if(strstr($service->shipping_method_code,"International")){
				$is_domestic=0;
				//国外
				if($ElectronicExportType=='PreDepartureITN' && empty($e['AES']))
					return self::getResult(1,'','请填写有效的AES号');
				if($ElectronicExportType=='PreDepartureITN')
					$FTRCode=$e['AES'];
				
				$StateOrProvinceCode_out=$order->consignee_province;
				
				if(empty($consigneephone))
					return self::getResult(1,'','收货人电话不能为空');
			}
			else{
				//国内
				if($order->consignee_country_code!='US')
					return self::getResult(1,'','目的地不到达');
				if(empty($StateOrProvinceCode_out))
					return self::getResult(1,'','收件人州不能为空,请填写完整的州名');
								
				if(strpos(strtolower($service->shipping_method_code),'usps')!==0 && empty($consigneephone))
					return self::getResult(1,'','收货人电话不能为空');
			}
			
			//电话号码处理================================================================================
			if(strpos(strtolower($service->shipping_method_code),'usps')===0 && !empty($consigneephone)){
				$phonearr=array(' ','+','-','(',')','.','*','/');
			
				//收件人电话
				foreach ($phonearr as $phonearrone){
					$consigneephone=str_replace($phonearrone,'',$consigneephone);
				}
				if(strlen($consigneephone)<10)
					return self::getResult(1,'','USPS 需要 10 位电话号码');
				$consigneephone=substr($consigneephone, -10);
				if(!is_numeric($consigneephone))
					return self::getResult(1,'','收件人电话格式不对');
			
				//发件人电话
				foreach ($phonearr as $phonearrone){
					$shippingfromphone=str_replace($phonearrone,'',$shippingfromphone);
				}
				if(strlen($shippingfromphone)<10)
					return self::getResult(1,'','USPS 需要 10 位电话号码');
				$shippingfromphone=substr($shippingfromphone, -10);
				if(!is_numeric($shippingfromphone))
					return self::getResult(1,'','收件人电话格式不对');
			}
			//end====================================================================================================
			
			$request = '<ShipRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
						<UserCredential>        
							<Key>'.$this->Key.'</Key>
							<Pwd>'.$this->Pwd.'</Pwd>
						</UserCredential>
						<RequstInfo>
							<Shipper>
									<PersonName><![CDATA['.$shippingfrom['contact_en'].']]></PersonName>
									<CompanyName><![CDATA['.$shippingfrom['company_en'].']]></CompanyName>
									<PhoneNumber><![CDATA['.$shippingfromphone.']]></PhoneNumber>
									<StreetLines><string><![CDATA['.$shippingfrom['street_en'].']]></string><string /></StreetLines>
									<City><![CDATA['.$shippingfromcity.']]></City>
									<StateOrProvinceCode>'.$StateOrProvinceCode_in.'</StateOrProvinceCode>
									<PostalCode><![CDATA['.$shippingfrom['postcode'].']]></PostalCode>
									<CountryCode><![CDATA['.$shippingfrom['country'].']]></CountryCode>
									<CountryName><![CDATA['.$shippingfrom['country'].']]></CountryName>
									<IsResidentialAddress xsi:nil="true" />
							</Shipper>
							<Recipient>
									<PersonName><![CDATA['.$order->consignee.']]></PersonName>
									<CompanyName><![CDATA['.$order->consignee_company.']]></CompanyName>
									<PhoneNumber><![CDATA['.$consigneephone.']]></PhoneNumber>
									<StreetLines><string><![CDATA['.$addressAndPhone['address_line1'].']]></string><string><![CDATA['.$addressAndPhone['address_line2'].']]></string></StreetLines>
									<City><![CDATA['.$consigneecity.']]></City>
									<StateOrProvinceCode>'.$StateOrProvinceCode_out.'</StateOrProvinceCode>
									<PostalCode><![CDATA['.$order->consignee_postal_code.']]></PostalCode>
									<CountryCode><![CDATA['.$order->consignee_country_code.']]></CountryCode>
									<CountryName><![CDATA['.$order->consignee_country.']]></CountryName>
									<IsResidentialAddress>'.(isset($e['IsResidentialAddress'])?$e['IsResidentialAddress']:'false').'</IsResidentialAddress>
							</Recipient>
							<PackageItems>
									<PackageItemInfo>
										<PackageId>0</PackageId>
										<Length>'.(empty($e['PostponyLength'])?'1':$e['PostponyLength']).'</Length>
										<Width>'.(empty($e['PostponyWidth'])?'1':$e['PostponyWidth']).'</Width>
										<Height>'.(empty($e['PostponyHeight'])?'1':$e['PostponyHeight']).'</Height>
										<Weight>'.$totalweight.'</Weight>
										<Insurance>'.(empty($e['Insurance'])?'0':$e['Insurance']).'</Insurance>
										<UspsMailpiece>'.(isset($carrier_params['Mailpiece'])?$carrier_params['Mailpiece']:'None').'</UspsMailpiece>
										<IsOurInsurance>false</IsOurInsurance>
									</PackageItemInfo>
							</PackageItems>
							<CustomsValue>'.$totalPrice.'</CustomsValue>';
			$request2=$request.'<Package>
									<LabelId>0</LabelId>
      								<Weight>0</Weight>
									<ShippingNotes><![CDATA['.$e['ShippingNotes'].']]></ShippingNotes>
									<ShipDate>'.date('Y-m-d',$order->create_time).'T'.date('h:m:s',$order->create_time).'</ShipDate>
									<FTRCode>'.$FTRCode.'</FTRCode>
									<ContentsType>'.(isset($e['ShipmentPurpose'])?$e['ShipmentPurpose']:'Gift').'</ContentsType>
									<ElectronicExportType>'.$ElectronicExportType.'</ElectronicExportType>
							</Package>
							<CustomsList>'.$PublicPackage.'</CustomsList>
							<LbSize>'.$carrier_params['LbSize'].'</LbSize>
							<Signature>None</Signature>
						</RequstInfo>
						<ShipType>'.$service->shipping_method_code.'</ShipType>
						<LabelFormatType />
						<AuthorizedKey>'.$this->AuthorizedKey.'</AuthorizedKey>
						<OrderId>'.$customer_number.'</OrderId>
						</ShipRequest>';
			
//有客户需求暂时屏蔽=========
// 			$shiparr=self::getRate($request);
// 			if($shiparr['error']==1)
// 				return self::getResult(1,'',$shiparr['msg']);
// 			if(!in_array($service->shipping_method_code,$shiparr['data']))
// 				return self::getResult(1,'','验证失败，请检查订单信息');
//ending有客户需求暂时屏蔽=========
// 			print_r($request2);die();
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($request2,1),"file");
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl.'Ship',$request2,$post_head);
			//返回不是xml格式的提示错误信息
			$xml_parser = xml_parser_create();
			if(!xml_parse($xml_parser,$response,true)){
				xml_parser_free($xml_parser);
				return self::getResult(1,'',$response);
			}			

			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
//     print_r($channelArr);die();
			if($channelArr['Sucess']=='true'){
				$serviceNumber=$channelArr['MainTrackingNum'];
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$serviceNumber,['OrderSign'=>$channelArr['LabelId']]);
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!'.$channelArr['LabelId'].'订单号'.$customer_number.'物流跟踪号:'.$serviceNumber);
			}
			else{
				if(strstr($channelArr['Msg'],'OrderId Already Exist')){
					//因为网络问题货代已经生成订单但这边捉取不了返回信息，订单再次提交时拿已经提交的订单的信息
					$Labelidarr=self::getLabelId($account,$customer_number);
					if($Labelidarr['error'])
						return self::getResult(1,'',$Labelidarr['Msg']);
					
					$labelid=$Labelidarr['LabelId'];				
					$serviceNumber=$Labelidarr['TrackNos'];
// 					return self::getResult(1,'',$labelid.'---'.$serviceNumber);
					$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$serviceNumber,['OrderSign'=>$labelid]);
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!'.$labelid.'订单号'.$customer_number.'物流跟踪号:'.$serviceNumber.'.');
				}
				else
					return self::getResult(1,'',$channelArr['Msg']);
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/11/15				初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消已确定的物流单。');
		
		try{
				
			$order = current($data);reset($data);
			$order = $order['order'];
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
				
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
				
			//获取请求信息
			$this->Key = $account_api_params['Key'];
			$this->Pwd = $account_api_params['Pwd'];
				
			$tmpBarcodeStr = '';
				
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
				
			//获取追踪号
			$delivery_orderId="";
			foreach ($data as $k=>$v) {
				$order = $v['order'];
		
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
		
				$delivery_orderId = $shipped['return_no']['OrderSign'];//物流商内部订单id
		
				$request='<CancelShipRequst xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
				xmlns:xsd="http://www.w3.org/2001/XMLSchema">
				<UserCredential>
				<Key>'.$this->Key.'</Key>
				<Pwd>'.$this->Pwd.'</Pwd>
				</UserCredential>
				<LabelId>'.$delivery_orderId.'</LabelId>
				</CancelShipRequst>';
								$response = Helper_Curl::post(self::$wsdl.'CancelShip',$request,$post_head);
								$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
								print_r($channelArr);die;

			}
		
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
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
	 **/
	public function getTrackingNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');

	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/11/15				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$pdf = new PDFMerger();
			
			$order = current($data);reset($data);
			$order = $order['order'];
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			//获取请求信息
			$this->Key = $account_api_params['Key'];
			$this->Pwd = $account_api_params['Pwd'];
			
			$tmpBarcodeStr = '';
			
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			
			//获取追踪号
			$delivery_orderId="";
			foreach ($data as $k=>$v) {
				$order = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				
				$delivery_orderId = $shipped['return_no']['OrderSign'];//物流商内部订单id
				$request='<DownloadRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
						xmlns:xsd="http://www.w3.org/2001/XMLSchema">
						<UserCredential>
						<Key>'.$this->Key.'</Key>
						<Pwd>'.$this->Pwd.'</Pwd>
						</UserCredential>
						<LabelId>'.$delivery_orderId.'</LabelId>
						</DownloadRequest>';		
// 				print_r($delivery_orderId);die;
// 				$t=self::getLabel($account,'64878');
				$response = Helper_Curl::post(self::$wsdl.'Download',$request,$post_head);
				$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);

				if($channelArr['Sucess']=='true'){
					$pdf_jiemi=base64_decode($channelArr['LableData']['base64Binary']);
					if(strlen($pdf_jiemi)<1000){
						foreach ($data as $v){
							$order = $v['order'];
							$order->carrier_error = $pdf_jiemi;
							$order->save();
						}
						return self::getResult(1, '', '返回的打印地址错误');
					}
					$pdfUrl = CarrierAPIHelper::savePDF($pdf_jiemi, $puid, $account->carrier_code.'_'.$order->customer_number,0);
					$pdf->addPDF($pdfUrl['filePath'], 'all');
				}else{
					return self::getResult(1,'',$channelArr['Msg']);
				}
			}
			
// 			foreach ($data as $v){
// 				$order = $v['order'];
// 				$order->is_print_carrier = 1;
// 				$order->print_carrier_operator = $puid;
// 				$order->printtime = time();
// 				$order->save();
// 			}
			
			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'物流单已生成,请点击页面中打印按钮');
			
// 			if(strstr($delivery_orderId,',')){
// 				return self::getResult(1, '', '目前该货代不支持多单打印');
// 			}			

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			return null;
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//查询订单 
	public function getLabel($account,$labelid){
		try{
			$account_api_params = $account->api_params;
				
			//获取请求信息
			$this->Key = $account_api_params['Key'];
			$this->Pwd = $account_api_params['Pwd'];
				
			$tmpBarcodeStr = '';
			$request='<?xml version="1.0" encoding="utf-8" ?>
					<ShippingDetailRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					xmlns:xsd="http://www.w3.org/2001/XMLSchema">
					<UserCredential>
					<Key>'.$this->Key.'</Key>
					<Pwd>'.$this->Pwd.'</Pwd>
					</UserCredential>
					<LabelId>'.$labelid.'</LabelId>
					</ShippingDetailRequest>';
		
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl.'ShippingDetail',$request,$post_head);
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
			print_r($channelArr);die();			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//查询报价
	public function getRate($request){
		$request=str_replace('<ShipRequest','<ShippingRatesRequest',$request);
		$request=str_replace('<RequstInfo>','',$request);
		$request=str_replace('</RequstInfo>','',$request);
		$request=str_replace('<PackageItems>','<PackageInfos>',$request);
		$request=str_replace('</PackageItems>','</PackageInfos>',$request);
		$request=str_replace('<Shipper>','<OriginalAddress>',$request);
		$request=str_replace('</Shipper>','</OriginalAddress>',$request);
		$request=str_replace('<Recipient>','<DestinationAddress>',$request);
		$request=str_replace('</Recipient>','</DestinationAddress>',$request);
		$request.='<TotalInsuredValue xsi:nil="true" /></ShippingRatesRequest>';
// 		print_r($request);die();
		$shiparr=array();
		$post_head = array('Content-Type:text/xml;charset=UTF-8');
		$response = Helper_Curl::post(self::$wsdl.'Rate',$request,$post_head);
// 		print_r($response);die();	
		
		$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);		
// 		print_r($channelArr);die();	
		$shiparr['error']=0;
		$shiparr['msg']=isset($channelArr['Msg'])?$channelArr['Msg']:'';
		if($channelArr['Sucess']=='false'){
			$shiparr['error']=1;
		}
		if(isset($channelArr['Fedex']['Data']['RateResultDetail'])){
			$Fedex=$channelArr['Fedex']['Data']['RateResultDetail'];
			if(isset($Fedex[0]))
				foreach ($Fedex as $Fedexone)
					$shiparr['data'][]=$Fedexone['ShipType'];
			else
				$shiparr['data'][]=isset($Fedex['ShipType'])?$Fedex['ShipType']:'';
			$shiparr['error']=0;
		}
			
		if(isset($channelArr['Usps']['Data']['RateResultDetail'])){
			$Usps=$channelArr['Usps']['Data']['RateResultDetail'];
			if(isset($Usps[0]))
				foreach ($Usps as $Uspsone)
					$shiparr['data'][]=$Uspsone['ShipType'];
			else
				$shiparr['data'][]=isset($Usps['ShipType'])?$Usps['ShipType']:'';
			$shiparr['error']=0;
		}
		
		return $shiparr;
	}
	
	//获取LabelId(货代平台id)
	public function getLabelId($account,$customer_number){
		try{
			$account_api_params = $account->api_params;
		
			//获取请求信息
			$this->Key = $account_api_params['Key'];
			$this->Pwd = $account_api_params['Pwd'];

			$request='<?xml version="1.0" encoding="utf-8" ?>
					<LabelIdRequest xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
					xmlns:xsd="http://www.w3.org/2001/XMLSchema">
					<UserCredential>
					<Key>'.$this->Key.'</Key>
					<Pwd>'.$this->Pwd.'</Pwd>
					</UserCredential>
					<OrderId>'.$customer_number.'</OrderId>
					</LabelIdRequest>';

			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl.'GetLabelId',$request,$post_head);
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
// 						print_r($channelArr);die();
			$result=array(
					"error"=>1,
					"Msg"=>isset($channelArr['Msg'])?$channelArr['Msg']:'',
					"LabelId"=>$channelArr['LabelId'],
					'TrackNos'=>isset($channelArr['TrackNos'])?$channelArr['TrackNos']['string']:'',
			);
			if($channelArr['Sucess']=='true'){
				$result['error']=0;
			}
			
			return $result;
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//查询美国州缩写
	public function getState($StateOrProvinceCode){
		$arr=[
				'Alabama'=>'AL',
				'Alaska'=>'AK',
				'Arizona'=>'AZ',
				'Arkansas'=>'AR',
				'California'=>'CA',
				'Colorado'=>'CO',
				'Connecticut'=>'CT',
				'Delaware'=>'DE',
				'District of Columbia'=>'DC',
				'Florida'=>'FL',
				'Georgia'=>'GA',
				'Hawaii'=>'HI',
				'Idaho'=>'ID',
				'Illinois'=>'IL',
				'Indiana'=>'IN',
				'Iowa'=>'IA',
				'Kansas'=>'KS',
				'Kentucky'=>'KY',
				'Louisiana'=>'LA',
				'Maine'=>'ME',
				'Maryland'=>'MD',
				'Massachusetts'=>'MA',
				'Michigan'=>'MI',
				'Minnesota'=>'MN',
				'Mississippi'=>'MS',
				'Missouri'=>'MO',
				'Montana'=>'MT',
				'Nebraska'=>'NE',
				'Nevada'=>'NV',
				'New Hampshire'=>'NH',
				'New Jersey'=>'NJ',
				'New Mexico'=>'NM',
				'New York'=>'NY',
				'North Carolina'=>'NC',
				'North Dakota'=>'ND',
				'Ohio'=>'OH',
				'Oklahoma'=>'OK',
				'Oregon'=>'OR',
				'Pennsylvania'=>'PA',
				'Rhode Island'=>'RI',
				'South Carolina'=>'SC',
				'South Dakota'=>'SD',
				'Tennessee'=>'TN',
				'Texas'=>'TX',
				'Utah'=>'UT',
				'Vermont'=>'VT',
				'Virginia'=>'VA',
				'Washington State'=>'WA',
				'West Virginia'=>'WV',
				'Wisconsin'=>'WI',
				'Wyoming'=>'WY',
				'Puerto Rico'=>'PR',
				'AP'=>'AP',
		];
		
		$StateOrProvinceCode=str_replace(' ','',$StateOrProvinceCode);
		
		//处理时变成大小写,用作修正用户填州大小写的问题
		$resulf='';
		foreach ($arr as $key=>$arrone){
			if(strtolower($StateOrProvinceCode)==strtolower(str_replace(' ','',$key)))
				$resulf=$arrone;
		}
		if($resulf==''){
			if(in_array(strtoupper($StateOrProvinceCode),$arr))
				$resulf=strtoupper($StateOrProvinceCode);
		}
		
		return $resulf;
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
		$result = array('is_support'=>0,'error'=>1);
		
// 		try{
// 			$request = array(
// 					'appid'=>$data['AppId'],
// 					'token'=>$data['Token'],
// 			);
		
// 			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'CheckAccount');
		
// 			if($response['error'] == 0){
// 				$response = $response['data']->CheckAccountResult;
// 				if($response)
// 					$result['error'] = 0;
// 			}
// 		}catch(CarrierException $e){
// 		}
		
		return $result;
		
	}
}
?>