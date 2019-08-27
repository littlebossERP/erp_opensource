<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ensogo_variance".
 *
 * @property integer $id
 * @property integer $product_id
 * @property string $parent_sku
 * @property string $name
 * @property string $sku
 * @property string $sync_status
 * @property string $internal_sku
 * @property string $color
 * @property string $size
 * @property string $price
 * @property string $shipping
 * @property string $shipping_time
 * @property integer $inventory
 * @property string $addinfo
 * @property string $enable
 * @property string $variance_product_id
 * @property string $image_url
 * @property string $msrp
 * @property string $error_message
 * @property integer $blocked
 */
class EnsogoVariance extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ensogo_variance';
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
            [['product_id', 'parent_sku', 'sku', 'price', 'shipping', 'inventory'], 'required'],
            [['product_id', 'inventory', 'blocked'], 'integer'],
            [['price', 'shipping', 'msrp'], 'number'],
            [['addinfo', 'error_message'], 'string'],
            [['parent_sku', 'internal_sku', 'shipping_time', 'variance_product_id'], 'string', 'max' => 50],
            [['name', 'image_url'], 'string', 'max' => 255],
            [['sku'], 'string', 'max' => 150],
            [['sync_status'], 'string', 'max' => 30],
            [['color', 'size'], 'string', 'max' => 100],
            [['enable'], 'string', 'max' => 1],
            [['product_id', 'parent_sku', 'sku'], 'unique', 'targetAttribute' => ['product_id', 'parent_sku', 'sku'], 'message' => 'The combination of Product ID, Parent Sku and Sku has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'parent_sku' => 'Parent Sku',
            'name' => 'Name',
            'sku' => 'Sku',
            'sync_status' => 'Sync Status',
            'internal_sku' => 'Internal Sku',
            'color' => 'Color',
            'size' => 'Size',
            'price' => 'Price',
            'shipping' => 'Shipping',
            'shipping_time' => 'Shipping Time',
            'inventory' => 'Inventory',
            'addinfo' => 'Addinfo',
            'enable' => 'Enable',
            'variance_product_id' => 'Variance Product ID',
            'image_url' => 'Image Url',
            'msrp' => 'Msrp',
            'error_message' => 'Error Message',
            'blocked' => 'Blocked',
        ];
    }
}
