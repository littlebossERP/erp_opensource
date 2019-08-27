<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_warehouse_has_shipping_method".
 *
 * @property string $id
 * @property string $warehouse_id
 * @property string $carrier_code
 * @property string $shipping_method_code
 * @property string $extra_info
 * @property string $pre_weight
 * @property string $pre_weight_price
 * @property string $add_weight
 * @property string $add_weight_price
 * @property string $create_time
 * @property string $update_time
 */
class WarehouseHasShippingMethod extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_warehouse_has_shipping_method';
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
            [['warehouse_id', 'create_time', 'update_time'], 'integer'],
            [['extra_info'], 'required'],
            [['extra_info'], 'string'],
            [['pre_weight', 'pre_weight_price', 'add_weight', 'add_weight_price'], 'number'],
            [['carrier_code', 'shipping_method_code'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'warehouse_id' => 'Warehouse ID',
            'carrier_code' => 'Carrier Code',
            'shipping_method_code' => 'Shipping Method Code',
            'extra_info' => 'Extra Info',
            'pre_weight' => 'Pre Weight',
            'pre_weight_price' => 'Pre Weight Price',
            'add_weight' => 'Add Weight',
            'add_weight_price' => 'Add Weight Price',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
