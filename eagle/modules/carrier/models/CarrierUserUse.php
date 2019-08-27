<?php

namespace crm\models;

use Yii;

/**
 * This is the model class for table "carrier_user_use".
 *
 * @property string $id
 * @property string $puid
 * @property string $carrier_code
 * @property string $carrier_account_id
 * @property string $param1
 * @property string $param2
 * @property string $param3
 * @property string $additional_info
 * @property integer $is_used
 */
class CarrierUserUse extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'carrier_user_use';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid'], 'required'],
            [['puid', 'carrier_account_id', 'is_used'], 'integer'],
            [['additional_info'], 'string'],
            [['carrier_code'], 'string', 'max' => 50],
            [['param1', 'param2', 'param3'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'carrier_code' => 'Carrier Code',
            'carrier_account_id' => 'Carrier Account ID',
            'param1' => 'Param1',
            'param2' => 'Param2',
            'param3' => 'Param3',
            'additional_info' => 'Additional Info',
            'is_used' => 'Is Used',
        ];
    }
}
