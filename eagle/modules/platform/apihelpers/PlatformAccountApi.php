<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasAliexpressUser;
use eagle\models\SaasEbayUser;
use eagle\models\UserBase;
use eagle\models\SaasDhgateUser;
use eagle\models\SaasAmazonUser;
use common\helpers\Helper_Array;
use eagle\models\SaasWishUser;
use eagle\models\EbaySite;
use eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper;
use eagle\models\EbayShippingservice;
use eagle\models\SaasCdiscountUser;
use eagle\modules\carrier\helpers\CarrierHelper;
use eagle\models\SaasLazadaUser;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use eagle\models\SaasEnsogoUser;
use eagle\models\SaasMessageAutosync;
use eagle\modules\message\helpers\SaasMessageAutoSyncApiHelper;
use eagle\modules\order\helpers\AliexpressOrderHelper;
use eagle\modules\dhgate\apihelpers\DhgateApiHelper;
use eagle\modules\amazon\apihelpers\AmazonApiHelper;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\QueueGetorder;
use eagle\modules\message\apihelpers\MessageApiHelper;
use eagle\models\SaasAliexpressAutosync;
use eagle\models\SaasPriceministerUser;
use eagle\models\SaasBonanzaUser;
use eagle\models\AmzOrder;
use eagle\models\SaasRumallUser;
use eagle\modules\order\helpers\NeweggApiHelper;
use eagle\models\SaasNeweggUser;
use eagle\models\SaasNeweggAutosync;
use eagle\modules\order\models\OdOrder;
use eagle\models\SaasCustomizedUser;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\permission\helpers\UserHelper;
use eagle\modules\order\apihelpers\OrderApiHelper;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\models\SaasShopeeUser;
use eagle\models\SaasShopeeAutosync;
use eagle\models\SaasAliexpressAutosyncV2;
/**
 +------------------------------------------------------------------------------
 * 平台管理模块接口类
 +------------------------------------------------------------------------------
 * @category	platform
 * @author		dzt <zhitian.deng@witsion.com>
 * @version		1.0
 +------------------------------------------------------------------------------
 */

class PlatformAccountApi {
	
	private static $PLATFORMAPPREDISNAME = 'Platform_AppTempData'; 
	// @todo 请增加新平台 ， 在这里增加对应的平台 
	public static $platformList = [
		'ebay', 'aliexpress', 'amazon','dhgate','cdiscount','lazada','linio' , 'jumia' , 'priceminister' , 'wish' ,'bonanza','rumall','newegg','customized','shopee'
	];
	
