<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "od_order_item_v2".
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
 * @property string $create_time
 * @property string $update_time
 * @property string $desc
 * @property string $platform_sku
 * @property integer $is_bundle
 * @property string $bdsku
 * @property string $source_item_id
 * @property string $order_source_order_id
 * @property string $order_source_transactionid
 * @property string $order_source_itemid
 * @property string $product_attributes
 * @property string $product_unit
 * @property integer $lot_num
 * @property integer $goods_prepare_time
 * @property string $product_url
 * @property string $remark
 * @property string $purchase_price
 * @property string $purchase_price_form
 * @property string $purchase_price_to
 */
class OdOrderItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_order_item_v2';
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
            [['order_id', 'order_source_srn', 'order_source_order_item_id', 'ordered_quantity', 'quantity', 'sent_quantity', 'packed_quantity', 'returned_quantity', 'create_time', 'update_time', 'is_bundle', 'lot_num', 'goods_prepare_time'], 'integer'],
            [['shipping_price', 'shipping_discount', 'price', 'promotion_discount', 'purchase_price'], 'number'],
            [['desc'], 'string'],
            [['purchase_price_form', 'purchase_price_to'], 'safe'],
            [['sku', 'product_name', 'remark'], 'string', 'max' => 255],
            [['photo_primary', 'product_url'], 'string', 'max' => 455],
            [['invoice_requirement', 'buyer_selected_invoice_category', 'invoice_title', 'invoice_information', 'platform_sku', 'bdsku', 'source_item_id', 'order_source_order_id', 'order_source_transactionid', 'order_source_itemid', 'product_unit'], 'string', 'max' => 50],
            [['product_attributes'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_item_id' => '订单商品id',
            'order_id' => '订单号',
            'order_source_srn' => 'od_ebay_transaction表salesrecordnum',
            'order_source_order_item_id' => 'od_ebay_transaction表id或amazon的OrderItemId',
            'sku' => '商品编码',
            'product_name' => '下单时标题',
            'photo_primary' => '商品主图冗余',
            'shipping_price' => '运费',
            'shipping_discount' => '运费折扣',
            'price' => '下单时价格',
            'promotion_discount' => '促销折扣',
            'ordered_quantity' => '下单时候的数量',
            'quantity' => '需发货的商品数量',
            'sent_quantity' => '已发货数量',
            'packed_quantity' => '已打包数量',
            'returned_quantity' => '退货数量',
            'invoice_requirement' => '发票要求',
            'buyer_selected_invoice_category' => '发票种类',
            'invoice_title' => '发票抬头',
            'invoice_information' => '发票内容',
            'create_time' => '创建时间',
            'update_time' => '更新时间',
            'desc' => 'Desc',
            'platform_sku' => 'Platform Sku',
            'is_bundle' => 'Is Bundle',
            'bdsku' => 'Bdsku',
            'source_item_id' => 'Source Item ID',
            'order_source_order_id' => 'Order Source Order ID',
            'order_source_transactionid' => 'Order Source Transactionid',
            'order_source_itemid' => 'Order Source Itemid',
            'product_attributes' => 'Product Attributes',
            'product_unit' => 'Product Unit',
            'lot_num' => 'Lot Num',
            'goods_prepare_time' => 'Goods Prepare Time',
            'product_url' => 'Product Url',
            'remark' => 'Remark',
            'purchase_price' => 'Purchase Price',
            'purchase_price_form' => 'Purchase Price Form',
            'purchase_price_to' => 'Purchase Price To',
        ];
    }
}
