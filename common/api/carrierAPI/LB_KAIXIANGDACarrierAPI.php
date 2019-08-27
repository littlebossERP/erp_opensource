<?php

namespace common\api\carrierAPI;
use yii;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use eagle\modules\dhgate\apihelpers\SaasDhgateAutoSyncApiHelper;

class LB_KAIXIANGDACarrierAPI extends BaseCarrierAPI
{
	static private $url_aj = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	static $connecttimeout=60;
	static $timeout=500;
	static $last_post_info=null;
	static $last_error =null;
	
	public $username = null;
	public $password = null;
	
	public function __construct(){
		//凯翔达没有测试环境
		self::$url_aj = 'http://kxd.bailidaming.com/';
		
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
				
			$this->username = $a['username'];
			$this->password = $a['password'];
			
			$addressInfo = (empty($order->consignee_county) ? '' : ','.$order->consignee_county).
			(empty($order->consignee_district) ? '' : ','.$order->consignee_district);
			
			$address=$order->consignee_address_line1.$order->consignee_address_line2.$order->consignee_address_line3;
			if (empty($address)){
				return self::getResult(1, '', '地址不能为空');
			}
			$address=$address.$addressInfo;
// 			if (empty($order->consignee_province)){
// 				return self::getResult(1, '', '省份不能为空');
// 			}

			//法国没有省份，直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(($order->consignee_country_code == 'FR') && empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			if (empty($order->consignee_city)){
				return self::getResult(1, '', '城市不能为空');
			}
			
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
			
// 			if (empty($order->consignee_postal_code)){
// 				return self::getResult(1, '', '邮编不能为空');
// 			}
			
			$phoneContact = (empty($order->consignee_phone) ? $order->consignee_mobile : $order->consignee_phone);
			
// 			if (empty($phoneContact)){
// 				return self::getResult(1, '', '联系方式不能为空');
// 			}
			
// 			//重复发货 添加不同的标识码
// 			$extra_id = isset($data['extra_id'])?$data['extra_id']:'';
// 			$customer_number = CarrierAPIHelper::getCustomerNum($order,$extra_id);
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 126,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 60
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$tmpAddressMode1 = '';
			if(!empty($order->consignee_company)){
				$tmpAddressMode1 = $order->consignee_company.';';
			}
			if(!empty($order->consignee_address_line1))
				$tmpAddressMode1 .= $order->consignee_address_line1;
			if(!empty($order->consignee_address_line2))
				$tmpAddressMode1 .=' '. $order->consignee_address_line2;
			if(!empty($order->consignee_address_line3))
				$tmpAddressMode1 .= ' '.$order->consignee_address_line3;
			if(!empty($order->consignee_district)){
				$tmpAddressMode1 .=';'. $order->consignee_district;
			}
			if(!empty($order->consignee_county)){
				$tmpAddressMode1 .=','. $order->consignee_county;
			}
			
			$orderMain = array(
					'username'=>$this->username,	//用户名
					'password'=>$this->password,	//密码 md5 32位数小写
					'fuwu'=>$service->shipping_method_code,	//渠道数字
					'danhao'=>$customer_number,	//订单号
					'guahao'=>'',	//挂号 （如需申请单号请留空）
					'contact'=>$order->consignee,	//收件人名称
					'gs'=>$order->consignee_company,	//收件人公司
					'tel'=>$order->consignee_phone,	//收件人电话,2)$order->consignee_mobile:收件人手机
					'sj'=>$order->consignee_mobile,	//收件人电话,2)$order->consignee_mobile:收件人手机
					'yb'=>$order->consignee_postal_code,	//邮编
					'country'=>$order->consignee_country_code,	//目的地国家 建议二字代码
					'state'=>$tmpConsigneeProvince,	//省份
					'cs'=>$order->consignee_city,	//城市
					'tto'=>$tmpAddressMode1,	//收件人地址
					'zzhong'=>0,	//包裹总重量 单位(克G)
					'title2'=>$e['pickingInfo'],	//配货信息（用来显示你的配货信息，或者其他货品信息）
					'zprice'=>0,	//货品总价值USD可以跟下面的价值相同
					'title'=>'',	//货品名称
					'shu'=>0,	//货品数量
					'zhong'=>0,	//货品单重量 (G克)
					'price'=>0,	//货品单价值USD
// 					'fk'=>'',	//付款时间
					'dp'=>'',	//所属店铺
					'bei'=>$e['remark'],	//备注
			);
			
