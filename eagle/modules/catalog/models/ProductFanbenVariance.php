<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "product_fanben_variance".
 *
 * @property integer $id
 * @property integer $fanben_id
 * @property string $parent_sku
 * @property string $sku
 * @property string $color
 * @property string $size
 * @property string $price
 * @property string $shipping
 * @property integer $inventory
 * @property string $addinfo
 * @property string $enable
 * @property string $image_url
 */
class ProductFanbenVariance extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_fanben_variance';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['fanben_id', 'parent_sku', 'sku', 'color', 'size', 'price', 'shipping', 'inventory'], 'required'],
            [['fanben_id', 'inventory'], 'integer'],
            [['price', 'shipping'], 'number'],
            [['addinfo'], 'string'],
            [['parent_sku', 'sku'], 'string', 'max' => 50],
            [['color', 'size'], 'string', 'max' => 100],
            [['enable'], 'string', 'max' => 1],
            [['image_url'], 'string', 'max' => 255],
            [['fanben_id', 'parent_sku', 'sku'], 'unique', 'targetAttribute' => ['fanben_id', 'parent_sku', 'sku'], 'message' => 'The combination of Fanben ID, Parent Sku and Sku has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'fanben_id' => 'Fanben ID',
            'parent_sku' => 'Parent Sku',
            'sku' => 'Sku',
            'color' => 'Color',
            'size' => 'Size',
            'price' => 'Price',
            'shipping' => 'Shipping',
            'inventory' => 'Inventory',
            'addinfo' => 'Addinfo',
            'enable' => 'Enable',
            'image_url' => 'Image Url',
        ];
    }
}
