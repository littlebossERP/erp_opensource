<?php
namespace common\api\carrierAPI;

use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\helpers\Helper_Curl;
use Jurosh\PDFMerge\PDFMerger;

class LB_ISHIPPERCarrierAPI extends BaseCarrierAPI
{
	static private $url_ishipper = '';	//物流接口
	public function __construct(){
		self::$url_ishipper = 'http://ishipper.e56.com/ishipper/openapi/user/1.2';
		$this->submitGate = new SubmitGate();
	}
	/**
	 * [getOrderNO 申请订单号]
	 * @Author   willage
	 * @DateTime 2016-07-14T18:13:44+0800
	 * @param    [type]                   $pdata [description]
	 * @return   [type]                          [description]
	 */
	public function getOrderNO($data){
	try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			/**
			 * [$order odOrder表内容]
			 * @var [type]
			 */
			$order = $data['order'];
			/**
			 * [$submitdata 用户在确认页面提交的数据]
			 * @var [type]
			 */
			$submitdata = $data['data'];
			/**
			 * [$info 物流商设置参数]
			 * @var [type]
			 */
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
			/**
			 * [$extra_id 添加不同的标识码]
			 * @var [type]
			 */
			$extra_id = isset($submitdata['extra_id'])?$submitdata['extra_id']:'';
			$customer_number = $submitdata['customer_number'];
			if(isset($submitdata['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}
			/**
			 * [$checkResult 对当前条件的验证:0 如果订单已存在,则报错;1 订单不存在,则报错]
			 * @var [type]
			 */
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
			/**
			 * [$json_string 获取数据组织JSON]
			 * @var [type]
			 */
			list($status,$json_string) = self::SetData_getOrderNO($order,$account,$service,$submitdata);
			if($status){
				return self::getResult(1,'',$json_string);
			}
			/**
			 * [$url_getOrderNO 设置URL]
			 * @var [type]
			 */
			$url_getOrderNO = self::$url_ishipper.'/addOrUpdateOrder';
			/**
			 * [$response 物流平台上传订单响应]
			 * @var [type]
			 */
			$response = $this->submitGate->mainGate($url_getOrderNO, $json_string, 'curl','POST');
			$resp_ishipper = json_decode($response['data'],true);
			if($response['error'])
				return self::getResult(1,'',$response['msg']);
			if($resp_ishipper['Status'] != 'success')
				return self::getResult(1,'',$resp_ishipper['ErrorMessage']);
			if ($resp_ishipper['Result'][0]['Status'] == 'fail'){
				return self::getResult(1,'',$resp_ishipper['Result'][0]['Error']);
			}
			if ($resp_ishipper['Result'][0]['Status'] == 'success') {
				/**
				 * [$saveresp 进行数据的保存:订单/运输服务/客户号/订单状态/跟踪号(选填)/returnNo(选填)]
				 * @var [type]
				 */
				$trackingNo = $resp_ishipper['Result'][0]['TrackingNo'];
				$reOrderNo = $resp_ishipper['Result'][0]['OrderId'];//客方参考号
				$saveresp = CarrierAPIHelper::orderSuccess($order,$service,$reOrderNo,OdOrder::CARRIER_WAITING_PRINT,$trackingNo,['ParcelId'=>$resp_ishipper['Result'][0]['ParcelId']]);
				return  self::getResult(0,$saveresp,'操作成功!订单号:'.$reOrderNo.' 追踪条码:'.$trackingNo);
			}
			return self::getResult(1,'',"未知错误，请联系小老板技术");
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}

		
	}

	/**
	 * [getTrackingNO 申请跟踪号]
	 * @Author   willage
	 * @DateTime 2016-07-14T18:16:09+0800
	 * @param    [type]                   $data [description]
	 * @return   [type]                         [description]
	 */
	public function getTrackingNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口申请订单号时就会返回跟踪号');
	}

	/**
	 * [doPrint 打单，允许批量操作]
	 * @Author   willage
	 * @DateTime 2016-07-14T18:16:50+0800
	 * @param    [type]                   $data [description]
	 * @return   [type]                         [description]
	 */
	public function doPrint($data = ''){
		try{
				$user=\Yii::$app->user->identity;
				if(empty($user))return  self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
				$puid = $user->getParentUid();
				/**
				 * [$order odOrder表内容（多个订单的物流配置是一样的，不用foreach）]
				 * @var [type]
				 */
				$order = current($data);reset($data);
				$order = $order['order'];
				/**
				 * [$info 物流商设置参数]
				 * @var [type]
				 */
				$info = CarrierAPIHelper::getAllInfo($order);
				$account = $info['account'];
				$service = $info['service'];
				/**
				 * [$url_doPrint 设置URL]
				 * @var [type]
				 */
				$url_doPrint = self::$url_ishipper.'/printOrderByLabelType';
				/**
				 * [$json_string 获取数据组织JSON]
				 * @var [type]
				 */
				$json_string = self::SetData_doPrint($data,$account,$service);
				/**
				 * [$response 物流平台打印数据流]
				 * @var [type]
				 */
				$response = $this->submitGate->mainGate($url_doPrint, $json_string, 'curl','POST');
				if($response['error'])return self::getResult(1,'',$response['msg']);
				$pdf_data = $response['data'];
				if(strlen($pdf_data)<1000){
					/**
					 *[foreach 数据过少视为失败]
					 */
					foreach ($data as $v){
						$oneOrder = $v['order'];
						$oneOrder->carrier_error = $pdf_data;
						$oneOrder->save();
					}
					$pdf_array = json_decode($pdf_data,true);
					return self::getResult(1, '', $pdf_array["ErrorMessage"]);
				}else{
					/**
					 * [foreach 批量保存订单信息]
					 */
					foreach ($data as $v){
						$oneOrder = $v['order'];
// 						$oneOrder->is_print_carrier = 1;
						$oneOrder->print_carrier_operator = $puid;
						$oneOrder->printtime = time();
						$oneOrder->save();
					}
				}
				/**
				 * [$pdfurl pdf保存链接]
				 * @var [type]
				 */
				$pdfurl = CarrierAPIHelper::savePDF($pdf_data,$puid,$account->carrier_code);
				return self::getResult(0,['pdfUrl'=>$pdfurl],'物流单已生成,请点击页面中打印按钮');
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}

	}
	/**
	 * [cancelOrderNO 取消订单]
	 * @Author   willage
	 * @DateTime 2016-07-15T09:59:33+0800
	 * @param    [type]                   $data [description]
	 * @return   [type]                         [description]
	 */
	public function cancelOrderNO($data){
		return self::getResult(1, '', '结果：该物流商API不支持取消订单功能');
	}
	/**
	 * [doDispatch 交运]
	 * @Author   willage
	 * @DateTime 2016-07-15T10:00:04+0800
	 * @param    [type]                   $data [description]
	 * @return   [type]                         [description]
	 */
	public function doDispatch($data){
        return self::getResult(1, '', '结果：该物流商API不支持交运订单功能');
	}

	/**
	 * [getShippingType 获取运输方式]
	 * @Author   willage
	 * @DateTime 2016-07-21T15:55:43+0800
	 * @return   [type]                   [description]
	 */
	public function getShippingType(){
		//设置URL
		$url_getShippingType = self::$url_ishipper.'/listShipwayCodes';
		//获取数据组织JSON文件
		$json_string = array('ApiToken' => '977037d801c67a90fe29473dc1c88da3');
		$request_label = json_encode($json_string);
		//var_dump($json_string);
		//发送
		$response = $this->submitGate->mainGate($url_getShippingType, $request_label, 'curl','POST');
		//提取运输方式，按格式（运输代码:运输方式;）保存
		$temp = json_decode($response['data'],true);
		$req = '';
		foreach ($temp["Result"] as $key => $value) {
		 	$req .= $value["Code"].":".$value["Name"].";";
		}
	}

/**
 * [SetData_getOrderNO 组织上传订单的json数据]
 * @Author   willage
 * @DateTime 2016-07-21T09:39:47+0800
 * @param    string                   $order      [description]
 * @param    string                   $account    [description]
 * @param    string                   $service    [description]
 * @param    string                   $submitdata [description]
 */
	static public function SetData_getOrderNO($order = "",$account = "",$service = "",$submitdata= ""){
		$array_string = [];
		$Success = 0;
		$Fail = 1;
		/**
		 * [$addressAndPhoneParams 统一电话地址（这里limit并不准确）]
		 * @var array
		 */
		$addressAndPhoneParams = array(
			'address' => array(
				'consignee_address_line1_limit' => 60,
				'consignee_address_line2_limit' => 60,
			),
			'consignee_district' => 1,
			'consignee_county' => 1,
			'consignee_company' => 1,
			'consignee_phone_limit' => 100
		);
		$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
		$array_string["ApiToken"] = $account->api_params["ApiToken"];
		$array_string["OrderList"][] = array(
			"OrderId" => $submitdata['customer_number'],
			"ShipwayCode" => $service->shipping_method_code,
			"SellerAccountName" => $order->selleruserid,
			"BuyerId" => $order->source_buyer_user_id,
			"ReceiverName" => $order->consignee,
			"AddressLine1" => $addressAndPhone['address_line1'],
			"AddressLine2" => $addressAndPhone['address_line2'],
			"City" => $order->consignee_city,
			"State" => $order->consignee_province,
			"CountryCode" => $order->consignee_country_code,
			"PhoneNumber" => $addressAndPhone['phone1'],
			"PostCode" => $order->consignee_postal_code,
			"Email" => "",//长度不能超过45，这里统一不填//$order->consignee_email,
			"Remark" => $order->desc,
		);
		if (count($order->items)){
			foreach ($order->items as $aitem){
				$array_string["OrderList"][0]["OrderItemList"][]=array(
					"Title" => $aitem->product_name,
					"ImgUrl" => "",
					"ItemUrl" => "",
					"Sku" => $aitem->sku,
					"Quantity" => $aitem->quantity
					);
			}
		}
		$array_string["OrderList"][0]["OrderCustoms"] = array(
			"Currency" => $order->currency,
			"CustomsType" => $service->carrier_params["CustomsType"],
			);
		if (count($order->items)){
			foreach ($order->items as $itemkey => $bitem){
				if (empty($submitdata['weight'][$itemkey])) {
					return array($Fail,"申报重量g 要求大于0");
				}
				if (empty($submitdata['DeclaredValue'][$itemkey])) {
					return array($Fail,"申报金额(USD) 要求大于0");
				}
				$array_string["OrderList"][0]["OrderCustoms"]["CustomsItemList"][0]=array(
						"Quantity" => $bitem->quantity,
						"DescriptionEn" => $submitdata['EName'][$itemkey],
						"DescriptionCn" => $submitdata['Name'][$itemkey],
						"Weight" => $submitdata['weight'][$itemkey]/1000,//物流商要求默认单位为KG
						"Value" => $submitdata['DeclaredValue'][$itemkey]
					);
			}
		}
		$json_string = json_encode($array_string);
		//\Yii::info('willage:'.$json_string, "file");
		return array($Success,$json_string);
	}

/**
 * [SetData_doPrint 组织打印订单的json数据]
 * @Author   willage
 * @DateTime 2016-07-21T09:40:25+0800
 * @param    string                   $order      [description]
 * @param    string                   $account    [description]
 * @param    string                   $service    [description]
 * @param    string                   $submitdata [description]
 */
	static public function SetData_doPrint($data = "",$account = "",$service = ""){
		$array_string = [];
		$array_string = array(
		"ApiToken" => $account->api_params["ApiToken"],
		"LabelFormat" =>
			isset($service->carrier_params['LabelFormat'])?$service->carrier_params['LabelFormat']:'Label_100x100',
		"OutPutFormat"=>"pdf",
		"PrintBuyer"=>
			isset($service->carrier_params['PrintBuyer'])?$service->carrier_params['PrintBuyer']:"",
		"PrintSalesOrderNo"=>
			isset($service->carrier_params['PrintSalesOrderNo'])?$service->carrier_params['PrintSalesOrderNo']:"",
		"PrintSellerAccount"=>
			isset($service->carrier_params['PrintSellerAccount'])?$service->carrier_params['PrintSellerAccount']:"",
		"PrintRemark"=>
			isset($service->carrier_params['PrintRemark'])?$service->carrier_params['PrintRemark']:"",
		"PrintCustoms"=>
			isset($service->carrier_params['PrintCustoms'])?$service->carrier_params['PrintCustoms']:"",
		"PrintProduct"=>
			isset($service->carrier_params['PrintProduct'])?$service->carrier_params['PrintProduct']:"",
		"PrintSenderName"=>
			isset($service->carrier_params['PrintSenderName'])?$service->carrier_params['PrintSenderName']:"",
		"PrintProductImg"=>
			isset($service->carrier_params['PrintProductImg'])?$service->carrier_params['PrintProductImg']:"",
		"PrintProductPosition"=>
			isset($service->carrier_params['PrintProductPosition'])?$service->carrier_params['PrintProductPosition']:"",
		"PrintProductFormat"=>"{sku}{productname}{itemtitle}",
		);
		foreach ($data as $d_value) {
			$d_order = $d_value['order'];
			$array_string["OrdersList"][] = array(
				"OrderId" => $d_order['customer_number'],
			);
		}
		$json_string = json_encode($array_string);
		//\Yii::info('willage:'.var_dump($service->carrier_params), "file");
 		//\Yii::info('willage:'.$json_string, "file");
		return $json_string;
	}
/**
 * [debug_savejsonfile description]
 * @Author   willage
 * @DateTime 2016-07-21T11:41:53+0800
 * @param    [type]                   $json [description]
 * @return   [type]                         [description]
 */
	static public function debug_savejsonfile($json){
		$tmp = str_replace(":{",":{\r\n",$json);
		$tmp = str_replace("{\"","{\r\n\"",$tmp);
		$tmp = str_replace("}","\r\n}",$tmp);
		$tmp = str_replace("[","[\r\n",$tmp);
		$tmp = str_replace("]","\r\n]",$tmp);
		$tmp = str_replace(",",",\r\n",$tmp);
		//
		$tmp = str_replace("\r\n}{","}{",$tmp);
		$tmp = str_replace("\r\n}\"","}\"",$tmp);
		$url ='D:\workbranch\file\data'."\\".__FUNCTION__.".json";
		file_put_contents($url, $tmp);
	}

	/**
	 * [debug_readjsonfile description]
	 * @Author   willage
	 * @DateTime 2016-07-21T18:00:21+0800
	 * @return   [type]                   [description]
	 */
	static public function debug_readjsonfile(){
		$tmp = file_get_contents('D:\workbranch\file\data\orderupdata_default.json');
		// $tmp = str_replace("{\r\n","{",$tmp);
		// $tmp = str_replace("\r\n}","}",$tmp);
		// $tmp = str_replace("[\r\n","[",$tmp);
		// $tmp = str_replace("\r\n]","]",$tmp);
		// $tmp = str_replace(",\r\n",",",$tmp);
		//file_put_contents('D:\workbranch\file\data\ccc.json', $tmp);
		return $tmp;
	}

	/**
	 * [checkstr 检测字符串$str中是否含有字符串$needle]
	 * @Author   willage
	 * @DateTime 2016-07-21T18:00:13+0800
	 * @param    [type]                   $str    [description]
	 * @param    [type]                   $needle [description]
	 * @return   [type]                           [description]
	 */
	static public function checkstr($str,$needle){
	    $tmparray = explode($needle,$str);
	    if(count($tmparray)>1){
	    return true;
	    } else{
	    return false;
	    }
	}
}










?>