<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "ensogo_order".
 *
 * @property string $order_id
 * @property string $order_time
 * @property string $order_total
 * @property string $transaction_id
 * @property string $city
 * @property string $country
 * @property string $name
 * @property string $phone_number
 * @property string $state
 * @property string $street_address1
 * @property string $street_address2
 * @property string $zipcode
 * @property string $last_updated
 * @property string $shipping_cost
 * @property string $shipping
 * @property string $status
 * @property string $buyer_id
 * @property string $shipping_provider
 * @property string $tracking_number
 * @property string $shipped_date
 * @property string $ship_note
 * @property string $product_id
 * @property string $variant_id
 * @property string $variant_name
 * @property integer $quantity
 * @property string $price
 * @property string $cost
 * @property string $product_name
 * @property string $product_image_url
 * @property integer $days_to_fulfill
 * @property integer $hours_to_fulfill
 * @property string $sku
 * @property string $size
 * @property string $refunded_by
 * @property string $refunded_time
 * @property string $refund_reason
 * @property string $website
 */
class EnsogoOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ensogo_order';
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
            [['order_id'], 'required'],
            [['order_time', 'last_updated', 'shipped_date', 'refunded_time'], 'safe'],
            [['order_total', 'shipping_cost', 'shipping', 'price', 'cost'], 'number'],
            [['street_address1', 'street_address2', 'ship_note', 'product_image_url'], 'string'],
            [['quantity', 'days_to_fulfill', 'hours_to_fulfill'], 'integer'],
            [['order_id', 'transaction_id', 'city', 'country', 'name', 'phone_number', 'state', 'zipcode', 'status', 'product_id', 'variant_id', 'variant_name', 'product_name', 'sku', 'size', 'refunded_by', 'refund_reason'], 'string', 'max' => 255],
            [['buyer_id', 'tracking_number'], 'string', 'max' => 50],
            [['shipping_provider'], 'string', 'max' => 100],
            [['website'], 'string', 'max' => 2]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'order_time' => 'Order Time',
            'order_total' => 'Order Total',
            'transaction_id' => 'Transaction ID',
            'city' => 'City',
            'country' => 'Country',
            'name' => 'Name',
            'phone_number' => 'Phone Number',
            'state' => 'State',
            'street_address1' => 'Street Address1',
            'street_address2' => 'Street Address2',
            'zipcode' => 'Zipcode',
            'last_updated' => 'Last Updated',
            'shipping_cost' => 'Shipping Cost',
            'shipping' => 'Shipping',
            'status' => 'Status',
            'buyer_id' => 'Buyer ID',
            'shipping_provider' => 'Shipping Provider',
            'tracking_number' => 'Tracking Number',
            'shipped_date' => 'Shipped Date',
            'ship_note' => 'Ship Note',
            'product_id' => 'Product ID',
            'variant_id' => 'Variant ID',
            'variant_name' => 'Variant Name',
            'quantity' => 'Quantity',
            'price' => 'Price',
            'cost' => 'Cost',
            'product_name' => 'Product Name',
            'product_image_url' => 'Product Image Url',
            'days_to_fulfill' => 'Days To Fulfill',
            'hours_to_fulfill' => 'Hours To Fulfill',
            'sku' => 'Sku',
            'size' => 'Size',
            'refunded_by' => 'Refunded By',
            'refunded_time' => 'Refunded Time',
            'refund_reason' => 'Refund Reason',
            'website' => 'Website',
        ];
    }
}
