<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "aliexpress_listing_detail".
 *
 * @property integer $id
 * @property string $productid
 * @property string $categoryid
 * @property string $selleruserid
 * @property double $product_price
 * @property double $product_gross_weight
 * @property integer $product_length
 * @property integer $product_width
 * @property integer $product_height
 * @property string $currencyCode
 * @property string $aeopAeProductPropertys
 * @property string $aeopAeProductSKUs
 * @property string $detail
 */
class AliexpressListingDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_listing_detail';
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
            [['categoryid'], 'required'],
            [['product_price', 'product_gross_weight'], 'number'],
            [['product_length', 'product_width', 'product_height','package_type','reduce_strategy','delivery_time','bulk_order','bulk_discount','isPackSell','baseUnit','addUnit','promise_templateid','wsValidNum','listen_id','lot_num','is_bulk'], 'integer'],
            [['aeopAeProductPropertys', 'aeopAeProductSKUs', 'detail','product_groups','product_unit','addWeight','product_mv'], 'string'],
            [['productid', 'categoryid', 'selleruserid'], 'string', 'max' => 20],
            [['currencyCode'], 'string', 'max' => 5]
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
            'categoryid' => 'Categoryid',
            'selleruserid' => 'Selleruserid',
            'product_price' => 'Product Price',
            'product_gross_weight' => 'Product Gross Weight',
            'product_length' => 'Product Length',
            'product_width' => 'Product Width',
            'product_height' => 'Product Height',
            'currencyCode' => 'Currency Code',
            'aeopAeProductPropertys' => 'Aeop Ae Product Propertys',
            'aeopAeProductSKUs' => 'Aeop Ae Product Skus',
            'detail' => 'Detail',
            'product_groups' => 'Product Groups',
            'product_unit' => 'Product Unit',
            'package_type' => 'Package Type',
            'reduce_strategy' => 'Reduce Strategy',
            'delivery_time' => 'Delivery Time',
            'bulk_order' => 'Bulk Order',
            'Bulk Discount' => 'Bulk Discount',
            'isPackSell' => 'Is Pack Sell',
            'baseUnit' => 'Base Unit',
            'addUnit' => 'Add Unit',
            'promise_templateid' => 'Promise Template Id',
            'addWeight'=> 'Add Weight',
            'wsValidNum'=> 'Ws Valid Num',
            'listen_id'=> 'Listen Id',
            'lot_num'=> 'Lot Num',
            'is_bulk'=>'Is Bulk',
            'product_mv'=>'Product Mv'
        ];
    }
}
