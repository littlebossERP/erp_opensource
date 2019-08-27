<?php
namespace eagle\modules\platform\helpers;

use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasAmazonUser;
use eagle\models\SaasAmazonUserPt;
use eagle\modules\amazon\apihelpers\AmazonHelper;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;
use eagle\modules\amazon\apihelpers\AmazonProxyConnectApiHelper;
//use eagle\models\SaasAmazonAutosync;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\models\SaasAmazonAutosyncV2;
use eagle\modules\amazon\apihelpers\AmazonSyncFetchOrderV2BaseHelper;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: hxl <plokplokplok@163.com>
+----------------------------------------------------------------------
| Create Date: 2014-01-30
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 物流方式模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/method
 * @subpackage  Exception
 * @author		hxl <plokplokplok@163.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class AmazonAccountsHelper
{

    //true -- exist
    private static function _checkAccountExistOrNot($merchantId,$storeName){
    	$saasAmazonUser = SaasAmazonUser::find()->where("merchant_id=:merchant_id or store_name=:store_name",array(":merchant_id"=>$merchantId,":store_name"=>$storeName))->all();
    	if (empty($saasAmazonUser)) return false;
    	return true;
    }
    
    //检查用户绑定的amazon api信息是否有误
    private static function _checkAmazonUserInfo($params){
        // dzt20190319
//     	if(empty($params['merchant_id']) or empty($params['access_key_id']) or empty($params['secret_access_key']))
//     		return array(false,TranslateHelper::t('merchant_id,access_key_id和secret_access_key都不得为空'));
    	
    	if(empty($params['merchant_id']) or empty($params['mws_auth_token']))
    	    return array(false,TranslateHelper::t('merchant_id和MWSAuthToken都不得为空'));
    	
    	$merchantId = $params["merchant_id"];
    	$storeName = $params["store_name"];
    	
    	//检查该账号是否已经存在
//     	if (self::_checkAccountExistOrNot($merchantId,$storeName)) 
//     		return array(false,TranslateHelper::t("该店铺名或者merchant_id已经存在"));
		// dzt20160216 修改 amazon store 不再全局唯一，控制只是同一个puid唯一
    	$saasAmazonUser = SaasAmazonUser::find()->where(['merchant_id'=>$merchantId])->all();
    	if (!empty($saasAmazonUser))
    		return array(false,TranslateHelper::t("该merchant_id已经存在"));
    	
    	$puid = \Yii::$app->user->identity->getParentUid();
    	$saasAmazonUser = SaasAmazonUser::find()->where(['uid'=>$puid,'store_name'=>$storeName])->all();
    	if (!empty($saasAmazonUser))
    		return array(false,TranslateHelper::t("该店铺名已经存在"));
    	
    	//检查是否选择了国家
    	$marketplaceIdList=self::_getMarketplaceIdListFromReq($params);
    	if (count($marketplaceIdList) == 0) 
    		return array(false,TranslateHelper::t("请选择amazon国家")); 
    	 
    	//检查请求中的amazon的信息是否可以正常连通amazon
    	$config=array(
    			'merchant_id' => $merchantId,
    	        // dzt20190319
//     			'access_key_id' => $params['access_key_id'],
//     			'secret_access_key' => $params['secret_access_key'],
    	        'mws_auth_token' => $params['mws_auth_token'],
    	);
    	
    	foreach($marketplaceIdList as $marketplaceId){    	
    		$config['marketplace_id'] = $marketplaceId;
    		$ret = AmazonProxyConnectApiHelper::testAmazonAccount($config);
    		$code = SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$marketplaceId];
    		if ($ret === false) 
    			return array(false,TranslateHelper::t("amazon连通测试失败。").SaasAmazonAutoSyncApiHelper::$COUNTRYCODE_NAME_MAP[$code].TranslateHelper::t("站连接不上，请检查amazon输入信息!"));
    	}    	

    	return array(true,"");
    }
    
    private static function _checkAmazonEmailInfo($params){
    	SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","_checkAmazonEmailInfo params:".print_r($params,true),"Debug");
    	//amazon email的连接检查
    	if (!empty($params["email_name_prefix"]) and !empty($params["email_passwd"])){
    	
    		if(!function_exists('imap_open')) {
    			SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","_checkAmazonEmailInfo function_exists('imap_open') fail.  imap not set up ","Error");
    			return array(false,TranslateHelper::t("email收发模块有异常，请联系小老板客服！"));
    		}
    		
    		
    		$emailConfig=TicketHelper::$AMAZON_EMAIL_CONFIG[$params["email_name_suffix"]]["fetch"];
    		$fetcher = new AmazonMailFetcher($params["email_name_prefix"],	$params["email_passwd"],
    				$emailConfig["host"],$emailConfig["port"],$emailConfig["protocol"],$emailConfig["encryption"]);
    		try{
    			$foo = error_reporting(E_ALL^E_NOTICE^E_WARNING);  //TODO--fetcher->connect() 异常的时候，这里不能正常捕捉，而且有错误输出，导致返回给前端的json信息中带有该错误信息。所以暂时关闭php报错输出设置。
    			$ret=$fetcher->connect();
    			if (!$ret) {
    				SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","_checkAmazonEmailInfo email连接测试失败。请检查账号和密码","Error");
    				return array(false,TranslateHelper::t("email连接测试失败。请检查账号和密码！"));
    			}
    	
    		}catch (\Exception $e) {
    			SysLogHelper::SysLog_Create("Amazon", __CLASS__,__FUNCTION__,"","_checkAmazonEmailInfo Exception e","Debug");
    			error_reporting($foo);
    			return array(false,TranslateHelper::t("email连接测试失败。请检查账号和密码！"));
    		}
    	}
    	
    	return array(true,"");
    	 
    }
    
    //根据请求中的信息获取用户选择的marketplaceid列表
    //params---array("marketplace_US","marketplace_CA"....)
    private static function _getMarketplaceIdListFromReq($params){
    	$marketplaceIdList = array();
    	$codeMarketplaceMap = array_flip(SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG);
    	foreach($codeMarketplaceMap as $code=>$marketplaceId){
    		if (!isset($params["marketplace_".$code])) continue;
    		$marketplaceIdList[] = $marketplaceId;
    	}
    	return $marketplaceIdList;    	 
    }
    
    
    //用户修改amazon账号--api和email    
    public static function updateAmazonAccount($params){
    	\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',更新amazon api和mail params:'.print_r($params,true),"file");
