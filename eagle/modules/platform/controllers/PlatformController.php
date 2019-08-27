<?php

namespace eagle\modules\platform\controllers;

use Yii;

use eagle\modules\platform\apihelpers\EbayAccountsApiHelper;
use eagle\models\SaasAliexpressUser;
use eagle\models\SaasWishUser;
use eagle\models\SaasDhgateUser;
use eagle\models\SaasAmazonUser;
use eagle\models\SaasAmazonUserMarketplace;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;
use eagle\models\SaasCdiscountUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\models\SaasLazadaUser;
use eagle\models\SaasMessageAutosync;
use eagle\modules\platform\apihelpers\EnsogoAccountsApiHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\helpers\ImageHelper;
use eagle\modules\app\apihelpers\AppTrackerApiHelper;
use yii\data\Pagination;
use eagle\modules\platform\helpers\PlatformHelper;
use eagle\models\SaasEbayUser;
use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\platform\apihelpers\AliexpressAccountsApiHelper;
use eagle\models\SaasPriceministerUser;
use eagle\models\SaasBonanzaUser;
use eagle\modules\platform\models\CdiscountRegister;
use eagle\modules\platform\models\LinioRegister;
use eagle\models\SaasRumallUser;
use eagle\models\SaasNeweggUser;
use eagle\models\SaasPaypalUser;
use eagle\models\SaasCustomizedUser;
use eagle\models\Saas1688User;
use eagle\modules\platform\apihelpers\Al1688AccountsApiHelper;
use eagle\modules\platform\apihelpers\ShopeeAccountsApiHelper;
use eagle\models\SaasShopeeUser;


class PlatformController extends \eagle\components\Controller{
	public $enableCsrfValidation = false; //非网页访问方式跳过通过csrf验证的 . 如: curl 和 post man
	

