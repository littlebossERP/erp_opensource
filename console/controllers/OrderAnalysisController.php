<?php
namespace console\controllers;

use yii\console\Controller;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\UserBase;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\helpers\OrderBackgroundHelper;
use eagle\modules\order\helpers\EbayOrderHelper;
use eagle\models\db_queue2\EbayItemPhotoQueue;

class OrderAnalysisController extends Controller
{
	static public  $Version="1.55";
	
	/**
	 +----------------------------------------------------------
	 * 查看当前控制器 的版本号
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/03/29				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/get-current-version
	 **/
	public function actionGetCurrentVersion(){
		echo "\n".self::$Version."\n";
	} 
	
	/**
	 +----------------------------------------------------------
	 * ebay 订单 root sku 数据修正
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/04/07				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/ebay-photo-update
	 **/
	public function actionEbayPhotoUpdate($IPQID=''){
		try {
			if (empty($IPQID)){
				echo " IPQID is empty !";
				return ;
			}
			//更新后重读
			$newIPQ=EbayItemPhotoQueue::find()
			->where(['id'=>$IPQID])
			->andwhere('retry_count < 5')
			->one();
			
			if (empty($newIPQ->puid)){
				echo " puid  is empty !";
				return ;
			}
		 
			 
			echo " 222=".$IPQID." status = ".$newIPQ->status;
			//回调保存到orderitem
			if($newIPQ->status=="C"){
				echo "\n ".$newIPQ->status;
				try{
					list($success,$UpdateMSG) = EbayOrderHelper::updateEbayOrderItemPhotoUrl($newIPQ->itemid,$newIPQ->product_attributes,$newIPQ->puid,$newIPQ->photo_url);
					echo "\n v1.1 uid=".$newIPQ->puid." itemid=".$newIPQ->itemid." $UpdateMSG and attr=".print_r($newIPQ->product_attributes,true);
					echo "\n photoURL= ".$newIPQ->photo_url."\n";
				}catch(\Exception $e){
					echo "call back updateEbayOrderItemPhotoUrl error ".$e->getMessage()."\n";
				};
			}
		} catch (\Exception $e) {
			echo "\n Error message:".$e->getMessage()." line no :".$e->getLine();
		}
		
	}
	
	/**
	 +----------------------------------------------------------
	 * ebay 订单 root sku 数据修正
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/04/07				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/empty-root-sku-set-value
	 **/
	public function actionEmptyRootSkuSetValue($puid=''){
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
	
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		$result = [];
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
	 
			//$sql ="select order_source , count(1) as cc from od_order_v2  where order_id in (select order_id from od_order_item_v2    where  ifnull(root_sku,'') = '' ) group by order_source  ";
	
			$sql ="select sku from od_order_item_v2 where order_id in (select order_id from od_order_v2 where order_source='ebay' and order_status = 200 ) and ifnull(root_sku,'')='' group by sku ";
			$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
			foreach($rts as $rt){
				if (!empty($rt['sku'])){
					//echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' sku is empty -warning ('.$rt['cc'].')';
					try {
						$rootSKU = \eagle\modules\catalog\apihelpers\ProductApiHelper::getRootSKUByAlias($rt['sku']);
						if (!empty($rootSKU)){
							$updateSql = "update  od_order_item_v2  set root_sku = '$rootSKU' where order_id in (select order_id from od_order_v2 where order_source='ebay' and order_status = 200) and ifnull(root_sku,'')='' and sku = '".$rt['sku']."'";
							$updateRT = \Yii::$app->subdb->createCommand($updateSql)->execute();
							echo " \n puid=".$puser['uid'].'  sku is empty  -warning ('.$rt['sku'].")=>$rootSKU root sku fix effect=".$updateRT;
						}
					} catch (Exception $e) {
						echo " \n puid=".$puser['uid'].' error message:'.$e->getMessage()." line no:".$e->getLine();
					}
						
					$total++;
				}else{
					echo " \n puid=".$puser['uid'].' is normal ';
				}
			}
	
	
		}//foreach database
	
