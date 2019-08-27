<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "od_order".
 *
 * @property string $order_id
 * @property integer $order_status
 * @property string $order_manual_id
 * @property integer $pay_status
 * @property integer $shipping_status
 * @property integer $is_manual_order
 * @property string $order_source
 * @property string $order_type
 * @property string $order_source_order_id
 * @property string $order_source_site_id
 * @property string $selleruserid
 * @property string $saas_platform_user_id
 * @property string $order_source_srn
 * @property string $customer_id
 * @property string $source_buyer_user_id
 * @property string $order_source_shipping_method
 * @property string $order_source_create_time
 * @property string $subtotal
 * @property string $shipping_cost
 * @property string $discount_amount
 * @property string $grand_total
 * @property string $returned_total
 * @property string $price_adjustment
 * @property string $currency
 * @property string $consignee
 * @property string $consignee_postal_code
 * @property string $consignee_phone
 * @property string $consignee_email
 * @property string $consignee_company
 * @property string $consignee_country
 * @property string $consignee_country_code
 * @property string $consignee_city
 * @property string $consignee_province
 * @property string $consignee_district
 * @property string $consignee_county
 * @property string $consignee_address_line1
 * @property string $consignee_address_line2
 * @property string $consignee_address_line3
 * @property integer $default_warehouse_id
 * @property string $default_carrier_code
 * @property string $default_shipping_method_code
 * @property string $paid_time
 * @property string $delivery_time
 * @property string $create_time
 * @property integer $update_time
 * @property string $user_message
 * @property integer $carrier_type
 * @property integer $is_feedback
 */
class OdOrder2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_order';
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
            [['order_status', 'order_manual_id', 'pay_status', 'shipping_status', 'is_manual_order', 'saas_platform_user_id', 'order_source_srn', 'customer_id', 'order_source_create_time', 'default_warehouse_id', 'paid_time', 'delivery_time', 'create_time', 'update_time', 'carrier_type', 'is_feedback'], 'integer'],
            [['subtotal', 'shipping_cost', 'discount_amount', 'grand_total', 'returned_total', 'price_adjustment'], 'number'],
            [['user_message'], 'required'],
            [['user_message'], 'string'],
            [['order_source', 'order_type', 'order_source_order_id', 'order_source_site_id', 'selleruserid', 'source_buyer_user_id', 'order_source_shipping_method', 'consignee', 'consignee_postal_code', 'consignee_phone', 'consignee_email', 'consignee_company', 'consignee_country', 'consignee_city', 'consignee_province', 'consignee_district', 'consignee_county', 'default_carrier_code', 'default_shipping_method_code'], 'string', 'max' => 50],
            [['currency'], 'string', 'max' => 3],
            [['consignee_country_code'], 'string', 'max' => 2],
            [['consignee_address_line1', 'consignee_address_line2', 'consignee_address_line3'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'order_status' => 'Order Status',
            'order_manual_id' => 'Order Manual ID',
            'pay_status' => 'Pay Status',
            'shipping_status' => 'Shipping Status',
            'is_manual_order' => 'Is Manual Order',
            'order_source' => 'Order Source',
            'order_type' => 'Order Type',
            'order_source_order_id' => 'Order Source Order ID',
            'order_source_site_id' => 'Order Source Site ID',
            'selleruserid' => 'Selleruserid',
            'saas_platform_user_id' => 'Saas Platform User ID',
            'order_source_srn' => 'Order Source Srn',
            'customer_id' => 'Customer ID',
            'source_buyer_user_id' => 'Source Buyer User ID',
            'order_source_shipping_method' => 'Order Source Shipping Method',
            'order_source_create_time' => 'Order Source Create Time',
            'subtotal' => 'Subtotal',
            'shipping_cost' => 'Shipping Cost',
            'discount_amount' => 'Discount Amount',
            'grand_total' => 'Grand Total',
            'returned_total' => 'Returned Total',
            'price_adjustment' => 'Price Adjustment',
            'currency' => 'Currency',
            'consignee' => 'Consignee',
            'consignee_postal_code' => 'Consignee Postal Code',
            'consignee_phone' => 'Consignee Phone',
            'consignee_email' => 'Consignee Email',
            'consignee_company' => 'Consignee Company',
            'consignee_country' => 'Consignee Country',
            'consignee_country_code' => 'Consignee Country Code',
            'consignee_city' => 'Consignee City',
            'consignee_province' => 'Consignee Province',
            'consignee_district' => 'Consignee District',
            'consignee_county' => 'Consignee County',
            'consignee_address_line1' => 'Consignee Address Line1',
            'consignee_address_line2' => 'Consignee Address Line2',
            'consignee_address_line3' => 'Consignee Address Line3',
            'default_warehouse_id' => 'Default Warehouse ID',
            'default_carrier_code' => 'Default Carrier Code',
            'default_shipping_method_code' => 'Default Shipping Method Code',
            'paid_time' => 'Paid Time',
            'delivery_time' => 'Delivery Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'user_message' => 'User Message',
            'carrier_type' => 'Carrier Type',
            'is_feedback' => 'Is Feedback',
        ];
    }
}
