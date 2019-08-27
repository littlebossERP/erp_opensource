<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "aliexpress_listing".
 *
 * @property integer $id
 * @property string $productid
 * @property string $freight_template_id
 * @property string $owner_member_seq
 * @property string $subject
 * @property string $photo_primary
 * @property string $imageurls
 * @property string $selleruserid
 * @property integer $ws_offline_date
 * @property double $product_min_price
 * @property string $ws_display
 * @property double $product_max_price
 * @property integer $gmt_modified
 * @property integer $gmt_create
 * @property integer $sku_stock
 * @property integer $created
 * @property integer $updated
 * @property integer $puid
 * @property integer $product_status
 * @property integer $edit_status
 */
class AliexpressListing extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_listing';
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
            [['imageurls'], 'string'],
            [['ws_offline_date', 'gmt_modified', 'gmt_create', 'sku_stock', 'created', 'updated', 'puid', 'product_status', 'edit_status'], 'integer'],
            [['product_min_price', 'product_max_price'], 'number'],
            [['productid', 'freight_template_id', 'owner_member_seq', 'selleruserid'], 'string', 'max' => 20],
            [['subject', 'photo_primary'], 'string', 'max' => 255],
            [['ws_display'], 'string', 'max' => 50]
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
            'freight_template_id' => 'Freight Template ID',
            'owner_member_seq' => 'Owner Member Seq',
            'subject' => 'Subject',
            'photo_primary' => 'Photo Primary',
            'imageurls' => 'Imageurls',
            'selleruserid' => 'Selleruserid',
            'ws_offline_date' => 'Ws Offline Date',
            'product_min_price' => 'Product Min Price',
            'ws_display' => 'Ws Display',
            'product_max_price' => 'Product Max Price',
            'gmt_modified' => 'Gmt Modified',
            'gmt_create' => 'Gmt Create',
            'sku_stock' => 'Sku Stock',
            'created' => 'Created',
            'updated' => 'Updated',
            'puid' => 'Puid',
            'product_status' => 'Product Status',
            'edit_status' => 'Edit Status',
        ];
    }
}
