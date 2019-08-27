<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\tracking\helpers;


use yii;
use yii\data\Pagination;
use \DateTime;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\models\GlobalLog;
use eagle\modules\util\helpers\HttpHelper;
use eagle\modules\util\helpers\AppPushDataHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\tracking\models\TrackerApiQueue;
use eagle\modules\tracking\models\TrackerApiSubQueue;
use eagle\modules\tracking\models\TrackerSubQueueManage;
use eagle\modules\tracking\models\TrackerGenerateRequest2queue;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ResultHelper;
use yii\base\Exception;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use yii\caching\DbDependency;
use eagle\models\SaasAliexpressUser;
use eagle\models\SaasCdiscountUser;
use eagle\models\SaasLazadaUser;
use eagle\models\SysShippingCodeNameMap;
use eagle\models\SaasDhgateUser;
use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\modules\message\helpers\MessageHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\RedisHelper;
use eagle\models\SaasWishUser;
use eagle\modules\order\helpers\OrderTrackerApiHelper;
use eagle\modules\message\models\MsgTemplate;
use eagle\modules\message\helpers\TrackingMsgHelper;
use common\helpers\Helper_Array;
use Qiniu\json_decode;
use eagle\modules\dash_board\helpers\DashBoardHelper;

/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
class TrackingQueueHelper {
//状态
	public static $TRACKER_FILE_LOG = false;
	const CONST_1= 1; //Sample
	const MainQHanlerThreads = 60;
	private static $INTERVAL_SUB_QUEUE = 2;  //SECONDS
	private static $Insert_Api_Queue_Buffer = array();
	private static $mainQueueVersion = '';	
	private static $mainQueueTaskIds = [];
	private static $subQueueVersion = '';
	private static $putIntoTrackQueueVersion = '';
	
	public static function RedisGet($keyL1,$keyL2 ) {
		return RedisHelper::RedisGet($keyL1, $keyL2);
	}
	
