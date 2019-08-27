<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use eagle\modules\listing\models\OdOrder2;
use eagle\modules\listing\models\OdOrderItem2;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\listing\models\OdOrderShipped2;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\UserBase;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\models\AliexpressOrder;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\listing\models\EbayItem;
use eagle\modules\listing\helpers\EbayitemHelper;

/**
 * 后台处理某些逻辑的脚本
 */
class ScriptController extends Controller {
	/**
	 * eagle1的订单库数据转移到eagle2中，包括表格od_order,od_order_item,od_order_shipped
	 * @author fanjs
	 */
	public function actionOrder1data1to2(){
		set_time_limit(0);
		$maxuserid=UserBase::find()->where('puid = 0 and uid >0 and uid <=240')->select('uid')->asArray()->all();
		foreach ($maxuserid as $k=>$v){
			$i=$v['uid'];
			echo 'start connect subdb:--->'.$i."\n";
			try {
				 
				//开始处理订单od_order
				echo 'start deal od_order for user:'.$i."\n";
				$db = OdOrder2::getDb();
				$q = $db->createCommand('select * from '.OdOrder2::tableName().' order by order_id ASC')->query();
				while ($row=$q->read()){
					if ($row['order_status']>200){
						$row['order_status']=200;
					}
					unset($row['order_manual_id']);
					unset($row['default_shipping_method_code']);
					$row['default_warehouse_id ']=0;
					unset($row['default_carrier_code']);
					$row['is_manual_order']='0';
					$neworder = new OdOrder();
//					$neworder->findOne(['order_id'=>$row['order_id']]);
					$neworder->setAttributes($row,false);
					if($neworder->save(false)){
						echo 'od_order:'.$row['order_id']."save success!\n";
					}else{
						echo 'od_order:'.$row['order_id']."save failure!\n";
						continue;
					}
					
					//订单如果处理完成，开始处理订单商品
					$items=OdOrderItem2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($items)){
						$failure=0;//记录失败存储的记录数量，》0 则表示部分数据保存失败
						foreach ($items as $i){
							$item=new OdOrderItem();
//							$item->findOne(['order_id'=>$i['order_id'],'order_item_id'=>$i['order_item_id']]);
							$item->setAttributes($i,false);
							if ($item->save(false)){
								echo 'od_order_item for OrderID:'.$row['order_id']."save success!\n";
							}else{
								$failure++;
								echo 'od_order_item for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
						if ($failure>0){
							OdOrder::deleteAll(['order_id'=>$row['order_id']]);
							OdOrderItem::deleteAll(['order_id'=>$row['order_id']]);
							continue;
						}
					}
					
					//开始处理订单的发货数据
					$shipped=OdOrderShipped2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($shipped)){
						foreach ($shipped as $s){
							$ship= new OdOrderShipped();
//							$ship->findOne(['order_id'=>$i['order_id'],'id'=>$s['id']]);
							$ship->setAttributes($s,false);
							if ($ship->save(false)){
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save success!\n";
							}else{
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
					}
				}
			}catch(\Exception $e){
				echo $e->getMessage()."\n";
			}
		}
	}
	
	public function actionOrder2data1to2(){
		set_time_limit(0);
//		$maxuserid=UserBase::find()->where('puid = 0 and uid >240 and uid <=480')->max('uid');
		$maxuserid=UserBase::find()->where('puid = 0 and uid >240 and uid <=480')->select('uid')->asArray()->all();
		foreach ($maxuserid as $k=>$v){
			$i=$v['uid'];
			echo 'start connect subdb:--->'.$i."\n";
			try {
				 
	
				//开始处理订单od_order
				echo 'start deal od_order for user:'.$i."\n";
				$db = OdOrder2::getDb();
				$q = $db->createCommand('select * from '.OdOrder2::tableName().' order by order_id ASC')->query();
				while ($row=$q->read()){
					if ($row['order_status']>200){
						$row['order_status']=200;
					}
					unset($row['order_manual_id']);
					unset($row['default_shipping_method_code']);
					$row['default_warehouse_id ']=0;
					unset($row['default_carrier_code']);
					$row['is_manual_order']='0';
					$neworder = new OdOrder();
					//					$neworder->findOne(['order_id'=>$row['order_id']]);
					$neworder->setAttributes($row,false);
					if($neworder->save(false)){
						echo 'od_order:'.$row['order_id']."save success!\n";
					}else{
						echo 'od_order:'.$row['order_id']."save failure!\n";
						continue;
					}
						
					//订单如果处理完成，开始处理订单商品
					$items=OdOrderItem2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($items)){
						$failure=0;//记录失败存储的记录数量，》0 则表示部分数据保存失败
						foreach ($items as $i){
							$item=new OdOrderItem();
							//							$item->findOne(['order_id'=>$i['order_id'],'order_item_id'=>$i['order_item_id']]);
							$item->setAttributes($i,false);
							if ($item->save(false)){
								echo 'od_order_item for OrderID:'.$row['order_id']."save success!\n";
							}else{
								$failure++;
								echo 'od_order_item for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
						if ($failure>0){
							OdOrder::deleteAll(['order_id'=>$row['order_id']]);
							OdOrderItem::deleteAll(['order_id'=>$row['order_id']]);
							continue;
						}
					}
						
					//开始处理订单的发货数据
					$shipped=OdOrderShipped2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($shipped)){
						foreach ($shipped as $s){
							$ship= new OdOrderShipped();
							//							$ship->findOne(['order_id'=>$i['order_id'],'id'=>$s['id']]);
							$ship->setAttributes($s,false);
							if ($ship->save(false)){
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save success!\n";
							}else{
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
					}
				}
			}catch(\Exception $e){
				echo $e->getMessage()."\n";
			}
		}
	}
	
	public function actionOrder3data1to2(){
		set_time_limit(0);
//		$maxuserid=UserBase::find()->where('puid = 0 and uid >480 and uid <=720')->max('uid');
		$maxuserid=UserBase::find()->where('puid = 0 and uid >480 and uid <=720')->select('uid')->asArray()->all();
		foreach ($maxuserid as $k=>$v){
			$i=$v['uid'];
			echo 'start connect subdb:--->'.$i."\n";
			try {
				 
	
				//开始处理订单od_order
				echo 'start deal od_order for user:'.$i."\n";
				$db = OdOrder2::getDb();
				$q = $db->createCommand('select * from '.OdOrder2::tableName().' order by order_id ASC')->query();
				while ($row=$q->read()){
					if ($row['order_status']>200){
						$row['order_status']=200;
					}
					unset($row['order_manual_id']);
					unset($row['default_shipping_method_code']);
					$row['default_warehouse_id ']=0;
					unset($row['default_carrier_code']);
					$row['is_manual_order']='0';
					$neworder = new OdOrder();
					//					$neworder->findOne(['order_id'=>$row['order_id']]);
					$neworder->setAttributes($row,false);
					if($neworder->save(false)){
						echo 'od_order:'.$row['order_id']."save success!\n";
					}else{
						echo 'od_order:'.$row['order_id']."save failure!\n";
						continue;
					}
						
					//订单如果处理完成，开始处理订单商品
					$items=OdOrderItem2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($items)){
						$failure=0;//记录失败存储的记录数量，》0 则表示部分数据保存失败
						foreach ($items as $i){
							$item=new OdOrderItem();
							//							$item->findOne(['order_id'=>$i['order_id'],'order_item_id'=>$i['order_item_id']]);
							$item->setAttributes($i,false);
							if ($item->save(false)){
								echo 'od_order_item for OrderID:'.$row['order_id']."save success!\n";
							}else{
								$failure++;
								echo 'od_order_item for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
						if ($failure>0){
							OdOrder::deleteAll(['order_id'=>$row['order_id']]);
							OdOrderItem::deleteAll(['order_id'=>$row['order_id']]);
							continue;
						}
					}
						
					//开始处理订单的发货数据
					$shipped=OdOrderShipped2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($shipped)){
						foreach ($shipped as $s){
							$ship= new OdOrderShipped();
							//							$ship->findOne(['order_id'=>$i['order_id'],'id'=>$s['id']]);
							$ship->setAttributes($s,false);
							if ($ship->save(false)){
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save success!\n";
							}else{
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
					}
				}
			}catch(\Exception $e){
				echo $e->getMessage()."\n";
			}
		}
	}
	
	public function actionOrder4data1to2(){
		set_time_limit(0);
		$maxuserid=UserBase::find()->where('puid = 0 and uid >720 and uid <=960')->select('uid')->asArray()->all();
		foreach ($maxuserid as $k=>$v){
			$i=$v['uid'];
			echo 'start connect subdb:--->'.$i."\n";
			try {
				 
				//开始处理订单od_order
				echo 'start deal od_order for user:'.$i."\n";
				$db = OdOrder2::getDb();
				$q = $db->createCommand('select * from '.OdOrder2::tableName().' order by order_id ASC')->query();
				while ($row=$q->read()){
					if ($row['order_status']>200){
						$row['order_status']=200;
					}
					unset($row['order_manual_id']);
					unset($row['default_shipping_method_code']);
					$row['default_warehouse_id ']=0;
					unset($row['default_carrier_code']);
					$row['is_manual_order']='0';
					$neworder = new OdOrder();
					//					$neworder->findOne(['order_id'=>$row['order_id']]);
					$neworder->setAttributes($row,false);
					if($neworder->save(false)){
						echo 'od_order:'.$row['order_id']."save success!\n";
					}else{
						echo 'od_order:'.$row['order_id']."save failure!\n";
						continue;
					}
						
					//订单如果处理完成，开始处理订单商品
					$items=OdOrderItem2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($items)){
						$failure=0;//记录失败存储的记录数量，》0 则表示部分数据保存失败
						foreach ($items as $i){
							$item=new OdOrderItem();
							//							$item->findOne(['order_id'=>$i['order_id'],'order_item_id'=>$i['order_item_id']]);
							$item->setAttributes($i,false);
							if ($item->save(false)){
								echo 'od_order_item for OrderID:'.$row['order_id']."save success!\n";
							}else{
								$failure++;
								echo 'od_order_item for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
						if ($failure>0){
							OdOrder::deleteAll(['order_id'=>$row['order_id']]);
							OdOrderItem::deleteAll(['order_id'=>$row['order_id']]);
							continue;
						}
					}
						
					//开始处理订单的发货数据
					$shipped=OdOrderShipped2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($shipped)){
						foreach ($shipped as $s){
							$ship= new OdOrderShipped();
							//							$ship->findOne(['order_id'=>$i['order_id'],'id'=>$s['id']]);
							$ship->setAttributes($s,false);
							if ($ship->save(false)){
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save success!\n";
							}else{
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
					}
				}
			}catch(\Exception $e){
				echo $e->getMessage()."\n";
			}
		}
	}
	
	public function actionOrder5data1to2(){
		set_time_limit(0);
		$maxuserid=UserBase::find()->where('puid = 0 and uid >960 and uid <=1200')->select('uid')->asArray()->all();
		foreach ($maxuserid as $k=>$v){
			$i=$v['uid'];
			echo 'start connect subdb:--->'.$i."\n";
			try {
				 
	
				//开始处理订单od_order
				echo 'start deal od_order for user:'.$i."\n";
				$db = OdOrder2::getDb();
				$q = $db->createCommand('select * from '.OdOrder2::tableName().' order by order_id ASC')->query();
				while ($row=$q->read()){
					if ($row['order_status']>200){
						$row['order_status']=200;
					}
					unset($row['order_manual_id']);
					unset($row['default_shipping_method_code']);
					$row['default_warehouse_id ']=0;
					unset($row['default_carrier_code']);
					$row['is_manual_order']='0';
					$neworder = new OdOrder();
					//					$neworder->findOne(['order_id'=>$row['order_id']]);
					$neworder->setAttributes($row,false);
					if($neworder->save(false)){
						echo 'od_order:'.$row['order_id']."save success!\n";
					}else{
						echo 'od_order:'.$row['order_id']."save failure!\n";
						continue;
					}
						
					//订单如果处理完成，开始处理订单商品
					$items=OdOrderItem2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($items)){
						$failure=0;//记录失败存储的记录数量，》0 则表示部分数据保存失败
						foreach ($items as $i){
							$item=new OdOrderItem();
							//							$item->findOne(['order_id'=>$i['order_id'],'order_item_id'=>$i['order_item_id']]);
							$item->setAttributes($i,false);
							if ($item->save(false)){
								echo 'od_order_item for OrderID:'.$row['order_id']."save success!\n";
							}else{
								$failure++;
								echo 'od_order_item for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
						if ($failure>0){
							OdOrder::deleteAll(['order_id'=>$row['order_id']]);
							OdOrderItem::deleteAll(['order_id'=>$row['order_id']]);
							continue;
						}
					}
						
					//开始处理订单的发货数据
					$shipped=OdOrderShipped2::find()->where(['order_id'=>$row['order_id']])->asArray()->all();
					if (count($shipped)){
						foreach ($shipped as $s){
							$ship= new OdOrderShipped();
							//							$ship->findOne(['order_id'=>$i['order_id'],'id'=>$s['id']]);
							$ship->setAttributes($s,false);
							if ($ship->save(false)){
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save success!\n";
							}else{
								echo 'od_order_shipped for OrderID:'.$row['order_id']."save failure!\n";
							}
						}
					}
				}
			}catch(\Exception $e){
				echo $e->getMessage()."\n";
			}
		}
	}
	
	/**
	 * 将各个子库的订单的历史订单批量检测状态
	 * @author fanjs
	 */
	public function actionDocheck(){
		set_time_limit(0);
		$maxuserid=UserBase::find()->where('puid = 0 and uid >0 and uid <=1300')->select('uid')->asArray()->all();
		foreach ($maxuserid as $k=>$v){
			$i=$v['uid'];
			echo 'start connect subdb:--->'.$i."\n";
			try {
				 
		
				//开始处理订单od_order
				echo 'start deal od_order for user:'.$i."\n";
				$db = OdOrder2::getDb();
				$q = $db->createCommand('select * from '.OdOrder::tableName().' where exception_status=0 and create_time <1437973200  order by order_id ASC')->query();
				while ($row=$q->read()){
						$order=OdOrder::findOne($row['order_id']);
						switch ($row['order_source']){
							case 'ebay':
								if (200<=$row['order_status'] && $row['order_status']<=300){
									//将有发货时间的，或者没有发货时间但是付款时间是20天以前的订单归为已完成
									if ($order->delivery_time>0||($order->delivery_time==0&&$order->paid_time<=(time()-20*24*3600))){
										$order->order_status=OdOrder::STATUS_SHIPPED;
									}
								}
								break;
							case 'aliexpress':
								$aliOrder = AliexpressOrder::find()->where(['id'=>$row['order_source_order_id']])->select(['orderstatus','gmtpaysuccess','gmtcreate'])->asArray()->One();
								if (in_array($aliOrder['orderstatus'], array('PLACE_ORDER_SUCCESS'))){//未付款
									$order_status = 100;
								}elseif (in_array($aliOrder['orderstatus'], array('WAIT_SELLER_SEND_GOODS','RISK_CONTROL'))){//已付款
									$order_status = 200;
								}elseif (in_array($aliOrder['orderstatus'], array('IN_CANCEL'))){//申请取消
									$order_status = 600;
								}elseif (in_array($aliOrder['orderstatus'], array('SELLER_PART_SEND_GOODS','WAIT_BUYER_ACCEPT_GOODS','FUND_PROCESSING','WAIT_SELLER_EXAMINE_MONEY'))){
									$order_status = 400;
								}elseif (in_array($aliOrder['orderstatus'], array('FINISH'))){
									$order_status = 500;
								}elseif (in_array($aliOrder['orderstatus'], array('IN_ISSUE','IN_FROZEN'))){//需要挂起的订单
									//根据是否有付款时间判断是否曾经付过款
									if (strlen($aliOrder['gmtpaysuccess'])>10){
										$order_status = 200;
									}else{
										$order_status = 100;
									}
								}
								$order->order_status=$order_status;
								$order->order_source_status=$aliOrder['orderstatus'];
								$order->order_source_create_time=AliexpressInterface_Helper::transLaStrTimetoTimestamp($aliOrder['gmtcreate']);
								$order->paid_time=AliexpressInterface_Helper::transLaStrTimetoTimestamp($aliOrder['gmtpaysuccess']);
								echo $row['order_source_order_id']."\n";
								print_r($aliOrder);
								break;
							default:
								break;
						}
						$order->save(false);
				}
			}catch(\Exception $e){
				echo $e->getMessage()."\n";
			}
		}
	}
	
	/**
	 * ebay的item多属性拆分到映射表
	 * @author fanjs
	 */
	public function actionVariationmajor(){
		$maxuserid=UserBase::find()->where('puid = 0 and uid >0')->select('uid')->asArray()->all();
		foreach ($maxuserid as $k=>$v){
			$i=$v['uid'];
			echo 'start connect subdb:--->'.$i."\n";
			try {
				 
		
				//开始处理ebayitem
				echo 'start deal ebayitem for user:'.$i."\n";
				$db = EbayItem::getDb();
				$q = $db->createCommand('select * from '.EbayItem::tableName().' where listingstatus = "Active" and isvariation = 1')->query();
				$count = 0;
				while ($row=$q->read()){
					$itemid=$row['itemid'];
					if (strlen($itemid)==0){
						continue;
					}
					echo '正在处理数据'.$itemid."\n";
					$ei = EbayItem::findOne(['itemid'=>$itemid]);
// 					$ei->save();
// 					$ei->detail->save();
					$detail = $ei->detail;
					EbayitemHelper::SaveVariation($itemid, $detail->variation);
					$count++;
				}
				echo '总共处理'.$count.'条数据';
			}catch(\Exception $e){
				echo $e->getMessage()."\n";
			}
		}
	}
}