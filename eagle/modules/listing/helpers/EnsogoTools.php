<?php namespace eagle\modules\listing\helpers;

use eagle\modules\listing\service\ensogo\Product;
use eagle\modules\listing\models\EnsogoProduct;
use eagle\models\SaasEnsogoUser;
use eagle\modules\listing\models\EnsogoProxy;

class EnsogoTools 
{
	static function changeDb($site_id){
		 
		return true;
	}

	static function getProductsFromPlatform($site_id,$timeStamp=NULL){
		// var_dump(\Yii::$app->params['currentEnv']);die;
		$product = new Product($site_id);
		$lists = $product->getProductsFromPlatform();
		return $lists;
	}

	static function getProductFromPlatform($site_id,$parent_sku){
		// var_dump(\Yii::$app->params['currentEnv']);
		self::changeDb($site_id);
		$product = new Product($site_id);
		$p = EnsogoProduct::find()->where([
			'parent_sku'=>$parent_sku
		])->one();
		return $product->getProductFromPlatform($p->ensogo_product_id);
	}

	// 刷新refresh_token
	static function refreshUserInfo($site_id){
		$user = SaasEnsogoUser::findOne($site_id);
		if(!$user->seller_id){
			echo 'seller_id not found';
		}
		$user = EnsogoProxy::getInstance($site_id)->refreshUser();
		return $user;
	}


	static function syncProductLists($site_id,$parent_sku,$product_id,$id){
		 
		$user = SaasEnsogoUser::findOne($site_id);
		 
		var_dump('uid:'.$user->uid);
		// 获取商品信息
		$product = new Product($site_id);
		if(!$product_id){
			if(!$p = EnsogoProduct::find()->where([
				'site_id'=>$site_id
			])->andWhere($id?['id'=>$id]:['parent_sku'=>$parent_sku])->one()){
				return 'null';
			}
			$product_id = $p->ensogo_product_id;
		}
		$info = $product->getProductFromPlatform($product_id);

		try{
			return $product->save($info);
		}catch(\Exception $e){
			return $e->getMessage();
		}

	}



	static function syncProduct($parent_sku){
		$product = EnsogoProduct::find()->where([
			'parent_sku'=>$parent_sku
		])->andWhere(['<>','ensogo_product_id','""'])->one();
		try{
			$data = $product->getOnline();
		}catch(\Exception $e){
			echo $e->getMessage();die;
		}
		$p = new Product($product->site_id);
		return $p->save($data);
	}

	static function removeProduct($site_id,$parent_sku,$id){
		 
		$user = SaasEnsogoUser::findOne($site_id);
		 
		var_dump('uid:'.$user->uid);
		// 获取商品信息
		$product = new Product($site_id);
		if(!$p = EnsogoProduct::find()->where([
			'site_id'=>$site_id
		])->andWhere($id?['id'=>$id]:['parent_sku'=>$parent_sku])->one()){
			return 'null';
		}
		return $p->delete();
	}

	static function refreshToken($site_id){
		EnsogoProxy::getInstance($site_id)->refreshToken();
		return SaasEnsogoUser::findOne($site_id)->token;
	}


	/**
	 * 发布商品
	 * @return [type] [description]
	 */
	static function pushProduct($parent_sku){
		$product = EnsogoProduct::find()->where(['parent_sku'=>$parent_sku])->one();
		$Product = new Product($product->site_id);
		return $Product->push($product);
	}


}