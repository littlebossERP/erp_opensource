<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "cdiscount_order".
 *
 * @property string $ordernumber
 * @property string $billing_address1
 * @property string $billing_address2
 * @property string $billing_building
 * @property string $billing_transaction_id
 * @property string $billing_city
 * @property string $billing_civility
 * @property string $billing_companyname
 * @property string $billing_country
 * @property string $billing_firstname
 * @property string $billing_instructions
 * @property string $billing_lastname
 * @property string $billing_placename
 * @property string $billing_street
 * @property integer $billing_zipcode
 * @property string $creationdate
 * @property string $customer_customerid
 * @property string $customer_mobilephone
 * @property string $customer_phone
 * @property string $customer_civility
 * @property string $customer_firstname
 * @property string $customer_lastname
 * @property string $hasclaims
 * @property double $initialtotalamount
 * @property double $initialtotalshippingchargesamount
 * @property string $lastupdateddate
 * @property string $modifieddate
 * @property string $offer
 * @property string $orderstate
 * @property double $shippedtotalamount
 * @property double $shippedTotalShippingCharges
 * @property string $shipping_address1
 * @property string $shipping_address2
 * @property string $shipping_apartmentnumber
 * @property string $shipping_building
 * @property string $shipping_city
 * @property string $shipping_civility
 * @property string $shipping_companyname
 * @property string $shipping_country
 * @property string $shipping_county
 * @property string $shipping_firstname
 * @property string $shipping_instructions
 * @property string $shipping_lastname
 * @property string $shipping_placename
 * @property string $shipping_relayid
 * @property string $shipping_street
 * @property integer $shipping_zipcode
 * @property string $shippingcode
 * @property double $sitecommissionpromisedamount
 * @property double $sitecommissionshippedamount
 * @property double $sitecommissionvalidatedamount
 * @property string $status
 * @property double $validatedtotalamount
 * @property double $validatedtotalshippingcharges
 * @property string $validationstatus
 * @property string $archiveparcellist
 * @property string $seller_id
 * @property string $addinfo
 * @property string $updated_time
 */
class CdiscountOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cdiscount_order';
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
            [['ordernumber'], 'required'],
            [['billing_zipcode'], 'integer'],
            [['creationdate', 'lastupdateddate', 'modifieddate', 'updated_time'], 'safe'],
            [['initialtotalamount', 'initialtotalshippingchargesamount', 'shippedtotalamount', 'shippedTotalShippingCharges', 'sitecommissionpromisedamount', 'sitecommissionshippedamount', 'sitecommissionvalidatedamount', 'validatedtotalamount', 'validatedtotalshippingcharges'], 'number'],
            [['addinfo'], 'string'],
            [['ordernumber'], 'string', 'max' => 50],
            [['billing_address1', 'billing_address2', 'billing_building', 'billing_transaction_id', 'billing_city', 'billing_civility', 'billing_companyname', 'billing_country', 'billing_firstname', 'billing_instructions', 'billing_street', 'shipping_address1', 'shipping_address2', 'shipping_apartmentnumber', 'shipping_building', 'shipping_city', 'shipping_civility', 'shipping_companyname', 'shipping_country', 'shipping_county', 'shipping_instructions', 'shipping_street', 'seller_id'], 'string', 'max' => 255],
            [['billing_lastname', 'billing_placename', 'customer_customerid', 'customer_mobilephone', 'customer_phone', 'customer_civility', 'customer_firstname', 'customer_lastname', 'hasclaims', 'offer', 'orderstate', 'shipping_firstname', 'shipping_lastname', 'shipping_placename', 'shipping_relayid', 'status', 'validationstatus', 'archiveparcellist'], 'string', 'max' => 100],
            [['shippingcode', 'shipping_zipcode'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ordernumber' => 'Ordernumber',
            'billing_address1' => 'Billing Address1',
            'billing_address2' => 'Billing Address2',
            'billing_building' => 'Billing Building',
            'billing_transaction_id' => 'Billing Transaction ID',
            'billing_city' => 'Billing City',
            'billing_civility' => 'Billing Civility',
            'billing_companyname' => 'Billing Companyname',
            'billing_country' => 'Billing Country',
            'billing_firstname' => 'Billing Firstname',
            'billing_instructions' => 'Billing Instructions',
            'billing_lastname' => 'Billing Lastname',
            'billing_placename' => 'Billing Placename',
            'billing_street' => 'Billing Street',
            'billing_zipcode' => 'Billing Zipcode',
            'creationdate' => 'Creationdate',
            'customer_customerid' => 'Customer Customerid',
            'customer_mobilephone' => 'Customer Mobilephone',
            'customer_phone' => 'Customer Phone',
            'customer_civility' => 'Customer Civility',
            'customer_firstname' => 'Customer Firstname',
            'customer_lastname' => 'Customer Lastname',
            'hasclaims' => 'Hasclaims',
            'initialtotalamount' => 'Initialtotalamount',
            'initialtotalshippingchargesamount' => 'Initialtotalshippingchargesamount',
            'lastupdateddate' => 'Lastupdateddate',
            'modifieddate' => 'Modifieddate',
            'offer' => 'Offer',
            'orderstate' => 'Orderstate',
            'shippedtotalamount' => 'Shippedtotalamount',
            'shippedTotalShippingCharges' => 'Shipped Total Shipping Charges',
            'shipping_address1' => 'Shipping Address1',
            'shipping_address2' => 'Shipping Address2',
            'shipping_apartmentnumber' => 'Shipping Apartmentnumber',
            'shipping_building' => 'Shipping Building',
            'shipping_city' => 'Shipping City',
            'shipping_civility' => 'Shipping Civility',
            'shipping_companyname' => 'Shipping Companyname',
            'shipping_country' => 'Shipping Country',
            'shipping_county' => 'Shipping County',
            'shipping_firstname' => 'Shipping Firstname',
            'shipping_instructions' => 'Shipping Instructions',
            'shipping_lastname' => 'Shipping Lastname',
            'shipping_placename' => 'Shipping Placename',
            'shipping_relayid' => 'Shipping Relayid',
            'shipping_street' => 'Shipping Street',
            'shipping_zipcode' => 'Shipping Zipcode',
            'shippingcode' => 'Shippingcode',
            'sitecommissionpromisedamount' => 'Sitecommissionpromisedamount',
            'sitecommissionshippedamount' => 'Sitecommissionshippedamount',
            'sitecommissionvalidatedamount' => 'Sitecommissionvalidatedamount',
            'status' => 'Status',
            'validatedtotalamount' => 'Validatedtotalamount',
            'validatedtotalshippingcharges' => 'Validatedtotalshippingcharges',
            'validationstatus' => 'Validationstatus',
            'archiveparcellist' => 'Archiveparcellist',
            'seller_id' => 'Seller ID',
            'addinfo' => 'Addinfo',
            'updated_time' => 'Updated Time',
        ];
    }
}
