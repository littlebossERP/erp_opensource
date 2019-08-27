<?php namespace eagle\modules\listing\models;

use eagle\models\SaasWishUser;
use eagle\models\SaasAliexpressUser;
use eagle\models\SaasLazadaUser;
 
class ListingDraftLog extends \eagle\models\ListingDraftLog 
{

	function getShopName(){
		return call_user_func_array([$this,'get'.ucfirst($this->platform_to).'ShopName'], [$this->shop_to]);
	}

	protected function getWishShopName(){
		$e = $this->hasOne(SaasWishUser::className(),[
			'site_id'=>'shop_to'
		]);
		return $e->one()->store_name;
	}

	protected function getAliexpressShopName(){
		$e = $this->hasOne(SaasAliexpressUser::className(),[
			'sellerloginid'=>'shop_to'
		]);
		return $e->one()->sellerloginid;
	}

	protected function getLazadaShopName(){
		$e = $this->hasOne(SaasLazadaUser::className(),[
			'lazada_uid'=>'shop_to'
		]);
		return $e->one()->lazada_site;
	}
 

}