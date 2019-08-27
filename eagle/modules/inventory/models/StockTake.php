<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_stock_take".
 *
 * @property string $stock_take_id
 * @property integer $warehouse_id
 * @property integer $number_of_sku
 * @property string $comment
 * @property integer $capture_user_id
 * @property string $create_time
 * @property string $update_time
 */
class StockTake extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_stock_take';
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
            [['warehouse_id', 'number_of_sku', 'capture_user_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['comment'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'stock_take_id' => 'Stock Take ID',
            'warehouse_id' => 'Warehouse ID',
            'number_of_sku' => 'Number Of Sku',
            'comment' => 'Comment',
            'capture_user_id' => 'Capture User ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