		if (!empty($result)){
			foreach($result as $platform=>$init_time){
				echo "\n v1.0 final platform=".$platform." init time=".date("Y-m-d H:i:s",$init_time);
			}
		}else{
			echo "\n all user are normal!";
		}
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}//end of function actionEmptyRootSkuSetValue
	
	/**
	 +----------------------------------------------------------
	 * 订单 无item 订单数据  检查
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/03/29				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/empty-sku-set-default-value
	 **/
	public function actionEmptySkuSetDefaultValue($puid=''){
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
	
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		$result = [];
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
	 
			//$sql ="select order_source , count(1) as cc from od_order_v2  where order_id in (select order_id from od_order_item_v2    where  ifnull(root_sku,'') = '' ) group by order_source  ";
	
			$sql ="select count(1) as cc from od_order_v2  where order_id in (select order_id from od_order_item_v2    where  ifnull(sku,'') = '' )  ";
			$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
			foreach($rts as $rt){
				if (!empty($rt['cc'])){
					//echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' sku is empty -warning ('.$rt['cc'].')';
					echo " \n puid=".$puser['uid'].'  sku is empty  -warning ('.$rt['cc'].')';
					try {
						OrderBackgroundHelper::OrderItemSKUDataCoversion($puser['uid']);
					} catch (Exception $e) {
						echo " \n puid=".$puser['uid'].' error message:'.$e->getMessage()." line no:".$e->getLine();
					}
					
					$total++;
				}else{
					echo " \n puid=".$puser['uid'].' is normal ';
				}
			}
	
	
		}//foreach database
	
		if (!empty($result)){
			foreach($result as $platform=>$init_time){
				echo "\n v1.0 final platform=".$platform." init time=".date("Y-m-d H:i:s",$init_time);
			}
		}else{
			echo "\n all user are normal!";
		}
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}//end of function actionEmptySkuSetDefaultValue
	
	/**
	 +----------------------------------------------------------
	 * 订单 无item 订单数据  检查
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/03/29				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/get-no-item-order
	 **/
	public function actionGetNoItemOrder($puid=''){
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		$result = [];
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
		 
			//$sql ="select order_source , count(1) as cc from od_order_v2  where order_id in (select order_id from od_order_item_v2    where  ifnull(root_sku,'') = '' ) group by order_source  ";
		
			$sql =" select order_source , min(create_time) as init_time from od_order_v2 where order_status < 500 and order_id not in (select order_id from od_order_item_v2) group by order_source ";
			$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
			if (!empty($rts)){
				foreach($rts as $rt){
					if (isset($result[$rt['order_source']])  ){
						if ($result[$rt['order_source']] > $rt['init_time']){
							$result[$rt['order_source']]  = $rt['init_time'];
						}
					}else{
						$result[$rt['order_source']] = $rt['init_time'];
					}
					
					echo "\n ".$puser['uid']." platform=".$rt['order_source']." init time=".date("Y-m-d H:i:s",$rt['init_time']);
				}
			}else{
				echo " \n puid=".$puser['uid'].' is normal ';
			}
				
		
		}//foreach database
		
		if (!empty($result)){
			foreach($result as $platform=>$init_time){
				echo "\n v1.0 final platform=".$platform." init time=".date("Y-m-d H:i:s",$init_time);
			}
		}else{
			echo "\n all user are normal!";
		}
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}//end of function actionGetNoItemOrder
	
	/**
	 +----------------------------------------------------------
	 * 订单 delivery status 健康检查
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/03/08				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/get-ebay-order-item
	 **/
	public function actionGetEbayOrderItem($puid=''){
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
		 
			//$sql ="select order_source , count(1) as cc from od_order_v2  where order_id in (select order_id from od_order_item_v2    where  ifnull(root_sku,'') = '' ) group by order_source  ";
			$updateSql = "update  od_ebay_transaction  a , od_order_v2 b  set a.order_id = b.order_id where a.orderid  = b.order_source_order_id and a.order_id is null and b.order_id is not null";
			$updateRT = \Yii::$app->subdb->createCommand($updateSql)->execute();
			echo "\n no order id fix effect=".$updateRT;
				
			$updateSql = "update  od_paypal_transaction  a , od_order_v2 b  set a.order_id = b.order_id where a.ebay_orderid  = b.order_source_order_id and a.order_id is null and b.order_id is not null";
			$updateRT = \Yii::$app->subdb->createCommand($updateSql)->execute();
			echo "\n no order id fix effect=".$updateRT;
			
			$tmp_total = EbayOrderHelper::retrieveOrderItem($puser['uid']);
			
			$total+=$tmp_total;
			if (empty($tmp_total)){
				echo " \n puid=".$puser['uid'].' is normal ';
			}
		
		}//foreach database
		
		echo " \n v1.3 total-warning (".$total.')';
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}//end of function actionGetEbayOrderItem
	
	/**
	 +----------------------------------------------------------
	 * 订单 delivery status 健康检查
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/03/08				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/get-order-item-delivery-status-last-day
	 **/
	public function actionGetOrderItemDeliveryStatusLastDay($puid=''){
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
		
		 
			//$sql ="select order_source , count(1) as cc from od_order_v2  where order_id in (select order_id from od_order_item_v2    where  ifnull(root_sku,'') = '' ) group by order_source  ";
		
			$sql ="select FROM_UNIXTIME(max(od_order_v2.create_time) ,'%Y-%m-%d %H:%i:%s') as tt, order_source  from od_order_item_v2 , od_order_v2
where od_order_v2.order_id = od_order_item_v2.order_id and od_order_v2.order_status in (200,300)  and od_order_item_v2.delivery_status = 'ban'
and platform_status in ('ACCEPTED','COMMITTED','AcceptedBySeller','ShippedBySeller' , 'pending','ready_to_ship','processing','shipped','delivered' , 'Unshipped','Shipped')  
group by od_order_v2.order_source ";
			$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
			if (!empty($rts)){
				foreach($rts as $rt){
					if (!empty($rt['tt'])){
						//echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' sku is empty -warning ('.$rt['cc'].')';
						$updateSql = "UPDATE od_order_item_v2 , od_order_v2 SET od_order_item_v2.`delivery_status` = 'allow', od_order_item_v2.`manual_status` = 'enable' 
where od_order_v2.order_id = od_order_item_v2.order_id and od_order_v2.order_status in (200,300)  and od_order_item_v2.delivery_status = 'ban'
and platform_status in ('ACCEPTED','COMMITTED','AcceptedBySeller','ShippedBySeller' , 'pending','ready_to_ship','processing','shipped','delivered' , 'Unshipped','Shipped')  ";
						$updateRT = '';
						echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' ban in shipping -last day ('.$rt['tt'].')';
							
					}else{
						echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' ban in shipping -last day is null';
					}
					$total++;
				}
			}else{
				echo " \n puid=".$puser['uid'].' is normal ';
			}
			
		
		}//foreach database
		
		echo " \n total-warning (".$total.')';
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单 root sku 健康检查
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/03/08				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/get-order-item-rootsku-last-day
	 **/
	
	public function actionGetOrderItemRootskuLastDay($puid=''){
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
		
		 
			//$sql ="select order_source , count(1) as cc from od_order_v2  where order_id in (select order_id from od_order_item_v2    where  ifnull(root_sku,'') = '' ) group by order_source  ";
				
			//$sql ="select FROM_UNIXTIME(max(order_source_create_time),'%Y-%m-%d %H:%i:%s') tt  , order_source from od_order_v2 a  where order_id in (select order_id from od_order_item_v2 where sku = '') and order_status in(200, 300 ) group by order_source ; ";
			$sql ="select FROM_UNIXTIME(max(order_source_create_time),'%Y-%m-%d %H:%i:%s') tt  , order_source from od_order_v2 a  where order_id in (select order_id from od_order_item_v2 where root_sku = '') and order_status in( 300 ) and order_source_create_time >=1490227200 group by order_source ; ";
			$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
			if (empty($rts)){
				echo " \n puid=".$puser['uid'].' is normal ';
			}
			foreach($rts as $rt){
				if (!empty($rt['tt'])){
					//echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' sku is empty -warning ('.$rt['cc'].')';
					echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' sku is empty in shipping -last day ('.$rt['tt'].')';
					$total++;
				}else{
					echo " \n puid=".$puser['uid'].' is normal ';
				}
			}
				
		}//foreach database
		
		echo " \n total-warning (".$total.')';
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}
	/**
	 +----------------------------------------------------------
	 * 订单 root sku 健康检查
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/02/20				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/order-item-rootsku-health-check
	 **/
	public function actionOrderItemRootskuHealthCheck($puid = ''){
		
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		$puid = '';
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
			$puid = $puser['uid'];
		 
			
			try {
				$sql ="select order_source , count(1) as cc from od_order_v2  where order_id in (select order_id from od_order_item_v2    where  ifnull(root_sku,'') = '' ) and order_status = 300 and order_source_create_time >=1490227200  group by order_source  ";
				
				//$sql ="select order_source , count(1) as cc from od_order_v2  where order_id in (select order_id from od_order_item_v2    where  ifnull(sku,'') = '' ) and order_status = 300 group by order_source  ";
				$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
				if (empty($rts)){
					echo " \n puid=".$puser['uid'].' is normal ';
				}
				foreach($rts as $rt){
					if (!empty($rt['cc'])){
						//echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' sku is empty -warning ('.$rt['cc'].')';
						echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' sku is empty in shipping -warning ('.$rt['cc'].')';
						$total++;
					}else{
						echo " \n puid=".$puser['uid'].' is normal ';
					}
				}//end of each result
			} catch (\Exception $e) {
				echo "\n puid=$puid Exception error:".$e->getMessage()." line no:".$e->getLine();
			}
				
		}//foreach database
		
		echo " \n total-warning (".$total.')';
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}//end of actionOrderRootSKUHealthCheck
	
	/**
	 +----------------------------------------------------------
	 * 订单禁用标记健康检查
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/02/20				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/order-item-delivery-status-health-check
	 **/
	public function actionOrderItemDeliveryStatusHealthCheck($puid = ''){
		
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
	
		 
			$sql ="select od_order_v2.order_source,od_order_item_v2.platform_status ,count(1) as ct  from od_order_item_v2 , od_order_v2
where od_order_v2.order_id = od_order_item_v2.order_id and od_order_v2.order_status in (200,300)  and od_order_item_v2.delivery_status = 'ban'
group by od_order_v2.order_source,od_order_item_v2.platform_status  ";
			$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
			
			if (!empty($rts)){
				foreach($rts as $rt){
					if (!empty($rt['ct'])){
						//echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' sku is empty -warning ('.$rt['cc'].')';
						echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' platform_status='.$rt['platform_status'].' ban in shipping -warning ('.$rt['ct'].')';
					}else{
						echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' platform_status='.$rt['platform_status'].' ban in shipping -warning is zero ';
					}
					$total++;
				}
			}else{
				echo " \n puid=".$puser['uid'].' is normal ';
			}
			
		}//foreach database
		
		echo " \n user-total-warning (".$total.')';
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		
	}//end of actionBuyerCountryCodeStatistics
	
	/**
	 +----------------------------------------------------------
	 * 订单禁用标记问题修正
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/02/20				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/order-item-delivery-status-bug-fixed
	 **/
	public function actionOrderItemDeliveryStatusBugFixed($puid=''){
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
	  	
			$sql ="select FROM_UNIXTIME(max(od_order_v2.create_time) ,'%Y-%m-%d %H:%i:%s') as tt, order_source  from od_order_item_v2 , od_order_v2
where od_order_v2.order_id = od_order_item_v2.order_id and od_order_v2.order_status in (200,300)  and od_order_item_v2.delivery_status = 'ban'
and platform_status in ('ACCEPTED','COMMITTED','AcceptedBySeller','ShippedBySeller' , 'pending','ready_to_ship','processing','shipped','delivered' , 'Unshipped','Shipped')
group by od_order_v2.order_source ";
			$rts = \Yii::$app->subdb->createCommand($sql)->queryAll();
			if (!empty($rts)){
				$updateSql = "UPDATE od_order_item_v2 , od_order_v2 SET od_order_item_v2.`delivery_status` = 'allow', od_order_item_v2.`manual_status` = 'enable'
where od_order_v2.order_id = od_order_item_v2.order_id and od_order_v2.order_status in (200,300)  and od_order_item_v2.delivery_status = 'ban'
and platform_status in ('ACCEPTED','COMMITTED','AcceptedBySeller','ShippedBySeller' , 'pending','ready_to_ship','processing','shipped','delivered' , 'Unshipped','Shipped')  ";
				$updateRT = \Yii::$app->subdb->createCommand($updateSql)->execute();
				foreach($rts as $rt){
					if (!empty($rt['tt'])){
						//echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' sku is empty -warning ('.$rt['cc'].')';
						
						echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' ban in shipping -last day ('.$rt['tt'].') then update effect ='.$updateRT;
							
					}else{
						echo " \n puid=".$puser['uid'].' order_source='.$rt['order_source'].' ban in shipping -last day is null  then update effect ='.$updateRT;
					}
					$total++;
				}
			}else{
				echo " \n puid=".$puser['uid'].' is normal ';
			}
			
		}//foreach database
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		
	}//end of function actionOrderItemDeliveryStatusBugFixed
	
	
	/**
	 +----------------------------------------------------------
	 * 订单禁用标记问题修正
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2017/02/20				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/order-item-delivery-status-aliexpress-bug-fixed
	 **/
	public function actionOrderItemDeliveryStatusAliexpressBugFixed($puid=''){
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		}
		$start_time = date('Y-m-d H:i:s');
		$comment = "\n cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
	
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$total = 0;
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
	 	
			$sql ="select FROM_UNIXTIME(max(od_order_v2.create_time) ,'%Y-%m-%d %H:%i:%s')  as cc from od_order_item_v2 , od_order_v2
