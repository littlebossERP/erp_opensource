<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_oversea_warehouse_stock".
 *
 * @property string $id
 * @property string $oversea_warehouse_sku
 * @property string $sku
 * @property string $product_name
 * @property string $carrier_code
 * @property string $third_party_code
 * @property string $warehouse_id
 * @property string $qty_on_hand
 * @property string $qty_ordered
 * @property string $qty_reserved
 * @property string $qty_time_out
 * @property string $specification
 * @property string $create_time
 * @property string $update_time
 */
class OverseaWarehouseStock extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_oversea_warehouse_stock';
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
            [['warehouse_id', 'qty_on_hand', 'qty_ordered', 'qty_reserved', 'qty_time_out', 'create_time', 'update_time'], 'integer'],
            [['product_name', 'carrier_code', 'third_party_code'], 'string', 'max' => 50],
            [['sku','oversea_warehouse_sku','specification'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'oversea_warehouse_sku' => 'Oversea Warehouse Sku',
            'sku' => 'Sku',
            'product_name' => 'Product Name',
            'carrier_code' => 'Carrier Code',
            'third_party_code' => 'Third Party Code',
            'warehouse_id' => 'Warehouse ID',
            'qty_on_hand' => 'Qty On Hand',
            'qty_ordered' => 'Qty Ordered',
            'qty_reserved' => 'Qty Reserved',
            'qty_time_out' => 'Qty Time Out',
            'specification' => 'Specification',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
