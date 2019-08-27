<?php
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: qfl <772821140@qq.com>
+----------------------------------------------------------------------
| Create Date: 2015-4-17
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use common\helpers\Helper_Array;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\order\models\OdOrderShipped;
use common\api\aliexpressinterface\AliexpressInterface_Base;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\carrier\helpers\CarrierException;

try{
include '../components/PDFMerger/PDFMerger.php';
}catch(\Exception $e){	
}
/**
 +------------------------------------------------------------------------------
 * ali接口业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		carrierAPI/aliExpressCarrierAPI
 * @subpackage  Exception
 * @author		qfl 
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_AliExpressCarrierAPI extends BaseCarrierAPI
{
	public static function getOnlineLogisticsServiceListByOrderId($order){
		$request = [
			// 'orderId'=>$order->order_source_itemid
			'orderId'=>'67158560508683'
		];
		$api = new AliexpressInterface_Api();
		$api->access_token = $api->getAccessToken($order->selleruserid);
		// $response = $api->listLogisticsService();
		// var_dump($response);die;
		$response = $api->getOnlineLogisticsServiceListByOrderId($request);
		$result = [];
		foreach($response['result'] as $v){
			if(!isset($result[$v['logisticsServiceId']]))
			$result[$v['logisticsServiceId']] = $v['logisticsServiceName'].' 运费:'.$v['trialResult'].' 时效:'.substr($v['logisticsTimeliness'],0,5);
		}
		// var_dump($result);die;
		return $result;
	}

	/*
	 * 速卖通线上发货订单提交  目前还没有完成 后期需要重新的调整接口**
	 */
	public function getOrderNO($data){
		try{
			//odOrder表内容
			$order = $data['order'];
			// if($order->order_source !='aliexpress'){
			// 	return self::getResult(1,'','该物流商仅支持速卖通订单');
			// }

			//对当前条件的验证  0 如果订单已存在 则报错 1 订单不存在 则报错
			$checkResult = CarrierAPIHelper::validate(1,0,$order);
			$puid = $checkResult['data']['puid'];

			//先通过接口获取发货方案
			// $serviceList = $this->getOnlineLogisticsServiceListByOrderId($order);


			//用户在确认页面提交的数据
			$e = $data['data'];
			//运输服务表内容
			$s = SysShippingService::find()->select(['carrier_params','carrier_account_id','web','service_name','service_code'])->where(['id'=>$order->default_shipping_method_code,'is_used'=>1])->one();
			if(empty($s))throw new CarrierException('请检查该订单运输服务是否已开启');

			$carrier_params = $s->carrier_params;

			$request['tradeOrderId'] = $order->order_source_order_id;//交易订单号
			$request['tradeOrderFrom'] = 'ESCROW';//订单来源
			$request['warehouseCarrierService'] = $e['warehouseCarrierService'];
			$request['domesticLogisticsCompanyId'] = $carrier_params['companyid'];
			$request['remark'] = $e['orderNote'];
			if($carrier_params['companyid']=='-1' && !empty($carrier_params['company']))$request['domesticLogisticsCompany'] = $carrier_params['company'];
			$request['domesticTrackingNo'] = $e['domesticTrackingNo'];

			//商品信息 json
			$product = [];
			foreach($order->items as $k=>$v){
				$product[] = [
					'productId'=>$v->order_source_itemid,//产品ID
					'categoryCnDesc'=>$e['Name'][$k],//申报中文名称
					'categoryEnDesc'=>$e['EName'][$k],//申报英文名称
					'productNum'=>$e['DeclarePieces'][$k],//产品件数
					'productDeclareAmount'=>$e['DeclaredValue'][$k],//申报金额
					'productWeight'=>$e['weight'][$k]/1000,//申报重量
					'isContainsBattery'=>$e['isContainsBattery'][$k],//是否包含锂电池(必填0/1)
					// 'scItemId'=>$v->sku,//仓储发货属性代码 团购订单货仓储发货必填 暂时没有接口查询属性代码
					// 'skuValue'=>$v->product_attributes//属性名称（团购订单，仓储发货必填，例如：White）
				];
			}
			$request['declareProductDTOs'] = $product;
			//用户账户表
			$account_obj = SysCarrierAccount::find()->where(['id'=>$s->carrier_account_id,'is_used'=>1])->one();
			if($account_obj == null)return self::getResult(1,'','请检查该该物流商帐号是否已启用');
			//组织订单地址信息
			$addr = $account_obj->address;
			$addressInfo = [
				'sender'=>[
					'country'=>$addr['shippingfrom']['country'],//国家简称*
					'province'=>$addr['shippingfrom']['province'],//省/州*
					'city'=>$addr['shippingfrom']['city'],//城市*
					'county'=>$addr['shippingfrom']['district'],//区县*
					'streetAddress'=>$addr['shippingfrom']['street'],//街道 *
					'name'=>$addr['shippingfrom']['contact'],//姓名*
					'phone'=>$addr['shippingfrom']['phone'],//联系电话*
					'mobile'=>$addr['shippingfrom']['mobile'],//手机
					'email'=>$addr['shippingfrom']['email'],//邮箱*
					'trademanageId'=>$order->selleruserid//旺旺*
				],
				'receiver'=>[
					'country'=>$order->consignee_country,//国家简称*
					'province'=>$order->consignee_province,//省/州*
					'city'=>$order->consignee_city,//城市*
					'county'=>$order->consignee_county,//区县*
					'streetAddress'=>$order->consignee_address_line1.$order->consignee_address_line2.$order->consignee_address_line3,//街道 *
					'name'=>$order->consignee,//姓名*
					'phone'=>$order->consignee_phone,//联系电话*
					'mobile'=>$order->consignee_mobile,//手机
					'email'=>$order->consignee_email,//邮箱*
					'trademanageId'=>$order->source_buyer_user_id//旺旺*
				]
			];
			//如果是中俄航空 还需要添加揽收地址
			if($s['carrier_params']['service']==180 && !empty($addr['pickupaddress']['contact'])){
				$addressInfo['pickup'] = [
					'country'=>$addr['pickupaddress']['country'],//国家简称*
					'province'=>$addr['pickupaddress']['province'],//省/州*
					'city'=>$addr['pickupaddress']['city'],//城市*
					'county'=>$addr['pickupaddress']['district'],//区县*
					'streetAddress'=>$addr['pickupaddress']['street'],//街道 *
					'name'=>$addr['pickupaddress']['contact'],//姓名*
					'phone'=>$addr['pickupaddress']['phone'],//联系电话*
					'mobile'=>$addr['pickupaddress']['mobile'],//手机
					'email'=>$addr['pickupaddress']['email'],//邮箱*
					'trademanageId'=>$order->selleruserid//旺旺*
				];
			}
			$request['addressDTOs'] = $addressInfo;
			/*==================================================================================*/
			//数据组织结束 开始发送
			var_dump($request);die;
			$data =[];
			foreach($request as $k=>$v){
				$data[$k] = json_encode($v);
			}
			$api = new AliexpressInterface_Api();
			$api->access_token = $api->getAccessToken($order->selleruserid);
			$response = $api->createWarehouseOrder($data);
			var_dump($response);die;

			##################################################################################
			//
			//返回数据需要确认 后面还需要更改
			//
			$serivce_code = $s->service_code;
			if(isset($response['success'])&&$response['success']){
				//上传成功后进行数据的保存 订单，运输服务，客户号，订单状态，跟踪号(选填),returnNo(选填)
				$r = CarrierAPIHelper::orderSuccess($order,$service,$response['data']['orderNo'],OdOrder::CARRIER_WAITING_DELIVERY);
				return  BaseCarrierAPI::getResult(0,$r,'操作成功!订单号');
			}
			
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}
	
	public function doPrint($data){
		try{
			foreach($data as $v){
				$order = $v['order'];
				if($v->customer_number){
					// $request['internationalLogisticsId'] = $v->customer_number;//国际运单号
					$request['internationalLogisticsId'] = '111111112121';//国际运单号
					$api = new AliexpressInterface_Api();
					$api->access_token = $api->getAccessToken($order->selleruserid);
					$response = $api->getPrintInfo($request);
					var_dump($response);die;
					##########################################################################
					$pdf = new \PDFMerger();
					//截至到这里 对于返回数据的判断条件还不清楚,等后期确认之后再写
				}
			}
		}catch(CarrierException $e){return self::getResult(1,'',$e->msg());}
	}

		
	/*
	 * 用来确定打印完成后 订单的下一步状态
	 */
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_WAITING_GETCODE;
	}
	
	public function cancelOrderNO($data){
		
	}
	
	//确认订单
	public function doDispatch($data){
		
	}
	
	//获取跟踪号
	public function getTrackingNO($data){
		
	}

	//重新发货
	public function Recreate(){return BaseCarrierAPI::getResult(1, '', '物流商不支持重新发货');}
}
?>
