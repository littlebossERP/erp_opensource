<?php
namespace eagle\modules\dhgate\apihelpers;

use \Yii;
use eagle\models\SaasDhgateAutosync;
use eagle\models\SaasDhgateUser;
use eagle\models\QueueDhgateGetorder;
use eagle\models\QueueDhgatePendingorder;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\DhgateOrder;
use eagle\models\DhgateOrderItem;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\SysCountry;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\models\DhgateCountry;

/**
 +------------------------------------------------------------------------------
 * Dhgate api helper类
 +------------------------------------------------------------------------------
 */
class DhgateApiHelper {
	
	/**
	 * 启动/停用 敦煌后台任务
	 * @author dzt 2015-06-23
	 */
	static function SwitchDhgateCronjob($is_active,$dhgate_uid){
		try {
			//如果用户设置账号不启用,则关闭敦煌的相关同步订单功能 , 开关同步功能不修改时间 ， update time 除外
			if ( 1 == $is_active){
				$asyncAffectRows = SaasDhgateAutosync::updateAll( array('is_active'=>$is_active,'status'=>0,'times'=>0,'update_time'=>time()) , 'dhgate_uid=:dhgate_uid ',array(':dhgate_uid'=>$dhgate_uid));
				yii::info("SaasDhgateAutosync::updateAll $is_active,$dhgate_uid .affect rows:".$asyncAffectRows,"file");
				
				$queueAffectRows = QueueDhgateGetorder::updateAll(['is_active'=>$is_active,'status'=>0,'times'=>0,'update_time'=>time()] , ['dhgate_uid'=>$dhgate_uid]);
				yii::info("QueueDhgateGetorder::updateAll is_active:$is_active,dhgate_uid:$dhgate_uid .affect rows:".$queueAffectRows,"file");

				$pendingAffectRows = QueueDhgatePendingorder::updateAll(['is_active'=>$is_active,'status'=>0,'times'=>0,'update_time'=>time()] , ['dhgate_uid'=>$dhgate_uid]);
				yii::info("QueueDhgatePendingorder::updateAll is_active:$is_active,dhgate_uid:$dhgate_uid .affect rows:".$pendingAffectRows,"file");
				
			}else{
				$asyncAffectRows = SaasDhgateAutosync::updateAll(array('is_active'=>$is_active,'update_time'=>time()),'dhgate_uid=:p',array(':p' => $dhgate_uid));
				yii::info("SaasDhgateAutosync::updateAll $is_active,$dhgate_uid .affect rows:".$asyncAffectRows,"file");
					
				$queueAffectRows = QueueDhgateGetorder::updateAll(['is_active'=>$is_active,'update_time'=>time()] , ['dhgate_uid'=>$dhgate_uid]);
				yii::info("QueueDhgateGetorder::updateAll $is_active,$dhgate_uid .affect rows:".$queueAffectRows,"file");

				$pendingAffectRows = QueueDhgatePendingorder::updateAll(['is_active'=>$is_active,'update_time'=>time()] , ['dhgate_uid'=>$dhgate_uid]);
				yii::info("QueueDhgatePendingorder::updateAll $is_active,$dhgate_uid .affect rows:".$pendingAffectRows,"file");
			}
		}catch (\Exception $ex){
			yii::info("启动/停用 敦煌后台任务 Exception:".print_r($ex,true),"file");
			return array("success"=>false , "message"=>$ex->getMessage());
		}
		
		return array("success"=>true , "message"=>'');
		
	}
	

	/**
	 * 获取敦煌 结束的订单状态 code
	 * @author dzt 2015-06-24
	 */
	static function getOrderCompleteStatus(){
		$statusArr = array();
		if(array_key_exists("111000", QueueDhgateGetorder::$orderStatus))
			$statusArr[] =  "111000";
		if(array_key_exists("111111", QueueDhgateGetorder::$orderStatus))
			$statusArr[] =  "111111";
		
		return implode(',', $statusArr);
	}
	
