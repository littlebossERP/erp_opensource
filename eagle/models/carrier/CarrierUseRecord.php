<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "carrier_use_record".
 *
 * @property integer $id
 * @property string $carrier_code
 * @property integer $is_active
 * @property string $create_time
 * @property string $update_time
 * @property integer $is_del
 */
class CarrierUseRecord extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'carrier_use_record';
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
            [['is_active', 'create_time', 'update_time', 'is_del'], 'integer'],
            [['carrier_code'], 'string', 'max' => 50]
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
            'is_active' => 'Is Active',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'is_del' => 'Is Del',
        ];
    }
}
