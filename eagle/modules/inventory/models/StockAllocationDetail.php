<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_stock_allocation_detail".
 *
 * @property integer $id
 * @property integer $allocatione_id
 * @property string $sku
 * @property string $location_grid
 * @property integer $qty
 */
class StockAllocationDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_stock_allocation_detail';
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
            [['allocatione_id'], 'required'],
            [['allocatione_id', 'qty'], 'integer'],
            [['sku'], 'string', 'max' => 255],
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
            'allocatione_id' => 'Allocatione ID',
            'sku' => 'Sku',
            'location_grid' => 'Location Grid',
            'qty' => 'Qty',
        ];
    }
}
