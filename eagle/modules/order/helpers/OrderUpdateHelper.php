<?php
namespace eagle\modules\order\helpers;

use eagle\modules\order\models\OdOrder;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\order\models\OrderRelation;
use eagle\modules\util\helpers\RedisHelper;
use Qiniu\json_decode;
/**
 +------------------------------------------------------------------------------
 * 订单数据更新类
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @version		1.0
 +------------------------------------------------------------------------------
 */
Class OrderUpdateHelper{
	static private $label_cn = [
			//同步订单涉及的字段
			'order_status'=>'订单流程状态',//订单流程状态
			'pay_status'=>'付款状态',//付款状态
			'shipping_status'=>'平台发货状态',//平台发货状态
			'order_source_create_time'=>'订单在来源平台的下单时间',//订单在来源平台的下单时间
			'paid_time'=>'订单付款时间',//订单付款时间
			'delivery_time'=>'订单发货时间',//订单发货时间
			'user_message'=>'用户留言',//用户留言
			'seller_commenttype'=>'卖家评价类型',//卖家评价类型
			'seller_commenttext'=>'卖家评价留言',//卖家评价留言
			'order_source_status'=>'订单来源平台订单状态',//订单来源平台订单状态
			'consignee'=>'收货人',
			'consignee_city'=>'收货人城市',
			'consignee_province'=>'收货人省',
			'consignee_country_code'=>'收货人国家代码',
			'consignee_country'=>'收货人国家名',
			'consignee_postal_code'=>'收货人邮编',
			'consignee_phone'=>'收货人电话',
			'consignee_email'=>'收货人Email',
			'consignee_address_line1'=>'收货人地址1',
			'consignee_address_line2'=>'收货人地址2',
			'consignee_address_line3'=>'收货人地址3',
			'consignee_company'=>'收货人公司',
			'consignee_district'=>'收货人区',
			'consignee_county'=>'收货人镇',
			'consignee_mobile'=>'收货人手机',
			'order_source_shipping_method'=>'平台下单时用户选择的运输服务',
			'source_buyer_user_id'=>'平台买家账号',
			'subtotal'=>'产品总价格',
			'shipping_cost'=>'运费',
			'discount_amount'=>'折扣',
			'commission_total'=>'订单平台佣金',
			'grand_total'=>'合计金额',
			'returned_total'=>'退款总金额',
			'last_modify_time'=>'平台最后修改时间',
			'sync_shipped_status'=>'虚拟发货同步状态',
			'default_warehouse_id'=>'仓库编号',
			
			'origin_shipment_detail'=>'收货人原始信息',
			
			'default_shipping_method_code'=>'运输服务id',
			'default_carrier_code'=>'物流商代码',
			
			//发货 涉及的字段
			'delivery_status'=>'发货状态',
			'delivery_id'=>'拣货单号',
			'distribution_inventory_status'=>'库存分配状态',
			//拣货 涉及的字段
			'is_print_picking'=>'是否打印拣货单',
			'print_picking_operator'=>'打印拣货单操作者',
			'print_picking_time'=>'打印拣货单时间',
			//配货 涉及的字段
			'is_print_distribution'=>'是否打印配货信息',
			'print_distribution_operator'=>'打印配货操作者',
			'print_distribution_time'=>'打印配货时间',
				
			//物流 涉及的字段
			'is_print_carrier'=>'是否打印物流',
			'print_carrier_operator'=>'打印物流操作者',
			'printtime'=>'打印物流时间',
			
			'order_verify'=>'订单验证',
		];
	
	static private $item_label_cn = [
		'platform_status'=>'平台商品状态',
		'photo_primary'=>'主图',
		'delivery_status'=>'允许发货状态',
	];
	
	static public $syncShipStatusLabelCN =  ['N'=>'无需同步','P'=>'待提交','S'=>'提交中','F'=>'提交失败','C'=>'提交成功','Y'=>'提交成功'];
	
	//订单商品指定更新的字段 ，控制待发货数量是由平台状态来更新，还是商品状态来更新的变量
	static public $updateItemAttr = [
		'lazada'=>[
			'photo_primary' , 'platform_status'
		],
		'cdiscount'=>[
			'platform_status', 'returned_quantity'
		],
		'priceminister'=>[
			'platform_status', 
		],
		'linio'=>[
			'platform_status',
		],
		
		'jumia'=>[
			'platform_status',
		],

	];
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更新订单数据统一接口
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $orderId	 		order model  存在 订单传入 order model ，或者 orderid
	 * @param  $newAttr			更新的数据
	 * @param  $isForce			待定参数
	 * @param  $fullName		操作者
	 * @param  $action			调用功能
	 * @param  $module			调用模块
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 *
	 * @invoking	OrderUpdateHelper::updateOrder($orderId , $newAttr ,false ,'Peter' , '修改订单' ,'order');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016-10-08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function updateOrder($orderId , $newAttr ,  $isForce = false , $fullName = '' , $action='' , $module ='order'  ){
		try {
			//validate $orderId
			if ($orderId instanceof OdOrder){
				$order = $orderId;
			}else if (is_string($orderId) || is_int($orderId)){
				$order = OdOrder::findOne($orderId);
			}else{
				return ['ack'=>false,'message'=>'order id格式不正确！','code'=>'4001','data'=>[]];
			}
			
			//validate $newAttr
			if (empty($newAttr)){
				return ['ack'=>false,'message'=>'newAttr 不能为空！','code'=>'4002','data'=>[]];
			}else if (is_array($newAttr)){
					
					
				$o = new OdOrder();
				$activeAttr = $o->getAttributes();
					
				if (isset($newAttr['statushide'])) unset($newAttr['statushide']);
				//validate field name is active
				foreach($newAttr as $key=>$value){
					if (array_key_exists($key, $activeAttr) == false){
						continue;// 不是有效的字段跳过
						//return ['ack'=>false,'message'=>'newAttr 中的 '.$key.'不是有效字段！','code'=>'4003','data'=>[]];
					}
				}

				if (isset($newAttr['order_id'])){
					unset($newAttr['order_id']);
				}
				
				$OriginData = [];
				
				$OriginStatus = $order->order_status;
				$CurrentStatus = $order->order_status;
				$OriginWarehouseID = $order->default_warehouse_id;
				$CurrentWarehouseID = $order->default_warehouse_id;
				$OriginSyncShipStatus = $order->sync_shipped_status;
				$CurrentSyncShippedStatus = $order->sync_shipped_status;
				$OriginExceptionStatus = $order->exception_status;
				$updateLog = '';// update order message
				$label_cn = self::$label_cn;
				$OrderStatusChange =false;
				foreach($newAttr as $k=>$v){
					if ($order->$k === $v) {
						//echo "<br> $k skip ".$order->$k." == $v";
						continue;//没有 改变的值 就跳过
					}
					
					if (!empty($updateLog)) $updateLog .=",";
					$thisFieldName =(!empty($label_cn[$k]))?  $label_cn[$k]:$k;
					if($k == 'default_warehouse_id'){
						$updateLog .= $thisFieldName."由[".$order->$k."]修改为[".$v."]";
						$CurrentWarehouseID = $v;
					}else
					if ($k == 'sync_shipped_status'){
						$updateLog .= $thisFieldName."由[".self::$syncShipStatusLabelCN[$order->$k]."]同步为[".self::$syncShipStatusLabelCN[$v]."]";
						$CurrentSyncShippedStatus = $v;
					}else
					if ($k == 'order_status'){
						$updateLog .= $thisFieldName."由[".OdOrder::$status[$order->$k]."]修改为[".OdOrder::$status[$v]."]";
						$CurrentStatus = $v;
						$OrderStatusChange = true;
					}else
					if (in_array($k , ['order_source_create_time' , 'paid_time','delivery_time'])){
						$updateLog .= $thisFieldName."由[".date("Y-m-d H:i:s",$order->$k)."]修改为[".date("Y-m-d H:i:s",$v)."]";
					}else{
						$updateLog .= $thisFieldName."由[".$order->$k."]修改为[".$v."]";
					}
					
					//赋值
					$order->$k = $v;
				}
				//exit($updateLog);
				$newAttr['update_time'] = time(); // update time 不需要写系统日志
				if (!empty($updateLog)){
					OperationLogHelper::log($module,$order->order_id,$action,'修改订单信息:'.$updateLog, $fullName);
				}else{
					return ['ack'=>true,'message'=>'订单没有变化无须修改','code'=>'2001','data'=>[]];
				}
				
				
				//$effect = intval(OdOrder::updateAll($newAttr, "order_id IN ($order->order_id) "));
				if ($order->save()){
					/*******************************************  callback start   ****************************************/
					$callbackErrorMsg = '';
					//订单状态发现变化
					if ($OrderStatusChange){
						
						$puid = \Yii::$app->subdb->getCurrentPuid();
						RedisHelper::delOrderCache( $puid , strtolower($order->order_source) );
						
						//告知dashboard面板，统计数量改变了
						//echo "<br> dash board $order->order_source, $CurrentStatus, $OriginStatus,$order->order_id ";
						OrderApiHelper::adjustStatusCount($order->order_source, $order->selleruserid, $CurrentStatus, $OriginStatus,$order->order_id);
						
						/************************************          重发订单专属回调         start                      ********************************/
						//重发订单变成已付款
						if($CurrentStatus == OdOrder::STATUS_PAY){
							
						}
						/************************************          重发订单专属回调         end                        ********************************/
						/************************************          取消订单专属回调        start                       ********************************/
						// 取消的订单 N 为无须执行
						if ($CurrentStatus == OdOrder::STATUS_CANCEL){
							// @todo 把setOrderSyncShippedStatus 这个逻辑提取出来
							//$tmpRt = OrderBackgroundHelper::setOrderSyncShippedStatus($order, 'N');
							$tmpRt = OrderBackgroundHelper::setRelationOrderSyncShippedStatus($order , $CurrentSyncShippedStatus , $OriginSyncShipStatus);
							if ($tmpRt['ack']==false){
								$callbackErrorMsg .= $tmpRt['message'];
							}
						}
						
						/************************************          取消订单专属回调      end                        ********************************/
						/************************************          完成订单专属回调   start                       ********************************/
						// 完成订单回调逻辑
						if ($CurrentStatus == OdOrder::STATUS_SHIPPED){
							try {
								//去掉虚假发货标签
								OrderApiHelper::unsetPlatformShippedTag($order->order_id);
							} catch (\Exception $e) {
								$callbackErrorMsg .= "unsetPlatformShippedTag Error Message:".$e->getMessage()." Line no:".$e->getLine();
							}
							//释放库存
							try {
								\eagle\modules\inventory\helpers\InventoryApiHelper::OrderProductReserveCancel($order->order_id,$module,$action);
							} catch (\Exception $e) {
								$callbackErrorMsg .= "OrderProductReserveCancel Error Message:".$e->getMessage()." Line no:".$e->getLine();
							}
							
						
							
						}//end of 完成订单专属回调 
						/************************************          完成订单专属回调  end                        ********************************/
						
						/************************************          订单状态变化共用回调    start                        ********************************/
						//已付款订单能与其他合并的情况， 假如移入已完成需要为其他订单检测一下剩下的订单数量 是否为一， 假如为一的情况 ， 要把剩下待合并订单标志去掉
						if ($OriginExceptionStatus == OdOrder::EXCEP_WAITMERGE && $OriginStatus == OdOrder::STATUS_PAY ){
						
							try {
								$canMergeOrderList = OrderHelper::listWaitMergeOrder($order);
							} catch (\Exception $e) {
								$callbackErrorMsg .= "listWaitMergeOrder Error Message:".$e->getMessage()." Line no:".$e->getLine();
							}
							try {
								if (count($canMergeOrderList) == 1){
									OdOrder::updateAll(['exception_status'=>OdOrder::EXCEP_WAITSEND], ['order_id'=>$canMergeOrderList , 'order_status'=>OdOrder::STATUS_PAY]);
									OperationLogHelper::log($module,$canMergeOrderList[0],$action,$order->order_id.'已付款至已完成,所以取消合并标志',$fullName);
								}
							} catch (\Exception $e) {
								$callbackErrorMsg .= $order->order_id.'已付款至已完成,所以取消合并标志'."  Error Message:".$e->getMessage()." Line no:".$e->getLine();
							}
							//剩下的订单为1 ， 清除待合并的标志
						
						}
						/************************************          订单状态变化共用回调     end                        ********************************/
					}//end of 订单状态变化
					
					// 更新待发货数量
					if ($OrderStatusChange || $OriginWarehouseID<>$CurrentWarehouseID){
						
						//已付款变成 不需要发货状态 时需要更新待发货数量
						
						foreach($order->items as $item){
							if (!empty($item->root_sku)){
								//默认值
								$OriginQty = $item['quantity'];
								$CurrentQty = $item['quantity'];
									
								//已付款,发货中=》取消	, 暂停 ， 已完成 ， 由需要算发货变成不需要算发货
								$zeroStatusList = [OdOrder::STATUS_CANCEL , OdOrder::STATUS_SUSPEND , OdOrder::STATUS_SHIPPED , OdOrder::STATUS_NOPAY];
									
								if ( in_array($OriginStatus, [OdOrder::STATUS_PAY,OdOrder::STATUS_WAITSEND])  &&  in_array($CurrentStatus,$zeroStatusList))	{
									$OriginQty = $item['quantity'];
									$CurrentQty = 0;
								}
									
								// 取消	, 暂停 ， 已完成 =》 已付款   由不算发货变成需要算发货
								if ($CurrentStatus == OdOrder::STATUS_PAY &&  in_array($OriginStatus,$zeroStatusList)){
									$OriginQty = 0;
									$CurrentQty = $item['quantity'];
								}
								
								// 暂停发货 -> 发货中，lrq20170926
								if ($OriginStatus == OdOrder::STATUS_SUSPEND && $CurrentStatus == OdOrder::STATUS_WAITSEND){
									$OriginQty = 0;
									$CurrentQty = $item['quantity'];
								}
									
								//echo "$rootSKU, $OriginWarehouseID, $CurrentWarehouseID, $OriginQty, $CurrentQty";
								$tmpRT = OrderBackgroundHelper::updateUnshippedQtyOMS($item->root_sku, $OriginWarehouseID, $CurrentWarehouseID, $OriginQty, $CurrentQty);
								if ($tmpRT['ack']==false){
									$callbackErrorMsg .= $tmpRT['message'];
								}
							}
							/*20170321start
							list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
							
							//默认值
							$OriginQty = $item['quantity'];
							$CurrentQty = $item['quantity'];
							
							//已付款,发货中=》取消	, 暂停 ， 已完成 ， 由需要算发货变成不需要算发货
							$zeroStatusList = [OdOrder::STATUS_CANCEL , OdOrder::STATUS_SUSPEND , OdOrder::STATUS_SHIPPED , OdOrder::STATUS_NOPAY];
							
							if ( in_array($OriginStatus, [OdOrder::STATUS_PAY,OdOrder::STATUS_WAITSEND])  &&  in_array($CurrentStatus,$zeroStatusList))	{
								$OriginQty = $item['quantity'];
								$CurrentQty = 0;
							}
							
							// 取消	, 暂停 ， 已完成 =》 已付款   由不算发货变成需要算发货
							if ($CurrentStatus == OdOrder::STATUS_PAY &&  in_array($OriginStatus,$zeroStatusList)){
								$OriginQty = 0;
								$CurrentQty = $item['quantity'];
							}
							
							//echo "$rootSKU, $OriginWarehouseID, $CurrentWarehouseID, $OriginQty, $CurrentQty";
							$tmpRT = OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, $OriginWarehouseID, $CurrentWarehouseID, $OriginQty, $CurrentQty);
							if ($tmpRT['ack']==false){
								$callbackErrorMsg .= $tmpRT['message'];
							}
							20170321end*/
						}
						
					}
					
					
					
					/*
					$list_clear_platform = [];
					//增加清除 平台redis
					if (!in_array($oneOrder->order_source, $list_clear_platform)){
						$list_clear_platform[] = $oneOrder->order_source;
					}
					
					//left menu 清除redis
					if (!empty($list_clear_platform)){
						OrderHelper::clearLeftMenuCache($list_clear_platform);
					}
					*/
					/*******************************************  callback  end  ****************************************/
				}else{
					//订单保存失败，返回失败信息
					return ['ack'=>false,'message'=>implode(',', $order->errors),'code'=>'4004','data'=>[]];
				}
				
				return ['ack'=>true,'message'=>$callbackErrorMsg,'code'=>'2000','data'=>[]];
			}else{
				return ['ack'=>false,'message'=>'newAttr 格式不正确！','code'=>'4005','data'=>[]];
			}
		} catch (\Exception $e) {
			return ['ack'=>false,'message'=>"Error Message:".$e->getMessage()." Line No:".$e->getLine(),'code'=>'4006','data'=>[]];
		}
	}//end of function updateOrder
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更新 平台订单商品信息 （【同步订单】时候专用的更新订单商品，手工订单与自己增加的本地产品 不会更新使用）
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $platform	 		order model  存在 订单传入 order model ，或者 orderid
	 * @param  $order_id			更新的数据
	 * @param  $item				待定参数
	 * @param  $add_info 			额外的参数
	 * @param  $fullName			操作者
	 * @param  $action				调用功能
	 * @param  $module				调用模块
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 *
	 * @invoking	OrderUpdateHelper::updateOrder($orderId , $newAttr ,false ,'Peter' , '修改订单' ,'order');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016-10-08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function updateItem($platform , $order_id , $item  , $add_info =[],  $fullName = '' , $action='' , $module ='order'){
		try {
			//validate params
			if (empty($item)){
				return ['ack'=>false,'message'=>'参数格式不正确！','code'=>'4001','data'=>[]];
			}
			
			if (empty($platform)){
				return ['ack'=>false,'message'=>'参数格式不正确！','code'=>'4002','data'=>[]];
			}
			
			/*  合并订单订单也需要更新 platform status
			if (isset(self::$updateItemAttr[$platform]) == false){
				return ['ack'=>true,'message'=>$platform."不支持更新商品信息",'code'=>'2001','data'=>[]];
			}
			*/
			//找出 对应 的订单商品
			if (in_array($platform,['lazada','linio','jumia'])){
				//$itemModel = OdOrderItem::findOne(['order_id'=>$order_id, 'order_source_order_item_id'=>$item['order_source_order_item_id']]);
				$itemModel = OrderGetDataHelper::getOrderItemModelByItemID($platform, $order_id, $item['order_source_order_item_id']);
			}elseif(in_array($platform,['priceminister'])){
				//$itemModel = OdOrderItem::findOne(['order_id'=>$order_id, 'source_item_id'=>$item['order_source_order_item_id']]);
				$itemModel = OrderGetDataHelper::getOrderItemModelByItemID($platform, $order_id, $item['order_source_order_item_id'], @$item['order_source_order_id']);
			}
			else{
				//$itemModel = OdOrderItem::findOne(['order_id'=>$order_id, 'order_source_itemid'=>$item['order_source_itemid']]);
				$itemModel = OrderGetDataHelper::getOrderItemModelByItemID($platform, $order_id, $item['order_source_itemid'], @$item['order_source_order_id']);
			}
			
			
			
			if (empty($itemModel)){
				return ['ack'=>false,'message'=>'订单商品不存在！','code'=>'4003','data'=>[]];
			}
			
			//设置额外参数
			if (isset($add_info['isUpdatePendingQty'])){
				$isUpdatePendingQty = $add_info['isUpdatePendingQty'];
			}else{
				$isUpdatePendingQty = true;
			}
			
			$OriginDeliveryStatus = $itemModel->delivery_status;
			
			$updateLog = '';
			
			
			//找出对应的平台订单商品状态更新是否能 发货
			/*
			if (isset(OrderHelper::$CanShipOrderItemStatus[$platform])){
				$CanShipStatus = OrderHelper::$CanShipOrderItemStatus[$platform];
				//存在指定状态发货
				if (in_array($item['platform_status']  ,$CanShipStatus )){
					//当前的平台状态允许发货
					$item['delivery_status'] = 'allow';
				}else{
					//当前的平台状态已经发货或不需要发货
					$item['delivery_status'] = 'ban';
				}
			}
			*/
			
			/*
			 * ----------------------------------------------
			 * 20170120 发货状态更新 的原则：根据 原来 的状态来决定新的状态改不
			 * 当 正常状态  禁用的状态 ，变成取消等状态 ： 不更新物流发货状态
			 * 当 正常状态 启用的状态 ，变成取消等状态 ： 更新物流发货状态
			 * 当未付款。取消禁用的状态 ， 变成正常状态：更新物流发货状态
			 * -----------------------------------------------
			 * 再由物流发货状态的变化来决定 是否改变待发货数量
			 * -----------------------------------------------
			 * ps 本地商品不用考虑更新问题
			 */
			// 只有平台来源的才更新 发货状态 ， 本地的假如 不发货直接删除，所以不维护 该状态
			if ($itemModel->item_source == 'platform'){
				$order = OdOrder::findOne(['order_id'=>$order_id]); // 获取订单状态
				$item['delivery_status']  = self::getOrderDeliveryStatus($platform, $item['platform_status']  , $order->order_status ,  $itemModel->manual_status);
				//echo "\n ### finally ".$order->order_id."  = ".$item['delivery_status'] ."\n";
			}
			
			//目前只更新$updateItemAttr中 指定平台 的指定 字段， 其他字段一律 不更新 
			
			
			if (isset(self::$updateItemAttr[$platform]) ){
				$currentUpdateAttr = self::$updateItemAttr[$platform];
			}else{
				$currentUpdateAttr = ['platform_status'];
			}
			
			//假如没有平台商品状态 ，则补上
			if (in_array('platform_status', $currentUpdateAttr) == false){
				array_push($currentUpdateAttr , 'platform_status');
			}
			
			//假如没有发货状态 ，则补上
			if (in_array('delivery_status', $currentUpdateAttr) == false){
				array_push($currentUpdateAttr , 'delivery_status');
			}
			
			foreach($currentUpdateAttr as $key){
				if (!empty($updateLog)) $updateLog .=",";
				if (isset($item[$key]) ){
					if ($itemModel->$key != $item[$key]){
						$updateLog .=''.@self::$item_label_cn[$key]." 由".@$itemModel->$key."改为".@$item[$key];
						$itemModel->$key = $item[$key];
					}
				}
			}
			
			//速卖通，把发货类型更新到addi_info，lrq20171019
			if ($platform == 'aliexpress'){
				try{
					if(!empty($item['sendGoodsOperator'])){
						$item_addi_info = array();
						$item_addi_json = $itemModel->addi_info;
						if(!empty($item_addi_json)){
							$item_addi_info = json_decode($item_addi_json, true);
						}
						
						$item_addi_info['sendGoodsOperator'] = $item['sendGoodsOperator'];
						$itemModel->addi_info = json_encode($item_addi_info);
					}
				}
				catch(Exception $ex){
					
				}
			}
			
			//ebay的product_attributes有可能过长，所以把过长的product_attributes保存到addi_info，lwj20180606
			if ($platform == 'ebay'){
			    try{
			        if(!empty($item['product_attributes'])&&strlen($item['product_attributes']) > 100){
			            $item_addi_info = array();
			            $item_addi_json = $itemModel->addi_info;
			            if(!empty($item_addi_json)){
			                $item_addi_info = json_decode($item_addi_json, true);
			            }
			
			            $item_addi_info['product_attributes'] = $item['product_attributes'];
			            $itemModel->addi_info = json_encode($item_addi_info);
			        }
			    }
			    catch(Exception $ex){
			        	
			    }
			}
			
			if (!empty($updateLog)){
				if ($itemModel->save()){
					//保存成功写上订单日志 
					OperationLogHelper::log($module,$itemModel->order_id,$action,'修改订单商品信息:'.$updateLog, $fullName);
					
				   /* 
					* 修改订单待发货数量 发生 的4种情况 ， 由于此处没有 仓库的变化， 所以不需要考虑仓库的影响
					* sku 变了 数量不变
					* sku 不变，数量变了
					* sku ，数量 都变了
					* sku ，数量都不变
					* 总结：
					* 原sku 的待发货数量  - 原数量
					* 新sku 的待发货数量 + 现在的数量
					* 进行上述两个步奏 能解决上述问题， 但sku ，数量都不变 执行这个只会浪费性能则不执行
					*
					*/
					//检查订单商品状态由需要发货变成不需要，则需要减去库存的待发货数量 ， delivery_status 为控制待发货数量的关键值
					if ($OriginDeliveryStatus != 'ban' && $item['delivery_status'] == 'ban' && $isUpdatePendingQty ==true){
						$errorMsg = '';
						if (!empty($itemModel->root_sku)){
							$rootSKU = $itemModel->root_sku;
							//list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($itemModel));
								
							//ban 为取消  默认仓库为0       订单的数量要从库存中减去
							list($ack , $code , $message  )  = array_values(OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, 0, 0,$odOrderItem->quantity ,  0 ));
							
							//echo "$rootSKU , $ack , $code , $message ";
							if (empty($ack)) $errorMsg .= " order_source_order_id=".$odOrderItem->order_source_order_id." Error Message:".$message ;
							if (!empty($errorMsg)) echo $errorMsg;
						}
					}
					
					return ['ack'=>true,'message'=>'','code'=>'2000','data'=>[]];
				}else{
					$errorMsg = $itemModel->errors;
					if (is_array($errorMsg)) $errorMsg = json_encode($errorMsg);
					return  ['ack'=>false,'message'=>$itemModel->order_source_order_item_id." error message".$errorMsg,'code'=>'4004','data'=>[]];
				}
			}else{
				return ['ack'=>true,'message'=>'数据没有变化','code'=>'2001','data'=>[]];
			}
				
		} catch (\Exception $e) {
			return ['ack'=>false,'message'=>"Error Message:".$e->getMessage()." Line No:".$e->getLine(),'code'=>'4006','data'=>[]];
		}

/*

$addtionLog = '';
		$item_tmp = $ItemList;
		//存储订单对应商品
		foreach ($item_tmp['sku'] as $key=>$val){
			$currentSKUMsg = '';
			if (isset($item_tmp['itemid'][$key])){
				$item = OdOrderItem::findOne($item_tmp['itemid'][$key]);
				$OriginQty = $item->quantity; //修改前的数量
				$OriginSKU = $item->sku; // 修改前的sku
				//订单商品只能修改数量
				$item->quantity = $item_tmp['quantity'][$key];
			}else{
				$item = new OdOrderItem();
				$OriginQty = 0; //修改前的数量
				$OriginSKU = '';// 修改前的sku
				
				//新商品需要保存相关的信息
				$item->order_id = $order->order_id;
				$item->product_name = $item_tmp['product_name'][$key];
				$item->sku = $item_tmp['sku'][$key];
				$item->ordered_quantity = $item_tmp['quantity'][$key];
				$item->quantity = $item_tmp['quantity'][$key];
				$item->order_source_srn =  empty($item_tmp['order_source_srn'][$key])?$order->order_source_srn:$item_tmp['order_source_srn'][$key];
				$item->price = $item_tmp['price'][$key];
				$item->update_time = time();
				$item->create_time = is_null($item->create_time)?time():$item->create_time;
				$currentSKUMsg = '增加了';
			}
		
			if ($item->save()){
				
				//检测是否需要修改待发货数量
				if ($OriginQty != $item_tmp['quantity'][$key] ||  $OriginSKU != $item_tmp['quantity'][$key]){
					
					list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));
						
					list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($rootSKU, $order->default_warehouse_id, $order->default_warehouse_id, $OriginQty, $item_tmp['quantity'][$key]));
					if ($ack){
						$addtionLog .= "$currentSKUMsg $rootSKU $OriginQty=>".$item_tmp['quantity'][$key];
					}
				}
			}//end of item save
			else {
				$result['success'] = false;
				foreach($item->getErrors() as $row ){
					$result['message'] .= $row;
				}
					
			}
		}//end of each item
			
		//写订单操作日志
		if (!empty($addtionLog)){
			OperationLogHelper::log('order',$order->order_id,'修改订单','编辑订单进行订单修改'.$addtionLog,\Yii::$app->user->identity->getFullName());
		}
*/
	}//end of function updateItem
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据当前item 信息生成 一个md5值
	 * 		ps：a.rumall  不需要标记发货的  b. 手工 订单  不需要标记发货的
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $orderId	 		order model  存在 订单传入 order model ，或者 orderid
	 * @param  $newAttr			更新的数据
	 * @param  $isForce			待定参数
	 * @param  $fullName		操作者
	 * @param  $action			调用功能
	 * @param  $module			调用模块
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 *
	 * @invoking	OrderUpdateHelper::updateOrder($orderId , $newAttr ,false ,'Peter' , '修改订单' ,'order');
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016-10-08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function modifySaveItems($platform , $order_id , $item  ,  $fullName = '' , $action='' , $module ='order'){
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装保存 root sku 的方法
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $item	 		order item model  存在 订单传入 order item model ，或者 order_item_id
	 * @param  $rootSKU			订单商品的root sku
	 * @param  $isUpdate		是否更新待发货数量
	 * @param  $fullName		操作者
	 * @param  $action			调用功能
	 * @param  $module			调用模块
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 * @invoking	OrderUpdateHelper::saveItemRootSKU('123' , '222' ,'Peter' , '修改订单' ,'order');
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016-10-08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function saveItemRootSKU( $itemid  , $rootSKU  , $isUpdate = false,  $fullName = '' , $action='' , $module ='order'){
		
		if ($itemid instanceof OdOrderItem){
			$item = $itemid;
		}else if (is_string($itemid) || is_int($itemid)){
			$item = OdOrderItem::findOne($itemid);
		}else{
			return ['ack'=>false,'message'=>'order item id格式不正确！','code'=>'4001','data'=>[]];
		}
		$originSKU = $item->root_sku;
		$item->root_sku = $rootSKU;
		
		if(!empty($item->addi_info)){
			$addi_info = json_decode($item->addi_info, true);
			if(!empty($addi_info['matching_pending'])){
				unset($addi_info['matching_pending']);
				if(empty($addi_info)){
					$item->addi_info = NULL;
				}
				else{
					$item->addi_info = json_encode($addi_info);
				}
			}
		}
		
		if ($item->save()){
			if (empty($fullName)){
				$fullName = \Yii::$app->user->identity->getFullName();
			}
			OperationLogHelper::log('order',$item->order_id,$action,'绑定sku由【'.$originSKU.'】改为【'.$item->root_sku.'】', $fullName);
			
			if ($isUpdate){
				$updateSKUList = [];
				if (!empty($originSKU)){
					$updateSKUList[] = $originSKU;
				}
				
				if (!empty($rootSKU)){
					$updateSKUList[] = $rootSKU;
				}
				//更新 待发货数量
				$rt = \eagle\modules\inventory\helpers\WarehouseHelper::RefreshSomeQtyOrdered($updateSKUList);
				
				if ($rt['status']==0){
					return ['ack'=>false,'message'=>'更新待发货数量失败:'.$rt['msg'],'code'=>'4002','data'=>[]];
				}
			}
			
			return ['ack'=>true,'message'=>'','code'=>'2000','data'=>[]];
		}else{
			$error = $item->getErrors();
			return ['ack'=>false,'message'=>'内部错误！'.json_encode($error),'code'=>'4003','data'=>[]];
		}
	}//end of function saveItemRootSKU
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更新关联订单的 root sku 的方法
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $item	 		order item model  存在 订单传入 order item model ，或者 order_item_id
	 * @param  $rootSKU			订单商品的root sku
	 * @param  $fullName		操作者
	 * @param  $action			调用功能
	 * @param  $module			调用模块
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 * @invoking	OrderUpdateHelper::batchSaveItemRootSKU('123' , '222' ,'Peter' , '修改订单' ,'order');
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016-10-08				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function batchSaveItemRootSKU($originSKU  , $rootSKU , $originRootSKU='' , $isUpdate = false ,  $fullName = '' , $action='' , $module ='order'){
		if (empty($originSKU)){
			return ['ack'=>false,'message'=>'origin SKU格式不正确！','code'=>'4001','data'=>[]];
		}
		if (empty($fullName)){
			$fullName = \Yii::$app->user->identity->getFullName();
		}
		$updateSKUList = [];
		if (empty($rootSKU)){
			$type = 'unbind';
		}else{
			$type = 'all';
		}
		$itemList = OrderGetDataHelper::getPayOrderItemBySKU($originSKU,$type, $originRootSKU);
		$errorMsg = '';
		foreach($itemList as $item){
			if (!empty($item->root_sku) && ( in_array($item->root_sku, $updateSKUList) == false) ){
				$updateSKUList[] = $item->root_sku;
			}
			
			$rt = self::saveItemRootSKU($item, $rootSKU,  $fullName , $action, $module);
			
			if (empty($rt['ack'])){
				$errorMsg .= $rt['message'];
			}
		}
		
		if (empty($errorMsg)){
			if  ($isUpdate){
				if (!empty($rootSKU)){
					$updateSKUList[] = $rootSKU;
				}
				//更新 待发货数量
				$rt = \eagle\modules\inventory\helpers\WarehouseHelper::RefreshSomeQtyOrdered($updateSKUList);
				
				if ($rt['status']==0){
					return ['ack'=>false,'message'=>'更新待发货数量失败:'.$rt['msg'],'code'=>'4003','data'=>[]];
				}
			}
			
			return ['ack'=>true,'message'=>'','code'=>'2000','data'=>[]];
		}else{
			return ['ack'=>false,'message'=>$errorMsg,'code'=>'4002','data'=>[]];
		}
	}//end of function batchSaveItemRootSKU
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据当前item 信息生成 一个md5值
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $order	 		order model  存在 订单传入 order model ， 不存在 订单传入null
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 *
	 * @invoking	OrderUpdateHelper::createItemMD5($items);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016-12-15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function createItemMD5($platform , $items){
		if (isset(self::$updateItemAttr[$platform])){
			$fieldList =self::$updateItemAttr[$platform];
			$string = '';
			foreach($items as $item){
				foreach($fieldList as $key){
					if (isset($item[$key])){
						$string .= $item[$key];
					}
				}
			}
			if (!empty($string)){
				return md5($string);
			}else{
				return '';
			}
		}else{
			return '';
		}
		
	}//end of createItemMD5
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 生成 订单是否可以发货
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $platform	 					平台 
	 * @param  $currentPlatformStatus	 		当前item 状态 
	 * @param  $currentOrderSourceStatus	 	当前 订单 状态
	 * @param  $currentManualStatus				当前的手工状态
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 *
	 * @invoking	OrderUpdateHelper::createItemMD5($items);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016-12-15				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderDeliveryStatus($platform , $currentPlatformStatus ,$currentOrderSourceStatus='' ,$currentManualStatus = ''){
		/*
		* 20170120 发货状态更新 的原则：根据 原来 的状态来决定新的状态改不
		* 当 正常状态  禁用的状态 ，变成取消等状态 ： 不更新物流发货状态
		* 当 正常状态 启用的状态 ，变成取消等状态 ： 更新物流发货状态
		* 当未付款。取消禁用的状态 ， 变成正常状态：更新物流发货状态
		*
		* ps 本地商品不用考虑更新问题
		*/
		try {
			$CannotShipStatus = self::getCanNotShipOrderItemStatus($platform);
			//第一优先级
			if (!empty($CannotShipStatus)){
				//$CanShipStatus = OrderHelper::$CanShipOrderItemStatus[$platform];
					
				//$CannotShipStatus = self::getCanNotShipOrderItemStatus($platform);
					
				//currentPlatformStatus 为空则表示没有 支持
				if (!empty($currentPlatformStatus)){
					//存在指定状态发货
					if (in_array($currentPlatformStatus ,$CannotShipStatus ) ==false && $currentManualStatus != 'disable'){
						//当前的平台状态允许发货
						//$item['delivery_status'] = 'allow';
						return 'allow';
					}else{
						//当前的平台状态已经发货或不需要发货
						//$item['delivery_status'] = 'ban';
						return 'ban';
					}
				}
			}
			//第2优先级
			// 能到这里， 证明上面 的item 级别平台 状态没有配对上 ， 所以要用订单级别的订单状态
			// dzt20200219 amazon暂停发货的订单更新订单时候 平台是shipped但由于暂停发货，这里传入的$currentOrderSourceStatus 是暂停发货，被认为不能发货
			// 由于平台状态没有传入，所以这里没法判断 平台是取消订单或已发货进行的更新，挂起中的订单就认为是可以发货的，再交由客户自己判断是否发货。而不是直接禁止发货
			if (!empty($currentOrderSourceStatus)){
			    $CanShipStatus = [OdOrder::STATUS_PAY , OdOrder::STATUS_WAITSEND , OdOrder::STATUS_SHIPPED , OdOrder::STATUS_OUTOFSTOCK
			        , OdOrder::STATUS_SUSPEND
			    ];
				if (in_array($currentOrderSourceStatus ,$CanShipStatus  ) && $currentManualStatus != 'disable'){
					//当前的平台状态允许发货
					return 'allow';
				}else{
					//当前的平台状态已经发货或不需要发货
					return 'ban';
				}
					
			}
		} catch (\Exception $e) {
			echo $e->getMessage().' line no '.$e->getLine();
			return 'allow';
		}
		
		//默认值
		return 'allow';
	}//end of function getOrderDeliveryStatus
	
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除指定 订单商品的数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $orderItemID	 					订单item的主键或者是item 的对象
	 * @param  $whid	 						仓库id
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 *
	 * @invoking	OrderUpdateHelper::deleteOrderItem(1921,0);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017-01-17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function deleteOrderItem($orderItemID , $whid='0'){
		try {
			//validate $orderItemID
			if ($orderItemID instanceof OdOrderItem){
				$item = $orderItemID;
			}else if (is_string($orderItemID) || is_int($orderItemID)){
				$item = OdOrderItem::findOne($orderItemID);
			}else{
				return ['ack'=>false,'message'=>'order item id格式不正确！','code'=>'4001','data'=>[]];
			}
			
			//更新待发货数量
			
			//list($ack , $message , $code , $rootSKU ) = array_values(OrderGetDataHelper::getRootSKUByOrderItem($item));

			if (!empty($item->root_sku)){
				list($ack , $code , $message  )  = array_values( OrderBackgroundHelper::updateUnshippedQtyOMS($item->root_sku, $whid, $whid, $item->quantity, 0));
					
				$msg = " 删除 商品 ".$item->sku;
			}else{
				$ack = true;
			}
			
			if ($ack){
				//成功更新  待发货数量  后执行删除！
				$rt = $item->delete();
				if ($rt === 0){
					return ['ack'=>true,'message'=>"请不要重复删除",'code'=>'2001','data'=>[]];
				}else if ($rt> 0){
					return ['ack'=>true,'message'=>"成功删除".$rt."条记录",'code'=>'2000','data'=>['logMsg'=>$msg]];
				}else{
					return ['ack'=>false,'message'=>"",'code'=>'4002','data'=>[]];
				}
			}else{
				//更新待发货数量 失败， 返回 失败原因
				return ['ack'=>false,'message'=>$message,'code'=>'4003','data'=>['code'=>$code]];
			}
			
			
		} catch (\Exception $e) {
			return ['ack'=>false,'message'=>"Error Message:".$e->getMessage()." Line No:".$e->getLine(),'code'=>'4100','data'=>[]];
		}
	}//end of function deleteOrderItem
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单不能发货的订单商品状态
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $orderItemID	 					订单item的主键或者是item 的对象
	 * @param  $whid	 						仓库id
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 *
	 * @invoking	OrderUpdateHelper::deleteOrderItem(1921,0);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017-01-17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getCanNotShipOrderItemStatus($platform){
		try {
			switch (true){
				case in_array(strtolower($platform), ['lazada']):
					return \eagle\modules\lazada\apihelpers\LazadaApiHelper::$CANNOT_SHIP_ORDERITEM_STATUS;
					break;
				case in_array(strtolower($platform), ['cdiscount']):
					return \eagle\modules\order\helpers\CdiscountOrderInterface::$CANNOT_SHIP_ORDERITEM_STATUS;
					break;
				case in_array(strtolower($platform), ['priceminister']):
					return \eagle\modules\order\helpers\PriceministerOrderInterface::$CANNOT_SHIP_ORDERITEM_STATUS;
					break;
				case in_array(strtolower($platform), ['newegg']):
					return \eagle\modules\order\helpers\NeweggOrderHelper::$CANNOT_SHIP_ORDERITEM_STATUS;
					break;
				case in_array(strtolower($platform), ['aliexpress']):
					return \eagle\modules\order\helpers\AliexpressOrderHelper::$CANNOT_SHIP_ORDERITEM_STATUS;
					break;
				default:
					return [];
			}
		} catch (\Exception $e) {
			return [];
		}
	}//end of function getCanNotShipOrderItemStatus
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更新 相关订单（合并订单）的item 相关信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $orderItemID	 					订单item的主键或者是item 的对象
	 * @param  $whid	 						仓库id
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 *
	 * @invoking	OrderUpdateHelper::updateRelationOrderItem(1921,0);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017-01-22				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function updateRelationOrderItem($orderid, $item){
		//找出订单关系的合并订单号
		$targetRel = OrderRelation::findOne(['father_orderid'=>$orderid , 'type'=>'merge' ]);
		//保存 合并的订单号
		$mergeOrderid = $targetRel->son_orderid;
		
		//找出对应的订单信息
		$order = OdOrder::findOne(['order_id'=>$mergeOrderid]);
		
		// 将对应的item信息 更新 合并订单item 上
		$item['order_id'] = $targetRel->son_orderid;
		$updateItemAddInfo = ['isUpdatePendingQty'=>false]; // 合并订单不更新库存
		$tmpRt = OrderUpdateHelper::updateItem($order->order_source, $order->order_id, $item, $updateItemAddInfo,  'System','同步订单','order');
		
	}//end of function updateRelationOrderItem
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 订单设置 订单item级别报关信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $orderItemId	 					订单item的主键或者是item 的对象
	 * @param  $nameCN	 						中文报关名字
	 * @param  $nameEN	 						英文报关名字
	 * @param  $price	 						申报金额
	 * @param  $weight	 						申报重量
	 * @param  $code	 						海关编码
	 * @param  $isChange						是否修改过的报关信息 (Y是修改过的，N为未修改过的)
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									ack								执行结果是还成功
	 * 									message							执行的相关信息
	 * 									code							执行的代码
	 * 									data							执行结果返回的数据
	 * @invoking					OrderUpdateHelper::setOrderItemDeclaration($orderIdList);
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/02/21				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderItemDeclaration($orderItemId , $nameCN = '' , $nameEN= '' , $price = '0.00'  , $weight = '0' , $code = ''  ,$isChange ='Y' , $fullName = '' , $action='修改报关信息' , $module ='order' ){
		try {
			if ($orderItemId instanceof OdOrderItem){
				$item = $orderItemId;
			}else if (is_string($orderItemId) || is_int($orderItemId)){
				$item = OdOrderItem::findOne($orderItemId);
			}else{
				return ['ack'=>false,'message'=>'order item id格式不正确！','code'=>'4001','data'=>[]];
			}
			
			//获取原有的报关信息
			$declaration = json_decode($item->declaration,true);
			
			if (empty($nameCN)){
				return ['ack'=>false,'message'=>'中文报关名字不能为空！','code'=>'4004','data'=>[]];
			}
			
			if (empty($nameEN)){
				return ['ack'=>false,'message'=>'英文报关名字不能为空！','code'=>'4005','data'=>[]];
			}
			
			
			if ($price<=0){
				return ['ack'=>false,'message'=>'申报金额必须大于0！','code'=>'4006','data'=>[]];
			}
			
			$logMsg = '';
			//中文报关名字
			if ( @$declaration['nameCN'] != $nameCN){
				if (empty($logMsg)){
					$logMsg .= '报关信息';
				}
				$logMsg .= "中文报关名字由".@$declaration['nameCN'] ."=>".$nameCN;
			}
			$declaration['nameCN'] = $nameCN;
			
			//英文报关名字
			if (@$declaration['nameEN'] != $nameEN){
				if (empty($logMsg)){
					$logMsg .= '报关信息';
				}
				$logMsg .= "英文报关名字由".@$declaration['nameEN'] ."=>".$nameEN;
			}
			
			$declaration['nameEN'] = $nameEN;
			
			//申报金额
			if (@$declaration['price'] != $price){
				if (empty($logMsg)){
					$logMsg .= '报关信息';
				}
				$logMsg .= "申报金额由".@$declaration['price'] ."=>".$price;
			}
			$declaration['price'] = $price;
			
			//申报重量
			if (@$declaration['weight'] != $weight){
				if (empty($logMsg)){
					$logMsg .= '报关信息';
				}
				$logMsg .= "申报重量由".@$declaration['weight'] ."=>".$weight;
			}
			$declaration['weight'] = $weight;
			
			//海关编码
			if (@$declaration['code'] != $code){
				if (empty($logMsg)){
					$logMsg .= '报关信息';
				}
				$logMsg .= "海关编码由".@$declaration['code'] ."=>".$code;
			}
			$declaration['code'] = $code;
			
			//是否修改过的报关信息
			if (@$declaration['isChange'] !=  $isChange){
				$declaration['isChange'] = $isChange;
			}
			
			// 有修改过报关信息则需要保存
			if (!empty($logMsg)){
				$item->declaration = json_encode($declaration);
				if ($item->save()){
					OperationLogHelper::log($module,$item->order_id,$action,$logMsg, $fullName);
					return ['ack'=>true,'message'=>'','code'=>'2000','data'=>[]];
				}else{
					$errors=$item->getErrors();
					return ['ack'=>false,'message'=>json_encode($errors),'code'=>'4002','data'=>[]];
				}
			}
			
		} catch (\Exception $e) {
			return ['ack'=>false,'message'=>'Error Message:'.$e->getMessage().' line no '.$e->getLine(),'code'=>'4003','data'=>[]];
		}
	}//end of function setOrderItemDeclaration
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重置订单的发货订单
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $orderId	 					订单item的主键或者是item 的对象
	 * @param  $whid	 						仓库id
	 +---------------------------------------------------------------------------------------------
	 * @return string
	 *
	 *
	 * @invoking	OrderUpdateHelper::resetOrderItemDeliveryStatus(1921);
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017-03-17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function resetOrderItemDeliveryStatus($orderId , $isForce = true){
		//validate $orderId
		if ($orderId instanceof OdOrder){
			$order = $orderId;
		}else if (is_string($orderId) || is_int($orderId)){
			$order = OdOrder::findOne($orderId);
		}else{
			return ['ack'=>false,'message'=>'order id格式不正确！','code'=>'4001','data'=>[]];
		}
		
		$CannotShipStatus = self::getCanNotShipOrderItemStatus($order->order_source);
		
		
		foreach($order->items as $item){
			$itemdata = [];
			if ($item->delivery_status == 'ban'){
				$isUpdate =false;
				
				if ($order->order_capture =='Y'){
					if ($item->manual_status != 'disable'){
						//手工订单都不是禁用都设置为可以允许发货
						$item->delivery_status = 'allow';
						$isUpdate=true;
					}
				}else{
					if (empty($CannotShipStatus)){
						// 以订单状态为准
						if ($order->order_status != $item->platform_status){
							$item->platform_status = $order->order_status;
							$isUpdate=true;
						}
							
						if (in_array($order->order_status , [OdOrder::STATUS_PAY , OdOrder::STATUS_WAITSEND])  && $item->manual_status =='enable' ){
							$item->delivery_status = 'allow';
							$isUpdate=true;
						}
					}else{
						//以订单商品状态为准
						if (in_array($item->platform_status , $CannotShipStatus) ==false && $item->manual_status =='enable' ){
							$item->delivery_status = 'allow';
							$isUpdate=true;
						}
					}
				}
				
				
				
				if ($isUpdate){
					if ($item->save() == false){
						//print_r($item->errors);
					}
				}
			}
		}
		return ['ack'=>true,'message'=>'','code'=>'2000','data'=>[]];
	}//end of function resetOrderItemDeliveryStatus
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重置订单表的first_sku，已配对则用配对SKU，否则用平台SKU
	 +---------------------------------------------------------------------------------------------
	 * @param     $order_id
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/08		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function resetFirstSku($order_id){
		$order = OdOrder::findOne(['order_id' => $order_id]);
		if(empty($order)){
			return ['success' => false, 'msg' => '订单不存在'];
		}
		//查询第一个item信息
		$item = OdOrderItem::find()->where(['order_id' => $order_id])->andWhere("manual_status!='disable'")->orderBy("order_item_id")->one();
		if(empty($item)){
			return ['success' => false, 'msg' => '订单明细不存在'];
		}
		$sku = empty($item->root_sku) ? $item->sku : $item->root_sku;
		$order->first_sku = $sku;
		if(!$order->save(false)){
			return ['success' => false, 'msg' => 'first_sku设置失败'];
		}
		return ['success' => true, 'msg' => ''];
	}
	
	
}