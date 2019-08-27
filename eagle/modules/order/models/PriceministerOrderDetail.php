<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "priceminister_order_detail".
 *
 * @property integer $id
 * @property string $purchaseid
 * @property string $sku
 * @property string $advertid
 * @property string $advertprice_amount
 * @property string $advertprice_currency
 * @property string $itemid
 * @property string $headline
 * @property string $itemstatus
 * @property string $itemstatus_of_get_item_infos
 * @property string $itemstatus_of_get_biling_information
 * @property string $ispreorder
 * @property string $isnego
 * @property string $negotiationcomment
 * @property string $price_amount
 * @property string $price_currency
 * @property string $isrsl
 * @property string $isbn
 * @property string $ean
 * @property string $paymentstatus
 * @property string $sellerscore
 */
class PriceministerOrderDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'priceminister_order_detail';
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
            [['purchaseid', 'advertid', 'itemid'], 'required'],
            [['advertprice_amount', 'price_amount'], 'number'],
            [['headline', 'negotiationcomment'], 'string'],
            [['purchaseid', 'sku', 'advertid', 'sellerscore'], 'string', 'max' => 255],
            [['advertprice_currency', 'ispreorder', 'isnego', 'price_currency', 'isrsl'], 'string', 'max' => 10],
            [['itemid', 'itemstatus', 'itemstatus_of_get_item_infos', 'itemstatus_of_get_biling_information', 'isbn', 'ean', 'paymentstatus'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'purchaseid' => 'Purchaseid',
            'sku' => 'Sku',
            'advertid' => 'Advertid',
            'advertprice_amount' => 'Advertprice Amount',
            'advertprice_currency' => 'Advertprice Currency',
            'itemid' => 'Itemid',
            'headline' => 'Headline',
            'itemstatus' => 'Itemstatus',
            'itemstatus_of_get_item_infos' => 'Itemstatus Of Get Item Infos',
            'itemstatus_of_get_biling_information' => 'Itemstatus Of Get Biling Information',
            'ispreorder' => 'Ispreorder',
            'isnego' => 'Isnego',
            'negotiationcomment' => 'Negotiationcomment',
            'price_amount' => 'Price Amount',
            'price_currency' => 'Price Currency',
            'isrsl' => 'Isrsl',
            'isbn' => 'Isbn',
            'ean' => 'Ean',
            'paymentstatus' => 'Paymentstatus',
            'sellerscore' => 'Sellerscore',
        ];
    }
}
