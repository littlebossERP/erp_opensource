<?php

namespace console\controllers;

use yii\console\Controller;
use eagle\models\UserBase;
use eagle\models\BackgroundMonitor;
use eagle\models\OrderHistoryStatisticsData;
use console\helpers\OrderUserStatisticHelper;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderOld;
use eagle\modules\order\models\OdOrderItemOld;
use eagle\modules\order\models\OdOrderShippedOld;
use eagle\modules\util\models\UserBackgroundJobControll;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\UserDatabase;

/**
 * SqlExecution controller
 */

//error_reporting(0);

class OrderArchiveController extends Controller
{
	//1431187200---2015-05-10 0:0:0              第一次
	//1470626340---2016-08-08 11:19:0        第二次  1454889600
	//public static $backupTime = 1470626340;
	
	//2017/10/22 6:52:20
	public static $backupTime = 1508626340;
 
	
	
	//指定uid2658来跑
	// ./yii order-archive/copy-to-old-table-oneuid
	 public function actionCopyToOldTableOneuid()
    {
    	 
 
	         $puid=1;
    	 
    		
    		// 封装搬移订单逻辑方便 重用 
    		self::_copyToOldTable($puid);
    			
    	
	}
	
	
	
	
	/**
	 +----------------------------------------------------------
	 * 初始化第一步---- 旧的订单copy到 旧的订单表
	 +----------------------------------------------------------
	 *  ./yii order-archive/copy-to-old-table
	 **/
    public function actionCopyToOldTable11()
    {
     
    	//获取数据    	
    	$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
    	
    	foreach ($mainUsers as $mainUser){
    		$puid=$mainUser["uid"];
    	 
    		
    		// 封装搬移订单逻辑方便 重用 
    		self::_copyToOldTable($puid);
    			
    	}//end of each puid 
	}
	
    public function actionCopyToOldTable13()
    {
    	//$mainUserPuidArr=array(297);
    	
    	//获取数据    	
    	$mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
    	
    	foreach ($mainUsers as $mainUser){
    		$puid=$mainUser["uid"];
    	 
    		// 封装搬移订单逻辑方便 重用
    		self::_copyToOldTable($puid);
    		
    			
    	}//end of each puid
	}
	
	

	/**
	 *  多进程跑order archive --- user0
	 * ./yii order-archive/multi-copy-to-old-table0
	 */
	public function actionMultiCopyToOldTable0(){
		self::_CopyToOldTableByDbserverId(0);
	}
	
	/**
	 *  多进程跑order archive --- user1
	 * ./yii order-archive/multi-copy-to-old-table1
	 */
	public function actionMultiCopyToOldTable1(){
		self::_CopyToOldTableByDbserverId(1);
	}
	
	/**
	 *  多进程跑order archive --- user2
	 * ./yii order-archive/multi-copy-to-old-table2
	 */
	public function actionMultiCopyToOldTable2(){
		self::_CopyToOldTableByDbserverId(2);
	}
	/**
	 *  多进程跑order archive --- user3
	 * ./yii order-archive/multi-copy-to-old-table3
	 */
	public function actionMultiCopyToOldTable3(){
		self::_CopyToOldTableByDbserverId(3);
	}
	/**
	 *  多进程跑order archive --- user4
	 * ./yii order-archive/multi-copy-to-old-table4
	 */
	public function actionMultiCopyToOldTable4(){
		self::_CopyToOldTableByDbserverId(4);
	}	
	/**
	 *  多进程跑order archive --- user5
	 * ./yii order-archive/multi-copy-to-old-table5
	 */
	public function actionMultiCopyToOldTable5(){
		self::_CopyToOldTableByDbserverId(5);
	}	
	
