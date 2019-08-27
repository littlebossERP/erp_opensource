<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "sys_carrier".
 *
 * @property string $carrier_code
 * @property string $carrier_name
 * @property string $create_time
 * @property string $update_time
 * @property integer $carrier_type
 * @property string $api_class
 * @property string $address_list
 */
class SysCarrier extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_carrier';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['carrier_code'], 'required'],
            [['create_time', 'update_time', 'carrier_type'], 'integer'],
            [['carrier_code'], 'string', 'max' => 50],
            [['carrier_name', 'api_class'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'carrier_code' => 'Carrier Code',
            'carrier_name' => 'Carrier Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'carrier_type' => 'Carrier Type',
            'api_class' => 'Api Class',
            'address_list' => 'Address List',
        ];
    }
}
