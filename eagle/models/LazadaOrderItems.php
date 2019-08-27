<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lazada_order_items".
 *
 * @property integer $id
 * @property string $OrderItemId
 * @property string $ShopId
 * @property string $OrderId
 * @property string $Name
 * @property string $Sku
 * @property string $ShopSku
 * @property string $ShippingType
 * @property string $ItemPrice
 * @property string $PaidPrice
 * @property string $Currency
 * @property string $WalletCredits
 * @property string $TaxAmount
 * @property string $ShippingAmount
 * @property string $VoucherAmount
 * @property string $VoucherCode
 * @property string $Status
 * @property string $ShipmentProvider
 * @property string $TrackingCode
 * @property string $Reason
 * @property string $PurchaseOrderId
 * @property string $PurchaseOrderNumber
 * @property string $PackageId
 * @property integer $CreatedAt
 * @property integer $UpdatedAt
 * @property string $SmallImageUrl
 */
class LazadaOrderItems extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_order_items';
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
            [['OrderItemId', 'ShopId', 'OrderId', 'Name', 'Sku', 'ShopSku', 'ShippingType', 'Currency', 'Status', 'CreatedAt', 'UpdatedAt'], 'required'],
            [['ItemPrice', 'PaidPrice', 'WalletCredits', 'TaxAmount', 'ShippingAmount', 'VoucherAmount'], 'number'],
            [['CreatedAt', 'UpdatedAt'], 'integer'],
            [['OrderItemId', 'ShopId', 'OrderId', 'Status', 'PurchaseOrderId', 'PurchaseOrderNumber', 'PackageId'], 'string', 'max' => 30],
            [['Name', 'VoucherCode', 'Reason'], 'string', 'max' => 255],
			[['SmallImageUrl'], 'string', 'max' => 455],
            [['Sku', 'ShopSku', 'TrackingCode'], 'string', 'max' => 100],
            [['ShippingType', 'ShipmentProvider'], 'string', 'max' => 50],
            [['Currency'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'OrderItemId' => 'Order Item ID',
            'ShopId' => 'Shop ID',
            'OrderId' => 'Order ID',
            'Name' => 'Name',
            'Sku' => 'Sku',
            'ShopSku' => 'Shop Sku',
            'ShippingType' => 'Shipping Type',
            'ItemPrice' => 'Item Price',
            'PaidPrice' => 'Paid Price',
            'Currency' => 'Currency',
            'WalletCredits' => 'Wallet Credits',
            'TaxAmount' => 'Tax Amount',
            'ShippingAmount' => 'Shipping Amount',
            'VoucherAmount' => 'Voucher Amount',
            'VoucherCode' => 'Voucher Code',
            'Status' => 'Status',
            'ShipmentProvider' => 'Shipment Provider',
            'TrackingCode' => 'Tracking Code',
            'Reason' => 'Reason',
            'PurchaseOrderId' => 'Purchase Order ID',
            'PurchaseOrderNumber' => 'Purchase Order Number',
            'PackageId' => 'Package ID',
            'CreatedAt' => 'Created At',
            'UpdatedAt' => 'Updated At',
            'SmallImageUrl' => 'Small Image Url',
        ];
    }
}
