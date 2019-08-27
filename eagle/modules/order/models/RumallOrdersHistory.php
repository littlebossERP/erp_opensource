<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "rumall_orders_history".
 *
 * @property string $id
 * @property string $store_name
 * @property string $orderId
 * @property string $order_detail
 */
class RumallOrdersHistory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'rumall_orders_history';
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
            [['store_name', 'orderId', 'order_detail'], 'required'],
            [['order_detail'], 'string'],
            [['store_name'], 'string', 'max' => 255],
            [['orderId'], 'string', 'max' => 55]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'store_name' => 'Store Name',
            'orderId' => 'Order ID',
            'order_detail' => 'Order Detail',
        ];
    }
}
