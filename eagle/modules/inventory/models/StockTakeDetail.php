<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_stock_take_detail".
 *
 * @property integer $id
 * @property integer $stock_take_id
 * @property string $sku
 * @property string $product_name
 * @property string $location_grid
 * @property integer $qty_shall_be
 * @property integer $qty_actual
 * @property integer $qty_reported
 */
class StockTakeDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_stock_take_detail';
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
            [['stock_take_id', 'qty_shall_be', 'qty_actual', 'qty_reported'], 'integer'],
            [['sku', 'product_name'], 'string', 'max' => 255],
            [['location_grid'], 'string', 'max' => 45]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stock_take_id' => 'Stock Take ID',
            'sku' => 'Sku',
            'product_name' => 'Product Name',
            'location_grid' => 'Location Grid',
            'qty_shall_be' => 'Qty Shall Be',
            'qty_actual' => 'Qty Actual',
            'qty_reported' => 'Qty Reported',
        ];
    }
}
