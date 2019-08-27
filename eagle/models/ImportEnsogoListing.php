<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "import_ensogo_listing".
 *
 * @property integer $id
 * @property string $aliexpress_category
 * @property string $ensogo_category
 * @property string $ensogo_store
 * @property string $parent_sku
 * @property string $sku
 * @property string $subject
 * @property string $color
 * @property string $size
 * @property string $tag
 * @property string $description
 * @property double $market_price
 * @property double $price
 * @property integer $stock
 * @property double $delivery_fee
 * @property string $delivery_duration
 * @property string $brand
 * @property string $upc
 * @property string $url
 * @property string $main_image
 * @property string $imageurl_1
 * @property string $imageurl_2
 * @property string $imageurl_3
 * @property string $imageurl_4
 * @property string $imageurl_5
 * @property string $imageurl_6
 * @property string $imageurl_7
 * @property string $imageurl_8
 * @property string $imageurl_9
 * @property string $imageurl_10
 * @property string $batch_num
 * @property integer $puid
 * @property integer $status
 * @property string $error_message
 */
class ImportEnsogoListing extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'import_ensogo_listing';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
    	return Yii::$app->get('db_queue');
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['description'], 'string'],
            [['market_price', 'price', 'delivery_fee'], 'number'],
            [['stock', 'puid', 'status'], 'integer'],
            [['puid'], 'required'],
            [['aliexpress_category', 'ensogo_category', 'ensogo_store', 'parent_sku', 'sku', 'subject', 'color', 'size', 'tag', 'delivery_duration', 'brand', 'upc', 'url', 'main_image', 'imageurl_1', 'imageurl_2', 'imageurl_3', 'imageurl_4', 'imageurl_5', 'imageurl_6', 'imageurl_7', 'imageurl_8', 'imageurl_9', 'imageurl_10', 'batch_num', 'error_message'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'aliexpress_category' => 'Aliexpress Category',
            'ensogo_category' => 'Ensogo Category',
            'ensogo_store' => 'Ensogo Store',
            'parent_sku' => 'Parent Sku',
            'sku' => 'Sku',
            'subject' => 'Subject',
            'color' => 'Color',
            'size' => 'Size',
            'tag' => 'Tag',
            'description' => 'Description',
            'market_price' => 'Market Price',
            'price' => 'Price',
            'stock' => 'Stock',
            'delivery_fee' => 'Delivery Fee',
            'delivery_duration' => 'Delivery Duration',
            'brand' => 'Brand',
            'upc' => 'Upc',
            'url' => 'Url',
            'main_image' => 'Main Image',
            'imageurl_1' => 'Imageurl 1',
            'imageurl_2' => 'Imageurl 2',
            'imageurl_3' => 'Imageurl 3',
            'imageurl_4' => 'Imageurl 4',
            'imageurl_5' => 'Imageurl 5',
            'imageurl_6' => 'Imageurl 6',
            'imageurl_7' => 'Imageurl 7',
            'imageurl_8' => 'Imageurl 8',
            'imageurl_9' => 'Imageurl 9',
            'imageurl_10' => 'Imageurl 10',
            'batch_num' => 'Batch Num',
            'puid' => 'Puid',
            'status' => 'Status',
            'error_message' => 'Error Message',
        ];
    }
}
