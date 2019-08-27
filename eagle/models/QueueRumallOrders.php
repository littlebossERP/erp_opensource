<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_rumall_orders".
 *
 * @property string $id
 * @property string $orderId
 * @property integer $uid
 * @property string $store_name
 */
class QueueRumallOrders extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_rumall_orders';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['orderId', 'uid', 'store_name'], 'required'],
            [['uid'], 'integer'],
            [['orderId'], 'string', 'max' => 255],
            [['store_name'], 'string', 'max' => 55]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orderId' => 'Order ID',
            'uid' => 'Uid',
            'store_name' => 'Store Name',
        ];
    }
}
