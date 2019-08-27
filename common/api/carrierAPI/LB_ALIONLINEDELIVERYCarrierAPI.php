<?php

namespace common\api\carrierAPI;
use common\api\carrierAPI\BaseCarrierAPI;
use common\helpers\SubmitGate;
use eagle\modules\order\models\OdOrder;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use \Exception;
use eagle\modules\carrier\helpers\CarrierException;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use Jurosh\PDFMerge\PDFMerger;
use eagle\modules\util\helpers\PDFMergeHelper;
use Qiniu\json_decode;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;
use eagle\models\CarrierUserLabel;

/**
 * 速卖通线上发货
 */

class LB_ALIONLINEDELIVERYCarrierAPI extends BaseCarrierAPI
{
	//中俄航空Ruston 的物流渠道
	public static $rustonChannel = array('HRB_WLB_ZTOSH','HRB_WLB_RUSTONHEB','HRB_WLB_ZTOGZ','HRB_WLB_RUSTONBJ');
		
	//递四方(4px) 的物流渠道
	public static $fourpxChannel = array('SGP_WLB_FPXGZ','SGP_WLB_FPXYW','SGP_WLB_FPXSS','SGP_WLB_FPXXM','SGP_WLB_FPXSH');
	
	public function __construct(){
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/27				初始化
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
			
			if($order->order_source != 'aliexpress'){
				return self::getResult(1, '', '不是速卖通的订单,不允许上传。');
			}
			
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
				
			//是否使用揽收
			$isHomeLanshou = 'N';
			if(!empty($a['HomeLanshou'])){
				$isHomeLanshou = $a['HomeLanshou'];
			}
			
			//是否退件
			$is_product = empty($e['is_product']) ? 0 : $e['is_product'];
			
			//获取速卖通线上发货对应的发件人地址信息
			$aliexpressUsers = \eagle\models\SaasAliexpressUser::find()->where(['uid'=>$puid])->andWhere(['sellerloginid'=>$order->selleruserid])->one();
			
			if($aliexpressUsers == null){
				return self::getResult(1, '', '账号绑定失效，请先绑定账号');
			}
			
			if(empty($aliexpressUsers->address_info)){
				$tmpAliexpressAddressInofResult = \eagle\modules\carrier\helpers\CarrierOpenHelper::setUpdateAliexpressAddressInof($puid, $order->selleruserid, $isHomeLanshou, $is_product);
				
				if($tmpAliexpressAddressInofResult['error'] == 1){
					return self::getResult(1, '', $tmpAliexpressAddressInofResult['msg']);
				}
				
				$aliexpressUsers = \eagle\models\SaasAliexpressUser::find()->where(['uid'=>$puid])->andWhere(['sellerloginid'=>$order->selleruserid])->one();
			}
			
			$tmpAliexpressUserVal = json_decode($aliexpressUsers->address_info, true);
			
			if(empty($tmpAliexpressUserVal)){
				return self::getResult(1, '', '请先设置线上发货所使用的发件人地址将统一在速卖通平台【交易】-【地址管理】页面设置，处理订单时将统一从【地址管理】页获取发件人地址信息:英文语言。');
			}
			
			//当缺少退货地址时，重新更新
			if(empty($tmpAliexpressUserVal['refund'])){
				$tmpAliexpressAddressInofResult = \eagle\modules\carrier\helpers\CarrierOpenHelper::setUpdateAliexpressAddressInof($puid, $order->selleruserid, $isHomeLanshou, $is_product);
				
				if($tmpAliexpressAddressInofResult['error'] == 1){
					return self::getResult(1, '', $tmpAliexpressAddressInofResult['msg']);
				}
				
				$aliexpressUsers = \eagle\models\SaasAliexpressUser::find()->where(['uid'=>$puid])->andWhere(['sellerloginid'=>$order->selleruserid])->one();
				
				$tmpAliexpressUserVal = json_decode($aliexpressUsers->address_info, true);
				
				if(empty($tmpAliexpressUserVal)){
					return self::getResult(1, '', '请先设置线上发货所使用的发件人地址将统一在速卖通平台【交易】-【地址管理】页面设置，处理订单时将统一从【地址管理】页获取发件人地址信息:英文语言。');
				}
			}
			
			$tmpSenderAddressInfo = array();
			$tmpPickupAddressInfo = array();
			$tmpRefundAddressInfo = array();
			
			//根据用户设置读取对应的地址信息
			if((isset($service['address']['aliexpressAddress'])) && (!empty($service['address']['aliexpressAddress']))){
				if(isset($service['address']['aliexpressAddress']['sender'])){
					if(is_array($service['address']['aliexpressAddress']['sender'])){
						if(isset($tmpAliexpressUserVal['sender'][$service['address']['aliexpressAddress']['sender'][$order->selleruserid]])){
							$tmpSenderAddressInfo = $tmpAliexpressUserVal['sender'][$service['address']['aliexpressAddress']['sender'][$order->selleruserid]];
						}
					}else{
						if(isset($tmpAliexpressUserVal['sender'][$service['address']['aliexpressAddress']['sender']])){
							$tmpSenderAddressInfo = $tmpAliexpressUserVal['sender'][$service['address']['aliexpressAddress']['sender']];
						}
					}
				}
				
				if(isset($service['address']['aliexpressAddress']['pickup'])){
					if(is_array($service['address']['aliexpressAddress']['pickup'])){
						if(isset($tmpAliexpressUserVal['pickup'][$service['address']['aliexpressAddress']['pickup'][$order->selleruserid]])){
							$tmpPickupAddressInfo = $tmpAliexpressUserVal['pickup'][$service['address']['aliexpressAddress']['pickup'][$order->selleruserid]];
						}
					}else{
						if(isset($tmpAliexpressUserVal['pickup'][$service['address']['aliexpressAddress']['pickup']])){
							$tmpPickupAddressInfo = $tmpAliexpressUserVal['pickup'][$service['address']['aliexpressAddress']['pickup']];
						}
					}
				}
				
				if(isset($service['address']['aliexpressAddress']['refund'])){
					if(is_array($service['address']['aliexpressAddress']['refund'])){
						if(isset($tmpAliexpressUserVal['refund'][$service['address']['aliexpressAddress']['refund'][$order->selleruserid]])){
							$tmpRefundAddressInfo = $tmpAliexpressUserVal['refund'][$service['address']['aliexpressAddress']['refund'][$order->selleruserid]];
						}
					}else{
						if(isset($tmpAliexpressUserVal['refund'][$service['address']['aliexpressAddress']['refund']])){
							$tmpRefundAddressInfo = $tmpAliexpressUserVal['refund'][$service['address']['aliexpressAddress']['refund']];
						}
					}
				}
			}
			
			//假如用户没有设置对应的地址信息则需要先用线上发货的默认设置去赋值 S
			if(empty($tmpSenderAddressInfo)){
				if(isset($tmpAliexpressUserVal['sender'])){
					foreach ($tmpAliexpressUserVal['sender'] as $tmpKey => $tmpVal){
						if($tmpVal['isDefault'] == 1){
							$tmpSenderAddressInfo = $tmpVal;
							break;
						}
						
						if(empty($tmpSenderAddressInfo)){
							$tmpSenderAddressInfo = $tmpAliexpressUserVal['sender'][$tmpKey];
						}
					}
				}
			}
			
			if(empty($tmpPickupAddressInfo)){
				if(isset($tmpAliexpressUserVal['pickup'])){
					foreach ($tmpAliexpressUserVal['pickup'] as $tmpKey => $tmpVal){
						if($tmpVal['isDefault'] == 1){
							$tmpPickupAddressInfo = $tmpVal;
							break;
						}
			
						if(empty($tmpPickupAddressInfo)){
							$tmpPickupAddressInfo = $tmpAliexpressUserVal['pickup'][$tmpKey];
						}
					}
				}
			}
			
			if(empty($tmpRefundAddressInfo)){
				if(isset($tmpAliexpressUserVal['refund'])){
					foreach ($tmpAliexpressUserVal['refund'] as $tmpKey => $tmpVal){
						if($tmpVal['isDefault'] == 1){
							$tmpRefundAddressInfo = $tmpVal;
							break;
						}
							
						if(empty($tmpRefundAddressInfo)){
							$tmpRefundAddressInfo = $tmpAliexpressUserVal['refund'][$tmpKey];
						}
					}
				}
			}
			//假如用户没有设置对应的地址信息则需要先用线上发货的默认设置去赋值 E
			
			//发件人地址信息 S
			$shippingfrom_enaddress = array();
			$shippingfrom_enaddress = $tmpSenderAddressInfo;
			
			if(empty($shippingfrom_enaddress)){
				return self::getResult(1, '', '线上发货所使用的发件人地址将统一在速卖通平台【交易】-【地址管理】页面设置，处理订单时将统一从【地址管理】页获取发件人地址信息:英文语言。');
			}
			
			//揽收人地址信息 S
			if ($isHomeLanshou == 'Y'){
				$shippingfrom_pickupaddress = array();
				$shippingfrom_pickupaddress = $tmpPickupAddressInfo;
				
				if(empty($shippingfrom_pickupaddress)){
					return self::getResult(1, '', '线上发货所使用的揽收地址将统一在速卖通平台【交易】-【地址管理】页面设置，处理订单时将统一从【地址管理】页获取发件人地址信息:中文语言。');
				}
			}
			
			//退货地址 S
			if($is_product == 1){
				$shippingfrom_refundaddressID = 0;
				
				if(isset($tmpRefundAddressInfo['addressId'])){
					$shippingfrom_refundaddressID = $tmpRefundAddressInfo['addressId'];
				}
				
				if(empty($shippingfrom_refundaddressID)){
					return self::getResult(1, '', '线上发货所使用的退货地址将统一在速卖通平台【交易】-【地址管理】页面设置，处理订单时将统一从【地址管理】页获取退货地址信息:中文语言。');
				}
			}
			
			if (($isHomeLanshou == 'Y') && (in_array($service->shipping_method_code, self::$rustonChannel))){
				//中俄航空Ruston
				if($e['AlidomesticLogisticsCompanyId'] != 500){
					return self::getResult(1, '', '选择中俄航空Ruston，国内快递公司必须选择中通快递');
				}
			
				if(empty($e['AlidomesticTrackingNo'])){
					return self::getResult(1, '', '国内快递单号必填');
				}
			}else if (($isHomeLanshou == 'Y') && (in_array($service->shipping_method_code, self::$fourpxChannel))){
				//选择4px的判断
				if($e['AlidomesticLogisticsCompanyId'] != -1){
// 					return self::getResult(1, '', '选择递四方(4px)，国内快递必须选择其他');
					$e['AlidomesticLogisticsCompanyId'] = -1;
				}
				
				if(empty($e['domesticLogisticsCompany'])){
					return self::getResult(1, '', '选择递四方(4px)，国内快递公司需要填写为：上门揽收');
				}
				
				if($e['domesticLogisticsCompany'] != '上门揽收'){
					return self::getResult(1, '', '选择递四方(4px)，国内快递公司需要填写为：上门揽收.');
				}
				
				if(empty($e['AlidomesticTrackingNo'])){
					return self::getResult(1, '', '国内快递单号必填，而且应为：4PX');
				}
				
				if($e['AlidomesticTrackingNo'] != '4PX'){
					return self::getResult(1, '', '国内快递单号必填，而且应为：4PX.');
				}
			}else if($isHomeLanshou == 'Y'){
				$e['AlidomesticLogisticsCompanyId'] = -1;
// 				if($e['AlidomesticLogisticsCompanyId'] != -1){
// 					return self::getResult(1, '', '设置了上门揽收，国内快递必须选择其他');
// 				}
			}
			
			if($e['AlidomesticLogisticsCompanyId'] == ''){
				return self::getResult(1, '', '国内快递必选');
			}else if($e['AlidomesticLogisticsCompanyId'] == '-1'){
				if(empty($e['domesticLogisticsCompany'])){
					return self::getResult(1, '', '国内快递公司名称必填');
				}
			}
				
			if(empty($e['AlidomesticTrackingNo'])){
				return self::getResult(1, '', '国内快递运单号必填');
			}
			
			$productList = [];
			
			//速卖通不可以合并订单发货，所以这里假如用户拆分订单了会导致订单list数据丢失
			$tmp_order_source_order_id = $order->order_source_order_id;
			
			//记录速卖通的order_source_itemid 因为速卖通会验证这部分信息的，所以假如是合并的订单需要直接用这个来通知速卖通
			$tmp_productId = '';
			foreach ($order->items as $tmp_item){
				if($tmp_item->order_source_order_id == $tmp_order_source_order_id){
					$tmp_productId = $tmp_item->order_source_itemid;
					break;
				}
			}
				
			foreach ($order->items as $j=>$vitem){
				if(empty($e['Name'][$j])){
					return self::getResult(1, '', '申报中文名称必填');
				}
				
				if(empty($e['EName'][$j])){
					return self::getResult(1, '', '申报英文名称必填');
				}
				
				if(empty($vitem->quantity)){
					return self::getResult(1, '', '该订单产品件数为0，请确认');
				}
				
				if(empty($e['DeclaredValue'][$j])){
					return self::getResult(1, '', '产品申报金额必填或不为0');
				}
				
				if(empty($e['weight'][$j])){
					return self::getResult(1, '', '产品申报重量必填或不为0');
				}
				
				$tmp_order_source_itemid = $vitem->order_source_itemid;
				
				if(($vitem->order_source_order_id != $tmp_order_source_order_id) || (empty($vitem->order_source_itemid))){
					$tmp_order_source_itemid = $tmp_productId;
				}
				
				$productList[$j]=[
					'productId' => $tmp_order_source_itemid,	//产品ID(必填,如为礼品,则设置为0)
					'categoryCnDesc' => $e['Name'][$j],	//申报中文名称(必填,长度1-20)
					'categoryEnDesc' => $e['EName'][$j],	//申报英文名称(必填,长度1-60)
					'productNum' => $vitem->quantity,	//产品件数(必填1-999)
					'productDeclareAmount' => $e['DeclaredValue'][$j],	//产品申报金额(必填,0.01-10000.00)
					'productWeight' => ($e['weight'][$j]) / 1000,	//为产品申报重量(必填0.001-2.000)
					'isContainsBattery' => empty($e['isContainsBattery'][$j]) ? 0 : 1,	//是否包含锂电池
// 					'scItemId' => '',	//仓储发货属性代码（团购订单，仓储发货必填
// 					'skuValue' => '',	//属性名称（团购订单，仓储发货必填，例如：White）
// 					'skuCode' => '',	//文档上面没有说明这是啥参数
// 					'scItemName' => '',	//文档上面没有说明这是啥参数
// 					'scItemCode' => '',	//文档上面没有说明这是啥参数
					'hsCode' => isset($e['hsCode'][$j]) ? $e['hsCode'][$j] : '',	//产品海关编码
					'isAneroidMarkup' => empty($e['isAneroidMarkup'][$j]) ? 0 : 1,	//是否含非液体化妆品（必填，填0代表不含非液体化妆品；填1代表含非液体化妆品；默认为0）
					'isOnlyBattery' => empty($e['isOnlyBattery'][$j]) ? 0 : 1,	//是否含纯电池产品（必填，填0代表不含纯电池产品；填1代表含纯电池产品；默认为0）;
				];
				
// 				$tmp_order_source_order_id = $vitem->order_source_order_id;
			}
			
			if(empty($order->consignee_city)){
				return self::getResult(1, '', '收件人城市不能为空');
			}
			
			if(empty($order->consignee_country_code)){
				return self::getResult(1, '', '收件人国家简称不能为空');
			}
			
			if(empty($order->consignee_phone) && empty($order->consignee_mobile)){
				return self::getResult(1, '', '收件人电话和收件人手机不能同时为空');
			}
			
			if(empty($order->consignee)){
				return self::getResult(1, '', '收件人姓名不能为空');
			}
			
			if(empty($order->consignee_address_line1)){
				return self::getResult(1, '', '收件人地址不能为空');
			}
			
			$addressAndPhoneParams = array(
					'address' => array(
							'consignee_address_line1_limit' => 90,
					),
					'consignee_district' => 1,
// 					'consignee_county' => 1,
					'consignee_company' => 1,
					'consignee_phone_limit' => 30
			);
				
			$addressAndPhone = CarrierAPIHelper::getCarrierAddressAndPhoneInfo($order, $addressAndPhoneParams, ',');
			
			//速卖通目的国家代码部分需要特殊处理
			$tmp_country = $order->consignee_country_code;
			if(($tmp_country == 'RS') && ($order->consignee_country == 'Serbia')){
				$tmp_country = 'SRB';
			}else if(($tmp_country == 'ME') && ($order->consignee_country == 'Montenegro')){
				$tmp_country = 'MNE';
			}else if($tmp_country == 'GG'){
				$tmp_country = 'GGY';
			}
			
			//收件人邮编，这里主要是为了兼顾上线时又有客户提交代码
			$tmp_postal_code = $order->consignee_postal_code;
			if(isset($e['postal_code_al'])){
				$tmp_postal_code = $e['postal_code_al'];
			}
			
			$addressDTOs = array(
					//收件人地址key值是receiver
					'receiver' => array(
							'city' => $order->consignee_city,	//收件人城市
							'country' => $tmp_country,	//收件人国家简称
							'email' => $order->consignee_email,	//收件人邮箱
							'county'=>$order->consignee_county,	//收件人区县
							'fax' => '',
							'memberType' => 'receiver',	//SMT收件人类型
							'mobile' => $order->consignee_mobile,	//收件人手机
							'name' => $order->consignee,	//收件人姓名
							'phone' => $order->consignee_phone,	//收件人电话
							'postcode' => $tmp_postal_code,	//收件人邮编
							'province' => $order->consignee_province,	//省/州
							'streetAddress' => $addressAndPhone['address_line1'],	//街道
							'trademanageId' => $order->source_buyer_user_id,	//收件人旺旺
					),
					//发货人地址key值是sender
					'sender' => array(
							'city' => $shippingfrom_enaddress['city'],	//发货人城市
							'country' => $shippingfrom_enaddress['country'],	//发货人国家简称
							'county' => $shippingfrom_enaddress['county'],	//发货人区县
							'email' => $shippingfrom_enaddress['email'],	//发货人邮箱
							'memberType' => 'sender',	//SMT发货人类型
							'name' => $shippingfrom_enaddress['name'],	//发货人姓名
							'phone' => $shippingfrom_enaddress['phone'],	//发货人电话
							'postcode' => $shippingfrom_enaddress['postcode'],	//发货人邮编
							'province' => $shippingfrom_enaddress['province'],	//发货人省/州
							'streetAddress' => $shippingfrom_enaddress['streetAddress'],	//发货人街道
							'trademanageId' => $shippingfrom_enaddress['trademanageId'],	//发货人旺旺
							'addressId' => $shippingfrom_enaddress['addressId'],
					),
			);
			
			//当需要使用揽收时需要传pickup揽收地址信息，（v2版本必须传揽收地址）
			if ($isHomeLanshou == 'Y'){
				$addressDTOs['pickup'] = array(
						'city' => $shippingfrom_pickupaddress['city'],	//揽收城市
						'country' => $shippingfrom_pickupaddress['country'],	//揽收国家简称
						'county' => $shippingfrom_pickupaddress['county'],	//揽收区县
						'email' => $shippingfrom_pickupaddress['email'],	//揽收邮箱
						'memberType' => 'pickup',	//SMT揽收类型
						'name' => $shippingfrom_pickupaddress['name'],	//揽收姓名
						'phone' => $shippingfrom_pickupaddress['phone'],	//揽收
						'postcode' => $shippingfrom_pickupaddress['postcode'],	//揽收
						'province' => $shippingfrom_pickupaddress['province'],	//揽收
						'streetAddress' => $shippingfrom_pickupaddress['streetAddress'],	//揽收
						'trademanageId' => $shippingfrom_pickupaddress['trademanageId'],	//揽收
						'addressId'=>$shippingfrom_pickupaddress['addressId'],
				);
			}
			
			if($is_product == 1){
				$addressDTOs['refund'] = array('addressId'=>$shippingfrom_refundaddressID);
			}
			
			if(empty($tmp_order_source_order_id)){
				return self::getResult(1, '', '来源订单号为空，请联系小老板技术');
			}
			
			$request = array(
					'tradeOrderId' => $tmp_order_source_order_id,	//交易订单号
					'tradeOrderFrom' => 'ESCROW',	//交易订单来源,AE订单为ESCROW
					'warehouseCarrierService' => $service->shipping_method_code,	//根据订单号获取线上发货物流方案
					'domesticLogisticsCompanyId' => $e['AlidomesticLogisticsCompanyId'],	//国内快递ID
					'domesticLogisticsCompany' => $e['domesticLogisticsCompany'],	//国内快递公司名称
					'domesticTrackingNo' => $e['AlidomesticTrackingNo'],	//国内快递运单号,长度1-32
					'remark' => $e['remark'],	//备注
					'declareProductDTOs' => json_encode($productList),	//申报产品信息,列表类型，以json格式来表达
					'addressDTOs' => json_encode($addressDTOs),	//地址信息,包含发货人地址,收货人地址.发货人地址.或者揽货地址
					'undeliverableDecision' => empty($is_product) ? 1 : 0,	//不可达处理(未选择:-1/退回:0/销毁:1)
			);
			
// 			print_r($request);
// 			exit;
			
			//数据组织完成 准备发送
			#########################################################################
// 			\Yii::info(print_r($request,1),"file");
			\Yii::info('lb_alionlinedelivery,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($request),"carrier_api");
			$response = self::uploadAliexpressCarrier($order->selleruserid, $request, $tmpRefundAddressInfo, $tmpPickupAddressInfo);
			\Yii::info('lb_alionlinedelivery,response,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
// 			print_r($response);
// 			exit;
			
			if($response['Ack'] == false){
				throw new CarrierException($response['error']);
			}
			
			//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)  订单状态（中环运该方法直接返回物流号，所以跳到第三步）
			$r = CarrierAPIHelper::orderSuccess($order,$service,$response['carrierInfo']['warehouseOrderId'],
						OdOrder::CARRIER_WAITING_GETCODE,'',
						['tradeOrderId'=>$response['carrierInfo']['tradeOrderId'],'outOrderId'=>(isset($response['carrierInfo']['outOrderId']) ? $response['carrierInfo']['outOrderId'] : '') ,'intlTrackingNo'=>(isset($response['carrierInfo']['intlTrackingNo']) ? $response['carrierInfo']['intlTrackingNo'] : '')]);
			
// 			if((isset($response['carrierInfo']['intlTrackingNo'])) && (!empty($response['carrierInfo']['intlTrackingNo']))){
// 				$print_param = array();
// 				$print_param['carrier_code'] = $service->carrier_code;
// 				$print_param['api_class'] = 'LB_ALIONLINEDELIVERYCarrierAPI';
// 				$print_param['tracking_number'] = $response['carrierInfo']['intlTrackingNo'];
// 				$print_param['selleruserid'] = $order->selleruserid;
// 				try{
// 					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $response['carrierInfo']['warehouseOrderId'], $print_param);
// 				}catch (\Exception $ex){
// 				}
// 			}
			
			return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号'.$response['carrierInfo']['warehouseOrderId']);
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 **/
	public function cancelOrderNO($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持取消跟踪号。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){
		return BaseCarrierAPI::getResult(1, '', '物流接口不支持交运。');
	}
	
	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/30				初始化
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){
		try{
			$user=\Yii::$app->user->identity;
			if(empty($user))return self::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = $data['order'];
			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(0,1,$order);
			$shipped = $checkResult['data']['shipped'];
		
			$request = array(
					'tradeOrderId' => $order->order_source_order_id,
			);
			
			$response = self::getAliexpressTrackingNoV2($order->selleruserid, $request);
			
			
			\Yii::info('lb_alionlinedelivery_trackingNo,response,puid:'.$puid.'，order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
// 			print_r($response);
// 			exit;
			
			if($response['Ack'] == false){
				if(is_array($response['error'])){
					return self::getResult(1,'', json_encode($response['error']));
				}else{
					return self::getResult(1,'', $response['error']);
				}
			}
			
			if(!empty($response['trackingInfo']['internationalLogisticsNum'])){
				$shipped->tracking_number=$response['trackingInfo']['internationalLogisticsNum'];
				$shipped->save();
			}
				
			if(!empty($response['trackingInfo']['internationalLogisticsNum'])){//有跟踪号的前提
				$order->tracking_number = $shipped->tracking_number;
				$order->save();
				
				$print_param = array();
				$print_param['carrier_code'] = 'lb_alionlinedelivery';
				$print_param['api_class'] = 'LB_ALIONLINEDELIVERYCarrierAPI';
				$print_param['tracking_number'] = $response['trackingInfo']['internationalLogisticsNum'];
				$print_param['selleruserid'] = $order->selleruserid;
				//$print_param['run_status'] = 0;
					
				try{
					CarrierApiHelper::saveCarrierUserLabelQueue($puid, $order->order_id, $order->customer_number, $print_param);
				}catch (\Exception $ex){}
				
				return  BaseCarrierAPI::getResult(0,'','查询成功成功!跟踪号'.$response['trackingInfo']['internationalLogisticsNum']);
			}else {//没有跟踪号
				throw new CarrierException('暂时没有跟踪号');
			}
				
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/30				初始化
	 +----------------------------------------------------------
	 **/
	public function doPrint($data){
		try{
			$pdf = new PDFMerger();
			$user=\Yii::$app->user->identity;
			if(empty($user))return  BaseCarrierAPI::getResult(1, '', '用户登陆信息缺失,请重新登陆');
			$puid = $user->getParentUid();
			
			$order = current($data);reset($data);
			$order = $order['order'];
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];
		
			$tmpPath = array();
			
			//查询出订单操作号
			$package_sn = '';
			foreach ($data as $k => $v) {
				$order = $v['order'];
// 				$order->is_print_carrier = 1;
// 				$order->print_carrier_operator = $puid;
// 				$order->printtime = time();
// 				$order->save();
		
				$checkResult = CarrierAPIHelper::validate(1,1,$order);
				$shipped = $checkResult['data']['shipped'];
				
				if(empty($shipped->tracking_number)){
// 					continue;
					return self::getResult(1, '', '平台订单号：'.$order->order_source_order_id.'请先获取跟踪号再打印');
				}
				
				//判断是否已经生成PDF，已经生成，则不需再调用API
				$label = CarrierUserLabel::findOne(['uid' => $puid, 'order_id' => $order->order_id, 'customer_number' => $order->customer_number]);
				if(!empty($label) && !empty($label->label_api_file_path)){
					$pdfUrl['pdfUrl'] = \Yii::$app->request->hostinfo.$label->label_api_file_path;
					$pdfUrl['filePath'] = \Yii::getAlias('@webroot').$label->label_api_file_path;
					
					$tmpPath[] = $pdfUrl['filePath'];
					
					continue;
				}
				
				$request = array('internationalLogisticsId' => $shipped->tracking_number);
				
				$response = self::getAliexpressPrintInfo($order->selleruserid, $request);
				
				if($response['Ack'] == false) return $response['error'];
				
				if(!isset($response['printInfo']['StatusCode'])){
					\Yii::info('lb_alionlinedelivery_print,puid:'.$puid.',result,order_id:'.$order->order_id.' '.json_encode($response),"carrier_api");
					
					return self::getResult(1, '', '速卖通返回结构失败e1,请稍后再试或者联系小老板技术'.$order->order_id);
				}
				
				if($response['printInfo']['StatusCode'] != 200){
					return self::getResult(1, '', '速卖通返回打印失败e2');
				}
				
				if(strlen($response['printInfo']['body'])<1000){
						$order->carrier_error = '速卖通返回打印失败e3';
						$order->save();
					return self::getResult(1, '', '速卖通返回打印失败e3');
				}else{
					//如果成功返回pdf 则保存到本地
// 					$order->is_print_carrier = 1;
					$order->print_carrier_operator = $puid;
					$order->printtime = time();
					$order->save();
					
					//if(in_array($puid, ['1', '10198', '6145', '14333'])){
						$pdfUrl = CarrierAPIHelper::savePDF2(base64_decode($response['printInfo']['body']),$puid,$order->order_id.$order['customer_number']."_api_".time());
						$filePath = $pdfUrl['filePath'];
						$pdfUrl['pdfUrl'] = \Yii::$app->request->hostinfo.$filePath;
						$pdfUrl['filePath'] = \Yii::getAlias('@webroot').$filePath;
						
						//保存信息到label队列
						$label = CarrierUserLabel::findOne(['uid' => $puid, 'order_id' => $order->order_id, 'customer_number' => $order->customer_number]);
						if(!empty($label) && empty($label->label_api_file_path)){
							$label->label_api_file_path = $filePath;
							$label->save(false);
						}
					/*}
					else{
						$pdfUrl = CarrierAPIHelper::savePDF(base64_decode($response['printInfo']['body']),$puid,$account->carrier_code.'_'.$order['customer_number'], 0);
					}*/
					
					$tmpPath[] = $pdfUrl['filePath'];
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
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	/**
	 * 用来确定打印完成后 订单的下一步状态
	 *
	 * 公共方法
	 **/
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}
	
	public static function getTestChannelListByOrderId(){
		$api = new AliexpressInterface_Api();
		$access_token = $api->getAccessToken ( 'cn1520025804qeul' );
		if ($access_token == false){
			return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
		}else{
			$api->access_token = $access_token;
		}
		
		$params['orderId'] = '502647021442441';
		
		$result = $api->getOnlineLogisticsServiceListByOrderId($params);
		
		$channelist = '';
		
// 		if(isset($result['success'])){
// 			if($result['success'] == true){
// 				$ListArr = $result['result'];
// 				foreach ($ListArr as $List){
// 					$channelist .= $List['logisticsServiceId'].':'.$List['logisticsServiceName'].";";
// 				}
// 			}
// 		}
		
		print_r($result);
	}
	
	public static function getTestCarrierCompany(){
		$api = new AliexpressInterface_Api();
		$access_token = $api->getAccessToken ( 'cn1514685701hmkj' );
		if ($access_token == false){
			return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
		}else{
			$api->access_token = $access_token;
		}
	
		$companyStr = '';
		
		$result = $api->qureyWlbDomesticLogisticsCompany();
		
		$result = $result['result'];
		
		foreach ($result as $val){
			$companyStr .= $val['companyId'].':'.$val['name'].';';
		}
		
		print_r($companyStr);
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通创建物流订单方法
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/30				初始化
	 +----------------------------------------------------------
	 **/
	public static function uploadAliexpressCarrier($selleruserid, $params, $tmpRefundAddressInfo, $tmpPickupAddressInfo) {
		try {
			//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
			$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
			if($is_aliexpress_v2){
				$result = self::uploadAliexpressCarrierV2($selleruserid, $params, $tmpRefundAddressInfo, $tmpPickupAddressInfo);
				return $result;
			}
			//****************判断此账号信息是否v2版    end*************
			
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $selleruserid );
				if ($access_token == false){
					return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
				}else{
					$api->access_token = $access_token;
				}
				// 接口传入参数
				$param = $params;
	
				// 调用接口创建线上发货物流订单
				$result = $api->createWarehouseOrder( $param );
				
				if(!isset($result['success'])){
					return array('Ack'=>false, 'error'=>print_r($result,true));
				}
				
				if($result['success'] == false){
					return array('Ack'=>false, 'error'=>print_r($result,true));
				}
				
				$result = $result['result'];
				
				if($result['success'] == false){
					return array('Ack'=>false, 'error'=>$result['errorDesc']);
				}
				
				return array('Ack'=>true, 'error'=>'', 'carrierInfo'=>$result);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>print_r($result,true));
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通创建物流订单方法，v2
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/09		初始化
	 +----------------------------------------------------------
	 **/
	public static function uploadAliexpressCarrierV2($selleruserid, $params, $tmpRefundAddressInfo, $tmpPickupAddressInfo) {
		try {
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Helper_Qimen::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api_Qimen ();
				// 接口传入参数整理
				$param = [
					'trade_order_id' => $params['tradeOrderId'],
					'trade_order_from' => $params['tradeOrderFrom'],
					'warehouse_carrier_service' => $params['warehouseCarrierService'],
					'domestic_logistics_company_id' => $params['domesticLogisticsCompanyId'],
					'domestic_logistics_company' => $params['domesticLogisticsCompany'],
					'domestic_tracking_no' => $params['domesticTrackingNo'],
					//'remark' => $params['remark'],
					'undeliverable_decision' => $params['undeliverableDecision'],
				];
				$declare_product_d_t_os = array();
				$declareProductDTOs = json_decode($params['declareProductDTOs'], true);
				foreach($declareProductDTOs as $one){
					$declare_product_d_t_os[] = [
						'product_id' => $one['productId'],
						'category_cn_desc' => $one['categoryCnDesc'],
						'category_en_desc' => $one['categoryEnDesc'],
						'product_num' => $one['productNum'],
						'product_declare_amount' => $one['productDeclareAmount'],
						'product_weight' => $one['productWeight'],
						'contains_battery' => empty($one['isContainsBattery']) ? false : true,
						'hs_code' => $one['hsCode'],
						'aneroid_markup' => empty($one['isAneroidMarkup']) ? false : true,
						'only_battery' => empty($one['isOnlyBattery']) ? false : true,
					];
				}
				$param['declare_product_d_t_os'] = json_encode($declare_product_d_t_os);
				$addressDTOs = json_decode($params['addressDTOs'], true);
				$address_d_t_os = [
					'receiver' => [
						'city' => $addressDTOs['receiver']['city'],
						'country' => $addressDTOs['receiver']['country'],
						'email' => $addressDTOs['receiver']['email'],
						'county' => $addressDTOs['receiver']['county'],
						'fax' => $addressDTOs['receiver']['fax'],
						'member_type' => $addressDTOs['receiver']['memberType'],
						'mobile' => $addressDTOs['receiver']['mobile'],
						'name' => $addressDTOs['receiver']['name'],
						'phone' => $addressDTOs['receiver']['phone'],
						'post_code' => $addressDTOs['receiver']['postcode'],
						'province' => $addressDTOs['receiver']['province'],
						'street_address' => $addressDTOs['receiver']['streetAddress'],
						'trademanage_id' => $addressDTOs['receiver']['trademanageId'],
						'address_id' => 0,
					],
					'sender' => [
						'city' => $addressDTOs['sender']['city'],
						'country' => $addressDTOs['sender']['country'],
						'county' => $addressDTOs['sender']['county'],
						'email' => $addressDTOs['sender']['email'],
						'member_type' => $addressDTOs['sender']['memberType'],
						'name' => $addressDTOs['sender']['name'],
						'phone' => $addressDTOs['sender']['phone'],
						'post_code' => $addressDTOs['sender']['postcode'],
						'province' => $addressDTOs['sender']['province'],
						'street_address' => $addressDTOs['sender']['streetAddress'],
						'trademanage_id' => $addressDTOs['sender']['trademanageId'],
						'address_id' => $addressDTOs['sender']['addressId'],
					]
				];
				if(isset($addressDTOs['pickup'])){
					//$addressId = empty($tmpPickupAddressInfo['addressId']) ? 0 : $tmpPickupAddressInfo['addressId'];
					$address_d_t_os['pickup'] = [
						'city' => $addressDTOs['pickup']['city'],
						'country' => $addressDTOs['pickup']['country'],
						'county' => $addressDTOs['pickup']['county'],
						'email' => $addressDTOs['pickup']['email'],
						'member_type' => $addressDTOs['pickup']['memberType'],
						'name' => $addressDTOs['pickup']['name'],
						'phone' => $addressDTOs['pickup']['phone'],
						'post_code' => $addressDTOs['pickup']['postcode'],
						'province' => $addressDTOs['pickup']['province'],
						'street_address' => $addressDTOs['pickup']['streetAddress'],
						'trademanage_id' => $addressDTOs['pickup']['trademanageId'],
						'address_id' => empty($addressDTOs['pickup']['addressId']) ? $addressId : $addressDTOs['pickup']['addressId'],
					];
				}
				else{
					//$address_d_t_os['pickup'] = [];
					//return array('Ack' => false, 'error' => '线上发货所使用的退货地址将统一在速卖通平台【交易】-【地址管理】页面设置，处理订单时将统一从【地址管理】页获取发货地址信息:中文语言。');
				}
				
				if(isset($addressDTOs['refund'])){
					$address_d_t_os['refund'] = [
						'address_id' => $addressDTOs['refund']['addressId'],
					];
				}
				else{
    				if(isset($tmpRefundAddressInfo['addressId'])){
    				    $address_d_t_os['refund'] = [
    				        'address_id' => $tmpRefundAddressInfo['addressId'],
    				    ];
    				}
    				
    				if(empty($address_d_t_os['refund'])){
    				    return array('Ack' => false, 'error' => '线上发货所使用的退货地址将统一在速卖通平台【交易】-【地址管理】页面设置，处理订单时将统一从【地址管理】页获取退货地址信息:中文语言。');
    				}
				}
				$param['address_d_t_os'] = json_encode($address_d_t_os);
				$param['id'] = $selleruserid;
	
				// 调用接口创建线上发货物流订单
				\Yii::info('lb_alionlinedelivery,createWarehouseOrder,response, '.json_encode($param),"carrier_api");
				$result = $api->createWarehouseOrder( $param );
				\Yii::info('lb_alionlinedelivery,createWarehouseOrder,result, '.json_encode($result),"carrier_api");
	
				if(!isset($result['success'])){
					return array('Ack'=>false, 'error'=>print_r($result,true));
				}
				
				if($result['success'] == false){
					return array('Ack'=>false, 'error'=>$result['error_message']);
				}
				
				$result2 = [
					'errorCode' => $result['error_code'],
					'errorDesc' => empty($result['error_message']) ? '' : $result['error_message'],
					'intlTrackingNo' => empty($result['intl_tracking_no']) ? '' : $result['intl_tracking_no'],
					'outOrderId' => $result['out_order_id'],
					'success' => $result['success'],
					'tradeOrderFrom' => $result['trade_order_from'],
					'tradeOrderId' => $result['trade_order_id'],
					'warehouseOrderId' => $result['warehouse_order_id'],
				];
				
				//如果运单号为空，需再次获取
				if(empty($result2['intlTrackingNo']) && !empty($param['trade_order_id'])){
					try {
						// 接口传入参数
						$param = ['id' => $selleruserid, 'trade_order_id' => $param['trade_order_id']];
						// 获取跟踪号
						$res = $api->queryLogisticsOrderDetail( $param );
						\Yii::info('lb_alionlinedelivery,queryLogisticsOrderDetail,trade_order_id:'.$param['trade_order_id'].' '.json_encode($res),"carrier_api");
						$res = $res['result'];
						
						if(!empty($result['result_list'][0])){
							$result2['intlTrackingNo'] = $result['result_list'][0]['logistics_order_id'];
						}
					}catch (\Exception $ex){}
				}
	
				return array('Ack'=>true, 'error'=>'', 'carrierInfo'=>$result2);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>$ex->getMessage());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通获取跟中号方法
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/30				初始化
	 +----------------------------------------------------------
	 **/
	public static function getAliexpressTrackingNo($selleruserid, $params) {
		try {
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $selleruserid );
				if ($access_token == false){
					return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！carrier');
				}else{
					$api->access_token = $access_token;
				}
				// 接口传入参数
				$param = $params;
	
				// 调用接口创建线上发货物流订单
				$result = $api->getOnlineLogisticsInfo( $param );
				
// 				$result = $api->getLogisticsSellerAddresses( $param );
				
// 				print_r($result);
// 				exit;
	
				if(!isset($result['success'])){
					return array('Ack'=>false, 'error'=>print_r($result,true));
				}
	
				if($result['success'] == false){
					return array('Ack'=>false, 'error'=>print_r($result,true));
				}
				
				$result = $result['result'];
	
// 				if($result['success'] == false){
// 					return array('Ack'=>false, 'error'=>$result['errorDesc']);
// 				}

				if(count($result) <= 0){
					return array('Ack'=>false, 'error'=>$result);
				}
	
				//速卖通接口会一次返回一张速卖通订单的多个重复上传的跟踪号，这里默认取下标为0的，因为看时间是最近的。
				return array('Ack'=>true, 'error'=>'', 'trackingInfo'=>$result[0]);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>print_r($result,true));
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通获取打印pdf
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/10/30				初始化
	 +----------------------------------------------------------
	 **/
	public static function getAliexpressPrintInfo($selleruserid, $params) {
		try {
			//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
			$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
			if($is_aliexpress_v2){
				$result = self::getAliexpressPrintInfoV2($selleruserid, $params);
				return $result;
			}
			//****************判断此账号信息是否v2版    end*************
			
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $selleruserid );
				if ($access_token == false){
					return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
				}else{
					$api->access_token = $access_token;
				}
				// 接口传入参数
				$param = $params;
	
				// 获取线上发货标签base64转码后生成运单标签的pdf字节流
				$result = $api->getPrintInfo( $param );
				
// 				print_r($result);
// 				exit;
				
				return array('Ack'=>true, 'error'=>'', 'printInfo'=>$result);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>print_r($result,true));
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通获取打印pdf，v2
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/10		初始化
	 +----------------------------------------------------------
	 **/
	public static function getAliexpressPrintInfoV2($selleruserid, $params) {
		try {
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Helper_Qimen::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api_Qimen ();
				// 接口传入参数
				$param = ['id' => $selleruserid, 'international_logistics_id' => $params['internationalLogisticsId']];
	
				// 获取线上发货标签base64转码后生成运单标签的pdf字节流
				$time = time();
				$result = $api->getPrintInfo( $param );
				\Yii::info('getPrintInfo, id:'.$param['international_logistics_id'].', diftime:'.(time() - $time).', '.json_encode($result),"carrier_api");
				
				if(!empty($result['error_message'])){
					return array('Ack'=>false, 'error'=>$result);
				}
	
				return array('Ack'=>true, 'error'=>'', 'printInfo'=>json_decode($result, true));
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>$ex->getMessage());
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
		try {
			$puid = $SAA_obj->uid;
			
			$request = array('internationalLogisticsId' => $print_param['tracking_number'],);
			$response = self::getAliexpressPrintInfo($print_param['selleruserid'], $request);
			
			if($response['Ack'] == false){
				return ['error'=>1, 'msg'=>print_r($response,true), 'filePath'=>''];
			}
			
			if($response['printInfo']['StatusCode'] != 200){
				return ['error'=>1, 'msg'=>'速卖通返回打印失败e_1', 'filePath'=>''];
			}
			
			if(strlen($response['printInfo']['body'])<1000){
				return ['error'=>1, 'msg'=>'速卖通返回打印失败e_2', 'filePath'=>''];
			}else{
				$pdfPath = CarrierAPIHelper::savePDF2(base64_decode($response['printInfo']['body']),$puid,$SAA_obj->order_id.$SAA_obj->customer_number."_api_".time());
				return $pdfPath;
			}
		}catch (CarrierException $e){
			return ['error'=>1, 'msg'=>$e->msg(), 'filePath'=>''];
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通获取卖家相关地址信息 发货地址信息，揽收地址信息，返回相应的地址列表。
	 * 
	 * 速卖通发货地址英文为：senderSellerAddressesList:发货地址信息
	 * 速卖通发货地址中文为：pickupSellerAddressesList:揽收地址信息
	 * 
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2016/07/21				初始化
	 +----------------------------------------------------------
	 **/
	public static function getAliexpressLogisticsSellerAddresses($selleruserid, $params){
		try {
			//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
			$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
			if($is_aliexpress_v2){
				$result = self::getAliexpressLogisticsSellerAddressesV2($selleruserid, $params);
				return $result;
			}
			//****************判断此账号信息是否v2版    end*************
			
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $selleruserid );
				if ($access_token == false){
					return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
				}else{
					$api->access_token = $access_token;
				}
				// 接口传入参数
				$param = array('request' => $params);
	
				//根据请求地址的类型：发货地址信息，揽收地址信息，返回相应的地址列表。
				$result = $api->getLogisticsSellerAddresses( $param );
				
				if(!isset($result['isSuccess'])){
					return array('Ack'=>false, 'error'=>print_r($result,true));
				}
	
				if($result['isSuccess'] == false){
					return array('Ack'=>false, 'error'=>print_r($result,true));
				}
	
				return array('Ack'=>true, 'error'=>'', 'addressInfo'=>$result);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>print_r($result,true));
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通获取卖家相关地址信息 发货地址信息，揽收地址信息，返回相应的地址列表。V2
	 *
	 * 速卖通发货地址英文为：senderSellerAddressesList:发货地址信息
	 * 速卖通发货地址中文为：pickupSellerAddressesList:揽收地址信息
	 *
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/09		初始化
	 +----------------------------------------------------------
	 **/
	public static function getAliexpressLogisticsSellerAddressesV2($selleruserid, $params){
		try {
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Helper_Qimen::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api_Qimen ();
				// 接口传入参数
				$params = str_replace(['[', ']', '"'], "", $params);
				$param = ['id' => $selleruserid, 'seller_address_query' => $params];
	
				//根据请求地址的类型：发货地址信息，揽收地址信息，返回相应的地址列表。
				$result = $api->getLogisticsSellerAddresses( $param );
	
				if(!isset($result['result_success'])){
					return array('Ack' => false, 'error' => $result['error_message']);
				}
	
				if(!$result['result_success']){
					return array('Ack' => false, 'error' => $result['error_message']);
				}
				
				$addressInfo = [];
				if(!empty($result['sender_seller_address_list'])){
					foreach($result['sender_seller_address_list'] as $one){
						$addressInfo['senderSellerAddressesList'][] = [
							'city' => $one['city'],
							'country' => $one['country'],
							'email' => $one['email'],
							'fax' => empty($one['fax']) ? '' : $one['fax'],
							'memberType' => $one['member_type'],
							'mobile' => empty($one['mobile']) ? '' : $one['mobile'],
							'name' => $one['name'],
							'phone' => $one['phone'],
							'postcode' => $one['postcode'],
							'province' => $one['province'],
							'streetAddress' => $one['street_address'],
							'trademanageId' => $one['trademanage_id'],
							'county' => $one['city'],
							'street' => $one['city'],
							'addressId' => $one['address_id'],
							'isNeedToUpdate' => empty($one['need_to_update']) ? false : $one['need_to_update'],
							'isDefault' => empty($one['is_default']) ? 0 : $one['is_default'],
						];
					}
				}
				
				if(!empty($result['pickup_seller_address_list'])){
					foreach($result['pickup_seller_address_list'] as $one){
						$addressInfo['pickupSellerAddressesList'][] = [
							'city' => $one['city'],
							'country' => $one['country'],
							'email' => $one['email'],
							'fax' => empty($one['fax']) ? '' : $one['fax'],
							'memberType' => $one['member_type'],
							'mobile' => empty($one['mobile']) ? '' : $one['mobile'],
							'name' => $one['name'],
							'phone' => $one['phone'],
							'postcode' => $one['postcode'],
							'province' => $one['province'],
							'streetAddress' => $one['street_address'],
							'trademanageId' => $one['trademanage_id'],
							'county' => $one['city'],
							'street' => $one['city'],
							'addressId' => $one['address_id'],
							'isNeedToUpdate' => empty($one['need_to_update']) ? false : $one['need_to_update'],
							'isDefault' => empty($one['is_default']) ? 0 : $one['is_default'],
						];
					}
				}
				if(!empty($result['refund_seller_address_list'])){
					foreach($result['refund_seller_address_list'] as $one){
						$addressInfo['refundSellerAddressesList'][] = [
							'city' => $one['city'],
							'country' => $one['country'],
							'email' => $one['email'],
							'fax' => empty($one['fax']) ? '' : $one['fax'],
							'memberType' => $one['member_type'],
							'mobile' => empty($one['mobile']) ? '' : $one['mobile'],
							'name' => $one['name'],
							'phone' => $one['phone'],
							'postcode' => $one['postcode'],
							'province' => $one['province'],
							'streetAddress' => $one['street_address'],
							'trademanageId' => $one['trademanage_id'],
							'county' => $one['city'],
							'street' => $one['city'],
							'addressId' => $one['address_id'],
							'isNeedToUpdate' => empty($one['need_to_update']) ? false : $one['need_to_update'],
							'isDefault' => empty($one['is_default']) ? 0 : $one['is_default'],
						];
					}
				}
				$addressInfo['isSuccess'] = $result['result_success']; 
				$addressInfo['errorCode'] = $result['error_code'];
				$addressInfo['errorDesc'] = empty($result['error_message']) ? '' : $result['error_message'];
	
				return array('Ack'=>true, 'error'=>'', 'addressInfo'=>$addressInfo);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error' => $ex->getMessage());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通获取云打印pdf数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2017/10/17				初始化
	 +----------------------------------------------------------
	 **/
	public static function getAliexpressCloudPrintInfo($selleruserid, $params) {
		try {
			//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
			$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
			if($is_aliexpress_v2){
				$result = self::getAliexpressCloudPrintInfoV2($selleruserid, $params);
				return $result;
			}
			//****************判断此账号信息是否v2版    end*************
			
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $selleruserid );
				if ($access_token == false){
					return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！');
				}else{
					$api->access_token = $access_token;
				}
				// 接口传入参数
				$param = $params;
	
				$result = $api->getPdfsByCloudPrint( $param );

				return array('Ack'=>true, 'error'=>'', 'printInfo'=>$result);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>print_r($result,true));
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通获取云打印pdf数据，v2
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/10		初始化
	 +----------------------------------------------------------
	 **/
	public static function getAliexpressCloudPrintInfoV2($selleruserid, $params) {
		try {
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Helper_Qimen::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api_Qimen ();
				// 接口传入参数
				$param['print_detail'] = empty($params['printDetail']) ? 'false' : $params['printDetail'];
				$warehouse_order_query_d_t_os = [];
				$warehouseOrderQueryDTOs = json_decode($params['warehouseOrderQueryDTOs'], true);
				foreach ($warehouseOrderQueryDTOs as $one){
					$item = [];
					/*if(isset($one['id'])){
						$item['id'] = $one['id'];
					}*/
					if(isset($one['internationalLogisticsId'])){
						$item['international_logistics_id'] = $one['internationalLogisticsId'];
					}
					if(isset($one['extendData'])){
						$item['extend_data'] = $one['extendData'];
					}
					$warehouse_order_query_d_t_os[] = $item;
				}
				$param['warehouse_order_query_d_t_os'] = json_encode($warehouse_order_query_d_t_os);
				$param['id'] = $selleruserid;
				$result = $api->getPdfsByCloudPrint( $param );
				
				$printInfo['success'] = isset($result['success']) ? $result['success'] : $result['result_success'];
				$printInfo['errorCode'] = $result['error_code'];
				$printInfo['errorMessage'] = empty($result['error_message']) ? '' : $result['error_message'];
				foreach($result['aeop_cloud_print_data_response_list'] as $one){
					$aeopCloudPrintDataResponse = [
						'orderCode' => $one['order_code'],
						'internationalLogisticsNum' => $one['international_logistics_num'],
						'wlWarehouseOrderId' => $one['wl_warehouse_order_id'],
					];
					foreach($one['cloud_print_data_list'] as $cloud_print_data){
						$aeopCloudPrintDataResponse['cloudPrintDataList'][] = [
							'printData' => $cloud_print_data['print_data'],
						];
					}
					
					$printInfo['aeopCloudPrintDataResponseList'][] = $aeopCloudPrintDataResponse;
				}
				
				return array('Ack'=>true, 'error'=>'', 'printInfo'=>$printInfo);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>$ex->getMessage());
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通获取跟中号方法 新方法
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2017/12/25				初始化
	 +----------------------------------------------------------
	 **/
	public static function getAliexpressTrackingNoV2($selleruserid, $params) {
		try {
			//****************判断此速卖通账号信息是否v2版    是则跳转     start*************
			$is_aliexpress_v2 = AliexpressInterface_Helper_Qimen::CheckAliexpressV2($selleruserid);
			if($is_aliexpress_v2){
				$result = self::getAliexpressTrackingNoV2New($selleruserid, $params);
				return $result;
			}
			//****************判断此账号信息是否v2版    end*************
			
			
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $selleruserid );
				if ($access_token == false){
					return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权失效！carrier');
				}else{
					$api->access_token = $access_token;
				}
				// 接口传入参数
				$param = $params;
	
				// 获取跟踪号
				$result = $api->queryLogisticsOrderDetail( $param );
				
				\Yii::info('lb_alionlinedelivery_trackingNo2,response,selleruserid:'.$selleruserid.':'.' '.json_encode($result),"carrier_api");
				
				if(!isset($result['success'])){
					return array('Ack'=>false, 'error'=>print_r($result,true));
				}
	
				if($result['success'] == false){
					return array('Ack'=>false, 'error'=>print_r($result,true));
				}
	
				$result = $result['result'];
	
				if(count($result) <= 0){
					return array('Ack'=>false, 'error'=>$result);
				}
	
				//速卖通接口会一次返回一张速卖通订单的多个重复上传的跟踪号，这里默认取下标为0的，因为看时间是最近的。
				return array('Ack'=>true, 'error'=>'', 'trackingInfo'=>$result[0]);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>print_r($result,true));
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 速卖通获取跟中号方法 新方法，v2新
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/09		初始化
	 +----------------------------------------------------------
	 **/
	public static function getAliexpressTrackingNoV2New($selleruserid, $params) {
		try {
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Helper_Qimen::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api_Qimen ();
				// 接口传入参数
				$param = ['id' => $selleruserid, 'trade_order_id' => $params['tradeOrderId']];
	
				// 获取跟踪号
				$result = $api->queryLogisticsOrderDetail( $param );
				$result = $result['result'];
	
				\Yii::info('lb_alionlinedelivery_trackingNo2,response,selleruserid:'.$selleruserid.':'.' '.json_encode($result),"carrier_api");
	
				if(!isset($result['success'])){
					return array('Ack' => false, 'error' => $result['error_desc']);
				}
	
				if($result['success'] == false){
					return array('Ack' => false, 'error' => $result['error_desc']);
				}
				
				if(empty($result['result_list'][0])){
					return array('Ack' => false, 'error' => 'result_list is null');
				}
				
				$result2 = $result['result_list'][0];
				$trackingInfo = [
					'tradeOrderId' => $result2['trade_order_id'],
					'logisticsOrderId' => $result2['logistics_order_id'],
					'internationalLogisticsType' => $result2['international_logistics_type'],
					'internationalLogisticsNum' => $result2['international_logistics_num'],
					'logisticsStatus' => $result2['logistics_status'],
					'channelCode' => $result2['channel_code'],
					
				];
	
				//速卖通接口会一次返回一张速卖通订单的多个重复上传的跟踪号，这里默认取下标为0的，因为看时间是最近的。
				return array('Ack'=>true, 'error'=>'', 'trackingInfo'=>$trackingInfo);
			}else{
				return array('Ack'=>false,'error'=>'速卖通账号：'.$selleruserid.'授权过期！');
			}
		}catch (\Exception $ex){
			return array('Ack'=>false,'error'=>print_r($result,true));
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 批量获取发货标签，每个标签独立array
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/08		初始化
	 +----------------------------------------------------------
	 **/
	public function getCarrierLabelApiPdfAlone($puid, $selleruserid, $internationalLogisticsIds){
		try {
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Helper_Qimen::checkToken ( $selleruserid );
			if ($a) {
				$api = new AliexpressInterface_Api_Qimen ();
				// 接口传入参数
				$param = ['id' => $selleruserid, 'international_logistics_ids' => implode($internationalLogisticsIds, ';')];
				$result = $api->getaloneprintinfos( $param );
		
				if(!empty($result['error_message'])){
					return ['error' => 1, 'msg' => $result['error_message']];
				}
				
				$pdfPath_list = array();
				foreach($result as $one){
					$pdf_body = json_decode($one['pdf_body'], true);
					if(empty($pdf_body)){
						return ['error'=>1, 'msg'=>'速卖通返回打印失败e_1'];
					}
					if($pdf_body['StatusCode'] != 200){
						return ['error'=>1, 'msg'=>'速卖通返回打印失败e_2'];
					}
					
					if(strlen($pdf_body['body']) < 1000){
						return ['error'=>1, 'msg'=>'速卖通返回打印失败e_3'];
					}
					
					$pdfPath = CarrierAPIHelper::savePDF2(base64_decode($pdf_body['body']), $puid, $one['international_logistics_id']."_api_".time());
					$pdfPath_list[$one['international_logistics_id']] = $pdfPath;
				}
				return ['error' => 0, 'msg' => '', 'filePaths' => $pdfPath_list];
			}else{
				return ['error' => 1, 'msg' => '速卖通账号：'.$selleruserid.'授权过期！'];
			}
		}catch (\Exception $ex){
			return ['error' => 1, 'msg' => $ex->getMessage()];
		}
	}
	
}

?>