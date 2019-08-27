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

class LB_WANOUCarrierAPI extends BaseCarrierAPI
{
	static private $wsdl = '';	// 物流接口
	
	public $accountNo=null;
	public $token=null;
			
	public function __construct(){
// 		if(isset(\Yii::$app->params["currentEnv"]) and \Yii::$app->params["currentEnv"]=='production' ){
			self::$wsdl = 'http://api.oneworldexpress.cn/';   //正式
// 		}
// 		else
// 			self::$wsdl = 'http://api-sbx.oneworldexpress.cn/';   //测试环境
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/09/02				初始化
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
			
			$this->accountNo=$account->api_params['accountNo'];
			$this->token=$account->api_params['token'];
						
			$senderAddressInfo=$info['senderAddressInfo'];
			$shippingfrom=$senderAddressInfo['shippingfrom'];
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 1000,
							'consignee_address_line2_limit' => 1000,
							'consignee_address_line3_limit' => 1000,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 100
			);
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
			
			$weightcount=0;
			$TotalValue=0;
			$request=array();
			foreach ($order->items as $j=>$vitem){
				if(empty($e['goodsTitle'][$j]))
					return self::getResult(1, '', '货物描述不能为空');
				if(empty($e['EName'][$j]))
					return self::getResult(1, '', '英文报关名称不能为空');
				if(empty($e['CN_Name'][$j]))
					return self::getResult(1, '', '中文报关名称不能为空');
				
				$request[]=[
					'GoodsTitle'=>$e['goodsTitle'][$j], //货物描述
					'DeclaredNameEn'=>$e['EName'][$j],         //英文报关名称
					'DeclaredNameCn'=>$e['CN_Name'][$j],       //中文报关名称
					'DeclaredValue'=>array(
							'Code'=>'USD',
							'Value'=>empty($e['DeclaredValue'][$j])?'0':$e['DeclaredValue'][$j],    //申报价值
					),
					'WeightInKg'=>empty($e['weight'][$j])?'0':$e['weight'][$j]/1000, //重量
					'Quantity'=>empty($e['quantity'][$j])?'0':$e['quantity'][$j],         //件数
				];
				$weightcount +=(empty($e['weight'][$j])?0:$e['weight'][$j]/1000)*(empty($e['quantity'][$j])?'0':$e['quantity'][$j]);
				$TotalValue +=(empty($e['DeclaredValue'][$j])?0:$e['DeclaredValue'][$j])*(empty($e['quantity'][$j])?'0':$e['quantity'][$j]);
			}
			
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($order->consignee_province)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
			
			if(empty($addressAndPhone['address_line1']))
				return self::getResult(1, '', '地址1不能为空');
			if(empty($order->consignee_city))
				return self::getResult(1, '', '城市不能为空');
			if(empty($order->consignee_postal_code))
				return self::getResult(1, '', '邮编不能为空');
			if(empty($order->consignee))
				return self::getResult(1, '', '收件人不能为空');
// 			if(empty($order->consignee_email))
// 				return self::getResult(1, '', '邮箱不能为空');

			$requestArr=[
				'ReferenceId'=>$customer_number,    //客户订单号
				'ShippingAddress'=>array(
							'Company'=>$order->consignee_company,    //公司
							'Street1'=>$addressAndPhone['address_line1'],    //街道1
							'Street2'=>$addressAndPhone['address_line2'],    //街道2
							'Street3'=>$addressAndPhone['address_line3'],    //街道3
							'City'=>$order->consignee_city,    
							'Province'=>$tmpConsigneeProvince,
							'CountryCode'=>$order->consignee_country_code=='UK'?'GB':$order->consignee_country_code,
							'Postcode'=>$order->consignee_postal_code,  //邮编
							'Contacter'=>$order->consignee,   //联系人
							'Tel'=>$order->consignee_phone,     //电话
							'Email'=>$order->consignee_email,      //邮箱地址
						),
				'WeightInKg'=>$weightcount,  //包裹重量
				'ItemDetails'=>$request,
				'TotalValue'=>array(
						'Code'=>'USD',
						'Value'=>$TotalValue,   //包裹总金额
				),
				'TotalVolume'=>array(           //尺寸
							'Height'=>empty($e['height'])?'1':$e['height'],
							'Length'=>empty($e['length'])?'1':$e['length'],
							'Width'=>empty($e['width'])?'1':$e['width'],
							'Unit'=>'CM',
						),
				'WithBatteryType'=>$e['withBatteryType'],        //是否带电
				'Notes'=>$e['Notes'],     //备注
				'WarehouseCode'=>$service['carrier_params']['warehouseCode'],     //交货仓库代码
				'ShippingMethod'=>$service->shipping_method_code,
				'ItemType'=>$e['itemType'],     //包裹类型
				'AutoConfirm'=>'true',        //为true时自动交运。
			];
	// 		#########################################################################
			$request=json_encode($requestArr);

