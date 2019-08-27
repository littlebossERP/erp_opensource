<?php
namespace common\api\carrierAPI;

use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use eagle\models\SysCountry;
use common\helpers\Helper_Curl;
use Qiniu\json_decode;
use Jurosh\PDFMerge\PDFMerger;

/**
 * 钮门系统
 * 关于钮门系统货代可以直接继承该物流
 * 
 * 对于打印接口需要对接时查看是否已经开通ajxEmsQueryPDFLabel_url,如果暂时没有开通先不要实现普通参数labelstyle
 * 
 * @author hqw
 */

class NiuMenBaseConfig{
	public $timestamp = null;
    public $data_info = null;
    public $app_url = null;
    public $api_url = null;
    public $ajxEmsQueryPDFLabel_url = null;
	
	public $return_info = [
		'error' => 0,
		'data' => '',
		'msg' => ''
	];
	
	public function __construct($data,$app_url){
		$this->data_info = $data;
		$this->app_url = 'http://'.$app_url.'/cgi-bin/EmsData.dll?DoApp';
		$this->api_url = 'http://'.$app_url.'/cgi-bin/GInfo.dll?DoApi';
		$this->ajxEmsQueryPDFLabel_url = 'http://'.$app_url.'/cgi-bin/GInfo.dll?ajxEmsQueryPDFLabel';
		
		//获取时间戳
		$request = ["RequestName"=>"TimeStamp"];
		$response = Helper_Curl::post($this->app_url, json_encode($request));
		$response = json_decode($response);
		$this->timestamp = $response->ReturnValue;
	}
	
