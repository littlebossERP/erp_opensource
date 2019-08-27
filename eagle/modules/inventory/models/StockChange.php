<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_stock_change".
 *
 * @property string $stock_change_id
 * @property integer $warehouse_id
 * @property integer $change_type
 * @property integer $reason
 * @property string $comment
 * @property string $addi_info
 * @property integer $capture_user_id
 * @property string $create_time
 * @property string $update_time
 */
class StockChange extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_stock_change';
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
            [['stock_change_id'], 'required'],
            [['warehouse_id', 'change_type', 'reason', 'capture_user_id', 'source_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['stock_change_id'], 'string', 'max' => 255],
            [['comment', 'addi_info'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'stock_change_id' => 'Stock Change ID',
            'warehouse_id' => 'Warehouse ID',
            'change_type' => 'Change Type',
            'reason' => 'Reason',
            'comment' => 'Comment',
            'addi_info' => 'Addi Info',
            'capture_user_id' => 'Capture User ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'source_id' => 'Source Id',
        ];
    }
}