	/**
	 * 根据puid获取敦煌账号 sellerloginid
	 * @param $puid 主账号 uid
	 * @return array sellerloginid 的数组  e.g. Array ( [0] => alldeals001 [1] => enmayer_shoes )
	 * @author dzt 2015-06-27
	 * */
	static function getSellerloginidByPuid($puid){
		if(empty($puid))
			return array();
		
		$sellerloginidArr = array();
		$SDUs = SaasDhgateUser::find()->where(['uid'=>$puid,'is_active'=>[1,2]])->asArray()->all();// is_active =0 和3 是客户主动停止的行为 
		foreach ($SDUs as $oneSDU){
			$sellerloginidArr[] =  $oneSDU['sellerloginid'];
		}
		
		return $sellerloginidArr;
	}
	
	/**
	 * 保存敦煌订单
	 * @param QueueDhgateGetorder $getorder_obj 同步订单列表单个订单对象
	 * @param array $order_detail 通过敦煌订单号取回的订单详细信息
	 * @param array $order_items  通过敦煌订单号取回的订单产品信息
	 * @author dzt 2015/06/24
	 */
	static function saveDhgateOrder($getorder_obj , $order_detail , $order_items) {
		try {
			#################################保存敦煌订单数据########################################
			$logTimeMS1=TimeUtil::getCurrentTimestampMS();
			$SDU_obj = SaasDhgateUser::findOne(['dhgate_uid'=>$getorder_obj->dhgate_uid]);
			
			// 因为get order list和get order detail 返回的数据里面有重复的，
			// 所以这里先将QueueDhgateGetorder里面的 order_info (base order info)拿出来，再由 order detail的内容覆盖
			$dhgateOrderHeader = json_decode($getorder_obj->order_info , true);
			$orderContact = empty($order_detail['orderContact'])? array():$order_detail['orderContact'];// 订单联系信息
			$orderDeliveryList =  empty($order_detail['orderDeliveryList'])? array():$order_detail['orderDeliveryList']; // 订单送货地址信息

			if(isset($order_detail['status']))// dzt20151229 下面的unset 不知道什么时候加的，可能由于有些接口没有返回这个status 导致exception
				unset($order_detail['status']);// api的执行状态
			$order_detail['orderContact'] = json_encode($orderContact);
			$order_detail['orderDeliveryList'] = json_encode($orderDeliveryList);
						
			$dhgateOrderHeader = array_merge($dhgateOrderHeader , $order_detail );
			$dhgateOrderItems = $order_items['orderProductList'];
			
			//组织数据
			$data_arr = array(
				'orderNo' => @$dhgateOrderHeader['orderNo'],
				'orderStatus' => @$dhgateOrderHeader['orderStatus'],
				'orderTotalPrice' => @$dhgateOrderHeader['orderTotalPrice'],
				'actualPrice' => @$dhgateOrderHeader['actualPrice'],
				'commissionAmount' => @$dhgateOrderHeader['commissionAmount'],
				'fillingMoney' => @$dhgateOrderHeader['fillingMoney'],
				'gatewayFee' => @$dhgateOrderHeader['gatewayFee'],
				'itemTotalPrice' => @$dhgateOrderHeader['itemTotalPrice'],
				'reducePrice' => @$dhgateOrderHeader['reducePrice'],
				'refundMoney' => @$dhgateOrderHeader['refundMoney'],
				'risePrice' => @$dhgateOrderHeader['risePrice'],
				'sellerCouponPrice' => @$dhgateOrderHeader['sellerCouponPrice'],
				'shippingCost' => @$dhgateOrderHeader['shippingCost'],
				'orderContact' => @$dhgateOrderHeader['orderContact'],
				'orderDeliveryList'=>@$dhgateOrderHeader['orderDeliveryList'],
				'buyerId' => @$dhgateOrderHeader['buyerId'],
				'buyerNickName' => @$dhgateOrderHeader['buyerNickName'],
				'country' => @$dhgateOrderHeader['country'],
				'complaintStatus' => @$dhgateOrderHeader['complaintStatus'],
				'deliveryDate' => @$dhgateOrderHeader['deliveryDate'],
				'shippingType' => @$dhgateOrderHeader['shippingType'],
				'isWarn' => @$dhgateOrderHeader['isWarn'],
				'warnReason' => @$dhgateOrderHeader['warnReason'],
				'buyerConfirmDate' => @$dhgateOrderHeader['buyerConfirmDate'],
				'cancelDate' => @$dhgateOrderHeader['cancelDate'],
				'deliveryDeadline' => @$dhgateOrderHeader['deliveryDeadline'],
				'inAccountDate' => @$dhgateOrderHeader['inAccountDate'],
				'payDate' => @$dhgateOrderHeader['payDate'],
				'startedDate' => @$dhgateOrderHeader['startedDate'],
				'orderRemark' => @$dhgateOrderHeader['orderRemark'],
			);
			
			$DO_obj = DhgateOrder::findOne(['orderNo'=>strval($dhgateOrderHeader['orderNo'])]);
			if (!isset($DO_obj)){
				$DO_obj= new DhgateOrder();
				$data_arr['create_time'] = date('Y-m-d H:i:s',time()); // 非原始字段
			}
			$data_arr['sellerloginid'] = $SDU_obj->sellerloginid;// 非原始字段
			$DO_obj->setAttributes($data_arr,false);
			
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			if ($DO_obj->save(false)){
				echo 'Original dhgate order:'.$dhgateOrderHeader['orderNo'].' saved'."\n";
				#################################保存订单产品#######################
				foreach ($dhgateOrderItems as $index=>$item){
					$DOI_obj = DhgateOrderItem::findOne(['itemcode'=>$item['itemcode'],'dhgateOrderNo'=>$dhgateOrderHeader['orderNo']]);
					if (!isset($DOI_obj)){
						$DOI_obj= new DhgateOrderItem();
						$item['dhgateOrderNo'] = $dhgateOrderHeader['orderNo'];// 非原始字段
					}
					$DOI_obj->setAttributes($item,false);
					if($DOI_obj->save()){
						$dhgateOrderItems[$index]['id'] = $DOI_obj->id; //for eagle order item 
					}else{
						echo "Original dhgate order item error:".print_r($DOI_obj,true)."\n";
						\Yii::info("Original dhgate order item error:".print_r($DOI_obj,true));
						return ['success'=>1,'message'=>"Original dhgate order item error:".print_r($DOI_obj,true)];
					}
					echo 'Original dhgate order item:'.$item['itemcode'].' saved'."\n";
				}
				
				
				$logTimeMS3=TimeUtil::getCurrentTimestampMS();
				#################################组织小老板订单数据########################################
				
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
				
				$myorder_arr = array();//小老板订单数据数组
				$order_arr=array(//主订单数组
						'order_status'=>$order_status,
						'order_source_status'=>$dhgateOrderHeader['orderStatus'],
						'is_manual_order'=>$is_manual_order,
						'order_source'=>'dhgate',
						//'order_type'=>'',
						'order_source_order_id'=>$dhgateOrderHeader['orderNo'],
						'selleruserid'=>$SDU_obj->sellerloginid,
// 						'source_buyer_user_id'=>$dhgateOrderHeader['buyerId'], // dzt20160109 改为 buyerNickName
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
// 						'consignee_mobile'=>'',  // 敦煌只有一个telephone
						'consignee_email'=>empty($orderContact['email'])?'':$orderContact['email'],
						'consignee_country'=>$orderContact['country'], //敦煌返回示例值：china,表示发货国家是中国
						'consignee_country_code'=>DhgateCountry::findOne(['name'=>$orderContact['country']])->countryid,
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
							'order_source_order_item_id'=>$one['id'],
// 							'order_source_transactionid'=>0,//订单来源交易号或子订单号 , 敦煌没有对应值     
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
				
				#############################保存为小老板订单###########################
				#############################保存为小老板1订单begin###########################
				$logTimeMS4=TimeUtil::getCurrentTimestampMS();
				
			 
				$eagleOrderId=-1;
				 
				echo "eagleOrderId:$eagleOrderId \n";
				$logTimeMS5=TimeUtil::getCurrentTimestampMS();
				#############################保存为小老板1订单end###########################
				$result =  OrderHelper::importPlatformOrder($myorder_arr,$eagleOrderId);
					
				$logTimeMS6=TimeUtil::getCurrentTimestampMS();
					
				\Yii::info("saveDhgateOrder  t2_1=".($logTimeMS2-$logTimeMS1).
				",t3_2=".($logTimeMS3-$logTimeMS2).",t4_3=".($logTimeMS4-$logTimeMS3).",t5_4=".($logTimeMS5-$logTimeMS4).
				",t6_5=".($logTimeMS6-$logTimeMS5).",puid=".$getorder_obj->uid,"file");
				
				if($result["success"]==1){
					echo "_saveOrderToEagle2 error:".$result['message']."\n";
					\Yii::info("_saveOrderToEagle2 error:".$result['message']);
					return ['success'=>false,'message'=>"_saveOrderToEagle2 error:".$result['message']];
				}else{
					return ['success'=>true , 'message'=>$result['message']];
				}
			}else{
				echo 'Fail to save original dhgate order:'.$dhgateOrderHeader['orderNo'];
				return ['success'=>false,'message'=>'保存敦煌原始订单失败'];
			}
			
		} catch (\Exception $e) {
			echo "file:".$e->getFile()." line:".$e->getLine()." error:".$e->getMessage()."\n";
			return ['success'=>false,'message'=>$e->getMessage()];
		}	
	}
	
	/**
	 * 保存eagle1.0订单
	 */
	static function _saveOrderToEagle1($order_data,$uid){
		$myorder_arr = array();//小老板订单数据数组
		$order_arr = array();
		$order_arr[0] = array(
				'order_status'=>$order_data['order_status'],
				'shipping_status'=>$order_data['shipping_status'],
				'order_source'=>$order_data['order_source'],
				'order_source_order_id'=>$order_data['order_source_order_id'],
// 				'order_source_site_id'=>$order_data['consignee_country'], // dhgate seller 后台可以看到站点是哪里的，但是订单api没有返回。
				'selleruserid'=>$order_data['selleruserid'],
				'order_source_srn'=>0,
				'source_buyer_user_id'=>$order_data['source_buyer_user_id'],
				'order_source_shipping_method'=>$order_data['order_source_shipping_method'],
				'order_source_create_time'=>$order_data['order_source_create_time'],
				'subtotal'=>$order_data['subtotal'],
				'shipping_cost'=>$order_data['shipping_cost'],
				'discount_amount'=>0,
				'grand_total'=>$order_data['grand_total'],
				'currency'=>$order_data['currency'],
				'consignee'=>$order_data['consignee'],
				'consignee_postal_code'=>$order_data['consignee_postal_code'],
				'consignee_city'=>$order_data['consignee_city'],
				'consignee_phone'=>$order_data['consignee_phone'],
				'consignee_email'=>$order_data['consignee_email'],
				'consignee_company'=>'',
				'consignee_country'=>$order_data['consignee_country'],
				'consignee_district'=>'',
				'consignee_country_code'=>$order_data['consignee_country_code'],
				'consignee_province'=>$order_data['consignee_province'],
				'consignee_address_line1'=>$order_data['consignee_address_line1'],
				'consignee_address_line2'=>$order_data['consignee_address_line2'],
				'consignee_address_line3'=>'',
				'default_carrier_code'=>'',
				'default_shipping_method_code'=>'',
				'paid_time'=>$order_data['paid_time'],
				'delivery_time'=>$order_data['delivery_time'],
				'user_message'=>$order_data['user_message'],
				//'orderShipped',
				//'items',
		);
		$items_arr = array();
		foreach ($order_data['items'] as $item){
			$item_arr = array(
					'order_source_order_item_id'=>$item['order_source_order_item_id'],
					'promotion_discount'=>0,
					'shipping_price'=>0,
					'shipping_discount'=>0,
					'sku'=>$item['sku'],
					'price'=>$item['price'],
					'quantity'=>$item['quantity'],
					'sent_quantity'=>'',
					'product_name'=>$item['product_name'],
					'photo_primary'=>$item['photo_primary'],
			);
			$items_arr[] = $item_arr;
		}
		$order_arr[0]['items']= $items_arr;
	
		$ship_arr = array();
		foreach ($order_data['orderShipped'] as $one){
			$arr = array(
					'tracking_number'=>$one['tracking_number'],
					'shipping_method_code'=>$one['shipping_method_name'],
			);
			$ship_arr[]=$arr;
		}
		$order_arr[0]['orderShipped']= $ship_arr;
		$myorder_arr[$uid]=$order_arr;
	
		$reqInfoJson = json_encode($myorder_arr,true);
		$postParams = array("orderinfo"=>$reqInfoJson);
		$result = EagleOneHttpApiHelper::sendOrderInfoToEagle($postParams);
		echo "result:".print_r($result,true);
		\Yii::info("result:".print_r($result,true));
		return $result;
	}
	
	public static function apiCallSum($api_call_name ='',$run_time = 0){
	/*	if ($api_call_name=='') exit;
	
		$now_str = date('Y-m-d H:i:s');
		$time_slot = substr($now_str, 0,13); //"2015-04-26 05"
		$connection = Yii::$app->db;
	
		$updateSql = "update api_ext_call_summary set total_count=total_count + 1,
			 total_time_ms = total_time_ms + ".$run_time."
				 where ext_call='$api_call_name' and time_slot= '$time_slot'";
			
		$command = $connection->createCommand($updateSql)  ;
	
		$affectRows = $command->execute();
		//	如果本来就没有这个record，先创建，在update
		if ($affectRows == 0){	//try once more
			//insert into
			$insertSql = "replace INTO  `api_ext_call_summary` (`ext_call`,`time_slot`,`total_count`,`total_time_ms`)
			VALUES ('$api_call_name','$time_slot',1,'$run_time')";
				
			$command = $connection->createCommand($insertSql)  ;
			$command->execute();
		}*/
	}
	
	
	/**
	 * 返回敦煌 可选的物流方式 
	 * @todo 这里是通过 api getShippingTypeList2 获取的回填物流方式，这里写死了所以要留意敦煌可能会随时更新
	 * @return array(array(shipping_code,shipping_value)) 或者 null.   其中null表示这个渠道是没有可选shipping code和name， 是free text
	 *
	 * shipping_code就是 通知平台的那个运输方式对应值
	 * shipping_value就是给卖家看到的可选择的运输方式名称
	 */
	public static function getShippingCodeNameMap(){
		return array(
				'139Express'=>'139Express',
				'ARAMEX'=>'ARAMEX',
				'Aramex-Online Shipping'=>'Aramex-Online Shipping',
				'BLI_AU'=>'Buylogic International-AU',
				'BLI'=>'Buylogic International-DE',
				'BLI_UK'=>'Buylogic International-UK',
				'CACESA-Online Shipping'=>'CACESA-Online Shipping',
				'CHINAPOSTAIR'=>'China Post Air',
				'China Post Air Mail'=>'China Post Air Mail',
				'China Post Air Maill-Online'=>'China Post Air Maill-Online',
				'CHINAPOSTSAL'=>'China Post SAL',
				'CHINA_POST_SAL_MAIL'=>'China Post SAL Mail',
				'CNE'=>'CNE',
				'DHL'=>'DHL',
				'DHL- Abroad Delivery'=>'DHL- Abroad Delivery',
				'DHL-Online Shipping'=>'DHL-Online Shipping',
				'DNJ'=>'DNJ',
				'DPD'=>'DPD',
				'EMS'=>'EMS',
				'ePacket'=>'ePacket',
				'Equick'=>'Equick',
				'Equick-express'=>'Equick-express',
				'Euro Business Parcel'=>'Euro Business Parcel',
				'FEDEX'=>'FEDEX',
				'Fedex- Abroad Delivery'=>'Fedex- Abroad Delivery',
				'FEDEX_IE'=>'FEDEX_IE',
				'FEDEX_IP'=>'FEDEX_IP',
				'Home delivery'=>'Home delivery',
				'HONGKONGPOST'=>'Hongkong Post',
				'JCEX'=>'JCEX',
				'JILLION'=>'JILLION',
				'Ocean'=>'Ocean freight',
				'PARCEL FORCE'=>'PARCEL FORCE',
				'post link'=>'post link',
				'RPX Express'=>'RPX Express',
				'Ruston Express'=>'Ruston Commercial-Online',
				'Ruston Express Mail'=>'Ruston russian air-Online',
				'SF European Mail-Online'=>'SF European Mail-Online',
				'SF Hybrid-EU'=>'SF Hybrid-EU',
				'SF-Express'=>'SF_Express',
				'SINGAPOREPOST'=>'Singapore post',
				'Sweden Post'=>'Sweden Post',
				'TNT'=>'TNT',
				'TNT Economy express'=>'TNT Economy express',
				'TNT Global express'=>'TNT Global express',
				'TNT Post'=>'TNT Post',
				'TNT- Abroad Delivery'=>'TNT- Abroad Delivery',
				'TOLL'=>'TOLL',
				'TOLL-Online Shipping'=>'TOLL-Online Shipping',
				'UBI Smart Parcel-Online'=>'UBI Smart Parcel-Online',
				'UPS'=>'UPS',
				'UPS- Abroad Delivery'=>'UPS- Abroad Delivery',
				'UPS-Expedited'=>'UPS-Expedited',
				'UPS-Saver'=>'UPS-Saver',
				'USPS'=>'USPS',
				'USPS- Abroad Delivery'=>'USPS- Abroad Delivery',
				'XRU'=>'XRU',
				'XRU-Economy'=>'XRU-Economy',
				'XRU-Quick'=>'XRU-Quick',
				'YANWEN'=>'YANWEN',
				'Royal Mail'=>'Royal Mail',
		);
	
	
	}
	
	/**
	 * 返回敦煌默认的物流方式shipping_code
	 * shipping_code就是 通知平台的那个运输方式对应值
	 * 
	 */
	public static function getDefaultShippingCode(){
		return "Home delivery";
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 敦煌 订单同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号表主键（必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=> array(is_active 是否启用 , last_time上次同步时间,message 信息 ,status 同步执行状态) 同步表的最新数据
	 *    //其中同步执行状态 为以下值'0'=>'等待同步','1'=>'已经有同步队列为他同步中','2'=>'同步成功','3'=>'同步失败','4'=>'同步完成',
	 * 					'message'=>执行详细结果
	 * 				    'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/12/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getLastOrderSyncDetail($account_key , $uid=0){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		
		//where  type 为time 定时抓取新单
		$SDAutosync  = SaasDhgateAutosync::find()->where(['uid'=>$uid ,'dhgate_uid'=>$account_key ,'type'=>'time'])->asArray()->one();
		$result = array();
		if (empty($SDAutosync)){
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}else{
			$result['is_active'] = $SDAutosync['is_active'];
			$result['last_time'] = $SDAutosync['last_time'];
			$result['message'] = $SDAutosync['message'];
			$result['status'] = $SDAutosync['status'];//status--- 0 没处理过; 2--部分完成; 3--上一次执行有问题;4--全部完成 刚好大致一样
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
		}
	}// end of getLastOrderSyncDetail
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取用户绑定的DH账号的异常情况，如token过期之类
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param int $uid 								uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array $problems						有问题账号信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2015/12/02				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getUserAccountProblems($uid){
		if(empty($uid))
			return [];
	
		$dhgateAccounts = SaasDhgateUser::find()->where(['uid'=>$uid])->andWhere("is_active<>3")->asArray()->all();
		if(empty($dhgateAccounts))
			return [];
	
		$accountUnActive = [];//未开启同步的账号
		$tokenExpired = [];//授权过期的账号
		$order_retrieve_errors = [];//获取订单失败
		$initial_order_failed = [];//首次绑定时，获取订单失败
		foreach ($dhgateAccounts as $account){
			if(2 == $account['is_active'] || time() >= $account['refresh_token_timeout']){
				$tokenExpired[] = $account;
				continue;
			}
		}
		$problems=[
		'token_expired'=>$tokenExpired,
		];
		return $problems;
	}
}