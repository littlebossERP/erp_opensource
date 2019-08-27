<?php namespace eagle\modules\assistant\models;

class OdOrder extends \eagle\modules\order\models\OdOrder 
{

	 public function rules(){
	 	return array_merge(parent::rules(),[
            [['isCustomServiceContacted'], 'integer']
	 	]);
	 }

	public function attributeLabels(){
		return array_merge(parent::attributeLabels(),[
		    'isCustomServiceContacted' => '是否客服联系过',
		]);
	}

	 // public function getIsCustomServiceContacted(){
	 // 	return $this->isCustomServiceContacted;
	 // }

	 // public function setIsCustomServiceContacted($v){
	 // 	$this->isCustomServiceContacted = $v;
	 // }

}