			// object/array required  海关申报信息
			foreach ($order->items as $j=>$vitem){
				$orderMain['title'] .= $e['EName'][$j].',';
				$orderMain['shu'] += (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]);
				$orderMain['zhong'] += (empty($e['weight'][$j]) ? 0 : $e['weight'][$j]);
				$orderMain['price'] += (empty($e['DeclaredValue'][$j]) ? 0 : $e['DeclaredValue'][$j]);
				
				$orderMain['zprice'] += (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]) * $e['DeclaredValue'][$j];
				$orderMain['zzhong'] += (empty($e['DeclarePieces'][$j])?$vitem->quantity:$e['DeclarePieces'][$j]) * $e['weight'][$j];
			}
				
			$orderMain['title']=substr($orderMain['title'],0,-1);
			
			if (empty($orderMain['shu'])){
				return self::getResult(1, '', '数量不能为0');
			}
			
			if (empty($orderMain['zhong'])){
				return self::getResult(1, '', '重量不能为0');
			}
			
			if (empty($orderMain['price'])){
				return self::getResult(1, '', '金额不能为0');
			}
			
			if (empty($orderMain['title'])){
				return self::getResult(1, '', '报关名不能为空');
			}
			
// 			print_r($orderMain);
// 			exit;
				
			//多条商品信息列表
			$request = $orderMain;
				
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($request,1),"file");
			
			$url = self::$url_aj.'napi.asp';
			
			$getKeyValue = array();
			foreach($request as $key=>$value){
				$getKeyValue[] =  "$key=".urlencode(trim($value));
			}
			$url_params = '?'.implode("&", $getKeyValue);
			
			$response = $this->submitGate->mainGate($url, $url_params, 'curl', 'GET');
			
			//安骏的访问速度不能过快，这里限制为200000微秒
			usleep(200000);
			
// 			return print_r($response);
// 			exit;
			
// 			if($response['error']){return $response;}
			$response = $response['data'];
			
// 			$isSuccess = $this->getCallCarrierApiIsSuccess($response);
// 			if ($isSuccess){
// 				return self::getResult(1,'',$isSuccess);
// 			}
			
			$tmpArr = explode(",",$response);
			
