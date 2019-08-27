<?php
namespace common\api\carrierAPI;

use yii;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use common\helpers\SubmitGate;
use Jurosh\PDFMerge\PDFMerger;
use \Exception;
use Qiniu\json_decode;


class LB_YIYUNQUANQIUCarrierAPI extends BaseCarrierAPI{
	
	static private $wsdl = '';	// 物流接口
	private $submitGate = null;	// SoapClient实例
	
	public $userId = null;
	public $license = null;
	
	public function __construct(){
		//测试环境
// 		self::$wsdl = 'http://58.210.255.186:8866/lgtbws/eship/orderShip?wsdl';

		//正式环境
// 		self::$wsdl = 'http://121.199.46.120:8401/lgtbws/eship/orderShip?wsdl';
		
		self::$wsdl = 'http://ws.xn--7hvv57c8im.com:8401/lgtbws/eship/orderShip?wsdl';
		
		
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
				
			$this->userId = $a['userId'];
			$this->license = empty($a['license']) ? 'AA00' : $a['license'];	//这里的认证信息暂时屏蔽，易运全球的技术说暂时不会认证该信息，叫我们弄个固定值就好
			
			$clientInfo = array(
					'userId' => $this->userId,
					'bankerId' => $this->license,
					'plantId' => 'xiaolaoban',
					'plantKey' => '32ea69d2f05cc625',
			);
			
			//直接用城市来替换
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($tmpConsigneeProvince)){
				$tmpConsigneeProvince = $order->consignee_city;
			}
		
			if (empty($order->consignee_city)){
				return self::getResult(1, '', '城市不能为空');
			}
				
			if (empty($order->consignee_postal_code)){
				return self::getResult(1, '', '邮编不能为空');
			}
				
