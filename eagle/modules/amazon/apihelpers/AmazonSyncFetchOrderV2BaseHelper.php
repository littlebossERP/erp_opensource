<?php

namespace eagle\modules\amazon\apihelpers;
use \Yii;
use eagle\models\AmazonOrderSubmitQueue;
use eagle\models\AmazonTempOrderidQueueHighpriority;
use eagle\models\AmazonTempOrderidQueueLowpriority;
use eagle\models\AmzOrder;
use eagle\models\AmzOrderDetail;
use eagle\models\SaasAmazonAutosyncV2;
use eagle\models\SaasAmazonUser;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\models\SellerCallPlatformApiCounter;
use eagle\models\SysCountry;
use eagle\modules\order\helpers\OrderHelper;
use eagle\modules\order\models\OdOrderShipped;
use eagle\modules\util\helpers\EagleOneHttpApiHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\UserLastActionTimeHelper;

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
	// 	array(OldUnshippedAll 600  =>array(high,"HigorderHeerslist_tablenmae",/namespace/HigorderHeerslist)
 // * 2、NewNotFba    800   =>low
 // * 3、NewFBA       3200+randon(100~200)
 // * 4、OldNotFbaNotUnshipped  6400+randon(100~200)
 // * 5、OldFbaNotUnshipped   6400+randon(100~200)

	// array(OldUnshippedAll=>"23"
 // * 2、NewNotFba==>"23"
 // * 3、NewFBA
 // * 4、OldNotFbaNotUnshipped
 // * 5、OldFbaNotUnshipped)

 // executionInterval second
class AmazonSyncFetchOrderV2BaseHelper{
	/**
	 * [$amzType 记录不同类型的优先级属性]
	 * [array['is_highpriority'=>bool,'table_name'=>string,'namespace/model'=>string]]
	 * @var array
	 */
	public static $amzType = array(
		'amzOldUnshippedAll'=>array(
			"is_highpriority"=>true,),
		'amzNewNotFba'=>array(
			"is_highpriority"=>true,),
		'amzNewFba'=>array(
			"is_highpriority"=>false,),
		'amzOldNotFbaNotUnshipped'=>array(
			"is_highpriority"=>false,),
		'amzOldFbaNotUnshipped'=>array(
			"is_highpriority"=>false,),
	);

