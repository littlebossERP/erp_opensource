<?php

namespace eagle\modules\amazon\apihelpers;
use \Yii;
use eagle\models\SaasAmazonAutosync;
use eagle\models\SaasAmazonAutosyncV2;
use eagle\models\SaasAmazonUserMarketplace;
//use eagle\models\AmazonTempOrderidQueue;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\models\AmzOrder;
use eagle\models\AmzOrderDetail;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderHelper;
use eagle\models\AmazonOrderSubmitQueue;
use eagle\models\SaasAmazonUser;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\models\SellerCallPlatformApiCounter;
use eagle\modules\amazon\apihelpers\AmazonSyncFetchOrderV2BaseHelper;
use eagle\models\AmazonTempOrderidQueueHighpriority;
use eagle\models\AmazonTempOrderidQueueLowpriority;

/**
 * 订单header分类：
 * 1、所有unshipped的旧订单(FBA和非FBA)
 * 2、最近更新的非FBA(状态变化/新订单)
 * 3、最近更新的FBA(状态变化/新订单)
 * 4、30天非FBA-非unshipper的订单
 * 5、30天FBA-非unshipper的订单
 *
 * 1、amzOldUnshippedAll
 * 2、amzNewNotFba
 * 3、amzNewFba
 * 4、amzOldNotFbaNotUnshipped
 * 5、amzOldFbaNotUnshipped
 */

