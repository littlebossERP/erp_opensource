<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\tracking\helpers;


use yii;
use yii\data\Pagination;
use eagle\modules\util\helpers\GoogleHelper;
use eagle\modules\tracking\models\Tracking;
use eagle\modules\util\models\GlobalLog;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\HttpHelper;
use eagle\modules\tracking\helpers\CarrierTypeOfTrackNumber;
use eagle\modules\tracking\helpers\TrackingHelper;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\tracking\models\TrackerApiQueue;
use eagle\modules\tracking\models\TrackerApiSubQueue;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ExcelHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ResultHelper;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\listing\apihelpers\ListingAliexpressApiHelper;
use common\api\aliexpressinterface\AliexpressInterface_Helper; 
use eagle\modules\util\helpers\RedisHelper;
/**
 * BaseHelper is the base class of module BaseHelpers.
 *
 */
class TrackingAgentHelper {
//状态
	const CONST_1= 1; //Sample
	static $contryEnMapCode='';

	//生成当前时间以及未来 N 个hours 的calling统计记录，这样就不会由多线程的SubJob自己来创建，避免创建的IO以及可能发生冲突
	public static function genThisDayCallSumRec($howMany = 6){
		return;
		$Ext_Call = array();
		$Ext_Call[]='Tracking.17Track';
		$Ext_Call[] = 'Tracking.Ubi';
		$Ext_Call[] = "Trk.MainQQuery";
		$Ext_Call[] = 'Trk.MainQPickOne';
		
		$now_str = date('Y-m-d H:i:s');
		$connection = Yii::$app->db;
		for($i = 0; $i<=$howMany ;$i++){
			$now_str = date('Y-m-d H:i:s',strtotime("+".$i." hour".($i>0?"s":"")));
			$time_slot = substr($now_str, 0,13); //"2015-04-26 05"
			foreach ($Ext_Call as $ext_call_name){
				//1. check if there is such a record, 
				$sql = "select count(1) from `ut_ext_call_summary` where ext_call='$ext_call_name' and time_slot='$time_slot'";
				$command = $connection->createCommand($sql);
				$recCount = $command->queryScalar();
				
				//2. if no, add
				if ($recCount == 0){
					//insert into
					$insertSql = "replace INTO  `ut_ext_call_summary` ( `ext_call`,   `time_slot`) VALUES ( '$ext_call_name','$time_slot')";
					$command = $connection->createCommand($insertSql)  ;
					$command->execute();
				}
			}
		}
		
	}