	public static $PLATFORMLOGO = [
		'ebay'=>'/images/platform_logo/ebay.png', 
		'aliexpress'=>"/images/platform_logo/aliexpress.png", 
		'amazon'=>"/images/platform_logo/amazon.png",
		'dhgate'=>"/images/platform_logo/dhgate.png",
		'cdiscount'=>"/images/platform_logo/cdiscount.png",
		'lazada'=>"/images/platform_logo/lazada.jpg",
		'linio'=>"/images/platform_logo/linio.png" , 
		'jumia'=>"/images/platform_logo/jumia.png" , 'priceminister'=>"/images/platform_logo/priceminister.png" , 
		'wish'=>'/images/platform_logo/wish.png',
	];
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 返回各个平台下次同步订单的时间间隔， 用户绑定页面的显示
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform					平台 （必需） ebay , aliexpress ， wish  , cdiscount  ....
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=> 订单同步 情况信息
	 * 					'message'=>执行详细结果
	 * 				 	 'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getOrderSyncNextTimeInterval(){
		return array_fill_keys(self::$platformList,0.5);
	}
		
	/**
	 +------------------------------------------------------------------------------
	 * tracker 获取 ebay,速卖通,敦煌 , wish ,cdiscount,lazada 最近一段时间内进行绑定的 主账号uid 
	 +------------------------------------------------------------------------------
	 * @param int $intervalHours : 时间间隔
	 +------------------------------------------------------------------------------
	 * @return array('ebayPuids'=> array , 'aliexpressPuids'=>array , 'dhgatePuids'=>array...)  
	 *
	 **/
	public static function getLastestBindingPuidTimeMap($interval = 24){
		if(is_numeric($interval)){
			$nowTime = time();
			$startTime = $nowTime - $interval * 3600;
			$endTime = $nowTime;
			
			$startTimeStr = date('Y-m-d H:i:s',$startTime);
			$endTimeStr = date('Y-m-d H:i:s',$endTime);
		}else{
			$startTimeStr = $interval.' 00:00:00';
			$endTimeStr = $interval.' 23:59:59';
			$startTime = strtotime($startTimeStr);
			$endTime = strtotime($endTimeStr);
			if(!$startTime || !$endTime){
				$nowTime = time();
				$startTime = $nowTime - 24 * 3600;
				$endTime = $nowTime;
			}
		}

		$aliexpressUsers = SaasAliexpressUser::find()->where(['between','update_time', $startTime , $endTime])->asArray()->all();
		$ebayUsers = SaasEbayUser::find()->where(['between','update_time', $startTime , $endTime])->asArray()->all();
		$dhgateUsers = SaasDhgateUser::find()->where(['between','update_time', $startTime , $endTime])->andWhere('is_active <> 3')->asArray()->all();// 不包括解绑用户
		$wishUsers = SaasWishUser::find()->where(['between','update_time', $startTime , $endTime])->asArray()->all();
		$ensogoUsers = SaasEnsogoUser::find()->where(['between','update_time', $startTime , $endTime])->asArray()->all();
		$cdUsers = SaasCdiscountUser::find()->where(['between','update_time', $startTimeStr , $endTimeStr])->asArray()->all();
		$pmUsers = SaasPriceministerUser::find()->where(['between','update_time', $startTimeStr , $endTimeStr])->asArray()->all();
		$lazadaUsers = SaasLazadaUser::find()->where(["platform"=>"lazada"])->andWhere(['between','update_time', $startTime , $endTime])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$linioUsers = SaasLazadaUser::find()->where(["platform"=>"linio"])->andWhere(['between','update_time', $startTime , $endTime])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户		
		$jumiaUsers = SaasLazadaUser::find()->where(["platform"=>"jumia"])->andWhere(['between','update_time', $startTime , $endTime])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		$neweggUsers = SaasNeweggUser::find()->where(['between','update_time', $startTime , $endTime])->asArray()->all();
		$customizedUsers = SaasCustomizedUser::find()->where(['between','update_time', $startTime , $endTime])->asArray()->all();
		$shopeeUsers = SaasShopeeUser::find()->Where(['between','update_time', $startTime , $endTime])->andWhere('status <> 3')->asArray()->all();// 不包括解绑用户
		
		
		$aliexpressUserPuids = array();
		$ebayUserPuids = array();
		$dhgateUserPuids = array();
		$wishUserPuids = array();
		$ensogoUserPuids = array();
		$cdUserPuids = array();
		$lazadaUserPuids = array();
		$linioUserPuids = array();
		$jumiaUserPuids = array();
		$pmUserPuids = array();
		$neweggUserPuids = array();
		$customizedUserPuids = array();
		$shopeeUserPuids = array();
		
		
		if(!empty($aliexpressUsers)){
			foreach ($aliexpressUsers as $aliexpressUser){
				$puid = self::findPuid($aliexpressUser['uid']);
				$aliexpressUserPuids[$puid] = $aliexpressUser['update_time'];
			}
		}
		
		if(!empty($ebayUsers)){
			foreach ($ebayUsers as $ebayUser){
				$puid = self::findPuid($ebayUser['uid']);
				$ebayUserPuids[$puid] = $ebayUser['update_time'];
			}
		}
		
		if(!empty($dhgateUsers)){
			foreach ($dhgateUsers as $dhgateUser){
				$puid = self::findPuid($dhgateUser['uid']);
				$dhgateUserPuids[$puid] = $dhgateUser['update_time'];
			}
		}
		
		if(!empty($wishUsers)){
			foreach ($wishUsers as $wishUser){
				$puid = self::findPuid($wishUser['uid']);
				$wishUserPuids[$puid] = $wishUser['update_time'];
			}
		}
		
		if(!empty($ensogoUsers)){
			foreach ($ensogoUsers as $ensogoUser){
				$puid = self::findPuid($ensogoUser['uid']);
				$ensogoUserPuids[$puid] = $ensogoUser['update_time'];
			}
		}
		
		if(!empty($cdUsers)){
			foreach ($cdUsers as $cdUser){
				$puid = self::findPuid($cdUser['uid']);
				$cdUserPuids[$puid] = $cdUser['update_time'];
			}	
		}
		
		if(!empty($pmUsers)){
			foreach ($pmUsers as $pmUser){
				$puid = self::findPuid($pmUser['uid']);
				$pmUserPuids[$puid] = $pmUser['update_time'];
			}
		}
		
		if(!empty($lazadaUsers)){
			foreach ($lazadaUsers as $lazadaUser){
				$puid = self::findPuid($lazadaUser['puid']);
				$lazadaUserPuids[$puid] = $lazadaUser['update_time'];
			}	
		}
		
		if(!empty($linioUsers)){
			foreach ($linioUsers as $linioUser){
				$puid = self::findPuid($linioUser['puid']);
				$linioUserPuids[$puid] = $linioUser['update_time'];
			}
		}
		
		if(!empty($jumiaUsers)){
			foreach ($jumiaUsers as $jumiaUser){
				$puid = self::findPuid($jumiaUser['puid']);
				$jumiaUserPuids[$puid] = $jumiaUser['update_time'];
			}
		}
		
		if(!empty($neweggUsers)){
			foreach ($neweggUsers as $neweggUser){
				$puid = self::findPuid($neweggUser['puid']);
				$neweggUserPuids[$puid] = $neweggUser['update_time'];
			}
		}
		
		if(!empty($customizedUsers)){
			foreach ($customizedUsers as $customizedUser){
				$puid = self::findPuid($customizedUser['puid']);
				$customizedUserPuids[$puid] = $customizedUser['update_time'];
			}
		}
		
		if(!empty($shopeeUsers)){
			foreach ($shopeeUsers as $shopeeUser){
				$puid = self::findPuid($shopeeUser['puid']);
				$shopeeUserPuids[$puid] = $shopeeUser['update_time'];
			}
		}
		
		return ['aliexpressPuids'=>$aliexpressUserPuids , 'ebayPuids'=>$ebayUserPuids , 'dhgatePuids'=>$dhgateUserPuids , 
		'wishPuids'=>$wishUserPuids , 'ensogoPuids'=>$ensogoUserPuids , 'cdiscountPuids'=>$cdUserPuids , 
		'lazadaPuids'=>$lazadaUserPuids , 'linioPuids'=>$linioUserPuids , 'jumiaPuids'=>$jumiaUserPuids,'priceministerPuids'=>$pmUserPuids,
		'neweggPuids'=>$neweggUserPuids,'customizedPuids'=>$customizedUserPuids,'shopeePuids'=>$shopeeUserPuids,
		];
	}
	
	
	/**
	 +------------------------------------------------------------------------------
	 * 查找该puid在最近一段时间内 有没有绑定 ebay,速卖通,敦煌,wish,cd,lazada
	 +------------------------------------------------------------------------------
	 * @param int $puid
	 * @param int $intervalHours : 时间间隔
	 +------------------------------------------------------------------------------
	 * @return array('ebay'=> boolean , 'aliexpress'=> boolean )
	 *
	 **/
	public static function getAcountBindingInfo( $puid , $interval = 24 ){
		if(is_numeric($interval)){
			$nowTime = time();
			$startTime = $nowTime - $interval * 3600;
			$endTime = $nowTime;
		}else{
			$startTimeStr = $interval.' 00:00:00';
			$endTimeStr = $interval.' 23:59:59';
			$startTime = strtotime($startTimeStr);
			$endTime = strtotime($endTimeStr);
			if(!$startTime || !$endTime){
				$nowTime = time();
				$startTime = $nowTime - 24 * 3600;
				$endTime = $nowTime;
			}
		}
		
		if(empty($puid)){
			$puid = \Yii::$app->user->identity->getParentUid();
		}
		
		$aliexpressUsers = SaasAliexpressUser::find()->where(['uid'=>$puid])->andWhere(['between','update_time', $startTime , $endTime])->one();
		$ebayUsers = SaasEbayUser::find()->where(['uid'=>$puid])->andWhere(['between','update_time', $startTime , $endTime])->one();
		$dhgateUsers = SaasDhgateUser::find()->where(['uid'=>$puid])->andWhere(['between','update_time', $startTime , $endTime])->andWhere('is_active <> 3')->one();
		$wishUsers = SaasWishUser::find()->where(['uid'=>$puid])->andWhere(['between','update_time', $startTime , $endTime])->one();
		$ensogoUsers = SaasEnsogoUser::find()->where(['uid'=>$puid])->andWhere(['between','update_time', $startTime , $endTime])->one();
		$cdUsers = SaasCdiscountUser::find()->where(['uid'=>$puid])->andWhere(['between','update_time', $startTimeStr , $endTimeStr])->one();
		$pmUsers = SaasPriceministerUser::find()->where(['uid'=>$puid])->andWhere(['between','update_time', $startTimeStr , $endTimeStr])->one();
		$lazadaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere(['between','update_time', $startTime , $endTime])->andWhere('status <> 3')->one();// 不包括解绑账号
		$linioUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere(['between','update_time', $startTime , $endTime])->andWhere('status <> 3')->one();// 不包括解绑账号
		$jumiaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere(['between','update_time', $startTime , $endTime])->andWhere('status <> 3')->one();// 不包括解绑账号
		$neweggUsers = SaasNeweggUser::find()->where(['uid'=>$puid])->andWhere(['between','update_time', $startTime , $endTime])->one();
		$customizedUsers = SaasCustomizedUser::find()->where(['uid'=>$puid])->andWhere(['between','update_time', $startTime , $endTime])->one();
		$shopeeUsers = SaasShopeeUser::find()->where(['puid'=>$puid])->andWhere(['between','update_time', $startTime , $endTime])->andWhere('status <> 3')->one();// 不包括解绑账号
		
		
		$aliHasUpdate = !empty($aliexpressUsers) ? true : false;
		$ebayHasUpdate = !empty($ebayUsers) ? true : false;
		$dhgateHasUpdate = !empty($dhgateUsers) ? true : false;
		$wishHasUpdate = !empty($wishUsers) ? true : false;
		$ensogoHasUpdate = !empty($ensogoUsers) ? true : false;
		$cdHasUpdate = !empty($cdUsers) ? true : false;
		$pmHasUpdate = !empty($pmUsers) ? true : false;
		$lazadaHasUpdate = !empty($lazadaUsers) ? true : false;
		$linioHasUpdate = !empty($linioUsers) ? true : false;
		$jumiaHasUpdate = !empty($jumiaUsers) ? true : false;
		$neweggHasUpdate = !empty($neweggUsers) ? true : false;
		$customizedHasUpdate = !empty($customizedUsers) ? true : false;
		$shopeeHasUpdate = !empty($shopeeUsers) ? true : false;
		
		return array('ebay'=>$ebayHasUpdate, 'aliexpress'=>$aliHasUpdate , 'dhgate'=>$dhgateHasUpdate , 'wish'=>$wishHasUpdate , 'ensogo'=>$ensogoHasUpdate , 
				'cdiscount'=>$cdHasUpdate , 'priceminister'=>$pmHasUpdate, 'lazada'=>$lazadaHasUpdate , 'linio'=>$linioHasUpdate , 'jumia'=>$jumiaHasUpdate,
				'newegg'=>$neweggHasUpdate,'customized'=>$customizedHasUpdate,'shopee'=>$shopeeHasUpdate
		);
	}
	
	/**
	 +------------------------------------------------------------------------------
	 * 获取该puid所有渠道的卖家账号 ， 用于物流匹配订单selleruserid
	 +------------------------------------------------------------------------------
	 * @param int $puid
	 +------------------------------------------------------------------------------
	 * @return array(
	 *	             aliexpress=>array("cn234234"=>"cn234234","cn1111"=>"cn111") ,// "cn234234"=>"cn234234", key就是渠道的有效值   value是给用户看的
	 *	             ebay=> array("234234"=>"234234","1111"=>"111") ,
	 *				 amazon => array(), // 没有绑定该渠道的账号就返回空array
	 *	       )
	 *	
	 **/
	public static function getAllPlatformOrderSelleruseridLabelMap( $puid = false , $is_store_name = false, $is_ebay_store_name = false){
		if(empty($puid)){
		    $puid = \Yii::$app->subdb->getCurrentPuid();
// 			$puid = \Yii::$app->user->identity->getParentUid();
		}
		
		$aliexpressUsers = SaasAliexpressUser::find()->where(['uid'=>$puid])->asArray()->all();
		$wishUsers = SaasWishUser::find()->where(['uid'=>$puid])->asArray()->all();
		//$ensogoUsers = SaasEnsogoUser::find()->where(['uid'=>$puid])->asArray()->all();
		$ebayUsers = SaasEbayUser::find()->where(['uid'=>$puid])->asArray()->all();
		$amazonUsers = SaasAmazonUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')->asArray()->all();
		$dhgateUsers = SaasDhgateUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')->asArray()->all();
		$cdiscountUsers = SaasCdiscountUser::find()->where(['uid'=>$puid])->andWhere(['is_active'=>1])->asArray()->all();
		$priceministerUsers = SaasPriceministerUser::find()->where(['uid'=>$puid])->andWhere(['is_active'=>1])->asArray()->all();
		$lazadaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑账号
		$linioUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑账号
		$jumiaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑账号
		$neweggUsers = SaasNeweggUser::find()->where(['uid'=>$puid,'is_active'=>1])->asArray()->all();// 不包括解绑账号
		$customizedUsers = SaasCustomizedUser::find()->where(['uid'=>$puid,'is_active'=>1])->asArray()->all();// 不包括解绑账号
		$bonanzaUsers = SaasBonanzaUser::find()->where(['uid'=>$puid])->asArray()->all();
		$rumallUsers = SaasRumallUser::find()->where(['uid'=>$puid])->asArray()->all();
		$shopeeUsers = SaasShopeeUser::find()->where(['puid'=>$puid])->andWhere('status <> 3')->asArray()->all();// 不包括解绑账号
		
		//速卖通显示store_name
		if($is_store_name == true){
			$aliSelleruids = Helper_Array::toHashmap($aliexpressUsers , 'sellerloginid' , 'sellerloginid', 'store_name');
			
			if(count($aliexpressUsers) > 0){
				unset($aliSelleruids);
				$aliSelleruids = array();
				
				foreach ($aliexpressUsers as $aliexpressUsersVal){
					$aliSelleruids[$aliexpressUsersVal['sellerloginid']] = $aliexpressUsersVal['sellerloginid'].(empty($aliexpressUsersVal['store_name']) ? '' : '【'.$aliexpressUsersVal['store_name'].'】');
				}
			}
		}else{
			$aliSelleruids = Helper_Array::toHashmap($aliexpressUsers , 'sellerloginid' , 'sellerloginid');
		}
		
		$wishSelleruids = Helper_Array::toHashmap($wishUsers , 'store_name' , 'store_name');
		//$ensogoSelleruids = Helper_Array::toHashmap($ensogoUsers , 'store_name' , 'store_name');
		if($is_ebay_store_name == true){
			$ebaySelleruids = Helper_Array::toHashmap($ebayUsers , 'selleruserid' , 'selleruserid', 'store_name');
				
			if(count($ebayUsers) > 0){
				unset($ebaySelleruids);
				$ebaySelleruids = array();
			
				foreach ($ebayUsers as $ebayUsersVal){
					$ebaySelleruids[$ebayUsersVal['selleruserid']] = ($ebayUsersVal['store_name'] == '') ? $ebayUsersVal['selleruserid'] : $ebayUsersVal['store_name'];
				}
			}
		}else{
			$ebaySelleruids = Helper_Array::toHashmap($ebayUsers , 'selleruserid' , 'selleruserid');
		}
		$amazonSelleruids = Helper_Array::toHashmap($amazonUsers , 'merchant_id' , 'store_name');
		$dhgateSelleruids = Helper_Array::toHashmap($dhgateUsers , 'sellerloginid' , 'sellerloginid');
		$cdiscountSelleruids = Helper_Array::toHashmap($cdiscountUsers , 'username' , 'store_name');
		$priceministerSelleruids = Helper_Array::toHashmap($priceministerUsers , 'username' , 'store_name');
		$lazadaPlatformUserids = Helper_Array::toHashmap($lazadaUsers , 'platform_userid' , 'platform_userid');
		$linioPlatformUserids = Helper_Array::toHashmap($linioUsers , 'platform_userid' , 'platform_userid');
		$jumiaPlatformUserids = Helper_Array::toHashmap($jumiaUsers , 'platform_userid' , 'platform_userid');
		$neweggPlatformUserids = Helper_Array::toHashmap($neweggUsers , 'SellerID' , 'store_name');
		$customizedPlatformUserids = Helper_Array::toHashmap($customizedUsers , 'username' , 'store_name');
		$bonanzaSelleruids = Helper_Array::toHashmap($bonanzaUsers , 'store_name' , 'store_name');
		$rumallSelleruids = Helper_Array::toHashmap($rumallUsers , 'company_code' , 'store_name');
		$shopeePlatformUserids = Helper_Array::toHashmap($shopeeUsers , 'shop_id' , 'store_name');
		
		
		return array('aliexpress'=>$aliSelleruids , 'ebay'=>$ebaySelleruids , 'amazon'=>$amazonSelleruids ,'wish'=>$wishSelleruids, //'ensogo'=>$ensogoSelleruids, 
				'dhgate'=>$dhgateSelleruids, 'cdiscount'=>$cdiscountSelleruids, 'priceminister'=>$priceministerSelleruids,
				'lazada'=>$lazadaPlatformUserids, 'linio'=>$linioPlatformUserids, 'jumia'=>$jumiaPlatformUserids,
				'newegg'=>$neweggPlatformUserids,'customized'=>$customizedPlatformUserids,'bonanza'=>$bonanzaSelleruids,
				'rumall'=>$rumallSelleruids,'shopee'=>$shopeePlatformUserids,
		);
	}
	
	/*
	 * 获取uid所有已经获得权限的平台店铺arr
	 * @return	array	like ['amazon'=>[store_key1=>store_name1,store_key2=>store_name2...],'ebay'=>[store_key1=>store_name1,store_key2=>store_name2...],...]
	 * @author	lzhl	2017-03-24
	 */
	public static function getAllAuthorizePlatformOrderSelleruseridLabelMap( $puid=false ,$uid=false, $is_store_name=false, $is_ebay_store_name = false){
		if(empty($puid)){
			$puid = \Yii::$app->user->identity->getParentUid();
		}
		if(empty($uid)){
			$uid = \Yii::$app->user->id;
		}
		//所有权限店铺arr
		$authorizePlatformAccounts = UserHelper::getUserAllAuthorizePlatformAccountsArr($uid);
		
		if(empty($authorizePlatformAccounts['aliexpress']))
			$aliexpressUsers = [];
		else {
			$aliexpressUsers = SaasAliexpressUser::find()->where(['uid'=>$puid])
			->andWhere(['sellerloginid'=>array_keys($authorizePlatformAccounts['aliexpress'])])->asArray()->all();
		}
		
		if(empty($authorizePlatformAccounts['wish'])){
			$wishUsers = [];
		}else{
			$wishUsers = SaasWishUser::find()->where(['uid'=>$puid])
			->andWhere(['store_name'=>array_keys($authorizePlatformAccounts['wish'])])->asArray()->all();
		}
		
		$ensogoUsers = [];//已经弃用
		
		if(empty($authorizePlatformAccounts['ebay'])){
			$ebayUsers = [];
		}else{
			$ebayUsers = SaasEbayUser::find()->where(['uid'=>$puid])
			->andWhere(['selleruserid'=>array_keys($authorizePlatformAccounts['ebay'])])->asArray()->all();
		}
		
		if(empty($authorizePlatformAccounts['amazon'])){
			$amazonUsers = [];
		}else{
			$amazonUsers = SaasAmazonUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')
			->andWhere(['merchant_id'=>array_keys($authorizePlatformAccounts['amazon'])])->asArray()->all();
		}
		
		if(empty($authorizePlatformAccounts['dhgate'])){
			$dhgateUsers = [];
		}else{
			$dhgateUsers = SaasDhgateUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')
			->andWhere(['sellerloginid'=>array_keys($authorizePlatformAccounts['dhgate'])])->asArray()->all();
		}
		
		if(empty($authorizePlatformAccounts['cdiscount'])){
			$cdiscountUsers = [];
		}else{
			$cdiscountUsers = SaasCdiscountUser::find()->where(['uid'=>$puid])->andWhere(['is_active'=>1])
			->andWhere(['username'=>array_keys($authorizePlatformAccounts['cdiscount'])])->asArray()->all();
		}
		
		if(empty($authorizePlatformAccounts['priceminister'])){
			$priceministerUsers = [];
		}else{
			$priceministerUsers = SaasPriceministerUser::find()->where(['uid'=>$puid])->andWhere(['is_active'=>1])
			->andWhere(['username'=>array_keys($authorizePlatformAccounts['priceminister'])])->asArray()->all();
		}
		
		if(empty($authorizePlatformAccounts['lazada'])){
			$lazadaUsers = [];
		}else{
			$lazadaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')
			->andWhere(['platform_userid'=>array_keys($authorizePlatformAccounts['lazada'])])->asArray()->all();// 不包括解绑账号
		}
		
		if(empty($authorizePlatformAccounts['linio'])){
			$linioUsers = [];
		}else{
			$linioUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')
			->andWhere(['platform_userid'=>array_keys($authorizePlatformAccounts['linio'])])->asArray()->all();// 不包括解绑账号
		}
		
		if(empty($authorizePlatformAccounts['jumia'])){
			$jumiaUsers = [];
		}else{
			$jumiaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')
			->andWhere(['platform_userid'=>array_keys($authorizePlatformAccounts['jumia'])])->asArray()->all();// 不包括解绑账号
		}
		
		if(empty($authorizePlatformAccounts['newegg'])){
			$neweggUsers = [];
		}else{
			$neweggUsers = SaasNeweggUser::find()->where(['uid'=>$puid,'is_active'=>1])
			->andWhere(['SellerID'=>array_keys($authorizePlatformAccounts['newegg'])])->asArray()->all();// 不包括解绑账号
		}
		
		if(empty($authorizePlatformAccounts['customized'])){
			$customizedUsers = [];
		}else{
			$customizedUsers = SaasCustomizedUser::find()->where(['uid'=>$puid,'is_active'=>1])
			->andWhere(['username'=>array_keys($authorizePlatformAccounts['customized'])])->asArray()->all();// 不包括解绑账号
		}
		
		if(empty($authorizePlatformAccounts['bonanza'])){
			$bonanzaUsers = [];
		}else{
			$bonanzaUsers = SaasBonanzaUser::find()->where(['uid'=>$puid,'is_active'=>1])
			->andWhere(['store_name'=>array_keys($authorizePlatformAccounts['bonanza'])])->asArray()->all();// 不包括解绑账号
		}
		
		if(empty($authorizePlatformAccounts['rumall'])){
			$rumallUsers = [];
		}else{
			$rumallUsers = SaasRumallUser::find()->where(['uid'=>$puid,'is_active'=>1])
			->andWhere(['company_code'=>array_keys($authorizePlatformAccounts['rumall'])])->asArray()->all();// 不包括解绑账号
		}
		if(empty($authorizePlatformAccounts['shopee'])){
			$shopeeUsers = [];
		}else{
			$shopeeUsers = SaasShopeeUser::find()->where(['puid'=>$puid])->andWhere('status <> 3')
			->andWhere(['shop_id'=>array_keys($authorizePlatformAccounts['shopee'])])->asArray()->all();// 不包括解绑账号
		}
		
		//速卖通显示store_name
		if($is_store_name == true){
			$aliSelleruids = Helper_Array::toHashmap($aliexpressUsers , 'sellerloginid' , 'sellerloginid', 'store_name');
				
			if(count($aliexpressUsers) > 0){
				unset($aliSelleruids);
				$aliSelleruids = array();
	
				foreach ($aliexpressUsers as $aliexpressUsersVal){
					$aliSelleruids[$aliexpressUsersVal['sellerloginid']] = $aliexpressUsersVal['sellerloginid'].(empty($aliexpressUsersVal['store_name']) ? '' : '【'.$aliexpressUsersVal['store_name'].'】');
				}
			}
		}else{
			$aliSelleruids = Helper_Array::toHashmap($aliexpressUsers , 'sellerloginid' , 'sellerloginid');
		}
		
		$wishSelleruids = Helper_Array::toHashmap($wishUsers , 'store_name' , 'store_name');
		$ensogoSelleruids = Helper_Array::toHashmap($ensogoUsers , 'store_name' , 'store_name');
		
		//ebay显示store_name
		if($is_ebay_store_name == true){
			if(count($ebayUsers) > 0){
				unset($ebaySelleruids);
				$ebaySelleruids = array();
			
				foreach ($ebayUsers as $ebayUsersVal){
					$ebaySelleruids[$ebayUsersVal['selleruserid']] = ($ebayUsersVal['store_name'] == '') ? $ebayUsersVal['selleruserid'] : $ebayUsersVal['store_name'];
				}
			}
		}else{
			$ebaySelleruids = Helper_Array::toHashmap($ebayUsers , 'selleruserid' , 'selleruserid');
		}
		
		if(!isset($ebaySelleruids)){
			$ebaySelleruids = array();
		}
		
		$amazonSelleruids = Helper_Array::toHashmap($amazonUsers , 'merchant_id' , 'store_name');
		$dhgateSelleruids = Helper_Array::toHashmap($dhgateUsers , 'sellerloginid' , 'sellerloginid');
		$cdiscountSelleruids = Helper_Array::toHashmap($cdiscountUsers , 'username' , 'store_name');
		$priceministerSelleruids = Helper_Array::toHashmap($priceministerUsers , 'username' , 'store_name');
		$lazadaPlatformUserids = Helper_Array::toHashmap($lazadaUsers , 'platform_userid' , 'platform_userid');
		$linioPlatformUserids = Helper_Array::toHashmap($linioUsers , 'platform_userid' , 'platform_userid');
		$jumiaPlatformUserids = Helper_Array::toHashmap($jumiaUsers , 'platform_userid' , 'platform_userid');
		$neweggPlatformUserids = Helper_Array::toHashmap($neweggUsers , 'SellerID' , 'store_name');
		$customizedPlatformUserids = Helper_Array::toHashmap($customizedUsers , 'username' , 'store_name');
		$bonanzaPlatformUserids = Helper_Array::toHashmap($bonanzaUsers , 'store_name' , 'store_name');
		$rumallPlatformUserids = Helper_Array::toHashmap($rumallUsers , 'company_code' , 'store_name');
		$shopeePlatformUserids = Helper_Array::toHashmap($shopeeUsers , 'shop_id' , 'store_name');
		
		$all_platforms = array('aliexpress'=>$aliSelleruids , 'ebay'=>$ebaySelleruids , 'amazon'=>$amazonSelleruids ,'wish'=>$wishSelleruids, //'ensogo'=>$ensogoSelleruids,
				'dhgate'=>$dhgateSelleruids, 'cdiscount'=>$cdiscountSelleruids, 'priceminister'=>$priceministerSelleruids,
				'lazada'=>$lazadaPlatformUserids, 'linio'=>$linioPlatformUserids, 'jumia'=>$jumiaPlatformUserids, 
				'newegg'=>$neweggPlatformUserids,'customized'=>$customizedPlatformUserids,'bonanza'=>$bonanzaPlatformUserids,
				'rumall'=>$rumallPlatformUserids,'shopee'=>$shopeePlatformUserids,
		);
		
		return $all_platforms;
	}
	
	/**
	 +------------------------------------------------------------------------------
	 * 获取所有渠道 的订单站点，用作物流匹配订单order_source_site_id。 
	 +------------------------------------------------------------------------------
	 * @param int $puid
	 +------------------------------------------------------------------------------
	 * @return array(
	 *	   ebay=>array("UK","US"...), 
	 *	   aliexpress=>"global" //  "global"的渠道即该渠道订单获取不到order_source_site_id，物流匹配会做特殊处理。
	 *	  .....
	 *	)
	 *
	 **/
	public static function getAllPlatformOrderSite(){
		$ebaySites = EbaySite::find()->orderBy('siteid')->select(['site'])->asArray()->all();
		/* $matchOdEbaySites = array();
		foreach ($ebaySites as $eSite){
			$matchOdEbaySites[] = $eSite['site'];
		} */
		$matchOdEbaySites = Helper_Array::toHashmap($ebaySites,'site','site');
		
		/* $matchOdAmazonSites = array();
		foreach (SaasAmazonAutoSyncApiHelper::$AMAZON_MARKETPLACE_REGION_CONFIG as $amazonSite){
			$matchOdAmazonSites[] = $amazonSite;
		} */
		$matchOdAmazonSites=SaasAmazonAutoSyncApiHelper::$COUNTRYCODE_NAME_MAP;
		$matchOdLazadaSites=LazadaApiHelper::$COUNTRYCODE_NAME_MAP_CARRIER;
		$matchOdLinioSites = array();
		foreach(LazadaApiHelper::$LINIO_COUNTRYCODE_NAME_MAP as $code=>$name){
			$matchOdLinioSites[strtoupper($code)] = $name;
		}
		
		$matchOdJumiaSites = array();
		foreach(LazadaApiHelper::$JUMIA_COUNTRYCODE_NAME_MAP as $code=>$name){
			$matchOdJumiaSites[strtoupper($code)] = $name;
		}
		$matchOdShopeeSites = ShopeeAccountsApiHelper::getCountryCodeSiteMapping();
		return array(
// 				'aliexpress'=>array('global'=>'全部') ,
				//'wish'=>'global' , 
				'ebay'=>$matchOdEbaySites , 
				'amazon'=>$matchOdAmazonSites ,
				//'dhgate'=>'global'
				'cdiscount'=>array("FR"=>"法国"),//cdiscount目前只有法国网站
				'priceminister'=>array("FR"=>"法国"),//cdiscount目前只有法国网站
				'lazada'=>$matchOdLazadaSites,
				'linio'=>$matchOdLinioSites,
				'jumia'=>$matchOdJumiaSites,
				'shopee'=>$matchOdShopeeSites,
		);
	}
	/**
	 +------------------------------------------------------------------------------
	 * 获取站点对应的买家可选物流服务用作物流匹配订单order_source_shipping_method。
	 +------------------------------------------------------------------------------
	 * return array(
	 *  'ebay'=>array(
	 *  	'US'=array(
	 *  		 'eBayNowImmediateDelivery' => string 'eBay Now Immediate Delivery',
	 *  		 'eBayNowNextDayDelivery' => string 'eBay Now Next Day Scheduled Delivery'
	 *  	)
	 *  	'Canada'=array(
	 *  		 'CanadaPostExpeditedFlatRateBox' => string 'Canada Post Expedited Flat Rate Box',
     *     		 'CanadaPostExpeditedFlatRateBoxUSA' => string 'Canada Post Expedited Flat Rate Box USA'
	 *  	)
	 *  )
	 * )
	 **/
	public static function getAllPlatformShippingServices(){
		$ebaySites = EbaySite::find()->orderBy('siteid')->select(['siteid','site'])->asArray()->all();
		$eabyServices=[];
		foreach ($ebaySites as $one){
			$eabyServices[$one['site']]=Helper_Array::toHashmap(EbayShippingservice::find()->where(['siteid'=>$one['siteid']])->orderBy('description')->select(['shippingservice','description'])->asArray()->all(),'shippingservice','description');
		
		}
		
		$cdiscountServices=CarrierHelper::getCdiscountBuyerShippingServices();
		$priceministerServices=CarrierHelper::getPriceministerBuyerShippingServices();
		
		$amzServices = [];
		$amazonServices = \eagle\modules\amazon\apihelpers\SaasAmazonAutoSyncApiHelper::$BuyerShippingServices;
// 		foreach (SaasAmazonAutoSyncApiHelper::$COUNTRYCODE_NAME_MAP as $siteKey=>$siteName){
// 			$amazonServices[$siteKey] = SaasAmazonAutoSyncApiHelper::$BuyerShippingServices;
// 		}
		
		$aliexpressServicesList = "139 ECONOMIC Package:139 ECONOMIC Package (139俄罗斯专线);4PX RM:4PX RM (递四方专线小包);4PX Singapore Post OM Pro:4PX Singapore Post OM Pro (4PX新邮经济小包);aCommerce:aCommerce (印尼本地物流);AliExpress PickUp Service:AliExpress PickUp Service;AliExpress Premium Shipping:AliExpress Premium Shipping (AliExpress 无忧物流-优先);AliExpress Standard Shipping:AliExpress Standard Shipping (AliExpress 无忧物流-标准);AliExpress Saver Shipping:AliExpress Saver Shipping;Aramex:Aramex (中东专线);Asendia:Asendia (Asendia);AUSPOST:AUSPOST (澳大利亚邮政);Austrian Post:Austrian Post (奥地利邮政);Bpost International:Bpost International (比利时邮政);Canada Post:Canada Post (加拿大邮政);CDEK:CDEK (CDEK俄罗斯专线);CDEK_RU:CDEK_RU (CDEK);China Post Air Parcel:China Post Air Parcel (中国邮政大包);China Post Ordinary Small Packet Plus:China Post Ordinary Small Packet Plus (中国邮政平常小包+);China Post Registered Air Mail:China Post Registered Air Mail (中国邮政挂号小包);Chukou1:Chukou1 (出口易);CLPOST:CLPOST;CNE Express:CNE Express (CNE);Correos:Correos (西班牙邮政);Correos Economy:Correos Economy (中外运-西邮经济小包);CORREOS PAQ 72:CORREOS PAQ 72 (中外运-西邮标准小包);Correos Seguimiento a Buzon:Correos Seguimiento a Buzon;Deutsche Post:Deutsche Post (德国邮政);DHL:DHL;DHL e-commerce:DHL e-commerce (DHL e-commerce);DHL Global Mail:DHL Global Mail;DHL_DE:DHL_DE (DHL-德国);DHL_ES:DHL_ES (DHL-西班牙);DHL_FR:DHL_FR (DHL-法国);DHL_IT:DHL_IT (DHL-意大利);DHL_UK:DHL_UK (DHL-英国);DPD:DPD (DPD);DPEX:DPEX;e-EMS:e-EMS (E特快);EMS:EMS;Enterprise des Poste Lao:Enterprise des Poste Lao (老挝邮政);Entrega Local:Correos PAQ72 (西班牙配送);Envialia:Envialia (西班牙本地物流);ePacket:ePacket (e邮宝);Equick:Equick (Equick);Euro-business Parcel:Euro-business Parcel;Fedex IE:Fedex IE;Fedex IP:Fedex IP;Flyt Express:Flyt Express (飞特物流);France Express:France Express;GATI:GATI (GATI);GLS:GLS (GLS);HongKong Post Air Mail:HongKong Post Air Mail (香港邮政挂号小包);HongKong Post Air Parcel:HongKong Post Air Parcel (香港邮政大包);IML Express:IML Express (IML);J-NET:J-NET (J-NET捷网);JNE:JNE (印尼本地物流);LAPOSTE:LAPOSTE (法国邮政);Magyar Post:Magyar Post (匈牙利邮政);Meest:Meest (Meest专线);Miuson Europe:Miuson Europe (淼信欧洲专线);Mongol Post:Mongol Post (蒙古邮政);New Zealand Post:New Zealand Post (新西兰邮政);Omniva:Omniva (爱沙尼亚邮政);OMNIVA Economic Air Mail:OMNIVA Economic Air Mail (爱沙尼亚邮政经济小包);One World Express:One World Express (万欧国际);PONY:PONY (PONY俄罗斯专线);PONY_RU:PONY_RU (PONY);POS Malaysia:POS Malaysia (马来西亚邮政挂号小包);Posteitaliane:Posteitaliane (意大利邮政);Posti Finland:Posti Finland (芬兰邮政挂号小包);Posti Finland Economy:Posti Finland Economy (速优宝芬邮经济小包);PostNL:PostNL (荷兰邮政挂号小包);RETS-EXPRESS:RETS-EXPRESS (俄通收中俄专线);Royal Mail:Royal Mail (英国邮政);Royal Mail Economy:Royal Mail Economy (中外运-英邮经济小包);Russia Express-SPSR:Russia Express-SPSR (中俄快递-SPSR);Russia Parcel Online:Russia Parcel Online (港俄航空专线);Russian Air:Russian Air (中俄航空 Ruston);Russian Post:Russian Post (俄罗斯邮政);Ruston Economic Air Mail:Ruston Economic Air Mail (中俄航空经济小包);Seller's Shipping Method:Seller's Shipping Method (卖家自定义-中国);Seller's Shipping Method - AU:Seller's Shipping Method - AU (卖家自定义-澳大利亚);Seller's Shipping Method - DE:Seller's Shipping Method - DE (卖家自定义-德国);Seller's Shipping Method - ES:Seller's Shipping Method - ES (卖家自定义-西班牙);Seller's Shipping Method - FR:Seller's Shipping Method - FR (卖家自定义-法国);Seller's Shipping Method - IT:Seller's Shipping Method - IT (卖家自定义-意大利);Seller's Shipping Method - RU:Seller's Shipping Method - RU (卖家自定义-俄罗斯);Seller's Shipping Method - UK:Seller's Shipping Method - UK (卖家自定义-英国);Seller's Shipping Method - US:Seller's Shipping Method - US (卖家自定义-美国);SF Economic Air Mail:SF Economic Air Mail (顺丰国际经济小包);SF eParcel:SF eParcel (顺丰国际挂号小包);SF Express:SF Express (顺丰速运);SFCService:SFCService (三态物流);Singapore Post:Singapore Post (新加坡邮政挂号小包);Special Line-YW:Special Line-YW (燕文航空挂号小包);Speedpost:Speedpost (新加坡邮政速递);SPSR_RU:SPSR_RU (SPSR-俄罗斯);SunYou:SunYou (顺友);SunYou Economic Air Mail:SunYou Economic Air Mail (顺友航空经济小包);Sweden Post:Sweden Post (瑞典邮政挂号小包);Swiss Post:Swiss Post (瑞士邮政挂号小包);TaiwanPost:TaiwanPost (台湾邮政);TEA-POST:TEA-POST (TEA俄罗斯专线);Thailand Post:Thailand Post (泰国邮政);TNT:TNT;Turkey Post:Turkey Post (土耳其邮政挂号小包);UBI:UBI (UBI);Ukrposhta:Ukrposhta (乌克兰邮政);UPS:UPS;UPS Expedited:UPS Expedited (UPS全球快捷);UPS Express Saver:UPS Express Saver (UPS全球速快);USPS:USPS (美国邮政);VietNam Post:VietNam Post (越南邮政);Yanwen Economic Air Mail:Yanwen Economic Air Mail (燕文航空经济小包);YODEL:YODEL (YODEL);YunExpress:YunExpress (云途);Cainiao Super Economy:Cainiao Super Economy (菜鸟)";
		
		$params = explode(';',rtrim($aliexpressServicesList,';'));
		Helper_Array::removeEmpty($params);
		$aliexpressServices = array();
		foreach($params as $v){
			$value = explode(':',$v);
			if(count($value)<2)return false;
			$aliexpressServices[$value[0]] = $value[1];
		}
		
		$neweggServicesList = NeweggApiHelper::getNeweggOrderCustomerShippingServiceCode();
		
		$linio_services = LazadaApiHelper::getBuyerShippingServices('linio');
		$jumia_services = LazadaApiHelper::getBuyerShippingServices('jumia');
		
		return array(
				'ebay'=>$eabyServices,
				'cdiscount'=>$cdiscountServices,
				'priceminister'=>$priceministerServices,
				'amazon'=>$amazonServices,
				'aliexpress'=>$aliexpressServices,
				'newegg'=>$neweggServicesList,
				'linio'=>$linio_services,
				'jumia'=>$jumia_services,
		);
	}
	
	/*
	 * 获取所有平台，  有哪些puid绑定了，解绑了的不算
	 * @param	$platforms array()	无传入指定平台时，为查询全部
	 * @return	array	like ['ebay'=>[297,123,...], 'amazon'=>[297,2123,...], 'cdiscount'=>[], ...]
	 * @author	lzhl	2016/10/12	初始化
	 */
	public static function getAllBindedPlatformPuidsArr($platforms=[]){
		//无传入指定平台时，为查询全部
		if(empty($platforms))
			$platformsArr = OdOrder::$orderSource;
		else{
			if(is_string($platforms))
				$platformsArr = [$platforms];
			elseif(is_array($platforms))
				$platformsArr = $platforms;
			else
				$platformsArr = OdOrder::$orderSource;
		}
		
		$result = [];
		$tmp_result = [];
		foreach($platformsArr as $platform){
			switch (strtolower($platform)){
				case 'ebay':
					$ebayPuids = SaasEbayUser::find()->select('uid')->distinct(true)->where(1)->asArray()->all();//无是否启用字段？
					$tmp_result [$platform] = $ebayPuids;
					break;
				case 'aliexpress':
					$aliexpressPuids = SaasAliexpressUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->asArray()->all();
					$tmp_result [$platform] = $aliexpressPuids;
					break;
				case 'wish':
					$wishPuids = SaasWishUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->asArray()->all();
					$tmp_result [$platform] = $wishPuids;
					break;
				case 'ensogo':
					$ensogoPuids  = SaasEnsogoUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->asArray()->all();
					$tmp_result [$platform] = $ensogoPuids;
					break;
				case 'amazon':
					$amazonPuids  = SaasAmazonUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->andWhere('is_active <> 3')->asArray()->all();
					$tmp_result [$platform] = $amazonPuids;
					break;
				case 'dhgate':
					$dhgatePuids  = SaasDhgateUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->andWhere('is_active <> 3')->asArray()->all();
					$tmp_result [$platform] = $dhgatePuids;
					break;
				case 'lazada':
					$lazadaPuids  = SaasLazadaUser::find()->select('puid as uid')->distinct(true)->where(["platform"=>"lazada",'status'=>1])->asArray()->all();
					$tmp_result [$platform] = $lazadaPuids;
					break;
				case 'linio':
					$linioPuids  = SaasLazadaUser::find()->select('puid as uid')->distinct(true)->where(["platform"=>"linio",'status'=>1])->asArray()->all();
					$tmp_result [$platform] = $linioPuids;
					break;
				case 'jumia':
					$jumiaPuids  = SaasLazadaUser::find()->select('puid as uid')->distinct(true)->where(["platform"=>"jumia",'status'=>1])->asArray()->all();
					$tmp_result [$platform] = $jumiaPuids;
					break;
				case 'cdiscount':
					$cdiscountPuids  = SaasCdiscountUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->asArray()->all();
					$tmp_result [$platform] = $cdiscountPuids;
					break;
				case 'priceminister':
					$priceministerPuids  = SaasPriceministerUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->asArray()->all();
					$tmp_result [$platform] = $priceministerPuids;
					break;
				case 'bonanza':
					$bonanzaPuids  = SaasBonanzaUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->asArray()->all();
					$tmp_result [$platform] = $bonanzaPuids;
					break;
				case 'rumall':
					$rumallPuids  = SaasRumallUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->asArray()->all();
					$tmp_result [$platform] = $rumallPuids;
					break;
				case 'newegg':
					$neweggPuids  = SaasNeweggUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->asArray()->all();
					$tmp_result [$platform] = $neweggPuids;
					break;
				case 'customized':
					$customizedPuids  = SaasCustomizedUser::find()->select('uid')->distinct(true)->where(['is_active'=>1])->asArray()->all();
					$tmp_result [$platform] = $customizedPuids;
					break;
				case 'shopee':
					$shopeePuids  = SaasShopeeUser::find()->select('puid as uid')->distinct(true)->where(['status'=>1])->asArray()->all();
					$tmp_result [$platform] = $shopeePuids;
					break;
			}
		}
		
 		foreach($tmp_result as $platform=>$puids){
 			$result[$platform] = [];
 			if( empty($puids) )
 				continue;
 			foreach($puids as $puid_row){
 				$result[$platform][] = $puid_row['uid'];
 			}
 		}
		
		return $result;
	}
	
	/**
	 +------------------------------------------------------------------------------
	 * 获取所有平台的绑定情况，是否有账号绑定
	 +------------------------------------------------------------------------------
	 * @param int platformsArr = array()
	 * 默认获取所有平台platformsArr = array()
	 * 指定某个平台 platformsArr = array("aliexpress")
	 * 
	 * 
	 +------------------------------------------------------------------------------
	 * return array(
	 *    'ebay'=> true,
	 *    'amazon'=> false,
	 *    ...
	 * )
	 **/
	public static function getAllPlatformBindingSituation($platformsArr = array(),$puid=0){
		if (empty($puid)){
			$puid = \Yii::$app->user->identity->getParentUid();
		}
		if (count($platformsArr)===1){
			if ($platformsArr[0]=="aliexpress"){
				$aliexpressUsers = SaasAliexpressUser::find()->where(['uid'=>$puid])->count();
				return array(						
						'aliexpress'=>$aliexpressUsers>0?true:false
			            );
			}else if ($platformsArr[0]=="ensogo"){
				$ensogoUsers = SaasEnsogoUser::find()->where(['uid'=>$puid])->count();
				return array(
						'ensogo'=>$ensogoUsers>0?true:false
				);
			}				
			
			
			
			
		}
	
		if (!empty($platformsArr)){
			$result = [];
			foreach($platformsArr as $platform){
				switch (strtolower($platform)){
					case 'ebay':
						$ebayUsers = SaasEbayUser::find()->where(['uid'=>$puid])->count();
						$result [$platform] =$ebayUsers>0?true:false;
						break;
					case 'aliexpress':
						$aliexpressUsers = SaasAliexpressUser::find()->where(['uid'=>$puid])->count();
						$result [$platform] =$aliexpressUsers>0?true:false;
						break;
					case 'wish':
						$wishUsers = SaasWishUser::find()->where(['uid'=>$puid])->count();
						$result [$platform] =$wishUsers>0?true:false;
						break;
					case 'ensogo':
						$ensogoUsers = SaasEnsogoUser::find()->where(['uid'=>$puid])->count();
						$result [$platform] =$ensogoUsers>0?true:false;
						break;
							
					case 'amazon':
						$amazonUsers = SaasAmazonUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')->count();
						$result [$platform] =$amazonUsers>0?true:false;
						break;
							
					case 'dhgate':
						$dhgateUsers = SaasDhgateUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')->count();
						$result [$platform] =$dhgateUsers>0?true:false;
						break;
			
					case 'lazada':
						$lazadaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->count();// 不包括解绑账号
						$result [$platform] =$lazadaUsers>0?true:false;
						break;
					case 'linio':
						$linioUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->count();// 不包括解绑账号
						$result [$platform] =$linioUsers>0?true:false;
						break;
					case 'jumia':
						$jumiaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->count();// 不包括解绑账号
						$result [$platform] =$jumiaUsers>0?true:false;
						break;
					case 'cdiscount':
						$cdiscountUsers = SaasCdiscountUser::find()->where(['uid'=>$puid])->count();
						$result [$platform] =$cdiscountUsers>0?true:false;
						break;
					case 'priceminister':
						$priceministerUsers = SaasPriceministerUser::find()->where(['uid'=>$puid])->count();
						$result [$platform] =$priceministerUsers>0?true:false;
						break;				
					case 'bonanza':
					    $bonanzaUsers = SaasBonanzaUser::find()->where(['uid'=>$puid])->count();
					    $result [$platform] =$bonanzaUsers>0?true:false;
					    break;
				    case 'rumall':
				        $rumallUsers = SaasRumallUser::find()->where(['uid'=>$puid])->count();
				        $result [$platform] =$rumallUsers>0?true:false;
				        break;
					case 'newegg':
					    $neweggUsers = SaasNeweggUser::find()->where(['uid'=>$puid])->count();
					    $result [$platform] =$neweggUsers>0?true:false;
					    break;
					case 'customized':
						$customizedUsers = SaasCustomizedUser::find()->where(['uid'=>$puid])->count();
					    $result [$platform] =$customizedUsers>0?true:false;
					    break;
				    case 'shopee':
				    	$shopeeUsers = SaasShopeeUser::find()->where(['puid'=>$puid])->andWhere('status <> 3')->count();// 不包括解绑账号
				    	$result [$platform] =$shopeeUsers>0?true:false;
				    	break;
				}
			}
			
			return $result;
		}else{
			$aliexpressUsers = SaasAliexpressUser::find()->where(['uid'=>$puid])->count();
			$wishUsers = SaasWishUser::find()->where(['uid'=>$puid])->count();
			$ensogoUsers = SaasEnsogoUser::find()->where(['uid'=>$puid])->count();
			$ebayUsers = SaasEbayUser::find()->where(['uid'=>$puid])->count();
			$amazonUsers = SaasAmazonUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')->count();
			$dhgateUsers = SaasDhgateUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')->count();
			$lazadaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->count();// 不包括解绑账号
			$linioUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->count();// 不包括解绑账号
			$jumiaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->count();// 不包括解绑账号
			$cdiscountUsers = SaasCdiscountUser::find()->where(['uid'=>$puid])->count();
			$priceministerUsers = SaasPriceministerUser::find()->where(['uid'=>$puid])->count();
			$bonanzaUsers = SaasBonanzaUser::find()->where(['uid'=>$puid])->count();
			$rumallUsers = SaasRumallUser::find()->where(['uid'=>$puid])->count();
			$neweggUsers = SaasNeweggUser::find()->where(['uid'=>$puid])->count();
			$customizedUsers = SaasCustomizedUser::find()->where(['uid'=>$puid])->count();
			$shopeeUsers = SaasShopeeUser::find()->where(['puid'=>$puid])->andWhere('status <> 3')->count();// 不包括解绑账号
			
			return array(
					'ebay'=>$ebayUsers>0?true:false,
					'aliexpress'=>$aliexpressUsers>0?true:false,
					'wish'=>$wishUsers>0?true:false,
// 					'ensogo'=>$ensogoUsers>0?true:false,
					'amazon'=>$amazonUsers>0?true:false,
					'dhgate'=>$dhgateUsers>0?true:false,
					'lazada'=>$lazadaUsers>0?true:false,
					'cdiscount'=>$cdiscountUsers>0?true:false,
					'priceminister'=>$priceministerUsers>0?true:false,
					'linio'=>$linioUsers>0?true:false,
					'jumia'=>$jumiaUsers>0?true:false,
			        'bonanza'=>$bonanzaUsers>0?true:false,
			        'rumall'=>$rumallUsers>0?true:false,
					'newegg'=>$neweggUsers>0?true:false,
					'customized'=>$customizedUsers>0?true:false,
					'shopee'=>$shopeeUsers>0?true:false,
			);
		}
	}
	
	public static function checkBindingAccountsExistOrNot($puid = false){
		$result = self::getAllPlatformBindingSituation();
		
		foreach($result as $row){
			if ($row) return $row;
		}
		return false;
	}//end of checkAccountsExistOrNot
	
	// 根据uid 查找puid
	private static function findPuid($uid){
		$userBase = UserBase::findOne($uid);
		if($userBase->puid != 0){
			return $userBase->puid;
		}else {
			return $uid;
		}
	}
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取指定平台， 指定账号， 订单同步 情况信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform					平台 （必需） ebay , aliexpress ， wish  , cdiscount  ....
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=> 订单同步 情况信息
	 * 					'message'=>执行详细结果
	 * 				 	 'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getOrderSyncInfo($platform , $account_key , $uid=0 ){
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
	
		switch (strtolower($platform)){
			case 'aliexpress':
				return AliexpressAccountsApiHelper::getLastOrderSyncDetail($account_key,$uid);
				break;
			case 'cdiscount':
				return CdiscountAccountsApiHelper::getCdiscountOrderSyncInfo($account_key, $uid);
				break;
			case 'wish':
				return WishAccountsApiHelper::getWishOrderSyncInfo($account_key, $uid);
				break;
			case 'ebay':
				return EbayAccountsApiHelper::getLastOrderSyncDetail($account_key,$uid);
				break;
				
			case 'dhgate':
				return DhgateApiHelper::getLastOrderSyncDetail($account_key,$uid);
			case 'amazon':
				return AmazonApiHelper::getLastOrderSyncDetail($account_key,$uid);
			case 'lazada':
				return LazadaApiHelper::getLastOrderSyncDetail($account_key,$uid,'lazada');
			case 'linio':
				return LazadaApiHelper::getLastOrderSyncDetail($account_key,$uid,'linio');
			case 'jumia':
				return LazadaApiHelper::getLastOrderSyncDetail($account_key,$uid,'jumia');
			case 'priceminister':
				return PriceministerAccountsApiHelper::getPriceministerOrderSyncInfo($account_key,$uid);	
			case 'bonanza':
				return BonanzaAccountsApiHelper::getBonanzaOrderSyncInfo($account_key,$uid);
				break;
			case 'rumall':
			    return RumallAccountsApiHelper::getRumallOrderSyncInfo($account_key,$uid);
			    break;
			case 'newegg':
				return NeweggAccountsApiHelper::getNeweggOrderSyncInfo($account_key,$uid);
				break;
			case 'shopee':
				return ShopeeAccountsApiHelper::getLastOrderSyncDetail($account_key,$uid);
			default :
				return ['success'=>false , 'message'=>'平台无效' , 'result'=>[]];
	
		}
	}//end of getOrderSyncInfo
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重置同步 订单次数
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform					平台 （必需） ebay , aliexpress ， wish  , cdiscount  ....
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=> 订单同步 情况信息
	 * 					'message'=>执行详细结果
	 * 				 	 'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/25				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function resetSyncOrderInfo($platform , $account_key , $uid=0 ){
		//get active uid
		if (empty($uid)){
			$userInfo = \Yii::$app->user->identity;
			if ($userInfo['puid']==0){
				$uid = $userInfo['uid'];
			}else {
				$uid = $userInfo['puid'];
			}
		}
		
		switch (strtolower($platform)){
			case 'aliexpress':
				return AliexpressAccountsApiHelper::resetSyncOrderInfo($account_key,$uid);
				break;
			case 'cdiscount':
				return CdiscountAccountsApiHelper::getCdiscountOrderSyncInfo($account_key, $uid);
				break;
			case 'priceminister':
				return PriceministerAccountsApiHelper::getPriceministerOrderSyncInfo($account_key, $uid);
				break;
			case 'wish':
				return WishAccountsApiHelper::getWishOrderSyncInfo($account_key, $uid);
				break;
			case 'ebay':
				return EbayAccountsApiHelper::getLastOrderSyncDetail($account_key,$uid);
				break;
		
			case 'dhgate':
				return DhgateApiHelper::getLastOrderSyncDetail($account_key,$uid);
			case 'amazon':
				return AmazonApiHelper::getLastOrderSyncDetail($account_key,$uid);
			case 'lazada':
				return LazadaApiHelper::getLastOrderSyncDetail($account_key,$uid);
			case 'linio':
				return LazadaApiHelper::getLastOrderSyncDetail($account_key,$uid);
			case 'jumia':
				return LazadaApiHelper::getLastOrderSyncDetail($account_key,$uid);
		
				break;
			case 'shopee':
				return ShopeeAccountsApiHelper::getLastOrderSyncDetail($account_key,$uid);
			default :
				return ['success'=>false , 'message'=>'平台无效' , 'result'=>[]];
		
		}
	}
	 
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取指定平台， 指定账号， 商品同步 情况信息
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform					平台 （必需） ebay , aliexpress ， wish  , cdiscount  ....
	 * @param $account_key 				各个平台账号的关键值 （必需） 就是用获取对应账号信息的
	 * @param $uid			 			uid use_base 的id
	 +---------------------------------------------------------------------------------------------
	 * @return array (  'result'=> 订单同步 情况信息
	 * 					'message'=>执行详细结果
	 * 				 	 'success'=> true 成功 false 失败	)
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/11/26				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getProductSyncInfo($platform , $account_key , $uid=0 ){
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
	
		switch (strtolower($platform)){
			case 'aliexpress':
				return AliexpressOrderHelper::getLastProductSyncDetail($account_key,$uid);
				break;
			case 'cdiscount':
				return CdiscountAccountsApiHelper::getCdiscountProductSyncInfo($account_key, $uid);
				break;
			case 'priceminister':
				return PriceministerAccountsApiHelper::getPriceministerProductSyncInfo($account_key, $uid);
				break;
			case 'wish':
				return WishAccountsApiHelper::getWishProductSyncInfo($account_key, $uid);
				break;
			case 'ebay':
				return EbayAccountsApiHelper::getLastProductSyncDetail($account_key,$uid);
				
			case 'lazada':
				return LazadaApiHelper::getLastProductSyncDetailV2($account_key,$uid);
			case 'linio':
				return LazadaApiHelper::getLastProductSyncDetail($account_key,$uid);
			case 'jumia':
				return LazadaApiHelper::getLastProductSyncDetail($account_key,$uid);
			case 'shopee':
				return ShopeeAccountsApiHelper::getLastProductSyncDetail($account_key,$uid);
			default :
				return ['success'=>false , 'message'=>'平台无效' , 'result'=>[]];
	
		}
	}//end of getProductSyncInfo
	
	
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
	static public function getMessageSyncInfo($platform ,$account_key , $uid=0){
		return SaasMessageAutoSyncApiHelper::getLastMessageSyncDetail($platform, $account_key,$uid);
	}//end of getMessageSyncDetail
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 保存 平台后台用到 的redis 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $type 						同步订单sync_order， 同步站内信sync_message , 同步商品sync_product 
	 * @param $platform						对应 的平台  aliexpress , ebay , wish 
	 * @param $accountID 					账号的ID
	 * @param $isActive			 			是否开启
	 +---------------------------------------------------------------------------------------------
	 * @return  
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setPlatformAccountSyncControlData($type ,$platform ,  $accountID ,$isActive){
		$classification = self::$PLATFORMAPPREDISNAME;
		return RedisHelper::RedisSet($classification,$type.".".$platform.'.'.$accountID,$isActive);
		//return \Yii::$app->redis->hset($classification,$type.".".$platform.'.'.$accountID,$isActive);
	}//end of setPlatformAccountSyncControlData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 读取 平台后台用到 的redis 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $type 						同步订单sync_order， 同步站内信sync_message , 同步商品sync_product 
	 * @param $platform						对应 的平台  aliexpress , ebay , wish 
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	string				数据在redis的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getPlatformAccountSyncControlData($type ,$platform ,  $accountID ){
		$classification = self::$PLATFORMAPPREDISNAME;
		return RedisHelper::RedisGet($classification,$type.".".$platform.'.'.$accountID);
		//return \Yii::$app->redis->hget($classification,$type.".".$platform.'.'.$accountID);
	}//end of getPlatformAccountSyncControlData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除 平台后台用到 的redis 数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $type 						同步订单sync_order， 同步站内信sync_message , 同步商品sync_product
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	string				数据在redis的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function delPlatformAccountSyncControlData($type ,$platform ,  $accountID ){
		$classification = self::$PLATFORMAPPREDISNAME;
		//echo 'hdel '.$classification." ". $type.".".$platform.'.'.$accountID;
		//return \Yii::$app->redis->hdel($classification,$type.".".$platform.'.'.$accountID);
		return RedisHelper::RedisDel($classification,$type.".".$platform.'.'.$accountID );
	}//end of getPlatformAccountSyncControlData
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 删除 指定平台 指定 账号 后台用到 的 订单同步队列 ， 站内信同步队列 ， 商品同步队列
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	string				数据在redis的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function delOnePlatformAccountSyncControlData($platform ,  $accountID ){
		self::delPlatformAccountSyncControlData('sync_order', $platform, $accountID);
		self::delPlatformAccountSyncControlData('sync_message', $platform, $accountID);
		self::delPlatformAccountSyncControlData('sync_product', $platform, $accountID);
	}//end of delOnePlatformAccountSyncControlData
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 开启   订单同步   队列的开关
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function turnOnSyncOrder($platform ,  $accountID){
		self::setPlatformAccountSyncControlData('sync_order' ,$platform ,  $accountID , "Y" );
	}//end of TurnOnSyncOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 关闭   订单同步   队列的开关
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function turnOffSyncOrder($platform ,  $accountID){
		self::setPlatformAccountSyncControlData('sync_order' ,$platform ,  $accountID , "N" );
	}//end of TurnOffSyncOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 检查   订单同步   队列的开关 是否开启
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	boolean true 开启 /false 关闭
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function isSyncOrderOpen($platform ,  $accountID){
		$rt = self::getPlatformAccountSyncControlData('sync_order',$platform ,  $accountID);
		return ($rt == "Y");
	}//end of isTurnOnSyncOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 开启   站内信同步   队列的开关
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function turnOnSyncMessage($platform ,  $accountID){
		self::setPlatformAccountSyncControlData('sync_message' ,$platform ,  $accountID , "Y" );
	}//end of TurnOnSyncMessage
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 关闭   站内信同步   队列的开关
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function turnOffSyncMessage($platform ,  $accountID){
		self::setPlatformAccountSyncControlData('sync_message' ,$platform ,  $accountID , "N" );
	}//end of TurnOffSyncMessage
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 检查   站内信同步   队列的开关 是否开启
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	boolean true 开启 /false 关闭
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function isSyncMessageOpen($platform ,  $accountID){
		$rt = self::getPlatformAccountSyncControlData('sync_message',$platform ,  $accountID);
		return ($rt == "Y");
	}//end of isTurnOnSyncOrder
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 开启   商品同步   队列的开关
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function turnOnSyncProduct($platform ,  $accountID){
		self::setPlatformAccountSyncControlData('sync_product' ,$platform ,  $accountID , "Y" );
	}//end of TurnOnSyncProduct
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 关闭   商品同步   队列的开关
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	na
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function turnOffSyncProduct($platform ,  $accountID){
		self::setPlatformAccountSyncControlData('sync_product' ,$platform ,  $accountID , "N" );
	}//end of TurnOffSyncProduct
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 检查  商品同步   队列的开关 是否开启
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform						对应 的平台  aliexpress , ebay , wish
	 * @param $accountID 					账号的ID
	 +---------------------------------------------------------------------------------------------
	 * @return	boolean true 开启 /false 关闭
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/14				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function isSyncProductOpen($platform ,  $accountID){
		$rt = self::getPlatformAccountSyncControlData('sync_product',$platform ,  $accountID);
		return ($rt == "Y");
	}//end of isTurnOnSyncOrder
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 保存 用户绑定平台的情况 相关 的redis 缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $result 						array 用户平台 绑定 情况  
	 * @param $uid							用户对应 的id
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setPlatformInfoInRedis($result,$uid=0){
		if (empty($uid)){
			$uid = \Yii::$app->subdb->getCurrentPuid();
		}
		
		$classification = self::$PLATFORMAPPREDISNAME;
		$type = 'user_'.$uid.'_binding_account_list';
		
		//当前用户没有绑定 账号时， 设置 一个默认值 
		if (empty($result)) $result = ['all'=>false];
		
		//当前结果是数组时， 设置为json字符串
		if (is_array($result)) $result = json_encode($result);
		return RedisHelper::RedisSet($classification,$type,$result);
		//return \Yii::$app->redis->hset($classification,$type,$result);
	}//end of setPlatformInfoInRedis
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重置  用户绑定平台的情况 相关 的redis 缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform 					平台 ebay ， aliexpress
	 * @param $uid							用户对应 的id
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function resetPlatformInfo($platform='all',$uid=0){
		if (empty($uid)){
			$uid = \Yii::$app->subdb->getCurrentPuid();
		}
		
		if ($platform != 'all'){
			//重置指定平台 的redis 缓存
			$currentData = self::getPlatformInfoInRedis($uid);
			if (is_string($currentData)) $currentData = json_decode($currentData,true);
			$tmpList = self::getAllPlatformBindingSituation([$platform],$uid);
			
			foreach($tmpList as $key =>$value){
				if ($key =='ensogo'){
					$currentData [$key] = false;
				}else{
					$currentData [$key] = $value;
				}
				
			}
		}else{
			//重置 所有 平台 的redis 缓存
			$currentData = self::getAllPlatformBindingSituation([],$uid);
		}
		
		self::setPlatformInfoInRedis($currentData,$uid);
	}//end of resetPlatformInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 读取 用户绑定平台的情况 相关 的redis 缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $uid 							用户对应 的id
	 +---------------------------------------------------------------------------------------------
	 * @return	string				数据在redis的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getPlatformInfoInRedis($uid=0){
		if (empty($uid)){
			$uid = \Yii::$app->subdb->getCurrentPuid();
		}
		$classification = self::$PLATFORMAPPREDISNAME;
		$type = 'user_'.$uid.'_binding_account_list';
		return RedisHelper::RedisGet($classification,$type);
		//return \Yii::$app->redis->hget($classification,$type);
	}//end of getPlatformInfoInRedis
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 读取 用户绑定平台的情况 相关 的redis 缓存数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform 					平台 ebay ， aliexpress
	 * @param $uid							用户对应 的id
	 +---------------------------------------------------------------------------------------------
	 * @return	string				数据在redis的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function getPlatformInfo($platform='all', $uid=0){
		if (empty($uid)){
			$uid = \Yii::$app->subdb->getCurrentPuid();
		}
		$currentData = self::getPlatformInfoInRedis($uid);
		
		if (is_string($currentData)){
			$currentData = json_decode($currentData,true);
		}
		//isset 存在 的情况 下就是已经初始化了
		if (!isset($currentData['all'])){
			//没有初始化的用户先初始化
			self::resetPlatformInfo('all',$uid);
			//没有设置的情况需要重新获取一下
			$currentData = self::getPlatformInfoInRedis($uid);
			
			if (is_string($currentData)){
				$currentData = json_decode($currentData,true);
			}
			
		}
		if ($platform == 'all'){
			return $currentData;
		}else{
			return @$currentData[$platform];
		}
		
	}//end of getPlatformInfo
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 为方便维护 ， 封装 平台 注册前的回调方法
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform 					平台 ebay ， aliexpress
	 * @param $uid							用户的id
	 * @param $params						自定义变量，方便日后扩展
	 +---------------------------------------------------------------------------------------------
	 * @return	string				数据在redis的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function callbackBeforeRegisterAccount($platform,$uid,$params=[]){
		try {
			// 各个平台 独立事件 在这里添加
			switch (strtolower($platform)){
				case 'ebay':
					break;
				case 'aliexpress':
					break;
				case 'wish':
					break;
				case 'ensogo':
					break;
				case 'amazon':
					break;
				case 'dhgate':
					break;
				case 'lazada':
					break;
				case 'linio':
					break;
				case 'jumia':
					break;
				case 'cdiscount':
					break;
				case 'priceminister':
					break;
				case 'bonanza':
					break;
				case 'rumall':
					break;
				case 'newegg':
					break;
				case 'shopee':
					break;
			}
		} catch (\Exception $e) {
			\Yii::error((__function__)." : ".$e->getMessage() ." line no:".$e->getLine(),"file");
		}
		
		
		try {
			// 各个平台 通用事件 在这里添加
		} catch (\Exception $e) {
			\Yii::error((__function__)." : ".$e->getMessage() ." line no:".$e->getLine(),"file");
		}
	}//end of function callbackBeforeRegisterAccount
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 为方便维护 ， 封装 平台 注册 成功后的回调方法
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform 					平台 ebay ， aliexpress
	 * @param $uid							用户的id
	 * @param $params						自定义变量，方便日后扩展
	 +---------------------------------------------------------------------------------------------
	 * @return	string				数据在redis的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function callbackAfterRegisterAccount($platform,$uid,$params=[]){
		switch (strtolower($platform)){
			case 'ebay':
				break;
			case 'aliexpress':
				break;
			case 'wish':
				break;
			case 'ensogo':
				//EnsogoHelper::addEnsogoTagsQueue($uid);
				break;
			case 'amazon':
				break;
			case 'dhgate':
				break;
			case 'lazada':
				break;
			case 'linio':
				break;
			case 'jumia':
				break;
			case 'cdiscount':
				if(isset($params['username'])){
					try{
						OdOrder::updateAll(['isshow'=>'Y']," isshow='N' and selleruserid=:username ",[':username'=>$params['username']]);
					} catch (\Exception $e) {
						
					}
				}
				break;
			case 'priceminister':
				break;
			case 'bonanza':
			    break;
		    case 'rumall':
		        break;
			case 'newegg':
				break;
			case 'customized':
				break;
			case 'shopee':
				break;
		}
		
		if (isset($params['selleruserid'])){
			OrderApiHelper::resetOrderVisibleByAccount($platform, $params['selleruserid'],'Y');
		}
		
		 
		self::resetPlatformInfo($platform,$uid);
	}//end of callbackAfterRegister

	/**
	 +---------------------------------------------------------------------------------------------
	 * 为方便维护 ， 封装 平台解绑  成功后的回调方法
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform 					平台 ebay ， aliexpress
	 * @param $uid							用户的id
	 * @param $params						自定义变量，方便日后扩展
	 +---------------------------------------------------------------------------------------------
	 * @return	string				数据在redis的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/	
	static public function callbackAfterDeleteAccount($platform,$uid,$params=[]){
		switch (strtolower($platform)){
			case 'ebay':
				break;
			case 'aliexpress':
				break;
			case 'wish':
				break;
			case 'ensogo':
				break;
			case 'amazon':
				break;
			case 'dhgate':
				break;
			case 'lazada':
				break;
			case 'linio':
				break;
			case 'jumia':
				break;
			case 'cdiscount':
				try{
					\eagle\modules\listing\helpers\CdiscountOfferSyncHelper::deleteOfferListAfterStoreUnbinded($params['selleruserid'],$uid);
				}catch(\Exception $e){
					SysLogHelper::SysLog_Create('platform',__CLASS__, __FUNCTION__,'error',is_array($e->getMessage())?json_encode($e->getMessage()):$e->getMessage());
				}
				break;
			case 'priceminister':
				break;
			case 'rumall':
			    break;
		    case 'newegg':
		    	break;
			case 'customized':
				break;
			case 'shopee':
				break;
		}
		
		if (isset($params['site_id']) ){
			//删除站内信同步队列数据 
			MessageApiHelper::delMsgQueue($platform, $params['site_id']);	
		}
		
		if (isset($params['selleruserid'])){
			if (isset($params['order_source_site_id'])){
				OrderApiHelper::resetOrderVisibleByAccount($platform, $params['selleruserid'],'N',$params['order_source_site_id']);
			}else{
				OrderApiHelper::resetOrderVisibleByAccount($platform, $params['selleruserid'],'N');
			}
			
		}
		
		self::resetPlatformInfo($platform);
	}//end of callbackAfterDeleteAccount
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $platform 					平台 ebay ， aliexpress
	 +---------------------------------------------------------------------------------------------
	 * @return	string				数据在redis的value
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/02/27				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function resetSyncSetting($platform,$accountID,$active,$uid){
		try {
			if ( in_array($active, ["Y",'1'])){
				$thisvalue = 1;
			}else{
				$thisvalue = 0;
			}
			if (empty($uid)){
				$uid = \Yii::$app->subdb->getCurrentPuid();
			}
			$msg = '';
			$sellerId='';
			switch (strtolower($platform)){
				case 'ebay':
					$account = SaasEbayUser::find()->where(['uid'=>$uid,'ebay_uid'=>$accountID])->one();
			
					if ( null == $account ){
						$msg .= "".TranslateHelper::t('无该账号');
						return ['success'=>false , 'message'=>$msg];
					}
					$sellerId = $account->selleruserid;
					$account->item_status = $thisvalue;
					if (!$account->save()){
						$msg .= "".json_encode($account->errors);
					}
			
					//同步订单
					//1: 订单 2: 刊登 3: 站内信 4: 评价 5: 售前纠纷Dispute 6: 全部纠纷UserCase 7:item
					//queue_getorder status=0
					$SyncOrderQueue = SaasEbayAutosyncstatus::findOne(['ebay_uid'=>$accountID,'type'=>1]);
					$SyncOrderQueue->status = $thisvalue;
			
					if (!$SyncOrderQueue->save()){
						$msg .= "".json_encode($SyncOrderQueue->errors);
					}
			
					//同步 商品
					//saas_ebay_autosyncstatus 表 type=7的 是同步在线item的
			
					$SyncItemQueue = SaasEbayAutosyncstatus::findOne(['ebay_uid'=>$accountID,'type'=>7]);
					$SyncItemQueue->status = $thisvalue;
					if (!$SyncItemQueue->save()){
						$msg .= "".json_encode($SyncItemQueue->errors);
					}
			
					//同步站内信
					$site_id = $account->ebay_uid;
			
					break;
				case 'aliexpress':
			
					//同步订单
					$aliexpress_uid = trim($accountID);
					//$sellerloginid = trim($_POST['sellerloginid']);
					$is_active = $thisvalue;
					$User_obj = SaasAliexpressUser::find()->where(' aliexpress_uid=:a', array(':a'=>$aliexpress_uid))->one();
					$sellerloginid = $User_obj->sellerloginid;
					$sellerId = $User_obj->sellerloginid;
					if(!isset($User_obj)) {
						return array("success"=>false,"message"=>TranslateHelper::t('操作失败').$sellerloginid.TranslateHelper::t('账号不存在'));
					}
					$User_obj->is_active = $is_active;
					$User_obj->update_time = time();
					$User_obj->save();
			
					//如果用户设置账号不启用,则关闭速卖通的相关同步订单功能
					$rt = SaasAliexpressAutosyncV2::updateAll(array('is_active'=>$User_obj->is_active,'update_time'=>time()),'sellerloginid=:p and aliexpress_uid=:a',array(':p' => $sellerloginid,':a'=>$aliexpress_uid));
			
					if ($User_obj->is_active ==1){
						$user_info = \Yii::$app->user->identity;
						if ($user_info['puid']==0){
							$uid = $user_info['uid'];
						}else {
							$uid = $user_info['puid'];
						}
						//绑定成功写入同步订单列表队列
						$types = array(
								'day120',
								'time',
								'onSelling',
						);
						foreach ($types as $type){
							$SAA_obj = SaasAliexpressAutosyncV2::find()->where('sellerloginid=:sellerloginid and type=:type',array(':sellerloginid'=>$sellerloginid,':type'=>$type))->one();
							if (isset($SAA_obj)){//已经有数据，只要更新
								$binding_time = $SAA_obj->binding_time;
								$status = $SAA_obj->status;
								$SAA_obj->is_active = $User_obj->is_active;
								$SAA_obj->status = 0;
								$SAA_obj->type=$type;
								$SAA_obj->times=0;
								$SAA_obj->binding_time=time();
								$SAA_obj->update_time = time();
								if ($type == 'finish' || $type == 'finish30'){
									if ($status == 4){
										$SAA_obj->start_time=$binding_time;
										$SAA_obj->end_time=0;
									}else{
										$SAA_obj->start_time=0;
										$SAA_obj->end_time=0;
									}
								}elseif ($type == 'time'){
									$SAA_obj->start_time=0;
									$SAA_obj->end_time=0;
								}
								$SAA_obj->save();
							}else{//新数据，插入一行数据
								$SAA_obj=new SaasAliexpressAutosyncV2();
								$SAA_obj->uid = $uid;
								$SAA_obj->sellerloginid = $User_obj->sellerloginid;
								$SAA_obj->aliexpress_uid = $User_obj->aliexpress_uid;
								$SAA_obj->is_active = $User_obj->is_active;
								$SAA_obj->status = 0;
								$SAA_obj->type=$type;
								$SAA_obj->times=0;
								$SAA_obj->start_time=0;
								$SAA_obj->end_time=0;
								$SAA_obj->last_time=0;
								$SAA_obj->binding_time=time();
								$SAA_obj->create_time = time();
								$SAA_obj->update_time = time();
								$SAA_obj->save();
							}
						}
					}
					//同步 商品
			
					//同步站内信
					$site_id = $aliexpress_uid;
					break;
				case 'wish':
					//同步订单   同步 商品
					$WishAccount = SaasWishUser::findOne(['site_id'=>$accountID]);
					$WishAccount->is_active = $thisvalue;
			
					if (! $WishAccount->save()){
						$msg .= "".json_encode($WishAccount->errors);
					}
					$sellerId = $WishAccount->store_name;
					//同步站内信
					$site_id = $WishAccount->site_id;
			
					break;
				case 'ensogo':
					//同步订单   同步 商品
					$EnsogoUser = SaasEnsogoUser::findOne(['site_id'=>$accountID]);
					$EnsogoUser->is_active = $thisvalue;
					if (! $EnsogoUser->save()){
						$msg .= "".json_encode($EnsogoUser->errors);
					}
					//同步站内信
					$site_id = $EnsogoUser->site_id;
					$sellerId = $WishAccount->store_name;
					break;
				case 'amazon':
					//同步订单
			
					//同步 商品
			
					//同步站内信
					break;
				case 'dhgate':
					//同步订单
			
					//同步 商品
			
					//同步站内信
					break;
				case 'lazada':
				    //同步订单
				    $User_obj = SaasLazadaUser::find()->where('lazada_uid=:a', array(':a'=>$accountID))->andWhere('status<>3')->one();
				    if($User_obj != null){
				        $User_obj->status = $thisvalue;// 禁用/启用 都是0/1
				        $User_obj->update_time = time();
				        $sellerId = $User_obj->store_name;
				        if($User_obj->save(false)){
				            $rtn = LazadaApiHelper::SwitchLazadaCronjobV2($thisvalue, $accountID);
				            if ($rtn == false){
				                $msg .= $rtn['message'];
				            }
				        }else{
				            $msg .= print_r($User_obj->getErrors(),true);
				        }
				    }else{
				        return array('success'=>false , 'message'=>'账号不存在');
				    }
				    	
				    //同步 商品
				    // SwitchLazadaCronjobV2已经包含产品处理
				    	
				    //同步站内信
				    break;
				case 'linio':
				case 'jumia':
					//同步订单
					$User_obj = SaasLazadaUser::find()->where('lazada_uid=:a', array(':a'=>$accountID))->andWhere('status<>3')->one();
					if($User_obj != null){
						$User_obj->status = $thisvalue;// 禁用/启用
						$User_obj->update_time = time();
						$sellerId = $User_obj->store_name;
						if($User_obj->save(false)){
							$rtn = LazadaApiHelper::SwitchLazadaCronjob($thisvalue, $accountID);
							if ($rtn == false){
								$msg .= $rtn['message'];
							}
						}else{
							$msg .= print_r($User_obj->getErrors(),true);
						}
					}else{
						return array('success'=>false , 'message'=>'账号不存在');
					}
			
					//同步 商品
					// SwitchLazadaCronjob已经包含产品处理
// 					list($tmp_success , $tmp_msg) = LazadaApiHelper::switchProductSync($accountID , $thisvalue);
// 					if ($tmp_success == false){
// 						$msg .= $tmp_msg;
// 					}
			
					//同步站内信
					break;
				case 'cdiscount':
					//同步订单
			
					//同步 商品
			
					//同步站内信
					break;
				case 'priceminister':
					//同步订单
			
					//同步 商品
			
					//同步站内信
					break;
				case 'shopee':
					//同步订单   同步 商品
					$shopee_user = SaasShopeeUser::find()->where(['shopee_uid' => $accountID])->andWhere("status<>3")->one();
					if(!empty($shopee_user)){
						$shopee_user->status = $active;
						$shopee_user->update_time = time();
						if ($shopee_user->save(false)){
							$ret = ShopeeAccountsApiHelper::SwitchShopeeCronjob($thisvalue, $accountID);
							if (!$ret['success']){
								$msg .= $ret['msg'];
							}
						}
						else{
							$msg .= print_r($shopee_user->getErrors(), true);
						}
					}
					else{
						return ['success' => false , 'message' => '账号不存在'];
					}
					
					break;
			}
			
			
			if (in_array($platform, ['ebay' , 'aliexpress','wish','dhgate'])){
				//同步站内信
				$msgRT = MessageApiHelper::setSyncMsg($platform,  $site_id , $sellerId, $thisvalue);
					
				if (empty($msg)){
					if ($msgRT['success']){
						return ['success'=>true , 'message'=>''];
					}else{
						return ['success'=>false , 'message'=>$msgRT['error']];
					}
				}else{
					return ['success'=>false , 'message'=>$msg];
				}
			}else{
				if (empty($msg)){
					return ['success'=>true , 'message'=>''];
				}else{
					return ['success'=>false , 'message'=>$msg];
				}
			}
		} catch (\Exception $e) {
			return ['success'=>false , 'message'=>"Message ".$e->getMessage()." line no ".$e->getLine()];
		}
		
	}//end of resetSyncSetting
	
	/*
	 * 获取用户某个销售平台绑定的账号列表
	 * @param     $platform		平台名称 目前有效平台为：aliexpress,ebay,dhgate,wish,cdiscount,priceminister,lazada,linio,jumia...
	 * @param     $puid		
	 * @author	lzhl	2016/7/5	初始化
	 */
	public static function getUserBindingAccountsByPlatform($platform,$puid){
		$accounts = [];
		$accounts['message']='';
		$accounts['data']=[];
		$account_infos=[];//saas表用户绑定的账号信息
		$seller_key='';//店铺唯一区分字段
		switch ($platform){
			case 'aliexpress':
				$account_infos = SaasAliexpressUser::find()->where(['uid'=>$puid])->asArray()->all();
				$seller_key = 'sellerloginid';
				break;
			case 'ebay':
				$account_infos = SaasEbayUser::find()->where(['uid'=>$puid])->asArray()->all();
				$seller_key = 'sellerloginid';
				break;
			case 'dhgate':
				$account_infos = SaasDhgateUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')->asArray()->all();
				$seller_key = 'sellerloginid';
				break;
			case 'wish':
				$account_infos = SaasWishUser::find()->where(['uid'=>$puid])->asArray()->all();
				$seller_key = 'merchant_id ';
				break;
			case 'cdiscount':
				$account_infos = SaasCdiscountUser::find()->where(['uid'=>$puid])->asArray()->all();
				$seller_key = 'username';
				break;
			case 'priceminister':
				$account_infos = SaasPriceministerUser::find()->where(['uid'=>$puid])->asArray()->all();
				$seller_key = 'username';
				break;
			case 'lazada':
				$account_infos = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->asArray()->all();
				$seller_key = ['platform_userid','lazada_site'];
				break;
			case 'linio':
				$account_infos = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->asArray()->all();
				$seller_key = ['platform_userid','lazada_site'];
				break;
			case 'jumia':
				$account_infos = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();
				$seller_key = ['platform_userid','lazada_site'];
				break;
				
			case 'newegg':
				$account_infos = SaasNeweggUser::find()->where(['uid'=>$puid])->asArray()->all();
				$seller_key = 'SellerID';
				break;
			case 'shopee':
				$account_infos = SaasShopeeUser::find()->where(['puid'=>$puid])->andWhere('status <> 3')->asArray()->all();
				$seller_key = ['shop_id','site'];
				break;
					
			default:
				$accounts['message']='未支持该平台';
				break;
		}
		if(!empty($account_infos)){
			foreach ($account_infos as $info){
				//店铺唯一区分字段为单个字段时
				if(!empty($seller_key) && is_string($seller_key)){
					$accounts['data'][$info[$seller_key]] = $info;
				}
				//店铺唯一区分字段为多个字段组合时
				if(!empty($seller_key) && is_array($seller_key)){
					$index='';
					foreach ($seller_key as $key){
						$index .= empty($index)?$info[$key]:' - '.$info[$key];
					}
					if(!empty($index))
						$accounts['data'][$index] = $info;
				}
			}
		}
		return $accounts;
	}
	
	
	/**
	 * 查看某puid下，对应平台的所有帐号信息
	 *lwj
	 */
	public static function getPlatformAllAccount($puid,$platform,$isShowStoreName= false){
	    if (empty($puid)){
	        //$puid = \Yii::$app->user->identity->getParentUid();
	        $puid = \Yii::$app->subdb->getCurrentPuid();
	    }
	    $result = [];
	    if(empty($platform)){
	        return array("success"=>false,'data'=>'','message'=>'没有选定平台，帐号获取失败');
	    }else{
	        switch (strtolower($platform)){
	            case 'ebay':
	                $ebayUsers = SaasEbayUser::find()->where(['uid'=>$puid])->asArray()->all();
	                $ebaySelleruids = Helper_Array::toHashmap($ebayUsers , 'selleruserid' , 'selleruserid');
	                $result [$platform] = $ebaySelleruids;
	                break;
	            case 'aliexpress':
	                $aliexpressUsers = SaasAliexpressUser::find()->where(['uid'=>$puid])->asArray()->all();
					if ($isShowStoreName){
						$aliSelleruids = Helper_Array::toHashmap($aliexpressUsers , 'sellerloginid' , 'store_name');
					}else{
						$aliSelleruids = Helper_Array::toHashmap($aliexpressUsers , 'sellerloginid' , 'sellerloginid');
					}
	                
	                $result [$platform] = $aliSelleruids;
	                break;
	            case 'wish':
	                $wishUsers = SaasWishUser::find()->where(['uid'=>$puid])->asArray()->all();
	                $wishSelleruids = Helper_Array::toHashmap($wishUsers , 'store_name' , 'store_name');
	                $result [$platform] = $wishSelleruids;
	                break;
	            case 'ensogo':
	                $ensogoUsers = SaasEnsogoUser::find()->where(['uid'=>$puid])->asArray()->all();
	                $ensogoSelleruids = Helper_Array::toHashmap($ensogoUsers , 'store_name' , 'store_name');
	                $result [$platform] = $ensogoSelleruids;
	                break;
	                 
	            case 'amazon':
	                $amazonUsers = SaasAmazonUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')->asArray()->all();
	                $amazonSelleruids = Helper_Array::toHashmap($amazonUsers , 'merchant_id' , 'store_name');
	                $result [$platform] = $amazonSelleruids;
	                break;
	                 
	            case 'dhgate':
	                $dhgateUsers = SaasDhgateUser::find()->where(['uid'=>$puid])->andWhere('is_active <> 3')->asArray()->all();
	                $dhgateSelleruids = Helper_Array::toHashmap($dhgateUsers , 'sellerloginid' , 'sellerloginid');
	                $result [$platform] = $dhgateSelleruids;
	                break;
	                 
	            case 'lazada':
	                $lazadaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"lazada"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑账号
	                $lazadaPlatformUserids = Helper_Array::toHashmap($lazadaUsers , 'platform_userid' , 'platform_userid');
	                $result [$platform] = $lazadaPlatformUserids;
	                break;
	            case 'linio':
	                $linioUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"linio"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑账号
	                $linioPlatformUserids = Helper_Array::toHashmap($linioUsers , 'platform_userid' , 'platform_userid');
	                $result [$platform] = $linioPlatformUserids;
	                break;
	            case 'jumia':
	                $jumiaUsers = SaasLazadaUser::find()->where(['puid'=>$puid,"platform"=>"jumia"])->andWhere('status <> 3')->asArray()->all();// 不包括解绑账号
	                $jumiaPlatformUserids = Helper_Array::toHashmap($jumiaUsers , 'platform_userid' , 'platform_userid');
	                $result [$platform] = $jumiaPlatformUserids;
	                break;
	            case 'cdiscount':
	                $cdiscountUsers = SaasCdiscountUser::find()->where(['uid'=>$puid])->asArray()->all();
	                // 		                $cdiscountSelleruids = Helper_Array::toHashmap($cdiscountUsers , 'username' , 'store_name');
	                $cdiscountSelleruids = Helper_Array::toHashmap($cdiscountUsers , 'username' , 'store_name');
	                $result [$platform] = $cdiscountSelleruids;
	                break;
	            case 'priceminister':
	                $priceministerUsers = SaasPriceministerUser::find()->where(['uid'=>$puid])->asArray()->all();
	                // 		                $priceministerSelleruids = Helper_Array::toHashmap($priceministerUsers , 'username' , 'store_name');
	                $priceministerSelleruids = Helper_Array::toHashmap($priceministerUsers , 'username' , 'store_name');
	                $result [$platform] = $priceministerSelleruids;
	                break;
	            case 'bonanza':
	                $bonanzaUsers = SaasBonanzaUser::find()->where(['uid'=>$puid])->asArray()->all();
	                $bonanzaUsersuids = Helper_Array::toHashmap($bonanzaUsers , 'store_name' , 'store_name');
	                $result [$platform] = $bonanzaUsersuids;
	                break;
                case 'rumall':
                    $rumallUsers = SaasRumallUser::find()->where(['uid'=>$puid])->asArray()->all();
                    $rumallUsersuids = Helper_Array::toHashmap($rumallUsers , 'company_code' , 'store_name');
                    $result [$platform] = $rumallUsersuids;
                    break;
                case 'newegg':
                	$neweggUsers = SaasNeweggUser::find()->where(['uid'=>$puid])->asArray()->all();
                    $neweggSelleruids = Helper_Array::toHashmap($neweggUsers , 'SellerID' , 'store_name');
                    $result [$platform] = $neweggSelleruids;
                    break;
                case 'customized':
                	$customizedUsers = SaasCustomizedUser::find()->where(['uid'=>$puid])->asArray()->all();
                    $customizedSelleruids = Helper_Array::toHashmap($customizedUsers , 'username' , 'store_name');
                    $result [$platform] = $customizedSelleruids;
                	break;
               	case 'shopee':
               		$shopeeUsers = SaasShopeeUser::find()->where(['puid'=>$puid])->andWhere('status <> 3')->asArray()->all();// 不包括解绑账号
               		$shopeePlatformUserids = Helper_Array::toHashmap($shopeeUsers , 'shop_id' , 'store_name');
               		$result [$platform] = $shopeePlatformUserids;
               		break;
	        }
	        
	        if(!empty($result [$platform])){
	            return array("success"=>true,'data'=>$result[$platform],'message'=>'');
	        }else{
	            return array("success"=>false,'data'=>'','message'=>'该平台没有相关帐号信息');
	        }
	
	
	
	    }
	}
	
	/*
	 * 获取用户绑定的CD账号中，有异常的账号信息
	 * 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时初始化获取订单失败、刊登失败
	 * @return	array
	 */
	public static function getUserCdiscountProblemAccounts($uid){
		if(empty($uid))
			return [];
		
		$cdiscountAccounts = SaasCdiscountUser::find()->where(['uid'=>$uid])->asArray()->all();
		if(empty($cdiscountAccounts))
			return [];
		
		$accountUnActive = [];//未开启同步的账号
		$tokenExpired = [];//授权过期的账号
		$order_retrieve_errors = [];//获取订单失败
		$initial_order_failed = [];//首次绑定时，初始化获取订单失败
		$listing_failed = [];//刊登失败
		foreach ($cdiscountAccounts as $account){
			if(empty($account['is_active'])){
				$accountUnActive[] = $account;
				continue;
			}
			if( strtotime($account['token_expired_date'])< strtotime("-2 days") || $account['order_retrieve_message']=='token已过期，请检测绑定信息中的 账号，密码是否正确。'){
				$tokenExpired[] = $account;
				continue;
			}
			if(empty($account['initial_fetched_changed_order_since']) || $account['initial_fetched_changed_order_since']=='0000-00-00 00:00:00'){
				$initial_order_failed[] = $account;
				continue;
			}
			if($account['order_retrieve_message']!=='token已过期，请检测绑定信息中的 账号，密码是否正确。' && !empty($account['order_retrieve_message'])){
				$order_retrieve_errors[] = $account;
				continue;
			}
		}
		$problems=[
			'unActive'=>$accountUnActive,
			'token_expired'=>$tokenExpired,
			'initial_failed'=>$initial_order_failed,
			'order_retrieve_failed'=>$order_retrieve_errors,
			'listing_failed'=>$listing_failed,
		];
		return $problems;
	}
	
	/*
	* 获取用户绑定的PM账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserPriceministerProblemAccounts($uid){
		if(empty($uid))
			return [];
	
		$priceministerAccounts = SaasPriceministerUser::find()->where(['uid'=>$uid])->asArray()->all();
		if(empty($priceministerAccounts))
			return [];
		
		$accountUnActive = [];//未开启同步的账号
		$tokenExpired = [];//授权过期的账号
		$order_retrieve_errors = [];//获取订单失败
		$initial_order_failed = [];//首次绑定时，初始化获取订单失败
		$listing_failed = [];//刊登失败
		foreach ($priceministerAccounts as $account){
			if(empty($account['is_active'])){
				$accountUnActive[] = $account;
				continue;
			}
			if($account['order_retrieve_message']=='账号或token错误'){
				$tokenExpired[] = $account;
				continue;
			}
			if(empty($account['initial_fetched_changed_order_since']) || $account['initial_fetched_changed_order_since']=='0000-00-00 00:00:00'){
				$initial_order_failed[] = $account;
				continue;
			}
			if($account['order_retrieve_message']!=='账号或token错误' && !empty($account['order_retrieve_message'])){
				$order_retrieve_errors[] = $account;
				continue;
			}
		}
		$problems=[
			'unActive'=>$accountUnActive,
			'token_expired'=>$tokenExpired,
			'initial_failed'=>$initial_order_failed,
			'order_retrieve_failed'=>$order_retrieve_errors,
			'listing_failed'=>$listing_failed,
		];
		return $problems;
	}
	
	
	/*
	* 获取用户绑定的newegg账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败
	* @return	array
	*/
	public static function getUserNeweggProblemAccounts($uid){
		if(empty($uid))
			return [];
	
		$neweggAccounts = SaasNeweggUser::find()->where(['uid'=>$uid])->asArray()->all();
		if(empty($neweggAccounts))
			return [];
		
		$accountUnActive = [];//未开启同步的账号
		$tokenExpired = [];//授权过期的账号	//newegg的token不会更新
		$order_retrieve_errors = [];//获取订单失败
		$initial_order_failed = [];//首次绑定时，初始化获取订单失败
		$listing_failed = [];//刊登失败
		foreach ($neweggAccounts as $account){
			if(empty($account['is_active'])){
				$accountUnActive[] = $account;
				continue;
			}
			$autoSync = SaasNeweggAutosync::find()->where(['site_id'=>$account['site_id']])->orderBy("type ASC")->asArray()->all();
			foreach ($autoSync as $sync){
				if($sync['type']==1){//同步新单
					if($sync['status']==1 || $sync['status']==3 || (int)$sync['last_finish_time']<(time()-3600) ){
						$order_retrieve_errors[] = $account;
						break;
					}
				}else{//同步旧单
					if($sync['status']==1 || $sync['status']==3){
						$initial_order_failed[] = $account;
						break;
					}
				}
			}
		}
		$problems=[
			'unActive'=>$accountUnActive,
			'token_expired'=>$tokenExpired,
			'initial_failed'=>$initial_order_failed,
			'order_retrieve_failed'=>$order_retrieve_errors,
			'listing_failed'=>$listing_failed,
		];
		return $problems;
	}
	
	
	/*
	* 获取用户绑定的SMT账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserAliexpressAccountProblems($uid){
		$tokenExpired_accounts =  \eagle\modules\order\helpers\AliexpressOrderHelper::getUserAccountProblems($uid);
		$problems = [
			'token_expired'=>$tokenExpired_accounts,
		];
		return $problems;
	}//end of function getUserAliexpressAccountProblems
	
	/*
	* 获取用户绑定的ebay账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserEbayAccountProblems($uid){
		//return [];//ebay 绑定信息有问题暂时返回 空值
		return \eagle\modules\platform\apihelpers\EbayAccountsApiHelper::getProblemAccount();
	}//end of function getUserEbayAccountProblems
	
	/*
	* 获取用户绑定的amazon账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserAmazonAccountProblems($uid){
		return [];//amazon 绑定信息有问题暂时未开发
		//return \eagle\modules\platform\apihelpers\EbayAccountsApiHelper::getProblemAccount();
	}//end of function getUserAmazonAccountProblems
	
	/*
	* 获取用户绑定的wish账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserWishAccountProblems($uid){
		$tokenExpired_accounts = \eagle\modules\order\helpers\WishOrderHelper::getUserAccountProblems($uid);
		$problems = [
			'token_expired'=>$tokenExpired_accounts,
		];
		return $problems;
	}//end of function getUserWishAccountProblems
	
	/*
	* 获取用户绑定的wish账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserDhgateAccountProblems($uid){
		return \eagle\modules\dhgate\apihelpers\DhgateApiHelper::getUserAccountProblems($uid);
	}//end of function getUserDhgateAccountProblems
	
	/*
	* 获取用户绑定的lazada账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserLazadaAccountProblems($uid){
		return LazadaApiHelper::getUserAccountRelatedErrorInfo($uid,'lazada');
	}//end of function getUserLazadaAccountProblems
	
	/*
	* 获取用户绑定的Linio账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserLinioAccountProblems($uid){
		return LazadaApiHelper::getUserAccountRelatedErrorInfo($uid,'linio');
	}//end of function getUserLinioAccountProblems
	
	/*
	* 获取用户绑定的jumia账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserJumiaAccountProblems($uid){
		return LazadaApiHelper::getUserAccountRelatedErrorInfo($uid,'jumia');
	}//end of function getUserJumiaAccountProblems
	
	/*
	* 获取用户绑定的Bonanza账号中，有异常的账号信息
	* 异常包括：未开启同步的账号、授权过期的账号、获取订单失败、首次绑定时、初始化获取订单失败、刊登失败
	* @return	array
	*/
	public static function getUserBonanzaAccountProblems($uid){
		return \eagle\modules\order\helpers\BonanzaOrderInterface::getUserAccountProblems($uid);
	}//end of function getUserBonanzaAccountProblems
	
	
	/*
	 * 获取用户指定平台的有权限账号
	 * @return array
	 * @author	lzhl	2017/3/24	初始化
	 */
	public static function getPlatformAuthorizeAccounts($platform=''){
		$uid = \Yii::$app->user->id;
		
		if(empty($uid) || empty($platform))
			return [];
		
		$AuthorizePlatformAccounts = UserHelper::getUserAuthorizePlatformAccounts($platform,$uid);
		if(!empty($AuthorizePlatformAccounts[$platform]))
			return $AuthorizePlatformAccounts[$platform];
		else 
			return [];
	}
}

?>