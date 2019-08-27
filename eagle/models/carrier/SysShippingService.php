<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "sys_shipping_service".
 *
 * @property string $id
 * @property string $carrier_code
 * @property string $carrier_params
 * @property string $ship_address
 * @property string $return_address
 * @property integer $is_used
 * @property string $service_name
 * @property string $service_code
 * @property integer $auto_ship
 * @property string $web
 * @property string $create_time
 * @property string $update_time
 * @property integer $carrier_account_id
 * @property string $extra_carrier
 */
class SysShippingService extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_shipping_service';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['is_used', 'auto_ship', 'create_time', 'update_time', 'carrier_account_id'], 'integer'],
        	[['is_used'], 'required','message'=>'请选择状态！'],
            //[['carrier_account_id'], 'required','message'=>'请选择物流账号！'],
        	[['service_name'], 'required','message'=>'请填写运输服务名！'],
        	[['carrier_code'], 'required','message'=>'请选择物流商！'],
            [['carrier_code'], 'string', 'max' => 100],
        	[['service_name'], 'string', 'max' => 255],
            [['ship_address', 'return_address'], 'string', 'max' => 500],
            [['web'], 'string', 'max' => 60],
        	[['web'], 'required','message'=>'请填写查询网址！'],
            [['extra_carrier'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'carrier_code' => 'Carrier Code',
            'carrier_params' => 'Carrier Params',
            'ship_address' => 'Ship Address',
            'return_address' => 'Return Address',
            'is_used' => 'Is Used',
            'service_name' => 'Service Name',
            'service_code' => 'Service Code',
            'auto_ship' => 'Auto Ship',
            'web' => 'Web',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'carrier_account_id' => 'Carrier Account ID',
            'extra_carrier' => 'Extra Carrier',
        ];
    }
}
