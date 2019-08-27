<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_order_reserve_product".
 *
 * @property integer $id
 * @property string $order_id
 * @property integer $package_id
 * @property integer $warehouse_id
 * @property string $sku
 * @property integer $reserved_qty
 * @property integer $reserved_qty_on_the_way
 * @property string $reserve_time
 */
class OrderReserveProduct extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_order_reserve_product';
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
            [['package_id', 'warehouse_id', 'reserved_qty', 'reserved_qty_on_the_way'], 'integer'],
            [['sku'], 'required'],
            [['reserve_time'], 'safe'],
            [['sku'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'package_id' => 'Package ID',
            'warehouse_id' => 'Warehouse ID',
            'sku' => 'Sku',
            'reserved_qty' => 'Reserved Qty',
            'reserved_qty_on_the_way' => 'Reserved Qty On The Way',
            'reserve_time' => 'Reserve Time',
        ];
    }
}