	/**
	 *  多进程跑order archive --- user7
	 * ./yii order-archive/multi-copy-to-old-table7
	 */
	public function actionMultiCopyToOldTable7(){
		self::_CopyToOldTableByDbserverId(7);
	}
	
	
	
	
	
	
	
	
	/**
	 * 获取是否有新机出现
	 *
	 * @author		hqw		2016/07/07				初始化
	 * @return boolean true 表示有新机，false 表示暂时没有新机
	 */
	public static function _getIsNewDbServer(){
		$dbServerCount = UserDatabase::find()->groupBy(['dbserverid'])->count();
	
		//表示现时的机器数，假如添加了机器这里需要添加加了几部机器，一部机器就要$nowCount + 1
		$nowCount = 7;
	
		if($dbServerCount <> $nowCount){
			return true;
		}else{
			return false;
		}
	}
	
	
	
	
	/**
	 * 公共函数指定物理db的id来备份order表
	 * @param  $dbserverid
	 */
	static private function _CopyToOldTableByDbserverId($dbserverid)
	{
		
		if(self::_getIsNewDbServer()){
			echo "There is a new machine ,Please confirm and try again. "."\n";
			exit;
		}

		
		
		
		
		$mainUsers = UserDatabase::find()->where(['dbserverid'=>$dbserverid])->orderBy(["uid"=>SORT_DESC])->asArray()->all();
		$tmpRecords = 0;
		 
		foreach ($mainUsers as $puser){
			$puid =$puser['uid'];
		 
			// 封装搬移订单逻辑方便 重用
			self::_copyToOldTable($puid);
			
		}
			 
	}
	
	
	
	
	/**
	 +----------------------------------------------------------
	 * 初始化第一步---- 旧的订单copy到 旧的订单表
	 +----------------------------------------------------------
	 *  ./yii order-archive/copy-to-old-table-by-puid 297
	 **/
	public function actionCopyToOldTableByPuid($puid){
		 
		
		// 封装搬移订单逻辑方便 重用
		self::_copyToOldTable($puid);
	}//end of actionCopyToOldTableByPuid
	
	
	
	
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 封装 copy 订单的逻辑
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $puid			int	 	小老板账号的puid
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2017/02/04				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function _copyToOldTable($puid){
		
		if ($puid==1 or $puid==297) return;
		
		$subdbConn=\yii::$app->subdb;
		echo "puid:".$puid." Running copyToOld ... \n";
		
		$logTimeMS1=TimeUtil::getCurrentTimestampMS();
		//1431187200---2015-05-10 0:0:0
		
		//限制数量
		$condition = '';
		if(in_array($puid, ['8801', '2244'])){
			$condition = ' limit 0, 50000';
		}
		
		$q = $subdbConn->createCommand('select * from od_order_v2 where order_source_create_time < '.self::$backupTime.$condition)->query();
		echo $q->count().PHP_EOL;
		$orderRows=array();
		$orderIdArr=array();
		$index=0;
		$orderkey1=array();
		$orderitemskey1=array();
		$ordershippedkey1=array();
		while ($row=$q->read()){
			//echo "read 1 \n";
			$index++;
			$orderRows[]=$row;
			$orderIdArr[]=$row["order_id"];
			 
			if ($index<=9){
				continue;
			}
			 
			 
			$orderRow=$orderRows[0];
			if (count($orderkey1)==0) $orderkey1=array_keys($orderRow);
			$valuesArr=array();
			foreach($orderRows as $orderRow){
				$valueArr=array();
				foreach($orderkey1 as $mykey){
					$valueArr[]=$orderRow[$mykey];
				}
				$valuesArr[]=$valueArr;
			}
			$ret=$subdbConn->createCommand()->batchInsert("od_order_old_v2",$orderkey1,$valuesArr)->execute();
			 
			$orderListStr=implode(",", $orderIdArr);
		
		
			$valuesArr=array();
			$itemsArr=$subdbConn->createCommand('select * from od_order_item_v2 where order_id in ('.$orderListStr.')')->query();
			foreach($itemsArr as $item){
				if (count($orderitemskey1)==0)  $orderitemskey1=array_keys($item);
				$valueArr=array();
				foreach($orderitemskey1 as $mykey){
					$valueArr[]=$item[$mykey];
				}
				$valuesArr[]=$valueArr;
			}
			if (count($valuesArr)>0){
				$ret=$subdbConn->createCommand()->batchInsert("od_order_item_old_v2",$orderitemskey1,$valuesArr)->execute();
				 
				$subdbConn->createCommand('delete from od_order_item_v2 where order_id in ('.$orderListStr.')')->query();
			}
			 
		
			$valuesArr=array();
			$itemsArr=$subdbConn->createCommand('select * from od_order_shipped_v2 where order_id in ('.$orderListStr.')')->query();
			foreach($itemsArr as $item){
				if (count($ordershippedkey1)==0)  $ordershippedkey1=array_keys($item);
				$valueArr=array();
				foreach($ordershippedkey1 as $mykey){
					$valueArr[]=$item[$mykey];
				}
				$valuesArr[]=$valueArr;
			}
			if (count($valuesArr)>0){
				$ret=$subdbConn->createCommand()->batchInsert("od_order_shipped_old_v2",$ordershippedkey1,$valuesArr)->execute();
				$subdbConn->createCommand('delete from od_order_shipped_v2 where order_id in ('.$orderListStr.')')->query();
			}
			 
			 
			$subdbConn->createCommand('delete from od_order_v2 where order_id in('.implode(",", $orderIdArr).')')->query();
			$orderRows=array();
			$orderIdArr=array();
			$index=0;
		}
		
		// 以防上面不够10行就退出了，最后几条执行不了
		if (count($orderRows)>0){
		
			$orderRow=$orderRows[0];
			if (count($orderkey1)==0) $orderkey1=array_keys($orderRow);
			$valuesArr=array();
			foreach($orderRows as $orderRow){
				$valueArr=array();
				foreach($orderkey1 as $mykey){
					$valueArr[]=$orderRow[$mykey];
				}
				$valuesArr[]=$valueArr;
			}
			$ret=$subdbConn->createCommand()->batchInsert("od_order_old_v2",$orderkey1,$valuesArr)->execute();
		
			$orderListStr=implode(",", $orderIdArr);
			 
			 
			$valuesArr=array();
			$itemsArr=$subdbConn->createCommand('select * from od_order_item_v2 where order_id in ('.$orderListStr.')')->query();
			foreach($itemsArr as $item){
				if (count($orderitemskey1)==0)  $orderitemskey1=array_keys($item);
				$valueArr=array();
				foreach($orderitemskey1 as $mykey){
					$valueArr[]=$item[$mykey];
				}
				$valuesArr[]=$valueArr;
			}
			if (count($valuesArr)>0){
				$ret=$subdbConn->createCommand()->batchInsert("od_order_item_old_v2",$orderitemskey1,$valuesArr)->execute();
				 
				$subdbConn->createCommand('delete from od_order_item_v2 where order_id in ('.$orderListStr.')')->query();
			}
		
			 
			$valuesArr=array();
			$itemsArr=$subdbConn->createCommand('select * from od_order_shipped_v2 where order_id in ('.$orderListStr.')')->query();
			foreach($itemsArr as $item){
				if (count($ordershippedkey1)==0)  $ordershippedkey1=array_keys($item);
				$valueArr=array();
				foreach($ordershippedkey1 as $mykey){
					$valueArr[]=$item[$mykey];
				}
				$valuesArr[]=$valueArr;
			}
			if (count($valuesArr)>0){
				$ret=$subdbConn->createCommand()->batchInsert("od_order_shipped_old_v2",$ordershippedkey1,$valuesArr)->execute();
				$subdbConn->createCommand('delete from od_order_shipped_v2 where order_id in ('.$orderListStr.')')->query();
			}
		
			$subdbConn->createCommand('delete from od_order_v2 where order_id in('.implode(",", $orderIdArr).')')->query();
		}
		
		
		$q = $subdbConn->createCommand('delete from operation_log_v2 where update_time < '.self::$backupTime)->query();
		
		// dzt20190415 add
		$subdbConn->createCommand('OPTIMIZE TABLE `od_order_shipped_v2` ')->query();
		$subdbConn->createCommand('OPTIMIZE TABLE `od_order_item_v2` ')->query();
		$subdbConn->createCommand('OPTIMIZE TABLE `od_order_v2` ')->query();
		$subdbConn->createCommand('OPTIMIZE TABLE `operation_log_v2` ')->query();
		
		$logTimeMS2=TimeUtil::getCurrentTimestampMS();
		
		echo "puid:$puid ending ... t2_t1=".($logTimeMS2-$logTimeMS1)." \n";
	}//end of function _copyToOldTable
		
}
