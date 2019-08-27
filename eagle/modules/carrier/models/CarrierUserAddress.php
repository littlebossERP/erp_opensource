<?php

namespace eagle\modules\carrier\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "carrier_user_address".
 *
 * @property integer $id
 * @property string $carrier_code
 * @property integer $type
 * @property string $address_name
 * @property integer $is_del
 * @property integer $is_default
 * @property string $address_params
 */
class CarrierUserAddress extends \eagle\models\carrier\CarrierUserAddress {
	public function behaviors(){
		return array(
				'SerializeBehavior' => array(
						'class' => SerializeBehavior::className(),
						'serialAttributes' => array('address_params'),
				)
		);
	}
}

?>