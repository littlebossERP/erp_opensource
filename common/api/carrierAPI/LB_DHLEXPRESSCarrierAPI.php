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
use yii;
use common\helpers\Helper_xml;



class LB_DHLEXPRESSCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	
	public $SiteID = null;
	public $Pwd = null;
	public $ShipperAccountNumber=null;
	
	private static $SoftwareName="littleboss";
	private static $SoftwareVersion="1.0";
	
	public function __construct(){
// 		self::$wsdl = 'https://xmlpitest-ea.dhl.com/XMLShippingServlet';       //测试
		self::$wsdl = 'https://xmlpi-ea.dhl.com/XMLShippingServlet';       //正式
		
// 		$user=\Yii::$app->user->identity;
// 		if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
// 		$puid = $user->getParentUid();
		
// 		if($puid == 9037){
// 			//这个账号是用于DHL Express内部测试
// 			self::$wsdl = 'https://xmlpitest-ea.dhl.com/XMLShippingServlet';
// 		}
		
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/12/15				初始化
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

			$this->SiteID = $a['SiteID'];
			$this->Pwd = $a['Password'];
			$this->ShipperAccountNumber=$a['ShipperAccountNumber'];
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 35,
							'consignee_address_line2_limit' => 35,
							'consignee_address_line3_limit' => 35,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 50
			);

			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);

			$totalweight=0;
			$totalPrice=0;
			$totalqty=0;
			$Contents='';
			
			$ExportLineItem="";  //发票的商品信息
			foreach ($order->items as $j=>$vitem){	
				if(empty($e['Weight'][$j]))
					return self::getResult(1,'','重量不能为空');
				if(empty($e['DeclaredValue'][$j]))
					return self::getResult(1,'','申报金额不能为空');
				if(empty($e['EName'][$j]))
					return self::getResult(1,'','英文报关名不能为空');
				if(floor($e['DeclaredValue'][$j])!=$e['DeclaredValue'][$j])
					return self::getResult(1,'','申报金额需为整数');
				
				$totalweight+=$e['Weight'][$j]*$e['quantity'][$j];
				$totalPrice+=$e['DeclaredValue'][$j]*$e['quantity'][$j];
				$totalqty++;
				$Contents.=$e['EName'][$j].';';
				
				$ExportLineItem.="<ExportLineItem>
									<LineNumber>".($j+1)."</LineNumber>
									<Quantity>".$e['quantity'][$j]."</Quantity>
									<QuantityUnit>PCS</QuantityUnit>
									<Description>".$e['EName'][$j]."</Description>
									<Value>".$e['DeclaredValue'][$j]."</Value>
									<IsDomestic>Y</IsDomestic>
									<Weight>
										<Weight>".$e['Weight'][$j]."</Weight>
										<WeightUnit>K</WeightUnit>
									</Weight>
									<GrossWeight>
										<Weight>".$e['Weight'][$j]."</Weight>
										<WeightUnit>K</WeightUnit>
									</GrossWeight>
									<ManufactureCountryName>CN</ManufactureCountryName>
								</ExportLineItem>";
			}
			
			$PublicPackage='<Pieces><Piece>
							<PieceID>1</PieceID>
							<Weight>'.$totalweight.'</Weight>';
			if(!empty($e['Width']) && !empty($e['Height']) && !empty($e['Depth']))
				$PublicPackage.='<Width>'.$e['Width'].'</Width><Height>'.$e['Height'].'</Height><Depth>'.$e['Depth'].'</Depth>';
			$PublicPackage.='</Piece></Pieces>';
			

			if(empty($order->consignee))
				return self::getResult(1,'','买家姓名不能为空');
			if(empty($order->consignee_country_code))
				return self::getResult(1,'','收件人国家不能为空');
			if(empty($order->consignee_city))
				return self::getResult(1,'','城市不能为空');
			if(empty($order->consignee_postal_code))
				return self::getResult(1,'','邮编不能为空');
			if(empty($order->consignee_phone))
				return self::getResult(1,'','电话不能为空');
			if(empty($order->consignee_address_line1))
				return self::getResult(1,'','地址1不能为空');
			if(!empty($e['InsuredAmount']) && $e['InsuredAmount']>$totalPrice)
				return self::getResult(1,'','保险价值不能大于申报价值');
			
			if(strlen($addressAndPhone['address_line1'])>35 || strlen($addressAndPhone['address_line2'])>35 || strlen($addressAndPhone['address_line3'])>35)
				return self::getResult(1,'','收件人地址信息长于35个字符，不能上传');
			
			if(strlen($shippingfrom['street_en'])>35)
				return self::getResult(1,'','发件人地址信息长于35个字符，不能上传');
				
			
			$format="8X4_PDF";    //纸张大小
			if(!empty($carrier_params['format']))
				$format = $carrier_params['format'];
			
