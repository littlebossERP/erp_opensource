<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "lazada_listing".
 *
 * @property integer $id
 * @property integer $lazada_uid_id
 * @property string $platform
 * @property string $site
 * @property string $SellerSku
 * @property string $ParentSku
 * @property string $ShopSku
 * @property string $Name
 * @property integer $Quantity
 * @property integer $Available
 * @property string $Price
 * @property string $SalePrice
 * @property integer $SaleStartDate
 * @property integer $SaleEndDate
 * @property string $Status
 * @property string $ProductId
 * @property string $Url
 * @property string $MainImage
 * @property string $Variation
 * @property integer $create_time
 * @property integer $update_time
 * @property string $sub_status
 * @property integer $is_editing
 * @property string $feed_id
 * @property string $error_message
 * @property string $operation_log
 * @property integer $FulfillmentBySellable
 * @property integer $FulfillmentByNonSellable
 * @property integer $ReservedStock
 * @property integer $RealTimeStock
 */
class LazadaListing extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_listing';
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
            [['lazada_uid_id', 'SellerSku', 'Status', 'create_time', 'update_time'], 'required'],
            [['lazada_uid_id', 'Quantity', 'Available', 'SaleStartDate', 'SaleEndDate', 'create_time', 'update_time', 'is_editing', 'FulfillmentBySellable', 'FulfillmentByNonSellable', 'ReservedStock', 'RealTimeStock'], 'integer'],
            [['Price', 'SalePrice'], 'number'],
            [['error_message', 'operation_log'], 'string'],
            [['platform'], 'string', 'max' => 20],
            [['site', 'Status'], 'string', 'max' => 10],
            [['SellerSku', 'ParentSku', 'Name', 'Url'], 'string', 'max' => 255],
            [['ShopSku', 'ProductId', 'Variation'], 'string', 'max' => 100],
            [['MainImage'], 'string', 'max' => 350],
            [['sub_status'], 'string', 'max' => 60],
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
            'lazada_uid_id' => 'Lazada Uid ID',
            'platform' => 'Platform',
            'site' => 'Site',
            'SellerSku' => 'Seller Sku',
            'ParentSku' => 'Parent Sku',
            'ShopSku' => 'Shop Sku',
            'Name' => 'Name',
            'Quantity' => 'Quantity',
            'Available' => 'Available',
            'Price' => 'Price',
            'SalePrice' => 'Sale Price',
            'SaleStartDate' => 'Sale Start Date',
            'SaleEndDate' => 'Sale End Date',
            'Status' => 'Status',
            'ProductId' => 'Product ID',
            'Url' => 'Url',
            'MainImage' => 'Main Image',
            'Variation' => 'Variation',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'sub_status' => 'Sub Status',
            'is_editing' => 'Is Editing',
            'feed_id' => 'Feed ID',
            'error_message' => 'Error Message',
            'operation_log' => 'Operation Log',
            'FulfillmentBySellable' => 'Fulfillment By Sellable',
            'FulfillmentByNonSellable' => 'Fulfillment By Non Sellable',
            'ReservedStock' => 'Reserved Stock',
            'RealTimeStock' => 'Real Time Stock',
        ];
    }
}
