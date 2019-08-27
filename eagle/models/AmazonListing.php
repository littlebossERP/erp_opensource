<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "amazon_listing".
 *
 * @property integer $id
 * @property string $merchant_id
 * @property string $marketplace_id
 * @property string $marketplace_short
 * @property string $asin
 * @property string $product_id
 * @property string $sku
 * @property string $ean
 * @property string $upc
 * @property string $title
 * @property string $description
 * @property string $price
 * @property string $images_src
 * @property integer $stock
 * @property integer $condition
 * @property string $currency
 * @property string $images
 * @property string $detail_page_url
 * @property string $binding
 * @property string $brand
 * @property string $catalog_number
 * @property string $cat_str
 * @property string $color
 * @property string $size
 * @property string $feature
 * @property string $label
 * @property string $publisher
 * @property string $studio
 * @property string $manufacturer
 * @property string $model
 * @property string $mpn
 * @property string $part_number
 * @property string $childrens
 * @property string $parent_asin
 * @property string $relationships
 * @property string $product_group
 * @property string $product_type_name
 * @property string $status
 * @property integer $get_info_step
 * @property string $report_info
 * @property string $prod_info
 * @property integer $is_get_prod_info
 * @property integer $err_times
 * @property string $err_msg
 * @property string $batch_num
 * @property string $create_time
 * @property string $update_time
 */
class AmazonListing extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_listing';
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
            [['merchant_id', 'marketplace_id', 'marketplace_short', 'asin', 'status', 'batch_num', 'create_time'], 'required'],
            [['title', 'description', 'images_src', 'images', 'detail_page_url', 'cat_str', 'feature', 'childrens', 'relationships', 'report_info', 'prod_info'], 'string'],
            [['price'], 'number'],
            [['stock', 'condition', 'get_info_step', 'is_get_prod_info', 'err_times'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['merchant_id', 'marketplace_id', 'sku', 'ean', 'upc', 'binding', 'brand', 'catalog_number', 'color', 'size', 'label', 'publisher', 'studio', 'manufacturer', 'model', 'mpn', 'part_number', 'status'], 'string', 'max' => 100],
            [['marketplace_short'], 'string', 'max' => 2],
            [['asin', 'product_id', 'parent_asin', 'err_msg', 'batch_num'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 3],
            [['product_group', 'product_type_name'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'merchant_id' => 'Merchant ID',
            'marketplace_id' => 'Marketplace ID',
            'marketplace_short' => 'Marketplace Short',
            'asin' => 'Asin',
            'product_id' => 'Product ID',
            'sku' => 'Sku',
            'ean' => 'Ean',
            'upc' => 'Upc',
            'title' => 'Title',
            'description' => 'Description',
            'price' => 'Price',
            'images_src' => 'Images Src',
            'stock' => 'Stock',
            'condition' => 'Condition',
            'currency' => 'Currency',
            'images' => 'Images',
            'detail_page_url' => 'Detail Page Url',
            'binding' => 'Binding',
            'brand' => 'Brand',
            'catalog_number' => 'Catalog Number',
            'cat_str' => 'Cat Str',
            'color' => 'Color',
            'size' => 'Size',
            'feature' => 'Feature',
            'label' => 'Label',
            'publisher' => 'Publisher',
            'studio' => 'Studio',
            'manufacturer' => 'Manufacturer',
            'model' => 'Model',
            'mpn' => 'Mpn',
            'part_number' => 'Part Number',
            'childrens' => 'Childrens',
            'parent_asin' => 'Parent Asin',
            'relationships' => 'Relationships',
            'product_group' => 'Product Group',
            'product_type_name' => 'Product Type Name',
            'status' => 'Status',
            'get_info_step' => 'Get Info Step',
            'report_info' => 'Report Info',
            'prod_info' => 'Prod Info',
            'is_get_prod_info' => 'Is Get Prod Info',
            'err_times' => 'Err Times',
            'err_msg' => 'Err Msg',
            'batch_num' => 'Batch Num',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    public function getChildrenlisting(){
        return AmazonListing::find()->where(['asin'=>json_decode($this->childrens,true)])->all();
        // return $this->hasOne(AmazonListing::className(),[
        //     'asin'=>'childrens'
        // ]);
    }
    public function getParentlisting(){
        return AmazonListing::find()->where(['asin'=>$this->parent_asin])->one();
        // return $this->hasOne(AmazonListing::className(),[
        //     'asin'=>'childrens'
        // ]);
    }
}
