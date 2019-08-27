<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "cdiscount_offer_terminator".
 *
 * @property integer $id
 * @property string $addi_info
 * @property integer $uid
 * @property string $product_id
 * @property string $create
 * @property string $is_parent_product
 * @property string $bestseller_name
 * @property string $bestseller_price
 */
class CdiscountOfferTerminator extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cdiscount_offer_terminator';
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
            [['addi_info'], 'string'],
            [['uid', 'product_id'], 'required'],
            [['uid'], 'integer'],
            [['create'], 'safe'],
            [['bestseller_price'], 'number'],
            [['product_id'], 'string', 'max' => 30],
            [['is_parent_product'], 'string', 'max' => 1],
            [['bestseller_name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'addi_info' => 'Addi Info',
            'uid' => 'Uid',
            'product_id' => 'Product ID',
            'create' => 'Create',
            'is_parent_product' => 'Is Parent Product',
            'bestseller_name' => 'Bestseller Name',
            'bestseller_price' => 'Bestseller Price',
        ];
    }
}