class AmazonSyncFetchOrderV2ApiHelper{
	public static $cronJobId=0;
	private static $amzGetOrderListVersion = null;
	private static $amzGetOrderItemsVersion = null;
	public static function fetchOrderList($type){
		/**
		 * [No.1. 参数检验]
		 */
		$BGJobId=self::getCronJobId();
	//	echo "entering ".__FUNCTION__."\n";
//		\Yii::info("entering ".__FUNCTION__."\n","file");
		if (array_key_exists($type,AmazonSyncFetchOrderV2BaseHelper::$amzType)){
			\Yii::info($BGJobId."[".__FUNCTION__."]"." No.1 current type = ".$type."\n","file");
			echo $BGJobId."[".__FUNCTION__."]"." No.1 current type = ".$type."\n";
		}else{
			\Yii::info($BGJobId."[".__FUNCTION__."]"." No.1 error type = ".$type."\n","file");
			echo $BGJobId."[".__FUNCTION__."]"." No.1 error type = ".$type."\n";
			return false;
		}
		//获取优先级
		$priority=AmazonSyncFetchOrderV2BaseHelper::$amzType[$type]["is_highpriority"];
		$checkret=self::checkNeedExitNot('list');
		if ($checkret===true) exit;
		/**
		 * [No.2. 提取对应type的记录，并设置type参数]
		 * @var [type]
		 */
		list($SAA_objs,$tmpchannel,$tmpstatus)=AmazonSyncFetchOrderV2BaseHelper::selectamzType($type,time());
	    \Yii::info($BGJobId."[".__FUNCTION__."]"." No.2 SAA_objs count = ".count($SAA_objs)."\n","file");
	    echo $BGJobId."[".__FUNCTION__."]"." No.2 SAA_objs count = ".count($SAA_objs)."\n";

	    if(count($SAA_objs)){
        	foreach ($SAA_objs as $SAA_objone) {
				/**
				 * [No.3.1. 抢记录]
				 * [如果是多进程的话，有抢不到的情况]
				 * @var [type]
				 */
				//\Yii::info($BGJobId."[".__FUNCTION__."]"." No.3.1 \n","file");
				$SAA_objone=AmazonSyncFetchOrderV2BaseHelper::listLockAutosyncRecord($SAA_objone->id,$type,time());
				if ($SAA_objone==NULL) continue;
				/**
				 * [No.3.2. 设置参数]
				 * [组合新表]
				 * @var [type]
				 */
				//\Yii::info($BGJobId."[".__FUNCTION__."]"." No.3.2 \n","file");
				$nowTime=time();
        		if($type=="amzOldUnshippedAll"||$type=="amzOldNotFbaNotUnshipped"||$type=="amzOldFbaNotUnshipped"){
					$tmp_limit = $SAA_objone->deadline_time;//最多30天的旧订单
					$tmpbefore = $SAA_objone->last_finish_time;
					$tmpbefore = empty($tmpbefore)?($SAA_objone->create_time):$tmpbefore;//提取记录next_execution_time保证在创建时间5分钟后拉取
					$tmpafter = empty($SAA_objone->slip_window_size)?$tmp_limit:($tmpbefore-$SAA_objone->slip_window_size);
					$tmpafter = ($tmpafter<$tmp_limit)?$tmp_limit:$tmpafter;
        		}else if ($type=="amzNewNotFba"||$type=="amzNewFba") {
        			$tmp_limit = $nowTime-300;//当前时间提前几分钟，否则amazon请求失败
					$tmpafter = $SAA_objone->last_finish_time;
					$tmpafter = empty($tmpafter)?$SAA_objone->create_time:$tmpafter;
					$tmpbefore = empty($SAA_objone->slip_window_size)?$tmp_limit:($tmpafter+$SAA_objone->slip_window_size);
					$tmpbefore = ($tmpbefore>$tmp_limit)?$tmp_limit:$tmpbefore;
        		}
				/**
				 * [No.3.3. 获取指定时间内的amazon订单]
				 * @var [type]
				 */
				//\Yii::info($BGJobId."[".__FUNCTION__."]"." No.3.3 \n","file");
				$userInfo_obj = AmazonSyncFetchOrderV2BaseHelper::listGetamzUserInfo($SAA_objone);
				
			//	echo $BGJobId."[".__FUNCTION__."]"." userInfo_obj".print_r($userInfo_obj,true)."\n";
				if (empty($userInfo_obj)) continue;
				
				\Yii::info($BGJobId."[".__FUNCTION__."]"."  merchant_id:".$userInfo_obj['platform_user_id'],"file");
				$fetch_begin_time=$tmpafter;
				$fetch_end_time=$tmpbefore;
				$configParams=array(
			      "marketplace_id"=>$userInfo_obj['site_id'],
			      "merchant_id"=>$userInfo_obj['platform_user_id'],
		          // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 			      "access_key_id"=>$userInfo_obj['access_key_id'],
// 			      "secret_access_key"=>$userInfo_obj['secret_access_key'],
			      "mws_auth_token"=>$userInfo_obj['mws_auth_token'],
				);
				$autoSyncId = $SAA_objone->id;
				$puid = $userInfo_obj['uid'];//可能存在问题，需要判断uid
				$FulfillChannel=$tmpchannel;
				$OrderStatus=$tmpstatus;

				\Yii::info($BGJobId."[".__FUNCTION__."]"." api arguments: puid=".$puid." autoSyncId=".$autoSyncId." priority=.".$priority." fetch_begin_time=".$fetch_begin_time." fetch_end_time=".$fetch_end_time." configParams=".json_encode($configParams)." FulfillChannel=".$FulfillChannel." OrderStatus=".$OrderStatus,"file");
				echo $BGJobId."[".__FUNCTION__."]"." api arguments: puid=".$puid." autoSyncId=".$autoSyncId." priority=.".$priority." fetch_begin_time=".$fetch_begin_time." fetch_end_time=".$fetch_end_time." configParams=".print_r($configParams,true)." FulfillChannel=".$FulfillChannel." OrderStatus=".$OrderStatus."\n";
				list($ret,$retryCount,$message)=AmazonSyncFetchOrderV2BaseHelper::listGetOrderHeaderFromAmazonAndSave($puid,$autoSyncId,$fetch_begin_time,$fetch_end_time,$configParams,$FulfillChannel,$OrderStatus,$priority);
				\Yii::info($BGJobId."[".__FUNCTION__."]"." result: ret=".$ret." retryCount=".$retryCount." message=".$message,"file");
				echo $BGJobId."[".__FUNCTION__."]"." result: ret=".$ret." retryCount=".$retryCount." message=".$message."\n";
				/**
				 * [No.3.4. 结果出来，并保存数据库]
				 */
				AmazonSyncFetchOrderV2BaseHelper::listSaveSAASamzAutosync($SAA_objone,$fetch_begin_time,$fetch_end_time,$ret,$retryCount,$message,$type,$nowTime);
        	}
        }else{
        	return false;
        }

        return true;
	}//end fetchOrderList
	/**
	 * [fetchOrderItems description]
	 * @Author   willage
	 * @DateTime 2016-08-03T10:34:24+0800
	 * @param    [type]                   $highpriority [description]
	 * @return   [type]                                 [description]
	 */
	public static function fetchOrderItems($priority){
		/**
		 * [No.1.获取待处理队列表]
		 */
		echo "entering ".__FUNCTION__."\n";
		$checkret=self::checkNeedExitNot('items');
		if ($checkret===true) exit;
        $OrderidQueue=AmazonSyncFetchOrderV2BaseHelper::$priorityType[$priority]["name_space"];
        \Yii::info(__FUNCTION__." No.1 name_space=".$OrderidQueue."\n","file");
        echo __FUNCTION__." No.1 name_space=".$OrderidQueue."\n";
        $tmpOrderidQueue=new $OrderidQueue;
 		$SAA_objs=$tmpOrderidQueue::find()
	 		->where("process_status=0 OR process_status=3")
	 		->andWhere("error_count < 30")
	 		->orderBy("update_time")
	 		->limit(50)
	 		->all();
        // $SAA_arry=$tmpOrderidQueue::find()->select("saas_platform_autosync_id")->where("process_status=0 OR process_status=3")->andWhere("error_count < 30")->limit(150)->asArray()->column();
        $SAA_arry = [];
        foreach ($SAA_objs as $value) {
            $SAA_arry[]=$value->saas_platform_autosync_id;
        }
		$handledOrderCount=0;
		$oneJobMaxHandleNum=800;//一个进程最多处理订单个数
		$hasGotRecord=false;//是否有抢到过待处理的记录
		\Yii::info(__FUNCTION__." SAA_objs count=".count($SAA_objs)."\n","file");
		echo __FUNCTION__." SAA_objs count=".count($SAA_objs)."\n";
		if(count($SAA_objs)){
			$syncIdAccountMap=AmazonSyncFetchOrderV2BaseHelper::itemsGetSyncIdAccountInfoMap($SAA_arry);
			foreach($SAA_objs as $SAA_objone) {
				/**
				 * [No.2.1. 异常处理]
				 */
				$syncId=$SAA_objone->saas_platform_autosync_id;
				if (!isset($syncIdAccountMap[$syncId])){
					\Yii::error(__FUNCTION__." No.2.1 saas_amazon_autosync_id:".$syncId." not exist in saas_amazon_user\n","file");
					echo __FUNCTION__." No.2.1 saas_amazon_autosync_id:".$syncId." not exist in saas_amazon_user\n";
					$SAA_objone->process_status=4;//4 异常情况，不需要重试，等待it人工分析
					$SAA_objone->update_time=time();
					$SAA_objone->error_count=$SAA_objone->error_count+1;
					$SAA_objone->error_message="saas_platform_autosync_id:".$syncId." not exist in saas_amazon_user";
					$SAA_objone->save(false);
					continue;
				}
				
				if(isset($syncIdAccountMap[$syncId]['status']) && $syncIdAccountMap[$syncId]['status'] == 0){
				    \Yii::error(__FUNCTION__." No.2.1.2 saas_amazon_autosync_id".$syncId." stop syncing\n","file");
				    echo __FUNCTION__." No.2.1.2 saas_amazon_autosync_id:".$syncId." stop syncing\n";
				    $SAA_objone->process_status=5;// 已关闭同步
				    $SAA_objone->update_time=time();
				    $SAA_objone->error_message="saas_platform_autosync_id:".$syncId." stop syncing";
				    $SAA_objone->save(false);
				}
				
				/**
				 * [No.2.2. 抢记录]
				 */
				$SAA_objone=AmazonSyncFetchOrderV2BaseHelper::itemsLockOrderidQueueRecord($SAA_objone->order_id,$priority,time());
				if (empty($SAA_objone)) continue;//抢不到---如果是多进程的话，有抢不到的情况
				$hasGotRecord=true;// 抢到记录
				/**
				 * [No.2.3. 获取指定时间内的amazon订单items]
				 */
				\Yii::info(__FUNCTION__." No.2.3 get amazon order items\n","file");
				echo __FUNCTION__." No.2.3 get amazon order items:".$SAA_objone['order_id']."\n";
				list($ret,$itemsArr,$retryCount,$errorMessage)=AmazonSyncFetchOrderV2BaseHelper::getOrderItemsFromAmazonById($SAA_objone['order_id'],$syncIdAccountMap[$syncId]);
				if ($ret==false) {//出错处理
				    echo __FUNCTION__." No.2.3 get amazon order items errorMessage:$errorMessage\n";
					AmazonSyncFetchOrderV2BaseHelper::itemsHandleFetchItemsError($SAA_objone,$errorMessage);
					continue;
				}
				/**
				 * [No.2.4. ]
				 */
			//	\Yii::info(__FUNCTION__." No.2.4 \n","file");
			//	echo __FUNCTION__." No.2.4 \n";
				$uid=$syncIdAccountMap[$syncId]["uid"];
				if ($uid==0){
					//异常情况
					Yii::error("syncId:$syncId uid:0","file");
					$SAA_objone->process_status=4;
					$SAA_objone->error_count=$SAA_objone->error_count+1;
					$SAA_objone->error_message="uid:0  stop retry";
					$SAA_objone->update_time=time();
					$SAA_objone->save(false);
					continue;
				}
	
				$orderHeaderArr=json_decode($SAA_objone['order_header_json'],true);
				$eagleOrderId=-1;
				$merchantId=$syncIdAccountMap[$syncId]["merchant_id"];
				$marketplaceId=$syncIdAccountMap[$syncId]["marketplace_id"];
				/**
				 * [No.2.5. 保存数据到系统中的od_order_v2和od_order_item_v2]
				 */
			//	\Yii::info(__FUNCTION__." No.2.5 \n","file");
			//	echo __FUNCTION__." No.2.5 \n";
				$result=AmazonSyncFetchOrderV2BaseHelper::itemsSaveAmazonOrderToEagle($orderHeaderArr,$itemsArr,$uid,$merchantId,$marketplaceId,$eagleOrderId,$SAA_objone->type);

				if ($result['success']===1){ //result['success']    0----ok,1---fail
					\Yii::info(__FUNCTION__." No.2.5 save not success\n","file");
					echo __FUNCTION__." No.2.5 save not success\n";
					AmazonSyncFetchOrderV2BaseHelper::itemsHandleFetchItemsError($SAA_objone,"itemsSaveAmazonOrderToEagle() fails.  error:".$result['message']);
					continue;
				}
				/**
				 * [No.2.6. 同步状态到待处理]
				 */
				\Yii::info(__FUNCTION__." No.2.6 \n","file");
				echo __FUNCTION__." No.2.6 \n";
				$SAA_objone['process_status']=2;
				$SAA_objone['update_time']=time();
				$SAA_objone['error_count']=0;
				$SAA_objone['error_message']="";
				$SAA_objone->save(false);
				$handledOrderCount++;
				/**
				 * [No.2.7. 处理数超过800，进程主动退出]
				 */
			//	\Yii::info(__FUNCTION__." No.2.7 \n","file");
			//	echo __FUNCTION__." No.2.7 \n";
				if ($handledOrderCount>$oneJobMaxHandleNum){
					break;
				}
			}
		}else{
			return $hasGotRecord;
		}
		return $hasGotRecord;
	}//end fetchOrderItems

