<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_stock_allocation".
 *
 * @property integer $id
 * @property string $stock_allocatione_id
 * @property integer $in_warehouse_id
 * @property integer $out_warehouse_id
 * @property integer $number_of_sku
 * @property string $comment
 * @property integer $capture_user_id
 * @property string $create_time
 * @property string $update_time
 */
class StockAllocation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_stock_allocation';
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
            [['stock_allocatione_id'], 'required'],
            [['in_warehouse_id', 'out_warehouse_id', 'number_of_sku', 'capture_user_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['stock_allocatione_id'], 'string', 'max' => 30],
            [['comment'], 'string', 'max' => 255],
            [['stock_allocatione_id'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stock_allocatione_id' => 'Stock Allocatione ID',
            'in_warehouse_id' => 'In Warehouse ID',
            'out_warehouse_id' => 'Out Warehouse ID',
            'number_of_sku' => 'Number Of Sku',
            'comment' => 'Comment',
            'capture_user_id' => 'Capture User ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
