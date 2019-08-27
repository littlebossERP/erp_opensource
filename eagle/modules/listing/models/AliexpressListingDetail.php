<?php namespace eagle\modules\listing\models;

use eagle\modules\listing\models\AliexpressListing;
use eagle\modules\listing\helpers\AliexpressHelper;
use eagle\modules\listing\helpers\AlipressApiHelper;

class AliexpressListingDetail extends \eagle\models\listing\AliexpressListingDetail
{
	// public $product;

	function afterFind(){
		parent::afterFind();
		if($this->product->edit_status == 2){
			$this->attributes = $this->product->_attr['detail'];
            // $data = RedisHelper::hGet($this->k(),$this->productid);
		}
	}

	function getProduct(){
		return $this->hasOne(AliexpressListing::className(),[
			'id'=>'listen_id'
		]);
	}

	function beforeSave($insert){
        if(parent::beforeSave($insert)){
        	return true;
    	}else{
    		return false;
    	}
	}

	function getGroup(){
		return $this->hasOne(AliexpressGroupInfo::className(),[
			'group_id'=>'product_groups'
		]);
	}

	function getGroups(){
		return AliexpressGroupInfo::find()->where([
			'IN','group_id',explode(',',$this->product_groups)
		])->all();
	}

	function getCategory(){
		// name_zh
		return $this->hasOne(AliexpressCategory::className(),[
			'cateid'=>'categoryid'
		]);
	}

  	function getGroupName(){
      return AliexpressHelper::GetProductGroupsName($this->product->selleruserid,explode(',',$this->product_groups)); 
    }

    function getCategoryName(){
    	return AliexpressHelper::GetCategoryDetail($this->categoryid);
    }

    function getDetailInfo(){
    	return AliexpressHelper::_getProductDetail($this->detail);
    }

    function getCategoryAttr(){
    	return AlipressApiHelper::getCartInfo($this->categoryid);
    }

    function getProductUnit(){
    	return AlipressApiHelper::getProductUnit();
    }

	function setBrand($val){
		$attr = json_decode($this->aeopAeProductPropertys);
		foreach($attr as $item){
			if(isset($item->attrNameId) && $item->attrNameId==2){
				$item->attrValueId = $val;
			}
		}
		$this->aeopAeProductPropertys = json_encode($attr);
	}

}