//     	SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","更新amazon api和mail params:".print_r($params,true), "info");
    	
    	$amazonUid = $params["amazon_uid"];    	
    	$amazonUser = SaasAmazonUser::findOne($amazonUid);    	
    	if ($amazonUser==null)  
    		return array(false,TranslateHelper::t("该amazon用户已经不存在"));
    	
    	//1。 amazon api部分的检查-----选择的国家的是否有变化,并连接检查
    	$userMarketplaceObject = null;
    	$newMarketplaceIdList = self::_getMarketplaceIdListFromReq($params);
    	$userMarketplaceCol = SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid"=>$amazonUid))->all();
    	$oldMarketplaceIdList = array();
    	foreach($userMarketplaceCol as $userMarketplace) {
    		$oldMarketplaceIdList[] = $userMarketplace->marketplace_id;
    		$userMarketplaceObject = $userMarketplace;
    	}
        \Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',oldMarketplaceIdList:'.print_r($oldMarketplaceIdList,true),"file");
        \Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',newMarketplaceIdList:'.print_r($newMarketplaceIdList,true),"file");
    	$marketplaceIdDiff = array_diff($newMarketplaceIdList,$oldMarketplaceIdList);  	
    	//检查新增的国家是否可以连接
    	//检查请求中的amazon的信息是否可以正常连通amazon
    	$params['merchant_id'] = $amazonUser->merchant_id;
    	// dzt20190319
    	$params['access_key_id'] = $userMarketplaceObject->access_key_id;
    	$params['secret_access_key'] = $userMarketplaceObject->secret_access_key;
    	
    	// 处理已添加 重新绑定加token的情况
    	if(empty($params['mws_auth_token']))
            $params['mws_auth_token'] = $userMarketplaceObject->mws_auth_token;
    	
    	$config=array(
    			'merchant_id' => $amazonUser->merchant_id,
    	        // dzt20190319
//     			'access_key_id' => $userMarketplaceObject->access_key_id,
//     			'secret_access_key' => $userMarketplaceObject->secret_access_key,
    	        'mws_auth_token' => $params['mws_auth_token'],
    	        
    	);    	 
    	foreach($marketplaceIdDiff as $marketplaceId){
    		$config['marketplace_id'] = $marketplaceId;
    		$ret = AmazonProxyConnectApiHelper::testAmazonAccount($config);
    		$code = SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$marketplaceId];
    		if ($ret === false) 
    			return array(false,TranslateHelper::t("amazon连通测试失败。").SaasAmazonAutoSyncApiHelper::$COUNTRYCODE_NAME_MAP[$code].TranslateHelper::t("站连接不上，请检查amazon输入信息!"));
    	}    	 
    	
    	//2.amazon email 部分的检查
    	//是否有改变