			if (empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
				
			if (empty($order->consignee_country_code)){
				return self::getResult(1, '', '国家信息不能为空');
			}
				
			if (empty($order->consignee_address_line1)){
				return self::getResult(1, '', '地址1不能为空');
			}
			
			if(empty($e['goods_description'])){
				return self::getResult(1, '', '货件描述不能为空');
			}
			
			if(empty($e['length'])){
				return self::getResult(1, '', '长不能为空');
			}
			
			if(empty($e['width'])){
				return self::getResult(1, '', '宽不能为空');
			}
			
			if(empty($e['height'])){
				return self::getResult(1, '', '高不能为空');
			}
				
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 30,
							'consignee_address_line2_limit' => 30,
							'consignee_address_line3_limit' => 30,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 20
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
		
			if (empty($addressAndPhone['phone1'])){
				return self::getResult(1, '', '联系方式不能为空');
			}
			
			$productList = array();
			
			$productList['orderId'] = $customer_number;					//客户端订单唯一编号
			$productList['priceId'] = $service->shipping_method_code;	//渠道ID，来自渠道信息;
			$productList['description'] = $e['goods_description'];		//货件描述
			$productList['referenceNumber'] = $customer_number;			//参考编号
			$productList['ppTransactionId'] = '';						//PayPal交易流水号
			$productList['packageType'] = empty($service_carrier_params['packageType']) ? '02' : $service_carrier_params['packageType'];	//包装类型，01：文件  02：包裹
	
			//收件人信息
			$productList['shipTo'] = array(
					'userName' => $order->consignee,					//姓名
					'companyName' => empty($order->consignee_company) ? $order->consignee_postal_code : $order->consignee_company,			//公司名
					'phoneNumber' => $addressAndPhone['phone1'],		//联系电话
					'email' => $order->consignee_email,					//邮箱
					'taxNumber' => '',									//税号
					'address' => array(
							'address1' => $addressAndPhone['address_line1'],	//地址栏1
							'address2' => $addressAndPhone['address_line2'],	//地址栏2
							'address3' => $addressAndPhone['address_line3'],	//地址栏3
							'city' => $order->consignee_city,						//城市
							'province' => $tmpConsigneeProvince,					//省份
							'country' => $order->consignee_country_code,			//国家
							'postalCode' => $order->consignee_postal_code			//邮编
					),
			);
			
			//发票
			$productList['invoice'] = array(
					'currencyCode' => 'USD',							//币种
					'shipmentTerms' => empty($service_carrier_params['shipmentTerms']) ? 'CFR' : $service_carrier_params['shipmentTerms'],	//销售条款
					'exportReason' => empty($service_carrier_params['exportReason']) ? 'SALE' : $service_carrier_params['exportReason'],	//出口原因
			);
			
			//发票明细
			$productList['invoice']['invoiceDetailList'] = array();
			
			$totalWeight = 0;
			$totalCount = 0;
			
			foreach ($order->items as $j=>$vitem){
				$productList['invoice']['invoiceDetailList'][$j]=[
					'descriptionEn' => $e['EName'][$j],
					'descriptionCn' => $e['Name'][$j],
					'partNumber' => '',
					'commodityCode' => $e['commodityCode'][$j],
					'originCountry' => $e['originCountry'][$j],
					'weight' => ($e['weight'][$j]) / 1000,
					'currencyValue' => $e['currencyValue'][$j],
					'unitCount' => empty($e['unitCount'][$j]) ? $vitem->quantity : $e['unitCount'][$j],
					'measure' => empty($e['measure'][$j]) ? 'PCS' : $e['measure'][$j],
				];
		
				$totalWeight += $productList['invoice']['invoiceDetailList'][$j]['weight'] * $productList['invoice']['invoiceDetailList'][$j]['unitCount'];
				$totalCount += empty($e['unitCount'][$j]) ? $vitem->quantity : $e['unitCount'][$j];
			}
			
			$productList['packageList'] = array(
					'weight' => $totalWeight,
					'length' => $e['length'],
					'width' => $e['width'],
					'height' => $e['height'],
					'count' => 1		//$totalCount表示包裹数量
			);
			
			$productList['label']=array(
					'labelType'=>'S',
					'printLabel'=>'Y',
			);
			
			//多条商品信息列表
			$request = array(
					'clientInfo'=>$clientInfo,
					'orderList'=>array($productList),
			);
			
// 			print_r($request);
// 			exit;
				
			//数据组织完成 准备发送
			#########################################################################
			\Yii::info(print_r($request,1),"file");
			try{
			$response = $this->submitGate->mainGate(self::$wsdl, $request, 'soap', 'orderShip');
			}
			catch(\Exception $e){
				if($e->getMessage()=="Error Fetching http headers")
					return self::getResult(1,'','上传且提交超时，请重试或者联系易运全球的客服。');
				else 
					return self::getResult(1,'',$e->getMessage());
			}
			
			$tmpJson = json_encode($response);
			
			\Yii::info('LB_YIYUNQUANQIU,puid:'.$puid.',response,order_id:'.$order->order_id.' '.$tmpJson,"carrier_api");
			
			unset($response);
			$response = json_decode($tmpJson, true);
// 			print_r($response);exit;
			if($response['error']){throw new CarrierException(print_r($response));}
			
			if($response['data']['result'] == 'false'){
				$tmpErr = '';
				
				if(isset($response['data']['messages'])){
					if(isset($response['data']['messages']['code'])){
						$text='';
						if(isset($response['data']['messages']['text']))
							$text=$response['data']['messages']['text'];
						$tmpErr = $response['data']['messages']['code'].':'.$text.';';
					}else{
						foreach ($response['data']['messages'] as $tmpDataMessage){
							$text='';
							if(isset($tmpDataMessage['text']))
								$text=$tmpDataMessage['text'];
							$tmpErr .= $tmpDataMessage['code'].':'.$tmpDataMessage['text'].';';
						}
					}
					
					throw new CarrierException($tmpErr);
				}else if(isset($response['data']['orderResult']['messages'])){
					if(isset($response['data']['orderResult']['messages']['code'])){
						$text='';
						if(isset($response['data']['orderResult']['messages']['text']))
							$text=$response['data']['orderResult']['messages']['text'];
						$tmpErr = $response['data']['orderResult']['messages']['code'].':'.$text.';';
					}else{
						foreach ($response['data']['orderResult']['messages'] as $tmpDataMessage){
							$text='';
							if(isset($tmpDataMessage['text']))
								$text=$tmpDataMessage['text'];
							$tmpErr .= $tmpDataMessage['code'].':'.$text.';';
						}
					}
					throw new CarrierException($tmpErr);
				}else{
					throw new CarrierException(print_r($response['data']));
				}
			}
			
			if($response['data']['result'] == 'true'){
// 				\Yii::info('YIYUNQUANQIU,puid:'.$puid.(json_encode($response['data'])), "file");
				
				//因为该物流直接返回base64打印，所以需要直接保存到我们服务器做备用
// 				$orderLabelPdfPathSave = CarrierAPIHelper::savePDF2(base64_decode($response['data']['orderResult']['orderLabel']),$puid,$order->order_id.$customer_number."_orderLabel_".time());
// 				$packageLabelPdfPathSave = CarrierAPIHelper::savePDF2(base64_decode($response['data']['orderResult']['packageList']['packageLabel']),$puid,$order->order_id.$customer_number."_packageLabel_".time());
				
				$orderLabelPdfPath = '';
				$packageLabelPdfPath = '';
				
// 				if($orderLabelPdfPathSave['error'] == 0){
// 					$orderLabelPdfPath = $orderLabelPdfPathSave['filePath'];
// 				}
				
// 				if($packageLabelPdfPathSave['error'] == 0){
// 					$packageLabelPdfPath = $packageLabelPdfPathSave['filePath'];
// 				}
				
// 				if(!empty($packageLabelPdfPath)){
// 					$print_param = array();
// 					$print_param['carrier_code'] = $service->carrier_code;
// 					$print_param['api_class'] = 'LB_YIYUNQUANQIUCarrierAPI';
// 					$print_param['label_api_file_path'] = $packageLabelPdfPath;
// 					$print_param['orderLabelPdfPath'] = $orderLabelPdfPath;
// 					$print_param['run_status'] = 4;
					
// 					try{
// 						CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
// 					}catch (\Exception $ex){
// 					}
// 				}

				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
				$r = CarrierAPIHelper::orderSuccess($order,$service,$customer_number,OdOrder::CARRIER_WAITING_PRINT,
						$response['data']['orderResult']['packageList']['trackingNum'],
						['OrderSign'=>$response['data']['orderResult']['orderId'],
						'packageLabelPath'=>$packageLabelPdfPath,'rOrderId'=>$response['data']['orderResult']['rOrderId']]);
		
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!物流跟踪号:'.$response['data']['orderResult']['packageList']['trackingNum']);
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
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$tmp_print_wsdl = 'http://ws.xn--7hvv57c8im.com:8401/lgtbws/eship/getOrderLabel?wsdl';
			
			$order = current($data);reset($data);
			$order = $order['order'];
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
			
			//获取到帐号中的认证参数
			$a = $account->api_params;
			
			$this->userId = $a['userId'];
			$this->license = empty($a['license']) ? 'AA00' : $a['license'];	//这里的认证信息暂时屏蔽，易运全球的技术说暂时不会认证该信息，叫我们弄个固定值就好
			
			$clientInfo = array(
				'userId' => $this->userId,
				'bankerId' => $this->license,
				'plantId' => 'xiaolaoban',
				'plantKey' => '32ea69d2f05cc625',
			);
			
			$tmpPath = array();
			
			foreach ($data as $v) {
				$listorder = array();// object/array required 订单信息
				$oneOrder = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
				$shipped = $checkResult['data']['shipped'];
			
				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}
			
// 				$pdf->addPDF($basepath.$shipped->return_no['packageLabelPath'],'all');
			
				try{
					unset($request);
					unset($response);
				
					$request = array(
						'clientInfo'=>$clientInfo,
						'orderId'=>$shipped->return_no['rOrderId'],		//$shipped->return_no['rOrderId']
					);
				
					$response = $this->submitGate->mainGate($tmp_print_wsdl, $request, 'soap', 'getOrderLabel');
					
					$tmpJson = json_encode($response);
					unset($response);
					$response = json_decode($tmpJson, true);
					
					if($response['error']){throw new CarrierException(print_r($response));}
					
// 					print_r($response['data']['orderLabelInfo']['slabel10List']);
// 					exit;
					
					if($response['data']['result'] == 'true'){
						$pdfUrl = CarrierAPIHelper::savePDF(base64_decode($response['data']['orderLabelInfo']['slabel10List']['labelBytes']),$puid,$account->carrier_code.'_'.$order['customer_number'], 0);
						$tmpPath[] = $pdfUrl['filePath'];
					}else{
						return self::getResult(1,'','易运全球返回异常e1');
					}
				}catch(\Exception $e){
					if($e->getMessage()=="Error Fetching http headers")
						return self::getResult(1,'','上传且提交超时，请重试或者联系易运全球的客服。');
					else
						return self::getResult(1,'',$e->getMessage());
				}
			}
			
			if(empty($tmpPath)){
				return self::getResult(1,'','打印失败');
			}
				
			if(count($tmpPath) == 1){
				return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
			}else{
				$pdfUrl = CarrierAPIHelper::savePDF('1',$puid,$account->carrier_code.'_'.$order['customer_number'].'_summerge_', 0);
				$pdfmergeResult = PDFMergeHelper::PDFMerge($pdfUrl['filePath'] , $tmpPath);
					
				if($pdfmergeResult['success'] == true){
					return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
				}else{
					return self::getResult(1,'',$pdfmergeResult['message']);
				}
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
		
		/*
		try {
			$pdf = new PDFMerger();
			
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
		
			$basepath = Yii::getAlias('@webroot');
			
			foreach ($data as $v) {
				$listorder = array();// object/array required 订单信息
				$oneOrder = $v['order'];
				$checkResult = CarrierAPIHelper::validate(1,1,$oneOrder);
				$shipped = $checkResult['data']['shipped'];
		
				if(empty($shipped->tracking_number)){
					return self::getResult(1,'', 'order:'.$oneOrder->order_id .' 缺少追踪号,请检查订单是否已上传' );
				}
				
				$pdf->addPDF($basepath.$shipped->return_no['packageLabelPath'],'all');
				
// 				$oneOrder->is_print_carrier = 1;
				$oneOrder->print_carrier_operator = $puid;
				$oneOrder->printtime = time();
				$oneOrder->save();
			}

			$pdfUrl = CarrierAPIHelper::savePDF('1',$puid,$account->carrier_code.'_', 0);

			isset($pdfUrl)?$pdf->merge('file', $pdfUrl['filePath']):$pdfUrl='';
			return self::getResult(0,['pdfUrl'=>$pdfUrl['pdfUrl']],'连接已生成,请点击并打印');
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
		*/
	}
	
	/**
	 * 获取路由信息，暂时没有地方调用，所以先屏蔽
	 * @param $data
	 */
	public function SyncStatus($data){
		$res = array();
		$res['referenceNumber'] = $data;
		
		//暂时屏蔽，以后用到再根据需求优化
		return $res;
		
		$clientInfo = array(
				'userId' => 'test001',		//认证信息暂时写死
				'license' => 'AA00',	//认证信息暂时写死
				'plantId' => 'xiaolaoban',
				'plantKey' => '123',
		);
		
		$request = array(
				'clientInfo'=>$clientInfo,
				'trackingNumList'=>$data,
		);
		
		$response = $this->submitGate->mainGate('http://ws.xn--7hvv57c8im.com:8401/lgtbws/eship/orderTrack?wsdl', $request, 'soap', 'orderTrack');
		
// 		print_r($response);
// 		exit;
		
		if($response['data']->result == 'false'){
			$tmpErr = '';
			
			if(isset($response['data']->messages)){
				if(isset($response['data']->messages->code)){
					$tmpErr = $response['data']->messages->code.':'.$response['data']->messages->text.';';
				}else{
					foreach ($response['data']->messages as $tmpDataMessage){
						$tmpErr .= $tmpDataMessage->code.':'.$tmpDataMessage->text.';';
					}
				}
			}else{
				$tmpErr = json_encode($response['data']);
			}
			
			$res['error'] = "1";
			$res['trackContent'] = $tmpErr;
			return $res;
		}else if($response['data']->result == 'true'){
			if($response['data']->orderTrackResultList->result == 'false'){
				$tmpErr = '';
				
				if(isset($response['data']->orderTrackResultList->messages)){
					if(isset($response['data']->orderTrackResultList->messages->code)){
						$tmpErr = $response['data']->orderTrackResultList->messages->code.':'.$response['data']->orderTrackResultList->messages->text.';';
					}else{
						foreach ($response['data']->orderTrackResultList->messages as $tmpDataMessage){
							$tmpErr .= $tmpDataMessage->code.':'.$tmpDataMessage->text.';';
						}
					}
				}else{
					$tmpErr = json_encode($response['data']);
				}
				
				$res['error'] = "1";
				$res['trackContent'] = $tmpErr;
				return $res;
			}else{
				if(!isset($response['data']->orderTrackResultList->trackInfoList)){
					$res['error'] = "1";
					$res['trackContent'] = '暂时没有跟踪信息';
					return $res;
				}else{
					$res['error'] = "0";
					$res['trackContent'] = $response['data']->orderTrackResultList->trackInfoList;
					
// 					foreach ($response['data']->orderTrackResultList->trackInfoList as $tmpVal){
// 						$res['trackContent'][] = $tmpVal;
// 					}
					
					return $res;
				}
			}
		}
	}
}

?>