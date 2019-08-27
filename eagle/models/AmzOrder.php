<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "amz_order".
 *
 * @property string $AmazonOrderId
 * @property string $merchant_id
 * @property string $marketplace_short
 * @property string $LastUpdateDate
 * @property string $PurchaseDate
 * @property string $Status
 * @property string $SalesChannel
 * @property string $OrderChannel
 * @property string $ShipServiceLevel
 * @property string $Name
 * @property string $AddressLine1
 * @property string $AddressLine2
 * @property string $AddressLine3
 * @property string $County
 * @property string $City
 * @property string $District
 * @property string $State
 * @property string $PostalCode
 * @property string $CountryCode
 * @property string $Phone
 * @property string $Currency
 * @property string $Amount
 * @property string $PaymentMethod
 * @property string $BuyerEmail
 * @property string $create_time
 * @property string $type
 * @property string $BuyerName
 * @property integer $NumberOfItemsShipped
 * @property integer $NumberOfItemsUnshipped
 * @property string $ShipmentServiceLevelCategory
 * @property integer $EarliestShipDate
 * @property integer $LatestShipDate
 * @property integer $EarliestDeliveryDate
 * @property integer $LatestDeliveryDate
 */
class AmzOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amz_order';
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
            [['AmazonOrderId'], 'required'],
            [['LastUpdateDate', 'PurchaseDate', 'create_time'], 'safe'],
            [['Amount'], 'number'],
            [['NumberOfItemsShipped', 'NumberOfItemsUnshipped', 'EarliestShipDate', 'LatestShipDate', 'EarliestDeliveryDate', 'LatestDeliveryDate'], 'integer'],
            [['AmazonOrderId'], 'string', 'max' => 50],
            [['merchant_id'], 'string', 'max' => 55],
            [['marketplace_short', 'CountryCode'], 'string', 'max' => 2],
            [['Status'], 'string', 'max' => 30],
            [['SalesChannel', 'OrderChannel', 'ShipServiceLevel', 'PaymentMethod'], 'string', 'max' => 45],
            [['Name', 'AddressLine1', 'AddressLine2', 'AddressLine3', 'County', 'City', 'District', 'State', 'PostalCode', 'Phone', 'BuyerEmail'], 'string', 'max' => 255],
            [['Currency'], 'string', 'max' => 3],
            [['type'], 'string', 'max' => 5],
            [['BuyerName', 'ShipmentServiceLevelCategory'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'AmazonOrderId' => 'Amazon Order ID',
            'merchant_id' => 'Merchant ID',
            'marketplace_short' => 'Marketplace Short',
            'LastUpdateDate' => 'Last Update Date',
            'PurchaseDate' => 'Purchase Date',
            'Status' => 'Status',
            'SalesChannel' => 'Sales Channel',
            'OrderChannel' => 'Order Channel',
            'ShipServiceLevel' => 'Ship Service Level',
            'Name' => 'Name',
            'AddressLine1' => 'Address Line1',
            'AddressLine2' => 'Address Line2',
            'AddressLine3' => 'Address Line3',
            'County' => 'County',
            'City' => 'City',
            'District' => 'District',
            'State' => 'State',
            'PostalCode' => 'Postal Code',
            'CountryCode' => 'Country Code',
            'Phone' => 'Phone',
            'Currency' => 'Currency',
            'Amount' => 'Amount',
            'PaymentMethod' => 'Payment Method',
            'BuyerEmail' => 'Buyer Email',
            'create_time' => 'Create Time',
            'type' => 'Type',
            'BuyerName' => 'Buyer Name',
            'NumberOfItemsShipped' => 'Number Of Items Shipped',
            'NumberOfItemsUnshipped' => 'Number Of Items Unshipped',
            'ShipmentServiceLevelCategory' => 'Shipment Service Level Category',
            'EarliestShipDate' => 'Earliest Ship Date',
            'LatestShipDate' => 'Latest Ship Date',
            'EarliestDeliveryDate' => 'Earliest Delivery Date',
            'LatestDeliveryDate' => 'Latest Delivery Date',
        ];
    }
}
