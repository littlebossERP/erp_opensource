<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "amazon_item".
 *
 * @property string $ASIN
 * @property string $Binding
 * @property string $Brand
 * @property string $Feature
 * @property string $Label
 * @property string $Manufacturer
 * @property string $Model
 * @property string $PartNumber
 * @property string $ProductGroup
 * @property string $ProductTypeName
 * @property string $Publisher
 * @property string $SmallImage
 * @property string $Studio
 * @property string $Title
 * @property string $Color
 * @property string $marketplace_short
 * @property string $merchant_id
 * @property string $origin_ASIN
 * @property string $sellerSKU
 * @property string $Price
 * @property string $Product_id
 */
class AmazonItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_item';
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
            [['ASIN', 'marketplace_short', 'merchant_id'], 'required'],
            [['Feature', 'SmallImage', 'Title'], 'string'],
            [['Price'], 'number'],
            [['ASIN', 'marketplace_short', 'merchant_id', 'origin_ASIN'], 'string', 'max' => 50],
            [['Binding', 'Brand', 'Label', 'Manufacturer', 'Model', 'PartNumber', 'ProductGroup', 'ProductTypeName', 'Publisher', 'Studio'], 'string', 'max' => 255],
            [['Color', 'sellerSKU', 'Product_id'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ASIN' => 'Asin',
            'Binding' => 'Binding',
            'Brand' => 'Brand',
            'Feature' => 'Feature',
            'Label' => 'Label',
            'Manufacturer' => 'Manufacturer',
            'Model' => 'Model',
            'PartNumber' => 'Part Number',
            'ProductGroup' => 'Product Group',
            'ProductTypeName' => 'Product Type Name',
            'Publisher' => 'Publisher',
            'SmallImage' => 'Small Image',
            'Studio' => 'Studio',
            'Title' => 'Title',
            'Color' => 'Color',
            'marketplace_short' => 'Marketplace Short',
            'merchant_id' => 'Merchant ID',
            'origin_ASIN' => 'Origin  Asin',
            'sellerSKU' => 'Seller Sku',
            'Price' => 'Price',
            'Product_id' => 'Product ID',
        ];
    }
}
