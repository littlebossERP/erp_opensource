<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "amazon_listing_fetch_addi_info_queue".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $merchant_id
 * @property string $marketplace_id
 * @property string $asin
 * @property string $seller_sku
 * @property integer $process
 * @property integer $process_status
 * @property string $callback
 * @property string $prod_info
 * @property string $img_info
 * @property integer $priority
 * @property integer $err_cnt
 * @property string $err_msg
 * @property string $create_time
 * @property string $update_time
 */
class AmazonListingFetchAddiInfoQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_listing_fetch_addi_info_queue';
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
            [['puid', 'merchant_id', 'marketplace_id', 'asin', 'seller_sku'], 'required'],
            [['puid', 'process', 'process_status', 'priority', 'err_cnt'], 'integer'],
            [['callback', 'prod_info', 'img_info', 'err_msg'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['merchant_id', 'marketplace_id'], 'string', 'max' => 100],
            [['asin', 'seller_sku'], 'string', 'max' => 255]
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
            'marketplace_id' => 'Marketplace ID',
            'asin' => 'Asin',
            'seller_sku' => 'Seller Sku',
            'process' => 'Process',
            'process_status' => 'Process Status',
            'callback' => 'Callback',
            'prod_info' => 'Prod Info',
            'img_info' => 'Img Info',
            'priority' => 'Priority',
            'err_cnt' => 'Err Cnt',
            'err_msg' => 'Err Msg',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
