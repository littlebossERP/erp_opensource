<?php
namespace eagle\modules\carrier\models;
use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "sys_carrier".
 *
 * @property string $carrier_code
 * @property string $carrier_name
 * @property string $create_time
 * @property string $update_time
 * @property integer $carrier_type
 * @property string $api_class
 */
class SysCarrier extends \eagle\models\carrier\SysCarrier
{
	public static $carrier_type = array('0'=>'货代','1'=>'海外仓');
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => ['address_list'],
    			)
    	);
    }
}
