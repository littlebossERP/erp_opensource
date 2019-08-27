<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "amazon_temp_listing".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $merchant_id
 * @property string $marketplace_short
 * @property string $title
 * @property string $SKU
 * @property string $ASIN
 * @property string $Price
 * @property integer $Quantity
 * @property string $cat_str
 * @property string $image_url
 * @property string $report_info
 * @property string $prod_info
 * @property integer $is_get_prod_info
 * @property integer $err_times
 * @property string $err_msg
 * @property string $batch_num
 * @property string $create_time
 * @property string $update_time
 */
class AmazonTempListing extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_temp_listing';
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
            [['puid', 'merchant_id', 'marketplace_short', 'ASIN', 'batch_num', 'create_time'], 'required'],
            [['puid', 'Quantity', 'is_get_prod_info', 'err_times'], 'integer'],
            [['Price'], 'number'],
            [['cat_str', 'image_url', 'report_info', 'prod_info'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['merchant_id'], 'string', 'max' => 50],
            [['marketplace_short'], 'string', 'max' => 2],
            [['title', 'SKU', 'ASIN', 'err_msg', 'batch_num'], 'string', 'max' => 255]
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
            'merchant_id' => 'Merchant ID',
            'marketplace_short' => 'Marketplace Short',
            'title' => 'Title',
            'SKU' => 'Sku',
            'ASIN' => 'Asin',
            'Price' => 'Price',
            'Quantity' => 'Quantity',
            'cat_str' => 'Cat Str',
            'image_url' => 'Image Url',
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
}
