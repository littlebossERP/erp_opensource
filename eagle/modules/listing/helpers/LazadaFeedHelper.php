<?php
namespace eagle\modules\listing\helpers;
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
use eagle\modules\listing\models\LazadaListing;
use eagle\models\LazadaFeedList;
use eagle\models\LazadaUserCheckControl;
use eagle\modules\util\models\UserBackgroundJobControll;
use common\helpers\Helper_Array;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;

/**
 +------------------------------------------------------------------------------
 * Lazada 数据同步类 主要执行订单数据同步
 +------------------------------------------------------------------------------
 */

class LazadaFeedHelper{
	
	const PRODUCT_CREATE="product_create";
	const PRODUCT_UPDATE="product_update";
	const PRODUCT_IMAGE_UPLOAD="product_image_upload";
	
	// for excel 导入添加的type
	const PRODUCT_CREATE2 = "product_create2";
	const PRODUCT_UPDATE2 ="product_update2";
	const PRODUCT_IMAGE_UPLOAD2 ="product_image_upload2";
	const PRODUCT_IMAGE_UPDATE2 ="product_image_update2";
	const PRODUCT_DELETE2 ="product_delete2";
	
	// 同步状态 process_status :  0--初始状态，1---已检查, 待调用回调函数，2--已检查等,已调用回调函数，3---已经进入人工待审核队列，7----运行有异常，需要后续重试
	const STATUS_INITIAL=0;
	const STATUS_CHECKED=1;
	const STATUS_CHECKED_CALLED=2;
	const STATUS_PENDING=3;
	//const STATUS_PENDING_CALLED=4;
	const STATUS_FAIL=7;
	
	
	
	public static $cronJobId=0;
	private static $lazadaGetOrderListVersion = null;
	
	/**
	 * 根据不同的type返回检查的时间间隔。 秒为单位
	 */
	public static function getCheckIntervalByType($type){
		if ($type==self::PRODUCT_CREATE){
			return 1*60;
		}
// 		else if ($type==self::PRODUCT_UPDATE){// dzt20160108 调整修改后第一次检查feed 执行情况间隔时间 从20分钟到5分钟
// 			return 5*60;
// 		}else if ($type==self::PRODUCT_IMAGE_UPLOAD){
// 			return 5*60;
// 		}				
// 		return 30*60;
		
		return 5*60;
	}
	
	/**
	 * 
	 * 插入一个待确认的创建商品的feed到lazada_feed_list
	 * @param unknown $puid
	 * @param unknown $lazadaSaasUserId------表lazada_saas_user 的id
	 * @param unknown $site
	 * @param unknown $feedId
	 * @param unknown $type------ LazadaFeedHelper::PRODUCT_CREATE PRODUCT_UPDATE.....
	 * @return boolean
	 */
	public static function insertFeed($puid,$lazadaSaasUserId,$site,$feedId,$type){
	
	//	self::updateCheckJob($lazadaSaasUserId, self::PRODUCT_CREATE, $puid );		
		
		$nowTime=time();
		$nextExecutionTime=$nowTime+self::getCheckIntervalByType($type);
		
		$lazadaFeedList=new LazadaFeedList;
		$lazadaFeedList->Feed=$feedId;
		$lazadaFeedList->puid=$puid;
		$lazadaFeedList->lazada_saas_user_id=$lazadaSaasUserId;
		$lazadaFeedList->type=$type;
		$lazadaFeedList->create_time=$nowTime;
		$lazadaFeedList->update_time=$nowTime;
		$lazadaFeedList->process_status=0;
		$lazadaFeedList->next_execution_time=$nextExecutionTime;
		$lazadaFeedList->site=$site;
		return $lazadaFeedList->save(false);
			
	}
	
	/**
	 * 更新后台检查job的配置
	 * 后台检查job，包括 feed的检查和getProduct检查
	 */
	public static function updateCheckJob($puid,$lazadaSaasUserId,$type){
		$nowTime=time();
		if ($type==self::PRODUCT_CREATE){
			$nextExecutionTime=$nowTime+10*60;
			$type="feed_check";			
		}else if ($type==self::PRODUCT_UPDATE){
			$nextExecutionTime=$nowTime+120*60;
			$type="feed_check";
		}
		
		$lazadaCheckObj=LazadaUserCheckControl::find()->where(["lazada_saas_user_id"=>$lazadaSaasUserId,"type"=>$type])->one();
		if ($lazadaCheckObj===null){
			$lazadaCheckObj=new LazadaUserCheckControl;
	//		if ($type==="feed_check"){
				$lazadaCheckObj->need_check=1;
				$lazadaCheckObj->next_execution_time=$nextExecutionTime;
				$lazadaCheckObj->update_time=$nowTime;
				$lazadaCheckObj->type=$type;
				$lazadaCheckObj->process_status=0;
				$lazadaCheckObj->puid=$puid;
				$lazadaCheckObj->lazada_saas_user_id=$lazadaSaasUserId;
				return $lazadaCheckObj->save(false);
		//	}
		}else{ //已经有需要检查的job
			//job正在运行
			if ($lazadaCheckObj->process_status==1){
				$lazadaCheckObj->need_another_check=1;
				$lazadaCheckObj->save(false);
				return;
			}				
			
			$lazadaCheckObj->update_time=$nowTime;
			$lazadaCheckObj->need_check=1;
			//取下次运行时间短的来
			if ($nextExecutionTime<$lazadaCheckObj->next_execution_time) $lazadaCheckObj->next_execution_time=$nextExecutionTime;				
			$lazadaCheckObj->save(false);
		
		}
		
		
	}
	
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
	 * 获取所有lazada用户的api访问信息。 email,token,销售站点
	 */
	private static function getAllLazadaAccountInfoMap(){
		$lazadauserMap=array();
		
		$lazadaUsers=SaasLazadaUser::find()->all();
		foreach($lazadaUsers as $lazadaUser){
			$lazadauserMap[$lazadaUser->lazada_uid]=array(
					"userId"=>$lazadaUser->platform_userid,
					"apiKey"=>$lazadaUser->token,
					"countryCode"=>$lazadaUser->lazada_site
			);			
		}
		
		return $lazadauserMap;
		
	}
	/**
	 * 获取指定ladazaUid的api访问信息。 email,token,销售站点
	 * @param unknown $lazadaUid
	 * @return 
	 */
	private static function _getLazadaAccountInfo($lazadaUid){
		$lazadaUser=SaasLazadaUser::findOne(["lazada_uid"=>$lazadaUid]);
		if ($lazadaUser===null) return null;
		
		$returnInfo=array(
					"userId"=>$lazadaUser->platform_userid,
					"apiKey"=>$lazadaUser->token,
					"countryCode"=>$lazadaUser->lazada_site
			);		
		return $returnInfo;	
	}
	
	
	