	/**
	 * 上传包裹信息
	 * @return array
	 */
	public function _getOrderNo(){
		$return_infos = $this->return_info;
		$data = $this->data_info;
		
		try{
			$extra_id = isset($data['data']['extra_id'])?$data['data']['extra_id']:'';
			$customer_number = $data['data']['customer_number'];
			$order = $data['order'];
			
			if(isset($data['data']['extra_id'])){
				if($extra_id == ''){
					$return_infos['error'] = 1;
					$return_infos['msg'] = '强制发货标识码，不能为空';
					return $return_infos;
				}
			}

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
			
			//获取到所需要使用的数据
			$e = $data['data'];
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];

			if(empty($info['senderAddressInfo']['shippingfrom'])){
				$return_infos['error'] = 1;
				$return_infos['msg'] = '地址信息没有设置好，请到相关的货代设置地址信息';
				return $return_infos;
			}
			
			$a = $account->api_params;
			$carrier_params = $service->carrier_params;
			$address = $info['senderAddressInfo'];
			$shippingFrom = $address['shippingfrom'];
			
			//获取目的地中文名
			$country_cn = SysCountry::findOne($order->consignee_country_code);
			$cDes = $country_cn?$country_cn->country_zh:'';
			
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($tmpConsigneeProvince)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			$addressAndPhoneParams = array(
				'address' => array(
					'consignee_address_line1_limit' => 254,
				),
				'consignee_district' => 1,
				'consignee_county' => 1,
				'consignee_company' => 0,
				'consignee_phone_limit' => 63
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$request = [
				'iID'=>0,//0 新增 1 修改 必须为第一个
				'nItemType'=>empty($carrier_params['nItemType']) ? 1 : $carrier_params['nItemType'],//快件类型 0 文件 1 包裹 2防水袋
				'nPayWay'=>$carrier_params['nPayWay'],//付款方式
				'cDes'=>$cDes,//目的地 中文
				
				//收件人信息
				'cReceiver'=>$order->consignee,
				'cRUnit'=>$order->consignee_company,
				'cRAddr'=>$addressAndPhone['address_line1'],//收件人地址
				'cRCity'=>$order->consignee_city,
				'cRPostcode'=>$order->consignee_postal_code,
				'cRProvince'=>$tmpConsigneeProvince,
				'cRCountry'=>$order->consignee_country,
				'cRPhone'=>$addressAndPhone['phone1'],
				'cREMail'=>$order->consignee_email,
				
				//客户参考号
				'cRNo'=>$customer_number,

				//发件人信息
				'cSender'=>$shippingFrom['contact'],
				'cSUnit'=>$shippingFrom['company'],
				'cSAddr'=>$shippingFrom['district'].$shippingFrom['street'],
				'cSCity'=>$shippingFrom['city'],
				'cSPostcode'=>$shippingFrom['postcode'],
				'cSProvince'=>$shippingFrom['province'],
				'cSCountry'=>$shippingFrom['country'],
				'cSPhone'=>strlen($shippingFrom['phone'])>4?$shippingFrom['phone']:$shippingFrom['mobile'],
				'cSEMail'=>$shippingFrom['email'],
				
				'cMemo'=>$e['memo'],//备注信息
				'cReserve'=>'',
				
				'cMoney'=>'USD',//货币代码，0-3字符。
				
				'cOrigin'=>'CN',//原产地国家代码，0-3字符。				
				
			];
						
			
			if(!empty($e['fIValue']))
				$request['fIValue']=$e['fIValue']; //物品投保价，2位小数。
			
			if(in_array($order->default_carrier_code, array('lb_jiewang'))){
				$request['cBy1']='XLB_ERP';
				
				if(in_array($service->shipping_method_code,array("J-NET俄全通带电","J-NET俄全通普货"))){
					$request['cBy3']=empty($e['taxNumber'])?"":$e['taxNumber'];    //税号
					$request['cRIDCard']=empty($e['passportNumber'])?"":$e['passportNumber'];    //护照号
				}
			}

			if(in_array($order->default_carrier_code, array('lb_boyang','lb_yilong','lb_xinding')))
				$request['cNum']=$customer_number;

			$total_weight = 0;
			$total_price = 0;
			foreach($order->items as $k=>$v){
				if(mb_strlen($e['Name'][$k],'utf-8')>31){
					$return_infos['error'] = 1;
					$return_infos['msg'] = '中文报关名不能超过31';
					return $return_infos;
				}
				if(empty($e['Name'][$k])){
					$return_infos['error'] = 1;
					$return_infos['msg'] = '中文报关名不能为空';
					return $return_infos;
				}
				if(strlen($e['EName'][$k])>63){
					$return_infos['error'] = 1;
					$return_infos['msg'] = '英文报关名不能超过63';
					return $return_infos;
				}
				if(empty($e['EName'][$k])){
					$return_infos['error'] = 1;
					$return_infos['msg'] = '英文报关名不能为空';
					return $return_infos;
				}
				if(empty($e['DeclaredValue'][$k])){
					$return_infos['error'] = 1;
					$return_infos['msg'] = '报关价值不能空';
					return $return_infos;
				}
				if(empty($e['DeclarePieces'][$k])){
					$return_infos['error'] = 1;
					$return_infos['msg'] = '商品数量不能空';
					return $return_infos;
				}if(empty($e['weight'][$k])){
					$return_infos['error'] = 1;
					$return_infos['msg'] = '报关重量不能为空';
					return $return_infos;
				}
				if(in_array($order->default_carrier_code, array('lb_jiewang')))
					$DeclaredValue=round($e['DeclaredValue'][$k],2);
				else
					$DeclaredValue=intval($e['DeclaredValue'][$k]);
				
				$goodsitem=[
					'cxGoods'=>$e['EName'][$k].'-'.$e['Name'][$k],//物品描述,0-63字符。必须。
					'ixQuantity'=>intval($e['DeclarePieces'][$k]),//物品数量。必须。
					'fxPrice'=>$DeclaredValue,//物品单价，2位小数。
					'cxGoodsA'=>$e['EName'][$k],//物品英文描述,0-63字符。
					'cxGCodeA'=>$v->sku,//物品SKU,0-63字符。
				];
				//捷网信息有所不同
				if(in_array($order->default_carrier_code, array('lb_jiewang'))){
					//$goodsitem['cxGCodeA'] = '';  //海关编码
					$goodsitem['cxGCodeB'] = $v->sku;  //sku
					$goodsitem['cxGoodsA'] = $e['EName'][$k];  //物品英文名
					$goodsitem['cxGoods'] = $e['Name'][$k];  //物品中文名
				}
				
				if(in_array($order->default_carrier_code, array('lb_jiewang'))){
					if(in_array($service->shipping_method_code,array("J-NET俄全通带电","J-NET俄全通普货"))){
						//商品属性
						$product_attributes="";
						$product_attributes_arr=\eagle\modules\order\helpers\OrderListV3Helper::getProductAttributesByPlatformItem($order->order_source, $v->product_attributes);
						if(!empty($product_attributes_arr)){
							foreach ($product_attributes_arr as $product_attributes_arrone){
								$product_attributes_type=explode(':',$product_attributes_arrone);
								if(!empty($product_attributes_type[0]) && strtolower($product_attributes_type[0])=="color"){
									$product_attributes=empty($product_attributes_type[1])?"":$product_attributes_type[1];
								}
							}
						}
						
						$goodsitem['cxGoodsUrl']=$v->photo_primary;
						$goodsitem['cxGoodsAttr']=$product_attributes;
					}
				}

				
				$goods[] = $goodsitem;

				
				$total_weight += $e['weight'][$k]*intval($e['DeclarePieces'][$k]);
				
				if(in_array($order->default_carrier_code, array('lb_jiewang')))
					$total_price += $e['DeclaredValue'][$k]*round($e['DeclarePieces'][$k],2);
				else
					$total_price += $e['DeclaredValue'][$k]*intval($e['DeclarePieces'][$k]);
			}
			
			$request['GoodsList'] = $goods;
			$request['fWeight'] = $total_weight/1000;//重量 公斤
			$request['fDValue'] = $total_price;
			if(in_array($order->default_carrier_code, array('lb_jiewang','lb_iml')))
				$request['cEmsKind'] = $service->shipping_method_code;
			$lastRequest[] = $request;

			$user=\Yii::$app->user->identity;
			if( $user->getParentUid()==4498 ){
				//print_r ($lastRequest);exit;
			}


			$response = $this->getResponse(
					$account->api_params['userkey'],
					$account->api_params['token'],
					'PreInputSet',
					$this->app_url,
					array('RecList'=>$lastRequest,'cEmsKind'=>$service->shipping_method_code)
			);

			\Yii::info($order->default_carrier_code.'1 '.$order->order_source_order_id.' '.print_r($response,1), "file");
			
			if(!isset($response->OK)){
				if(isset($response->ReturnValue)){
					$err_message = $this->getReturnValue($response->ReturnValue);
					throw new CarrierException($err_message);
				}else{
					throw new CarrierException("操作失败:请检查订单数据是否完整");
				}
			}else{
				//成功
				$errlist = $response->ErrList;
				$detail = $errlist[0];



				if(isset($detail->iID) && ($detail->iID > 0 || !empty($detail->cNo) || !empty($detail->cNum))){
					//跟踪号
					if(in_array($order->default_carrier_code, array('lb_boyang','lb_yilong','lb_xinding','lb_iml')))
						$serviceNumber=$detail->cNo;
					else
						$serviceNumber=$detail->cNum;
					//lb_iml 用
					if( $serviceNumber=='' ){
						$return_infos['error'] = 1;
						$return_infos['msg'] = '操作失败:'.$detail->cMess;
						return $return_infos;
					}

					//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
					$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$serviceNumber,['cNo'=>$detail->cNo]);
			
					$return_infos['data'] = $r;
					$return_infos['msg'] = '操作成功!订单号'.$serviceNumber;
					
					return $return_infos;
				}else{
					$return_infos['error'] = 1;
					$return_infos['msg'] = '操作失败:'.$detail->cMess;
					return $return_infos;
				}
			}
		}catch(CarrierException $e){
			$return_infos['error'] = 1;
			$return_infos['msg'] = $e->msg();
		}
		return $return_infos;
	}
	
	/**
	 * 获取物流单号
	 * @return array
	 */
	public function _getTrackingNo(){
		$return_infos = $this->return_info;
		
		$return_infos['error'] = 1;
		$return_infos['msg'] = '结果：该物流商API不支持获取跟踪号功能';
		
		return $return_infos;
	}
	
	/**
	 * 打印详情单
	 * 这里钮门的打印接口分开了两个，暂时只对接了sGetLabel这个接口
	 * 钮门/citylink  11:35:28
			算了你就别提供下家的打印接口，就直接提供系统的PDF接口打印吧
			下家的打印接口太麻烦了，后续还要更新
	 */
	public function _doPrint(){
		$return_infos = $this->return_info;
		$pdf = new PDFMerger();
		$data = $this->data_info;
		
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user)){
				throw new CarrierException('用户登陆信息缺失,请重新登陆');
			}
			$puid = $user->getParentUid();
			
			$order = current($data);reset($data);
			$order = $order['order'];
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$a = $account->api_params;
			$carrier_params = $service->carrier_params;
			
			if(empty($carrier_params['labelstyle'])){
				$tmpPDFLabel = $this->getAjxEmsQueryPDFLabel();
				
				if(empty($tmpPDFLabel)){
					$return_infos['msg'] = '该货代暂不支持api打印,请到货代后台打印e_1';
				}else{
					$return_infos['msg'] = '请先到运输方式设置对应的打印模板e_2';
				}
				
				$return_infos['error'] = 1;
				return $return_infos;
			}
			
			$aNoArr = array();
			foreach ($data as $k=>$v) {
				$order = $v['order'];
				
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
		
				if(empty($shipped->tracking_number)){
					$return_infos['error'] = 1;
					$return_infos['msg'] = 'order:'.$order->order_id .' 缺少追踪号,请检查订单是否已上传';
					return $return_infos;
				}
				
				$aNoArr[] = $shipped->tracking_number;
				
				//钮门无法判断是否打印成功 所以只要调用 默认认为成功 保存信息 进行下一步
// 				$order->is_print_carrier = 1;
				$order->print_carrier_operator = $puid;
				$order->printtime = time();
				$order->save();
			}
			
