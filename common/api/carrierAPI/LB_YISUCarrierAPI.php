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



class LB_YISUCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = 'http://api.espeedpost.com';	// 物流接口
	static private $wsdlgn = '';    //上传订单
	static private $wsdlcr = '';	// 获取物流接口
	static private $wsdlpr = '';      //打印接口
	static private $wsdltr = '';      //物流包裹查询接口(可用来获取跟踪号)
	private $submitGate = null;	// SoapClient实例
	
	public $appID = null;
	public $appKey = null;
	public $devid = 'LB01-80100';
	
	public function __construct(){
		self::$wsdlgn = self::$wsdl.'/shipping/';
		self::$wsdlcr = self::$wsdl.'/methodservice/';
		self::$wsdlpr=self::$wsdl.'/label/';
		self::$wsdltr=self::$wsdl.'/tracking/';
		$this->submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/08/29				初始化
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

			$this->appID = $a['appID'];
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
					
			$description='';
			$dezcription='';
			$quantity='';
			$weight='';
			$value='';
			$str='';
			$num="1";
			foreach ($order->items as $j=>$vitem){
				if($num>4)
					return self::getResult(1,'','一张订单最多允许4个商品上传');
				if(empty($e['EName'][$j]))
					return self::getResult(1,'','英文报关名称不能为空');
				if(empty($e['quantity'][$j]))
					return self::getResult(1,'','申报数量不能为空');
				if(empty($e['weight'][$j]))
					return self::getResult(1,'','申报重量不能为空');
				if(empty($e['DeclaredValue'][$j]))
					return self::getResult(1,'','申报金额不能为空');
				
				$description[]=$e['EName'][$j];
				$dezcription[]=$e['CN_Name'][$j];
				$quantity[]=empty($e['quantity'][$j])?1:$e['quantity'][$j];
				$weight[]=empty($e['weight'][$j])?0.1:$e['weight'][$j]/1000;
				$tmp_value=$e['quantity'][$j]*$e['DeclaredValue'][$j];
				$value[]=empty($tmp_value)?10:$tmp_value;
				$num++;
			}
			
			if($e['category']=="O" && $e['categorycontent']=="")
				return self::getResult(1,'','当申报类别为其它时，须注明其它类别名称');
			
			if(empty($order->consignee))
				return self::getResult(1,'','收件人姓名不能为空');
			if(empty($addressAndPhone['address_line1']))
				return self::getResult(1,'','收件人地址1不能为空');
			if(empty($order->consignee_phone))
				return self::getResult(1,'','电话不能为空');

			$request=array(
					'no'=>$customer_number,    //客户自定义包裹号
					'ordernumber'=>$order->order_source_order_id,  //订单号
					'methodservice'=>$service->shipping_method_code,
					'sellerid'=>$order->selleruserid,         //卖家ID
					'buyerid'=>$order->source_buyer_user_id,   //买家ID
						
// 					'ebayid'=>$order->order_source=='eaby'?'':'',      //卖家的eBay用户名
// 					'ebaysalesrecordnumber'=>$order->order_source=='eaby'?'':'',    //卖家的eBay销售记录号
// 					'ebayorderid'=>$order->order_source=='eaby'?'':'',    //卖家的eBay订单号
// 					'ebaytransactionid'=>$order->order_source=='eaby'?'':'',  //卖家的eBay交易号
					//传入上述4个值后客户可使用我司系统自动标记发货功能
					'label'=>$e['label'],     //自定义产品代码(标签),可显示在打印出来的地址标签上
					'category'=>$e['category'],        //申报类别
					'categorycontent'=>$e['category']=='O'?$e['categorycontent']:'',     //申报类别名称(可选)当category值为O-其它时，应传入该值,默认为"Other"
					'money'=>$order->currency,      //货币单位
					'recipient'=>$order->consignee,          //收件人姓名
					'address1'=>$addressAndPhone['address_line1'],     //收件人地址行1
					'address2'=>$addressAndPhone['address_line2'],         //收件人地址行2
					'city'=>$order->consignee_city,          //收件人城市
					'state'=>$order->consignee_province,        //收件人省份/州
					'zip'=>$order->consignee_postal_code,        //收件人邮编
					'tel'=>$order->consignee_phone,        //收件人电话
					'mobile'=>$order->consignee_mobile,      //收件人手机号
					'destination'=>$order->consignee_country_code,   //目的国家两位代码
					'email'=>$order->consignee_email,     //收件人电子邮件地址
					'insured'=>isset($e['insured'])?$e['insured']:'N',    //是否购买保险(可选)如购买保险,传入"Y",默认为"N"。目前仅针对香港挂号提供保险服务。
					'ifreturn'=>isset($e['ifreturn'])?$e['ifreturn']:'N',      //是否需要退件(可选)默认为"Y"-需要退件。如放弃退件,传入"N"
					'trackingnumber'=>'',
					'note'=>$e['note'],     //备注信息
					'description'=>$description,
					'dezcription'=>$dezcription,
					'quantity'=>$quantity,
					'weight1'=>$weight,
					'value1'=>$value,
			);
			$url_params=json_encode(['order'=>array($request)]);
			$url_params=str_replace(' ','%20',$url_params);
			
			//数据组织完成 准备发送
			#########################################################################
// 			\Yii::info(print_r($request,1),"file");
			$url_params='?todo=create&devid='.$this->devid.'&appid='.$this->appID.'&appkey='.$this->appKey.'&format=JSON&orders='.$url_params;
// 			print_r(self::$wsdlgn.$url_params);die;
			$response=Helper_Curl::get(self::$wsdlgn.$url_params);
			$channelArr=json_decode($response, true);
// 			print_r($channelArr);die;
			if($channelArr['result']=='success'){
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态
				$trackingnumber=empty($channelArr['trackingnumber'])?'':$channelArr['trackingnumber'];
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$trackingnumber,['OrderSign'=>$channelArr['sn']]);
				if(empty($trackingnumber)){
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'该货代无法立刻获取运单号,需要获取跟踪号后再确认发货完成');         //该货代无法立刻获取运单号,需要在 "物流模块->物流操作状态->待交运" 中获取跟踪号后再确认交运
				}
				else{
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$customer_number.'物流跟踪号:'.$trackingnumber);
				}
			}
			else
				return self::getResult(1,'',$channelArr['message'][0]['errorMsg']);
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 * 接口只能删除草稿的订单，所以该方法暂时没有用
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/08/29				初始化
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
	 * @author		lgw		2016/08/29				初始化
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运');
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
				
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];

			$tracking_number=$shipped['tracking_number'];
			if(empty($tracking_number)){
				return BaseCarrierAPI::getResult(1,'','跟踪号为空，无法交运，请先获取跟踪号');
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
	 * @author		lgw		2016/08/29				初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
// 		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			//获取到帐号中的认证参数
			$a = $account->api_params;
			
			$this->appID = $a['appID'];
			$this->appKey = $a['appKey'];
			
			$checkResult = CarrierAPIHelper::validate(1,1,$order);
			$shipped = $checkResult['data']['shipped'];
			$OrderSign=$shipped->return_no;

			$request=array(
					"devid=".$this->devid,
					"appid=".$this->appID,
					"appkey=".$this->appKey,
					"sn=".$OrderSign['OrderSign'],
			);
				
			$url=self::$wsdltr."?".implode("&", $request);
			$response = $this->submitGate->mainGate($url,null,'curl','get');

			if($response['error'])return self::getResult(1,'',$response['msg']);
			$response = $response['data'];
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);

			if($channelArr['result']=='success'){
				if(empty($channelArr['parcel']['trackingnumber']))
					return BaseCarrierAPI::getResult(1, '', '没有跟踪号获取');
				$shipped->tracking_number = $channelArr['parcel']['trackingnumber'];
				$shipped->save();
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				
				return BaseCarrierAPI::getResult(0, '', '获取成功！跟踪号：'.$channelArr['parcel']['trackingnumber']);
			}
			else{
				return self::getResult(1,'',$channelArr['message']['errorMsg']);
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/08/29				初始化
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
			
			$layout="";
			$customs="N";
			$ordersize="";
			if(!empty($carrier_params['layout'])){
				$layout=$carrier_params['layout'];
				if($layout=="ReMi")
					$layout='';
			}
			if(!empty($carrier_params['customs'])){
				$customs=$carrier_params['customs'];
			}
			if(!empty($carrier_params['ordersize'])){
				$ordersize=$carrier_params['ordersize'];
				if($ordersize=="N")
					$ordersize='';
			}
			//获取请求信息
			$this->appID = $account_api_params['appID'];
			$this->appKey = $account_api_params['appKey'];
			
			$tmpBarcodeStr = '';
			
			//获取追踪号
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
			
			$request=array(
					"devid=".$this->devid,
					"appid=".$this->appID,
					"appkey=".$this->appKey,
					"sn=".$tmpBarcodeStr,
					"layout=".$layout,
					"customs=".$customs,
					"ordersize=".$ordersize,
			);
			
			$url=self::$wsdlpr."?".implode("&", $request);
			$response = $this->submitGate->mainGate($url,null,'curl','get');
// 			print_r($response);die;
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
		
	/**
	 +----------------------------------------------------------
	 * 获取运输方式的值
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/08/29				初始化
	 +----------------------------------------------------------
	 **/
	public function getShippingServer($service){
		try{
			$response = $this->submitGate->mainGate(self::$wsdlcr, null, 'curl', 'get');

			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}

			if(empty($response['data']))
				return self::getResult(1,'','获取运输方式失败');
				
			$response=$response['data'];
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
				
			if(empty($channelArr['methodservice']))
				return self::getResult(1,'','获取运输方式失败');
				
			$channelArr=$channelArr['methodservice'];
			$result=array();
			foreach ($channelArr as $channelVal){
				if(!empty($channelVal['@attributes'])){
					if($channelVal['@attributes']['methodservice']==$service){
						$result=array(
							'method'=>$channelVal['@attributes']['method'],
							'service'=>$channelVal['@attributes']['service'],
						);
					}
				}
			}
			return $result;
		
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 用来获取运输方式
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/08/29				初始化
	 +----------------------------------------------------------
	 **/
	public function getCarrierShippingServiceStr($account){
		try{
			$response = $this->submitGate->mainGate(self::$wsdlcr, null, 'curl', 'get');
			if($response['error']){
				return self::getResult(1,'',$response['msg']);
			}
			
			if(empty($response['data']))
				return self::getResult(1,'','获取运输方式失败');
			
			$response=$response['data'];
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
			
			if(empty($channelArr['methodservice']))
				return self::getResult(1,'','获取运输方式失败');
			
			$channelArr=$channelArr['methodservice'];
			$result='';
			foreach ($channelArr as $channelVal){
				if(!empty($channelVal['@attributes'])){
					$result .= $channelVal['@attributes']['methodservice'].':'.$channelVal['@attributes']['value'].';';
				}
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
		$result = array('is_support'=>1,'error'=>0);
		
		try{		
			$this->appID = $data['appID'];
			$this->appKey = $data['appKey'];
			
			$request=array(
					"devid=".$this->devid,
					"appid=".$this->appID,
					"appkey=".$this->appKey,
					"sn=".'0000000000',
			);
			
			$url=self::$wsdltr."?".implode("&", $request);
			$response = $this->submitGate->mainGate($url,null,'curl','get');
				
			if($response['error'])
				$result['error'] = 1;
			$response = $response['data'];
			$channelArr=json_decode(json_encode((array) simplexml_load_string($response)), true);
				
			if($channelArr['message']['errorCode']=="1002"){
				$result['error'] = 1;
			}
			
		}catch(CarrierException $e){
		}
		
		return $result;
		
	}
}
?>