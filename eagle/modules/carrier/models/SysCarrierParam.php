<?php

namespace eagle\modules\carrier\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "sys_carrier_param".
 *
 * @property string $id
 * @property string $carrier_code
 * @property string $carrier_param_key
 * @property string $carrier_param_name
 * @property string $carrier_param_value
 * @property string $display_type
 * @property string $create_time
 * @property string $update_time
 * @property integer $type
 */
class SysCarrierParam extends \eagle\models\carrier\SysCarrierParam
{
   
    
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('carrier_param_value'),
    			)
    	);
    }
}
