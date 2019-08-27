<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use Jurosh\PDFMerge\PDFMerger;
use common\helpers\Helper_Curl;
/**
 +------------------------------------------------------------------------------
 * LB_WANBEXPRESSCarrierAPI接口业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/4LB_WANBEXPRESSCarrierAPI
 * @subpackage  Exception
 * @author		akirametero
 * @version		1.0
 *
 * api url :http://apidoc.wanbexpress.com/
 +------------------------------------------------------------------------------
 */
class LB_WANBEXPRESSCarrierAPI extends BaseCarrierAPI
{
	public $soapClient = null; // SoapClient实例
	public $wsdl = null; // 物流接口

	private $_domain= 'http://api.wanbexpress.com';

	function __construct(){

	}
	
	// 上传订单
	public function getOrderNO($pdata){
		try{
			$order = $pdata['order'];// object OdOrder
			$data = $pdata['data'];

			//重复发货 添加不同的标识码
			$extra_id = isset($pdata['data']['extra_id'])?$pdata['data']['extra_id']:'';
			$customer_number = $pdata['data']['customer_number'];
				
			if(isset($pdata['data']['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
			$shipped = $checkResult['data']['shipped']; // object OdOrderShipped

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];// object SysCarrierAccount
			$service = $info['service'];// object SysShippingService
			$account_api_params = $account->api_params;//获取到帐号中的认证参数

			$addressAndPhoneParams = array(
				'address' => array(
					'consignee_address_line1_limit' => 500,
				),
				'consignee_district' => 1,
				'consignee_county' => 1,
				'consignee_company' => 1,
				'consignee_phone_limit' => 100
			);

			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);


			
			//组装收件人信息
			$ShippingAddress_arr= array();
			$ShippingAddress_arr['Company']= $order->consignee_company;
			$ShippingAddress_arr['Street1']= $addressAndPhone['address_line1'];
			$ShippingAddress_arr['City']= $order->consignee_city;
			$ShippingAddress_arr['Province']= $order->consignee_province;
			$ShippingAddress_arr['CountryCode']= (('UK' === $order->consignee_country_code) ? 'GB' : $order->consignee_country_code);
			$ShippingAddress_arr['Postcode']= $order->consignee_postal_code;
			$ShippingAddress_arr['Contacter']= $order->consignee;
			if( $order->consignee_mobile=='' ){
				$ShippingAddress_arr['Tel']= $order->consignee_mobile;
			}else{
				$ShippingAddress_arr['Tel']= $order->consignee_phone;
			}
			$ShippingAddress_arr['Email']= $order->consignee_email;



			$totalWeight = 0;
			$invoice = array();

			foreach ($order->items as $j=>$vitem){
				$invoice[$j]=[
				    // dzt20190510代客户反映再加    
// 				    'GoodsId'           => $data['GoodsId'][$j] ,// 如SKU,库存编号
					'GoodsTitle'		=> $data['DeclaredNameEn'][$j] ,	//货物描述
					'DeclaredNameEn'	=> $data['DeclaredNameEn'][$j] , // string required 海关申报品名
					'DeclaredNameCn'	=> $data['DeclaredNameCn'][$j] , // string 中文海关申报品名
					'Quantity'	=> empty($data['Quantity'][$j])?$vitem->quantity:$data['Quantity'][$j] , // int required 数量
					'DeclaredValue'=> (array('Code'=>'USD','Value'=>$data['DeclaredValue'][$j])) , // float required 单价
					'WeightInKg'=>$data['WeightInKg'][$j]/1000,

				];

				$totalWeight += $data['WeightInKg'][$j] * $invoice[$j]['Quantity'];
			}
			

			//组装提交的数据
			$post= array();
			$post['ReferenceId']= $customer_number;
			$post['ShippingAddress']= ($ShippingAddress_arr);
			$post['WeightInKg']= round($totalWeight / 1000 , 2);
			$post['ItemDetails']= $invoice;
			$post['TotalValue']= array( 'Code'=>'USD','Value'=>$data['TotalValue'] );
			$post['TotalVolume']= array('Height'=>$data['Height'],'Length'=>$data['Length'],'Width'=>$data['Width'],'Unit'=>'CM');
			$post['WithBatteryType']= $data['WithBatteryType'];
			$post['WarehouseCode']= $data['WarehouseCode'];
			$post['ShippingMethod']= $service->shipping_method_code;
			$post['ItemType']= $data['ItemType'];
			$post['Notes']= $data['remark'];

			$post_str= json_encode($post);

			$url= $this->_domain.'/api/parcels';
			$AccountNo= $account_api_params['AccountNo'];
			$Token= $account_api_params['Token'];
			$Nounce= time();
			$requestHeader = array(
				"Accept:application/json",
				"Authorization: Hc-OweDeveloper {$AccountNo};{$Token};{$Nounce}",
				"Host: ".str_replace('http://','',$this->_domain),
				"Content-Type:application/json"
			);

			$result= Helper_Curl::post( $url,$post_str,$requestHeader );
			$res= json_decode( $result,true );
			if( isset( $res['Succeeded'] ) && $res['Succeeded']===true ){
				//上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态（浩远直接返回物流号但不一定是真的，所以跳到第2步），跟踪号(选填),returnNo(选填)
				$r = CarrierApiHelper::orderSuccess( $order , $service , $res['Data']['ReferenceId'] , OdOrder::CARRIER_WAITING_DELIVERY,'',$res['Data']);
				return  self::getResult(0,$r, "上传订单成功！");
			}else{
				//print_r ($res);
				return self::getResult(1, '', '上传订单失败！错误信息：' . $res['Error']['Code'] .';'.$res['Error']['Message'] );
			}

			
		}catch (CarrierException $e){
			return self::getResult(1,'',"file:".$e->getFile()." line:".$e->getLine()." ".$e->msg());
		}
	}
	
	// 取消订单
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');
	}
	