//     	$amazonEmail=AmazonEmail::model()->find("merchant_id=:merchant_id",array(":merchant_id"=>$amazonUser->merchant_id));
//     	$emailHasChanged=false;
//     	if ($amazonEmail==null){
//     		if ($params["email_name_prefix"]<>"" or $params["email_passwd"]<>"") $emailHasChanged=true;    		
//     		list($ret,$message)=self::_checkAmazonEmailInfo($params);
//     		if ($ret===false) return array($ret,$message);    		
//     	}else{
//     		//本身有email信息的话，不允许在更新的时候清空账号或者密码。
//     		if ($params["email_name_prefix"]=="" or $params["email_passwd"]==""){
//     			return array(false,TranslateHelper::t("需要输入正确的email账号和密码!"));
//     		}
//     		$oldEmail=$amazonEmail->email;
//     		$emailArr=explode("@",$oldEmail);
//     		$oldPrefix=$emailArr[0];
//     		$tempArr=explode(".",$emailArr[1]);    		
//     		$oldSuffix=$tempArr[0];
//     		$oldPasswd=CryptUtil::decrypt($amazonEmail->userpass,CryptUtil::getSecretSalt());
//     		if ($params["email_name_prefix"]<>$oldPrefix or $params["email_name_suffix"]<>$oldSuffix or $params["email_passwd"]<>$oldPasswd){
//     			$emailHasChanged=true;
//     			list($ret,$message)=self::_checkAmazonEmailInfo($params); //检查更新的账号和密码是否能正常连接到邮箱服务器
//     			if ($ret===false) return array($ret,$message);    			 
//     		}
//     	}    	 
    	
    	//3.保存amazon api的变化信息
    	$amazonUser->store_name = $params["store_name"];
    	$amazonUser->is_active = $params["is_active"];
    	
    	$amazonUser->save();
    	$marketplaceIdDiff = array_diff($newMarketplaceIdList,$oldMarketplaceIdList);
    	if (count($marketplaceIdDiff)>0){
    		foreach($marketplaceIdDiff as $marketplaceId){
    			$params['marketplace_id'] = $marketplaceId;
    			$ret = MarketplaceHelper::helpInsert($params,false);
    			if ($ret === true){} else return array(false,TranslateHelper::t("amazon国家添加出现异常，请联系客服!"));
    		}
    	}
    	$marketplaceIdDiff = array_diff($oldMarketplaceIdList,$newMarketplaceIdList);
    	if (count($marketplaceIdDiff)>0){
    		foreach($marketplaceIdDiff as $marketplaceId){
    			$ret = MarketplaceHelper::helpDelete($amazonUid, $marketplaceId);
    		}
    	}
    	// 更新已有marketplace
    	$marketplaceIdIntersect = array_intersect($oldMarketplaceIdList,$newMarketplaceIdList);
    	if (count($marketplaceIdIntersect)>0){
    		foreach($marketplaceIdIntersect as $marketplaceId){
    			$params['marketplace_id'] = $marketplaceId;
    			$ret = MarketplaceHelper::helpUpdate($params);
    			if ($ret === true){} else return array(false,TranslateHelper::t("amazon marketplace信息更新异常，请重试或联系客服!"));
    		}
    	}
    	
    	return array(true,"");
    }
    
    //用户绑定amazon账号--api和email
    public static function createAmazonAccount($params){
    	\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',新增绑定amazon api和mail params:'.print_r($params,true),"file");
//     	SysLogHelper::SysLog_Create("Platform",__CLASS__, __FUNCTION__,"","新增绑定amazon api和mail params:".print_r($params,true), "info");
    	$params['store_name'] = trim($params['store_name']);
    	$params['merchant_id'] = trim($params['merchant_id']);
    	// dzt20190319 amazon新授权
    	// $params['access_key_id'] = trim($params['access_key_id']);
    	// $params['secret_access_key'] = trim($params['secret_access_key']);
    	$params['mws_auth_token'] = trim($params['mws_auth_token']);
    	
    	//1.检查amazon信息的合法性，连接测试是否ok
    	list($ret,$message)=self::_checkAmazonUserInfo($params);
    	\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',after _checkAmazonUserInfo',"file");
    	if ($ret===false) return array($ret,$message);
    	
    	\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',after _checkAmazonUserInfo 2',"file");
    	//2. amazon email的连接检查
