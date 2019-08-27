<?php
namespace eagle\modules\platform\helpers;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SaasWishUser;
use eagle\modules\util\helpers\GetControlData;
use yii\helpers\VarDumper;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\modules\listing\helpers\WishProxyConnectHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
/*+----------------------------------------------------------------------
| 小老板
+----------------------------------------------------------------------
| Copyright (c) 2011 http://www.xiaolaoban.cn All rights reserved.
+----------------------------------------------------------------------
| Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
+----------------------------------------------------------------------
| Author: lzhl
+----------------------------------------------------------------------
| Create Date: 2014-01-26
+----------------------------------------------------------------------
 */

/**
 +------------------------------------------------------------------------------
 * 物流方式模块业务逻辑类
 +------------------------------------------------------------------------------
 * @category	Order
 * @package		Helper/method
 * @subpackage  Exception
 * @author		lzhl
 * @version		1.0
 +------------------------------------------------------------------------------
 */
class WishAccountsHelper
{
    // true -- exist
    // private static function _checkAccountExistOrNot($merchantId,$storeName){
    	// $saasWishUser=SaasWishUser::model()->find("merchant_id=:merchant_id or store_name=:store_name",array(":merchant_id"=>$merchantId,":store_name"=>$storeName));
    	// if ($saasWishUser===null) return false;
    	// return true;
    // }
    // #检查用户绑定的Wish api信息是否有误
    // private static function _checkWishUserInfo($params){
    	// if(empty($params['merchant_id']) or empty($params['access_key_id']) or empty($params['secret_access_key']))
    		// return array(false,'merchant_id,access_key_id和secret_access_key都不得为空');
    	// $merchantId=$params["merchant_id"];
    	// $storeName=$params["store_name"];
    	// #检查该账号是否已经存在
    	// if (self::_checkAccountExistOrNot($merchantId,$storeName)) return array(false,"该店铺名或者merchant_id已经存在");
    	// #检查是否选择了国家
    	// $marketplaceIdList=self::_getMarketplaceIdListFromReq($params);
    	// if (count($marketplaceIdList)==0) return array(false,"请选择Wish国家"); 
    	// #检查请求中的Wish的信息是否可以正常连通Wish
    	// $config=array(
    			// 'merchant_id' =>$merchantId,
    			// 'access_key_id' => $params['access_key_id'],
    			// 'secret_access_key' => $params['secret_access_key'],
    	// );
    	// foreach($marketplaceIdList as $marketplaceId){    	
    		// $config['marketplace_id']=$marketplaceId;
    		// $ret=WishProxyConnectHelper::testWishAccount($config);
    		// $code=SaasWishAutoSyncHelper::$Wish_MARKETPLACE_REGION_CONFIG[$marketplaceId];
    		// if ($ret===false) return array(false,"Wish连通测试失败。".SaasWishAutoSyncHelper::$COUNTRYCODE_NAME_MAP[$code]."站连接不上，请检查Wish输入信息!");
    	// }
    	// return array(true,"");
    // }
    // private static function _checkWishEmailInfo($params){
    	// SysLogHelper::SysLog_Create("Wish", __CLASS__,__FUNCTION__,"","_checkWishEmailInfo params:".print_r($params,true),"Debug");
    	// #Wish email的连接检查
    	// if (!empty($params["email_name_prefix"]) and !empty($params["email_passwd"])){
    		// if(!function_exists('imap_open')) {
    			// SysLogHelper::SysLog_Create("Wish", __CLASS__,__FUNCTION__,"","_checkWishEmailInfo function_exists('imap_open') fail.  imap not set up ","Error");
    			// return array(false,"email收发模块有异常，请联系小老板客服！");
    		// }
    		// $emailConfig=TicketHelper::$Wish_EMAIL_CONFIG[$params["email_name_suffix"]]["fetch"];
    		// $fetcher = new WishMailFetcher($params["email_name_prefix"],	$params["email_passwd"],
    				// $emailConfig["host"],$emailConfig["port"],$emailConfig["protocol"],$emailConfig["encryption"]);
    		// try{
    			// $foo = error_reporting(E_ALL^E_NOTICE^E_WARNING);  //TODO--fetcher->connect() 异常的时候，这里不能正常捕捉，而且有错误输出，导致返回给前端的json信息中带有该错误信息。所以暂时关闭php报错输出设置。
    			// $ret=$fetcher->connect();
    			// if (!$ret) {
    				// SysLogHelper::SysLog_Create("Wish", __CLASS__,__FUNCTION__,"","_checkWishEmailInfo email连接测试失败。请检查账号和密码","Error");
    				// return array(false,"email连接测试失败。请检查账号和密码！");
    			// }
    		// }catch (Exception $e) {
    			// SysLogHelper::SysLog_Create("Wish", __CLASS__,__FUNCTION__,"","_checkWishEmailInfo Exception e","Debug");
    			// error_reporting($foo);
    			// return array(false,"email连接测试失败。请检查账号和密码！");
    		// }
    	// }
    	// return array(true,"");
    // }
    