	/**
	 * [getCronJobId description]
	 * @Author   willage
	 * @DateTime 2016-07-26T17:19:34+0800
	 * @return   [type]                   [description]
	 */
	public static function getCronJobId() {
		return self::$cronJobId;
	}

	/**
	 * [setCronJobId description]
	 * @Author   willage
	 * @DateTime 2016-07-26T17:19:42+0800
	 * @param    [type]                   $cronJobId [description]
	 */
	public static function setCronJobId($cronJobId) {
		self::$cronJobId = $cronJobId;
	}


	/**
	 * [checkNeedExitNot 该进程判断是否需要退出]
	 * @Author   willage
	 * @DateTime 2016-08-29T09:57:46+0800
	 * 通过配置全局配置数据表ut_global_config_data的Order/dhgateGetOrderDetailVersion 对应数值
	 * @return   [type]                   [description]
	 */
	private static function checkNeedExitNot($jobType){
		echo "jobType :".$jobType."\n";
		if ($jobType=='list') {
			$amzGetOrderVersionFromConfig = ConfigHelper::getGlobalConfig("Order/amazonGetOrderListVersion",'NO_CACHE');
		}else if($jobType=='items'){
			$amzGetOrderVersionFromConfig = ConfigHelper::getGlobalConfig("Order/amazonGetOrderDetailVersion",'NO_CACHE');
		}else{
			echo "error jobType :".$jobType."\n";
			return false;
		}
		if (empty($amzGetOrderVersionFromConfig))  {
			//数据表没有定义该字段，不退出。
			echo $amzGetOrderVersionFromConfig."amzGetOrderVersionFromConfig nothing\n";
			return false;
		}
		//如果自己还没有定义，去使用global config来初始化自己
		switch ($jobType) {
			case 'list':
				if (self::$amzGetOrderListVersion===null){
					self::$amzGetOrderListVersion = $amzGetOrderVersionFromConfig;
				}
				$tmpVersion=self::$amzGetOrderListVersion;
				break;
			case 'items':
				if (self::$amzGetOrderItemsVersion===null){
					self::$amzGetOrderItemsVersion = $amzGetOrderVersionFromConfig;
				}
				$tmpVersion=self::$amzGetOrderItemsVersion;
				break;
		}
		if (empty($tmpVersion))  {
			return false;
		}
		echo $tmpVersion."tmpVersion\n";
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if ($tmpVersion <> $amzGetOrderVersionFromConfig){
			echo $jobType." Version new $amzGetOrderVersionFromConfig , this job ver ".$tmpVersion." exits \n";
			return true;
		}
		return false;
	}