// 			if(strlen($Contents)>88){
// 				$Contents=substr($Contents,0,88);
// 			}
			
// print_r($order);die;
/*
$MessageReference=self::rand(32,$customer_number);
			$request='<?xml version="1.0" encoding="UTF-8"?>
			<req:ShipmentRequest xmlns:req="http://www.dhl.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dhl.com ship-val-global-req.xsd" schemaVersion="4.0">
	<Request>
		<ServiceHeader>
			<MessageTime>'.date('Y-m-d',time()).'T'.date('H:i:s',time()).'</MessageTime>
			<MessageReference>'.$MessageReference.'</MessageReference>
			<SiteID>'.$this->SiteID.'</SiteID>
			<Password>'.$this->Pwd.'</Password>
		</ServiceHeader>
	</Request>
	<RegionCode>AP</RegionCode>
	<LanguageCode>en</LanguageCode>
	<PiecesEnabled>Y</PiecesEnabled>
	<Billing>
		<ShipperAccountNumber>'.$this->ShipperAccountNumber.'</ShipperAccountNumber>
		<ShippingPaymentType>S</ShippingPaymentType>
		<DutyPaymentType>R</DutyPaymentType>
	</Billing>
	<Consignee>
		<CompanyName>'.(empty($order->consignee_company)?$order->consignee:$order->consignee_company).'</CompanyName>
		<AddressLine><![CDATA['.$addressAndPhone['address_line1'].']]></AddressLine>
		<AddressLine><![CDATA['.(empty($addressAndPhone['address_line2'])?$order->consignee:$addressAndPhone['address_line2']).']]></AddressLine>
		<AddressLine><![CDATA['.$addressAndPhone['address_line3'].']]></AddressLine> 
		<City><![CDATA['.$order->consignee_city.']]></City>
		<DivisionCode>'.$order->consignee_province.'</DivisionCode>
		<PostalCode>'.$order->consignee_postal_code.'</PostalCode>
		<CountryCode>'.($order->consignee_country_code=='UK'?'GB':$order->consignee_country_code).'</CountryCode>
		<CountryName>'.$order->consignee_country.'</CountryName>
		<Contact>
			<PersonName><![CDATA['.$order->consignee.']]></PersonName>
			<PhoneNumber>'.$order->consignee_phone.'</PhoneNumber>
			<PhoneExtension></PhoneExtension>
			<FaxNumber>'.$order->consignee_fax.'</FaxNumber>
			<Telex></Telex>
			<Email>'.$order->consignee_email.'</Email>';
			if(!empty($order->consignee_mobile))
				$request.='<MobilePhoneNumber>'.$order->consignee_mobile.'</MobilePhoneNumber>';
	$request.='</Contact>
	</Consignee>
	<Dutiable>
		<DeclaredValue>'.round($totalPrice,2).'</DeclaredValue>
		<DeclaredCurrency>USD</DeclaredCurrency>
		<ShipperEIN>Text</ShipperEIN>	
	</Dutiable>
	<Reference>
		<ReferenceID>'.$customer_number.'</ReferenceID>
		<ReferenceType>st</ReferenceType>
	</Reference>
	<ShipmentDetails>
		<NumberOfPieces>1</NumberOfPieces>
				'.$PublicPackage.'
		<Weight>'.$totalweight.'</Weight>
		<WeightUnit>K</WeightUnit>
		<GlobalProductCode>P</GlobalProductCode>
		<LocalProductCode>P</LocalProductCode>
		<Date>'.date('Y-m-d',time()).'</Date>
		<Contents><![CDATA['.$Contents.']]></Contents>
		<DoorTo>DD</DoorTo>
		<DimensionUnit>C</DimensionUnit>';
		if(!empty($e['InsuredAmount']))
				$request.='<InsuredAmount>'.round($e['InsuredAmount'],2).'</InsuredAmount>';
		$request.='<CurrencyCode>CNY</CurrencyCode>
	</ShipmentDetails>
	<Shipper>
		<ShipperID>'.$this->ShipperAccountNumber.'</ShipperID>
		<CompanyName><![CDATA['.(empty($shippingfrom['company_en'])?$shippingfrom['contact_en']:$shippingfrom['company_en']).']]></CompanyName>
		<AddressLine><![CDATA['.$shippingfrom['street_en'].']]></AddressLine>
		<AddressLine><![CDATA['.(isset($shippingfrom['street_en2'])?$shippingfrom['street_en2']:'').']]></AddressLine>
		<AddressLine><![CDATA['.(isset($shippingfrom['street_en3'])?$shippingfrom['street_en3']:'').']]></AddressLine>
		<City><![CDATA['.$shippingfrom['city_en'].']]></City>
		<DivisionCode><![CDATA['.$shippingfrom['province_en'].']]></DivisionCode>
		<PostalCode>'.$shippingfrom['postcode'].'</PostalCode>
		<CountryCode>'.$shippingfrom['country'].'</CountryCode>
		<CountryName>'.$shippingfrom['country'].'</CountryName>
		<Contact>
			<PersonName><![CDATA['.$shippingfrom['contact'].']]></PersonName>
			<PhoneNumber>'.$shippingfrom['phone'].'</PhoneNumber>
			<PhoneExtension></PhoneExtension>
			<FaxNumber>'.$shippingfrom['fax'].'</FaxNumber>
			<Telex></Telex>
			<Email>'.$shippingfrom['email'].'</Email>
		</Contact>
	</Shipper>';
	if(!empty($e['InsuredAmount']))
		$request.='	<SpecialService><SpecialServiceType>I</SpecialServiceType></SpecialService>';
	$request.='
	<LabelImageFormat>PDF</LabelImageFormat> 
<Label>			
	<LabelTemplate>'.$format.'</LabelTemplate>		
</Label>			
			
</req:ShipmentRequest>';
*/
			
