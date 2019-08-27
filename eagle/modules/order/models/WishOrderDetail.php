<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "wish_order_detail".
 *
 * @property integer $id
 * @property string $product_id
 * @property integer $quantity
 * @property string $price
 * @property string $cost
 * @property string $shipping
 * @property string $shipping_cost
 * @property string $product_name
 * @property string $product_image_url
 * @property integer $days_to_fulfill
 * @property string $sku
 * @property string $size
 * @property string $order_id
 */
class WishOrderDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wish_order_detail';
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
            [['quantity', 'days_to_fulfill'], 'integer'],
            [['price', 'cost', 'shipping', 'shipping_cost'], 'number'],
            [['product_image_url'], 'string'],
            [['order_id'], 'required'],
            [['product_id', 'product_name', 'sku', 'size', 'order_id'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'quantity' => 'Quantity',
            'price' => 'Price',
            'cost' => 'Cost',
            'shipping' => 'Shipping',
            'shipping_cost' => 'Shipping Cost',
            'product_name' => 'Product Name',
            'product_image_url' => 'Product Image Url',
            'days_to_fulfill' => 'Days To Fulfill',
            'sku' => 'Sku',
            'size' => 'Size',
            'order_id' => 'Order ID',
        ];
    }
}