	public static $priorityType = array(
		'highpriority'=>array(
			"table_name"=>"amazon_temp_orderid_queue_highpriority",
			"name_space"=>"eagle\models\AmazonTempOrderidQueueHighpriority"),
		'lowpriority'=>array(
			"table_name"=>"amazon_temp_orderid_queue_lowpriority",
			"name_space"=>"eagle\models\AmazonTempOrderidQueueLowpriority"),
	);
	/**
	 * [$AMAZON_EAGLE_ORDER_STATUS_MAP amazon平台的状态跟eagle的订单状态的对应关系]
	 * @var array
	 */
	public static $AMAZON_EAGLE_ORDER_STATUS_MAP = array(
			//'Pending' =>100,  //等待买家付款
			'Unshipped' => 200, //买家已付款
			'Shipped' => 500,//CUBE_CONST::SentGood,  //卖家已发货
			'PartiallyShipped' => 200,
			'Canceled'=>600
			//'Canceled' => $Canceled,	//交易关闭
	);
	public static $AMAZON_MARKETPLACE_REGION_CONFIG = array(
		'A2EUQ1WTGCTBG2'=>"CA",
		'ATVPDKIKX0DER'=>"US",
		'A1PA6795UKMFR9'=>"DE",
		'A1RKKUPIHCS9HS'=>"ES",
		'A13V1IB3VIYZZH'=>"FR",
		'A21TJRUUN4KGV'=>"IN",
		'APJ6JRA9NG5V4'=>"IT",
		'A1F83G8C2ARO7P'=>"UK",
		'A1VC38T7YXB528'=>"JP",
		'AAHKV2X7AFYLW'=>"CN",
		'A1AM78C64UM0Y8'=>"MX",
		'A39IBJ37TRP1C6'=>"AU",
	        
		'A2Q3Y263D00KWC'=>"BR",
        'A2VIGQ35RCS4UG'=>"AE",
        'A33AVAJ2PDY3EV'=>"TR",
		
		'A19VAU5U5O7RUS'=>"SG",
    );
	/**
	 * [listGetOrderHeaderFromAmazonAndSave description]
	 * @Author   willage
	 * @DateTime 2016-07-27T15:07:09+0800
	 * @param    [type]                   $puid             [description]
	 * @param    [type]                   $autoSyncId       [description]
	 * @param    [type]                   $fetch_begin_time [description]
	 * @param    [type]                   $fetch_end_time   [description]
	 * @param    [type]                   $configParams     [description]
	 * @param    string                   $type             [description]
	 * @param    [type]                   $status           [description]
	 * @param    [type]                   $highpriority     [description]
	 * @return   [type]                                     [description]
	 */
	public static function listGetOrderHeaderFromAmazonAndSave($puid,$autoSyncId,$fetch_begin_time,$fetch_end_time,$configParams,$type="MFN",$status,$IS_highpriority){
		/**
		 * [No.1.$reqParams 设置参数，用于给proxy API使用]
		 * @var array
		 */
		$BGJobId=AmazonSyncFetchOrderV2ApiHelper::getCronJobId();
		$config=$configParams;
		//需要获取的订单list的开始时间gmt0，注意：如果这个超过当前时间的话，amazon会请求失败！！！！！
		// $fromDateTime=gmdate("Y-m-d H:i:s",$fetch_begin_time);
		// $toDateTime=gmdate("Y-m-d H:i:s",$fetch_end_time);
		$fromDateTime=date("Y-m-d H:i:s",$fetch_begin_time);
		$toDateTime=date("Y-m-d H:i:s",$fetch_end_time);
		$reqParams=array();
		$reqParams["fromDateTime"]=$fromDateTime;
		$reqParams["toDateTime"]=$toDateTime;
		$reqParams["orderType"]=$type;
		$reqParams["config"]=json_encode($config);
		$reqParams["status"] = $status;
		$timeout=140; //超时设置
		try{
			$retInfo=AmazonProxyConnectApiHelper::call_amazon_api("getorder",$reqParams,$timeout);
			$getProxyResultTimeStr=date("Y-m-d H:i:s");
		}catch(\Exception $e){
			\Yii::info($BGJobId."[".__FUNCTION__."] false No.1 call_amazon_api\n","file");
			echo $BGJobId."[".__FUNCTION__."] false No.1 call_amazon_api\n";
			return array(false,-1,"AmazonProxyConnectApiHelper::call_amazon_api  exception");
		}
		/**
		 * [No.2.返回结果异常处理]
		 * @var array
		 */
		list($ret,$retryCount,$message)=self::_listExceptionHandling($retInfo,$getProxyResultTimeStr);
		if ($ret===false) {
			\Yii::info($BGJobId."[".__FUNCTION__."] false No.2 error results\n","file");
			echo $BGJobId."[".__FUNCTION__."] false No.2 error results\n";
			return array($ret,$retryCount,$message);
		}
		/**
		 * [No.3. 处理没有order的情况,并记录proxy访问amazon的重试情况]
		 * @var [type]
		 */
		$retryCount=$retInfo["response"]["retryCount"];
		if (($retInfo["response"]["order"]===null) or count($retInfo["response"]["order"])==0) {
			\Yii::info($BGJobId."[".__FUNCTION__."] false No.3 no order\n","file");
			echo $BGJobId."[".__FUNCTION__."] false No.3 no order\n";
			return array(true,$retryCount,"");
		}
		/**
		 * [No.4.amazon_temp_orderid_queue数据表更新]
		 * @var array
		 */
		\Yii::info($BGJobId."[".__FUNCTION__."] update temp_orderid_queue \n","file");
		$ordersArr=$retInfo["response"]["order"];
		foreach($ordersArr as $order){
			$orderId=$order["AmazonOrderId"];
		//	\Yii::info($BGJobId."[".__FUNCTION__."] save orderId=".$orderId."\n","file");
	//		echo $BGJobId."[".__FUNCTION__."] save orderId=".$orderId."\n";
			if ($IS_highpriority) {
				$readyGetDetailQueue = AmazonTempOrderidQueueHighpriority::find()->where('order_id = :orderId',array(':orderId'=>$orderId))->one();
			}else{
				$readyGetDetailQueue = AmazonTempOrderidQueueLowpriority::find()->where('order_id = :orderId',array(':orderId'=>$orderId))->one();
			}
			if ($readyGetDetailQueue===null){
				//不存在，需要insert
				if ($IS_highpriority) $readyGetDetailQueue=new AmazonTempOrderidQueueHighpriority();
				else $readyGetDetailQueue=new AmazonTempOrderidQueueLowpriority();
				$readyGetDetailQueue->order_id=$orderId;
				$readyGetDetailQueue->type=$order["FulfillmentChannel"];
				$readyGetDetailQueue->create_time=time();
				$readyGetDetailQueue->update_time=$readyGetDetailQueue->create_time;
				$readyGetDetailQueue->saas_platform_autosync_id=$autoSyncId;
				$readyGetDetailQueue->error_count=0;
				$readyGetDetailQueue->process_status=0;
				$readyGetDetailQueue->puid=$puid;
				$order["type"]=$order["FulfillmentChannel"];//header json信息中处理type都是amazon原始返回的信息;type MFN or AFN
				$readyGetDetailQueue->order_header_json=json_encode($order);
			}else{
				//存在，需要update
				$readyGetDetailQueue->puid=$puid;
				$readyGetDetailQueue->order_header_json=json_encode($order);
				$readyGetDetailQueue->saas_platform_autosync_id=$autoSyncId;
				$readyGetDetailQueue->process_status=0;//重置状态重新获取订单内容
				$readyGetDetailQueue->update_time=time();
			}
			if (!$readyGetDetailQueue->save()){
				\Yii::info($BGJobId."[".__FUNCTION__."] readyGetDetailQueue->save error:".print_r($readyGetDetailQueue->errors,true)."\n","file");
				echo $BGJobId."[".__FUNCTION__."] readyGetDetailQueue->save error:".print_r($readyGetDetailQueue->errors,true)."\n";
			}
		}
		return array(true,$retryCount,"");
	}//end listGetOrderHeaderFromAmazonAndSave