//发票内容
$ExportDeclaration='<ExportDeclaration>		
		<SignatureName><![CDATA['.$shippingfrom['contact'].']]></SignatureName>
		<SignatureTitle></SignatureTitle>
		<ExportReason></ExportReason>
		<ExportReasonCode>'.$e['ExportReasonCode'].'</ExportReasonCode>
		<InvoiceNumber>'.$customer_number.'</InvoiceNumber>
		<InvoiceDate>'.date("Y-m-d",time()).'</InvoiceDate>
		<BillToCompanyName><![CDATA['.(empty($shippingfrom['company_en'])?$shippingfrom['contact_en']:$shippingfrom['company_en']).']]></BillToCompanyName>
		<BillToContanctName><![CDATA['.$shippingfrom['contact'].']]></BillToContanctName>
		<BillToAddressLine><![CDATA['.$shippingfrom['street_en'].']]></BillToAddressLine>
		<BillToCity><![CDATA['.$shippingfrom['city_en'].']]></BillToCity>
		<BillToPostcode>'.$shippingfrom['postcode'].'</BillToPostcode>
		<BillToSuburb></BillToSuburb>
		<BillToState><![CDATA['.$shippingfrom['province_en'].']]></BillToState>
		<BillToCountryName>'.$shippingfrom['country'].'</BillToCountryName>
		<BillToPhoneNumber>'.$shippingfrom['phone'].'</BillToPhoneNumber>
		<BillToPhoneNumberExtn></BillToPhoneNumberExtn>';
		if(!empty($shippingfrom['fax']))
			$ExportDeclaration.='<BillToFaxNumber>'.$shippingfrom['fax'].'</BillToFaxNumber>';
		$ExportDeclaration.='<BillToFederalTaxID></BillToFederalTaxID>
		<Remarks></Remarks>
		'.$ExportLineItem.'
	</ExportDeclaration>';			
			
