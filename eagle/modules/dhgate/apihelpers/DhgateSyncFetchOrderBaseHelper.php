<?php
namespace eagle\modules\dhgate\apihelpers;

use \Yii;
use eagle\models\SaasDhgateAutosync;
use eagle\models\SaasDhgateUser;
use eagle\models\QueueDhgateGetorder;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\DhgateOrder;
use eagle\models\DhgateOrderItem;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\SysCountry;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\models\DhgateCountry;
use eagle\models\QueueDhgatePendingorder;


/**
* 
*/
class DhgateSyncFetchOrderBaseHelper{
	/**
	 * [saveDhgateOrder 保存敦煌订单]
	 * @Author   willage
	 * @DateTime 2016-08-17T11:43:55+0800
	 * @param    [type]              $getorder_obj [同步订单列表单个订单对象]
	 * @param    [type]              $order_detail [通过敦煌订单号取回的订单详细信息]
	 * @param    [type]              $order_items  [通过敦煌订单号取回的订单产品信息]
	 * @return   [type]                            [description]
	 */
	public static function saveDhgateOrder($getorder_obj,$order_detail,$order_items) {
		try {
			/**
			 * api 返回订单状态没有变化(与队列比较)，则返回成功，但不保存
			 */
			echo "orderStatus ".$order_detail['orderStatus']."\n";
			//前提是v2里面已经保存
			if (($getorder_obj->type==QueueDhgateGetorder::DAILY_QUERY)) {
				if ((isset($order_detail['orderStatus']))&&($getorder_obj->order_status==$order_detail['orderStatus'])) {
					echo "the same\n";
					return ['success'=>true , 'message'=>"orderStatus not change,do not re-save"];
				}
			}

			$SDU_obj = SaasDhgateUser::findOne(['dhgate_uid'=>$getorder_obj->dhgate_uid]);
		/**
		 * No.1 提取原始数据数据(list/detail和product)
		 */
			/**
			 * [$order_detail 订单联系信息和送货信息json编码后覆盖]
			 * @var [type]
			 */
			//订单联系信息
			$orderContact = empty($order_detail['orderContact'])? array():$order_detail['orderContact'];
			//订单送货地址信息
			$orderDeliveryList = empty($order_detail['orderDeliveryList'])? array():$order_detail['orderDeliveryList'];
			//下面的unset不知道什么时候加的,可能由于有些接口没有返回这个status导致exception
			if(isset($order_detail['status']))
				unset($order_detail['status']);//api的执行状态

			$order_detail['orderContact'] = json_encode($orderContact);
			$order_detail['orderDeliveryList'] = json_encode($orderDeliveryList);
			/**
			 * [$dhgateOrderHeader 合并order_list和order_detail作为od_order_v2]
			 * @var array
			 */
			$dhgateOrderHeader = json_decode($getorder_obj->order_info,true);
			$dhgateOrderHeader = array_merge($dhgateOrderHeader,$order_detail);
			/**
			 * [$dhgateOrderItems order_product作为od_order_items_v2]
			 * @var [type]
			 */
			$dhgateOrderItems = $order_items['orderProductList'];

			/**
			 * No.2 组织成小老板订单数据
			 */
			$is_manual_order = 0;
			if (in_array($dhgateOrderHeader['orderStatus'], array('101003'))){//未付款
				$order_status = 100;
			}elseif (in_array($dhgateOrderHeader['orderStatus'], array('103001','105003'))){//已付款,等待发货
				$order_status = 200;
			}elseif (in_array($dhgateOrderHeader['orderStatus'], array('111000'))){//申请取消
				//挂起，需要及时处理的订单，可能不需要发货
				$is_manual_order = 1;
				$order_status = 600;
			}elseif (in_array($dhgateOrderHeader['orderStatus'], array('103002','101009'))){
				$order_status = 400;
			}elseif (in_array($dhgateOrderHeader['orderStatus'], array('102111','102006','102007','111111'))){
				$order_status = 500;
			}elseif (in_array($dhgateOrderHeader['orderStatus'], array('102001','105001','105002','106001','106002','105004','106003'))){//需要挂起的订单
				//挂起，需要及时处理的订单，可能不需要发货
				$is_manual_order = 1;
				//根据是否有付款时间判断是否曾经付过款
				if (isset($dhgateOrderHeader['payDate']) && strlen($dhgateOrderHeader['payDate'])>10){
					//判断是否有发货时间来判断是否已发货
					if (isset($dhgateOrderHeader['deliveryDate']) && strlen($dhgateOrderHeader['deliveryDate'])>10){
						$order_status = 500;
					}else{
						$order_status = 200;
					}
				}else{
					$order_status = 100;
				}
			}
			//付款时间
			if (isset($dhgateOrderHeader['payDate']) && strlen($dhgateOrderHeader['payDate'])>10){
				$paid_time = strtotime($dhgateOrderHeader['payDate']);
				$pay_status = 1;
			}else{
				$paid_time = 0;
				$pay_status = 0;
			}
			//物流信息
			$orderShipped = array();
			foreach ($orderDeliveryList as $oneShipped){
				$tmp = array(
						'order_source_order_id'=>$dhgateOrderHeader['orderNo'],
						'order_source'=>'dhgate',
						'selleruserid'=>$SDU_obj->sellerloginid,
						'tracking_number'=>empty($oneShipped['newDeliveryNo'])?$oneShipped['deliveryNo']:$oneShipped['newDeliveryNo'],
						'shipping_method_name'=>empty($oneShipped['newShippingType'])?$oneShipped['shippingType']:$oneShipped['newShippingType'],
						'addtype'=>'平台API',
				);
				//赋缺省值
				$orderShipped[]= array_merge(OrderHelper::$order_shipped_demo,$tmp);
			}
			//发货时间,因为返回的结构不同所以要判断
			if (isset($dhgateOrderHeader['deliveryDate']) && strlen($dhgateOrderHeader['deliveryDate'])>10){
				$delivery_time = strtotime($dhgateOrderHeader['deliveryDate']);
				$shipping_status = 1;
			}else{
				$shipping_status = 0;
				$delivery_time = 0;
			}
			/**
			 * [$myorder_arr 小老板订单数据数组]
			 * @var array
			 */
			$myorder_arr = array();
			/**
			 * [$order_arr 主订单数组]
			 * @var array
			 */
			$country = str_replace('(China)', '', $orderContact['country']);
			$order_arr=array(
				'order_status'=>$order_status,
				'order_source_status'=>$dhgateOrderHeader['orderStatus'],
				'is_manual_order'=>$is_manual_order,
				'order_source'=>'dhgate',
				//'order_type'=>'',
				'order_source_order_id'=>$dhgateOrderHeader['orderNo'],
				'selleruserid'=>$SDU_obj->sellerloginid,
				//'source_buyer_user_id'=>$dhgateOrderHeader['buyerId'], // dzt20160109 改为 buyerNickName
				'source_buyer_user_id'=>$dhgateOrderHeader['buyerNickName'],
				'order_source_create_time'=> strtotime($dhgateOrderHeader['startedDate']),
				'order_source_shipping_method' => $dhgateOrderHeader['shippingType'],
				'subtotal'=>$dhgateOrderHeader['itemTotalPrice'],
				'shipping_cost'=>$dhgateOrderHeader['shippingCost'],
				'grand_total'=>$dhgateOrderHeader['orderTotalPrice'],
				'currency'=>'USD', // 敦煌订单api 返回数据默认 USD
				'consignee'=>$orderContact['firstName'].' '.$orderContact['lastName'],
				'consignee_postal_code'=>$orderContact['postalcode'],
				'consignee_city'=>$orderContact['city'],
				'consignee_phone'=>$orderContact['telephone'],
				//'consignee_mobile'=>'',  // 敦煌只有一个telephone
				'consignee_email'=>empty($orderContact['email'])?'':$orderContact['email'],
				'consignee_country'=>$orderContact['country'],//敦煌返回示例值：china,表示发货国家是中国
				'consignee_country_code'=>DhgateCountry::findOne(['name'=>$country])->countryid,
				'consignee_province'=>$orderContact['state'],
				'consignee_address_line1'=>$orderContact['addressLine1'],
				'consignee_address_line2'=>empty($orderContact['addressLine2'])?'':$orderContact['addressLine2'],
				'paid_time'=>$paid_time,
				'pay_status'=>$pay_status,
				'delivery_time'=>$delivery_time,
				'shipping_status'=>$shipping_status,
				//'user_message'=>json_encode($OrderById['orderMsgList']),
				'orderShipped'=>$orderShipped,
			);

			$userMessage = '';
			$orderitem_arr=array();//订单商品数组
			foreach ($dhgateOrderItems as $one){
				//商品属性
				$attr_arr = array();
				if (!empty($one['itemAttr'])){ // US7,Black
					$attr_arr = explode(',', $one['itemAttr']);
				}
				if (count($attr_arr)){
					$attr_str = implode(' + ', $attr_arr);
				}else{
					$attr_str = '';
				}
				$arr = array(
						'order_source_order_id'=>$dhgateOrderHeader['orderNo'],//平台订单号
						//20160818 敦煌没有order_source_order_item_id，过去是用原item表自动生成的id，现在不再保存原表，并且数据库要求不能为NULL，故使用itemcode(可能不唯一)
						'order_source_order_item_id'=>$one['itemcode'],
						//'order_source_transactionid'=>0,//订单来源交易号或子订单号 , 敦煌没有对应值
						'order_source_itemid'=>$one['itemcode'],//产品ID listing的唯一标示
						'sku'=>empty($one['skuCode'])?'':$one['skuCode'],//商品编码
						'price'=>$one['itemPrice'],//单价
						'ordered_quantity'=>$one['itemCount'],//下单时候的数量
						'quantity'=>$one['itemCount'],//需发货的商品数量,原则上和下单时候的数量相等，订单可能会修改发货数量，发货的时候按照quantity发货
						'product_name'=>$one['itemName'],//下单时标题  
						'photo_primary'=>"http://image.dhgate.com/".$one['itemImage'],//商品主图冗余
						'desc'=>$one['buyerRemark'],//订单商品备注,
						'product_attributes'=>$attr_str,//商品属性
						'product_unit'=>$one['measureName'],//单位
						'lot_num'=>$one['packingQuantity'],//单位数量  //@todo 20150625不能确定
						'product_url'=>"http://www.dhgate.com/".$one['itemUrl'],//商品url
				);
				//赋缺省值
				$orderitem_arr[] = array_merge(OrderHelper::$order_item_demo,$arr);
				$userMessage = $one['buyerRemark'];
			}
			//订单商品
			$order_arr['items'] = $orderitem_arr;
			//订单备注
			$order_arr['user_message'] = empty($userMessage)?'':$userMessage;
			//赋缺省值
			$myorder_arr[$getorder_obj->uid] = array_merge(OrderHelper::$order_demo,$order_arr);
			/**
			 * No.3 保存成小老板订单数据
			 */
			$result =  OrderHelper::importPlatformOrder($myorder_arr,-1);
			\Yii::info("saveDhgateOrder puid=".$getorder_obj->uid,"file");
			if($result["success"]==1){
				echo "_saveOrderToEagle2 error:".$result['message']."\n";
				\Yii::info("_saveOrderToEagle2 error:".$result['message']);
				return ['success'=>false,'message'=>"_saveOrderToEagle2 error:".$result['message']];
			}else{
				return ['success'=>true , 'message'=>$result['message']];
			}
		}catch(\Exception $e){
			echo "file:".$e->getFile()." line:".$e->getLine()." error:".$e->getMessage()."\n";
			return ['success'=>false,'message'=>$e->getMessage()];
		}
	}//end

