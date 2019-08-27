<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "amz_order_detail".
 *
 * @property string $AmazonOrderId
 * @property string $OrderItemId
 * @property string $SellerSKU
 * @property string $ASIN
 * @property string $Title
 * @property string $Amount
 * @property integer $QuantityOrdered
 * @property string $QuantityShipped
 * @property string $ItemPrice
 * @property string $ShippingPrice
 * @property string $ShippingDiscount
 * @property string $ShippingTax
 * @property string $GiftWrapPrice
 * @property string $GiftWrapTax
 * @property string $ItemTax
 * @property string $PromotionDiscount
 * @property string $GiftMessageText
 * @property string $GiftWrapLevel
 * @property string $PromotionIds
 * @property string $CODFee
 * @property string $CODFeeDiscount
 * @property string $InvoiceRequirement
 * @property string $BuyerSelectedInvoiceCategory
 * @property string $InvoiceTitle
 * @property string $InvoiceInformation
 */
class AmzOrderDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amz_order_detail';
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
            [['OrderItemId'], 'required'],
            [['Amount', 'QuantityShipped', 'ItemPrice', 'ShippingPrice', 'ShippingDiscount', 'ShippingTax', 'GiftWrapPrice', 'GiftWrapTax', 'ItemTax', 'PromotionDiscount', 'CODFee', 'CODFeeDiscount'], 'number'],
            [['QuantityOrdered'], 'integer'],
            [['AmazonOrderId', 'SellerSKU'], 'string', 'max' => 45],
            [['OrderItemId'], 'string', 'max' => 50],
            [['ASIN', 'GiftMessageText', 'GiftWrapLevel', 'PromotionIds', 'InvoiceRequirement', 'BuyerSelectedInvoiceCategory', 'InvoiceTitle', 'InvoiceInformation'], 'string', 'max' => 255],
            [['Title'], 'string', 'max' => 501]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'AmazonOrderId' => 'Amazon Order ID',
            'OrderItemId' => 'Order Item ID',
            'SellerSKU' => 'Seller Sku',
            'ASIN' => 'Asin',
            'Title' => 'Title',
            'Amount' => 'Amount',
            'QuantityOrdered' => 'Quantity Ordered',
            'QuantityShipped' => 'Quantity Shipped',
            'ItemPrice' => 'Item Price',
            'ShippingPrice' => 'Shipping Price',
            'ShippingDiscount' => 'Shipping Discount',
            'ShippingTax' => 'Shipping Tax',
            'GiftWrapPrice' => 'Gift Wrap Price',
            'GiftWrapTax' => 'Gift Wrap Tax',
            'ItemTax' => 'Item Tax',
            'PromotionDiscount' => 'Promotion Discount',
            'GiftMessageText' => 'Gift Message Text',
            'GiftWrapLevel' => 'Gift Wrap Level',
            'PromotionIds' => 'Promotion Ids',
            'CODFee' => 'Codfee',
            'CODFeeDiscount' => 'Codfee Discount',
            'InvoiceRequirement' => 'Invoice Requirement',
            'BuyerSelectedInvoiceCategory' => 'Buyer Selected Invoice Category',
            'InvoiceTitle' => 'Invoice Title',
            'InvoiceInformation' => 'Invoice Information',
        ];
    }
}
