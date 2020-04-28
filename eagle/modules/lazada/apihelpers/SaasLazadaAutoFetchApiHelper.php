<?php
namespace eagle\modules\lazada\apihelpers;
use \Yii;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\models\SaasLazadaAutosync;
use eagle\models\SaasLazadaUser;
use common\api\lazadainterface\LazadaInterface_Helper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\QueueLazadaGetorder;
use eagle\models\LazadaOrder;
use eagle\models\LazadaOrderItems;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderHelper;
use Qiniu\json_decode;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\util\helpers\RedisHelper;
use common\api\lazadainterface\LazadaProxyConnectHelper;

/**
 +------------------------------------------------------------------------------
 * Lazada 数据同步类 主要执行订单数据同步
 +------------------------------------------------------------------------------
 */

class SaasLazadaAutoFetchApiHelper{
	public static $cronJobId=0;
	private static $lazadaGetOrderListVersion = null;
	const ALL_EXISTING = 1;
	const NEW_BY_TIME = 2;
	
	public static $type = array(
			1=>'首次需要同步的所有订单',
			2=>'',
	);
	
	//lazada平台的状态跟eagle的订单状态的对应关系
	public static $LAZADA_EAGLE_ORDER_STATUS_MAP = array(
			//	'Pending' =>100,  //等待买家付款
			'pending' => 200, //买家已付款
			'ready_to_ship' => 500, //卖家已发货
			'processing'=>500,// 应该是你把货寄到LGS的途中
			'shipped' => 500,//CUBE_CONST::SentGood,  //卖家已发货
			'delivered'=>500,
			'return_waiting_for_approval'=>500,//买家申请退货，等待卖家同意
			'return_shipped_by_customer'=>500,//买家申请退货，买家已退回
			'return_rejected'=>500,//买家申请退货，卖家拒绝
			'returned'=>600,
			'failed'=>600,
			'canceled'=>600
	);	

	/**
	 * @return the $cronJobId
	*/
	public static function getCronJobId() {
		return self::$cronJobId;
	}
	
	/**
	 * @param number $cronJobId
	 */
	public static function setCronJobId($cronJobId) {
		self::$cronJobId = $cronJobId;
	}
	
	/**
	 * 先判断是否真的抢到待处理账号
	 * @param  $autosyncId  -- saas_lazada_autosync表的id
	 * @return null或者$SAA_obj , null表示抢不到记录
	 */
	private static function _lockLazadaAutosyncRecord($autosyncId){
		$nowTime=time();
		$connection=Yii::$app->db;
		$command = $connection->createCommand("update saas_lazada_autosync set status=1,last_finish_time=$nowTime where id =". $autosyncId." and status<>1 ") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
		// 抢到记录
		$SAA_obj = 	SaasLazadaAutosync::findOne($autosyncId);
		return $SAA_obj;
	}
	