// 			if($order['default_carrier_code'] == 'lb_yilong'){
// 				$response = $this->getResponse(
// 						$account->api_params['icID'],
// 						$account->api_params['password'],
// 						'sGetLabel',
// 						$this->api_url,
// 						array('iTable'=>1,'iNoType'=>1,'cModelName'=>$carrier_params['labelstyle'],'aNo'=>$aNoArr),
// 						false
// 				);
// 			}else{
				$response = $this->getResponse(
						$account->api_params['userkey'],
						$account->api_params['token'],
						'sGetLabel',
						$this->api_url,
						array('iTable'=>1,'iNoType'=>1,'cModelName'=>$carrier_params['labelstyle'],'aNo'=>$aNoArr),
						false
				);
// 			}

			\Yii::info($account->carrier_code.'2 puid:'.$puid.' '.print_r($response,1), "file");
				
			if(strlen($response)<1000){
				//print_r ( array('iTable'=>1,'iNoType'=>1,'cModelName'=>$carrier_params['labelstyle'],'aNo'=>$aNoArr) );exit;
				$return_infos['error'] = 1;
				$return_infos['msg'] = '返回打印失败';
				return $return_infos;
			}else{
				//如果成功返回pdf 则保存到本地
				
				$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$order['customer_number'], 0);
				$pdf->addPDF($pdfUrl['filePath'],'all');
			}
			
			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl='';
			$return_infos['msg'] = '连接已生成,请点击并打印';
			$return_infos['data'] = ['pdfUrl'=>$pdfUrl['pdfUrl']];
			return $return_infos;
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
		
		
	}
	
	//获取运输服务
	public function _getCarrierShippingServiceStr(){
		$return_infos = $this->return_info;
		$account = $this->data_info;

		// TODO carrier user account @XXX@
		$response = $this->getResponse(
				is_null($account)?'@XXX@':$account->api_params['userkey'],
				is_null($account)?'@XXX@':$account->api_params['token'],
				'EmsKindList',
				$this->app_url
		);

		$str = '';
		foreach($response->List as $v){
			$str .= $v->oName.':'.$v->oName.';';
		}

		$return_infos['data'] = $str;
		return $return_infos;
	}
	
	//用户获取账户信息，用户测试
	public function _clientInfo(){
		$return_infos = $this->return_info;
		$account = $this->data_info;
	
		// TODO carrier user account @XXX@
		$response = $this->getResponse(
				'@XXX@',
				'@XXX@',
				'ClientInfo',
				$this->app_url
		);
		
		print_r($response);
	}
	
	/**
	 * 获取可以选择的打印模板，用于第一次使用
	 */
	public function getAjxEmsQueryPDFLabel(){

		$response = Helper_Curl::get($this->ajxEmsQueryPDFLabel_url);
		$response = json_decode($response, true);

		$recName = '';
		
		if($response['ReturnValue'] == '0'){
			return $recName;
		}
		foreach ($response['RecList'] as $reclistVal){
			$recName .= $reclistVal['cName'].':'.$reclistVal['cName'].';';
		}
		return $recName;
	}
	
	/*
	 * 发送请求数据 获得返回结果进行处理
	*/
	public function getResponse($id,$token,$requestName,$url,$relatedParams = array(), $isJsonDecode = true){
		//组织请求数据
		$request = [
			'RequestName'=>$requestName,
			'icID'=>$id,
			'TimeStamp'=>$this->timestamp,
		];

		$md5 = md5($request['icID'].$request['TimeStamp'].$token);
		$request['MD5'] = $md5;
		
		if(!empty($relatedParams)){
			foreach ($relatedParams as $relatedParamsKey => $relatedParamsVal){
				$request[$relatedParamsKey] = $relatedParamsVal;
			}
		}

		$response = Helper_Curl::post($url,json_encode($request));
		
		if($isJsonDecode)
			$response = json_decode($response);

		return $response;
	}
	
	/**
	 * 用于取消交运物流订单
	 */
	public function _cancelOrderNO(){
		$return_infos = $this->return_info;
		
		$return_infos['error'] = 1;
		$return_infos['msg'] = '结果：该物流商API不支持取消运单';
		
		return $return_infos;
	}
	
	/**
	 * 用于交运物流订单
	 */
	public function _doDispatch(){
		$return_infos = $this->return_info;
		
		$return_infos['error'] = 1;
		$return_infos['msg'] = '结果：该物流商API不支持交运';
		
		return $return_infos;
	}
	
	/*
	 * 获取创建订单的状态信息
	*/
	public function getReturnValue($key){
		$arr = [
			-9=>'API接口程序错误，通常为数据库查询失败',
			-8=>'未获授权',
			-7=>'安全校验失败，不是配置的IP或数字签名错误',
			-4=>'请求不支持，版本错误或请求未实现',
			-3=>'未提供必须的请求参数，操作失败',
			-2=>'记录不存在，操作失败',
			-1=>'唯一性字段值重复，操作失败',
			-710=>'认证信息错误（icID 错误，未提供或小于1）',
			-711=>'认证信息错误（icID 错误，客户不存在）',
			-720=>'认证信息错误（时间戳错误，超出了同步阈值）',
			-730=>'认证信息错误（数字签名错误，长度不是32字符）',
			-731=>'认证信息错误（数字签名错误，不匹配）'
		];
		if(array_key_exists($key,$arr)) return $arr[$key];
		else return "操作失败:物流商未知错误".$key;
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
	public function _getVerifyCarrierAccountInformation($data){
		$result = array('is_support'=>1,'error'=>1);
		$return_infos = $this->return_info;
		
		try{
			$response = $this->getResponse(
					$data['userkey'],
					$data['token'],
					'ClientInfo',
					$this->app_url
			);
			
			if(!empty($response)){
				$response=$response->ReturnValue;
				if($response>0)
					$result['error'] = 0;
			}
						
		}catch(CarrierException $e){
		}
	
		$return_infos['error'] = 0;
		$return_infos['data'] = $result;
		$return_infos['msg'] = '';
		return $return_infos;
	}
}
?>