	/**
	 * 先判断是否真的抢到待处理账号
	 * @param 
	 * @return null或者$SAA_obj , null表示抢不到记录
	 */
	private static function _lockCheckControl($rowId){
		$nowTime=time();
		$connection=\Yii::$app->db;
		$command = $connection->createCommand("update lazada_feed_list set is_running=1 where is_running=0 and id=".$rowId) ;
	//	$affectRows = $command->execute();
	//	if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
		// 抢到记录
		$SAA_obj = LazadaFeedList::findOne($rowId);
		return $SAA_obj;
	}
	
	
	/**
	 *  后台触发---检查所有由小老板提交的但没有完成的feed的status。	  
	 */
	public static function checkAllFeedStatus(){
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		//$ret=self::checkNeedExitNot();
		//if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=\Yii::$app->db;
		
		//2. 查看是否有需要检查的feed         
		$hasGotRecord=false;
		$nowTime=time();
		// 同步状态 process_status :  0--初始状态，1---已检查, 待调用回调函数，2--已检查等,已调用回调函数，3---已经进入人工待审核队列，7----运行有异常，需要后续重试
		//TODO 去除为了测试的设置
		$sqlStr='select `id` from  `lazada_feed_list`  '.
		' where  is_running=0 and error_times<10 and (process_status='.self::STATUS_INITIAL.' or process_status='.self::STATUS_FAIL.') AND next_execution_time<'.$nowTime ;
		//	' where  is_running=0 AND next_execution_time<'.$nowTime ;
    		//  ' where  next_execution_time<'.$nowTime ;
		\Yii::info("sql:$sqlStr","file");
		$command=$connection->createCommand($sqlStr);		
	
		$allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			echo "checkAllFeedStatus dataReader->read() id:".$row['id']."\n";
		// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockCheckControl($row['id']);
			
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况	
			$hasGotRecord=true; 
	
			//3. 找到待检查的feed，整理参数，返回proxy	
			$feedId=$SAA_obj->Feed;		
			$config=$allLazadaAccountsInfoMap[$SAA_obj->lazada_saas_user_id];			
			$reqParams=array("FeedID"=>$feedId);
			\Yii::info("before LazadaInterface_Helper::getFeedStatus feedId:$feedId","file");
			$result=LazadaInterface_Helper::getFeedStatus($config,$reqParams);
			//TODO 当稳定之后需要删除的log
			\Yii::info("LazadaInterface_Helper::getFeedStatus feedId:$feedId result:".print_r($result,true),"file");	
			
			//4. 根据返回结果做处理
			//4.1 异常处理
			$nowTime=time();
			$errorMessage="";			
			if ($result["success"]===false){
				$errorMessage=$result["message"];				
			}else if (!isset($result["response"]["feeds"])){
				$errorMessage="no response feeds";
			}else if (count($result["response"]["feeds"])<=0){
				$errorMessage="no response feeds subelement";
			}			
		    if ($errorMessage<>""){
		    	$SAA_obj->next_execution_time=$nowTime+self::getCheckIntervalByType($SAA_obj->type);
		    	$SAA_obj->update_time=$nowTime;
		    	$SAA_obj->message=$errorMessage;
		    	$SAA_obj->error_times=$SAA_obj->error_times+1;
		    	$SAA_obj->is_running=0;
		    	$SAA_obj->process_status=self::STATUS_FAIL;
		    	$SAA_obj->save(false);
		    	\Yii::info("checkAllFeedStatus  feedId:$feedId STATUS_FAIL save $errorMessage","file");
		    	continue;
		    } 
		    
			
			$feedInfoRet=$result["response"]["feeds"][0];			
			$statusRet=$feedInfoRet["Status"];
			
			\Yii::info("checkAllFeedStatus feedId:$feedId statusRet:$statusRet","file");
			
			//4.2 状态为 排队中
			if ( $statusRet=="Queued"){
				if ($SAA_obj->Status==""){
					//第一次拉取结果
					$SAA_obj->Status=$statusRet;
					//2015-08-26T22:18:52+0800 时间转成 时间戳
					$SAA_obj->CreationDate=TimeUtil::getTimestampFromISO8601($feedInfoRet["CreationDate"]);
					$SAA_obj->UpdatedDate=TimeUtil::getTimestampFromISO8601($feedInfoRet["UpdatedDate"]);
					$SAA_obj->Source=$feedInfoRet["Source"];
					$SAA_obj->Action=$feedInfoRet["Action"];
				}
				
				$SAA_obj->next_execution_time=$nowTime+self::getCheckIntervalByType($SAA_obj->type);
				$SAA_obj->update_time=$nowTime;
				$SAA_obj->is_running=0;
				$SAA_obj->save(false);
				continue;				
			}
			//4.3 状态为已完成
			if ($statusRet=="Finished"){
				
				$SAA_obj->TotalRecords=$feedInfoRet["TotalRecords"];
				$SAA_obj->ProcessedRecords=$feedInfoRet["ProcessedRecords"];
				$SAA_obj->FailedRecords=$feedInfoRet["FailedRecords"];
				if ($SAA_obj->FailedRecords>0){
					$SAA_obj->FeedErrors=json_encode($feedInfoRet["FeedErrors"]);
				}
				if ($SAA_obj->Status != "Finished"){
					//第一次拉取结果
					$SAA_obj->Status=$statusRet;
					$SAA_obj->CreationDate=TimeUtil::getTimestampFromISO8601($feedInfoRet["CreationDate"]);
					$SAA_obj->UpdatedDate=TimeUtil::getTimestampFromISO8601($feedInfoRet["UpdatedDate"]);
					$SAA_obj->Source=$feedInfoRet["Source"];
					$SAA_obj->Action=$feedInfoRet["Action"];
				}
				$SAA_obj->update_time=$nowTime;
				$SAA_obj->process_status=self::STATUS_CHECKED;
				\Yii::info("feedId:$feedId STATUS_CHECKED","file");
				$SAA_obj->save(false);
				
				\Yii::info("checkAllFeedStatus feedId:$feedId self::STATUS_CHECKED save","file");
				
				
				//TODO call api---只有finish的状态才调用类型相对应的回调函数。这里会根据不同的type调用对应的回调函数
				$SAA_obj->is_running=0;
				// 界面刊登的产品刊登/修改
				if ($SAA_obj->type==self::PRODUCT_CREATE or $SAA_obj->type==self::PRODUCT_UPDATE) {
					$reqParams=array("puid"=>$SAA_obj->puid,"feedId"=>$SAA_obj->Feed,"totalRecords"=>$SAA_obj->TotalRecords);
					if ($SAA_obj->FailedRecords>0){
						$reqErrors=array();
						if(isset($feedInfoRet["FeedErrors"]["Error"]["SellerSku"])){
							$errorsInfo = array($feedInfoRet["FeedErrors"]["Error"]);
						}else{
							$errorsInfo=$feedInfoRet["FeedErrors"]["Error"];
						}
						
						// dzt20151203 FailedRecords 1  but $feedInfoRet["FeedErrors"]["Error"] is array containing 2 or more errors...
// 						if ($SAA_obj->FailedRecords==1) {
// 							$reqErrors[$errorsInfo["SellerSku"]]=$errorsInfo["Message"];
// 						}else{
							foreach($errorsInfo as $feedErrorInfo){
								if(!empty($reqErrors[$feedErrorInfo["SellerSku"]])){
									$reqErrors[$feedErrorInfo["SellerSku"]]=$reqErrors[$feedErrorInfo["SellerSku"]]."<br />".$feedErrorInfo["Message"];
								}else{
									$reqErrors[$feedErrorInfo["SellerSku"]]=$feedErrorInfo["Message"];						
								}
							}						
// 						}
						$reqParams["failReport"]=$reqErrors;
					}		
							
					if ($SAA_obj->type==self::PRODUCT_CREATE){
						\Yii::info("LazadaCallbackHelper::productCreate before feedId:$feedId reqParams:".print_r($reqParams,true),"file");
						$ret=LazadaCallbackHelper::productCreate($reqParams);
						\Yii::info("LazadaCallbackHelper::productCreate after feedId:$feedId ret:".print_r($ret,true),"file");
					}else{ //$SAA_obj->type==self::PRODUCT_UPDATE
						// 回调修改listing 修改状态信息
						\Yii::info("LazadaCallbackHelper::PRODUCT_UPDATE before feedId:$feedId reqParams:".print_r($reqParams,true),"file");
						$ret=LazadaCallbackHelper::productUpdate($reqParams,$config);// dzt20160119 带上config 参数立即获取产品信息
						\Yii::info("LazadaCallbackHelper::PRODUCT_UPDATE after feedId:$feedId ret:".print_r($ret,true),"file");
					}
				    
				    
				    if ($ret===true){
				    	$SAA_obj->process_status=self::STATUS_CHECKED_CALLED;
				    	\Yii::info("feedId:$feedId STATUS_CHECKED_CALLED","file");
				    	$SAA_obj->save(false);
				    }else{
				    	$SAA_obj->message="LazadaCallbackHelper::productCreate";
				    	$SAA_obj->error_times=$SAA_obj->error_times+1;				    
				    	$SAA_obj->process_status=self::STATUS_FAIL;
				    	$SAA_obj->save(false);
				    	\Yii::info("feedId:$feedId STATUS_FAIL checkAllFeedStatus  productCreate  save","file");
				    }
				    continue;				    
				}	
				
				// 界面刊登的图片上传
				if ($SAA_obj->type==self::PRODUCT_IMAGE_UPLOAD) {
					//(1)图片upload已经完成（不一定成功）
					$reqParams=array("puid"=>$SAA_obj->puid,"feedId"=>$SAA_obj->Feed,"totalRecords"=>$SAA_obj->TotalRecords);
						
					if ($SAA_obj->FailedRecords>0){
						$reqErrors=array();
						
						if (isset($feedInfoRet["FeedErrors"]["Error"]["SellerSku"])){// 只有一个feed时，返回结构可能不一样 
							$errorsInfo = array($feedInfoRet["FeedErrors"]["Error"]);
						}else{
							$errorsInfo = $feedInfoRet["FeedErrors"]["Error"];
						}
						
						foreach($errorsInfo as $feedErrorInfo){
							$reqErrors[$feedErrorInfo["SellerSku"]]=$feedErrorInfo["Message"];
						}
						$reqParams["failReport"]=$reqErrors;
					}
					
					//(2)图片upload成功的话，回调函数调用
					\Yii::info("LazadaCallbackHelper::imageUpload reqParams:".print_r($reqParams,true),"file");
					list($ret,$sellerSkus)=LazadaCallbackHelper::imageUpload($reqParams);
					\Yii::info("LazadaCallbackHelper::imageUpload sellerSku:".print_r($sellerSkus,true),"file");					
					if ($ret===false){  //LazadaCallbackHelper::productImageUpload 失败
						//$SAA_obj->process_status=2;
						$SAA_obj->error_times=$SAA_obj->error_times+1;
						$SAA_obj->message="LazadaCallbackHelper::imageUpload fails";
						$SAA_obj->save(false);
						continue;
					}
					$SAA_obj->process_status=self::STATUS_CHECKED_CALLED;
					\Yii::info("feedId:$feedId STATUS_CHECKED_CALLED","file");
					$SAA_obj->save(false);
						
					if(empty($sellerSkus)){//TODO 图片上传失败，不用检查是否进入人工审核,逻辑还要加强
					    \Yii::info("feedId:$feedId image upload fails","file");
						continue;
					}
					
					//(3)回调函数成功，检查是否已经人工审核状态
					$reqParams=array(
							"SkuSellerList"=>implode(",", $sellerSkus),
							"Filter"=>"pending"
					);	
										
					\Yii::info("LazadaInterface_Helper::getProducts before reqParams:".print_r($reqParams,true),"file");
					$result=LazadaInterface_Helper::getProducts($config,$reqParams);
					\Yii::info("LazadaInterface_Helper::getProducts result:".print_r($result,true),"file");
					if ($result["success"]===false or !isset($result["response"]) or !isset($result["response"]["products"]) ){
						//error
						$SAA_obj->next_execution_time=$nowTime+5*60;  
						$SAA_obj->update_time=$nowTime;
						$SAA_obj->message=$result["message"];
						$SAA_obj->error_times=$SAA_obj->error_times+1;
						$SAA_obj->is_running=0;
						$SAA_obj->process_status=self::STATUS_FAIL;
						$SAA_obj->save(false);
						
						\Yii::info("Error after imageupload LazadaInterface_Helper::getProducts sku:".implode(",", $sellerSkus).".  synId:".$SAA_obj->id." puid:".$SAA_obj->puid." ErrorMessage:".$SAA_obj->message,"file");	
					}
						
					$products=$result["response"]["products"];
					//(4)进入人工审核状态，回调函数调用  skuPendingMap=>array("sku1"=>0,"sku2"=>1....)   //0---表示不是pending，1----是pending
					$skuPendingMap = array();
					foreach ($sellerSkus as $oneSku){
						$skuPendingMap[$oneSku] = 0;
					}
					if(!empty($products)){
						foreach ($products as $pendingProd){
							if(in_array($pendingProd['SellerSku'], $sellerSkus)){
								$skuPendingMap[$pendingProd['SellerSku']] = 1;
							}
						}
					}
					$reqParams=array("puid"=>$SAA_obj->puid,"feedId"=>$SAA_obj->Feed,"skuPendingMap"=>$skuPendingMap);
					if (count($products)==0){
						\Yii::info("LazadaInterface_Helper::markPendingProduct before reqParams:".print_r($reqParams,true),"file");
						$ret=LazadaCallbackHelper::markPendingProduct($reqParams);
						\Yii::info("after LazadaInterface_Helper::markPendingProduct ret:".print_r($ret,true),"file");
					}else{
						$SAA_obj->process_status=self::STATUS_PENDING;
						\Yii::info("feedId:$feedId STATUS_PENDING","file");
						$SAA_obj->save(false);
						$reqParams["isPending"]=1;
						LazadaCallbackHelper::markPendingProduct($reqParams);
					}
					continue;
				}
				
				
				// 处理excel 导入结果信息，另外在backgroup job再捡起这些结果处理
				if (in_array($SAA_obj->type, [self::PRODUCT_CREATE2, self::PRODUCT_UPDATE2, self::PRODUCT_IMAGE_UPLOAD2, self::PRODUCT_IMAGE_UPDATE2, self::PRODUCT_DELETE2])) {
				    $SAA_obj->save(false);
				    \Yii::info(__FUNCTION__.",feedId:$feedId,type:".$SAA_obj->type." Finished check success.","file");
				    continue;
				}
				
			}			
			
			//4.4 lazada方面的api 失败: 可能是lazada方面的处理问题，导致产品创建失败，需重新刊登
			if($statusRet=="Error"){
				$SAA_obj->TotalRecords=$feedInfoRet["TotalRecords"];
				$SAA_obj->ProcessedRecords=$feedInfoRet["ProcessedRecords"];
				$SAA_obj->FailedRecords=$feedInfoRet["FailedRecords"];
				if ($SAA_obj->FailedRecords>0){
					$SAA_obj->FeedErrors=json_encode($feedInfoRet["FeedErrors"]);
				}
				if ($SAA_obj->Status != "Error"){
					//第一次拉取结果
					$SAA_obj->Status=$statusRet;
					$SAA_obj->CreationDate=TimeUtil::getTimestampFromISO8601($feedInfoRet["CreationDate"]);
					$SAA_obj->UpdatedDate=TimeUtil::getTimestampFromISO8601($feedInfoRet["UpdatedDate"]);
					$SAA_obj->Source=$feedInfoRet["Source"];
					$SAA_obj->Action=$feedInfoRet["Action"];
				}
				$SAA_obj->update_time=$nowTime;
				$SAA_obj->process_status=self::STATUS_CHECKED;
				\Yii::info("feedId:$feedId STATUS_CHECKED","file");
				$SAA_obj->save(false);
				\Yii::info("checkAllFeedStatus feedId:$feedId self::STATUS_CHECKED save","file");
				
				
				$SAA_obj->is_running=0;
				
				// 导入的不需处理 直接保存即可。
				if (in_array($SAA_obj->type, [self::PRODUCT_CREATE2, self::PRODUCT_UPDATE2, self::PRODUCT_IMAGE_UPLOAD2, self::PRODUCT_IMAGE_UPDATE2, self::PRODUCT_DELETE2])) {
				    $SAA_obj->save(false);
				    \Yii::info(__FUNCTION__.",feedId:$feedId,type:".$SAA_obj->type." Error check success.","file");
				    continue;
				}
				
				$reqParams=array("puid"=>$SAA_obj->puid,"feedId"=>$SAA_obj->Feed,"totalRecords"=>$SAA_obj->TotalRecords);
				$reqParams["failReport"]="lazada api error";
				
				$ret = false;
				if ($SAA_obj->type==self::PRODUCT_CREATE){
					\Yii::info("LazadaCallbackHelper::productCreate before feedId:$feedId reqParams:".print_r($reqParams,true),"file");
					$ret=LazadaCallbackHelper::productCreate($reqParams);
					\Yii::info("LazadaCallbackHelper::productCreate after feedId:$feedId ret:".print_r($ret,true),"file");
				}else if($SAA_obj->type==self::PRODUCT_UPDATE){
					// 回调修改listing 修改状态信息
					\Yii::info("LazadaCallbackHelper::PRODUCT_UPDATE before feedId:$feedId reqParams:".print_r($reqParams,true),"file");
					$ret=LazadaCallbackHelper::productUpdate($reqParams,$config);// dzt20160119 带上config 参数立即获取产品信息
					\Yii::info("LazadaCallbackHelper::PRODUCT_UPDATE after feedId:$feedId ret:".print_r($ret,true),"file");
				}else if ($SAA_obj->type==self::PRODUCT_IMAGE_UPLOAD) {
					\Yii::info("LazadaCallbackHelper::imageUpload reqParams:".print_r($reqParams,true),"file");
					list($ret,$sellerSkus)=LazadaCallbackHelper::imageUpload($reqParams);
					\Yii::info("LazadaCallbackHelper::imageUpload sellerSku:".print_r($sellerSkus,true),"file");
				}
				
				if ($ret===true){
					$SAA_obj->process_status=self::STATUS_CHECKED_CALLED;
					\Yii::info("feedId:$feedId STATUS_CHECKED_CALLED Error","file");
					$SAA_obj->save(false);
				}else{
					$SAA_obj->message="mark all product error fail.";
					$SAA_obj->error_times=$SAA_obj->error_times+1;
					$SAA_obj->next_execution_time=$nowTime+self::getCheckIntervalByType($SAA_obj->type);
					$SAA_obj->process_status=self::STATUS_FAIL;
					$SAA_obj->save(false);
					\Yii::info("feedId:$feedId STATUS_FAIL checkAllFeedStatus Error save","file");
				}
				continue;
			}
			
			$SAA_obj->TotalRecords=$feedInfoRet["TotalRecords"];
			$SAA_obj->ProcessedRecords=$feedInfoRet["ProcessedRecords"];
			$SAA_obj->FailedRecords=$feedInfoRet["FailedRecords"];
			if ($SAA_obj->FailedRecords>0){
				$SAA_obj->FeedErrors=json_encode($feedInfoRet["FeedErrors"]);
			}	
				
			$SAA_obj->next_execution_time=$nowTime+self::getCheckIntervalByType($SAA_obj->type);
			$SAA_obj->update_time=$nowTime;
			$SAA_obj->process_status=0;
			$SAA_obj->save(false);	
				
			
		}	
	}
	
	// 由于拉取问题导致或者其他问题导致feed无法正常拉取，要通知listing 
	public static function handleErrorFeed(){
		$connection=\Yii::$app->db;
		
		//2. 查看是否有需要检查的feed
		$hasGotRecord=false;
		$nowTime=time();
		// 同步状态 process_status :  0--初始状态，1---已检查, 待调用回调函数，2--已检查等,已调用回调函数，3---已经进入人工待审核队列，7----运行有异常，需要后续重试
		//TODO 去除为了测试的设置
		$sqlStr='select `id` from  `lazada_feed_list`  '.
				' where error_times>=10 ' ;
		//	' where  is_running=0 AND next_execution_time<'.$nowTime ;
		//  ' where  next_execution_time<'.$nowTime ;
		\Yii::info("sql:$sqlStr","file");
		$command=$connection->createCommand($sqlStr);
		
		$allLazadaAccountsInfoMap=self::getAllLazadaAccountInfoMap();
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			echo "checkAllFeedStatus dataReader->read() id:".$row['id']."\n";
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockCheckControl($row['id']);
				
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
			$hasGotRecord=true;
			$config=$allLazadaAccountsInfoMap[$SAA_obj->lazada_saas_user_id];
			$feedId=$SAA_obj->Feed;
			$reqParams=array("puid"=>$SAA_obj->puid,"feedId"=>$SAA_obj->Feed,"totalRecords"=>$SAA_obj->TotalRecords);
			$reqParams["failReport"]= "lazada api error";
			
			if ($SAA_obj->type==self::PRODUCT_CREATE){
				\Yii::info("LazadaCallbackHelper::productCreate before feedId:$feedId reqParams:".print_r($reqParams,true),"file");
				$ret=LazadaCallbackHelper::productCreate($reqParams);
				\Yii::info("LazadaCallbackHelper::productCreate after feedId:$feedId ret:".print_r($ret,true),"file");
			}else if($SAA_obj->type==self::PRODUCT_UPDATE){
				// 回调修改listing 修改状态信息
				\Yii::info("LazadaCallbackHelper::PRODUCT_UPDATE before feedId:$feedId reqParams:".print_r($reqParams,true),"file");
				$ret=LazadaCallbackHelper::productUpdate($reqParams,$config);// dzt20160119 带上config 参数立即获取产品信息
				\Yii::info("LazadaCallbackHelper::PRODUCT_UPDATE after feedId:$feedId ret:".print_r($ret,true),"file");
			}else if ($SAA_obj->type==self::PRODUCT_IMAGE_UPLOAD) {
				\Yii::info("LazadaCallbackHelper::imageUpload reqParams:".print_r($reqParams,true),"file");
				list($ret,$sellerSkus)=LazadaCallbackHelper::imageUpload($reqParams);
				\Yii::info("LazadaCallbackHelper::imageUpload sellerSku:".print_r($sellerSkus,true),"file");
			}
			
			$SAA_obj->delete();
		}
	}
	
	/**
	 *  后台触发----- 处理后台导入产品创建、修改、添加图片的的feed
	 */
	public static function checkImportFeed(){
	    $job_name = LazadaLinioJumiaProductFeedHelper::$check_feed_job_name;
	    $nowTime = time();
	    
	    //status： 0 未处理 ，1处理中 ，2完成 ，3失败
	    $jobObjs = UserBackgroundJobControll::find()
	    ->where('is_active="Y" AND (status=0 or status=3) AND job_name="' . $job_name . '" AND error_count<5')
        ->andWhere('next_execution_time<'.$nowTime)
	    ->orderBy('next_execution_time')->limit(20)->all(); // 多到一定程度的时候，就需要使用多进程的方式来并发拉取。
	    
	    $allAccountsInfoMap = LLJHelper::getBindAccountInfoMap();
	    if (!empty($jobObjs)) {
	        foreach ($jobObjs as $jobObj) {
	            $nowTime = time();
	            $attrs = array();
	            $attrs['status'] = 1;
	            $attrs['last_begin_run_time'] = $nowTime;
	            $affectRows = UserBackgroundJobControll::updateAll($attrs,['id'=>$jobObj->id, 'status'=>[0, 3]]);
	            $jobObj->refresh();
	             
	            if (empty($affectRows))//抢不到---如果是多进程的话，有抢不到的情况
	                continue;
	            
	            $puid = $jobObj->puid;
	            $additionalInfo = json_decode($jobObj->additional_info, true);
	            try {
	                switch ($jobObj->custom_name){
	                    case self::PRODUCT_CREATE2:
	                        $tag = "创建产品";
	                        $feedObj = LazadaFeedList::findOne(["Feed"=>$additionalInfo['feedId']]);
	                        break;
	                    case self::PRODUCT_UPDATE2:
	                        $tag = "修改产品";
	                        $feedObj = LazadaFeedList::findOne(["Feed"=>$additionalInfo['feedId']]);
	                        break;
	                    case self::PRODUCT_IMAGE_UPLOAD2:
	                        $tag = "上传图片";
	                        $feedObj = LazadaFeedList::findOne(["Feed"=>$additionalInfo['imgFeedId']]);
	                        break;
                        case self::PRODUCT_IMAGE_UPDATE2:
                            $tag = "修改图片";
                            $feedObj = LazadaFeedList::findOne(["Feed"=>$additionalInfo['feedId']]);
                            break;
                        case self::PRODUCT_DELETE2:
                            $tag = "删除产品";
                            $feedObj = LazadaFeedList::findOne(["Feed"=>$additionalInfo['feedId']]);
                            break;
	                    default:
	                        $tag = "创建产品";
	                        $feedObj = LazadaFeedList::findOne(["Feed"=>$additionalInfo['feedId']]);
	                        break;
	                }
	                
	                
	                $sourceBgJob = UserBackgroundJobControll::findOne($additionalInfo['sourceJobId']);
	                $sourceAddInfo = json_decode($sourceBgJob->additional_info, true);
	                $indexMap = $additionalInfo['prodIndexs'];
	                echo PHP_EOL.__FUNCTION__." job check start $tag, job:".$jobObj->id.", source job:".$sourceBgJob->id."--".date("Ymd H:i:s", time());
	                
	                // 同步状态 process_status :  0--初始状态，1---已检查, 待调用回调函数，2--已检查等,已调用回调函数，3---已经进入人工待审核队列，7----运行有异常，需要后续重试
	                // 检查创建产品的feed运行情况，将结果回写原任务，或者执行下一步任务
	                
	                // feed 未处理，重置状态，延迟下次处理时间
	                if($feedObj->process_status == self::STATUS_INITIAL 
	                        || $feedObj->process_status == self::STATUS_FAIL && $feedObj->error_times<10){
	                    $jobObj->status = 0;
	                    $jobObj->next_execution_time = $nowTime + self::getCheckIntervalByType($jobObj->custom_name);
	                    $jobObj->last_finish_time = $nowTime;
	                    $jobObj->error_count = 0;
	                    $jobObj->error_message = '';
	                    $jobObj->update_time = $nowTime;
	                    $jobObj->save(false);
	                    
	                    echo PHP_EOL.__FUNCTION__." feed uncheck.job check skip, job:".$jobObj->id.", source job:".$sourceBgJob->id;
	                    continue;
	                }elseif($feedObj->process_status == self::STATUS_FAIL && $feedObj->error_times>=10){// 已经停止处理。回写结果
	                    $rowMsg = "";
	                    foreach ($indexMap as $sku=>$rowIdx){
	                        $rowMsg .= "第".$rowIdx."行，";
	                    }
	                    
	                    $errMsg = "$tag feed处理失败：".$feedObj->message;
	                    self::_callbackErrMsgToSourceBgJob($sourceBgJob, $errMsg, $jobObj, $rowMsg);
	                    echo PHP_EOL."_callbackErrMsgToSourceBgJob msg:{$rowMsg} {$errMsg}, delete job:".$jobObj->id.", source job:".$sourceBgJob->id;
	                    // 处理完就删，feed也可以删了，没了这个记录，相应的feed也不到对应的原任务
	                    $jobObj->delete();
	                }elseif($feedObj->process_status == self::STATUS_CHECKED){
	                    
	                    if($feedObj->Status == "Error"){
	                        $rowMsg = "";
	                        foreach ($indexMap as $sku=>$rowIdx){
	                            $rowMsg .= "第".$rowIdx."行，";
	                        }
	                        
	                        $errMsg = "平台接口问题，$tag 失败，请换个时间重试";
	                        self::_callbackErrMsgToSourceBgJob($sourceBgJob, $errMsg, $jobObj, $rowMsg);
	                        echo PHP_EOL."_callbackErrMsgToSourceBgJob msg:{$rowMsg} {$errMsg}, delete job:".$jobObj->id.", source job:".$sourceBgJob->id;
	                        $jobObj->delete();
	                        
	                    }elseif ($feedObj->FailedRecords>0){
	                        $errMsg = "";
	                        if($feedObj->FailedRecords != $feedObj->TotalRecords){
	                            $errMsg .= "（该组有".($feedObj->TotalRecords - $feedObj->FailedRecords)."个sku执行成功，但总体按全部失败处理）";
	                        }
	                        
	                        $feedErrors = json_decode($feedObj->FeedErrors, true);
	                        
	                        if(isset($feedErrors["Error"]["SellerSku"])){
	                            $errsInfo = array($feedErrors["Error"]);
	                        }else{
	                            $errsInfo = $feedErrors["Error"];
	                        }
	                        
	                        $reqErrors=array();
	                        foreach($errsInfo as $feedErrorInfo){
	                            if(empty($reqErrors[$feedErrorInfo["SellerSku"]])) 
	                                $reqErrors[$feedErrorInfo["SellerSku"]] = [];
                                $reqErrors[$feedErrorInfo["SellerSku"]][] = $feedErrorInfo["Message"];
	                        }
	                        
	                        foreach($reqErrors as $sku=>$skuMapErrInfo){
	                            $rowMsg = "";
	                            if(!empty($indexMap[$sku]))
	                                $rowMsg = "第".$indexMap[$sku]."行，";
	                            
	                            $newErrMsg = implode(";", $skuMapErrInfo);
	                            
	                            // 重复的报错信息
	                            if(strpos($errMsg, $newErrMsg) !== false){
	                                $errMsg = str_replace($newErrMsg, "<br />".$rowMsg."sku:<font style='color: red'>$sku</font>,".$newErrMsg, $errMsg);
	                            }else{
	                                $errMsg .= "<br />".$rowMsg."sku:<font style='color: red'>$sku</font>,".$newErrMsg;
	                            }
	                        }
	                        
	                        // 这里当全部失败处理，因为同一个feed成功的产品进入不了下一步
	                        self::_callbackErrMsgToSourceBgJob($sourceBgJob, $errMsg, $jobObj);
	                        echo PHP_EOL."_callbackErrMsgToSourceBgJob msg:$errMsg, delete job:".$jobObj->id.", source job:".$sourceBgJob->id;
	                        $jobObj->delete();
	                        
	                    }else{// feed 成功
	                        
	                        $jobObj->error_count = 0;
	                        $jobObj->error_message = "";
	                        if($jobObj->custom_name == self::PRODUCT_CREATE2){// 处理新建产品feed
	                            self::_processAfterCreateProd($jobObj, $sourceBgJob, $additionalInfo);
	                        
	                        }
	                        //// 处理产品更新feed 处理产品删除feed
	                        elseif($jobObj->custom_name == self::PRODUCT_UPDATE2 || $jobObj->custom_name == self::PRODUCT_DELETE2){
	                            self::_processAfterUpdateProd($jobObj, $sourceBgJob, $additionalInfo);
	                            
	                        }
	                        // 处理图片feed
	                        elseif($jobObj->custom_name == self::PRODUCT_IMAGE_UPLOAD2 || $jobObj->custom_name == self::PRODUCT_IMAGE_UPDATE2){
	                            self::_processAfterImageUp($jobObj, $sourceBgJob, $additionalInfo);
	                            
	                        }
	                    }
	                    
	                    $feedObj->process_status=self::STATUS_CHECKED_CALLED;
	                    $feedObj->save(false);
	                    echo PHP_EOL.__FUNCTION__." job check finish, source job:".$sourceBgJob->id
	                    .",feedId:".$feedObj->Feed." STATUS_CHECKED_CALLED";
	                    \Yii::info(__FUNCTION__." job check finish, source job:".$sourceBgJob->id
	                    .",feedId:".$feedObj->Feed." STATUS_CHECKED_CALLED","file");
	                    
	                }else{// process_status 为2的 已经处理过
	                    echo PHP_EOL.__FUNCTION__." job check already finish, source job:".$sourceBgJob->id
	                    .",feedId:".$feedObj->Feed." STATUS_CHECKED_CALLED";
	                    $jobObj->delete();
	                }
	                
	            } catch (\Exception $e) {
	                echo "checkImportFeed Exception:file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage().PHP_EOL;
	                \Yii::error("checkImportFeed Exception:file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage(), "file");
	                if (!empty($jobObj)){
	                    self::handleBgJobError($jobObj, $e->getMessage());
	                }
	            
	                continue;
	            }
	        }
	    }
	}
	
	private static function handleBgJobError($recommendJobObj, $errorMessage)
	{
	    $nowTime = time();
	    $recommendJobObj->status = 3;
	    $recommendJobObj->error_count = $recommendJobObj->error_count + 1;
	    $recommendJobObj->error_message = $errorMessage;
	    $recommendJobObj->last_finish_time = $nowTime;
	    $recommendJobObj->update_time = $nowTime;
	    $recommendJobObj->next_execution_time = $nowTime + 10 * 60;//10分钟后重试
	    $recommendJobObj->save(false);
	    return true;
	}
	
	
	private static function _callbackErrMsgToSourceBgJob($sourceBgJob, $errorMessage, $jobObj, $rowMsg = "") {
	    $sourceBgJob->refresh();// 更新类的值，尽量确保execution_request 更新了
	    
	    // 这里一个Job 报错就将sourceBgJob 设置上传失败，而其他job 依然可能会进来这里。注意如果有子任务成功的 不要随便修改状态
	    $sourceAddInfo = json_decode($sourceBgJob->additional_info, true);
	    $addInfo = json_decode($jobObj->additional_info, true);
	    $nowTime = time();
	    
	    $sourceAddInfo['subjobId'][$jobObj->id] = "fail";
	    
	    if(empty($sourceAddInfo['failNum'] ))
	        $sourceAddInfo['failNum'] = 0;
	    
	    $sourceAddInfo['failNum'] += count($addInfo['skus']);
	    $sourceBgJob->additional_info = json_encode($sourceAddInfo);
	    
	    // 重复的报错信息
	    if(strpos($sourceBgJob->error_message, $errorMessage) !== false){
	        $sourceBgJob->error_message = str_replace($errorMessage, $rowMsg.$errorMessage, $sourceBgJob->error_message);
	    }else{
	        if(!empty($sourceBgJob->error_message))
	            $sourceBgJob->error_message = $sourceBgJob->error_message."<br /> ";
	         
	        $sourceBgJob->error_message .= $errorMessage;
	    }
	    
	    $sourceBgJob->last_finish_time = $nowTime;
	    $sourceBgJob->update_time = $nowTime;
	    
	    if($sourceBgJob->status == 4){
	        // 检查子任务重置父任务状态
	        $lastCount = UserBackgroundJobControll::find()->where(['job_name'=>LazadaLinioJumiaProductFeedHelper::$check_feed_job_name,'execution_request'=>$sourceBgJob->id])->count();
	        if($lastCount == 0){
	            $sourceBgJob->is_active = "N";
	            $sourceBgJob->status = 2;
	        }
	    }
	    
	    $sourceBgJob->save(false);
	    return true;
	}
	
	// 提交图片请求
	private static function _processAfterCreateProd($jobObj, $sourceBgJob, $addInfo){
	    $sourceAddInfo = json_decode($sourceBgJob->additional_info, true);
	    $config = self::_getLazadaAccountInfo($sourceAddInfo['lazada_uid']);

	    // 由于产品调用批量提交发布，这里区分不了 提交图片的请求是不是变参产品还是批量全部提交，所以统一处理成批量提交
	    // 变参产品就只是最后一个sku的结果覆盖前面的，这样都是一个请求提交修改图片不影响
	    // 变参只需要上传一组图片即可
	    $upSkus = $addInfo['skus'];
	    $indexMap = $addInfo['prodIndexs'];
// 	    do{
// 	        $sku = array_shift($upSkus);
// 	        $images = $addInfo['images'][$sku];
// 	        Helper_Array::removeEmpty($images);
// 	    }while (empty($images) && count($upSkus)>0);
	    
// 	    // 没有图片直接返回
// 	    if(empty($images)){
// 	        $rowMsg = $sku;
// 	        $sku = $addInfo['skus'][0];
//             if(!empty($indexMap[$sku]))
// 	            $rowMsg = "第".$indexMap[$sku]."行，";
//             $errMsg = "sku:<font style='color: red'>$sku</font>,Images cannot be empty.";
	        
// 	        self::_callbackErrMsgToSourceBgJob($sourceBgJob, $response['message'], $jobObj, $rowMsg);
// 	        echo PHP_EOL."_callbackErrMsgToSourceBgJob msg:".$response['message'].", delete job:".$jobObj->id.", source job:".$sourceBgJob->id;
// 	        $jobObj->delete();
// 	        return false;
// 	    }

// 	    sleep(5);// 请求太快jumia返回 httpcode 429 too many request问题
// 	    $uploads = array();
// 	    $uploads['SellerSku'] = LazadaApiHelper::transformFeedString($sku);
// 	    $uploads['Images'] = $images;
	    
// 	    echo PHP_EOL.__FUNCTION__." before LazadaInterface_Helper::productImage uploads:".json_encode($uploads).PHP_EOL;
// 	    $response = LazadaInterface_Helper::productsImage($config, $uploads);
// 	    echo __FUNCTION__." after LazadaInterface_Helper::productImage response:".json_encode($response).PHP_EOL;
	    
	    $toUpProducts = [];
	    foreach ($addInfo['images'] as $sku=>$toUpImages){
	        $uploads = [];
	        $uploads['SellerSku'] = $sku;
	        $uploads['Images'] = $toUpImages;
	        if(empty($toUpImages)){// 图片为空的跳过
	            $rowMsg = $sku;
	            if(!empty($indexMap[$sku]))
	                $rowMsg = "第".$indexMap[$sku]."行，";
	            $errMsg = "sku:$sku,Images cannot be empty.";
	    
	            echo PHP_EOL."_callbackErrMsgToSourceBgJob msg:$errMsg, job:".$jobObj->id.", source job:".$sourceBgJob->id;
	            continue;
	        }
	         
	        $toUpProducts[] = $uploads;
	    }
	    
	    
	    sleep(60);// 请求太快jumia返回 httpcode 429 too many request问题
	    echo PHP_EOL.__FUNCTION__." before LazadaInterface_Helper::productsImage uploads:".json_encode($toUpProducts).PHP_EOL;
	    $response = LazadaInterface_Helper::productsImage($config, array('products' => $toUpProducts));
	    echo __FUNCTION__." after LazadaInterface_Helper::productsImage response:".json_encode($response).PHP_EOL;
	    
	    
	    if ($response['success'] != true) {
	        // 目前总结到 state_code 为28 的为 curl timeout 可以接受重试 429为访问频繁限制
	        if (isset($response['state_code']) && (28 == $response['state_code']
	                || (isset($response['response']['state_code']) && (28 == $response['response']['state_code'] || 429 == $response['response']['state_code'])))
	        ) {// 不改变state 只记下error message
	            
	            self::handleBgJobError($jobObj, $response['message']);
	            return false;
	        } else {// 其他错误 mark state 为fail 不再重试
	            if (stripos($response['message'], 'Internal Application Error') !== false) {// dzt20160310 对平台错误 提示客户重试减少客服量
	                $response['message'] = $response['message'] . '<br>Jumia平台不稳定问题，请稍后再重新发布商品';
	            }
	            
                $rowMsg = "第".implode(",", $indexMap)."行，";
	            // 非网络原因的报错都不重试
	            self::_callbackErrMsgToSourceBgJob($sourceBgJob, "上传图片失败，".$response['message'], $jobObj, $rowMsg);
	            echo PHP_EOL."_callbackErrMsgToSourceBgJob msg:"."上传图片失败，".$response['message'].", delete job:".$jobObj->id.", source job:".$sourceBgJob->id;
	            $jobObj->delete();
	            
	            return false;
	        }
	    } else {// 调用接口成功
	        $nowTime = time();
	        $feedId = $response['response']['body']['Head']['RequestId'];
	        $addInfo['imgFeedId'] = $feedId;
	        
	        // 插入通用feed 检查任务
	        $addFeedRet = LazadaFeedHelper::insertFeed($sourceBgJob->puid, $sourceAddInfo['lazada_uid'], $config["countryCode"], $feedId, LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD2);
	        if(!$addFeedRet){
	            echo (__FUNCTION__." insertFeed ".LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD2." failed. puid:".$sourceBgJob->puid
	                    ." lazada_uid:".$sourceAddInfo['lazada_uid']." site:".$config["countryCode"]." feedId".$feedId.PHP_EOL);
	            
	            $message = "sku:".implode(',', $upSkus)." add image feed failed.feedId".$feedId."<br />";
	            self::handleBgJobError($jobObj, $message);// 这个失败会重试
	            return false;
	        }else{
	            echo (__FUNCTION__." insertFeed ".LazadaFeedHelper::PRODUCT_IMAGE_UPLOAD2." success. puid:".$sourceBgJob->puid
	                    ." lazada_uid:".$sourceAddInfo['lazada_uid']." site:".$config["countryCode"]." feedId".$feedId.PHP_EOL);
	        }
	        
	        // 不删Job 重置job状态
	        $jobObj->status = 0;
	        $jobObj->custom_name = self::PRODUCT_IMAGE_UPLOAD2;
	        $jobObj->last_finish_time = $nowTime;
	        $jobObj->next_execution_time = $nowTime + self::getCheckIntervalByType($jobObj->custom_name);
	        $jobObj->additional_info = json_encode($addInfo);
	        $jobObj->save(false);
	        
	        $sourceAddInfo['subjobId'][$jobObj->id] = "product_create_success";
	        $sourceBgJob->additional_info = json_encode($sourceAddInfo);
	        
	        $sourceBgJob->last_finish_time = $nowTime;
	        $sourceBgJob->save(false);
	        return true;
	    }
	    
	}
	
	// 处理上传图片成功结果
	private static function _processAfterImageUp($jobObj, $sourceBgJob, $addInfo){
	    $nowTime = time();
	    
	    $sourceBgJob->refresh();// 更新类的值，尽量确保execution_request 更新了
	    $sourceAddInfo = json_decode($sourceBgJob->additional_info, true);
	    $sourceAddInfo['subjobId'][$jobObj->id] = "image_upload_success";
	    $sourceBgJob->additional_info = json_encode($sourceAddInfo);
	    
	    if($sourceBgJob->status == 4){
	        // 检查子任务重置父任务状态
	        $lastCount = UserBackgroundJobControll::find()->where(['job_name'=>LazadaLinioJumiaProductFeedHelper::$check_feed_job_name,'execution_request'=>$sourceBgJob->id])->count();
	        if($lastCount == 0){
	            $sourceBgJob->is_active = "N";
	            $sourceBgJob->status = 2;
	        }
	    }
	    
	    $sourceBgJob->last_finish_time = $nowTime;
	    $sourceBgJob->save(false);
	    
	    $jobObj->delete();
	}
	
	// 处理修改产品成功结果
	private static function _processAfterUpdateProd($jobObj, $sourceBgJob, $addInfo){
	    $nowTime = time();
	     
	    $sourceBgJob->refresh();// 更新类的值，尽量确保execution_request 更新了
	    $sourceAddInfo = json_decode($sourceBgJob->additional_info, true);
	    $sourceAddInfo['subjobId'][$jobObj->id] = "prod_update_success";
	    $sourceBgJob->additional_info = json_encode($sourceAddInfo);
	     
	    if($sourceBgJob->status == 4){
	        // 检查子任务重置父任务状态
	        $lastCount = UserBackgroundJobControll::find()->where(['job_name'=>LazadaLinioJumiaProductFeedHelper::$check_feed_job_name,'execution_request'=>$sourceBgJob->id])->count();
	        if($lastCount == 0){
	            $sourceBgJob->is_active = "N";
	            $sourceBgJob->status = 2;
	        }
	    }
	     
	    $sourceBgJob->last_finish_time = $nowTime;
	    $sourceBgJob->save(false);
	     
	    $jobObj->delete();
	}
	
	
	
}


?>