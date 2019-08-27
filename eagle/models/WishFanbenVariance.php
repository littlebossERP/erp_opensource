<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "wish_fanben_variance".
 *
 * @property integer $id
 * @property integer $fanben_id
 * @property string $parent_sku
 * @property string $sku
 * @property string $sync_status
 * @property string $internal_sku
 * @property string $color
 * @property string $size
 * @property string $price
 * @property string $shipping
 * @property string $inventory
 * @property string $addinfo
 * @property string $enable
 * @property string $variance_product_id
 * @property string $image_url
 */
class WishFanbenVariance extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wish_fanben_variance';
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
            [['fanben_id', 'parent_sku', 'sku', 'price'], 'required'],
            [['fanben_id', 'inventory'], 'integer'],
            [['price', 'shipping'], 'number'],
            [['addinfo'], 'string'],
            [['parent_sku', 'sku', 'internal_sku', 'variance_product_id'], 'string', 'max' => 50],
            [['sync_status'], 'string', 'max' => 30],
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
            'sync_status' => 'Sync Status',
            'internal_sku' => 'Internal Sku',
            'color' => 'Color',
            'size' => 'Size',
            'price' => 'Price',
            'shipping' => 'Shipping',
            'inventory' => 'Inventory',
            'addinfo' => 'Addinfo',
            'enable' => 'Enable',
            'variance_product_id' => 'Variance Product ID',
            'image_url' => 'Image Url',
        ];
    }
}
