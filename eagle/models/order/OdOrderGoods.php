<?php

namespace eagle\models\order;

use Yii;

/**
 * This is the model class for table "od_order_goods".
 *
 * @property integer $id
 * @property string $order_source
 * @property integer $order_id
 * @property string $order_source_order_id
 * @property integer $order_item_id
 * @property string $order_item_sku
 * @property integer $order_item_quantity
 * @property string $sku
 * @property integer $quantity
 * @property string $price
 * @property string $sold_time
 * @property string $paid_time
 * @property string $selleruserid
 * @property string $source_buyer_user_id
 */
class OdOrderGoods extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_order_goods';
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
            [['order_id', 'order_item_id'], 'required'],
            [['order_id', 'order_item_id', 'order_item_quantity', 'quantity'], 'integer'],
            [['price'], 'number'],
            [['sold_time', 'paid_time'], 'safe'],
            [['order_source'], 'string', 'max' => 20],
            [['order_source_order_id', 'selleruserid'], 'string', 'max' => 50],
            [['order_item_sku', 'sku', 'source_buyer_user_id'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_source' => 'Order Source',
            'order_id' => 'Order ID',
            'order_source_order_id' => 'Order Source Order ID',
            'order_item_id' => 'Order Item ID',
            'order_item_sku' => 'Order Item Sku',
            'order_item_quantity' => 'Order Item Quantity',
            'sku' => 'Sku',
            'quantity' => 'Quantity',
            'price' => 'Price',
            'sold_time' => 'Sold Time',
            'paid_time' => 'Paid Time',
            'selleruserid' => 'Selleruserid',
            'source_buyer_user_id' => 'Source Buyer User ID',
        ];
    }
}