	public static function RedisSet($keyL1,$keyL2 , $val=0) {	
		return RedisHelper::RedisSet($keyL1, $keyL2,$val);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * SubQueue manager
	 * 这个会是双进程，用来判断哪个subQueue Job idle，assign时间点开动，以及计算总体的 查询耗费次数
	 * 之所以用双进程，是怕其中一个manager 到时见自动exit后，没有其他manager调动SubQueue Drivers
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::sub_queue_manager();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function routeManager(){
		global $CACHE ;
	 
		$INTERVAL = 0.5; //SECONDS
	
		if (!isset ($CACHE['JOBID']))
			$CACHE['JOBID'] =  (1 +  rand(1,9999) * rand(1,9999)) % 1147483647;
	
		$job_key = $CACHE['JOBID'];
	
		$now_str = date('Y-m-d H:i:s');
		//step 0: 检查新版本，要不要重启自己
		$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
		if (empty($currentMainQueueVersion))
			$currentMainQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$mainQueueVersion))
			self::$mainQueueVersion = $currentMainQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$mainQueueVersion <> $currentMainQueueVersion){
			TrackingAgentHelper::extCallSum( );
			//退出时候，mark 这个job没有管了，让下一次循环进来接管
			$affectRows = Yii::$app->db_queue->createCommand(
					"update tracker_sub_queue_manage set is_idle='Y',job_key='$job_key',
					update_time='$now_str' where job_name='RouteManager'")->execute();
			
			//Load this data to redis, so the subQH mainQH do not have to load each time
			$hasMap = Helper_Array::toHashmap(SysShippingCodeNameMap::find()->select(['shipping_code','shipping_name'])->where(['platform'=>'aliexpress'])->asArray()->all(),'shipping_code','shipping_name');
			self::RedisSet('Tracker_AppTempData', "SysShippingCodeNameMapHashMap", json_encode($hasMap));
			TrackingAgentHelper::markJobUpDown("Trk.RouteMgrDown",$CACHE['jobStartTime']);
			DashBoardHelper::WatchMeDown();
			exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
		}

				//step1: 查询manager是否有记录在 db_queue 里面的 tracker_sub_queue_manage
				if ( empty($CACHE['hasManagerRecord'])){
					$lastModifyLimit = date('Y-m-d H:i:s',strtotime('-10 seconds'));
					$RecordOne = TrackerSubQueueManage::find()
					->andWhere("job_name='RouteManager'  "  )
					->asArray()
					->one();
						
					if (empty($RecordOne)){
						Yii::$app->db_queue->createCommand(//避免2个job都要求create，用 replace into
						"insert into tracker_sub_queue_manage (job_name,is_idle,update_time,create_time) values
						('RouteManager','Y','$now_str','$now_str')")
						->execute();
						sleep(1); //避免2个job都同时走 replace into，sleep 一下
					}
						
					$CACHE['hasManagerRecord'] = true; //so that 下一次进来就不需要再检测Rotemanager 有没有了		
				}
	
				//step 2: 尝试这个job 做active的manager
				$now_str = date('Y-m-d H:i:s');
				 
				$lastModifyLimit = date('Y-m-d H:i:s',strtotime('-15 seconds'));
				$ManagerRec = TrackerSubQueueManage::find()
					->andWhere("job_name='RouteManager' and (is_idle='Y' or update_time<'$lastModifyLimit' )"  )
					->one();
	
				if (empty($ManagerRec)){
					echo "RouteManger can not active, exit \n";//ystest
					return ;
				}
					
	
				//mark use self to lock the manager position
				$affectRows = Yii::$app->db_queue->createCommand(
				"update tracker_sub_queue_manage set is_idle='N',job_key='$job_key',
					update_time='$now_str' where job_name='RouteManager'
					and is_idle='".$ManagerRec->is_idle."' and update_time='".$ManagerRec->update_time."' ")
						->execute();
	
				//如果已经被人lock了，这个manager就是替补的，退出吧
				if ($affectRows <= 0){
					echo "RouteManger is locked, exit \n";//ystest
					return;
				}
				//删除update time是很久以前的sub Queue，那些事死掉的了。
				$affectRows = Yii::$app->db_queue->createCommand(
						"delete from tracker_sub_queue_manage   where
						update_time<='".date('Y-m-d H:i:s',strtotime('-5 minutes'))."' and job_type='MH'")
									->execute();
	
				//Step 3.1: 先Load 280个mainQueue task 进来， 然后处理， 中途再尝试看看有没有priority 高的新增
				//如果有proirity高的新增，野load 进来，否则就走完这500个才推出去再进来
				// "Step 3.1 try to get 280 pending mainQueue at $now_str\n";
				//防止一个客户太多request，每次随机一个数，优先处理puid mod 5 ==seed 的这个
				$one_go_count = 350;
				 
				$CACHE['priority1'] = Yii::$app->db_queue->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
								"select * from tracker_api_queue force index (status_2) where status='P' and priority=1 
								 ")->queryAll();

				$CACHE['priorityOther'] = Yii::$app->db_queue->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
						"select * from tracker_api_queue force index (status_2) where status='P' and priority >=2
						order by priority asc, run_time asc  limit $one_go_count ")
						->queryAll();
				
				if (empty($CACHE['priority1']) and empty($CACHE['priorityOther']) ){
					sleep(2);
					self::submitCompletedMQHsToRedisCommitQueue();
					$affectRows = Yii::$app->db_queue->createCommand(
						   "update tracker_sub_queue_manage set is_idle='Y',job_key='$job_key',
							update_time='$now_str' where job_name='RouteManager'")->execute();

				 	echo "No pending main task, exit \n";//ystest
					return;
				}
				
				/*step 3.2, 查找MainQueue memTable，对状态是idle =Y的，分配一个任务id（*1），给他，
				 * update memTable设置他是 idle=N，task id=539
					吧任务相关的 Tracking model 以及 SubQueue放到 redis  
					TrackMainQ/1126_TrackingModel_539, TrackMainQ/1126_MainQueueModel_539
				*/
				//echo "ys1 got pending counts are :".count($CACHE['priority1'])."/".count($CACHE['priorityOther'])."\n"; //ystest
				$assignedTaskIds = [];
				$ind = 0;
				//3.2.1 check all handlers in tracker_sub_queue_manage for subqueue, find the idle ones
				while ( !empty($CACHE['priority1']) or !empty($CACHE['priorityOther']) ){
					//echo "ys1.a got pending counts are :".count($CACHE['priority1'])."/".count($CACHE['priorityOther'])."\n"; //ystest
					$ind ++;
					$now_str = date('Y-m-d H:i:s');
					self::submitCompletedMQHsToRedisCommitQueue();
					$assignedTaskIds = self::assignBulkTaskToMQH();
					//echo "Assigned to MainQ Handler, count=".count($assignedTaskIds)."\n";
					//每隔5秒，通知一次我还活着
					if (empty($CACHE['Last_alive_signal_time']))
						$CACHE['Last_alive_signal_time'] = date('Y-m-d H:i:s');
					
					$sec_5_ago = date('Y-m-d H:i:s',strtotime('-5 seconds'));
					if ($CACHE['Last_alive_signal_time'] < $sec_5_ago){
						TrackerSubQueueManage::updateAll(['update_time'=> date('Y-m-d H:i:s')] ,['job_name'=>'RouteManager','job_key'=>$CACHE['JOBID']] );
						$CACHE['Last_alive_signal_time'] = $now_str;
					}
					
					if (!empty($assignedTaskIds))
						TrackerApiQueue::updateAll(['status'=>'S','update_time'=>date('Y-m-d H:i:s') ],['id'=>$assignedTaskIds ]);
					//if the priority1 is empty, Load the high priority into array if any
					sleep(0.7);

					//Step A， priority = 1 de 可以插队
					if (empty($CACHE['priority1']) )
						$CACHE['priority1'] = Yii::$app->db_queue->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
							"select * from tracker_api_queue force index (status_2) where status='P' and priority=1
								 ")->queryAll();
					
					//循环10次检查一次版本，要不要退出
					if ($ind % 10 == 0){
						//step 0: 检查新版本，要不要重启自己
						$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
						if (empty($currentMainQueueVersion))
							$currentMainQueueVersion = 0;
						
						//如果自己还没有定义，去使用global config来初始化自己
						if (empty(self::$mainQueueVersion))
							self::$mainQueueVersion = $currentMainQueueVersion;
							
						//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
						if (self::$mainQueueVersion <> $currentMainQueueVersion){
							TrackingAgentHelper::extCallSum( );
							TrackingAgentHelper::markJobUpDown("Trk.RouteMgrDown",$CACHE['jobStartTime']);
							DashBoardHelper::WatchMeDown();
							exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
						}
					}
					//echo "ys1A counts are :".count($CACHE['priority1'])."   ".count($CACHE['priorityOther'])."\n"; //ystest
				}//while there is still some task not assgined
				

				//step final, 
				/*如果有 memTable的 idel=N ，update time 已经超过5秒钟，那么就是这个MainJob死了
				Delete memTable for this Job Id xxx
				delete redis  TrackMainQ/TrackingModel_xxx, TrackMainQ/MainQueueModel_xxx
				以下是保底操作，其实在 assignBulkTaskToMQH 的时候，已经做了处理的，而且比这个处理要干净
				*/		
			 
				\Yii::$app->db_queue->createCommand(
						" delete from tracker_sub_queue_manage where
						  job_type='MH' and is_idle='N' and  update_time <='". date('Y-m-d H:i:s',strtotime('-5 minutes'))."' ")
						->execute();
				
				/*
				如果有 memTable的 idel=S ，update time 已经超过5分钟，那么就是这个MainJob死了
				Delete memTable for this Job Id xxx
				delete redis  TrackMainQ/TrackingModel_xxx, TrackMainQ/MainQueueModel_xxx
				*/
				\Yii::$app->db_queue->createCommand(
						" delete from tracker_sub_queue_manage where
						  job_type='MH' and is_idle='S' and  update_time <='". date('Y-m-d H:i:s',strtotime('-15 minutes'))."' ")
						->execute();
				
				$now_str = date('Y-m-d H:i:s');
				//echo "Finished assigned for main Queue   count:".count($assignedTaskIds)." , exit at $now_str \n";
				
				//step final 2
				
				
				//退出时候，mark 这个job没有管了，让下一次循环进来接管
				$affectRows = Yii::$app->db_queue->createCommand(
						"update tracker_sub_queue_manage set is_idle='Y',job_key='$job_key',
						update_time='$now_str' where job_name='RouteManager'")->execute();		
				
				return ;
	}
	
	
	static private function submitCompletedMQHsToRedisCommitQueue(){
		/*检查 memTable是否有 idle=C 的已完成任务,有则判断其priority，如果<3，把这个 task id 号, 创见一条记录，写到 redis TrackerCommitQueue_HighP/Task_539 = $puid,
		 如果>= 3，把这个 task id 号, 创见一条记录，写到 redis TrackerCommitQueue_LowP/Task_539 = $puid
		update idle=Y
		* */
		//echo "start to handle the Mh Completed, put to redis HighP and Low P \n";
		$completedMHs = Yii::$app->db_queue->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
				"select * from tracker_sub_queue_manage  where  job_type='MH' and is_idle='C'
						")->queryAll();
		
		$doneJobNames = array();
		$doneTaskIds = array();
		foreach ($completedMHs as $aMH){
			$doneJobNames[$aMH['job_name']] = $aMH['job_name'];
			//$aMH['task_id']= $datePrefix.'_'.$puids."_".$aMH['id']s ,
			$arr1 = explode('_', $aMH['task_id']);
			$datePrefix = $arr1[0];
			$puids = $arr1[1];
			$taskIds = $arr1[2];
			
			if (!isset($arr1[2]))
				continue;
			
			$taskIds_array = explode(",", $taskIds);
			$puids_array = explode(",", $puids);
			$priorities_array = explode(",", $aMH['priority']);
			
			//set e.g. TrackerCommitQueue_LowP/1126_1_56984 = 1
			foreach ($taskIds_array as $k=>$aTaskId){
				$queue_key = $datePrefix."_".$puids_array[$k]."_".$aTaskId;
				self::RedisSet("TrackerCommitQueue_".($priorities_array[$k]<=2?"High":"Low")."P",
					$queue_key,1);
			}

			$doneTaskIds[] = $arr1[2];
		}
			
		//echo "assigned complete  doneTaskIds".print_r($doneTaskIds,true)."\n";//ystest
		$now_str = date('Y-m-d H:i:s');
		if (!empty($doneJobNames)){
			TrackerSubQueueManage::updateAll(['is_idle'=>'Y','task_id'=>''],['job_name'=>$doneJobNames]);
			TrackerApiQueue::updateAll(['status'=>'Q','update_time'=>$now_str],['id'=>$doneTaskIds]);
		}
		return true;
	}
	
	static private function assignBulkTaskToMQH(){
		global $CACHE;
		$assignedTaskIds=array();
		$datePrefix = substr( date('Y-m-d H:i:s'),5,2).substr( date('Y-m-d H:i:s'),8,2);
		//有一些是 死掉的job的注册表，如果状态是 N 而且 update time 是 10秒钟前的，就删除吧
		//理论上从 Y update 成N后，哪个job 1秒钟左右就会读取，修改自己状态为 S 然后去干活的
		//杀之前吧这些assigned 的task 回收，改为 P状态, 又或者 状态是 S 但已经5分钟了
		$DeadSubQueueHdls = TrackerSubQueueManage::find()
			->andWhere("job_type='MH' and  (is_idle='N'  and update_time < '".date('Y-m-d H:i:s',strtotime('-10 seconds'))."' 
					    or is_idle='S'  and update_time < '".date('Y-m-d H:i:s',strtotime('-13 minutes'))."')"  )
			->asArray()
			->all();
		$dead_task_ids = "";
		$no_respond_task_ids = "";
		$dead_MQH_job_names = [];
		if (!empty($DeadSubQueueHdls))
		foreach ($DeadSubQueueHdls as $aDeadMQH){
			$arr1 = explode('_', $aDeadMQH['task_id']);
			if (isset($arr1[2])){
				if ($aDeadMQH['is_idle'] =='S')
					$no_respond_task_ids .= (",".  $arr1[2]);
				else
					$no_respond_task_ids .= (",".  $arr1[2]);
			} 
			
			$dead_MQH_job_names[] = $aDeadMQH['job_name'];
		}
		
		if (!empty($dead_task_ids)){
			TrackerApiQueue::updateAll(["status"=>'P'] ,['id'=>explode(',',$dead_task_ids)] );
			//echo "Change MainQueue task to status P for $dead_task_ids \n";
		}
		//update them as missing, 需要人工再去看，为何会变成Missing了
		if (!empty($no_respond_task_ids))
			TrackerApiQueue::updateAll(["status"=>'M'] ,['id'=>explode(',',$no_respond_task_ids) ] );
		
		if (!empty($dead_MQH_job_names))
			TrackerSubQueueManage::deleteAll(['job_name'=>$dead_MQH_job_names]);
		$now_str = date('Y-m-d H:i:s');

		//3.2.1 Load all idle MainQ handler first
		
		$SubQueueHdls = TrackerSubQueueManage::find()
			->andWhere("job_type='MH' and  is_idle='Y'  "  )
			->asArray()
			->all();
		$MQH_array=[];
		foreach ($SubQueueHdls as $aMQH){
			$MQH_array[]= $aMQH['job_name'];
		}
		
		$idleMQHanlers_count = count($MQH_array);
		if (empty($MQH_array)){
			//echo "YS2.1 not Found idle MQHanlders \n"; //ystest
			sleep(1);
			return array(); 
		}
		//echo "YS2.2 Found idle MQHanlders $idleMQHanlers_count: \n"; //ystest
	
		//3.2.2	先把 priority 高的分配给他然后到priority 低的, 提取一共要准备处理的 task 到array，
		//进行分析他们的 user puid，尽量减少switch puid 的load tracking信息出来，分派任务
		//echo "ysa 3.2.2  counts for : ".count($CACHE['priority1'])." and ".count($CACHE['priorityOther'])." \n"; //ystest
		$prioritys= ['priority1','priorityOther']; 
		$readyToDoTaskortByPuid = [];
		$readyToDoTask = [];
		//因为每一个MQHander可以处理 30 个线程，所以这里相当于有 * 30 个的处理能力
		$idleMQHanlers_can_handle_count = $idleMQHanlers_count * self::MainQHanlerThreads;
		foreach ($prioritys as $priorityName){
			if ($idleMQHanlers_can_handle_count == 0)
				break;
			foreach ($CACHE[$priorityName] as $key=>$aMQTask){
				//3.2.2.A Assign task to MQHander，并且放到redis中
				if ($idleMQHanlers_can_handle_count == 0)
					break;
			/*
				if (isset($CACHE['MainQHandled'][ $aMQTask['puid'] ][$aMQTask['track_no']][$aMQTask['candidate_carriers']])){
					unset($CACHE[$priorityName][$key]);
					$affectRows = Yii::$app->db_queue->createCommand(
							"delete from tracker_api_queue   where   id = ".$aMQTask['id']." ")
							 ->execute();
					continue;
				}
			*/	
				//按照puid 来归类，容易做 db connection load tracking data				
				$readyToDoTaskortByPuid[strval($aMQTask['puid'])][] = $aMQTask['track_no'];
				$readyToDoTask[$aMQTask['puid']."-". strtoupper($aMQTask['track_no']) ] = $aMQTask;
				$idleMQHanlers_can_handle_count --;
				unset($CACHE[$priorityName][$key]);
				/*
				$affectRows = Yii::$app->db_queue->createCommand(
						"delete from tracker_api_queue   where puid=".$aMQTask['puid']." and 
						 status ='P' and track_no='".$aMQTask['track_no']."' 
						 and candidate_carriers='".$aMQTask['candidate_carriers']."' 
						 and id <> ".$aMQTask['id']." ")
						->execute();
				 */
				$CACHE['MainQHandled'][ $aMQTask['puid'] ][ strtoupper($aMQTask['track_no']) ][$aMQTask['candidate_carriers']] =1;
				
			//	echo "Try to unset cache $priorityName $key \n";//ystest
			}//end of each of task
		}//end of each priority class

		$idleMQHanlers_count = count($MQH_array);
		//因为每一个MQHander可以处理 30 个线程，所以这里相当于有 * 30 个的处理能力
		$idleMQHanlers_can_handle_count = $idleMQHanlers_count * self::MainQHanlerThreads;
		
		//step 3.2.2.B, 吧要做的，按照 puid 顺序，assign 到具体每一个任务去
		$now_str = date('Y-m-d H:i:s');
		$use_idle_job_index = 0;
		$trackingArray_all = [];
		foreach ($readyToDoTaskortByPuid as $puid=>$track_nos){
			if ($idleMQHanlers_can_handle_count <= 0)
				break;
			//echo "ys3.2.2 try to load all tracking for this puid $puid \n"; //ystest
			//try to load all tracking for this puid
	
			$trackingArray = Tracking::find()
			->andWhere(['IN','track_no', $track_nos ])
			->asArray()
			->all();
			$sql ='';
		   
			//比较一下 想要search的 track code 和得到的结果，如果有一些已经被删除了，那么也要删除这个 task in queue
			//$readyToDoTask[$aMQTask['puid']."-".$aMQTask['track_no']]
			$searchGotIds = [];
			foreach ($trackingArray as $k=>$aTracking){
				$searchGotIds[ strtoupper($aTracking['track_no'])] = $aTracking['track_no'];
				
				$addinfo = json_decode($aTracking['addi_info'],true);
				
				if (!empty($addinfo['return_no'])){//如果有return no，但是 track no 不是ID开头的18位，删除return no 吧
					if (  !(
							substr( strtoupper($aTracking['track_no']),0,2) =='ID' and
							strlen(trim($aTracking['track_no']) ) ==18
					) ){
						$addinfo['return_no'] = '';
						$aTracking1 = $aTracking;
						$aTracking1['addi_info'] = json_encode($addinfo);
				
						//write it to db for further use
						$command = Yii::$app->subdb->createCommand("update lt_tracking set
									addi_info=:addi_info where track_no  = :track_no"  );
						$command->bindValue(':addi_info', $aTracking1['addi_info'], \PDO::PARAM_STR);
						$command->bindValue(':track_no', $aTracking['track_no'], \PDO::PARAM_STR);
						$affectRows = $command->execute();
						//echo "This is WINT track no ".$aTracking['track_no'] . ", addi info= ".$aTracking1['addi_info']." ,step 3 ,rows= $affectRows \n";
						$trackingArray[$k] = $aTracking1;
					}
				
				}
				
				//这里看看，如果是万邑通的，尝试从oms load 出来他的return no，写到这个tracking 信息里面去，update回去lt_tracking 那么就下次就不需要再找了
				$carrier_specified_WINIT= false;
				if (strpos(strtoupper($aTracking['ship_by']), "WINIT") !== false or
				substr( strtoupper($aTracking['track_no']),0,2) =='ID' and strlen(trim($aTracking['track_no'])==18 ) or
				strpos($aTracking['ship_by'], "万邑") !== false
				){  
					$carrier_specified_WINIT = true;
					//echo "This is WINT track no ".$aTracking['track_no'] . " and addinfo is ".print_r($addinfo,true)."\n";
					if (empty($addinfo['return_no'])){
						//try to check its od_order_shipped_v2 table
						$order_shipped_v2_record = OdOrderShipped::find()
							->where(['tracking_number'=>$aTracking['track_no'] ])->one();
						//echo "This is WINT track no ".$aTracking['track_no'] . " step 1 \n";
						if (!empty($order_shipped_v2_record) and !empty($order_shipped_v2_record->return_no['TrackingNo'])){
							//echo "This is WINT track no ".$aTracking['track_no'] . " step 2 \n";
							$addinfo['return_no'] = $order_shipped_v2_record->return_no['TrackingNo'];
							$aTracking1 = $aTracking;
							$aTracking1['addi_info'] = json_encode($addinfo);
							
							//write it to db for further use
							$command = Yii::$app->subdb->createCommand("update lt_tracking set  
									addi_info=:addi_info where track_no  = :track_no"  );
							$command->bindValue(':addi_info', $aTracking1['addi_info'], \PDO::PARAM_STR);
							$command->bindValue(':track_no', $aTracking['track_no'], \PDO::PARAM_STR);
							$affectRows = $command->execute();
							//echo "This is WINT track no ".$aTracking['track_no'] . ", addi info= ".$aTracking1['addi_info']." ,step 3 ,rows= $affectRows \n";
							$trackingArray[$k] = $aTracking1;
						}
					}//end of if return no is not empty
				}
				
				 
				
			}//end of each result for this puid
			
			foreach ($track_nos as $shouldHaveTrackNo){
				if (!isset($searchGotIds[strtoupper($shouldHaveTrackNo)])){   
					//delete the q task, because no longer in lt tracking table
					$theTask = $readyToDoTask[$puid."-".strtoupper($shouldHaveTrackNo) ];
					$affectRows = Yii::$app->db_queue->createCommand(
							"delete from tracker_api_queue where id = ".$theTask['id']." ")
											 ->execute();
					unset($readyToDoTask[$puid."-".strtoupper($shouldHaveTrackNo) ]);
				}
			}
			
		 
			//echo "ys3.2.3 $sql \n puid $puid, ".count(  $track_nos)." records, load from Tracking count ".count($trackingArray)." \n"; //ystest
			$idleMQHanlers_can_handle_count -= count($trackingArray);
			$trackingArray_all[strval($puid)] = $trackingArray;
		}//end of each puid and its arrays
		
		//Start to assign for all these tracking tasks
		$assignedTrackNo = [];
		$index1 = 0;
		$thisBatchCount=0;
		$thisBatchPuids = [];
		$thisBatchPrioritys = [];
		$thisBatchTaskIds = [];
		$thisMQHAssignedTaskCount = 0;
		foreach ($trackingArray_all as $puid=>$trackingArray){
		foreach ($trackingArray as $aTracking){
		 
			$index1 ++;		
			if ($idleMQHanlers_count <= $use_idle_job_index)
				break;
		
			if (isset($assignedTrackNo[$puid."-".strtoupper($aTracking['track_no']) ]))
				continue;
		
			if (!isset($readyToDoTask[$puid."-".strtoupper($aTracking['track_no'])])){
				echo "YS1 Exception: not found for readyToDoTask Index ".$puid."-".strtoupper($aTracking['track_no'])." \n";
				continue;
			}
		
			$theTaskData = $readyToDoTask[$puid."-".strtoupper($aTracking['track_no'])];
			
			self::RedisSet('TrackMainQ', $datePrefix."_TrackingModel_".$theTaskData['id'] , json_encode($aTracking) );
			self::RedisSet('TrackMainQ', $datePrefix."_MainQueueModel_".$theTaskData['id'] , json_encode($theTaskData) );
		
			$assignedTaskIds[] = $theTaskData['id'];
		
			$assignedTrackNo[$puid."-".strtoupper($aTracking['track_no'])] = 1;
		
			$thisMQHAssignedTaskCount++;
		
			$thisBatchPuids[] = $puid;
			$thisBatchPrioritys [] = $theTaskData['priority'];
			$thisBatchTaskIds [] = $theTaskData['id'];
			//每一个MainJob做30个，如果到达30个或者是最后一个request了，就assign吧
		
			if ($thisMQHAssignedTaskCount >=self::MainQHanlerThreads ){
				$now_str = date('Y-m-d H:i:s');
				$affectRows = \Yii::$app->db_queue->createCommand(
						" update tracker_sub_queue_manage set is_idle='N' , priority= '".implode(",", $thisBatchPrioritys)."',
						task_id= '".$datePrefix."_".implode(",", $thisBatchPuids)."_".implode(",", $thisBatchTaskIds)."',
						update_time='$now_str', next_start='$now_str' where job_name='".$MQH_array[$use_idle_job_index]."' ")
						->execute();
		
			//	echo "YS1.5 assigned task $affectRows,total assigned ".count($thisBatchTaskIds)." : ".$datePrefix."_".implode(",", $thisBatchPuids)."_".
						implode(",", $thisBatchTaskIds)." to job name ".$MQH_array[$use_idle_job_index] ."\n";
		
								$use_idle_job_index ++;
								$thisBatchPuids = [];
								$thisBatchPrioritys = [];
								$thisBatchTaskIds = [];
								$thisMQHAssignedTaskCount = 0;
			}
		
			//$assignedTrackNo[$puid."-".$aTracking['track_no']] = 1; //mark this trakck no for this puid is done
		}//end of each tracking number of target tracking models
		}//end of each puid to be assigned
		//Final ones
		if ($thisMQHAssignedTaskCount > 0){
			$now_str = date('Y-m-d H:i:s');
			$affectRows = \Yii::$app->db_queue->createCommand(
					" update tracker_sub_queue_manage set is_idle='N' , priority= '".implode(",", $thisBatchPrioritys)."',
						task_id= '".$datePrefix."_".implode(",", $thisBatchPuids)."_".implode(",", $thisBatchTaskIds)."',
					update_time='$now_str', next_start='$now_str' where job_name='".$MQH_array[$use_idle_job_index]."' ")
					->execute();
		
		//	echo "YS1.5a assigned task $affectRows,total assigned ".count($thisBatchTaskIds)." : ".$datePrefix."_".implode(",", $thisBatchPuids)."_".
			implode(",", $thisBatchTaskIds)." to job name ".$MQH_array[$use_idle_job_index] ."\n";
		
					$use_idle_job_index ++;
					$thisBatchPuids = [];
					$thisBatchPrioritys = [];
					$thisBatchTaskIds = [];
					$thisMQHAssignedTaskCount = 0;
		}
		
		return $assignedTaskIds;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Commit Manager
	 * 这个会是双进程，用来判断哪个memTable 里面的MainQueue Hanler 昨晚了，
	 * 之所以用双进程，是怕其中一个manager 到时见自动exit后，1分钟内没有人 重新submit
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::sub_queue_manager();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function commitManager($workForRedisQueues = array('TrackerCommitQueue_HighP','TrackerCommitQueue_LowP')){
		global $CACHE ;
	
		$INTERVAL = 0.5; //SECONDS
		$jobName = $CACHE['JOBNAME']; //it can be CommitManagerHigh or CommitManagerLow
		if (!isset ($CACHE['JOBID']))
			$CACHE['JOBID'] =  (1 +  rand(1,9999) * rand(1,9999)) % 1147483647;
	
		$job_key = $CACHE['JOBID'];
	
		$now_str = date('Y-m-d H:i:s');
		//step 0: 检查新版本，要不要重启自己 
		$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
		if (empty($currentMainQueueVersion))
			$currentMainQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$mainQueueVersion))
			self::$mainQueueVersion = $currentMainQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$mainQueueVersion <> $currentMainQueueVersion){
			TrackingAgentHelper::extCallSum( );
			TrackingAgentHelper::markJobUpDown("Trk.CommitMgrDown",$CACHE['jobStartTime']);
			DashBoardHelper::WatchMeDown();
			exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
		}
	
		//step1: 查询manager是否有记录在 db_queue 里面的 tracker_sub_queue_manage
		$lastModifyLimit = date('Y-m-d H:i:s',strtotime('-3 minutes'));
		if ( empty($CACHE['hasManagerRecord'])){
			
			$RecordOne = TrackerSubQueueManage::find()
			->andWhere("job_name='$jobName'  "  )
			->asArray()
			->one();
	
			if (empty($RecordOne)){
				Yii::$app->db_queue->createCommand(//避免2个job都要求create，用 replace into
				"insert into tracker_sub_queue_manage (job_name,is_idle,update_time,create_time) values
				('$jobName','Y','$now_str','$now_str')")
				->execute();
				sleep(1); //避免2个job都同时走 replace into，sleep 一下
			}
	
			$CACHE['hasManagerRecord'] = true; //so that 下一次进来就不需要再检测CommitManager 有没有了
		}
	
		//step 2: 尝试这个job 做active的manager
		$now_str = date('Y-m-d H:i:s');
		$lastModifyLimit = date('Y-m-d H:i:s',strtotime('-30 seconds'));
		$ManagerRec = TrackerSubQueueManage::find()
		->andWhere("job_name='$jobName' and (is_idle='Y' or update_time<'$lastModifyLimit' )"  )
		->one();
	
		if (empty($ManagerRec)){
			return ;
		}
			
	
		//mark use self to lock the manager position
		$affectRows = Yii::$app->db_queue->createCommand(
				"update tracker_sub_queue_manage set is_idle='N',job_key='$job_key',
				update_time='$now_str' where job_name='$jobName'
				and is_idle='".$ManagerRec->is_idle."' and update_time='".$ManagerRec->update_time."' ")
				->execute();
	
		//如果已经被人lock了，这个manager就是替补的，退出吧
		if ($affectRows <= 0){
			echo "others Jie Zu Xian Deng, this one $job_key return and wait for next round \n";
			return;
		}
		//step 3:
		
		/*检查 redis TrackerCommitQueue_HighP 所有元素，按照 puid 排队，do commit using value							
		 TrackMainQ/TrackingModel_539, TrackMainQ/MainQueueModel_539							
		 Delete TrackMainQ/1126_TrackingModel_539, TrackMainQ1126_/MainQueueModel_539
		 * 
			//前面route已经做了  e.g. TrackerCommitQueue_LowP/1126_1_56984 = 1
			self::RedisSet("TrackerCommitQueue_".($aMH['priority']<=2?"High":"Low")."P",
			$aMH['task_id'],1);
			$datePrefix.'_'.$puid."_".$theTaskData['id'] ,
		*/
 
			//看看有没有High Priority的需要commit，有就立即commit		
			if (empty($CACHE['last_do_LowP']))
				$CACHE['last_do_LowP'] = date('Y-m-d H:i:s');
				
			 
			$queues = $workForRedisQueues; //array('TrackerCommitQueue_HighP','TrackerCommitQueue_LowP')
			//如果是 LowP的，40秒才做一次，highP 的每次都做
			foreach ($queues as $aQueueType){
				if ($aQueueType == 'TrackerCommitQueue_LowP'){			 
					$sec_40_ago = date('Y-m-d H:i:s',strtotime('-40 seconds'));
					if ($CACHE['last_do_LowP'] > $sec_40_ago)
						continue; //skip, do nothing for LowP
					else 
						$CACHE['last_do_LowP'] = date('Y-m-d H:i:s');
				}
				
				$keys = RedisHelper::RedisExe ('hkeys',array($aQueueType));
				if (!empty($keys)){
					$toDoArray= [];
					foreach ($keys as $keyName){
						if (empty($keyName))
							continue;
						
						//extract the puid from the keyName
						$arr1 = explode('_',$keyName);
						if (empty($arr1[1])){
							echo "Err001: $aQueueType Can not extract puid from $keyName";
							continue;
						}
						//吧相同puid的放在一起，减少db chagne userx 库的次数
						$toDoArray[strval($arr1[1])][$keyName] = $keyName;
					}//end of each key name, like user_1.carrier_frequency
					
					foreach ($toDoArray as $puid=>$taskNames){
						 
	
						foreach ($taskNames as $aKeyName){
							$arr1 = explode('_',$aKeyName);
							//step 1: do the commit, also the 2 models in redis will be purged
							self::doCommitTrackingFromRedisToDb($arr1[0],$arr1[1],$arr1[2]);
							//step 2:remove highP task from redis queue
							RedisHelper::RedisExe ('hdel',array($aQueueType,$aKeyName));

							//每隔5秒，通知一次我还活着
							if (empty($CACHE['Last_alive_signal_time']))
								$CACHE['Last_alive_signal_time'] = date('Y-m-d H:i:s');
							
							$sec_5_ago = date('Y-m-d H:i:s',strtotime('-5 seconds'));
							if ($CACHE['Last_alive_signal_time'] < $sec_5_ago){
								TrackerSubQueueManage::updateAll(['update_time'=> date('Y-m-d H:i:s') ] ,['job_name'=>$jobName,'job_key'=>$job_key ] );
								$CACHE['Last_alive_signal_time'] = $now_str;
							}
							
						}
	
					}//end of each puid
				}
			}//end of HighP or LowP

			//退出时候，mark 这个job没有管了，让下一次循环进来接管
			$now_str = date('Y-m-d H:i:s');
			$affectRows = Yii::$app->db_queue->createCommand(
					"update tracker_sub_queue_manage set is_idle='Y',job_key='$job_key',
					update_time='$now_str' where job_name='$jobName'")->execute();
		
		 return ;
		}
	
	static private function doCommitTrackingFromRedisToDb($datePrefix, $puid,$taskId){
		global $CACHE;
		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		//step 1: commit the userx lt tracking table
		$trackingModelLink[0] = $datePrefix;
		$trackingModelLink[1] = 'TrackingModel';
		$trackingModelLink[2] = $taskId;
		
		$mainQueueModelLink = $trackingModelLink;
		$mainQueueModelLink[1] = 'MainQueueModel';

		$CACHE['workingMainQueueTaskModelLink'] = implode('_', $mainQueueModelLink);
		$CACHE['workingTrackingModelLink'] = implode('_', $trackingModelLink);
		
		$trackingModelArray = json_decode(self::RedisGet('TrackMainQ', $CACHE['workingTrackingModelLink']),true );
		$track_no = $trackingModelArray['track_no'];;
		$updateFields = array('parcel_type'=>1,'status'=>1,'carrier_type'=>1,'from_nation'=>1,'to_nation'=>1,
				'all_event'=>1,'total_days'=>1,'first_event_date'=>1,'from_lang'=>1,'to_lang'=>1,'last_event_date'=>1,
				'ship_out_date'=>1,'state'=>1,'stay_days'=>1,'update_time'=>1
		);
		$populateFieldandValues = array();
		if (!empty($trackingModelArray) and is_array($trackingModelArray)){
			foreach ($trackingModelArray as $k=>$v){
				if (isset($updateFields[$k]))
					$populateFieldandValues[$k] = $v;
			}
	
			Tracking::updateAll($populateFieldandValues,['track_no'=>$track_no]);
			
			//force update the top menu statistics
			TrackingHelper::setTrackerTempDataToRedis("left_menu_statistics", json_encode(array()));
			TrackingHelper::setTrackerTempDataToRedis("top_menu_statistics", json_encode(array()));
		}
		
		//step 2: commit the main Q task result，as completed
		$taskModelArray = json_decode(self::RedisGet('TrackMainQ', $CACHE['workingMainQueueTaskModelLink']),true );
		if (empty($taskModelArray) or !is_array($taskModelArray)){
			echo "Error Failed to get TrackMainQ from redis for ". $CACHE['workingMainQueueTaskModelLink'].
				"\n".print_r($taskModelArray,true)." \n";
		}
		else
			TrackerApiQueue::updateAll($taskModelArray,['id'=> $taskModelArray['id'] ]);
		
		//step 3, purge redis for the 2 kinds of models of this task
		 
		RedisHelper::RedisExe ('hdel',array('TrackMainQ',$CACHE['workingMainQueueTaskModelLink']));
		RedisHelper::RedisExe ('hdel',array('TrackMainQ',$CACHE['workingTrackingModelLink']));
		
		//step 4 维护redis里面这个客户的 total ship by 以你nations
		$old_carriers = TrackingHelper::getTrackerTempDataFromRedis("to_nations" );
		$toNations = json_decode($old_carriers,true);
		
		if (!isset($toNations[ $trackingModelArray['to_nation'] ])){
			$toNations[ $trackingModelArray['to_nation'] ] =  $trackingModelArray['to_nation'];
			TrackingHelper::setTrackerTempDataToRedis("to_nations", json_encode($toNations));
		}
		
		$old_carriers = TrackingHelper::getTrackerTempDataFromRedis("using_carriers" ,$puid);
			
		if (empty($old_carriers)){
			//重新初始化一下
			$using_carriers = array();
			$allCarriers = Yii::$app->subdb->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
					"select distinct ship_by from lt_tracking   ")->queryAll();
			foreach ($allCarriers as $aCarrier){
				$using_carriers[ $aCarrier['ship_by']  ] = $aCarrier['ship_by'] ;
			}
			TrackingHelper::setTrackerTempDataToRedis("using_carriers", json_encode($using_carriers),$puid);
		}else{
			$using_carriers = json_decode($old_carriers,true);
			if (!isset($using_carriers[ $trackingModelArray['ship_by'] ])){
				$using_carriers[ $trackingModelArray['ship_by'] ] =  $trackingModelArray['ship_by'];
				TrackingHelper::setTrackerTempDataToRedis("using_carriers", json_encode($using_carriers),$puid);
			}
		}
		
		return true;
	}
	
	/*
	 * 退出之前，看看有无assigned比自己的taks，如果有，把这些task变成 状态P，才走
	 * */
	static public function mainQHanlerExitClear( ){
		global $CACHE ;
		$job_key = $CACHE['JOBID'];
		$job_name = $CACHE['JOBNAME'];
		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		 
		$JobRec = TrackerSubQueueManage::find()
		->andWhere("job_name='$job_name' and job_type ='MH' and job_key=$job_key"  )
		->one();
		if (empty($JobRec))
			return;
		
		$arrs = explode('_', $JobRec['task_id']);
		if (count($arrs) <> 3){
			echo "Task id is not good ".$JobRec['task_id']."\n";
			return;
		}
		
		$datePrefix = $arrs[0];
		$puids = $arrs[1];
		$taskIds = $arrs[2];
		
		$assignedTaskIds=explode(",", $taskIds);

		if (!empty($assignedTaskIds))
			TrackerApiQueue::updateAll(['status'=>'P','update_time'=>date('Y-m-d H:i:s') ],['id'=>$assignedTaskIds ,'status'=>'S']);
	}
	
	static public function mainQueueHandler(){
		global $CACHE ;
		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		$INTERVAL = self::$INTERVAL_SUB_QUEUE; //SECONDS
		
		//step 0: 检查新版本，要不要重启自己
		$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
		if (empty($currentMainQueueVersion))
			$currentMainQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$mainQueueVersion))
			self::$mainQueueVersion = $currentMainQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$mainQueueVersion <> $currentMainQueueVersion){
			TrackingAgentHelper::extCallSum( );
			self::mainQHanlerExitClear();
			TrackingAgentHelper::markJobUpDown("Trk.MainQHdlDown",$CACHE['jobStartTime']);
			DashBoardHelper::WatchMeDown();
			exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
		}
	
		//step1: 查询manager是否有记录在 db_queue 里面的 tracker_sub_queue_manage
		//if not, new this process, have self job id as key, and get a job name by queue max one
		//this process is new
		if ( empty($CACHE['JOBID'])){
			$CACHE['JOBID'] =  (rand(1,9999) +  rand(1,9999) * rand(1,9999)) % 1147483647;
			$job_key = $CACHE['JOBID'];
			/*
			$maxName = Yii::$app->db_queue->createCommand(
					"select max(job_name) from tracker_sub_queue_manage where job_type ='MH' ")->queryScalar();
			//maxName like HDL11050023
				
			if (empty($maxName) or strlen($maxName) <11 ){
				$seed=0;
			}else
				$seed = substr($maxName,7,4); //后面四位是 序列号
				*/
			if (empty($CACHE['seed']))
				$CACHE['seed'] = 1;
				
			$job_name = "MQH".substr($now_str, 5,2).substr($now_str, 8,2)."". substr(strval ( 100000 + rand(1,99999) ),1);
			$CACHE['JOBNAME'] = $job_name;
	try{
			Yii::$app->db_queue->createCommand(//避免2个job都要求create，用 replace into
			"insert into tracker_sub_queue_manage (job_type,job_name,is_idle,update_time,create_time,job_key) values
			('MH','$job_name','x','$now_str','$now_str',$job_key)")
			->execute();
			sleep(1); //避免2个job都同时走 replace into，sleep 一下
				
			//确认一下是自己job注册了这个job name
			$JobRec = TrackerSubQueueManage::find()
			->andWhere("job_name='$job_name' and job_type ='MH'and  is_idle='x' and job_key=$job_key and create_time='$now_str' "  )
			->one();
	}catch(\Exception $e){
		$JobRec = [];
	}
			if (empty($JobRec)){
				$CACHE['seed'] += 2; //the next time comes in, use this as seed.
				unset($CACHE['JOBNAME']);
				unset($CACHE['JOBID']);
				echo  "JobName $job_name is already existing, exit for another try. \n";
				return;
			}else{//标记这个job idle
				$CACHE['seed'] = 1;
				$JobRec->is_idle='Y'; //通知说自己得闲
				$JobRec->save(false);
				sleep(1); //等待任务
			}
				
		}//end of already registered this sub job
	
		$job_key = $CACHE['JOBID'];
		$job_name = $CACHE['JOBNAME'];
		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		
		/*step 2:查找memTable自己是否有任务(idle=N)，如果有， update idle=S，and execute	
		completed,读取 并 修改 redis 里面的 TrackMainQ/1126_TrackingModel_539, TrackMainQ/1126_MainQueueModel_539，	
		update idle =C 
		*/
		$JobRec = TrackerSubQueueManage::find()
			->andWhere("job_name='$job_name' and job_type ='MH' and job_key=$job_key"  )
			->one();
		//task_id= $puid.'_'.'$datePrefix"."_".$theTaskData['id']."'
		
		if (empty($JobRec) ){ //no task assigned, so return and wait for next cycle
			self::mainQHanlerExitClear();
			TrackingAgentHelper::markJobUpDown("Trk.MainQHdlDown",$CACHE['jobStartTime']);
			DashBoardHelper::WatchMeDown();
			exit("self registered record is missing, exit whole process $job_name $job_key");
		}
		
		if ($JobRec['is_idle']<>'N'){
			//echo "no task assigned, so return and wait for next cycle \n";
			TrackerSubQueueManage::updateAll(['update_time'=>$now_str],['job_name'=>$job_name]);
			return;
		}
		
		$arrs = explode('_', $JobRec['task_id']);
		if (count($arrs) <> 3){
			echo "Task id is not good ".$JobRec['task_id']."\n";
			return;
		}
		
		$datePrefix = $arrs[0];
		$puids = $arrs[1];
		$taskIds = $arrs[2];
		
		$puids=explode(",", $puids);
		$taskIds=explode(",", $taskIds);
		$priorities=explode(",", $JobRec['priority']);
		
		TrackerSubQueueManage::updateAll(['update_time'=>$now_str,'is_idle'=>'S'],['job_name'=>$job_name]);
		
		$thread_data=[];
		$cleanCACHE = $CACHE;
		//step 1, 对每个task，进行处理 阶段1，然后集中在阶段2等待 subQueue完成
		 
		foreach ($taskIds as $k=>$taskId){
			$puid = $puids[$k];
			$CACHE = $cleanCACHE;
			$CACHE['puid'] = $puid;
			$trackingModelLink[0] = $datePrefix;
			$trackingModelLink[1] = 'TrackingModel';
			$trackingModelLink[2] = $taskId;
			
			$mainQueueModelLink = $trackingModelLink;
			$mainQueueModelLink[1] = 'MainQueueModel';
			
			$CACHE['workingMainQueueTaskModelLink'] = implode('_', $mainQueueModelLink);
			$CACHE['workingTrackingModelLink'] = implode('_', $trackingModelLink);
			
			$mainQueueModel = json_decode( self::RedisGet('TrackMainQ',$CACHE['workingMainQueueTaskModelLink']) ,true) ;
			$trackingModel = json_decode(self::RedisGet('TrackMainQ',$CACHE['workingTrackingModelLink']),true);
			
			//execute the request, using the redis cache
			if (empty($mainQueueModel['id'])){
			//	echo "Processed part 1 skip for TaskId $taskId, redis not found its model \n";
			}
			else{
				$thread_data['Task'.$taskId] = self::queueHandlerProcessing1($mainQueueModel, $trackingModel);
				//echo "Processed part 1 for TaskId $taskId ".date('Y-m-d H:i:s')." \n" ;
			}
		}//each task id
		
		//头13秒一定不会有结果出来的
		sleep(13);
		 
		$lastCheck = date('Y-m-d H:i:s');
		$enterLoopTime='';
		$ind=0;
		while (!empty($thread_data)){
			if ($lastCheck > date('Y-m-d H:i:s',strtotime('-2 seconds')))
				sleep(2);
			
			$lastCheck = date('Y-m-d H:i:s');
			$ind ++;
			foreach ($thread_data as $key => $rtn){	
				//做等待and 检查的工作
				//echo "Processed part 2 for TaskId $key ".date('Y-m-d H:i:s')." \n" ;
				
				if (empty($rtn['snapshot_vars'])){
					unset($thread_data[$key]);
					continue;
				}
				
				$rtn1 = self::waitForSubQueueResultComesUp($rtn['snapshot_vars']);
				/*$rtn['needNextFunction'] = true;
				  $rtn['needLoopAgain'] = true;
				  $rtn['snapshot_vars'] = get_defined_vars();
				*/
				if ($rtn1['needLoopAgain']){ //need to do it after 1 seconds
					//echo " TaskId $taskId , re Check next second ".date('Y-m-d H:i:s')."  \n" ;
					$thread_data[$key] = $rtn1;
				}else{//do not need to Loop again, can commit the result lar
					self::submitThisMainQueueResult($rtn1['snapshot_vars']);
					//echo "Processed part 3 for TaskId $key ".date('Y-m-d H:i:s')."  \n" ;
					unset($thread_data[$key]);
				}
				
			}//end of each task need checking the subQueue task
			
			//循环10次检查一次版本，要不要退出
			if ($ind % 5 == 0){
				TrackerSubQueueManage::updateAll(['update_time'=> date('Y-m-d H:i:s')] ,[ 'job_key'=>$job_key] );
				//step 0: 检查新版本，要不要重启自己
				$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
				if (empty($currentMainQueueVersion))
					$currentMainQueueVersion = 0;
			
				//如果自己还没有定义，去使用global config来初始化自己
				if (empty(self::$mainQueueVersion))
					self::$mainQueueVersion = $currentMainQueueVersion;
					
				//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
				if (self::$mainQueueVersion <> $currentMainQueueVersion){
					TrackingAgentHelper::extCallSum( );
					self::mainQHanlerExitClear();
					TrackingAgentHelper::markJobUpDown("Trk.MainQHdlDown",$CACHE['jobStartTime']);
					DashBoardHelper::WatchMeDown();
					exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
				}
			}
			
		}//while there is still thread not completed
		//this MainQueue Handler returns
		TrackerSubQueueManage::updateAll(['update_time'=>$now_str,'is_idle'=>'C'],['job_name'=>$job_name]);
		echo "MainQueue Done ready to exit ".date('Y-m-d H:i:s')." \n" ;
		
		$CACHE = $cleanCACHE;
		
		return true;
	}

	
	/**
	 +---------------------------------------------------------------------------------------------
	 * SubQueue manager
	 * 这个会是双进程，用来判断哪个subQueue Job idle，assign时间点开动，以及计算总体的 查询耗费次数
	 * 之所以用双进程，是怕其中一个manager 到时见自动exit后，没有其他manager调动SubQueue Drivers
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::sub_queue_manager();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function subQueueManager(){
		global $CACHE ;
		
		$INTERVAL = self::$INTERVAL_SUB_QUEUE; //SECONDS
		
		if (!isset ($CACHE['JOBID']))
			$CACHE['JOBID'] =  (1 +  rand(1,9999) * rand(1,9999)) % 1147483647;
		
		$job_key = $CACHE['JOBID'];
		
		$now_str = date('Y-m-d H:i:s');
		//step 0: 检查新版本，要不要重启自己
		$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
		if (empty($currentMainQueueVersion))
			$currentMainQueueVersion = 0;
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$mainQueueVersion))
			self::$mainQueueVersion = $currentMainQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$mainQueueVersion <> $currentMainQueueVersion){
			TrackingAgentHelper::extCallSum( );
			//$affectRows = Yii::$app->db_queue->createCommand(
			//		"truncate table tracker_sub_queue_manage  ")->execute();
			self::clearnSubQueue();
			TrackingAgentHelper::markJobUpDown("Trk.SubQMgrDown",$CACHE['jobStartTime']);
			DashBoardHelper::WatchMeDown();
			exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
		}
		
		//step1: 查询manager是否有记录在 db_queue 里面的 tracker_sub_queue_manage
		if ( empty($CACHE['hasManagerRecord'])){
			$lastModifyLimit = date('Y-m-d H:i:s',strtotime('-10 seconds'));
			$RecordOne = TrackerSubQueueManage::find()
				->andWhere("job_name='MANAGER'  "  )	
				->asArray()
				->one();
			
			if (empty($RecordOne)){
				 Yii::$app->db_queue->createCommand(//避免2个job都要求create，用 replace into
						"insert into tracker_sub_queue_manage (job_name,is_idle,update_time,create_time) values 
							('MANAGER','Y','$now_str','$now_str')")
						->execute();
						sleep(1); //避免2个job都同时走 replace into，sleep 一下
			}
			
			$CACHE['hasManagerRecord'] = true;			
		}
		
		//step 2: 尝试这个job 做active的manager
		$now_str = date('Y-m-d H:i:s');
		//echo "Try to do manager at $now_str\n";
		$lastModifyLimit = date('Y-m-d H:i:s',strtotime('-10 seconds'));
		$ManagerRec = TrackerSubQueueManage::find()
			->andWhere("job_name='MANAGER' and (is_idle='Y' or update_time<'$lastModifyLimit' )"  )
			->one();
		
		if (empty($ManagerRec)){
			return ;
		}
			
		
		//mark use self to lock the manager position
		$affectRows = Yii::$app->db_queue->createCommand( 
						"update tracker_sub_queue_manage set is_idle='N',job_key='$job_key',
				         update_time='$now_str' where job_name='MANAGER' 
						 and is_idle='".$ManagerRec->is_idle."' and update_time='".$ManagerRec->update_time."' ")				
						->execute();
		
		//如果已经被人lock了，这个manager就是替补的，退出吧
		if ($affectRows <= 0)
			return;		
		
		//删除update time是很久以前的sub Queue，那些事死掉的了。
		$affectRows = Yii::$app->db_queue->createCommand(
						"delete from tracker_sub_queue_manage   where  
						update_time<='".date('Y-m-d H:i:s',strtotime('-5 minutes'))."' and job_type='SH' ")
						->execute();
		
		//Step 3.1:检查TimeFrame排期，是否需要开出一个新的班车(bulk query) //$INTERVAL seconds
		//echo "Step 3.1 check redis at $now_str\n";
		
		$keys = RedisHelper::RedisExe ('hkeys',array('TrkSQTimeFrame'));
		$jobsGotAssigned=[];
		$latest_time = '';
		if (!empty($keys)){
			foreach ($keys as $aTime){
				$latest_time = $aTime;
				$jobName = self::RedisGet('TrkSQTimeFrame',$aTime);
				$jobsGotAssigned[$jobName] = $aTime;
				
				//step 3.1a	if a timeFrame existing, and not executed after 10 seconds, delete this timeFrame, 
				//mark the handler in SubQueue17Trk as idel='D',NextStart=timeFrame
				if (  $aTime < date('Y-m-d H:i:s',strtotime('-10 seconds')) ){
					//delete the assignment 10 seconds ago, not usable now
					RedisHelper::RedisExe ('hdel',array('TrkSQTimeFrame',$aTime));
					
					Yii::$app->db_queue->createCommand(
						"update tracker_sub_queue_manage set is_idle='D' ,
							update_time='$now_str', next_start='0000-00-00 00:00:00' where job_name='$jobName' ")
						->execute();
				}
			}//end of each key name, like user_1.carrier_frequency
		}
		
		//如果没有最后time frame 或者最后的time frame 是INTERVAL - 2 秒钟以前，那么生成一个新的timeFrame
		if (empty($latest_time) or $latest_time <= date('Y-m-d H:i:s',strtotime( ( 0 - $INTERVAL + 4).' seconds'))){
			
		}else {
			$affectRows = Yii::$app->db_queue->createCommand(
					"update tracker_sub_queue_manage set is_idle='Y',job_key='',
					update_time='".date('Y-m-d H:i:s')."' where job_name='MANAGER' ")
								->execute();
			//echo "return because last time slot is far way from interval +4 sec \n";
			return ;
		}
	   
		
		//Step 3.2:check all handlers in tracker_sub_queue_manage for subqueue, find the idle ones
		//有一些是 死掉的job的注册表，如果状态是 D 而且 update time 是 30秒钟前的，就删除吧
		TrackerSubQueueManage::deleteAll("is_idle='D' and job_type='SH' and update_time < '".date('Y-m-d H:i:s',strtotime('-30 seconds'))."'");
		
		$lastModifyLimit = date('Y-m-d H:i:s',strtotime('-10 seconds'));
		
		$SubQueueHdls = TrackerSubQueueManage::find()
			->andWhere("job_type='SH' and is_idle='Y' "  )
			->asArray()
			->all();
		
		$gotIdelJobs = [];
		foreach ($SubQueueHdls as $aSubQHdl){
			if (isset($jobsGotAssigned[$aSubQHdl['job_name']]))
				continue;
			
			$gotIdelJobs[] = $aSubQHdl['job_name'];
		}
	 
		//如果没有任何一个job有空，那就算了，等下一秒钟再来
		if (empty($gotIdelJobs)){
			$affectRows = Yii::$app->db_queue->createCommand(
					"update tracker_sub_queue_manage set is_idle='Y',job_key='',
					update_time='".date('Y-m-d H:i:s')."' where job_name='MANAGER' ")
								->execute();
			return;
		}
		
		//step 4	according to now datetime, create time Frames for coming time slot (2 seconds later), e.g : 9:06:00
		//如果last time是现在以前，就用下一秒钟吧
		if ($latest_time < date('Y-m-d H:i:s',strtotime(  '0 seconds')))
			$next_time = date('Y-m-d H:i:s',strtotime(  '1 seconds'));
		else{
			$next_time = new DateTime($latest_time);
			$next_time->modify("+$INTERVAL seconds");
			$next_time = $next_time->format('Y-m-d H:i:s');
		}
		
		//step 5	check all idle handles, if they are not in existing planed timeFrame, write one to new time frame,e.g. HDL025
		//对拿到的 n 个idle job，assign n 个time slot
		foreach ($gotIdelJobs as $anIdleJobName){
			self::RedisSet('TrkSQTimeFrame',$next_time , $anIdleJobName );
			\Yii::$app->db_queue->createCommand(
				" update tracker_sub_queue_manage set is_idle='N' ,
				        update_time='$now_str', next_start='$next_time' where job_name='$anIdleJobName' ")
				->execute();
			
			$ntime = new DateTime($next_time);
			$ntime->modify("+$INTERVAL seconds");
			$next_time = $ntime->format('Y-m-d H:i:s');
		}
		
		//step 6: 退出时候 release manager lock，so next job will take over the control
		$affectRows = Yii::$app->db_queue->createCommand(
				"update tracker_sub_queue_manage set is_idle='Y',job_key='',
					update_time='".date('Y-m-d H:i:s')."' where job_name='MANAGER' ")
				->execute();
		
		//echo "Finished assigned for sub Queue $next_time count:".count($gotIdelJobs)." , exit at $now_str \n";
		return ;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * SubQueue Handler
	 * 大概可以上10个进程，每个注册自己的name，然后根据redis 里面 TrkSQTimeFrame 的安排，到点开动
	 * 执行多个物流号的批量查询
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingQueueHelper::sub_queue_handler();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function subQueueHandler(){
		global $CACHE ;
		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		$INTERVAL = self::$INTERVAL_SUB_QUEUE; //SECONDS
	
		//step 0: 检查新版本，要不要重启自己
		$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
		if (empty($currentMainQueueVersion))
			$currentMainQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$mainQueueVersion))
			self::$mainQueueVersion = $currentMainQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$mainQueueVersion <> $currentMainQueueVersion){
			TrackingAgentHelper::extCallSum( );
			self::clearnSubQueue();
			TrackingAgentHelper::markJobUpDown("Trk.SubQHdlDown",$CACHE['jobStartTime']);
			DashBoardHelper::WatchMeDown();
			exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
		}
	
		//step1: 查询manager是否有记录在 db_queue 里面的 tracker_sub_queue_manage
		//if not, new this process, have self job id as key, and get a job name by queue max one
		//this process is new
		if ( empty($CACHE['JOBID'])){
			$CACHE['JOBID'] =  (1 +  rand(1,9999) * rand(1,9999)) % 1147483647;
			$job_key = $CACHE['JOBID'];
		/*	$maxName = Yii::$app->db_queue->createCommand(
				"select max(job_name) from tracker_sub_queue_manage where job_type ='SH' ")->queryScalar();
			//maxName like HDL11050023
			
			if (empty($maxName) or strlen($maxName) <11 ){
				$seed=0;
			}else
				$seed = substr($maxName,7,4); //后面四位是 序列号
			*/
			if (empty($CACHE['seed']))
				$CACHE['seed'] = 1;
			
			$job_name = "HDL".substr($now_str, 5,2).substr($now_str, 8,2)."". substr(strval ( 100000 + rand(1,99999) ),1);
			$CACHE['JOBNAME'] = $job_name;
			 
			
			try{
				Yii::$app->db_queue->createCommand(//避免2个job都要求create，用 replace into
				"insert into tracker_sub_queue_manage (job_type,job_name,is_idle,update_time,create_time,job_key) values
				('SH','$job_name','N','$now_str','$now_str',$job_key)")
				->execute();
				sleep(1); //避免2个job都同时走 replace into，sleep 一下
			
				//确认一下是自己job注册了这个job name
				$JobRec = TrackerSubQueueManage::find()
				->andWhere("job_name='$job_name' and job_type ='SH' and  is_idle='N' and job_key=$job_key and create_time='$now_str' "  )
				->one();
			 
			}catch(\Exception $e){
				$JobRec = [];
			}

			if (empty($JobRec)){
				$CACHE['seed'] += 2; //the next time comes in, use this as seed.
				unset($CACHE['JOBNAME']);
				unset($CACHE['JOBID']);
				echo "JobName $job_name $job_key is already existing, exit for another try. SQL was: "."job_name='$job_name' and job_type ='SH' and  is_idle='N' and job_key=$job_key and create_time='$now_str' "." \n";
				return;
			}else{//标记这个job idle
				$CACHE['seed'] = 1;
				$JobRec->is_idle='Y'; //通知说自己得闲
				$JobRec->save(false);
			}
							
		}//end of already registered this sub job
		
		$job_key = $CACHE['JOBID'];
		$job_name = $CACHE['JOBNAME'];
		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		
		//step 2: find self assignment in a certain time slot, update idel=N, and execute
		$JobRec = TrackerSubQueueManage::find()
			->andWhere("job_name='$job_name' "  )
			->one();
	
		if (empty($JobRec)){
			unset($CACHE['JOBNAME']);
			unset($CACHE['JOBID']);//making cache empty, will self assign a new Job Name
			return ;
		}
		
		//step 2.1如果自己的状态不是 idle, update set idle然后等待分配，
		//如果已经是idle 并且next start time 有安排，执行
		if ($JobRec->is_idle <>'Y'){
			$JobRec->is_idle='Y';
			$JobRec->update_time= $now_str;
			$JobRec->save(false);
			return;
		}
		
		//step 2.1a, 如果被分配到的 next start 是000，就是没事干，退出好了
		if ($JobRec->next_start == "0000-00-00 00:00:00"){
			//echo "job $job_name Not assigned for any next start task, so exit and enter after 1 second \n";
			return;
		}
		
		//step 2.2, 如果被分配的next start time 是 now Or +1 秒，那么就可以开播了，否则会出去sleep一秒，多不好
		$longer_time = new DateTime( $JobRec->next_start );
		$longer_time->modify("+1 seconds");
		$longer_time = $longer_time->format('Y-m-d H:i:s');
		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		$assigned_start_time = $JobRec->next_start;
		while ($now_str <= $JobRec->next_start ){	
			//如果 $now 已经大于next time 的longer time，那么自己迟到了，有问题，需要结束整个 进程
			if ($now_str > $longer_time){
				$message = "E001, $job_name now is Bigger than Next Longer Time, exit this process";
				//echo $message."\n";
				\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
				self::clearnSubQueue($job_name);
				TrackingAgentHelper::markJobUpDown("Trk.SubQHdlDown",$CACHE['jobStartTime']);
				DashBoardHelper::WatchMeDown();
				exit(0);
			}
			
	 		if ($now_str >= $JobRec->next_start and $now_str <= $longer_time){
	 			//execute
	 			$JobRec->is_idle='N';
	 			$JobRec->update_time= $now_str;
	 			$JobRec->query_start= $now_str;
	 			$JobRec->save(false);
	 			//do it
	 			//echo "start to do 17Trk query \n";
	 			self::subqHandlerByCarrier17Track();
	 			
	 			//while finished, update the status
	 			$now_str = date('Y-m-d H:i:s'); //2015-11-10
	 			$JobRec->is_idle='Y';
	 			$JobRec->update_time= $now_str;
	 			$JobRec->next_start= "0000-00-00 00:00:00";
	 			$JobRec->query_end= $now_str;
	 			$JobRec->save(false);
	 		}else {
	 			//echo "Assigned to run at ".$JobRec->next_start." , so sleep 1 second now ".date('Y-m-d H:i:s')."\n";
		 		sleep(1) ; //so go out and sleep for 1 seconds
	 		}
	 		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		}//end of while and sleep
	 	
		//step 3, 退出的时候尽量从redis remove it，不要 manager帮忙 wipe the ass，因为 wipe ass 之前会使得这个job不能被assign东西
		RedisHelper::RedisExe('hdel',array('TrkSQTimeFrame',$assigned_start_time));
		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		//echo "job $job_name Finished an assignment for sub Queue ".$JobRec->next_start.", exit at $now_str \n";
		return ;
	}
		
	
	static public function clearnSubQueue($job_name=''){
		global $CACHE;
		if (empty($job_name) and !empty($CACHE['JOBNAME']))
			$job_name = $CACHE['JOBNAME'];
		TrackerSubQueueManage::deleteAll(['job_name'=>$job_name  ]);
	}
	
	static private function putTrackingToRedis($newValues ){
		global $CACHE;
		//$CACHE['workingMainQueueTaskModelLink']  ;
		//$CACHE['workingTrackingModelLink']  ;
	 
		if (isset($newValues['update_time']))
			$newValues['update_time'] =  date('Y-m-d H:i:s');

		self::RedisSet('TrackMainQ', $CACHE['workingTrackingModelLink'] , json_encode($newValues) );
		return true;
	}
	
	static private function putPendingMainQTaskToRedis($newValues ){
		global $CACHE;
		//$CACHE['workingMainQueueTaskModelLink']  ;
		//$CACHE['workingTrackingModelLink']  ;

		if (isset($newValues['update_time']))
			$newValues['update_time'] =  date('Y-m-d H:i:s');
		
		//echo "Try to put to redis for ".$CACHE['workingMainQueueTaskModelLink']." ".$newValues['track_no']."=".$newValues['status']."\n";
		
		self::RedisSet('TrackMainQ', $CACHE['workingMainQueueTaskModelLink'] , json_encode($newValues) );
		return true;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * API队列处理器。按照priority执行一个API，然后把结果以及状态update到queue，
	 * 同时把信息写到每个user数据库的 Tracking 表中.
	 * 该方法只会执行排在最前面的一个request，然后就返回了，不会持续执行好多
	 * 该任务支持多进程并发执行
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::queueHandlerProcessing1();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function queueHandlerProcessing1($pendingOne1=[], $aTracking1=[]){
		global $CACHE ;
		$rtn = [];
		$now_str = date('Y-m-d H:i:s'); //2015-11-10
		$JOBID = $CACHE['JOBID'];
		$WriteLog = false;
		$current_time=explode(" ",microtime()); $start1_time=round($current_time[0]*1000+$current_time[1]*1000);

		//step 1, try to get a pending request in queue, according to priority
		if (!isset($CACHE['trackingModel']))
			$CACHE['trackingModel'] = new Tracking();
		
		$aTracking = $CACHE['trackingModel'];
		$aTracking->setAttributes($aTracking1);
		
		$track_no = $aTracking->track_no;
		
		if (!isset($CACHE['mainQueueModel']))
			$CACHE['mainQueueModel'] = new TrackerApiQueue();
		
		$pendingOne = $CACHE['mainQueueModel'];
		$pendingOne->id = $pendingOne1['id'];
		$pendingOne->setAttributes($pendingOne1);

		//echo "mainQueue start to handle: \n".print_r($pendingOne->getAttributes(),true); //ystest
		//step 2.1, 读取tracking 实体
		$TrackingOrigState = '';
		$TrackingOrigStatus = '';
		$stay_days_too_long_try_other_carrier = false;
	
		$TrackingOrigState = $aTracking->state;
		$TrackingOrigStatus = $aTracking->status;
		
		$addiInfo = json_decode($pendingOne->addinfo ,true);
		$return_no = empty($addiInfo['return_no'])?'':$addiInfo['return_no'];
		
		if ($aTracking['stay_days'] >= 10  and ($aTracking['stay_days'] % 5) == 0){ //and $aTracking->stay_days <=30
			$today = substr($now_str,0,10);
			
			if (empty($addiInfo[$today." try other carrier"]) )  {
				$addiInfo[$today." try other carrier"] = 1;
				$stay_days_too_long_try_other_carrier = true;
				$aTracking['addi_info'] = json_encode($addiInfo);
				self::putTrackingToRedis($aTracking->getAttributes()  );
				//$aTracking->save(false);//todo
			}
		}
		 
		$TrackingLastStatus = $TrackingOrigStatus;
		if ($TrackingLastStatus == Tracking::getSysStatus("查询等候中")){
		 
			if (!empty($addiInfo['last_status']))
				$TrackingLastStatus = $addiInfo['last_status'];
		}
		 
		if ($aTracking->status <>'no_info' and  $aTracking->carrier_type <> ''
				and ($aTracking->state =='normal' or $aTracking->state =='exception') ){
			//use the candidate and selected carriers
		}else
		{
			$pendingOne->candidate_carriers = ''; //不需要考虑其他任何 carrier啦
		}
		 
		$aliResultParsed = array();
		$aliLastEventDate = '';
		$candidate_tried = array();
		
		if(isset($addiInfo['set_carrier_type']) and $addiInfo['set_carrier_type']>=0 ){
			$userManuallySetCarrierType = true;
		}else
			$userManuallySetCarrierType = false;
					
		
		$track_no_like_global_post = false;//                                                                    SYBWQ01145087
		$track_no_like_global_post = (CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no) == "##*********##"  or CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no) == "#####********"); //LK484334664CN or SYBWQ01145087
		
		if ($track_no =='959642063008')
			echo "YS20171031Step1 ".$pendingOne->selected_carrier."\n";
		
		//如果从 oms 过来的物流号，还有可能获得他的 carrier name，那么把 ship by 和 carrier name 拼接在一起，等一下search keyword 匹配特定物流商
		if (!empty($addiInfo['carrier_name']))
			$ship_by_carrier_name_long = strtoupper($addiInfo['carrier_name'].$aTracking->ship_by);
		else
			$ship_by_carrier_name_long = strtoupper( $aTracking->ship_by);
		
		
		if (!empty($addiInfo['shipping_method_code']))
			$ship_by_carrier_name_long .= strtoupper($addiInfo['shipping_method_code'] );

		//YS0916 step 0.1, 如果指定了4PX递送carrier，优先使用4px
		//判断是否指定了4px这个递送商，如果是，那么只用4px来  YS0916
		$carrier_specified_4px = false;	
		if (!$userManuallySetCarrierType and CarrierTypeOfTrackNumber::isOSHorForeignExpress($ship_by_carrier_name_long) == false)	
		if (strpos($ship_by_carrier_name_long, "4PX") !== false or
		strpos($ship_by_carrier_name_long, "LYT") !== false or
		strpos($ship_by_carrier_name_long, "第四方") !== false or	 
		strpos($ship_by_carrier_name_long, "联邮通") !== false or
		strpos($ship_by_carrier_name_long, "递四方") !== false
		){
			$carrier_specified_4px = true;
			$pendingOne->candidate_carriers = '999000002'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000002; //不需要考虑其他任何 carrier啦
		}
		
		$foreign_track_no_alias = '';
		//YS1226 step 0.1, 如果指定了CNE递送carrier，优先使用CNE
		//判断是否指定了CNE这个递送商，如果是，那么只用CNE来 
		$carrier_specified_cne= false;
		if (!$userManuallySetCarrierType and CarrierTypeOfTrackNumber::isOSHorForeignExpress($ship_by_carrier_name_long) == false)
		if (strpos($ship_by_carrier_name_long, "CNE") !== false or	
			substr($aTracking->track_no,0,4) =='3A5V' or 
			strpos($ship_by_carrier_name_long, "全球通") !== false 		
		){
			$carrier_specified_cne = true;
			$pendingOne->candidate_carriers = '999000003'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000003; //不需要考虑其他任何 carrier啦
		}
		
		//判断是否顺丰的, if it is SF eParcel, 是速卖通线上发货吧
		$carrier_specified_SF= false;
		if (  !$userManuallySetCarrierType and (strpos($ship_by_carrier_name_long, "顺丰") !== false or
		         strpos($ship_by_carrier_name_long, "SF") !== false)
		){
			$carrier_specified_SF = true;
			$pendingOne->candidate_carriers = '999000005'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000005; //不需要考虑其他任何 carrier啦
		}
		
		if ($track_no =='959642063008')
			echo "YS20171031Step2 ".$pendingOne->selected_carrier."\n";
		
		//判断是否云途的
		$carrier_specified_YUNTU= false;
		if ( !$userManuallySetCarrierType and CarrierTypeOfTrackNumber::isOSHorForeignExpress($ship_by_carrier_name_long) == false)
		if (strpos($ship_by_carrier_name_long, "云途") !== false or
		substr($aTracking->track_no,0,2) =='YT' and strlen(trim($aTracking->track_no))==18  or
		strpos($ship_by_carrier_name_long, "YUNTU") !== false
		){
			$carrier_specified_YUNTU = true;
			$pendingOne->candidate_carriers = '999000006'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000006; //不需要考虑其他任何 carrier啦
		}
		//判断是否colissimo的
		//echo 'try to check the carrer type for '.$aTracking->track_no."\n";
		$carrier_specified_colissimo= false;
		if (!$userManuallySetCarrierType   )
		if (strpos(strtolower($ship_by_carrier_name_long), "colissimo") !== false or
			(substr($aTracking->track_no,0,2) =='6A' or substr($aTracking->track_no,0,2) =='9V' or substr($aTracking->track_no,0,2) =='8L' or substr($aTracking->track_no,0,2) =='9L') 
			and strlen(trim($aTracking->track_no) )==13 or
			(    strpos($ship_by_carrier_name_long, "法国专线") !== false and 
				(strpos($ship_by_carrier_name_long, "安骏") !== false or strpos(strtolower($ship_by_carrier_name_long), "anjun") !== false) 
			)
		){
			$carrier_specified_colissimo = true;
			$pendingOne->candidate_carriers = '999000008'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000008; //不需要考虑其他任何 carrier啦
		}
		 
		//判断是否BRT的
		$carrier_specified_BRT= false;
		if (!$userManuallySetCarrierType   )
		if (strpos($ship_by_carrier_name_long, "BRT") !== false or
		substr($aTracking->track_no,0,2) =='00' and strlen(trim($aTracking->track_no))==15 and  is_numeric(trim($aTracking->track_no))  or
		strpos(strtolower($ship_by_carrier_name_long), "corriere espresso") !== false
		){
			$carrier_specified_BRT = true;
			$pendingOne->candidate_carriers = '999000007'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000007; //不需要考虑其他任何 carrier啦
		}		
		//YS2016-1-20 step 0.1, 如果指定了万邑通递送carrier，优先使用万邑通的，否则17track他们可能查询不到的
		//判断是否指定了CNE这个递送商，如果是，那么只用CNE来
		$carrier_specified_WINIT= false;
		if (!$userManuallySetCarrierType   )
		if (CarrierTypeOfTrackNumber::isOSHorForeignExpress($ship_by_carrier_name_long) == false)
		if (strpos($ship_by_carrier_name_long, "WINIT") !== false or
			substr($aTracking->track_no,0,2) =='ID' and strlen(trim($aTracking->track_no) )==18 or
			strpos($ship_by_carrier_name_long, "万邑") !== false
		){
			$carrier_specified_WINIT = true;
			$pendingOne->candidate_carriers = '999000004'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000004; //不需要考虑其他任何 carrier啦
		}
		
		$carrier_specified_139Express = false;
		if (!$userManuallySetCarrierType   )
		if (CarrierTypeOfTrackNumber::isOSHorForeignExpress($ship_by_carrier_name_long) == false)
		if (strpos($ship_by_carrier_name_long, "139") !== false  
		){
			$carrier_specified_139Express = true;
			$pendingOne->candidate_carriers = '999000009'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000009; //不需要考虑其他任何 carrier啦
		}

		$carrier_specified_Pony = false;
		if (!$userManuallySetCarrierType   )
		if (CarrierTypeOfTrackNumber::isOSHorForeignExpress($ship_by_carrier_name_long) == false)
		if (strpos(strtolower($ship_by_carrier_name_long), "pony") !== false
		){
			$carrier_specified_Pony = true;
			$pendingOne->candidate_carriers = '999000010'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000010; //不需要考虑其他任何 carrier啦
		}
		
		if (!$userManuallySetCarrierType   )
		if (CarrierTypeOfTrackNumber::isOSHorForeignExpress($ship_by_carrier_name_long) == false)
		if (strpos($ship_by_carrier_name_long, "CNCAI") !== false  and strlen(trim($aTracking->track_no) )==22 
		 
		){
		 
			$pendingOne->candidate_carriers = '999000001'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000001; //不需要考虑其他任何 carrier啦
		}
		
		//如果是 速卖通订单，并且是 carrier name = "SF eParcel"，还是用速卖通线上发货吧，默认的话
		if (!$userManuallySetCarrierType and strpos($ship_by_carrier_name_long, "SF eParcel") !== false 
		    and $aTracking->source =='O' and $aTracking->platform =='aliexpress' and !empty($toNation2Code ) ){
			$pendingOne->candidate_carriers = '999000001'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 999000001; //不需要考虑其他任何 carrier啦
		}
		
		
		//如果是万邑通了，判断是不是已经发货了5天以上了，如果5天以上了，就不要用他的内部IDxxxx去查，而是要用他的return no，RG654654CN 这种去查 全球邮政
		if (!$userManuallySetCarrierType   )
		if ($carrier_specified_WINIT or $pendingOne->selected_carrier == 999000004){
			if (!empty($return_no) and 
			 ($aTracking->ship_out_date < date('Y-m-d',strtotime('-5 days')) or 
			  $aTracking->create_time < date('Y-m-d',strtotime('-5 days')) ) and 
			  substr($aTracking->track_no,0,2) =='ID' and strlen(trim($aTracking->track_no) ) ==18 and 
			  substr($return_no,0,2) <>'ID' and strlen(trim($return_no))==13  	
			){
				$carrier_specified_WINIT = false;
				$pendingOne->selected_carrier = 0;// 用全球邮政吧
				$foreign_track_no_alias = $return_no;
			}
		}
		
		
		//判断是否指定了燕文物流这个递送商，如果是，那么只用4px来  YS1006
		$carrier_specified_yanwen = false;
		if (!$userManuallySetCarrierType   )
		if (CarrierTypeOfTrackNumber::isOSHorForeignExpress($ship_by_carrier_name_long) == false)
		if (strpos(strtoupper($aTracking->ship_by), "YANWEN") !== false or
		strpos(strtoupper($aTracking->ship_by), "YAN WEN") !== false or
		strpos(strtoupper($aTracking->ship_by), "燕文") !== false){
			$carrier_specified_yanwen = true;
			$pendingOne->candidate_carriers = '190012'; //不需要考虑其他任何 carrier啦
			$pendingOne->selected_carrier = 190012; //不需要考虑其他任何 carrier啦
		}
		/*
		 1）smt 订单优先使用smt 查询包裹
		2）如果smt 没有查询到结果，用17track
		3）如果smt 有结果，判断其 最新事件是否3天内，如果是，应用smt 查询结果， 否的话是用17Track，如果17Track 有结果，smt 有结果，取时间最近的结果为准
		*/
		// echo "ys1 ready to use ".$pendingOne->selected_carrier."\n";
		//step 0.2, 判断这个订单是否smt，如果是，先玩玩smt api直接parcel，如果失败了，在用17Track
		$toNation2Code = $aTracking->getConsignee_country_code();

		if ($track_no =='959642063008')
			echo "YS20171031Step3 ".$pendingOne->selected_carrier."\n";
					;
		if ( !isset($addiInfo['set_carrier_type']) and $pendingOne->selected_carrier == 999000001  or $pendingOne->selected_carrier < 999000002 
			and $aTracking->source =='O' and $aTracking->platform =='aliexpress' and !empty($toNation2Code ) ){
			//use smt interface to query
			if (empty($addiInfo['shipping_method_code'])){ 
				$addiInfo['shipping_method_code']="CAINIAO_STANDARD";
			}
			
			if ($track_no =='959642063008')
				echo "YS20171031Step4 ".$addiInfo['shipping_method_code']."\n";
			
			if (!empty($addiInfo['shipping_method_code'])){
					$aliResult = TrackingAgentHelper::queryAliexpressParcel($aTracking->seller_id, $addiInfo['shipping_method_code'] ,$aTracking->track_no,$toNation2Code, $aTracking->order_id);
				if ($aliResult['success']){
					$aliResultParsed = $aliResult['parsedResult'];
					$aliLastEventDate = (isset($aliResultParsed['last_event_date']) ? $aliResultParsed['last_event_date'] :'');
					 
					$daysAgo = date('Y-m-d H:i:s',strtotime('-4 days'));
					$daysAgo10 = date('Y-m-d H:i:s',strtotime('-10 days'));
					//如果 递送商系 China Post Ordinary，而且最后更新日期已经是4天前，认为这个是时候移动到已忽略了，因为平邮不会再有更新了
					if(strpos(strtolower($aTracking->ship_by), "china post ordinary") !== false and
					!empty($aliLastEventDate) and substr($aliLastEventDate,0,10) <= substr($daysAgo,0,10)){
						$aliResult['parsedResult']['status'] = 'ignored'; 
					}
					
					if ($track_no =='959642063008')
						echo "YS20171031Step5 ".$aliLastEventDate."\n";
					
					//如果smt返回的最后时间日期是10天内的，可信，直接拿这个result了，否则要和 17track的result 对比
					if (strlen($aliLastEventDate) >= 10 and 
					    ! (substr($aliLastEventDate,0,10) < substr($daysAgo10,0,10) and $track_no_like_global_post ) ){
						
						if ($track_no =='959642063008')
							echo "YS20171031Step6 ".$aliLastEventDate."\n";
						
						$aliResult['parsedResult']['carrier_type'] = 999000001;
						$rtn = self::commitTrackingResultUsingValue($aliResult['parsedResult'], $pendingOne->puid);
						$pendingOne->status='C';
						$pendingOne->selected_carrier = 999000001 ; // mark for using 阿里explress查询接口
						//put to redis is fine, do not save $pendingOne->save(false);
						$rtn['message'] = "using 阿里explress查询接口 Success";
						$rtn['success'] = true;
						self::putPendingMainQTaskToRedis($pendingOne->getAttributes());
						return $rtn;
					}else {
						$pendingOne->selected_carrier = 0;
					}
						
					
					
					
				}//end if ali query success
			}//end if shipping method code is no empty
		}
	

		if ($track_no =='959642063008')
			echo "YS20171031Step7 " . ''."\n";
		
		
		//Step 1， 判断这个puid 的账号健康度，如果不太健康了，ignore这个task
		$track_success_distribute_str = TrackingHelper::getTrackerTempDataFromRedis("success_distribute");
		$track_success_distribute = json_decode($track_success_distribute_str,true);
		if (empty($track_success_distribute)) $track_success_distribute = array();
	
		//判断是否这个order 的order date 都是时间太近而不成功的，如果是，skip 时间太近的那些
		$thisDate = date('Y-m-d');
		$days_gap = -1;
		if (!empty($aTracking->ship_out_date)){
			$d1=strtotime($thisDate);
			$d2=strtotime($aTracking->ship_out_date);
			$days_gap = round(($d1-$d2)/3600/24);
		}
	
		if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 4 job:".$CACHE['JOBID'] ],"edb\global");
			
		//如果优先级高，就不考虑 skip 了，都做一下查询
		if ($days_gap >= 0 and $pendingOne->priority > 2 and
			$pendingOne->selected_carrier < 999000002
		  ){
			$format_print_letr = CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true );
			$track_letr_pattern_daysgap_str = TrackingHelper::getTrackerTempDataFromRedis("letr_pattern_daysgap_$thisDate");
			$track_letr_pattern_daysgap = json_decode($track_letr_pattern_daysgap_str,true);
			if (empty($track_letr_pattern_daysgap)) $track_letr_pattern_daysgap = array();
				
			//如果这个pattern 的物流号在这个时间段失败数量大于成功数量的10个，就不做了
			if (!isset($track_letr_pattern_daysgap["$format_print_letr $days_gap fail"]))
				$track_letr_pattern_daysgap["$format_print_letr $days_gap fail"] = 0;
			if (!isset($track_letr_pattern_daysgap["$format_print_letr $days_gap success"]))
				$track_letr_pattern_daysgap["$format_print_letr $days_gap success"] = 0;
				
			if ($track_letr_pattern_daysgap["$format_print_letr $days_gap fail"] - $track_letr_pattern_daysgap["$format_print_letr $days_gap success"] > 10
			and  $track_letr_pattern_daysgap["$format_print_letr $days_gap fail"] > 2* $track_letr_pattern_daysgap["$format_print_letr $days_gap success"]
			){
				$message ="Ignore Puid ".$pendingOne->puid." Tracking ".$pendingOne->track_no ." 这个pattern 的物流号在这个时间段失败数量大于成功数量的10个，就不做了";
				$pendingOne->status = 'I';
				$pendingOne->addinfo = $message;
				//put to redis is fine, do not save$pendingOne->save();
				self::commitTrackingResultUsingValue(array('track_no'=>$pendingOne->track_no,'status'=>'suspend'),$pendingOne->puid);
				$rtn['message'] = "ignore";
				$rtn['success'] = false;
				self::putPendingMainQTaskToRedis($pendingOne->getAttributes());
				//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message ],"edb\global");
				return $rtn;
			}
		}//end of days gap
	
		$original_seleted_carrier = $pendingOne->selected_carrier;
		if ($track_no =='959642063008')
			echo "YS20171031Step8 ".$original_seleted_carrier."\n";
		
		//step 3, 生成sub queue 任务，并发对可能的渠道 进行本请求的处理
		//step 3.1, according to 物流号规则判断是那种物流类型渠道的
		$track_no_like_global_post = false;//                                                                    SYBWQ01145087
		$track_no_like_global_post = (CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no) == "##*********##"  or CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no) == "#####********"); //LK484334664CN or SYBWQ01145087
			
		//如果是燕文的，就不要搞了
		if ($track_no_like_global_post and strtoupper( substr($pendingOne->track_no,0,1)) =='Y' or strtoupper( substr($pendingOne->track_no,11,2)) =='YP')
			$track_no_like_global_post = false;
			
		$main_queue_task_selected_carrier = $pendingOne->selected_carrier;
		if ($pendingOne->candidate_carriers <> ''){
			$candidate_carrier_types = explode(",", $pendingOne->candidate_carriers);
		}else{
			$candidate_carrier_types = TrackingHelper::getCandidateCarrierType($pendingOne->track_no, $aTracking->ship_by);
			//when there is no returned, check all candidates
			$pendingOne->candidate_carriers = implode(",", $candidate_carrier_types);
		}
			
		//echo "ys2.1 ready to use pending one's = ".$pendingOne->candidate_carriers." afte calculation:".print_r($candidate_carrier_types,true)."\n";
		 
		//ys0917 如果他过往 4 天前订单，有10% 几率 4px 成功的，或者尝试数量还没有8次，就加入4px作为candidate
		if (empty($CACHE['puid_use_4px'][''.$pendingOne->puid] ) and $pendingOne->selected_carrier < 999000002){ //如果缓存没有记录，自己load出来计算一下
			$px4_daysgap_str = TrackingHelper::getTrackerTempDataFromRedis("4px_$thisDate");
			if (!empty($px4_daysgap_str))
				$px4_daysgap = json_decode($px4_daysgap_str,true);
				
			if (!empty($px4_daysgap_str) and !empty($px4_daysgap['totalCount'])){
				if (empty($px4_daysgap['success']))
					$px4_daysgap['success'] = 0;
					
				if ($px4_daysgap['totalCount']>=8 and $px4_daysgap['success']/$px4_daysgap['totalCount'] < 0.1 ){
					//咩都不要做
				}else
				{ //加入到candidate
					$candidate_carrier_types['999000002'] = 999000002;
				}
			}else
				$candidate_carrier_types['999000002'] = 999000002;
		}else //如果已经cache了要做这个puid，就直接加进去好了
			$candidate_carrier_types['999000002'] = 999000002;
			
		//echo "ys2.2 ready to use ".print_r($candidate_carrier_types,true)."\n";
		//step 3.2, 如果selected_carrier = -1， 表示上次执行发现推测的所有carrier都没有结果，本次是翻工
		//          那么就全部candidate carrier都试试。
		//          如果selected_carrier >= 0, 上次的candidate查找已经成功。使用上次的就可以。
		//          如果selected_carrier = -100, 初始执行，检查该用户常用的carrier type，按照常用顺序来attampt
		// 如果常用第一个就成功并且常用第一个使用频率是第二个的1倍以上，差的绝对值大于5，那么就放弃第二个可能的carrier type
		//     否则尝试第二个carrier，如果多个carrier都有结果，以时间最近的carrier 为准
		$candidate_tried = array();
			
		//Load 该客户历史统计的渠道使用比例
		$carrier_frequency_str = TrackingHelper::getTrackerTempDataFromRedis("carrier_frequency");
		$carrier_frequency = json_decode($carrier_frequency_str,true);
		/*Sample Data: $carrier_frequency = array('0'=>50, '10009'=>23) */
		if (empty($carrier_frequency)) $carrier_frequency = array();
		if (!is_array($carrier_frequency))
			$carrier_frequency = array();
	
		//	倒序排序并且保持 key 和 value 的关系
		arsort ($carrier_frequency);
			
		//如果selected_carrier > 0, 上次的candidate查找已经成功。使用上次的就可以。
		if ($pendingOne->selected_carrier >= 0){
		//	echo "YS3.1.a found main queue selected carrier =".$pendingOne->selected_carrier . "\n";
			if (! $stay_days_too_long_try_other_carrier or strlen(strval($pendingOne->selected_carrier))==9 and 
				substr(strval($pendingOne->selected_carrier),0,8)=='99900000'){
				if ($pendingOne->selected_carrier <> 999000001){ //这个是smt，不需要做subQueue处理的
					$candidate_tried[ 'a'.$pendingOne->selected_carrier ] =  1;
					$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), $pendingOne->selected_carrier,$TrackingLastStatus,$aTracking->getConsignee_country_code(),$aTracking->ship_by ,$foreign_track_no_alias);		//ystest
				//	echo "ys3.1.0 for ".$pendingOne->selected_carrier." \n";
				}
				//如果已经指定了的 carrier type 不是 0 （全球邮政），但是 物流格式是 0，那么，添加一个 使用 全球邮政 查询的请求，如果全球邮政有结果，优先用他的
				if ($pendingOne->selected_carrier <> 0 and $pendingOne->selected_carrier <> 999000002 and $pendingOne->selected_carrier <> 999000001 and $track_no_like_global_post){
					$candidate_tried[ 'a0' ] =  1;
					$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), 0,$TrackingLastStatus,$aTracking->getConsignee_country_code(),$aTracking->ship_by,$foreign_track_no_alias);//ystest
				}
			//	echo "ys3.1.1 \n";
			}else{//运输途中太久，并且上次用的不是全球邮政，尝试用用其他的查查
				$candidate_carrier_types = TrackingHelper::getCandidateCarrierType($pendingOne->track_no,$aTracking->ship_by);
				//echo "ystest a".print_r($candidate_carrier_types,true)."\n ";
				foreach ($candidate_carrier_types as $carrier_type){
				//	echo "ys3.1.2 $carrier_type \n";
					if ($carrier_type <> 999000001){ //这个是smt，不需要做subQueue处理的
						$candidate_tried['a'.$carrier_type] = 1;
						//随便insert，如果这个main queue id 和carrier type 组合之前试过了，会skip的。
						self::insertOneSubQueue($pendingOne->getAttributes(), $carrier_type,$TrackingLastStatus,$aTracking->getConsignee_country_code(),$aTracking->ship_by,$foreign_track_no_alias);//ystest
					}
					//如果已经指定了的 carrier type 不是 0 （全球邮政），但是 物流格式是 0，那么，添加一个 使用 全球邮政 查询的请求，如果全球邮政有结果，优先用他的
					if (  $track_no_like_global_post and !isset($candidate_tried['a0']) and  
						  !self::isThisCarrierUseInternalApi($pendingOne->selected_carrier)
						 ){
						$candidate_tried[ 'a0' ] =  1;
						$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), 0,'','',$aTracking->ship_by,$foreign_track_no_alias);
					}
	
				}//end of each candidate carrier type
			}//end of 运输过久，用用其他的看 已签收没有
		}

		if ($track_no =='959642063008')
			echo "YS20171031Step9 ".$pendingOne->selected_carrier."\n";
		
		//如果selected_carrier = 0, 检查该用户常用的carrier type，按照常用顺序来attampt
		//如果selected_carrier = -100, 初始执行，检查该用户常用的carrier type，按照常用顺序来attampt
		//如果是-100，也就是第一次尝试，并且该用户录入的 ship by 已经有上次成功的记录了，那么先尝试只用上次的那个
		if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 7 job:".$CACHE['JOBID'] ],"edb\global");
	
		$current_time=explode(" ",microtime()); $start5_time=round($current_time[0]*1000+$current_time[1]*1000);
			
		if (self::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_main step5 getit mainjobid=$JOBID,t5_t4=".($start5_time-$start4_time));
		$shipBy = $aTracking->ship_by;
		
		if ($pendingOne->selected_carrier == -100){
			TrackingAgentHelper::extCallSum("Tracking.FirstTry",0,false);
			$history_success_carrier = TrackingHelper::getSuccessCarrierFromHistoryByCodePattern(CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true),date('Y-m-d'));
			if ($history_success_carrier == '')
				$history_success_carrier = TrackingHelper::getSuccessCarrierFromHistoryByCodePattern(CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true),date('Y-m-d',strtotime('-1 day')) );
	
			if ($history_success_carrier <> '')
				$carrier_frequency = array($history_success_carrier => 100);
	
			//除了历史使用统计作为参考，更重要是使用客户的ship by 作为参考，如果ship by写明了，优先是有ship by 写的方式
			
			$biggestFreq = 100;
			if (!empty($shipBy)  ){
			
				$carriers_array = CarrierTypeOfTrackNumber::testShipBySpecifiedCarrier($shipBy);
				/*$carriers_array = array('DHL'=>'10001')
				 * */
				foreach ($carriers_array as $shipCode=>$shipName){
					 
						$biggestFreq -- ;
						$carrier_frequency[$shipCode]=$biggestFreq;
					 
				}
				
				//	倒序排序并且保持 key 和 value 的关系
					
				//ys0917 如果有4px的candidate，把 carrier frequency 放到前面，因为4px速度快，优先使用
				if (isset($candidate_carrier_types['999000002'])  )
					$carrier_frequency['999000002']=100 + 1;
				
				if (isset($candidate_carrier_types['999000003'])  )
					$carrier_frequency['999000003']=100 + 1;
				
				if (isset($candidate_carrier_types['999000004'])  )
					$carrier_frequency['999000004']=100 + 1;
				
				if (isset($candidate_carrier_types['999000005'])  )
					$carrier_frequency['999000005']=100 + 1;
				
				if (isset($candidate_carrier_types['999000006'])  )
					$carrier_frequency['999000006']=100 + 1;
				
				if (isset($candidate_carrier_types['999000007'])  )
					$carrier_frequency['999000007']=100 + 1;
				
				if (isset($candidate_carrier_types['999000008'])  )
					$carrier_frequency['999000008']=100 + 1;
				
				if (isset($candidate_carrier_types['999000009'])  )
					$carrier_frequency['999000009']=100 + 1;
				
				if (isset($candidate_carrier_types['999000010'])  )
					$carrier_frequency['999000010']=100 + 1;
				
				arsort ($carrier_frequency);
			}//end of ship by got iput
		}//end of when carrier type = -100, means has no idea what carrier
		//echo "ys 3.0  candidate_carrier_types = ".print_r($candidate_carrier_types,true)."\n";
		//echo "ys3 ready to use ".print_r($carrier_frequency,true)."\n";

		if ($track_no =='959642063008')
			echo "YS20171031Step10 ".$pendingOne->selected_carrier."<br>";
		
		if ($pendingOne->selected_carrier == -100 and count($carrier_frequency)> 0){
				
			$results_array = array();
	
			//从以往历史中，使用频率最高的开始尝试,找出
			$last_frequence = 0;
			
			//如果指定了物流商，那么只要玩他指定的就可以，其他不需要玩了
			$specified_carriers_array = CarrierTypeOfTrackNumber::testShipBySpecifiedCarrier($shipBy);

			foreach ($carrier_frequency as $carrier_type => $frequence){
				if (!isset($candidate_carrier_types[''.$carrier_type]) )
					continue;
					
				if (!empty($candidate_tried))
					continue; //串行尝试，先做 一个可能性
					
				//e.g: $carriers_array = array('DHL'=>'10001')
				if (!empty($specified_carriers_array) and empty($specified_carriers_array[strval($carrier_type)]) )
					continue;
				
				//	如果上一个用得多的carrier比这个多了10，那么就认为不会是这个了，skip吧
				//echo "try to fuck $carrier_type ,  last_frequence = $last_frequence ,frequence=$frequence , is global post $track_no_like_global_post <br>";
				if ($last_frequence > 0 and $last_frequence >= $frequence + 9  ){
					if (!($track_no_like_global_post and $carrier_type == '190012')){
						//echo "Post? $track_no_like_global_post Tracking ".$pendingOne->track_no ." 因为当前$carrier_type 使用频率比上一个少10。skip这个carrier尝试".print_r($carrier_frequency,true)."<br>";
						//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"Tracking ".$pendingOne->track_no ." 因为当前$carrier_type 使用频率比上一个少10，并且上一个物流已经查到结果。skip这个carrier尝试" ],"edb\global");
						//如果是 全球邮政类型的，并且又配拍到190012，那就不要跳过 燕文
						continue;
					}
				}
					
				//	将要并发进行的不同carrier type写入到sub queue，然后等待结果返回来
				$last_frequence = $frequence;
					
				if ($carrier_type <> 999000001){ //这个是smt，不需要做subQueue处理的
					$candidate_tried['a'.$carrier_type.''] = 1;
					self::insertOneSubQueue($pendingOne->getAttributes(), $carrier_type,$TrackingLastStatus,$aTracking->getConsignee_country_code(),$aTracking->ship_by ,$foreign_track_no_alias);//ystest
				}
					
				//如果已经指定了的 carrier type 不是 0 （全球邮政），但是 物流格式是 0，那么，添加一个 使用 全球邮政 查询的请求，如果全球邮政有结果，优先用他的
				if (  !self::isThisCarrierUseInternalApi($carrier_type) 
						 and  $track_no_like_global_post and !isset($candidate_tried['a0'])){
					$candidate_tried[ 'a0' ] =  1;
					$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), 0,$TrackingLastStatus,$aTracking->getConsignee_country_code(),$aTracking->ship_by,$foreign_track_no_alias);//ystest
				}
				 
			}//end of each carrier frequency in history
		}//end of when selected carrier == 0, means first run of such track no
		//echo "YS 4 .x \n";
		//如果selected_carrier = -1， 表示上次执行发现推测的所有carrier都没有结果，本次是翻工,全部都尝试

		if ($track_no =='959642063008')
			echo "YS20171031Step11 ".$pendingOne->selected_carrier."<br>";
		
		
		if ($pendingOne->selected_carrier == -1 or count($carrier_frequency)== 0){
			$addis  = json_decode($pendingOne->addinfo,true);
	
			//如果指定了物流商，那么只要玩他指定的就可以，其他不需要玩了
			$specified_carriers_array = CarrierTypeOfTrackNumber::testShipBySpecifiedCarrier($shipBy);
			//e.g: $carriers_array = array('DHL'=>'10001')
			
			foreach ($candidate_carrier_types as $carrier_type){
					
				//如果是全球邮政的 物流号，不要尝试其他无聊的，浪费
				if ($track_no_like_global_post and $carrier_type <> '0')
					continue;
					
				if (isset($addis['tried']['a'.$carrier_type])) //既然是翻工，上次做过的就不要做了
					continue;
					
				//如果是国内物流商接口，就不要节省他了
				if ($carrier_type < 999000002 and !empty($specified_carriers_array) and empty($specified_carriers_array[strval($carrier_type)]) )
					continue;

				//记录统计这个Carrier Type for 这种track format成功与否
				//Load 该客户今天的carrier type 成功记录
				$forDate = date('Y-m-d');
				$format_print = CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true );
				$carrier_success_rate = TrackingHelper::getTrackerTempDataFromRedis("carrier_success_rate_$forDate");
				$carrier_success_rate = json_decode($carrier_success_rate,true);
				/*Sample Data: $carrier_success_rate = array('RG******CN'=>array('0'=>array('Success'=>10,'Fail'=>5))) */
				if (empty($carrier_success_rate)) $carrier_success_rate = array();
					
				if (empty($carrier_success_rate[$format_print][$carrier_type]['Success']))
					$carrier_success_rate[$format_print][$carrier_type]['Success']=0;
					
				if (empty($carrier_success_rate[$format_print][$carrier_type]['Fail']))
					$carrier_success_rate[$format_print][$carrier_type]['Fail']=0;
					
				if ($carrier_success_rate[$format_print][$carrier_type]['Success'] == 0 and
				$carrier_success_rate[$format_print][$carrier_type]['Fail'] >10){
					//Do Nothing, ignore such carrier type for this format print
				}else{
					if ($carrier_type <> 999000001){ //这个是smt，不需要做subQueue处理的
						$candidate_tried['a'.$carrier_type] = 1;
						//随便insert，如果这个main queue id 和carrier type 组合之前试过了，会skip的。
						self::insertOneSubQueue($pendingOne->getAttributes(), $carrier_type,$TrackingLastStatus,$aTracking->getConsignee_country_code(),$aTracking->ship_by,$foreign_track_no_alias);//ystest
					}
					//如果已经指定了的 carrier type 不是 0 （全球邮政），但是 物流格式是 0，那么，添加一个 使用 全球邮政 查询的请求，如果全球邮政有结果，优先用他的
					if (  $track_no_like_global_post and !isset($candidate_tried['a0'])){
						$candidate_tried[ 'a0' ] =  1;
						$rtn = self::insertOneSubQueue($pendingOne->getAttributes(), 0,'','',$aTracking->ship_by,$foreign_track_no_alias);
					}
				}
			}//end of each candidate carrier type
		} // end of selected_carrier = -1， 表示上次执行发现推测的所有carrier都没有结果
		if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 8,wait for subQueue job:".$CACHE['JOBID'] ],"edb\global");
		//step 3.3,sleep,然后获取结果，如果一个结果都没有获取得到，尝试把 seleted Candidate 设置为 -1，那么
		//下次再做这个request会全部candidate都try
		//如果有多个结果获得了，获取时间最近的那个
		$candidateDoneCount = 0;
		$SeletedSuccessCarrierType = -1;
		$elapsedTimeSeconds = 0;
		$earliestEventOfResult = '';
		$thisCarrierTypeSuccess = 'Fail';
		// echo " YS6 .". print_r($candidate_tried,true)." and ready to collect results" ; return;
		
		$rtn['needNextFunction'] = true;
		$globalCache=$CACHE;
		$rtn['snapshot_vars'] = get_defined_vars();
		return $rtn;

	}//end of queue handler processing	
	
	static function submitThisMainQueueResult($Parameters_list){
		global $CACHE ;
		foreach ( $Parameters_list as $key => $value )			
		//if (! isset ( ${$key} ))
			${$key} = $value;
		
		if (isset($globalCache))
			$CACHE = $globalCache;
		
		$errorMsg = "candidateDoneCount = $candidateDoneCount, count(candidate_tried) = ". count($candidate_tried).";";
		$timeOut=false;
		if ($candidateDoneCount < count($candidate_tried) and $SeletedSuccessCarrierType<0){
			$timeOut=true;
			$errorMsg .="so time out;";
		}
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 10,done from subQueue job:".$CACHE['JOBID'] ],"edb\global");
		//step 4, update such request and its tracking record
		//如果 $SeletedSuccessCarrierType = -1，就是还没有找到任何有结果的
		//把这次的carrier type使用次数+1 写到 config中，次数最大值是 100，也就是太久的使用频率不会很影响近期的carrier使用
		//Load 该客户历史统计的渠道使用比例
		$carrier_frequency_str = TrackingHelper::getTrackerTempDataFromRedis("carrier_frequency");
		$carrier_frequency = json_decode($carrier_frequency_str,true);
		/*Sample Data: $carrier_frequency = array('0'=>50, '10009'=>23) */
		if (empty($carrier_frequency)) $carrier_frequency = array();
		if (!is_array($carrier_frequency))
			$carrier_frequency = array();
			
		//	倒序排序并且保持 key 和 value 的关系
		arsort ($carrier_frequency);
		
		$results['success'] = false;
		//echo "4.1.a using SeletedSuccessCarrierType = $SeletedSuccessCarrierType ";
		/*
		 *   $aliResultParsed = array();
		$aliLastEventDate = '';
		如果17Track渠道插叙不到，但是速卖通有结果，用速卖通的
		* */
		if ($SeletedSuccessCarrierType < 0 and !empty($aliLastEventDate)){
			$SeletedSuccessCarrierType = 999000001;//smt 官方
			$parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType] = $aliResultParsed;
		}else{
			//3）如果smt 有结果，判断其 最新事件是否3天内，如果是，应用smt 查询结果， 否的话是用17Track，如果17Track 有结果，smt 有结果，取时间最近的结果为准
			if (isset($parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType]['last_event_date']) and
			isset($aliResultParsed['last_event_date']) and
			$parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType]['last_event_date'] < $aliResultParsed['last_event_date']  ){
					
				$SeletedSuccessCarrierType = 999000001;
				$parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType] = $aliResultParsed;
			}//end of 使用smt 结果覆盖17结果
		}
			
		//echo "Got SeletedSuccessCarrierType = $SeletedSuccessCarrierType \n";
		//echo "Step 3 Got parsed result:".print_r($parsed17TrackResultForSubqueue,true)."\n";
		if ($SeletedSuccessCarrierType >= 0){
			$a_sub_queue_reord = (isset($subQueueRecord[$SeletedSuccessCarrierType]) ? $subQueueRecord[$SeletedSuccessCarrierType] : '');
			$pendingOne->run_time = (isset($a_sub_queue_reord->run_time ) ? $a_sub_queue_reord->run_time : 0);
			//There is no result field in Main Queue, it is in Sub Queue Level now
		
			//17Track 返回的结果数组d里面的 carrier type，可能是乱来的，所以不可信，这里需要自己overwrite一下
			$parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType]['carrier_type'] = $SeletedSuccessCarrierType;
		
			$rtn = self::commitTrackingResultUsingValue($parsed17TrackResultForSubqueue[$SeletedSuccessCarrierType], $pendingOne->puid);
		
			$pendingOne->selected_carrier = $SeletedSuccessCarrierType;
		
			//更新历史统计数据
			if (!isset($carrier_frequency[$SeletedSuccessCarrierType]))
				$carrier_frequency[$SeletedSuccessCarrierType] = 0;
		
			$carrier_frequency[$SeletedSuccessCarrierType] ++;
		
			//如果有任何一种渠道累计大于100的，大家都减去10，防止Old data occupies the toilet forever
			if ($carrier_frequency[$SeletedSuccessCarrierType] > 100)
			foreach ($carrier_frequency as $carrier_type_1 => $used_count_1){
				$carrier_frequency[$carrier_type_1] = ($used_count_1 - 10) < 0 ? 0 : ($used_count_1 - 10);
				if ($carrier_frequency[$carrier_type_1] == 0)
					unset($carrier_frequency[$carrier_type_1]);
			}
		
			TrackingHelper::setTrackerTempDataToRedis("carrier_frequency",json_encode($carrier_frequency));
		}
			
		$pendingOne->try_count = $pendingOne->try_count + 1;
		$pendingOne->update_time = date('Y-m-d H:i:s');
		if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 8.5 ,done from subQueue job:".$CACHE['JOBID'] ],"edb\global");
		$current_time=explode(" ",microtime()); $start6_time=round($current_time[0]*1000+$current_time[1]*1000);
		if (self::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_main step6 getit mainjobid=$JOBID,t6_t5=".($start6_time-$start5_time));
		
		//Step 4.1, check if result means success = true
		//if not success, update it as Retry(status still = P) or Failed.(when try count >=3, mark it failed)
		//echo "ys1: ".$pendingOne->status."\n";
		if ($SeletedSuccessCarrierType == -1  ){
			$pendingOne->status = ($pendingOne->try_count >=2 ? "F" : "P");
			if ($pendingOne->status =='F')
				$errorMsg .= "F4.1;";
			echo "main Status got Fail due to reason 1.\n";
		}else
			$pendingOne->status = 'C';
		
		//如果原来 selected_carrier == -100，是初始状态并且这次 selected Carrier=-1，那么还是让他状态是P，再做一次
		if ($pendingOne->selected_carrier == -1 and $SeletedSuccessCarrierType=-1){
			$pendingOne->status = "F";
			echo "main Status got Fail due to reason 2.\n";
			$errorMsg .= "F4.1a;";
		}
		
		//echo "ys2: ".$pendingOne->status."\n";
		if ($pendingOne->selected_carrier == -100 and $SeletedSuccessCarrierType=-1){
			/*yzq comment on 2015-8-4
			 * 本来是让他进行重试，并且后面会扶着 selected carrier -1，就全部candidate 重试。
			* 但是现在为了节省api开销，决定不要对全部carrier重试了，物流号格式匹配到的结果没有就算了
			*
			* $pendingOne->status = "P";
			*
			* */
			//\Yii::info(['Tracking', __CLASS__ , __FUNCTION__ , 'Background' , $pendingOne->track_no." need retry for rest candidates"], "edb\global");
		}
		//echo "ys3: ".$pendingOne->status."\n";
		$pendingOne->selected_carrier = $SeletedSuccessCarrierType;
			
		//if status ='P', means try to do once more, so save current to 'F' and then create a new one
		$thisRetry = false;
		if ($pendingOne->status == "P"){
				
			$origData = $pendingOne->getAttributes();
			unset($origData['id']);
			/* 不要重试了，如果17trck 偶然不行，我们决定重试，才 enable以下logic
			 $aNewPendingOne = new TrackerApiQueue();
			$aNewPendingOne->setAttributes($origData);
			$aNewPendingOne->create_time = date('Y-m-d H:i:s');
			$aNewPendingOne->priority = ($aNewPendingOne->priority < 2 ? $aNewPendingOne->priority : 2);
			$aNewPendingOne->candidate_carriers = '';
			$addiNew = array();
			$addiNew['tried']=$candidate_tried;
			$aNewPendingOne->addinfo = json_encode($addiNew); //tell the 接力者，有一些carrier尝试过了
			$aNewPendingOne->save(false);
			*/
			$thisRetry = true;
			$pendingOne->status = 'F';
			echo "main Status got Fail due to reason a.1.\n";
			 
		}
		//echo "ys4: ".$pendingOne->status."\n";
		//记录下来如果failed的话，原因是啥
		if ($pendingOne->status == "F"){
			$addi = json_decode( $pendingOne->addinfo , true);
			$addi['message'] = $errorMsg;
			$pendingOne->addinfo = json_encode($addi);
			 
		}
		//echo "ys5: ".$pendingOne->status."\n";
		//save it to redis
		//echo "Ready to commit to redis for ".$pendingOne->track_no." ".$pendingOne->status ." $SeletedSuccessCarrierType \n";
		if ( self::putPendingMainQTaskToRedis($pendingOne->getAttributes())  ){//save successfull  //$pendingOne->save(false)
			//如果此次$pendingOne->status = "F"，也就是不成功，先update为查询失败
			if ($pendingOne->status == "F" ){
			 
				$message = self::commitTrackingResultUsingValue(array('track_no'=>$pendingOne->track_no,'status'=>'no_info'),$pendingOne->puid);
					
				 
			}
		
			if ($WriteLog) \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue Handler Monitor 12 job:".$CACHE['JOBID'] ],"edb\global");
			//如果这个物流号原来是 查询中 状态，
			//或者原来是 noinfo，suspend，但是现在不是了，那么就是第一次得到结果，把结果写到统计里面，重做的就不统计了
			if ($TrackingOrigState == Tracking::getSysState("初始")
			or ($TrackingOrigStatus=='no_info' or $TrackingOrigStatus=='suspend')
			and  $pendingOne->status == "C" ){
				$thisDate = date('Y-m-d');
				$days_gap = -1;
				if (!empty($aTracking->ship_out_date)){
					$d1=strtotime($thisDate);
					$d2=strtotime($aTracking->ship_out_date);
					$days_gap = round(($d1-$d2)/3600/24);
				}
					
				//	更新统计，判断该用户在近1日，3日，5日 内的tracking 跟踪成功或者失败次数。防止恶意用户玩野
				$track_success_distribute_str = TrackingHelper::getTrackerTempDataFromRedis("success_distribute");
				$track_success_distribute = json_decode($track_success_distribute_str,true);
				if (empty($track_success_distribute)) $track_success_distribute = array();
				/*Sample Data: $track_success_distribute =
				 * 	array('2015-03-10 success'=>50, '2015-03-10 success'=>23, '2015-03-10 ok percent'=50,
				 		* '2015-03-10 fail'=>10, '2015-03-11 fail'=>33, '2015-03-11 ok percent'=50, ) */
		
				if (!isset($track_success_distribute["$thisDate success"]))
					$track_success_distribute["$thisDate success"]  = 0;
		
				if (!isset($track_success_distribute["$thisDate fail"]))
					$track_success_distribute["$thisDate fail"]  = 0;
		
				if ($pendingOne->status == "F"){
					$track_success_distribute["$thisDate fail"] ++;
				}
		
				if ($pendingOne->status == "C"){
					$track_success_distribute["$thisDate success"] ++;
				}
		
				if ($track_success_distribute["$thisDate success"] + $track_success_distribute["$thisDate success"] > 0)
					$track_success_distribute["$thisDate ok percent"] = 100 *  $track_success_distribute["$thisDate success"] / ($track_success_distribute["$thisDate success"] + $track_success_distribute["$thisDate fail"] );
					
				TrackingHelper::setTrackerTempDataToRedis("success_distribute",json_encode($track_success_distribute));
		
				//更新统计，对这个 带有字母的pattern，统计当天的，订单日期相隔x天的物流号 的成功数量 和失败数量。
				if ($days_gap >= 0){
					$format_print_letr = CarrierTypeOfTrackNumber::getCodeFormatOfString($pendingOne->track_no,true );
					$track_letr_pattern_daysgap_str = TrackingHelper::getTrackerTempDataFromRedis("letr_pattern_daysgap_$thisDate");
					$track_letr_pattern_daysgap = json_decode($track_letr_pattern_daysgap_str,true);
					if (empty($track_letr_pattern_daysgap)) $track_letr_pattern_daysgap = array();
					/*Sample Data: $track_letr_pattern_daysgap =
					 * 	array('2015-03-10 success'=>50, '2015-03-10 success'=>23, '2015-03-10 ok percent'=50,
					 		* '2015-03-10 fail'=>10, '2015-03-11 fail'=>33, '2015-03-11 ok percent'=50, ) */
						
					if (!isset($track_letr_pattern_daysgap["$format_print_letr $days_gap success"]))
						$track_letr_pattern_daysgap["$format_print_letr $days_gap success"]  = 0;
		
					if (!isset($track_letr_pattern_daysgap["$format_print_letr $days_gap fail"]))
						$track_letr_pattern_daysgap["$format_print_letr $days_gap fail"]  = 0;
		
					if ($pendingOne->status == "C"){
						$track_letr_pattern_daysgap["$format_print_letr $days_gap success"] ++;		
					}
					
					if ($pendingOne->status == "F"){						
						$track_letr_pattern_daysgap["$format_print_letr $days_gap fail"] ++;
					}
					
					TrackingHelper::setTrackerTempDataToRedis("letr_pattern_daysgap_$thisDate",json_encode($track_letr_pattern_daysgap));
		
				}//end of days gap >= 0
			}//end of when 本次是该物流号的第一次查询
		
			//ys0917 同时维护这个puid的使用4px成功与否状况
			//ys0917 如果他过往 4 天前订单，有10% 几率 4px 成功的，或者尝试数量还没有8次，就加入4px作为candidate
			//echo " haha:".empty($CACHE['puid_use_4px'][''.$pendingOne->puid] )."-".isset($candidate_tried['a999000002'])." - $days_gap " ;
			if (isset($candidate_tried['a999000002']) and $days_gap >= 4){ //如果缓存没有记录，自己load出来计算一下
				$px4_daysgap_str = TrackingHelper::getTrackerTempDataFromRedis("4px_$thisDate");
				if (!empty($px4_daysgap_str))
					$px4_daysgap = json_decode($px4_daysgap_str,true);
		
				if (empty($px4_daysgap['success']))
					$px4_daysgap['success'] = 0;
		
				if (empty($px4_daysgap['totalCount']))
					$px4_daysgap['totalCount'] = 0;
		
				$px4_daysgap['totalCount'] ++;
				if ($pendingOne->status == "C" and $pendingOne->selected_carrier == "999000002"){
					$px4_daysgap['success'] ++;
				}
		
				if ($px4_daysgap['totalCount']>=8 and $px4_daysgap['success']/$px4_daysgap['totalCount'] >= 0.1 ){
					$CACHE['puid_use_4px'][''.$pendingOne->puid] = true; //cache结果，同一个进程无需再统计啦
				}
				TrackingHelper::setTrackerTempDataToRedis("4px_$thisDate",json_encode($px4_daysgap));
			}
		
		}//end of saved
		
		if ($WriteLog)
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"MainQueue 20 Enter:".$CACHE['JOBID'] ." 完成了一个pendign task ".$pendingOne->track_no ],"edb\global");
		
		$current_time=explode(" ",microtime()); $start7_time=round($current_time[0]*1000+$current_time[1]*1000);
		if (self::$TRACKER_FILE_LOG)
			\Yii::info("=====multiple_process_main step7 saveok mainjobid=$JOBID,t7_t1=".($start7_time-$start1_time));
		
		TrackingAgentHelper::extCallSum("Trk.MainQQuery",$start7_time-$start1_time);
		return $rtn;
	}

	static function waitForSubQueueResultComesUp($Parameters_list){
		global $CACHE ;
		foreach ( $Parameters_list as $key => $value )
		//if (! isset ( ${$key} ))
			${$key} = $value;
		
		if (isset($globalCache))
			$CACHE = $globalCache;
		
		$rtn = [];
		$loopedOnce = false;
		
		if (empty($enterLoopTime))
			$enterLoopTime = date('Y-m-d H:i:s'); 
		
		//echo " candidate_tried = ".count($candidate_tried)." \n";
		
		if (count($candidate_tried) > 0){
			do{
				$sleep_time = 1;
				//sleep($sleep_time); //wait for 3 seconds each loop, max time out 60 seconds
				
				if ($loopedOnce){
					$rtn['needNextFunction'] = true;
					$rtn['needLoopAgain'] = true;
					$globalCache=$CACHE;
					$rtn['snapshot_vars'] = get_defined_vars();
					//echo "YS22A needLoopAgain = ".$rtn['needLoopAgain']."\n";
					return $rtn;
				}
				
				$elapsedTimeSeconds += $sleep_time;
				$TrackerApiSubQueuesDone = TrackerApiSubQueue::find()
				->andWhere("main_queue_id=:main_queue_id and sub_queue_status='C'",array(':main_queue_id'=>$pendingOne->id) )
				->all();
					
				//echo " main_queue_id = ".$pendingOne->id ." \n";
				foreach ($TrackerApiSubQueuesDone as $aSubQueueDone){
					if (isset($parsed17TrackResultForSubqueue[$aSubQueueDone->carrier_type]))
						continue;

					if ($aSubQueueDone->carrier_type=='888000001'){ //UBI
						$parse17TrackResult = TrackingAgentHelper::parseUbiResult($aSubQueueDone->result,$aSubQueueDone->track_no );
					}
					elseif ($aSubQueueDone->carrier_type=='888000002' or $aSubQueueDone->carrier_type=='999000007' or $aSubQueueDone->carrier_type=='999000008'  or $aSubQueueDone->carrier_type=='999000010'){ //equick
						$parse17TrackResult = TrackingAgentHelper::parseEquickResult($aSubQueueDone->result,$aSubQueueDone->track_no );
					}
					elseif ( self::isThisCarrierUseInternalApi($aSubQueueDone->carrier_type) ){ //4px / CNE html
						$parse17TrackResult = TrackingAgentHelper::parse4PXResult($aSubQueueDone->result,$aSubQueueDone->track_no );
						//echo "Parsed 4px or CNE result1:".print_r($parse17TrackResult,true)."\n";
						
					}else{
						//$parse17TrackResult = TrackingAgentHelper::parse17TrackResult($aSubQueueDone->result,$aSubQueueDone->track_no );
						//get the redis parsed result, the subqueue already put there.
						try{
							$RedisResult = self::RedisGet("Tracker_AppTempData","ResultFor_".$aSubQueueDone->track_no ."_".$aSubQueueDone->carrier_type );
							
							//if (!empty($RedisResult)){
							//	$RedisResult = base64_decode($RedisResult);
						//	}
							
							if ($aSubQueueDone->track_no <> 'RS370933807CN')
							RedisHelper::RedisExe('hdel', array("Tracker_AppTempData", "ResultFor_" . $aSubQueueDone->track_no . "_". $aSubQueueDone->carrier_type) );
							
							$parse17TrackResult = json_decode($RedisResult,true);
 
						}catch (\Exception $e) {
							$RedisResult='';
							$parse17TrackResult['parsedResult'] = array();
							$parse17TrackResult['success'] = false;
						}
							
						if (!isset($parse17TrackResult['parsedResult'])){
							$parse17TrackResult['parsedResult'] = array();
							$parse17TrackResult['success'] = false;
						}else{
							//$parse17TrackResult['parsedResult'] = json_decode($RedisResult,true);
							//$parse17TrackResult['success'] = true;
						}
					}
						
					//echo "Got parsed rsult ".print_r($parse17TrackResult,true)."\n";//ystest
													  
					$thisResult = $parse17TrackResult['parsedResult'];
					//echo "Parsed 4px or CNE result2:".print_r($thisResult,true)."\n";
					//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$aSubQueueDone->track_no ." Got result:".print_r($thisResult,true)],"edb\global");
					//when 17Track return code is not 1, the 'success' = fasel
					//ret: (1)->查询成功, (-3)->系统更新, (-4)->客户端没有授权, (-5)->客户端IP被禁止, (-7)->没有提交单号
					if (isset($parse17TrackResult['success']) and $parse17TrackResult['success'] and
					!empty($thisResult['first_event_date'] ) ){
						//如果已经选择了的carrier是0（全球邮政的结果），那么不要使用其他的结果了，尽管其他结果日期更加早，因为可能是错的
						//或者 如果 $track_no_like_global_post 是true，并且 有 结果的carrier type = 0 （全球邮政的结果），那么就用全球邮政吧
							
						//如果已经用了4px结果，不要理会其他的
						//如果已经用了CNE结果，不要理会其他的
						//如果已经用了万邑通结果，不要理会其他的
						if (  !self::isThisCarrierUseInternalApi($SeletedSuccessCarrierType)
					           and ($earliestEventOfResult < $thisResult['first_event_date'] and $SeletedSuccessCarrierType <> 0								
					           	or $aSubQueueDone->carrier_type == 0 and $track_no_like_global_post
					           	or self::isThisCarrierUseInternalApi($aSubQueueDone->carrier_type)	
								  )  ){//如果4px有结果，优先用4px的
							$earliestEventOfResult = $thisResult['first_event_date'];
							$SeletedSuccessCarrierType = $aSubQueueDone->carrier_type;
							$thisCarrierTypeSuccess = 'Success';
						}
						
					}else
						$thisCarrierTypeSuccess = 'Fail';
		
					//write reason why this is failed if the tracking no is target
					//write to subQueue addi info
					if ($aSubQueueDone->track_no =="RS370933807CN"){
						$ad1 = json_decode($aSubQueueDone->addinfo,true);
						$var = array();
						$var['aaa1']=isset($parse17TrackResult['success']);
						
						if (isset($parse17TrackResult['success']))
							$var['$parse17TrackResult-success']=$parse17TrackResult['success'];
						
						
						$var['$thisCarrierTypeSuccess']=$thisCarrierTypeSuccess;
						$var['isThisCarrierUseInternalApi 1']=self::isThisCarrierUseInternalApi($SeletedSuccessCarrierType);
						$var['$earliestEventOfResult']=$earliestEventOfResult;
						$var['$thisResult$first_event_date']=isset($thisResult['first_event_date'])?$thisResult['first_event_date']:"isEmpty";
						$var['$SeletedSuccessCarrierType']=$SeletedSuccessCarrierType;
						$var['$aSubQueueDone->carrier_type']=$aSubQueueDone->carrier_type;
						$var['$track_no_like_global_post']=$track_no_like_global_post;
						$var['isThisCarrierUseInternalApi 2']=self::isThisCarrierUseInternalApi($aSubQueueDone->carrier_type);
						
						$var['$RedisResult ']=$RedisResult;
						$var['$parse17TrackResult ']=$parse17TrackResult;
						
						$ad1['vars'] = $var;
						$aSubQueueDone->addinfo = json_encode($ad1);
						$aSubQueueDone->save(false);
					}
					
					
					$subQueueRecord[$aSubQueueDone->carrier_type] = $aSubQueueDone;
					$parsed17TrackResultForSubqueue[$aSubQueueDone->carrier_type] = $thisResult;
		
					//记录统计这个Carrier Type for 这种track format成功与否
					//Load 该客户今天的carrier type 成功记录
					$forDate = date('Y-m-d');
					$format_print = CarrierTypeOfTrackNumber::getCodeFormatOfString($aSubQueueDone->track_no,true );
					$carrier_success_rate = TrackingHelper::getTrackerTempDataFromRedis("carrier_success_rate_$forDate");
					$carrier_success_rate = json_decode($carrier_success_rate,true);
					/*Sample Data: $carrier_success_rate = array('RG******CN'=>array('0'=>array('Success'=>10,'Fail'=>5))) */
					if (empty($carrier_success_rate)) $carrier_success_rate = array();
		
					if (!isset($carrier_success_rate[$format_print][$aSubQueueDone->carrier_type][$thisCarrierTypeSuccess]))
						$carrier_success_rate[$format_print][$aSubQueueDone->carrier_type][$thisCarrierTypeSuccess] = 0;
		
					$carrier_success_rate[$format_print][$aSubQueueDone->carrier_type][$thisCarrierTypeSuccess] ++;
		
					TrackingHelper::setTrackerTempDataToRedis("carrier_success_rate_$forDate", json_encode($carrier_success_rate));
		
				}//end of each SubQueueDone
					
				$candidateDoneCount = count($TrackerApiSubQueuesDone);
					
				//如果以上已完成的 sub queue数量不等于tried 数量，就是还有一些是pending 或者 S
				if ($candidateDoneCount < count($candidate_tried) )
					$candidateDoneCount = TrackerApiSubQueue::find()->andWhere("main_queue_id=:main_queue_id and sub_queue_status in ('C','F')",array(':main_queue_id'=>$pendingOne->id) )->count();
					
				$loopedOnce = true;
				
			/*	 
				 echo "Checked SubQueue done,  candidateDoneCount=$candidateDoneCount \n
				count( candidate_tried) = ".count($candidate_tried)." \n
				enterLoopTime	=$enterLoopTime ,  SeletedSuccessCarrierType = $SeletedSuccessCarrierType \n
				8 minutes ago is ".date('Y-m-d H:i:s',strtotime('-8 minutes'))."\n";
			 */

			}while( $candidateDoneCount < count($candidate_tried)  and 
					$enterLoopTime >  date('Y-m-d H:i:s',strtotime('-8 minutes')) 
					and !self::isThisCarrierUseInternalApi($SeletedSuccessCarrierType)
					);
		}//end if candidate tried > 0
		

		$rtn['needNextFunction'] = true;
		$rtn['needLoopAgain'] = false;
		$globalCache=$CACHE;
		if (!isset($parsed17TrackResultForSubqueue))
			$parsed17TrackResultForSubqueue=[];
		
		//echo "Got Step 2 parsed result $SeletedSuccessCarrierType :".print_r($parsed17TrackResultForSubqueue,true)."\n";
		$rtn['snapshot_vars'] = get_defined_vars();
		//echo "YS22 needLoopAgain = ".$rtn['needLoopAgain']."\n";
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 针对某个Carrier Type，发送17Track请求，尝试获得该Tracking number的追中返回
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  sub_id1          subid specified
	 * @param  scope            NOT17TRACK / 17TRACK , if 17TRACK, it will do bulk query
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::subqueueHandlerForTrackingByCarrier()
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/2        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function subqHandlerByCarrierNon17Track($sub_id1='' ){
		//this is a CONST for the proxy server, linking 17Track
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
	
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 0 ".$JOBID],"file");
		$current_time=explode(" ",microtime());		$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
		if (self::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub step1 subjobid=$JOBID");
	
		//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$currentSubQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
		if (empty($currentSubQueueVersion))
			$currentSubQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$subQueueVersion))
			self::$subQueueVersion = $currentSubQueueVersion;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$subQueueVersion <> $currentSubQueueVersion){
			TrackingAgentHelper::extCallSum( );
			TrackingAgentHelper::markJobUpDown("Trk.SubQHdlDown",$CACHE['jobStartTime']);
			DashBoardHelper::WatchMeDown();
			exit("Version new $currentSubQueueVersion , this job ver ".self::$subQueueVersion." exits for using new version $currentSubQueueVersion.");
		}
	
		//step 1, try to get a pending request in queue, according to priority
		$pendingSubOne = TrackerApiSubQueue::find()
		->andWhere( ($sub_id1=='')?"sub_queue_status='P' ":" sub_id= $sub_id1 " )
		->andWhere( " carrier_type in ('888000001','888000002','999000002','999000003','999000004','999000005','999000006','999000007','999000008','999000009','999000010') " )
		->one();
	
		$CACHE['subOne'] = $pendingSubOne; //放到global cache，其他function可以直接拿来用
		//if no pending one found, return true, message = 'n/a';
		if (empty($pendingSubOne)){
			$rtn['message'] = "n/a";
			$rtn['success'] = true;
				
			$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
			if (self::$TRACKER_FILE_LOG)
				\Yii::info("multiple_process_sub get-no-P sleep4 subjobid=$JOBID,t2_t1=".($start2_time-$start1_time));
	
			return $rtn;
		}
		//	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 1 ".$JOBID],"file");
		$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
		if (self::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub step2 subjobid=$JOBID,t2_t1=".($start2_time-$start1_time));
	
	
		if  ($pendingSubOne){
			$original_status = $pendingSubOne->sub_queue_status;
			$connection = Yii::$app->db;
				
			//step 2, 尝试Mark这个record由本进程来做，其他人就不要处理这条记录啦
	
			$command = Yii::$app->db_queue->createCommand("update tracker_api_sub_queue set sub_queue_status='S',update_time='$now_str'
					where sub_id=:sub_id and sub_queue_status in ('P','R') "  );
					// Bind the parameter
					$command->bindValue(':sub_id', $pendingSubOne->sub_id, \PDO::PARAM_STR);
					$affectRows = $command->execute();
	
					if ($affectRows == 0 and $sub_id1==''){
					$message = "进程处理同一个SubQueue请求冲突，本进程退出";
					//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message.$JOBID],"edb\global");
					$rtn['message'] .= TranslateHelper::t($message);
					$rtn['success'] = false;
	
					$current_time=explode(" ",microtime()); $start3_time=round($current_time[0]*1000+$current_time[1]*1000);
					if (self::$TRACKER_FILE_LOG)
				\Yii::info("multiple_process_sub conflict subjobid=$JOBID,t3_t2=".($start3_time-$start2_time));
					
				return $rtn;
					}//end if updated the task status successfully
					else{//防止YII model出错，重新Load一次
					$pendingSubOne = TrackerApiSubQueue::find()
					->andWhere("sub_id=".$pendingSubOne->sub_id )
					->one();
					}
						
					$trackingNo = $pendingSubOne->track_no;
					$current_time=explode(" ",microtime()); $start3_time=round($current_time[0]*1000+$current_time[1]*1000);
					if (self::$TRACKER_FILE_LOG)
						\Yii::info("multiple_process_sub step2-1 subjobid=$JOBID,track_no=$trackingNo,t3_t2=".($start3_time-$start2_time));
						 
			   if ($pendingSubOne->carrier_type=='888000001'){ //UBI
					$query_result = TrackingAgentHelper::queryUbiInterface($pendingSubOne );
					}elseif ($pendingSubOne->carrier_type=='888000002'){ //equick
					$query_result = TrackingAgentHelper::queryEquickProxy($pendingSubOne );
					}elseif ($pendingSubOne->carrier_type=='999000002'){ //4pxHtml
					$query_result = TrackingAgentHelper::query4PXAPI( $pendingSubOne->track_no );
					}elseif ($pendingSubOne->carrier_type=='999000003'){ //4pxHtml
					$query_result = TrackingAgentHelper::queryCNEAPI( $pendingSubOne->track_no );
					}elseif ($pendingSubOne->carrier_type=='999000004'){ //4pxHtml
					$query_result = TrackingAgentHelper::queryWinitAPI( $pendingSubOne->track_no );
					}elseif ($pendingSubOne->carrier_type=='999000009'){ //4pxHtml
					$query_result = TrackingAgentHelper::query139API( $pendingSubOne->track_no );
					}elseif ($pendingSubOne->carrier_type=='999000005'){ //4pxHtml
						$addinfo_str = $pendingSubOne->addinfo;
						if (!empty($addinfo_str))
							$addinfo = json_decode($addinfo_str,true);
						else
							$addinfo = array();
						
					$query_result = TrackingAgentHelper::querySFAPI( empty($addinfo['return_no']) ? $pendingSubOne->track_no : $addinfo['return_no'] );
					}elseif ($pendingSubOne->carrier_type=='999000006'){ //4pxHtml
					$query_result = TrackingAgentHelper::queryYUNTUAPI( $pendingSubOne->track_no );
					}elseif ($pendingSubOne->carrier_type=='999000007'){ //BRT Html
					$query_result = TrackingAgentHelper::queryBRTProxy($pendingSubOne );
					}elseif ($pendingSubOne->carrier_type=='999000008'){ //BRT Html
					$query_result = TrackingAgentHelper::queryColissimoProxy($pendingSubOne );
					}elseif ($pendingSubOne->carrier_type=='999000010'){ //BRT Html
					$query_result = TrackingAgentHelper::queryPonyProxy($pendingSubOne );
					}
	
			   $current_time=explode(" ",microtime()); $start4_time=round($current_time[0]*1000+$current_time[1]*1000);
			   if (self::$TRACKER_FILE_LOG)
			   	\Yii::info("multiple_process_sub step3 subjobid=$JOBID,track_no=$trackingNo,t4_t3=".($start4_time-$start3_time));
	
			   			$pendingSubOne->sub_queue_status = $query_result['sub_queue_status'];
			   			$pendingSubOne->result = $query_result['result'];
			   			$pendingSubOne->run_time = $query_result['run_time'];
			   			 
			   			 		if (!empty($query_result['proxy_call'])){
			   			 		$addinfo_str = $pendingSubOne->addinfo;
			   			 		if (!empty($addinfo_str))
			   			 		$addinfo = json_decode($addinfo_str,true);
			   			 		else
			   			 			$addinfo = array();
	
			   			 			$addinfo['proxy_call'] = $query_result['proxy_call'];
			   			 			$pendingSubOne->addinfo = json_encode($addinfo);
			   			 		}
	
			   			 		 
			   			 		//update the info of this Sub Queue task
			   			 		$pendingSubOne->update_time = date('Y-m-d H:i:s');
			   			 		if ( $pendingSubOne->save(false) ){//save successfull
			   			 		// \Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 4 ".$JOBID],"file");
			   			 		$current_time=explode(" ",microtime()); $start5_time=round($current_time[0]*1000+$current_time[1]*1000);
			   			 		if (self::$TRACKER_FILE_LOG)
			   			 			\Yii::info("multiple_process_sub step4 saveok subjobid=$JOBID,track_no=$trackingNo,t5_t1=".($start5_time-$start1_time).",pt=".($start5_time-$getp_time));
	
	}else{
		$message = "ETRK015：保存SubQueue队列中API请求的执行结果，出现错误.";
		\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"file");
		$rtn['message'] .= TranslateHelper::t($message);
		$rtn['success'] = false;
			
		foreach ($pendingSubOne->errors as $k => $anError){
		$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
		}//end of each error
		//				\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 5 ".$JOBID],"file");
		$current_time=explode(" ",microtime()); $start5_time=round($current_time[0]*1000+$current_time[1]*1000);
		if (self::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub step4 savefalse subjobid=$JOBID,track_no=$trackingNo,t5_t1=".($start5_time-$start1_time).",pt=".($start5_time-$getp_time));
	
			return $rtn;
	}//end of save failed
	
	}//end of found one pending task in Sub Queue
	//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 6 ".$JOBID],"file");
	$current_time=explode(" ",microtime()); $start5_time=round($current_time[0]*1000+$current_time[1]*1000);
	if (self::$TRACKER_FILE_LOG)
		\Yii::info("====multiple_process_sub step4 return subjobid=$JOBID,track_no=$trackingNo,t5_t1=".($start5_time-$start1_time));
	
		return $rtn;
	}
	
	static public function subqHandlerByCarrier17Track($sub_id1='' ){
	//this is a CONST for the proxy server, linking 17Track
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
	
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 0 ".$JOBID],"file");
		$current_time=explode(" ",microtime());		$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
		if (self::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub step1 subjobid=$JOBID");
	
			//Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
			$currentSubQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
			if (empty($currentSubQueueVersion))
			$currentSubQueueVersion = 0;
	
				//如果自己还没有定义，去使用global config来初始化自己
				if (empty(self::$subQueueVersion))
			self::$subQueueVersion = $currentSubQueueVersion;
				
			//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
			if (self::$subQueueVersion <> $currentSubQueueVersion){
			TrackingAgentHelper::extCallSum( );
			TrackingAgentHelper::markJobUpDown("Trk.SubQHdlDown",$CACHE['jobStartTime']);
			DashBoardHelper::WatchMeDown();
			exit("Version new $currentSubQueueVersion , this job ver ".self::$subQueueVersion." exits for using new version $currentSubQueueVersion.");
			}
	
			//step 1, try to get a pending request in queue, according to priority
			$pendingSubOne_array = TrackerApiSubQueue::find()
			->andWhere( ($sub_id1=='')?"sub_queue_status='P' ":" sub_id= $sub_id1 " )
			->andWhere( " carrier_type not in ('888000001','888000002','999000002','999000003','999000004','999000005','999000006','999000007','999000008','999000009','999000010') " )
				->limit(40)
			->all();
	
		//if no pending one found, return true, message = 'n/a';
			if (empty($pendingSubOne_array)){
			$rtn['message'] = "n/a";
			$rtn['success'] = true;
	
			$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
			if (self::$TRACKER_FILE_LOG)
				\Yii::info("multiple_process_sub get-no-P sleep4 subjobid=$JOBID,t2_t1=".($start2_time-$start1_time));
	
						return $rtn;
			}
	
			$current_time=explode(" ",microtime()); $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
			if (self::$TRACKER_FILE_LOG)
				\Yii::info("multiple_process_sub step2 subjobid=$JOBID,t2_t1=".($start2_time-$start1_time));
	
			$original_status_array = [];
			$sub_id_array =[];
			//因为17Trk 有bug，如果提交的多个里面，含有相同的 track no，尽管不同的fc sc，17也会死掉，所以这里不要重复做一个track no
			$worksForTrackNo = [];
			$willDoSubTask = [];
			foreach ($pendingSubOne_array as $pendingSubOne) {
				if (isset($worksForTrackNo[$pendingSubOne->track_no ]))
					continue;
				
				$worksForTrackNo[ $pendingSubOne->track_no ] = 1;
				
				$original_status_array[$pendingSubOne->track_no] = $pendingSubOne->sub_queue_status;
				$sub_id_array[] = $pendingSubOne->sub_id;
				$willDoSubTask [] = $pendingSubOne;
			}
			
			$pendingSubOne_array = $willDoSubTask;
	
			//step 1.2, 尝试Mark这个record由本进程来做，其他人就不要处理这条记录啦
			//mark the 40 物流号status = S
			$command = Yii::$app->db_queue->createCommand("update tracker_api_sub_queue set sub_queue_status='S',update_time='$now_str'
			where sub_id in (".implode(",", $sub_id_array).") and sub_queue_status in ('P','R') "  );
					// Bind the parameter
				$affectRows = $command->execute();
	//echo "Process SubQ task ".implode(",", $sub_id_array)."\n";
					$query_result = TrackingAgentHelper::query17TrackProxy($pendingSubOne_array, $original_status_array);
					/*$query_result 里面包含了多大40个result，e.g.：
					['results_for_track_no'][$trackingNo]   (string)
					['sub_queue_status'][$trackingNo]    (string)
					['fc'][$trackingNo]
					['yt'][$trackingNo]
					['run_time']
					['proxy_call']
					*/
	
					foreach   ($pendingSubOne_array as &$pendingSubOne){
						//step 2, 尝试Mark这个record由本进程来做，其他人就不要处理这条记录啦
						$trackingNo = $pendingSubOne->track_no;
						$current_time=explode(" ",microtime()); $start3_time=round($current_time[0]*1000+$current_time[1]*1000);
						if (self::$TRACKER_FILE_LOG)
							\Yii::info("multiple_process_sub step2-1 subjobid=$JOBID,track_no=$trackingNo,t3_t2=".($start3_time-$start2_time));
	
					 	if (empty($query_result['results_for_track_no'][$trackingNo]))
						$query_result['results_for_track_no'][$trackingNo]='n/a';
						 
						$pendingSubOne->sub_queue_status = $query_result['sub_queue_status'][$trackingNo]  ;
						$pendingSubOne->result = $query_result['results_for_track_no'][$trackingNo];
						$pendingSubOne->run_time = $query_result['run_time'];
	
						$addinfo_str = $pendingSubOne->addinfo;
						if (!empty($addinfo_str))
							$addinfo = json_decode($addinfo_str,true);
						else
							$addinfo = array();
								
						//echo "Check for result yt:". print_r($query_result['yt'][$trackingNo],true)."\n"; //ystest
						
						if (!empty($query_result['proxy_call']) or !empty($query_result['yt'][$trackingNo]) ){
				
								if (!empty($query_result['proxy_call']))
								$addinfo['proxy_call'] = $query_result['proxy_call'];
								 
								if (!empty($query_result['yt'][$trackingNo]))
										$addinfo['yt'] = $query_result['yt'][$trackingNo];
							}
					
				if (empty($query_result['fc'][$trackingNo]))
					$sc = 0;
				else
					$sc = $query_result['fc'][$trackingNo];
					
				$addinfo['tried_yt']['tried_'.$sc ] = 1;
				$pendingSubOne->addinfo = json_encode($addinfo);
					
						//判断是否 yt 有其他可能性，如果有那么如果fail 就继续逐个发信,如果都已经试过了，那好吧就F吧
 
						if ($pendingSubOne->sub_queue_status <>'C' and isset($addinfo['yt'])){
							if (isset($addinfo['yt']['d'])){
								$sc = 0;
								$tried_yt = (isset($addinfo['tried_yt'])?$addinfo['tried_yt'] : []);
								foreach ($addinfo['yt']['d'] as $aYtCode){
									if (!isset($tried_yt[ 'tried_'.$aYtCode ]))
										$sc = $aYtCode;
								}//end of each ytCode									
								 
								//如果还有sc没有尝试的，试试这个sc把
								if ($sc <> 0){
									//设置为P就可以，下次做他自动回找 yt - yt tried 来做sc的
									$pendingSubOne->sub_queue_status = 'P';
								}
								
								
							}
						}
						
						//update the info of this Sub Queue task
							$pendingSubOne->update_time = date('Y-m-d H:i:s');
							
						//step final: save the sub Queue result to db
						$sql = "update tracker_api_sub_queue set sub_queue_status='".$pendingSubOne->sub_queue_status."' , 
								update_time='".$pendingSubOne->update_time."', addinfo=:addi_info
								where sub_id = ".$pendingSubOne->sub_id;
						
						$command = Yii::$app->db_queue->createCommand($sql );
						$command->bindValue(':addi_info', $pendingSubOne->addinfo , \PDO::PARAM_STR);
						$command->execute();
	
							}//end of found one pending task in Sub Queue
							//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ 6 ".$JOBID],"file");
							//echo "Subqueue finished , exit \n";
							
							$current_time=explode(" ",microtime()); $start5_time=round($current_time[0]*1000+$current_time[1]*1000);
							if (self::$TRACKER_FILE_LOG)
								\Yii::info("====multiple_process_sub step4 return subjobid=$JOBID,track_no=$trackingNo,t5_t1=".($start5_time-$start1_time));
	
						return $rtn;
	}
	
	
	/**
	+---------------------------------------------------------------------------------------------
	* 添加一个Tracking task 进入 Sub Queue，为了指定某个tracking number 使用某个 carrier type
	* 一般由Main Queue 处理器触发。
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  mainQueueInfo        array('track_no'=>'RG6546546CN','puid'=>1,...)
	 * @param  $carrier_type        0:国际邮政，(100001)->DHL, (100002)->UPS, (100003)->Fedex, (100004)->TNT,
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::insertOneSubQueue();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	 static public function insertOneSubQueue($mainQueueInfo, $carrier_type='0',$trackingStatus='',$nationCode='',$ship_by='',$foreign_track_no_alias=''){ //ystest
	 $rtn['message'] = "";
	 $rtn['success'] = true;
		//echo "try to insert subQueue for $carrier_type \n";
	 if (empty($mainQueueInfo['id']) and empty($carrier_type)){
	 	$rtn['message'] = "Empty mainQueue Id and carrier type intpu";
	 	$rtn['success'] = false;
	 	return $rtn;
	 }
	 
	 //if this task already in Sub Queue, skip it
	 $aSubQueueModel = TrackerApiSubQueue::find()
	 ->andWhere("main_queue_id=".$mainQueueInfo['id']." and carrier_type=$carrier_type" )
	 ->one();
	
	 if ($aSubQueueModel <> null){
	 //step 1, check queue, if there is a such one processing but no respond for 5 minutes, update it to P
	 $five_minutes_ago = date('Y-m-d H:i:s',strtotime('-5 minutes'));
	 //check if it is with 5 minutes, if yes, do nothing, leave it processing
	 	if ($aSubQueueModel->update_time > $five_minutes_ago)
	 		return $rtn;
	 }else
	 	$aSubQueueModel = new TrackerApiSubQueue();
	
		$aSubQueueModel->setAttributes($mainQueueInfo);
	 $aSubQueueModel->main_queue_id = $mainQueueInfo['id'];
	 $aSubQueueModel->carrier_type = $carrier_type;
	 $aSubQueueModel->sub_queue_status='P';
	 $aSubQueueModel->create_time = date('Y-m-d H:i:s');
	 $mainAddinfo = json_decode($mainQueueInfo['addinfo'],true);
	 //ystest starts
	 if (isset($mainAddinfo['order_id']))
	 	$addiInfo['order_id'] = $mainAddinfo['order_id'];
	 
	 if (isset($mainAddinfo['set_carrier_type']))
	 	$addiInfo['set_carrier_type'] = $mainAddinfo['set_carrier_type'];

	 $addiInfo['tracking_status'] = $trackingStatus;
	 $addiInfo['nation_code'] = $nationCode;
	 $addiInfo['ship_by'] = $ship_by;
	 $addiInfo['return_no'] = $foreign_track_no_alias;
	 $aSubQueueModel->addinfo = json_encode($addiInfo);
	 //ystest ends
	
		if ( $aSubQueueModel->save() ){//save successfull
	
		}else{
			foreach ($aSubQueueModel->errors as $k => $anError){
			$rtn['message'] .= ($rtn['message']==""?"":"<br>"). $k.":".$anError[0];
			}//end of each error
	
			$rtn['message'] .= TranslateHelper::t($rtn['message']);
				$rtn['success'] = false;
	
				$message = "ETRK101：插入API SubQueue For 出现错误.".$rtn['message'];
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$rtn['message']],"edb\global");
	
				}//end of save failed
				return $rtn;
	}
	
	/*999000002,999000003,999000004,999000005,999000006 这种就是我们自己对接的国内物流商，不需要通过17track
	 *
	* */
	static public function isThisCarrierUseInternalApi($carrier_type){
		if (strlen(strval($carrier_type)) <> 9 )
			return false;
	
		return intval($carrier_type) >= 999000002;
	
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 把Tracker信息写入到用户Tracking信息。
	 * Tracker 队列处理器，使用subQueue并行获得多个carrier type的查询结果，决定使用某个来写入Tracking信息中
	 * 这个只是commit到Redis中，不是真的写数据库的
	 +---------------------------------------------------------------------------------------------
	
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  trackingValue           array 格式的Tracking Values
	 * @param  puid                    用户数据库编号
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					TrackingHelper::commitTrackingResultUsingValue();
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/2				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function commitTrackingResultUsingValue($trackingValue,$puid=0 ){
		$rtn['message'] = "";
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		global $CACHE;
		//Load tracking model from redis
		if (isset($CACHE['workingTrackingModelLink']))
			$useYiBu = true;
		else
			$useYiBu = false;
		
		$JOBID=isset($CACHE['JOBID']) ? $CACHE['JOBID'] : "";
		//step 1, switch db for puid, and load the tracking
		 
		
		$track_no = $trackingValue['track_no'];

		if ($useYiBu){
			$tempStr = self::RedisGet('TrackMainQ', $CACHE['workingTrackingModelLink'] );
			
			if (empty($CACHE['tempTrackingModel']))
				$CACHE['tempTrackingModel'] = new Tracking();
			
			$aTracking = $CACHE['tempTrackingModel'] ;

			$aTrackingRedisImage = json_decode($tempStr , true);
		
			if (empty($aTrackingRedisImage)){
				//		异常情况
				$rtn['message']="ETRK0012：这个物流号已经不存在了(redis can not found ".(empty($CACHE['workingTrackingModelLink'])?"":$CACHE['workingTrackingModelLink']).")，无法update其物流信息".$track_no;
				$rtn['success'] = false;
				return $rtn;
			}
		
			$aTracking->setAttributes($aTrackingRedisImage);
		}else {
			$aTracking = Tracking::find()
					->andWhere("track_no=:trackno",array(':trackno'=>$track_no) )
					->one();
			if (empty($aTracking)){
				//		异常情况
				$rtn['message']="ETRK0012：这个物流号已经不存在了(redis can not found tracking model )，无法update其物流信息".$track_no;
				$rtn['success'] = false;
				return $rtn;
			}
		}
		$orig_data = $aTracking->getAttributes();
		//echo "ys 4.1 got redis tracking data:".print_r($orig_data,true)."\n";
		//step 2, define which fields are to be updated.
		$updateFields = array('parcel_type'=>1,'status'=>1,'carrier_type'=>1,'from_nation'=>1,'to_nation'=>1,
				'all_event'=>1,'total_days'=>1,'first_event_date'=>1,'from_lang'=>1,'to_lang'=>1,'last_event_date'=>1,
		);
		$populateFieldandValues = array();
		foreach ($trackingValue as $k=>$v){
			if (isset($updateFields[$k]))
				$populateFieldandValues[$k] = $v;
		}
	
		//if can not load the tracking, error
		$aTracking->update_time = $now_str;
		$aTracking->setAttributes($populateFieldandValues);
		 
		//step 4, set state according to status and created time, long ago
		//如果已经查询到结果，看看是否手工指定的查询途径，如果是，记录到全局redis做mapping
		if ($aTracking->status <> Tracking::getSysStatus("查询不到")
			and $aTracking->status <> Tracking::getSysStatus("无法交运")){
			$addi = json_decode($aTracking->addi_info,true);
			if (!empty($addi['set_carrier_type'])){
				$mapping1=[];
				$mapping1[$aTracking->ship_by] =$addi['set_carrier_type'] ;
				TrackingHelper::addGlobalShipByAndCarrierTypeMappingToRedis($mapping1);
				TrackingHelper::addUserShipByAndCarrierTypeMappingToRedis($mapping1,$puid);
			}
		}

		//special handling for some cases
		//A. 如果Tracking 创建时间5天还没有 查询得到信息，判断为交运不成功
		$five_days_ago = date('Y-m-d H:i:s',strtotime('-5 days'));
		$fifteen_days_ago = date('Y-m-d H:i:s',strtotime('-15 days'));
		if ($aTracking->status == Tracking::getSysStatus("查询不到")
		and $aTracking->create_time < date('Y-m-d H:i:s',strtotime('-10 days'))){
			$aTracking->status = Tracking::getSysStatus("无法交运");
		}
		//B. 如果运输超过了15天了，还没有到，判断为 运输过久
		elseif ($aTracking->status == Tracking::getSysStatus("运输途中")
				and $aTracking->create_time < $fifteen_days_ago ){
			//$aTracking->status = Tracking::getSysStatus("ship_over_time");
		}
	
		//set default state by status
		$aTracking->state = Tracking::getParcelStateByStatus($aTracking->status);
	
		//如果本来就有结果，可是这次没有结果，就是17Track 返回有问题，不要把有问题的结果提交
		$canCommit=true;
		
		if ($aTracking->status <> Tracking::getSysStatus("买家已确认") and
		$aTracking->state <> Tracking::getSysState("已完成") and
		empty($populateFieldandValues['first_event_date'] ) and !empty($orig_data['first_event_date']) )
			$canCommit=false;
	
		//如果原来的是已完成的state，而新状态不是已完成的state，就不让update了
		if ($orig_data['state'] == Tracking::getSysState("已完成") and
			$aTracking->state <> Tracking::getSysState("已完成")
		)$canCommit=false;
		
		
		if ( $canCommit ){//save successfull
			$populateFieldandValues['update_time'] = $aTracking->update_time;
			$populateFieldandValues['status'] = $aTracking->status;
			$populateFieldandValues['state'] = $aTracking->state;
				
			//如果有last event date 并且还没有完成的，就计算器停留时间天数
			if (!empty($populateFieldandValues['last_event_date']) and
			( $aTracking->state ==Tracking::getSysState("正常")  or
					$aTracking->state ==Tracking::getSysState("异常")  )
			)  {
				$datetime1 = strtotime (date('Y-m-d H:i:s'));
				$datetime2 = strtotime (substr($populateFieldandValues['last_event_date'], 0,10)." 00:00:00");
				$days =ceil(($datetime1-$datetime2)/86400); //60s*60min*24h
				$populateFieldandValues['stay_days'] =  $days;
			}
				
			//如果是已完成的state，停留时间为 0 天就可以
			if ($aTracking->state ==Tracking::getSysState("已完成")){
				$populateFieldandValues['stay_days'] =  0;
			}
				
			$datetime1='';
			if ( !empty($aTracking->first_event_date) )
				$datetime1 = $aTracking->first_event_date;
	
			//如果已经完成并且 first day = last day，表示只有一个事件，这种，total days 要改成last day - 订单日期
			if ( empty($datetime1) and !empty($orig_data['addi_info']) ){
				$addi_info = json_decode($orig_data['addi_info'],true);
				if (!empty($addi_info['order_paid_time'])){
					$datetime1 = $addi_info['order_paid_time'];
				}
			}
	
			if (!empty($aTracking->last_event_date)){
				//date1 就是 first event date，如果没有，就是paid date
				$datetime1 = strtotime (substr($datetime1, 0,10)." 00:00:00");
				
				if ($aTracking->state ==Tracking::getSysState("已完成"))
					$datetime2 = strtotime (substr($aTracking->last_event_date, 0,10)." 00:00:00");
				else
					$datetime2 = strtotime (substr($now_str, 0,10)." 00:00:00");
				
				$days = abs(ceil(($datetime1-$datetime2)/86400)); //60s*60min*24h
				//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"total days ab: ".$aTracking->first_event_date." - $days"    ],"edb\global");
				$populateFieldandValues['total_days'] = $days;
			}
				
			$set_str='';
				
			//如果有 first event date，那么弄到 ship out date 里面去
			if (!empty($populateFieldandValues['first_event_date']))
				$populateFieldandValues['ship_out_date'] = $populateFieldandValues['first_event_date'];
			
			
			foreach ($populateFieldandValues as $key =>$val){
				$set_str .= ($set_str==''?"":",");
				$set_str .= "$key='$val'";
			}
				
			$trackingNo = $track_no;
		 
			
			if ($useYiBu){	
				$aTracking->setAttributes($aTrackingRedisImage);
				$aTracking->setAttributes($populateFieldandValues);
				$commitData =$aTracking->getAttributes();
				self::putTrackingToRedis($commitData);//it will auto touch the update time
			}else{
				$aTracking->setAttributes($populateFieldandValues);
				$commitData =$aTracking->getAttributes();
				$command = Yii::$app->subdb->createCommand("update lt_tracking set $set_str where track_no= '$track_no'");
				$command->execute();
			}
			//因为有新的tracking commit了，原来的统计cache就dirty了，设置为空，下次有人登陆会重新计算
			TrackingHelper::setTrackerTempDataToRedis("left_menu_statistics", json_encode(array()));
			TrackingHelper::setTrackerTempDataToRedis("top_menu_statistics", json_encode(array()));
	
			$rtn['message'] = $aTracking->status;
			$rtn['track_no'] = $aTracking->track_no;
			
			//如果可以commit，并且新的status 或者 last evnet date变化了，推送到oms去
			if (!empty($commitData['last_event_date']))
				$commitData['last_event_date'] = "";
			
			if (!empty($commitData['last_event_date']) and strlen($commitData['last_event_date']) > 10)
				$commitData['last_event_date'] = substr($commitData['last_event_date'],0,10);
			
			if ($orig_data['source']=='O' and !empty($orig_data['order_id']) and
				  ($orig_data['status'] <>$commitData['status']    ) ){
				
				$command_line = '\eagle\modules\tracking\helpers\TrackingHelper::pushToOMS( '. $puid .' , "'. $orig_data['order_id'].'","'.$commitData['status'].'","'.$commitData['last_event_date'].'")';
				AppPushDataHelper::insertOneRequest("Tracker", "OMS", $puid, $command_line);
			}
			
		} else{//就算canNot commit，也要update一下update time，否则会自动蛢命未他 刷新的
			
			//如果原来状态是 查询中，那么cannot commit 野把他弄成 无法查询吧。
			$commitData = $aTrackingRedisImage;
			$needUpdateStatus= false;
			if ($orig_data['status'] == Tracking::getSysStatus("查询等候中") ){
				$commitData['state'] = 'normal';
				$commitData['status'] = Tracking::getSysStatus("查询不到");
			}
			
			
			if ($useYiBu){
				self::putTrackingToRedis($commitData);//it will auto touch the update time
			}else{				
				$command = Yii::$app->subdb->createCommand("update lt_tracking set status='".$commitData['status']."', state='".$commitData['state']."', update_time='$now_str' where track_no= '$track_no'");
				$command->execute();
			}
		}
	
		//check all distinct delivery target nations  , write it to Config ys20151016
		if (!$useYiBu){
			$old_carriers = TrackingHelper::getTrackerTempDataFromRedis("to_nations" );
			$toNations = json_decode($old_carriers,true);
		
			if (!isset($toNations[ $aTracking->to_nation ])){
				$toNations[ $aTracking->to_nation ] =  $aTracking->to_nation;
				TrackingHelper::setTrackerTempDataToRedis("to_nations", json_encode($toNations));
			}
		
			$old_carriers = TrackingHelper::getTrackerTempDataFromRedis("using_carriers" ,$puid);
			
			if (empty($old_carriers)){
				//重新初始化一下
				$using_carriers = array();
				$allCarriers = Yii::$app->subdb->createCommand(//run_time = -10, 也就是估计可以通过smt api 搞定的，所以早点做掉
						"select distinct ship_by from lt_tracking   ")->queryAll();
				foreach ($allCarriers as $aCarrier){
					$using_carriers[ $aCarrier['ship_by'] ] = $aCarrier['ship_by'] ;
				}
				TrackingHelper::setTrackerTempDataToRedis("using_carriers", json_encode($using_carriers),$puid);
			}else{
				$using_carriers = json_decode($old_carriers,true);
				if (!isset($using_carriers[ $aTracking->ship_by ])){
					$using_carriers[ $aTracking->ship_by ] =  $aTracking->ship_by;
					TrackingHelper::setTrackerTempDataToRedis("using_carriers", json_encode($using_carriers),$puid);
				}
			}
		}
		
		return $rtn;
	}
	

	
}
