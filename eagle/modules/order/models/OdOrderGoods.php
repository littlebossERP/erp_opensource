<?php
namespace eagle\modules\order\models;
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
class OdOrderGoods extends \eagle\models\order\OdOrderGoods
{
	/* public static $a = array();
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => [''],
    			)
    	);
    } */
}