	/**
	 * 该进程判断是否需要退出
	 * 通过配置全局配置数据表ut_global_config_data的Order/lazadaGetOrderVersion 对应数值
	 *
	 * @return  true or false
	 */
	private static function checkNeedExitNot(){
		$lazadaGetOrderVersionFromConfig = ConfigHelper::getGlobalConfig("Order/lazadaGetOrderVersion",'NO_CACHE');
		if (empty($lazadaGetOrderVersionFromConfig))  {
			//数据表没有定义该字段，不退出。
			//	self::$lazadaGetOrderListVersion ="v0";
			return false;
		}
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (self::$lazadaGetOrderListVersion===null)	self::$lazadaGetOrderListVersion = $lazadaGetOrderVersionFromConfig;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$lazadaGetOrderListVersion <> $lazadaGetOrderVersionFromConfig){
			echo "Order/lazadaGetOrderVersion version new $lazadaGetOrderVersionFromConfig , this job ver ".self::$lazadaGetOrderListVersion." exits \n";
			return true;
		}
		return false;
	}
	
	/**
	 * 获取所有lazada用户的api访问信息。 email,token,销售站点
	 */
	private static function getAllLazadaAccountInfoMap(){
		$lazadauserMap=array();
		
		$lazadaUsers=SaasLazadaUser::find()->all();
		foreach($lazadaUsers as $lazadaUser){
			$lazadauserMap[$lazadaUser->lazada_uid]=array(
					"userId"=>$lazadaUser->platform_userid,
					"apiKey"=>$lazadaUser->token,
					"platform"=>$lazadaUser->platform,
					"countryCode"=>$lazadaUser->lazada_site
			);			
		}
		
		return $lazadauserMap;
		
	}
	

	
	
	/**
	 * 这里订单拉取分成3个进程
	 * 1. 新订单的拉取。 用户绑定的时间点之后create的订单
	 * 2. 新订单的创建之后，更新信息的拉取。 用户绑定的时间点之后update的订单
	 * (由于新创建的订单，updatedafter的接口获取不了，所以这里需要进程1， 通过createdafter接口来获取)
	 * 3. 旧订单的第一次拉取。绑定的时间点之前，n天之内create的订单。
	 * 4. 旧订单的后续拉取。绑定的时间点之前，n天之外create的订单。
	 */
	
	
	
	/**
	 *  新订单的拉取。 用户绑定的时间点之后create的订单	  
	 */
	public static function getOrderListNewCreate($platforms = false){
		echo "++++++++++++getOrderListNewCreate \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=\Yii::$app->db;
		$type = 2;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单
		$hasGotRecord=false;//是否抢到账号
		$fetchPeriod=20*60; //秒为单位. 2次后台自动拉取的时间间隔
		$fetchEndTimeAdvanced=5*60;  //秒为单位 .为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
		$failRetryPeriod=5*60;// 失败重试
		
		//2. 从账户同步表（订单列表同步表提取带同步的账号。         
		$nowTime=time();
		// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题
// 		$command=$connection->createCommand(
// 			'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` in (0,2,3) '.
// 			'AND `error_times` < 10 AND `type`='.$type.'  AND  next_execution_time<'.$nowTime .
// 	        ' order by next_execution_time asc limit 30 ');
		
		// dzt20161227 加入平台过滤
		$sql = 'select `id` from  `saas_lazada_autosync` as sla ';
		$where = 'sla.`is_active` = 1 AND sla.`status` in (0,2,3) AND sla.`error_times` < 10 AND sla.`type`='.$type.' AND sla.next_execution_time<'.$nowTime;
		if(!empty($platforms)){
// 		    $sql .= ' inner join `saas_lazada_user` as slu on slu.`lazada_uid`=sla.`lazada_uid` ';
// 		    $where .= ' AND slu.`platform` in ("'.implode('","', $platforms).'") ' ;
		    // saas_lazada_autosync 也添加platform,site字段
            if(is_array($platforms)){
                $where .= ' AND sla.`platform` in ("'.implode('","', $platforms).'") ' ;
            } else {
                $where .= ' AND sla.`platform` = "'.$platforms.'" ' ;
            }
		}
		
		$command=$connection->createCommand($sql." where ".$where." order by sla.next_execution_time asc limit 30 ");
		
		
// 		$command=$connection->createCommand(
// 		        'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` <>1 and lazada_uid=1026 '.
// 		        ' AND `type`='.$type.'  AND  next_execution_time<'.$nowTime );
		
		$allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			echo "++++++++dataReader->read() ".$row['id']."\n";
			$timeMS1=TimeUtil::getCurrentTimestampMS();
			
		// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockLazadaAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
	
			$hasGotRecord=true;  // 抢到记录
	
			//3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
			//3.1 整理请求参数
			$tempConfig=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],					
					"countryCode"=>$tempConfig["countryCode"],
			);			
			
			$nowTime=time();
			if ($SAA_obj->end_time == 0){
				$start_time = $SAA_obj->last_binding_time;
				$end_time = $nowTime;
			} else {
			    $start_time = $SAA_obj->end_time;
			    $end_time = $nowTime;
			}

			//为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
			$end_time=$end_time-$fetchEndTimeAdvanced;
		
			// dzt20161129 添加lazada get order list请求offset
			// 如果有offset 即上次start_time - end_time 的订单未获取完
			$offset = self::_getAutoSyncOffset($SAA_obj->id);
			if(!empty($offset)) {
			    $start_time = $SAA_obj->start_time;
			    $end_time = $SAA_obj->end_time;
			}else{
			    $offset = 0;
			}
			
			$timeMS2=TimeUtil::getCurrentTimestampMS();
			//3.2 访问proxy并保存结果
			list($ret,$errorMessage)=self::_getOrderListAndSaveToQueue($config,$start_time,$end_time,$SAA_obj,"createTime",$offset);
			$timeMS3=TimeUtil::getCurrentTimestampMS();
			if ($ret==false){	
			    Yii::info("getOrderListNewCreate platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.", $start_time,$end_time,$type --- error message=$errorMessage,t2_1=".($timeMS2-$timeMS1).",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
				$SAA_obj->message=$errorMessage;
				$SAA_obj->error_times=$SAA_obj->error_times+1;
				$SAA_obj->status=3;
				//$SAA_obj->last_finish_time=$nowTime;//上一次运行时间
				$SAA_obj->next_execution_time=$nowTime+$failRetryPeriod;
				$SAA_obj->update_time=$nowTime;
				$SAA_obj->save(false);
				continue;
			}
			 
		    if (!true){
			    $newFetchPeriod = 6 * $fetchPeriod;
			}else{
			    $newFetchPeriod = $fetchPeriod;
			}
			
			$SAA_obj->start_time=$start_time;
			$SAA_obj->end_time=$end_time;			
			$SAA_obj->status = 2;
			$SAA_obj->error_times = 0;
			$SAA_obj->message="";
			$SAA_obj->update_time=$nowTime;
			$SAA_obj->next_execution_time=$nowTime+$newFetchPeriod;
			$SAA_obj->save (false);		
			
			Yii::info("getOrderListNewCreate platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.",t2_1=".($timeMS2-$timeMS1).",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
		}
		
		return $hasGotRecord;
	}
	
	

	/**
	 *  新订单的创建之后，更新信息的拉取。 用户绑定的时间点之后update的订单
	 * (由于新创建的订单，updatedafter的接口获取不了，所以这里需要进程1， 通过createdafter接口来获取)
	 */
	public static function getOrderListNewUpdate($platforms=false){
		echo "++++++++++++getOrderListNewUpdate \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=\Yii::$app->db;
		$type = 3;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单
		$hasGotRecord=false;//是否抢到账号
		$fetchPeriod=20*60; //秒为单位. 2次后台自动拉取的时间间隔
		$fetchEndTimeAdvanced=5*60;  //秒为单位 .为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
		$failRetryPeriod=5*60;// 失败重试
		
		//2. 从账户同步表（订单列表同步表提取带同步的账号。
		$nowTime=time();
		// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题
// 		$command=$connection->createCommand(
// 				'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` in (0,2,3) '.
// 				'AND `error_times` < 10 AND `type`='.$type.'  AND  next_execution_time<'.$nowTime .
// 		        ' order by next_execution_time asc limit 30 ');
		
		// dzt20161227 加入平台过滤
		$sql = 'select `id` from  `saas_lazada_autosync` as sla ';
		$where = 'sla.`is_active` = 1 AND sla.`status` in (0,2,3) AND sla.`error_times` < 10 AND sla.`type`='.$type.' AND sla.next_execution_time<'.$nowTime;
		if(!empty($platforms)){
// 		    $sql .= ' inner join `saas_lazada_user` as slu on slu.`lazada_uid`=sla.`lazada_uid` ';
// 		    $where .= ' AND slu.`platform` in ("'.implode('","', $platforms).'") ' ;
		    // saas_lazada_autosync 也添加platform,site字段
            if(is_array($platforms)){
                $where .= ' AND sla.`platform` in ("'.implode('","', $platforms).'") ' ;
            } else {
                $where .= ' AND sla.`platform` = "'.$platforms.'" ' ;
            }
		}
		
		$command=$connection->createCommand($sql." where ".$where." order by sla.next_execution_time asc limit 30 ");
		
// 		$command=$connection->createCommand(
// 		        'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` <>1 and lazada_uid=837 '.
// 		        ' AND `type`='.$type.'  AND  next_execution_time<'.$nowTime );
		
		$allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
		
		
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			echo "++++++++dataReader->read() ".$row['id']."\n";
			$timeMS1=TimeUtil::getCurrentTimestampMS();
			
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockLazadaAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
	
			//	\Yii::info("lazada_get_order_list_by_finish gotit jobid=$backgroundJobId start","file");
			// 			echo "++dhgate_get_order_list_by_finish gotit jobid=$backgroundJobId start \n";
			$hasGotRecord=true;  // 抢到记录
	
			//3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
			//3.1 整理请求参数
		//	$config=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
			$tempConfig=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
				
			$nowTime=time();
			if ($SAA_obj->end_time == 0){
				$start_time = $SAA_obj->last_binding_time;
				$end_time = $nowTime;
			} else {
			    $start_time = $SAA_obj->end_time;
			    $end_time = $nowTime;
			}
			
			//为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
			$end_time=$end_time-$fetchEndTimeAdvanced;
	
			// dzt20161129 添加lazada get order list请求offset
			// 如果有offset 即上次start_time - end_time 的订单未获取完
			$offset = self::_getAutoSyncOffset($SAA_obj->id);
			if(!empty($offset)) {
			    $start_time = $SAA_obj->start_time;
			    $end_time = $SAA_obj->end_time;
			}else{
			    $offset = 0;
			}
			
			$timeMS2=TimeUtil::getCurrentTimestampMS();
			//3.2 访问proxy并保存结果
			list($ret,$errorMessage)=self::_getOrderListAndSaveToQueue($config,$start_time,$end_time,$SAA_obj,"updateTime",$offset);
			$timeMS3=TimeUtil::getCurrentTimestampMS();
			if ($ret==false){
			    Yii::info("getOrderListNewUpdate platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.", $start_time,$end_time,$type --- error message=$errorMessage,t2_1=".($timeMS2-$timeMS1).",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
				$SAA_obj->message=$errorMessage;
				$SAA_obj->error_times=$SAA_obj->error_times+1;
				$SAA_obj->status=3;
				//$SAA_obj->last_finish_time=$nowTime;//上一次运行时间
				$SAA_obj->next_execution_time=$nowTime+$failRetryPeriod;
				$SAA_obj->update_time=$nowTime;
				$SAA_obj->save(false);
				continue;
			}
			
			if (!true){
			    $newFetchPeriod = 6 * $fetchPeriod;
			}else{
			    $newFetchPeriod = $fetchPeriod;
			}
			
			$SAA_obj->start_time=$start_time;
			$SAA_obj->end_time=$end_time;
			$SAA_obj->status = 2;
			$SAA_obj->error_times = 0;
			$SAA_obj->message="";
			$SAA_obj->update_time=$nowTime;
			$SAA_obj->next_execution_time=$nowTime+$newFetchPeriod;
			$SAA_obj->save (false);
				
			Yii::info("getOrderListNewUpdate platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.",t2_1=".($timeMS2-$timeMS1).",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
		}
		
		return $hasGotRecord;
	}
	
	
	
	/**
	 * 旧订单的第一次拉取。绑定的时间点之前，n天之内create的订单。
	 * dzt20170614 修改成拉取 30天内未完成订单(接口只支持filter一个状态)，不拉取全部订单
	 */
	public static function getOrderListOldFirst($platforms=false){
		echo "++++++++++++getOrderListOldFirst \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=\Yii::$app->db;
		$type = 1;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单
		$hasGotRecord=false;//是否抢到账号
		$fetchPeriod=20*60; //秒为单位. 2次后台自动拉取的时间间隔
		$failRetryPeriod=5*60;// 失败重试
		
		//2. 从账户同步表（订单列表同步表提取带同步的账号。
		$nowTime=time();
		// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题
// 		$command=$connection->createCommand('select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND '.
// 				' `status` in (0,2,3) AND `error_times` < 10 AND `type`='.$type .
// 		        ' order by next_execution_time asc limit 30 ');
	
		// dzt20161227 加入平台过滤
		$sql = 'select `id` from  `saas_lazada_autosync` as sla ';
		$where = 'sla.`is_active` = 1 AND sla.`status` in (0,2,3) AND sla.`error_times` < 10 AND sla.`type`='.$type.' AND sla.next_execution_time<'.$nowTime;
		if(!empty($platforms)){
// 		    $sql .= ' inner join `saas_lazada_user` as slu on slu.`lazada_uid`=sla.`lazada_uid` ';
// 		    $where .= ' AND slu.`platform` in ("'.implode('","', $platforms).'") ' ;
		    // saas_lazada_autosync 也添加platform,site字段
            if(is_array($platforms)){
                $where .= ' AND sla.`platform` in ("'.implode('","', $platforms).'") ' ;
            } else {
                $where .= ' AND sla.`platform` = "'.$platforms.'" ' ;
            }
		}
		
		$command=$connection->createCommand($sql." where ".$where." order by sla.next_execution_time asc limit 30 ");
		
// 		$command=$connection->createCommand('select `id` from  `saas_lazada_autosync` where `is_active` = 1 and lazada_uid=830 AND '.
// 		        ' `status` <> 4 AND `error_times` < 10 AND `type`='.$type );
		
		$allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
		    echo "++++++++dataReader->read() ".$row['id']."\n";
		    $timeMS1=TimeUtil::getCurrentTimestampMS();
		    
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockLazadaAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
	
			//	\Yii::info("lazada_get_order_list_by_finish gotit jobid=$backgroundJobId start","file");
			// 			echo "++dhgate_get_order_list_by_finish gotit jobid=$backgroundJobId start \n";
			$hasGotRecord=true;  // 抢到记录
	
			//3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
			//3.1 整理请求参数
		//	$config=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
			$tempConfig=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
				
	
			$start_time=$SAA_obj->last_binding_time-30*24*3600;
			$end_time = $SAA_obj->last_binding_time;
			//3.2 访问proxy并保存结果
			
			// dzt20161129 添加lazada get order list请求offset
			// 如果有offset 即上次start_time - end_time 的订单未获取完
			$offset = self::_getAutoSyncOffset($SAA_obj->id);
			if(!empty($offset)) {
			    $start_time = $SAA_obj->start_time;
			    $end_time = $SAA_obj->end_time;
			}else{
			    $offset = 0;
			}
			
			$timeMS2=TimeUtil::getCurrentTimestampMS();
			list($ret,$errorMessage)=self::_getOrderListAndSaveToQueue($config,$start_time,$end_time,$SAA_obj,"createTime",$offset,'pending');
			$timeMS3=TimeUtil::getCurrentTimestampMS();
			//list($ret,$errorMessage)=self::_getOrderListAndSaveToQueue($config,$start_time,$end_time,$SAA_obj,"createTime");
			if ($ret==false){
			    Yii::info("getOrderListOldFirst platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.", $start_time,$end_time,$type --- error message=$errorMessage,t2_1=".($timeMS2-$timeMS1).",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
				$SAA_obj->message=$errorMessage;
				$SAA_obj->error_times=$SAA_obj->error_times+1;
				$SAA_obj->status=3;
				//$SAA_obj->last_finish_time=$nowTime;//上一次运行时间
				$SAA_obj->next_execution_time=$nowTime+$failRetryPeriod;
				$SAA_obj->update_time=$nowTime;
				$SAA_obj->save(false);
				continue;
			}
			
			if (!true){
			    $newFetchPeriod = 6 * $fetchPeriod;
			}else{
			    $newFetchPeriod = $fetchPeriod;
			}
			
			$SAA_obj->start_time=$start_time;
			$SAA_obj->end_time=$end_time;
			
            // dzt20161130 由于 old second 任务已经被关闭了，所以这里修改一下完成status		
			$offset = self::_getAutoSyncOffset($SAA_obj->id);
			$SAA_obj->status = !empty($offset)?2:4;
			
			$SAA_obj->error_times = 0;
			$SAA_obj->message="";
			$SAA_obj->update_time=$nowTime;
			$SAA_obj->next_execution_time=$nowTime+$newFetchPeriod;
			$SAA_obj->save (false);			
	
			Yii::info("getOrderListOldFirst platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.",t2_1=".($timeMS2-$timeMS1).",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
		}
	
		return $hasGotRecord;
	}	
	


	/**
	 * 旧订单的后续拉取。绑定的时间点之前，n天之外create的订单。
	 */
	public static function getOrderListOldSecond($platforms=false){
		echo "++++++++++++getOrderListOldSecond \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=\Yii::$app->db;
		$oldestOrderDay=50; //最多拉取60天之前的订单
		$type = 1;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单
		$hasGotRecord=false;//是否抢到账号
		$fetchPeriod=20*60; //秒为单位. 2次后台自动拉取的时间间隔
		$failRetryPeriod=5*60;// 失败重试
		
		//2. 从账户同步表（订单列表同步表提取带同步的账号。
		$nowTime=time();
		// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题，4---旧订单拉取全部完成
// 		$command=$connection->createCommand('select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND '.
// 				' `status` in (0,2,3) AND end_time>0 AND `error_times` < 10 AND `type`='.$type .' AND  next_execution_time<'.$nowTime .
// 		        ' order by next_execution_time asc limit 30 ');
	
		// dzt20161227 加入平台过滤
		$sql = 'select `id` from  `saas_lazada_autosync` as sla ';
		$where = 'sla.`is_active` = 1 AND sla.`status` in (0,2,3) AND sla.`error_times` < 10 AND sla.`type`='.$type.' AND sla.next_execution_time<'.$nowTime;
		if(!empty($platforms)){
// 		    $sql .= ' inner join `saas_lazada_user` as slu on slu.`lazada_uid`=sla.`lazada_uid` ';
// 		    $where .= ' AND slu.`platform` in ("'.implode('","', $platforms).'") ' ;
		    // saas_lazada_autosync 也添加platform,site字段
		    if(is_array($platforms)){
		        $where .= ' AND sla.`platform` in ("'.implode('","', $platforms).'") ' ;
		    } else {
		        $where .= ' AND sla.`platform` = "'.$platforms.'" ' ;
		    }
		}
		
		$command=$connection->createCommand($sql." where ".$where." order by sla.next_execution_time asc limit 30 ");
		
		$allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			// 先判断是否真的抢到待处理账号
			$timeMS1=TimeUtil::getCurrentTimestampMS();
			$SAA_obj = self::_lockLazadaAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
	
			//	\Yii::info("lazada_get_order_list_by_finish gotit jobid=$backgroundJobId start","file");
			// 			echo "++dhgate_get_order_list_by_finish gotit jobid=$backgroundJobId start \n";
			$hasGotRecord=true;  // 抢到记录
	
			//3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
			//3.1 整理请求参数
			//$config=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
			$tempConfig=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
				
	
			$start_time=$SAA_obj->start_time-10*24*3600;
			$end_time = $SAA_obj->start_time;
			
			// dzt20161129 添加lazada get order list请求offset
			// 如果有offset 即上次start_time - end_time 的订单未获取完
			$offset = self::_getAutoSyncOffset($SAA_obj->id);
			if(!empty($offset)) {
			    $start_time = $SAA_obj->start_time;
			    $end_time = $SAA_obj->end_time;
			}else{
			    $offset = 0;
			}
			
			//3.2 访问proxy并保存结果
			$timeMS2=TimeUtil::getCurrentTimestampMS();
			list($ret,$errorMessage)=self::_getOrderListAndSaveToQueue($config,$start_time,$end_time,$SAA_obj,"createTime",$offset);
			$timeMS3=TimeUtil::getCurrentTimestampMS();
			if ($ret==false){
			    Yii::info("getOrderListOldSecond platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.", $start_time,$end_time,$type --- error message=$errorMessage,t2_1=".($timeMS2-$timeMS1).",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
				$SAA_obj->message=$errorMessage;
				$SAA_obj->error_times=$SAA_obj->error_times+1;
				$SAA_obj->status=3;
				//$SAA_obj->last_finish_time=$nowTime;//上一次运行时间
				$SAA_obj->next_execution_time=$nowTime+$failRetryPeriod;
				$SAA_obj->update_time=$nowTime;
				$SAA_obj->save(false);
				continue;
			}
			
			if (!true){
			    $newFetchPeriod = 6 * $fetchPeriod;
			}else{
			    $newFetchPeriod = $fetchPeriod;
			}
			
			$SAA_obj->start_time=$start_time;
			$SAA_obj->end_time=$end_time;
			if ($start_time<$SAA_obj->last_binding_time-$oldestOrderDay*24*3600) $SAA_obj->status = 4;
			else 	$SAA_obj->status = 2;
			$SAA_obj->error_times = 0;
			$SAA_obj->message="";
			$SAA_obj->update_time=$nowTime;
			$SAA_obj->next_execution_time=$nowTime+$newFetchPeriod;
			$SAA_obj->save (false);
			
			Yii::info("getOrderListOldSecond platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.",t2_1=".($timeMS2-$timeMS1).
			",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
				
		}
	
		return $hasGotRecord;
	}	
	
	
	
	
	
	/**
	 * 指定时间段，从proxy中获取订单list并保存到QueueLazadaGetorder
	 * @param unknown $config
	 * @param unknown $start_time
	 * @param unknown $end_time
	 * @param unknown $SAA_obj
	 * @param unknown $type---  "createTime" or "updateTime"
	 * @param int $offset get order list api offset
	 */
	private static function _getOrderListAndSaveToQueue($config,$start_time,$end_time,$SAA_obj,$type="updateTime",$offset=false,$status=false){
		echo "_getOrderListAndSaveToQueue  $start_time,$end_time,$type \n";
		
		if ($type=="createTime"){
			$reqParams=array(
					"CreatedAfter"=>gmdate("Y-m-d\TH:i:s+0000",$start_time),
					"CreatedBefore"=>gmdate("Y-m-d\TH:i:s+0000",$end_time)
			);				
		}else{
			$reqParams=array(
					"UpdatedAfter"=>gmdate("Y-m-d\TH:i:s+0000",$start_time),
					"UpdatedBefore"=>gmdate("Y-m-d\TH:i:s+0000",$end_time)
			);				
		}
		
		// dzt20170614 接口只支持filter一个状态
		if(!empty($status)){
		    $reqParams['status'] = $status;
		}
		
		$timeout = 60;
		if('updateTime2' == $type){
			$timeout = 300;
		}
		
		// dzt20161129 _getOrderListAndSaveToQueue未传入$offset参数则，请求接口不带offset参数
		if($offset !== false)
            $reqParams['offset'] = $offset;
		
		// dzt20170203 设置请求的Proxy 参数
		LazadaProxyConnectHelper::$PROXY_INDEX = $SAA_obj->lazada_uid % 2;
		
		print_r($reqParams);
		$timeMS1=TimeUtil::getCurrentTimestampMS();
		$result=LazadaInterface_Helper::getOrderList($config,$reqParams,$timeout);
		$timeMS2=TimeUtil::getCurrentTimestampMS();
		print_r($result);
		
		$nowTime = time();
		
		if ($result["success"]===false){
		    Yii::info("_getOrderListAndSaveToQueue platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.", $start_time,$end_time,$type --- error message=".$result["message"].",t2_1=".($timeMS2-$timeMS1),"file");
			return array(false,$result["message"]);
		}
		
		// 注意！！！！！ dzt20161129 要留意 如果有offset 要输入$start_time,$end_time直接为$SAA_obj的
		// 如果是处理后的$start_time,$end_time则会导致上次拉取未完成，而漏单
		if($offset !== false){
		    if ($result["response"]["hasGotAll"] == 1) {
		        if($offset != 0){
		            self::_setAutoSyncOffset($SAA_obj->id, 0);
		            Yii::info("_getOrderListAndSaveToQueue get all orders, unset offset done： $start_time,$end_time,$type","file");
		        }
		    }else{
		        self::_setAutoSyncOffset($SAA_obj->id, $result["response"]["offset"]);// 接收下次请求的offset
		        Yii::info("_getOrderListAndSaveToQueue hasn't get all orders,last_offset:$offset,next_offset:".$result["response"]["offset"].",$start_time,$end_time,$type","file");
		    }
		}
		
		//	保存数据到同步订单详情队列
		$ordersArr=$result["response"]["orders"];
		$orderNum=0;
		foreach ( $ordersArr as $one ) {
			$orderNum++;
			// lazada订单的产生时间就是付款时间（其实是付款并且lazada自动审核通过的时间）
			//2015-08-26T22:18:52+0800 时间转成 时间戳
			$one["CreatedAt"]=TimeUtil::getTimestampFromISO8601($one["CreatedAt"]);
			$one["UpdatedAt"]=TimeUtil::getTimestampFromISO8601($one["UpdatedAt"]);
		
			if(!empty($one["AddressUpdatedAt"]))
				$one["AddressUpdatedAt"]=TimeUtil::getTimestampFromISO8601($one["AddressUpdatedAt"]);
			if(!empty($one["PromisedShippingTime"]))
				$one["PromisedShippingTime"]=TimeUtil::getTimestampFromISO8601($one["PromisedShippingTime"]);
			
			// dzt20161020 5791 lazada_uid 679马来站，订单id 7180062 出现两个status(pending,canceled) 其中两个产品一个是pending 状态，一个canceled
			if(!empty($one["Statuses"]["Status"]) && is_array($one["Statuses"]["Status"])){
				if(in_array('pending',$one["Statuses"]["Status"])){
					$orderSourceStatus = 'pending';
				}else{
					$orderSourceStatus = $one["Statuses"]["Status"][0];
				}
			}elseif(!empty($one["Statuses"]["Status"])){// dzt20161116 lazada改变了返回结构
				$orderSourceStatus = $one["Statuses"]["Status"];
			}elseif(!empty($one["Statuses"][0])){// dzt20161116 修改后的结构貌似一定是数组说
			    if(in_array('pending',$one["Statuses"])){
			        $orderSourceStatus = 'pending';
			    }else{
			        $orderSourceStatus = $one["Statuses"][0];
			    }
			}else{
			    return array(false,"can not get Statuses from order");	
			}
			
			// dzt20151119 出现 linio 墨西哥 订单OrderId 与 lazada 马来西亚订单OrderId 相同情况，加上lazada_uid 过滤
			// dzt20171218 绑定账号解绑不删，重新绑到其他小老板账号里面账号里面，这里没有过滤puid导致订单更新到之前的小老板账号里面
			$QAG_obj = QueueLazadaGetorder::findOne(['orderid'=>$one['OrderId'], 'lazada_uid'=>$SAA_obj->lazada_uid, 'puid'=>$SAA_obj->puid]);
		
			$nowTime=time ();
			if (isset ( $QAG_obj )) {
				if('updateTime2' == $type){// 漏单检查处理
					$order_info = json_decode($QAG_obj->order_info,true); 
					if($one["UpdatedAt"] > $order_info["UpdatedAt"]){
						$QAG_obj->status = 0;
						$QAG_obj->order_status=$orderSourceStatus;
						$QAG_obj->order_info = json_encode ( $one );
						$QAG_obj->update_time = $nowTime;
						$QAG_obj->is_active=1;
						$QAG_obj->save ();
					}
				}else{
					$QAG_obj->status = 0;
					$QAG_obj->order_status = $orderSourceStatus;
					$QAG_obj->order_info = json_encode ( $one );
					$QAG_obj->update_time = $nowTime;
					$QAG_obj->is_active=1;
					$QAG_obj->save ();
				}
			} else {
				$QAG_obj = new QueueLazadaGetorder();
				$QAG_obj->puid = $SAA_obj->puid;
				$QAG_obj->lazada_uid = $SAA_obj->lazada_uid;
				$QAG_obj->platform = $SAA_obj->platform;
				$QAG_obj->site = $SAA_obj->site;
				$QAG_obj->status = 0;
				$QAG_obj->is_active=1;
				$QAG_obj->orderid = $one ['OrderId'];
				$QAG_obj->error_times = 0;
				$QAG_obj->order_info = json_encode ( $one );
				$QAG_obj->last_finish_time = 0;
				$QAG_obj->create_time = $nowTime;
				$QAG_obj->update_time = $nowTime;
				$QAG_obj->order_status = $orderSourceStatus;
					
				$QAG_obj->gmtcreate = 0;
				
		
				$QAG_obj->save ();
			}
		}
		
		$timeMS3=TimeUtil::getCurrentTimestampMS();
		Yii::info("_getOrderListAndSaveToQueue platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.", $start_time,$end_time,$type --- orderNum=$orderNum,t2_1=".($timeMS2-$timeMS1).
		",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
			
		return array(true,"");	

		
	}
	
	/**
	 * items获取的错误处理
	 * @param unknown $queueOrderForSync
	 * @param unknown $message
	 */
	private static function _handleFetchItemsError($queueOrderForSync,$message){
		$queueOrderForSync->status=3;
		$queueOrderForSync->error_times=$queueOrderForSync->error_times+1;
		$queueOrderForSync->message=$message;
		$queueOrderForSync->update_time=time();
		$queueOrderForSync->save(false);
		//\Yii::error("orderid:".$queueOrderForSync->order_id."  ".$message,"file");
	}	
	
	
	/**
	 * 保存order的header信息到user_库的lazada_order表
	 * @param $orderHeaderArr---amazon的返回的order header信息（就是amazon order list接口返回的其中1个订单的信息）
	 * @param $merchantId
	 * @param $marketPlaceId
	 * @return array($ret,$message)
	 * $ret--- true or false
	 */
	private static function _saveLazadaOriginHeaderByArr($orderHeaderArr,$lazadaApiEmail){
		echo "_saveLazadaOriginHeaderByArr \n";

		$orderHeaderArr["lazada_api_email"]=$lazadaApiEmail;
		$orderHeaderArr["AddressBilling"]=json_encode($orderHeaderArr["AddressBilling"]);
		$orderHeaderArr["AddressShipping"]=json_encode($orderHeaderArr["AddressShipping"]);
		$orderHeaderArr["Statuses"]=json_encode($orderHeaderArr["Statuses"]);

		
		$lazadaOrder=LazadaOrder::find()->where(["OrderId"=>$orderHeaderArr["OrderId"]])->one();
		
        $nowTime=time();		
		if ($lazadaOrder){
		//已经存在
			$lazadaOrder->attributes=$orderHeaderArr;
			$lazadaOrder["create_time"]=$nowTime;
		}else{
			$lazadaOrder=new LazadaOrder();
			$lazadaOrder->attributes=$orderHeaderArr;
		}
		
		$lazadaOrder["update_time"]=$nowTime;
	
		//TODO  save(false) for good performance
		if ($lazadaOrder->save()) return array(true,"");
		$errorMessage="_saveLazadaOriginHeaderByArr orderid:".$orderHeaderArr["OrderId"].print_r($lazadaOrder->errors,true);
		echo "$errorMessage \n";
		//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"",$errorMessage,"error");
//		Yii::info("err:".$errorMessage,"file");
//		Yii::error($errorMessage,"file");
		return array(false,$errorMessage);
				
	}	
	
	//保存lazada的订单的items信息到数据库
	private static function _saveLazadaOriginItemsByArr($itemsArr){
		foreach($itemsArr as $itemInfo){

			//2015-08-26T22:18:52+0800 时间转成 时间戳
			$itemInfo["CreatedAt"]=TimeUtil::getTimestampFromISO8601($itemInfo["CreatedAt"]);
			$itemInfo["UpdatedAt"]=TimeUtil::getTimestampFromISO8601($itemInfo["UpdatedAt"]);
				
			
			$lazadaOrderDetail=LazadaOrderItems::find()->where(["OrderItemId"=>$itemInfo["OrderItemId"]])->one();

			if ($lazadaOrderDetail===null){
				$lazadaOrderDetail=new LazadaOrderItems();
			}
			$lazadaOrderDetail->attributes=$itemInfo;
			if (!$lazadaOrderDetail->save(false)){
				$errorMessage="lazadaOrderDetail->save() orderitemid:".$itemInfo["OrderItemId"]." ".print_r($lazadaOrderDetail->errors,true);
				echo $errorMessage." \n";
				//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"",$errorMessage,"error");
				Yii::error($errorMessage,"file");
				return array(false,$errorMessage);
			}
		}
	
		return array(true,"");
	}
	
	//根据item信息，计算order header subtotal,shipping cost,total discount 这3个信息在lazada的原始header中没有包括
	public static function _caculateTotalAmount($orderItems) {
		$subtotal=0;
		$shippingCost=0;
		$totalDiscount=0;
		foreach($orderItems as $item){
			$subtotal=$subtotal+$item["ItemPrice"];
			$shippingCost=$shippingCost+$item["ShippingAmount"];
			// dzt20170621 有客户提出平台给出的折扣券不影响卖家，卖家收款依然以原价收款，这样导致统计模块统计利润可能有问题
			$totalDiscount=$totalDiscount+$item["ItemPrice"]-$item["PaidPrice"];
			
			//$itemPrice=isset($item["ItemPrice"])?$item["ItemPrice"]:0;
		//	$subtotal=$subtotal+$itemPrice*$item["QuantityOrdered"];
			//$shipDiscount=isset($item["ShippingDiscount"])?$item["ShippingDiscount"]:0;
			//$promotionDiscount=isset($item["PromotionDiscount"])?$item["PromotionDiscount"]:0;
			//$shippingPrice=isset($item["ShippingPrice"])?$item["ShippingPrice"]:0;
		}
	
		return array($subtotal,$shippingCost,$totalDiscount);
	}
	
	/**
	 * 把lazada的订单信息header和items 同步到eagle系统中user_库的od_order和od_order_item
	 * @param $platformAccountId----seller email
	 * @param $platformSiteId --- 销售站点
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	private static function _saveLazadaOrderToEagle($platformAccountId,$platformSiteId,$orderHeaderInfo,$orderItems,$uid,$platform="lazada"){

		//eagle订单header的字段解释
		//grand_total---buyer付的钱 = 产品总价+运费-折扣
		//sub_total--- 产品总价		
		//根据item信息，计算order header subtotal,shipping cost,total discount 这3个信息在amazon的原始header中没有包括
		list($subtotal,$shippingCost,$totalDiscount)=self::_caculateTotalAmount($orderItems);
	
		//整理导入eagle平台的订单信息
	
		// dzt20161227 comment lazada订单已经支持item多状态
		//eagle的订单状态
// 		if (count($orderHeaderInfo["Statuses"])>1){
// 			echo "lazada too much statuses info".print_r($orderHeaderInfo,true)." \n";
// 			\Yii::error("lazada too much statuses info".print_r($orderHeaderInfo,true),"file");
// 			return false;
// 		}
		
		// $lazadaOrderStatus=$orderHeaderInfo["Statuses"]["Status"];
		// dzt20161020 5791 lazada_uid 679马来站，订单id 7180062 出现两个status(pending,canceled) 其中两个产品一个是pending 状态，一个canceled
		if(!empty($orderHeaderInfo["Statuses"]["Status"]) && is_array($orderHeaderInfo["Statuses"]["Status"])){
			if(in_array('pending',$orderHeaderInfo["Statuses"]["Status"])){
				$lazadaOrderStatus = 'pending';
			}else{
				$lazadaOrderStatus = $orderHeaderInfo["Statuses"]["Status"][0];
			}
		}elseif(!empty($orderHeaderInfo["Statuses"]["Status"])){// dzt20161116 lazada改变了返回结构{
			$lazadaOrderStatus = $orderHeaderInfo["Statuses"]["Status"];
		}elseif(!empty($orderHeaderInfo["Statuses"][0])){// dzt20161116 修改后的结构貌似一定是数组说
		    if(in_array('pending',$orderHeaderInfo["Statuses"])){
		        $lazadaOrderStatus = 'pending';
		    }else{
		        $lazadaOrderStatus = $orderHeaderInfo["Statuses"][0];
		    }
		}else{
		    return array('success'=>1,'message'=>"can not get Statuses from order:".$orderHeaderInfo["OrderId"]);
		}
		
		$order_status=self::$LAZADA_EAGLE_ORDER_STATUS_MAP[$lazadaOrderStatus];
	
		$is_manual_order=0;
		/*if (strtolower($orderHeaderInfo["Status"])=="canceled"){
		 //挂起，需要及时处理的订单，可能不需要发货
		$is_manual_order = 1;
		}*/
		//订单类型 MFN或AFN(FBA)----------非FBA的订单，默认不传入order_type 参数或该参数值传入为空
		if (!isset($orderHeaderInfo["type"]) or $orderHeaderInfo["type"]=="") $order_type="MFN";
		else $order_type=$orderHeaderInfo["type"];
	
		$consignee_country="";
		if (isset($orderHeaderInfo['CountryCode'])){
			$sysCountry=SysCountry::findOne(['country_code'=>$orderHeaderInfo['CountryCode']]);
			if ($sysCountry<>null) $consignee_country=$sysCountry->country_en;
		}
	
		$currency=$orderItems[0]["Currency"];
		
		//'consignee_country'=>$addressShipInfo['Country'],
		$addressShipInfo=$orderHeaderInfo['AddressShipping'];
		$consigneeCountry=$addressShipInfo['Country'];
		if("Kenya" == $consigneeCountry)// dzt20170720 jumia 返回国家为Kenya 数据表为Kenya Coast Republic
		    $consigneeCountry = "Kenya Coast Republic";
		
		if("Côte d’Ivoire" == $consigneeCountry)// dzt20180327 jumia 返回国家为Côte d’Ivoire 数据表为Cote d Ivoire (Ivory Coast)
		    $consigneeCountry = "Cote d Ivoire (Ivory Coast)";
		
		$consigneeCountryCode = "";
		if(!empty($consigneeCountry)){// dzt20191102 发现cancel订单没有地址信息
		$tempObj=SysCountry::findOne(['country_en'=>$consigneeCountry]);
		if ($tempObj===null){
			echo "lazada consigneeCountry:$consigneeCountry not in Sys_Country table \n";
			\Yii::error("lazada consigneeCountry:$consigneeCountry not in Sys_Country table \n","file");
			return array('success'=>1,'message'=>"lazada consigneeCountry:$consigneeCountry not in Sys_Country table" );				
		} 
		$consigneeCountryCode=$tempObj->country_code;
		}
		
	
		//物流信息
		$orderShipped = array();
		// dzt20161208 puid 602客户再次提出 All order items must have status Pending or Ready To Ship问题
		// 所以这里记录产品的状态到addi_info，标记发货/lgs上传的时候去掉 不合适状态的标记发货 
		$itemStatus = array();
		$orderType = 'FBL';
		$order_source_shipping_method = "";
		foreach ($orderItems as &$orderItem){
		    if(empty($itemStatus[$orderItem['OrderItemId']])){
		        $itemStatus[$orderItem['OrderItemId']] = $orderItem['Status'];
		    }
		    
		    // dzt20170125 fbl支持，fbl 订单不需任何操作，lazada自动发货，订单状态目前已知有pending,shipped,delivered。即可能一样有多个种订单状态
		    // lazada 客服指出可能存在一个订单里面有两个item 的ShippingType分别是Dropshipping 和Own Warehouse的订单
		    // 所以当出现一个item是Dropshipping的话，这个订单就被认为是Dropshipping的
		    // TODO 和oms 沟通，处理哪些item 可发货问题
		    if($orderItem['ShippingType'] == "Dropshipping"){
		        $orderType = 'FBM';// fullfillment by merchant
		    }else{
		        $orderItem['Status'] = $orderItem['ShippingType'];
		    }
		    
		    // dzt20180104 for linio要求添加显示客选物流，@todo 实际上未确订单pending时 ShipmentProvider记录的是否就是客选物流
		    if(empty($order_source_shipping_method) && $orderItem["ShipmentProvider"]<>"")
		        $order_source_shipping_method = $orderItem["ShipmentProvider"];
		    
			if (empty($orderShipped) && $orderItem["TrackingCode"]<>""){//有物流号的话
				//foreach ($orderDeliveryList as $oneShipped){
				
			    // dzt20170710 puid:5675,lazada_uid：666 orderid 3990756 OrderNumber 202427421 出现 order item 接口获取回来是{"TrackingCode": "\"RS714291191CN \""}
			    // 导致保存失败，这里处理一下这个TrackingCode字符串
			    if(substr($orderItem["TrackingCode"],0,1) == '"')
			        $orderItem["TrackingCode"] = substr($orderItem["TrackingCode"],1);
			    
			    if(substr($orderItem["TrackingCode"],strlen($orderItem["TrackingCode"])-1,1) == '"')
			        $orderItem["TrackingCode"] = substr($orderItem["TrackingCode"],0,strlen($orderItem["TrackingCode"])-1);
			    
				$tmp = array(
						'order_source_order_id'=>$orderHeaderInfo['OrderNumber'],
						'order_source'=>$platform,
						'selleruserid'=>$platformAccountId,
						'tracking_number'=>$orderItem["TrackingCode"],
						'shipping_method_name'=>$orderItem['ShipmentProvider'],
						'addtype'=>'平台API',
				);
				//赋缺省值
			
				$orderShipped[]= array_merge(OrderHelper::$order_shipped_demo,$tmp);
				//}
// 				break;
			}
		}
		
		// dzt20200326 jumia客户反映，有平台发货的子订单 平台发货后，子订单状态覆盖了订单状态，导致小老板订单转为已完成，导致自发货产品无法发货
		if($orderType == 'FBM' && in_array("pending", $itemStatus)){
		    $lazadaOrderStatus = 'pending';
		    $order_status=self::$LAZADA_EAGLE_ORDER_STATUS_MAP[$lazadaOrderStatus];
		}
		
		// dzt20180228 jumia 客户要求标记COD 货到付款订单
		if(strtolower($platform) == "jumia" && !empty($orderHeaderInfo['PaymentMethod']) && "CashOnDelivery" == $orderHeaderInfo['PaymentMethod']){
		    $orderType = 'COD';
		}
		
		// dzt20161117 印尼站点出现 "82,000","67,000"这样的价格，保存数据库报错,ItemPrice则没有这个问题
		// dzt20170411 通过啊泉反映了解到接口返回的这个price 并没有减去discount
		// 420250150@qq.com my 'OrderId'=>"104580431" 有一个item 算出来discount 1.61 但是order_v2记录的grand_total 是20.99 和item原价一样
		$totalPrice = str_replace(',', '', $orderHeaderInfo['Price']);
		if(empty($totalPrice))// dzt20161118 lazada接口抽风，返回Price 都是0
			$totalPrice = $subtotal + $shippingCost - $totalDiscount;
			
		elseif(strtolower($platform) == "jumia")
			$totalPrice = $totalPrice - $totalDiscount;// dzt20171220 $totalPrice + $shippingCost - $totalDiscount 改为目前 jumia第一次发现有运费，确认$orderHeaderInfo['Price'] 包含了运费，修改公式
		else // dzt20170605 加上运费 折扣计算
		    $totalPrice = $totalPrice + $shippingCost - $totalDiscount;
		    
		//1.  订单header信息
		$order_arr=array(//主订单数组
				'order_status'=>$order_status,
				'order_source_status'=>$lazadaOrderStatus,
				'is_manual_order'=>$is_manual_order,
				'order_source'=>$platform,
				'order_type'=>$orderType,  
				'order_source_site_id'=>$platformSiteId,	
				'order_source_order_id'=>$orderHeaderInfo['OrderNumber'],  //订单来源平台订单号
				'selleruserid'=>$platformAccountId,
				'source_buyer_user_id'=>$orderHeaderInfo['CustomerFirstName']." ".$orderHeaderInfo['CustomerLastName'],	//来源买家用户名
				'order_source_create_time'=>$orderHeaderInfo["CreatedAt"], //时间戳
				'subtotal'=>$subtotal,
				'shipping_cost'=>$shippingCost,
				'grand_total'=>$totalPrice,
				'discount_amount'=>$totalDiscount,
				'currency'=>$currency,
				'consignee'=>$addressShipInfo['FirstName']." ".$addressShipInfo['LastName'],
				'consignee_postal_code'=>$addressShipInfo['PostCode'],
				'consignee_city'=>$addressShipInfo['City'],
				'consignee_phone'=>$addressShipInfo['Phone'],
				'consignee_mobile'=>$addressShipInfo['Phone2'],
		//		'consignee_email'=>isset($orderHeaderInfo['BuyerEmail'])?$orderHeaderInfo['BuyerEmail']:'',
				'consignee_country'=>$consigneeCountry,				
				'consignee_country_code'=>$consigneeCountryCode,
				'consignee_province'=>empty($addressShipInfo['Region'])?"":$addressShipInfo['Region'],
				'consignee_address_line1'=>$addressShipInfo['Address1'],
				'consignee_address_line2' =>$addressShipInfo['Address2']." ".$addressShipInfo['Address3']." ".$addressShipInfo['Address4']." ".$addressShipInfo['Address5'],
// 				'consignee_address_line3' =>$addressShipInfo['Address3']." ".$addressShipInfo['Address4']." ".$addressShipInfo['Address5'],// dzt20170516 注释 oms负责人lkh表示不要用地址3了
				'paid_time'=>$orderHeaderInfo["CreatedAt"], //时间戳  
				// dzt20160726 加最迟发货时间 到订单表做提示有些站点有，有些没有
				'fulfill_deadline'=>(!empty($orderHeaderInfo['PromisedShippingTime'])&&is_numeric($orderHeaderInfo['PromisedShippingTime']))?$orderHeaderInfo['PromisedShippingTime']:0,
		        'order_source_shipping_method'=>$order_source_shipping_method,// dzt20180104
		        
				'addi_info'=>json_encode(array(
					'lgs_related'=>array(
						'OrderId'=>$orderHeaderInfo["OrderId"],
						'PaymentMethod'=>$orderHeaderInfo["PaymentMethod"],
					    'itemStatus'=>$itemStatus,
					)
				)),// dzt20160723 for 准备去除原始订单保存，这样需要把其他读取到原始订单的字段加到这里 @todo 留意是否存在覆盖情况
		//amazon是没有返回发货时间的！！！！！
		//当订单是FBA的时候，LatestDeliveryDate貌似为空
		//'delivery_time'=>isset($orderHeaderInfo['LatestDeliveryDate'])?strtotime($orderHeaderInfo['LatestDeliveryDate']):0, //时间戳
		//'user_message'=>json_encode($OrderById['orderMsgList']),
		        'orderShipped'=>$orderShipped,
		);

		//2. 订单的items信息
		$userMessage = '';
		$orderitem_arr=array();//订单商品数组
		foreach ($orderItems as $one){
			$productUrl = "";
			if(!empty($one['ProductDetailUrl'])){
			    $productUrl = $one['ProductDetailUrl'];
			}elseif (isset($one['ProductUrl']) and $one['ProductUrl']<>NULL){
				$productUrl = $one['ProductUrl'];
			}
			
			$photoPrimary = "";
			if(!empty($one['productMainImage'])){
			    $photoPrimary = $one['productMainImage'];
			}elseif(!empty($one['SmallImageUrl'])){
			    $photoPrimary = $one['SmallImageUrl'];
			}
			
			$orderItemsArr = array(
					'order_source_order_id'=>$orderHeaderInfo['OrderNumber'],  //订单来源平台订单号
					'order_source_order_item_id'=>$one['OrderItemId'],
					//'order_source_transactionid'=>$one['childid'],//订单来源交易号或子订单号
					'order_source_itemid'=>$one['ShopSku'],//产品ID listing的唯一标示
					//'sent_quantity'=>$one['QuantityShipped'],  //lolo add -- 速卖通貌似没有的
					//'promotion_discount'=>isset($one['PromotionDiscount'])?$one['PromotionDiscount']:0,   //lolo add -- 速卖通貌似没有的
					'shipping_price'=>$one['ShippingAmount'], 
					//'shipping_discount'=>isset($one['ShippingDiscount'])?$one['ShippingDiscount']:0,  //lolo add -- 速卖通貌似没有的
						
					'sku'=>empty($one['Sku'])?$one['ShopSku']:$one['Sku'],// dzt20170307 for Linio pe,co 有订单item 返回 Sku为空
					'price'=>$one['ItemPrice'],//如果订单是取消状态，该字段amazon不会返回
					'ordered_quantity'=>1,//下单时候的数量
					'quantity'=>1,  //需发货的商品数量,原则上和下单时候的数量相等，订单可能会修改发货数量，发货的时候按照quantity发货
					'product_name'=>$one['Name'],//下单时标题
					'photo_primary'=>$photoPrimary,//商品主图冗余
					'product_url'=>$productUrl,
					//	'desc'=>$one['memo'],//订单商品备注,
					//	'product_attributes'=>$attr_str,//商品属性
					//'product_unit'=>$one['productunit'],//单位
					//'lot_num'=>$one['lotnum'],//单位数量
			        'platform_status'=>$one['Status'],// dzt20161209  配合oms新改动，当item 如下字段出现更改，则更新item信息
			);
			//赋缺省值
			$orderitem_arr[]=array_merge(OrderHelper::$order_item_demo,$orderItemsArr);
			//$userMessage = $one['memo'];
		}
	
		//订单商品
		$order_arr['items']=$orderitem_arr;
		//订单备注
		$order_arr['user_message']= "";
		//赋缺省值
		$myorder_arr[$uid]=array_merge(OrderHelper::$order_demo,$order_arr);
	
		//3.  订单header和items信息导入到eagle系统
// 		\Yii::info("before OrderHelper::importPlatformOrder info uid:$uid:".json_encode($myorder_arr),"file");
		try{
			$result =  OrderHelper::importPlatformOrder($myorder_arr);
		}catch(\Exception $e){
			echo "OrderHelper::importPlatformOrder fails. Exception:File:".$e->getFile().",".$e->getLine().",Line:".$e->getMessage().",trace:".$e->getTraceAsString()."  \n";
			\Yii::error("OrderHelper::importPlatformOrder fails.  Exception error:".$e->getMessage()." trace:".$e->getTraceAsString(),"file");
	
			return array('success'=>1,'message'=>"importPlatformOrder Exception:File:".$e->getFile().",Line:".$e->getLine().',message:'.$e->getMessage());
		}
	//	echo "after OrderHelper::importPlatformOrder result:".print_r($result,true);
		// ！！！注意  result['success']的返回值。    0----表示ok,1---表示fail
		if ($result['success']===1){
			//	SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","OrderHelper::importPlatformOrder fails. error:".$result['message'],"error");
			\Yii::error("OrderHelper::importPlatformOrder fails.  error:".$result['message'],"file");
		}
	
	
	
		return $result;
	}	
	
	/**
	 * 后台触发多进程， 从queue_lazada_getorder根据订单id，拉取amazon的订单items
	 * 这里才会把订单信息传递到eagle中！！！！
	 */
	public static function cronAutoFetchOrderItems($platforms = false){
	    //1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
	    $ret=self::checkNeedExitNot();
	    if ($ret===true) exit;
	    
		$backgroundJobId=self::getCronJobId();
		$platformTag = '';
		if(is_array($platforms)){
		    $platformTag = implode(',', $platforms);
		}else{
		    $platformTag = $platforms;
		}
		Yii::info("entering lazada_cronAutoFetchOrderItems cronJobId:$backgroundJobId ,platform:$platformTag","file");
		echo "entering lazada_cronAutoFetchOrderItems cronJobId:$backgroundJobId ,platform:$platformTag \n";
		$totalTimeMS1=TimeUtil::getCurrentTimestampMS();
		$hasGotRecord = false;
			
		//提取没有拉取过detail或者失败次数不太10次。  
		//TODO 后面需要加入next_execution_time来控制失败重试间隔
		$query = QueueLazadaGetorder::find() 
		->where('(status=0 or status =3) and error_times<10')
// 		->where('orderid=104839332')
		->andWhere('is_active=1');
		
		// dzt20161227 加入平台过滤
		if(!empty($platforms)){
		    $query->andWhere(['platform'=>$platforms]);
		}
		
		$SAA_objs = $query->orderBy('update_time asc')->limit(100)->all();
		
		
		$totalTimeMS2=TimeUtil::getCurrentTimestampMS();
		
		echo "cronJobId:$backgroundJobId $platformTag count:".count($SAA_objs).",totalT2_1=".($totalTimeMS2-$totalTimeMS1)." \n";
		Yii::info("lazada_fetch_order_items $platformTag cronJobId:$backgroundJobId -- There are ".count($SAA_objs)." orders waiting for fetching detail by id,totalT2_1=".($totalTimeMS2-$totalTimeMS1),"file");
		//$syncIdAccountMap=self::_getSyncIdAccountInfoMap();
		$allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
		if(count($SAA_objs)){
		    $lastPuid = false;
			foreach($SAA_objs as $SAA_obj) {
				// 先判断是否真的抢到待处理账号
				$nowTime = time();
				$lazadaUid=$SAA_obj->lazada_uid;
				$uid=$SAA_obj->puid;
				
				$timeMS01=TimeUtil::getCurrentTimestampMS();
				$connection=Yii::$app->db;
				// dzt20160729 status=0 or status=3防止已完成的status=2的再进入
				$command = $connection->createCommand("update queue_lazada_getorder set status=1,update_time=$nowTime where id =". $SAA_obj->id." and (status=0 or status=3) ") ;
				$affectRows = $command->execute();
				if ($affectRows <= 0)	{
					$timeMS02=TimeUtil::getCurrentTimestampMS();
					echo 'lazada_fetch_order_items skip,id:'.$SAA_obj->id.',puid:'.$SAA_obj->puid.',lazada_uid:'.$SAA_obj->lazada_uid.',t02_01='.($timeMS02-$timeMS01)." \n";
					continue; //抢不到---如果是多进程的话，有抢不到的情况
				}
				
				$timeMS02=TimeUtil::getCurrentTimestampMS();
				$SAA_obj = 	QueueLazadaGetorder::findOne($SAA_obj->id);
				$timeMS03=TimeUtil::getCurrentTimestampMS();
				echo "lazada_fetch_order_items_1 cronJobId:$backgroundJobId queueid=".$SAA_obj->id."  puid=".$uid.",t02_01=".($timeMS02-$timeMS01).",t03_02=".($timeMS03-$timeMS02).",t03_01=".($timeMS03-$timeMS01)." \n";

				$hasGotRecord=true;  // 抢到记录
				
				//1. 提取需要拉取items的订单
				//lazada_uid是saas_lazada_user数据表 的key
				$timeMS1=TimeUtil::getCurrentTimestampMS();
				if (!isset($allLazadaAccountsInfoMap[$lazadaUid])){					
					Yii::error("lazada_uid:".$lazadaUid." not exist in saas_lazada_user","file");
					$SAA_obj->status=4;//4 异常情况，不需要重试，等待it人工分析
					$SAA_obj->update_time=time();
					$SAA_obj->error_times=$SAA_obj->error_times+1;
					$SAA_obj->error_message="lazada_uid:".$lazadaUid." not exist in saas_lazada_user";
					$SAA_obj->save(false);
					continue;
				}
	
				// dzt20151215 添加同步锁的时候已经更新
// 				$SAA_obj->status=1;
// 				$SAA_obj->update_time=time();
// 				$SAA_obj->save(false);
	
				//2. 整理参数访问lazada proxy获取items的信息
				//$config=$allLazadaAccountsInfoMap[$lazadaUid];
				$tempConfig=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
				$config=array(
						"userId"=>$tempConfig["userId"],
						"apiKey"=>$tempConfig["apiKey"],
						"countryCode"=>$tempConfig["countryCode"],
				);
				
				$platform=$tempConfig["platform"];
				
				// dzt20170203
				LazadaProxyConnectHelper::$PROXY_INDEX = $SAA_obj->lazada_uid % 2;
				
				$apiParams=array(
						"OrderId"=>$SAA_obj->orderid
				);
				//echo "uid:$uid orderId:".$SAA_obj->orderid." config:".print_r($config,true)." \n";
				$timeMS2=TimeUtil::getCurrentTimestampMS();
				$itemsResult=LazadaInterface_Helper::getOrderItems($config,$apiParams);
				$timeMS3=TimeUtil::getCurrentTimestampMS();
				
				echo "lazada_fetch_order_items_2 cronJobId:$backgroundJobId queueid=".$SAA_obj->id." after LazadaInterface_Helper::getOrderItems puid:$uid itemsResult:".print_r($itemsResult,true)." \n";
				//\Yii::info("after LazadaInterface_Helper::getOrderItems puid:$uid itemsResult:".print_r($itemsResult,true),"file");
				\Yii::info("after LazadaInterface_Helper::getOrderItems puid:$uid ","file");
				
				if ($itemsResult["success"]===false) {				
					self::_handleFetchItemsError($SAA_obj,$itemsResult["message"]);
					continue;
				}
				
				$itemsArr=$itemsResult["response"]["items"][$SAA_obj->orderid];
				
				
				//3.1保存header信息
				$timeMS4=TimeUtil::getCurrentTimestampMS();
				$orderHeaderArr=json_decode($SAA_obj->order_info,true);
// 				Yii::info("uid=$uid before _saveLazadaOriginHeaderByArr","file");
// 				list($ret,$errorMessage)=self::_saveLazadaOriginHeaderByArr($orderHeaderArr, $config["userId"]);
// 				Yii::info("uid=$uid after _saveLazadaOriginHeaderByArr","file");
// 				if ($ret===false) {
// 					self::_handleFetchItemsError($SAA_obj,$errorMessage);
// 					continue;
// 				}
				
				
				//3.2保存items信息
				$timeMS5=TimeUtil::getCurrentTimestampMS();
// 				list($ret,$errorMessage)=self::_saveLazadaOriginItemsByArr($itemsArr);
				$timeMS6=TimeUtil::getCurrentTimestampMS();			
// 				if ($ret===false) {
// 					self::_handleFetchItemsError($SAA_obj,$errorMessage);
// 					continue;
// 				}
				
				
	    		//3.3保存订单信息到order_v2和order_items_v2
	    		//2位国家码
	    		if ($platform=="lazada"){
	    		     $countryCode2=LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP[$config["countryCode"]];
	    		}else{
	    			$countryCode2=strtoupper($config["countryCode"]);
	    		}
	    		
	    		//echo "puid:$uid before _saveLazadaOrderToEagle".print_r($itemsArr,true)." \n";
				$result=self::_saveLazadaOrderToEagle($config["userId"],$countryCode2, $orderHeaderArr, $itemsArr, $uid,$platform);
				$timeMS7=TimeUtil::getCurrentTimestampMS();				
				if ($result['success']===1){ // result['success']    0----ok,1---fail
					self::_handleFetchItemsError($SAA_obj,"_saveLazadaOrderToEagle() fails.  error:".$result['message']);
					continue;
				}
				
				echo "lazada_fetch_order_items_3 cronJobId:$backgroundJobId _saveLazadaOrderToEagle  \n";
				
				
				Yii::info("lazada_fetch_order_items puid=".$uid.",".$platform.
				",t02_01=".($timeMS02-$timeMS01).",t03_02=".($timeMS03-$timeMS02).",t03_01=".($timeMS03-$timeMS01).",t2_1=".($timeMS2-$timeMS1).
				",t3_2=".($timeMS3-$timeMS2).",t4_3=".($timeMS4-$timeMS3).",t5_4=".($timeMS5-$timeMS4).
				",t6_5=".($timeMS6-$timeMS5).",t7_6=".($timeMS7-$timeMS6).",t7_1=".($timeMS7-$timeMS1),"file");
					
				//5. after sync is ok,set the order item of the queue
				$SAA_obj->status=2;
				$SAA_obj->update_time=time();
				$SAA_obj->error_times=0;
				$SAA_obj->message="";
				$SAA_obj->save(false);
			}
		}
		
		return $hasGotRecord;
	}
	
	/**
	 * 后台触发多进程，批量 从queue_lazada_getorder根据订单id，拉取amazon的订单items
	 * 这里才会把订单信息传递到eagle中！！！！
	 * 
	 * dzt 2016-12-30
	 */
	public static function cronBatchAutoFetchOrderItems($platforms = false){
	    //1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
	    $ret=self::checkNeedExitNot();
	    if ($ret===true) exit;
	     
	    $backgroundJobId=self::getCronJobId();
	    $platformTag = '';
	    if(is_array($platforms)){
	        $platformTag = implode(',', $platforms);
	    }else{
	        $platformTag = $platforms;
	    }
	    
	    Yii::info("entering lazada_cronBatchAutoFetchOrderItems cronJobId:$backgroundJobId ,platform:$platformTag","file");
	    echo "entering lazada_cronBatchAutoFetchOrderItems cronJobId:$backgroundJobId ,platform:$platformTag \n";
	    
	    $totalTimeMS1=TimeUtil::getCurrentTimestampMS();
	    $hasGotRecord = false;
	    $connection=Yii::$app->db;
	    
	    //提取没有拉取过detail或者失败次数不太10次。
	    $query = QueueLazadaGetorder::find()
	    ->select('puid,lazada_uid')
	    ->where('(status=0 or status =3) and error_times<10')
	    ->andWhere('is_active=1');
	     
	    // dzt20161227 加入平台过滤
	    if(!empty($platforms)){
	        $query->andWhere(['platform'=>$platforms]);
	    }
	    
	    $SAA_groups = $query->groupBy('puid,lazada_uid')->orderBy('update_time asc')->asArray()->all();
	    
	    $totalTimeMS2=TimeUtil::getCurrentTimestampMS();
	
	    echo date("Ymd H:i:s")." cronJobId:$backgroundJobId,platform:$platformTag count group:".count($SAA_groups).",totalT2_1=".($totalTimeMS2-$totalTimeMS1)." \n";
	    Yii::info("lazada_batch_fetch_order_items entering platform:$platformTag cronJobId:$backgroundJobId -- There are ".count($SAA_groups)." groups waiting for fetching detail by id,totalT2_1=".($totalTimeMS2-$totalTimeMS1),"file");
	    //$syncIdAccountMap=self::_getSyncIdAccountInfoMap();
	    $allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
	    if(count($SAA_groups)>0){
	        foreach ($SAA_groups as $group){
	            
	            $nowTime = time();
	            $puid = $group['puid'];
	            $lazadaUid = $group['lazada_uid'];
	            $timeMS1=TimeUtil::getCurrentTimestampMS();
	            
	            if (!isset($allLazadaAccountsInfoMap[$lazadaUid])){
	                Yii::error("lazada_uid:".$lazadaUid." not exist in saas_lazada_user","file");
	                $SAA_obj->status = 4;//4 异常情况，不需要重试，等待it人工分析
	                $SAA_obj->update_time = $nowTime;
	                $SAA_obj->error_times = $SAA_obj->error_times+1;
	                $SAA_obj->error_message = "lazada_uid:".$lazadaUid." not exist in saas_lazada_user";
	                $SAA_obj->save(false);
	                continue;
	            }
	            
	            $timeMS01=TimeUtil::getCurrentTimestampMS();
	            //1. 整理参数访问lazada proxy获取items的信息
	            $tempConfig = $allLazadaAccountsInfoMap[$lazadaUid];
	            $config = array(
	                    "userId"=>$tempConfig["userId"],
	                    "apiKey"=>$tempConfig["apiKey"],
	                    "countryCode"=>$tempConfig["countryCode"],
	            );
	            $platform = $tempConfig["platform"];
	            $site = $tempConfig["countryCode"];
	            
	            $SAA_objs = QueueLazadaGetorder::find()
	            ->where('(status=0 or status =3) and error_times<10')
	            ->andWhere(['is_active'=>1,'puid'=>$puid,'lazada_uid'=>$lazadaUid])
	            ->orderBy('update_time asc')->limit(5)->all();// 5个一次 
	            
	            
	            // dzt20170621 for 测试订单重复拉取问题，未找到问题，测试结果是不会重复拉取。
// 	            $key = 'lazada_uid_'.$lazadaUid;
// 	            $var = RedisHelper::RedisGet("test_batch_auto_getitems",$key);
// 	            if(empty($var)){
// 	                RedisHelper::RedisSet("test_batch_auto_getitems",$key,1);
// 	            }else{
// 	                RedisHelper::RedisSet("test_batch_auto_getitems",$key,2);
// 	            }
	            
// 	            var_dump($key);
	            
// 	            for ($i=1 ; $i<100000000000; $i++){
// 	                $var = RedisHelper::RedisGet("test_batch_auto_getitems",$key);
// 	                if($var == 2)break;
// 	            }
	            
	            //2. 提取需要拉取items的订单
	            if(count($SAA_objs)>0){
	                $hasGotRecord=true;  // 抢到记录
	                $lastPuid = false;
	                $orderIds = array();
	                $timeMS02=TimeUtil::getCurrentTimestampMS();
	                foreach($SAA_objs as $SAA_obj){
	                    // 判断是否真的抢到待处理订单
	                    if(!array_key_exists($SAA_obj->id, $orderIds)){
	                        
// 	                        $SAA_obj->status = 1;
// 	                        $hasGot = $SAA_obj->update(false);
// 	                        if($hasGot == 0) continue;
	                         
	                        // dzt20170307 上面抢记录的方式没有where 条件，导致可能是完成了的再重新做，所以改成用下面方式抢记录
	                        $command = $connection->createCommand("update queue_lazada_getorder set status=1,update_time=$nowTime where id =". $SAA_obj->id." and (status=0 or status=3) ") ;
	                        echo "cronJobId:$backgroundJobId mark sql: update queue_lazada_getorder set status=1,update_time=$nowTime where id =". $SAA_obj->id." and (status=0 or status=3) ".PHP_EOL;
	                        $affectRows = $command->execute();
// 	                        var_dump($affectRows);
	                        if ($affectRows <= 0)	{
	                            $timeMS02=TimeUtil::getCurrentTimestampMS();
	                            echo 'cronJobId:'.$backgroundJobId.',lazada_fetch_order_items skip,id:'.$SAA_obj->id.',puid:'.$SAA_obj->puid.',lazada_uid:'.$SAA_obj->lazada_uid.',t02_01='.($timeMS02-$timeMS01)." \n";
	                            continue; //抢不到---如果是多进程的话，有抢不到的情况
	                        }
	                        
	                        $orderIds[$SAA_obj->id] = $SAA_obj->orderid;
	                    }
	                }
	                
	                // 万一抢不到
	                if(empty($orderIds)) continue;
	                
	                echo "cronJobId:$backgroundJobId,group:puid:".$puid.",lazada_uid:".$lazadaUid." count:".count($orderIds)." \n";
	                Yii::info("lazada_batch_fetch_order_items platform:$platform,site:$site -- group:puid:".$puid.",lazada_uid:".$lazadaUid.",count ".count($orderIds),"file");
	                
	                $timeMS03=TimeUtil::getCurrentTimestampMS();
	                echo "cronJobId:$backgroundJobId,lazada_batch_fetch_order_items queueIds=".implode(',', array_keys($orderIds))."  puid=".$puid.",t02_01=".($timeMS02-$timeMS01).",t03_02=".($timeMS03-$timeMS02).",t03_01=".($timeMS03-$timeMS01)." \n";
	                
	                $apiParams=array(
                        "OrderId"=>implode(',', $orderIds)
	                );
	                
	                $timeMS2=TimeUtil::getCurrentTimestampMS();
	                $itemsResult=LazadaInterface_Helper::getOrderItems($config,$apiParams);
	                $timeMS3=TimeUtil::getCurrentTimestampMS();
	                
	                echo "cronJobId:$backgroundJobId,lazada_batch_fetch_order_items queueIds=".implode(',', array_keys($orderIds))." after LazadaInterface_Helper::getOrderItems puid:$puid itemsResult:".print_r($itemsResult,true)." \n";
	                
	                if ($itemsResult["success"]===false) {
	                    foreach ($orderIds as $queueId=>$orderid){
	                        $SAA_obj = QueueLazadaGetorder::findOne(['id'=>$queueId]);
	                        self::_handleFetchItemsError($SAA_obj,$itemsResult["message"]);
	                    }
	                    continue;
	                }
	             
	                foreach ($orderIds as $queueId=>$orderid){
    					$SAA_obj = QueueLazadaGetorder::findOne(['id'=>$queueId]);
    					
    					$itemsArr=$itemsResult["response"]["items"][$SAA_obj->orderid];
    					//3.1保存header信息
    					$timeMS4=TimeUtil::getCurrentTimestampMS();
    					$orderHeaderArr=json_decode($SAA_obj->order_info,true);
// 						Yii::info("uid=$puid before _saveLazadaOriginHeaderByArr","file");
// 						list($ret,$errorMessage)=self::_saveLazadaOriginHeaderByArr($orderHeaderArr, $config["userId"]);
// 						Yii::info("uid=$puid after _saveLazadaOriginHeaderByArr","file");
// 						if ($ret===false) {
// 							self::_handleFetchItemsError($SAA_obj,$errorMessage);
// 							continue;
// 						}

    					//3.2保存items信息
    					$timeMS5=TimeUtil::getCurrentTimestampMS();
// 						list($ret,$errorMessage)=self::_saveLazadaOriginItemsByArr($itemsArr);
    					$timeMS6=TimeUtil::getCurrentTimestampMS();
// 						if ($ret===false) {
// 							self::_handleFetchItemsError($SAA_obj,$errorMessage);
// 							continue;
// 						}

    					//3.3保存订单信息到order_v2和order_items_v2
    					//2位国家码
    					if ($platform=="lazada"){
    					    $countryCode2=LazadaApiHelper::$COUNTRYCODE_COUNTRYCode2_MAP[$config["countryCode"]];
    					}else{
    					    $countryCode2=strtoupper($config["countryCode"]);
    					}
    					
//     					echo "puid:$puid before _saveLazadaOrderToEagle".print_r($itemsArr,true)." \n";
    					$result=self::_saveLazadaOrderToEagle($config["userId"],$countryCode2, $orderHeaderArr, $itemsArr, $puid,$platform);
    					$timeMS7=TimeUtil::getCurrentTimestampMS();
    					if ($result['success']===1){ // result['success']    0----ok,1---fail
    					    self::_handleFetchItemsError($SAA_obj,"_saveLazadaOrderToEagle() fails.  error:".$result['message']);
    					    continue;
    					}
    					
    					Yii::info("lazada_batch_fetch_order_items orderId:".$orderHeaderArr['OrderNumber'].", platform:$platform,site:$site,group:puid:".$puid.",lazada_uid:".$lazadaUid.
				        ",t02_01=".($timeMS02-$timeMS01).",t03_02=".($timeMS03-$timeMS02).",t03_01=".($timeMS03-$timeMS01).",t2_1=".($timeMS2-$timeMS1).
				        ",t3_2=".($timeMS3-$timeMS2).",t4_3=".($timeMS4-$timeMS3).",t5_4=".($timeMS5-$timeMS4).
				        ",t6_5=".($timeMS6-$timeMS5).",t7_6=".($timeMS7-$timeMS6).",t7_1=".($timeMS7-$timeMS1),"file");
    					
    					
    					//5. after sync is ok,set the order item of the queue
    					$SAA_obj->status=2;
    					$SAA_obj->update_time=time();
    					$SAA_obj->error_times=0;
    					$SAA_obj->message="";
    					$SAA_obj->save(false);
    					
    				}// end of group orders
	            } 
	        }// end of foreach groups
	    }
	
	    return $hasGotRecord;
	}
	
	// dzt20151119 重新获取所有 Linio 客户一个月前的订单
	public static function reGetAllLinioOrders(){
		$allLinioUsers = SaasLazadaUser::find()->where(['platform'=>'linio'])->AsArray()->all();
		echo "count:".count($allLinioUsers);
		foreach ($allLinioUsers as $linioUser){
			
			$config=array(
					"userId"=>$linioUser['platform_userid'],
					"apiKey"=>$linioUser['token'],
					"platform"=>$linioUser['platform'],
					"countryCode"=>$linioUser['lazada_site']
			);
			$nowTime = time();
			$start_time = $nowTime - (30*24*3600);
			$end_time = $nowTime;
			// _getOrderListAndSaveToQueue 需要的 $SAA_obj 其实是同步object 但是由于function 只用了  $SAA_obj->puid 和$SAA_obj->lazada_uid 
			// 这个两个值SaasLazadaUser 都有，所以暂时用这个顶替
			$SAA_obj = SaasLazadaUser::findOne(['lazada_uid'=>$linioUser['lazada_uid']]);
			$timeMS1=TimeUtil::getCurrentTimestampMS();
			list($ret,$errorMessage)=self::_getOrderListAndSaveToQueue($config,$start_time,$end_time,$SAA_obj,"createTime");
			$timeMS2=TimeUtil::getCurrentTimestampMS();
			echo  "reGetAllLinioOrders lazada_uid:".$linioUser['lazada_uid']." t2_1=".($timeMS2-$timeMS1);
			Yii::info("reGetAllLinioOrders lazada_uid:".$linioUser['lazada_uid']." t2_1=".($timeMS2-$timeMS1),"file");
			if ($ret==false){
				Yii::error("reGetAllLinioOrders lazada_uid:".$linioUser['lazada_uid']." get order head fail: $start_time - $end_time :".$errorMessage,"file");
				echo "reGetAllLinioOrders lazada_uid:".$linioUser['lazada_uid']." get order head fail: $start_time - $end_time :" .$errorMessage;
			}
				
		}
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * linio智利站点不清楚原因最近比较多漏单以及订单，这里加一个每三小时触发拉取近两天更新的订单的任务
	 * 如果update time大于queue表订单header的update time不同，即进行更新，如果不在queue表即插入
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		dzt		2016-05-03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOrderListByDay2($platforms = false){
		echo "++++++++++++getOrderListByDay2 \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=\Yii::$app->db;
		$type = 5;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单
		$hasGotRecord=false;//是否抢到账号
		$fetchPeriod = 3600 * 3;//秒为单位. 2次后台自动拉取的时间间隔
		$getOrderPeriod = 3600 * 24 * 2; // 拉取1天前订单
		$fetchEndTimeAdvanced=5*60;  //秒为单位 .为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
		$failRetryPeriod = 5 * 60;// 失败重试
		
		//2. 从账户同步表（订单列表同步表提取带同步的账号。
		$nowTime=time();
		// 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题，4---旧订单拉取全部完成
// 		$command=$connection->createCommand(
// 				'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` in (0,2,3) ' .
// 				'AND `error_times` < 10 AND `type`='.$type.'  AND  next_execution_time<'.$nowTime .
// 		        ' order by next_execution_time asc limit 30 ');

		// dzt20161227 加入平台过滤
		$sql = 'select `id` from  `saas_lazada_autosync` as sla ';
		$where = 'sla.`is_active` = 1 AND sla.`status` in (0,2,3) AND sla.`error_times` < 10 AND sla.`type`='.$type.' AND sla.next_execution_time<'.$nowTime;
		if(!empty($platforms)){
// 		    $sql .= ' inner join `saas_lazada_user` as slu on slu.`lazada_uid`=sla.`lazada_uid` ';
// 		    $where .= ' AND slu.`platform` in ("'.implode('","', $platforms).'") ' ;
		    // saas_lazada_autosync 也添加platform,site字段
            if(is_array($platforms)){
                $where .= ' AND sla.`platform` in ("'.implode('","', $platforms).'") ' ;
            } else {
                $where .= ' AND sla.`platform` = "'.$platforms.'" ' ;
            }
		}
		
		// 漏单拉取 每隔几小时跑一次，一次跑全部记录，所以不加Limit
		$command=$connection->createCommand($sql." where ".$where." order by sla.next_execution_time asc");
		
// 		$command=$connection->createCommand(
// 		        'select `id` from  `saas_lazada_autosync` where `type`='.$type.' and puid=5791 ' );
		
		$allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			echo "++++++++dataReader->read() ".$row['id']."\n";
			// 先判断是否真的抢到待处理账号
			$timeMS1=TimeUtil::getCurrentTimestampMS();
			$SAA_obj = self::_lockLazadaAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
			
			echo "++++++++got ".$row['id']."\n";
			$hasGotRecord=true;  // 抢到记录
	
			//3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
			//3.1 整理请求参数
			//$config=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
			$tempConfig=$allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
			$config=array(
					"userId"=>$tempConfig["userId"],
					"apiKey"=>$tempConfig["apiKey"],
					"countryCode"=>$tempConfig["countryCode"],
			);
	
	
			$nowTime=time();
			$start_time = $nowTime - $getOrderPeriod;
			$end_time = $nowTime;

			//为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
			$end_time=$end_time-$fetchEndTimeAdvanced;
			
			// dzt20161129 添加lazada get order list请求offset
			// 如果有offset 即上次start_time - end_time 的订单未获取完
			$offset = self::_getAutoSyncOffset($SAA_obj->id);
			if(!empty($offset)) {
			    $start_time = $SAA_obj->start_time;
			    $end_time = $SAA_obj->end_time;
			}else{
			    $offset = 0;
			}
			
			//3.2 访问proxy并保存结果
			$timeMS2=TimeUtil::getCurrentTimestampMS();
			list($ret,$errorMessage)=self::_getOrderListAndSaveToQueue($config,$start_time,$end_time,$SAA_obj,"updateTime2");
			$timeMS3=TimeUtil::getCurrentTimestampMS();
			if ($ret==false){
			    Yii::info("getOrderListByDay2 platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.", $start_time,$end_time,$type --- error message=$errorMessage,t2_1=".($timeMS2-$timeMS1).",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
				$SAA_obj->message=$errorMessage;
				$SAA_obj->error_times=$SAA_obj->error_times+1;
				$SAA_obj->status=3;
				$SAA_obj->next_execution_time=$nowTime+$failRetryPeriod;
				$SAA_obj->update_time=$nowTime;
				$SAA_obj->save(false);
				continue;
			}
			
			if (!true){
			    $newFetchPeriod = 6 * $fetchPeriod;
			}else{
			    $newFetchPeriod = $fetchPeriod;
			}
			
			$SAA_obj->start_time=$start_time;
			$SAA_obj->end_time=$end_time;
			$SAA_obj->status = 2;
			$SAA_obj->error_times = 0;
			$SAA_obj->message="";
			$SAA_obj->update_time=$nowTime;
			$SAA_obj->next_execution_time=$nowTime+$newFetchPeriod;
			$SAA_obj->save (false);
	
			Yii::info("getOrderListByDay2 platform:".$SAA_obj->platform.",site:".$SAA_obj->site.",puid:".$SAA_obj->puid.",lazada_uid:".$SAA_obj->lazada_uid.",t2_1=".($timeMS2-$timeMS1).",t3_2=".($timeMS3-$timeMS2).",t3_1=".($timeMS3-$timeMS1),"file");
		}
	}
	
    // 获取lazada get order list请求offset
	private static function _getAutoSyncOffset($autoSyncId){
		$key = 'syncId_'.$autoSyncId;
		//return \Yii::$app->redis->hget("lazada_get_order_offset",$key);
		return RedisHelper::RedisGet("lazada_get_order_offset",$key);
	}
	
	// 设置lazada get order list请求offset
	private static function _setAutoSyncOffset($autoSyncId,$val){
	    $key = 'syncId_'.$autoSyncId;
	    //return \Yii::$app->redis->hset("lazada_get_order_offset",$key,$val);
		return RedisHelper::RedisSet("lazada_get_order_offset",$key,$val);

	}
}


?>