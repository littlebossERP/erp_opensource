<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ensogo_product".
 *
 * @property integer $id
 * @property string $brand
 * @property integer $type
 * @property string $status
 * @property integer $lb_status
 * @property integer $site_id
 * @property string $parent_sku
 * @property integer $variance_count
 * @property string $name
 * @property string $tags
 * @property string $upc
 * @property string $landing_page_url
 * @property string $internal_sku
 * @property integer $category_id
 * @property string $msrp
 * @property string $shipping_time
 * @property string $main_image
 * @property string $extra_image_1
 * @property string $extra_image_2
 * @property string $extra_image_3
 * @property string $extra_image_4
 * @property string $extra_image_5
 * @property string $extra_image_6
 * @property string $extra_image_7
 * @property string $extra_image_8
 * @property string $extra_image_9
 * @property string $extra_image_10
 * @property string $create_time
 * @property string $update_time
 * @property string $description
 * @property integer $capture_user_id
 * @property string $ensogo_product_id
 * @property string $error_message
 * @property string $addinfo
 * @property string $price
 * @property integer $inventory
 * @property string $shipping
 * @property integer $number_saves
 * @property integer $number_sold
 * @property integer $is_enable
 * @property string $variant_name
 * @property string $json_info
 * @property string $request_id
 * @property integer $blocked
 * @property integer $sale_type
 */
class EnsogoProduct extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ensogo_product';
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
            [['type', 'lb_status', 'site_id', 'variance_count', 'category_id', 'capture_user_id', 'inventory', 'number_saves', 'number_sold', 'is_enable', 'blocked', 'sale_type'], 'integer'],
            [['name', 'tags', 'capture_user_id'], 'required'],
            [['landing_page_url', 'main_image', 'extra_image_1', 'extra_image_2', 'extra_image_3', 'extra_image_4', 'extra_image_5', 'extra_image_6', 'extra_image_7', 'extra_image_8', 'extra_image_9', 'extra_image_10', 'description', 'error_message', 'json_info'], 'string'],
            [['msrp', 'price', 'shipping'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['brand', 'upc', 'internal_sku', 'ensogo_product_id'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 20],
            [['parent_sku'], 'string', 'max' => 150],
            [['name', 'tags', 'addinfo', 'variant_name', 'request_id'], 'string', 'max' => 255],
            [['shipping_time'], 'string', 'max' => 250]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'brand' => 'Brand',
            'type' => 'Type',
            'status' => 'Status',
            'lb_status' => 'Lb Status',
            'site_id' => 'Site ID',
            'parent_sku' => 'Parent Sku',
            'variance_count' => 'Variance Count',
            'name' => 'Name',
            'tags' => 'Tags',
            'upc' => 'Upc',
            'landing_page_url' => 'Landing Page Url',
            'internal_sku' => 'Internal Sku',
            'category_id' => 'Category ID',
            'msrp' => 'Msrp',
            'shipping_time' => 'Shipping Time',
            'main_image' => 'Main Image',
            'extra_image_1' => 'Extra Image 1',
            'extra_image_2' => 'Extra Image 2',
            'extra_image_3' => 'Extra Image 3',
            'extra_image_4' => 'Extra Image 4',
            'extra_image_5' => 'Extra Image 5',
            'extra_image_6' => 'Extra Image 6',
            'extra_image_7' => 'Extra Image 7',
            'extra_image_8' => 'Extra Image 8',
            'extra_image_9' => 'Extra Image 9',
            'extra_image_10' => 'Extra Image 10',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'description' => 'Description',
            'capture_user_id' => 'Capture User ID',
            'ensogo_product_id' => 'Ensogo Product ID',
            'error_message' => 'Error Message',
            'addinfo' => 'Addinfo',
            'price' => 'Price',
            'inventory' => 'Inventory',
            'shipping' => 'Shipping',
            'number_saves' => 'Number Saves',
            'number_sold' => 'Number Sold',
            'is_enable' => 'Is Enable',
            'variant_name' => 'Variant Name',
            'json_info' => 'Json Info',
            'request_id' => 'Request ID',
            'blocked' => 'Blocked',
            'sale_type' => 'Sale Type',
        ];
    }
}
