<?php
namespace common\api\carrierAPI;

use yii;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;
use eagle\modules\order\models\OdOrder;
use common\helpers\SubmitGate;
use Jurosh\PDFMerge\PDFMerger;
use common\helpers\Helper_Curl;
use eagle\modules\util\helpers\PDFMergeHelper; 
use Qiniu\json_decode;

class LB_DEBANGCarrierAPI extends BaseCarrierAPI{
	
	public static $wsdl = '';
	public static $submitGate = null;
	
	public function __construct(){
		//正式环境
		self::$wsdl = 'http://cbs.deppon.com/default/svc/wsdl';
		//测试环境
		//self::$wsdl = 'http://58.40.17.67:10307/default/svc/wsdl';
		self::$submitGate = new SubmitGate();
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lrq 		2017/08/09			初始化
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
	
			//odOrder表内容
			$order = $data['order'];
			//表单提交的数据
			$form_data = $data['data'];
			$customer_number = $form_data['customer_number'];
			
			//获取物流商信息、运输方式信息等
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$service_carrier_params = $service->carrier_params;   //参数键值对
			$account = $info['account'];
			
			//获取物流商 账号 的认证参数
			$api_params = $account->api_params;
	
			//重复发货，添加不同的标识码
			$extra_id = isset($form_data['extra_id']) ? $form_data['extra_id'] : '';
			if(isset($form_data['extra_id'])){
				if($extra_id == ''){
					return self::getResult(1, '', '强制发货标识码，不能为空');
				}
			}
			
			//对当前条件的验证，校验是否登录，校验是否已上传过
			$checkResult = CarrierAPIHelper::validate(0,0,$order,$extra_id,$customer_number);
	
			//是否启用云途运单号设置（普通参数）
			$tmp_is_use_mailno = empty($service['carrier_params']['is_use_mailno']) ? 'N' : $service['carrier_params']['is_use_mailno'];

			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 100,
							'consignee_address_line2_limit' => 100,
							'consignee_address_line3_limit' => 100,
					),
					'consignee_district' => 1,
					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 16
			);
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams);
	
			//法国没有省份，直接用城市来替换；若城市长度大于20，则直接传‘FR’到省。
			$tmpConsigneeProvince = $order->consignee_province;
			if(empty($tmpConsigneeProvince)){
				$tmpConsigneeProvince = $order->consignee_city;
				/*if(strlen($tmpConsigneeProvince)>20){
					$tmpConsigneeProvince = $order->consignee_country_code;
				}*/
			}
	
			$post_order = array();
			$post_order['reference_no'] = $customer_number;     //客户订单号
			$post_order['shipper_hawbcode'] = '';    //系统单号
			$post_order['shipping_method'] = $service->shipping_method_code;  //运输方式
			$post_order['country_code'] = $order->consignee_country_code == 'UK' ? 'GB' : $order->consignee_country_code;  //收件人国家二字码
			$post_order['service'] = '';   //附加服务代码，每个以英文分号“;”隔开
			$post_order['shipping_method_no'] = '';   //服务商单号
			$post_order['order_pieces'] = empty($form_data['PackageNumber']) ? 1 : (int)$form_data['PackageNumber'];       //外包装件数  int
			$post_order['insurance_value'] = 0;   //投保金额
			$post_order['mail_cargo_type'] = empty($form_data['ApplicationType']) ? 4 : $form_data['ApplicationType'];     // 包裹申报种类，1:Gift礼品；2:CommercialSample；商品货样；3:Document,文件；4:Other,其他；默认4
			$post_order['length'] = 1;   // 包裹长
			$post_order['width'] =  1;   // 包裹宽
			$post_order['height'] = 1;   // 包裹高
			$post_order['is_return'] = empty($post_order['IsReturn']) ? 0 : $service_carrier_params['IsReturn'];  // 是否退回,包裹无人签收时是否退回，1-退回，0-不退回
	
			//Consignee  收件人信息
			$Consignee = array(
				'consignee_company' => $order->consignee_company,   //收件人公司名
				'consignee_province' => $tmpConsigneeProvince,   //收件人省
				'consignee_city' => $order->consignee_city,   //收件人城市
				'consignee_street' => $addressAndPhone['address_line1'],   //收件人地址1
				'consignee_street2' => $addressAndPhone['address_line2'],   //收件人地址2
				'consignee_street3' => $addressAndPhone['address_line3'],   //收件人地址3
				'consignee_postcode' => $order->consignee_postal_code,   //收件人邮编
				'consignee_name' => $order->consignee,   //收件人姓名
				'consignee_telephone' => $order->consignee_phone,   //收件人电话
				'consignee_mobile' => $order->consignee_mobile,   //收件人手机
				'consignee_email' => $order->consignee_email,   //收件人邮箱
			);
			$post_order['Consignee'] = $Consignee;
	
			//Shipper  发件人信息
			$eub_method = ['PK0041', '1', 'SHA-EMS', 'SHA-EBG'];
			if(!empty($info['senderAddressInfo']['shippingfrom'])){
				$shippingfromaddress = $info['senderAddressInfo']['shippingfrom'];
				
				$Shipper = array(
					'shipper_company' => $shippingfromaddress['company'],   //发件人公司名
					'shipper_countrycode' => $shippingfromaddress['country'],   //发件人国家二字码
					'shipper_province' => in_array($post_order['shipping_method'], $eub_method) ? '310000' : $shippingfromaddress['province'],   //发件人省
					'shipper_city' => in_array($post_order['shipping_method'], $eub_method) ? '310000' : $shippingfromaddress['city'],   //发件人城市
					'shipper_street' => $shippingfromaddress['street'],   //发件人地址
					'shipper_postcode' => $shippingfromaddress['postcode'],   //发件人邮编
					'shipper_name' => $shippingfromaddress['contact'],   //发件人姓名
					'shipper_telephone' => $shippingfromaddress['phone'],   //发件人电话
					'shipper_mobile' => $shippingfromaddress['mobile'],   //发件人手机
					'shipper_email' => $shippingfromaddress['email'],   //发件人邮箱
					'shipper_fax' => '',   //发件人传真
					'order_note' => $form_data['Remark'],   //订单备注
				);
				$post_order['Shipper'] = $Shipper;
			}
			else{
				$post_order['Shipper'] = [];
			}
	
	
			// ItemArr  海关申报信息
			$totalWeight = 0;
			$ItemArr[0] = array();
			foreach($order->items as $j => $vitem){
				$ItemArr[$j] = array(
					'invoice_enname' => $form_data['EName'][$j],   //海关申报品名
					'invoice_cnname' => $form_data['Name'][$j],   //中文海关申报品名
					'invoice_weight' => round(($form_data['weight'][$j])/1000,3),   //申报重量，单位KG, 精确到三位小数
					'invoice_quantity' => empty($form_data['DeclarePieces'][$j]) ? $vitem->quantity : $form_data['DeclarePieces'][$j],   //数量
					'unit_code' => 'PCE',   //单位(MTR(米), PCE(件), SET(套)),默认PCE
					'invoice_unitcharge' => $form_data['DeclaredValue'][$j],   //单价
					'invoice_currencycode' => 'USD',   //申报币种，默认为USD(美元)
					'hs_code' => empty($form_data['Hscode'][$j]) ? '' : $form_data['Hscode'][$j],   //海关协制编号
					'invoice_note' => $form_data['Remark'][$j],   //配货信息
					'sku' => $vitem->sku,   //产品SKU编码
				);
				$totalWeight += $form_data['weight'][$j] * $ItemArr[$j]['invoice_quantity'];
			}
			$post_order['ItemArr'] = $ItemArr;
			$post_order['order_weight'] = round($totalWeight / 1000 , 3); // 订单重量，单位KG，最多3位小数
			
			$params = [
				'appToken' => $api_params['appToken'],
                'appKey' => $api_params['appKey'],
				'paramsJson' => json_encode($post_order),
				'service' => 'createOrder',
			];
			
			\Yii::info('LB_DEBANGCarrierAPI1,puid:'.$puid.',order_id:'.$order->order_id.' '.json_encode($post_order),"file");
			
			$res = self::$submitGate->mainGate(self::$wsdl, $params, 'soap', 'callService');
			
			if(empty($res)){
				return self::getResult(1, '', '提交失败: 货代返回信息为空！');
			}
			
			\Yii::info('LB_DEBANGCarrierAPI2,puid:'.$puid.',order_id:'.$order->order_id.' '.json_encode($res),"file");
			
			if($res['error'] != 0){
				return self::getResult(1, '', '提交失败:'.$res['msg']);
			}
			
			$responseData = json_decode($res['data']->response, true);
			
			if(!empty($responseData['ask']) && $responseData['ask'] == 'Success'){
				$print_param = array();
                $print_param['carrier_code'] = $service->carrier_code;
                $print_param['api_class'] = 'LB_DEBANGCarrierAPI';
                $print_param['service_id'] = $order->default_shipping_method_code;
                $print_param['appToken'] = $api_params['appToken'];
                $print_param['appKey'] = $api_params['appKey'];
                $print_param['order_id'] = $order->order_id;
                $print_param['carrier_params'] = $service->carrier_params;

				try{
					CarrierAPIHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $customer_number, $print_param);
				}catch (\Exception $ex){
				}

				$agent_mailno = $tmp_is_use_mailno == 'Y' ? $responseData['order_code'] : $responseData['shipping_method_no'];

				if ($responseData['track_status'] == 1) {
					//（已产生或等待跟踪号时）上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态，跟踪号(选填),returnNo(选填)
					$r = CarrierApiHelper::orderSuccess($order, $service, $responseData['reference_no'], OdOrder::CARRIER_WAITING_PRINT, $agent_mailno, ['order_code' => $responseData['order_code'], 'shipping_method_no' => $responseData['shipping_method_no'], 'reference_no' => $responseData['reference_no']]);
					return self::getResult(0, $r, "提交结果:" . $responseData['message'] ."<br>物流跟踪号：" . $agent_mailno);
				}
				if ($item['track_status'] == 2) {
					//（已产生或等待跟踪号时）上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态，跟踪号(选填),returnNo(选填)
					if($tmp_is_use_mailno=='Y'){
						$r = CarrierApiHelper::orderSuccess($order, $service, $responseData['reference_no'], OdOrder::CARRIER_WAITING_PRINT,$agent_mailno, ['order_code' => $responseData['order_code'], 'shipping_method_no' => $responseData['shipping_method_no'], 'reference_no' => $responseData['reference_no']]);
						return self::getResult(0, $r, "暂时没跟踪号，等待物流商出仓发货后再获取跟踪号。<br> 运单号：". $agent_mailno);
					}else{
						$r = CarrierApiHelper::orderSuccess($order, $service, $responseData['reference_no'], OdOrder::CARRIER_WAITING_GETCODE,'', ['order_code' => $responseData['order_code'], 'shipping_method_no' => $responseData['shipping_method_no'], 'reference_no' => $responseData['reference_no']]);
						return self::getResult(0, $r, "暂时没跟踪号，等待物流商出仓发货后再获取跟踪号。");
					}
				}
				if($item['track_status'] == 3) {
					//（没有跟踪号时）上传成功后进行数据的保存 订单(object OdOrder)，运输服务(object SysShippingService)，客户参考号 ，订单状态，跟踪号(选填),returnNo(选填)
					if($tmp_is_use_mailno=='Y'){
						$r = CarrierApiHelper::orderSuccess($order, $service, $responseData['reference_no'], OdOrder::CARRIER_WAITING_PRINT,$agent_mailno, ['order_code' => $responseData['order_code'], 'shipping_method_no' => $responseData['shipping_method_no'], 'reference_no' => $responseData['reference_no']]);
						return self::getResult(0, $r, "此运输方式不需要跟踪号。运单号：".$agent_mailno);
					}else{
						$r = CarrierApiHelper::orderSuccess($order, $service, $responseData['reference_no'], OdOrder::CARRIER_WAITING_PRINT,'', ['order_code' => $responseData['order_code'], 'shipping_method_no' => $responseData['shipping_method_no'], 'reference_no' => $responseData['reference_no']]);
						return self::getResult(0, $r, "此运输方式不带跟踪号！");
					}

				}
			}
			else{
				if(!empty($responseData['Error']['errMessage'])){
					return self::getResult(1, '', '提交失败: '.$responseData['Error']['errMessage'].', '.$responseData['Error']['errCode']);
				}
				else if(!empty($responseData['ask']) && $responseData['ask'] == 'Failure' && !empty($responseData['message'])){
					return self::getResult(1, '', '提交失败: '.$responseData['message']);
				}
				else{
					return self::getResult(1, '', '提交失败: 货代返回错误信息为空！');
				}
			}
		}
		catch (\CarrierException $e){
			return self::getResult(1,'',"file:".$e->getFile()." line:".$e->getLine()." ".$e->msg());
		}
	}
	
	//取消订单
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消物流单。');
	}
	
	
	//交运
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运物流单，上传物流单便会立即交运。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		try
		{
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
		
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$account = $info['account'];
			$service = $info['service'];
			$api_params = $account->api_params;
		
			$tmp_is_use_mailno = empty($service['carrier_params']['is_use_mailno']) ? 'N' : $service['carrier_params']['is_use_mailno'];
		
			$post_order['reference_no'][] = $order->customer_number;
			$params = [
				'appToken' => $api_params['appToken'],
				'appKey' => $api_params['appKey'],
				'paramsJson' => json_encode($post_order),
				'service' => 'getTrackNumber',
			];
			$res = self::$submitGate->mainGate(self::$wsdl, $params, 'soap', 'callService');
			
			if(empty($res)){
				return self::getResult(1, '', '获取失败: 货代返回信息为空！');
			}
				
			if($res['error'] != 0){
				return self::getResult(1, '', '获取失败:'.$res['msg']);
			}
				
			$responseData = json_decode($res['data']->response, true);
			if(!empty($responseData['ask']) && $responseData['ask'] == 'Success'){
				if($tmp_is_use_mailno == 'Y'){
					$shipped->tracking_number = $responseData['data'][0]['WayBillNumber'];
					$shipped->save();
					$order->tracking_number = $shipped->tracking_number;
					$order->save();
					return self::getResult(0, '', '跟踪号[系统运单号]:' . $shipped->tracking_number);
				}
				else
				{
					if(empty($responseData['data'][0]['TrackingNumber'])){
						return self::getResult(1,'', '提交结果:'.$responseData['message']);
					}
					else{
						$shipped->tracking_number = $responseData['data'][0]['TrackingNumber'];
						$shipped->save();
						$order->tracking_number = $shipped->tracking_number;
						$order->save();
						return self::getResult(0, '', '物流跟踪号:' . $shipped->tracking_number);
					}
				}
			}
			else{
				if(!empty($responseData['Error']['errMessage'])){
					return self::getResult(1, '', '获取失败: '.$responseData['Error']['errMessage'].', '.$responseData['Error']['errCode']);
				}
				else if(!empty($responseData['ask']) && $responseData['ask'] == 'Failure' && !empty($responseData['message'])){
					return self::getResult(1, '', '获取失败: '.$responseData['message']);
				}
				else{
					return self::getResult(1, '', '获取失败: 货代返回错误信息为空！');
				}
			}
		}catch (CarrierException $e){
			return self::getResult(1,'',$e->msg());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name		date				note
	 * @author		lrq 		2017/08/09			初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try {
			$pdf = new PDFMerger();
			$order = current($data);reset($data);
			$getAccountInfoOrder = $order['order'];
			
			$user=\Yii::$app->user->identity;
            if(empty($user))return BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
            $puid = $user->getParentUid();
		
			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($getAccountInfoOrder);
			$account = $info['account'];
			$service = $info['service'];
			$api_params = $account->api_params;
			$carrier_params = $service->carrier_params;
			$label_type = empty($carrier_params['label_type']) ? 1 : $carrier_params['label_type'];
			$label_content_type = empty($carrier_params['label_content_type']) ? 4 : $carrier_params['label_content_type'];
			
			$OrderNumbers = array();// 客户订单号作提交参数
			
			foreach ($data as $v) {
				$order = $v['order'];
				$OrderNumbers[]= $order->customer_number;
			}
			
			$post_order['codes'] = $OrderNumbers;
			$post_order['label_content_type'] = $label_content_type;
			$post_order['label_type'] = $label_type;
			
			$params = [
				'appToken' => $api_params['appToken'],
				'appKey' => $api_params['appKey'],
				'paramsJson' => json_encode($post_order),
				'service' => 'batchGetlabel',
			];
			
			$res = self::$submitGate->mainGate(self::$wsdl, $params, 'soap', 'callService');
			
			if(empty($res)){
				return self::getResult(1, '', '货代返回信息为空！');
			}
			
			\Yii::info('LB_DEBANGCarrierAPI5,puid:'.$puid.',params:'.json_encode($params).', res:'.json_encode($res),"file");
			
			
			if($res['error'] != 0){
				return self::getResult(1, '', $res['msg']);
			}
			
			$responseData = json_decode($res['data']->response, true);
			if(!empty($responseData['ask']) && $responseData['ask'] == 'Success' && !empty($responseData['result'][0]['url'])){
				if($responseData['state'] == 1){
					$PDF_URL = $responseData['result'][0]['url'];
					if( !preg_match('/\.pdf$/', $PDF_URL)){
						
						//当返回的是链接，而不是PDF时
						return ['error'=>0, 'data'=>['pdfUrl'=>$PDF_URL], 'msg'=>'连接已生成,请点击并打印'];
						
						return self::getResult(1, '', '没有获取到正确的PDF连接');
					}
				
					$responsePdf = Helper_Curl::get($PDF_URL);
					if( strlen( $responsePdf) < 1000){
						return self::getResult(1, '', '接口返回内容不是一个有效的PDF');
					}
						
					$pdfUrl = CarrierAPIHelper::savePDF( $responsePdf, $puid, $account->carrier_code.'_'.$order['customer_number'], 0);
					$pdf->addPDF( $pdfUrl['filePath'], 'all');
				}
				else if($responseData['state'] == 2){
					return self::getResult(1, '', '部分订单获取失败！');
				}
			}
			else{
				$msg = '';
				if(!empty($responseData['result'][0]['printInfo'])){
					foreach($responseData['result'][0]['printInfo'] as $val){
						$msg .= $val['code'].' '.$val['msg'].'; ';
					}
				}
				
				if($msg != ''){
					return self::getResult(1, '', $msg);
				}
				else{
					return self::getResult(1, '', '货代返回信息失败！');
				}
			}
			
			if( isset( $pdfUrl)){
				$pdf->merge('file', $pdfUrl['filePath']);
				return ['error'=>0, 'data'=>['pdfUrl'=>$pdfUrl['pdfUrl']], 'msg'=>'连接已生成,请点击并打印'];
			}
			else{
				return ['error'=>1, 'data'=>['pdfUrl' => ''], 'msg'=>'连接生成失败'];
			}
		}catch (CarrierException $e) {
			return self::getResult(1,'',$e->msg());
		}
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
		try{
			$puid = $SAA_obj->uid;
	
			$OrderNumbers[]= $print_param['order_number'];
			$json_req=json_encode($OrderNumbers);
			
			$post_order['reference_no'] = $shipped['return_no']['reference_no'];
			$post_order['order_type'] = 2;
			$post_order['label_type'] = 1;
			$params = [
				'appToken' => $api_params['appToken'],
				'appKey' => $api_params['appKey'],
				'paramsJson' => json_encode($post_order),
				'service' => 'getLabelUrl',
			];
			$res = self::$submitGate->mainGate(self::$wsdl, $params, 'soap', 'callService');
				
			if(empty($res)){
				return self::getResult(1, '', '货代返回信息为空！');
			}
			if($res['error'] != 0){
				return self::getResult(1, '', $res['msg']);
			}
			
			$responseData = json_decode($res['data']->response, true);
			
			if(!empty($responseData['ask']) && !empty($responseData['url']) && $responseData['ask'] == 'Success'){
				$PDF_URL = $responseData['url'];
				if( !preg_match('/\.pdf$/', $PDF_URL)){
					return self::getResult(1, '', '订单'.$shipped->order_id.'原单号('.$shipped->order_source_order_id.')没有获取到正确的PDF连接');
				}
			
				$responsePdf = Helper_Curl::get($PDF_URL);
				if( strlen( $responsePdf) < 1000){
					return self::getResult(1, '', '订单'.$shipped->order_id.'原单号('.$shipped->order_source_order_id.')接口返回内容不是一个有效的PDF');
				}
					
				$pdfUrl = CarrierAPIHelper::savePDF( $responsePdf, $puid, $account->carrier_code.'_'.$order['customer_number'], 0);
				return $pdfUrl;
			}
			else{
				if(!empty($responseData['Error']['errMessage'])){
					return self::getResult(1, '', $responseData['Error']['errMessage']);
				}
				else if(!empty($responseData['ask']) && $responseData['ask'] == 'Failure' && !empty($responseData['message'])){
					return self::getResult(1, '', $responseData['message']);
				}
				else{
					return self::getResult(1, '', '货代返回信息失败！');
				}
			}
	
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
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
	public function getVerifyCarrierAccountInformation($data)
	{
		$result = array('is_support'=>1,'error'=>0);
	
		try
		{
			$post_order['reference_no'] = '1234';
			$params = [
				'appKey' => $data['appKey'],
				'appToken' => $data['appToken'],
				'paramsJson' => json_encode($post_order),
				'service' => 'getOrder',
			];
			
			$res = self::$submitGate->mainGate(self::$wsdl, $params, 'soap', 'callService');
			
			if(!empty($res['data']->response)){
				$response = json_decode($res['data']->response, true);
				if(!empty($response['err_code']) && $response['ask'] == 'Failure' && in_array($response['err_code'], ['50001', '50002', '50003'])){
					$result['error'] = 1;
				}
			}
		}
		catch(CarrierException $e){}
	
		return $result;
	}
	
	//获取运输方式
	public static function getCarrierShippingServiceStr($account){
		try{
			// TODO carrier user account @XXX@
			$params = [
				'appKey' => '@XXX@',
				'appToken' => '@XXX@',
				'paramsJson' => '',
				'service' => 'getShippingMethod',
			];
			
			$res = self::$submitGate->mainGate(self::$wsdl, $params, 'soap', 'callService');
	
			if(empty($res) || !is_array($res) || $res['error'] != 0){
				return self::getResult(1,'','获取运输方式失败');
			}
			
			$channelStr="";
			$response = json_decode($res['data']->response, true);
			if(!empty($response) && $response['ask'] == 'Success'){
				foreach ($response['data'] as $countryVal){
					$channelStr .= $countryVal['code'].":".$countryVal['cn_name'].";";
				}
			}
			else{
				return self::getResult(1,'','获取运输方式失败：'.json_encode($res));
			}
	
			if(empty($channelStr)){
				return self::getResult(1, '', '');
			}else{
				return self::getResult(0, $channelStr, '');
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
}
?>