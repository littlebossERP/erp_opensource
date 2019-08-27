<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lt_customized_recommended_prod".
 *
 * @property string $id
 * @property string $puid
 * @property string $seller_id
 * @property string $platform
 * @property string $product_name
 * @property string $title
 * @property string $photo_url
 * @property string $product_url
 * @property string $price
 * @property string $currency
 * @property string $sku
 * @property string $comment
 * @property string $group_id
 * @property string $addi_info
 * @property integer $create_time
 * @property integer $update_time
 */
class LtCustomizedRecommendedProd extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lt_customized_recommended_prod';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue2');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'seller_id', 'platform', 'product_name', 'title', 'photo_url', 'product_url', 'price', 'currency'], 'required'],
            [['puid', 'group_id', 'create_time', 'update_time'], 'integer'],
            [['price'], 'number'],
            [['addi_info'], 'string'],
            [['seller_id', 'platform', 'sku'], 'string', 'max' => 50],
            [['product_name', 'title', 'photo_url', 'product_url', 'comment'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 3]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'seller_id' => 'Seller ID',
            'platform' => 'Platform',
            'product_name' => 'Product Name',
            'title' => 'Title',
            'photo_url' => 'Photo Url',
            'product_url' => 'Product Url',
            'price' => 'Price',
            'currency' => 'Currency',
            'sku' => 'Sku',
            'comment' => 'Comment',
            'group_id' => 'Group ID',
            'addi_info' => 'Addi Info',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