			$response=$this->link($account->api_params['accountNo'],$account->api_params['token'],self::$wsdl.'api/parcels','POST',$request);
			$response=json_decode($response);
// 			print_r($response);die;
			if($response->Succeeded=='true'){
				$trackingnumber=$response->Data->TrackingNumber;
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,$trackingnumber,['delivery_orderId'=>$response->Data->ProcessCode]);
				if(empty($trackingnumber))
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$customer_number.',该货代无法立刻获取跟踪号,需要获取跟踪号后再确认发货完成');
				else
					return  BaseCarrierAPI::getResult(0,$r,'操作成功!客户单号为:'.$customer_number.'物流跟踪号:'.$trackingnumber);
			}
			else{
				return self::getResult(1,'',$response->Error->Message);
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
		try{
			$order = $data['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
				
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
				
			if(empty($shipped['return_no']['delivery_orderId']))
				return self::getResult(1,'','获取订单号失败');
								
			$response=$this->link($account['api_params']['accountNo'],$account['api_params']['token'],self::$wsdl.'api/parcels/'.$shipped['return_no']['delivery_orderId'],'DELETE','',1);
			$response=json_decode($response);
			if($response->Succeeded=='true'){
				return self::getResult(0,'','删除成功');
			}
			else
				return self::getResult(1,'',$response->Error->Message);
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/09/02				初始化
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
			
			if(empty($shipped['return_no']['delivery_orderId']))
				return self::getResult(1,'','获取订单号失败');
			
// 			$tracking_number=$shipped['tracking_number'];
// 			if(empty($tracking_number)){
// 				return BaseCarrierAPI::getResult(1,'','跟踪号为空，无法交运，请在‘待交运’中先获取跟踪号');
// 			}
			
			$response=$this->link($account['api_params']['accountNo'],$account['api_params']['token'],self::$wsdl.'api/parcels/'.$shipped['return_no']['delivery_orderId'].'/confirmation','POST');
			$response=json_decode($response);

			if(empty($response)){
				return self::getResult(1,'','交运失败');
			}
			else{
				if(isset($response->Succeeded) && $response->Succeeded){
					$order->carrier_step = OdOrder::CARRIER_WAITING_PRINT;
					$order->save();
					if(empty($response->Data->TrackingNumber))
						return BaseCarrierAPI::getResult(0, '', '订单交运成功！该货代无法立刻获取跟踪号,需要获取跟踪号后再确认发货完成');
					else
						return BaseCarrierAPI::getResult(0, '', '订单交运成功！跟踪号为:'.$response->Data->TrackingNumber);
				}
				else
					return BaseCarrierAPI::getResult(1, '', '订单交运失败！');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/09/02				初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		try{
				$order = $data['order'];
				//获取到所需要使用的数据
				$info = CarrierAPIHelper::getAllInfo($order);
				$account = $info['account'];
				
				//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
				$checkResult = CarrierAPIHelper::validate(0,1,$order);
				$shipped = $checkResult['data']['shipped'];
				
				if(empty($shipped['return_no']['delivery_orderId']))
					return self::getResult(1,'','获取跟踪号失败');
				
				$response=$this->link($account['api_params']['accountNo'],$account['api_params']['token'],self::$wsdl.'api/parcels/'.$shipped['return_no']['delivery_orderId'].'');
				$response=json_decode($response);
// 				print_r($response);die;
				if(empty($response)){
					return self::getResult(1,'','获取跟踪号失败');
				}
				else{
					$response=$response->Data;
					if($response->TrackingNoProcessResult->Code=="Success"){
						$shipped->tracking_number = $response->FinalTrackingNumber;
						$shipped->save();
						$order->tracking_number = $shipped->tracking_number;
						$order->save();
						return BaseCarrierAPI::getResult(0, '', '获取成功！跟踪号：'.$response->FinalTrackingNumber);
					}
					else if($response->TrackingNoProcessResult->Code=="Processing"){
						return BaseCarrierAPI::getResult(1, '', '跟踪号还没有返回');
					}
					else{
						return BaseCarrierAPI::getResult(1, '', '获取跟踪号失败,请修改信息后重新上传订单，原因: '.$response->TrackingNoProcessResult->Message);
					}
				}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lgw		2016/09/02				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
						
			$order = current($data);reset($data);
			$order = $order['order'];
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
			
			//取得打印类型
			$printType="buylogicLabel10Pdf";    
			if(!empty($service['carrier_params']['printType']))
				$printType = $service['carrier_params']['printType'];
			
			$package_sn = '';
			foreach ($data as $k => $v) {
				$order = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				$package_sn .= $shipped['return_no']['delivery_orderId']."|";
			}
			$package_sn=substr($package_sn,0,-1);

			$response=$this->link($account->api_params['accountNo'], $account->api_params['token'], self::$wsdl.'api/parcels/labels?processCodes='.$package_sn);
			$responsearr=json_decode($response);
// 			print_r($response);die;
			if(isset($responsearr->Succeeded) && empty($responsearr->Succeeded))
				return self::getResult(1,'',$responsearr->Error->Message);
			if(strlen($response)<1000){
				return self::getResult(1,null,$response);
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
		
	//用来获取运输仓库
	public function getWarehouses(){
		try{
			$response=$this->link('', '', self::$wsdl.'api/warehouses','GET','',1);
			if(is_null($response))
				return self::getResult(1, '', '');
			$result='';
			$response=json_decode($response);

			if($response->Succeeded==1){
				$channelArr=$response->Data->Warehouses;
				foreach ($channelArr as $channelArrone){
					$result .=$channelArrone->Code.":".$channelArrone->Name.";";
				}
			}
			
			if(empty($result)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $result, '');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	//用来获取运输方式
	public function getCarrierShippingServiceStr($account){
		try{
			// TODO carrier user account @XXX@
			$accountNo_tmp=empty($account->api_params['accountNo'])?'@XXX@':$account->api_params['accountNo'];
			$token_tmp=empty($account->api_params['token'])?'@XXX@':$account->api_params['token'];
			
			$response=$this->link($accountNo_tmp, $token_tmp, self::$wsdl.'api/services');
			if(is_null($response))
				return self::getResult(1, '', '');

			$result='';
			$response=json_decode($response);

			if($response->Succeeded==1){
				$channelArr=$response->Data->ShippingMethods;
				foreach ($channelArr as $channelArrone){
					$arr=explode(":",$channelArrone->Name);
					$result .=$channelArrone->Code.":".$arr[1].";";
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
		$result = array('is_support'=>1,'error'=>1);

		try{
			$response=$this->link($data['accountNo'], $data['token'], self::$wsdl.'api/whoami');

			if(!is_null($response)){
				$response=json_decode($response);
				if(isset($response->Succeeded) && $response->Succeeded==1){
					$result['error'] = 0;
				}
			}
		}catch(CarrierException $e){}
	
		return $result;
	
	}
	
	//提交
	private function link($AccountNo='',$Token='',$url='',$curl='GET',$pram=null,$test=0){
		if($test===1){
			// TODO carrier user account @XXX@
			$AccountNo='@XXX@';
			$Token='@XXX@';
		}

		$response=null;
		try{
			$header = array();
			$header[] = 'Accept:application/json';
			$header[] = 'Authorization:Hc-OweDeveloper '.$AccountNo.';'.$Token.';'.$this->getRandomString(32);
			$header[] = 'Content-type:application/json';
			if($curl=='POST'){
				$response=Helper_Curl::post($url,$pram,$header);
			}
			else if($curl=='DELETE'){
				$response=Helper_Curl::delete($url,null,$header);
			}
			else{
				$response=Helper_Curl::get($url,null,$header);
			}
		}
		catch(CarrierException $e){}
		
		return $response;
	}
	
	private function getRandomString($len, $chars=null)
	{
		if (is_null($chars)){
			$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";     //abcdefghijklmnopqrstuvwxyz
		}
		mt_srand(10000000*(double)microtime());
		for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++){
			$str .= $chars[mt_rand(0, $lc)];
		}
		return $str;
	}
	

	
}?>