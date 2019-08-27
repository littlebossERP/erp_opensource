<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "od_ebay_externaltransaction".
 *
 * @property string $eid
 * @property string $ebay_orderid
 * @property string $selleruserid
 * @property string $externaltransactionid
 * @property string $paymentorrefundamount
 * @property string $feeorcreditamount
 * @property integer $externaltransactiontime
 * @property integer $created
 */
class OdEbayExternaltransaction extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_ebay_externaltransaction';
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
            [['paymentorrefundamount', 'feeorcreditamount'], 'number'],
            [['externaltransactiontime', 'created'], 'integer'],
            [['ebay_orderid', 'selleruserid'], 'string', 'max' => 50],
            [['externaltransactionid'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'eid' => 'Eid',
            'ebay_orderid' => 'Ebay Orderid',
            'selleruserid' => 'Selleruserid',
            'externaltransactionid' => 'Externaltransactionid',
            'paymentorrefundamount' => 'Paymentorrefundamount',
            'feeorcreditamount' => 'Feeorcreditamount',
            'externaltransactiontime' => 'Externaltransactiontime',
            'created' => 'Created',
        ];
    }
}
