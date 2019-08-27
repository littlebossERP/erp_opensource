<?php

namespace eagle\modules\carrier\models;

use Yii;
use yii\behaviors\SerializeBehavior;

class SysCarrierCustom extends \eagle\models\carrier\SysCarrierCustom
{
	public function behaviors(){
		return array(
				'SerializeBehavior' => array(
						'class' => SerializeBehavior::className(),
						'serialAttributes' => array('address_list','excel_format'),
				)
		);
	}
}
