<?php
namespace eagle\modules\carrier\apihelpers;

use yii;
use common\helpers\Helper_Array;
use eagle\modules\carrier\models\SysCarrier;
use eagle\modules\carrier\models\SysShippingService;
use eagle\models\carrier\SysCarrierAccount;
use eagle\modules\carrier\models\MatchingRule;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\catalog\models\ProductTags;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\modules\dhgate\apihelpers\DhgateApiHelper;
use eagle\modules\util\helpers\RedisHelper;
use common\api\wishinterface\WishInterface_Helper;
use eagle\modules\order\helpers\WishOrderInterface;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\carrier\models\SysCarrierCustom;
// use eagle\modules\carrier\helpers\CarrierAPIHelper;
use eagle\models\SysCountry;
use eagle\models\carrier\CrTemplate;
use eagle\modules\inventory\helpers\InventoryApiHelper;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\models\carrier\CarrierUseRecord;
use eagle\modules\carrier\helpers\CarrierOpenHelper;
use yii\db\Query;
use eagle\modules\inventory\helpers\InventoryHelper;
use Qiniu\json_decode;
use common\helpers\Helper_Currency;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\dash_board\helpers\DashBoardHelper;

class PDFQueueHelper{ 
	
	CONST PDF_TASKS = "PDF_TASK";
	CONST PDF_JOBS = "PDF_JOBS";
	CONST PDF_TASK_DETAIL = "PDF_TASK_DETAIL";
	CONST PDF_TASK_DONE_URL = "PDF_TASK_DONE_URL";
	CONST PDF_HANDLER_ALIVE_TIMEOUT = 8; //SECONDS,如果超过这个时间没有刷新自己的refresh time，就认为他可能死掉了
	CONST PDF_HANDLER_WORKING_TIMEOUT = 5; //seconds,如果做一个pdf 生成，超过这个时间判断为job 死掉了
	public static $HANDLER_SHOULD_EXIT = false;
	public static $mainQueueVersion ='';
	
	/**
	 * 插入一个创建n个pdf的任务, 根据timestamp + puid先创建一个key，然后这个key放到redis中去，如果插入失败(已经有相同的了)，再尝试一次换个key
	 *
	 * param: array(0=>array(pdfinfo) , 1=>array(pdfinfo))
	 *
	 * author: yzq
	 * date 2017-4-17
	 *
	 * 返回：array('success'=>true/false , 'message'=>'', 'key'=>key)
	 * 
	 * 后面提取该任务的内容pdf生成结果的时候，需要 $keyL2."/0" ,  $keyL2."/1" , 这样来提取
	 */
	public static function insertATaskForPDFs( $pdf_details=array()){
	 
		$retryCount=-1;
		if (empty($pdf_details))
			return array('success'=>false , 'message'=>'empty details in parameter', 'key'=>'');
		
		$keyL2Head='';
		do {
			$retryCount++;
			$keyL2Head = time()."_".rand(10,99)."_".$retryCount;
			$keyL2 = $keyL2Head."/0";
			$rtn = RedisHelper::RedisCreate(self::PDF_TASKS, $keyL2 , date('Y-m-d H:i:s')."Detail in ".self::PDF_TASK_DETAIL."/".$keyL2);
			} while ($rtn <= 0 and $retryCount<10);
		
		if ($rtn < 1)
			$message="PDF E02:Insert Redis failed";
		else {
			$message='';
			//insert this task Detail
			RedisHelper::RedisCreate(self::PDF_TASK_DETAIL, $keyL2 , json_encode($pdf_details[0]));
			
			//继续插入其他的
			
			$appendTasks=[];
			$appendTasksHeader=[];
			for ($i=1; $i<count($pdf_details); $i++){
				$keyL2 = $keyL2Head."/".$i;
				$appendTasks[$keyL2] =json_encode($pdf_details[$i]) ;
				$appendTasksHeader[$keyL2] = date('Y-m-d H:i:s')."Detail in ".self::PDF_TASK_DETAIL."/".$keyL2;
			}
			
			if (count($pdf_details)>1){
				$rtn = RedisHelper::RedisMSet(self::PDF_TASKS, $appendTasksHeader);
				RedisHelper::RedisMSet(self::PDF_TASK_DETAIL, $appendTasks);
			}
		}
		
		return array('success'=>($rtn == 1) or ($rtn == 'OK')  , 'message'=>$message, 'key'=>$keyL2Head);
	}
	
