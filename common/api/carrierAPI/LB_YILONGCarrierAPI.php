<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lx
+----------------------------------------------------------------------
| Create Date: 2015-06-15
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use eagle\modules\order\models\OdOrder;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\Helper_Curl;
use eagle\models\SysCountry;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\models\carrier\SysCarrierParam;
use eagle\modules\carrier\helpers\CarrierException;
use Jurosh\PDFMerge\PDFMerger;
use common\api\carrierAPI\NiuMenBaseConfig;
// try{
// include '../components/PDFMerger/PDFMerger.php';
// }catch(\Exception $e){	
// }
/**
 +------------------------------------------------------------------------------
 * 颐龙物流接口对接
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		common\api\carrierAPI
 * @subpackage  Exception
 * @author		lx 
 * @version		1.0
 * 
 * 旧的资源地址:114.215.236.126
 * 新的资源地址:open.ylexp.com
 +------------------------------------------------------------------------------
 */
class LB_YILONGCarrierAPI extends BaseCarrierAPI
{
	static public $url = 'open.ylexp.com';
	
	public static $requestName = null ;
	public static $timestamp = null;
	public	$password	=	"";
	public	$icID	=	"";
	public 	$url_app	=	'open.ylexp.com/cgi-bin/EmsData.dll?DoApp';
	public 	$url_api	=	'open.ylexp.com/cgi-bin/EmsData.dll?DoApi';
	public static	$res_err_arr=array(
			'0'=>"获取失败，没有资源或未定义",
			'1'=>"成功；解析cNo中的运单号",
			'-1'=>"唯一性字段值重复，操作失败",
		    '-2'=>"记录不存在，操作失败",
		    '-3'=>"未提供必须的请求参数，操作失败",
		    '-4'=>"请求不支持，版本错误或请求未实现",
		    '-7'=>"安全校验失败，不是配置的IP或数字签名错误",
		    '-8'=>"未获授权",
		    '-9'=>"EmsData.dll程序错误，通常为数据库查询失败",
		    '-9999'=>"数据库忙，稍后再试！",
			'-14'=>"未提供快递类别(cEmsKind)",
			'-15'=>"未定义该快递类别的转单获取",
			'-710'=>"icID 错误，未提供或小于1",
	    	'-711'=>"icID 错误，客户不存在",
	   	 	'-720'=>"TimeStamp 错误，超出了同步阈值",
	    	'-730'=>"MD5 错误，长度不是32字符",
	    	'-731'=>"MD5 错误，不匹配",
		);
	//*测试ID:71，密钥：12345ABCDE
	//接口API为：“http://api.cnexps.com/cgi-bin/EmsData.dll?DoApi”
	//TimeStamp：时间戳，1970.1.1 0:0:0开始到请求时刻的毫秒数(UTC),13位整数
	//MD5：数字签名，=MD5(icID+TimeStamp+客户密钥)
    //*比如客户密钥为“12345ABCDE”,如上例程为：MD5("71140553442112312345ABCDE")="efd49dfb22292d6c42c0941f08cc4717"
// 	public function __construct(){
// 		$request = ["RequestName"=>"TimeStamp"];
// 		$response = Helper_Curl::post('open.ylexp.com/cgi-bin/EmsData.dll?DoApi',json_encode($request));
// 		$response = json_decode($response);
// // 		self::$timestamp = $response->ReturnValue;
// 	}

	//获取运输方式代码
	function getCarrierService(){
		$request = [
			'RequestName'=>'EmsKindList',
			'icID'=>$this->icID,
			'TimeStamp'=>self::$timestamp,
		];
		$md5 = md5($request['icID'].$request['TimeStamp'].$this->password);
		$request['MD5'] = $md5;
		$response = Helper_Curl::post('open.ylexp.com/cgi-bin/EmsData.dll?DoApp',json_encode($request));
		$response = json_decode($response);
		$str = '';
		foreach($response->List as $v){
			$str .= $v->oName.':'.$v->oName.';';
		}
		echo $str;die;
	}
	
