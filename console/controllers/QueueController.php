<?php
/**
 * 
 * @author witsionjs
 * @用于各个后台进程的管理
 */
namespace console\controllers;

use yii;
use yii\console\Controller;
use console\helpers\QueueGetorderHelper;
use eagle\models\SaasEbayAutosyncstatus;
use console\helpers\SaasEbayAutosyncstatusHelper;
use yii\base\Exception;
use eagle\models\SaasEbayUser;
use eagle\modules\util\helpers\AppPushDataHelper;
use eagle\modules\util\helpers\ImageCacherHelper;
use common\api\ebayinterface\getbestoffers;
use console\helpers\AliexpressHelper;
use eagle\models\QueueSyncshipped;
use eagle\modules\order\helpers\QueueShippedHelper;
use console\helpers\QueueAutoShippedHelper;
use eagle\modules\order\helpers\AmazonDeliveryApiHelper;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use common\api\ebayinterface\getsellerlist;
use common\api\ebayinterface\base;
use eagle\modules\listing\models\EbayItem;
use common\api\ebayinterface\shopping\getsingleitem;
use common\api\ebayinterface\getitem;
use common\api\ebayinterface\getsellerevents;
use eagle\modules\listing\models\EbayItemDetail;
use eagle\models\EbayAutoadditemset;
use eagle\modules\listing\models\EbayMuban;
use eagle\modules\listing\models\EbayLogMuban;
use common\api\ebayinterface\additem;
use common\helpers\Helper_Array;
use eagle\models\QueueItemprocess;
use eagle\modules\listing\helpers\EbayitemHelper;
use eagle\modules\listing\models\EbayLogItem;
use common\api\ebayinterface\reviseinventorystatus;
use common\api\paypalinterface\PaypalInterface_GetTransactionDetails;
use eagle\models\EbayCategory;
use common\api\ebayinterface\product\getcompatibilitysearchnames;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\listing\apihelpers\EbayItemApiHelper;

class QueueController extends Controller {
	private  $CurrentVersion = '2.0';
	