$MessageReference=self::rand(32,$customer_number);
			$request='<?xml version="1.0" encoding="UTF-8"?>
			<req:ShipmentRequest xmlns:req="http://www.dhl.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.dhl.com ship-val-global-req.xsd" schemaVersion="6.1">
	<Request>
		<ServiceHeader>
			<MessageTime>'.date('Y-m-d',time()).'T'.date('H:i:s',time()).'</MessageTime>
			<MessageReference>'.$MessageReference.'</MessageReference>
			<SiteID>'.$this->SiteID.'</SiteID>
			<Password>'.$this->Pwd.'</Password>
		</ServiceHeader>
		<MetaData>
			<SoftwareName>'.self::$SoftwareName.'</SoftwareName>
			<SoftwareVersion>'.self::$SoftwareVersion.'</SoftwareVersion>
		</MetaData>
	</Request>
	<RegionCode>AP</RegionCode>
	<LanguageCode>en</LanguageCode>
	<PiecesEnabled>Y</PiecesEnabled>
	<Billing>
		<ShipperAccountNumber>'.$this->ShipperAccountNumber.'</ShipperAccountNumber>
		<ShippingPaymentType>S</ShippingPaymentType>
		<DutyPaymentType>R</DutyPaymentType>
	</Billing>
	<Consignee>
		<CompanyName>'.(empty($order->consignee_company)?$order->consignee:$order->consignee_company).'</CompanyName>
		<AddressLine><![CDATA['.$addressAndPhone['address_line1'].']]></AddressLine>
		<AddressLine><![CDATA['.(empty($addressAndPhone['address_line2'])?$order->consignee:$addressAndPhone['address_line2']).']]></AddressLine>
		<AddressLine><![CDATA['.$addressAndPhone['address_line3'].']]></AddressLine>
		<City><![CDATA['.$order->consignee_city.']]></City>
		<DivisionCode>'.$order->consignee_province.'</DivisionCode>
		<PostalCode>'.$order->consignee_postal_code.'</PostalCode>
		<CountryCode>'.($order->consignee_country_code=='UK'?'GB':$order->consignee_country_code).'</CountryCode>
		<CountryName>'.$order->consignee_country.'</CountryName>
		<Contact>
			<PersonName><![CDATA['.$order->consignee.']]></PersonName>
			<PhoneNumber>'.$order->consignee_phone.'</PhoneNumber>
			<PhoneExtension></PhoneExtension>';
			if(!empty($order->consignee_fax))
				$request.='<FaxNumber>'.$order->consignee_fax.'</FaxNumber>';
			$request.='<Telex></Telex>
			<Email>'.$order->consignee_email.'</Email>';
			if(!empty($order->consignee_mobile))
				$request.='<MobilePhoneNumber>'.$order->consignee_mobile.'</MobilePhoneNumber>';
			$request.='</Contact>
	</Consignee>
	<Dutiable>
		<DeclaredValue>'.round($totalPrice,2).'</DeclaredValue>
		<DeclaredCurrency>USD</DeclaredCurrency>
		<ShipperEIN>Text</ShipperEIN>
	</Dutiable>
	<UseDHLInvoice>Y</UseDHLInvoice>
	<DHLInvoiceLanguageCode>en</DHLInvoiceLanguageCode>
	<DHLInvoiceType>'.$e['DHLInvoiceType'].'</DHLInvoiceType>
	'.$ExportDeclaration.'
	<Reference>
		<ReferenceID>'.$customer_number.'</ReferenceID>
	</Reference>
	<ShipmentDetails>
		<NumberOfPieces>1</NumberOfPieces>
				'.$PublicPackage.'
		<Weight>'.$totalweight.'</Weight>
		<WeightUnit>K</WeightUnit>
		<GlobalProductCode>P</GlobalProductCode>
		<LocalProductCode>P</LocalProductCode>
		<Date>'.date('Y-m-d',time()).'</Date>
		<Contents><![CDATA['.$Contents.']]></Contents>
		<DoorTo>DD</DoorTo>
		<DimensionUnit>C</DimensionUnit>';
			if(!empty($e['InsuredAmount']))
				$request.='<InsuredAmount>'.round($e['InsuredAmount'],2).'</InsuredAmount>';
			$request.='<PackageType>EE</PackageType>
						<IsDutiable>Y</IsDutiable>
					<CurrencyCode>CNY</CurrencyCode>
	</ShipmentDetails>
	<Shipper>
		<ShipperID>'.$this->ShipperAccountNumber.'</ShipperID>
		<CompanyName><![CDATA['.(empty($shippingfrom['company_en'])?$shippingfrom['contact_en']:$shippingfrom['company_en']).']]></CompanyName>
		<AddressLine><![CDATA['.$shippingfrom['street_en'].']]></AddressLine>
		<AddressLine><![CDATA['.(isset($shippingfrom['street_en2'])?$shippingfrom['street_en2']:'').']]></AddressLine>
		<AddressLine><![CDATA['.(isset($shippingfrom['street_en3'])?$shippingfrom['street_en3']:'').']]></AddressLine>
		<City><![CDATA['.$shippingfrom['city_en'].']]></City>
		<DivisionCode><![CDATA['.$shippingfrom['province_en'].']]></DivisionCode>
		<PostalCode>'.$shippingfrom['postcode'].'</PostalCode>
		<CountryCode>'.$shippingfrom['country'].'</CountryCode>
		<CountryName>'.$shippingfrom['country'].'</CountryName>
		<Contact>
			<PersonName><![CDATA['.$shippingfrom['contact'].']]></PersonName>
			<PhoneNumber>'.$shippingfrom['phone'].'</PhoneNumber>
			<PhoneExtension></PhoneExtension>';
			if(!empty($shippingfrom['fax']))
				$request.='<FaxNumber>'.$shippingfrom['fax'].'</FaxNumber>';
			$request.='<Telex></Telex>
			<Email>'.$shippingfrom['email'].'</Email>
		</Contact>
	</Shipper>
	<SpecialService>
		<SpecialServiceType>WY</SpecialServiceType>';
			if(!empty($e['InsuredAmount']))
				$request.='	<SpecialServiceType>I</SpecialServiceType>';
			$request.='
	</SpecialService>
	<LabelImageFormat>PDF</LabelImageFormat>
