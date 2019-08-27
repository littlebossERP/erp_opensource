<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "od_order_item".
 *
 * @property string $order_item_id
 * @property string $order_id
 * @property string $order_source_srn
 * @property string $order_source_order_item_id
 * @property string $sku
 * @property string $product_name
 * @property string $photo_primary
 * @property string $shipping_price
 * @property string $shipping_discount
 * @property string $price
 * @property string $promotion_discount
 * @property string $ordered_quantity
 * @property string $quantity
 * @property string $sent_quantity
 * @property string $packed_quantity
 * @property string $returned_quantity
 * @property string $invoice_requirement
 * @property string $buyer_selected_invoice_category
 * @property string $invoice_title
 * @property string $invoice_information
 * @property string $remark
 * @property string $create_time
 * @property string $update_time
 * @property string $source_item_id
 * @property string $platform_sku
 * @property integer $is_bundle
 * @property string $bdsku
 */
class OdOrderItem2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_order_item';
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
            [['order_id', 'order_source_srn', 'ordered_quantity', 'quantity', 'sent_quantity', 'packed_quantity', 'returned_quantity', 'create_time', 'update_time', 'is_bundle'], 'integer'],
            [['shipping_price', 'shipping_discount', 'price', 'promotion_discount'], 'number'],
            [['order_source_order_item_id', 'invoice_requirement', 'buyer_selected_invoice_category', 'invoice_title', 'invoice_information', 'source_item_id', 'platform_sku', 'bdsku'], 'string', 'max' => 50],
            [['sku'], 'string', 'max' => 30],
            [['product_name', 'photo_primary', 'remark'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_item_id' => 'Order Item ID',
            'order_id' => 'Order ID',
            'order_source_srn' => 'Order Source Srn',
            'order_source_order_item_id' => 'Order Source Order Item ID',
            'sku' => 'Sku',
            'product_name' => 'Product Name',
            'photo_primary' => 'Photo Primary',
            'shipping_price' => 'Shipping Price',
            'shipping_discount' => 'Shipping Discount',
            'price' => 'Price',
            'promotion_discount' => 'Promotion Discount',
            'ordered_quantity' => 'Ordered Quantity',
            'quantity' => 'Quantity',
            'sent_quantity' => 'Sent Quantity',
            'packed_quantity' => 'Packed Quantity',
            'returned_quantity' => 'Returned Quantity',
            'invoice_requirement' => 'Invoice Requirement',
            'buyer_selected_invoice_category' => 'Buyer Selected Invoice Category',
            'invoice_title' => 'Invoice Title',
            'invoice_information' => 'Invoice Information',
            'remark' => 'Remark',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'source_item_id' => 'Source Item ID',
            'platform_sku' => 'Platform Sku',
            'is_bundle' => 'Is Bundle',
            'bdsku' => 'Bdsku',
        ];
    }
}