	public static function savetoQueueDhgatePendingorder($queuegetorder){
		$tmp=self::getOrderUnpayUnship();
		$tmpArr = explode(',',$tmp);
		$pendingOrder=new QueueDhgatePendingorder();
		//$pendingOrder->id=$queuegetorder->id;
		$pendingOrder->uid =$queuegetorder->uid;
		$pendingOrder->dhgate_uid =$queuegetorder->dhgate_uid;
		$pendingOrder->status =$queuegetorder->status;
		$pendingOrder->order_status =$queuegetorder->order_status;
		$pendingOrder->orderid =$queuegetorder->orderid;
		$pendingOrder->times =$queuegetorder->times;
		$pendingOrder->order_info =$queuegetorder->order_info;
		$pendingOrder->last_time = $queuegetorder->last_time;
		$pendingOrder->gmtcreate =$queuegetorder->gmtcreate;
		$pendingOrder->message =$queuegetorder->message;
		if (in_array($queuegetorder->order_status, $tmpArr)) {
			$pendingOrder->next_execute_time=time()+1800;
		}else {
			$pendingOrder->next_execute_time=time()+21600;
		}
		$pendingOrder->create_time=$queuegetorder->create_time;
		$pendingOrder->update_time =$queuegetorder->update_time;
		$pendingOrder->type =$queuegetorder->type;
		$pendingOrder->is_active=$queuegetorder->is_active;
		$ret=$pendingOrder->save(false);
		return $ret;
	}