	public static function getOrderItemsFromAmazonById($orderId,$accountInfo)
	{
		$config=array(
				'merchant_id' => $accountInfo["merchant_id"],
				'marketplace_id' => $accountInfo["marketplace_id"],
		        // dzt20190619 amazon要求去掉旧授权调用，否则开发者账号会降权
// 				'access_key_id' => $accountInfo["access_key_id"],
// 				'secret_access_key' => $accountInfo["secret_access_key"],
		        'mws_auth_token' => $accountInfo["mws_auth_token"],
		);
		$reqParams=array();
		$reqParams["config"]=json_encode($config);
		$reqParams["orderid"]=$orderId;
		try{
			$timeout=60; //s
			$retInfo=AmazonProxyConnectApiHelper::call_amazon_api("getorderitem",$reqParams,$timeout);
		}catch(\Exception $e) {
			return array(false,array(),0,"AmazonProxyConnectApiHelper::call_amazon_api  exception");
		}
		/**
		 * 1. eagle跟proxy的通信有问题
		 */
		if ($retInfo["success"]===false){
			if (isset($retInfo["message"])) $errorMsg=$retInfo["message"];
			else $errorMsg="amazon proxy return error";

			return array(false,array(),0,$errorMsg);
		}
		/**
		 * 2. proxy跟amazon通信有问题
		 */
		if ($retInfo["response"]["success"]===false){
			if (isset($retInfo["response"]["message"]))  $errorMsg=$retInfo["message"];
			else $errorMsg="amazon proxy return error";

			return array(false,array(),0,$errorMsg);
		}
		$retryCount=$retInfo["response"]["retryCount"];
		$itemsArr=$retInfo["response"]["item"];

		return array(true,$itemsArr,$retryCount,"");
	}//end _getOrderItemsFromAmazonById

	/**
	 * [_listExceptionHandling 异常处理]
	 * [No.1.与proxy通信异常情况：
	 * A、eagle跟proxy的通信有问题;
	 * B、proxy跟amazon通信有问题;
	 * C、proxy的返回的结果结构上有问题。]
	 * @Author   willage
	 * @DateTime 2016-07-27T15:46:59+0800
	 * @return   [type]                   [description]
	 */
	private static function _listExceptionHandling($api_result,$getResultTime){
		/**
		 * [1、eagle跟proxy的通信有问题;]
		 */
		if ($api_result["success"]===false){
			if (isset($api_result["message"])) $errorMsg=$api_result["message"];
			else $errorMsg="amazon proxy return error";
			return array(false,-1,$getResultTime." ".$errorMsg);
		}
		/**
		 * [2、proxy跟amazon通信有问题;]
		 */
		if ($api_result["response"]["success"]===false){
			//先检查是否该用户订单数太多，导致第一次拉取的超时或者超出访问限制
			if (isset($api_result["response"]["order"])){
				if (count($api_result["response"]["order"])>200){
					return array(false,-3,$getResultTime." "."he gets too much orders");
				}
			}
			if (isset($api_result["response"]["message"]))  $errorMsg=$api_result["response"]["message"];
			else $errorMsg="amazon proxy return error";

			return array(false,-1,$getResultTime." ".$errorMsg);
		}
		/**
		 * [3、proxy的返回的结果结构上有问题。]
		 */
		if (!in_array("order",array_keys($api_result["response"]))) {
			return array(false,-1,$getResultTime." "."no [order] element in reponse data structure");
		}

		/**
		 * 检查是否该用户订单数太多，导致第一次拉取的超时或者超出访问限制
		 */
		if ($api_result["success"]===true and $api_result["response"]["success"]===false  )  {
			if (isset($api_result["response"]["order"])){
				if (count($api_result["response"]["order"])>200){
					return array(false,-3,$getResultTime." "."he gets too much orders 2");
				}
			}
		}

	}//end _listExceptionHandling

	/**
	 * [itemsLockOrderidQueueRecord 判断是否抢到待处理账号]
	 * @Author   willage
	 * @DateTime 2016-08-01T11:45:59+0800
	 * @param    [type]                   $orderId [amazon_temp_orderid_queue表的orderid]
	 * @return   [type]                            [null或者$SAA_obj , null表示抢不到记录]
	 */
	public static function itemsLockOrderidQueueRecord($orderId,$priority,$nowTime){
        $tmpTable=self::$priorityType[$priority]["table_name"];
        $tmpmodel=self::$priorityType[$priority]["name_space"];
		\Yii::info(__FUNCTION__." orderId=".$orderId." Table=".$tmpTable." model=".$tmpmodel."\n","file");

		$connection=Yii::$app->db_queue;
		$command = $connection->createCommand("UPDATE `".$tmpTable."` SET process_status=1,update_time=".$nowTime." WHERE order_id = '".$orderId."' AND (process_status=0 or process_status=3) ");
		$affectRows = $command->execute();

		if ($affectRows <= 0) return null; //抢不到---如果是多进程的话，有抢不到的情况
		//抢到记录
		$OrderidQueue=new $tmpmodel;
		$retobj=$OrderidQueue::find()->where(["order_id"=>$orderId])->one();
		return $retobj;
	}//end itemsLockOrderidQueueRecord

	public static function listLockAutosyncRecord($tableId,$type,$nowTime){
		$connection = \Yii::$app->db;
		$command = $connection->createCommand("UPDATE `saas_amazon_autosync_v2` SET process_status=1,update_time=".$nowTime." WHERE id =".$tableId." AND type="."'".$type."'"." AND process_status<>1") ;
		$affectRows = $command->execute();
		if ($affectRows <= 0)	return null; //抢不到

		$tmpobj=SaasAmazonAutosyncV2::find()->where('id ='.$tableId)->andWhere('type='."'".$type."'")->one();
		return $tmpobj;
	}//end listLockAutosyncRecord