	// 交运
	public function doDispatch($data){
		try {
			$user = \Yii::$app->user->identity;
			if (empty($user)) return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();

			//订单对象
			$order = $data['order'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0, 1, $order);
			$shipped = $checkResult['data']['shipped'];
			$return_no= $shipped->return_no;
			if( empty( $return_no ) || !isset( $return_no['ProcessCode'] ) ){
				return self::getResult(1, '', '交运信息缺失1');
			}
			$ProcessCode= $return_no['ProcessCode'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$a = $info['account'];
			$service = $info['service'];// 运输服务相关信息

			//获取到帐号中的认证参数
			$api_params = $a->api_params;
			$AccountNo= $api_params['AccountNo'];
			$url= $this->_domain."/api/parcels/{$ProcessCode}/confirmation";
			//print_r ($url);exit;
			$Token= $api_params['Token'];
			$Nounce= time();
			$requestHeader = array(
				"Accept:application/json",
				"Authorization: Hc-OweDeveloper {$AccountNo};{$Token};{$Nounce}",
				"Host: ".str_replace('http://','',$this->_domain),
				"Content-Type:application/json"
			);
			$res= Helper_Curl::post( $url,null,$requestHeader );
			$result= json_decode($res,true);

			if( isset( $result['Succeeded'] ) && $result['Succeeded']===true ){

				$shipped->tracking_number = $result['Data']['TrackingNumber'];
				$shipped->save();
				$order->carrier_step = OdOrder::CARRIER_WAITING_GETCODE;
				$order->tracking_number = $result['Data']['TrackingNumber'];
				$order->save();
				//print_r ($result);exit;
				return BaseCarrierAPI::getResult(0, '', '订单交运成功！已生成服务商单号：'.$result['Data']['TrackingNumber']);
			}else{
				return self::getResult(1, '', '交运失败！错误信息：' . $result['Error']['Code'] .';'.$result['Error']['Message'] );
			}
		}catch(CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}


	}
	
	// 获取物流号跟踪号
	public function getTrackingNO($data){
		try {
			$user = \Yii::$app->user->identity;
			if (empty($user)) return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();

			//订单对象
			$order = $data['order'];

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0, 1, $order);
			$shipped = $checkResult['data']['shipped'];
			$return_no= $shipped->return_no;
			if( empty( $return_no ) || !isset( $return_no['ProcessCode'] ) ){
				return self::getResult(1, '', '交运信息缺失1');
			}
			$ProcessCode= $return_no['ProcessCode'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$a = $info['account'];
			$service = $info['service'];// 运输服务相关信息

			//获取到帐号中的认证参数
			$api_params = $a->api_params;
			$AccountNo= $api_params['AccountNo'];
			$url= $this->_domain."/api/parcels/{$ProcessCode}";
			$Token= $api_params['Token'];

			$Nounce= time();
			$requestHeader = array(
				"Accept:application/json",
				"Authorization: Hc-OweDeveloper {$AccountNo};{$Token};{$Nounce}",
				"Host: ".str_replace('http://','',$this->_domain),
				"Content-Type:application/json"
			);
			$res= Helper_Curl::get( $url,null,$requestHeader );
			$result= json_decode($res,true);
			if( isset( $result['Succeeded'] ) && $result['Succeeded']===true ){
				$TrackingNoProcessResult= $result['Data']['TrackingNoProcessResult'];
				if( $TrackingNoProcessResult['Code']=='Success' ){
					$FinalTrackingNumber= $result['Data']['FinalTrackingNumber'];
					$shipped->tracking_number = $FinalTrackingNumber;
					$shipped->save();
					$order->tracking_number = $FinalTrackingNumber;
					$order->save();
					return BaseCarrierAPI::getResult(0, '',"获取跟踪号:{$FinalTrackingNumber}成功！");
				}else{
					return self::getResult(1,'','物流商还未返回跟踪号，请稍后再试');
				}
			}else{
				return self::getResult(1,'','物流接口返回错误');
			}
		
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
		
	}
	
	//打印物流单
	public function doPrint($data){

		try {
			$user = \Yii::$app->user->identity;
			if (empty($user)) return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();

			//订单对象
			$order = current($data);reset($data);
			$order = $order['order'];

			$ProcessCode_arr= array();
			foreach( $data as $vd ){
				$order = $vd['order'];
				//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
				$checkResult = CarrierAPIHelper::validate(1, 1, $order);
				$shipped = $checkResult['data']['shipped'];
				$return_no = $shipped->return_no;
				if (empty($return_no) || !isset($return_no['ProcessCode'])) {
					return self::getResult(1, '', '交运信息缺失');
				}
				$ProcessCode = $return_no['ProcessCode'];
				$ProcessCode_arr[]= $ProcessCode;

				//echo $ProcessCode.PHP_EOL;
			}



			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$a = $info['account'];

			//获取到帐号中的认证参数
			$api_params = $a->api_params;
			$AccountNo= $api_params['AccountNo'];
			$Token= $api_params['Token'];

			if( count($ProcessCode_arr)>1 ){
				$processCodes= urlencode(implode('|',$ProcessCode_arr));
				$url= $this->_domain."/api/parcels/labels?processCodes={$processCodes}";
			}else{
				$url= $this->_domain."/api/parcels/{$ProcessCode_arr[0]}/label";
			}



			$Nounce= time();
			$requestHeader = array(
				"Accept:application/json",
				"Authorization: Hc-OweDeveloper {$AccountNo};{$Token};{$Nounce}",
				"Host: ".str_replace('http://','',$this->_domain),
				"Content-Type:application/json"
			);
			$res= Helper_Curl::get( $url,null,$requestHeader );
			//print_r ($res);exit;
			if(strlen($res) < 1000){
				return ['error'=>1, 'filePath'=>'', 'msg'=>'打印失败！错误信息：接口返回内容不是一个有效的PDF'];
			}
			//$result= json_decode($res,true);
			$pdfPath = CarrierAPIHelper::savePDF2($res, $puid, $order->order_id.$order->customer_number."_api_".time());
			//print_r ($pdfPath);exit;
			return self::getResult(0,['pdfUrl'=>$pdfPath['filePath']],'连接已生成,请点击并打印');
			//return BaseCarrierAPI::getResult(0,'','pdf下载后打印:'.$pdfPath['filePath']);

		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}


		return BaseCarrierAPI::getResult(1, '', '物流接口不支持打印物流单。');
	}
	
	/*
	 * 用来确定打印完成后 订单的下一步状态
	*/
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}


	public function getCarrierShippingServiceStr($data)
	{

		$user = \Yii::$app->user->identity;
		if (empty($user)) return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
		$puid = $user->getParentUid();


		//获取到帐号中的认证参数
		$api_params = $data->api_params;
		$AccountNo= $api_params['AccountNo'];
		$url= $this->_domain."/api/services";
		//print_r ($url);exit;
		$Token= $api_params['Token'];
		$Nounce= time();
		$requestHeader = array(
			"Accept:application/json",
			"Authorization: Hc-OweDeveloper {$AccountNo};{$Token};{$Nounce}",
			"Host: ".str_replace('http://','',$this->_domain),
			"Content-Type:application/json"
		);
		$res= Helper_Curl::get( $url,null,$requestHeader );
		$result= json_decode($res,true);

		if( isset( $result['Succeeded'] ) && $result['Succeeded']===true ){
			$str = '';
			foreach( $result['Data']['ShippingMethods'] as $vs ){
				$str.= str_replace('：',':',$vs['Name']).';';
			}

			return BaseCarrierAPI::getResult(0, $str,'');
		}else{
			return BaseCarrierAPI::getResult(1, '', '运输方式更新失败');
		}


	}
	
	################################ 全程动力其他获取信息接口  #############################################
	
	// 获取运输方式列表
	public function getshippingmethod(){
		try {

		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}

	
}



?>