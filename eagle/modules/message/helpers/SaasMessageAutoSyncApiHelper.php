<?php
namespace eagle\modules\message\helpers;

use \Yii;
use eagle\models\SaasMessageAutosync;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use eagle\modules\util\helpers\RedisHelper;

class SaasMessageAutoSyncApiHelper{
	public static $cronJobId=0;
	private static $msgGetListVersion = null;
	
	/**
	 * 设置每个平台第一次执行拉取站内信信息时取绑定日期前几天的数据 默认30day
	 */
	public static $platformFetchFewDaysAgoMsg=[
		'aliexpress' => 30,
		'dhgate' => 30,  //敦煌的日期值不能大于366，最好不要超过360
		'wish' => 30, //wish拉取站内信时没有指定日期段的，所以该值随意
		'ebay' => 30,  // Ebay messages expire after one year.
	    'cdiscount' => 30,
		'priceminister'=>30,
	];
	
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
                  获取活跃用户的puid列表-----目前是7天内没有登录的就算不活跃用户。
	 */
	public static function getActiveUsersList(){
		
		$activeUsersPuidArr = UserLastActionTimeHelper::getPuidArrByInterval(7*24);
		return $activeUsersPuidArr; 
		
	}
	
	/**
	 * 该进程判断是否需要退出
	 * 通过配置全局配置数据表ut_global_config_data的Message/msgGetListVersion 对应数值
	 * 
	 * @param $platformAndMsgType 表示平台类型和msg类型
	 *
	 * @return  true or false
	 */
	private static function checkNeedExitNot( $platformAndMsgType ){
		$msgGetListVersionFromConfig = ConfigHelper::getGlobalConfig("Message/".$platformAndMsgType."msgGetListVersion",'NO_CACHE');
		
		if (empty($msgGetListVersionFromConfig))  {
			//数据表没有定义该字段，不退出。
			//	self::$msgGetListVersionFromConfig ="v0";
			return false;
		}
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (self::$msgGetListVersion===null) self::$msgGetListVersion = $msgGetListVersionFromConfig;
		
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$msgGetListVersion <> $msgGetListVersionFromConfig){
			echo "Version new $msgGetListVersionFromConfig , this job ver ".self::$msgGetListVersion." exits \n";
			return true;
		}
		
