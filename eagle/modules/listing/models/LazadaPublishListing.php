<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "lazada_publish_listing".
 *
 * @property integer $id
 * @property integer $lazada_uid
 * @property string $platform
 * @property string $site
 * @property string $store_info
 * @property string $base_info
 * @property string $variant_info
 * @property string $image_info
 * @property string $description_info
 * @property string $shipping_info
 * @property string $warranty_info
 * @property string $state
 * @property string $status
 * @property string $feed_id
 * @property string $feed_info
 * @property string $uploaded_product
 * @property integer $create_time
 * @property integer $update_time
 */
class LazadaPublishListing extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_publish_listing';
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
            [['lazada_uid', 'site'], 'required'],
            [['lazada_uid', 'create_time', 'update_time'], 'integer'],
            [['store_info', 'base_info', 'variant_info', 'image_info', 'description_info', 'shipping_info', 'warranty_info', 'feed_info', 'uploaded_product'], 'string'],
            [['platform'], 'string', 'max' => 20],
            [['site'], 'string', 'max' => 10],
            [['state'], 'string', 'max' => 32],
            [['status'], 'string', 'max' => 64],
            [['feed_id'], 'string', 'max' => 127]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'lazada_uid' => 'Lazada Uid',
            'platform' => 'Platform',
            'site' => 'Site',
            'store_info' => 'Store Info',
            'base_info' => 'Base Info',
            'variant_info' => 'Variant Info',
            'image_info' => 'Image Info',
            'description_info' => 'Description Info',
            'shipping_info' => 'Shipping Info',
            'warranty_info' => 'Warranty Info',
            'state' => 'State',
            'status' => 'Status',
            'feed_id' => 'Feed ID',
            'feed_info' => 'Feed Info',
            'uploaded_product' => 'Uploaded Product',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