	public static function itemsGetSyncIdAccountInfoMap($syncid_array){
		$syncIdAccountMap=array();
		$syncIdUidMap = array();
		if (empty($syncid_array)) {
			$sql="SELECT aa.id AS sync_id, am.access_key_id AS access_key_id,am.secret_access_key AS secret_access_key,am.mws_auth_token AS mws_auth_token,aa.platform_user_id AS merchant_id, aa.site_id AS marketplace_id, au.uid AS uid ".
              " FROM  `saas_amazon_user` au, saas_amazon_autosync_v2 aa, saas_amazon_user_marketplace am ".
              " WHERE au.amazon_uid = aa.eagle_platform_user_id ".
             " AND am.amazon_uid = aa.eagle_platform_user_id ".
             " AND aa.site_id = am.marketplace_id ";
		}else{
			$sql="SELECT aa.id AS sync_id, am.access_key_id AS access_key_id,am.secret_access_key AS secret_access_key,am.mws_auth_token AS mws_auth_token,aa.platform_user_id AS merchant_id, aa.site_id AS marketplace_id, au.uid AS uid ".
              " FROM  `saas_amazon_user` au, saas_amazon_autosync_v2 aa, saas_amazon_user_marketplace am ".
               " WHERE aa.id  IN (".implode(',',$syncid_array).")".
              " AND au.amazon_uid = aa.eagle_platform_user_id ".
             " AND am.amazon_uid = aa.eagle_platform_user_id ".
             " AND aa.site_id = am.marketplace_id ";
		}
		$rows=Yii::$app->db->createCommand($sql)->queryAll();
		// \yii::info($rows,"file");
		foreach($rows as $row){
			$syncIdUidMap[$row["sync_id"]]=$row;
		}
		return $syncIdUidMap;
	}


	/**
	 * [itemsHandleFetchItemsError description]
	 * @Author   willage
	 * @DateTime 2016-08-01T14:51:38+0800
	 * @param    [type]                   $queueOrderForSync [description]
	 * @param    [type]                   $message           [description]
	 * @return   [type]                                      [description]
	 */
	public static function itemsHandleFetchItemsError($queueOrderForSync,$message){
		$queueOrderForSync->process_status=3;
		$queueOrderForSync->error_count=$queueOrderForSync->error_count+1;
		$queueOrderForSync->error_message=$message;
		$queueOrderForSync->update_time=time();
		$queueOrderForSync->save(false);
	}//end itemsHandleFetchItemsError

	/**
	 * [_selcetamzType description]
	 * @Author   willage
	 * @DateTime 2016-08-03T16:37:07+0800
	 * @param    [type]                   $type [description]
	 * @return   [type]                         [description]
	 */
	public static function selectamzType($type,$nowTime){
		$BGJobId=AmazonSyncFetchOrderV2ApiHelper::getCronJobId();
		switch ($type) {
			case "amzOldUnshippedAll":
			$SAA_objs=SaasAmazonAutosyncV2::find()->where('status=1')->andWhere('process_status <> 1')->andWhere('err_cnt < 30')->andWhere("type="."'".$type."'")->andWhere('next_execute_time<'.$nowTime.' OR next_execute_time IS NULL')->andWhere('deadline_time<last_finish_time OR last_finish_time IS NULL OR last_finish_time=0')->orderBy('next_execute_time asc')->all();
			$tmpchannel = "MFN,AFN";
			$tmpstatus = "Unshipped,PartiallyShipped";
			\Yii::info($BGJobId."[".__FUNCTION__."]"." here is amzOldUnshippedAll tmpchannel=".$tmpchannel." tmpstatus=".$tmpstatus,"file");
				break;
			case "amzNewNotFba":
			$SAA_objs=SaasAmazonAutosyncV2::find()->where('status=1')->andWhere('process_status <> 1')->andWhere('err_cnt < 30')->andWhere("type="."'".$type."'")->andWhere('next_execute_time<'.$nowTime.' OR next_execute_time IS NULL')->orderBy('next_execute_time asc')->all();
			$tmpchannel = "MFN";
			$tmpstatus = "Unshipped,PartiallyShipped,Shipped,Canceled";
			\Yii::info($BGJobId."[".__FUNCTION__."]"." here is amzNewNotFba tmpchannel=".$tmpchannel." tmpstatus=".$tmpstatus,"file");
				break;
			case "amzNewFba":
			$SAA_objs=SaasAmazonAutosyncV2::find()->where('status=1')->andWhere('process_status <> 1')->andWhere('err_cnt < 30')->andWhere("type="."'".$type."'")->andWhere('next_execute_time<'.$nowTime.' OR next_execute_time IS NULL')->orderBy('next_execute_time asc')->all();
			$tmpchannel = "AFN";
			$tmpstatus = "Shipped,Canceled";
			\Yii::info($BGJobId."[".__FUNCTION__."]"." here is amzNewFBA tmpchannel=".$tmpchannel." tmpstatus=".$tmpstatus,"file");
				break;
			case "amzOldNotFbaNotUnshipped":
			$SAA_objs=SaasAmazonAutosyncV2::find()->where('status=1')->andWhere('process_status <> 1')->andWhere('err_cnt < 30')->andWhere("type="."'".$type."'")->andWhere('next_execute_time<'.$nowTime.' OR next_execute_time IS NULL')->andWhere('deadline_time<last_finish_time OR last_finish_time IS NULL OR last_finish_time=0')->orderBy('next_execute_time asc')->all();
			$tmpchannel = "MFN";
			$tmpstatus = "Shipped,Canceled";
			\Yii::info($BGJobId."[".__FUNCTION__."]"." here is amzOldNotFbaNotUnshipped tmpchannel=".$tmpchannel." tmpstatus=".$tmpstatus,"file");
				break;
			case "amzOldFbaNotUnshipped":
			$SAA_objs=SaasAmazonAutosyncV2::find()->where('status=1')->andWhere('process_status <> 1')->andWhere('err_cnt < 30')->andWhere("type="."'".$type."'")->andWhere('next_execute_time<'.$nowTime.' OR next_execute_time IS NULL')->andWhere('deadline_time<last_finish_time OR last_finish_time IS NULL OR last_finish_time=0')->orderBy('next_execute_time asc')->all();
			$tmpchannel = "AFN";
			$tmpstatus = "Shipped,Canceled";
			\Yii::info($BGJobId."[".__FUNCTION__."]"." here is amzOldFbaNotUnshipped tmpchannel=".$tmpchannel." tmpstatus=".$tmpstatus,"file");
				break;
			default:
			$SAA_objs=NULL;$tmpchannel=NULL;$tmpstatus=NULL;
			\Yii::info($BGJobId."[".__FUNCTION__."]"." here is default,error","file");
				break;
		}
		return array($SAA_objs,$tmpchannel,$tmpstatus);
	}