//     	list($ret,$message)=self::_checkAmazonEmailInfo($params);
//     	if ($ret===false) return array($ret,$message);
    
    	//3. 保存amazon信息到db
    	$marketplaceIdList = self::_getMarketplaceIdListFromReq($params);
    	\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',after _getMarketplaceIdListFromReq ',"file");
    	$info=array();
    	$info['store_name'] = $params['store_name'];
    	$info['merchant_id'] = $params['merchant_id'];
    	// dzt20190319
    	// $info['access_key_id'] = $params['access_key_id'];
    	// $info['secret_access_key'] = $params['secret_access_key'];
    	$info['mws_auth_token'] = $params['mws_auth_token'];
    	$info['is_active'] = $params['is_active'];

    	if (!isset(\Yii::$app->user->identity) && \Yii::$app->user->identity->getParentUid() == 0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行amazon的绑定，!"));
    	}
    	
    	$puid = \Yii::$app->user->identity->getParentUid();
    	
    	\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',saasId:$saasId merchantId:'.$info['merchant_id'],"file");
//     	SysLogHelper::GlobalLog_Create("amazon", __CLASS__,__FUNCTION__,"","saasId:$saasId merchantId:".$info['merchant_id'] ,"info");
    	
    	$ret=self::insertAmazonUserInfo($info, $marketplaceIdList, $puid);
    	\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',after insertAmazonUserInfo',"file");
    	
    	
    	if ($ret==false){
    		\Yii::info('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',after insertAmazonUserInfo ret==false',"file");
    		return array(false,TranslateHelper::t("绑定amazon api出现异常，请联系小老板相关客服!"));
    	}
    	//重置账号绑定情况到redis
    	PlatformAccountApi::resetPlatformInfo('amazon');
    }
    
    //$info---'store_name','merchant_id','marketplace_id','access_key_id','secret_access_key','is_active'
    public static function insertAmazonUserInfo($info,$marketplaceIdList,$saasId){
    	//1. 保存amazon 账号级别（marchant_id）的信息
    	$now = time();
    	
    	$user = new SaasAmazonUser();
    	$user->merchant_id = $info['merchant_id'];
    	$user->store_name = $info['store_name'];
    	$user->create_time = $now;    	 
    	$user->update_time = $now;
    	$user->is_active = $info['is_active'];

    	$user->uid = $saasId;
    	if (!$user->save()) {
    		\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .', user->save '.print_r($user->errors,true) , 'file');
//             SysLogHelper::SysLog_Create("Platform", __CLASS__,__FUNCTION__,""," user->save ".print_r($user->errors,true) ,"Error");
    		return false;
    	}
 
    	$userPt = new SaasAmazonUserPt();
    	$userPt->setAttributes($user->getAttributes());
    	$userPt->save(false);
    	
    	$info['amazon_uid']=$user->amazon_uid;
    	//2. 保存amazon 店铺级别（marketplace_id）的信息
    	foreach($marketplaceIdList as $marketplaceId){
    		$info['marketplace_id'] = $marketplaceId;
    		$ret = MarketplaceHelper::helpInsert($info,false);
    		if ($ret === true){    			
    		}else{
    			return false;
    		}
    	}
    	//绑定成功调用
    	PlatformAccountApi::callbackAfterRegisterAccount('amazon', $saasId);
    	return true;
    }   

    // 开启/关闭 账号信息同步
    public static function switchSyncAmazonAccount($status,$amazonUid){
    	// 更新已有marketplace
    	$userMarketplaceList = SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid"=>$amazonUid))->all();
    	foreach($userMarketplaceList as $userMarketplace) {
			$userMarketplace->is_active = $status;
        	$userMarketplace->update_time = time();
        	if (!$userMarketplace->save(false)){
        		\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',marketplace->save() '.print_r($userMarketplace->errors,true) , "file");
        		return array(false,TranslateHelper::t("出现异常，请重试或联系小老板的相关客服"));
        	}
    	}

    	// 更新所有autosync 记录。目前foreach save() model比较慢，后面可以参考 敦煌或者的开启/关闭 账号信息同步的处理
    	// $autoSyncList = SaasAmazonAutosync::find()->where(array("amazon_user_id"=>$amazonUid))->all();
    	// foreach($autoSyncList as $autoSync) {
    	// 	$autoSync->status = $status;//status --0表示没开启; 1 表示开启
    	// 	if (!$autoSync->save()){
    	// 		\Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',$autoSync->save() '.print_r($marketplace->errors,true) , "file");
    	// 		return array(false,TranslateHelper::t("出现异常，请重试或联系小老板的相关客服"));
    	// 	}
    	// }

        // 更新所有autosyncv2 记录。目前foreach save() model比较慢，后面可以参考 敦煌或者的开启/关闭 账号信息同步的处理
    	$autoSyncv2List = SaasAmazonAutosyncV2::find()->where(array("eagle_platform_user_id"=>$amazonUid))->all();
        foreach($autoSyncv2List as $autoSyncv2) {//解绑，关闭同步，开启同步
            $autoSyncv2->status = $status;//status --0表示没开启; 1 表示开启；3 解除绑定
            if ($status==3) {//解除绑定
                $autoSyncv2->update_time = time();
                $autoSyncv2->process_status = 0;
                $autoSyncv2->err_cnt = 0;
                $autoSyncv2->last_finish_time = time();
                if($autoSyncv2->type=="amzOldUnshippedAll"){
                    $autoSyncv2->deadline_time=$autoSyncv2->update_time-30*24*3600;
                }
                if($autoSyncv2->type=="amzOldNotFbaNotUnshipped"||$autoSyncv2->type=="amzOldFbaNotUnshipped"){
                    $autoSyncv2->deadline_time=$autoSyncv2->update_time-2*24*3600;
                }
                if ($autoSyncv2->type=="amzOldUnshippedAll") {//避免同时触发，初次延后时间
                    $autoSyncv2->next_execute_time=$autoSyncv2->update_time+5*60+rand(0,100);
                }else if ($autoSyncv2->type=="amzNewNotFba") {
                    $autoSyncv2->next_execute_time=$autoSyncv2->update_time+45*60+rand(0,100);
                }else if ($autoSyncv2->type=="amzNewFba") {
                    $autoSyncv2->next_execute_time=$autoSyncv2->update_time+1*3600+rand(0,100);
                }else if ($autoSyncv2->type=="amzOldNotFbaNotUnshipped") {
                    $autoSyncv2->next_execute_time=$autoSyncv2->update_time+5*3600+rand(0,100);
                }else if ($autoSyncv2->type=="amzOldFbaNotUnshipped") {
                    $autoSyncv2->next_execute_time=$autoSyncv2->update_time+5*3600+rand(0,100);
                }
            }

            if (!$autoSyncv2->save()){
                \Yii::error('Platform,' . __CLASS__ . ',' . __FUNCTION__ .',$autoSyncv2->save() '.print_r($marketplace->errors,true) , "file");
                return array(false,TranslateHelper::t("出现异常，请重试或联系小老板的相关客服"));
            }
            
            //dzt20190415 处理queue表
            self::processQueue($status, $autoSyncv2->id);
            
        }



    	return array(true,'');
    }
    
    public static function processQueue($status, $autoSyncv2Id){
        $highOrderidQueue = AmazonSyncFetchOrderV2BaseHelper::$priorityType["highpriority"]["name_space"];
        $tmpHighOrderQueue = new $highOrderidQueue;
        
        $lowOrderidQueue = AmazonSyncFetchOrderV2BaseHelper::$priorityType["lowpriority"]["name_space"];
        $tmpLowOrderQueue = new $lowOrderidQueue;
        if($status == 0){
            $tmpHighOrderQueue::updateAll(['process_status'=>5],['saas_platform_autosync_id'=>$autoSyncv2Id, 'process_status'=>[0, 3]]);
            $tmpLowOrderQueue::updateAll(['process_status'=>5],['saas_platform_autosync_id'=>$autoSyncv2Id, 'process_status'=>[0, 3]]);
        }else if($status == 1){
            $tmpHighOrderQueue::updateAll(['process_status'=>0],['saas_platform_autosync_id'=>$autoSyncv2Id, 'process_status'=>5]);
            $tmpLowOrderQueue::updateAll(['process_status'=>0],['saas_platform_autosync_id'=>$autoSyncv2Id, 'process_status'=>5]);
        }else if($status == 3){// 目前调用不存在3的情况。
            $tmpHighOrderQueue::deleteAll(['saas_platform_autosync_id'=>$autoSyncv2Id]);
            $tmpLowOrderQueue::deleteAll(['saas_platform_autosync_id'=>$autoSyncv2Id]);
        }
    }
    
}