where od_order_v2.order_id = od_order_item_v2.order_id and od_order_v2.order_status in (200,300) and od_order_v2.order_source='aliexpress'  and od_order_item_v2.delivery_status = 'ban' ";
			$rt = \Yii::$app->subdb->createCommand($sql)->queryAll();
			if (!empty($rt[0]['cc'])){
				echo " \n puid=".$puser['uid'].'-warning time('.$rt[0]['cc'].')';
				$updateSql = "update od_order_item_v2 , od_order_v2 set  od_order_item_v2.platform_status = od_order_v2.order_status  where od_order_v2.order_id = od_order_item_v2.order_id and od_order_v2.order_status in (200,300) and od_order_v2.order_source='aliexpress'  and od_order_item_v2.delivery_status = 'ban' ";
				$rt = \Yii::$app->subdb->createCommand($updateSql)->execute();
				echo " \n puid=".$puser['uid'].'-execute ('.$rt.')';
				$updateSql = " UPDATE `od_order_item_v2` SET `delivery_status` = 'allow', `manual_status` = 'enable'
				where  order_id in (select order_id from od_order_v2 where order_status in (200,300) and order_source='aliexpress' ) and delivery_status = 'ban'  ";
				$rt = \Yii::$app->subdb->createCommand($updateSql)->execute();
				echo " \n puid=".$puser['uid'].'-execute ('.$rt.')';
					
				$sql ="select count(1) as cc from od_order_item_v2 where  order_id in (select order_id from od_order_v2 where order_status in (200,300)  ) and delivery_status = 'ban'  ";
				$rt = \Yii::$app->subdb->createCommand($sql)->queryAll();
				if (!empty($rt[0]['cc'])){
					echo " \n puid=".$puser['uid'].'-warning ('.$rt[0]['cc'].')';
					$total++;
				}else{
					echo " \n puid=".$puser['uid'].' is normal ';
				}
	
				echo " \n puid=".$puser['uid'].'-warning ('.$rt[0]['cc'].')';
				$total++;
			}else{
				echo " \n puid=".$puser['uid'].' is normal ';
			}
				
				
		}//foreach database
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "\n cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	
	}//end of function actionOrderItemDeliveryStatusBugFixed
	
	
	//./yii order-analysis/test-add-order-check-queue
	public function actionTestAddOrderCheckQueue(){
		foreach(OdOrder::find()->where(['order_status'=>OdOrder::STATUS_PAY])->asArray()->each(1) as $order){
			echo '\n '.$order['order_id'].' ';
			$rt = \eagle\modules\order\helpers\OrderBackgroundHelper::insertOrderAutoCheckQueue('1', $order['order_id']);
			var_dump($rt);
		}
	}
	
	/**
	 +----------------------------------------------------------
	 * 订单状态 自动检测
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/10/31				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/reset-order-sync-ship-queue
	 **/
	public function actionResetOrderSyncShipQueue(){
		 
	}//end of function actionResetOrderSyncShipQueue
	
	/**
	 +----------------------------------------------------------
	 * 订单状态 自动检测
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/04/05				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/init-order-sync-ship-status
	 **/
	public function actionInitOrderSyncShipStatus(){
		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$missingkeys = [];
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
		 
			//OdOrder::find()->where(['order_status'=>OdOrder::STATUS_PAY])->each(1)
				
			
			$rt = \eagle\modules\order\helpers\OrderBackgroundHelper::initOrderSyncShipStatusCount(true);
			var_dump($rt);
				
		}//foreach database
	}//end of actionInitOrderSyncShipStatus
	
	/**
	 +----------------------------------------------------------
	 * 订单状态 自动检测 
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/04/05				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/queue-auto-order-check
	 **/
	 /* 双11 进程同步不过来
	public function actionQueueAutoOrderCheck(){
		
		$start_time = date('Y-m-d H:i:s');
		$comment = "cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		
		do{
			$rt = \eagle\modules\order\helpers\OrderBackgroundHelper::handleOrderAutoCheckQueue();
			$success = $rt['success'];
			$message = $rt['message'];
			if ($success == false){
				$sleepTime = 10;
				echo "\n".$message." no data in queue , then sleep $sleepTime second...";
				sleep($sleepTime);
			}else{
				echo "\n".$message;
			}
			
			$auto_exit_time = 25 + rand(1,10); // 25 - 35 minutes to leave
			$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
		
		}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}//end of function actionQueueAutoOrderCheck
	*/
	/**
	 +----------------------------------------------------------
	 * 订单状态 自动检测 
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/11/12				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/queue-auto-order-check0
	 **/
	public function actionQueueAutoOrderCheck0(){
		
		$start_time = date('Y-m-d H:i:s');
		$comment = "cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		
		do{
			$rt = \eagle\modules\order\helpers\OrderBackgroundHelper::handleOrderAutoCheckQueue(0);
			$success = $rt['success'];
			$message = $rt['message'];
			if ($success == false){
				$sleepTime = 10;
				echo "\n".$message." no data in queue , then sleep $sleepTime second...";
				sleep($sleepTime);
			}else{
				echo "\n".$message;
			}
			
			$auto_exit_time = 25 + rand(1,10); // 25 - 35 minutes to leave
			$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
		
		}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}//end of function actionQueueAutoOrderCheck0
	
	/**
	 +----------------------------------------------------------
	 * 订单状态 自动检测 
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/11/12				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/queue-auto-order-check1
	 **/
	public function actionQueueAutoOrderCheck1(){
		
		$start_time = date('Y-m-d H:i:s');
		$comment = "cron service runnning for Order Status Check Queue at $start_time";
		echo $comment;
		
		do{
			$rt = \eagle\modules\order\helpers\OrderBackgroundHelper::handleOrderAutoCheckQueue(1);
			$success = $rt['success'];
			$message = $rt['message'];
			if ($success == false){
				$sleepTime = 10;
				echo "\n".$message." no data in queue , then sleep $sleepTime second...";
				sleep($sleepTime);
			}else{
				echo "\n".$message;
			}
			
			$auto_exit_time = 25 + rand(1,10); // 25 - 35 minutes to leave
			$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
		
		}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
		
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "cron service stops for  Order Status Check Queue  at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
	}//end of function actionQueueAutoOrderCheck1
	
	/**
	 +----------------------------------------------------------
	 * 删除 订单状态 自动检测 数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/04/05				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/delete-queue-auto-order-check-data
	 **/
	public function actionDeleteQueueAutoOrderCheckData(){
		$start_time = date('Y-m-d H:i:s');
		//默认删除前2天的数据
		$rt = \eagle\modules\order\helpers\OrderBackgroundHelper::deleteOrderCheckQueue();
		echo "\n ".$start_time." delete ".$rt." record!";
	}//end of function actionDeleteQueueAutoOrderCheckData
	
	
	/**
	 +----------------------------------------------------------
	 * od_order_old_v2 表， 数据结构检查
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/04/05				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/auto-order-check
	 **/
	public function actionAutoOrderCheck(){
		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$missingkeys = [];
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
		
		 
				//OdOrder::find()->where(['order_status'=>OdOrder::STATUS_PAY])->each(1)
			
			//获取old表  所有 字段
			foreach(OdOrder::find()->where(['order_status'=>OdOrder::STATUS_PAY])->each(1) as $order){
				
				$order->checkorderstatus('System');
				
				if ($order->save()){
					echo "\n uid=".$puser['uid']." oid=".$order->order_id.'is ok';
				}else{
					echo "\n uid=".$puser['uid']." oid=".$order->order_id.' error messge :'.json_encode($order->errors);
				}
				
			}
			
		}//foreach database
	}//end of $order->errors
	
	
	/**
	 +----------------------------------------------------------
	 * od_order_old_v2 表， 数据结构检查
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/04/05				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/order-old-table-structure-health-check
	 **/
	public function actionOrderOldTableStructureHealthCheck($puid=''){
		if (!empty($puid)){
			$mainUsers = [['uid'=>$puid]];
		}else{
			//$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
			$mainUsers = UserBase::find()->where( " uid>= 9868 ")->asArray()->all();
		}
		
		//$colums_o = '["order_id","order_status","pay_status","order_source_status","order_manual_id","is_manual_order","shipping_status","exception_status","weird_status","order_source","order_type","order_source_order_id","order_source_site_id","selleruserid","saas_platform_user_id","order_source_srn","customer_id","source_buyer_user_id","order_source_shipping_method","order_source_create_time","subtotal","shipping_cost","antcipated_shipping_cost","actual_shipping_cost","discount_amount","commission_total","grand_total","returned_total","price_adjustment","currency","consignee","consignee_postal_code","consignee_phone","consignee_mobile","consignee_fax","consignee_email","consignee_company","consignee_country","consignee_country_code","consignee_city","consignee_province","consignee_district","consignee_county","consignee_address_line1","consignee_address_line2","consignee_address_line3","default_warehouse_id","default_carrier_code","default_shipping_method_code","paid_time","delivery_time","create_time","update_time","user_message","carrier_type","hassendinvoice","seller_commenttype","seller_commenttext","status_dispute","is_feedback","rule_id","customer_number","carrier_step","is_print_picking","print_picking_operator","print_picking_time","is_print_distribution","print_distribution_operator","print_distribution_time","is_print_carrier","print_carrier_operator","printtime","delivery_status","delivery_id","desc","carrier_error","is_comment_status","is_comment_ignore","issuestatus","payment_type","logistic_status","logistic_last_event_time","fulfill_deadline","profit","logistics_cost","logistics_weight","addi_info","distribution_inventory_status","reorder_type","purchase_status","pay_order_type","order_evaluation","tracker_status","origin_shipment_detail"]';
		$colums_o = '["order_id","order_status","pay_status","order_source_status","order_manual_id","is_manual_order","shipping_status","exception_status","weird_status","order_source","order_type","order_source_order_id","order_source_site_id","selleruserid","saas_platform_user_id","order_source_srn","customer_id","source_buyer_user_id","order_source_shipping_method","order_source_create_time","subtotal","shipping_cost","antcipated_shipping_cost","actual_shipping_cost","discount_amount","commission_total","paypal_fee","grand_total","returned_total","price_adjustment","currency","consignee","consignee_postal_code","consignee_phone","consignee_mobile","consignee_fax","consignee_email","consignee_company","consignee_country","consignee_country_code","consignee_city","consignee_province","consignee_district","consignee_county","consignee_address_line1","consignee_address_line2","consignee_address_line3","default_warehouse_id","default_carrier_code","default_shipping_method_code","paid_time","delivery_time","create_time","update_time","user_message","carrier_type","hassendinvoice","seller_commenttype","seller_commenttext","status_dispute","is_feedback","rule_id","customer_number","carrier_step","is_print_picking","print_picking_operator","print_picking_time","is_print_distribution","print_distribution_operator","print_distribution_time","is_print_carrier","print_carrier_operator","printtime","delivery_status","delivery_id","desc","carrier_error","is_comment_status","is_comment_ignore","issuestatus","payment_type","logistic_status","logistic_last_event_time","fulfill_deadline","profit","logistics_cost","logistics_weight","addi_info","distribution_inventory_status","reorder_type","purchase_status","pay_order_type","order_evaluation","tracker_status","origin_shipment_detail","order_ship_time","shipping_notified","pending_fetch_notified","rejected_notified","received_notified","seller_weight","order_capture","order_relation","last_modify_time","sync_shipped_status","system_tag_1","system_tag_2","system_tag_3","system_tag_4","system_tag_5","customized_tag_1","customized_tag_2","customized_tag_3","customized_tag_4","customized_tag_5","customized_tag_6","customized_tag_7","customized_tag_8","customized_tag_9","customized_tag_10","tracking_no_state","order_verify","items_md5","complete_ship_time","declaration_info","isshow","first_sku","ismultipleProduct","tracking_number","billing_info"]';
		
		$colums = json_decode($colums_o,true);
		
		
		$orderKeys = array_flip(json_decode($colums_o,true));
		
		
		//$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		$missingkeys = [];
		$missingkeysV2=[];
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
		 
			//获取old表  所有 字段
			$sql = "show full columns from od_order_old_v2 ";
				
			$CurrentResult = \yii::$app->subdb->createCommand($sql)->queryAll();
			$currentKeys = [];
			if (count($CurrentResult) != count($colums)){
				
				foreach($CurrentResult as $row){
					$currentKeys[] = $row['Field'];
				}
				
				$missingkeys[$puser['uid']] = array_diff($colums , $currentKeys);
				
				echo "puid=".$puser['uid']." old total:".count($colums)." miss ".(count($colums)-count($CurrentResult) )." \n";
			}else{
				echo "puid=".$puser['uid']." old total:".count($colums)." ok \n";
			}
			
			//获取old表  所有 字段
			$sql = "show full columns from od_order_v2 ";
			
			$CurrentResult = \yii::$app->subdb->createCommand($sql)->queryAll();
			$currentKeys = [];
			if (count($CurrentResult) != count($colums)){
			
				foreach($CurrentResult as $row){
					$currentKeys[] = $row['Field'];
				}
			
				$missingkeysV2[$puser['uid']] = array_diff($colums , $currentKeys);
			
				echo "puid=".$puser['uid']." v2 total:".count($colums)." miss ".(count($colums)-count($CurrentResult) )." \n";
			}else{
				echo "puid=".$puser['uid']." v2 total:".count($colums)." ok \n";
			}
			
			//var_dump($CurrentResult);
			
			
				
		}//foreach database
		//echo count($AllEmailList[$platform]);
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		echo "\n *********************** \n";
		echo " \n ".(__FUNCTION__)." report  time spend=".($logTimeMS2-$logTimeMS1)." data:\n";
		echo json_encode($missingkeys);
		echo "\n *********************** \n";
		echo json_encode($missingkeysV2);
		//echo "\n".json_encode($AllEmailList);
	}
	/**
	 +----------------------------------------------------------
	 * OMS买家邮箱数量统计
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/04/05				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/buyer-email-statistics
	 **/
	public function actionBuyerEmailStatistics(){
		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		$targetPlatform = ['aliexpress', 'ebay'];
		$AllEmailList = ['aliexpress'=>[] , 'ebay'=>[] ];// buyer email 总表
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
		 
			foreach ($targetPlatform as $platform){
				self::_calcPlatformBugerEmail($platform, $AllEmailList, $puser);
			}
			//$platform = 'aliexpress';
			
		}//foreach database
		//echo count($AllEmailList[$platform]);
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		echo "\n *********************** \n";
		echo " \n ".(__FUNCTION__)." report  time spend=".($logTimeMS2-$logTimeMS1)." data:";
		foreach($AllEmailList as $rt_platform=> $row){
			echo " \n ".$rt_platform."(".count($row).")";
		}
		echo "\n *********************** \n";
		//echo "\n".json_encode($AllEmailList);
	}//end of actionBuyerEmailStatistics
	
	static private function _calcPlatformBugerEmail($platform , &$AllEmailList , &$puser){
		try{
			$logTimeMS1=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS1 = (memory_get_usage()/1024/1024);
			echo "\n".$puser['uid'].__FUNCTION__.' step start diff v2 1 '.$platform .'  :'.(memory_get_usage()/1024/1024). 'M '; //test kh
			
			//获取v2表  所有邮箱
			$sql = "select consignee_email  from od_order_v2 where order_source = '$platform' and consignee_email <> '' group by consignee_email";
		
			$CurrentEmail = \yii::$app->subdb->createCommand($sql)->queryAll();
			foreach($CurrentEmail as $row){
				if (empty($row['consignee_email'])) continue; // 邮箱为空则skip
				
				if (isset($AllEmailList[$platform]) ==false) $AllEmailList[$platform] = [];
					
				if (key_exists($row['consignee_email'], $AllEmailList[$platform])){
					continue;  //邮箱已经统计过了， 也skip
				}else{
					$AllEmailList[$platform][$row['consignee_email']] = 1;
				}
				
				/*
				if (in_array($row['consignee_email'],$AllEmailList[$platform])) continue;// 邮箱已经统计过了， 也skip
				$AllEmailList[$platform][] = $row['consignee_email'];
				*/
			}
			
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS2 = (memory_get_usage()/1024/1024);
			
			echo "\n".$puser['uid'].__FUNCTION__.' step finish diff v2 2 '.$platform .'   T=('.($logTimeMS2-$logTimeMS1).') and M='.($logMemoryMS2-$logMemoryMS1).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and total '.count($CurrentEmail).''; //test kh
			
			//release memory
			unset($CurrentEmail);
			
			$logTimeMS1=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS1 = (memory_get_usage()/1024/1024);
			echo "\n".$puser['uid'].__FUNCTION__.' step start diff old 1  '.$platform .'   :'.(memory_get_usage()/1024/1024). 'M '; //test kh
			
			//获取old表  所有邮箱  由于old表可能数据量大， 安全起见 不用group by
			$sql = "select consignee_email  from od_order_old_v2 where order_source = '$platform' and consignee_email <> ''  group by consignee_email ";
			
			$CurrentEmail = \yii::$app->subdb->createCommand($sql)->queryAll();
			foreach($CurrentEmail as $row){
				if (empty($row['consignee_email'])) continue; // 邮箱为空则skip
			
				if (isset($AllEmailList[$platform]) ==false) $AllEmailList[$platform] = [];
					
				if (key_exists($row['consignee_email'], $AllEmailList[$platform])){
					continue;  //邮箱已经统计过了， 也skip
				}else{
					$AllEmailList[$platform][$row['consignee_email']] = 1;
				}
				
			}
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS2 = (memory_get_usage()/1024/1024);
				
			echo "\n".$puser['uid'].__FUNCTION__.' step finish diff old 2 '.$platform .'  T=('.($logTimeMS2-$logTimeMS1).') and M='.($logMemoryMS2-$logMemoryMS1).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and total '.count($CurrentEmail); //test kh

			//release memory
			unset($CurrentEmail);
			$subMsg =  "\n"."current result : ";
			foreach($AllEmailList as $rt_platform=> $row){
				$subMsg .= " ".$rt_platform."(".count($row).")";
			}
			echo $subMsg;
		} catch(\Exception $e){
			echo "\n".$puser['uid']."error msg :".$e->getMessage()."\n";
		}
	}
	
	
	/**
	 +----------------------------------------------------------
	 * OMS买家邮箱对应国家数量统计
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/04/05				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/buyer-country-code-statistics
	 **/
	public function actionBuyerCountryCodeStatistics(){
		$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
		$targetPlatform = ['aliexpress', 'ebay'];
		$AllCountryCodeList = ['aliexpress'=>[] , 'ebay'=>[] ];// buyer country 总表
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		foreach ($mainUsers as $puser){
			$db_name = \Yii::$app->params['subdb']['dbPrefix'].$puser['uid'];
	
			 
	
			foreach ($targetPlatform as $platform){
				self::_calcPlatformBugerCountryCode($platform, $AllCountryCodeList, $puser);
			}
			//$platform = 'aliexpress';
	
		}//foreach database
		//echo count($AllEmailList[$platform]);
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		echo "\n *********************** \n";
		echo " \n ".(__FUNCTION__)." report  time spend=".($logTimeMS2-$logTimeMS1)." data:";
		
		foreach($AllCountryCodeList as $rt_platform=> &$row){
			arsort($row);
			echo " \n\n ".$rt_platform."";
			$limit = 0;
			$total = 0;
			foreach($row as $code=>$ct){
				echo " \n ".$code."(".$ct.")";
				$total += $ct;
				$limit++;
				if ($limit>9) break;
			}
			echo " \n total(".$total.")";
		}
		echo "\n *********************** \n";
	}//end of actionBuyerCountryCodeStatistics
	
	static private function _calcPlatformBugerCountryCode($platform, &$AllCountryCodeList, &$puser){
		try{
			$logTimeMS1=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS1 = (memory_get_usage()/1024/1024);
			echo "\n".$puser['uid'].__FUNCTION__.' step start diff v2 1 '.$platform .'  :'.(memory_get_usage()/1024/1024). 'M '; //test kh
				
			//获取v2表  所有邮箱
			$sql = "select consignee_country_code ,count(1) as ct from od_order_v2 where order_source = '$platform' and consignee_email <> '' group by consignee_country_code";
		
			$CurrentResult = \yii::$app->subdb->createCommand($sql)->queryAll();
			foreach($CurrentResult as $row){
				if (empty($row['consignee_country_code'])) continue; // 邮箱为空则skip
		
				if (isset($AllCountryCodeList[$platform]) ==false) $AllCountryCodeList[$platform] = [];
					
				if (key_exists($row['consignee_country_code'], $AllCountryCodeList[$platform])){
					$AllCountryCodeList[$platform][$row['consignee_country_code']] += $row['ct'];
					continue;  //邮箱已经统计过了， 也skip
				}else{
					$AllCountryCodeList[$platform][$row['consignee_country_code']] = $row['ct'];
				}
		
				/*
				 if (in_array($row['consignee_email'],$AllEmailList[$platform])) continue;// 邮箱已经统计过了， 也skip
				$AllEmailList[$platform][] = $row['consignee_email'];
				*/
			}
				
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS2 = (memory_get_usage()/1024/1024);
				
			echo "\n".$puser['uid'].__FUNCTION__.' step finish diff v2 2 '.$platform .'   T=('.($logTimeMS2-$logTimeMS1).') and M='.($logMemoryMS2-$logMemoryMS1).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and total '.count($CurrentResult).''; //test kh
				
			//release memory
			unset($CurrentResult);
				
			$logTimeMS1=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS1 = (memory_get_usage()/1024/1024);
			echo "\n".$puser['uid'].__FUNCTION__.' step start diff old 1  '.$platform .'   :'.(memory_get_usage()/1024/1024). 'M '; //test kh
				
			//获取old表  所有邮箱  由于old表可能数据量大， 安全起见 不用group by
			$sql = "select consignee_country_code , count(1)  from od_order_old_v2 where order_source = '$platform' and consignee_email <> ''  group by consignee_country_code ";
				
			$CurrentResult = \yii::$app->subdb->createCommand($sql)->queryAll();
			foreach($CurrentResult as $row){
				if (empty($row['consignee_country_code'])) continue; // 为空则skip
		
				if (isset($AllCountryCodeList[$platform]) ==false) $AllCountryCodeList[$platform] = [];
					
				if (key_exists($row['consignee_country_code'], $AllCountryCodeList[$platform])){
					$AllCountryCodeList[$platform][$row['consignee_country_code']] += $row['ct'];
					continue;  //已经统计过了， 也skip
				}else{
					$AllCountryCodeList[$platform][$row['consignee_country_code']] = $row['ct'];
				}
		
			}
			$logTimeMS2=TimeUtil::getCurrentTimestampMS();
			$logMemoryMS2 = (memory_get_usage()/1024/1024);
		
			echo "\n".$puser['uid'].__FUNCTION__.' step finish diff old 2 '.$platform .'  T=('.($logTimeMS2-$logTimeMS1).') and M='.($logMemoryMS2-$logMemoryMS1).'M and Now Memory='.(memory_get_usage()/1024/1024). 'M and total '.count($CurrentResult); //test kh
		
			//release memory
			unset($CurrentResult);
			$subMsg =  "\n"."current result : ";
			foreach($AllCountryCodeList as $rt_platform=> &$row){
				arsort($row);
				echo "  ".$rt_platform." ";
				$limit = 0;
				$total = 0;
				foreach($row as $code=>$ct){
					echo "   ".$code."(".$ct.")" ;
					$total += $ct;
					$limit++;
					if ($limit>9) break;
				}
				echo "  total(".$total.")";
			}
			echo $subMsg;
		} catch(\Exception $e){
			echo "\n".$puser['uid']."error msg :".$e->getMessage()."\n";
		}
	}
	

	/**
	 +----------------------------------------------------------
	 * 清除订单备份数据
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh 	2016/04/05				初始化
	 +----------------------------------------------------------
	 *
	 *./yii order-analysis/truncate-order-old-data
	 **/
	public function actionTruncateOrderOldData(){
		$mainUsers = UserBase::find()->select('user_base.uid,user_database.did')
		->leftJoin('user_database',' user_database.uid = user_base.uid')
		->where(['user_base.puid'=>0,'user_base.is_active'=>1])->andWhere('user_database.dbserverid = 5')->asArray()->all();
		
		$user_info = array();
		
		foreach ($mainUsers as $puser){
			$puid = $puser['uid'];
			 
				
			$user_info[$puid] = $puid;
			
			try{ $update_sql = 'truncate table `od_order_old_v2`;'; \yii::$app->subdb->createCommand($update_sql)->execute(); } catch (\Exception $ex){}
			try{ $update_sql = 'truncate table `od_order_item_old_v2`;'; \yii::$app->subdb->createCommand($update_sql)->execute(); } catch (\Exception $ex){}
			try{ $update_sql = 'truncate table `od_order_shipped_old_v2`;'; \yii::$app->subdb->createCommand($update_sql)->execute(); } catch (\Exception $ex){}
			echo "\n".$puid." End ...\n";
		}
		
		
		echo "\n".json_encode($user_info);
	}//end of actionClearOrderOldData
	
}