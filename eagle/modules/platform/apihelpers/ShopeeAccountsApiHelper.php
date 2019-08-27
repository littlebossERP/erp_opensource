<?php
namespace eagle\modules\platform\apihelpers;

use eagle\models\SaasShopeeAutosync;
use eagle\modules\platform\helpers\ShopeeAccountsHelper;
/**
 +------------------------------------------------------------------------------
 * shopee平台管理模块业务接口类
 +------------------------------------------------------------------------------
 * @category	platform
 +------------------------------------------------------------------------------
 */

class ShopeeAccountsApiHelper {
	
	/**
	 * 返回shopee 所有站点CountryCode和站点中文的mapping
	 *
	 */
	public static function getCountryCodeSiteMapping(){
		return ShopeeAccountsHelper::getCountryCodeSiteMapping();
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * shopee 订单同步情况 数据
	 * +---------------------------------------------------------------------------------------------
	 * @param $account_key		账号表主键
	 * @param $uid				uid use_base 的id
	 * +---------------------------------------------------------------------------------------------
	 **/
	public static function getLastOrderSyncDetail($account_key, $uid = 0){
		return ShopeeAccountsHelper::getLastOrderSyncDetail($account_key, $uid);
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * shopee 商品同步情况 数据
	 * +---------------------------------------------------------------------------------------------
	 * @param $account_key		账号表主键
	 * @param $uid				uid use_base 的id
	 * +---------------------------------------------------------------------------------------------
	 **/
	public static function getLastProductSyncDetail($account_key, $uid = 0){
		return ShopeeAccountsHelper::getLastProductSyncDetail($account_key, $uid);
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 变更shopee后台job状态
	 * +---------------------------------------------------------------------------------------------
	 * @param status			状态
	 * @param $shopee_uid		账号表主键
	 * +---------------------------------------------------------------------------------------------
	 **/
	public static function SwitchShopeeCronjob($status, $shopee_uid){
		return ShopeeAccountsHelper::SwitchShopeeCronjob($status, $shopee_uid);
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 获取用户绑定的CD账号的异常情况，如同步订单失败之类
	 * +---------------------------------------------------------------------------------------------
	 * @param $uid	
	 * +---------------------------------------------------------------------------------------------
	 **/
	public static function getUserAccountProblems($uid  = ''){
		return ShopeeAccountsHelper::getUserAccountProblems($uid);
	}
	
}
?>