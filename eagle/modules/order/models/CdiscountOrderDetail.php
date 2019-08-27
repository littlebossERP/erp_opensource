<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "cdiscount_order_detail".
 *
 * @property integer $id
 * @property string $ordernumber
 * @property string $acceptationstate
 * @property string $categorycode
 * @property string $deliverydatemax
 * @property string $deliverydatemin
 * @property string $hasclaim
 * @property string $initialprice
 * @property string $isnegotiated
 * @property string $isproducteangenerated
 * @property string $name
 * @property string $orderlinechildlist
 * @property string $productcondition
 * @property string $productean
 * @property string $productid
 * @property double $purchaseprice
 * @property integer $quantity
 * @property string $rowid
 * @property string $sellerproductid
 * @property string $shippingdatemax
 * @property string $shippingdatemin
 * @property string $sku
 * @property string $skuparent
 * @property double $unitadditionalshippingcharges
 * @property double $unitshippingcharges
 */
class CdiscountOrderDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cdiscount_order_detail';
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
            [['ordernumber', 'productid'], 'required'],
            [['deliverydatemax', 'deliverydatemin', 'shippingdatemax', 'shippingdatemin'], 'safe'],
            [['purchaseprice', 'unitadditionalshippingcharges', 'unitshippingcharges'], 'number'],
            [['quantity'], 'integer'],
            [['ordernumber'], 'string', 'max' => 50],
            [['acceptationstate', 'categorycode', 'hasclaim', 'initialprice', 'isnegotiated', 'isproducteangenerated', 'name', 'productcondition', 'productean', 'productid', 'rowid', 'sellerproductid', 'sku', 'skuparent'], 'string', 'max' => 100],
            [['orderlinechildlist'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ordernumber' => 'Ordernumber',
            'acceptationstate' => 'Acceptationstate',
            'categorycode' => 'Categorycode',
            'deliverydatemax' => 'Deliverydatemax',
            'deliverydatemin' => 'Deliverydatemin',
            'hasclaim' => 'Hasclaim',
            'initialprice' => 'Initialprice',
            'isnegotiated' => 'Isnegotiated',
            'isproducteangenerated' => 'Isproducteangenerated',
            'name' => 'Name',
            'orderlinechildlist' => 'Orderlinechildlist',
            'productcondition' => 'Productcondition',
            'productean' => 'Productean',
            'productid' => 'Productid',
            'purchaseprice' => 'Purchaseprice',
            'quantity' => 'Quantity',
            'rowid' => 'Rowid',
            'sellerproductid' => 'Sellerproductid',
            'shippingdatemax' => 'Shippingdatemax',
            'shippingdatemin' => 'Shippingdatemin',
            'sku' => 'Sku',
            'skuparent' => 'Skuparent',
            'unitadditionalshippingcharges' => 'Unitadditionalshippingcharges',
            'unitshippingcharges' => 'Unitshippingcharges',
        ];
    }
}
