<?php

namespace eagle\models\carrier;

use Yii;

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
class SysCarrierParam extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_carrier_param';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time', 'update_time', 'type'], 'integer'],
            [['carrier_code', 'carrier_param_key'], 'string', 'max' => 50],
            [['carrier_param_name'], 'string', 'max' => 100],
            [['display_type'], 'string', 'max' => 15]
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
            'carrier_param_key' => 'Carrier Param Key',
            'carrier_param_name' => 'Carrier Param Name',
            'carrier_param_value' => 'Carrier Param Value',
            'display_type' => 'Display Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'type' => 'Type',
            'is_required' => 'Is Required',
        ];
    }
}
