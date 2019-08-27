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
| Create Date: 2015-3-9
+----------------------------------------------------------------------
 */
namespace common\api\carrierAPI;
use eagle\modules\carrier\models\SysCarrierAccount;
use common\helpers\Helper_Array;
use common\api\carrierAPI\BaseCarrierAPI;
use eagle\modules\carrier\models\SysShippingService;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use yii\base\Exception;
use eagle\modules\carrier\helpers\CarrierException;
// use common\lbException;

/**
 +------------------------------------------------------------------------------
 * SFC三态接口业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Interface
 * @package		vendors/CarrierAPI/
 * @subpackage  Exception
 * @author		qfl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class LB_SFCCarrierAPI extends BaseCarrierAPI
{
	public static $soapClient=null;
	public $wsdl = 'http://www.sendfromchina.com/ishipsvc/web-service?wsdl';
	private $token = null;
	private $appKey = null;
	private $userId = null;
	// public static $operate = null;
	
	public function __construct(){
		if(is_null(self::$soapClient)||!is_object(self::$soapClient)){
			try {
				self::$soapClient=new \SoapClient($this->wsdl, array('trace' => 1));
			}catch (Exception $e){
				return self::getResult(1,'','网络连接故障<br/>'.$e->getMessage());
				exit();
			}
		}
	}

	private function send($methodName,Array $params=[],OdOrder $order){
		if(!$this->appKey){
			$account = CarrierAPIHelper::getAllInfo($order)['account'];
			$this->appkey = $account->api_params['appKey'];
			$this->token = $account->api_params['token'];
			$this->userId = $account->api_params['userId'];
		}
		$data = array_merge([
			'HeaderRequest'=>[
				'appKey'=>$this->appkey,
				'token'=>$this->token,
				'userId'=>$this->userId
			]
		],$params);
		return self::$soapClient->__soapCall($methodName, [$data]);
	}
	
	/*
	 * 获取三态物流商的运输方式
	 */
	public function getShippingMethod($order){
		try{
			$params = [];
			$result = $this->send('getShipTypes',$params,$order);
			$str = '';
			foreach($result->shiptypes as $v){
				$str .= $v->method_code.':'.$v->cn_name.';';
			}
			return $str;
		}catch(\SoapFault $e){return self::getResult(1,'',$e->getMessage());}
	}

	/**
	 +----------------------------------------------------------
	 * 申请订单号
	 +----------------------------------------------------------
	 **/
	public function getOrderNO($data){
		try{
			//初次对接获取物流商的运输方式 又需要的时候再打开注释
			// echo '<pre>';
			// print_r($this->getShippingMethod($data['order']));
			// echo '</pre>';die;

			$order = $data['order'];
			$post = $data['data'];

			//获取到所需要使用的数据
			$info = CarrierAPIHelper::getAllInfo($order);
			$service = $info['service'];
			$account = $info['account'];

			$goodsDetails = [];
			foreach($order->items as $i=>$item){
				$goodsDetails[$i] = [
					'detailDescriptionCN' => $post['detailDescriptionCN'][$i],
					'detailDescription' => $item->product_name,
					'detailQuantity' => $post['detailQuantity'][$i],
					// 'detailCustomLabel' => 'customlabel1',
					'detailWorth' => $post['detailWorth'][$i],
					'hsCode' =>$post['hscode'][$i],
					'detailEbayTxnId'=>$order->order_source=='ebay'?$item->order_source_transactionid:'',
					'detailEbayItemId'=>$order->order_source=='ebay'?$item->order_source_itemid:'',
					'detailEbayUserId'=>$order->order_source=='ebay'?$order->source_buyer_user_id:'',
					'enMaterial'=>$post['enMaterial'][$i],
					'cnMaterial'=>$post['cnMaterial'][$i],
				];
			}
			// 编辑参数
			$params = [
				'addOrderRequestInfo'=>[
					'customerOrderNo' => $data['data']['customer_number'],
					'shipperAddressType' => 2,
					'shipperName' => $account['address']['shippingfrom']['contact'],
					'shipperEmail' => $account['address']['shippingfrom']['email'],
					'shipperAddress' => $account['address']['shippingfrom']['street'],
					'shipperZipCode' => $account['address']['shippingfrom']['postcode'],
					'shipperPhone' => strlen($account['address']['shippingfrom']['phone'])>4?$account['address']['shippingfrom']['phone']:$account['address']['shippingfrom']['mobile'],
					'shippingMethod' => $service->shipping_method_code, //三态提供运输方式
					'recipientCountry' => $order->consignee_country, //三态提供的国家英文名称
					'recipientName' => $order->consignee,
					'recipientState' => $order->consignee_province,
					'recipientCity' => $order->consignee_city,
					'recipientAddress' => $order->consignee_address_line1.'-'.$order->consignee_address_line2,
					'recipientZipcode' => $order->consignee_postal_code,
					'recipientPhone' => strlen($order->consignee_phone)>4?$order->consignee_phone:$order->consignee_mobile,
					'recipientEmail' => $order->consignee_email,
					'orderStatus' => 'sumbmitted',//上传并交寄
					'goodsDescription' => $post['goodsDescription'],
					'goodsQuantity' => $post['total'],
					'goodsDeclareWorth' => $post['total_price'],
					'taxType'=>$post['taxType'],
					'taxesNumber'=> $post['taxesNumber'],
					'isRemoteConfirm'=> $service->carrier_params['isRemoteConfirm'],
					'isReturn' => intval($service->carrier_params['isReturn']),
					'evaluate'=>$post['evaluate'],
					'goodsWeight'=>$post['total_weight'],
					'goodsDetails' => $goodsDetails
				]
			];
			// echo '<pre>';
			// print_r($params);
			// echo '</pre>';die;
			$result = $this->send('addOrder',$params,$order);
			if($result->orderActionStatus=='Y'){ // 成功
				// var_dump($result);die();
				$r = CarrierAPIHelper::orderSuccess($order,$service,$result->customerOrderNo,OdOrder::CARRIER_WAITING_PRINT,$result->orderCode);
				return self::getResult(0,$r,'操作成功');
			}else{ 
				return self::getResult(1,$result,'操作失败：'.$result->note);
			}

		}
		catch(CarrierException $e){return self::getResult(1,'',$e->getMessage());}
		catch(\SoapFault $s){return self::getResult(1,'',$s->getMessage());}

		// return $this->send('addOrder',$data,$data['order']);
	}
	/**
	 +----------------------------------------------------------
	 * 取消跟踪号
	 +----------------------------------------------------------
	 **/
	// abstract public function cancelOrderNO($data);
	/**
	 +----------------------------------------------------------
	 * 交运
	 +----------------------------------------------------------
	 **/
	public function doDispatch($data){return self::getResult(1,'','该物流商不支持交运功能');}

	/**
	 +----------------------------------------------------------
	 * 申请跟踪号
	 +----------------------------------------------------------
	 **/
	public function getTrackingNO($data){return self::getResult(1,'','该物流商不支持获取跟踪号');}

	/**
	 +----------------------------------------------------------
	 * 打单
	 +----------------------------------------------------------
	 **/
	//$params $dealtype 标签格式标识 1 没有配货单 2有配货单
	public function doPrint($data){
		foreach ($data as $v) {

			// 获取打印单号
			$order = $v['order'];
			// 获取普通参数（打印参数）
			$printParams = (object)(SysShippingService::find()->where(['id'=>$order->default_shipping_method_code,'is_used'=>1])->one()->carrier_params);

			$url = "http://www.sendfromchina.com/api/label?orderCodeList={$order->customer_number}&printType={$printParams->printType}&print_type={$printParams->print_type}&printSize={$printParams->printSize}";
			//这里具体返回的数据需要等到有一笔成功上传的订单之后 进行一下测试，才能继续开发
			die();
		}
	}

	//用来判断是否支持打印
	public static function isPrintOk(){
		return true;
	}

	/*
	 * 用来确定打印完成后 订单的下一步状态
	 */
	public static function getOrderNextStatus(){
		return OdOrder::CARRIER_FINISHED;
	}
	
	//取消订单
	public function cancelOrderNO($data){
		return self::getResult(1,'','该物流商不支持取消功能');
	}

	
	
}
?>