<Label>
	<LabelTemplate>'.$format.'</LabelTemplate>
</Label>
		
</req:ShipmentRequest>';	
			

	\Yii::info('LB_DHLEXPRESS,puid:'.$puid.',request,order_id:'.$order->order_id.' '.$request,"carrier_api");
			//数据组织完成 准备发送
			#########################################################################
			$post_head = array('Content-Type:text/xml;charset=UTF-8');
			$response = Helper_Curl::post(self::$wsdl,$request,$post_head);
			\Yii::info('LB_DHLEXPRESS,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$response,"carrier_api");
// 			print_r($response);die();						
			$response = utf8_encode($response);
			$string=Helper_xml::xmlparse($response);
			$channelArr=json_decode(json_encode((array) $string), true);
				
//     print_r($channelArr);die();
			if(isset($channelArr['Note']['ActionNote']) && $channelArr['Note']['ActionNote']=='Success'){
				$serviceNumber=$channelArr['AirwayBillNumber'];

				$t=$channelArr['LabelImage']['OutputImage'];
				$t_j=base64_decode($t);
				$pdfUrl = CarrierAPIHelper::savePDF2($t_j,$puid,$order->order_id."_packageLabel_".time());   //原本文件名$order->order_id.$customer_number."_packageLabel_".time()
				
				$i=$channelArr['LabelImage']['MultiLabels']['MultiLabel']['DocImageVal'];
				$i_j=base64_decode($i);
				$pdfUrl2 = CarrierAPIHelper::savePDF2($i_j,$puid,$order->order_id."_invoiceLabel_".time());

				$orderLabelPdfPath='';
				if($pdfUrl['error'] == 0){
					$orderLabelPdfPath = $pdfUrl['filePath'];
				}
				$invoiceLabelPdfPath='';
				if($pdfUrl2['error'] == 0){
					$invoiceLabelPdfPath = $pdfUrl2['filePath'];
				}

				if(!empty($orderLabelPdfPath)){
					$print_param = array();
					$print_param['carrier_code'] = $service->carrier_code;
					$print_param['api_class'] = 'LB_DHLEXPRESSCarrierAPI';
					$print_param['label_api_file_path'] = $orderLabelPdfPath;
					$print_param['run_status'] = 4;
						
					try{
						CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
					}catch (\Exception $ex){
					}
				}
				
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$serviceNumber,['packageLabelPath'=>$orderLabelPdfPath,'invoiceLabelPdfPath'=>$invoiceLabelPdfPath]);
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$serviceNumber);
			}
			else{
				if(empty($channelArr))
					return self::getResult(1,'','网络不稳定,请稍后重试');
					
				$channelArr=$channelArr['Response']['Status']['Condition'];		
				foreach ($channelArr as $key=>$channelArrone){
					if($key===0){
						$ConditionCode=$channelArrone['ConditionCode'];
						$ConditionData=$channelArrone['ConditionData'];
						break;
					}
					else if($key=='ConditionCode')
						$ConditionCode=$channelArrone;
					else if($key=='ConditionData')
						$ConditionData=$channelArrone;
				}
				
				return self::getResult(1,'',$ConditionCode.': '.$ConditionData);
			}
			
		}catch(\Exception $e){return self::getResult(1,'',$e->getMessage());}
	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/12/15				初始化
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消已确定的物流单。');
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
	 * @author		lgw		2016/12/15				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$pdf = new PDFMerger();

			$order = current($data);reset($data);
			$order = $order['order'];
			$checkResult = CarrierAPIHelper::validate(1,1,$order);
			$shipped = $checkResult['data']['shipped'];
			$puid = $checkResult['data']['puid'];
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$account_api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			//获取请求信息
			$this->SiteID = $account_api_params['SiteID'];
			$this->Pwd = $account_api_params['Password'];
			
			$basepath = Yii::getAlias('@webroot');
			
			foreach ($data as $k=>$v) {
				$oneOrder = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
				$shipped = $checkResult['data']['shipped'];
				
				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}

				if(!isset($shipped->return_no['packageLabelPath']))
					return self::getResult(1,'','打印失败，不是通过小老板上传的订单没有面单返回');
				else
					$pdf->addPDF($basepath.$shipped->return_no['packageLabelPath'],'all');
				
				$oneOrder->is_print_carrier = 1;
				$oneOrder->print_carrier_operator = $puid;
				$oneOrder->printtime = time();
				$oneOrder->save();
			}

			$pdfUrl = CarrierAPIHelper::savePDF('1',$puid,$account->carrier_code.'_', 0);
			
			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl='';
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			return null;
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	private function rand($i,$txt=''){
		preg_match_all('/\d+/',$txt,$arr);
		$arr = join('',$arr[0]);

		$t_txt=$arr.date('YmdHis',time());
		$j=$i-strlen($t_txt);
		 
		if(strlen($t_txt)>$i){
			$result=substr($arr,0, $j).date('YmdHis',time());
		}
		else {
			$result=$t_txt;
			for($t=0;$t<$j;$t++)
				$result.=rand(0, 9);
		}
		
		return $result;
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