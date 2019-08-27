<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_product_stock".
 *
 * @property integer $prod_stock_id
 * @property integer $warehouse_id
 * @property string $sku
 * @property string $location_grid
 * @property integer $qty_in_stock
 * @property integer $qty_purchased_coming
 * @property integer $qty_ordered
 * @property integer $qty_order_reserved
 * @property string $average_price
 * @property string $total_purchased
 * @property string $addi_info
 * @property string $update_time
 */
class ProductStock extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_product_stock';
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
            [['warehouse_id', 'qty_in_stock', 'qty_purchased_coming', 'qty_ordered', 'qty_order_reserved', 'total_purchased'], 'integer'],
            [['average_price'], 'number'],
            [['update_time'], 'safe'],            
            [['location_grid'], 'string', 'max' => 45],
            [['addi_info','sku'], 'string', 'max' => 255],
            [['warehouse_id', 'sku'], 'unique', 'targetAttribute' => ['warehouse_id', 'sku'], 'message' => 'The combination of Warehouse ID and Sku has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'prod_stock_id' => 'Prod Stock ID',
            'warehouse_id' => 'Warehouse ID',
            'sku' => 'Sku',
            'location_grid' => 'Location Grid',
            'qty_in_stock' => 'Qty In Stock',
            'qty_purchased_coming' => 'Qty Purchased Coming',
            'qty_ordered' => 'Qty Ordered',
            'qty_order_reserved' => 'Qty Order Reserved',
            'average_price' => 'Average Price',
            'total_purchased' => 'Total Purchased',
            'addi_info' => 'Addi Info',
            'update_time' => 'Update Time',
        ];
    }
}
