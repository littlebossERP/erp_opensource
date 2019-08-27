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



class LB_AIPAQICarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	static private $wsdlpf = '';	// 物流打单接口
	private $submitGate = null;	// SoapClient实例
	
	public $customerNumber = null;
	public $appKey = null;
	
	public function __construct(){
		self::$wsdl = 'http://api.aprche.net/OpenWebService.asmx?WSDL';
		self::$wsdlpf = 'http://api.aprche.net/PrintFaceSingle.aspx';
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/13				初始化
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

			//获取到帐号中的认证参数
			$a = $account->api_params;

			$this->appSecret = $a['appSecret'];
			$this->appKey = $a['appKey'];
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 500,
							'consignee_address_line2_limit' => 500,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			//查找国家信息
			$countryArr=$this->loadCountry();  
// 			$channelArr=json_decode(json_encode((array) simplexml_load_string($countryArr['data'])), true);
			$channelArr=$countryArr['CountryInfoList']['CountryInfo'];
			unset($countryArr);
			$countryCode=$order->consignee_country_code;
			foreach ($channelArr as $countryVal){
				if(array_search($countryCode,$countryVal)!=false){
					$countryArr=$countryVal;
					break;
				}
			}
			
			if(empty($countryArr))
				return self::getResult(1,'','该运输方式不支持该国家');
					
			$CustomsInfoList='<CustomsInfoList>';
			foreach ($order->items as $j=>$vitem){
				$CustomsInfoList=$CustomsInfoList."<CustomsInfo>";
				$CustomsInfoList=$CustomsInfoList."<ProductName_CN>".$e['Name'][$j]."</ProductName_CN>";    //商品中文名
				$CustomsInfoList=$CustomsInfoList."<ProductName_EN>".$e['EName'][$j]."</ProductName_EN>";        //商品英文名
				$CustomsInfoList=$CustomsInfoList."<DeclareQuantity>".$e['DeclarePieces'][$j]."</DeclareQuantity>";	//申报数量
				$CustomsInfoList=$CustomsInfoList."<DeclarePrice>".$e['DeclaredValue'][$j]."</DeclarePrice>";	//申报价值
				$CustomsInfoList=$CustomsInfoList."<CustomsCode>".$e['customsCode'][$j]."</CustomsCode>";	//报关编号
				$CustomsInfoList=$CustomsInfoList."<CustomsNote>".$e['customsNote'][$j]."</CustomsNote>";	//报关备注
				$CustomsInfoList=$CustomsInfoList."<ProductInfo></ProductInfo>";	//配货信息
				$CustomsInfoList=$CustomsInfoList."<ProductSKU>".$e['diPickName'][$j]."</ProductSKU>";	//商品SKU
				$CustomsInfoList=$CustomsInfoList."<ProductURL></ProductURL>";	//商品链接
				$CustomsInfoList=$CustomsInfoList."<ProductPicURL></ProductPicURL>";	//商品图片链接
				$CustomsInfoList=$CustomsInfoList.'</CustomsInfo>';
			}
			$CustomsInfoList=$CustomsInfoList."</CustomsInfoList>";
			
			//法国没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(($order->consignee_country_code == 'FR') && empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			$xmlstr="<InsertWaybillService><WaybillInfo>";
			$xmlstr=$xmlstr."<CustomerWaybillNumber>".$customer_number."</CustomerWaybillNumber>" //客户订单号
					."<ServiceNumber></ServiceNumber>"            //追踪单号
					."<ForecastWeight>".strval(floatval($e['forecastWeight'])/1000)."</ForecastWeight>"   //预报重量
					."<ForecastLong>".$e['forecastLong']."</ForecastLong>"    //预报长
					."<ForecastWide>".$e['forecastWide']."</ForecastWide>"   //预报宽
					."<ForecastHigh>".$e['forecastHigh']."</ForecastHigh>"    //预报高
					."<FreightWayId>".$service->shipping_method_code."</FreightWayId>"   //运输渠道编号
					."<ParcelCategoryCode>".$carrier_params['parcelCategoryCode']."</ParcelCategoryCode>"   //包裹类型
					."<BuyerCode>".str_replace('&', '', $order->source_buyer_user_id)."</BuyerCode>"   //买家ID
					."<BuyerFullName>".$order->consignee."</BuyerFullName>"     //买家全名
					."<BuyerCompany>".$order->consignee_company."</BuyerCompany>"   //买家公司
					."<BuyerPhone>".$order->consignee_phone."</BuyerPhone>"     //买家电话
					."<BuyerMobile>".$order->consignee_mobile."</BuyerMobile>"  //买家手机
					."<BuyerFax>".$order->consignee_fax."</BuyerFax>"      //买家传真
					."<BuyerEmail>".$order->consignee_email."</BuyerEmail>"     //买家邮箱
					."<BuyerZipCode>".$order->consignee_postal_code."</BuyerZipCode>"   //买家邮编
					."<BuyerCountryId>".$countryArr['CountryId']."</BuyerCountryId>" //买家国家
					."<BuyerCountryName_CN>".$countryArr['CountryName_CN']."</BuyerCountryName_CN>"  //买家国家中文名
					."<BuyerCountryName_EN>".$countryArr['CountryName_EN']."</BuyerCountryName_EN>"    //买家国家英文名
					."<BuyerCountryCode>".$order->consignee_country_code."</BuyerCountryCode>"  //买家国家代码
					."<BuyerState>".$tmpConsigneeProvince."</BuyerState>"  //买家州省
					."<BuyerCity>".$order->consignee_city."</BuyerCity>" //买家城市
					."<BuyerCounty>".$order->consignee_district."</BuyerCounty>"    //买家区县
					."<BuyerAddress>".$addressAndPhone['address_line1']."</BuyerAddress>"  //买家地址
					."<BuyerAddress1>".$addressAndPhone['address_line2']."</BuyerAddress1>"  //买家地址1
					."<InsureStatus></InsureStatus>"      //投保状态
					."<FreightWayInsureId></FreightWayInsureId>"  //渠道保险ID
					."<InsureValue></InsureValue>"; //投保价值
			$xmlstr=$xmlstr.$CustomsInfoList."</WaybillInfo></InsertWaybillService>";
// print_r($xmlstr);die();
			//ToKenCategory授权类别7为小老板
			$request = array(
					'AppKey'=>$this->appKey,
					'AppSecret'=>$this->appSecret,
					'ToKenCategory'=>'7',
					'WaybillnfoXml'=>$xmlstr,
			);

			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($request,1),"file");
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'Insure_Waybill');
			
			\Yii::info('LB_AIPAQICarrierAPI1 puid:'.$puid.'，order_id:'.$order->order_id.'  '.print_r($response,1), "file");
