<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "product_fanben".
 *
 * @property integer $id
 * @property integer $productid
 * @property string $brand
 * @property integer $type
 * @property string $status
 * @property integer $lb_status
 * @property string $parent_sku
 * @property integer $variance_count
 * @property string $name
 * @property string $landing_page_url
 * @property string $internal_sku
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
 * @property string $wish_product_id
 * @property string $error_message
 * @property string $addinfo
 * @property string $price
 * @property string $inventory
 * @property integer $number_saves
 * @property integer $number_sold
 * @property integer $is_enable
 * @property string $shipping
 */
class ProductFanben extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'product_fanben';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['productid', 'status', 'name', 'main_image', 'description', 'capture_user_id'], 'required'],
            [['productid', 'type', 'lb_status', 'variance_count', 'capture_user_id', 'inventory', 'number_saves', 'number_sold', 'is_enable'], 'integer'],
            [['landing_page_url', 'main_image', 'extra_image_1', 'extra_image_2', 'extra_image_3', 'extra_image_4', 'extra_image_5', 'extra_image_6', 'extra_image_7', 'extra_image_8', 'extra_image_9', 'extra_image_10', 'description', 'error_message'], 'string'],
            [['msrp', 'price', 'shipping'], 'number'],
            [['create_time', 'update_time'], 'safe'],
            [['brand', 'parent_sku', 'internal_sku', 'wish_product_id'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 20],
            [['name', 'addinfo'], 'string', 'max' => 255],
            [['shipping_time'], 'string', 'max' => 250],
            [['parent_sku'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'productid' => 'Productid',
            'brand' => 'Brand',
            'type' => 'Type',
            'status' => 'Status',
            'lb_status' => 'Lb Status',
            'parent_sku' => 'Parent Sku',
            'variance_count' => 'Variance Count',
            'name' => 'Name',
            'landing_page_url' => 'Landing Page Url',
            'internal_sku' => 'Internal Sku',
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
            'wish_product_id' => 'Wish Product ID',
            'error_message' => 'Error Message',
            'addinfo' => 'Addinfo',
            'price' => 'Price',
            'inventory' => 'Inventory',
            'number_saves' => 'Number Saves',
            'number_sold' => 'Number Sold',
            'is_enable' => 'Is Enable',
            'shipping' => 'Shipping',
        ];
    }
}
