<?php
namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use common\helpers\Helper_Curl;
use common\helpers\SubmitGate;
use Qiniu\json_decode;
use eagle\modules\order\models\OdOrder;

class LB_SHENZHENYOUZHENGCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl=null; //物流接口
	static private $wsdls="http://shipping.szems.cn"; //服务端口
	private $submitGate = null;	// SoapClient实例
		
	//错误信息
	public static	$res_err_arr=array(
			'B0300'=>"订单信息导入成功",
			'B0301'=>"客户身份认证Token验证未通过",
			'B0302'=>"挂件订单类型标识非4",
			'B0303'=>"订单内件数超过100个",
			'B0304'=>"订单内件数为0个",
			'B0305'=>"物流名称不允许为空",
			'B0306'=>"根据token获取custId值为空",
			'B0307'=>"跟踪号不允许为空",
			'B0308'=>"跟踪号格式不正确",
			'B0309'=>"跟踪号系统已存在",
			'B0310'=>"客户身份识别Token不允许为空",
			'B0311'=>"订单类型不允许为空",
			'B0312'=>"物流产品代码不允许为空",
			'B0313'=>"订单号不允许为空",
			'B0314'=>"邮件重量不允许为空",
			'B0315'=>"发件人邮编不允许为空",
			'B0316'=>"发件人名称不允许为空",
			'B0317'=>"发件人地址不允许为空",
			'B0318'=>"发件人电话不允许为空",
			'B0319'=>"发件人移动电话不允许为空",
			'B0320'=>"发件人英文省名不允许为空",
			'B0321'=>"发件人城市名称英文不允许为空",
			'B0322'=>"收件人邮编不允许为空",
			'B0323'=>"收件人名称不允许为空",
			'B0325'=>"收件人地址不允许为空",
			'B0326'=>"收件人电话不允许为空",
			'B0327'=>"收件人移动电话不允许为空",
			'B0328'=>"收件人城市不允许为空",
			'B0329'=>"收件人州不允许为空",
			'B0330'=>"收件人国家中文名不允许为空",
			'B0331'=>"收件人英文国家名不允许为空",
			'B0332'=>"商品SKU编号不允许为空",
			'B0333'=>"原寄地不允许为空",
			'B0334'=>"发件人省名必须是英文",
			'B0335'=>"发件人城市名称必须是英文",
			'B0336'=>"收件人名称必须是英文",
			'B0337'=>"收件人地址必须是英文",
			'B0338'=>"收件人城市必须是英文",
			'B0339'=>"收件人州名必须是英文",
			'B0340'=>"收件人英文国家名必须是英文",
			'B0341'=>"商品英文名称必须是英文",
			'B0342'=>"订单业务类型错误",
			'B0343'=>"邮件重量必须是正整数",
			'B0344'=>"内件数量必须是正整数",
			'B0345'=>"内件重量（克）必须是正整数",
			'B0346'=>"当前物流产品跟踪号不允许为空",
			'B0348'=>"报关价格必须是数字",
			'B0349'=>"收件人电话和移动电话不能同时为空",
			'B0350'=>"收件人国家中文名对应国家代码不存在",
			'B0351'=>"当前客户的订单号系统已经存在",
			'B0352'=>"写入数据库失败",
			'B0353'=>"接口数据格式异常",
			'B0354'=>"推送物流产品代码系统不存在",
			'B0355'=>"国家产品类型限重未配置或目的国家不通邮",   //'目的国家不通邮'自加
			'B0356'=>"邮件重量超过产品限重",
			'B0357'=>"API根据产品类型申请条码返回空",
			'B0358'=>"该产品类型的号码池资源不足",
			'B0360'=>"订单类型最大长度不能超过1",
			'B0361'=>"物流产品代码最大长度不能超过2",
			'B0362'=>"订单号最大长度不能超过50",
			'B0363'=>"邮件重量最大长度不能超过8",
			'B0364'=>"最大长度不能超过120",
			'B0365'=>"发件人邮编最大长度不能超过10",
			'B0366'=>"发件人名称最大长度不能超过50",
			'B0367'=>"发件人地址最大长度不能超过200",
			'B0368'=>"发件人电话最大长度不能超过20",
			'B0369'=>"发件人移动电话最大长度不能超过20",
			'B0370'=>"发件人英文省名最大长度不能超过100",
			'B0371'=>"发件人英文城市名称最大长度不能超过100",
			'B0372'=>"收件人邮编最大长度不能超过16",
			'B0373'=>"收件人名称英文最大长度不能超过100",
			'B0374'=>"收件人地址英文最大长度不能超过200",
			'B0375'=>"收件人电话最大长度不能超过20",
			'B0376'=>"收件人移动电话最大长度不能超过20",
			'B0377'=>"收件人城市英文最大长度不能超过100",
			'B0378'=>"收件人州英文最大长度不能超过100",
			'B0379'=>"收件人电子邮箱最大长度不能超过64",
			'B0380'=>"收件人国家中文名最大长度不能超过32",
			'B0381'=>"收件人英文国家名最大长度不能超过64",
			'B0382'=>"商品SKU编号最大长度不能超过32",
			'B0383'=>"商品中文名称最大长度不能超过100",
			'B0384'=>"商品英文名称最大长度不能超过100",
			'B0385'=>"商品数量最大长度不能超过4",
			'B0386'=>"商品重量最大长度不能超过6",
			'B0387'=>"报关价格最大长度不能超过8",
			'B0388'=>"原寄地最大长度不能超过30",
			'B0389'=>"跟踪单号最大长度不能超过20",
			'B0390'=>"海关编码最大长度不能超过10",
			'B0391'=>"订单来源最大长度不能超过1",
			'B0392'=>"备注信息最大长度不能超过32",
			'B0393'=>"内件类型不允许为空",
			'B0394'=>"内件类型最大长度不能超过1",
			'B0395'=>"内件成分说明最大长度不能超过60",
			'B0396'=>"商品SKU编号最大长度不能超过100"
			);
		
	public function __construct(){
		self::$wsdl=self::$wsdls."/sdselfsys/services/mailSearch?wsdl";
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/05/09				初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$e  = $data['data'];
				
			//重复发货 添加不同的标识码
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
			
			if(isset($data['data']['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			
			if(empty($info['senderAddressInfo']['shippingfrom'])){
				return self::getResult(1,'','地址信息没有设置好，请到相关的货代设置地址信息');
			}
			$senderAddressInfo=$info['senderAddressInfo'];
	
			//认证参数
			$params=$account->api_params;
			$this->custToken=$params['custToken'];
			
			//卖家信息
			$shippingfrom=$senderAddressInfo['shippingfrom'];
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 200,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 20
			);
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			$innerList=array();
			foreach ($order->items as $k=>$v){
				if(empty($e['Name'][$k]))
					return self::getResult(1,'','商品中文名称不能为空');
				if(empty($e['EName'][$k]))
					return self::getResult(1,'','商品英文名称不能为空');
				if(empty($e['DeclarePieces'][$k]))
					return self::getResult(1,'','商品数量不能为空');
				if(empty($e['weight'][$k]))
					return self::getResult(1,'','商品重量不能为空');
				if(empty($e['DeclaredValue'][$k]))
					return self::getResult(1,'','报关价格不能为空');
				if(empty($e['sku'][$k]))
					return self::getResult(1,'','商品 SKU不能为空');
				
				$innerList[]=[
					"innerName"=>$e['Name'][$k],      //商品中文名称 中文
					"innerNameEn"=>$e['EName'][$k],   //商品英文名称 英文
					"innerQty"=>$e['DeclarePieces'][$k],  //商品数量, 不允许出现小数点
					"innerWeight"=>$e['weight'][$k],  //商品重量（克） , 不允许出现小数点
					"innerPrice"=>$e['DeclaredValue'][$k],  //报关价格（保留整数，美元）
					"sku"=>$e['sku'][$k], //商品 SKU 编号，商品拣货信息
					"original"=>"CN",    //原寄地 默认用 CN
					"customsCode"=>$e['customsCode'][$k],  //海关编码 商品的税则号
					"innerIngredient"=>$e['innerIngredient'][$k],   //内件成分
				];
			}
			
			$json_innerList=json_encode($innerList);
			
			if(empty($shippingfrom['postcode']))
				return self::getResult(1,'','发件人邮编不能为空');
			if(empty($shippingfrom['contact']))
				return self::getResult(1,'','发件人名称不能为空');
			if(empty($shippingfrom['street']))
				return self::getResult(1,'','发件人地址不能为空');
			if(empty($shippingfrom['phone']))
				return self::getResult(1,'','发件人电话不能为空');
			if(empty($shippingfrom['mobile']))
				return self::getResult(1,'','发件人移动电话不能为空');
			if(empty($shippingfrom['province_en']))
				return self::getResult(1,'','发件人英文省名不能为空');
			if(empty($shippingfrom['city_en']))
				return self::getResult(1,'','发件人英文城市名称不能为空');
	
			$request=array();
			$request=[
				"custToken"=>$this->custToken,           //客户身份识别 Token
				"orderType"=>"4",        //订单类型 4 挂件， 5 平件
				"logisticsProduct"=>$service->shipping_method_code,        //物流产品代码
				"orderId"=>$customer_number,        //订单号
				"mailWeight"=>$e['fWeight'],         //邮件重量,整数（克）
				"sendPostCode"=>$shippingfrom['postcode'],        //发件人邮编
				"sendUserName"=>$shippingfrom['contact'],       //发件人名称
				"sendUserAddress"=>$shippingfrom['street'],        //发件人地址
				"sendUserTel"=>$shippingfrom['phone'],   //发件人电话
				"sendMobilePhone"=>$shippingfrom['mobile'],      //发件人移动电话
				"sendEngProvince"=>$shippingfrom['province_en'],    //发件人省名,英文
				"sendEngCity"=>$shippingfrom['city_en'],      //发件人城市名称,英文
				"accPostCode"=>$order->consignee_postal_code,   //收件人邮编
				"accUserName"=>$order->consignee,       //收件人名称,英文
				"accUserAddress"=>$addressAndPhone['address_line1'],     //收件人地址，英文
				"accTel"=>$order->consignee_phone,    //收件人电话
				"accMobilePhone"=>$order->consignee_mobile,   //收件人移动电话
				"accCity"=>$order->consignee_city,   //收件人城市，英文
				"accState"=>$tmpConsigneeProvince,        //收件人州，英文
				"accEmail"=>$order->consignee_email,       //收件人电子邮箱
				"countryName"=>$order->consignee_country_code,     //收件人国家中文名或标准国家简码
				"countryEnName"=>$order->consignee_country,      //收件人英文国家名
				"mailCode"=>'',  //跟踪单号，如果没有就是空值
				"remark"=>$e['remark'],     //备注信息，可为空
				"innerType"=>$e['innerType'],    //内件类型
				"innerList"=>'$json_innerList',
			];
// 		print_r($request);die;	
			###################################################################################
			$json_req=json_encode($request);
	
			$json_req=str_replace('"$json_innerList"',$json_innerList,$json_req);
			$requestBody = ['in0'=>$json_req];
	// 		$requestBody=$json_req;
			$response = $this->submitGate->mainGate(self::$wsdl, $requestBody, 'soap', 'prepareImportOrder');
			
			if($response['error']){return self::getResult(1,'',$response['msg']);}
			$res_arr=json_decode($response['data']->out);
			if($res_arr->message=='B0300' && $res_arr->status=='0'){
				$serviceNumber=$res_arr->mailCode;				
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$serviceNumber,['OrderSign'=>$res_arr->orderId]);
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$serviceNumber);
			}
			else{
				$errstr='';
				foreach(self::$res_err_arr as $k=>$r){
					if($res_arr->message==$k){
						$errstr=$r;break;
					}
				}
				return self::getResult(1,'',$errstr);
			}
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
	 **/
	public function doPrint($data){
// 		return BaseCarrierAPI::getResult(1, '', '物流接口不支持api打印。');
		try{
			$order = current($data);reset($data);
			$order = $order['order'];
			$user=\Yii::$app->user->identity;
			if(empty($user))throw new CarrierException('用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
				
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
			$carrier_params = $service->carrier_params;
				
			$pageType="1";
			if(!empty($carrier_params['pageType'])){
				$pageType=$carrier_params['pageType'];
			}
			
			//获取追踪号
			$tmpBarcodeStr='';
			if(count($data)>50)
				return self::getResult(1,null,'最多不能超过 50 个面单');
			foreach ($data as $k=>$v) {
				$order = $v['order'];
			
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
			
				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$order->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}
			
				$tmpBarcodeStr.=$shipped['tracking_number'].",";
			}
			$tmpBarcodeStr=substr($tmpBarcodeStr,0,-1);

			$request=self::$wsdls."/sdselfsys/orderPrint?trackingNos=".$tmpBarcodeStr."&pageType=".$pageType;

			$response = $this->submitGate->mainGate($request, null, 'curl', 'GET');
			
			if($response['error'])return self::getResult(1,'',$response['msg']);
			if(strlen($response['data'])<1000){
				return self::getResult(1,null,$response['data']);
			}

			//如果成功返回pdf 则保存到本地
			foreach($data as $v){
				$order = $v['order'];
// 				$order->is_print_carrier = 1;
				$order->print_carrier_operator = $puid;
				$order->printtime = time();
				$order->save();
			}
			$pdfurl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code);
						
			return self::getResult(0,['pdfUrl'=>$pdfurl],'物流单已生成,请点击页面中打印按钮');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//获取运输方式
	public function getTransportationServices(){
		//1:e邮宝;2:e 包裹;3:e 速宝赛诚专线;4:e 速宝永兴专线;5:e特快;
		$result="111:中快e速宝标准+(带 电 五 国);212:中快 e 速宝经济+（带电 10国）;213:中快 e 速宝经济（不带电 26国);";
		$result=str_replace(" ","",$result);
		if(empty($result)){
			return self::getResult(1, '', '');
		}else{
			return self::getResult(0, $result, '');
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
		$result = array('is_support'=>0,'error'=>1);
		
		return $result;
	}
}
?>