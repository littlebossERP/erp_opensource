<?php

namespace eagle\modules\order\models;

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
class OdOrderShipped extends \eagle\models\OdOrderShipped
{
	static public $status = array(
			0=>'未标记',
			1=>'标记成功',
			2=>'标记失败',
			3=>'等待平台处理结果',
			4=>'标记假发货',
	);
    public function behaviors(){
        return array(
                'SerializeBehavior' => array(
                        'class' => SerializeBehavior::className(),
                        'serialAttributes' => array('return_no'),
                )
        );
    }
}
