<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_item_variation_map".
 *
 * @property integer $id
 * @property string $itemid
 * @property string $sku
 * @property string $startprice
 * @property integer $quantity
 * @property integer $quantitysold
 * @property integer $onlinequantity
 * @property integer $createtime
 * @property integer $updatetime
 */
class EbayItemVariationMap extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_item_variation_map';
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
            [['itemid', 'quantity', 'quantitysold', 'onlinequantity', 'createtime', 'updatetime'], 'integer'],
            [['startprice'], 'number'],
            [['sku'], 'string', 'max' => 155]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'itemid' => 'Itemid',
            'sku' => 'Sku',
            'startprice' => 'Startprice',
            'quantity' => 'Quantity',
            'quantitysold' => 'Quantitysold',
            'onlinequantity' => 'Onlinequantity',
            'createtime' => 'Createtime',
            'updatetime' => 'Updatetime',
        ];
    }
}
