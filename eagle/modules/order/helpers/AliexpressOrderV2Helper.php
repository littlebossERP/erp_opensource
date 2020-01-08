<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\order\helpers;

use \Yii;
use eagle\models\SaasAliexpressAutosyncV2;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\models\QueueAliexpressGetorderV2;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TranslateHelper;
use common\api\aliexpressinterfacev2\AliexpressInterface_Api_Qimen;
use eagle\modules\manual_sync\models\Queue;
use eagle\models\QueueAliexpressGetorder4V2;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_Qimen;
use eagle\models\SaasAliexpressUser;
use eagle\modules\tracking\helpers\TrackingHelper;
use yii\helpers\Url;
use eagle\modules\delivery\helpers\DeliveryHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\order\models\OdOrderItem;
use Qiniu\json_decode;
use common\api\aliexpressinterfacev2\AliexpressInterface_Helper_V2;

class AliexpressOrderV2Helper{
	
	/**
	 +----------------------------------------------------------
	 * 前端手动同步订单
	 +----------------------------------------------------------
	 * @param 	$queue
	 +----------------------------------------------------------
	 * @return	
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq    2017/12/29		初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderListManual( $queue ){
	
		$sellerloginid= $queue->site_id;
		$connection=Yii::$app->db_queue;
		$return= array();
		// 检查授权是否过期或者是否授权,返回true，false
	
		if (!AliexpressInterface_Helper_Qimen::checkToken ( $sellerloginid )) {
			$queue->data(['error'=>'未授权']);
			return false;
		}
		//获取
		//echo date("Y-m-d");
		//同步状态值更改
		$update_arr = array();
		$update_arr['status'] = 1;
		$update_arr['last_time'] = time();
	
		$where_arr= array();
		$where_arr['sellerloginid']= $sellerloginid;
		$where_arr['type']= 'time';
	
		$res= SaasAliexpressAutosyncV2::updateAll( $update_arr,$where_arr );
	
		$api = new AliexpressInterface_Api_Qimen ();
	
		//获取最新的同步信息
		$obj= SaasAliexpressAutosyncV2::findOne( $where_arr );
		$time = time();
		if($obj->end_time == 0) {
			//初始同步
			$start_time = $obj->binding_time;
			$end_time = $time;
		}else {
			//增量同步
			$start_time = $obj->end_time - 86400 * 10;
			$end_time = $time;
		}
		$format_start_time = self::getLaFormatTime ("m/d/Y H:i:s", $start_time );
		$format_end_time = self::getLaFormatTime ("m/d/Y H:i:s", $end_time );
		//echo $format_start_time.'--'.$format_end_time;
	
		// 是否全部同步完成
		$success = true;
		//先切换数据库,省的循环切你N次了
		$uid = $obj->uid;
 
	
		//获取最近读不到图片的item
		//$no_photo_item = self::GetNoPhotoItem($start_time);
		//分页设置
		$page = 1;
		$pageSize = 50;
		$order_ids = [];
		//$api_types = ['create', 'modified'];
		$api_types = ['create_v2', 'modified_v2'];
		
		foreach($api_types as $api_type){
			$page = 1;
			do {
				$api_time= time();//接口调用时间
				// 接口传入参数
				if(strpos($api_type, '_v2') !== false){
					$param = ['id' => $sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, rtrim($api_type, '_v2').'_date_start' => $format_start_time, rtrim($api_type, '_v2').'_date_end' => $format_end_time, 'buyer_login_id' => 'new'])];
				}
				else{
					$param = ['id' => $sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, $api_type.'_date_start' => $format_start_time, $api_type.'_date_end' => $format_end_time])];
				}
				//调用接口获取订单列表
				$result = $api->findOrderListQuery($param);
				// 判断是否有订单
				if (!isset ($result['total_item'])) {
					$success = false;
					\Yii::info("getOrderListManual--findOrderListQuery--$api_type--err--".json_encode($result), "file");
					break;
				}
				else if($result['total_item'] > 0 && empty($result['order_list'])) {
					$success = false;
					\Yii::info("getOrderListManual--findOrderListQuery--$api_type--err--".json_encode($result), "file");
					break;
				}
				else if(empty($result['order_list'])) {
					\Yii::info("getOrderListManual--findOrderListQuery--$api_type--err--".json_encode($result), "file");
					break;
				}
				
				foreach($result['order_list'] as $order){
					$order_info = array();
					$orderid = number_format($order['order_id'], 0, '', '');
					//排除重复的
					if(in_array($orderid, $order_ids)){
						continue;
					}
					//新版接口返回的是美国时间，需转换
					if(strpos($api_type, '_v2') !== false){
						$order['gmt_create'] = date("Y-m-d H:i:s", AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create'], 'US', 'cn1510671045'));
					}
					$order_ids[] = $orderid;
					$order_info = [
						'biz_type' => $order['biz_type'],
						'gmt_create' => $order['gmt_create'],
						'memo' => '',
						'order_id' => $order['order_id'],
						'order_status' => $order['order_status'],
						'product_list' => [],
						'day' => isset($order['left_send_good_day']) ? $order['left_send_good_day'] : 0,
						'hour' => isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] : 0,
						'min' => isset($order['left_send_good_min']) ? $order['left_send_good_min'] : 0,
						'api_time' => $api_time,
					];
					
					$logisticsServiceName_arr= array();     //买家物流信息
					$memo_arr= array();                     //买家备注
					$sendGoodsOperator_arr = array();       //发货类型
					if (isset($order['product_list'])) {
						foreach ($order['product_list'] as $pl) {
							$productid = strval($pl['product_id']);
							$child_id = number_format($pl['child_id'], 0, '', '');
					
							$order_info['product_list'][] = [
								'child_id' => $child_id,
								'money_back3x' => $pl['money_back3x'],
								'product_count' => $pl['product_count'],
								'product_id' => $productid,
								'product_img_url' => empty($pl['product_img_url'])?"":$pl['product_img_url'],//dzt20191018 最近有些产品没有图片，和URL
								'product_name' => $pl['product_name'],
								'product_snap_url' => empty($pl['product_snap_url'])?"":$pl['product_snap_url'],//dzt20191018
								'product_unit' => $pl['product_unit'],
								'product_unit_price' => empty($pl['product_unit_price']['cent_factor']) ? $pl['product_unit_price']['amount'] : $pl['product_unit_price']['cent'] / $pl['product_unit_price']['cent_factor'],
								'product_unit_price_cur' => $pl['product_unit_price']['currency_code'],
								'sku_code' => empty($pl['sku_code']) ? '' : $pl['sku_code'],
								'son_order_status' => $pl['son_order_status'],
							];
					
							//客选物流
							if (isset($pl['logistics_service_name'])) {
								$logisticsServiceName = $pl['logistics_service_name'];
								$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
							}
							//买家备注
							if( isset($pl['memo']) ){
								$pmemo= str_replace("'","",$pl['memo']);
								if( $pmemo=='' ){
									$pmemo= '无';
								}
								$memo_arr[]= $pmemo;
								//$logisticsServiceName_arr["user_message"][$productid] = $memo;
							}
							//发货类型
							if (isset($pl['send_goods_operator'])) {
								$sendGoodsOperator = $pl['send_goods_operator'];
								$sendGoodsOperator_arr[$productid] = $sendGoodsOperator;
							}
							/*
							//判断是否属于无图片的item，是则更新
							if(array_key_exists($sellerloginid.'_'.$productid.'_'.$child_id, $no_photo_item)){
								$item = OdOrderItem::findOne(['order_item_id' => $no_photo_item[$sellerloginid.'_'.$productid.'_'.$child_id]['order_item_id'], 'order_source_itemid' => $productid, 'order_source_transactionid' => $child_id, 'order_source_order_id' => $orderid]);
								if(!empty($pl['product_img_url']) && !empty($item)){
									$item->photo_primary = $pl['product_img_url'];
									$item->save(false);
								}
							}
							*/
						}
					}
					//客选物流
					if(!empty($logisticsServiceName_arr)){
						$order_info['logisticsServiceName_arr'] = $logisticsServiceName_arr;
					}
					//买家备注
					if(!empty($memo_arr)){
						$order_info['memo_arr'] = $memo_arr;
					}
					//发货类型
					if(!empty($sendGoodsOperator_arr)){
						$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr;
					}
		
					//队列4中是否存在这个订单
					$rs_order4 = $connection->createCommand("SELECT id FROM queue_aliexpress_getorder4_v2 WHERE orderid='{$orderid}' ORDER BY id DESC  ")->query()->read();
					if ( $rs_order4 === false ) {
						//没有数据,写入队列4
						$QAG_four = new QueueAliexpressGetorder4V2();
						$QAG_four->uid = $uid;
						$QAG_four->sellerloginid = $sellerloginid;
						$QAG_four->aliexpress_uid = $obj->aliexpress_uid;
						$QAG_four->order_status = $order['order_status'];
						$QAG_four->orderid = $orderid;
						$QAG_four->order_info = json_encode($order_info);
						$QAG_four->gmtcreate = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create']);
						$QAG_four->create_time = time();
						$QAG_four->update_time = time();
						$boolfour = $QAG_four->save();
					
						$newid = $QAG_four->primaryKey;
					} else {
						//有数据,不处理
						$boolfour= true;
						$newid= $rs_order4['id'];
					}
					
					//推送表中是否存在未锁定,未处理的数据,存在就锁定了,省的后面重复处理
					//管他有没有这个订单号的,统一update掉算了,反正没有的话,也就update false而已
					$auto_order_eof = $connection->createCommand("UPDATE queue_aliexpress_auto_order SET is_lock=1  WHERE order_id='{$orderid}' ")->execute();
					//同步数据到用户表的小老板订单中
					if ($boolfour === true) {
						$getorder4_obj= QueueAliexpressGetorder4V2::findOne( $newid );
						$param = ['id' => $sellerloginid, 'param1' => json_encode(['order_id' => $orderid])];
						$res = $api->findOrderById($param );
						if (!empty($res['error_response']) || empty ($res)) {
							$success = false;
							\Yii::info("getOrderListManual--findOrderById--$api_type--err--".json_encode($res), "file");
							break;
						}
						$res['id'] = strval($getorder4_obj->orderid);
						$res["sellerOperatorLoginId"]= $getorder4_obj->sellerloginid;
						$r = AliexpressInterface_Helper_V2::saveAliexpressOrder ( $getorder4_obj, $res );
						if( $r['success']==0 ){
							//同步成功
							$update_t = date("Y-m-d H:i:s");
							$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=3,update_time='{$update_t}'  WHERE order_id='{$orderid}' ")->execute();
							//error_log("同步成功".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
							if( $queue != '' ){
								$queue->addProgress();
							}
							//echo 1;
						}else{
							//同步失败
							if( isset($r['message']) && isset( $r['success'] ) ){
								$error= $r['success'].'--'.$r['message'];
							}else{
								$error= '订单更新失败';
							}
							$update_t= date("Y-m-d H:i:s");
					
							$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2,update_time='{$update_t}',error_message='{$error}'  WHERE order_id='{$orderid}' ")->execute();
							//error_log("同步失败".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
							$success = false;
							//echo 2;
						}
						
						//设置客选物流
						if (!empty($logisticsServiceName_arr)) {
							list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
							/*if( $order_status === false ){
								echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
							}*/
						}
							
						//判断是否存在剩余发货时间的3个属性
						$leftSendGoodDay = isset($order['left_send_good_day']) ? $order['left_send_good_day'] * 86400 : 0;
						$leftSendGoodHour = isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] * 3600 : 0;
						$leftSendGoodMin = isset($order['left_send_good_min']) ? $order['left_send_good_min'] * 60 : 0;
							
						//如果都是0的话,不处理最后发货时间,有一个不是0,才去处理
						if( $leftSendGoodDay > 0 || $leftSendGoodHour > 0 || $leftSendGoodMin > 0 ){
							//在接口调用时间上,加上秒数就是最后发货时间啦
							$fulfill_deadline = ceil($leftSendGoodDay + $leftSendGoodHour + $leftSendGoodMin + $api_time);
							//更新掉字段
							Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_source_order_id='{$orderid}'")->execute();
						}
							
						//设置买家备注
						$memo= '';
						if( !empty( $memo_arr ) ){
							$memo_eof= false;
							foreach( $memo_arr as $memo_vss ){
								if( $memo_vss!='无' ){
									$memo_eof= true;
									break;
								}
							}
							if( $memo_eof===true ){
								foreach( $memo_arr as $key=>$memo_vss ){
									$count= $key+1;
									$memo.= "商品{$count}:{$memo_vss};";
								}
							}
						}
						if( $memo != '' ) {
							//需要获取自增id
							$ro = OdOrder::findOne(['order_source_order_id' => $orderid]);
							if (!empty($ro)) {
								$sysTagRt = OrderTagHelper::setOrderSysTag($ro->order_id, 'pay_memo');
								if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400') {
									//echo '\n' . $ro->order_id . ' insert pay_memo failure :' . $sysTagRt['message'];
								}
							}
							$rof= Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET user_message='{$memo}' WHERE order_source_order_id='{$orderid}'")->execute();
						}
					}
					
		
				}
				$page ++;
				$p = ceil($result['total_item']/50);
			} while ( $page <= $p );
			
			if(!$success){
				break;
			}
		}
		
		//////////////////////////////////////////////////////////////////////////////////////////////////////////
	
		//完了,解锁
		$update_arr = array();
		$update_arr['status'] = 0;
		$update_arr['last_time'] = time();
		if($success){
			$update_arr['end_time'] = $end_time;
			$update_arr['next_time']= time()+3600;
		}
	
		$where_arr= array();
		$where_arr['sellerloginid']= $sellerloginid;
		$where_arr['type']= 'time';
	
		SaasAliexpressAutosyncV2::updateAll( $update_arr,$where_arr );
		return true;
	
	}
	//end function
	
	/**
	 +----------------------------------------------------------
	 * 后台手动同步订单
	 +----------------------------------------------------------
	 * @param 	$queue
	 +----------------------------------------------------------
	 * @return
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq    2017/12/29		初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderListManualByUid( $uid, $sellid='', $mt = 30 ){
		if( $sellid != '' ){
			$rsle = SaasAliexpressUser::find()->where(['sellerloginid' => $sellid, 'is_active' => 1, 'version' => 'v2'])->asArray()->all();
		}
		else{
			$rsle = SaasAliexpressUser::find()->where(['uid' => $uid, 'is_active' => 1, 'version' => 'v2'])->asArray()->all();
		}
	
		if( !empty( $rsle ) ){
			foreach( $rsle as $vsle ){
				$sellerloginid = $vsle['sellerloginid'];
				$connection = Yii::$app->db_queue;
				$return= array();
				
				// 检查授权是否过期或者是否授权,返回true，false
				if (!AliexpressInterface_Helper_Qimen::checkToken ( $sellerloginid )) {
					error_log("未授权".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
					continue;
				}
				
				echo date("Y-m-d H:i:s").PHP_EOL;
				$where_arr = array();
				$where_arr['sellerloginid'] = $sellerloginid;
				$where_arr['type']= 'time';
				//判断是否在同步中
				$obj = SaasAliexpressAutosyncV2::findOne( $where_arr );
				if($obj->status == 1){
					echo '正在同步中...'.PHP_EOL;
					continue;
				}
				//同步状态值更改
				$update_arr = array();
				$update_arr['status'] = 1;
				$update_arr['last_time'] = time();
				$res = SaasAliexpressAutosyncV2::updateAll($update_arr, $where_arr);
				//获取token
				$api = new AliexpressInterface_Api_Qimen ();
	
				//获取最新的同步信息
				$obj = SaasAliexpressAutosyncV2::findOne( $where_arr );
				$time = time();
				if($obj->end_time == 0) {
					//初始同步
					$start_time = $obj->binding_time;
					$end_time = $time;
				}else {
					//增量同步
					$start_time = $obj->end_time - 86400 * $mt;
					$end_time = $time;
				}
				$format_start_time = self::getLaFormatTime ("m/d/Y H:i:s", $start_time );
				$format_end_time = self::getLaFormatTime ("m/d/Y H:i:s", $end_time );
				echo $format_start_time.'--'.$format_end_time.PHP_EOL;
				
				//是否全部同步完成
				$success = true;
				 
				$uid = $obj->uid;
 
	
				//////////////////////////////////////////////////////////////////////////////////////////////////////////
				//分页设置
				$page = 1;
				$pageSize = 50;
				$orders = [];
				$total_item = 0;
				echo "uid: $uid, sellerloginid: $sellerloginid".PHP_EOL;
				do {
					$api_time = time();//接口调用时间
					// 接口传入参数
					$param = ['id' => $sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, 'create_date_start' => $format_start_time, 'create_date_end' => $format_end_time])];
					//调用接口获取订单列表
					$result = $api->findOrderListQuery($param);
					echo "get-result: findOrderListQuery, create_date_start".PHP_EOL;
					//判断是否有订单
					if (!isset ($result['total_item'])) {
						$success = false;
						echo "findOrderListQuery--create--err--".PHP_EOL;
						echo json_encode($result).PHP_EOL;
						break;
					}
					else if($result['total_item'] > 0 && empty($result['order_list'])) {
						$success = false;
						echo "findOrderListQuery--create--err--".PHP_EOL;
						echo json_encode($result).PHP_EOL;
						break;
					}
					else if(empty($result['order_list'])) {
						break;
					}
					echo "total_item: ".$result['total_item'].", order_list: ".count($result['order_list']).PHP_EOL;
					
					print_r ($result ['order_list']);
					foreach($result['order_list'] as $order){
						$order_info = array();
						$orderid = number_format($order['order_id'], 0, '', '');
						echo "order_id: $orderid".PHP_EOL;
						$order_info = [
							'biz_type' => $order['biz_type'],
							'gmt_create' => $order['gmt_create'],
							'memo' => '',
							'order_id' => $order['order_id'],
							'order_status' => $order['order_status'],
							'product_list' => [],
							'day' => isset($order['left_send_good_day']) ? $order['left_send_good_day'] : 0,
							'hour' => isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] : 0,
							'min' => isset($order['left_send_good_min']) ? $order['left_send_good_min'] : 0,
							'api_time' => $api_time,
						];
	
						$logisticsServiceName_arr= array();     //买家物流信息
						$memo_arr= array();                     //买家备注
						$sendGoodsOperator_arr = array();       //发货类型
						if (isset($order['product_list'])) {
							foreach ($order['product_list'] as $pl) {
								$productid = strval($pl['product_id']);
								
								$order_info['product_list'][] = [
									'child_id' => number_format($pl['child_id'], 0, '', ''),
									'money_back3x' => $pl['money_back3x'],
									'product_count' => $pl['product_count'],
									'product_id' => $productid,
									'product_img_url' => empty($pl['product_img_url'])?"":$pl['product_img_url'],//dzt20191018
									'product_name' => $pl['product_name'],
									'product_snap_url' => empty($pl['product_snap_url'])?"":$pl['product_snap_url'],//dzt20191018
									'product_unit' => $pl['product_unit'],
									'product_unit_price' => empty($pl['product_unit_price']['cent_factor']) ? $pl['product_unit_price']['amount'] : $pl['product_unit_price']['cent'] / $pl['product_unit_price']['cent_factor'],
									'product_unit_price_cur' => $pl['product_unit_price']['currency_code'],
									'sku_code' => empty($pl['sku_code']) ? '' : $pl['sku_code'],
									'son_order_status' => $pl['son_order_status'],
								];
								
								//客选物流
								if (isset($pl['logistics_service_name'])) {
									$logisticsServiceName = $pl['logistics_service_name'];
									$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
								}
								//买家备注
								if( isset($pl['memo']) ){
									$pmemo= str_replace("'","",$pl['memo']);
									if( $pmemo=='' ){
										$pmemo= '无';
									}
									$memo_arr[]= $pmemo;
									//$logisticsServiceName_arr["user_message"][$productid] = $memo;
								}
								//发货类型
								if (isset($pl['send_goods_operator'])) {
									$sendGoodsOperator = $pl['send_goods_operator'];
									$sendGoodsOperator_arr[$productid] = $sendGoodsOperator;
								}
							}
						}
						//客选物流
	    				if(!empty($logisticsServiceName_arr)){
	    					$order_info['logisticsServiceName_arr'] = $logisticsServiceName_arr;
	    				}
	    				//买家备注
	    				if(!empty($memo_arr)){
	    					$order_info['memo_arr'] = $memo_arr;
	    				}
	    				//发货类型
	    				if(!empty($sendGoodsOperator_arr)){
	    					$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr;
	    				}
						
						//队列4中是否存在这个订单
						$rs_order4 = $connection->createCommand("SELECT id FROM queue_aliexpress_getorder4_v2 WHERE orderid='{$orderid}' ORDER BY id DESC  ")->query()->read();
						if ( $rs_order4 === false ) {
							//没有数据,写入队列4
							$QAG_four = new QueueAliexpressGetorder4V2();
							$QAG_four->uid = $uid;
							$QAG_four->sellerloginid = $sellerloginid;
							$QAG_four->aliexpress_uid = $obj->aliexpress_uid;
							$QAG_four->order_status = $order['order_status'];
							$QAG_four->orderid = $orderid;
							$QAG_four->order_info = json_encode($order_info);
							$QAG_four->gmtcreate = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create']);
							$QAG_four->create_time = time();
							$QAG_four->update_time = time();
							$boolfour = $QAG_four->save();
						
							$newid = $QAG_four->primaryKey;
						} else {
							//有数据,不处理
							$boolfour= true;
							$newid = $rs_order4['id'];
						}
						
						//推送表中是否存在未锁定,未处理的数据,存在就锁定了,省的后面重复处理
						//管他有没有这个订单号的,统一update掉算了,反正没有的话,也就update false而已
						$auto_order_eof = $connection->createCommand("UPDATE queue_aliexpress_auto_order SET is_lock=1  WHERE order_id='{$orderid}' ")->execute();
						//同步数据到用户表的小老板订单中
						if ($boolfour === true) {
							$getorder4_obj = QueueAliexpressGetorder4V2::findOne( $newid );
							$param = ['id' => $sellerloginid, 'param1' => json_encode(['order_id' => $orderid])];
							$res = $api->findOrderById($param );
							if (!empty($res['error_response']) || empty ($res)) {
								$success = false;
								echo json_encode($res).PHP_EOL;
								break;
							}
							$res['id'] = strval($getorder4_obj->orderid);
							$res["sellerOperatorLoginId"] = $getorder4_obj->sellerloginid;
							echo '店铺ID---',$res["sellerOperatorLoginId"].', order_id: '.$orderid.PHP_EOL;
							print_r ( $getorder4_obj );
							print_r ($res);
							$r = AliexpressInterface_Helper_V2::saveAliexpressOrder ( $getorder4_obj, $res );
							if( $r['success']==0 ){
								//同步成功
								$update_t = date("Y-m-d H:i:s");
								$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=3,update_time='{$update_t}'  WHERE order_id='{$orderid}' ")->execute();
								//error_log("同步成功".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
						
								echo 1;
							}else{
								//同步失败
								if( isset($r['message']) && isset( $r['success'] ) ){
									$error = $r['success'].'--'.$r['message'];
								}else{
									$error = '订单更新失败';
								}
								$update_t = date("Y-m-d H:i:s");
						
								$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2,update_time='{$update_t}',error_message='{$error}'  WHERE order_id='{$orderid}' ")->execute();
								//error_log("同步失败".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
								$success  = false;
								echo 2;
							}
							
							//设置客选物流
							if (!empty($logisticsServiceName_arr)) {
								list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
								if( $order_status === false ){
									echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
								}
							}
							
							//判断是否存在剩余发货时间的3个属性
							$leftSendGoodDay = isset($order['left_send_good_day']) ? $order['left_send_good_day'] * 86400 : 0;
							$leftSendGoodHour = isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] * 3600 : 0;
							$leftSendGoodMin = isset($order['left_send_good_min']) ? $order['left_send_good_min'] * 60 : 0;
							
							//如果都是0的话,不处理最后发货时间,有一个不是0,才去处理
							if( $leftSendGoodDay > 0 || $leftSendGoodHour > 0 || $leftSendGoodMin > 0 ){
								//在接口调用时间上,加上秒数就是最后发货时间啦
								$fulfill_deadline = ceil($leftSendGoodDay + $leftSendGoodHour + $leftSendGoodMin + $api_time);
								//更新掉字段
								Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_source_order_id='{$orderid}'")->execute();
							}
							
							//设置买家备注
							$memo= '';
							if( !empty( $memo_arr ) ){
								$memo_eof= false;
								foreach( $memo_arr as $memo_vss ){
									if( $memo_vss!='无' ){
										$memo_eof= true;
										break;
									}
								}
								if( $memo_eof===true ){
									foreach( $memo_arr as $key=>$memo_vss ){
										$count= $key+1;
										$memo.= "商品{$count}:{$memo_vss};";
									}
								}
							}
							if( $memo != '' ) {
								//需要获取自增id
								$ro = OdOrder::findOne(['order_source_order_id' => $orderid]);
								if (!empty($ro)) {
									$sysTagRt = OrderTagHelper::setOrderSysTag($ro->order_id, 'pay_memo');
									if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400') {
										echo '\n' . $ro->order_id . ' insert pay_memo failure :' . $sysTagRt['message'];
									}
								}
								$rof= Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET user_message='{$memo}' WHERE order_source_order_id='{$orderid}'")->execute();
							}
							
							$total_item++;
						}
					}
					$page ++;
					$p = ceil($result['total_item']/50);
				} while ( $page <= $p );
				
				//保存本次同步订单数
				SaasAliexpressAutosyncV2::updateAll( ['order_item' => $total_item], ['id' => $obj->id] );
	
				//完了,解锁
				$update_arr = array();
				$update_arr['status'] = 0;
				$update_arr['last_time'] = $time;
				if($success){
					$update_arr['end_time'] = $end_time;
					$update_arr['next_time']= $time + 3600;
				}
				/*$where_arr= array();
				$where_arr['uid']= $uid;
				$where_arr['type']= 'time';*/
				SaasAliexpressAutosyncV2::updateAll($update_arr, $where_arr );
			}
		}
		return true;
	
	}
	//end function
	
	static function getLaFormatTime($format , $timestamp){
		$dt = new \DateTime();
		$dt->setTimestamp($timestamp);
		$dt->setTimezone(new \DateTimeZone('America/Los_Angeles'));
		return $dt->format($format);
	}
	
	static function transLaStrTimetoTimestamp($gmttime_str){//速卖通返回时间字符串是20150705120727000-0700 格式的(utc -7时区)。
		$time_str_arr = explode('-', $gmttime_str);
		$time_str_arr[0] = substr($time_str_arr[0],0,14);
		// 初始化时间字符串格式如：20150705120727-0700 ，DateTime 会自动根据该字符串将 该dateTime Object 的时区set 成字符串定义的 ，如 -0700 就是utc -7时区
		$serverLocalTime = new \DateTime(implode('-', $time_str_arr));
		// 		$serverLocalTime->setTimezone(new \DateTimeZone('America/Los_Angeles'));
		return  $serverLocalTime->getTimestamp();
	}
	
	private static function _setOrderDay($orders,$one){
		if(isset($orders[$one['order_id']])){
			$one['day'] = $orders[$one['order_id']]['day'];
			$one['hour'] = $orders[$one['order_id']]['hour'];
			$one['min'] = $orders[$one['order_id']]['min'];
		} else {
			$one['day'] = 0;
			$one['hour'] = 0;
			$one['min'] = 0;
		}
		return $one;
	}
	
	/*
	 * 获取订单不正常的item信息
	+---------------------------------------------------------------------------------------------
	* @author	lrq		2017/10/23		初始化
	+---------------------------------------------------------------------------------------------
	**/
	public static function GetNoPhotoItem($start_time){
		$data = array();
		try{
			$connection = Yii::$app->subdb;
			$items = $connection->createCommand("SELECT item.order_item_id, item.order_source_itemid,od.selleruserid,order_source_transactionid FROM `od_order_item_v2` item left join od_order_v2 od on od.order_id = item.order_id where od.order_source_create_time>$start_time and od.order_source='aliexpress' and photo_primary like '%no_photo.gif%'")->queryall();
			if(!empty($items)){
				foreach($items as $item){
					$data[$item['selleruserid'].'_'.$item['order_source_itemid'].'_'.$item['order_source_transactionid']] = $item;
				}
			}
		}
		catch(\Exception $ex){
		}
	
		return $data;
	}
	
	/**
	 +----------------------------------------------------------
	 * 测试
	 +----------------------------------------------------------
	 * @return
	 +----------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq    2017/12/29		初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderListManualByUidTest( $uid, $sellid='', $mt = 10 ){
	
		$sellerloginid= $sellid;
		$connection=Yii::$app->db_queue;
		$return= array();
		// 检查授权是否过期或者是否授权,返回true，false
	
		if (!AliexpressInterface_Helper_Qimen::checkToken ( $sellerloginid )) {
			echo '未授权'.PHP_EOL;
			return false;
		}
		//获取
		//echo date("Y-m-d");
		//同步状态值更改
		$update_arr = array();
		$update_arr['status'] = 1;
		$update_arr['last_time'] = time();
	
		$where_arr= array();
		$where_arr['sellerloginid']= $sellerloginid;
		$where_arr['type']= 'time';
	
		$res= SaasAliexpressAutosyncV2::updateAll( $update_arr,$where_arr );
	
		$api = new AliexpressInterface_Api_Qimen ();
	
		//获取最新的同步信息
		$obj= SaasAliexpressAutosyncV2::findOne( $where_arr );
		$time = time();
		if($obj->end_time == 0) {
			//初始同步
			$start_time = $obj->binding_time;
			$end_time = $time;
		}else {
			//增量同步
			$start_time = $obj->end_time - 86400 * $mt;
			$end_time = $time;
		}
		$format_start_time = self::getLaFormatTime ("m/d/Y H:i:s", $start_time );
		$format_end_time = self::getLaFormatTime ("m/d/Y H:i:s", $end_time );
		//echo $format_start_time.'--'.$format_end_time;
	
		// 是否全部同步完成
		$success = true;
		 
		$uid = $obj->uid;
 
	
		//获取最近读不到图片的item
		$no_photo_item = self::GetNoPhotoItem($start_time);
		//分页设置
		$page = 1;
		$pageSize = 50;
		$order_ids = [];
		//$api_types = ['create', 'modified'];
		$api_types = ['create_v2', 'modified_v2'];
		foreach($api_types as $api_type){
			$page = 1;
			do {
				$api_time= time();//接口调用时间
				// 接口传入参数
				if(strpos($api_type, '_v2') !== false){
					$param = ['id' => $sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, rtrim($api_type, '_v2').'_date_start' => $format_start_time, rtrim($api_type, '_v2').'_date_end' => $format_end_time, 'buyer_login_id' => 'new'])];
				}
				else{
					$param = ['id' => $sellerloginid, 'param1' => json_encode(['page' => $page, 'page_size' => $pageSize, $api_type.'_date_start' => $format_start_time, $api_type.'_date_end' => $format_end_time])];
				}
				//调用接口获取订单列表
				$result = $api->findOrderListQuery($param);
				// 判断是否有订单
				if (!isset ($result['total_item'])) {
					$success = false;
					echo "getOrderListManual--findOrderListQuery--$api_type--err--";
					\Yii::info("getOrderListManual--findOrderListQuery--$api_type--err--".json_encode($result), "file");
					break;
				}
				else if($result['total_item'] > 0 && empty($result['order_list'])) {
					$success = false;
					echo "getOrderListManual--findOrderListQuery--$api_type--err--";
					\Yii::info("getOrderListManual--findOrderListQuery--$api_type--err--".json_encode($result), "file");
					break;
				}
				else if(empty($result['order_list'])) {
					echo "getOrderListManual--findOrderListQuery--$api_type--err--";
					\Yii::info("getOrderListManual--findOrderListQuery--$api_type--err--".json_encode($result), "file");
					break;
				}
				echo 'order_list count '.count($result['order_list']);
				foreach($result['order_list'] as $order){
					$order_info = array();
					$orderid = number_format($order['order_id'], 0, '', '');
					//排除重复的
					if(in_array($orderid, $order_ids)){
						continue;
					}
					//新版接口返回的是美国时间，需转换
					if(strpos($api_type, '_v2') !== false){
						$order['gmt_create'] = date("Y-m-d H:i:s", AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create'], 'US', 'cn1510671045'));
					}
					$order_ids[] = $orderid;
					$order_info = [
						'biz_type' => $order['biz_type'],
						'gmt_create' => $order['gmt_create'],
						'memo' => '',
						'order_id' => $order['order_id'],
						'order_status' => $order['order_status'],
						'product_list' => [],
						'day' => isset($order['left_send_good_day']) ? $order['left_send_good_day'] : 0,
						'hour' => isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] : 0,
						'min' => isset($order['left_send_good_min']) ? $order['left_send_good_min'] : 0,
						'api_time' => $api_time,
					];
						
					$logisticsServiceName_arr= array();     //买家物流信息
					$memo_arr= array();                     //买家备注
					$sendGoodsOperator_arr = array();       //发货类型
					if (isset($order['product_list'])) {
						foreach ($order['product_list'] as $pl) {
							$productid = strval($pl['product_id']);
							$child_id = number_format($pl['child_id'], 0, '', '');
								
							$order_info['product_list'][] = [
								'child_id' => $child_id,
								'money_back3x' => $pl['money_back3x'],
								'product_count' => $pl['product_count'],
								'product_id' => $productid,
								'product_img_url' => empty($pl['product_img_url'])?"":$pl['product_img_url'],//dzt20191018
								'product_name' => $pl['product_name'],
								'product_snap_url' => empty($pl['product_snap_url'])?"":$pl['product_snap_url'],//dzt20191018
								'product_unit' => $pl['product_unit'],
								'product_unit_price' => empty($pl['product_unit_price']['cent_factor']) ? $pl['product_unit_price']['amount'] : $pl['product_unit_price']['cent'] / $pl['product_unit_price']['cent_factor'],
								'product_unit_price_cur' => $pl['product_unit_price']['currency_code'],
								'sku_code' => empty($pl['sku_code']) ? '' : $pl['sku_code'],
								'son_order_status' => $pl['son_order_status'],
							];
								
							//客选物流
							if (isset($pl['logistics_service_name'])) {
								$logisticsServiceName = $pl['logistics_service_name'];
								$logisticsServiceName_arr["shipping_service"][$productid] = $logisticsServiceName;
							}
							//买家备注
							if( isset($pl['memo']) ){
								$pmemo= str_replace("'","",$pl['memo']);
								if( $pmemo=='' ){
									$pmemo= '无';
								}
								$memo_arr[]= $pmemo;
								//$logisticsServiceName_arr["user_message"][$productid] = $memo;
							}
							//发货类型
							if (isset($pl['send_goods_operator'])) {
								$sendGoodsOperator = $pl['send_goods_operator'];
								$sendGoodsOperator_arr[$productid] = $sendGoodsOperator;
							}
							//判断是否属于无图片的item，是则更新
							if(array_key_exists($sellerloginid.'_'.$productid.'_'.$child_id, $no_photo_item)){
								$item = OdOrderItem::findOne(['order_item_id' => $no_photo_item[$sellerloginid.'_'.$productid.'_'.$child_id]['order_item_id'], 'order_source_itemid' => $productid, 'order_source_transactionid' => $child_id, 'order_source_order_id' => $orderid]);
								if(!empty($pl['product_img_url']) && !empty($item)){
									$item->photo_primary = $pl['product_img_url'];
									$item->save(false);
								}
							}
						}
					}
					//客选物流
					if(!empty($logisticsServiceName_arr)){
						$order_info['logisticsServiceName_arr'] = $logisticsServiceName_arr;
					}
					//买家备注
					if(!empty($memo_arr)){
						$order_info['memo_arr'] = $memo_arr;
					}
					//发货类型
					if(!empty($sendGoodsOperator_arr)){
						$order_info['sendGoodsOperator'] = $sendGoodsOperator_arr;
					}
					echo 'ok1';
					//队列4中是否存在这个订单
					$rs_order4 = $connection->createCommand("SELECT id FROM queue_aliexpress_getorder4_v2 WHERE orderid='{$orderid}' ORDER BY id DESC  ")->query()->read();
					if ( $rs_order4 === false ) {
						//没有数据,写入队列4
						$QAG_four = new QueueAliexpressGetorder4V2();
						$QAG_four->uid = $uid;
						$QAG_four->sellerloginid = $sellerloginid;
						$QAG_four->aliexpress_uid = $obj->aliexpress_uid;
						$QAG_four->order_status = $order['order_status'];
						$QAG_four->orderid = $orderid;
						$QAG_four->order_info = json_encode($order_info);
						$QAG_four->gmtcreate = AliexpressInterface_Helper_Qimen::transLaStrTimetoTimestamp($order['gmt_create']);
						$QAG_four->create_time = time();
						$QAG_four->update_time = time();
						$boolfour = $QAG_four->save();
							
						$newid = $QAG_four->primaryKey;
					} else {
						//有数据,不处理
						$boolfour= true;
						$newid= $rs_order4['id'];
					}
						
					//推送表中是否存在未锁定,未处理的数据,存在就锁定了,省的后面重复处理
					//管他有没有这个订单号的,统一update掉算了,反正没有的话,也就update false而已
					$auto_order_eof = $connection->createCommand("UPDATE queue_aliexpress_auto_order SET is_lock=1  WHERE order_id='{$orderid}' ")->execute();
					//同步数据到用户表的小老板订单中
					if ($boolfour === true) {
						$getorder4_obj= QueueAliexpressGetorder4V2::findOne( $newid );
						$param = ['id' => $sellerloginid, 'param1' => json_encode(['order_id' => $orderid])];
						$res = $api->findOrderById($param );
						if (!empty($res['error_response']) || empty ($res)) {
							$success = false;
							\Yii::info("getOrderListManual--findOrderById--$api_type--err--".json_encode($res), "file");
							break;
						}
						$res['id'] = strval($getorder4_obj->orderid);
						$res["sellerOperatorLoginId"]= $getorder4_obj->sellerloginid;
						$r = AliexpressInterface_Helper_V2::saveAliexpressOrder ( $getorder4_obj, $res );
						echo 'ok2';
						if( $r['success']==0 ){
							//同步成功
							$update_t = date("Y-m-d H:i:s");
							$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=3,update_time='{$update_t}'  WHERE order_id='{$orderid}' ")->execute();
							//error_log("同步成功".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
							
							//echo 1;
						}else{
							//同步失败
							if( isset($r['message']) && isset( $r['success'] ) ){
								$error= $r['success'].'--'.$r['message'];
							}else{
								$error= '订单更新失败';
							}
							$update_t= date("Y-m-d H:i:s");
								
							$connection->createCommand("UPDATE queue_aliexpress_auto_order SET `status`=2,update_time='{$update_t}',error_message='{$error}'  WHERE order_id='{$orderid}' ")->execute();
							//error_log("同步失败".date("Y-m-d H:i:s"),3,'/tmp/smt_push.log');
							$success = false;
							//echo 2;
						}
	
						//设置客选物流
						if (!empty($logisticsServiceName_arr)) {
							list( $order_status,$msg )= OrderHelper::updateOrderAddiInfoByOrderID( $orderid, $sellerloginid , 'aliexpress' , $logisticsServiceName_arr );
							/*if( $order_status === false ){
							 echo $orderid.'可选物流更新失败--'.$msg.PHP_EOL;
							}*/
						}
							
						//判断是否存在剩余发货时间的3个属性
						$leftSendGoodDay = isset($order['left_send_good_day']) ? $order['left_send_good_day'] * 86400 : 0;
						$leftSendGoodHour = isset($order['left_send_good_hour']) ? $order['left_send_good_hour'] * 3600 : 0;
						$leftSendGoodMin = isset($order['left_send_good_min']) ? $order['left_send_good_min'] * 60 : 0;
							
						//如果都是0的话,不处理最后发货时间,有一个不是0,才去处理
						if( $leftSendGoodDay > 0 || $leftSendGoodHour > 0 || $leftSendGoodMin > 0 ){
							//在接口调用时间上,加上秒数就是最后发货时间啦
							$fulfill_deadline = ceil($leftSendGoodDay + $leftSendGoodHour + $leftSendGoodMin + $api_time);
							//更新掉字段
							Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET fulfill_deadline='{$fulfill_deadline}' WHERE order_source_order_id='{$orderid}'")->execute();
						}
							
						//设置买家备注
						$memo= '';
						if( !empty( $memo_arr ) ){
							$memo_eof= false;
							foreach( $memo_arr as $memo_vss ){
								if( $memo_vss!='无' ){
									$memo_eof= true;
									break;
								}
							}
							if( $memo_eof===true ){
								foreach( $memo_arr as $key=>$memo_vss ){
									$count= $key+1;
									$memo.= "商品{$count}:{$memo_vss};";
								}
							}
						}
						if( $memo != '' ) {
							//需要获取自增id
							$ro = OdOrder::findOne(['order_source_order_id' => $orderid]);
							if (!empty($ro)) {
								$sysTagRt = OrderTagHelper::setOrderSysTag($ro->order_id, 'pay_memo');
								if (isset($sysTagRt['code']) && $sysTagRt['code'] == '400') {
									//echo '\n' . $ro->order_id . ' insert pay_memo failure :' . $sysTagRt['message'];
								}
							}
							$rof= Yii::$app->subdb->createCommand("UPDATE od_order_v2 SET user_message='{$memo}' WHERE order_source_order_id='{$orderid}'")->execute();
						}
					}
						
	
				}
				$page ++;
				$p = ceil($result['total_item']/50);
			} while ( $page <= $p );
				
			if(!$success){
				break;
			}
		}
	
		//////////////////////////////////////////////////////////////////////////////////////////////////////////
	
		//完了,解锁
		$update_arr = array();
		$update_arr['status'] = 0;
		$update_arr['last_time'] = time();
		if($success){
			$update_arr['end_time'] = $end_time;
			$update_arr['next_time']= time()+3600;
		}
	
		$where_arr= array();
		$where_arr['sellerloginid']= $sellerloginid;
		$where_arr['type']= 'time';
	
		SaasAliexpressAutosyncV2::updateAll( $update_arr,$where_arr );
		return true;
	
	}
	//end function
}