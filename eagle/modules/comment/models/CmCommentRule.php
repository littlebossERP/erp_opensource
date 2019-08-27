<?php namespace eagle\modules\comment\models;

use eagle\models\comment\CmCommentEnable;
use common\helpers\Helper_Array;

class CmCommentRule extends \eagle\models\comment\CmCommentRule
{

	private function attrMapFunc(){
		$prefix = debug_backtrace()[1]['function'];
		foreach($this->attributes as $name=>$val){
			if(method_exists($this, $prefix.'_'.$name)){
				$this->$name = call_user_func_array([$this,$prefix.'_'.$name],[$val]);
			}
		}
	}

	public function afterFind(){
		parent::afterFind();
		$this->attrMapFunc();
	}

	public function filter(){
		$this->attrMapFunc();
		return $this;
	}

	public function save($runValidation = false, $attributeNames = NULL){
		$this->attrMapFunc();
		return call_user_func_array('parent::save',func_get_args());
	}

	protected function save_selleruseridlist($val){
		if(is_array($val)){
			return implode(',',$val);
		}else{
			return $val;
		}
	}

	protected function save_countrylist($val){
		if(is_array($val)){
			return implode(',',$val);
		}else{
			return $val;
		}
	}

	protected function beforeSave_selleruseridlist($val){
		return implode(',',$val);
	}

	/**
	 * 取出后过滤掉未开启的店铺
	 * @param  [type] $val [description]
	 * @return [type]      [description]
	 */
	protected function filter_selleruseridlist($val){
		$seller_open = CmCommentEnable::find()
			->select(['selleruserid'])
			->where([
				'enable_status'=>1
			])->all();
		return array_intersect($val,Helper_Array::getCols($seller_open,'selleruserid'));
	}

	protected function afterFind_selleruseridlist($val){
		if(!$val){
			$val = '';
		}
		return explode(',',$val);
	}

	protected function afterFind_countrylist($val){
		return explode(',',$val);
	}

	function init(){
		parent::init();
		$this->selleruseridlist = [];
		$this->countrylist = [];
	}

}