		return false;
	}
	
	/**
	 * 同步各个平台新产生的站内信，时间由各个平台决定 $platformFetchFewDaysAgoMsg
	 * 
	 * log			name	date					note
     * @author		hqw 	2015/07/03				初始化
     * 
     * @param
     * $platformType	平台类型
     * $byPuid	用于测试单独的puid是否运行正常
	 * $apiVersion	因为速卖通的接口变动了，所以这里加了一个版本参数
	 */
	public static function getMsgListByTime( $platformType, $byPuid='', $apiVersion='1.0'){
		echo "++++++++++++ start to get msg for ".$platformType."getMsgListByTime \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot( $platformType.'Msg' );
		if ($ret===true) exit;
		
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=Yii::$app->db;
		#########################
		$type = 'msgTime';
		$t = time()-1800;//同一账号至少要隔半个小时才进行的拉取！！！  账号第一次绑定的时候last_time=0
		$hasGotRecord=false;//是否抢到账号
		echo "start for job getMsgListByTime \n";
		
		$byPuidAndSql = "";
		if (!empty($byPuid)){
			$byPuidAndSql=' and `uid`='.$byPuid;
		}
		
		//2. 从账户同步表（MSG同步表）saas_message_autosync 提取带同步的账号。          
		//status--- 0 没处理过; 2--已完成; 3--上一次执行有问题;
		//杨增强 2016-3-21 如果技术是 status=1 并且update time 是1小时以前的，认为他可能死掉了，需要把它重新设置为2
		$command = $connection->createCommand("update saas_message_autosync set status=2 where   status=1 and update_time <".(time()-3600)) ;
		$affectRows = $command->execute();
		
// 		$command=$connection->createCommand('select id,uid,last_time from  `saas_message_autosync` where `is_active` = 1 AND `status` <> 1 AND `times` < 10 AND `type`="'.$type.'" AND `platform_source`="'.$platformType.'" AND last_time < '.$t.$byPuidAndSql.' order by `last_time` ASC');
		
		//将times大于10的也作拉取，只要判断拉取日期超过1天，并且失败次数大于等于10的重新试啦
		$command=$connection->createCommand('select id,uid,last_time,times from  `saas_message_autosync` where `is_active` = 1 AND `status` <> 1 AND `type`="'.$type.'" AND `platform_source`="'.$platformType.'" AND last_time < '.$t.$byPuidAndSql.' order by `last_time` ASC limit 10');
		
		#################################
		$activeUsersPuidArr=self::getActiveUsersList();
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			
			$puid=$row['uid'];
			
			echo "try to do for id:".$row['id'].", puid:$puid \n";
			// 先判断是否真的抢到待处理账号
			$current_time=explode(" ",microtime());	$start1_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
			
			$SAA_obj = self::_lockMsgAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
			
			//lolo20151012-----对于不活跃的用户应该减低message拉取的频率.目前是10天内没有登录的就算不活跃用户,3天才拉取一次。
			if ($row['last_time']>0 and !in_array($puid,$activeUsersPuidArr) and (time()-$row['last_time']<3*24*3600)){//不是第一次拉取而且非活跃
				if($platformType == 'dhgate')
					$SAA_obj->last_time = time()+1800;
				else
					$SAA_obj->last_time = time();
				
				$SAA_obj->status = 2;
			
				if (!$SAA_obj->save ()){
					echo "Failed to update 20160127_1 ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid .json_encode($SAA_obj->errors)."\n";
				}
			
				continue;
			}
				
			//将times大于10的也作拉取，只要判断拉取日期超过1天，并且失败次数大于等于10的重新试啦
			if(($row['times'] >= 10) && ($row['last_time']+3600*24 >= $t)){
				echo "skip last_time puid ".$puid." \n";
				
				if($platformType == 'dhgate')
					$SAA_obj->last_time = time()+1800;
				else
					$SAA_obj->last_time = time();
				
				$SAA_obj->status = 3;
					
				if (!$SAA_obj->save ()){
					echo "Failed to update 20160127_2 ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid .json_encode($SAA_obj->errors)."\n";
				}
				
				continue;
			}
		 
			
			\Yii::info($platformType."_msg_get_list_by_time gotit jobid=$backgroundJobId start","file");
			$hasGotRecord=true;  // 抢到记录
			
			//3. 调用MessageApiHelper的api来获取对应账号的站内信信息，并把返回的所有的站内信信息保存到各个user数据库中ticket_session,ticket_message
			//3.1 整理请求参数
			$nowTime = time();
			if ($SAA_obj->end_time == 0){
				//第一次拉取
				if($SAA_obj->binding_time == 0){//假如绑定时间为0,将当前时间设置为绑定时间
				    $SAA_obj->binding_time = $nowTime;
				    $start_time = $nowTime - (86400 * self::$platformFetchFewDaysAgoMsg[$platformType]);
				}else{
				    $start_time = $SAA_obj->binding_time - (86400 * self::$platformFetchFewDaysAgoMsg[$platformType]);
				}
				$end_time = $nowTime;
			}else {
				$start_time = $SAA_obj->end_time;
				$end_time = $nowTime;
			}
			$current_time=explode(" ",microtime());	$start2_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
			echo "used time t2-t1 ".($start2_time-$start1_time)."\n"; //ystest
			
			try{
				$logTimeMS1=TimeUtil::getCurrentTimestampMS();
				
				//$result 返回success、error
				$result = MessageApiHelper::getPubMsg($platformType, $SAA_obj->uid, $SAA_obj->sellerloginid, $start_time, $end_time, $SAA_obj->platform_uid, $apiVersion);
				//$result['touched_session_ids'] is array for all toched session ids
				$logTimeMS2=TimeUtil::getCurrentTimestampMS();
				\Yii::info(__CLASS__.' function:'.__FUNCTION__.' '.$platformType.",t2_1=".($logTimeMS2-$logTimeMS1)." ","file");
			}catch (\Exception $ex) {
				$result['success']=false;
				$result['error'] .= print_r($ex,true);
				echo "Retrive from api failed: ".$result['error']."\n";
				\Yii::error(__CLASS__.' function:'.__FUNCTION__.' '.$platformType.' '.print_r($ex,true));
			}
			
			$current_time=explode(" ",microtime());	$start3_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
			echo "used time t3-t2 ".($start3_time-$start2_time)."\n"; //ystest
			//4. 保存 账户同步表（MSG同步表）saas_message_autosync 提取的账号处理后的结果
			if ($result['success']) {
				$SAA_obj->start_time = $start_time;
				$SAA_obj->end_time = $end_time;
				
				if($platformType == 'dhgate')
					$SAA_obj->last_time = time()+1800;
				else
					$SAA_obj->last_time = time();
				
				$SAA_obj->status = 2;
				$SAA_obj->times = 0;
				$SAA_obj->message = '';

				//批量更新cs_customer的os_flag是否outstanding状态，1为是，0为否
				MessageApiHelper::customerUpdateOsFlag($SAA_obj->uid, $platformType, '', (empty($result['touched_customer_ids'])?array():$result['touched_customer_ids']), $SAA_obj->sellerloginid);
				
				$current_time=explode(" ",microtime());	$start3a_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
				echo "used time customerUpdateOsFlag".($start3a_time-$start3_time)."\n"; //ystest
				
				//批量更改已读状态
				MessageApiHelper::updateTicketSessionHasRead($SAA_obj->uid, $platformType , (empty($result['touched_session_ids'])?array():$result['touched_session_ids']) );
				$current_time=explode(" ",microtime());	$start3b_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
				echo "used timeupdateTicketSessionHasRead ".($start3b_time-$start3a_time)."\n"; //ystest
				
				//删除主动发送成功，后主动拉取的msg
				if (in_array($platformType, array('dhgate','ebay'))){
					MessageApiHelper::delSendSuccessMsgOrOrder($SAA_obj->uid,$platformType,'1,2',(empty($result['touched_customer_ids'])?array():$result['touched_customer_ids']));
				}else if ($platformType == 'aliexpress'){
					MessageApiHelper::delSendSuccessMsgOrOrder($SAA_obj->uid,$platformType,'2',(empty($result['touched_customer_ids'])?array():$result['touched_customer_ids']));
				}else if($platformType == 'priceminister'){
				    MessageApiHelper::delSendSuccessMsgOrOrder($SAA_obj->uid,$platformType,'1',(empty($result['touched_customer_ids'])?array():$result['touched_customer_ids']));
				}
				
			} else {
				$SAA_obj->message = $result['error'];
				$SAA_obj->last_time = time();
				$SAA_obj->status = 3;
				$SAA_obj->times += 1;
			}
			
			if (!$SAA_obj->save ()){
				\Yii::error(['message',__CLASS__,__FUNCTION__,'Online', json_encode($SAA_obj->errors) ],"edb\global");
				echo "Failed to update  ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid .json_encode($SAA_obj->errors)."\n";
			}
			
			//吧这个 puid 的message 左边菜单 清空计数器，下次online user 读取会立即计算一次,这个时候已经 切换成改puid 的user库了。
			//ConfigHelper::setConfig("Message/left_menu_statistics", json_encode(array()));
			if(empty($puid)) $puid = \Yii::$app->user->identity->getParentUid();
			RedisHelper::delMessageCache($puid);
			
			$current_time=explode(" ",microtime());	$start4_time=round($current_time[0]*1000+$current_time[1]*1000);//ystest
			echo "finish for ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid."used time t4-t3 ".($start4_time-$start3_time)."\n";
		}//end while of each binded platform account
		return $hasGotRecord;
	}
	
	/**
	 * 同步各个平台新产生的订单留言，时间由各个平台决定 $platformFetchFewDaysAgoMsg
	 *
	 * log			name	date					note
	 * @author		hqw 	2015/07/03				初始化
	 *
	 * @param
	 * $platformType	//平台类型
	 * $apiVersion	因为速卖通的接口变动了，所以这里加了一个版本参数
	 */
	public static function getOrderMsgListByTime( $platformType ,$apiVersion='1.0'){
		echo "++++++++++++".$platformType."getMsgListByTime \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot( $platformType.'Ordermsg' );
		if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=Yii::$app->db;
		#########################
		$type = 'OrdermsgTime';
		$t = time()-1800;//同一账号至少要隔半个小时才进行的拉取！！！  账号第一次绑定的时候last_time=0
		$hasGotRecord=false;//是否抢到账号
	
		//2. 从账户同步表（MSG同步表）saas_message_autosync 提取带同步的账号。          status--- 0 没处理过; 2--已完成; 3--上一次执行有问题;
		$command=$connection->createCommand('select  id,uid,last_time,times  from  `saas_message_autosync` where `is_active` = 1 AND `status` <> 1 AND `type`="'.$type.'" AND `platform_source`="'.$platformType.'" AND last_time < '.$t.' order by `last_time` ASC limit 10' );
		#################################
		$activeUsersPuidArr=self::getActiveUsersList();	
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			
			$puid=$row['uid'];
				
			echo "try to do for id:".$row['id'].",puid:$puid \n";
			
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockMsgAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
			
			//lolo20151012-----对于不活跃的用户应该减低message拉取的频率.目前是10天内没有登录的就算不活跃用户,3天才拉取一次。
			if ($row['last_time']>0 and !in_array($puid,$activeUsersPuidArr) and (time()-$row['last_time']<3*24*3600)){//不是第一次拉取而且非活跃
				//	echo "skip unactive puid ".$puid." \n";
				if($platformType == 'dhgate')
					$SAA_obj->last_time = time()+1800;
				else
					$SAA_obj->last_time = time();
				
				$SAA_obj->status = 2;
					
				if (!$SAA_obj->save ()){
					echo "Failed to update 20160127_2 ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid .json_encode($SAA_obj->errors)."\n";
				}
				continue;
			}
				
			//将times大于10的也作拉取，只要判断拉取日期超过1天，并且失败次数大于等于10的重新试啦
			if(($row['times'] >= 10) && ($row['last_time']+3600*24 >= $t)){
				echo "skip last_time puid ".$puid." \n";
				if($platformType == 'dhgate')
					$SAA_obj->last_time = time()+1800;
				else
					$SAA_obj->last_time = time();
				
				$SAA_obj->status = 3;
					
				if (!$SAA_obj->save ()){
					echo "Failed to update 20160127_2 ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid .json_encode($SAA_obj->errors)."\n";
				}
				continue;
			}
				
			\Yii::info($platformType."_Ordermsg_get_list_by_time gotit jobid=$backgroundJobId start","file");
			$hasGotRecord=true;  // 抢到记录
				
			//3. 调用MessageApiHelper的api来获取对应账号的站内信信息，并把返回的所有的站内信信息保存到各个user数据库中ticket_session,ticket_message
			//3.1 整理请求参数
			$nowTime = time();
			if ($SAA_obj->end_time == 0){
				//第一次拉取
				$start_time = $SAA_obj->binding_time-(86400 * self::$platformFetchFewDaysAgoMsg[$platformType]);
				$end_time = $nowTime;
			}else {
				$start_time = $SAA_obj->end_time;
				$end_time = $nowTime;
			}
				
			//$result 返回success、error
			$result = MessageApiHelper::getPubOrderMsg($platformType, $SAA_obj->uid, $SAA_obj->sellerloginid, $start_time, $end_time, $apiVersion);
				
			//4. 保存 账户同步表（MSG同步表）saas_message_autosync 提取的账号处理后的结果
			if ($result['success']) {
				$SAA_obj->start_time = $start_time;
				$SAA_obj->end_time = $end_time;
				$SAA_obj->last_time = time();
				$SAA_obj->status = 2;
				$SAA_obj->times = 0;
				$SAA_obj->message = '';
// 				$SAA_obj->save ();
				
				//批量更新cs_customer的os_flag是否outstanding状态，1为是，0为否
				MessageApiHelper::customerUpdateOsFlag($SAA_obj->uid, $platformType, '', (empty($result['touched_customer_ids'])?array():$result['touched_customer_ids']), $SAA_obj->sellerloginid);
				
				//批量更改已读状态
				MessageApiHelper::updateTicketSessionHasRead($SAA_obj->uid, $platformType , (empty($result['touched_session_ids'])?array():$result['touched_session_ids']) );
				
				//删除主动发送成功，后主动拉取的msg
				if ($platformType == 'aliexpress'){
					MessageApiHelper::delSendSuccessMsgOrOrder($SAA_obj->uid,$platformType,'2',(empty($result['touched_customer_ids'])?array():$result['touched_customer_ids']));
				}
			} else {
				$SAA_obj->message = $result['error'];
				$SAA_obj->last_time = time();
				$SAA_obj->status = 3;
				$SAA_obj->times += 1;
// 				$SAA_obj->save ();
			}
			
			if (!$SAA_obj->save ()){
				\Yii::error(['message',__CLASS__,__FUNCTION__,'Online', json_encode($SAA_obj->errors) ],"edb\global");
				echo "Failed to update  ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid .json_encode($SAA_obj->errors)."\n";
			}
	
			echo "finish for ".$SAA_obj->uid." - ". $SAA_obj->sellerloginid."\n";
		}
		return $hasGotRecord;
	}
	
	/**
	 * 先判断是否真的抢到待处理账号
	 * @param  $msgAutosyncId  -- saas_message_autosync表的id
	 * @return null或者$SAA_obj , null表示抢不到记录
	 */
	private static function _lockMsgAutosyncRecord($msgAutosyncId){
		$connection=Yii::$app->db;
		$command = $connection->createCommand("update saas_message_autosync set 
				update_time= ".time()." , status=1 where id =". $msgAutosyncId." and status<>1 ") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
			
		// 抢到记录
		$SAA_obj = 	SaasMessageAutosync::findOne($msgAutosyncId);
		
		return $SAA_obj;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更新saas_message_autosync的开始和结束日期，用于平台拉取大数据时反应缓慢时做时间分割时,将已经拉取过的日期不在拉取
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	$uid	//小老板Uid
	 * @param	$sellerloginid	卖家ID
	 * @param	$type	//类型 msgTime、OrdermsgTime
	 * @param	$start_time	更新的开始日期
	 * @param	$end_time	更新的结束日期
	 +---------------------------------------------------------------------------------------------
	 * @return	array ('success' => true, 'message'='')
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw		2015/9/2				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function updateSaasMsgAutosyncRunTime($uid, $sellerloginid, $type, $start_time, $end_time){
		try{
			$connection=Yii::$app->db;
			
			$command = $connection->createCommand(
					"update saas_message_autosync set `start_time`=:start_time,`end_time`=:end_time,`last_time`=:last_time where `uid`=:uid and `sellerloginid`=:sellerloginid and `type`=:type and status=1 ");
			
			$command->bindValue(':start_time', $start_time, \PDO::PARAM_STR);
			$command->bindValue(':end_time', $end_time, \PDO::PARAM_STR);
			$command->bindValue(':uid', $uid, \PDO::PARAM_STR);
			$command->bindValue(':sellerloginid', $sellerloginid, \PDO::PARAM_STR);
			$command->bindValue(':type', $type, \PDO::PARAM_STR);
			$command->bindValue(':last_time', time(), \PDO::PARAM_STR);
			$affectRows = $command->execute();
		}catch(\Exception $ex){
			return array('success'=>false,'message'=>'function:'.__FUNCTION__.print_r($ex,true));
		}
		
		return array('success'=>true,'message'=>$affectRows);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 站内信同步情况 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=>同步表的最新数据
	 * 					'message'=>执行详细结果
	 * 				    'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getLastMessageSyncDetail($platform ,$account_key , $uid=0){
		if (empty($account_key)) return ['success'=>false , 'message'=>'账号无效！' , 'result'=>[]];
		
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		if (in_array($platform , ['ebay','aliexpress'])){
			$result = SaasMessageAutosync::find()->where(['uid'=>$uid , 'sellerloginid'=>$account_key ,'platform_source'=>$platform ])->orderBy(' last_time desc ')->asArray()->one();
		}else{
			$result = SaasMessageAutosync::find()->where(['uid'=>$uid , 'platform_uid'=>$account_key ,'platform_source'=>$platform ])->orderBy(' last_time desc ')->asArray()->one();
		}
		
		if (!empty($result)){
			return  ['success'=>true , 'message'=>'' , 'result'=>$result];
		}else{
			return  ['success'=>false , 'message'=>'没有同步信息' , 'result'=>[]];
		}
	}//end of getLastMessageSyncDetail
	
	
}