	static public function routeManagerResign(){
		global $CACHE;
		//如果当前job queue里面注册的 主人只自己，并且是8秒内有过活跃的，那么把自己的注册信息删除吧，因为自己要退位了
		$currentRouteManager_str = RedisHelper::RedisGet(self::PDF_JOBS, "ROUTE-MANAGER");
		if (!empty($currentRouteManager_str)){
			$currentRouteManager = json_decode($currentRouteManager_str,true);
			if (!empty($currentRouteManager['jobKey']) and $currentRouteManager['jobKey'] == $CACHE['JOBID'] and
			!empty($currentRouteManager['lastTouch']) and $currentRouteManager['lastTouch']> time()-8 )
				RedisHelper::RedisDel(self::PDF_JOBS, "ROUTE-MANAGER");
		}
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
		if (!isset ($CACHE['JOBID']))
			$CACHE['JOBID'] =  (1 +  rand(1,9999) * rand(1,9999)) % 1147483647;
	
		$job_key = $CACHE['JOBID'];
	
		$now_str = date('Y-m-d H:i:s');
		//step 1: 检查新版本，要不要重启自己
		$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
		if (empty($currentMainQueueVersion))
			$currentMainQueueVersion = 0;

		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$mainQueueVersion))
			self::$mainQueueVersion = $currentMainQueueVersion;

		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$mainQueueVersion <> $currentMainQueueVersion){
		 
			PDFQueueHelper::routeManagerResign();
		 
			DashBoardHelper::WatchMeDown();
			exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
		}

				//step 2: 尝试这个job 做active的manager,因为有2个job同时抢做active，避免active manager死掉了要等1分钟cool down
				$currentRouteManager_str = RedisHelper::RedisGet(self::PDF_JOBS, "ROUTE-MANAGER");
				$tryToLock = false;
				
				if (!empty($currentRouteManager_str)){ //自己是之前的active manager，就6秒钟 update一次touch time就ok，如果是别人是上次active manager，等他12秒都不更新才尝试夺位
					$currentRouteManager = json_decode($currentRouteManager_str,true);
					if (!empty($currentRouteManager['jobKey']) and $currentRouteManager['jobKey'] == $CACHE['JOBID'] and
					!empty($currentRouteManager['lastTouch']) and $currentRouteManager['lastTouch']> time()-6
					 or 
					 !empty($currentRouteManager['jobKey']) and $currentRouteManager['jobKey'] <> $CACHE['JOBID'] and
					 !empty($currentRouteManager['lastTouch']) and $currentRouteManager['lastTouch']< time()-12 
					)
					$tryToLock = true;	
				}else 
					$tryToLock = true;
				
				//try to lock / touch the record, make self the active manager
				if (!$tryToLock){
					echo "RouteManger can not active, the other is UP , I quit \n";//ystest
					return ;
				}
					
				$setArray=array('jobKey'=>$CACHE['JOBID'] , 'lastTouch'=>time());
				RedisHelper::RedisSet(self::PDF_JOBS, "ROUTE-MANAGER",json_encode($setArray));
	
				usleep(100 * 1000); //避免2个同时想要 做active manager，等等
				$currentRouteManager_str = RedisHelper::RedisGet(self::PDF_JOBS, "ROUTE-MANAGER");
				$currentRouteManager = json_decode($currentRouteManager_str,true);
				
				//如果load出来发现active manager不是自己，就是被抢了，那就算了，自己休息吧
				if (empty($currentRouteManager['jobKey']) or $currentRouteManager['jobKey']<>$CACHE['JOBID']){
					echo "RouteManger can not active, the other is UP , I quit \n";//ystest
					return ;
				}
					
				/*step 3.2 把任务分派给idel jobs，因为下一次还是这个job做manager，不需要销毁 job Task array，如果还没有做完的话
				*/

				//每隔4秒，通知一次我还活着
				if (empty($CACHE['Last_alive_signal_time']))
					$CACHE['Last_alive_signal_time'] = date('Y-m-d H:i:s');
				
				do{
					//step 3.1 看看还有没有任务需要做
					if (empty($pendingTasks))
						$pendingTasks =RedisHelper::RedisGetAll( self::PDF_TASKS);
					
					if (empty($pendingTasks))
						break;
					
					//step 3.2 删除update time是很久以前的job handler，那些事死掉的了。并且看看有哪些job现在idle 了
					$allJobs = RedisHelper::RedisGetAll(self::PDF_JOBS);
					$idleJobs = array();
					
					foreach ($allJobs as $jobKey=>$jobDetailStr){
						$jobDetail = json_decode($jobDetailStr,true);
					 
						if ( $jobKey <>'ROUTE-MANAGER'){
						 
							if ($jobDetail['status']=='P' and $jobDetail['touch_time'] < time()- self::PDF_HANDLER_ALIVE_TIMEOUT or
								$jobDetail['status']<>'P' and $jobDetail['touch_time'] < time()- self::PDF_HANDLER_WORKING_TIMEOUT ){//已经超过x秒没有响应了,那就是job死掉了，删除这个注册信息，把这个任务写到pdf failed表里面
								//handler job 自己也会自己touch自己，表示alive，但是会每隔PDF_HANDLER_ALIVE_TIMEOUT- 2 秒就touch一次
								$command = Yii::$app->db_queue2->createCommand("insert into pdf_task_failed ( job_id,start_time,touch_time,status,assignment,task_detail) values
											( :job_id,:start_time,:touch_time,:status,:assignment,:task_detail )"  );
								$command->bindValue(':job_id', isset($jobDetail['job_id'])?$jobDetail['job_id']:'', \PDO::PARAM_STR);
								$command->bindValue(':start_time', isset($jobDetail['start_time'])?$jobDetail['start_time']:'1995-5-5 05:05:05', \PDO::PARAM_STR);
								$command->bindValue(':touch_time',isset($jobDetail['touch_time'])?$jobDetail['touch_time']:'1995-5-5 05:05:05', \PDO::PARAM_STR);
								$command->bindValue(':status', isset($jobDetail['status'])?$jobDetail['status']:'', \PDO::PARAM_STR);
								$command->bindValue(':assignment', isset($jobDetail['assignment'])?$jobDetail['assignment']:'', \PDO::PARAM_STR);
								$command->bindValue(':task_detail', isset($jobDetail['task_detail'])?$jobDetail['task_detail']:'', \PDO::PARAM_STR);
								$affectRows = $command->execute();
									
								if ($affectRows <=0 )
									echo "Error PDF01, failed to insert into pdf task failed table $jobKey";
									else
										RedisHelper::RedisDel(self::PDF_JOBS, $jobKey);
											
							}else{//it is within 4 seconds, so this is a alive job
								//check if its status is P and assignment is blank,and lasttouch time 距离他自己刷新报告alive还有3秒（因为他会自己到钟前1秒刷新自己alive，避免并发写入redis冲突） //if ($jobDetail['touch_time'] < time() - self::PDF_HANDLER_ALIVE_TIMEOUT +2){
								if (empty($jobDetail['assignment']) and $jobDetail['status']=='P' and $jobDetail['touch_time'] - 3 > time()- self::PDF_HANDLER_ALIVE_TIMEOUT)
									  //give heart beat condition, a pdf handler is if ($jobDetail['touch_time'] < time() - self::PDF_HANDLER_ALIVE_TIMEOUT +2)
								$idleJobs[$jobKey] = $jobDetail;
							}
							}//end if this is a PDF Creator job
						}//end of each job in job register
					
						if (empty($idleJobs ) )
							break;	
								
					//step 3.3 assign 任务啦
								
					echo "ys1.a got pending counts are :".count($pendingTasks) ."\n"; //ystest
					$now_str = date('Y-m-d H:i:s');
				//	self::submitCompletedMQHsToRedisCommitQueue();
					$assignedTaskIds = self::assignBulkTaskToMQH( $pendingTasks,  $idleJobs);
					//echo "Assigned to MainQ Handler, count=".count($assignedTaskIds)."\n";
					
					//Step A， 如果已经派发完毕工作了，看看有没有新的pending task
					if (empty($pendingTasks) ){
						//如果已经做完了，看看还有没有新来的需要做
						$pendingTasks =RedisHelper::RedisGetAll( self::PDF_TASKS);
						
						if (empty($pendingTasks))
							$pendingTasks = array();	
					}
					
					//每隔4秒，通知一次我还活着	
					$sec_4_ago = date('Y-m-d H:i:s',strtotime('-4 seconds'));
					if ($CACHE['Last_alive_signal_time'] < $sec_4_ago){
						$setArray=array('jobKey'=>$CACHE['JOBID'] , 'lastTouch'=>time());
						RedisHelper::RedisSet(self::PDF_JOBS, "ROUTE-MANAGER",json_encode($setArray));
					
						//step 0: 检查新版本，要不要重启自己
						$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
						if (empty($currentMainQueueVersion))
							$currentMainQueueVersion = 0;
					
						//如果自己还没有定义，去使用global config来初始化自己
						if (empty(self::$mainQueueVersion))
							self::$mainQueueVersion = $currentMainQueueVersion;
					
						//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
						if (self::$mainQueueVersion <> $currentMainQueueVersion){
							PDFQueueHelper::routeManagerResign();
							DashBoardHelper::WatchMeDown();
							exit("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
						}
					}//end of if time to give heart beat
					
					//echo "ys1A counts are :".count($CACHE['priority1'])."   ".count($CACHE['priorityOther'])."\n"; //ystest
				}while ( 1 );//知道没有任务了，或者没有可用的job了，才借宿循环
				
				return '';
	}
	
	/*
	 * 如果return 是 empty的，就是还没有结果，
	 * 第一个参数，task key 
	 * 第二个参数，true/false，默认是true，就是要不要顺便删除这个结果在redis
	 * */
	static public function getResultFor($taskKey, $delete=true){
		$result= RedisHelper::RedisGet(self::PDF_TASK_DONE_URL,$taskKey);
		if ($delete and !empty($result)){
			self::delResultFor($taskKey );
		}
		
		return $result;
	}
	
	/*
	 * 删除这个结果在redis
	* 第一个参数，task key
	* 
	* */
	static public function delResultFor($taskKey ){
			RedisHelper::RedisDel(self::PDF_TASK_DONE_URL,$taskKey);
			RedisHelper::RedisDel(self::PDF_TASK_DETAIL,$taskKey);
		return 1;
	}
	
	static public function pdfJobHandler(){
		global $CACHE ;
		if (!isset ($CACHE['JOBID']))
			$CACHE['JOBID'] =  (1 +  rand(1,9999) * rand(1,9999)) % 1147483647;
	
		$job_key = $CACHE['JOBID'];
	
		$now_str = date('Y-m-d H:i:s');
		//step 1: 检查新版本，要不要重启自己
		$currentMainQueueVersion = ConfigHelper::getGlobalConfig("Tracking/subQueueVersion",'NO_CACHE');
		if (empty($currentMainQueueVersion))
			$currentMainQueueVersion = 0;
	
		//如果自己还没有定义，去使用global config来初始化自己
		if (empty(self::$mainQueueVersion))
			self::$mainQueueVersion = $currentMainQueueVersion;
	
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$mainQueueVersion <> $currentMainQueueVersion){
			self::$HANDLER_SHOULD_EXIT = true;	
			echo ("Version new $currentMainQueueVersion , this job ver ".self::$mainQueueVersion." exits for using new version $currentMainQueueVersion.");
		}
		
		//step 2：看看是否自己需要退出，如果需要退出indicator亮了，就退出，(譬如外层的controller发现足够30分钟了)，或者发现版本更新了
		if (self::$HANDLER_SHOULD_EXIT  ){
			PDFQueueHelper::pdfHandlerResign();
			
		}
		
		//step 3: 读取自己的job task redis报道信息，如果还没有，写入自己的报道信息
		$jobDetailStr = RedisHelper::RedisGet(self::PDF_JOBS,$job_key);
		/*
		 job_id,start_time,touch_time,status,assignment,task_detail
		*/
		if (empty($jobDetailStr)){
			$jobDetail = array();
			$jobDetail['job_id'] = $job_key;
			$jobDetail['start_time'] = time();
			$jobDetail['touch_time'] = time();
			$jobDetail['status'] = 'P';
			$jobDetail['assignment'] = '';
			$jobDetail['task_detail'] = '';
			RedisHelper::RedisSet(self::PDF_JOBS,$job_key, json_encode($jobDetail));
		}else
			$jobDetail = json_decode($jobDetailStr,true);

		
		//step 4：看看自己有没有被assigned 到任务
		$taskDoneResult='';
		$time1 = 0;
			if (!empty($jobDetail['assignment']) and $jobDetail['status']=='P'){
				$jobDetail['touch_time'] = time();
				$time1 = time();
				$jobDetail['status'] = 'S';
				$jobDetail['task_detail'] = '';
				echo "Got task,mark status S for $job_key ". date('Y-m-d H:i:s')."\n";
				RedisHelper::RedisSet(self::PDF_JOBS,$job_key, json_encode($jobDetail));
				
				//step 4.5：做好任务，写好结果
				$taskDetailStr = RedisHelper::RedisGet(self::PDF_TASK_DETAIL,$jobDetail['assignment']);
				if (!empty($taskDetailStr)){
					$taskDetail = json_decode($taskDetailStr,true);
					echo "---try to work for task ".$jobDetail['assignment']." at ".date('Y-m-d H:i:s')."\n";
					$runResult= \eagle\modules\carrier\helpers\CarrierAPIHelper::doPDFGenerate($taskDetail);
					
					RedisHelper::RedisSet(self::PDF_TASK_DONE_URL,$jobDetail['assignment'], json_encode($runResult));
					
					$jobDetail['touch_time'] = time();
					$jobDetail['status'] = 'P';
					$jobDetail['assignment']='';
					$jobDetail['task_detail'] = '';
					RedisHelper::RedisSet(self::PDF_JOBS,$job_key, json_encode($jobDetail));
					
					$time2 = time();
					echo "---Finished for job $job_key , used time = ".($time2 - $time1)." seconds \n";
				}else{
					echo "E003 failed to finid the PDF_TASK_DETAIL for task ".$jobDetail['assignment']."\n";
				}
					 
			}else{ //give 心跳，防止这个job被以为死掉了，timeout 前一秒 刷新 自己 就ok了
				if ($jobDetail['touch_time'] < time() - self::PDF_HANDLER_ALIVE_TIMEOUT +2){
					$jobDetail['touch_time'] = time();
					echo "try to give heart beat for $job_key ".date('Y-m-d H:i:s')."\n";
					RedisHelper::RedisSet(self::PDF_JOBS,$job_key, json_encode($jobDetail));
				}
			}	
	}
	
	static private function assignBulkTaskToMQH(&$pendingTasks, &$idleJobs){
		global $CACHE;
		$assignedTaskIds=array();
		 
		$idleMQHanlers_can_handle_count = count($idleJobs);
			 
			foreach ($pendingTasks as $TaskKey=>$TaskDetail ){
				//3.2.2.A Assign task to MQHander，并且放到redis中
				if ($idleMQHanlers_can_handle_count == 0)
					break;
				 
				/*
					job_id,start_time,touch_time,status,assignment,task_detail
				*/ 
				foreach ($idleJobs as $jobKey=>$jobDetail){ //here $jobDetail is already an array
					$jobDetail['touch_time'] = time();
					$jobDetail['status'] = 'P';
					$jobDetail['assignment'] = $TaskKey;
					RedisHelper::RedisSet(self::PDF_JOBS,$jobKey, json_encode($jobDetail));
					RedisHelper::RedisDel(self::PDF_TASKS,$TaskKey);
					unset($idleJobs[$jobKey]);
					unset($pendingTasks[$TaskKey]);
					$assignedTaskIds[$TaskKey]=true;
					break; //only use the first to do, then quit this loop
				}
				$idleMQHanlers_can_handle_count = count($idleJobs);
				//	echo "Try to unset cache $priorityName $key \n";//ystest
			}//end of each of task

		return $assignedTaskIds;
		}
		
		
	static private function pdfHandlerResign(){
		global $CACHE ;
		$job_key = $CACHE['JOBID'];
		
		//step 1: 读取自己的job task redis报道信息，如果还没有，写入自己的报道信息
		$jobDetailStr = RedisHelper::RedisGet(self::PDF_JOBS,$job_key);
		/*
		 job_id,start_time,touch_time,status,assignment,task_detail
		*/
		if (empty($jobDetailStr)){ //这特不太可能
			
		}else{ //为了避免我想退出，但同时有manager assign 任务给我，所以先要试试看update status为 R，然后等3秒钟看没有人assign任务给我我才走，
			
			$jobDetail = json_decode($jobDetailStr,true);
			/*
			$jobDetail = array();
			$jobDetail['job_id'] = $job_key;
			$jobDetail['start_time'] = time();
			$jobDetail['touch_time'] = time();
			$jobDetail['status'] = 'P';
			$jobDetail['assignment'] = '';
			$jobDetail['task_detail'] = '';*/
			
			if ( !empty($jobDetail['assignment']) ) // 把任务重新写回去 task queue
				RedisHelper::RedisCreate(self::PDF_TASKS, $jobDetail['assignment'] , date('Y-m-d H:i:s')."Detail in ".self::PDF_TASK_DETAIL."/".$jobDetail['assignment']);
			
			$jobDetail['assignment'] = '';
			$jobDetail['status'] = 'R';
			RedisHelper::RedisSet(self::PDF_JOBS,$job_key, json_encode($jobDetail));
			
			sleep(3);
			$jobDetailStr = RedisHelper::RedisGet(self::PDF_JOBS,$job_key);
			if (empty($jobDetailStr)){
				$jobDetail = json_decode($jobDetailStr,true);
				
				if ( !empty($jobDetail['assignment']) )  // 把任务重新写回去 task queue
				RedisHelper::RedisCreate(self::PDF_TASKS, $jobDetail['assignment'] , date('Y-m-d H:i:s')."Detail in ".self::PDF_TASK_DETAIL."/".$jobDetail['assignment']);
				
				RedisHelper::RedisDel(self::PDF_TASKS, $jobDetail['assignment']);
			}
		}
		
		DashBoardHelper::WatchMeDown();
		exit("as planned to exit $job_key at ".date('Y-m-d H:i:s'));
	}	
	
}