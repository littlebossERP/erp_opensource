<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "bonanza_order".
 *
 * @property string $orderID
 * @property double $amountPaid
 * @property integer $amountSaved
 * @property string $buyerCheckoutMessage
 * @property string $buyerUserID
 * @property string $buyerUserName
 * @property string $checkoutStatus
 * @property string $createdTime
 * @property string $creatingUserRole
 * @property string $orderStatus
 * @property double $subtotal
 * @property double $taxAmount
 * @property double $total
 * @property string $email
 * @property string $providerName
 * @property string $providerID
 * @property double $finalValueFee
 * @property string $paidTime
 * @property string $addressID
 * @property string $cityName
 * @property string $country
 * @property string $countryName
 * @property string $name
 * @property string $postalCode
 * @property string $stateOrProvince
 * @property string $street1
 * @property string $street2
 * @property double $insuranceFee
 * @property integer $amount
 * @property string $servicesArray
 * @property string $shippingService
 * @property string $notes
 * @property string $addinfo
 */
class BonanzaOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bonanza_order';
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
            [['orderID'], 'required'],
            [['amountPaid', 'subtotal', 'taxAmount', 'total', 'finalValueFee', 'insuranceFee'], 'number'],
            [['amountSaved', 'amount'], 'integer'],
            [['createdTime', 'paidTime'], 'safe'],
            [['addinfo'], 'string'],
            [['orderID', 'buyerCheckoutMessage', 'buyerUserID', 'buyerUserName', 'email', 'providerID', 'addressID', 'cityName', 'postalCode', 'stateOrProvince', 'street1', 'street2', 'servicesArray', 'shippingService', 'notes'], 'string', 'max' => 255],
            [['checkoutStatus', 'creatingUserRole', 'orderStatus', 'providerName', 'countryName', 'name'], 'string', 'max' => 100],
            [['country'], 'string', 'max' => 55]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'orderID' => 'Order ID',
            'amountPaid' => 'Amount Paid',
            'amountSaved' => 'Amount Saved',
            'buyerCheckoutMessage' => 'Buyer Checkout Message',
            'buyerUserID' => 'Buyer User ID',
            'buyerUserName' => 'Buyer User Name',
            'checkoutStatus' => 'Checkout Status',
            'createdTime' => 'Created Time',
            'creatingUserRole' => 'Creating User Role',
            'orderStatus' => 'Order Status',
            'subtotal' => 'Subtotal',
            'taxAmount' => 'Tax Amount',
            'total' => 'Total',
            'email' => 'Email',
            'providerName' => 'Provider Name',
            'providerID' => 'Provider ID',
            'finalValueFee' => 'Final Value Fee',
            'paidTime' => 'Paid Time',
            'addressID' => 'Address ID',
            'cityName' => 'City Name',
            'country' => 'Country',
            'countryName' => 'Country Name',
            'name' => 'Name',
            'postalCode' => 'Postal Code',
            'stateOrProvince' => 'State Or Province',
            'street1' => 'Street1',
            'street2' => 'Street2',
            'insuranceFee' => 'Insurance Fee',
            'amount' => 'Amount',
            'servicesArray' => 'Services Array',
            'shippingService' => 'Shipping Service',
            'notes' => 'Notes',
            'addinfo' => 'Addinfo',
        ];
    }
}
