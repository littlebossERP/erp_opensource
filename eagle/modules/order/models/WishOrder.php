<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "wish_order".
 *
 * @property string $order_id
 * @property string $order_time
 * @property string $order_total
 * @property string $transaction_id
 * @property string $variant_id
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
 */
class WishOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wish_order';
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
            [['order_time', 'last_updated'], 'safe'],
            [['order_total', 'shipping_cost', 'shipping'], 'number'],
            [['street_address1', 'street_address2'], 'string'],
            [['order_id', 'transaction_id', 'variant_id', 'city', 'country', 'name', 'phone_number', 'state', 'zipcode', 'status'], 'string', 'max' => 255],
            [['buyer_id', 'tracking_number'], 'string', 'max' => 50],
            [['shipping_provider'], 'string', 'max' => 100]
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
            'variant_id' => 'Variant ID',
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
        ];
    }
}
