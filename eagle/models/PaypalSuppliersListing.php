<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "paypal_suppliers_listing".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $Merchantkey
 * @property string $Category
 * @property string $Sub_category
 * @property string $Product_name
 * @property string $Sku_no
 * @property string $Description
 * @property string $Min_qty
 * @property string $Image_paths
 * @property string $Price
 * @property string $Unit
 * @property string $Published_date
 * @property string $qiniu_image_paths
 * @property integer $create_time
 */
class PaypalSuppliersListing extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'paypal_suppliers_listing';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'Merchantkey'], 'required'],
            [['puid', 'create_time'], 'integer'],
            [['Description', 'Image_paths', 'qiniu_image_paths'], 'string'],
            [['Merchantkey', 'Category', 'Sub_category', 'Product_name', 'Sku_no', 'Min_qty', 'Price', 'Unit', 'Published_date'], 'string', 'max' => 255]
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
            'Merchantkey' => 'Merchantkey',
            'Category' => 'Category',
            'Sub_category' => 'Sub Category',
            'Product_name' => 'Product Name',
            'Sku_no' => 'Sku No',
            'Description' => 'Description',
            'Min_qty' => 'Min Qty',
            'Image_paths' => 'Image Paths',
            'Price' => 'Price',
            'Unit' => 'Unit',
            'Published_date' => 'Published Date',
            'qiniu_image_paths' => 'Qiniu Image Paths',
            'create_time' => 'Create Time',
        ];
    }
}