	/**
	 * 
	 * ./yii queue/get-version
	 */
	public function actionGetVersion(){
		echo "\n".$this->CurrentVersion."\n";
	}
	/**
	 * 统计这次同步需要同步的订单，并予以同步。(新订单)
	 */
	/*
	public function actionSyncQueueEbayOrder() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder ();
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		sleep ( 5 );
	}
	*/
	/**
	 * 统计这次同步需要同步的订单，并予以同步。(新订单)
	 * ./yii queue/sync-queue-ebay-order0
	 */
	public function actionSyncQueueEbayOrder0() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder (0);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	public function actionSyncQueueEbayOrder1() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder (1);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).'Error Message:' . $ex->getMessage ()." Line no ".$ex->getLine() . "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	public function actionSyncQueueEbayOrder2() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder (2);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).'Error Message:' . $ex->getMessage ()." Line no ".$ex->getLine() . "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	public function actionSyncQueueEbayOrder3() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder (3);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		sleep ( 5 );
	}
	
	public function actionSyncQueueEbayOrder4() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder (4);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).'Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		sleep ( 5 );
	}
	/**
	 * 统计这次同步需要同步的订单，并予以同步。(旧订单)
	 * ./yii queue/sync-queue-ebay2-order
	 */
	/*
	public function actionSyncQueueEbay2Order() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder2 ();
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		sleep ( 5 );
	}
	*/
	/**
	 * 统计这次同步需要同步的订单，并予以同步。(旧订单) 3 进程
	 * ./yii queue/sync-queue-ebay2-order0
	 */
	public function actionSyncQueueEbay2Order0() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder2 (0);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	public function actionSyncQueueEbay2Order1() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder2 (1);
			QueueGetorderHelper::setEbayOrderPriority();
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	public function actionSyncQueueEbay2Order2() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder2 (2);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	public function actionSyncQueueEbay2Order3() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder2 (3);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	public function actionSyncQueueEbay2Order4() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder2 (4);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	/**
	 * 统计这次同步需要同步的订单，并予以同步。(特殊订单) 
	 * ./yii queue/sync-queue-ebay9-order
	 */
	public function actionSyncQueueEbay9Order() {
		try {
			QueueGetorderHelper::autoRequestEbayOrder9 ();
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	/**
	 * 统计这次同步需要同步的订单，并予以同步。(客户手工同步订单)
	 * ./yii queue/sync-queue-ebay8-order
	 */
	public function actionSyncQueueEbay8Order() {
		try {
			
			$start_time = date('Y-m-d H:i:s');
			$comment = "cron service runnning for ".(__function__)." at $start_time";
			echo $comment;
			// 		\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
			do{
			
				echo "\n v".$this->CurrentVersion ." ".(__function__);
				$rtn = QueueGetorderHelper::autoRequestEbayOrder8 ();
			
				// 			$rtn = WishHelper::cronQueueHandleFanben();
			
				//如果没有需要handle的request了，退出
				if ($rtn ==0 ){
					echo "\n no reqeust pending, sleep for 10 sec";
					sleep(10);
			
				}
			
				$auto_exit_time = 25 + rand(1,10); // 25 - 35 minutes to leave
				// 			$auto_exit_time = 1 + rand(1,2); // 25 - 35 minutes to leave
				$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
				echo " \n exittime =$auto_exit_time ,start_time=$start_time,deadline =".$half_hour_ago;
			
			}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
			
			//write the memery used into it as well.
			$memUsed = floor (memory_get_usage() / 1024 / 1024);
			$comment =  "cron service stops for ".(__function__)." at ".date('Y-m-d H:i:s');
			$comment .= " - RAM Used: ".$memUsed."M";
			echo $comment;
			
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","SyncQueueOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		
	}
	
	/**
	 * 找出这半小时需要同步的订单队列，将需要同步的订单的账号插入到队列中去
	 * ./yii queue/cron-request-order
	 */
	/*
	public function actionCronRequestOrder() {
		try {
			SaasEbayAutosyncstatusHelper::AutoSyncOrder ();
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","CronRequestOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		sleep ( 5 );
	}
	*/
	/**
	 * 找出这半小时需要同步的订单队列，将需要同步的订单的账号插入到队列中去
	 * ./yii queue/cron-request-order0
	 */
	public function actionCronRequestOrder0() {
		try {
			SaasEbayAutosyncstatusHelper::AutoSyncOrder (0);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","CronRequestOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	public function actionCronRequestOrder1() {
		try {
			SaasEbayAutosyncstatusHelper::AutoSyncOrder (1);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","CronRequestOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	public function actionCronRequestOrder2() {
		try {
			SaasEbayAutosyncstatusHelper::AutoSyncOrder (2);
		} catch ( \Exception $ex ) {
			echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","CronRequestOrder failure:".print_r($ex->getMessage())],"edb\global");
		}
		echo "\n then sleep 5 s...";
		sleep ( 5 );
	}
	
	/**
	 * dzt20191106 GetSellerTransactions 漏单问题，添加的每小时跑一下拉5小时之前的订单。
	 * 只插入queue不更新，释放正常同步任务每次同步5小时导致queue累积问题
	 * ./yii queue/cron-request-order-for-five-hours
	 */
	public function actionCronRequestOrderForFiveHours() {
	    try {
	        SaasEbayAutosyncstatusHelper::AutoSyncOrder5Hours();
	    } catch ( \Exception $ex ) {
	        echo "\n".(__function__).' Error Message:' . $ex->getMessage () ." Line no ".$ex->getLine(). "\n";
	    }
	    echo "\n then sleep 5 s...";
	    sleep ( 5 );
	}
	
	/**
	 * 同步站内信
	 */
	public function actionCronRequestMyMessage() {
		try {
			SaasEbayAutosyncstatusHelper::AutoSyncMessage ();
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","CronRequestMyMessage failure:".print_r($ex->getMessage())],"edb\global");
		}
		sleep ( 2 );
	}
	
	/**
	 * 同步feedback
	 * @invoking					./yii queue/cron-request-ebay-feedback
	 */
	public function actionCronRequestEbayFeedback() {
		$start_time = date('Y-m-d H:i:s');
		echo "start CronRequestEbayFeedback at $start_time";
		
		try {
			SaasEbayAutosyncstatusHelper::AutoSyncFeedback ();
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
			//	\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","CronRequestMyFeedback failure:".print_r($ex->getMessage())],"edb\global");
		}
		$end_time = date('Y-m-d H:i:s');
		echo "end CronRequestEbayFeedback at $end_time";
		sleep ( 2 );
	}
	
	/**
	 * 同步用户的bestoffer请求
	 * @author fanjs
	 * */
	function actionCronSyncBestoffer(){
		try {
			$HQs = SaasEbayAutosyncstatus::findBySql('select * from saas_ebay_autosyncstatus where status = 0 and type = 8 ORDER BY lastrequestedtime ASC')->all();
			if (count ( $HQs ) == 0) {
				exit ();
			}
			foreach ( $HQs as $HQ ) {
				$HQ->lastrequestedtime = time ();
				$HQ->status_process = 1;
				$HQ->status = 1;
				$HQ->save ();
	
				\Yii::info ('start sync bestoffer for:'.$HQ->selleruserid);
				$ebayuser = SaasEbayUser::find()->where(['selleruserid'=>$HQ->selleruserid])->one();
 
				set_time_limit ( 0 );
	
				getbestoffers::syncBestOffers($HQ->selleruserid);
				$HQ->status=2;
				$HQ->save();
			}
		} catch ( Exception $ex ) {
			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Background","CronSyncBestoffer failure:".print_r($ex->getMessage())],"edb\global");
		}
		sleep ( 5 );
	}
	
	/**
	 * 自动同步账号的Item
	 *
	 * @author fanjs
	 * ./yii queue/cron-request-item
	 */
	function actionCronRequestItem() {
		try {
			$startRunTime=time();
			$keepRunningMins=50+rand(1,10);//分钟为单位,
			do{
				$tmptime = explode(" ",microtime());
				$starttime = round($tmptime[0]*1000+$tmptime[1]*1000);

				ini_set ( 'display_errors', 'On' );
				$HQs = SaasEbayAutosyncstatus::findBySql("select * from `saas_ebay_autosyncstatus` where status = 1 and type = 7 and status_process<>1 and (next_execute_time<".time()." or next_execute_time IS NULL) ORDER BY lastrequestedtime ASC limit 10")->all();
				// $HQs = SaasEbayAutosyncstatus::findBysql("select * from `saas_ebay_autosyncstatus` where status = 1 and type = 7 and status_process<>1 and (next_execute_time<1486701069 or next_execute_time IS NULL) ORDER BY lastrequestedtime ASC limit 1000")->all();
				if (count ( $HQs ) == 0) {
					exit ();
				}

				$astart_time=time();
				echo "==========AAAAAAAAAA count HQs = ".count ( $HQs )." start_time=".$astart_time."\n";
				//获取活跃ebay用户的ebay_uid
				$activeEbayUidArr=EbayItemApiHelper::getEbayActiveUsersList();
				// foreach ( $HQs as $HQ ) {
				// 	echo $HQ->selleruserid."\n";
				// 	// echo $HQ->type."\n";
				// 	// echo $HQ->next_execute_time."\n";
				// }
				foreach ( $HQs as $HQ ) {
					if (time () - $HQ->lastrequestedtime < 60 * 60) {
						\Yii::info ( 'Fast Sync. Skip\n',"file" );
						echo "Fast Sync. Skip\n";
						continue;
					}
					// if ($HQ->selleruserid !='vipwitsionstore') {
					// 	//echo "not=\n";
					// 	continue;
					// }
					$HQ->lastrequestedtime = time ();
					$HQ->status_process = 1;
					if (true) {
						$HQ->next_execute_time=time()+6*3600;//活跃用户6小时后自动拉取
						echo "-active selleruserid=".$HQ->selleruserid." next_execute_time=".$HQ->next_execute_time."\n";
					}else{
						//非活跃用户7天后的0点到6点之间拉取
						$HQ->next_execute_time=floor(time()/3600)*3600+7*24*3600+rand(0,6*3600);
						echo "-not-active selleruserid=".$HQ->selleruserid." next_execute_time=".$HQ->next_execute_time."\n";
					}
					$HQ->save ();
		
					$eu = SaasEbayUser::find()->where ( ['selleruserid'=>$HQ->selleruserid] )->andWhere('expiration_time>='.time())->one();
					
					if (empty($eu)){
						\yii::info($HQ->selleruserid.' cant be found.',"file");
						echo $HQ->selleruserid.' cant be found.'."or token timeout expiration_time=".$eu->expiration_time."\n";
						continue;
					}
					// echo "expiration_time=".$eu->expiration_time."\n";
					\Yii::info( $eu->selleruserid,"file");
 
					set_time_limit ( 0 );
					echo "getsellerlist\n";
					$api = new getsellerlist();
					$api->resetConfig($eu->DevAcccountID);
					$api->eBayAuthToken = $eu->token;
		
					$start = base::dateTime(time() - 20 * 24 * 3600 );
					$end = base::dateTime ( time () + 50 * 24 * 3600 );
					$api->_before_request_xmlarray ['OutputSelector'] = array (
							'PaginationResult',
							'ItemArray.Item.Quantity',
							'ItemArray.Item.Variations.Variation',
							'ItemArray.Item.SKU',
							'ItemArray.Item.SellingStatus',
							'ItemArray.Item.ItemID',
							'ItemArray.Item.Site',
							'ItemArray.Item.StartPrice',
							'ItemArray.Item.Storefront.StoreCategoryID',
							'ItemArray.Item.Storefront.StoreCategory2ID',
							'ItemArray.Item.Location',
							'ItemArray.Item.ListingDetails.EndTime'
					);
					$currentPage = 0;
					$activeItemID = array ();
					do {
						$currentPage ++;
						$pagination = array (
								'EntriesPerPage' => 50,
								'PageNumber' => $currentPage
						);
						set_time_limit ( 0 );
						try {
							$r = $api->api ( $pagination, 'ReturnAll', $start, $end );
						} catch ( Exception $ex ) {
							\Yii::info ( print_r ( $pagination, true ) . print_r ( $r, true ) ,"file");
							$currentPage --;
							continue;
						}
						set_time_limit ( 0 );
						if ($api->responseIsFailure ()) {
							if ($r ['Errors'] ['ErrorCode'] == 340) {
								\Yii::info ( 'Page Out',"file" );
								echo 'Page Out';
								break;
							}
							if ($r ['Errors'] ['ErrorCode'] == 932) {
								\Yii::info ( 'Auth token is hard expired',"file" );
								echo "selleruserid=".$HQ->selleruserid.", Auth token is hard expired\n";
								break;
							}
							\Yii::info ( print_r ( $pagination, true ) . print_r ( $r, true ) ,"file");
							echo print_r ( $pagination, true ) . print_r ( $r, true );
							$currentPage --;
							continue;
						}
						if (isset ( $r ['ItemArray'] ['Item'] )) {
							$itemArr = $r ['ItemArray'] ['Item'];
							if (isset ( $itemArr ['ItemID'] )) {
								$itemArr = array (
										$itemArr
								);
							}
							$_sitemap = Helper_Array::toHashmap($itemArr, 'ItemID','Site');
							// 开始分析Item
							foreach ( $itemArr as $row ) {
								$_itemid = $row ['ItemID'];
								$_sku = isset($row ['SKU'])?$row ['SKU']:'';
								$_price = $row ['SellingStatus'] ['CurrentPrice'];
								$_quantity = $row ['Quantity'];
								$_site = isset($row['Site'])?$row['Site']:'';
									
								\Yii::info ( $eu->selleruserid . '-' . $_itemid );
								echo $eu->selleruserid . '-' . $_itemid."\n";
								// 统计在线item
								if ($row ['SellingStatus'] ['ListingStatus'] == 'Active' && strtotime ( $row ['ListingDetails'] ['EndTime'] ) > time ()) {
									$active_itemids [] = $_itemid;
								}
								// 多属性 sku
								if (isset ( $row ['Variations'] )) {
									$variation = $row ['Variations'] ['Variation'];
									if (isset ( $variation ['StartPrice'] )) {
										$variation = array (
												$variation
										);
									}
									$row ['Variations'] ['Variation'] = $variation;
								}
								$ei = EbayItem::find()->where ( ['itemid'=>$_itemid] )->one ();
	//							if (empty($ei)|| strlen ( $ei->itemtitle ) == 0) {
									\Yii::info( 'get new' );
									echo "get new\n";
									$getitem_api = new getsingleitem();
									try {
										set_time_limit ( 0 );
										$_r = $getitem_api->apiItem($_itemid);
									} catch ( \Exception $ex ) {
										\Yii::error(print_r($ex->getMessage()));
									}
									if (!$getitem_api->responseIsFail) {
										\Yii::info( 'get success' ,"file");
										echo "-get success,start save-\n";
										// 保存 同步状态
										$getitem_api->save ( $_r,$HQ, $_sitemap);
									} else {
										\Yii::info( 'get failed' );
										echo "-get failed,start save-";
										$ei->setAttributes ( array (
												'uid' => $eu->uid,
												'selleruserid' => $eu->selleruserid,
												'itemid' => $_itemid,
												'site'=>$_site,
												'quantity' => $_quantity,
												'listingstatus' => @$row ['SellingStatus'] ['ListingStatus'],
												'sku' => $_sku,
												'startprice' => @$row ['StartPrice'],
												'buyitnowprice' => @$row ['BuyItNowPrice'],
												'endtime' => strtotime ( $row ['ListingDetails'] ['EndTime'] )
										) );
										$detail = EbayItemDetail::find()->where(['itemid'=>$_itemid])->one();
										if (empty($detail)){
											$detail = new EbayItemDetail();
										}
										$detail->itemid=$_itemid;
										// 保存location
										if (! empty ( $row ['Location'] )) {
											if (is_array ( $row ['Location'] )) {
												$detail->location = reset ( $row ['Location'] );
											}
										}
										$detail->storecategoryid = @$row ['Storefront'] ['StoreCategoryID'];
										$detail->storecategory2id = @$row ['Storefront'] ['StoreCategory2ID'];
											
										$detail->sellingstatus = $row ['SellingStatus'];
										if ($_price) {
											$ei->currentprice = $_price;
										}
										/**
										 * sku 不是最新的
										 */
										// 保存多属性库存状态
										if (isset ( $row ['Variations'] )) {
											$t_variations = $detail->variation;
											if ($row ['Variations'] ['Variation']) {
												$t_variations ['Variation'] = $row ['Variations'] ['Variation'];
											}
											if (isset ( $row ['Variations'] ['Pictures'] )) {
												$t_variations ['Pictures'] = $row ['Variations'] ['Pictures'];
											}
											if (isset ( $row ['Variations'] ['VariationSpecificsSet'] )) {
												$t_variations ['VariationSpecificsSet'] = $row ['Variations'] ['VariationSpecificsSet'];
											}
		
											$detail->variation = $t_variations;
										}
										$ei->save (false);
										$detail->save(false);
									echo "-save completed\n";
	// 							} else {
	// 								// 更新item状态
	// 								\Yii::info( 'update item' );
	// 								echo 'update item';
	// 								$row ['Seller'] ['UserID'] = $ei->selleruserid;
		
	// 								$saveapi = new getitem();
	// 								$saveapi->save ( $row );
	 							}
							}
						}
					} while (( (empty ( $r ['PaginationResult'] ) && $currentPage != 0) || $r ['PaginationResult'] ['TotalNumberOfPages'] > $currentPage )&& @$r['Ack']!='Failure');
					echo '-all item has updated!'."\n";
					// 同步下架Item
					// GetSellerEvent
					if (!is_null($HQ->lastprocessedtime)){//is_null过滤掉第一次绑定时同步，不需要同步下架的item
						$apiD = new getsellerevents();
						$apiD->resetConfig($eu['DevAcccountID']);
						$apiD->eBayAuthToken = $eu ['token'];
						$r = $apiD->api ( array (
								'EndTimeFrom' => base::dateTime ( $HQ->lastprocessedtime )
						) );
						if (isset ( $r ['ItemArray'] ['Item'] ) && count ( $r ['ItemArray'] ['Item'] )) {
							if (isset ( $r ['ItemArray'] ['Item'] ['ItemID'] )) {
								$r ['ItemArray'] ['Item'] = array (
										$r ['ItemArray'] ['Item']
								);
							}
							foreach ( $r ['ItemArray'] ['Item'] as $row ) {
								$ei = EbayItem::find()->where ( ['itemid'=> $row ['ItemID']] )->one();
								if (empty($ei)) {
									\Yii::info( 'skip new item:' . $row ['ItemID'],"file" );
									continue;
								}
								$ei->setAttributes ( array (
										'itemid' => $row ['ItemID'],
										'starttime' => strtotime ( $row ['ListingDetails'] ['StartTime'] ),
										'endtime' => strtotime ( $row ['ListingDetails'] ['EndTime'] ),
										'quantity' => @$row ['Quantity'],
										'quantitysold' => $row ['SellingStatus'] ['QuantitySold'],
										'listingstatus' => $row ['SellingStatus'] ['ListingStatus']
								) );
								\Yii::info( 'End ItemID:' . $row ['ItemID'] );
								// 保存多属性库存状态
								if (isset ( $row ['Variations'] )) {
									// $ei->detail->variation = $row ['Variations'];
									$detail = EbayItemDetail::find()->where(['itemid'=>$row ['ItemID']])->one();
									if (empty($detail)){
										\Yii::info( 'skip new item:' . $row ['ItemID'] );
										continue;
									}
									$Dvariation = $detail->variation;
									if (isset ( $Dvariation ['Variation'] )) {
										$Dvariation ['Variation'] = $row ['Variations'] ['Variation'];
										$detail->variation = $Dvariation;
									} else {
										// 取回 variation
										// Queue_Itemprocess::AddGetItem($row['ItemID'],$ei->selleruserid);
									}
									$detail->save(false);
								}
								$ei->save ();
							}
						}
					}
		
					// 同步在线数量
					set_time_limit ( 0 );
		
					// 同步完成
					$HQ->lastprocessedtime = time ();
					$HQ->status_process = 2;
					if (true) {
						$HQ->next_execute_time=time()+6*3600;//活跃用户6小时后自动拉取
						echo "=active selleruserid=".$HQ->selleruserid." next_execute_time=".$HQ->next_execute_time."\n";
					}else{
						//非活跃用户7天后的0点到6点之间拉取
						$HQ->next_execute_time=floor(time()/3600)*3600+7*24*3600+rand(0,6*3600);
						echo "=not-active selleruserid=".$HQ->selleruserid." next_execute_time=".$HQ->next_execute_time."\n";
					}
					$HQ->save ();
					// 回收旧Item
					EbayItem::deleteAll('itemid < 100000');
					echo $HQ->selleruserid." has completed sync work!\n";
		
				}
				// 2个小时都未处理完成的,状态 重置为0
				SaasEbayAutosyncstatus::updateAll(['status_process'=>0],'type=7 And status=1 And status_process=1 And lastrequestedtime <'.time () - 7200);
				$aend_time=time();
				echo "==========AAAAAAAAAA end_time=".$aend_time." used =".($aend_time-$astart_time)."\n";
				// exit ();

				$nowTime=time();
			}while(($startRunTime+60*$keepRunningMins > $nowTime));
			// exit ();
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
		sleep ( 5 );
	}


	
	
	// ############################速卖通脚本begin##########################################
	// 第一步：同步速卖通订单列表
	/**
	 * PLACE_ORDER_SUCCESS:等待买家付款; 1
	 */
	function actionAliGetOrderList1() {
		try {
			AliexpressHelper::getOrderList('PLACE_ORDER_SUCCESS',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * IN_CANCEL:买家申请取消; 2
	 */
	function actionAliGetOrderList2() {
		try {
			AliexpressHelper::getOrderList('IN_CANCEL',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * WAIT_SELLER_SEND_GOODS:等待您发货; 3
	 */
	function actionAliGetOrderList3() {
		try {
			AliexpressHelper::getOrderList('WAIT_SELLER_SEND_GOODS',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * SELLER_PART_SEND_GOODS:部分发货; 4
	 */
	function actionAliGetOrderList4() {
		try {
			AliexpressHelper::getOrderList('SELLER_PART_SEND_GOODS',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * WAIT_BUYER_ACCEPT_GOODS:等待买家收货; 5
	 */
	function actionAliGetOrderList5() {
		try {
			AliexpressHelper::getOrderList('WAIT_BUYER_ACCEPT_GOODS',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * FUND_PROCESSING:买家确认收货后，等待退放款处理的状态; 6
	 */
	function actionAliGetOrderList6() {
		try {
			AliexpressHelper::getOrderList('FUND_PROCESSING',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * FINISH:已结束的订单; 7
	 */
	function actionAliGetOrderList7() {
		try {
			AliexpressHelper::getOrderList('FINISH',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * 订单状态：
	 * IN_ISSUE:含纠纷的订单; 8
	 */
	function actionAliGetOrderList8() {
		try {
			AliexpressHelper::getOrderList('IN_ISSUE',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * IN_FROZEN:冻结中的订单; 9
	 */
	function actionAliGetOrderList9() {
		try {
			AliexpressHelper::getOrderList('IN_FROZEN',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * WAIT_SELLER_EXAMINE_MONEY:等待您确认金额; 10
	 */
	function actionAliGetOrderList10() {
		try {
			AliexpressHelper::getOrderList('WAIT_SELLER_EXAMINE_MONEY',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * RISK_CONTROL:订单处于风控24小时中，从买家在线支付完成后开始，持续24小时。11
	 */
	function actionAliGetOrderList11() {
		try {
			AliexpressHelper::getOrderList('RISK_CONTROL',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * PLACE_ORDER_SUCCESS:等待买家付款; 1
	 */
	function actionAliGetOrder1() {
		try {
			AliexpressHelper::getOrder('PLACE_ORDER_SUCCESS',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * IN_CANCEL:买家申请取消; 2
	 */
	function actionAliGetOrder2() {
		try {
			AliexpressHelper::getOrder('IN_CANCEL',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	
	/**
	 * WAIT_SELLER_SEND_GOODS:等待您发货; 3
	 */
	function actionAliGetOrder3() {
		try {
			AliexpressHelper::getOrder('WAIT_SELLER_SEND_GOODS',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * SELLER_PART_SEND_GOODS:部分发货; 4
	 */
	function actionAliGetOrder4() {
		try {
			AliexpressHelper::getOrder('SELLER_PART_SEND_GOODS',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * WAIT_BUYER_ACCEPT_GOODS:等待买家收货; 5
	 */
	function actionAliGetOrder5() {
		try {
			AliexpressHelper::getOrder('WAIT_BUYER_ACCEPT_GOODS',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * FUND_PROCESSING:买家确认收货后，等待退放款处理的状态; 6
	 */
	function actionAliGetOrder6() {
		try {
			AliexpressHelper::getOrder('FUND_PROCESSING',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * FINISH:已结束的订单; 7
	 */
	function actionAliGetOrder7() {
		try {
			AliexpressHelper::getOrder('FINISH',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * IN_ISSUE:含纠纷的订单; 8
	 */
	function actionAliGetOrder8() {
		try {
			AliexpressHelper::getOrder('IN_ISSUE',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * IN_FROZEN:冻结中的订单; 9
	 */
	function actionAliGetOrder9() {
		try {
			AliexpressHelper::getOrder('IN_FROZEN',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * WAIT_SELLER_EXAMINE_MONEY:等待您确认金额; 10
	 */
	function actionAliGetOrder10() {
		try {
			AliexpressHelper::getOrder('WAIT_SELLER_EXAMINE_MONEY',1800);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	/**
	 * RISK_CONTROL:订单处于风控24小时中，从买家在线支付完成后开始，持续24小时。11
	 */
	function actionAliGetOrder11() {
		try {
			AliexpressHelper::getOrder('RISK_CONTROL',86400);
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	
	/*
	 * @author million 用于同步速卖通刊登时所用的类目等基本数据信息 *
	*/
	function actionCronSyncBaseAliexpressInfo() {
		try {
			Yii::log ( 'start sync aliexpress category!------>' );
			echo "start sync aliexpress category!------>\n";
			$selleruserid = 'cn1510671045';
			SaasAliexpressAutosyncHelper::AutoSyncAliexpressCategory($selleruserid,array(0));
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}
	// ##############################速卖通脚本end########################################
	
	
	//***********************************  标记发货脚本 start ***********************************//
	/**
	 * 标记发货脚本
	 * @author million
	 * ./yii queue/cron-auto-shipped
	 */
	function actionCronAutoShipped(){
		try {
			$connection=\Yii::$app->db;
			$command=$connection->createCommand('select `id`,`selleruserid`,`order_source`,`order_source_order_id` from  `queue_syncshipped` where `status` = 0 and order_source not in ("ebay","cdiscount","priceminister","bonanza","amazon","aliexpress")  order by `created` ASC');
			$dataReader=$command->query();
			while(($row=$dataReader->read())!==false) {
				echo "v".$this->CurrentVersion." order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				$QssObj = QueueSyncshipped::findOne($row['id']);
				$success = false;
				$delivery_time = -1;
				switch ($row['order_source']){
					case 'ebay':
						//QueueAutoShippedHelper::EbayShipped($QssObj);
						break;
					case 'amazon':
						//AmazonApiHelper::AmazonShipped($QssObj);
						list($success,$delivery_time)=AmazonApiHelper::AmazonShipped($QssObj);
						break;
					case 'aliexpress':
						$success = QueueAutoShippedHelper::AliexpressShipped($QssObj);
						break;
					case 'wish':
						$success = QueueAutoShippedHelper::WishShipped($QssObj);
						break;
					case 'dhgate':
						$success = QueueAutoShippedHelper::DhgateShipped($QssObj);
						break;
					case 'cdiscount':
						//QueueAutoShippedHelper::CdiscountShipped($QssObj);
						break;
					case 'lazada':
						$success = QueueAutoShippedHelper::LazadaShipped($QssObj);
						break;
					case 'linio':
						$success = QueueAutoShippedHelper::LinioShipped($QssObj);
						break;
					case 'jumia':
						$success = QueueAutoShippedHelper::JumiaShipped($QssObj);
						break;
					case 'ensogo':
						$success = QueueAutoShippedHelper::EnsogoShipped($QssObj);
						break;
					case 'newegg':
						$success = QueueAutoShippedHelper::NeweggShipped($QssObj);
						break;
    				case 'shopee':
    				    $success = QueueAutoShippedHelper::ShopeeShipped($QssObj);
    				    break;
						
					default:break;
				}
				
				
// 				OrderApiHelper::setOrderShippingStatus($QssObj->order_source_order_id, $success,$delivery_time);
				
				if ($success){//amazon 为异步标记， 所以不在这里回写
					$syncShippedStatus = "C";
					$ActivePlatformList = ['aliexpress', 'wish' , 'dhgate','lazada' , 'linio' , 'jumia','newegg','shopee'];
				}else{
					$syncShippedStatus = "F";
					//amazon 插入队列失败也需要写上
					$ActivePlatformList = ['aliexpress', 'wish' , 'dhgate','lazada' , 'linio' , 'jumia' , 'amazon','newegg','shopee'];
					echo "failure to sync ship then status set F  order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				}
				
				if (in_array($row['order_source'] ,$ActivePlatformList)){
					// 更新订单 虚拟发货 状态 start
					$syncRT = OrderApiHelper::setOrderSyncShippedStatus(OdOrderShipped::findOne($QssObj->osid)->order_id, $syncShippedStatus, $delivery_time);
					// 更新订单 虚拟发货 状态 end
					echo $row['order_source_order_id']." sync status= $syncShippedStatus ".print_r($syncRT,1);
				}
				
			}
		} catch ( \Exception $ex ) {
			echo 'Error File:' . $ex->getFile().' , Error Line:' . $ex->getLine() .' , Error Message:' . $ex->getMessage () . "\n";
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * amazon  标记发货 同步   脚本
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii queue/cron-amazon-auto-shipped
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/05/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCronAmazonAutoShipped(){
		echo "\n v".$this->CurrentVersion;
		QueueAutoShippedHelper::autoShip('amazon');
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * amazon  标记发货 同步   脚本
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii queue/cron-amazon-auto-shipped-v2
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/05/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCronAmazonAutoShippedV2(){
		$start_time = date('Y-m-d H:i:s');
		$comment = "cron service runnning for ".(__function__)." at $start_time";
		echo $comment;
		// 		\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
		do{
				
			echo "\n v".$this->CurrentVersion ." ".(__function__);
			$rtn = QueueAutoShippedHelper::autoShipV2('amazon');
				
			// 			$rtn = WishHelper::cronQueueHandleFanben();
	
			//如果没有需要handle的request了，退出
			if ($rtn['success'] and $rtn['message']=="n/a"){
				echo "\n no reqeust pending, sleep for 10 sec";
				sleep(10);
				
			}
	
			$auto_exit_time = 25 + rand(1,10); // 25 - 35 minutes to leave
// 			$auto_exit_time = 1 + rand(1,2); // 25 - 35 minutes to leave
			$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
			echo " \n exittime =$auto_exit_time ,start_time=$start_time,deadline =".$half_hour_ago;
	
		}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
	
		//write the memery used into it as well.
		$memUsed = floor (memory_get_usage() / 1024 / 1024);
		$comment =  "cron service stops for ".(__function__)." at ".date('Y-m-d H:i:s');
		$comment .= " - RAM Used: ".$memUsed."M";
		echo $comment;
		// 		\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * aliexpress  标记发货 同步   脚本
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii queue/cron-aliexpress-auto-shipped
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/05/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCronAliexpressAutoShipped(){
		echo "\n v".$this->CurrentVersion;
		QueueAutoShippedHelper::autoShip('aliexpress');
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay 标记发货 同步   脚本
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii queue/cron-ebay-auto-shipped
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCronEbayAutoShipped(){
		try {
			$connection=\Yii::$app->db;
			$command=$connection->createCommand('select `id`,`selleruserid`,`order_source`,`order_source_order_id` from  `queue_syncshipped` where `status` = 0  and order_source = "ebay" order by `created` ASC');
			$dataReader=$command->query();
			while(($row=$dataReader->read())!==false) {
				echo "v".$this->CurrentVersion." order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				$QssObj = QueueSyncshipped::findOne($row['id']);
				$success = false;
				switch ($row['order_source']){
					case 'ebay':
						//echo $row['order_source']."shipped ";
						$success = QueueAutoShippedHelper::EbayShipped($QssObj);
						break;
					
					default:break;
				}
				
				//OrderApiHelper::setOrderShippingStatus($QssObj->order_source_order_id, $success);
				// 更新订单 虚拟发货 状态 start
				if ($success){
					$syncShippedStatus = "C";
				}else{
					$syncShippedStatus = "F";
					echo "failure to sync ship then status set F  order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				}
				$syncRT = OrderApiHelper::setOrderSyncShippedStatus(OdOrderShipped::findOne($QssObj->osid)->order_id, $syncShippedStatus);
				// 更新订单 虚拟发货 状态 end
				echo $row['order_source_order_id']." sync status= $syncShippedStatus ".print_r($syncRT,1);
		
			}
		} catch ( \Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}//end of actionCronEbayAutoShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * cdiscount 标记发货 同步   脚本
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii queue/cron-cdiscount-auto-shipped
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/09				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCronCdiscountAutoShipped(){
		try {
			//echo __FUNCTION__." into ";
			$connection=\Yii::$app->db;
			$command=$connection->createCommand('select `id`,`selleruserid`,`order_source`,`order_source_order_id` from  `queue_syncshipped` where `status` = 0  and order_source = "cdiscount" order by `created` ASC');
			$dataReader=$command->query();
			while(($row=$dataReader->read())!==false) {
				echo "\n start : order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				$QssObj = QueueSyncshipped::findOne($row['id']);
				$success = false;
				switch ($row['order_source']){
					case 'cdiscount':
						//echo $row['order_source']."shipped ";
						$success = QueueAutoShippedHelper::CdiscountShipped($QssObj);
						break;
							
					default:break;
				}
 
				
				// 更新订单 虚拟发货 状态 start
				if ($success){
					$syncShippedStatus = "C";
				}else{
					$syncShippedStatus = "F";
					echo "failure to sync ship then status set F  order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				}
				$order = OdOrderShipped::findOne($QssObj->osid);
				if(!empty($order)){
					$order_id = $order->order_id;
					echo "\n start to setOrderSyncShippedStatus for uid: ".$QssObj['uid'].", order_id : ".$order_id;
					OrderApiHelper::setOrderSyncShippedStatus($order_id, $syncShippedStatus);
				}
				// 更新订单 虚拟发货 状态 end
			}
		} catch ( \Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}//end of actionCronEbayAutoShipped
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * priceminister 标记发货 同步   脚本
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii queue/cron-priceminister-auto-shipped
	 +---------------------------------------------------------------------------------------------
	 * log			name		date				note
	 * @author		lzhl		2016/04/06			初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCronPriceministerAutoShipped(){
		try {
			$connection=\Yii::$app->db;
			$command=$connection->createCommand('select `id`,`selleruserid`,`order_source`,`order_source_order_id` from  `queue_syncshipped` where `status` = 0  and order_source = "priceminister" order by `created` ASC');
			$dataReader=$command->query();
			while(($row=$dataReader->read())!==false) {
				echo "order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				$QssObj = QueueSyncshipped::findOne($row['id']);
				$success = false;
				switch ($row['order_source']){
					case 'priceminister':
						//echo $row['order_source']."shipped ";
						$success = QueueAutoShippedHelper::PriceministerShipped($QssObj);
						break;
							
					default:break;
				}
 
				
				// 更新订单 虚拟发货 状态 start
				if ($success){
					$syncShippedStatus = "C";
				}else{
					$syncShippedStatus = "F";
					echo "failure to sync ship then status set F  order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
				}
				OrderApiHelper::setOrderSyncShippedStatus(OdOrderShipped::findOne($QssObj->osid)->order_id, $syncShippedStatus);
				// 更新订单 虚拟发货 状态 end
	
			}
		} catch ( \Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	}//end of actionCronEbayAutoShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * bonanza 标记发货 同步   脚本
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii queue/cron-bonanza-auto-shipped
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/04/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCronBonanzaAutoShipped(){
	    try {
	        //echo __FUNCTION__." into ";
	        $connection=\Yii::$app->db;
	        $command=$connection->createCommand('select `id`,`selleruserid`,`order_source`,`order_source_order_id` from  `queue_syncshipped` where `status` = 0  and order_source = "bonanza" order by `created` ASC');
	        $dataReader=$command->query();
	        while(($row=$dataReader->read())!==false) {
	            echo "order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
	            $QssObj = QueueSyncshipped::findOne($row['id']);
	            $success = false;
	            switch ($row['order_source']){
	                case 'bonanza':
	                    //echo $row['order_source']."shipped ";
	                    $success = QueueAutoShippedHelper::BonanzaShipped($QssObj);
	                    break;
	                    	
	                default:break;
	            }
 

	            // 更新订单 虚拟发货 状态 start
	            if ($success){
	            	$syncShippedStatus = "C";
	            }else{
	            	$syncShippedStatus = "F";
	            	echo "failure to sync ship then status set F  order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
	            }
	            OrderApiHelper::setOrderSyncShippedStatus(OdOrderShipped::findOne($QssObj->osid)->order_id, $syncShippedStatus);
	            // 更新订单 虚拟发货 状态 end
	
	        }
	    } catch ( \Exception $ex ) {
	        echo 'Error Message:' . $ex->getMessage () . "\n";
	    }
	}//end of actionCronEbayAutoShipped
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * rumall 标记发货 同步   脚本
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii queue/cron-bonanza-auto-shipped
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lwj		2016/04/28				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
// 	public function actionCronRumallAutoShipped(){
// 	    try {
// 	        //echo __FUNCTION__." into ";
// 	        $connection=\Yii::$app->db;
// 	        $command=$connection->createCommand('select `id`,`selleruserid`,`order_source`,`order_source_order_id` from  `queue_syncshipped` where `status` = 0  and order_source = "rumall" order by `created` ASC');
// 	        $dataReader=$command->query();
// 	        while(($row=$dataReader->read())!==false) {
// 	            echo "order_source_order_id:".$row['order_source_order_id']."time:".time()." order_source:".$row['order_source']." selleruserid:".$row['selleruserid']." \n";
// 	            $QssObj = QueueSyncshipped::findOne($row['id']);
// 	            $success = false;
// 	            switch ($row['order_source']){
// 	                case 'rumall':
// 	                    //echo $row['order_source']."shipped ";
// 	                    $success = QueueAutoShippedHelper::RumallShipped($QssObj);
// 	                    break;
	
// 	                default:break;
// 	            }
	
// 	        }
// 	    } catch ( \Exception $ex ) {
// 	        echo 'Error Message:' . $ex->getMessage () . "\n";
// 	    }
// 	}//end of actionCronEbayAutoShipped
	
	//***********************************  标记发货脚本 end ***********************************//
	
	/**
	 * 定时刊登队列
	 *
	 * @author fanjs
	 */
	function actionCronAdditem() {
		set_time_limit ( 0 );
		$i = 0;
		while ( true ) {
			\Yii::info ( 'autoadditem start:round ' . ++ $i );
			echo 'autoadditem start:round ' . ++ $i . "\n";
			try {
				$watcher = array (
						'list' => array (),
						'date' => date ( 'Ymd', time () )
				);
				$begin_timestamp = time ();
				$last_run = array ();
				\Yii::info ( 'outoftime_timers begin:' );
				echo 'outoftime_timers begin:' . "\n";
				// 处理过期的定时刊登设置
				$outoftime_timers = EbayAutoadditemset::find()->where('next_runtime < :nr',[':nr'=>($begin_timestamp - 30 * 60)])->all();
				foreach ( $outoftime_timers as $ot ) {
					 
					$em = EbayMuban::findOne(['mubanid'=>$ot->mubanid]);
					if (is_null($em)){
						$em = new EbayMuban();
					}
					$em->createLog('延时未执行', EbayLogMuban::RESULT_ERROR, $ot);
					$ot->delete ();
				}
				\Yii::info ( 'outoftime_timers: ' . count ( $outoftime_timers ) . ' Nums' );
				echo 'outoftime_timers: ' . count ( $outoftime_timers ) . ' Nums' . "\n";
				// 一分钟扫描一次发帖登记表
				// 时间区间是 当前时间 之前的 1800 .
				$timers = EbayAutoadditemset::find()->where('next_runtime > :time1 and next_runtime < :time2',[':time1' => $begin_timestamp - 1800,':time2' => $begin_timestamp])->orderBy('next_runtime ASC')->all();
				if (count ( $timers ) > 0) {
					foreach ( $timers as $a ) {
						 
						// 查是否重复发送了
						if (in_array ( $a->timerid, $last_run )) {
							\Yii::info ( 'Duplicate timer!' );
							echo 'Duplicate timer!' . "\n";
							continue;
						}
						\Yii::info ( 'timer:' . $a->timerid );
						// .监控检测，每个timer每天只能运行一次，如果多次运行证明出现问题
						if (isset ( $watcher ['list'] [$a->timerid] ) && $watcher ['list'] [$a->timerid] > 0) {
							// 处理，出现问题
							\Yii::info ( 'error timer:' . $a->timerid );
							echo 'error timer:' . $a->timerid . "\n";
							continue;
						} else {
							@$watcher ['list'] [$a->timerid] += 1;
						}
						// .组织xml
						\Yii::info ( 'muban:' . $a->mubanid );
						echo 'muban:' . $a->mubanid . "\n";
	
						$aapi = new additem();
						$r = $aapi->apiFromMuban ( $a->ebay_muban, $a->ebay_muban->uid, $a->ebay_muban->selleruserid, $a->ebay_muban->detail->storecategoryid, $a->ebay_muban->detail->storecategory2id, $a );
						if (isset ( $r ['ItemID'] )) {
							\Yii::info ( 'ItemID:' . $r ['ItemID'] );
							echo 'ItemID:' . $r ['ItemID'] . "\n";
						} else {
							\Yii::info ( 'Failure' );
							echo 'Failure' . "\n";
						}
						// 删除定时刊登器
						$a->delete ();
					}
				}
				$end_timestamp = time ();
				// 保存到 $last_run
				$last_run = Helper_Array::getCols ( $timers, 'timerid' );
	
				if ($end_timestamp - $begin_timestamp < 3) {
					sleep ( 1 );
				}
			} catch ( Exception $e ) {
				\Yii::info ( 'error' );
				\Yii::info ( print_r ( $e, true ) );
			}
			exit ();
		}
	}
	
	/**
	 * 自动补数量
	 * @author fanjs
	 */
	function actionCronQueueItemProcess(){
		do {
			$select = QueueItemprocess::findOne(['status'=>'0','type'=>QueueItemprocess::TYPE_REVISEINVENTORYSTATUS]);
			if (!empty($select)){
				echo "-- selleruserid : " . $select->selleruserid . " , Itemid: " . $select->itemid . "   \n";
				//查找对应的队列所需要的ebay账号，切换子库
				$eu = SaasEbayUser::findOne(['selleruserid'=>$select->selleruserid]);
				if(empty($eu)){
				    echo "\n[".date('H:i:s').'-'.__LINE__.'- saas_ebay_user have no user:'.$select->selleruserid."]\n";
				    continue;
				};
				 
				echo '[' . date('H:i:s') . ']  itemid:' . $select->itemid . " \n ";
//				$eu = EbayItem::findOne(['itemid'=>$select->itemid]);
				$user_token = $eu->token;
				//处理补库存
				if ($select->type == QueueItemprocess::TYPE_REVISEINVENTORYSTATUS) {
					$sku = !is_null($select->sku)?$select->sku:null;
					$error = '';
					$qfs = EbayitemHelper::getQuantityForSale($select->itemid,$select->data1, $select->transactionid,$sku,$error,true);
					if ($qfs === false) {
						var_dump($error);
						echo 'Itemid'.$select->itemid.' have error: '.print_r($error,1);
	
						echo 'get item';
	
						$result=array();
						$result['Ack']='Failure';
						//修改在线刊登记录
						EbayLogItem::Addlog('', 'System', '自动补货', $select->itemid, 'ItemId '.$select->itemid.' 自动补充库存错误: '.$error, $result,$select->transactionid);
	
						// 标记为失败
						$select->status='2';
						$select->updated = time();
						$select->save();
						continue;
					}
					$api = new reviseinventorystatus();
					$api->resetConfig($eu->DevAcccountID);
					$api->eBayAuthToken = $user_token;
					//速度太快，只需要补原来的数量即可
					if ($qfs > 0) {
						$r = $api->api($select->itemid, $qfs, $eu, $qfs, '', $sku, $select->transactionid);
					}
					if (! $api->responseIsFailure()) {
						echo 'Success';
					} else {
						echo 'Failure';
					}
					QueueItemprocess::deleteAll(['qid'=>$select->qid]);
				}
			}else{
				sleep(10);
				echo "end\n";
			}
		} while (1);
	}
	
	/*	同步paypal交易信息
	 * ./yii queue/sync-queue-paypal-transaction
	 */
	/*
	 * ./yii queue/sync-queue-paypal-transaction
	 */
	function actionSyncQueuePaypalTransaction() {
		try {
			$start_time = date('Y-m-d H:i:s');
			$comment = "\n  v1.0  cron ".(__FUNCTION__)." service runnning for ".(__function__)." at $start_time";
			echo $comment;
			$number = PaypalInterface_GetTransactionDetails::CronProcessQueue ();
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
		
		if (empty($number)){
			echo "\n no pending data ,then sleep 5 s ...";
			sleep ( 5 );
		}else{
			echo "\n $number pending data has done !";
		}
		
	}
	
	/*
	 * 根据puid同步paypal地址
	 * ./yii queue/manual-sync-paypal-transaction
	*/
	function actionManualSyncPaypalTransaction($puid) {
		try {
			$start_time = date('Y-m-d H:i:s');
			$comment = "\n  v1.1  cron ".(__FUNCTION__)." service runnning for ".(__function__)." at $start_time";
			echo $comment;
			if (empty($puid)) exit("\n no puid!");
			
			$number = PaypalInterface_GetTransactionDetails::CronProcessQueueByPuid($puid);
			echo "\n puid = $puid   number:".$number;
			
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
	
		if (empty($number)){
			echo "\n no pending data ,then sleep 5 s ...";
			sleep ( 5 );
		}else{
			echo "\n $number pending data has done !";
		}
	
	}
	
	/*
	 * 同步paypal地址
	* ./yii queue/cron-sync-paypal-address
	*/
	function actionCronSyncPaypalAddress(){
		try {
			$start_time = date('Y-m-d H:i:s');
			$comment = "\n  v1.1  cron ".(__FUNCTION__)." service runnning for ".(__function__)." at $start_time";
			echo $comment;
				
			$number = PaypalInterface_GetTransactionDetails::CronSyncPaypalAddress();
				
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
		
		if (empty($number)){
			echo "\n no pending data ,then sleep 5 s ...";
			sleep ( 5 );
		}else{
			echo "\n $number pending data has done !";
		}
		
	}
	
	/**
	 * 后台同步ebay类目的汽配fitment信息
	 * @author fanjs
	 */
	function actionRefreshfitmentname(){
		set_time_limit(0);
		$count=EbayCategory::find()->where('siteid in (100,15,77,3) and leaf = 1 and iscompatibility is null')->count();
		if ($count == 0){
			echo 'no datas need deal'."\n";
			\Yii::info('no datas need deal'."\n");exit();
		}
		$count=ceil($count/100);$cou=$count;
//		$token = SaasEbayUser::findOne(['selleruserid'=>base::DEFAULT_REQUEST_USER])->token;
		for($i=0;$i<$count;$i++){
			$ec=EbayCategory::find()->where('siteid in (100,15,77,3) and leaf = 1 and iscompatibility is null')->orderBy('id ASC')->offset($i*100)->limit(100)->all();
			foreach ($ec as $e){
				set_time_limit(0);
				echo 'categoryID:'.$e->categoryid.'-siteID:'.$e->siteid."\n";
				\Yii::info('categoryID'.$e->categoryid.'-siteID'.$e->siteid."\n");
				
				$gn = new getcompatibilitysearchnames();
				echo time();
				$r=$gn->api($e->categoryid,$e->siteid);
				echo '-'.time()."\n";
//				$r=$gn->api('50441','100');
				if ($r['ack']!='Failure'){
					$propertyName=array();
					foreach($r['properties']['propertyName'] as $p){
						$propertyName[$p['propertyNameMetadata']['displaySequence']]=$p["propertyName"];
					}
					$rr=$propertyName;
					$e->iscompatibility=1;
					$e->compatibilityname=implode(',',$rr);
					$e->save();
				}
				if ($r['ack']=='Failure' && isset($r['errorMessage']['error']['errorId']) && $r['errorMessage']['error']['errorId']==30){
					$e->iscompatibility = 0;
					$e->save();
				}
			}
			$cou-=1;
			echo 'there is '.$cou.' pages need deal;'."\n";
			\Yii::info('there is '.$cou.' pages need deal;'."\n");
		}
		exit('data has updated!'."\n");
	}
	
	 
	/*从队列拉取 app 之间需要push的异步请求，然后通过 eval 执行，执行的 时间里面，自己判断 puid 是否等于 db current，不等就自己change user db
	 *  @invoking					./yii queue/app-push-data0    */
	function actionAppPushData0(){
		
		do {$rtn = AppPushDataHelper::executeAppPushRequests(2,0);
		}
		while ($rtn > 0);
	}
	
	//./yii queue/app-push-data1
	function actionAppPushData1(){
		do {$rtn = AppPushDataHelper::executeAppPushRequests(2,1);
		}
		while ($rtn > 0);
	}
	
	//./yii queue/app-push-data-test
	function actionAppPushDataTest(){
	 $rtn = AppPushDataHelper::executeAppPushRequests(0,0,1838792);
	 echo "Done";	 
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * Image Cacher 看看队列有没有排队的，提取请求七牛做cache，然后把结果放到redis中，其他程序查找redis就知道有没有这个图片的cache了
	 +---------------------------------------------------------------------------------------------
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2016-2-17				初始化
	 +---------------------------------------------------------------------------------------------
	  *  @invoking					./yii queue/image-cacher-run-eagle2
	 **/
	function actionImageCacherRunEagle2() {
		 do {$rtn = ImageCacherHelper::processCacheReq($specified_orig_url='',$totalJobs=3,$thisJobId=0);
		 	}
			while ($rtn['count'] > 0);
	}
	
	/**
	 *  @invoking					./yii queue/image-cacher-run-eagle2-job2
	 **/
	function actionImageCacherRunEagle2Job2() {
		do {$rtn = ImageCacherHelper::processCacheReq($specified_orig_url='',$totalJobs=3,$thisJobId=1);
		}
		while ($rtn['count'] > 0);
	}
	
	/**
	 *  @invoking					./yii queue/image-cacher-run-eagle2-job3
	 **/
	function actionImageCacherRunEagle2Job3() {
		do {$rtn = ImageCacherHelper::processCacheReq($specified_orig_url='',$totalJobs=3,$thisJobId=2);
		}
		while ($rtn['count'] > 0);
	}
	
	###################################################################################################################
	/**
	 +---------------------------------------------------------------------------------------------
	 * 标记发货队列 数据健康检查
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 * ./yii queue/cron-queue-ship-health-check
	 +---------------------------------------------------------------------------------------------
	 * @param na
	 +---------------------------------------------------------------------------------------------
	 * @return                na
	 +---------------------------------------------------------------------------------------------
	 * log            name    date                    note
	 * @author        lkh     2016/05/17                                          初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	function actionCronQueueShipHealthCheck(){
		$start_time = date('Y-m-d H:i:s');
        $comment = "\n cron service runnning for ".(__function__)." at $start_time";
        echo $comment;
        \Yii::info($comment,'file');
        //目前放在半小时的脚本中，安全起见加下了半小时退出的
        do{
        	$rtn = QueueAutoShippedHelper::AutoShipHealthCheck();
        	
        	//目前半小时检测一次
        	sleep(60*30);
        	
        	$auto_exit_time = 30; // 30 minutes to leave
        	$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));

        }while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
        
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "\n cron service stops for ".(__function__)." at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info($comment,'file');
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay同步 订单检查  数据健康检查
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 * ./yii queue/sync-ebay-order-health-check
	 +---------------------------------------------------------------------------------------------
	 * @param na
	 +---------------------------------------------------------------------------------------------
	 * @return                na
	 +---------------------------------------------------------------------------------------------
	 * log            name    date                    note
	 * @author        lkh     2016/05/17                                          初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	function actionSyncEbayOrderHealthCheck(){
		//20170223kh  增加了ebay同步 订单检查
		QueueGetorderHelper::syncEbayOrderHealthCheck("N" );//Y = send email, N= not send
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * ebay 定期关闭长时时间没有登录账号的同步
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 * ./yii queue/stop-sync-ebay-order
	 +---------------------------------------------------------------------------------------------
	 * @param na
	 +---------------------------------------------------------------------------------------------
	 * @return                na
	 +---------------------------------------------------------------------------------------------
	 * log            name    date                    note
	 * @author        lkh     2017/05/24                                          初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	function actionStopSyncEbayOrder(){
		//20170223kh  增加了ebay同步 订单检查
		$now = time();
		$deadline = strtotime("-1 months");
		//echo "dead line = $deadline";
		//长时间无登录，自动关闭同步
		$sql = " update saas_ebay_user a , user_base b set a.item_status = 0  , a.error_message='长时间无登录，自动关闭同步!E001' , a.update_time=$now  where a.uid=b.uid  and a.item_status = 1 and  b.last_login_date <= $deadline  ";
		
		$rt = \yii::$app->db->createCommand($sql)->execute();
		echo "\n  after ".date("Y-m-d H:i:s",$deadline)." without log in then stop sync ebay order , update effect=".$rt;
		
		//自动删除paypal 队列数量
		$deadline2 = strtotime("-5 days");
		$sql = " delete from queue_paypaltransaction where created <= '$deadline2'";
		$rt = \yii::$app->db->createCommand($sql)->execute();
		echo "\n  delete paypal queue  before".date("Y-m-d H:i:s",$deadline2)."   and delete effect=".$rt;
	}
	###################################################################################################################
	
	
	/**
	 * 同步ebay账号所有feedback
	 * @invoking					./yii queue/cron-request-ebay-all-feedback
	 */
	public function actionCronRequestEbayAllFeedback() {
		$start_time = date('Y-m-d H:i:s');
		echo "start CronRequestEbayFeedback at $start_time";
	
		$block = '$flag = \common\api\ebayinterface\getfeedback::cronRequest_website($eu,0);';
		
		$ebayAccounts = SaasEbayUser::find()->where(1)->all();
		try {
			foreach ($ebayAccounts as $ebayAccount){
				$eu = $ebayAccount;
				$M = SaasEbayAutosyncstatus::find()
				->where('`type` = 4 AND `status` = 1 AND `status_process` <> 1')
				->andWhere(['selleruserid'=>$ebayAccount->selleruserid,'ebay_uid'=>$ebayAccount->ebay_uid])
				->one();
				if(empty($M))
					continue;
				
				$M->lastrequestedtime = time();
				$M->status_process = 1;
					
					
				$M->status_process = 0;
					
				$M->save();
				try{
					$flag = false;
					//执行block
					eval($block);
					if($flag) {
						$M->lastprocessedtime = time();
						$M->status_process = 2;
					}
				}catch(\Exception $ex){
					echo "\n".(__function__).'Error Message:'.$ex->getMessage()." Line no :".$ex->getLine()."\n";
				}
				$M->save();
			}
		} catch ( Exception $ex ) {
			echo 'Error Message:' . $ex->getMessage () . "\n";
		}
		$end_time = date('Y-m-d H:i:s');
		echo "end CronRequestEbayFeedback at $end_time";
		sleep ( 2 );
	}
}