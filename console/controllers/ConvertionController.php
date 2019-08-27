<?php
 
namespace console\controllers;
 
use yii;
use yii\console\Controller;
use \eagle\modules\util\helpers\ImageHelper;
use \eagle\modules\util\helpers\SQLHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use console\helpers\ConvertionHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\message\models\TicketSession;
use eagle\modules\util\helpers\RedisHelper;
/**
 * convertion controller
 */
class ConvertionController extends Controller {
	/*
	* @invoking					./yii convertion/test
	* */	
	public function actionTest() {
		echo "sdfsdf";
	}
	
	/*
	 * @invoking					./yii convertion/ysredismove
	* */
	public function actionYsredismove(){
		global $CACHE ;
		 
		$toMove=array('Tracker_DashBoard','global_config',
				'DASHBOARD_STS',
				'CdOffernNewlyLostBestSeller',
				'CdiscountAccountAddiFellow',
				'CdiscountAccountMaxFellow',
				'CdiscountAccountAddiHotSale',
				'CDOT_commitQueueHP',
				'CDOT_commitQueueLP',
				'LastSentCdTerminatorAnnounceProductIds'
		);
		 
		foreach ($toMove as $Level1){
			echo "start to do for $Level1 \n ";
			$keys = RedisHelper::RedisExe1 ('hkeys',array($Level1));
			if (!empty($keys)){
			foreach ($keys as $keyName){
			$valRedis2 = RedisHelper::RedisGet2 ($Level1,$keyName) ;
			if (empty($valRedis2))
				RedisHelper::RedisSet2 ($Level1,$keyName, RedisHelper::RedisGet1 ($Level1,$keyName) );
			}//end of each key name, like user_1.carrier_frequency
			}
			}
			 
			echo "redis move done";
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 批量导入 chi系统商品数据 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					./yii convertion/import-chi-product
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/8/11				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
    public function actionImportChiProduct(){
    	$puid = '297';
    	//商品基础信息
    	ProductApiHelper::importChiProductInfo($puid);
    	
    	ProductApiHelper::importChiProductAlias($puid);
    	
    	ProductApiHelper::importChiProductPhoto($puid);
    }//end of function actionImportChiProduct
  
    /*
     * @invoking					./yii convertion/del-problem-orders-and-refetch
     */
    public static function actionDelProblemOrdersAndRefetch(){
    	$time='2016-09-22 13:00:00';//问题发生时间(需修复时间)
    	$command = \Yii::$app->db->createCommand("select `uid` from user_base where 1 " );
    	$users = $command->queryAll();
    	foreach ($users as $user){
    		$uid = $user['uid'];
    		$rtn = ConvertionHelper::DelProblemOrdersAndRefetch($uid,$time);
    	}
    }
    
    /*
     * @invoking					./yii convertion/check-have-problem-order-user
    */
    public function actionCheckHaveProblemOrderUser(){
    	$time='2016-09-22 13:00:00';//问题发生时间(需修复时间)
    	$command = \Yii::$app->db->createCommand("select `uid` from user_base where 1 " );
    	$users = $command->queryAll();
    	
    	$timestamp = strtotime('2016-09-22 14:30');
    	foreach ($users as $user){
    		$uid = (int)$user['uid'];
    		 
    		$command = \Yii::$app->subdb->createCommand("SELECT distinct `order_source` FROM od_order_v2 WHERE `order_id` not in (SELECT order_id FROM od_order_item_v2) and create_time > $timestamp " );
			$platforms = $command->queryAll();
			if(!empty($platforms)){
				echo "\n $uid :";
				foreach ($platforms as $platform)
					echo " ".$platform['order_source'];
			}
    	}
    	
    	echo "\n\n###### end #######";
    }
    
    
    /*
     * @invoking					./yii convertion/cs-notified
    */
    public function actionCsNotified(){
    	
    	$command = \Yii::$app->db->createCommand("SELECT * FROM `message_api_queue`
    		WHERE `create_time`>='2016-11-15 11:00:00' AND `create_time`<='2016-11-16 16:40:00' 
    		AND  `app_source`='cs_ticket' " );
    	$queues = $command->queryAll();
    	$convertionData = [];
    	foreach ($queues as $q){
    		$convertionData[$q['puid']][$q['platform']][] = $q['ticket_id']; 
    		
    	}
    	
    	foreach ($convertionData as $puid=>$platformTicketIds){
    		echo "\n $puid start;";
    		
    		try{
    			 
    			foreach ($platformTicketIds as $platform=>$ticket_ids){
    				$tmp_ids = array_unique($ticket_ids);
    				echo "\n platform: $platform ticket count ".count($tmp_ids);
    				$tickets = TicketSession::find()->where(['ticket_id'=>$tmp_ids])->asArray()->all();
    				$order_no = [];
    				foreach ($tickets as $t){
    					$order_no[] = $t['related_id'];
    				}
    				OdOrder::updateAll(['shipping_notified'=>'Y'], ['order_source'=>$platform,'order_source_order_id'=>$order_no]);
    			}
    		}catch(\Exception $e){
    			echo "\n uid_$puid: actionCsNotified Exception:".$e->getMessage();
    			continue;
    		}
    	}
    }
}