	/**
	 * [listGetamzUserInfo description]
	 * @Author   willage
	 * @DateTime 2016-08-03T16:40:47+0800
	 * @param    [type]                   $eagle_platform_user_id [description]
	 * @return   [type]                                           [description]
	 */
	//public static function listGetamzUserInfo($eagle_platform_user_id){
	// 	$connection = \Yii::$app->db;
	// 	$command = $connection->createCommand("SELECT c.uid,a.eagle_platform_user_id,a.site_id,a.platform_user_id,b.access_key_id,b.secret_access_key FROM `saas_amazon_autosync_v2` a,`saas_amazon_user_marketplace` b ,`saas_amazon_user` c WHERE  a.eagle_platform_user_id = b.amazon_uid AND a.eagle_platform_user_id=".$eagle_platform_user_id." AND a.platform_user_id=c.merchant_id LIMIT 1");
	// 	$command->execute();
	// 	$tmpobj = $command->queryone();
	// 	return $tmpobj;
	// }//end listGetamzUserInfo
	public static function listGetamzUserInfo($SAA_obj){
		$amazonUserId=$SAA_obj->eagle_platform_user_id;
		$merchantId=$SAA_obj->platform_user_id;
		//获取  $access_key_id和 $secret_access_key
		$amazonAccessInfo=SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid"=>$amazonUserId))->asArray()->one();
		//获取puid
		$amazonUserInfo=SaasAmazonUser::find()->where("merchant_id=:merchant_id",array(":merchant_id"=>$merchantId))->one();
		if ($amazonUserInfo<>null) $puid=$amazonUserInfo->uid; else $puid=0; 

		$amazonAccessInfo=SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid"=>$amazonUserId))->asArray()->one();

		$tmpobj=array(
		      // "uid"=>$SAA_obj->id,
		      "uid"=>$amazonUserInfo->uid,
		      "eagle_platform_user_id"=>$SAA_obj->eagle_platform_user_id,
		      "platform_user_id"=>$SAA_obj->platform_user_id,
		      "site_id"=>$SAA_obj->site_id,
		      "access_key_id"=>$amazonAccessInfo["access_key_id"],
		      "secret_access_key"=>$amazonAccessInfo["secret_access_key"],
	          "mws_auth_token"=>$amazonAccessInfo["mws_auth_token"],
		);
		return $tmpobj;
	}//end listGetamzUserInfo
	/**
	 * [listSaveSAASamzAutosync description]
	 * @Author   willage
	 * @DateTime 2016-08-03T16:53:32+0800
	 * @param    [type]                   $SAA_objone [description]
	 * @param    [type]                   $ret        [description]
	 * @param    [type]                   $type       [description]
	 * @return   [type]                               [description]
	 */
	public static function listSaveSAASamzAutosync($SAA_objone,$fetch_begin_time,$fetch_end_time,$ret,$retryCount,$message,$type,$nowTime){
		$NextExeInter=empty($SAA_objone->execution_interval)?3600:$SAA_objone->execution_interval;//同一个用户，2次同步间的时间间隔（秒为单位）
		$SAA_objone->next_execute_time = $nowTime+$NextExeInter;
		$SAA_objone->fetch_begin_time = $fetch_begin_time;
		$SAA_objone->fetch_end_time = $fetch_end_time;
		$SAA_objone->update_time = $nowTime;
		if($ret===true) { // 成功同步
			$SAA_objone->process_status = 2;
			//$SAA_objone->slip_window_size =0;
			$SAA_objone->err_cnt=0;
			$SAA_objone->err_msg ="";
			if ($type=="amzOldUnshippedAll"||$type=="amzOldNotFbaNotUnshipped"||$type=="amzOldFbaNotUnshipped") {
				$SAA_objone->last_finish_time = $fetch_begin_time;
				if ($SAA_objone->last_finish_time==$SAA_objone->deadline_time) {
					$SAA_objone->status = 2;//永久停止
				}
			}else if ($type=="amzNewNotFba"||$type=="amzNewFba"){
				$SAA_objone->last_finish_time = $fetch_end_time;
			}
			//低优先级区分活跃用户
			if ($type=="amzOldNotFbaNotUnshipped"||$type=="amzOldFbaNotUnshipped"||$type=="amzNewFba") {
				$user=SaasAmazonUser::find()->select(["uid"])->where(["merchant_id"=>$SAA_objone->platform_user_id])->one();
				// $lastTouch=UserLastActionTimeHelper::getLastTouchTimeByPuid($user["uid"]);
				$lastTouch = date('Y-m-d H:i:s',time());
				if (empty($lastTouch))
					$lastTouch = date('Y-m-d H:i:s',strtotime('-3 days'));

				if ($type=="amzOldNotFbaNotUnshipped"||$type=="amzOldFbaNotUnshipped") {
					if ($lastTouch <= date('Y-m-d H:i:s',strtotime('-30 days')) )
						$SAA_objone->execution_interval = 3600*24*2+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-20 days')) )
						$SAA_objone->execution_interval = 3600*12*3+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-14 days')) )
						$SAA_objone->execution_interval = 3600*24+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-10 days')) )
						$SAA_objone->execution_interval = 3600*15+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-7 days')) )
						$SAA_objone->execution_interval = 3600*12+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-3 days')) )
						$SAA_objone->execution_interval = 3600*6+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-1 days')) )
						$SAA_objone->execution_interval = 6400+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',time()) )
						$SAA_objone->execution_interval = 4000+rand(0,120);
				}
				if ($type=="amzNewFba") {
					if ($lastTouch <= date('Y-m-d H:i:s',strtotime('-30 days')) )
						$SAA_objone->execution_interval = 3600*24*2+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-14 days')) )
						$SAA_objone->execution_interval = 3600*24+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-7 days')) )
						$SAA_objone->execution_interval = 3600*12+rand(0,120);
					elseif ($lastTouch <= date('Y-m-d H:i:s',strtotime('-3 days')) )
						$SAA_objone->execution_interval = 3200+rand(0,60);
					elseif ($lastTouch <= date('Y-m-d H:i:s',time()) )
						$SAA_objone->execution_interval = 3000+rand(0,60);
				}
				echo "gaptime : ".$SAA_objone->execution_interval.PHP_EOL;
				$SAA_objone->next_execute_time = $nowTime+$SAA_objone->execution_interval;
			}
			$SAA_objone->save(false);
		}else{
			if ($retryCount==-3){//订单过多处理,滑窗设为1天
				$SAA_objone->err_msg =$message;
				$SAA_objone->process_status = 3;
				$SAA_objone->err_cnt += 1;
				if(!empty($SAA_objone->slip_window_size)){// 设置了化窗依然报错，折半再减
				    if($SAA_objone->slip_window_size <= 3600){// 一个小时的订单依然过多的话 要人工特殊处理
				    }else{
				        $SAA_objone->slip_window_size = round($SAA_objone->slip_window_size / 2);
				    }
				}else{
					$SAA_objone->slip_window_size=24*3600;
				}
				
				$SAA_objone->save(false);
			}else{
				$SAA_objone->err_msg = $message;
				$SAA_objone->process_status = 3;
				if (strpos($message,"RequestThrottled")===false){
				    $SAA_objone->err_cnt += 1;
				}else{//访问超限---需要控制下次访问的时间
				    $SAA_objone->next_execute_time+=300;
				}
				$SAA_objone->save(false);
			}
		}

	}//end listSaveSAASamzAutosync


