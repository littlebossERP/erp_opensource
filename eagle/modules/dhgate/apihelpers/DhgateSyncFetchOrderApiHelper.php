<?php
namespace eagle\modules\dhgate\apihelpers;

use \Yii;
//use eagle\models\SaasAliexpressAutosync;
//use common\api\aliexpressinterface\AliexpressInterface_Auth;
//use common\api\aliexpressinterface\AliexpressInterface_Api;
//use eagle\models\QueueAliexpressGetorder;
//use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\SaasDhgateAutosync;
use eagle\models\QueueDhgateGetorder;
use eagle\models\QueueDhgatePendingorder;
use common\api\dhgateinterface\Dhgateinterface_Api;
use eagle\modules\dhgate\apihelpers\DhgateSyncFetchOrderBaseHelper;

class DhgateSyncFetchOrderApiHelper{
	public static $cronJobId=0;
	private static $dhgateGetOrderDetailVersion = null;
	public static $typename = array(
		'day120' => QueueDhgateGetorder::OLD_UNFININSHED,
		'finish' => QueueDhgateGetorder::OLD_FINISH,
		'time' => QueueDhgateGetorder::NEW_ORDER,
	 );
	public static function getCronJobId() {
		return self::$cronJobId;
	}
	public static function setCronJobId($cronJobId) {
		self::$cronJobId = $cronJobId;
	}
	/**
	 * [checkNeedExitNot 该进程判断是否需要退出]
	 * @Author   willage
	 * @DateTime 2016-08-18T09:17:01+0800
	 * 通过配置全局配置数据表ut_global_config_data的Order/dhgateGetOrderDetailVersion 对应数值
	 * @return   [type]                   [description]
	 */
	private static function checkNeedExitNot(){
		$dhgateGetOrderVersionFromConfig = ConfigHelper::getGlobalConfig("Order/dhgateGetOrderVersion",'NO_CACHE');
		if (empty($dhgateGetOrderVersionFromConfig))  {
			//数据表没有定义该字段，不退出。
			return false;
		}
		//如果自己还没有定义，去使用global config来初始化自己
		if (self::$dhgateGetOrderDetailVersion===null)	self::$dhgateGetOrderDetailVersion = $dhgateGetOrderVersionFromConfig;
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$dhgateGetOrderDetailVersion <> $dhgateGetOrderVersionFromConfig){
			echo "Version new $dhgateGetOrderVersionFromConfig , this job ver ".self::$dhgateGetOrderDetailVersion." exits \n";
			return true;
		}
		return false;
	}
	/**
	 * [_lockDhgateAutosyncRecord 先判断是否真的抢到待处理账号]
	 * @Author   willage
	 * @DateTime 2016-08-18T11:58:50+0800
	 * @param    [type]                   $dhgateAutosyncId [saas_dhgate_autosync表的id]
	 * @return   [type]                   [null或者$SAA_obj , null表示抢不到记录]
	 */
 	private static function _lockDhgateAutosyncRecord($dhgateAutosyncId){
 		$connection=Yii::$app->db;
 		$command = $connection->createCommand("update saas_dhgate_autosync set status=1 where id =". $dhgateAutosyncId." and status<>1 ") ;
 		$affectRows = $command->execute();
 		if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
 		$SAA_obj = 	SaasDhgateAutosync::findOne($dhgateAutosyncId);// 抢到记录
 		return $SAA_obj;
	}
	/**
	 * [fetchOrderList description]
	 * @Author   willage
	 * @DateTime 2016-08-18T09:16:28+0800
	 * @param    [type]                   $type [description]
	 * @return   [type]                         [description]
	 */
	public static function fetchOrderList($type){
		echo "++++++++++++".__FUNCTION__." \n";
		if (!array_key_exists($type,self::$typename)){//参数检查
			echo "not correct type!!!";
			return false;
		}
		/**
		 * No.1 检查当前本job版本，如果和自己的版本不一样，就自动exit
		 */
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
		$backgroundJobId=self::getCronJobId();//获取进程id号，主要是为了打印log
		$hasGotRecord=false;//是否抢到账号
		$queueDhgateGetorderType=self::$typename[$type];//记录对应的类型名字
		/**
		 * No.2 saas_dhgate_autosync 提取同步信息]
		 * status:0-- 没处理过; 2--完成; 3--上一次执行有问题;4--永久停止(day120和finish只拉取一次)
		 */
		$connection=Yii::$app->db;
		if ($type=='time') {
			//拉取新订单list，在最后执行时间半小时后再执行
			$command=$connection->createCommand('select `id` from  `saas_dhgate_autosync` where `is_active` = 1 AND `status` in (0,2,3) AND `times` < 10 AND `type`="'.$type.'" AND (`last_time`+1800 <='.time().' OR `last_time` IS NULL) order by `last_time` ASC limit 5');
		}else{
			$command=$connection->createCommand('select `id` from  `saas_dhgate_autosync` where `is_active` = 1 AND `status` in (0,2,3) AND `times` < 10 AND `type`="'.$type.'" order by `last_time` ASC limit 5');
		}
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockDhgateAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue;//抢不到---如果是多进程的话，有抢不到的情况
			\Yii::info(__FUNCTION__." row['id']=".$row['id']." type=".$type." jobid=$backgroundJobId start","file");
			$hasGotRecord=true;//抢到记录
			/**
			 * No.3 调用dhgate的api来获取对应账号的订单列表信息，并把dhgate返回的所有的订单header的信息保存到订单详情同步表QueueDhgateGetorder
			 */
			switch ($type) {
				case 'day120':
					$start_time = $SAA_obj->binding_time-(86400*120);//(86400*120);
					$end_time = $SAA_obj->binding_time;
					$od_status='101003,102001,103001,105001,105002,105003,105004,103002,101009,106001,106002,106003,102006,102007,102111';
					break;
				case 'finish':
					$start_time = $SAA_obj->binding_time-(86400*60);//(86400*60);
					$end_time =$SAA_obj->binding_time;
					$od_status='111000,111111';
					break;
				case 'time':
					$nowTime = time();
					if ($SAA_obj->end_time == 0){//第一次拉取
						$start_time = $SAA_obj->binding_time;
						$end_time = $nowTime;
					}else {
						$start_time = $SAA_obj->end_time;
						$end_time = $nowTime;
					}
					break;
			}
			$createDateStart=date("Y-m-d H:i:s",$start_time);
			$createDateEnd=date("Y-m-d H:i:s", $end_time);
			$querytimeType=1;
			$pageNo=1;
			$pageSize = 200;
			$success=true;
			/**
			 * No.3.1 设置请求参数
			 * @var [type]
			 */
			$appParams = array();
			$appParams["querytimeType"] = $querytimeType;
			$appParams["startDate"] = $createDateStart;
			$appParams["endDate"] = $createDateEnd;
			if ($type != 'time') {
				$appParams['orderStatus']=$od_status;
			}
			\Yii::info(print_r($appParams,true),"file");
			$dhgateinterface_Api = new Dhgateinterface_Api();
			do{
				/**
				 * No.3.2 调用dhgate的api来获取对应账号的订单列表信息。 由于有每页订单个数限制，这里需要循环多页面
				 */
				$appParams["pageNo"] = $pageNo;
				$appParams["pageSize"] = $pageSize;
				/**
				 * No.3.3 查询订单列表getOrderList
				 */
				$response = $dhgateinterface_Api->getOrderList($SAA_obj->dhgate_uid, $appParams);
				\Yii::info(print_r($response,true),"file");

				//TODO ---
				//3.3 根据$response的信息进行 异常判断. --跳出循环
				// access Token 过期
				// 返回error code
				// 返回值没有["response"]["orderBaseInfoList"]
				// 返回值没有["response"]["count"]
				if ($response['success'] === false){
				    $success=false;
				   	$errorMessage=$response['error_message'];
				    break;
				}
				if (isset($response['response'])) {
					if ($response['response']['status']['code'] != "00000000"){
						//当返回109时代表查询不到记录
						if ($response['response']['status']['code'] == "109")
							$success=true;
						else
							$success=false;
						$errorMessage=$response['response']['status']['message'];
						break;
					}
					$resultCount=$response["response"]["count"];
					$resultPages=$response["response"]["pages"];
					/**
					 * No.3.4 返回结果正常，需要把dhgate返回的所有的订单header的信息保存到订单详情同步表QueueDhgateGetorder
					 */
					if ($resultCount>0){
						$orderList=$response["response"]["orderBaseInfoList"];
						foreach($orderList as $one){
							DhgateSyncFetchOrderBaseHelper::insertOrUpdateOrderQueue($one, $SAA_obj, $queueDhgateGetorderType);
						}
					}
				}else{
					$success=false;
				}
				$pageNo++;
			} while($pageNo<=$resultPages);
            /**
             * No.4 保存saas_dhgate_autosync 提取的账号处理后的结果
             */
            if ($success) {
            	$SAA_obj->start_time = $start_time;
            	$SAA_obj->end_time = $end_time;
            	$SAA_obj->last_time = time();
            	if ($type=='day120' || $type=='finish') {
            		$SAA_obj->status = 4;
            	}else if ($type=='time') {
            		$SAA_obj->status = 2;
            	}
            	$SAA_obj->times = 0;
            	$SAA_obj->save ();
            } else {
            	$SAA_obj->message = $errorMessage;
            	$SAA_obj->last_time = time();
            	$SAA_obj->status = 3;
            	$SAA_obj->times += 1;
            	$SAA_obj->save ();
            }
            echo "finish \n";
		}
		return $hasGotRecord;
	}
	/**
	 * [fetchOrderDetail description]
	 * @Author   willage
	 * @DateTime 2016-08-18T09:29:53+0800
	 * @param    [type]                   $type          [description]
	 * @param    string                   $orderBy       [description]
	 * @param    integer                  $time_interval [description]
	 * @return   [type]                                  [description]
	 */
	public static function fetchOrderDetail($type){
		echo "++++++++++++".__FUNCTION__." \n";
		//TODO 类型检查
		/**
		 * No.1 检查当前本job版本，如果和自己的版本不一样，就自动exit
		 */
		$ret = self::checkNeedExitNot();
		if (true === $ret) exit;
		$backgroundJobId=self::getCronJobId();
		/**
		 * No.2 提取记录
		 */
		$connection = Yii::$app->db_queue;
		$command=$connection->createCommand('select * from  `queue_dhgate_getorder` where `status` <> 1 AND `is_active`=1 AND `times` < 10 AND type='.$type.' limit 100');
		$dataReader=$command->query();
		$hasGotRecord=false;
		while(($row=$dataReader->read())!==false) {
			\Yii::info(__FUNCTION__." row['uid']=".$row['uid']." orderid=".$row['orderid']." type=".$type." jobid=$backgroundJobId start","file");
			$puid=$row['uid'];
			$last_time = time();//获取当前时间戳，毫秒为单位，该数值只有比较意义。
			echo $row['orderid']."\n";
			/**
			 * No.3 抢记录
			 */
			$command = $connection->createCommand("update queue_dhgate_getorder set status=1 where id =". $row['id']." and status<>1 ") ;
			$affectRows = $command->execute();
			if ($affectRows <= 0)	continue; //抢不到
			$hasGotRecord=true;//抢到记录
			$QDG_obj = QueueDhgateGetorder::findOne($row['id']);
			$api = new Dhgateinterface_Api();
			/**
			 * No.4 调用接口获取订单列表
			 */
			$appParams = array (
				'orderNo' => $row['orderid']
			);
			$order_detail = $api->getOrderDetail($row['dhgate_uid'], $appParams);
			$order_items = $api->getOrderItems($row['dhgate_uid'], $appParams);
			/**
			 * No.5 拉取结果处理
			 */
			$detail_result=$order_detail['success'];
			$items_result=$order_items['success'];
			echo "detail_result = ".$detail_result."\n";
			echo "items_result = ".$items_result."\n";
			if (($detail_result!==true)||($items_result!==true)) {
				/**
				 * No.5.1 拉取失败，修改记录(不保存在数据库)
				 */
				$QDG_obj->message = $order_detail ['error_message'];
				$QDG_obj->status = 3;
				$QDG_obj->times += 1;
				$QDG_obj->last_time = $last_time;
				$QDG_obj->update_time = time ();
			}elseif (($detail_result==true)&&($items_result==true)) {
				/**
				 * No.5.2 拉取成功，保存V2数据表，并修改记录
				 */
				echo "No.5.2 save data\n";
				$finishStatus = DhgateSyncFetchOrderBaseHelper::getOrderCompleteStatus();
				$finishStatusArr = explode(',',$finishStatus);
				$unfinishStatus = DhgateSyncFetchOrderBaseHelper::getOrderNotCompleteStatus();
				$unfinishStatusArr = explode(',',$unfinishStatus);

				if (true){ 
					$rsp = DhgateSyncFetchOrderBaseHelper::saveDhgateOrder($QDG_obj,$order_detail['response'],$order_items['response']);
					$save_status=$rsp['success'];
					if($save_status == false){//保存失败
						$QDG_obj->status = 3;
						$QDG_obj->times += 1;
						$QDG_obj->message = "敦煌订单".$QDG_obj->orderid."保存失败".$rsp ['message'];
					}else{//保存成功
						$QDG_obj->status = 2;
						$QDG_obj->times = 0;
						$QDG_obj->type = QueueDhgateGetorder::DAILY_QUERY;
						$QDG_obj->message = '';
					}
					$QDG_obj->order_status = isset($order_detail['response']['orderStatus'])?$order_detail['response']['orderStatus']:$QDG_obj->order_status;
					$QDG_obj->last_time = $last_time;
					$QDG_obj->update_time = time();
				}else{
					$save_status=false;
				}
			}
			/*
			 * No.6 插入数据(未完成)到queue_dhgate_pendingorder
			 * 未完成的$type更改成DAILY_QUERY
			 */
			if (($detail_result!==true)||($items_result!==true)) {
				/**
				 * No.6.1 拉取失败，转存到queue_dhgate_pendingorder
				 */
				echo "No.6.1 fetch fail --insert queue_dhgate_pendingorder\n";
				DhgateSyncFetchOrderBaseHelper::savetoQueueDhgatePendingorder($QDG_obj);
			}elseif (($detail_result==true)&&($items_result==true)) {
				/**
				 * No.6.2 拉取成功保存失败，转存到queue_dhgate_pendingorder
				 */
				echo "No.6.2\n";
				if ($save_status==false) {//拉取成功，保存失败
					echo "No.6.2 fetch ok,save false --insert queue_dhgate_pendingorder\n";
					DhgateSyncFetchOrderBaseHelper::savetoQueueDhgatePendingorder($QDG_obj);
				}else{
				/**
				 * No.6.3
				 * 拉取成功and保存成功并且为未完成状态，
				 * 转存到queue_dhgate_pendingorder
				 */
					if (isset($order_detail['response']['orderStatus']) && in_array($order_detail['response']['orderStatus'], $unfinishStatusArr)) {
						echo "No.6.3 success --insert queue_dhgate_pendingorder\n";
						DhgateSyncFetchOrderBaseHelper::savetoQueueDhgatePendingorder($QDG_obj);
					}
				}

			}

			/**
			 * No.7 删除queue_dhgate_getorder数据(未完成/已完成)
			 */
			echo "No.7 delete queue_dhgate_getorder\n";
			$QDG_obj->delete();
			\Yii::info(__FUNCTION__." type=".$type." saveok jobid=".$backgroundJobId.",puid=".$QDG_obj->uid,"file");
		}

		return $hasGotRecord;
	}//end fetchOrderDetail
	/**
	 * [movequeue description]
	 * @Author   willage
	 * @DateTime 2016-08-18T11:07:54+0800
	 * @return   [type]                   [description]
	 */
	public static function moveQueue(){
		/**
		 * No.1 提取记录
		 */
		echo "No.1 +++++++++++++".__FUNCTION__."\n";
		$backgroundJobId=self::getCronJobId();
		$nowTime=time();
		$connection = Yii::$app->db_queue;
		$command=$connection->createCommand('select * from  `queue_dhgate_pendingorder` where `is_active`=1 AND next_execute_time<='.$nowTime.' limit 100');
		$dataReader=$command->query();
		echo "count = ".count($dataReader)."\n";
		if (count($dataReader)==0) {
			return false;
		}
		while(($row=$dataReader->read())!==false) {
			$QDP_obj = QueueDhgatePendingorder::findOne($row['id']);
			/**
			 * No.2 插入记录到队列queue_dhgate_getorder
			 */
			echo "No.2 insert\n";
			$rsp=DhgateSyncFetchOrderBaseHelper::savetoQueueDhgateGetorder($QDP_obj);
			/**
			 * No.3 删除记录queue_dhgate_pendingorder
			 */
			echo "No.3 delete\n";
			if($rsp){//保存成功，则删除
				$QDP_obj->delete();
			}else{
				\Yii::info(__FUNCTION__." savefail jobid=".$backgroundJobId.",puid=".$QDP_obj->uid,"file");
				continue;
			}
			echo "finish\n";
		}
		return true;
	}//end movequeue



}
?>