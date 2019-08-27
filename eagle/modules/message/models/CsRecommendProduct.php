<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_recommend_product".
 *
 * @property integer $id
 * @property string $platform
 * @property string $platform_account_id
 * @property string $platform_site_id
 * @property string $listing_id
 * @property string $product_image_url
 * @property string $product_name
 * @property string $product_url
 * @property integer $life_view_count
 * @property integer $life_click_count
 * @property string $is_on_sale
 * @property string $create_time
 * @property string $update_time
 * @property string $is_active
 * @property string $product_price
 * @property string $product_min_price
 * @property string $product_max_price
 * @property string $product_price_currency
 */
class CsRecommendProduct extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_recommend_product';
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
            [['platform', 'platform_account_id', 'product_url', 'create_time', 'update_time'], 'required'],
            [['life_view_count', 'life_click_count'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['product_price', 'product_min_price', 'product_max_price'], 'number'],
            [['platform'], 'string', 'max' => 50],
            [['platform_account_id', 'product_image_url', 'product_name', 'product_url'], 'string', 'max' => 255],
            [['platform_site_id'], 'string', 'max' => 10],
            [['listing_id'], 'string', 'max' => 100],
            [['is_on_sale', 'is_active'], 'string', 'max' => 1],
            [['product_price_currency'], 'string', 'max' => 3]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'platform_account_id' => 'Platform Account ID',
            'platform_site_id' => 'Platform Site ID',
            'listing_id' => 'Listing ID',
            'product_image_url' => 'Product Image Url',
            'product_name' => 'Product Name',
            'product_url' => 'Product Url',
            'life_view_count' => 'Life View Count',
            'life_click_count' => 'Life Click Count',
            'is_on_sale' => 'Is On Sale',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'is_active' => 'Is Active',
            'product_price' => 'Product Price',
            'product_min_price' => 'Product Min Price',
            'product_max_price' => 'Product Max Price',
            'product_price_currency' => 'Product Price Currency',
        ];
    }
}
