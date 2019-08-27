<?php

namespace eagle\models\carrier;

use Yii;

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
class CarrierUserAddress extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'carrier_user_address';
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
            [['type', 'is_del', 'is_default'], 'integer'],
            [['address_params'], 'string'],
            [['carrier_code'], 'string', 'max' => 50],
            [['address_name'], 'string', 'max' => 100]
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
            'type' => 'Type',
            'address_name' => 'Address Name',
            'is_del' => 'Is Del',
            'is_default' => 'Is Default',
            'address_params' => 'Address Params',
        ];
    }
}