	/*
	 * 获取毫秒时间戳
	*/
	public function getMicroTime(){
		$time = microtime();
		$arr = explode(' ',$time);
		$microSec = floor($arr[0]*1000);
		return $arr[1].$microSec-(8*60*60*1000);
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param $data		包裹及物流商相关参数(多个包裹)
	 +----------------------------------------------------------
	 * @return	apiResult 
	 * array(
	 *	'key为shipping_method' => array(
	 *		'key为delivery_id' => self::getResult(0, '此处为获取的tracking_no', ''),
	 *		'key为delivery_id' => self::getResult(非0的errorCode, '', '错误信息')
	 *	)
	 * )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		qfl		2015/05/18				初始化
	 +----------------------------------------------------------
	**/
	public function getOrderNO($data){
// 		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
// 		$return_info = $base_config_obj->_getOrderNo();
// 		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
		
		try{
			// $this->getCarrierService();//获取运输方式的代码** 有需要的时候再打开
			// $this->getLabelStyle();//获取打印标签样式代码** 有需要的时候再打开
			//odOrder表内容
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
			
			$service_code	=	$service->shipping_method_code;	
			$service_name	=	$service['shipping_method_name'];
// 			$emsKind		=	$service['carrier_params']['emskind'];
			$emsKind = $service->shipping_method_code;
			
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,0,$order,$extra_id,$customer_number);
			$puid = $checkResult['data']['puid'];

			//生成cnum
// 			$extra_id = isset($e['extra_id'])?$e['extra_id']:'';
// 			$customer_number = CarrierAPIHelper::getCustomerNum($order,$extra_id);
			
			$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
				(empty($order->consignee_district) ? '' : ','.$order->consignee_district);

			$a = $account->api_params;
			$this->icID	=	$a['userkey'];
			$this->password	=	$a['token'];
			
			//获取运单号$cNo
			$cNo = $customer_number;
			if(substr($service_name,0,1)=="A"){
// 				$cno_res=$this->DoGetNo(1,$emsKind);
				$url	=	$this->url_api;
			}else{
// 				$cno_res=$this->DoGetNo(0,$service_name);
				$url	=	$this->url_app;
// 				if($cno_res['ReturnValue']>0){
// 					$cNo	=	$cno_res['cNo'];
// 				}else{
// 					return self::getResult(1,'',$cno_res['mess']);
// 				}
			}
			
			###################################################################################
			$carrier_params = $service->carrier_params;
			$address = $account->address;
			//获取目的地中文名
			$country_cn = SysCountry::findOne($order->consignee_country_code);
			$cDes = $country_cn?$country_cn->country_zh:'';
			$timestamp	=	json_decode($this->getTimeStamp(),true);
			
			$total_weight = 0;
			$total_price = 0;
			$total_qty = 0;
			$zhName = '';	//用于DoApi接口的物品中文描述，因该接口不支持多个物品，所以只能将其转成一个字符串
			$enName = '';	//用于DoApi接口的物品英文描述，因该接口不支持多个物品，所以只能将其转成一个字符串

			foreach($order->items as $k=>$v){
				$goods[] = [
						'cxGoods'=>$e['Name'][$k],//物品描述,0-63字符。必须。
						'ixQuantity'=>$e['DeclarePieces'][$k],//物品数量。必须。
						'fxPrice'=>$e['DeclaredValue'][$k],//物品单价，2位小数。
						'cxGoodsA'=>$e['EName'][$k],//物品英文描述,0-63字符。
						];
				$total_weight += $e['weight'][$k] * $e['DeclarePieces'][$k];
				$total_price += $e['DeclaredValue'][$k] * $e['DeclarePieces'][$k];
				$total_qty += $e['DeclarePieces'][$k];
				
				$zhName .= $e['Name'][$k].';';
				$enName .= $e['EName'][$k].';';
			}
			
			if (empty($total_qty)){
				return self::getResult(1, '', '数量不能为0');
			}
			
			if (empty($zhName)){
				return self::getResult(1, '', '物品中文品名不能为空');
			}
			
			if (empty($enName)){
				return self::getResult(1, '', '物品英文品名不能为空');
			}
			
			$zhName=substr($zhName,0,-1);
			$enName=substr($enName,0,-1);
			
			if (empty($total_weight)){
				return self::getResult(1, '', '重量不能为0');
			}
			
			if (empty($order->consignee_province)){
				return self::getResult(1, '', '省份不能为空');
			}
			
			if (empty($order->consignee_city)){
				return self::getResult(1, '', '城市不能为空');
			}
			
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
			
			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}
			
			$phoneContact = '';
			if (empty($order->consignee_phone) && empty($order->consignee_mobile)){
				return self::getResult(1, '', '联系方式不能为空');
			}
			
			if (empty($order->consignee_phone) || empty($order->consignee_mobile)){
				$phoneContact = $order->consignee_phone.$order->consignee_mobile;
			}else{
				$phoneContact = $order->consignee_phone.','.$order->consignee_mobile;
			}
			
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 254,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 63
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			
			if($url	==	$this->url_api){
				$request=[
						"RequestName"=>"EPostUpload",
						"icID"=>$this->icID,
						"TimeStamp"=>$timestamp['ReturnValue'],
						"MD5"=>md5($this->icID.$timestamp['ReturnValue'].$this->password),
						"iSP_Type"=>0,
						"cNum"=>$customer_number,
						"cEmsKind"=>$service->shipping_method_code,
						
						"cDes"=>$cDes,
						//收件人信息
						'cReceiver'=>$order->consignee,
						'cRUnit'=>$order->consignee_company,
						'cRAddr'=>$addressAndPhone['address_line1'],//收件人地址
						'cRCity'=>$order->consignee_city,
						'cRPostcode'=>$order->consignee_postal_code,
						'cRProvince'=>$order->consignee_province,
						'cRCountry'=>$order->consignee_country,
						'cRPhone'=>$addressAndPhone['phone1'],
						'cREMail'=>$order->consignee_email,
						
// 						"fWeight"=>1.000,
						'cTransNote'=>$e['memo'],
// 						"cRSms"=>"",
						"cGoods"=>$zhName,
						"cGoodsA"=>$enName,
						"iQuantity"=>$total_qty, //记录的是总数量,不能写为$goods[0]['ixQuantity']
// 						"fPrice"=>11.00
						];
				
				$request['fWeight'] = empty($total_weight)?1:round($total_weight/1000,3);//重量，公斤，3位小数
				$request['fPrice'] = round($total_price/$total_qty,2);	//物品单价，2位小数。 必须
			}else{
				$request = [
						'RequestName'=>"PreInputSet",
						'icID'=>$this->icID,
						'TimeStamp'=>$timestamp['ReturnValue'],
						'MD5'	=>	md5($this->icID.$timestamp['ReturnValue'].$this->password),
						
						'RecList'=>array(
								'iID'=>0,//0 新增 1 修改 必须为第一个
								'nItemType'=>1,//快件类型 0 文件 1 包裹 2防水袋
								'cEmsKind'=>$service->shipping_method_code,
								// 'fAmount'=>,//快递费
								'cDes'=>$cDes,//目的地 中文
								'cNum'=>$cNo,
								'cNo'=>'',
								'nLanguage'=>2,
								//收件人信息
								'cReceiver'=>$order->consignee,
								'cRUnit'=>$order->consignee_company,
								'cRAddr'=>$addressAndPhone['address_line1'],//收件人地址
								'cRCity'=>$order->consignee_city,
								'cRPostcode'=>$order->consignee_postal_code,
								'cRProvince'=>$order->consignee_province,
								'cRCountry'=>$order->consignee_country,
								'cRPhone'=>$addressAndPhone['phone1'],
								'cREMail'=>$order->consignee_email,
								'cTransNote'=>$e['memo'],
						)
				
						];
						
						$request['GoodsList'] = $goods;
						$request['RecList']['fWeight'] = empty($total_weight)?1:round($total_weight/1000,3);//重量，公斤，3位小数
						$request['RecList']['fDValue'] = $total_price;
			}
			
			$json_req=json_encode($request);
			
			###################################################################################
			
			
			$response = json_decode(Helper_Curl::post($url,$json_req),true);
// 			print_r($request);
// 			print_r($response);die;
//			echo '<meta charset="utf8">';
//			echo $response['cMess'];
			if($url	==	$this->url_api){
				if(!isset($response['ReturnValue'])){
					return self::getResult(1,'','操作失败:请检查订单数据是否完整');
				}elseif($response['ReturnValue']>0){
					//成功
					$detail = $response;
					//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
					$r = CarrierAPIHelper::orderSuccess($order,$service,$detail['cNum'],OdOrder::CARRIER_WAITING_PRINT,$detail['cNo']);
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$detail['cNo']);
				}else{
					//失败
					return self::getResult(1,'','操作失败:'.$response['cMess']);
				}
				
			}else{
				if(!isset($response['OK'])){
					return self::getResult(1,'','操作失败:请检查订单数据是否完整');
				}else if($response['OK'] >0){
					//成功
					$errlist = $response['ErrList'];
					$detail = $errlist[0];
					//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
					$r = CarrierAPIHelper::orderSuccess($order,$service,$detail['cNum'],OdOrder::CARRIER_WAITING_PRINT,$detail['cNo']);
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$detail['cNo']);
				}else if($response['OK'] ==0){
					//失败
					$errlist = $response['ErrList'];
					$detail = $errlist[0];
					return self::getResult(1,'','操作失败:'.$detail['cMess']);
				}
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	public function cancelOrderNO($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_cancelOrderNO();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
		try{
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,1,$order);
			$shipped = $checkResult['data']['shipped'];
			
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			$a = $account->api_params;
			$this->icID	=	$a['icID'];
			$this->password	=	$a['password'];
			$timestamp	=	json_decode($this->getTimeStamp(),true);
			$serviceid	=	$order['default_shipping_method_code'];
			$shipping_s	=	SysShippingService::findOne($serviceid);
			
			/*
			 * 不清楚此段代码用意何在,所以暂时屏蔽  2015-09-11 hqw 
			$params=SysCarrierParam::findOne(array(
					'carrier_code'=>"lb_yilong",
					'carrier_param_key'=>"emskind"
			));
			$arr_emskindlist=unserialize($params->carrier_param_value);
			*/
				
			$service_code=$shipping_s->shipping_method_code;
			$service_name=$shipping_s['shipping_method_name'];
			
			if(substr($service_name,0,1)=="A"){
				return self::getResult(1,'','此服务，不支持取消操作');
				$url	=	$this->url_api;
			}else{
				$url	=	$this->url_app;
			}
			$request=[
					"RequestName"=>"",
					"icID"=>$this->icID,
					"TimeStamp"=>$timestamp['ReturnValue'],
					"MD5"=>md5($this->icID.$timestamp['ReturnValue'].$this->password),
					"iSP_Type"=>0,
					"cNum"=>$order->customer_number,
			];
			//先获取订单信息
			$request['RequestName']="PreInputData";
			$json_req=json_encode($request);
			$response = json_decode(Helper_Curl::post($url,$json_req),true);
// 			$iID = $response['iID'];
			if($response<0){
				return self::getResult(1,'','操作失败:订单已不存在或已入快递系统');
			}
			$iID = $response['iID'];
			
			//删除
			$request['RequestName']="PreInputDel";
			$request['iIDs'][]=$iID;
			$json_req=json_encode($request);
			$response = json_decode(Helper_Curl::post($url,$json_req),true);
			if($response['OK']>0){
				$shipped->delete();
				$order->carrier_step = OdOrder::CARRIER_CANCELED;
				$order->customer_number = '';
				$order->save();
				return self::getResult(0,'','取消订单成功');
			}else{
				return self::getResult(1,'','操作失败:订单已不存在或者已入快递系统');
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 * 该方法没有发现有任何地方用到 2015-09-11 hqw
	 */
	public function PreInputData($timestamp){
		$request=[
				"RequestName"=>"PreInputDel",
				"icID"=>$this->icID,
				"TimeStamp"=>$timestamp['ReturnValue'],
				"MD5"=>md5($this->icID.$timestamp['ReturnValue'].$this->password),
				"iSP_Type"=>0,
				"cNum"=>$cNum,
				];
	}
	
	/**
	 * 该方法应该是直接参考CNE物流代码时复制过来，不知道用途 2015-09-11 hqw
	 */
	public static function getLabelStyle(){
		//组织请求数据
		$signature = md5('71'.self::$timestamp.'12345ABCDE');
		$url = 'http://label.cnexps.com/cnePrint/getType?timestamp='.self::$timestamp.'&icID=71&signature='.$signature;
		$response = Helper_Curl::get($url);
		$response = json_decode($response);
		$str = '';
		foreach($response as $v){
			$str .= $v->value.':'.$v->name.';';
		}
		echo $str;die;
	}
	
	/**
	 * 查询运输服务
	 * 该方法没有发现有任何地方用到，不知道用途 2015-09-11 hqw
	 */
	public	function emskindlist() {
		$timestamp	=	json_decode($this->getTimeStamp(),true);
		$data=array(
				"RequestName"=>"EmsKindList",
				"icID"=>$this->icID,
				"TimeStamp"=>$timestamp['ReturnValue'],
				"MD5"=>md5($this->icID.$timestamp['ReturnValue'].$this->password),
		);
		$json	=	json_encode($data);
		$url = $this->url;
		$response = Helper_Curl::post($url,$json);
		$res_arr=json_decode($response);
		$str = '';
		foreach($res_arr->List as $v){
			$str .= $v->oName.':'.$v->cName.';';
		}
		echo '<meta charset="utf8">';
		print_r($str);exit;
	}
	
	//获取转单号
	public	function DoGetNo($type,$emsKind) {
		$timestamp = json_decode($this->getTimeStamp(),true);
		$data=array(
				"RequestName"=>"DoGetNo",
				"icID"=>$this->icID,
				"TimeStamp"=>$timestamp['ReturnValue'],
				"MD5"=>md5($this->icID.$timestamp['ReturnValue'].$this->password),
				'cEmsKind'=>$emsKind
		);
		$json	=	json_encode($data);
		$url =$type==1?$this->url_api: $this->url_app;//$this->url_api;
		$response = Helper_Curl::post($url,$json);
		$res_arr=json_decode($response,true);
		
		foreach(self::$res_err_arr as $k=>$r){
			if($res_arr['ReturnValue']==$k){
				$res_arr['mess']	=	$r;break;
			}
		}
		return $res_arr;
	}
	
	/**
	 * 获取错误信息
	 * 该方法没有发现有任何地方用到，不知道用途 2015-09-11 hqw
	 */
	public function get_report_err($num){
		$res_err_arr=array(
			
		);
	}
	
	

	/**
	 * 发送请求数据 获得返回结果进行处理
	 * 该方法没有发现有任何地方用到，而且调用该方法应该会出现问题因为没有发现使用密钥参数，不知道用途 2015-09-11 hqw
	 */
	public static function getResponse($id,$url,$data,$cEmsKind=null){
		//组织请求数据
		$request = [
			'RequestName'=>self::$requestName,
			'icID'=>$id,
			'TimeStamp'=>self::$timestamp,
			'RecList'=>$data,
		];
		$md5 = md5($request['icID'].$request['TimeStamp']);
		$request['MD5'] = $md5;
		if($cEmsKind)$request['cEmsKind'] = $cEmsKind;
		// var_dump($request);die;
		$response = Helper_Curl::post($url,json_encode($request));
		$response = json_decode($response);
		return $response;
	}
	
	/**
	 * 
	 */
	public function getTimeStamp(){
		return $response = Helper_Curl::post('http://open.ylexp.com/cgi-bin/EmsData.dll?DoApi','{"RequestName":"TimeStamp"}');
	}
	
	
	

	/**
	 * 取消跟踪号
	 */
	public function cancelOrderNO2($data){
		
// 		OdOrder::updateAll(array('carrier_step' => OdOrder::CARRIER_WAITING_PRINT), 'order_id in ('.$data['order']['order_id'].')');
		return self::getResult(1, '', '结果：该物流商API不支持取消功能');
	}

	/**
	 * 交运
	 */
	public function doDispatch($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_doDispatch();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
		OdOrder::updateAll(array('carrier_step' => OdOrder::CARRIER_WAITING_PRINT), 'order_id in ('.$data['order']['order_id'].')');
		return self::getResult(1, '', '结果：该物流商API不支持交运订单功能');
		
	}

	/*
	 * 获取跟踪号
	 */
	public function getTrackingNO($data){
// 		OdOrder::updateAll(array('carrier_step' => OdOrder::CARRIER_WAITING_PRINT), 'order_id in ('.$data['order']['order_id'].')');
		return self::getResult(1, '', '结果：该物流商API不支持获取跟踪号功能');
	}



	/**
	 * 打单
	 */
	public function doPrint($data){
		$base_config_obj = new NiuMenBaseConfig($data,self::$url);
		$return_info = $base_config_obj->_doPrint();
		return BaseCarrierAPI::getResult($return_info['error'],$return_info['data'],$return_info['msg']);
	}

	/*
	 * 用来确定打印完成后 订单的下一步状态
	 */
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}

	//订单重新发货
	public function Recreate($data){return self::getResult(1,'', '该物流商不支持此操作');}
	
	public function YilongTest(){
		$url = $this->url_app;
		$timestamp	=	json_decode($this->getTimeStamp(),true);
		
		$request=[
		"RequestName"=>"PreInputData",
		"icID"=>$this->icID,
		"TimeStamp"=>$timestamp['ReturnValue'],
		"MD5"=>md5($this->icID.$timestamp['ReturnValue'].$this->password),
		'cNum'=>'171-1294429-5949162',
		];
		$json_req=json_encode($request);
		$response = Helper_Curl::post($url,$json_req);
		
// 		$response = json_decode($response);
		
		return $response;
	}
}
