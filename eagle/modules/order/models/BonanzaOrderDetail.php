<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "bonanza_order_detail".
 *
 * @property integer $id
 * @property string $itemID
 * @property string $sellerInventoryID
 * @property string $sku
 * @property string $title
 * @property double $price
 * @property integer $quantity
 * @property string $ebayId
 * @property string $orderID
 */
class BonanzaOrderDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'bonanza_order_detail';
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
            [['itemID', 'orderID'], 'required'],
            [['price'], 'number'],
            [['quantity'], 'integer'],
            [['itemID', 'sellerInventoryID', 'sku', 'title', 'ebayId', 'orderID'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'itemID' => 'Item ID',
            'sellerInventoryID' => 'Seller Inventory ID',
            'sku' => 'Sku',
            'title' => 'Title',
            'price' => 'Price',
            'quantity' => 'Quantity',
            'ebayId' => 'Ebay ID',
            'orderID' => 'Order ID',
        ];
    }
}
