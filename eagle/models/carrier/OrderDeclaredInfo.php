<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "order_declared_info".
 *
 * @property string $id
 * @property string $platform
 * @property string $itemID
 * @property string $sku
 * @property string $ch_name
 * @property string $en_name
 * @property string $declared_value
 * @property string $declared_weight
 * @property string $detail_hs_code
 */
class OrderDeclaredInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'order_declared_info';
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
            [['declared_value', 'declared_weight'], 'number'],
            [['platform', 'detail_hs_code'], 'string', 'max' => 50],
            [['itemID', 'sku'], 'string', 'max' => 255],
            [['ch_name', 'en_name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'itemID' => 'Item ID',
            'sku' => 'Sku',
            'ch_name' => 'Ch Name',
            'en_name' => 'En Name',
            'declared_value' => 'Declared Value',
            'declared_weight' => 'Declared Weight',
            'detail_hs_code' => 'Detail Hs Code',
        ];
    }
}