   /**
    * 由tracker进来的平台 绑定 页面
    */
	public function actionTrackerPlatformAccountBinding(){
		//20170215kh 屏蔽tracker 的绑定界面
		$url = '/platform/platform/all-platform-account-binding';
		return $this->redirect($url);
		$allUserList = array();
		$MsgErrorInfo = [];
		
		// 获取速卖通 user list
		$userInfo = \Yii::$app->user->identity;
		if ($userInfo['puid']==0){
			$uid = $userInfo['uid'];
		}else {
			$uid = $userInfo['puid'];
		}
		
		
		// 获取ebay user list
		$ebayUserList = EbayAccountsApiHelper::helpList('expiration_time' , 'asc');
		$allUserList["ebayUserList"] = $ebayUserList;
		 
		 
		
		 
		$users = SaasAliexpressUser::find()->where('uid ='.$uid)
		->orderBy('refresh_token_timeout desc')
		->asArray()
		->all();
		 
		$aliexpressUserList = array();
		foreach ($users as $user){
			$user['refresh_token_timeout'] = $user['refresh_token_timeout'] > 0?date('Y-m-d',$user['refresh_token_timeout']):'未授权';
			$aliexpressUserList[] = $user;
		}
		$allUserList['aliexpressUserList'] = $aliexpressUserList;
		 
		// 获取wish user list
		$WishUserData = SaasWishUser::find()->where(["uid" => $uid])
		->orderBy("last_order_success_retrieve_time desc")
		->asArray()
		->all();
		 
		$allUserList['WishUserList'] = $WishUserData;
		 
		// 获取wish user list
		$dhgateUserData = SaasDhgateUser::find()->where(["uid" => $uid])->andWhere('is_active <> 3')
		->orderBy("refresh_token_timeout desc")
		->asArray()
		->all();
		 
		$dhgateUserList = array();
		foreach ($dhgateUserData as $UserData){
			$UserData['refresh_token_timeout'] = $UserData['refresh_token_timeout'] > 0?date('Y-m-d',$UserData['refresh_token_timeout']):'未授权';
			$dhgateUserList[] = $UserData;
		}
		 
		$allUserList['dhgateUserList'] = $dhgateUserList;
		
		// 获取Lazada user list
		// @todo lazada 和 linio user共用一个表，如果将来要分开，这里的过滤条件platform要去掉
		$LazadaUserList = array();
		$lazadaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping();
		$lazadaUserData = SaasLazadaUser::find()->where(["puid" => $uid,"platform"=>"lazada"])->andWhere('status <> 3')
		->asArray()
		->all();
		
		foreach ($lazadaUserData as $lazadaUser){
			if (!empty($lazadaSite[$lazadaUser['lazada_site']]))
    			$lazadaUser['lazada_site'] = $lazadaSite[$lazadaUser['lazada_site']];
    		else 
    			$lazadaUser['lazada_site'] ='';
			$LazadaUserList[] = $lazadaUser;
		}
		$allUserList['LazadaUserList'] = $LazadaUserList;

		// 获取Linio user list
		// @todo lazada 和 linio user共用一个表，如果将来要分开，这里的过滤条件platform要去掉
		$LinioUserList = array();
		$linioSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
		$linioUserData = SaasLazadaUser::find()->where(["puid" => $uid,"platform"=>"linio"])->andWhere('status <> 3')
		->asArray()
		->all();
		
		foreach ($linioUserData as $linioUser){
			if (!empty($linioSite[$linioUser['lazada_site']]))
				$linioUser['lazada_site'] = $linioSite[$linioUser['lazada_site']];
			else
				$linioUser['lazada_site'] ='';
			$LinioUserList[] = $linioUser;
		}
		$allUserList['LinioUserList'] = $LinioUserList;
		
		// 获取Jumia user list
		// @todo lazada jumia 和 linio user共用一个表，如果将来要分开，这里的过滤条件platform要去掉
		$JumiaUserList = array();
		$jumiaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
		$jumiaUserData = SaasLazadaUser::find()->where(["puid" => $uid,"platform"=>"jumia"])->andWhere('status <> 3')
		->asArray()
		->all();
		
		foreach ($jumiaUserData as $jumiaUser){
			if (!empty($jumiaSite[$jumiaUser['lazada_site']]))
				$jumiaUser['lazada_site'] = $jumiaSite[$jumiaUser['lazada_site']];
			else
				$jumiaUser['lazada_site'] ='';
			$JumiaUserList[] = $jumiaUser;
		}
		$allUserList['JumiaUserList'] = $JumiaUserList;
		
		// 获取Ensogo user list
		// 获取Ensogo user list
// 		$EnsogoUserList = [];
// 		$EnsogoUserList = EnsogoAccountsApiHelper::ListAccounts($uid);
// 		$allUserList['EnsogoUserList'] = $EnsogoUserList;
 
		return $this->render('tracker_bind_platform_store' , $allUserList);
		
	}
	
	
    /**
     * 由v2进来的平台 绑定 页面
     */
    public function actionAllPlatformAccountBinding(){
    	$userInfo = \Yii::$app->user->identity;
    	if ($userInfo['puid']==0){
    		$uid = $userInfo['uid'];
    	}else {
    		$uid = $userInfo['puid'];
    	}
    	
    	//从redis缓存中读取绑定的数据, 有绑定记录才访问数据库， 减少访问数据库的次数
    	$allBindingAccountData = PlatformAccountApi::getPlatformInfo('all',$uid);
    	$nextTimeList = PlatformAccountApi::getOrderSyncNextTimeInterval();
    	
    	// 获取Ensogo user list
//     	$EnsogoUserList = [];
//     	if (!empty($allBindingAccountData['ensogo'])){
//     		$EnsogoUserList = EnsogoAccountsApiHelper::ListAccounts($uid);
//     	}
    	
//     	$allUserList['EnsogoUserList'] = $EnsogoUserList;
//     	$allUserList['platform']['ensogo'] = $EnsogoUserList;
    	
    	
    	//$tmp = PlatformAccountApi::getAllPlatformBindingSituation(['ensogo','ebay']);
    	// 获取ebay user list
    	if (!empty($allBindingAccountData['ebay'])){
    		$ebayUserList = EbayAccountsApiHelper::helpList('expiration_time' , 'asc');
    	}else{
    		$ebayUserList = [];
    	}
    	
    	foreach($ebayUserList as &$user){
    		/*	计算下次同步 时间
    		 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
    		 * 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
    		 */
    		
    		$currentPlaform = 'ebay';
    		$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['selleruserid']);
			
    		
    		if (!empty($tmp['result']['last_time'])){
    			if (empty($nextTimeList['ebay'])) $nextTimeList['ebay'] = 0.5;
    			$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
    		}else{
    			$user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList[$currentPlaform]);
    		}
    	}
    	
    	
    	$allUserList["ebayUserList"] = $ebayUserList;
    	$allUserList['platform']['ebay'] = $ebayUserList;
    	unset($user); //release 
    	// 获取amazon user list
    	if (!empty($allBindingAccountData['amazon'])){
    		$amazonUserList = SaasAmazonUser::find()->where("uid = '$uid'")->andWhere('is_active<>3')->asArray()->all();
    		$currentPlaform = 'amazon';
    		if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
			
    		foreach ($amazonUserList as &$amazonUser){
    			/*	计算下次同步 时间
    			 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
    			* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
    			*/
    			//计算next time start
    			
    			$tmpRT = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $amazonUser['amazon_uid']);
    			//计算next time end
    			
    			$amazonMarketCol = SaasAmazonUserMarketplace::find()->where("amazon_uid=:amazon_uid",array(":amazon_uid" => $amazonUser['amazon_uid']))->all();
    			$countryList = "";
    			foreach($amazonMarketCol as $amzonMarketplace){
    				$countryCode = SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG[$amzonMarketplace->marketplace_id];
    				$countryList = $countryList." ".SaasAmazonAutoSyncApiHelper::$COUNTRYCODE_NAME_MAP[$countryCode];
    				
//     				if(empty($amazonUser['mws_auth_token']) && !empty($amzonMarketplace->mws_auth_token))
//     				    $amazonUser['mws_auth_token'] = $amzonMarketplace->mws_auth_token;
    				
    				//计算next time start
    				if (!empty($amazonUser['next_time'] ))
    					$amazonUser['next_time'] .='<br>';
    				else
    					$amazonUser['next_time'] = '';
    				$amazonUser['next_time'] .= "[".SaasAmazonAutoSyncApiHelper::$COUNTRYCODE_NAME_MAP[$countryCode]."]". date('Y-m-d H:i:s',$amazonUser['create_time']+60*60*$nextTimeList[$currentPlaform]);
    				//计算next time end
    			}
    			$amazonUser['countryList'] = $countryList;
				
    			//计算next time start
    			if (!empty($tmpRT['result'])){
    				
    				$amazonUser['next_time'] = '';
    				foreach($tmpRT['result'] as $tmp_country=>$tmp){
    					if (!empty($amazonUser['next_time'] ))
    						$amazonUser['next_time'] .='<br>';
    			
						if (!empty($tmp['last_time'])){
							$amazonUser['next_time'] .= "[$tmp_country]". date('Y-m-d H:i:s',$tmp['last_time']+60*60*$nextTimeList[$currentPlaform]);
						}
    					
    				}
    			}
    			
    			// 没有国家
    			if (empty($amazonUser['next_time'])){
    				$amazonUser['next_time'] =  date('Y-m-d H:i:s',$amazonUser['create_time']+60*60*$nextTimeList[$currentPlaform]);
    			}
    			//计算next time end
    		}
    	}else{
    		$amazonUserList = [];
    	}
    	
    	$allUserList['amazonUserList'] = $amazonUserList;
    	$allUserList['platform']['amazon'] = $amazonUserList;
    	unset($user); //release
    	
    	// 获取速卖通 user list
    	if (!empty($allBindingAccountData['aliexpress'])){
    		$users = SaasAliexpressUser::find()->where('uid ='.$uid)
    		->orderBy('refresh_token_timeout desc')
    		->asArray()
    		->all();
    		 
    		$aliexpressUserList = array();
    		foreach ($users as $user){
    			$tmp = AliexpressAccountsApiHelper::getLastOrderSyncDetail($user['sellerloginid']);
    			/*	计算下次同步 时间
    			 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
    			 * 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
    			 */
    			if (!empty($tmp['result']['next_time'])){
    				$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['next_time']);
    			}else if (!empty($tmp['result']['last_time'])){
    				if (empty($nextTimeList['aliexpress'])) $nextTimeList['aliexpress'] = 0.5;
    				$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList['aliexpress']);
    			}else{
    				$user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList['aliexpress']);
    			}
    			
    			if(empty($user['version'])){
    				$user['refresh_token_timeout'] = '授权异常，请重新授权！';
    			}
    			else{
    				if($user['refresh_token_timeout'] > 0){
    					if($user['refresh_token_timeout'] < time()){
    						$user['refresh_token_timeout'] = '已过期';
    					}
    					else{
    						$user['refresh_token_timeout'] = date('Y-m-d',$user['refresh_token_timeout']);
    					}
    				}
    				else{
    					$user['refresh_token_timeout'] = '未授权';
    				}
    			}
    			$aliexpressUserList[] = $user;
    		}
    	}else{
    		$aliexpressUserList = [];
    	}
    	
    	$allUserList['aliexpressUserList'] = $aliexpressUserList;
    	$allUserList['platform']['aliexpress'] = $aliexpressUserList;
    	// 获取wish user list
    	if (!empty($allBindingAccountData['wish'])){
    		$WishUserData = SaasWishUser::find()->where(["uid" => $uid])
    		->orderBy("last_order_success_retrieve_time desc")
    		->asArray()
    		->all();
    	}else{
    		$WishUserData = [];
    	}
    	
    	foreach($WishUserData as &$user){
    		/*	计算下次同步 时间
    		 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
    		* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
    		*/
    	
    		$currentPlaform = 'wish';
    		$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['site_id']);
    	
    	
    		if (!empty($tmp['result']['last_time'])){
    			if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
    			$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
    		}else{
    			$user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList[$currentPlaform]);
    		}
    	}
    	
    	
    	$allUserList['WishUserList'] = $WishUserData;
    	$allUserList['platform']['wish'] = $WishUserData;
    	unset($user);
    	// 获取敦煌 user list
    	if (!empty($allBindingAccountData['dhgate'])){
    		$dhgateUserData = SaasDhgateUser::find()->where(["uid" => $uid])->andWhere('is_active <> 3')
    		->orderBy("refresh_token_timeout desc")
    		->asArray()
    		->all();
    		 
    		$dhgateUserList = array();
    		foreach ($dhgateUserData as $UserData){
    			$UserData['refresh_token_timeout'] = $UserData['refresh_token_timeout'] > 0?date('Y-m-d H:i:s',$UserData['refresh_token_timeout']):'未授权';
    			$dhgateUserList[] = $UserData;
    		}
    	}else{
    		$dhgateUserList = [];
    	}
    	
    	foreach($dhgateUserList as &$user){
    		/*	计算下次同步 时间
    		 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
    		* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
    		*/
    		 
    		$currentPlaform = 'dhgate';
    		$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['dhgate_uid']);
    		 
    		 
    		if (!empty($tmp['result']['last_time'])){
    			if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
    			$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
    		}else{
    			$user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList[$currentPlaform]);
    		}
    	}
    	
    	$allUserList['dhgateUserList'] = $dhgateUserList;
    	$allUserList['platform']['dhgate'] = $dhgateUserList;
    	
    	unset($user);
    	// 获取cdiscount user list
    	if (!empty($allBindingAccountData['cdiscount'])){
    		$CdiscountUserData = SaasCdiscountUser::find()->where(["uid" => $uid])
    		->orderBy("last_order_success_retrieve_time desc")
    		->asArray()
    		->all();
    	}else{
    		$CdiscountUserData = [];
    	}
    	 
    	foreach($CdiscountUserData as &$user){
    		/*	计算下次同步 时间
    		 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
    		* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
    		*/
    		 
    		$currentPlaform = 'cdiscount';
    		$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['site_id']);
    		 
    		 
    		if (!empty($tmp['result']['last_time'])){
    			if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
    			$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
    		}else{
    			$user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList[$currentPlaform]);
    		}
    	}
    	
    	$allUserList['CdiscountUserList'] = $CdiscountUserData;
    	$allUserList['platform']['cdiscount'] = $CdiscountUserData;
    	unset($user);
		// 获取Lazada user list
    	// @todo lazada 和 linio user共用一个表，如果将来要分开，这里的过滤条件platform要去掉
		$LazadaUserList = array();
		if (!empty($allBindingAccountData['lazada'])){
			$lazadaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping();
			$lazadaUserData = SaasLazadaUser::find()->where(["puid" => $uid,"platform"=>"lazada"])->andWhere('status <> 3')
			->asArray()
			->all();
				
			foreach ($lazadaUserData as $lazadaUser){
				if (!empty($lazadaSite[$lazadaUser['lazada_site']]))
					$lazadaUser['lazada_site'] = $lazadaSite[$lazadaUser['lazada_site']];
				else
					$lazadaUser['lazada_site'] ='';
				$LazadaUserList[] = $lazadaUser;
			}
		}
		
		//查询lazada所有同步信息
		$tmps = LazadaApiHelper::getLastOrderSyncDetailV2(0, 'lazada');
		
		if($tmps['success']){
			foreach($LazadaUserList as &$user){
				/*	计算下次同步 时间
				 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
				* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
				*/
				 
				if(array_key_exists($user['lazada_uid'], $tmps['result'])){
					$tmp = $tmps['result'][$user['lazada_uid']];
				    if(!empty($tmp['next_time'])){
				        $user['next_time'] = date('Y-m-d H:i:s',$tmp['next_time']);
				    }else if (!empty($tmp['last_time'])){
				        if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
				        $user['next_time'] = date('Y-m-d H:i:s',$tmp['last_time']+60*60*$nextTimeList[$currentPlaform]);
				    }else{
				        $user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList[$currentPlaform]);
				    }
				    
				    $user['last_time'] = empty($tmp['last_time'])?"":date('Y-m-d H:i:s',$tmp['last_time']);
				    $user['message'] = empty($tmp['message'])?"":$tmp['message'];
				}
			}
		}
		
		$allUserList['LazadaUserList'] = $LazadaUserList;
		$allUserList['platform']['lazada'] = $LazadaUserList;
		unset($user);
		unset($tmps);
		// 获取Linio user list
		// @todo lazada 和 linio user共用一个表，如果将来要分开，这里的过滤条件platform要去掉
		$LinioUserList = array();
		if (!empty($allBindingAccountData['linio'])){
			$linioSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping('linio');
			$linioUserData = SaasLazadaUser::find()->where(["puid" => $uid,"platform"=>"linio"])->andWhere('status <> 3')
			->asArray()
			->all();
			
			foreach ($linioUserData as $linioUser){
				if (!empty($linioSite[$linioUser['lazada_site']]))
					$linioUser['lazada_site'] = $linioSite[$linioUser['lazada_site']];
				else
					$linioUser['lazada_site'] ='';
				$LinioUserList[] = $linioUser;
			}
		}
		
		foreach($LinioUserList as &$user){
			/*	计算下次同步 时间
			 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
			* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
			*/
		
			$currentPlaform = 'linio';
			$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['lazada_uid']);
		
		
	        if($tmp['success']){
			    if(!empty($tmp['result']['next_time'])){
			        $user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['next_time']);
			    }else if (!empty($tmp['result']['last_time'])){
			        if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
			        $user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
			    }else{
			        $user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList[$currentPlaform]);
			    }
			    
			    $user['last_time'] = empty($tmp['result']['last_time'])?"":date('Y-m-d H:i:s',$tmp['result']['last_time']);
			    $user['message'] = empty($tmp['result']['message'])?"":$tmp['result']['message'];
			}else{
				$user['last_time'] = '';
				$user['next_time'] = '';
				$user['message']  ='没有同步信息';
			}
		}
		
		$allUserList['LinioUserList'] = $LinioUserList;
		$allUserList['platform']['linio'] = $LinioUserList;
		unset($user);
		// 获取Jumia user list
		// @todo lazada jumia 和 linio user共用一个表，如果将来要分开，这里的过滤条件platform要去掉
		$JumiaUserList = array();
		if (!empty($allBindingAccountData['jumia'])){
			$jumiaSite = LazadaApiHelper::getLazadaCountryCodeSiteMapping('jumia');
			$jumiaUserData = SaasLazadaUser::find()->where(["puid" => $uid,"platform"=>"jumia"])->andWhere('status <> 3')
			->asArray()
			->all();
			
			foreach ($jumiaUserData as $jumiaUser){
				if (!empty($jumiaSite[$jumiaUser['lazada_site']]))
					$jumiaUser['lazada_site'] = $jumiaSite[$jumiaUser['lazada_site']];
				else
					$jumiaUser['lazada_site'] ='';
				$JumiaUserList[] = $jumiaUser;
			}

		}
		
		foreach($JumiaUserList as &$user){
			/*	计算下次同步 时间
			 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
			* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
			*/
		
			$currentPlaform = 'jumia';
			$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['lazada_uid']);
		
		
	        if($tmp['success']){
			    if(!empty($tmp['result']['next_time'])){
			        $user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['next_time']);
			    }else if (!empty($tmp['result']['last_time'])){
			        if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
			        $user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
			    }else{
			        $user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList[$currentPlaform]);
			    }
			    
			    $user['last_time'] = empty($tmp['result']['last_time'])?"":date('Y-m-d H:i:s',$tmp['result']['last_time']);
			    $user['message'] = empty($tmp['result']['message'])?"":$tmp['result']['message'];
			}
		}
		
		$allUserList['JumiaUserList'] = $JumiaUserList;
		$allUserList['platform']['jumia'] = $JumiaUserList;
		unset($user);
		
		/*
		// 获取Ensogo user list
		$EnsogoUserList = [];
		$EnsogoUserList = EnsogoAccountsApiHelper::ListAccounts($uid);
		$allUserList['EnsogoUserList'] = $EnsogoUserList;
		*/
		
		// 获取Priceminister user list
		//if (!empty($allBindingAccountData['priceminister'])){
			$PriceministerUserData = SaasPriceministerUser::find()->where(["uid" => $uid])
			->orderBy("last_order_success_retrieve_time desc")
			->asArray()
			->all();
		//}else{
		//	$PriceministerUserData = [];
		//}
		
		foreach($PriceministerUserData as &$user){
			/*	计算下次同步 时间
			 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
			* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
			*/
		
			$currentPlaform = 'priceminister';
			$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['site_id']);
			
		
			if (!empty($tmp['result']['last_time'])){
				if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
				$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
			}else{
				$user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList[$currentPlaform]);
			}
		}
			
		$allUserList['PriceministerUserList'] = $PriceministerUserData;
		$allUserList['platform']['priceminister'] = $PriceministerUserData;
		unset($user);
		
 
		// 获取bonanza user list
		if (!empty($allBindingAccountData['bonanza'])){
		    $BonanzaUserData = SaasBonanzaUser::find()->where(["uid" => $uid])
		    ->orderBy("last_order_success_retrieve_time desc")
		    ->asArray()
		    ->all();
		}else{
		    $BonanzaUserData = [];
		}
		
		foreach($BonanzaUserData as &$user){
			/*	计算下次同步 时间
			 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
			* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
			*/
		
			$currentPlaform = 'bonanza';
			$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['site_id']);
				
		
			if (!empty($tmp['result']['last_time'])){
				if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
				$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
			}else{
				$user['next_time'] = date('Y-m-d H:i:s',strtotime($user['create_time'])+60*60*$nextTimeList[$currentPlaform]);
			}
		}
		
		$allUserList['BonanzaUserList'] = $BonanzaUserData;
		$allUserList['platform']['bonanza'] = $BonanzaUserData;
		unset($user);
		
		
		// 获取ruamll user list
