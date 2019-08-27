<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lazada_order".
 *
 * @property integer $id
 * @property string $OrderId
 * @property string $CustomerFirstName
 * @property string $CustomerLastName
 * @property string $OrderNumber
 * @property string $PaymentMethod
 * @property string $DeliveryInfo
 * @property string $Remarks
 * @property string $Price
 * @property integer $GiftOption
 * @property string $GiftMessage
 * @property string $VoucherCode
 * @property integer $CreatedAt
 * @property integer $UpdatedAt
 * @property string $AddressBilling
 * @property string $AddressShipping
 * @property string $NationalRegistrationNumber
 * @property integer $ItemsCount
 * @property string $Statuses
 * @property integer $create_time
 * @property integer $update_time
 * @property string $lazada_api_email
 */
class LazadaOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_order';
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
            [['OrderId', 'OrderNumber', 'PaymentMethod', 'GiftOption', 'CreatedAt', 'UpdatedAt', 'ItemsCount', 'Statuses', 'lazada_api_email'], 'required'],
            [['Price'], 'number'],
            [['GiftOption', 'CreatedAt', 'UpdatedAt', 'ItemsCount', 'create_time', 'update_time'], 'integer'],
            [['GiftMessage', 'AddressBilling', 'AddressShipping'], 'string'],
            [['OrderId'], 'string', 'max' => 30],
            [['CustomerFirstName', 'CustomerLastName', 'OrderNumber', 'PaymentMethod'], 'string', 'max' => 50],
            [['DeliveryInfo', 'Remarks', 'VoucherCode', 'Statuses', 'lazada_api_email'], 'string', 'max' => 255],
            [['NationalRegistrationNumber'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'OrderId' => 'Order ID',
            'CustomerFirstName' => 'Customer First Name',
            'CustomerLastName' => 'Customer Last Name',
            'OrderNumber' => 'Order Number',
            'PaymentMethod' => 'Payment Method',
            'DeliveryInfo' => 'Delivery Info',
            'Remarks' => 'Remarks',
            'Price' => 'Price',
            'GiftOption' => 'Gift Option',
            'GiftMessage' => 'Gift Message',
            'VoucherCode' => 'Voucher Code',
            'CreatedAt' => 'Created At',
            'UpdatedAt' => 'Updated At',
            'AddressBilling' => 'Address Billing',
            'AddressShipping' => 'Address Shipping',
            'NationalRegistrationNumber' => 'National Registration Number',
            'ItemsCount' => 'Items Count',
            'Statuses' => 'Statuses',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'lazada_api_email' => 'Lazada Api Email',
        ];
    }
}
