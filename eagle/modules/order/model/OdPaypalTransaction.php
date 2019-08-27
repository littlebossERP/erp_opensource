<?php

namespace eagle\modules\order\model;

use Yii;

/**
 * This is the model class for table "od_paypal_transaction".
 *
 * @property string $ptid
 * @property integer $uid
 * @property integer $eorderid
 * @property string $ebay_orderid
 * @property integer $order_id
 * @property string $transactionid
 * @property string $transactiontype
 * @property integer $ordertime
 * @property string $amt
 * @property string $feeamt
 * @property string $netamt
 * @property string $currencycode
 * @property string $buyerid
 * @property string $email
 * @property string $receiverbusiness
 * @property string $receiveremail
 * @property string $shiptoname
 * @property string $shiptostreet
 * @property string $shiptostreet2
 * @property string $shiptocity
 * @property string $shiptostate
 * @property string $shiptocountrycode
 * @property string $shiptocountryname
 * @property string $shiptozip
 * @property string $addressowner
 * @property string $paymentstatus
 * @property string $detail
 * @property integer $created
 * @property integer $updated
 */
class OdPaypalTransaction extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_paypal_transaction';
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
            [['uid', 'eorderid', 'order_id', 'ordertime', 'created', 'updated'], 'integer'],
            [['amt', 'feeamt', 'netamt'], 'number'],
            [['detail'], 'string'],
            [['ebay_orderid', 'transactionid', 'buyerid', 'email', 'receiverbusiness', 'receiveremail', 'shiptoname', 'shiptostreet', 'shiptocity', 'shiptostate', 'shiptocountryname'], 'string', 'max' => 50],
            [['transactiontype'], 'string', 'max' => 10],
            [['currencycode', 'shiptocountrycode'], 'string', 'max' => 3],
            [['shiptostreet2'], 'string', 'max' => 100],
            [['shiptozip', 'addressowner', 'paymentstatus'], 'string', 'max' => 20],
            [['transactionid'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ptid' => 'Ptid',
            'uid' => 'Uid',
            'eorderid' => 'Eorderid',
            'ebay_orderid' => 'Ebay Orderid',
            'order_id' => 'Order ID',
            'transactionid' => 'Transactionid',
            'transactiontype' => 'Transactiontype',
            'ordertime' => 'Ordertime',
            'amt' => 'Amt',
            'feeamt' => 'Feeamt',
            'netamt' => 'Netamt',
            'currencycode' => 'Currencycode',
            'buyerid' => 'Buyerid',
            'email' => 'Email',
            'receiverbusiness' => 'Receiverbusiness',
            'receiveremail' => 'Receiveremail',
            'shiptoname' => 'Shiptoname',
            'shiptostreet' => 'Shiptostreet',
            'shiptostreet2' => 'Shiptostreet2',
            'shiptocity' => 'Shiptocity',
            'shiptostate' => 'Shiptostate',
            'shiptocountrycode' => 'Shiptocountrycode',
            'shiptocountryname' => 'Shiptocountryname',
            'shiptozip' => 'Shiptozip',
            'addressowner' => 'Addressowner',
            'paymentstatus' => 'Paymentstatus',
            'detail' => 'Detail',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