    // #根据请求中的信息获取用户选择的marketplaceid列表
    // params---array("marketplace_US","marketplace_CA"....)
    // private static function _getMarketplaceIdListFromReq($params){
    	// $marketplaceIdList=array();
    	// $codeMarketplaceMap=array_flip(SaasWishAutoSyncHelper::$Wish_MARKETPLACE_REGION_CONFIG);
    	// foreach($codeMarketplaceMap as $code=>$marketplaceId){
    		// if (!isset($params["marketplace_".$code])) continue;
    		// $marketplaceIdList[]=$marketplaceId;
    	// }
    	// return $marketplaceIdList;    	 
    // } 
    
    
    //用户修改Wish账号--api和email    
    public static function updateWishAccount($params){
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId == 0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Wish的绑定!"));
    	}
    	if (empty($params["store_name"]) or empty($params["token"])){
			return array(false,TranslateHelper::t("store_name 和 token 都不能为空"));
		}
		
    	$WishData = SaasWishUser::findOne($params["Wish_id"]);    	
    	if ($WishData==null)  return array(false,TranslateHelper::t("该Wish账户不存在"));
		
		#检查Wish信息的合法性(name,token是否被占用)
		$filteData = SaasWishUser::find()->where(array('store_name' => $params['store_name']))->one();
		if ($filteData!==null){
			$id = $filteData->site_id;
			if ($id !== $params['Wish_id']){
				return array(false,TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
			}
			
		}
		$filteData=SaasWishUser::find()->where(array('token' => $params['token']))->one();
		if ($filteData!==null){
			$id = $filteData->site_id;
			if ($id !== $params['Wish_id']){
				return array(false,TranslateHelper::t("token 已存在（不区分大小写），不能重复使用!"));
			}
			
		}
		
    	//保存Wish api的变化信息

		$WishData->store_name = $params["store_name"];
		$WishData->token = $params["token"];
		$WishData->is_active = $params["is_active"];
		$WishData->update_time = GetControlData::getNowDateTime_str();
		if ($WishData->save()){
			return array(true,"");
		}else{
			$message = '';
            foreach ($WishData->errors as $k => $anError){
				$message .= "Update Wish user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    //用户绑定Wish账号
    public static function createWishAccount($params){
    	// #1.检查Wish信息的合法性，连接测试是否ok
    	// list($ret,$message)=self::_checkWishUserInfo($params);
    	// if ($ret===false) return array($ret,$message);
    	
    	// #2. Wish email的连接检查
    	// list($ret,$message)=self::_checkWishEmailInfo($params);
    	// if ($ret===false) return array($ret,$message);
		
		#3.检查Wish信息的合法性(name,token是否被占用)
		#检查Wish信息的合法性(name,token是否被占用)
		$filteData = SaasWishUser::find()->where(array('store_name' => $params['store_name']))->one();
		if ($filteData!==null){
			return array(false, TranslateHelper::t("store_name 已存在（不区分大小写），不能重复使用!"));
		}
		
		
		$filteData = SaasWishUser::find()->where(array('token' => $params['token']))->one();
		if ($filteData!==null){
			return array(false, TranslateHelper::t("token 已存在（不区分大小写），不能重复使用!"));
		}		
    	/* 2015-08-26 token 已经改变了授权方式 
    	if (empty($params["store_name"]) or empty($params["client_id"]) or  empty($params['client_secret']) or empty($params['redirect_uri'])){
			return array(false, TranslateHelper::t("店铺名称  ,   客户id  , 客户端密钥和 重新载入URI 都不能为空"));
		}
		*/
    	#4. 保存Wish信息到db
    	$info=array();
    	$info['store_name'] = $params['store_name'];
    	if (!empty($params['token']))
    		$info['token'] = $params['token'];
    	else
    		$info['token'] = '';
		/*
    	$info['client_id'] = $params['client_id'];
    	$info['client_secret'] = $params['client_secret'];
    	$info['redirect_uri'] = $params['redirect_uri'];
		*/
    	$info['create_time'] = GetControlData::getNowDateTime_str();    	
    	$info['is_active']=$params['is_active'];    	
    	$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,"请退出小老板并重新登录，再进行Wish的绑定!");
    	}
    	$ret=self::insertWishUserInfo($info,$saasId);
    	if ($ret !==true ){
    		return array(false,$ret);
    	}
    }
	
    //$info---'store_name','merchant_id','marketplace_id','access_key_id','secret_access_key','is_active'
    public static function insertWishUserInfo($info,$saasId){
    	//1. 保存Wish 账号级别（marchant_id）的信息
    	$now = GetControlData::getNowDateTime_str();
    	//time();
    	
    	$user = new SaasWishUser();
    	$user->token = $info['token'];
    	$user->store_name = $info['store_name'];
    	$user->create_time = $now;    	 
    	$user->update_time = $now;
    	$user->is_active = $info['is_active'];
    	$user->uid = $saasId;
    	if (!$user->save()) {
			$message = '';
            foreach ($user->errors as $k => $anError){
				$message .= "Insert Wish user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
			return $message;
    	}
    	
    	//绑定账号时，将拉取站内信的app数据一并生成
    	$rtn = MessageApiHelper::setSaasMsgAutosync($saasId, $user->site_id, $info['store_name'], 'wish');
    	if('fail' == $rtn['code']){
    		return $rtn['message'];
    	}
    	
    	return true;
    }    
    
    
	//用户删除Wish账号
    public static function deleteWishAccount($id) 
    {
		$saasId = \Yii::$app->user->identity->getParentUid();
    	if ($saasId==0) {
    		//用户没登陆导致????
    		return array(false,TranslateHelper::t("请退出小老板并重新登录，再进行Wish的绑定!"));
    	}
		$model = SaasWishUser::findOne($id);
		$sellerid = $model->store_name;
        if ($model->delete()){
            PlatformAccountApi::callbackAfterDeleteAccount('wish',$saasId,['selleruserid'=>$sellerid]);
            return true;
        }else{
            $message = '';
            foreach ($model->errors as $k => $anError){
				$message .= "Delete Wish user ". ($message==""?"":"<br>"). $k." error:".$anError[0];
			}
            return array(false,$message);
        }
    }
    
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 获取wish授权信息
     +----------------------------------------------------------------------------------------------------------------------------
     * @access static
     +----------------------------------------------------------------------------------------------------------------------------
     * @param 	$client_id			string		客户端ID
     * 			$client_secret		string		客户端密钥
     * 			$code				string		授权code
     * 			$redirect_uri		string		回调地址
     +----------------------------------------------------------------------------------------------------------------------------
     * @return			array
     * 	boolean				success   	执行结果
     * 	string/array		message   	执行失败的提示信息
     * 	array				wishReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		WishProxyConnectHelper::getWishToken($client_id , $client_secret , $code , $redirect_uri)
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2014/08/26				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    public static function getWishToken($client_id , $client_secret , $code , $redirect_uri){
    	$get_params = [
    	'client_id'=>$client_id,
    	'client_secret'=>$client_secret,
    	'code'=>$code,
    	'redirect_uri'=>$$redirect_uri,
    	];
    	return WishProxyConnectHelper::call_WISH_api('GetAccessToken',$get_params);
    }//end of getWishToken
    
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 更新 wish授权
     +----------------------------------------------------------------------------------------------------------------------------
     * @access static
     +----------------------------------------------------------------------------------------------------------------------------
     * @param 	$client_id			string		客户端ID
     * 			$client_secret		string		客户端密钥
     * 			$resfresh_token		string		刷新授权
     +----------------------------------------------------------------------------------------------------------------------------
     * @return			array
     * 	boolean				success   	执行结果
     * 	string/array		message   	执行失败的提示信息
     * 	array				wishReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		WishProxyConnectHelper::refreshWishToken($client_id , $client_secret , $resfresh_token)
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2014/08/26				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    public static function refreshWishToken($client_id , $client_secret , $resfresh_token){
    	$get_params = [
    	'client_id'=>$client_id,
    	'client_secret'=>$client_secret,
    	'refresh_token'=>$refresh_token,
    	];
    	return WishProxyConnectHelper::call_WISH_api('refreshAccessToken',$get_params);
    }//end of refreshWishToken
    
    /**
     +----------------------------------------------------------------------------------------------------------------------------
     * 更新 wish授权
     +----------------------------------------------------------------------------------------------------------------------------
     * @access static
     +----------------------------------------------------------------------------------------------------------------------------
     * @param 	$client_id			string		客户端ID
     * 			$code				string		wish redirect出来 的code
     +----------------------------------------------------------------------------------------------------------------------------
     * @return			array
     * 	boolean				success   	执行结果
     * 	string/array		message   	执行失败的提示信息
     * 	array				wishReturn 	授权结果
     +----------------------------------------------------------------------------------------------------------------------------
     * @invoking		WishProxyConnectHelper::refreshWishToken($client_id , $client_secret , $resfresh_token)
     +----------------------------------------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2014/08/26				初始化
     +----------------------------------------------------------------------------------------------------------------------------
     **/
    public function saveWishToken($client_id , $code){
    	if (!empty($code) && !empty($client_id)){
    		$model = SaasWishUser::findOne(['client_id'=>$client_id]);
    			
    		if (! empty($model)){
    			$result = self::getWishToken($client_id, $model->client_secret, $_REQUEST['code'], $model->redirect_uri);
    			if ($result['success']){
    				//proxy 层成功
    	
    				if(!empty($result['wishReturn']) || !empty($result['wishReturn'] )){
    					//WISH api 调用 成功
    					if (is_string($result['wishReturn'])){
    						// json 字符
    						$wishReturn = json_decode($result['wishReturn'],true);
    					}else if (is_array($result['wishReturn'])){
    						//array
    						$wishReturn = $result['wishReturn'];
    					}else{
    						//其他格式  todo
    						$wishReturn = [];
    					}
    						
    					if (!empty($wishReturn)){
    						$model->code = $_REQUEST['code'];
    						$model->token = $wishReturn['access_token'];
    						$model->refresh_token = $wishReturn['refresh_token'];
    						$model->expires_in = date('Y-m-d H:i:s',$wishReturn['expires_in']);
    						$model->expiry_time = date('Y-m-d H:i:s',$wishReturn['expiry_time']);
    						if (! $model->save()) {
    							//todo write log
    						}
    					}
    						
    				}//end of if(!empty($result['wishReturn']) || !empty($result['wishReturn'] )) wish api 返回结果处理
    	
    			}//end of if ($result['success']) proxy 返回结果
    	
    		}//end of if (! empty($model)) 找到对应 wish user 账号 的model
    			
    	}//end of if (!empty($code) && !empty($client_id)) code 和 client id 不能为空
    }//end of saveWishToken
    
    
     
    
}
