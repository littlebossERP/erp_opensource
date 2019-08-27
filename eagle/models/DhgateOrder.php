<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "dhgate_order".
 *
 * @property string $orderNo
 * @property string $sellerloginid
 * @property string $orderStatus
 * @property string $orderTotalPrice
 * @property string $actualPrice
 * @property string $commissionAmount
 * @property string $fillingMoney
 * @property string $gatewayFee
 * @property string $itemTotalPrice
 * @property string $reducePrice
 * @property string $refundMoney
 * @property string $risePrice
 * @property string $sellerCouponPrice
 * @property string $shippingCost
 * @property string $buyerId
 * @property string $buyerNickName
 * @property string $firstName
 * @property string $lastName
 * @property string $addressLine1
 * @property string $addressLine2
 * @property string $country
 * @property string $city
 * @property string $state
 * @property string $email
 * @property string $postalcode
 * @property string $telephone
 * @property string $vatNumber
 * @property string $complaintStatus
 * @property string $deliveryDate
 * @property string $deliveryNo
 * @property string $newDeliveryNo
 * @property string $delivery_newRemark
 * @property string $newShippingType
 * @property string $processingResults
 * @property string $delivery_remark
 * @property string $shippingType
 * @property integer $isWarn
 * @property string $warnReason
 * @property string $buyerConfirmDate
 * @property string $cancelDate
 * @property string $deliveryDeadline
 * @property string $inAccountDate
 * @property string $payDate
 * @property string $startedDate
 * @property string $create_time
 * @property string $orderRemark
 */
class DhgateOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dhgate_order';
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
            [['orderNo'], 'required'],
            [['orderTotalPrice', 'actualPrice', 'commissionAmount', 'fillingMoney', 'gatewayFee', 'itemTotalPrice', 'reducePrice', 'refundMoney', 'risePrice', 'sellerCouponPrice', 'shippingCost'], 'number'],
            [['deliveryDate', 'buyerConfirmDate', 'cancelDate', 'deliveryDeadline', 'inAccountDate', 'payDate', 'startedDate', 'create_time'], 'safe'],
            [['isWarn'], 'integer'],
            [['orderNo', 'deliveryNo', 'newDeliveryNo'], 'string', 'max' => 50],
            [['sellerloginid', 'buyerId', 'telephone', 'vatNumber', 'newShippingType', 'shippingType'], 'string', 'max' => 55],
            [['orderStatus', 'complaintStatus'], 'string', 'max' => 30],
            [['buyerNickName', 'firstName', 'lastName', 'addressLine1', 'addressLine2', 'country', 'city', 'state', 'email', 'postalcode', 'delivery_newRemark', 'processingResults', 'delivery_remark', 'warnReason', 'orderRemark'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'orderNo' => 'Order No',
            'sellerloginid' => 'Sellerloginid',
            'orderStatus' => 'Order Status',
            'orderTotalPrice' => 'Order Total Price',
            'actualPrice' => 'Actual Price',
            'commissionAmount' => 'Commission Amount',
            'fillingMoney' => 'Filling Money',
            'gatewayFee' => 'Gateway Fee',
            'itemTotalPrice' => 'Item Total Price',
            'reducePrice' => 'Reduce Price',
            'refundMoney' => 'Refund Money',
            'risePrice' => 'Rise Price',
            'sellerCouponPrice' => 'Seller Coupon Price',
            'shippingCost' => 'Shipping Cost',
            'buyerId' => 'Buyer ID',
            'buyerNickName' => 'Buyer Nick Name',
            'firstName' => 'First Name',
            'lastName' => 'Last Name',
            'addressLine1' => 'Address Line1',
            'addressLine2' => 'Address Line2',
            'country' => 'Country',
            'city' => 'City',
            'state' => 'State',
            'email' => 'Email',
            'postalcode' => 'Postalcode',
            'telephone' => 'Telephone',
            'vatNumber' => 'Vat Number',
            'complaintStatus' => 'Complaint Status',
            'deliveryDate' => 'Delivery Date',
            'deliveryNo' => 'Delivery No',
            'newDeliveryNo' => 'New Delivery No',
            'delivery_newRemark' => 'Delivery New Remark',
            'newShippingType' => 'New Shipping Type',
            'processingResults' => 'Processing Results',
            'delivery_remark' => 'Delivery Remark',
            'shippingType' => 'Shipping Type',
            'isWarn' => 'Is Warn',
            'warnReason' => 'Warn Reason',
            'buyerConfirmDate' => 'Buyer Confirm Date',
            'cancelDate' => 'Cancel Date',
            'deliveryDeadline' => 'Delivery Deadline',
            'inAccountDate' => 'In Account Date',
            'payDate' => 'Pay Date',
            'startedDate' => 'Started Date',
            'create_time' => 'Create Time',
            'orderRemark' => 'Order Remark',
        ];
    }
}
