<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_product_log".
 *
 * @property integer $id
 * @property string $create_time
 * @property string $update_time
 * @property string $status
 * @property integer $total_product
 * @property integer $total_variance
 * @property string $platform
 * @property integer $operator
 * @property string $shop
 */
class QueueProductLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_product_log';
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
            [['total_product', 'total_variance', 'operator'], 'integer'],
            [['status'], 'string', 'max' => 1],
            [['platform'], 'string', 'max' => 50],
            [['shop'], 'string', 'max' => 255]
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
            'platform' => 'Platform',
            'operator' => 'Operator',
            'shop' => 'Shop',
        ];
    }
}
