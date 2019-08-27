<?php

namespace eagle\modules\order\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "od_ebay_order".
 *
 * @property string $eorderid
 * @property string $ebay_orderid
 * @property integer $ebay_uid
 * @property string $orderstatus
 * @property string $ebaypaymentstatus
 * @property string $paymentmethod
 * @property string $checkoutstatus
 * @property string $integratedmerchantcreditcardenabled
 * @property string $adjustmentamount
 * @property string $amountpaid
 * @property string $amountsaved
 * @property string $salestaxpercent
 * @property string $salestaxamount
 * @property string $shippingservicecost
 * @property string $subtotal
 * @property string $total
 * @property string $feeorcreditamount
 * @property string $paymentorrefundamount
 * @property string $insurancecost
 * @property string $currency
 * @property integer $lastmodifiedtime
 * @property integer $createdtime
 * @property integer $paidtime
 * @property integer $shippedtime
 * @property string $buyeruserid
 * @property string $shippingservice
 * @property integer $shippingincludedintax
 * @property string $ship_name
 * @property string $ship_company
 * @property string $ship_cityname
 * @property string $ship_stateorprovince
 * @property string $ship_country
 * @property string $ship_countryname
 * @property string $ship_street1
 * @property string $ship_street2
 * @property string $ship_postalcode
 * @property string $ship_phone
 * @property string $ship_email
 * @property string $addressid
 * @property string $addressowner
 * @property string $externaladdressid
 * @property string $externaltransactionid
 * @property integer $externaltransactiontime
 * @property string $shippingaddress
 * @property string $externaltransaction
 * @property string $buyercheckoutmessage
 * @property string $shippingserviceselected
 * @property string $selleruserid
 * @property integer $ecid
 * @property integer $responsedat
 * @property integer $berequest
 * @property integer $status_berequest
 * @property integer $salesrecordnum
 */
class OdEbayOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_ebay_order';
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
            [['ebay_uid', 'lastmodifiedtime', 'createdtime', 'paidtime', 'shippedtime', 'shippingincludedintax', 'externaltransactiontime', 'ecid', 'responsedat', 'berequest', 'status_berequest', 'salesrecordnum'], 'integer'],
            [['adjustmentamount', 'amountpaid', 'amountsaved', 'salestaxpercent', 'salestaxamount', 'shippingservicecost', 'subtotal', 'total', 'feeorcreditamount', 'paymentorrefundamount', 'insurancecost'], 'number'],
            [['buyercheckoutmessage'], 'string'],
            [['ebay_orderid', 'selleruserid'], 'string', 'max' => 55],
            [['orderstatus', 'ebaypaymentstatus', 'paymentmethod', 'checkoutstatus', 'integratedmerchantcreditcardenabled', 'shippingservice', 'ship_postalcode', 'addressid', 'addressowner', 'externaladdressid', 'externaltransactionid'], 'string', 'max' => 50],
            [['currency'], 'string', 'max' => 3],
            [['buyeruserid'], 'string', 'max' => 64],
            [['ship_name', 'ship_company', 'ship_cityname', 'ship_stateorprovince', 'ship_country', 'ship_countryname'], 'string', 'max' => 100],
            [['ship_street1', 'ship_street2', 'ship_phone'], 'string', 'max' => 255],
            [['ship_email'], 'string', 'max' => 150]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'eorderid' => 'Eorderid',
            'ebay_orderid' => 'Ebay Orderid',
            'ebay_uid' => 'Ebay Uid',
            'orderstatus' => 'Orderstatus',
            'ebaypaymentstatus' => 'Ebaypaymentstatus',
            'paymentmethod' => 'Paymentmethod',
            'checkoutstatus' => 'Checkoutstatus',
            'integratedmerchantcreditcardenabled' => 'Integratedmerchantcreditcardenabled',
            'adjustmentamount' => 'Adjustmentamount',
            'amountpaid' => 'Amountpaid',
            'amountsaved' => 'Amountsaved',
            'salestaxpercent' => 'Salestaxpercent',
            'salestaxamount' => 'Salestaxamount',
            'shippingservicecost' => 'Shippingservicecost',
            'subtotal' => 'Subtotal',
            'total' => 'Total',
            'feeorcreditamount' => 'Feeorcreditamount',
            'paymentorrefundamount' => 'Paymentorrefundamount',
            'insurancecost' => 'Insurancecost',
            'currency' => 'Currency',
            'lastmodifiedtime' => 'Lastmodifiedtime',
            'createdtime' => 'Createdtime',
            'paidtime' => 'Paidtime',
            'shippedtime' => 'Shippedtime',
            'buyeruserid' => 'Buyeruserid',
            'shippingservice' => 'Shippingservice',
            'shippingincludedintax' => 'Shippingincludedintax',
            'ship_name' => 'Ship Name',
            'ship_company' => 'Ship Company',
            'ship_cityname' => 'Ship Cityname',
            'ship_stateorprovince' => 'Ship Stateorprovince',
            'ship_country' => 'Ship Country',
            'ship_countryname' => 'Ship Countryname',
            'ship_street1' => 'Ship Street1',
            'ship_street2' => 'Ship Street2',
            'ship_postalcode' => 'Ship Postalcode',
            'ship_phone' => 'Ship Phone',
            'ship_email' => 'Ship Email',
            'addressid' => 'Addressid',
            'addressowner' => 'Addressowner',
            'externaladdressid' => 'Externaladdressid',
            'externaltransactionid' => 'Externaltransactionid',
            'externaltransactiontime' => 'Externaltransactiontime',
            'shippingaddress' => 'Shippingaddress',
            'externaltransaction' => 'Externaltransaction',
            'buyercheckoutmessage' => 'Buyercheckoutmessage',
            'shippingserviceselected' => 'Shippingserviceselected',
            'selleruserid' => 'Selleruserid',
            'ecid' => 'Ecid',
            'responsedat' => 'Responsedat',
            'berequest' => 'Berequest',
            'status_berequest' => 'Status Berequest',
            'salesrecordnum' => 'Salesrecordnum',
        ];
    }
    
    public function behaviors(){
    	return array(
    		'SerializeBehavior' => array(
    			'class' => SerializeBehavior::className(),
    			'serialAttributes' => array('shippingserviceselected','shippingaddress','externaltransaction'),
    		)
    	);
    }
}
