<?php namespace eagle\modules\listing\service\wish;

use eagle\modules\listing\models\WishFanben;
use eagle\modules\platform\apihelpers\WishAccountsApiHelper;
use eagle\models\SaasWishUser;

class Account implements \eagle\modules\listing\service\Account
{


	static function getAccountByToken($token){
        $wish_account = SaasWishUser::find()
        	->where([
        		'token'=>$token,
        		'is_active'=>1
        	])->one();
        return $wish_account;
	}

	// 根据site_id查询账户信息
	static function getAccountBySiteId($site_id){
        $wish_account = WishAccountsApiHelper::RetrieveAccountBySiteID($site_id);
        return (object)$wish_account['account'];
	}

	// 根据范本的sku获取site_id
	static function getSiteIdByFanbenId($fanben_id){
		$fanben_info = WishFanben::findOne($fanben_id);
        if($fanben_info == NULL){
            // self::error(203,'范本信息不存在！');
        }
		return $fanben_info->site_id;
	}

	static private function error($code,$msg){
		echo json_encode(['code'=>$code,'message'=>$msg]);die;
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
		return SaasWishUser::find()
			->where([
				'is_active'=>1
			])->all();
	}


}