//     print_r($response);die();
			if($response['error']){return self::getResult(1,'',$response['msg']);}
			$response = $response['data']->Insure_WaybillResult->any;
			
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);

			$result=$channelArr['WaybillInfoList']['WaybillInfo']['Result'];  //返回的信息	
			if(strstr($result,'商品总重量') && strstr($result,'超过限定值')){
				return self::getResult(1,'','商品总重量超过限定值');
			}
			if(strstr($result,'订单验证错误') && strstr($result,'cvc-maxInclusive-valid')){
				return self::getResult(1,'','商品总重量超过限定值');
			}
			if(strstr($result,'订单验证错误') && strstr($result,'商品中文名')){
				return self::getResult(1,'','商品中文名含有不规则字符');
			}
			if(!strstr($result,'创建并确认订单成功')){        //未知错误
				return self::getResult(1,'',$result);
			}

			if(strstr($result,"创建并确认订单成功")){
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
				$serviceNumber=$channelArr['WaybillInfoList']['WaybillInfo']['ServiceNumber'];
				if(empty($serviceNumber)) //可能不返回跟踪号
					$serviceNumber=null;
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$serviceNumber,['OrderSign'=>$channelArr['WaybillInfoList']['WaybillInfo']['WaybillId']]);
	
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$serviceNumber);
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 * 接口只能删除草稿的订单，所以该方法暂时没有用
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/13				初始化
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
	 * @author		lgw		2016/04/13				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
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
			
			$format="1a2ac86a-2992-490c-aedb-80fcb29278d6";
			if(!empty($carrier_params['format'])){
				$format=$carrier_params['format'];
			}
			//获取请求信息
			$this->appSecret = $account_api_params['appSecret'];
			$this->appKey = $account_api_params['appKey'];
			$this->faceSingleId=$format;
			$this->freightWayId=$service['shipping_method_code'];
			//$this->serviceNumber=$shipped['tracking_number'];
			
			
			$tmpBarcodeStr = '';
			
			//获取追踪号
			foreach ($data as $k=>$v) {
				$order = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];

				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$order->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}
				
				$tmpBarcodeStr.="<ServiceNumber>".$shipped['tracking_number']."</ServiceNumber>";
			}
			
			// 					<?xml version='1.0' encoding='UTF-8'? >
			$getorder_xml="
						<WaybillPrintService>
						<AppKey>".$this->appKey."</AppKey>
						<AppSecret>".$this->appSecret."</AppSecret>
						<FaceSingleId>".$this->faceSingleId."</FaceSingleId>
						<FreightWayId>".$this->freightWayId."</FreightWayId>
						<ServiceNumberList>
						".$tmpBarcodeStr."
						</ServiceNumberList>
						</WaybillPrintService>";	
			
			//print_r($getorder_xml);die();
			
			$header=array();
			$header[]='Content-Type:text/xml;charset=utf-8';

			//直接返回一个网页
			$response = Helper_Curl::post(self::$wsdlpf,$getorder_xml,$header);
			return self::getResult(0,$response,'');

		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取国家信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/13				初始化
	 +----------------------------------------------------------
	 **/
	public function loadCountry(){
		//有时请求失败，所以直接返回一个数组去获取
		$str = <<<EOF
{"Result":"\u4fe1\u606f\u83b7\u53d6\u6210\u529f","CountryInfoList":{"CountryInfo":[{"CountryId":"1","CountryName_CN":"\u5fb7\u56fd","CountryName_EN":"GERMANY","CountryCode":"DE"},{"CountryId":"2","CountryName_CN":"\u82f1\u56fd","CountryName_EN":"UNITED KINGDOM","CountryCode":"GB"},{"CountryId":"3","CountryName_CN":"\u897f\u73ed\u7259","CountryName_EN":"SPAIN","CountryCode":"ES"},{"CountryId":"4","CountryName_CN":"\u7f8e\u56fd","CountryName_EN":"UNITED STATES","CountryCode":"US"},{"CountryId":"5","CountryName_CN":"\u52a0\u62ff\u5927","CountryName_EN":"CANADA","CountryCode":"CA"},{"CountryId":"6","CountryName_CN":"\u610f\u5927\u5229","CountryName_EN":"ITALY","CountryCode":"IT"},{"CountryId":"7","CountryName_CN":"\u6cd5\u56fd","CountryName_EN":"FRANCE","CountryCode":"FR"},{"CountryId":"8","CountryName_CN":"\u65e5\u672c","CountryName_EN":"JAPAN","CountryCode":"JP"},{"CountryId":"9","CountryName_CN":"\u4fc4\u7f57\u65af","CountryName_EN":"RUSSIAN","CountryCode":"RU"},{"CountryId":"10","CountryName_CN":"\u8377\u5170","CountryName_EN":"NETHERLANDS","CountryCode":"NL"},{"CountryId":"11","CountryName_CN":"\u6fb3\u5927\u5229\u4e9a","CountryName_EN":"AUSTRALIA","CountryCode":"AU"},{"CountryId":"12","CountryName_CN":"\u963f\u5bcc\u6c57","CountryName_EN":"AFGHANISTAN","CountryCode":"AF"},{"CountryId":"13","CountryName_CN":"\u963f\u5c14\u5df4\u5c3c\u4e9a","CountryName_EN":"ALBANIA","CountryCode":"AL"},{"CountryId":"14","CountryName_CN":"\u963f\u5c14\u53ca\u5229\u4e9a","CountryName_EN":"ALGERIA","CountryCode":"DZ"},{"CountryId":"15","CountryName_CN":"\u5b89\u572d\u62c9\u5c9b","CountryName_EN":"ANGUILLA","CountryCode":"AI"},{"CountryId":"16","CountryName_CN":"\u7f8e\u5c5e\u8428\u6469\u4e9a\u7fa4\u5c9b","CountryName_EN":"AMERICAN SAMOA","CountryCode":"AS"},{"CountryId":"17","CountryName_CN":"\u5b89\u9053\u5c14","CountryName_EN":"ANDORRA","CountryCode":"AD"},{"CountryId":"18","CountryName_CN":"\u5b89\u54e5\u62c9","CountryName_EN":"ANGOLA","CountryCode":"AO"},{"CountryId":"19","CountryName_CN":"\u5b89\u63d0\u74dc\u53ca\u5df4\u5e03\u8fbe","CountryName_EN":"ANTIGUA AND BARBUDA","CountryCode":"AG"},{"CountryId":"20","CountryName_CN":"\u963f\u6839\u5ef7","CountryName_EN":"ARGENTINA","CountryCode":"AR"},{"CountryId":"21","CountryName_CN":"\u4e9a\u7f8e\u5c3c\u4e9a","CountryName_EN":"ARMENIA","CountryCode":"AM"},{"CountryId":"22","CountryName_CN":"\u963f\u9c81\u5df4\u5c9b","CountryName_EN":"ARUBA","CountryCode":"AW"},{"CountryId":"23","CountryName_CN":"\u963f\u68ee\u677e","CountryName_EN":"ASCENSION","CountryCode":"XD"},{"CountryId":"24","CountryName_CN":"\u5965\u5730\u5229","CountryName_EN":"AUSTRIA","CountryCode":"AT"},{"CountryId":"25","CountryName_CN":"\u963f\u585e\u62dc\u7586","CountryName_EN":"AZERBAIJAN","CountryCode":"AZ"},{"CountryId":"26","CountryName_CN":"\u4e9a\u901f\u5c14\u7fa4\u5c9b","CountryName_EN":"AZORES","CountryCode":"XH"},{"CountryId":"27","CountryName_CN":"\u5df4\u6797","CountryName_EN":"BAHRAIN","CountryCode":"BH"},{"CountryId":"28","CountryName_CN":"\u5df4\u54c8\u9a6c","CountryName_EN":"BAHAMAS","CountryCode":"BS"},{"CountryId":"29","CountryName_CN":"\u5df4\u5229\u963f\u91cc\u7fa4\u5c9b","CountryName_EN":"BALEARIC ISLANDS","CountryCode":"XJ"},{"CountryId":"30","CountryName_CN":"\u5b5f\u52a0\u62c9","CountryName_EN":"BANGLADESH","CountryCode":"BD"},{"CountryId":"31","CountryName_CN":"\u5df4\u5df4\u591a\u65af","CountryName_EN":"BARBADOS","CountryCode":"BB"},{"CountryId":"32","CountryName_CN":"\u767d\u4fc4\u7f57\u65af","CountryName_EN":"BELARUS","CountryCode":"BY"},{"CountryId":"33","CountryName_CN":"\u6bd4\u5229\u65f6","CountryName_EN":"BELGIUM","CountryCode":"BE"},{"CountryId":"34","CountryName_CN":"\u4f2f\u5229\u5179","CountryName_EN":"BELIZE","CountryCode":"BZ"},{"CountryId":"35","CountryName_CN":"\u8d1d\u5b81","CountryName_EN":"BENIN","CountryCode":"BJ"},{"CountryId":"36","CountryName_CN":"\u767e\u6155\u8fbe","CountryName_EN":"BERMUDA","CountryCode":"BM"},{"CountryId":"37","CountryName_CN":"\u4e0d\u4e39","CountryName_EN":"BHUTAN","CountryCode":"BT"},{"CountryId":"38","CountryName_CN":"\u73bb\u5229\u7ef4\u4e9a","CountryName_EN":"BOLIVIA","CountryCode":"BO"},{"CountryId":"39","CountryName_CN":"\u6ce2\u65af\u5c3c\u4e9a-\u9ed1\u585e\u54e5\u7ef4\u90a3\u5171\u548c\u56fd","CountryName_EN":"BOSNIA AND HERZEGOVINA","CountryCode":"BA"},{"CountryId":"41","CountryName_CN":"\u535a\u8328\u74e6\u7eb3","CountryName_EN":"BOTSWANA","CountryCode":"BW"},{"CountryId":"42","CountryName_CN":"\u5e03\u7ef4\u5c9b","CountryName_EN":"BOUVET ISLAND","CountryCode":"BV"},{"CountryId":"43","CountryName_CN":"\u5df4\u897f","CountryName_EN":"BRAZIL","CountryCode":"BR"},{"CountryId":"44","CountryName_CN":"\u82f1\u5c5e\u5370\u5ea6\u6d0b\u5730\u533a(\u67e5\u5404\u7fa4\u5c9b)","CountryName_EN":"BRITISH INDIAN OCEAN TERRITORY","CountryCode":"IO"},{"CountryId":"45","CountryName_CN":"\u6587\u83b1","CountryName_EN":"BRUNEI","CountryCode":"BN"},{"CountryId":"46","CountryName_CN":"\u4fdd\u52a0\u5229\u4e9a","CountryName_EN":"BULGARIA","CountryCode":"BG"},{"CountryId":"47","CountryName_CN":"\u5e03\u57fa\u7eb3\u6cd5\u7d22","CountryName_EN":"BURKINA FASO","CountryCode":"BF"},{"CountryId":"48","CountryName_CN":"\u67ec\u57d4\u5be8","CountryName_EN":"CAMBODIA","CountryCode":"KH"},{"CountryId":"49","CountryName_CN":"\u5e03\u9686\u8fea","CountryName_EN":"BURUNDI","CountryCode":"BI"},{"CountryId":"50","CountryName_CN":"\u5580\u9ea6\u9686","CountryName_EN":"CAMEROON","CountryCode":"CM"},{"CountryId":"51","CountryName_CN":"\u52a0\u90a3\u5229\u7fa4\u5c9b","CountryName_EN":"CANARY ISLANDS","CountryCode":"IC"},{"CountryId":"52","CountryName_CN":"\u4f5b\u5f97\u89d2","CountryName_EN":"CAPE VERDE","CountryCode":"CV"},{"CountryId":"53","CountryName_CN":"\u52a0\u7f57\u6797\u7fa4\u5c9b","CountryName_EN":"CAROLINE ISLANDS","CountryCode":"XK"},{"CountryId":"54","CountryName_CN":"\u5f00\u66fc\u7fa4\u5c9b","CountryName_EN":"CAYMAN ISLANDS","CountryCode":"KY"},{"CountryId":"55","CountryName_CN":"\u4e2d\u975e\u5171\u548c\u56fd","CountryName_EN":"CENTRAL REPUBLIC","CountryCode":"CF"},{"CountryId":"56","CountryName_CN":"\u4e4d\u5f97","CountryName_EN":"CHAD","CountryCode":"TD"},{"CountryId":"57","CountryName_CN":"\u667a\u5229","CountryName_EN":"CHILE","CountryCode":"CL"},{"CountryId":"58","CountryName_CN":"\u5723\u8bde\u5c9b","CountryName_EN":"CHRISTMAS ISLAND","CountryCode":"CX"},{"CountryId":"59","CountryName_CN":"\u4e2d\u56fd","CountryName_EN":"CHINA","CountryCode":"CN"},{"CountryId":"60","CountryName_CN":"\u79d1\u79d1\u65af\u7fa4\u5c9b","CountryName_EN":"COCOS(KEELING)ISLANDS","CountryCode":"CC"},{"CountryId":"61","CountryName_CN":"\u54e5\u4f26\u6bd4\u4e9a","CountryName_EN":"COLOMBIA","CountryCode":"CO"},{"CountryId":"62","CountryName_CN":"\u79d1\u6469\u7f57","CountryName_EN":"COMOROS","CountryCode":"KM"},{"CountryId":"63","CountryName_CN":"\u54e5\u65af\u8fbe\u9ece\u52a0","CountryName_EN":"COSTA RICA","CountryCode":"CR"},{"CountryId":"64","CountryName_CN":"\u521a\u679c","CountryName_EN":"CONGO","CountryCode":"CG"},{"CountryId":"65","CountryName_CN":"\u5e93\u514b\u7fa4\u5c9b","CountryName_EN":"COOK ISLANDS","CountryCode":"CK"},{"CountryId":"66","CountryName_CN":"\u521a\u679c\uff08\u5e03\uff09","CountryName_EN":"CONGO REPUBLIC ","CountryCode":"CD"},{"CountryId":"67","CountryName_CN":"\u79d1\u7279\u8fea\u74e6(\u8c61\u7259\u6d77\u5cb8) ","CountryName_EN":"COTE D'LVOIRE(IVORY)","CountryCode":"CI"},{"CountryId":"68","CountryName_CN":"\u514b\u7f57\u5730\u4e9a","CountryName_EN":"CROATIA","CountryCode":"HR"},{"CountryId":"69","CountryName_CN":"\u5e93\u62c9\u7d22\u5c9b(\u8377\u5170)","CountryName_EN":"CURACAO","CountryCode":"RC"},{"CountryId":"70","CountryName_CN":"\u6377\u514b","CountryName_EN":"CZECH REPUBLIC","CountryCode":"CZ"},{"CountryId":"71","CountryName_CN":"\u53e4\u5df4","CountryName_EN":"CUBA","CountryCode":"CU"},{"CountryId":"72","CountryName_CN":"\u585e\u6d66\u8def\u65af","CountryName_EN":"CYPRUS","CountryCode":"CY"},{"CountryId":"73","CountryName_CN":"\u4e39\u9ea6","CountryName_EN":"DENMARK","CountryCode":"DK"},{"CountryId":"74","CountryName_CN":"\u5409\u5e03\u63d0","CountryName_EN":"DJIBOUTI","CountryCode":"DJ"},{"CountryId":"75","CountryName_CN":"\u591a\u7c73\u5c3c\u514b","CountryName_EN":"DOMINICA","CountryCode":"DM"},{"CountryId":"76","CountryName_CN":"\u591a\u7c73\u5c3c\u52a0\u5171\u5408\u56fd","CountryName_EN":"DOMINICAN REPUBLIC","CountryCode":"DO"},{"CountryId":"77","CountryName_CN":"\u5384\u74dc\u591a\u5c14","CountryName_EN":"ECUADOR","CountryCode":"EC"},{"CountryId":"78","CountryName_CN":"\u4e1c\u5e1d\u6c76","CountryName_EN":"EAST TIMOR","CountryCode":"TL"},{"CountryId":"79","CountryName_CN":"\u57c3\u53ca","CountryName_EN":"EGYPT","CountryCode":"EG"},{"CountryId":"80","CountryName_CN":"\u8428\u5c14\u74e6\u591a","CountryName_EN":"EL SALVADOR","CountryCode":"SV"},{"CountryId":"81","CountryName_CN":"\u8d64\u9053\u51e0\u5185\u4e9a","CountryName_EN":"EQUATORIAL GUINEA ","CountryCode":"GQ"},{"CountryId":"82","CountryName_CN":"\u5384\u91cc\u7279\u7acb\u4e9a","CountryName_EN":"ERITREA","CountryCode":"ER"},{"CountryId":"83","CountryName_CN":"\u7231\u6c99\u5c3c\u4e9a","CountryName_EN":"ESTONIA","CountryCode":"EE"},{"CountryId":"84","CountryName_CN":"\u57c3\u585e\u4fc4\u6bd4\u4e9a","CountryName_EN":"ETHIOPIA","CountryCode":"ET"},{"CountryId":"85","CountryName_CN":"\u798f\u514b\u5170\u7fa4\u5c9b","CountryName_EN":"FALKLAND ISLAND","CountryCode":"FK"},{"CountryId":"86","CountryName_CN":"\u6cd5\u7f57\u7fa4\u5c9b","CountryName_EN":"FAROE ISLANDS","CountryCode":"FO"},{"CountryId":"87","CountryName_CN":"\u6590\u6d4e","CountryName_EN":"FIJI","CountryCode":"FJ"},{"CountryId":"88","CountryName_CN":"\u82ac\u5170","CountryName_EN":"FINLAND","CountryCode":"FI"},{"CountryId":"89","CountryName_CN":"\u6cd5\u5c5e\u7f8e\u7279\u7f57\u6ce2\u5229\u5766","CountryName_EN":"FRANCE, METROPOLITAN","CountryCode":"FX"},{"CountryId":"90","CountryName_CN":"\u6cd5\u5c5e\u572d\u4e9a\u90a3","CountryName_EN":"FRENCH GUIANA","CountryCode":"GF"},{"CountryId":"91","CountryName_CN":"\u6cd5\u5c5e\u6ce2\u5229\u5c3c\u897f\u4e9a","CountryName_EN":"FRENCH POLYNESIA","CountryCode":"PF"},{"CountryId":"92","CountryName_CN":"\u6cd5\u5c5e\u5357\u90e8\u9886\u571f","CountryName_EN":"FRENCH SOUTHERN TERRITORIES","CountryCode":"TF"},{"CountryId":"93","CountryName_CN":"\u52a0\u84ec","CountryName_EN":"GABON","CountryCode":"GA"},{"CountryId":"94","CountryName_CN":"\u5188\u6bd4\u4e9a","CountryName_EN":"GAMBIA","CountryCode":"GM"},{"CountryId":"95","CountryName_CN":"\u76f4\u5e03\u7f57\u9640","CountryName_EN":"GIBRALTAR","CountryCode":"GI"},{"CountryId":"96","CountryName_CN":"\u52a0\u7eb3","CountryName_EN":"GHANA","CountryCode":"GH"},{"CountryId":"97","CountryName_CN":"\u683c\u9c81\u5409\u4e9a","CountryName_EN":"GEORGIA","CountryCode":"GE"},{"CountryId":"98","CountryName_CN":"\u5e0c\u814a","CountryName_EN":"GREECE","CountryCode":"GR"},{"CountryId":"99","CountryName_CN":"\u683c\u9675\u5170","CountryName_EN":"GREENLAND","CountryCode":"GL"},{"CountryId":"100","CountryName_CN":"\u683c\u6797\u7eb3\u8fbe","CountryName_EN":"GRENADA","CountryCode":"GD"},{"CountryId":"102","CountryName_CN":"\u5173\u5c9b","CountryName_EN":"GUAM","CountryCode":"GU"},{"CountryId":"103","CountryName_CN":"\u5371\u5730\u9a6c\u62c9","CountryName_EN":"GUATEMALA","CountryCode":"GT"},{"CountryId":"104","CountryName_CN":"\u6839\u897f\u5c9b","CountryName_EN":"GUERNSEY","CountryCode":"GG"},{"CountryId":"105","CountryName_CN":"\u51e0\u5185\u4e9a","CountryName_EN":"GUINEA ","CountryCode":"GN"},{"CountryId":"106","CountryName_CN":"\u51e0\u5185\u4e9a\u6bd4\u7ecd","CountryName_EN":"GUINEA BISSAU","CountryCode":"GW"},{"CountryId":"107","CountryName_CN":"\u6d77\u5730","CountryName_EN":"HAITI","CountryCode":"HT"},{"CountryId":"108","CountryName_CN":"\u572d\u4e9a\u90a3","CountryName_EN":"GUYANA (BRITISH)","CountryCode":"GY"},{"CountryId":"109","CountryName_CN":"\u8d6b\u5fb7\u5c9b\u548c\u9ea6\u514b\u5510\u7eb3\u5c9b","CountryName_EN":"HEARD ISLAND AND MCDONALD ISLANDS","CountryCode":"HM"},{"CountryId":"110","CountryName_CN":"\u6d2a\u90fd\u62c9\u65af","CountryName_EN":"HONDURAS","CountryCode":"HN"},{"CountryId":"111","CountryName_CN":"\u9999\u6e2f","CountryName_EN":"HONG KONG","CountryCode":"HK"},{"CountryId":"112","CountryName_CN":"\u5308\u7259\u5229","CountryName_EN":"HUNGARY","CountryCode":"HU"},{"CountryId":"113","CountryName_CN":"\u5370\u5ea6","CountryName_EN":"INDIA","CountryCode":"IN"},{"CountryId":"114","CountryName_CN":"\u51b0\u5c9b","CountryName_EN":"ICELAND","CountryCode":"IS"},{"CountryId":"115","CountryName_CN":"\u5370\u5ea6\u5c3c\u897f\u4e9a","CountryName_EN":"INDONESIA","CountryCode":"ID"},{"CountryId":"116","CountryName_CN":"\u4f0a\u6717","CountryName_EN":"IRAN (ISLAMIC REPUBLIC OF)","CountryCode":"IR"},{"CountryId":"117","CountryName_CN":"\u4f0a\u62c9\u514b","CountryName_EN":"IRAQ","CountryCode":"IQ"},{"CountryId":"118","CountryName_CN":"\u7231\u5c14\u5170","CountryName_EN":"IRELAND","CountryCode":"IE"},{"CountryId":"119","CountryName_CN":"\u4ee5\u8272\u5217","CountryName_EN":"ISRAEL","CountryCode":"IL"},{"CountryId":"120","CountryName_CN":"\u7259\u4e70\u52a0","CountryName_EN":"JAMAICA","CountryCode":"JM"},{"CountryId":"121","CountryName_CN":"\u6cfd\u897f\u5c9b(\u82f1\u5c5e)","CountryName_EN":"JERSEY","CountryCode":"JE"},{"CountryId":"122","CountryName_CN":"\u7ea6\u65e6","CountryName_EN":"JORDAN","CountryCode":"JO"},{"CountryId":"123","CountryName_CN":"\u54c8\u8428\u514b\u65af\u5766","CountryName_EN":"KAZAKHSTAN","CountryCode":"KZ"},{"CountryId":"124","CountryName_CN":"\u80af\u5c3c\u4e9a","CountryName_EN":"KENYA","CountryCode":"KE"},{"CountryId":"125","CountryName_CN":"\u57fa\u5229\u5df4\u65af\u5171\u548c\u56fd","CountryName_EN":"KIRIBATI REPUBILC","CountryCode":"KI"},{"CountryId":"126","CountryName_CN":"\u79d1\u7d22\u6c83","CountryName_EN":"KOSOVO","CountryCode":"KV"},{"CountryId":"127","CountryName_CN":"\u79d1\u5a01\u7279","CountryName_EN":"KUWAIT","CountryCode":"KW"},{"CountryId":"128","CountryName_CN":"\u8001\u631d","CountryName_EN":"LAOS","CountryCode":"LA"},{"CountryId":"129","CountryName_CN":"\u5409\u5c14\u5409\u65af\u65af\u5766","CountryName_EN":"KYRGYZSTAN","CountryCode":"KG"},{"CountryId":"130","CountryName_CN":"\u62c9\u8131\u7ef4\u4e9a","CountryName_EN":"LATVIA","CountryCode":"LV"},{"CountryId":"131","CountryName_CN":"\u9ece\u5df4\u5ae9","CountryName_EN":"LEBANON","CountryCode":"LB"},{"CountryId":"132","CountryName_CN":"\u83b1\u7d22\u6258","CountryName_EN":"LESOTHO","CountryCode":"LS"},{"CountryId":"133","CountryName_CN":"\u5229\u6bd4\u91cc\u4e9a","CountryName_EN":"LIBERIA","CountryCode":"LR"},{"CountryId":"134","CountryName_CN":"\u5229\u6bd4\u4e9a","CountryName_EN":"LIBYA","CountryCode":"LY"},{"CountryId":"135","CountryName_CN":"\u5217\u652f\u6566\u58eb\u767b","CountryName_EN":"LIECHTENSTEIN","CountryCode":"LI"},{"CountryId":"136","CountryName_CN":"\u7acb\u9676\u5b9b","CountryName_EN":"LITHUANIA","CountryCode":"LT"},{"CountryId":"137","CountryName_CN":"\u5362\u68ee\u5821","CountryName_EN":"LUXEMBOURG","CountryCode":"LU"},{"CountryId":"138","CountryName_CN":"\u6fb3\u95e8","CountryName_EN":"MACAU","CountryCode":"MO"},{"CountryId":"139","CountryName_CN":"\u9a6c\u5176\u987f","CountryName_EN":"MACEDONIA","CountryCode":"MK"},{"CountryId":"140","CountryName_CN":"\u9a6c\u8fbe\u52a0\u65af\u52a0","CountryName_EN":"MADAGASCAR","CountryCode":"MG"},{"CountryId":"141","CountryName_CN":"\u9a6c\u5fb7\u62c9\u5c9b","CountryName_EN":"MADEIRA","CountryCode":"XI"},{"CountryId":"142","CountryName_CN":"\u9a6c\u62c9\u7ef4","CountryName_EN":"MALAWI","CountryCode":"MW"},{"CountryId":"143","CountryName_CN":"\u9a6c\u6765\u897f\u4e9a","CountryName_EN":"MALAYSIA","CountryCode":"MY"},{"CountryId":"144","CountryName_CN":"\u9a6c\u5c14\u4ee3\u592b","CountryName_EN":"MALDIVES","CountryCode":"MV"},{"CountryId":"145","CountryName_CN":"\u9a6c\u91cc","CountryName_EN":"MALI","CountryCode":"ML"},{"CountryId":"146","CountryName_CN":"\u9a6c\u8033\u4ed6","CountryName_EN":"MALTA","CountryCode":"MT"},{"CountryId":"147","CountryName_CN":"\u9a6c\u7ecd\u5c14\u7fa4\u5c9b","CountryName_EN":"MARSHALL ISLANDS","CountryCode":"MH"},{"CountryId":"148","CountryName_CN":"\u9a6c\u63d0\u5c3c\u514b","CountryName_EN":"MARTINIQUE","CountryCode":"MQ"},{"CountryId":"149","CountryName_CN":"\u6bdb\u91cc\u5854\u5c3c\u4e9a","CountryName_EN":"MAURITANIA","CountryCode":"MR"},{"CountryId":"150","CountryName_CN":"\u9a6c\u7ea6\u7279","CountryName_EN":"MAYOTTE","CountryCode":"YT"},{"CountryId":"152","CountryName_CN":"\u58a8\u897f\u54e5","CountryName_EN":"MEXICO","CountryCode":"MX"},{"CountryId":"153","CountryName_CN":"\u5bc6\u514b\u7f57\u5c3c\u897f\u4e9a","CountryName_EN":"MICRONESIA","CountryCode":"FM"},{"CountryId":"154","CountryName_CN":"\u6469\u5c14\u591a\u74e6","CountryName_EN":"MOLDOVA","CountryCode":"MD"},{"CountryId":"155","CountryName_CN":"\u6469\u7eb3\u54e5","CountryName_EN":"MONACO","CountryCode":"MC"},{"CountryId":"156","CountryName_CN":"\u8499\u53e4","CountryName_EN":"MONGOLIA","CountryCode":"MN"},{"CountryId":"157","CountryName_CN":"\u9ed1\u5c71\u5171\u548c\u56fd","CountryName_EN":"MONTENEGRO","CountryCode":"MNE"},{"CountryId":"158","CountryName_CN":"\u8499\u7279\u585e\u62c9\u5c9b","CountryName_EN":"MONTSERRAT","CountryCode":"MS"},{"CountryId":"159","CountryName_CN":"\u6469\u6d1b\u54e5","CountryName_EN":"MOROCCO","CountryCode":"MA"},{"CountryId":"160","CountryName_CN":"\u83ab\u6851\u6bd4\u514b","CountryName_EN":"MOZAMBIQUE","CountryCode":"MZ"},{"CountryId":"161","CountryName_CN":"\u7eb3\u7c73\u6bd4\u4e9a","CountryName_EN":"NAMIBIA","CountryCode":"NA"},{"CountryId":"162","CountryName_CN":"\u5c3c\u6cca\u5c14","CountryName_EN":"NEPAL","CountryCode":"NP"},{"CountryId":"163","CountryName_CN":"\u7459\u9c81","CountryName_EN":"Nauru","CountryCode":"NR"},{"CountryId":"164","CountryName_CN":"\u7f05\u7538","CountryName_EN":"MYANMAR","CountryCode":"MM"},{"CountryId":"165","CountryName_CN":"\u8377\u5c5e\u5b89\u7684\u5217\u65af\u7fa4\u5c9b","CountryName_EN":"NETHERLANDS ANTILLES","CountryCode":"AN"},{"CountryId":"166","CountryName_CN":"\u5c3c\u7ef4\u65af\u5c9b","CountryName_EN":"NEVIS","CountryCode":"XN"},{"CountryId":"167","CountryName_CN":"\u65b0\u5580\u91cc\u591a\u5c3c\u4e9a","CountryName_EN":"NEW CALEDONIA","CountryCode":"NC"},{"CountryId":"168","CountryName_CN":"\u5c3c\u65e5\u5c14","CountryName_EN":"NIGER","CountryCode":"NE"},{"CountryId":"169","CountryName_CN":"\u65b0\u897f\u5170","CountryName_EN":"NEW ZEALAND","CountryCode":"NZ"},{"CountryId":"170","CountryName_CN":"\u5c3c\u52a0\u62c9\u74dc","CountryName_EN":"NICARAGUA","CountryCode":"NI"},{"CountryId":"171","CountryName_CN":"\u5c3c\u65e5\u5229\u4e9a","CountryName_EN":"NIGERIA","CountryCode":"NG"},{"CountryId":"172","CountryName_CN":"\u7ebd\u57c3\u5c9b","CountryName_EN":"NIUE","CountryCode":"NU"},{"CountryId":"173","CountryName_CN":"\u671d\u9c9c","CountryName_EN":"NORTH KOREA","CountryCode":"KP"},{"CountryId":"174","CountryName_CN":"\u8bfa\u8914\u514b\u5c9b","CountryName_EN":"NORFOLK ISLAND","CountryCode":"NF"},{"CountryId":"175","CountryName_CN":"\u632a\u5a01","CountryName_EN":"NORWAY","CountryCode":"NO"},{"CountryId":"176","CountryName_CN":"\u963f\u66fc","CountryName_EN":"OMAN","CountryCode":"OM"},{"CountryId":"177","CountryName_CN":"\u5df4\u57fa\u65af\u5766","CountryName_EN":"PAKISTAN","CountryCode":"PK"},{"CountryId":"178","CountryName_CN":"\u5e15\u52b3","CountryName_EN":"PALAU","CountryCode":"PW"},{"CountryId":"179","CountryName_CN":"\u5df4\u62ff\u9a6c","CountryName_EN":"PANAMA","CountryCode":"PA"},{"CountryId":"180","CountryName_CN":"\u5df4\u5e03\u4e9a\u65b0\u51e0\u5185\u4e9a","CountryName_EN":"PAPUA NEW GUINEA","CountryCode":"PG"},{"CountryId":"181","CountryName_CN":"\u5df4\u62c9\u572d","CountryName_EN":"PARAGUAY","CountryCode":"PY"},{"CountryId":"182","CountryName_CN":"\u79d8\u9c81","CountryName_EN":"PERU","CountryCode":"PE"},{"CountryId":"183","CountryName_CN":"\u83f2\u5f8b\u5bbe","CountryName_EN":"PHILIPPINES","CountryCode":"PH"},{"CountryId":"184","CountryName_CN":"\u76ae\u7279\u51ef\u6069\u7fa4\u5c9b","CountryName_EN":"PITCAIRN ISLANDS","CountryCode":"PN"},{"CountryId":"185","CountryName_CN":"\u6ce2\u5170","CountryName_EN":"POLAND","CountryCode":"PL"},{"CountryId":"186","CountryName_CN":"\u6ce2\u591a\u9ece\u5404","CountryName_EN":"PUERTO RICO","CountryCode":"PR"},{"CountryId":"187","CountryName_CN":"\u8461\u8404\u7259","CountryName_EN":"PORTUGAL","CountryCode":"PT"},{"CountryId":"188","CountryName_CN":"\u5361\u5854\u5c14","CountryName_EN":"QATAR","CountryCode":"QA"},{"CountryId":"189","CountryName_CN":"\u7559\u5c3c\u6c6a","CountryName_EN":"REUNION ISLAND ","CountryCode":"RE"},{"CountryId":"190","CountryName_CN":"\u7f57\u9a6c\u5c3c\u4e9a","CountryName_EN":"ROMANIA","CountryCode":"RO"},{"CountryId":"191","CountryName_CN":"\u5362\u65fa\u8fbe","CountryName_EN":"RWANDA","CountryCode":"RW"},{"CountryId":"192","CountryName_CN":"\u5723\u57fa\u8328","CountryName_EN":"SAINT KITTS ","CountryCode":"KN"},{"CountryId":"193","CountryName_CN":"\u5723\u76ae\u57c3\u5c14\u548c\u5bc6\u514b\u9686\u7fa4\u5c9b","CountryName_EN":"SAINT PIERRE AND MIQUELON","CountryCode":"PM"},{"CountryId":"194","CountryName_CN":"\u5723\u6587\u68ee\u7279\u548c\u683c\u6797\u7eb3\u4e01\u65af\u5c9b","CountryName_EN":"SAINT VINCENT AND THE GRENADINES","CountryCode":"VC"},{"CountryId":"195","CountryName_CN":"\u5317\u9a6c\u91cc\u4e9a\u7eb3\u7fa4\u5c9b","CountryName_EN":"Northern mariana islands","CountryCode":"MP"},{"CountryId":"196","CountryName_CN":"\u5723\u591a\u7f8e\u548c\u666e\u6797\u897f\u6bd4","CountryName_EN":"SAO TOME AND PRINCIPE","CountryCode":"ST"},{"CountryId":"197","CountryName_CN":"\u5723\u9a6c\u529b\u8bfa","CountryName_EN":"SAN MARINO","CountryCode":"SM"},{"CountryId":"198","CountryName_CN":"\u585e\u5185\u52a0\u5c14","CountryName_EN":"SENEGAL","CountryCode":"SN"},{"CountryId":"199","CountryName_CN":"\u6c99\u7279\u963f\u62c9\u4f2f","CountryName_EN":"SAUDI ARABIA","CountryCode":"SA"},{"CountryId":"200","CountryName_CN":"\u585e\u5c14\u7ef4\u4e9a\u5171\u548c\u56fd","CountryName_EN":"Serbia","CountryCode":"RS"},{"CountryId":"201","CountryName_CN":"\u585e\u62c9\u91cc\u6602","CountryName_EN":"SIERRA LEONE","CountryCode":"SL"},{"CountryId":"202","CountryName_CN":"\u585e\u820c\u5c14","CountryName_EN":"SEYCHELLES","CountryCode":"SC"},{"CountryId":"203","CountryName_CN":"\u65b0\u52a0\u5761","CountryName_EN":"SINGAPORE","CountryCode":"SG"},{"CountryId":"204","CountryName_CN":"\u65af\u6d1b\u4f10\u514b","CountryName_EN":"SLOVAKIA REPUBLIC","CountryCode":"SK"},{"CountryId":"205","CountryName_CN":"\u65af\u6d1b\u6587\u5c3c\u4e9a","CountryName_EN":"SLOVENIA","CountryCode":"SI"},{"CountryId":"206","CountryName_CN":"\u6240\u7f57\u95e8\u7fa4\u5c9b","CountryName_EN":"SOLOMON ISLANDS","CountryCode":"SB"},{"CountryId":"207","CountryName_CN":"\u7d22\u9a6c\u91cc","CountryName_EN":"SOMALIA","CountryCode":"SO"},{"CountryId":"208","CountryName_CN":"\u7d22\u9a6c\u91cc\u5171\u548c\u56fd","CountryName_EN":"SOMALILAND","CountryCode":"XS"},{"CountryId":"209","CountryName_CN":"\u5357\u975e","CountryName_EN":"SOUTH AFRICA","CountryCode":"ZA"},{"CountryId":"210","CountryName_CN":"\u5357\u4e54\u6cbb\u4e9a\u5c9b\u548c\u5357\u6851\u5a01\u5947\u7fa4\u5c9b","CountryName_EN":"SOUTH GEORGIA AND THE SOUTH SANDWICH ISL","CountryCode":"GS"},{"CountryId":"211","CountryName_CN":"\u97e9\u56fd","CountryName_EN":"SOUTH KOREA","CountryCode":"KR"},{"CountryId":"212","CountryName_CN":"\u5357\u82cf\u4e39\u5171\u548c\u56fd","CountryName_EN":"SOUTH SUDAN","CountryCode":"SS"},{"CountryId":"213","CountryName_CN":"\u5317\u975e\u897f\u73ed\u7259\u5c5e\u571f","CountryName_EN":"SPANISH TERRITORIES OF N. AFRICA","CountryCode":"XG"},{"CountryId":"214","CountryName_CN":"\u65af\u91cc\u5170\u5361","CountryName_EN":"SRI LANKA","CountryCode":"LK"},{"CountryId":"215","CountryName_CN":"\u5723\u8d6b\u52d2\u62ff\u5c9b","CountryName_EN":"ST HELENA","CountryCode":"SH"},{"CountryId":"216","CountryName_CN":"\u5723\u5df4\u7279\u52d2\u7c73\u5c9b","CountryName_EN":"ST. BARTHELEMY","CountryCode":"XY"},{"CountryId":"217","CountryName_CN":"\u5723\u5c24\u65af\u5854\u63d0\u9a6c\u65af\u5c9b","CountryName_EN":"ST. EUSTATIUS","CountryCode":[]},{"CountryId":"218","CountryName_CN":"\u5723\u5362\u897f\u4e9a","CountryName_EN":"ST. LUCIA","CountryCode":"LC"},{"CountryId":"219","CountryName_CN":"\u5723\u9a6c\u817e\u5c9b","CountryName_EN":"ST. MAARTEN","CountryCode":[]},{"CountryId":"220","CountryName_CN":"\u82cf\u4e39","CountryName_EN":"SUDAN","CountryCode":"SD"},{"CountryId":"221","CountryName_CN":"\u82cf\u91cc\u5357","CountryName_EN":"SURINAME","CountryCode":"SR"},{"CountryId":"222","CountryName_CN":"\u65af\u74e6\u5c14\u5df4\u5c9b\u548c\u626c\u9a6c\u5ef6\u5c9b","CountryName_EN":"SVALBARD AND JAN MAYEN","CountryCode":"SJ"},{"CountryId":"223","CountryName_CN":"\u65af\u5a01\u58eb\u5170","CountryName_EN":"SWAZILAND","CountryCode":"SZ"},{"CountryId":"224","CountryName_CN":"\u745e\u5178","CountryName_EN":"SWEDEN","CountryCode":"SE"},{"CountryId":"225","CountryName_CN":"\u745e\u58eb","CountryName_EN":"SWITZERLAND","CountryCode":"CH"},{"CountryId":"226","CountryName_CN":"\u53d9\u5229\u4e9a","CountryName_EN":"SYRIA","CountryCode":"SY"},{"CountryId":"227","CountryName_CN":"\u53f0\u6e7e","CountryName_EN":"TAIWAN","CountryCode":"TW"},{"CountryId":"228","CountryName_CN":"\u5854\u5409\u514b\u65af\u5766","CountryName_EN":"TAJIKISTAN","CountryCode":"TJ"},{"CountryId":"229","CountryName_CN":"\u5766\u6851\u5c3c\u4e9a","CountryName_EN":"TANZANIA","CountryCode":"TZ"},{"CountryId":"230","CountryName_CN":"\u6cf0\u56fd","CountryName_EN":"THAILAND","CountryCode":"TH"},{"CountryId":"231","CountryName_CN":"\u591a\u54e5","CountryName_EN":"TOGO","CountryCode":"TG"},{"CountryId":"232","CountryName_CN":"\u6258\u514b\u52b3","CountryName_EN":"TOKELAU","CountryCode":"TK"},{"CountryId":"233","CountryName_CN":"\u6c64\u52a0","CountryName_EN":"TONGA","CountryCode":"TO"},{"CountryId":"234","CountryName_CN":"\u7279\u91cc\u5c3c\u8fbe\u548c\u591a\u5df4\u54e5","CountryName_EN":"TRINIDAD AND TOBAGO","CountryCode":"TT"},{"CountryId":"235","CountryName_CN":"\u7279\u91cc\u65af\u5766","CountryName_EN":"TRISTAN DA CUNBA","CountryCode":"TA"},{"CountryId":"236","CountryName_CN":"\u7a81\u5c3c\u65af","CountryName_EN":"TUNISIA","CountryCode":"TN"},{"CountryId":"237","CountryName_CN":"\u571f\u8033\u5176","CountryName_EN":"TURKEY","CountryCode":"TR"},{"CountryId":"238","CountryName_CN":"\u571f\u5e93\u66fc\u65af\u5766","CountryName_EN":"TURKMENISTAN","CountryCode":"TM"},{"CountryId":"239","CountryName_CN":"\u7279\u514b\u65af\u548c\u51ef\u79d1\u65af\u7fa4\u5c9b","CountryName_EN":"TURKS AND CAICOS ISLANDS","CountryCode":"TC"},{"CountryId":"240","CountryName_CN":"\u4e4c\u5e72\u8fbe","CountryName_EN":"UGANDA","CountryCode":"UG"},{"CountryId":"241","CountryName_CN":"\u4e4c\u514b\u5170","CountryName_EN":"UKRAINE","CountryCode":"UA"},{"CountryId":"242","CountryName_CN":"\u7f8e\u56fd\u672c\u571f\u5916\u5c0f\u5c9b\u5c7f","CountryName_EN":"UNITED STATES MINOR OUTLYING ISLANDS","CountryCode":"UM"},{"CountryId":"243","CountryName_CN":"\u963f\u8054\u914b","CountryName_EN":"UNITED ARAB EMIRATES","CountryCode":"AE"},{"CountryId":"244","CountryName_CN":"\u4e4c\u62c9\u572d","CountryName_EN":"URUGUAY","CountryCode":"UY"},{"CountryId":"245","CountryName_CN":"\u4e4c\u5179\u522b\u514b\u65af\u5766","CountryName_EN":"UZBEKISTAN","CountryCode":"UZ"},{"CountryId":"246","CountryName_CN":"\u74e6\u52aa\u963f\u56fe","CountryName_EN":"VANUATU","CountryCode":"VU"},{"CountryId":"247","CountryName_CN":"\u68b5\u8482\u5188","CountryName_EN":"VATICAN CITY","CountryCode":"VA"},{"CountryId":"248","CountryName_CN":"\u59d4\u5185\u745e\u62c9","CountryName_EN":"VENEZUELA","CountryCode":"VE"},{"CountryId":"249","CountryName_CN":"\u56fe\u74e6\u5362","CountryName_EN":"TUVALU","CountryCode":"TV"},{"CountryId":"250","CountryName_CN":"\u8d8a\u5357","CountryName_EN":"VIETNAM","CountryCode":"VN"},{"CountryId":"251","CountryName_CN":"\u82f1\u5c5e\u7ef4\u5c14\u4eac\u7fa4\u5c9b","CountryName_EN":"VIRGIN ISLAND (GB)","CountryCode":"VG"},{"CountryId":"252","CountryName_CN":"\u7f8e\u5c5e\u7ef4\u5c14\u4eac\u7fa4\u5c9b","CountryName_EN":"VIRGIN ISLAND (US)","CountryCode":"VI"},{"CountryId":"253","CountryName_CN":"\u74e6\u5229\u65af\u7fa4\u5c9b\u548c\u5bcc\u56fe\u7eb3\u7fa4\u5c9b","CountryName_EN":"WALLIS AND FUTUNA ISLANDS","CountryCode":"WF"},{"CountryId":"254","CountryName_CN":"\u897f\u6492\u54c8\u62c9","CountryName_EN":"WESTERN SAHARA ","CountryCode":"EH"},{"CountryId":"255","CountryName_CN":"\u897f\u8428\u6469\u4e9a","CountryName_EN":"WESTERN SAMOA","CountryCode":"WS"},{"CountryId":"257","CountryName_CN":"\u5357\u65af\u62c9\u592b","CountryName_EN":"YUGOSLAVIA","CountryCode":"JU"},{"CountryId":"258","CountryName_CN":"\u624e\u4f0a\u5c14","CountryName_EN":"ZAIRE","CountryCode":"ZR"},{"CountryId":"259","CountryName_CN":"\u8d5e\u6bd4\u4e9a","CountryName_EN":"ZAMBIA","CountryCode":"ZM"},{"CountryId":"260","CountryName_CN":"\u6d25\u5df4\u5e03\u97e6","CountryName_EN":"ZIMBABWE","CountryCode":"ZW"},{"CountryId":"262","CountryName_CN":"\u82cf\u683c\u5170","CountryName_EN":"Kingdom of Scotland","CountryCode":"SCO"},{"CountryId":"263","CountryName_CN":"\u68b5\u5e1d\u5188","CountryName_EN":"Vatican City State","CountryCode":"VAT"},{"CountryId":"266","CountryName_CN":"\u5bc6\u514b\u7f57\u5c3c\u897f\u4e9a\u7fa4\u5c9b","CountryName_EN":"Federated States of Micronesia","CountryCode":"FSM"},{"CountryId":"273","CountryName_CN":"\u5df4\u52d2\u65af\u5766","CountryName_EN":"Palestine","CountryCode":"PS"},{"CountryId":"275","CountryName_CN":"\u6d77\u5ce1\u7fa4\u5c9b","CountryName_EN":"Channel islands","CountryCode":"XC"},{"CountryId":"457","CountryName_CN":"\u52a0\u7eb3\u5229\u7fa4\u5c9b","CountryName_EN":"The canary islands","CountryCode":"XA"},{"CountryId":"478","CountryName_CN":"\u7279\u91cc\u65af\u5766-\u8fbe\u5e93\u5c3c\u4e9a\u7fa4\u5c9b","CountryName_EN":"Tristan da cunha archipelago","CountryCode":"XB"},{"CountryId":"542","CountryName_CN":"\u54c8\u4e09\u514b\u65af\u5766","CountryName_EN":[],"CountryCode":[]},{"CountryId":"556","CountryName_CN":"\u52a0\u6c99\u53ca\u6c57\u5c24\u5c3c\u65af","CountryName_EN":"GAZA AND KHAN YUNIS","CountryCode":"XE"},{"CountryId":"569","CountryName_CN":"\u65b0\u897f\u5170\u5c5e\u571f\u5c9b\u5c7f (\u5e93\u514b\u7fa4\u5c9b)","CountryName_EN":"NEW ZEALAND ISLANDS TERRITORIES","CountryCode":"XL"},{"CountryId":"582","CountryName_CN":"\u5a01\u514b\u5c9b","CountryName_EN":"WAKE ISLAND","CountryCode":"XM"},{"CountryId":"584","CountryName_CN":"\u4e5f\u95e8","CountryName_EN":"YEMEN (REPUBLIC OF)","CountryCode":"YE"},{"CountryId":"587","CountryName_CN":"\u767e\u6155\u5927","CountryName_EN":[],"CountryCode":[]},{"CountryId":"588","CountryName_CN":"\u6469\u65af\u96f7(\u5bc6\u514b\u7f57\u5c3c\u897f\u4e9a\u8054\u90a6\uff09","CountryName_EN":[],"CountryCode":[]},{"CountryId":"589","CountryName_CN":"\u8bfa\u798f\u514b\u7fa4\u5c9b\uff08\u6fb3\u5927\u5229\u4e9a\uff09","CountryName_EN":[],"CountryCode":[]},{"CountryId":"590","CountryName_CN":"\u6ce2\u7eb3\u4f69\u5c9b(\u5bc6\u514b\u7f57\u5c3c\u897f\u4e9a\u8054\u90a6\uff09","CountryName_EN":[],"CountryCode":[]},{"CountryId":"592","CountryName_CN":"\u5927\u6eaa\u5730\uff08\u6cd5\u5c5e\u73bb\u5229\u5c3c\u4e9a\uff09","CountryName_EN":[],"CountryCode":[]},{"CountryId":"594","CountryName_CN":"\u5929\u5b81\u5c9b\uff08\u5317\u9a6c\u91cc\u4e9a\u7eb3\u7fa4\u5c9b\uff09","CountryName_EN":[],"CountryCode":[]},{"CountryId":"595","CountryName_CN":"\u7279\u9c81\u514b(\u5bc6\u514b\u7f57\u5c3c\u4e9a\u897f\u8054\u90a6)","CountryName_EN":[],"CountryCode":[]},{"CountryId":"597","CountryName_CN":"\u96c5\u6d66\uff08\u5bc6\u514b\u7f57\u5c3c\u4e9a\u897f\u8054\u90a6\uff09","CountryName_EN":[],"CountryCode":[]},{"CountryId":"598","CountryName_CN":"\u5e03\u8f9b\u6839","CountryName_EN":[],"CountryCode":[]},{"CountryId":"599","CountryName_CN":"\u574e\u76ae\u5965\u5185\/\u5362\u52a0\u8bfa\u6e56","CountryName_EN":[],"CountryCode":[]},{"CountryId":"600","CountryName_CN":"\u4f11\u8fbe","CountryName_EN":[],"CountryCode":[]},{"CountryId":"601","CountryName_CN":"\u82f1\u683c\u5170","CountryName_EN":[],"CountryCode":[]},{"CountryId":"602","CountryName_CN":"\u975e\u7f57\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"603","CountryName_CN":"\u9ed1\u5c14\u683c\u5170","CountryName_EN":[],"CountryCode":[]},{"CountryId":"604","CountryName_CN":"\u5229\u7ef4\u5c3c\u5965","CountryName_EN":[],"CountryCode":[]},{"CountryId":"605","CountryName_CN":"\u9a6c\u5fb7\u62c9\u7fa4\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"606","CountryName_CN":"\u6885\u5229\u5229\u4e9a","CountryName_EN":[],"CountryCode":[]},{"CountryId":"607","CountryName_CN":"\u963f\u9640\u65af\u5c71","CountryName_EN":[],"CountryCode":[]},{"CountryId":"609","CountryName_CN":"\u5e93\u814a\u7d22","CountryName_EN":[],"CountryCode":[]},{"CountryId":"610","CountryName_CN":"\u52a0","CountryName_EN":[],"CountryCode":[]},{"CountryId":"613","CountryName_CN":"\u683c\u68f1\u5170\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"615","CountryName_CN":"\u4f2f\u62c9\u9c81\u65af","CountryName_EN":[],"CountryCode":[]},{"CountryId":"616","CountryName_CN":"\u6ce2\u5948\u5c14","CountryName_EN":[],"CountryCode":[]},{"CountryId":"617","CountryName_CN":"\u5f17\u7684\u89d2","CountryName_EN":[],"CountryCode":[]},{"CountryId":"618","CountryName_CN":"\u5723\u591a\u7f8e\u52a0\u548c\u666e\u6797\u897f\u6bd4","CountryName_EN":[],"CountryCode":[]},{"CountryId":"619","CountryName_CN":"\u8056\u9a6c\u529b\u8bfa","CountryName_EN":[],"CountryCode":[]},{"CountryId":"620","CountryName_CN":"\u591a\u540d\u5c3c\u52a0","CountryName_EN":[],"CountryCode":[]},{"CountryId":"621","CountryName_CN":"\u7279\u585e\u62c9\u7279","CountryName_EN":[],"CountryCode":[]},{"CountryId":"623","CountryName_CN":"\u8056\u5df4\u7279\u52d2\u7c73\u5cf6","CountryName_EN":[],"CountryCode":[]},{"CountryId":"624","CountryName_CN":"\u8056\u514b\u91cc\u65af\u591a\u4f5b\u7fa4\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"626","CountryName_CN":"\u5384\u5fb7\u7f57\u666e","CountryName_EN":[],"CountryCode":[]},{"CountryId":"627","CountryName_CN":"\u62c9\u6258\u7ef4\u4e9a","CountryName_EN":[],"CountryCode":[]},{"CountryId":"629","CountryName_CN":"\u8056\u5c24\u65af\u5854\u5824\u70cf\u65af\u5cf6","CountryName_EN":[],"CountryCode":[]},{"CountryId":"630","CountryName_CN":"\u8056\u5362\u897f\u4e9a\u7fa4\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"631","CountryName_CN":"\u8056\u99ac\u4e01\u5cf6","CountryName_EN":[],"CountryCode":[]},{"CountryId":"632","CountryName_CN":"\u8056\u9a6c\u4e01\u7fa4\u5c9b\u74dc\u5fb7\u7f57\u666e\u5c9b","CountryName_EN":[],"CountryCode":"GP"},{"CountryId":"633","CountryName_CN":"\u8056\u6258\u9a6c\u65af\u7fa4\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"634","CountryName_CN":"\u8056\u6587\u68ee\u7279\u548c\u683c\u6797\u7eb3\u4e01\u65af","CountryName_EN":[],"CountryCode":[]},{"CountryId":"636","CountryName_CN":"\u6258\u5c14\u6258\u62c9\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"637","CountryName_CN":"\u8054\u5408\u7fa4\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"638","CountryName_CN":"\u6bdb\u91cc\u6c42","CountryName_EN":[],"CountryCode":[]},{"CountryId":"639","CountryName_CN":"\u79d1\u79d1\u65af(\u57fa\u6797)\u7fa4\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"640","CountryName_CN":"\u8d6b\u5fb7\u5c9b\u548c\u9ea6\u514b\u5510\u90a3\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"641","CountryName_CN":"\u82f1\u5c5e\u5370\u5ea6\u6d0b\u9886\u571f","CountryName_EN":[],"CountryCode":[]},{"CountryId":"642","CountryName_CN":"\u5723\u57fa\u8328\u548c\u5c3c\u7ef4\u65af\u8054\u90a6","CountryName_EN":[],"CountryCode":[]},{"CountryId":"644","CountryName_CN":"\u963f\u68ee\u677e\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"647","CountryName_CN":"\u5723\u8d6b\u52d2\u62ff","CountryName_EN":[],"CountryCode":[]},{"CountryId":"649","CountryName_CN":"\u798f\u514b\u5170\u7fa4\u5c9b\uff08\u9a6c\u5c14\u7ef4\u7eb3\u65af\uff09","CountryName_EN":[],"CountryCode":[]},{"CountryId":"650","CountryName_CN":"\u5723\u76ae\u57c3\u5c14\u548c\u5bc6\u514b\u9686","CountryName_EN":[],"CountryCode":[]},{"CountryId":"651","CountryName_CN":"\u590d\u6d3b\u5c9b","CountryName_EN":[],"CountryCode":[]},{"CountryId":"652","CountryName_CN":"\u5343\u91cc\u8fbe\u4e0e\u591a\u5df4\u54e5","CountryName_EN":[],"CountryCode":[]},{"CountryId":"1218","CountryName_CN":"\u6bdb\u91cc\u6c42\u65af","CountryName_EN":"The Republic of Mauritius","CountryCode":"MU"},{"CountryId":"1220","CountryName_CN":"\u585e\u5c14\u7ef4\u4e9a","CountryName_EN":"Republic of Serbia","CountryCode":"SRB"},{"CountryId":"1221","CountryName_CN":"\u79d1\u7279\u8fea\u74e6","CountryName_EN":"Ivory Coast","CountryCode":"CIV"}]}}
EOF;

		return json_decode($str,true);
		
		
		//正常从端口获取信息
		try{
			$request = array(
					'AppKey'=>$this->appKey, 
					'AppSecret'=>$this->appSecret,
			);
		
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'Load_Country');
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			$response = $response['data']->Load_CountryResult->any;
			return self::getResult(0, $response, '');
				
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	/**
	 +----------------------------------------------------------
	 * 获取运输渠道信息
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/13				初始化
	 +----------------------------------------------------------
	 **/
	public function loadFreightChannels(){
		try{
			$request = array(
					'AppKey'=>$this->appKey, 
					'AppSecret'=>$this->appSecret,
					'ToKenCategory'=>''
			);
	
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'Load_Freight_Channels');
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			$response = $response['data']->Load_Freight_ChannelsResult->any;

			return self::getResult(0, $response, '');
	
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取打印模板
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/04/13				初始化
	 +----------------------------------------------------------
	 **/
	public function loadPrintTemplate(){
		try{
			$request = array(
					'AppKey'=>$this->appKey,
					'AppSecret'=>$this->appSecret,
			);
		
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'Load_Print_Template');

			$response = $response['data']->Load_Print_TemplateResult->any;
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
			$channelArr=$channelArr['FaceSingleList']['FaceSingle'];
			
			$channelStr = '';
			foreach ($channelArr as $channelVal){
				$channelStr .= $channelVal['FaceSingleId'].':'.$channelVal['FaceSingleName'].';';
			}
			return self::getResult(0, $channelStr, '');
		
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			//因为艾帕奇不同账号获取的渠道不同，所以自动更新不包括艾帕奇
			// TODO carrier user account @XXX@
			$request = array(
					'AppKey'=>is_null($account)?'@XXX@':$account->api_params['appKey'],
					'AppSecret'=>is_null($account)?'@XXX@':$account->api_params['appSecret'],
					'ToKenCategory'=>''
			);

			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'Load_Freight_Channels');
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			if(!isset($response['data']->Load_Freight_ChannelsResult->any) || empty($response['data']->Load_Freight_ChannelsResult->any))
				return self::getResult(1,'','获取运输方式失败');
			$response = $response['data']->Load_Freight_ChannelsResult->any;
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
			
			if(empty($channelArr['FreightWayInfoList']))
				return self::getResult(1,'','获取运输方式失败');
			$channelArr=$channelArr['FreightWayInfoList']['FreightWayInfo'];
			
			$result = '';
			foreach ($channelArr as $channelVal){
				$result .= $channelVal['FreightWayId'].':'.$channelVal['FreightWayName_CN'].';';
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
		$result = array('is_support'=>0,'error'=>1);
		
// 		try{
// 			$request = array(
// 					'AppKey'=>$data['appKey'],
// 					'AppSecret'=>$data['appSecret'],
// 			);
		
// 			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'Load_Country');
		
// 			if($response['error'] == 0){
// 				$response = $response['data']->Load_CountryResult->any;
// 				$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
		
// 				if($channelArr['Result']=='信息获取成功')
// 					$result['error'] = 0;
// 			}
// 		}catch(CarrierException $e){
// 		}
		
		return $result;
		
	}
}
?>