	/**
	 * [ConverToSaasAmazonAutosyncV2 转换SaasAmazonAutosyncV2]
	 * @Author   willage
	 * @DateTime 2016-08-13T11:50:57+0800
	 * @param    [type]                   $marketplace  [description]
	 * @param    [type]                   $merchantid   [description]
	 * @param    boolean                  $old_complete [description]
	 */
	public static function ConverToSaasAmazonAutosyncV2($marketplace,$merchantid,$old_complete=false){
		$SAAS_objone=SaasAmazonAutosync::find()->where("merchant_id="."'".$merchantid."'")->andWhere("	marketplace_id="."'".$marketplace->marketplace_id."'")->one();
		if (empty($SAAS_objone)) {
			echo "SaasAmazonAutosync : ".count($SAAS_objone)."\n";
			return false;
		}
		$SAASv2_obj=SaasAmazonAutosyncV2::find()->where("platform_user_id="."'".$SAAS_objone->merchant_id."'")->andWhere("site_id="."'".$SAAS_objone->marketplace_id."'")->one();
		if (!empty($SAASv2_obj)) {//若存在用户的站点，表示已经存在，不再重复加入
			echo "THE SaasAmazonAutosyncV2 ".$SAAS_objone->merchant_id." OBJ ALREADY EXISTS!!!\n";
			echo "count".count($SAASv2_obj)."\n";
			return false;
		}
		foreach (AmazonSyncFetchOrderV2BaseHelper::$amzType as $type_key => $type_value) {
			$saasAmazonAutosyncV2Object = new SaasAmazonAutosyncV2();
			$saasAmazonAutosyncV2Object->eagle_platform_user_id = $SAAS_objone->amazon_user_id;
			$saasAmazonAutosyncV2Object->platform_user_id = $SAAS_objone->merchant_id;
			$saasAmazonAutosyncV2Object->site_id = $SAAS_objone->marketplace_id;
			$saasAmazonAutosyncV2Object->status = $SAAS_objone->status;
			$saasAmazonAutosyncV2Object->process_status = $SAAS_objone->process_status; //没同步
			$saasAmazonAutosyncV2Object->create_time = $marketplace->create_time ;
			$saasAmazonAutosyncV2Object->update_time = $marketplace->update_time ;
			$saasAmazonAutosyncV2Object->type = $type_key;
			if($type_key=="amzOldUnshippedAll"||$type_key=="amzOldNotFbaNotUnshipped"||$type_key=="amzOldFbaNotUnshipped"){	//区分新旧情况
				if ($old_complete==true) {
					$saasAmazonAutosyncV2Object->status=$SAAS_objone->status?2:$SAAS_objone->status;//永久停止
					$saasAmazonAutosyncV2Object->process_status=2;//状态完成
				}
			$saasAmazonAutosyncV2Object->deadline_time=$marketplace->create_time-30*24*3600;
			$saasAmazonAutosyncV2Object->err_cnt=$SAAS_objone->old_error_count;
			$saasAmazonAutosyncV2Object->err_msg=$SAAS_objone->old_error_message;
			$saasAmazonAutosyncV2Object->fetch_begin_time=$SAAS_objone->old_begin_time;
			$saasAmazonAutosyncV2Object->fetch_end_time=$SAAS_objone->old_end_time;
			$saasAmazonAutosyncV2Object->last_finish_time=$SAAS_objone->old_last_finish_time;
			$saasAmazonAutosyncV2Object->next_execute_time=$SAAS_objone->old_next_execute_time;
			}else{
			$saasAmazonAutosyncV2Object->err_cnt=$SAAS_objone->error_count;
			$saasAmazonAutosyncV2Object->err_msg=$SAAS_objone->error_message;
			$saasAmazonAutosyncV2Object->fetch_begin_time=$SAAS_objone->fetch_begin_time;
			$saasAmazonAutosyncV2Object->fetch_end_time=$SAAS_objone->fetch_end_time;
			$saasAmazonAutosyncV2Object->last_finish_time=$SAAS_objone->last_finish_time;
			$saasAmazonAutosyncV2Object->next_execute_time=$SAAS_objone->next_execute_time;
			}
			if ($type_key=="amzNewFba"||$type_key=="amzOldNotFbaNotUnshipped"||$type_key=="amzOldFbaNotUnshipped") {//其他情况默认为0
				$saasAmazonAutosyncV2Object->slip_window_size=24*3600;//1天
			}
			if ($type_key=="amzOldUnshippedAll") {//避免同时触发，添加随机数
				$saasAmazonAutosyncV2Object->execution_interval=600+rand(0,100);
			}else if ($type_key=="amzNewNotFba") {
				$saasAmazonAutosyncV2Object->execution_interval=800+rand(0,100);
			}else if ($type_key=="amzNewFba") {
				$saasAmazonAutosyncV2Object->execution_interval=3200+rand(100,200);
			}else if ($type_key=="amzOldNotFbaNotUnshipped") {
				$saasAmazonAutosyncV2Object->execution_interval=6400+rand(100,200);
			}else if ($type_key=="amzOldFbaNotUnshipped") {
				$saasAmazonAutosyncV2Object->execution_interval =6400+rand(100,200);
			}
			if (!$saasAmazonAutosyncV2Object->save()){
				\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',saasAmazonAutosyncV2Object->save() '.print_r($saasAmazonAutosyncV2Object->errors,true) , "file");
				\Yii::info('Platform fail,' . __CLASS__ . ',' . __FUNCTION__ , "file");
				return false;//出现异常，请联系小老板的相关客服
			}
		}
		\Yii::info('Platform OK,' . __CLASS__ . ',' . __FUNCTION__ , "file");
		return true;

	}//end InsertSaasAmazonAutosyncV2





}
?>