	public static function savetoQueueDhgateGetorder($queuependingorder){
		$getOrder=new QueueDhgateGetorder();
		//$getOrder->id=$queuependingorder->id;
		$getOrder->uid =$queuependingorder->uid;
		$getOrder->dhgate_uid =$queuependingorder->dhgate_uid;
		$getOrder->status =$queuependingorder->status;
		$getOrder->order_status =$queuependingorder->order_status;
		$getOrder->orderid =$queuependingorder->orderid;
		$getOrder->times =$queuependingorder->times;
		$getOrder->order_info =$queuependingorder->order_info;
		$getOrder->last_time = $queuependingorder->last_time;
		$getOrder->gmtcreate =$queuependingorder->gmtcreate;
		$getOrder->message =$queuependingorder->message;
		//$getOrder->next_execute_time=,
		$getOrder->create_time=$queuependingorder->create_time;
		$getOrder->update_time =$queuependingorder->update_time;
		$getOrder->type =$queuependingorder->type;
		$getOrder->is_active=$queuependingorder->is_active;
		$ret=$getOrder->save(false);
		return $ret;
	}
	/**
	 * [getOrderCompleteStatus 获取敦煌结束的订单状态code]
	 * @Author   willage
	 * @DateTime 2016-08-17T14:39:56+0800
	 * @return   [type]                   [description]
	 */
	public static function getOrderCompleteStatus(){
		$statusArr = array();
		if(array_key_exists("111000", QueueDhgateGetorder::$orderStatus))
			$statusArr[] =  "111000";
		if(array_key_exists("111111", QueueDhgateGetorder::$orderStatus))
			$statusArr[] =  "111111";
		return implode(',', $statusArr);
	}
	/**
	 * [getOrderNotCompleteStatus description]
	 * @Author   willage
	 * @DateTime 2016-08-17T16:26:01+0800
	 * @return   [type]                   [description]
	 */
	public static function getOrderNotCompleteStatus(){
		$statusArr = '101003,102001,103001,105001,105002,105003,105004,103002,101009,106001,106002,106003,102006,102007,102111';
		return $statusArr;
	}
	/**
	 * [getOrderUnpayUnship description]
	 * @Author   willage
	 * @DateTime 2016-08-19T16:30:54+0800
	 * @return   [type]                   [description]
	 * 111000	订单取消
	 * 101003	等待买家付款
	 * 102001	买家已付款，等待平台确认
	 * 103001	等待发货
	 * 105001	买家申请退款，等待协商结果
	 * 105002	退款协议已达成
	 * 105003	部分退款后，等待发货
	 * 105004	买家取消退款申请
	 * 103002	已部分发货
	 * 101009	等待买家确认收货
	 * 106001	退款/退货协商中，等待协议达成
	 * 106002	买家投诉到平台
	 * 106003	协议已达成，执行中
	 * 102006	人工确认收货
	 * 102007	超过预定期限，自动确认收货
	 * 102111	交易成功
	 * 111111	交易关闭
	 */
	public static function getOrderUnpayUnship(){
		$statusArr = '101003,102001,103001,105003,103002';
		return $statusArr;
	}

