<?php
namespace common\api\shopeeinterface;
use \Yii;
use eagle\models\AliexpressOrder;
use eagle\models\SysCountry;
use eagle\models\AliexpressChildorderlist;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use Exception;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\order\models\OdOrder;
use eagle\models\SysShippingCodeNameMap;
use common\helpers\Helper_Array;
use eagle\modules\util\helpers\CountryHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\models\SaasAliexpressUser;
use eagle\models\SaasShopeeUser;
use eagle\models\QueueShopeeGetorder;

class ShopeeInterface_Helper{
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取订单列表数据，插入到队列queue_shopee_getorder
	 +---------------------------------------------------------------------------------------------
	 * @param	$shopee_uid		shopee user表id
	 * @param	$start_time		开始时间戳
	 * @param	$end_time		结束时间戳
	 * @param	$is_echo	是否echo信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/27		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOrderListToQueue($shopee_uid, $start_time, $end_time, $is_echo = false){
		$order_ids = array();
		$total_item = 0;
		//循环根据创建时间、更新时间读取订单
		$per_page = 20;
		//初始授权信息
		$user = SaasShopeeUser::find()->where(['shopee_uid' => $shopee_uid, 'status' => 1])->one();
		if(empty($user)){
			return self::getResult(false, $shopee_uid.' 此店铺信息不存在！E1');
		}
		$api = new ShopeeInterface_Api();
		$api->shop_id = $user->shop_id;
		$api->partner_id = $user->partner_id;
		$api->secret_key = $user->secret_key;
		
		$api_types = ['create', 'update'];
		foreach($api_types as $api_type){
			$next_row = 0;
			do{
				$is_next_page = false;
				$api_time = time();//接口调用时间
				//接口传入参数
				$param = [$api_type.'_time_from' => $start_time, $api_type.'_time_to' => $end_time, 'pagination_entries_per_page' => $per_page, 'pagination_offset' => $next_row];
				//调用接口获取订单列表
				$result = $api->GetOrdersList($param);
				\Yii::info("shopee--GetOrdersList--$api_type--$shopee_uid--".json_encode($result), "file");
				if(!isset($result['orders'])){
					if($is_echo){
						echo "shopee--findOrderListQuery--$api_type--$shopee_uid--err--".PHP_EOL;
						echo json_encode($result).PHP_EOL;
					}
					if(!empty($result['msg'])){
						\Yii::info("shopee---GetOrdersList--$api_type--$shopee_uid--err ".json_encode($result), "file");
						return self::getResult(false, $result['msg']);
					}
					break;
				}
				else if(count($result['orders']) == 0){
					break;
				}
				if($is_echo){
					echo "$api_type, orders: ".count($result['orders']).PHP_EOL;
					print_r ($result ['orders']);
				}
				foreach($result['orders'] as $order){
					$orderid = $order['ordersn'];
					//排除重复
					if(in_array($orderid, $order_ids)){
						continue;
					}
					$order_ids[] = $orderid;
					if($is_echo){
						echo "order_id: $orderid".PHP_EOL;
					}
					
					$QAG_obj = QueueShopeeGetorder::findOne(['orderid' => $orderid]);
					if(empty($QAG_obj)){
						$QAG_obj = new QueueShopeeGetorder();
						$QAG_obj->uid = $user->puid;
						$QAG_obj->shop_id = $user->shop_id;
						$QAG_obj->site = $user->site;
						$QAG_obj->shopee_uid = $user->shopee_uid;
						$QAG_obj->status = 0;
						$QAG_obj->orderid = $orderid;
						$QAG_obj->last_time = 0;
						$QAG_obj->create_time = time();
					}
					$QAG_obj->type = 3;   //新订单标识
					$QAG_obj->times = 0;
					$QAG_obj->order_status = $order['order_status'];
					$QAG_obj->gmtupdate = $order['update_time'];
					$QAG_obj->update_time = time();
					if(!$QAG_obj->save(false)){
						\Yii::info("step2 error puid=".$user->puid.",shop_id=".$user->shop_id.",totalOrderNum=".count($result['orders'])." error_msg : " . var_export($QAG_obj->errors, true), "file");
						if($is_echo){
							echo "step2 error puid=".$user->puid.",shop_id=".$user->shop_id.",totalOrderNum=".count($result['orders'])." error_msg : " . var_export($QAG_obj->errors, true).PHP_EOL;
						}
						return self::getResult(false, "保存队列信息失败！");
						break;
					}
					$total_item++;
					
				}
				
				//判断是否需要下一页
				if(!empty($result['more'])){
					$is_next_page = true;
				}
				$next_row += $per_page;
			}while($is_next_page);
		}
		
		return self::getResult(true, '', $total_item);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 根据queue_aliexpress_getorder_v2队列，同步订单信息到db
	 +---------------------------------------------------------------------------------------------
	 * @param	$shopee_uid		shopee user表id
	 * @param	$orderids			订单号列表
	 * @param	$is_echo		是否echo信息
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/02		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOrderListToDb($shopee_uid, $orderids, $is_echo = false){
		//初始授权信息
		$user = SaasShopeeUser::find()->where(['shopee_uid' => $shopee_uid, 'status' => 1])->one();
		if(empty($user)){
			return self::getResult(false, $shopee_uid.' 此店铺信息不存在！E1');
		}
		$api = new ShopeeInterface_Api();
		$api->shop_id = $user->shop_id;
		$api->partner_id = $user->partner_id;
		$api->secret_key = $user->secret_key;
		
		//接口传入参数
		$param = ['ordersn_list' => $orderids];
		//调用接口，获取订单明细信息
		$result = $api->GetOrderDetails($param);
		if(empty($result['orders'])){
			return self::getResult(false, '批量获取订单明细失败！');
		}
		
		 
		
		$errors = array();
		$success_orderids = [];
		foreach($result['orders'] as $order){
			try{
				$order_id = $order['ordersn'];
				if($is_echo){
					echo 'orderid:'.$order_id.PHP_EOL;
				}
				//获取item明细信息
				if(empty($order['items'])){
					$errors[$order_id] = 'items is null';
					continue;
				}
				$is_get_item_status = true;   //获取商品是否成功
				$subtotal = 0;   //产品总价
				$orderitem_arr = array();   //商品明细
				$totalDiscount = 0;// 产品折扣
				foreach($order['items'] as $item){
					$item_res = $api->GetItemDetail(['item_id' => $item['item_id']]);
					if(empty($item_res['item'])){// sHopee接口不稳定有时候返回null 重试即可
						$is_get_item_status = false;
						break;
					}
					$item_detail = $item_res['item'];
					//属性
					$attributes = '';
					if(!empty($item_detail['attributes'])){
						foreach($item_detail['attributes'] as $attribute){
							$attributes .= $attribute['attribute_name'].':'.$attribute['attribute_value'].' + ';
						}
					}
					$item['attributes'] = rtrim($attributes, ' + ');
					//单价
// 					$price = $item_detail['price'];
                    //dzt20191030 出现订单 sub_total+运费小于grand_total情况，发现产品单价低了，对比其他字段猜测用variation_original_price代替
                    
					
					if(!empty($item['variation_discounted_price'])){
					    $price = $item['variation_discounted_price'];
					    
					    // 计算折扣
					    if(!empty($item['variation_original_price'])){
					        $totalDiscount += $item['variation_original_price'] - $item['variation_discounted_price'];
					    }
					}elseif(!empty($item['variation_original_price'])){
					    $price = $item['variation_original_price'];
					}else{
						$price = $item_detail['price'];
					}
					
					if(!empty($item['is_wholesale'])){
						//当是以批发价购买
						if(!empty($item_detail['wholesales']['unit_price'])){
							$price = $item_detail['wholesales']['unit_price'];
						}
					}
					
					$arr = array(
						'order_source_order_id' => $order_id,//平台订单号
						'order_source_order_item_id' => $item['item_id'],
						'order_source_transactionid' => $order_id,//订单来源交易号或子订单号
						'order_source_itemid' => $item['item_id'],//产品ID listing的唯一标示
						'sku' => $item['variation_sku'],//商品编码
						'price' => $price,//单价
						'ordered_quantity' => $item['variation_quantity_purchased'],//下单时候的数量
						'quantity' => $item['variation_quantity_purchased'],//需发货的商品数量,原则上和下单时候的数量相等，订单可能会修改发货数量，发货的时候按照quantity发货
						'product_name' => $item['item_name'],//下单时标题
						'photo_primary' => empty($item_detail['images'][0]) ? '' : $item_detail['images'][0],//商品主图
						//'desc' => $item['memo'],//订单商品备注,
						'product_attributes' => $attributes,//商品属性
						//'product_unit' => $item['productunit'],//单位
						//'lot_num' => $item['lotnum'],//单位数量
						//'product_url' => '',//商品url
						'addi_info' => !empty($item['variation_name']) ? json_encode(['variation_name' => $item['variation_name'], 'item_sku' => $item['item_sku']]) : '',
					);
					//赋缺省值
					$orderitem_arr[] = array_merge(OrderHelper::$order_item_demo, $arr);
					
					//产品总价
					$subtotal += $item['variation_quantity_purchased'] * $price;
				}
				if(!$is_get_item_status){
					$errors[$order_id] = 'item_id can not find！';
					continue;
				}
				
				//**********************组织订单数据
				//订单状态
				$is_manual_order = 0;
				if (in_array($order['order_status'], array('UNPAID'))){//未付款
					$order_status = 100;
				}else if (in_array($order['order_status'], array('READY_TO_SHIP'))){//已付款
					$order_status = 200;
				}else if (in_array($order['order_status'], array('IN_CANCEL', 'CANCELLED', 'INVALID', 'TO_RETURN'))){//取消
					$is_manual_order = 1;
					$order_status = 600;
				}else if (in_array($order['order_status'], array('SHIPPED','TO_CONFIRM_RECEIVE','COMPLETED', 'RETRY_SHIP'))){  //已完成
					$order_status = 500;
				}
				//国家简码
				$country_code = empty($order['recipient_address']['country']) ? $order['country'] : $order['recipient_address']['country'];
				//整理买家收件地址，由于详细地址包含市、镇等，所以需去掉
				$address = trim($order['recipient_address']['full_address']);
				//州
				if(!empty($order['recipient_address']['state'])){
					$address = rtrim(rtrim($address, $order['recipient_address']['state']));
				}
				//邮编
				if(!empty($order['recipient_address']['zipcode'])){
					$address = rtrim(rtrim($address, $order['recipient_address']['zipcode'].','));
				}
				//城市
				if(!empty($order['recipient_address']['city'])){
					$address = rtrim(rtrim($address, $order['recipient_address']['city']));
				}
				//国家
				$countryInfo = CountryHelper::getCountryName($country_code);
				if (count($countryInfo) === 0) {
				    \Yii::info("shopeeOrder  Error country:".$country_code, "file");
				    $errors[$order_id] = 'country:'.$country_code.' not found';
				    continue;
				}
				$consigneeCountryEn = $countryInfo["country_en"];
				$address = rtrim(rtrim($address, strtoupper($consigneeCountryEn)));
				//物流信息
				$orderShipped = [];
				if (!empty($order['tracking_no'])){
					$tmp = [
						'order_source_order_id' => $order_id,
						'order_source' => 'shopee',
						'selleruserid' => $user->shop_id,
						'tracking_number' => $order['tracking_no'],
						'shipping_method_name' => $order['shipping_carrier'],
						'addtype' => '平台API',
					];
					//赋缺省值
					$orderShipped[]= array_merge(OrderHelper::$order_shipped_demo, $tmp);
				}
				
				$order_arr=array( //主订单数组
					'order_status' => $order_status,
					'order_source_status' => $order['order_status'],
					'is_manual_order' => $is_manual_order,
					'shipping_status' => !empty($order['tracking_no']) && in_array($order['order_status'], ['SHIPPED', 'TO_CONFIRM_RECEIVE', 'COMPLETED', 'RETRY_SHIP']) ? 1 : 0,
					'order_source' => 'shopee',
					//'issuestatus' => '',
					//'order_type' => '',
					'order_source_order_id' => $order_id,
					'selleruserid' => $user->shop_id,
				    'order_source_site_id' => $order['country'],
					//'source_buyer_user_id' => '',
					'order_source_create_time' => self::transLaStrTimetoTimestamp($order['create_time']),
					'subtotal' => $subtotal,
					'shipping_cost' => empty($order['estimated_shipping_fee']) ? 0 : $order['estimated_shipping_fee'],
				    'discount_amount'=>$totalDiscount,
					'grand_total' => $order['total_amount'],
					'currency' => $order['currency'],
					'consignee' => $order['recipient_address']['name'],
					'consignee_postal_code' => empty($order['recipient_address']['zipcode']) ? '' : $order['recipient_address']['zipcode'],
					'consignee_city' => empty($order['recipient_address']['city']) ? '' : $order['recipient_address']['city'],
					//'consignee_phone' => isset($order['recipient_address']['phone']) ? $order['recipient_address']['phone'] : '',
					'consignee_mobile' => empty($order['recipient_address']['phone']) ? '' : $order['recipient_address']['phone'],
					//'consignee_email' => '',
					'consignee_country' => $consigneeCountryEn,
					'consignee_country_code' => $country_code,
					'consignee_province' => empty($order['recipient_address']['state']) ? '' : $order['recipient_address']['state'],
					'consignee_address_line1' => $address,
					//'consignee_address_line2' => '',
					'paid_time' => empty($order['pay_time'])?0:self::transLaStrTimetoTimestamp($order['pay_time']),
					'payment_type' => $order['payment_method'],
					//'delivery_time' => '',
					'user_message' => $order['message_to_seller'],
					'order_source_shipping_method' => $order['shipping_carrier'],
					'orderShipped' => $orderShipped,
					'items' => $orderitem_arr,
				);
				
				$myorder_arr[$user->puid] = array_merge(OrderHelper::$order_demo, $order_arr);
				
				//插入订单信息到user db
				\Yii::info('importPlatformOrder: order_id='.$order_id.' '.json_encode($myorder_arr), "file");
				if($is_echo){
					echo 'importPlatformOrder: order_id='.$order_id.' '.json_encode($myorder_arr).PHP_EOL;
				}
				
				$result =  OrderHelper::importPlatformOrder($myorder_arr);
				if($is_echo){
					echo PHP_EOL.'-----'.PHP_EOL.PHP_EOL;
				}
				if(!$result['success']){
					$success_orderids[] = $order_id;
				}
				else{
					$errors[$order_id] = $result['message'];
					if($is_echo){
						echo 'faile: '.$result['message'].PHP_EOL;
					}
				}
			}
			catch(\Exception $ex){
				$errors[$order_id] = $ex->getMessage();
				if($is_echo){
					echo 'faile: '.$ex->getMessage().PHP_EOL;
					echo print_r($order, true);
				}
			}
		}
		
		return self::getResult(true, '', ['errors' => $errors, 'success_orderids' => $success_orderids]);
		
	}
	
	public static function getResult($success, $msg = '', $result = ''){
		return ['success' => $success, 'msg' => $msg, 'result' => $result];
	}
	
	/**
	 * shopee返回的是UTC时间戳
	 */
	static function transLaStrTimetoTimestamp($time_str){
		return  $time_str;
	}
	
	
}


