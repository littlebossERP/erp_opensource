<?php

namespace eagle\modules\delivery\models;

use Yii;

/**
 * This is the model class for table "od_delivery_order".
 *
 * @property integer $id
 * @property string $delivery_id
 * @property integer $order_id
 * @property string $sku
 * @property integer $count
 */
class OdDeliveryOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_delivery_order';
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
            [['delivery_id', 'order_id'], 'required'],
            [['delivery_id', 'order_id', 'count'], 'integer'],
            [['sku'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'delivery_id' => 'Delivery ID',
            'order_id' => 'Order ID',
            'sku' => 'Sku',
            'count' => 'Count',
        ];
    }
}