	/**
	 * 把amazon的订单信息header和items 同步到eagle系统中user_库的od_order和od_order_item 
	 * @param unknown $orderHeaderInfo
	 * @param unknown $orderItems
	 * @param unknown $uid
	 * @param unknown $merchantId
	 * @param unknown $marketplaceId
	 * @return multitype:number NULL |Ambigous <\eagle\modules\order\helpers\订单id, multitype:number string >
	 */
	public static function itemsSaveAmazonOrderToEagle($orderHeaderInfo,$orderItems,$uid,$merchantId,$marketplaceId,$eagleOrderId=-1,$odType='MFN'){
		
		//！！！！！！注意：如果订单状态为取消 header和item信息量少				
		/*
		 * header信息
		 * {"AmazonOrderId":"171-1288495-6303559","PurchaseDate":"2015-04-12T20:40:27Z","LastUpdateDate":"2015-04-14T06:22:58Z","Status":"Canceled",
		 * "SalesChannel":"Amazon.fr","ShipServiceLevel":"Std FR Dom","Currency":"EUR","Amount":"38.99","NumberOfItemsShipped":"0","NumberOfItemsUnshipped":"0",
		 * "ShipmentServiceLevelCategory":"Standard","EarliestShipDate":"2015-04-12T22:00:00Z","LatestShipDate":"2015-04-14T21:59:59Z","FulfillmentChannel":"MFN",
		 * "MarketplaceId":"A13V1IB3VIYZZH","ShippedByAmazonTFM":"false","OrderType":"StandardOrder","type":"MFN"}
         *
		 * 如果是FBA的订单，信息可能更少。 如：没有Amount
		 *
		 * 
		 * item信息		  
		 * [ASIN] => B00KXXMMFA
		 * [SellerSKU] => W13000000
		 * [OrderItemId] => 3098360
		 * [Title] => Nillkin H+ 
		 * [QuantityOrdered] => 1
		 * [QuantityShipped] => 0
		 */		
		
		//根据item信息，计算order header subtotal,shipping cost,total discount 这3个信息在amazon的原始header中没有包括		 
		list($subtotal,$shippingCost,$totalDiscount)=self::_caculateTotalAmount($orderItems);
		
		//整理导入eagle平台的订单信息
		$reqParams=array();
		
		$amazonOrderId=$orderHeaderInfo['AmazonOrderId'];

		//eagle的订单状态
		$order_status=self::$AMAZON_EAGLE_ORDER_STATUS_MAP[$orderHeaderInfo["Status"]];
		
		$is_manual_order=0;
		/*if (strtolower($orderHeaderInfo["Status"])=="canceled"){
				//挂起，需要及时处理的订单，可能不需要发货
				$is_manual_order = 1;
		}*/
		//订单类型 MFN或AFN(FBA)----------非FBA的订单，默认不传入order_type 参数或该参数值传入为空
		print_r($orderHeaderInfo);
		/**
		 * willage-20171219,订单类型会错误 (AFN变成MFN),改成直接传类型
		 */
		$order_type = $odType;
		// if (!isset($orderHeaderInfo["type"]) or $orderHeaderInfo["type"]=="") $order_type="MFN";
		// else $order_type=$orderHeaderInfo["type"];
		
		$consignee_country="";
		if (isset($orderHeaderInfo['CountryCode'])){
			$sysCountry=SysCountry::findOne(['country_code'=>$orderHeaderInfo['CountryCode']]);
			if ($sysCountry<>null) $consignee_country=$sysCountry->country_en;			
		}
		
		

		//1.  订单header信息
		$order_arr=array(//主订单数组
				'order_status'=>$order_status,
				'order_source_status'=>$orderHeaderInfo["Status"],				
				'is_manual_order'=>$is_manual_order,				
				'order_source'=>'amazon',				
				'order_type'=>$order_type,  //订单类型如amazon FBA订单
				'order_source_site_id'=>self::$AMAZON_MARKETPLACE_REGION_CONFIG[$marketplaceId],
				
				'order_source_order_id'=>$orderHeaderInfo['AmazonOrderId'],  //订单来源平台订单号
				'selleruserid'=>$merchantId,
				'source_buyer_user_id'=>isset($orderHeaderInfo['BuyerName'])?$orderHeaderInfo['BuyerName']:'',	//来源买家用户名			
				'order_source_create_time'=>strtotime($orderHeaderInfo["PurchaseDate"]), //时间戳
				'subtotal'=>$subtotal,
				'shipping_cost'=>$shippingCost,
				'grand_total'=>isset($orderHeaderInfo['Amount'])?$orderHeaderInfo['Amount']:0,
				'discount_amount'=>$totalDiscount,
				'currency'=>isset($orderHeaderInfo['Currency'])?$orderHeaderInfo['Currency']:'USD', //TODO currency需要提供，不能为空，不然导入订单到eagle接口会有问题
				'consignee'=>isset($orderHeaderInfo['Name'])?$orderHeaderInfo['Name']:'',
				'consignee_postal_code'=>isset($orderHeaderInfo['PostalCode'])?$orderHeaderInfo['PostalCode']:'',
				'consignee_city'=>isset($orderHeaderInfo['City'])?$orderHeaderInfo['City']:'', 
				'consignee_phone'=>isset($orderHeaderInfo['Phone'])?$orderHeaderInfo['Phone']:'',
				//'consignee_mobile'=>"",
				'consignee_email'=>isset($orderHeaderInfo['BuyerEmail'])?$orderHeaderInfo['BuyerEmail']:'',
				'consignee_country'=>$consignee_country,
				'consignee_country_code'=>isset($orderHeaderInfo['CountryCode'])?$orderHeaderInfo['CountryCode']:'',
				'consignee_province'=>isset($orderHeaderInfo['State'])?$orderHeaderInfo['State']:'',
				'consignee_address_line1'=>isset($orderHeaderInfo['AddressLine1'])?$orderHeaderInfo['AddressLine1']:'',
				'consignee_address_line2' =>isset($orderHeaderInfo['AddressLine2'])?$orderHeaderInfo['AddressLine2']:'',
				'consignee_address_line3' =>isset($orderHeaderInfo['AddressLine3'])?$orderHeaderInfo['AddressLine3']:'',
				'paid_time'=>strtotime($orderHeaderInfo["PurchaseDate"]), //时间戳 , PurchaseDate是amazon 订单创建时间
				//amazon是没有返回发货时间的！！！！！
				//当订单是FBA的时候，LatestDeliveryDate貌似为空
				//'delivery_time'=>isset($orderHeaderInfo['LatestDeliveryDate'])?strtotime($orderHeaderInfo['LatestDeliveryDate']):0, //时间戳
				//'user_message'=>json_encode($OrderById['orderMsgList']),
				//'orderShipped'=>$orderShipped,
				// strtotime 对这种时间2016-03-16T06:59:59Z 返回utc 0时间戳
				'fulfill_deadline'=>isset($orderHeaderInfo['LatestShipDate'])?strtotime($orderHeaderInfo['LatestShipDate']):'',// dzt20160312 加最迟发货时间 到订单表做提示
				// dzt20160519 add 买家运输服务 for 匹配
				'order_source_shipping_method'=>isset($orderHeaderInfo['ShipmentServiceLevelCategory'])?$orderHeaderInfo['ShipmentServiceLevelCategory']:'',
		);
		
		
		//print_r($order_arr);  //lolotest
		
		
		
		//2. 订单的items信息
		$userMessage = '';
		$orderitem_arr=array();//订单商品数组
		foreach ($orderItems as $one){		
			if (isset($one['SellerSKU'])) {//VAT_TAX作为item时候，会有sku为''
				if ($one['SellerSKU']==''||$one['SellerSKU']==NULL) {
					$sku_tmp="skunull";
				}else{
					$sku_tmp=$one['SellerSKU'];
				}
			}else{
				$sku_tmp="skunull";
			}
			$orderItemsArr = array(
					'order_source_order_id'=>$orderHeaderInfo['AmazonOrderId'],  //订单来源平台订单号
					'order_source_order_item_id'=>$one['OrderItemId'],
					//'order_source_transactionid'=>$one['childid'],//订单来源交易号或子订单号
					'order_source_itemid'=>$one['ASIN'],//产品ID listing的唯一标示
					
					'asin'=>$one['ASIN'],  //应该没有用的字段//lolo add -- 速卖通貌似没有的
					'sent_quantity'=>$one['QuantityShipped'],  //lolo add -- 速卖通貌似没有的
					'promotion_discount'=>isset($one['PromotionDiscount'])?$one['PromotionDiscount']:0,   //lolo add -- 速卖通貌似没有的
					'shipping_price'=>isset($one['ShippingPrice'])?$one['ShippingPrice']:0,  //lolo add -- 速卖通貌似没有的
					'shipping_discount'=>isset($one['ShippingDiscount'])?$one['ShippingDiscount']:0,  //lolo add -- 速卖通貌似没有的
					
					'sku'=>$sku_tmp,//商品编码
					'price'=>isset($one['ItemPrice'])?$one['ItemPrice']:0,//如果订单是取消状态，该字段amazon不会返回
					'ordered_quantity'=>isset($one['QuantityOrdered'])?$one['QuantityOrdered']:0,//下单时候的数量
					'quantity'=>isset($one['QuantityOrdered'])?$one['QuantityOrdered']:0,  //需发货的商品数量,原则上和下单时候的数量相等，订单可能会修改发货数量，发货的时候按照quantity发货
					'product_name'=>$one['Title'],//下单时标题
					'photo_primary'=>isset($one['SmallImageUrl'])?$one['SmallImageUrl']:"",//商品主图冗余
				//	'desc'=>$one['memo'],//订单商品备注,
				//	'product_attributes'=>$attr_str,//商品属性
					//'product_unit'=>$one['productunit'],//单位
					//'lot_num'=>$one['lotnum'],//单位数量
				//	'product_url'=>$one['productsnapurl'],//商品url
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
		print_r($order_arr);
		//3.  订单header和items信息导入到eagle系统
	//	\Yii::info("before OrderHelper::importPlatformOrder info:".json_encode($myorder_arr,true),"file");
		try{
	    	$result =  OrderHelper::importPlatformOrder($myorder_arr,$eagleOrderId);
		}catch(\Exception $e){
			echo "OrderHelper::importPlatformOrder fails. Exception  \n";
			\Yii::error("OrderHelper::importPlatformOrder fails.  amazonId=$amazonOrderId  Exception error:".$e->getMessage()." trace:".$e->getTraceAsString(),"file");
		
			return ['success'=>1,'message'=>$e->getMessage()];			
		}
		echo "after OrderHelper::importPlatformOrder result:".print_r($result,true);
		// ！！！注意  result['success']的返回值。    0----表示ok,1---表示fail
		if ($result['success']===1){ 
		//	SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","OrderHelper::importPlatformOrder fails. error:".$result['message'],"error");
			\Yii::error("OrderHelper::importPlatformOrder fails. amazonId=$amazonOrderId error:".$result['message'],"file");
		}
		
		

		return $result;
	}//end itemsSaveAmazonOrderToEagle


	//根据item信息，计算order header subtotal,shipping cost,total discount 这3个信息在amazon的原始header中没有包括
	public static function _caculateTotalAmount($orderItems) {
		$subtotal=0;
		$shippingCost=0;
		$totalDiscount=0;
		foreach($orderItems as $item){
			$itemPrice=isset($item["ItemPrice"])?$item["ItemPrice"]:0;
			$subtotal=$subtotal+$itemPrice*$item["QuantityOrdered"];
			$shipDiscount=isset($item["ShippingDiscount"])?$item["ShippingDiscount"]:0;
			$promotionDiscount=isset($item["PromotionDiscount"])?$item["PromotionDiscount"]:0;
			$shippingPrice=isset($item["ShippingPrice"])?$item["ShippingPrice"]:0;
			
			$totalDiscount=$totalDiscount+$shipDiscount+$promotionDiscount;
			$shippingCost=$shippingCost+$shippingPrice;
		}
	
		return array($subtotal,$shippingCost,$totalDiscount);
	}//end _caculateTotalAmount









}
?>