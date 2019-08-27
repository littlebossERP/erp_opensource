<?php
namespace eagle\modules\order\helpers;

use Yii;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\catalog\models\Product;
use yii\db\Query;
use yii\data\Pagination;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\order\models\OdOrder;
use eagle\models\QueueAliexpressPraise;
use eagle\models\QueueAliexpressPraiseInfo;
use eagle\modules\order\models\OdOrderItemOld;
use eagle\modules\carrier\apihelpers\CarrierApiHelper;
use eagle\modules\order\models\OrderRelation;
use eagle\modules\tracking\helpers\TrackingHelper;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: yzq
+----------------------------------------------------------------------
| Create Date: 2015-2-26
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 订单模块对其他模块的API业务逻辑
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/order
 * @subpackage  Exception
 * @author		yzq
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class OrderTrackerApiHelper {
	
	private static function setTooOldTrackingNumberStatusQ($platform, $sellerId1, $puid, $bindingTime=''){
		
		$days_old = TrackingHelper::getPlatformGetHowLongAgoOrderTrackNo($platform, $puid);
		$targetDateTime = strtotime("-".$days_old." days") ;
		$sql = "update od_order_v2,od_order_shipped_v2 set sync_to_tracker = 'Q' where
								 			 sync_to_tracker not in ('Y','U','Q') and od_order_shipped_v2.selleruserid   =:sellerid
				 and od_order_v2.order_id = od_order_shipped_v2.order_id " ;
		if(!empty($bindingTime)){
			if(!is_numeric($bindingTime))
				$bindingTime = strtotime($bindingTime);
			$targetDateTime = $bindingTime - ((int)$days_old * 86400) ;
			$sql .= " and order_source_create_time < $targetDateTime ";
		}
		$command = Yii::$app->get('subdb')->createCommand( $sql );
		$command->bindValue(':sellerid', $sellerId1, \PDO::PARAM_STR);
		$command->execute();
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据 不同的status 获取对应的物流信息
	 * 此场景用于Tracker模块调用获取，订单某块定时从ebay，smt等拉取下来的，已发货订单的过期或有效物流号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param    $status		物流单号 的状态 1为有效 , 4为过期
	 * @param    $puid			 当前 的puid
	 * @param	 $getAll        是否获取当前所有过期物流信息
	 +----------------------------------------------------------
	 * @return			   Array of Order basic information
	 *                     array([1485-78-654, RG1546873CN, 100.99, USD, 2015-1-23, China Post, 袁超汽配店],
	 *                     			[],[]
	 *                     		 )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015-6-26   			初始化
	 +----------------------------------------------------------
	 **/
	private static function _getOrderShippedByStatus($status , $puid  , $getAll=false){
		global $CACHE;
		//step 3.5, check if the seller id is unbinded
		$sellerIds = array();
		$sellerStr = '';
		
		if (empty($puid))
			$puid = Yii::$app->subdb->getCurrentPuid();
		
		//step 3.5.1: Load all binded smt and ebay account user ids
		$connection = Yii::$app->db;
		$command = $connection->createCommand(
				"select selleruserid,create_time from saas_ebay_user where uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['selleruserid'])."'";
			//try to set the Shipped record to Q if this platform got records too old
			if ($status=='1'){
				self::setTooOldTrackingNumberStatusQ('ebay', $aRow['selleruserid'], $puid, $aRow['create_time']);
			}
		}
		
		$command = $connection->createCommand("select sellerloginid,create_time from saas_aliexpress_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['sellerloginid'])   ."'";
			//try to set the Shipped record to Q if this platform got records too old
			if ($status=='1'){
				self::setTooOldTrackingNumberStatusQ('aliexpress', $aRow['sellerloginid'], $puid, $aRow['create_time']);
			}
		}
		
		$command = $connection->createCommand("select sellerloginid,create_time from saas_dhgate_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".  str_replace("'","\'",$aRow['sellerloginid'])  ."'";
			//try to set the Shipped record to Q if this platform got records too old
			if ($status=='1'){
				self::setTooOldTrackingNumberStatusQ('dhgate', $aRow['sellerloginid'], $puid, $aRow['create_time']);
			}
		}
		
		$command = $connection->createCommand("select platform_userid,create_time from saas_lazada_user where  puid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".  str_replace("'","\'",$aRow['platform_userid'])  ."'";
			//try to set the Shipped record to Q if this platform got records too old
			if ($status=='1'){
				self::setTooOldTrackingNumberStatusQ('lazada', $aRow['platform_userid'], $puid, $aRow['create_time']);
			}
		}

		$command = $connection->createCommand("select username,create_time  from saas_cdiscount_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".  str_replace("'","\'",$aRow['username'])  ."'";
			//try to set the Shipped record to Q if this platform got records too old
			if ($status=='1'){
				self::setTooOldTrackingNumberStatusQ('cdiscount', $aRow['username'], $puid, $aRow['create_time']);
			}
		}		
		$command = $connection->createCommand("select username,create_time  from saas_priceminister_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".  str_replace("'","\'",$aRow['username'])  ."'";
			//try to set the Shipped record to Q if this platform got records too old
			if ($status=='1'){
				self::setTooOldTrackingNumberStatusQ('priceminister', $aRow['username'], $puid, $aRow['create_time']);
			}
			
		}
		$command = $connection->createCommand("select store_name,create_time  from saas_bonanza_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".  str_replace("'","\'",$aRow['store_name'])  ."'";
			//try to set the Shipped record to Q if this platform got records too old
			if ($status=='1'){
				self::setTooOldTrackingNumberStatusQ('bonanza', $aRow['store_name'], $puid, $aRow['create_time']);
			}
		}
		
		$command = $connection->createCommand(
				"select store_name,create_time from saas_wish_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['store_name']) ."'";
			//try to set the Shipped record to Q if this platform got records too old
			if ($status=='1'){
				self::setTooOldTrackingNumberStatusQ('wish', $aRow['store_name'], $puid, $aRow['create_time']);
			}
		}
		
		/*
		$command = $connection->createCommand(
				"select store_name from saas_ensogo_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".str_replace("'","\'",$aRow['store_name']) ."'";
		}
		*/
		$command = $connection->createCommand("select merchant_id,create_time  from saas_amazon_user where  uid=$puid " ) ;
		$rows = $command->queryAll();
		foreach ($rows as $aRow ){
			$sellerIds[] = "'".  str_replace("'","\'",$aRow['merchant_id'])  ."'";
			//try to set the Shipped record to Q if this platform got records too old
			if ($status=='1'){
				self::setTooOldTrackingNumberStatusQ('amazon', $aRow['merchant_id'], $puid, $aRow['create_time']);
			}
		}
		
		
		
		
				
		if (!empty($sellerIds) and $status=='1'){
			$sellerStr.= " ";
			//V2 as well,sync_to_tracker = Q means it is not supposed to get in Tracker, due to Quota usage control
			$command = Yii::$app->get('subdb')->createCommand("update od_order_shipped_v2 set sync_to_tracker = 'U' where
							 			 sync_to_tracker not in ('Y','U','Q') and selleruserid not in (".implode(",", $sellerIds ).")" );
			$command->execute();
			
			//解绑了 再绑回来的，U变回未拉取状态
			$command = Yii::$app->get('subdb')->createCommand("update od_order_shipped_v2 set sync_to_tracker = '' where
							 			 selleruserid   in (".implode(",", $sellerIds ).") and  sync_to_tracker = 'U' " );
			$command->execute();
			
		}
		/*
		//Load all carrier setup into CACHE
		if (!isset($CACHE['carrier_code_name'])){
			
			$CACHE['carrier_code_name'] = CarrierApiHelper::getCarriers();
		}
		
		$allCarriers = $CACHE['carrier_code_name'];
		*/
		$allCarriers = CarrierApiHelper::getCarriers();
		//获取所有更新时间大于上次获取时间的订单，
		$selectStr="select b.id,a.order_source_order_id,default_carrier_code, b.tracking_number,shipping_method_code,a.order_id,a.order_source,
				subtotal,currency,create_time,paid_time,  shipping_method_name,
                a.selleruserid,consignee_country_code,return_no,shipping_method_code
				from od_order_v2 a,od_order_shipped_v2 b ";
		
		$sql = "$selectStr where sync_to_tracker not in ('Y','U','Q') and b.order_id = a.order_id and b.status='".$status."' limit 300";
		
		if ($getAll){ //for test scenario only
			$sql = "$selectStr where b.order_id = a.order_id and b.status='".$status."' order by paid_time desc  limit 300 ";
		}
		
		$command = Yii::$app->get('subdb')->createCommand($sql);
		$ordersArray = $command->queryAll();
		
		//期待返回的格式
		$order_returning	=	array(
				'order_id'		=>	0,
				'tracking_no'	=>	0,
				'subtotal'		=>	0,
				'currency'		=>	"",
				'create_time'	=>	"",
				'paid_time'	=>	"",
				'carrier'	=>	0,
				'selleruserid'	=>	"",
				'order_source'  =>  '',
				'consignee_country_code'  =>  '',
				'eagle2_carrier_code' =>'',
				'carrier_name'=>'',
				'return_no'=>'',
				'shipping_method_code'=>''
		);
		
		$return_orders	=	array();
		$ids_str = "(-1";
		if($ordersArray){
			foreach ($ordersArray as $anOrder){
				$ids_str .= ','.$anOrder['id'];
				//对每个order信息，提取需要返回的属性即可
				foreach ($order_returning as  $fieldName=>$kv){
					if($fieldName=="tracking_no" or $fieldName =='carrier' ){
						if ($fieldName=="tracking_no")
							$order_returning[$fieldName] = $anOrder['tracking_number'];
						if ($fieldName=="carrier")
							$order_returning[$fieldName] = $anOrder['shipping_method_name'];
						
						continue;
					}
					
					if ($fieldName=="eagle2_carrier_code"){
						$order_returning[$fieldName] = $anOrder['default_carrier_code'];
						continue;
					}
					
					if ($fieldName=="carrier_name"){
						$carrierCode = $anOrder['default_carrier_code'];
						$order_returning[$fieldName] = isset($allCarriers[$carrierCode]) ? $allCarriers[$carrierCode] :"";
						continue;
					}
					
						$order_returning[$fieldName]=$anOrder[$fieldName];
				}//end of each key defined in key attributes
		
				//Tracker wants the orderid to be platform id, not internal orderid
				$order_returning['order_id'] = $anOrder['order_source_order_id'];

				if (!empty($order_returning["tracking_no"]))
					$return_orders[] = $order_returning;
			}//end of each order found from db table
		}
		$ids_str .= ")";
		
		//update these retrieved fields as flag Y, synced to tracking modules
		if (! $getAll){
			$sql = "update od_order_shipped_v2 set sync_to_tracker = 'Y' where id in $ids_str ";
			//echo "try to update flag = Y : $sql \n ";
			$command = Yii::$app->get('subdb')->createCommand($sql );
			$command->execute();
		}
		return $return_orders;
	}//end of _getOrderShippedByStatus
	
	
	/**
	 +----------------------------------------------------------
	 * 获取已发货订单列表的过期数据
	 * 此场景用于Tracker模块调用获取，订单某块定时从ebay，smt等拉取下来的，已发货订单的过期物流号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param    $puid			 当前 的puid
	 * @param	 $getAll        是否获取当前所有过期物流信息 
	 +----------------------------------------------------------
	 * @return			   Array of Order basic information
	 *                     array([1485-78-654, RG1546873CN, 100.99, USD, 2015-1-23, China Post, 袁超汽配店],
	 *                     			[],[]
	 *                     		 )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015-6-26   			初始化
	 +----------------------------------------------------------
	 **/
	public static function getOverdueOrderShippedListModifiedAfter($puid , $getAll=false) {
		return self::_getOrderShippedByStatus('4', $puid , $getAll);
	}//end of function getOverdueOrderShippedListModifiedAfter

	/**
	 +----------------------------------------------------------
	 * 获取已发货订单列表,对更新时间是某个time以后的
	 * 此场景用于Tracker模块调用获取，订单某块定时从ebay，smt等拉取下来的，已发货订单的物流号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param    $puid			 当前 的puid
	 * @param	 $update_time    上次获取过的时间，本次从上次的时间开始重新获取一次
	 +----------------------------------------------------------
	 * @return			   Array of Order basic information 
	 *                     array([1485-78-654, RG1546873CN, 100.99, USD, 2015-1-23, China Post, 袁超汽配店],
	 *                     			[],[]
	 *                     		 )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015-2-26   			初始化
	 +----------------------------------------------------------
	**/
	public static function getShippedOrderListModifiedAfter($puid ,$update_time='' , $getAll=false) {
		return self::_getOrderShippedByStatus('1', $puid , $getAll);
	}//end of function getShippedOrderListModifiedAfter
 
	
	/**
	 +----------------------------------------------------------
	 * 获取某个订单的详细信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $track_no   物流号			
	 +----------------------------------------------------------
	 * @return			    Array of Order attributes
	 *                      e.g. array(orderid=>, order_amount=> ....
	 *                     		 )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015-2-26   			初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderDetailByTrackNo($track_no) {
		$orderDetail = array();
		//$ship		=	OdOrderShipped::model()->find(" tracking_number='".$track_no."' ");
		$command = Yii::$app->get('subdb')->createCommand("select * from od_order_shipped_v2 where tracking_number= '$track_no'" );
		$ships =  $command->queryAll();
		
		//try to seek for this in V1 data table, in case the oms conversion is not RUN successfully
		//20150729 hqw od_order_shipped 已经停用
// 		if (empty($ships)){
// 			$command = Yii::$app->get('subdb')->createCommand("select * from od_order_shipped where tracking_number= '$track_no'" );
// 			$ships =  $command->queryAll();
// 		}
		
		if (!empty($ships))
		foreach ($ships as $ship){
		}
		
		if (!empty($ship)){
			$orderDetail = self::getOrderDetail('order_id', $ship['order_id']);
			
			//改成公共调用 20150729 hqw
			/*
			$orders		= null; //OdOrder::model()->find("order_id=".$ship['order_id']);	
			if (empty($orders)){
				$command = Yii::$app->get('subdb')->createCommand("select * from od_order_v2 where order_id=  ".$ship['order_id'] );
				$orders =  $command->queryAll();
			}
			//20150729 hqw od_order 已经停用
// 			if (empty($orders)){
// 				$command = Yii::$app->get('subdb')->createCommand("select * from od_order  where order_id=  ".$ship['order_id'] );
// 				$orders =  $command->queryAll();
// 			}
			
			if (!empty($orders))
			foreach ($orders as $order){
			}
			
			if (!empty($order)){
				$itemsModel		=	OdOrderItem::findAll(["order_id"=>$ship['order_id']]);
				$orderDetail = $order;//$order->attributes;
				$orderDetail['items'] = array();
				//set photo for each item, if there is no photo in record, try to load it from product model
				if ($itemsModel != null){
					foreach ($itemsModel as $anItemModel){
						$anItem = $anItemModel->attributes;
						if (empty($anItem['photo_primary'])){
							$prodInfo = Product::findone(['sku'=>$anItem['sku']]);
							if ($prodInfo != null)
								$anItem['photo_primary'] = $prodInfo['photo_primary'];
						}
						
						$orderDetail['items'][] = $anItem;
					}//end of each item for this order
				}//end if items found

			}//end of found this order info record
			*/
		}//end of when the track no found a shipping record
		
		//order detail not found ,then check it old table 
		if (empty($orderDetail)){
			unset($ships);
			$command = Yii::$app->get('subdb')->createCommand("select * from od_order_shipped_old_v2 where tracking_number= '$track_no'" );
			$ships =  $command->queryAll();
			
			if (!empty($ships))
			foreach ($ships as $ship){
			}
			
			if (!empty($ship)){
				$orderDetail = self::getOrderDetailOld('order_id', $ship['order_id']);
			}
		}
		
		return $orderDetail;
	}//end of function getOrderDetailByTrackNo
	
	
	/**
	 +----------------------------------------------------------
	 * 获取某个订单的详细信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $platform_order_no   平台订单号Id
	 +----------------------------------------------------------
	 * @return			    Array of Order attributes
	 *                      e.g. array(orderid=>, order_amount=> ....
	 *                     		 )
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015-7-29   			初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderDetailByOrderNo($platform_order_no) {
		$orderDetail = array();
		$orderDetail = self::getOrderDetail('order_source_order_id', $platform_order_no);
		
		return $orderDetail;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取某个订单的Detail信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param    $paramKey order_source_order_id  OR order_id  Key
	 * 			 $order_id 平台订单号Id            OR 小老板平台订单id
	 +----------------------------------------------------------
	 * @return			    Array of Order items array()
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015-7-29   			初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderDetail($paramKey, $order_id) {
		$orderDetail = array();
		
		$command = Yii::$app->get('subdb')->createCommand("select * from od_order_v2 where $paramKey='".$order_id."'" );
		$orders =  $command->queryAll();
		
		if (!empty($orders))
		foreach ($orders as $order){
		}
		
		if (!empty($order)){
			$itemsModel	= OdOrderItem::findAll(["order_id"=>$order['order_id']]);
			$orderDetail = $order;//$order->attributes;
			$orderDetail['items'] = array();
			//set photo for each item, if there is no photo in record, try to load it from product model
			if ($itemsModel != null){
				foreach ($itemsModel as $anItemModel){
					$anItem = $anItemModel->attributes;
					if (empty($anItem['photo_primary'])){
						$prodInfo = Product::findone(['sku'=>$anItem['sku']]);
						if ($prodInfo != null)
							$anItem['photo_primary'] = $prodInfo['photo_primary'];
					}
			
					$orderDetail['items'][] = $anItem;
				}//end of each item for this order
			}//end if items found
		}
		
		return $orderDetail;
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据$order的model获取最后一条Tracking信息,适用于订单不确定是否为合并原始单的情况
	 * @access	static
	 * @param	mdoel	$order  订单mdoel
	 * @return	array	TrackingInfo
	 * @author	lzhl	2017-2-15	初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderTrackingInfoByOrder($order){
		//获取 tracking 数据 用来代换数据
		if($order->order_relation=='fm'){
			$relation = OrderRelation::find()->where(['father_orderid'=>$order->order_id,'type'=>'merge'])->asArray()->One();
			$SmOrderId = empty($relation['son_orderid'])?'':$relation['son_orderid'];
			$TrackingInfo = Tracking::find()->where(['order_id'=>$SmOrderId])->orderBy("id DESC")->asArray()->One();
		}elseif($order->order_relation=='normal' || $order->order_relation=='sm'){
			$TrackingInfo = Tracking::find()->where(['order_id'=>$order->order_id])->orderBy("id DESC")->asArray()->One();
		}
		
		return $TrackingInfo;
	}
	
	/**
	 +----------------------------------------------------------
	 * 获取某个订单的Detail信息
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param    $paramKey order_source_order_id  OR order_id  Key
	 * 			 $order_id 平台订单号Id            OR 小老板平台订单id
	 +----------------------------------------------------------
	 * @return			    Array of Order items array()
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015-11-10   			初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderDetailOld($paramKey, $order_id) {
		$orderDetail = array();
	
		$command = Yii::$app->get('subdb')->createCommand("select * from od_order_old_v2 where $paramKey='".$order_id."'" );
		$orders =  $command->queryAll();
	
		if (!empty($orders))
		foreach ($orders as $order){
		}
	
		if (!empty($order)){
			$itemsModel	= OdOrderItemOld::findAll(["order_id"=>$order['order_id']]);
			$orderDetail = $order;//$order->attributes;
			$orderDetail['items'] = array();
			//set photo for each item, if there is no photo in record, try to load it from product model
			if ($itemsModel != null){
				foreach ($itemsModel as $anItemModel){
					$anItem = $anItemModel->attributes;
					if (empty($anItem['photo_primary'])){
						$prodInfo = Product::findone(['sku'=>$anItem['sku']]);
						if ($prodInfo != null)
							$anItem['photo_primary'] = $prodInfo['photo_primary'];
					}
						
					$orderDetail['items'][] = $anItem;
				}//end of each item for this order
			}//end if items found
		}
	
		return $orderDetail;
	}//end of getOrderDetailOld
	
	/**
	 +----------------------------------------------------------
	 * 根据 $customerArr 获取指定平台的订单列表信息
	 * 可以根据某个sku获取所有订单的listing
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $platformType //平台类型
	 * 			  $customerArr=array('source_buyer_user_id' => '');  //实例: 不同平台的customer唯一性不确定,需要使用者确定自己的customer_id
	 * 			  $params=array(sort, order, sku, source_order_id); //sort, order是排序用到的参数, sku:某个 sku 的所有订单的listing,source_order_id订单来源  的订单id
	 *			  $defaultPageSize //默认每页显示的记录数
	 +----------------------------------------------------------
	                                                             是否执行成功         错误信息         订单列表信息
	 * @return			    Array 'success'=>true,'error'=>'','orderArr'=>$data
	 * 
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015-07-22   			初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderList($platformType, $customerArr = array(), $params = array(), $defaultPageSize = 20){
		$conn=\Yii::$app->subdb;
		$queryTmp = new Query;
	
		$andSql = '';
		
		$andSql2 = '';
		$andParam2 = array();
	
		foreach ($customerArr as $key => $values){
// 			$andSql .= ' and '.$key.'='."'$values' ";
			$andSql2 .= ' and '.$key.'=:'.$key.' ';
			$andParam2[':'.$key] = $values;
		}
		
		if(!empty($params['sku'])){
			$andSql .= " and exists(select 1 from od_order_item_v2 a1 where a1.order_id=t.order_id and a1.sku='".$params['sku']."')";
		}
		
		if(!empty($params['source_order_id'])){
			$andSql .= " and t.order_source_order_id='".$params['source_order_id']."'";
		}
	
		//t1.status 到时展示时需要做转换Tracking::getChineseStatus
// 		$queryTmp->select("t.*,t2.track_no,t2.status")
// 			->from("od_order_v2 t")
// 			->leftJoin("(
// 				select a.order_id,max(a.id) as id
// 				from lt_tracking a
// 				group by a.order_id
// 				) t1", "t1.order_id = t.order_source_order_id")
// 			->leftJoin("lt_tracking t2", "t2.id=t1.id")
// 			->where(['and', "t.order_source='$platformType'".$andSql]);

		$queryTmp->select("t.*,'' as `track_no`,'' as `status`")
			->from("od_order_v2 t")
			->where(['and', "t.order_source='$platformType'".$andSql.$andSql2],$andParam2);
		
		$DataCount = $queryTmp->count("1", $conn);
	
		$pagination = new Pagination([
				'defaultPageSize' => $defaultPageSize,
				'totalCount' => $DataCount,
				]);
	
		$data['pagination'] = $pagination;
	
		if(empty($params['sort'])){
			$params['sort'] = ' t.create_time ';
			$params['order'] = 'desc';
		}
	
		$queryTmp->orderBy($params['sort']." ".$params['order']);
		$queryTmp->limit($pagination->limit);
		$queryTmp->offset($pagination->offset);
	
		$orderDataArr = $queryTmp->createCommand($conn)->queryAll();
		
		foreach ($orderDataArr as $orderDataKey => $orderData){
			$trackingOne=Tracking::find()->where(['order_id'=>$orderData['order_source_order_id'],'seller_id'=>$orderData['selleruserid']])
				->orderBy([ 'update_time'=>SORT_DESC] )->asArray()->one();
			
			if (count($trackingOne) != 0 ){
				$orderDataArr[$orderDataKey]['track_no'] = $trackingOne['track_no'];
				$orderDataArr[$orderDataKey]['status'] = $trackingOne['status'];
			}
			unset($trackingOne);
		}
	
		$data['data'] = $orderDataArr;
		return array('success'=>true,'error'=>'','orderArr'=>$data);
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 根据 $customerArr 获取指定平台的订单列表信息
	 * 可以根据某个sku获取所有订单的listing
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $platformType //平台类型
	 * 			  $customer_id  //实例: 客户id
	 * 			  $sku 			//商品sku
	 *
	 +----------------------------------------------------------
	 是否执行成功
	 * @return			    Array 'type'=>,'error'=>'','dataInfo'=>$data
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015-07-30   			初始化
	 +----------------------------------------------------------
	 **/
	public static function getOrderDetailOrSkuDetail($platformType, $customer_id, $sku){
		$conn=\Yii::$app->subdb;
		$queryTmp = new Query;
	
		$andSql2 = ' and t.source_buyer_user_id=:source_buyer_user_id ';
		$andParam2 = array();
		$andParam2[':source_buyer_user_id'] = $customer_id;
		
		$andSql = '';
// 		$andSql .= ' and t.source_buyer_user_id='."'$customer_id' ";
        if($platformType == 'ebay'){//ebay特殊处理，sku为itemid
            $andSql .= " and exists(select 1 from od_order_item_v2 a1 where a1.order_id=t.order_id and a1.source_item_id='".$sku."')";
        }else{
            $andSql .= " and exists(select 1 from od_order_item_v2 a1 where a1.order_id=t.order_id and a1.sku='".$sku."')";
        }
		
	
		//t1.status 到时展示时需要做转换Tracking::getChineseStatus
		$queryTmp->select("t.*,'' as `track_no`,'' as `status`")
		->from("od_order_v2 t")
		->where(['and', "t.order_source='$platformType'".$andSql.$andSql2],$andParam2);
	
		$queryTmp->orderBy('t.order_source_create_time'." ".'desc');
		$queryTmp->limit(1);
		$queryTmp->offset(0);
	
		$orderHead = $queryTmp->createCommand($conn)->queryAll();
	
		if (empty($orderHead)){
			$result = ProductApiHelper::getProductInfo($sku);
				
			return array('type'=>'sku','dataInfo'=>$result);
		}else{
			$orderDetail = array();
			$orderHead = $orderHead[0];
			$orderDetail = self::getOrderDetail('order_source_order_id', $orderHead['order_source_order_id']);
				
			return array('type'=>'order','dataInfo'=>$orderDetail);
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 根据$order_id获取最后一条Tracking跟踪号号
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $seller_id //卖家Id
	 * 			  $order_id  //所属平台订单号
	 *
	 +----------------------------------------------------------
	 是否执行成功
	 * @return			    Array $result
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015-07-31   			初始化
	 +----------------------------------------------------------
	 **/
	public static function  getTrackingNoByOrderId($seller_id, $order_id){
		$result = array('track_no'=>'','status'=>'');
		
		$trackingOne=Tracking::find()->where(['order_id'=>$order_id,'seller_id'=>$seller_id])
			->orderBy(['update_time'=>SORT_DESC])->asArray()->one();
	
		if (count($trackingOne) != 0 ){
			$result['track_no'] = $trackingOne['track_no'];
			$result['status'] = $trackingOne['status'];
		}
		
		return $result;
	}
	
	
	
	/**
	 +----------------------------------------------------------
	 * 根据提供的参数，把需要同步好评的订单插入队列表
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     $seller_id //卖家Id
	 * 			  $order_id  //所属平台订单号
	 *
	 +----------------------------------------------------------
	 是否执行成功
	 * @return			    Array $result
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		kincen	2015-08-26   			初始化
	 +----------------------------------------------------------
	 **/
	public static function insertOrderToQueue($params){
	    $IOQ_obj = new QueueAliexpressPraise();
	    $IOQ_obj->orderId = $params['orderId'];
	    $IOQ_obj->score = $params['score'];
	    $IOQ_obj->feedbackContent = $params['feedbackContent'];
	    $IOQ_obj->sellerloginid = $params['sellerloginid'];
	    $IOQ_obj->status = 0;
	    $res = $IOQ_obj->save();
	    if($res){
	        $arr['msg'] = 'true';
	        $arr['result'] = array();
	    }else{
	        $arr['msg'] = 'false';
	        $arr['result'] = $params;
}
	    return $arr;
	}
	
	
	/**
	 +----------------------------------------------------------
	 * 根据提供的参数，把需要同步好评的订单插入队列表
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param     
	 * 			  $order_id  //所属平台订单号
	 *
	 +----------------------------------------------------------
	 是否执行成功
	 * @return			    Array $result
	 *
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		kincen	2015-08-26   			初始化
	 +----------------------------------------------------------
	 **/
	public static function getPraiseInfo($params){
        $QAP = QueueAliexpressPraiseInfo::findOne(array('orderId'=>$params['orderId']));
		   if(isset($QAP)){
			   $result['orderId'] = $QAP->orderId;
				 $result['score'] = $QAP->score;
				 $result['feedbackContent'] = $QAP->feedbackContent;
				 $result['sellerloginid'] = $QAP->sellerloginid;
				 $result['errorCode'] = $QAP->errorCode;
				 $result['errorMessage'] = $QAP->errorMessage;
				 $result['success'] = $QAP->success;
		   }else{
			   $result = array();
		   }
        return $result;
	}

	
}