// 			if(count($tmpArr) !== 2){
// 				throw new CarrierException($response);
// 			}

			if(count($tmpArr) < 2){
				throw new CarrierException($response);
			}

			if($tmpArr[0] == 'false'){
				$tmpError = str_replace("false,", "", $response);
				
				if(is_numeric($tmpError)){
					$isSuccess = $this->getCallCarrierApiIsSuccess($tmpError);
					if ($isSuccess){
						$tmpError1 = '';
						if($tmpError == '006')
							$tmpError1 = '或者凯翔达系统挂号不足，请等待我司补充';
						
						throw new CarrierException($isSuccess.$tmpError1);
					}
				}
				
				throw new CarrierException($tmpError);
			}
			
			//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
			$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$tmpArr[2],'');
			
			return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$tmpArr[2]);
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
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	**/
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
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
	 * @author		hqw		2015/08/12				初始化
	 +----------------------------------------------------------
	**/
	public function doPrint($data){
		try {
// 			$pdf = new \PDFMerger();
				
			$order = current($data);reset($data);
			$getAccountInfoOrder = $order['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,1,$getAccountInfoOrder);
			$shipped = $checkResult['data']['shipped'];
			$puid = $checkResult['data']['puid'];
			
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($getAccountInfoOrder);
			$account = $info['account'];// object SysCarrierAccount
			$service = $info['service'];// object SysShippingService
				
			$account_api_params = $account->api_params;//获取到帐号中的认证参数
			$normalparams = $service->carrier_params;
				
			$this->username = $account_api_params['username'];
			$this->password = $account_api_params['password'];
				
			$param = array();
			$url_params = '';
			$guahao=array();
			$g='';
			// object required 配置信息
			foreach ($data as $v) {
				//print_r($data);
				//print_r(count($data));
				$listorder = array();// object/array required 订单信息
				$oneOrder = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
				$shipped = $checkResult['data']['shipped'];
				
				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}
				$guahao=$shipped->tracking_number;
				//print_r($guahao);
				$g.=$guahao.',';			
			}
			//print_r($g);
			//取得打印尺寸
			$printFormat = $service['carrier_params']['printFormat'];
			$url_params = '?username='.$this->username.'&password='.$this->password.'&e='.(empty($printFormat) ? '1' : $printFormat).'&guahao='.$g;//$shipped->tracking_number;
			// 				$url_params = '?username='.$this->username.'&password='.$this->password.'&guahao='.'xxxx';
			//$url_params .= ','.$shipped->tracking_number;
			$url = self::$url_aj.'api5.asp';
			$response = self::get($url.$url_params,null,null,false,null,null,true);
			if(strlen($response)<1000){
				foreach ($data as $v){
					$oneOrder = $v['order'];
					$oneOrder->carrier_error = $response;
					$oneOrder->save();
				}
				return self::getResult(1, '', $response);
			}
			$pdfUrl = CarrierAPIHelper::savePDF($response,$puid,$account->carrier_code.'_'.$oneOrder->customer_number, 0);
// 			$pdf->addPDF($pdfUrl['filePath'],'all');
			foreach ($data as $v){
				$oneOrder = $v['order'];
// 				$oneOrder->is_print_carrier = 1;
				$oneOrder->print_carrier_operator = $puid;
				$oneOrder->printtime = time();
				$oneOrder->save();
			}
			
			//合并多个PDF  这里还需要进一步测试
// 			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl['filePath']='';
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取渠道信息列表
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/08/27				初始化
	 +----------------------------------------------------------
	 **/
	public function getChannelList(){
		$url = self::$url_aj.'api4.asp?username='.$this->username.'&password='.$this->password;
		$response = Helper_Curl::get($url);
		$response = explode("<br>",$response);
		$result = '';
		
		for($i=1; $i <= count($response); $i++){
			if (!empty($response[$i])){
				$tmpArr = explode("-",$response[$i]);
				$result .= $tmpArr[1].':'.$tmpArr[0].';';
			}
		}
		
		return $result;
	}
	
	/**
	 * 用来获取运输方式
	 *
	 * @author		hqw		2015/12/21				初始化
	 * 公共方法
	 **/
	public function getCarrierShippingServiceStr($account){
		//当传递进来的账号信息为null时，使用默认账号信息
		if($account == null){
			// TODO carrier user account @XXX@
			$this->username = '@XXX@';
			$this->password = '@XXX@';
		}else{
			$this->username = $account->api_params['username'];
			$this->password = $account->api_params['password'];
		}
		
		$url = self::$url_aj.'api4.asp?username='.$this->username.'&password='.$this->password;
		$response = Helper_Curl::get($url);
		
		$response = explode("<br>",$response);
		$result = '';
		
		for($i=1; $i <= count($response); $i++){
			if (!empty($response[$i])){
				$tmpArr = explode("-",$response[$i]);
				$result .= $tmpArr[1].':'.$tmpArr[0].';';
			}
		}
		
		if(empty($result)){
			return self::getResult(1, '', '');
		}else{
			return self::getResult(0, $result, '');
		}
	}
	
	/*
	 * 获取调用的api是否成功
	*/
	public function getCallCarrierApiIsSuccess($key){
		$arr = [
			'001'=>'帐号错误',
			'002'=>'密码错误',
			'003'=>'帐号被锁定',
			'004'=>'登录失败',
			'005'=>'订单号重复',
			'006'=>'提交的挂号在系统已经存在',
			'007'=>'挂号分配失败，请重新提交分配',
			'008'=>'系统挂号不足，请等待我司补充',
			'009'=>'渠道不存在或者已经关闭',
			'010'=>'预报提交失败，请再次尝试提交',
			'011'=>'挂号已预报，请勿重复预报。',
			'012'=>'订单号不存在',
			'013'=>'登录失败',
		];
		if(array_key_exists($key,$arr))return $arr[$key];
		else return false;
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
	
			//设置用户密钥Token
			$this->username = $print_param['username'];
			$this->password = $print_param['password'];
	
			$url_params = '?username='.$this->username.'&password='.$this->password.'&e=1&guahao='.$print_param['tracking_number'];
			
			$url = self::$url_aj.'api5.asp';
			$response = self::get($url.$url_params,null,null,false,null,null,true);
			
			if(strlen($response)<1000){
				return ['error'=>1, 'msg'=>'打印失败！错误信息：'.print_r($response,true), 'filePath'=>''];
			}
			
			$pdfPath = CarrierAPIHelper::savePDF2($response,$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
			
			return $pdfPath;
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
	
}

?>