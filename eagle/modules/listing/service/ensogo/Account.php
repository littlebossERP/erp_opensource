<?php namespace eagle\modules\listing\service\ensogo;

use eagle\models\SaasEnsogoUser;


class Account implements \eagle\modules\listing\service\Account
{

	const STATUS_ACTIVE = 1;

	// 根据seller_id查询账户信息
	static function getAccountBySiteSellerId($seller_id){
		return SaasEnsogoUser::find()->where([
			'store_name'=>$seller_id,
			'is_active'=>self::STATUS_ACTIVE
		])->one();
	}


	static function getAccountByToken($token){
		return SaasEnsogoUser::find()->where([
			'token'=>$token,
			'is_active'=>self::STATUS_ACTIVE
		])->one();
	}

	// 根据site_id查询账户信息
	static function getAccountBySiteId($site_id){
		$account = SaasEnsogoUser::find()->where([
			'site_id'=>$site_id,
			'is_active'=>self::STATUS_ACTIVE
		]);
		// echo $account->createCommand()->getRawSql();
		return $account->one(); 
	}

	// 根据范本的sku获取site_id
	static function getSiteIdByFanbenId($fanben_id){

	}

	/**
	 * 更新商品最后更新时间戳
	 * @param  [type] $token [description]
	 * @return [type]            [description]
	 */
	static function updateLastProductSuccessTimeByToken($token){
		$account = self::getAccountByToken($token);
		$account->last_product_success_retrieve_time = date('Y-m-d H:i:s');
		return $account->save();
	}


	static function getAllAccounts(){
		return SaasEnsogoUser::find()
			->where([
				'is_active'=>1
			])->all();
	}


}