// 		if (!empty($allBindingAccountData['rumall'])){
// 		    $RumallUserData = SaasRumallUser::find()->where(["uid" => $uid])
// 		    ->orderBy("last_order_success_retrieve_time desc")
// 		    ->asArray()
// 		    ->all();
// 		}else{
// 		    $RumallUserData = [];
// 		}
// 		foreach($RumallUserData as &$user){
// 		    /*	计算下次同步 时间
// 		     * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
// 		     * 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
// 		     */
		
// 		    $currentPlaform = 'rumall';
// 		    $tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['site_id']);
		    
		
// 		    if (!empty($tmp['result']['last_time'])){
// 		        if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
// 		        $user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
// 		    }else{
// 		        $user['next_time'] = date('Y-m-d H:i:s',strtotime($user['create_time'])+60*60*$nextTimeList[$currentPlaform]);
// 		    }
// 		}
		
// 		$allUserList['RumallUserList'] = $RumallUserData;
// 		$allUserList['platform']['rumall'] = $RumallUserData;
// 		unset($user);
		
		// 获取newegg user list
		if (!empty($allBindingAccountData['newegg'])){
			$NeweggUserData = SaasNeweggUser::find()->where(["uid" => $uid])
			->orderBy("update_time desc")
			->asArray()
			->all();
		}else{
			$NeweggUserData = [];
		}
		
		foreach($NeweggUserData as &$user){
			/*	计算下次同步 时间
			 * 1.存在最后 一次 同步订单 时间的直接 加上时间间隔 （目前是半小时）
			* 2.不存在最后一次订单同步 时间的 则为新账号， 默认为账号绑定时间， 再加上时间间隔 （目前是半小时）
			*/
		
			$currentPlaform = 'newegg';
			$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['site_id']);
		
			if (!empty($tmp['result']['last_time'])){
				$user['last_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']);
				if (empty($nextTimeList[$currentPlaform])) $nextTimeList[$currentPlaform] = 0.5;
				$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['last_time']+60*60*$nextTimeList[$currentPlaform]);
			}else{
				$user['next_time'] = date('Y-m-d H:i:s',$user['create_time']+60*60*$nextTimeList[$currentPlaform]);
			}
		}
			
		$allUserList['NeweggUserList'] = $NeweggUserData;
		$allUserList['platform']['newegg'] = $NeweggUserData;
		unset($user);
		
		
		// 获取paypal user list
		//if (empty($allBindingAccountData['paypal'])){
			$PaypalUserList = SaasPaypalUser::find()->where(["puid" => $uid])
			->orderBy("ppid asc")
			->asArray()
			->all();
		//}
			
		$allUserList['paypalUserList'] = $PaypalUserList;
		$allUserList['platform']['paypal'] = $PaypalUserList;
		
		//获取自定义店铺账号
		//if (empty($allBindingAccountData['customized'])){
			$CustomizedUserList = SaasCustomizedUser::find()->where(["uid" => $uid])
			->orderBy("site_id asc")
			->asArray()
			->all();
		//}
			
		$allUserList['CustomizedUserList'] = $CustomizedUserList;
		$allUserList['platform']['customized'] = $CustomizedUserList;
		unset($user);
		
		// 获取1688 user list
		$users = Saas1688User::find()->where('uid ='.$uid)
			->orderBy('refresh_token_timeout desc')
			->asArray()
			->all();
		 
		$Al1688UserList = array();
		foreach ($users as $user){
			$tmp = Al1688AccountsApiHelper::getLastOrderSyncDetail($user['aliId']);
			$user['refresh_token_timeout'] = $user['refresh_token_timeout'] > 0?date('Y-m-d',$user['refresh_token_timeout']):'未授权';
			$Al1688UserList[] = $user;
		}
		 
		$allUserList['Al1688UserList'] = $Al1688UserList;
		$allUserList['platform']['1688'] = $Al1688UserList;
		// 获取Shopee user list
		$ShopeeUserList = array();
		if (!empty($allBindingAccountData['shopee'])){
			$shopeeSite = ShopeeAccountsApiHelper::getCountryCodeSiteMapping();
			$shopeeUserData = SaasShopeeUser::find()->where(["puid" => $uid])->andWhere('status <> 3')->asArray()->all();
		
			foreach ($shopeeUserData as $shopeeUser){
				if (!empty($shopeeSite[$shopeeUser['site']]))
					$shopeeUser['site'] = $shopeeSite[$shopeeUser['site']];
				else
					$shopeeUser['site'] ='';
				$ShopeeUserList[] = $shopeeUser;
			}
		}
		
		foreach($ShopeeUserList as &$user){
			$currentPlaform = 'shopee';
			$tmp = PlatformAccountApi::getOrderSyncInfo($currentPlaform, $user['shopee_uid']);
		
			if($tmp['success']){
				if(empty($nextTimeList[$currentPlaform])){
					$nextTimeList[$currentPlaform] = 0.5;
				}
				
				if(!empty($tmp['result']['next_time'])){
					$user['next_time'] = date('Y-m-d H:i:s',$tmp['result']['next_time']);
				}else if (!empty($tmp['result']['last_time'])){
					$user['next_time'] = date('Y-m-d H:i:s', $tmp['result']['last_time'] + 3600 * $nextTimeList[$currentPlaform]);
				}else{
					$user['next_time'] = date('Y-m-d H:i:s', $user['create_time'] + 3600 * $nextTimeList[$currentPlaform]);
				}
				 
				$user['last_time'] = empty($tmp['result']['last_time'])?"":date('Y-m-d H:i:s',$tmp['result']['last_time']);
				$user['message'] = empty($tmp['result']['message'])?"":$tmp['result']['message'];
			}
		}
		
		$allUserList['ShopeeUserList'] = $ShopeeUserList;
		$allUserList['platform']['shopee'] = $ShopeeUserList;
		unset($user);
		
		//获取所有message 同步 情况
		
		$allMessageSyncList = SaasMessageAutosync::find()->where(['uid'=>$uid])->asArray()->all();
		$MsgErrorInfo = [];
		foreach($allMessageSyncList as $MsgSyncInfo){
			if (empty($MsgSyncInfo['message'])) continue;
			if (!empty($MsgErrorInfo [$MsgSyncInfo['platform_source']][$MsgSyncInfo['platform_uid']]))
				$MsgErrorInfo [$MsgSyncInfo['platform_source']][$MsgSyncInfo['platform_uid']] .= '<br>站内信错误信息：'.$MsgSyncInfo['message'];
			else
				$MsgErrorInfo [$MsgSyncInfo['platform_source']][$MsgSyncInfo['platform_uid']] = '<br>站内信错误信息：'.$MsgSyncInfo['message'];
		}
		$allUserList['msg_error'] = $MsgErrorInfo;
		
		//
		return $this->render('bind_platform_store' , $allUserList );
    }//end of actionPlatform_account_binding
	
	/**
	 +----------------------------------------------------------
	 * ensogo 单独的注册 页面
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name			date			note
	 * @author		lkh				2016/01/16		初始化
	 +----------------------------------------------------------
	 **/
	public function actionEnsogoRegisterPage(){
		//$EnsogoData = [];
		
		return $this->render('ensogo_register_page.php');
		
	}//end of actionEnsogoRegisterPage



    /**
     * +----------------------------------------------------------
     * Cdiscount 单独的注册 页面
     * +----------------------------------------------------------
     * @access public
     * +----------------------------------------------------------
     * log            name            date            note
     * @author        dwg                2016/03/7        初始化
     * +----------------------------------------------------------
     **/
    public function actionCdiscountRegisterPage()
    {
        return $this->render('cdiscount_register_page');
    }

    /**
     * cdiscount 注册操作
     * @author dwg
     */
    public function actionCdiscountRegister()
    {
        $postarr = \Yii::$app->request->post();//提交到后台的全部数据（除了图片文件）

        $success = false;
        $message = null;

        AppTrackerApiHelper::actionLog("image_lib", "/util/image/upload");
        set_time_limit(0);
//        $imgTmpPath = \Yii::getAlias('@app') . '/web/attachment/image/';
        $imgTmpPath = \Yii::getAlias("@eagle/web/attachment/image/cdiscount-submit").DIRECTORY_SEPARATOR;


        //检查上传的图片信息
        if (!isset($_FILES["company_license_picture"]) || !is_uploaded_file($_FILES["company_license_picture"]["tmp_name"])) {    //是否存在文件
            return json_encode(array('name' => null, 'status' => false, 'size' => null, 'message' => TranslateHelper::t("公司营业执照图片不存在!"),'success' => $success));
        }

        if (!isset($_FILES["sponsor_id_card_picture"]) || !is_uploaded_file($_FILES["sponsor_id_card_picture"]["tmp_name"])) {    //是否存在文件
            return json_encode(array('name' => null, 'status' => false, 'size' => null, 'message' => TranslateHelper::t("法人身份证照图片不存在!"),'success' => $success));
        }

        $file_license = isset($_FILES['company_license_picture']) ? $_FILES['company_license_picture'] : '';
        $file_id_card = isset($_FILES['sponsor_id_card_picture']) ? $_FILES['sponsor_id_card_picture'] : '';

        //检查上传是否成功
        if ($file_license["error"] > 0 || $file_license["size"] <= 0) {
            return json_encode(array('name' => null, 'status' => false, 'size' => null, 'message' => TranslateHelper::t("公司营业执照图片上传出错，请稍候再试..."),'success' => $success));
        }
        if ($file_id_card["error"] > 0 || $file_id_card["size"] <= 0) {
            return json_encode(array('name' => null, 'status' => false, 'size' => null, 'message' => TranslateHelper::t("法人身份证照图片上传出错，请稍候再试..."),'success' => $success));
        }


        //检查格式
        $message = "";
        if (!in_array(strtolower(substr($file_license["name"], strrpos($file_license["name"], ".") + 1)), ImageHelper::$photoMime) || !array_key_exists($file_license["type"], ImageHelper::$photoMime)) {
            $message .= TranslateHelper::t("%s :对不起，我们只支持上传 jpg , gif , png 格式的图片！", $file_license["name"], implode(",", array_keys(ImageHelper::$photoMime)));
        }
        if (!in_array(strtolower(substr($file_id_card["name"], strrpos($file_id_card["name"], ".") + 1)), ImageHelper::$photoMime) || !array_key_exists($file_id_card["type"], ImageHelper::$photoMime)) {
            $message .= TranslateHelper::t("%s :对不起，我们只支持上传 jpg , gif , png 格式的图片！", $file_id_card["name"], implode(",", array_keys(ImageHelper::$photoMime)));
        }

        //检查上传文件最大size
        if ($file_license["size"] > ImageHelper::$photoMaxSize) {
            $message .= TranslateHelper::t("%s :图片 %s K , 不能超出规定大小  %s K ， 请重新上传图片!", $file_license["name"], round($file_license["size"] / 1024), (ImageHelper::$photoMaxSize / 1024));
        }
        if ($file_id_card["size"] > ImageHelper::$photoMaxSize) {
            $message .= TranslateHelper::t("%s :图片 %s K , 不能超出规定大小  %s K ， 请重新上传图片!", $file_id_card["name"], round($file_id_card["size"] / 1024), (ImageHelper::$photoMaxSize / 1024));
        }


        //重命名文件，上传文件
        $name = $file_license["name"];
        $originName = date('YmdHis') . '-' . rand(1, 100) . substr(md5($name), 0, 5) . '.' . pathinfo($name, PATHINFO_EXTENSION);

        $name_id_card = $file_id_card["name"];
        $originName_id_card = date('YmdHis') . '-' . rand(1, 100) . substr(md5($name_id_card), 0, 5) . '.' . pathinfo($name_id_card, PATHINFO_EXTENSION);


        if (move_uploaded_file($file_license["tmp_name"], $imgTmpPath . $originName) === false) {// 重命名上传图片
            return json_encode(array('name' => $file_license["name"], 'status' => false, 'size' => $file_license["size"], 'message' => TranslateHelper::t('系统获取公司营业执照图片失败！'),'success' => $success));
        }
        if (move_uploaded_file($file_id_card["tmp_name"], $imgTmpPath . $originName_id_card) === false) {// 重命名上传图片
            return json_encode(array('name' => $file_id_card["name"], 'status' => false, 'size' => $file_id_card["size"], 'message' => TranslateHelper::t('系统获取法人身份证照图片失败！'),'success' => $success));
        }


        //unlink ($imgTmpPath . $originName );// 删除保存到本地的上传图片
        $Path = '/attachment/image/cdiscount-submit/'; //用来读取图片的路径

        //插入相关信息到用户表：cd_shop_opening_application
        $cdiscountRegister = new CdiscountRegister();
        $cdiscountRegister->puid = $postarr['puid'];
        $cdiscountRegister->company_license_picture = $Path . $originName;
        $cdiscountRegister->sponsor_id_card_picture = $Path . $originName_id_card;
        $cdiscountRegister->company_e_name = $postarr['company_e_name'];
        $cdiscountRegister->company_license_code = $postarr['company_license_code'];
        $cdiscountRegister->company_e_address = $postarr['company_e_address'];
        $cdiscountRegister->company_postcode = $postarr['company_postcode'];
        $cdiscountRegister->shop_e_name = $postarr['shop_e_name'];
        $cdiscountRegister->director_e_name = $postarr['director_e_name'];
        $cdiscountRegister->phone = $postarr['phone'];
        $cdiscountRegister->e_mail = $postarr['e_mail'];
        $cdiscountRegister->all_website_link = $postarr['all_website_link'];
        $cdiscountRegister->oversea_warehouse_name = $postarr['oversea_warehouse_name'];
        $cdiscountRegister->third_party_payment_method = $postarr['third_party_payment_method'];
        $cdiscountRegister->create_time = strtotime(date('Y-m-d H:i:s'));

        if (!empty($_FILES['brand_picture']['tmp_name'])) {
            $file_brand = $_FILES['brand_picture'];

            if (!in_array(strtolower(substr($file_brand["name"], strrpos($file_brand["name"], ".") + 1)), ImageHelper::$photoMime) || !array_key_exists($file_brand["type"], ImageHelper::$photoMime)) {
                $message .= TranslateHelper::t("%s :对不起，我们只支持上传 jpg , gif , png 格式的图片！", $file_brand["name"], implode(",", array_keys(ImageHelper::$photoMime)));
            }

            if ($file_brand["size"] > ImageHelper::$photoMaxSize) {
                $message .= TranslateHelper::t("%s :图片 %s K , 不能超出规定大小  %s K ， 请重新上传图片!", $file_brand["name"], round($file_brand["size"] / 1024), (ImageHelper::$photoMaxSize / 1024));
            }

            $name_brand = $file_brand["name"];
            $originName_brand = date('YmdHis') . '-' . rand(1, 100) . substr(md5($name_brand), 0, 5) . '.' . pathinfo($name_brand, PATHINFO_EXTENSION);

            if (move_uploaded_file($file_brand["tmp_name"], $imgTmpPath . $originName_brand) === false) {// 重命名上传图片
                return json_encode(array('name' => $file_brand["name"], 'status' => false, 'size' => $file_brand["size"], 'message' => TranslateHelper::t('系统获取品牌图片失败！'),'success' => $success));
            }

            $cdiscountRegister->brand_picture = $Path . $originName_brand;

        }

        if ($message <> '') {
            return json_encode(array('name' => $file_license["name"], 'status' => false, 'size' => $file_license["size"], 'message' => $message,'success' => $success));
        }

        if (!$cdiscountRegister->save(false)) {
            return json_encode(array('success' => $success, 'message' => '上传数据失败,请联系客服！'));
        } else {
            $success = true;
            return json_encode(array('success' => $success, 'message' => '上传数据成功，请等候客服进行审核！'));
        }

    }

    /**
     * 管理员审核cdiscount注册info页面
     * @author dwg
     */
    public function actionCdiscountCheckPage()
    {
        if(PlatformHelper::cdApprovalAuthorized()===true)  //指定puid能访问到，防止直接打url访问到该页面
        {
            $pageSize = isset($_GET['per-page'])?$_GET['per-page']:20;

            $search_email = isset($_REQUEST['search_email'])?trim($_REQUEST['search_email']):"";
            $startdate = isset($_REQUEST['Startdate'])?trim($_REQUEST['Startdate']):"";
            $enddate = isset($_REQUEST['Enddate'])?trim($_REQUEST['Enddate']):"";

            //        $cd_register_models = CdiscountRegister::find()->asArray()->all();
            $data = CdiscountRegister::find();

            if (!empty($search_email)){
                $data->andWhere("e_mail = :search_email",[':search_email'=>$search_email]);

            }

            if(!empty($startdate)){
                $startdatetime = $startdate.' 00:00:00';
                $data->andWhere("create_time > :search_startdate",[':search_startdate'=>strtotime($startdatetime)]);
            }
            if(!empty($enddate)){
                $enddatetime = $enddate.' 23:59:59';
                $data->andWhere("create_time < :search_enddate",[':search_enddate'=>strtotime($enddatetime)]);
            }

            //必须加上一个默认排序
            $ordersort = '';
            $ordersort .= 'create_time desc';

            $pages = new Pagination([
                'defaultPageSize' => 20,
                'pageSize' => $pageSize,
                'totalCount' => $data->count(),
                'pageSizeLimit'=>[5,200],//每页显示条数范围
                'params'=>$_REQUEST,
            ]);
            $cd_register_models = $data->offset($pages->offset)
                ->orderBy($ordersort)
                ->limit($pages->limit)
                ->all();

            /*echo models current sql */
            $command = $data->offset($pages->offset)
                ->limit($pages->limit)->createCommand();
//        echo $command->getRawSql();

            return $this->render('cdiscount_check_page',array(
                'cd_register_models'=>$cd_register_models,
                'pages' => $pages,
                'startdate' =>$startdate,
                'enddate' =>$enddate,

            ));
        }
        else{
            return $this->render('cdiscount_register_page');
        }


    }

    /**
     * 管理员审核页面->更新备注页面+操作
     * @author dwg
     */
    public function actionUpdateDescPage(){
        $id = isset($_REQUEST['id'])?$_REQUEST['id']:'';
        $update_desc = isset($_REQUEST['update_desc'])?$_REQUEST['update_desc']:'';

        if(!empty($update_desc)){
            $cdiscountRegister = CdiscountRegister::findOne(['id'=>$id]);
            $cdiscountRegister->desc = $update_desc;
            if($cdiscountRegister->save(false)){ //save方法加false可以跳过yii的验证保存
                return json_encode(array('message'=>'修改备注成功！'));
            };
        }

        return $this->renderAjax('update_desc_page',array(
            'id'=>$id,
        ));
    }


    /**
     * +----------------------------------------------------------
     * Linio 开铺申请注册 页面
     * @author        dwg       2016/08/12
     * +----------------------------------------------------------
     **/
    public function actionLinioRegisterPage()
    {
        $puid = \Yii::$app->user->identity->getParentUid();
        $is_exist= LinioRegister::find()->where(['puid'=>$puid])->asArray()->all();
        return $this->render('linio_register_page',['puid'=>$puid,'is_exist'=>$is_exist]);
    }
    public function actionLinioRegister()
    {
        $postarr = \Yii::$app->request->post();

        $check_result = $postarr['check_result'];//审核结果

        $delete_arr = [];
        $combine_country = ['Chile','Colombia','Mexico','Panama','Peru'];
        $ship_service_arrive_day = [];
        $arriveDay = '';
        $i = 1;
        /***start 截取货运信息内容,来合并成cargo_info的json字段放入数据库，再删除合并前数组的货运信息和审核结果***/
        $cargo_info_arr = array_slice($postarr,-12,10);
        foreach($cargo_info_arr as $cargo){
            if($i%2 == 0){
                $ship_service_arrive_day[$cargo] = $arriveDay;
                $delete_arr[] = $ship_service_arrive_day;
            }else{
                $arriveDay = $cargo;
            }
            $i++;
        }
        foreach($delete_arr as $delete){
            $combine_service_arrive[] = array_splice($delete,-1);
        }
        //合成数组为array(国家1=>array(货运方式1=>货运时长),国家2=>(货运方式2=>货运时长))
        $final_combine = array_combine($combine_country,$combine_service_arrive);

        array_splice($postarr,-12,10);
        array_splice($postarr,1,1);

        /***end 截取货运信息内容,来合并成cargo_info的json字段放入数据库，再删除合并前的货运信息和审核结果***/

        $postarr['cargo_info'] = json_encode($final_combine);
        $postarr['create_time'] = time();

        $model = new LinioRegister();
        //批量插入数据库
        if($check_result == 0) {
            $model->setAttributes($postarr);
            $num = $model->save(false);
        }
        //批量更新数据库
        elseif($check_result == 2){
            $no_pass_data = LinioRegister::findOne(['puid'=>$postarr['puid']]);
            $num = $model->updateAll($postarr,['id'=>$no_pass_data['id']]);
        }

        if (!$num) {
            return json_encode(array('success' => false, 'message' => '提交数据失败！！'));
        } else {
            return json_encode(array('success' => true, 'message' => '您的信息已提交完成<br>审核结果将尽快发送至您的注册邮箱'));
        }


    }





	

    /**
     * 查看和设置指定的绑定账号的同步信息
     * @return string
     */
    public function actionViewOrSetSyncConfig(){
    	$userInfo = \Yii::$app->user->identity;
    	if ($userInfo['puid']==0){
    		$uid = $userInfo['uid'];
    	}else {
    		$uid = $userInfo['puid'];
    	}
    	if(empty($uid))
    		return "请先登录！";
    	
    	$platform = !empty($_REQUEST['platform'])?$_REQUEST['platform']:'';
    	if (in_array($platform , ['aliexpress','ebay'])){
    		$account_key = !empty($_REQUEST['store_name'])?$_REQUEST['store_name']:'';
    	}else{
    		$account_key = !empty($_REQUEST['site_id'])?$_REQUEST['site_id']:'';
    	}
    	
    	//@todo Marketplace之类
    	
    	if(empty($platform) || empty($account_key))
    		return "平台信息或绑定的账号id信息丢失!";
    	
    	$statusMapping = [
    		'0'=>'等待同步',
    		'1'=>'已经有同步队列为他同步中',
    		'2'=>'同步成功',
    		'3'=>'同步失败',
    		'4'=>'同步完成',
    	];
    	
    	$syncInfo['messageSyncInfo']=[];
    	$messageSyncInfo = PlatformAccountApi::getMessageSyncInfo($platform, $account_key);
    	if(empty($messageSyncInfo['success']) && !empty($messageSyncInfo['message']) && empty($messageSyncInfo['result'])){
    		$syncInfo['messageSyncInfo']=['is_active'=>'','status'=>'','last_time'=>'','message'=>$messageSyncInfo['message']];
    	}
    	if(!empty($messageSyncInfo['success']) && empty($messageSyncInfo['message']) && !empty($messageSyncInfo['result'])){
    		$syncInfo['messageSyncInfo']=[
    		'is_active'=>$messageSyncInfo['result']['is_active'],
    		'status'=>isset($statusMapping[$messageSyncInfo['result']['status']])?$statusMapping[$messageSyncInfo['result']['status']]:$messageSyncInfo['result']['status'],
    		'last_time'=>empty($messageSyncInfo['result']['last_time'])?'N/A':date('Y-m-d H:i:s',$messageSyncInfo['result']['last_time']),
    		'message'=>$messageSyncInfo['result']['message']
    		];
    	}
    	 
    	$syncInfo['orderSyncInfo']=[];
    	$orderSyncInfo = PlatformAccountApi::getOrderSyncInfo($platform, $account_key);
    	
    	if(empty($orderSyncInfo['success']) && !empty($orderSyncInfo['message']) && empty($orderSyncInfo['result'])){
    		$syncInfo['orderSyncInfo'][]=['is_active'=>'','status'=>'','last_time'=>'','message'=>$orderSyncInfo['message']];
    	}
    	if(!empty($orderSyncInfo['success']) && empty($orderSyncInfo['message']) && !empty($orderSyncInfo['result'])){
    		if (in_array($platform , ['amazon'])){
    			foreach($orderSyncInfo['result'] as $mk=>$row){
    				$syncInfo['orderSyncInfo'][$mk] = [
		    			'is_active'=>$row['is_active'],
		    			'status'=>isset($statusMapping[$row['status']])?$statusMapping[$row['status']]:$row['status'],
		    			'times'=> isset($row['times'])?$row['times']:0,
		    			'last_time'=>empty($row['last_time'])?'N/A':date('Y-m-d H:i:s',$row['last_time']),
		    			'message'=>$row['message']
	    			];
    			}
    		}else{
    			$syncInfo['orderSyncInfo'][] =[
    			'is_active'=>$orderSyncInfo['result']['is_active'],
    			'status'=>isset($statusMapping[$orderSyncInfo['result']['status']])?$statusMapping[$orderSyncInfo['result']['status']]:$orderSyncInfo['result']['status'],
    			'times'=> isset($orderSyncInfo['result']['times'])?$orderSyncInfo['result']['times']:0,
    			'last_time'=>empty($orderSyncInfo['result']['last_time'])?'N/A':date('Y-m-d H:i:s',$orderSyncInfo['result']['last_time']),
    			'message'=>$orderSyncInfo['result']['message']
    			];
    		}
    		
    	}
    
    	$syncInfo['productSyncInfo']=[];
    	$productSyncInfo = PlatformAccountApi::getProductSyncInfo($platform, $account_key);
    	if(empty($productSyncInfo['success']) && !empty($productSyncInfo['message']) && empty($productSyncInfo['result'])){
    		$syncInfo['productSyncInfo']=['is_active'=>'','status'=>'','last_time'=>'','message'=>$productSyncInfo['message']];
    	}
    	if(!empty($productSyncInfo['success']) && empty($productSyncInfo['message']) && !empty($productSyncInfo['result'])){
    		$syncInfo['productSyncInfo']=[
    		'is_active'=>$productSyncInfo['result']['is_active'],
    		'status'=>isset($statusMapping[$productSyncInfo['result']['status']])?$statusMapping[$productSyncInfo['result']['status']]:$productSyncInfo['result']['status'],
    		'last_time'=>empty($productSyncInfo['result']['last_time'])?'N/A':date('Y-m-d H:i:s',$productSyncInfo['result']['last_time']),
    		'message'=>$productSyncInfo['result']['message']
    		];
    	}
    	
    	/*
		if(empty($accountInfo)){
			return "获取绑定的账号详细信息失败！";
		}else{
			return $this->renderAjax('view_and_set_sync',[
					'accountInfo'=>$accountInfo,
					]);
		}
		*/
		return $this->render('view_and_set_sync',[
				'syncInfo'=>$syncInfo,
				'platform'=>$platform,
				'site_id'=>$account_key,
				]);
    }
    
    public function actionSetSyncByType(){
    	$uid = \Yii::$app->user->id;
    	if(empty($uid))
    		return "请先登录!";
    	if(empty($_GET['platform']) || empty($_GET['site_id']) || empty($_GET['type']) || !isset($_GET['status']))
    		return "请指定平台和账号 和 设置同步的类型";
    	
    	if(empty($_GET['status']))
    		$sync='N';
    	else 
    		$sync='Y';
    	switch ($_GET['platform']){
    		case 'ebay':
    			$account = SaasEbayUser::find()->where(['uid'=>$uid,'ebay_uid'=>$_GET['site_id']])->one();
    			break;
    		case 'amazon':
    			$account = SaasAmazonUser::find()->where(['uid'=>$uid,'amazon_uid'=>$_GET['site_id']])->one();
    			break;
    		case 'aliexpress':
    			$account = SaasAliexpressUser::find()->where(['uid'=>$uid,'sellerloginid'=>$_GET['site_id']])->one();
		    	switch ($_GET['type']){
		    		case 'order':
		    			if ($sync == "Y"){
		    				$rt = AliexpressAccountsApiHelper::resetSyncOrderInfo($_GET['site_id'], $uid);
		    			}
		    			break;
		    		case 'message':
		    			break;
		    		case 'product':
		    			break;
		    		default:
		    			break;
		    	}
    			
    			break;
    		case 'wish':
    			$account = SaasWishUser::find()->where(['uid'=>$uid,'site_id'=>$_GET['site_id']])->one();
    			break;
    		case 'dhgate':
    			$account = SaasDhgateUser::find()->where(['uid'=>$uid,'dhgate_uid'=>$_GET['site_id']])->one();
    			break;
    		case 'cdiscount':
    			$account = SaasCdiscountUser::find()->where(['uid'=>$uid,'site_id'=>$_GET['site_id']])->one();
    			break;
    		case 'lazada':
    			$account = SaasLazadaUser::find()->where(['puid'=>$uid,'lazada_uid'=>$_GET['site_id']])->one();
    			break;
    		case 'linio':
    			$account = SaasLazadaUser::find()->where(['uid'=>$uid,'dhgate_uid'=>$_GET['site_id']])->one();
    			break;
    		case 'jumia':
    			$account = SaasLazadaUser::find()->where(['uid'=>$uid,'dhgate_uid'=>$_GET['site_id']])->one();
    			break;
    		//@todo 添加新平台					
    		default:
    			break;
    	}
    	
    	PlatformAccountApi::resetSyncSetting($_GET['platform'], $_GET['site_id'], $sync, $uid);
    	
    	if($account->save(false)){
    		return "设置成功，请刷新页面";
    	}else{
    		return print_r($account->getErrors());
    	}
    }
    
}
