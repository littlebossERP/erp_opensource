<?php

namespace eagle\modules\tracking\models;

use Yii;

/**
 * This is the model class for table "tracker_recommend_product".
 *
 * @property integer $id
 * @property string $platform
 * @property string $platform_account_id
 * @property string $platform_site_id
 * @property string $listing_id
 * @property string $product_image_url
 * @property string $product_name
 * @property string $product_url
 * @property integer $show_times
 * @property integer $click_times
 * @property string $is_on_save
 * @property string $create_time
 * @property string $update_time
 * @property string $is_active
 * @property string $product_price
 * @property string $product_price_currency
 */
class TrackerRecommendProduct extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tracker_recommend_product';
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
            [['platform', 'platform_account_id', 'platform_site_id', 'listing_id', 'product_image_url', 'product_name', 'product_url', 'create_time', 'update_time', 'product_price', 'product_price_currency'], 'required'],
            [['show_times', 'click_times'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['product_price'], 'number'],
            [['platform'], 'string', 'max' => 50],
            [['platform_account_id', 'listing_id', 'product_image_url', 'product_name', 'product_url'], 'string', 'max' => 255],
            [['platform_site_id'], 'string', 'max' => 25],
            [['is_on_save', 'is_active'], 'string', 'max' => 1],
            [['product_price_currency'], 'string', 'max' => 10]
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
            'show_times' => 'Show Times',
            'click_times' => 'Click Times',
            'is_on_save' => 'Is On Save',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'is_active' => 'Is Active',
            'product_price' => 'Product Price',
            'product_price_currency' => 'Product Price Currency',
        ];
    }
}
