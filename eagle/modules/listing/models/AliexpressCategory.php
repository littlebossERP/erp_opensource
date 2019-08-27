<?php namespace eagle\modules\listing\models;

use eagle\modules\listing\helpers\AlipressApiHelper;

class AliexpressCategory extends \eagle\models\AliexpressCategory
{
	private $_online = false;
	public $_attr;

	function online(){
		$this->_online = true;
		return $this;
	}

	function getAttr(){
		if(!$this->_attr){
			$this->_attr = AlipressApiHelper::getCartInfo($this->cateid,$this->_online,'');
		}
		return $this->_attr;
	}


	function getBrands(){
		$brands = [];
		foreach($this->getAttr() as $attr){
			if($attr['name_zh']=='品牌'){
				return json_decode(json_encode($attr['values']));
			}
		}
		return [];
	}

}