	/**
	 * [insertOrUpdateOrderQueue 把dhgate返回的订单header的信息保存到订单详情同步表QueueDhgateGetorder]
	 * @Author   willage
	 * @DateTime 2016-08-18T14:56:34+0800
	 * @param    [type]  &$dhgateOrderHeader [dhgate返回的订单header信息，array形式]
	 * @param    [type]  $SAA_obj            [订单list同步表SaasDhgateAutosync的对象]
	 * @param    [type]  $type               [订单详情同步表QueueDhgateGetorder记录对应的类型]
	 * @return   [type]                      [true or false]
	 */
	public static function insertOrUpdateOrderQueue(&$dhgateOrderHeader,$SAA_obj,$type){
		// 订单产生时间
		$gmtCreate_str = $dhgateOrderHeader ['startedDate'];
		$gmtCreate = strtotime ( $gmtCreate_str );

		$QAG_obj = QueueDhgateGetorder::findOne(['orderid'=>$dhgateOrderHeader ['orderNo']]);
		if (isset ( $QAG_obj )) {
			$QAG_obj->order_status = $dhgateOrderHeader['orderStatus'];
			$QAG_obj->order_info = json_encode ( $dhgateOrderHeader );
			$QAG_obj->update_time = time ();
			$QAG_obj->save ();
		} else {
			$QAG_obj = new QueueDhgateGetorder ();
			$QAG_obj->uid = $SAA_obj->uid;
			//	$QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
			$QAG_obj->dhgate_uid = $SAA_obj->dhgate_uid;
			$QAG_obj->status = 0;
			$QAG_obj->type = $type;
			$QAG_obj->order_status = $dhgateOrderHeader['orderStatus'];
			$QAG_obj->orderid = $dhgateOrderHeader ['orderNo'];
			$QAG_obj->times = 0;
			$QAG_obj->order_info = json_encode ( $dhgateOrderHeader );
			$QAG_obj->last_time = 0;
			$QAG_obj->gmtcreate = $gmtCreate;
			$QAG_obj->create_time = time ();
			$QAG_obj->update_time = time ();
			$QAG_obj->save ();
		}
		return true;
	}//end



}
?>