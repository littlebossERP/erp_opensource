<?php namespace eagle\modules\listing\service;

use eagle\modules\listing\models\WishFanben;
use eagle\modules\platform\apihelpers\WishAccountsApiHelper;
use eagle\models\SaasWishUser;

interface Account 
{

	static function getAccountByToken($token);

	// 根据site_id查询账户信息
	static function getAccountBySiteId($site_id);

	// 根据范本的sku获取site_id
	static function getSiteIdByFanbenId($fanben_id);

	/**
	 * 更新商品最后更新时间戳
	 * @param  [type] $token [description]
	 * @return [type]            [description]
	 */
	static function updateLastProductSuccessTimeByToken($token);

	static function getAllAccounts();

}