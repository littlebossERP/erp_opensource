<?php namespace eagle\modules\assistant\models;

use \eagle\models\assistant\DpEnable;

class SaasAliexpressUser extends \eagle\models\SaasAliexpressUser
{

	public function getDpEnable(){
		return $this->hasOne(DpEnable::className(),[
			'dp_shop_id'=>'sellerloginid'
		]);
	}


}
