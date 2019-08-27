<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "wish_product_queue_log".
 *
 * @property integer $id
 * @property string $create_time
 * @property string $update_time
 * @property string $status
 * @property integer $total_product
 * @property integer $total_variance
 */
class WishProductQueueLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wish_product_queue_log';
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
            [['create_time', 'update_time'], 'safe'],
            [['total_product', 'total_variance'], 'integer'],
            [['status'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'status' => 'Status',
            'total_product' => 'Total Product',
            'total_variance' => 'Total Variance',
        ];
    }
}