	/*记录这个external call 的调用次数以及每次的耗时 ms，
	 * 默认是每隔3分钟提交一次到数据库统计，
	 * 如果是该job要离开了，请调用 ::extCallSum("",0,true); 来请求立即把buffer 提交数据库
	 * 
	 * @param        ext_call_name			free text，like Trk.17TrackCall
	 * @param        run time               number, micro seconds
	 * @param        now submit             true/false, default false, when true, put the buffer into db immediatelly         
	 * */
	public static function extCallSum($ext_call_name ='',$run_time = 0,$now_submit=false,$count=1){
		global $CACHE;
		$now_str = date('Y-m-d H:i:s');
		
		//如果pass进来的 ext call name 是空白，表示没内容，只是要立即提交buffer 到db
		if ($ext_call_name == '')
			$now_submit = true;
		else{
			$time_slot = substr($now_str, 0,13); //"2015-04-26 05"
			if (!isset($CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_count']))
				$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_count'] = 0;
		
			if (!isset($CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_time_ms']))
				$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_time_ms'] = 0;
		
			$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_count'] += $count;
			$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_time_ms'] += $run_time * $count;
		}
		
		if (!isset($CACHE['ExtCall']['lastSubmitTime']))
			$CACHE['ExtCall']['lastSubmitTime'] = $now_str;
		
		$three_minutes_ago = date('Y-m-d H:i:s',strtotime('-20 minutes'));
		//如果已经间隔了3分钟或者 job 准备退出，要求立即submit这个小计
		if (( $now_submit or $CACHE['ExtCall']['lastSubmitTime'] < $three_minutes_ago) and !empty($CACHE['ExtCall']['ext_call']) ){

			$connection = Yii::$app->db;
			foreach ($CACHE['ExtCall']['ext_call'] as $ext_call_name=>$time_slot_data){
				
				if ($ext_call_name=='')
					continue;
				
				foreach ($time_slot_data as $time_slot=>$data){
					
					if ($data['total_time_ms']==0 and $data['total_count']==0)
						continue;
					
					$updateSql = "update ut_ext_call_summary set total_count=total_count+ ".$data['total_count'].",
						 total_time_ms = total_time_ms + ".$data['total_time_ms']." 
						where ext_call='$ext_call_name' and time_slot= '$time_slot'";
					
					$command = $connection->createCommand($updateSql)  ;
								
					$affectRows = $command->execute();
					//	如果本来就没有这个record，先创建，在update
					if ($affectRows == 0){	//try once more
						//insert into
						$insertSql = "replace INTO  `ut_ext_call_summary` ( `ext_call`,   `time_slot`) VALUES ( '$ext_call_name','$time_slot')";
						$command = $connection->createCommand($insertSql)  ;
						$command->execute();
						
						$command = $connection->createCommand($updateSql)  ;
						$affectRows = $command->execute();	
					}
					
					if ($affectRows > 0){	//do the rest actions
						$updateSql = "update ut_ext_call_summary set  average_time_ms = total_time_ms/total_count
											 where total_count > 0 and ext_call='$ext_call_name' and time_slot= '$time_slot'";
						
						$command = $connection->createCommand($updateSql)  ;
						$affectRows = $command->execute();

						$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_count'] = 0;
						$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_time_ms']  = 0;
					}
				}
			}//end of each ext call name
			$CACHE['ExtCall']['lastSubmitTime'] = $now_str;
		}
		
	}
	
	public static function intCallSum($called_app ='',$invoker = '',$func_name='',$puid=0,$thedate=''){
		global $CACHE;
		$connection = Yii::$app->db;
		$now_str = date('Y-m-d H:i:s');
		$time_slot = substr($now_str, 0,10); //"2015-04-26"
		$updateSql = "update ut_internal_call_summary set counts=counts+ 1  
								 where called_app='$called_app' and thedate = '$time_slot'
			and invoker='$invoker' and func_name='$func_name' and puid=$puid
		";
		
		$command = $connection->createCommand($updateSql)  ;
		
		$affectRows = $command->execute();
		//	如果本来就没有这个record，先创建，在update
		if ($affectRows == 0){	//try once more
			//insert into
			$insertSql = "replace INTO  `ut_internal_call_summary` (  
			`called_app`,thedate,invoker,func_name,puid
			) VALUES ( '$called_app','$time_slot', '$invoker','$func_name','$puid')";
			$command = $connection->createCommand($insertSql)  ;
			$command->execute();
				
			//do the update again
			$command = $connection->createCommand($updateSql)  ;
			$affectRows = $command->execute();
		}
		return $affectRows;
	}

	/*记录这个job 进入 以及正常退出的次数 的调用次数以及每次的耗时 ms，
	* 默认是每隔3分钟提交一次到数据库统计，
	* 如果是该job要离开了，请调用 ::extCallSum("",0,true); 来请求立即把buffer 提交数据库
	*
	* @param        ext_call_name			free text，like Trk.17TrackCall
	* @param        run time               number, micro seconds
	* @param        now submit             true/false, default false, when true, put the buffer into db immediatelly
	* */
	public static function markJobUpDown($ext_call_name ='',$now_str = '',$now_submit=true,$count=1){
		global $CACHE;
		$run_time = 0;
		if (empty($now_str))
			$now_str = date('Y-m-d H:i:s');
	
		//如果pass进来的 ext call name 是空白，表示没内容，只是要立即提交buffer 到db
		if ($ext_call_name == '')
			$now_submit = true;
		else{
			$time_slot = substr($now_str, 0,13); //"2015-04-26 05"
			if (!isset($CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_count']))
				$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_count'] = 0;
	
			if (!isset($CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_time_ms']))
				$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_time_ms'] = 0;
	
			$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_count'] += $count;
			$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_time_ms'] += $run_time * $count;
		}
	
		if (!isset($CACHE['ExtCall']['lastSubmitTime']))
			$CACHE['ExtCall']['lastSubmitTime'] = $now_str;
	
		$three_minutes_ago = date('Y-m-d H:i:s',strtotime('-20 minutes'));
		//如果已经间隔了3分钟或者 job 准备退出，要求立即submit这个小计
		if (( $now_submit or $CACHE['ExtCall']['lastSubmitTime'] < $three_minutes_ago) and !empty($CACHE['ExtCall']['ext_call']) ){
	
			$connection = Yii::$app->db;
			foreach ($CACHE['ExtCall']['ext_call'] as $ext_call_name=>$time_slot_data){
	
				if ($ext_call_name=='')
					continue;
	
				foreach ($time_slot_data as $time_slot=>$data){
						
					if ($data['total_time_ms']==0 and $data['total_count']==0)
						continue;
						
					$updateSql = "update ut_ext_call_summary set total_count=total_count+ ".$data['total_count'].",
						 total_time_ms = total_time_ms + ".$data['total_time_ms']."
							 where ext_call='$ext_call_name' and time_slot= '$time_slot'";
						
					$command = $connection->createCommand($updateSql)  ;
	
					$affectRows = $command->execute();
					//	如果本来就没有这个record，先创建，在update
					if ($affectRows == 0){	//try once more
						//insert into
						$insertSql = "replace INTO  `ut_ext_call_summary` ( `ext_call`,   `time_slot`) VALUES ( '$ext_call_name','$time_slot')";
						$command = $connection->createCommand($insertSql)  ;
						$command->execute();
	
						$command = $connection->createCommand($updateSql)  ;
						$affectRows = $command->execute();
					}
						
					if ($affectRows > 0){	//do the rest actions
						$updateSql = "update ut_ext_call_summary set  average_time_ms = total_time_ms/total_count
						where total_count > 0 and ext_call='$ext_call_name' and time_slot= '$time_slot'";
	
						$command = $connection->createCommand($updateSql)  ;
						$affectRows = $command->execute();
	
						$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_count'] = 0;
						$CACHE['ExtCall']['ext_call'][$ext_call_name][$time_slot]['total_time_ms']  = 0;
					}
				}
			}//end of each ext call name
			$CACHE['ExtCall']['lastSubmitTime'] = $now_str;
		}
	
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * Call Or Proxy to do 17Track query, by http.
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $pendingSubOne         sub Queue task model
	 * @param  $sub_id1               sub Queue task id, if specified
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'result'='',run_time=8,sub_queue_status=>C)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/2        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function queryUbiInterface($pendingSubOne ){
		global $CACHE;
		$Ext_Call = 'Tracking.Ubi';
		$JOBID = $CACHE['JOBID'];
		$CACHE['Ext_Call'] = $Ext_Call;
		\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ Ubi.1 ".$JOBID],"file");
		if (! is_string($pendingSubOne))
			$track_no = $pendingSubOne->track_no;
		else
			$track_no = $pendingSubOne;
	
		$url = "http://smartparcel.gotoubi.com/track?trackingNo=$track_no";
		$ubiArray = array();
	
		$current_time=explode(" ",microtime());
	
	
		$ubiArray['success'] = true;
		$ubiArray['track_no'] = "";
		$ubiArray['events'] = array();
	
		$trackNoArr = explode("=", $url);
	
		if (count($trackNoArr) < 1) {
			$ubiArray['success'] = false;
			return $ubiArray;
		}
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
		$ubiArray['track_no'] = $trackNoArr[1];
	//	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ Ubi.2 ".$JOBID],"file");
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
	
		$output = curl_exec($ch);
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		if ($curl_errno > 0) { // network error
			$ubiArray['success'] = false;
			curl_close($ch);
			return $ubiArray;
		}
	//	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ Ubi.3 ".$JOBID],"file");
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	
		$current_time=explode(" ",microtime());
		$time2=round($current_time[0]*1000+$current_time[1]*1000);
	
		//计算累计做了多少次external 的调用以及耗时
	
		$run_time = $time2 - $time1; //这个得到的$time是以 ms 为单位的
	
		self::extCallSum($Ext_Call,$run_time);
	
		$ubiArray['result'] = $output;
		$ubiArray['run_time'] = $run_time;
	//	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ Ubi.4 ".$JOBID],"file");
		if ($httpCode == '200'){
		}else{ // network error
			$ubiArray['success'] = false ;
		}
	
		if (!$ubiArray['success'])
			$ubiArray['sub_queue_status'] = 'F';
		else
			$ubiArray['sub_queue_status'] = 'C';
	
		curl_close($ch);
	//	\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"SJ Ubi.5 ".$JOBID],"file");
		return $ubiArray;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Call Or Proxy to do Equick query, by http.
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $pendingSubOne         sub Queue task model
	 * @param  $sub_id1               sub Queue task id, if specified
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'result'='',run_time=8,sub_queue_status=>C)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/6/12        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	static public function queryEquickProxy($pendingSubOne,$sub_id1=''){
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn = array();
		$rtn['success'] = true;
		$Ext_Call='Tracking.Equick';
		$CACHE['Ext_Call'] = $Ext_Call;
		// TODO proxy host
		$TRACK_PROXY_URL = "http://localhost/equick_proxy/equick.php?token=HEHEyssdfWERSDF,werSDFJIYfghg,ddctYAYA&track_no=";
		$trackingNo = $pendingSubOne->track_no;
		 
		//开始通过Proxy 查询这个请求的track code + carrier type
		$current_time=explode(" ",microtime());				
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
 
		$url = $TRACK_PROXY_URL.$trackingNo ;
     if (TrackingHelper::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub eQuick 20.1 subjobid=$JOBID,track_no=$trackingNo" );

		$url_result = self::getHttpByCurl($url);
		
     if (TrackingHelper::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub eQuick 20.3 subjobid=$JOBID,track_no=$trackingNo" );
		$resultStr = $url_result['content'];
 
		
		//如果17Track 返回结果为空，尝试在玩一次
		if(empty($resultStr)) {
			$url_result = self::getHttpByCurl($url);
			$resultStr = $url_result['content'];
		}
     if (TrackingHelper::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub eQuick 20.3b subjobid=$JOBID,track_no=$trackingNo" );
     
		$results = json_decode($resultStr,true);
		if (empty($results)) $results = array();
 
		$run_time = $url_result['run_time'];
		$rtn['result'] = $resultStr;
		$rtn['run_time'] = $run_time;
		$pendingSubOne->result = $resultStr;
     if (TrackingHelper::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub eQuick 20.4 subjobid=$JOBID,track_no=$trackingNo" );
		//if not success, update it as Retry(status still = P) or Failed.(when try count >=3, mark it failed)
		if (!isset($results['Success']) or !$results['Success'] ){
			$rtn['sub_queue_status'] = 'F';
		}else{
			$rtn['sub_queue_status'] = 'C';
	
		}//end of when call proxy success
     if (TrackingHelper::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub eQuick 20.Ret subjobid=$JOBID,track_no=$trackingNo" );
	return $rtn;
	}

	
	static public function queryBRTProxy($pendingSubOne,$sub_id1=''){
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn = array();
		$rtn['success'] = true;
		$Ext_Call='Tracking.Equick';
		$CACHE['Ext_Call'] = $Ext_Call;
		// TODO proxy host
		$TRACK_PROXY_URL = "http://localhost/brt_proxy/do_track.php?token=HEHEyssdfWERSDF,werSDFJIYfghg,ddctYAYA&track_no=";
		$trackingNo = $pendingSubOne->track_no;
			
		//开始通过Proxy 查询这个请求的track code + carrier type
		$current_time=explode(" ",microtime());
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
	
		$url = $TRACK_PROXY_URL.$trackingNo ;
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub eQuick 20.1 subjobid=$JOBID,track_no=$trackingNo" );
	
		$url_result = self::getHttpByCurl($url);
	
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub eQuick 20.3 subjobid=$JOBID,track_no=$trackingNo" );
		$resultStr = $url_result['content'];
	
	
		//如果17Track 返回结果为空，尝试在玩一次
		if(empty($resultStr)) {
			$url_result = self::getHttpByCurl($url);
			$resultStr = $url_result['content'];
		}
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub eQuick 20.3b subjobid=$JOBID,track_no=$trackingNo" );
		 
		$results = json_decode($resultStr,true);
		if (empty($results)) $results = array();
	
		$run_time = $url_result['run_time'];
		$rtn['result'] = $resultStr;
		$rtn['run_time'] = $run_time;
		$rtn['proxy_call'] = $url;
		$pendingSubOne->result = $resultStr;
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub eQuick 20.4 subjobid=$JOBID,track_no=$trackingNo" );
		//if not success, update it as Retry(status still = P) or Failed.(when try count >=3, mark it failed)
		if (!isset($results['Success']) or !$results['Success'] ){
			$rtn['sub_queue_status'] = 'F';
		}else{
			$rtn['sub_queue_status'] = 'C';
	
		}//end of when call proxy success
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub eQuick 20.Ret subjobid=$JOBID,track_no=$trackingNo" );
		return $rtn;
	}
	
	static public function queryColissimoProxy($pendingSubOne,$sub_id1=''){
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn = array();
		$rtn['success'] = true;
		$Ext_Call='Tracking.Colissimo';
		$CACHE['Ext_Call'] = $Ext_Call;
		// TODO proxy host
		$TRACK_PROXY_URL = "http://localhost/colissimo_proxy/colissimo.php?token=HEHEyssdfWERSDF,werSDFJIYfghg,ddctYAYA&track_no=";
		$trackingNo = $pendingSubOne->track_no;
			
		//开始通过Proxy 查询这个请求的track code + carrier type
		$current_time=explode(" ",microtime());
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
	
		$url = $TRACK_PROXY_URL.$trackingNo ;
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Colissimo 20.1 subjobid=$JOBID,track_no=$trackingNo" );
	
		$url_result = self::getHttpByCurl($url);
	
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Colissimo 20.3 subjobid=$JOBID,track_no=$trackingNo" );
		$resultStr = $url_result['content'];
	
	
		//如果17Track 返回结果为空，尝试在玩一次
		if(empty($resultStr)) {
			$url_result = self::getHttpByCurl($url);
			$resultStr = $url_result['content'];
		}
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Colissimo 20.3b subjobid=$JOBID,track_no=$trackingNo" );
			
		$results = json_decode($resultStr,true);
		if (empty($results)) $results = array();
	
		$run_time = $url_result['run_time'];
		$rtn['result'] = $resultStr;
		$rtn['run_time'] = $run_time;
		$rtn['proxy_call'] = $url;
		$pendingSubOne->result = $resultStr;
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Colissimo 20.4 subjobid=$JOBID,track_no=$trackingNo" );
		//if not success, update it as Retry(status still = P) or Failed.(when try count >=3, mark it failed)
		if (!isset($results['Success']) or !$results['Success'] ){
			$rtn['sub_queue_status'] = 'F';
		}else{
			$rtn['sub_queue_status'] = 'C';
	
		}//end of when call proxy success
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Colissimo 20.Ret subjobid=$JOBID,track_no=$trackingNo" );
		return $rtn;
	}
	static public function queryPonyProxy($pendingSubOne,$sub_id1=''){
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn = array();
		$rtn['success'] = true;
		$Ext_Call='Tracking.Pony';
		$CACHE['Ext_Call'] = $Ext_Call;
		// TODO proxy host
		$TRACK_PROXY_URL = "http://localhost/colissimo_proxy/pony.php?token=HEHEyssdfWERSDF,werSDFJIYfghg,ddctYAYA&track_no=";
		$trackingNo = $pendingSubOne->track_no;
			
		//开始通过Proxy 查询这个请求的track code + carrier type
		$current_time=explode(" ",microtime());
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
	
		$url = $TRACK_PROXY_URL.$trackingNo ;
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Pony 20.1 subjobid=$JOBID,track_no=$trackingNo" );
	
		$url_result = self::getHttpByCurl($url);
	
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Pony 20.3 subjobid=$JOBID,track_no=$trackingNo" );
		$resultStr = $url_result['content'];
	
	
		//如果17Track 返回结果为空，尝试在玩一次
		if(empty($resultStr)) {
			$url_result = self::getHttpByCurl($url);
			$resultStr = $url_result['content'];
		}
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Pony 20.3b subjobid=$JOBID,track_no=$trackingNo" );
			
		$results = json_decode($resultStr,true);
		if (empty($results)) $results = array();
	
		$run_time = $url_result['run_time'];
		$rtn['result'] = $resultStr;
		$rtn['run_time'] = $run_time;
		$rtn['proxy_call'] = $url;
		$pendingSubOne->result = $resultStr;
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Pony 20.4 subjobid=$JOBID,track_no=$trackingNo" );
		//if not success, update it as Retry(status still = P) or Failed.(when try count >=3, mark it failed)
		if (!isset($results['Success']) or !$results['Success'] ){
			$rtn['sub_queue_status'] = 'F';
		}else{
			$rtn['sub_queue_status'] = 'C';
	
		}//end of when call proxy success
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub Pony 20.Ret subjobid=$JOBID,track_no=$trackingNo" );
		return $rtn;
	}	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Call Or Proxy to do Equick query, by http.
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $pendingSubOne         sub Queue task model
	 * @param  $sub_id1               sub Queue task id, if specified
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'result'='',run_time=8,sub_queue_status=>C)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/6/12        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function query4PXProxy($pendingSubOne,$sub_id1=''){ //999000002
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn = array();
		$rtn['success'] = true;
		$Ext_Call='Tracking.4PX';
		$CACHE['Ext_Call'] = $Ext_Call;
		// TODO proxy host
		$TRACK_PROXY_URL = "http://localhost/proxy_4px/queryhtml4px.php?token=HEHEyssdfWERSDF,werSDFJIYfghg,ddctYAYA&track_code=";
		$trackingNo = $pendingSubOne->track_no;
			
		//开始通过Proxy 查询这个请求的track code + carrier type
		$current_time=explode(" ",microtime());
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
	
		$url = $TRACK_PROXY_URL.$trackingNo ;
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub 4PX 20.1 subjobid=$JOBID,track_no=$trackingNo" );
	
		$url_result = self::getHttpByCurl($url);
	
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub 4PX 20.3 subjobid=$JOBID,track_no=$trackingNo" );
		
		$resultStr = $url_result['content'];
	
		//如果4px 返回结果为空，尝试在玩一次
		if(empty($resultStr)) {
			$url_result = self::getHttpByCurl($url);
			$resultStr = $url_result['content'];
		}
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub 4PX 20.3b subjobid=$JOBID,track_no=$trackingNo" );
		 
		$results = json_decode($resultStr,true);
		if (empty($results)) $results = array();
	
		$run_time = $url_result['run_time'];
		$rtn['result'] = $resultStr;
		$rtn['run_time'] = $run_time;
		$pendingSubOne->result = $resultStr;
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub 4PX 20.4 subjobid=$JOBID,track_no=$trackingNo" );
		//if not success, update it as Retry(status still = P) or Failed.(when try count >=3, mark it failed)
		if (!isset($results['Success']) or !$results['Success'] ){
			$rtn['sub_queue_status'] = 'F';
		}else{
			$rtn['sub_queue_status'] = 'C';
		}//end of when call proxy success
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub 4PX 20.Ret subjobid=$JOBID,track_no=$trackingNo" );
		
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Call 4px api internal.
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $pendingSubOne         sub Queue task model
	 * @param  $sub_id1               sub Queue task id, if specified
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'result'='',run_time=8,sub_queue_status=>C)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/12/23        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function query4PXAPI($trackingNo='',$sub_id1='',$type='4PX'){ //999000002
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn = array();
		$rtn['success'] = true;
		$res=[];
		$res['message'] = '';
		$Ext_Call='Tracking.4PXAPI';
		$CACHE['Ext_Call'] = $Ext_Call;
		 
		//开始通过4PX API 查询这个请求的track code  
		$current_time=explode(" ",microtime());
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
		try{
			$class_name = '\common\api\carrierAPI\\LB_'.$type.'CarrierAPI';
			$interface = new $class_name;
		}  catch(\Exception $e){	
			$res['message'] .= "Got exception1 LB_".$type."CarrierAPI by Carrier API ".$e->getMessage();
		}
		
		try{
			$res = $interface->SyncStatus( [ $trackingNo ]);
		}  catch(\Exception $e){
			$res['message'] .= "Got exception2 by Carrier API $class_name param [ $trackingNo ]  ".$e->getMessage();
		}
		
		 //echo "Got carrier api non 17 ".print_r($res,true)."\n";
		
		$current_time=explode(" ",microtime());
		$time2=round($current_time[0]*1000+$current_time[1]*1000);
		
		$rtn['result'] = json_encode($res);
		$rtn['run_time'] = $time2 - $time1;
		 
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub  $type api 20.4 subjobid=$JOBID,track_no=$trackingNo Runtime=".$rtn['run_time'] );
		//if not success, update it as Retry(status still = P) or Failed.(when try count >=3, mark it failed)
		if (!isset($res['error'] ) or $res['error']=="1" ){
			$rtn['sub_queue_status'] = 'F';
		}else{
			$rtn['sub_queue_status'] = 'C';
		}//end of when call proxy success
		if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub  $type  20.Ret subjobid=$JOBID,track_no=$trackingNo" );
	
		return $rtn;
	}	
	
	static public function queryCNEAPI($trackingNo='',$sub_id1='',$type='4PX'){ //999000002
		return self::query4PXAPI($trackingNo ,$sub_id1,'CNE');
	}
	
	static public function queryWinitAPI($trackingNo='',$sub_id1='',$type='4PX'){ //999000002
		return self::query4PXAPI($trackingNo ,$sub_id1,'WANYITONG');
	}
	
	static public function query139API($trackingNo='',$sub_id1='',$type='4PX'){ //999000002
		return self::query4PXAPI($trackingNo ,$sub_id1,'139EXPRESS');
	}
	
	static public function queryYUNTUAPI($trackingNo='',$sub_id1='',$type='4PX'){ //999000002
		return self::query4PXAPI($trackingNo ,$sub_id1,'YUNTU');
	}
	
	 
	
	static public function querySFAPI($trackingNo='',$sub_id1='',$type='SF'){ //999000002
		return self::query4PXAPI($trackingNo ,$sub_id1,'SF');
	}
	/**
	 +---------------------------------------------------------------------------------------------
	 * Call AliExpress API 获取这个parcel的update.
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	  * $selleruserid 平台登录账号（获取token使用）
	  * $serviceName 物流服务KEY	UPS	
	  * $logisticsNo 物流追踪号	20100810142400000-0700
	  * $toArea      交易订单收货国家(简称)	FJ,Fiji;FI,Finland;FR,France;
	  * $orderId      用户需要查询的订单id
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'result'='',run_time=8,sub_queue_status=>C)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/9-8        		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function queryAliexpressParcel($selleruserid, $serviceName, $logisticsNo, $toArea, $orderId){
		global $CACHE,$ALIEXPRESS_CARRIER_NAME_2_CODE;
		if (empty($ALIEXPRESS_CARRIER_NAME_2_CODE))
			$ALIEXPRESS_CARRIER_NAME_2_CODE = array_flip( AliexpressInterface_Helper::getShippingCodeNameMap() );

		$JOBID = $CACHE['JOBID'];
		$rtn = array();
		$rtn['success'] = false;
		$Ext_Call='Tracking.SmtTrack';
		$CACHE['Ext_Call'] = $Ext_Call;
		$suspectReturn=false;
		//把 Singorpre Post 之类的，变成速卖通接口要求的 SGP
		if (!empty($ALIEXPRESS_CARRIER_NAME_2_CODE[$serviceName]))
			$serviceName = $ALIEXPRESS_CARRIER_NAME_2_CODE[$serviceName];
		
		try{
			//echo "Try to query aliexpress api :$selleruserid, $serviceName, $logisticsNo, $toArea,$orderId <br> ";
			$smtReturn = ListingAliexpressApiHelper::queryTrackingResult($selleruserid, $serviceName, $logisticsNo, $toArea,$orderId);
		}catch (\Exception $e) {
			$rtn['success'] = false;
			$rtn['message'] = $e->getMessage();
			return $rtn;
		}
		
		if (!empty($smtReturn['details'])){

			$aTracking = new Tracking();
			$aTracking->track_no = $logisticsNo;
			$TrackingClass = $aTracking;
			$aTracking->from_nation = 'CN';
			$aTracking->to_nation = $toArea;

			$aTracking->parcel_type = 0;
			$aTracking->status = Tracking::getSysStatus("运输途中");
			
			$aTracking->total_days = -1;
			
			$allEvents = array();
			$previousEvent = null;
			$aTracking->first_event_date = '';
			$last_event_date ='';
			$aTracking->from_lang = 'zh-cn';
			$aTracking->to_lang ='en';
			$seeDeliveryWord = false;
			$seeDeliveryWordDate = "";
			$LatestDaoDaDaiQu = "";
			$LatestShipping='';
			foreach ($smtReturn['details'] as $anEvent){
				if (!isset($anEvent['eventDate']) or !isset($anEvent['eventDesc'])) 
					continue;		

				//删除废话 shenzhen  等待收件
				if (stripos( ($anEvent['eventDesc']),"shenzhen") !== false   and
					stripos( ($anEvent['eventDesc']),"等待收件") !== false )
					continue;
				
				$newEvent = array();
				$newEvent['when'] = date('Y-m-d',$anEvent['eventDate']) ;
				$newEvent['where'] = base64_encode( '');
				$newEvent['what'] = base64_encode( $anEvent['eventDesc']);
				$newEvent['lang'] = empty($aTracking->to_lang)? $aTracking->from_lang : $aTracking->to_lang;
				$newEvent['type'] = 'toNation';
					
				if ( isset($anEvent['status']) and (trim($anEvent['status']))=='delivery' ){
					$aTracking->status = Tracking::getSysStatus("成功签收");
				}
					
				//如果之前的event和这个一样，就不要save这个了，重复的不要
				if (isset($previousEvent['when']) and $previousEvent['when'] == $newEvent['when'] and
				isset($previousEvent['where']) and $previousEvent['where'] == $newEvent['where'] and
				isset($previousEvent['what']) and $previousEvent['what'] == $newEvent['what'] )
					continue;
			
				$previousEvent = $newEvent;
				$allEvents[] = $newEvent;
			
				if ($newEvent['when']<>'' and $aTracking->first_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] < $aTracking->first_event_date )
					$aTracking->first_event_date = $newEvent['when'];
					
				if ($newEvent['when']<>'' and $last_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] >$last_event_date )
					$last_event_date = $newEvent['when'];
				
				if (stripos( ($anEvent['eventDesc']),"delivered") !== false
				or stripos( ($anEvent['eventDesc']),"CONSEGNATA") !== false
				or stripos( ($anEvent['eventDesc']),"ENTREGADO") !== false
				or stripos(  ($anEvent['eventDesc']),"Вручен") !== false
				or stripos(  ($anEvent['eventDesc']),"bручен") !== false
				or stripos( ($anEvent['eventDesc']),"签收") !== false
				or stripos( ($anEvent['eventDesc']),"妥投") !== false
				or stripos( ($anEvent['eventDesc']),"delivery to the addressee") !== false
				or stripos( ($anEvent['eventDesc']),"entregue") !== false
				or stripos( ($anEvent['eventDesc']),"successful") !== false and stripos( ($anEvent['eventDesc']),"unsuccessful") === false
				or stripos( ($anEvent['eventDesc']),"Properly cast") !== false
				or stripos( ($anEvent['eventDesc']),"Left with individual") !== false
				or stripos( ($anEvent['eventDesc']),"Signed for") !== false
				or stripos( ($anEvent['eventDesc']),"Signed by") !== false
				or stripos( ($anEvent['eventDesc']),"Sign for") !== false
				or stripos( ($anEvent['eventDesc']),"Sign by") !== false
				or stripos( ($anEvent['eventDesc']),"To the addressee by a courier") !== false
				
					 ){
					$aTracking->status = Tracking::getSysStatus("成功签收");
				}
				
				//case 1.1, 尝试找一下 】 这个后面的，如果就只有一个 Delivery这样的单词，也签收了
				$pos = strrpos( $anEvent['eventDesc'],"】");
				if ( $pos !== false and strlen($anEvent['eventDesc']) > $pos){
					$nakedMsg = substr($anEvent['eventDesc'],$pos+strlen("】"));
					if (trim(strtolower(($nakedMsg)))=='delivery' )
						$aTracking->status = Tracking::getSysStatus("成功签收");
				}
				
				//case 1.2, 尝试找一下 ] 这个后面的，如果就只有一个 Delivery这样的单词，也签收了
				$pos = strrpos( ($anEvent['eventDesc']),"]");
				if ( $pos !== false and strlen($anEvent['eventDesc']) > $pos){
					$nakedMsg = substr($anEvent['eventDesc'],$pos+strlen("]"));
					if (trim(($nakedMsg))=='delivery' )
						$aTracking->status = Tracking::getSysStatus("成功签收");
				}
				
				if ($aTracking->status <> Tracking::getSysStatus("成功签收")
				and $aTracking->status <> Tracking::getSysStatus("投递失败")
				and ( stripos( ($anEvent['eventDesc']),"cannot") !== false
						or stripos( ($anEvent['eventDesc']),"can not") !== false
						or stripos( ($anEvent['eventDesc']),"fail to") !== false
						or stripos( ($anEvent['eventDesc']),"failed to") !== false
						or stripos( ($anEvent['eventDesc']),"delivery fail") !== false
						or stripos( ($anEvent['eventDesc']),"fetch") !== false
						or stripos( ($anEvent['eventDesc']),"notified") !== false
						or stripos( ($anEvent['eventDesc']),"notifi") !== false
						or stripos( ($anEvent['eventDesc']),"pickup station") !== false
						or stripos( ($anEvent['eventDesc']),"pick-up station") !== false
						or stripos( ($anEvent['eventDesc']),"locker") !== false
						or stripos( ($anEvent['eventDesc'])," put ") !== false
						or stripos( ($anEvent['eventDesc']),"notice") !== false
						and ! stripos( ($anEvent['eventDesc']),"forward") !== false
						or stripos( ($anEvent['eventDesc']),"wait") !== false
						and  stripos( ($anEvent['eventDesc']),"consignee") !== false
						or stripos( ($anEvent['eventDesc']),"unsuccessful") !== false    
				
						//参考17track资料
				or stripos( ($anEvent['eventDesc']),"Redirected") !== false
				or stripos( ($anEvent['eventDesc']),"Undelivered as addressed") !== false
				or stripos( ($anEvent['eventDesc'])," not present") !== false
				or stripos( ($anEvent['eventDesc'])," not properly cast") !== false
				or stripos( ($anEvent['eventDesc']),"Attempted Delivery") !== false
				or stripos( ($anEvent['eventDesc']),"Failed Delivery") !== false
				or stripos( ($anEvent['eventDesc'])," Delivery failure") !== false
				or stripos( ($anEvent['eventDesc'])," Item being held") !== false
				or stripos( ($anEvent['eventDesc']),"  not delivered") !== false
				or stripos( ($anEvent['eventDesc']),"Available to collect") !== false
				or stripos( ($anEvent['eventDesc'])," Unsuccessful delivery attempt") !== false
				or stripos( ($anEvent['eventDesc']),"address not correct") !== false
				or stripos( ($anEvent['eventDesc']),"address incorrect") !== false
				or stripos( ($anEvent['eventDesc']),"An attempt to deliver your item") !== false
				or stripos( ($anEvent['eventDesc']),"no access to ") !== false
				or stripos( ($anEvent['eventDesc']),"address") !== false and stripos( ($anEvent['eventDesc']),"incorrect") !== false
			    or stripos( ($anEvent['eventDesc']),"address") !== false and stripos( ($anEvent['eventDesc']),"not correct") !== false
				or stripos( ($anEvent['eventDesc']),"address") !== false and stripos( ($anEvent['eventDesc']),"illegible") !== false
				or stripos( ($anEvent['eventDesc']),"address") !== false and stripos( ($anEvent['eventDesc']),"incomplete ") !== false
						
				or stripos( ($anEvent['eventDesc']),"Impossible to") !== false
				or stripos( ($anEvent['eventDesc']),"addressee does not") !== false
				or stripos( ($anEvent['eventDesc']),"Notice left") !== false
				or stripos( ($anEvent['eventDesc']),"Unsuccessful deliver") !== false
				or stripos( ($anEvent['eventDesc']),"Addressee advised to") !== false
				or stripos( ($anEvent['eventDesc']),"Refusal") !== false
				or stripos( ($anEvent['eventDesc']),"cannot be located") !== false
						
		 			)
				){
					$aTracking->status = Tracking::getSysStatus("投递失败");
				}
				
				
				if ($aTracking->status <> Tracking::getSysStatus("成功签收")
				    and $aTracking->status <> Tracking::getSysStatus("投递失败")
				    and (   //参考17track资料
							 stripos( ($anEvent['eventDesc'])," at facility") !== false
				    		and  ! stripos( ($anEvent['eventDesc']),"country") !== false
							or stripos( ($anEvent['eventDesc']),"arrive at post office") !== false
				    		or stripos( ($anEvent['eventDesc']),"arrival at post office") !== false
				    		or stripos( ($anEvent['eventDesc']),"arrived at post office") !== false
							or stripos( ($anEvent['eventDesc']),"Out for delivery") !== false
							or stripos( ($anEvent['eventDesc'])," To Office") !== false
							or stripos( ($anEvent['eventDesc'])," at delivery depot") !== false
							or stripos( ($anEvent['eventDesc']),"In the delivery process") !== false
							or stripos( ($anEvent['eventDesc']),"Out for physical delivery") !== false
							or stripos( ($anEvent['eventDesc'])," at Pick-Up Point") !== false
							or stripos( ($anEvent['eventDesc']),"Submitted for delivery") !== false
							or stripos( ($anEvent['eventDesc']),"Delivery scheduled") !== false
							or stripos( ($anEvent['eventDesc']),"Recipient advised to pick up the item") !== false
							or stripos( ($anEvent['eventDesc']),"Notification to rec") !== false
							or stripos( ($anEvent['eventDesc']),"Poste Restante") !== false
							or stripos( ($anEvent['eventDesc']),"Delivery in process") !== false
							or stripos( ($anEvent['eventDesc']),"at the place of delivery") !== false
							or stripos( ($anEvent['eventDesc']),"parcel delivery centre") !== false
							or stripos( ($anEvent['eventDesc']),"at delivery office") !== false
							//or stripos( ($anEvent['eventDesc']),"destination post") !== false
							or stripos( ($anEvent['eventDesc']),"postal office") !== false
				    		or stripos( ($anEvent['eventDesc']),"will be delivered in the coming days") !== false
				    		
				    	)	
				){
					$aTracking->status = Tracking::getSysStatus("到达待取");
					if ($LatestDaoDaDaiQu < $newEvent['when'])
						$LatestDaoDaDaiQu = $newEvent['when'];
				}
				
				if (stripos( ($anEvent['eventDesc']),"Depart") !== false or 
					stripos( ($anEvent['eventDesc']),"processing") !== false or 
					stripos( ($anEvent['eventDesc']),"center") !== false or 
					stripos( ($anEvent['eventDesc']),"sorting") !== false
				){
					if ($LatestShipping < $newEvent['when'])
						$LatestShipping = $newEvent['when'];
				}
				
				
				//倒数3天看看，如果还是没有新日期的追踪信息，就算他是 到达待取了
				if ($aTracking->status <> Tracking::getSysStatus("成功签收")
					and $aTracking->status <> Tracking::getSysStatus("异常退回")
				    and ( stripos( ($anEvent['eventDesc']),"arrival") !== false
				    		and ! stripos( ($anEvent['eventDesc']),"office") !== false
				    		and ! stripos( ($anEvent['eventDesc']),"post") !== false
						or stripos( ($anEvent['eventDesc']),"arrive") !== false
				    	   and ! stripos( ($anEvent['eventDesc']),"office") !== false
				    	or stripos( ($anEvent['eventDesc']),"attempt") !== false
						or stripos( ($anEvent['eventDesc']),"office") !== false
				    		and (stripos( ($anEvent['eventDesc']),"wait") !== false 
				    			 or stripos( ($anEvent['eventDesc']),"pick") !== false				 
				    			 or stripos( ($anEvent['eventDesc']),"put") !== false)
				    	or stripos( ($anEvent['eventDesc']),"wait") !== false 
				    			 and ( stripos( ($anEvent['eventDesc']),"consignee") !== false				 
				    			       or stripos( ($anEvent['eventDesc']),"customer") !== false)	
					    or stripos( ($anEvent['eventDesc']),"agent") !== false
					    or stripos( ($anEvent['eventDesc']),"left to") !== false
					    or stripos( ($anEvent['eventDesc']),"leave a") !== false
				    	or stripos( ($anEvent['eventDesc']),"undeliverable") !== false
				    	or stripos( ($anEvent['eventDesc']),"delivery office") !== false //孟加拉国 马尔代夫喜欢这样写
				    		
				    ) and ! stripos( ($anEvent['eventDesc']),"inward") !== false
				      and ! stripos( ($anEvent['eventDesc']),"exchange") !== false
				      and ! stripos( ($anEvent['eventDesc']),"custom") !== false
				      and ! stripos( ($anEvent['eventDesc']),"territory") !== false
				      and ! stripos( ($anEvent['eventDesc']),"russian") !== false
				      and ! stripos( ($anEvent['eventDesc']),"federation") !== false
				      and ! stripos( ($anEvent['eventDesc']),"country") !== false
				      and ! stripos( ($anEvent['eventDesc']),"china") !== false
				      and ! stripos( ($anEvent['eventDesc']),"shenzhen") !== false
				      and ! stripos( ($anEvent['eventDesc']),"export") !== false
				      and ! stripos( ($anEvent['eventDesc']),"sorting") !== false
				      and ! stripos( ($anEvent['eventDesc']),"process center") !== false
				      and ! stripos( ($anEvent['eventDesc']),"processing center") !== false
				      and ! stripos( ($anEvent['eventDesc']),"destination") !== false
				      and ! stripos( ($anEvent['eventDesc']),"airport") !== false
				      and ! stripos( ($anEvent['eventDesc']),"delay") !== false
				      and ! stripos( ($anEvent['eventDesc']),"facility") !== false
				      and ! stripos( ($anEvent['eventDesc']),"origin") !== false
				      and ! stripos( ($anEvent['eventDesc']),"arrange") !== false
				      and ! stripos( ($anEvent['eventDesc']),"depart") !== false
				      and ! stripos( ($anEvent['eventDesc']),"try ") !== false
				      and ! stripos( ($anEvent['eventDesc']),"chile") !== false
				      and ! stripos( ($anEvent['eventDesc']),"line") !== false
					 ){
					$seeDeliveryWord = true;
					if ($seeDeliveryWordDate < $newEvent['when'])
						$seeDeliveryWordDate = $newEvent['when'];
				}
				
				if (( stripos( ($anEvent['eventDesc']),"cancel") !== false
					  or stripos( ($anEvent['eventDesc']),"deleted") !== false
					  or stripos( ($anEvent['eventDesc']),"returned") !== false
						 and stripos( ($anEvent['eventDesc']),"sender") !== false
					  or stripos( ($anEvent['eventDesc']),"overweight") !== false
					  or stripos( ($anEvent['eventDesc']),"inbound fail") !== false
						//下面你是17track的参考
						or stripos( ($anEvent['eventDesc']),"x-ray") !== false
						or stripos( ($anEvent['eventDesc']),"not allowed") !== false
						or stripos( ($anEvent['eventDesc']),"has imported opening") !== false
						or stripos( ($anEvent['eventDesc']),"Item refused by") !== false
						or stripos( ($anEvent['eventDesc']),"Missed delivery") !== false
						or stripos( ($anEvent['eventDesc']),"Being returned") !== false
						or stripos( ($anEvent['eventDesc']),"Exception") !== false
						or stripos( ($anEvent['eventDesc']),"unclaimed") !== false
						or stripos( ($anEvent['eventDesc']),"Prohibited") !== false
				
						
				)
				){
					//$aTracking->status = Tracking::getSysStatus("异常退回");
					$suspectReturn = true;
				}

			}//end of each event
			
			if ($aTracking->status == Tracking::getSysStatus("到达待取") and $LatestShipping > $LatestDaoDaDaiQu)
				$aTracking->status = Tracking::getSysStatus("运输途中"); 
			
			if ($aTracking->status <> Tracking::getSysStatus("成功签收") and $suspectReturn)
				$aTracking->status = Tracking::getSysStatus("异常退回");
			
			if ($aTracking->status == Tracking::getSysStatus("运输途中")  and 
			    $seeDeliveryWord==true and !empty($last_event_date) and  $last_event_date==$seeDeliveryWordDate ){	
				//计算最后日期和现在是否相隔3天，如果是，判断为 到达待取
				$datetime2 = date_create($last_event_date);
				$datetime1 = date_create(date('Y-m-d H:i:s'));
				$interval = date_diff($datetime1, $datetime2);
				if ($interval->days >= 8)
					$aTracking->status = Tracking::getSysStatus("到达待取");
			}
	 
			$aTracking->all_event = json_encode($allEvents);
			$aTracking->carrier_type = 0;
			//calculate the total days, if returned value is -1
			if ($aTracking->total_days <= 0 and $aTracking->first_event_date <>'' and strlen($aTracking->first_event_date)>=10){
				if ($aTracking->status == Tracking::getSysStatus("成功签收") and !empty($last_event_date) )
					//使用最后一条记录时间作为签收时间
					$datetime1 = date_create($last_event_date);
				else //使用当前时间
					$datetime1 = date_create(date('Y-m-d H:i:s'));
					
				$datetime2 = date_create(substr($aTracking->first_event_date, 0,10));
				$interval = date_diff($datetime1, $datetime2);
				$aTracking->total_days = $interval->days;
			}
			$aTracking->last_event_date = $last_event_date;
			
			$rtn['parsedResult'] = $aTracking->getAttributes();
			$rtn['success'] = true;
		}
		$rtn['smtReturn'] = $smtReturn;
		
		return $rtn;
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Call Or Proxy to do 17Track query, by http.
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  $pendingSubOne_array         [$model_subQueue1, $model_subQueue2 ,...]
	 * @param  $original_status_array  		['RN342w342CN'=>'shipping','RN22222CN'=>'pending_for_fetch',...]
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'result'='',run_time=8,sub_queue_status=>C)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/11/20      		初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	static public function query17TrackProxy($pendingSubOne_array, $original_status_array){
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$rtn = array();
		$rtn['success'] = true;
		/*
		['results_for_track_no'][$trackingNo]   (string)
		['sub_queue_status'][$trackingNo]    (string)
		['fc'][$trackingNo]
		['yt'][$trackingNo]
		['run_time']
		['proxy_call']
		*/
		$Ext_Call='Tracking.17Track';
		$CACHE['Ext_Call'] = $Ext_Call;
		// TODO proxy host
		$TRACK_PROXY_URL = "http://localhost/17tracker_proxy/ApiEntryV5.php?token=HEHEyssdfWERSDF,werSDFJIYfghg,ddctYAYA&track_code=";
		
		//开始通过Proxy 查询这个请求的track code + carrier type
		$current_time=explode(" ",microtime());				
		$time1=round($current_time[0]*1000+$current_time[1]*1000);
        $usingURL = '';
        
        $bulkSendTrackingNos ='';
        $bulkSendFc ='';
        $bulkSendSc ='';
        $track_no_array =array();
        $carrier_type_array = array();
        $using_Foreign_Track_code_map_ForeignVsOrig = [];
        $using_Foreign_Track_code_map_OrigVsForeign = [];
        //step 1，看看有多少一次过要处理的，make url 需要的 物流号，sc，fc 逗号隔开
        foreach ($pendingSubOne_array as $pendingSubOne){
        	$addinfo_str = $pendingSubOne->addinfo;
        	if (!empty($addinfo_str))
        		$addinfo = json_decode($addinfo_str,true);
        	else
        		$addinfo = [];
        	//$addinfo['return_no'] may contain the foreign track no for this 万邑通
        	
        	if (!empty($addinfo['return_no'])){
        		$trackingNo = $addinfo['return_no'];
        		$using_Foreign_Track_code_map_ForeignVsOrig[ $addinfo['return_no'] ] = $pendingSubOne->track_no ;
        		$using_Foreign_Track_code_map_OrigVsForeign[$pendingSubOne->track_no ] = $trackingNo;
        	}else
        		$trackingNo = $pendingSubOne->track_no;
        	
        	$track_no_array[] = $trackingNo;
        	$bulkSendTrackingNos .= ($bulkSendTrackingNos==''?'':',').$trackingNo;
        	//$bulkSendFc .= ($bulkSendFc==''?'':',').$pendingSubOne->carrier_type;
        	
        	$carrier_type_array[$trackingNo] = $pendingSubOne->carrier_type;
        	
        	// 0 is default Second Carrier code, if not returned by previous YT, it should be 0
        	$fc = $pendingSubOne->carrier_type;
        	$sc ='0';
        	//echo "Try to do this, see yt is ".print_r($addinfo,true)."\n"; //ystest
        	if (isset($addinfo['yt']['d'])){
        		$tried_yt = (isset($addinfo['tried_yt'])?$addinfo['tried_yt'] : []);
        		foreach ($addinfo['yt']['d'] as $aYtCode){
        			if (!isset($tried_yt[ 'tried_'.$aYtCode ]))
        				$fc = $aYtCode;
        		}//end of each ytCode	
        	}
        	
        	$bulkSendFc .= ($bulkSendFc==''?'':',').$fc; 
        	$bulkSendSc .= ($bulkSendSc==''?'':',').$sc;
        	$rtn['fc'][ $pendingSubOne->track_no ] = $fc;
        }//end of each sub Task

        //step 2: call 17Track V5 接口，同时处理多大40个物流号
		$start1_time=round($current_time[0]*1000+$current_time[1]*1000);
		$time1=round($current_time[0]*1000+$current_time[1]*1000);

     	if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub 17Agent 17.2 subjobid=$JOBID,track_no=$trackingNo" );

     	$usingURL = $TRACK_PROXY_URL.$bulkSendTrackingNos."&fc=".$bulkSendFc."&sc=".$bulkSendSc;
	     $url_result = self::getHttpByCurl($usingURL, count($pendingSubOne_array));
	     
	     $rtn['proxy_call'] = $usingURL;
     
     if (TrackingHelper::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub 17Agent 17.3 subjobid=$JOBID,track_no=$trackingNo" );
		$resultStr = $url_result['content'];

		//如果17Track 返回结果为空，尝试在玩一次
		if(empty($resultStr)) {
			//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"Query 17Track Proxy $url" ],"edb\global");
			$url_result = self::getHttpByCurl($usingURL, count($pendingSubOne_array));
			$resultStr = $url_result['content'];
		}
		
	     if (TrackingHelper::$TRACKER_FILE_LOG)
			\Yii::info("multiple_process_sub 17Agent 17.3b subjobid=$JOBID,track_no=$trackingNo" );
		$results = json_decode($resultStr,true);
		if (empty($results)) $results = array();
 
		$run_time = $url_result['run_time'];
		
		
		//step 3，把返回的东西，解析一下
		$parse17TrackResult_array = self::parse17TrackResult($resultStr,$track_no_array,$carrier_type_array);
		/*$rtn[success]  , $rtn[message]
		 *$rtn['individualResults'][$track_no]['resultStr'] = string
		* $rtn['individualResults'][$track_no]['parsedResult'] = array of tracking attributes
		* $rtn['individualResults'][$track_no]['success'] = true / false
		* $rtn['individualResults'][$track_no]['message'] = string
		* */
		
		//echo "Try to parse result \n $resultStr \n Got ".print_r($parse17TrackResult_array,true); //ystest
		
		
		//step 4 从返回的内容中，分拣出每个物流号的，填入 return 的内容中
		/*['results_for_track_no'][$trackingNo]   (string)
		['sub_queue_status'][$trackingNo]    (string)
		['fc'][$trackingNo]
		['yt'][$trackingNo]
		 * */
		foreach ($pendingSubOne_array as $pendingSubOne){
			$trackingNo = $pendingSubOne->track_no;
			
			//如果这个内部物流号有对应的外部 return no的，用外部的来查询并且 处理结果
			if (!empty($using_Foreign_Track_code_map_OrigVsForeign[$trackingNo]))
				$trackingNo = $using_Foreign_Track_code_map_OrigVsForeign[$trackingNo];
			
			if (!empty($parse17TrackResult_array['individualResults'][strtoupper($trackingNo)]))
				$aResult = $parse17TrackResult_array['individualResults'][strtoupper($trackingNo)];
			else 
				$aResult = null;
			
			
			if ($pendingSubOne->track_no =="rm909065136cn"){
			 
				SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($trackingNo,"YSFeb10: doing this 1."));
				$pendingSubOne->result = $resultStr;
				$ad1 = json_decode($pendingSubOne->addinfo,true);
					
				$ad1['aResult'] = $aResult;
				$ad1['parsedResult'] = $parse17TrackResult_array;
				
				$pendingSubOne->addinfo = json_encode($ad1);
				$pendingSubOne->save(false);
			}
			
			$pendingSubOne->result = $aResult['resultStr'];
			
			if ($pendingSubOne->track_no =="rm909065136cn")
			SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($trackingNo,"YSFeb10: doing this 2."));
			
			if (!isset($aResult['success']) or !$aResult['success']){
				$rtn['sub_queue_status'][$pendingSubOne->track_no] = 'F';
				//echo "sub Status got Fail due to reason 1.\n";
			//	echo print_r($aResult,true)."\n";
			}else{
				if (!empty($aResult['parsedResult']['first_event_date']) and $aResult['parsedResult']['first_event_date'] !='0000-00-00 00:00:00')
					$rtn['sub_queue_status'][$pendingSubOne->track_no] = 'C';
				else
					$rtn['sub_queue_status'][$pendingSubOne->track_no] = 'F';
				//	echo "sub Status got Fail due to reason 2.\n";
			}
			
			//echo "Got result :\n ".print_r($aResult,true)."\n"; //ystest
			if ($pendingSubOne->track_no =="RS370933807CN")
			SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($trackingNo,"YSFeb10: doing this 2.1"));
				
			$rtn['results_for_track_no'][$pendingSubOne->track_no] = $pendingSubOne->result;
			if ($pendingSubOne->track_no =="RS370933807CN")
			SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($trackingNo,"YSFeb10: doing this 2.2"));
			
			if (isset($aResult['yt']) and is_string($aResult['yt']))
				$aResult['yt'] = json_decode($aResult['yt'],true );
			
			if ($pendingSubOne->track_no =="RS370933807CN")
			SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($trackingNo,"YSFeb10: doing this 2.3"));
			
			$rtn['yt'][$pendingSubOne->track_no] = (isset($aResult['yt']) )? $aResult['yt']: array() ;
			
			if ($pendingSubOne->track_no =="RS370933807CN")
			SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($trackingNo,"YSFeb10: doing this 2.4"));
	
		
			if ($pendingSubOne->track_no =="RS370933807CN")
			SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($trackingNo,"YSFeb10: doing this 3.". (isset($rtn['sub_queue_status'][$pendingSubOne->track_no])?$rtn['sub_queue_status'][$pendingSubOne->track_no]:"NULL")));
			
			//2017-1-12 yzq: only when it is success, write the result to redis, otherwise, not to write
			if ($rtn['sub_queue_status'][$pendingSubOne->track_no] == 'C'){
				$hkey= "ResultFor_".$pendingSubOne->track_no ."_". $pendingSubOne->carrier_type;
				$hvalue =  json_encode($aResult);
				
				if ($pendingSubOne->track_no =="RS370933807CN") 
				SysLogHelper::InvokeJrn_Create("Tracker",__CLASS__, __FUNCTION__ , array($trackingNo, "YSFeb10: doing this 4. write redis Hset $hkey = ",$hvalue));			
				
				if (RedisHelper::RedisSet("Tracker_AppTempData",$hkey , ($hvalue) ) < 0 ){ //base64_encode
					echo "Exception Y001:Failed to set to redis. for ".$pendingSubOne->track_no."\n";
				}
			}
		
		}//end of each sub queue task
		
		$rtn['run_time'] = $run_time;

     if (TrackingHelper::$TRACKER_FILE_LOG)
		\Yii::info("multiple_process_sub 17Agent 17.Ret subjobid=$JOBID,track_no=$trackingNo" );
     
    $rtn['proxy_call'] = $usingURL; 
    /* 需要return 
    ['results_for_track_no'][$trackingNo]   (string)
    ['sub_queue_status'][$trackingNo]    (string)
    ['fc'][$trackingNo]
    ['yt'][$trackingNo]
    ['run_time']
    ['proxy_call']
    */
	return $rtn;
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 * 解释17Track返回的东东
	 * 如果觉得返回结果可以，就call commitTrackingResultUsingValue 写到数据库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  proxy_return_message         proxy return message, contains 17Track返回的信息 
	 * @param  track_no_array               array('RN2323CN','RN3454234CN')
	 * @param  carrier_type_array           array('RN2323CN'=>0,'RN3454234CN'=>19002)
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='','parsedResult'=>Tracking model->getAttributes)
	 *
	 * @invoking					TrackingHelper::parse17TrackResult();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function parse17TrackResult($proxy_return_message, $track_no_array,$carrier_type_array=array()){				
		/*(1)->查询成功, (0)->接口访问错误, (-4)->接口访问错误, (-5)->超出访问限制, 
		 * (-6)->单号数量超出限制40个, (-7)->请求参数有误, (-8)->客户端没有授权*/
	
		
		$track_result_map = array ("0"=>"接口访问错误","1"=>"查询成功", "3"=>"系统更新", "4"=>"接口访问错误",
				 	     "5"=>"超出访问限制","6"=>"单号数量超出限制40个","7"=>"请求参数有误","8"=>"客户端没有授权");
		$rtn['parsedResult'] = array();
		$rtn['message'] = "";
		$rtn['success'] = true; 
		$rtn['errorCode'] = 0;
		$rtn['action'] = '';
		
		$now_str = date('Y-m-d H:i:s');			
		$result_proxy = json_decode($proxy_return_message,true);
		if (empty($result_proxy)) $result_proxy = array();
		
		//先从proxy返回的message中，获取proxy return success 以及17Track Return message
		if (empty($result_proxy['success']) or $result_proxy['success'] == false or empty($result_proxy['rtn'])){
			$message = "ETRK016: Tracking Proxy 返回结果格式里面没有成功，退出".",请联系17Track";
			\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			$rtn['message'] = $message;
			$rtn['success'] = false;
			$rtn['errorCode'] = -998;
			return $rtn;
		}
		
		
		//$result_proxy['rtn']可能前面 夹渣了垃圾，例如 邮编的 ， 所以加入逻辑，要 { 以后的东西就可
		//"37b\r\n{\"ret\":1,\"msg\"
		$validStartOffset = strpos($result_proxy['rtn'], "{",0) ;
		$lastOffset = strripos($result_proxy['rtn'], "}");
		if ( $validStartOffset > 0 and $lastOffset > 0 )
			$result_proxy['rtn'] = substr($result_proxy['rtn'],$validStartOffset,$lastOffset - $validStartOffset + 1);
		
		$result = json_decode($result_proxy['rtn'],true);
		if (empty($result)) $result = array();
		
		/*$proxy_return_message sample data:
		"ret": 1,
    	"msg": "Ok",
    	"dat": [
        {
            "no": "RG193205979CN",
            "delay": 0,
            "yt": null,
            "track": {
                "w1": 3011,
                "w2": 6051,
                "c": 605,
                "b": 301,		 * 
			}
		}
		{
            "no": "RG223205979CN",
            "delay": 0,
            "yt": null,
            "track": {
                "w1": 3011,
                "w2": 6051,
                "c": 605,
                "b": 301,		 * 
			}
		}
		]
		 * */
		if (!isset($result['ret'])){
				$message = "ETRK010: 17Track 没有返回return code";
				\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
				$rtn['message'] = $message;
				$rtn['success'] = false;
				$rtn['errorCode'] = -4;
				//echo $message . print_r($result,true)."\n";
				//echo "proxy returns original:".print_r($result_proxy['rtn'],true)."|end.\n";
				return $rtn;
		}
			
		$returnCode_17Trk = abs(intval($result['ret']));
		if ( $returnCode_17Trk <>1 ){
			$message = "ETRK019: 17Track 返回不成功";
			if (isset($track_result_map[strval($returnCode_17Trk)]))
				$message.= $track_result_map[strval($returnCode_17Trk)];
			
			\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			$rtn['message'] = $message;
			$rtn['success'] = false;
			$rtn['errorCode'] = -5;
			return $rtn;
		}

		//如果返回没有期望的格式，就错误退出罗
		if (!isset($result['dat'])){
			$message = "ETRK013: 17Track返回结果格式里面没有dat，退出".",请联系17Track ".$track_no_original;
			\Yii::error(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			$rtn['message'] = $message;
			$rtn['success'] = false;
			$rtn['errorCode'] = -999;
			return $rtn;
		}
			
		$datas = $result['dat']; 
		//这个datas包含了好多个物流号的结果，需要一个一个分析 	
		foreach ($datas as $data){
			$tempReturn = self::parseEach17TrkResult($data, '' ,$carrier_type_array);
			$track_no = $tempReturn['track_no'];
			$rtn['individualResults'][$track_no] = $tempReturn;
			$rtn['individualResults'][$track_no]['resultStr'] = json_encode($data);
			/*Sample for each resutl
			 *  $rtn['parsedResult'] = array();
				$rtn['message'] = "";
				$rtn['success'] = true;
				$rtn['yt'] = array();
			*/
			
		}//end of each data returned by 17 trk
		return $rtn;	

		/*
		 * $rtn['individualResults'][$track_no]['resultStr'] = string
		 * $rtn['individualResults'][$track_no]['parsedResult'] = array of tracking attributes
		 * $rtn['individualResults'][$track_no]['success'] = true / false
		 * $rtn['individualResults'][$track_no]['message'] = string
		 * */
	}//end of parse17TrackResult

	
	static private function parseEach17TrkResult($data , $track_no, $carrier_type_array=array() ){
		$rtn['parsedResult'] = array();
		$rtn['message'] = "";
		$rtn['success'] = true;
		$rtn['yt'] = array();
		
		if ( !isset($data['no']) or  isset($data['no']) and $track_no <> $data['no']){
			$track_no = $data['no'];
		}
		
		$rtn['track_no'] = $track_no;
		
		$aTracking = new Tracking();
		$aTracking->track_no = $track_no;
		
		//step 1, 如果返回有多种可能性，yt 不是空，就要退出处理了;
		if ( empty($data['yt']) or is_string($data['yt']) and $data['yt']=='null' ){
			//那就是没有问题了
		}else{			
			$rtn['yt'] = $data['yt'];
			return $rtn;
		}
		
		//step 2, 检查是否17Track 得到了第三方的api 错误
		
		if (empty($data['track']) or !isset($data['track']['b']) or !isset($data['track']['c']) or !isset($data['track']['e'])){
			$message = "ETRK011: 返回的内容 track 里面为空 $track_no ";
			if (!isset($data['track']['b']))
				$message .= "empty b.";
			if (!isset($data['track']['c']))
				$message .= "empty c.";
			if (!isset($data['track']['e']))
				$message .= "empty e.";
			$rtn['message'] = $message;
			$rtn['success'] = false;
			return $rtn;
		}
		
		$data = $data['track'];
		
		//step 3， 正经一点了, parse the fields
		$fromNation_eng =  Tracking::get17TrackNationEnglish($data['b']=='' ? '0': $data['b']);
		$toNation_eng =  Tracking::get17TrackNationEnglish($data['c']=='' ? '0': $data['c']);
		
		$aTracking->from_lang = isset($data['ln1'])?$data['ln1']:'';//发件国家查询结果的语言
		$aTracking->to_lang = isset($data['ln2'])?$data['ln2']:'';//目的国家查询结果的语言
		
		if ($fromNation_eng == 'Hong Kong [CN]' or $fromNation_eng == 'Hong Kong CN')
			$fromNation_eng = 'Hong Kong';
		
		if ($toNation_eng == 'Hong Kong [CN]' or $toNation_eng == 'Hong Kong CN')
			$toNation_eng = 'Hong Kong';
		
		//use the english nation name to get contry code
		if (StandardConst::$COUNTRIES_NAME_EN_CODE == '')
			StandardConst::$COUNTRIES_NAME_EN_CODE = array_flip(StandardConst::$COUNTRIES_CODE_NAME_EN);
		
		$aTracking->from_nation = isset(StandardConst::$COUNTRIES_NAME_EN_CODE[$fromNation_eng])?StandardConst::$COUNTRIES_NAME_EN_CODE[$fromNation_eng]:"--"; //at most 2 char
		
		if ($aTracking->from_nation == '--'){
			$command = Yii::$app->db->createCommand("select country_code from sys_country
										where country_en =:engName");
			$command->bindValue(':engName', $fromNation_eng , \PDO::PARAM_STR);
			$countryCode = $command->queryScalar();
			if (!empty($countryCode))
				$aTracking->from_nation = $countryCode;
		}
		
		$aTracking->to_nation = isset(StandardConst::$COUNTRIES_NAME_EN_CODE[$toNation_eng])?StandardConst::$COUNTRIES_NAME_EN_CODE[$toNation_eng]:"--";   //at most 2 char
		if ($aTracking->to_nation == '--'){
			$command = Yii::$app->db->createCommand("select country_code from sys_country
										where country_en =:engName");
			$command->bindValue(':engName', $toNation_eng , \PDO::PARAM_STR);
			$countryCode = $command->queryScalar();
			if (!empty($countryCode))
				$aTracking->to_nation = $countryCode;
		}
		
		//如果17Track返回目标国家空白，使用订单的收件人国家
		if ($aTracking->to_nation =='--' or $aTracking->to_nation =='0' or $aTracking->to_nation==''){
			$aTracking1 = Tracking::find()
			->andWhere("track_no=:trackno",array(':trackno'=>$track_no) )
			->one();
			if ($aTracking1 <> null){
				$aTracking->to_nation = $aTracking->getConsignee_country_code();
			}
		}
		$carrier_type = isset($carrier_type_array[$track_no])?$carrier_type_array[$track_no] : '0' ;
		
		
		$aTracking->status = Tracking::getParcelStatusBy17TrackStatus($data['e']);
		$aTracking->total_days = $data['f'];
		//z1->发件国家事件集合(每条事件格式同z0)
		//z2->目的国家事件集合(每条事件格式同z0)
		$events_from_nation = isset($data['z1'])?$data['z1']:array();
		$events_to_nation = isset($data['z2'])?$data['z2']:array();
		
		$allEvents = array();
		$previousEvent = null;
		$aTracking->first_event_date = '';
		$aTracking->last_event_date = '';
		$second_event_date='';
		
		foreach ($events_to_nation as $anEvent){
			if (!isset($anEvent['a']) or !isset($anEvent['c']) or !isset($anEvent['d'])  or !isset($anEvent['z'])  )
				continue;
			
			$newEvent = array();
			$newEvent['when'] = $anEvent['a'];
			$newEvent['where'] = base64_encode( $anEvent['c'].(trim($anEvent['d'])==''?'':' ' ).$anEvent['d']);
			$newEvent['what'] = base64_encode( $anEvent['z']);
			$newEvent['lang'] = empty($aTracking->to_lang)? $aTracking->from_lang : $aTracking->to_lang;
			$newEvent['type'] = 'toNation';
				
			//如果之前的event和这个一样，就不要save这个了，重复的不要
			if (isset($previousEvent['when']) and $previousEvent['when'] == $newEvent['when'] and
			isset($previousEvent['where']) and $previousEvent['where'] == $newEvent['where'] and
			isset($previousEvent['what']) and $previousEvent['what'] == $newEvent['what'] )
				continue;
		
			$previousEvent = $newEvent;
			$allEvents[] = $newEvent;
				
			if ($newEvent['when']<>'' and $aTracking->first_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] < $aTracking->first_event_date ){
				$second_event_date = $aTracking->first_event_date;
				$aTracking->first_event_date = $newEvent['when'];
			}
				
			if ($newEvent['when']<>'' and $aTracking->last_event_date =='' or $newEvent['when']<>'' and $newEvent['when'] > $aTracking->last_event_date ){
				$aTracking->last_event_date = $newEvent['when'];
			}
		
		}//end of each event
		
		$hasFeiHua = false; //是否有燕文的废话，燕文 $carrier_type = 190012
		
		foreach ($events_from_nation as $anEvent){
			if (!isset($anEvent['a']) or !isset($anEvent['c']) or !isset($anEvent['d'])  or !isset($anEvent['z'])  )
				continue;
				
			
			if ($carrier_type == 190012 and !empty($anEvent['z']) and
			strpos( strtolower(  $anEvent['z']) ,'yanwen') !== false or
			strpos( strtolower(  $anEvent['z']) ,'yan wen') !== false
			)
				$hasFeiHua = true;
			$newEvent = array();
			$fullWhere = $anEvent['c'].(trim($anEvent['d'])==''?'':' ' ).$anEvent['d'];
			$newEvent['when'] = $anEvent['a'];
			$newEvent['where'] = base64_encode( $fullWhere ) ;
			$newEvent['what'] = base64_encode( $anEvent['z'] );
			$newEvent['lang'] = $aTracking->from_lang;
			$newEvent['type'] = 'fromNation';
				
			//如果没有声明from哪个国家，尝试从from nation event 那里去提取
			if ($aTracking->from_nation == '--'){
				if (strpos($anEvent['z'],'HK') >0 or strpos(strtolower($anEvent['z']),'hongkong') >0 or strpos(strtolower($anEvent['z']),'hong kong') >0 or
				strpos($fullWhere,'HK') >0 or strpos(strtolower($fullWhere),'hongkong') >0 or strpos(strtolower($fullWhere),'hong kong') >0)
					$aTracking->from_nation = "HK";
			}
			if ($aTracking->from_nation == '--'){
				if (strpos($anEvent['z'],'CN') >0 or strpos(strtolower($anEvent['z']),'china') >0 or strpos(strtolower($anEvent['z']),'dongguan') >0 or
				strpos($fullWhere,'CN') >0 or strpos(strtolower($fullWhere),'china') >0 or strpos(strtolower($fullWhere),'dongguan') >0)
					$aTracking->from_nation = "CN";
			}
				
			//如果之前的event和这个一样，就不要save这个了，重复的不要
			if (isset($previousEvent['when']) and $previousEvent['when'] == $newEvent['when'] and
			isset($previousEvent['where']) and $previousEvent['where'] == $newEvent['where'] and
			isset($previousEvent['what']) and $previousEvent['what'] == $newEvent['what']  )
				continue;
				
			$previousEvent = $newEvent;
			$allEvents[] = $newEvent;
				
			if ($newEvent['when']<>'' and $aTracking->first_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] < $aTracking->first_event_date ){
				$second_event_date = $aTracking->first_event_date;
				$aTracking->first_event_date = $newEvent['when'];
			}
				
			if ($newEvent['when']<>'' and $aTracking->last_event_date =='' or $newEvent['when']<>'' and $newEvent['when'] > $aTracking->last_event_date ){
				$aTracking->last_event_date = $newEvent['when'];
			}
				
		}//end of each event
		
		//如果是燕文的废话 而且只有1条，那么就是还没有查到结果
		if ($hasFeiHua and count($allEvents) == 1 ){
			$rtn['message'] = "燕文渠道返回只有Received Shippment请求，其实没有递送事件";
			$rtn['success'] = false;
			$rtn['errorCode'] = 5051;
			return $rtn;
		}
		
		$aTracking->all_event = json_encode($allEvents);
		
		//calculate first event day interval from second event day, if gap is 180 days.
		//it may be personal mistake, use second event day.
		if ($second_event_date <>'' and $aTracking->first_event_date<>''){
			$datetime1 = strtotime (substr($second_event_date, 0,10)." 00:00:00");
			$datetime2 = strtotime (substr($aTracking->first_event_date, 0,10)." 00:00:00");
			$days =ceil(($datetime1-$datetime2)/86400); //60s*60min*24h
			if (abs($days) > 120)
				$aTracking->first_event_date = $second_event_date;
		}
		
		$aTracking->to_nation = strtoupper($aTracking->to_nation);
		$rtn['parsedResult'] = $aTracking->getAttributes();
		
		return $rtn;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 解释Ubi返回的东东,其实已经好靓仔
	 * 如果觉得返回结果可以，就call commitTrackingResultUsingValue 写到数据库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  proxy_return_message         proxy return message, contains 17Track返回的信息
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='','parsedResult'=>Tracking model->getAttributes)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function parseUbiResult($proxy_return_message, $track_no_original=''){
		$rtn['parsedResult'] = array();
		$rtn['message'] = "";
		$rtn['success'] = true;
		$rtn['errorCode'] = 0;
	
		$now_str = date('Y-m-d H:i:s');
		$result_proxy = json_decode($proxy_return_message,true);
		if (empty($result_proxy)) $result_proxy = array();
 
		//先从proxy返回的message中，获取proxy return success 以及17Track Return message
		if (empty($result_proxy['countryJson'][0]['country'])   ){
			$message = "ETRK030: UBI Tracking Proxy 返回结果格式里面没有成功，退出".",请联系Ubi Support";
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			$rtn['message'] = $message;
			$rtn['success'] = false;
			$rtn['errorCode'] = -998;
			$rtn['return_message'] = $result_proxy;
			return $rtn;
		}
         $outputJson = $result_proxy['data'];
         
		$ubiArray['events'] = array();
		if ($outputJson==null){
			$message = "ETRK031: UBI 返回结果无法JsonDecode,请联系Ubi Support";
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			$rtn['message'] = $message;
			$rtn['success'] = false;
			$rtn['errorCode'] = -997;
			$rtn['return_message'] = $result_proxy;
			return $rtn;
		}else{
			$dataArr = $outputJson;
		 
			foreach($dataArr as $data){
				foreach ($data as $da){
				 
					$ubiArray['events'][]=array('who'=>"",'when'=>date('Y-m-d H:i',substr($da['eventTime'],0,strlen($da['eventTime'])-3)),
							'what'=>$da['activity'],'where'=>$da['location']);
				}
			}
		
			rsort($ubiArray['events']);
		
			if (count($ubiArray['events']) == 0){
				$ubiArray['success'] = false;
			}
		}
		
		$track_no = $track_no_original;
		$aTracking = new Tracking();
		$aTracking->track_no = $track_no;
		$TrackingClass = $aTracking;
		$aTracking->from_nation = 'CN';
		$aTracking->to_nation = $result_proxy['countryJson'][0]['country'];
		//$aTracking->from_nation = isset(StandardConst::$COUNTRIES_NAME_EN_CODE[$fromNation_eng])?StandardConst::$COUNTRIES_NAME_EN_CODE[$fromNation_eng]:"--"; //at most 2 char
		//$aTracking->to_nation = isset(StandardConst::$COUNTRIES_NAME_EN_CODE[$toNation_eng])?StandardConst::$COUNTRIES_NAME_EN_CODE[$toNation_eng]:"--";   //at most 2 char
		if (strlen($aTracking->to_nation) > 2)
			$aTracking->to_nation = "--";
		
		//如果17Track返回目标国家空白，使用订单的收件人国家
		if ($aTracking->to_nation =='--' or $aTracking->to_nation =='0' or $aTracking->to_nation==''){
			$aTracking1 = Tracking::find()
			->andWhere("track_no=:trackno",array(':trackno'=>$track_no) )
			->one();
			if ($aTracking1 <> null){
				$aTracking->to_nation = $aTracking->getConsignee_country_code();
			}
		}
	
		$aTracking->parcel_type = 0;
		$aTracking->status = Tracking::getSysStatus("运输途中");
		
		$aTracking->total_days = -1;
	
		$allEvents = array();
		$previousEvent = null;
		$aTracking->first_event_date = '';
		$last_event_date ='';
		$aTracking->from_lang = 'zh-cn';
		$aTracking->to_lang ='en';
		foreach ($ubiArray['events'] as $anEvent){
			$newEvent = array();
			$newEvent['when'] = $anEvent['when'];
			$newEvent['where'] = base64_encode( $anEvent['where']);
			$newEvent['what'] = base64_encode( $anEvent['what']);
			$newEvent['lang'] = empty($aTracking->to_lang)? $aTracking->from_lang : $aTracking->to_lang;
			$newEvent['type'] = 'toNation';
			
			if (strpos( strtolower($anEvent['what']),"delivered") !== false 
				or strpos( strtolower($anEvent['what']),"签收") !== false
				or strpos( strtoupper($anEvent['what']),"CONSEGNATA") !== false	){
				$aTracking->status = Tracking::getSysStatus("成功签收");
			}
			
			//如果之前的event和这个一样，就不要save这个了，重复的不要
			if (isset($previousEvent['when']) and $previousEvent['when'] == $newEvent['when'] and
			isset($previousEvent['where']) and $previousEvent['where'] == $newEvent['where'] and
			isset($previousEvent['what']) and $previousEvent['what'] == $newEvent['what'] )
				continue;
	
			$previousEvent = $newEvent;
			$allEvents[] = $newEvent;
				
			if ($newEvent['when']<>'' and $aTracking->first_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] < $aTracking->first_event_date )
				$aTracking->first_event_date = $newEvent['when'];
			
			if ($newEvent['when']<>'' and $last_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] >$last_event_date )
				$last_event_date = $newEvent['when'];
		}//end of each event

		$aTracking->all_event = json_encode($allEvents);
	
		//calculate the total days, if returned value is -1
		if ($aTracking->total_days <= 0 and $aTracking->first_event_date <>'' and strlen($aTracking->first_event_date)>=10){
			if ($aTracking->status == Tracking::getSysStatus("成功签收") and !empty($last_event_date) )
				//使用最后一条记录时间作为签收时间
				$datetime1 = date_create($last_event_date);
				else //使用当前时间
				$datetime1 = date_create(date('Y-m-d H:i:s'));
			
			$datetime2 = date_create(substr($aTracking->first_event_date, 0,10));
			$interval = date_diff($datetime1, $datetime2);
			$aTracking->total_days = $interval->days;
		}
		$aTracking->to_nation = strtoupper($aTracking->to_nation);
		$rtn['parsedResult'] = $aTracking->getAttributes();
		return $rtn;
	}//end of parseUbiResult
 
	/**
	 +---------------------------------------------------------------------------------------------
	 * 解释Equick返回的东东,其实已经好靓仔
	 * 如果觉得返回结果可以，就call commitTrackingResultUsingValue 写到数据库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  proxy_return_message         proxy return message, contains 17Track返回的信息
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='','parsedResult'=>Tracking model->getAttributes)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function parseEquickResult($proxy_return_message, $track_no_original=''){
		$rtn['parsedResult'] = array();
		$rtn['message'] = "";
		$rtn['success'] = true;
		$rtn['errorCode'] = 0;
	
		$now_str = date('Y-m-d H:i:s');
		$result_proxy = json_decode($proxy_return_message,true);
		if (empty($result_proxy)) $result_proxy = array();
	
		//先从proxy返回的message中，获取proxy return success 以及17Track Return message
		if (empty($result_proxy['Success'])   ){
			$message = "ETRK040: Equick Proxy 返回结果格式错误，退出".",".(empty($result_proxy['Message'])?"":$result_proxy['Message']);
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			$rtn['message'] = $message;
			$rtn['success'] = false;
			$rtn['errorCode'] = -998;
			$rtn['return_message'] = $result_proxy;
			return $rtn;
		}
		$outputJson = $result_proxy ;
		 
		$equickArray['events'] = array();
		{
			$dataArr = empty($result_proxy['Events'])? array():$result_proxy['Events'];
				
			foreach($dataArr as $da){	
				//echo "get event ".print_r($da,true)." \n";	
				
				if ( isset($da['When']) and strlen($da['When']) == 10)
					$da['When'] .= " 00:00";
				
					if (!isset($da['When']) or strlen($da['When']) < strlen('2015-06-04 20:25'))
						$da['When'] = $now_str;
					
					
					
					$equickArray['events'][]=array('who'=>$da['Who'],'when'=> self::changeDateFormat(substr($da['When'],0,16)) ,
							'what'=>$da['What'],'where'=>$da['Where']);			 
			}//end of each event
	
			rsort($equickArray['events']);
	
			if (count($equickArray['events']) == 0){
				$equickArray['success'] = false;
			}
		}
	
		$track_no = $track_no_original;
		$aTracking = new Tracking();
		$aTracking->track_no = $track_no;
		$TrackingClass = $aTracking;
		$aTracking->from_nation = 'CN';
		$aTracking->to_nation = strtolower(empty($result_proxy['Country'])? '':$result_proxy['Country']);
		
		if (empty(self::$contryEnMapCode)){
			$command = Yii::$app->db->createCommand("select * from sys_country") ;
			$nations = $command->queryAll();
			foreach ($nations as $aNation){
				self::$contryEnMapCode[ strtolower($aNation['country_en'])] =  $aNation['country_code'];
			}
		}
		
		if (isset(self::$contryEnMapCode[ $aTracking->to_nation ]))
			$aTracking->to_nation = self::$contryEnMapCode[ $aTracking->to_nation ] ;
		
		if (strlen($aTracking->to_nation) > 2)
			$aTracking->to_nation = "--";
	
		//如果17Track返回目标国家空白，使用订单的收件人国家
		if ($aTracking->to_nation =='--' or $aTracking->to_nation =='0' or $aTracking->to_nation==''  or $aTracking->to_nation=='null'){
			$aTracking1 = Tracking::find()
			->andWhere("track_no=:trackno",array(':trackno'=>$track_no) )
			->one();
			if ($aTracking1 <> null){
				$aTracking->to_nation = $aTracking->getConsignee_country_code();
			}
		}
	
		$gotStatus = empty($result_proxy['Shipment_status'])? '':$result_proxy['Shipment_status'];
		if ($gotStatus =='SHIPPING')
			$gotStatus = 'In progress';
		
		if ($gotStatus =='DELIVERED')
			$gotStatus = 'Delivered';
		
		if (strpos(strtoupper($gotStatus ), "DELIVERED") !== false )
			$gotStatus = 'Delivered';
		
		$statusMap['In progress'] = "运输途中";
		$statusMap['Delivered'] = "成功签收";
		$statusMap[''] = "查询不到";
		
		$aTracking->parcel_type = 0;
		
	
		$aTracking->total_days = -1;
	
		$allEvents = array();
		$previousEvent = null;
		$aTracking->first_event_date = '';
		$last_event_date ='';
		$aTracking->from_lang = 'zh-cn';
		$aTracking->to_lang ='en';
		$ubiArray = $equickArray;
		
		if (empty($gotStatus))
			$gotStatus ='In progress';
		
		if (!isset($statusMap[$gotStatus]))
			$gotStatus = 'In progress';
		
		foreach ($ubiArray['events'] as $anEvent){
			
			if (  $gotStatus=='In progress' and  (
				strpos( strtolower($anEvent['what']),'delivered') !== false or
				strpos( strtolower($anEvent['what']),'送达') !== false or
				strpos( strtolower($anEvent['what']),'签收') !== false or
				strpos( strtoupper($anEvent['what']),'CONSEGNATA') !== false  )
			){
				$gotStatus = "Delivered";
			}
			
			$newEvent = array();
			$newEvent['when'] = $anEvent['when'];
			$newEvent['where'] = base64_encode( $anEvent['where']);
			$newEvent['what'] = base64_encode( $anEvent['what']);
			$newEvent['lang'] = empty($aTracking->to_lang)? $aTracking->from_lang : $aTracking->to_lang;
			$newEvent['type'] = 'toNation';
				/*
			if (strpos( strtolower($anEvent['what']),"delivered") !== false){
				$aTracking->status = Tracking::getSysStatus("成功签收");
			}
				*/
			//如果之前的event和这个一样，就不要save这个了，重复的不要
			if (isset($previousEvent['when']) and $previousEvent['when'] == $newEvent['when'] and
			isset($previousEvent['where']) and $previousEvent['where'] == $newEvent['where'] and
			isset($previousEvent['what']) and $previousEvent['what'] == $newEvent['what'] )
				continue;
	
			$previousEvent = $newEvent;
			$allEvents[] = $newEvent;
	
			if ($newEvent['when']<>'' and $aTracking->first_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] < $aTracking->first_event_date )
				$aTracking->first_event_date = $newEvent['when'];
				
			if ($newEvent['when']<>'' and $last_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] >$last_event_date )
				$last_event_date = $newEvent['when'];
		}//end of each event
		
		
		
