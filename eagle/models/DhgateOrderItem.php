<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "dhgate_order_item".
 *
 * @property integer $id
 * @property string $dhgateOrderNo
 * @property string $itemcode
 * @property string $categoryName
 * @property string $grossWeight
 * @property string $height
 * @property string $length
 * @property string $width
 * @property string $itemAttr
 * @property integer $itemCount
 * @property string $itemImage
 * @property string $itemName
 * @property string $itemPrice
 * @property string $itemUrl
 * @property string $measureName
 * @property integer $packingQuantity
 * @property string $skuCode
 * @property string $buyerRemark
 */
class DhgateOrderItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dhgate_order_item';
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
            [['dhgateOrderNo'], 'required'],
            [['grossWeight', 'height', 'length', 'width', 'itemPrice'], 'number'],
            [['itemCount', 'packingQuantity'], 'integer'],
            [['dhgateOrderNo', 'itemcode', 'measureName'], 'string', 'max' => 50],
            [['categoryName', 'itemAttr', 'itemImage', 'itemName', 'itemUrl', 'buyerRemark'], 'string', 'max' => 255],
            [['skuCode'], 'string', 'max' => 45]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'dhgateOrderNo' => 'Dhgate Order No',
            'itemcode' => 'Itemcode',
            'categoryName' => 'Category Name',
            'grossWeight' => 'Gross Weight',
            'height' => 'Height',
            'length' => 'Length',
            'width' => 'Width',
            'itemAttr' => 'Item Attr',
            'itemCount' => 'Item Count',
            'itemImage' => 'Item Image',
            'itemName' => 'Item Name',
            'itemPrice' => 'Item Price',
            'itemUrl' => 'Item Url',
            'measureName' => 'Measure Name',
            'packingQuantity' => 'Packing Quantity',
            'skuCode' => 'Sku Code',
            'buyerRemark' => 'Buyer Remark',
        ];
    }
}
