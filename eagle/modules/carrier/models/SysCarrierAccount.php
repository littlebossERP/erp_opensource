<?php

namespace eagle\modules\carrier\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "sys_carrier_account".
 *
 * @property string $id
 * @property string $carrier_code
 * @property string $carrier_name
 * @property integer $carrier_type
 * @property string $api_params
 * @property string $create_time
 * @property string $update_time
 * @property string $user_id
 * @property integer $is_used
 * @property string $user_account
 */
class SysCarrierAccount extends \eagle\models\carrier\SysCarrierAccount
{
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('address','api_params','warehouse'),
    			)
    	);
    }
}