		$aTracking->status = Tracking::getSysStatus($statusMap[$gotStatus]);
		$aTracking->all_event = json_encode($allEvents);
	
		//calculate the total days, if returned value is -1
		if ($aTracking->total_days <= 0 and $aTracking->first_event_date <>'' and strlen($aTracking->first_event_date)>=10){
			if ($aTracking->status == Tracking::getSysStatus("成功签收") and !empty($last_event_date) )
				//使用最后一条记录时间作为签收时间
				$datetime1 = date_create(substr($last_event_date, 0,10) );
			else //使用当前时间
				$datetime1 = date_create(date('Y-m-d H:i:s'));
				
			$datetime2 = date_create(substr($aTracking->first_event_date, 0,10));
			$interval = date_diff($datetime1, $datetime2);
			$aTracking->total_days = $interval->days;
		}
		if (!empty($last_event_date))
			$aTracking->last_event_date = $last_event_date;
		
		$aTracking->to_nation = strtoupper($aTracking->to_nation);
		$rtn['parsedResult'] = $aTracking->getAttributes();
		return $rtn;
	}//end of parseEquickResult

	public function changeDateFormat($date1){
		if (substr($date1,5,1)==" " and strlen($date1)==16){
			$date2 = substr($date1,12,4)."-".substr($date1,9,2)."-".substr($date1,6,2)." ".substr($date1,0,5);
		}else
			$date2 = $date1;
		
		return $date2;	
			
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 解释Equick返回的东东,其实已经好靓仔
	 * 如果觉得返回结果可以，就call commitTrackingResultUsingValue 写到数据库中
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param  proxy_return_message         proxy return message, contains 17Track返回的信息
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='','parsedResult'=>Tracking model->getAttributes)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function parse4PXResult($proxy_return_message, $track_no_original=''){
		$rtn['parsedResult'] = array();
		$rtn['message'] = "";
		$rtn['success'] = true;
		$rtn['errorCode'] = 0;
	
		$now_str = date('Y-m-d H:i:s');
		$result_proxy = json_decode($proxy_return_message,true);
		if (empty($result_proxy)) $result_proxy = array();
	
		//先从proxy返回的message中，获取proxy return success 以及17Track Return message
		if (!isset($result_proxy['error']) or $result_proxy['error']=='1'   ){
			$message = "ETRK040: 4px api 返回结果格式错误，退出"." " ;
			\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',$message],"edb\global");
			$rtn['message'] = $message;
			$rtn['success'] = false;
			$rtn['errorCode'] = -998;
			$rtn['return_message'] = $result_proxy;
			return $rtn;
		}
		$gotStatus ='shipping';
		
		$equickArray['events'] = array();
		 
			$dataArr = empty($result_proxy['trackContent'])? array():$result_proxy['trackContent'];
	
			foreach($dataArr as $da){				 
				if (!isset($da['occurDate']) or strlen($da['occurDate']) < strlen('2015-06-04 20:25'))
					$da['occurDate'] = $now_str;
					
				if ( strpos( strtolower($da['trackContent']),'delivered') !== false or 
					strpos( strtolower($da['trackContent']),'送达') !== false or
					strpos( strtolower($da['trackContent']),'签收') !== false or 
					strpos( strtolower($da['trackContent']),'成功派送') !== false or
					strpos( strtolower($da['trackContent']),'成功递送') !== false or
					strpos( strtoupper($da['trackContent']),'CONSEGNATA') !== false 	
				){
					$gotStatus = 'delivered';
					$da['trackContent'] .= '. Delivered. Distribué. Verteilt. Distribuidos.';
				}
				
				$equickArray['events'][]=array('who'=>$da['createPerson'],'when'=> substr($da['occurDate'],0,16) ,
						'what'=>$da['trackContent'],'where'=>$da['occurAddress']);
			}//end of each event
	
			//rsort($equickArray['events']);
	
			if (count($equickArray['events']) == 0){
				$equickArray['success'] = false;
			}
		 
		$track_no = $track_no_original;
		$aTracking = new Tracking();
		$aTracking->track_no = $track_no;
		$TrackingClass = $aTracking;
		$aTracking->from_nation = 'CN';
		$aTracking->to_nation = strtolower(empty($result_proxy['destinationCountryCode'])? '':$result_proxy['destinationCountryCode']);
	
		if (empty(self::$contryEnMapCode)){
			$command = Yii::$app->db->createCommand("select * from sys_country") ;
			$nations = $command->queryAll();
			foreach ($nations as $aNation){
				self::$contryEnMapCode[ strtolower($aNation['country_en'])] =  $aNation['country_code'];
			}
		}
	
		if (isset(self::$contryEnMapCode[ $aTracking->to_nation ]))
			$aTracking->to_nation = self::$contryEnMapCode[ $aTracking->to_nation ] ;
	
		if (strlen($aTracking->to_nation) > 2)
			$aTracking->to_nation = "--";
	
		//如果17Track返回目标国家空白，使用订单的收件人国家
		if ($aTracking->to_nation =='--' or $aTracking->to_nation =='0' or $aTracking->to_nation==''){
			$aTracking1 = Tracking::find()
			->andWhere("track_no=:trackno",array(':trackno'=>$track_no) )
			->one();
			if ($aTracking1 <> null){
				$aTracking->to_nation = $aTracking->getConsignee_country_code();
			}
		}
	
		
		
		$statusMap['shipping'] = "运输途中";
		$statusMap['delivered'] = "成功签收"; //Parcel delivered
	
		$aTracking->parcel_type = 0;
		$aTracking->status = Tracking::getSysStatus(isset($statusMap[$gotStatus])?$statusMap[$gotStatus]:"运输途中");
	
		$aTracking->total_days = -1;
	
		$allEvents = array();
		$previousEvent = null;
		$aTracking->first_event_date = '';
		$last_event_date ='';
		$aTracking->from_lang = 'zh-cn';
		$aTracking->to_lang ='en';
		$ubiArray = $equickArray;
		foreach ($ubiArray['events'] as $anEvent){
			$newEvent = array();
			$newEvent['when'] = $anEvent['when'];
			$newEvent['where'] = base64_encode( $anEvent['where']);
			$newEvent['what'] = base64_encode( $anEvent['what']);
			$newEvent['lang'] = empty($aTracking->to_lang)? $aTracking->from_lang : $aTracking->to_lang;
			$newEvent['type'] = 'toNation';
			/*
				if (strpos( strtolower($anEvent['what']),"delivered") !== false){
			$aTracking->status = Tracking::getSysStatus("成功签收");
			}
			*/
			//如果之前的event和这个一样，就不要save这个了，重复的不要
			if (isset($previousEvent['when']) and $previousEvent['when'] == $newEvent['when'] and
			isset($previousEvent['where']) and $previousEvent['where'] == $newEvent['where'] and
			isset($previousEvent['what']) and $previousEvent['what'] == $newEvent['what'] )
				continue;
	
			$previousEvent = $newEvent;
			$allEvents[] = $newEvent;
	
			if ($newEvent['when']<>'' and $aTracking->first_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] < $aTracking->first_event_date )
				$aTracking->first_event_date = $newEvent['when'];
	
			if ($newEvent['when']<>'' and $last_event_date=='' or $newEvent['when']<>'' and $newEvent['when'] >$last_event_date ){
				$last_event_date = $newEvent['when'];
				$aTracking->last_event_date = $newEvent['when'];
			}
			 
			
			
		}//end of each event
	
		$aTracking->all_event = json_encode($allEvents);
	
		//calculate the total days, if returned value is -1
		if ($aTracking->total_days <= 0 and $aTracking->first_event_date <>'' and strlen($aTracking->first_event_date)>=10){
			if ($aTracking->status == Tracking::getSysStatus("成功签收") and !empty($last_event_date) )
				//使用最后一条记录时间作为签收时间
				$datetime1 = date_create($last_event_date);
			else //使用当前时间
				$datetime1 = date_create(date('Y-m-d H:i:s'));
	
			$datetime2 = date_create(substr($aTracking->first_event_date, 0,10));
			$interval = date_diff($datetime1, $datetime2);
			$aTracking->total_days = $interval->days;
		}
	
		$aTracking->to_nation = strtoupper($aTracking->to_nation) ;
		$rtn['parsedResult'] = $aTracking->getAttributes();
		return $rtn;
	}//end of parseEquickResult
	
	
	public static function getHttpByCurl($url , $counts=1){
		//echo "Curl: $url \n";
		global $CACHE;
		$JOBID = $CACHE['JOBID'];
		$Ext_Call = empty($CACHE['Ext_Call'])?"":$CACHE['Ext_Call'];
		$current_time=explode(" ",microtime()); $time1=round($current_time[0]*1000+$current_time[1]*1000);
		
		$rtn['message']='';
		$rtn['content']='';
		$rtn['run_time']=0;
		$rtn['error_code']='0';
		$rtn['success']=true;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$TIME_OUT = 80;
		$Connection_TIME_OUT = 30;
		curl_setopt($ch, CURLOPT_TIMEOUT, $TIME_OUT);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $Connection_TIME_OUT);
		//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"ExtTrack Curl Try $Ext_Call" ],"edb\global");
		$output = curl_exec($ch);
		$current_time=explode(" ",microtime() ); $time2=round($current_time[0]*1000+$current_time[1]*1000);
		$run_time = $time2 - $time1; //这个得到的$time是以秒为单位的
		self::extCallSum($Ext_Call,$run_time,false,$counts);
		
		/*
		//记录这个状态下查询17Track一次了
		if ($Ext_Call=='Tracking.17Track'){
			 if (empty($CACHE['Tracking']['this_tracking_status']))
			 	$CACHE['Tracking']['this_tracking_status'] ='查询中';
			 
			self::extCallSum('Tracking.17@'.$CACHE['Tracking']['this_tracking_status'] , $run_time);
		}
			*/
		$rtn['run_time'] = $run_time;
		
		$curl_errno = curl_errno($ch);
		$curl_error = curl_error($ch);
		if ($curl_errno > 0) {// network error
			//retry once
			$current_time=explode(" ",microtime()); $time1=round($current_time[0]*1000+$current_time[1]*1000);
		
			$ch = curl_init();			
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_HEADER, 0);
			$TIME_OUT = 80;
			curl_setopt($ch, CURLOPT_TIMEOUT, $TIME_OUT);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $Connection_TIME_OUT);
			//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"ExtTrack Curl ReTry after error $curl_errno , $Ext_Call" ],"edb\global");
			$output = curl_exec($ch);
			$current_time=explode(" ",microtime());$time2=round($current_time[0]*1000+$current_time[1]*1000);
			$run_time = $time2 - $time1; //这个得到的$time是以秒为单位的
			self::extCallSum($Ext_Call,$run_time,false,$counts);
			 
			$rtn['run_time'] = $run_time;
			
			$curl_errno = curl_errno($ch);
			$curl_error = curl_error($ch);
			if ($curl_errno > 0) { // network error
				//\Yii::info(['Tracking',__CLASS__,__FUNCTION__,'Background',"ExtTrack Curl ReTry after error $curl_errno , $Ext_Call" ],"edb\global");
				$rtn['message']='net work error';
				$rtn['error_code']=$curl_errno;
				$rtn['success']=false;
			 
				curl_close($ch);
				return $rtn;
			}
		}
		
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($httpCode == '200'){
			$rtn['content'] = $output;
		}else{ // network error
			$rtn['success'] = false ;
			$rtn['error_code']=$httpCode;
		}

		curl_close($ch);

		return $rtn;
	}//end of getHttpByCurl
	
	/*
	 * 通过 timestamp 拼接6位buyerId 拼接 3位随机码，生成 订单号
	 * 为了混淆，26进制的计数器，从A开始
	 * 输入：interger 的 buyer Id
	 * 输出：12位string的 20171118ABBC
	 * */
 	public static function encodeOrderNumber($buyerId = 0){
 		$now_str = time();  //2017-11-18 03:55:00
 		$longstr = $now_str . "".substr( ''.($buyerId + 1000000),1).''.substr( ''.(rand(1,999) + 1000),1);
 		 
 		$longstrRest = (int)$longstr;
 		$result='';
 		while($longstrRest >0){
 			$a = $longstrRest % 26;
 			$longstrRest= floor($longstrRest / 26);	
 			$result = chr(66+$a) . $result;
 		}

 		return  array('longInt'=>$longstr , 'longStr'=>$result);
 	}
	
  
 	
}
