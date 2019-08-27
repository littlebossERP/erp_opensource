<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "rumall_order".
 *
 * @property string $id
 * @property string $Remark
 * @property string $SfOrderType
 * @property string $ErpOrder
 * @property string $OrderNote
 * @property string $PackageNote
 * @property string $TradeOrderDateTime
 * @property string $PayDateTime
 * @property string $CurrencyCode
 * @property string $CompanyNote
 * @property string $ShopName
 * @property string $TradePlatform
 * @property string $TradeOrder
 * @property string $BuyerId
 * @property string $DeliveryDate
 * @property string $PaymentMethod
 * @property string $PaymentNumber
 * @property string $Freight
 * @property string $DiscountRate
 * @property string $OrderTotalAmount
 * @property string $OrderDiscount
 * @property string $OtherCharge
 * @property string $balance_amount
 * @property string $is_appoint_delivery
 * @property string $appoint_delivery_status
 * @property string $appoint_delivery_remark
 * @property string $order_url
 * @property string $order_website
 * @property string $ActualAmount
 * @property string $DeliveryModel
 * @property string $IsAllowSplit
 * @property string $ReceiverCompany
 * @property string $ReceiverName
 * @property string $ReceiverEmail
 * @property string $ReceiverZipCode
 * @property string $ReceiverMobile
 * @property string $ReceiverPhone
 * @property string $CountryCode
 * @property string $ConsigneeFullname
 * @property string $ReceiverCountry
 * @property string $ReceiverProvince
 * @property string $ReceiverCity
 * @property string $ReceiverArea
 * @property string $ReceiverAddress
 * @property string $ConsigneeStreet
 * @property string $ConsigneeDoorplate
 * @property string $ReceiverIdType
 * @property string $ReceiverIdCard
 * @property string $OrderSenderInfo
 * @property string $order_detail
 * @property string $addinfo
 */
class RumallOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'rumall_order';
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
            [['ErpOrder', 'CurrencyCode', 'ShopName', 'BuyerId'], 'required'],
            [['TradeOrderDateTime', 'PayDateTime', 'DeliveryDate'], 'safe'],
            [['Freight', 'DiscountRate', 'OrderTotalAmount', 'OrderDiscount', 'OtherCharge', 'balance_amount', 'ActualAmount'], 'number'],
            [['OrderSenderInfo', 'order_detail', 'addinfo'], 'string'],
            [['Remark', 'ErpOrder', 'OrderNote', 'PackageNote', 'CompanyNote', 'TradeOrder', 'appoint_delivery_remark', 'order_url', 'order_website', 'ReceiverCompany', 'ReceiverName', 'ReceiverEmail', 'ConsigneeFullname', 'ReceiverCountry', 'ReceiverProvince', 'ReceiverCity', 'ReceiverArea', 'ReceiverAddress', 'ConsigneeStreet', 'ConsigneeDoorplate', 'ReceiverIdType', 'ReceiverIdCard'], 'string', 'max' => 255],
            [['SfOrderType', 'DeliveryModel'], 'string', 'max' => 50],
            [['CurrencyCode'], 'string', 'max' => 20],
            [['ShopName', 'TradePlatform', 'BuyerId', 'PaymentMethod', 'PaymentNumber', 'appoint_delivery_status', 'ReceiverZipCode', 'ReceiverMobile', 'ReceiverPhone'], 'string', 'max' => 55],
            [['is_appoint_delivery', 'IsAllowSplit'], 'string', 'max' => 2],
            [['CountryCode'], 'string', 'max' => 5]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'Remark' => 'Remark',
            'SfOrderType' => 'Sf Order Type',
            'ErpOrder' => 'Erp Order',
            'OrderNote' => 'Order Note',
            'PackageNote' => 'Package Note',
            'TradeOrderDateTime' => 'Trade Order Date Time',
            'PayDateTime' => 'Pay Date Time',
            'CurrencyCode' => 'Currency Code',
            'CompanyNote' => 'Company Note',
            'ShopName' => 'Shop Name',
            'TradePlatform' => 'Trade Platform',
            'TradeOrder' => 'Trade Order',
            'BuyerId' => 'Buyer ID',
            'DeliveryDate' => 'Delivery Date',
            'PaymentMethod' => 'Payment Method',
            'PaymentNumber' => 'Payment Number',
            'Freight' => 'Freight',
            'DiscountRate' => 'Discount Rate',
            'OrderTotalAmount' => 'Order Total Amount',
            'OrderDiscount' => 'Order Discount',
            'OtherCharge' => 'Other Charge',
            'balance_amount' => 'Balance Amount',
            'is_appoint_delivery' => 'Is Appoint Delivery',
            'appoint_delivery_status' => 'Appoint Delivery Status',
            'appoint_delivery_remark' => 'Appoint Delivery Remark',
            'order_url' => 'Order Url',
            'order_website' => 'Order Website',
            'ActualAmount' => 'Actual Amount',
            'DeliveryModel' => 'Delivery Model',
            'IsAllowSplit' => 'Is Allow Split',
            'ReceiverCompany' => 'Receiver Company',
            'ReceiverName' => 'Receiver Name',
            'ReceiverEmail' => 'Receiver Email',
            'ReceiverZipCode' => 'Receiver Zip Code',
            'ReceiverMobile' => 'Receiver Mobile',
            'ReceiverPhone' => 'Receiver Phone',
            'CountryCode' => 'Country Code',
            'ConsigneeFullname' => 'Consignee Fullname',
            'ReceiverCountry' => 'Receiver Country',
            'ReceiverProvince' => 'Receiver Province',
            'ReceiverCity' => 'Receiver City',
            'ReceiverArea' => 'Receiver Area',
            'ReceiverAddress' => 'Receiver Address',
            'ConsigneeStreet' => 'Consignee Street',
            'ConsigneeDoorplate' => 'Consignee Doorplate',
            'ReceiverIdType' => 'Receiver Id Type',
            'ReceiverIdCard' => 'Receiver Id Card',
            'OrderSenderInfo' => 'Order Sender Info',
            'order_detail' => 'Order Detail',
            'addinfo' => 'Addinfo',
        ];
    }
}
