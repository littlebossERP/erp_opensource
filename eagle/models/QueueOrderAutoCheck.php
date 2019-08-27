<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_order_auto_check".
 *
 * @property integer $id
 * @property integer $puid
 * @property integer $order_id
 * @property string $status
 * @property integer $retry_count
 * @property integer $priority
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $run_time
 * @property string $add_info
 */
class QueueOrderAutoCheck extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_order_auto_check';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue2');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'order_id', 'status', 'priority', 'create_time', 'update_time'], 'required'],
            [['puid', 'order_id', 'retry_count', 'priority', 'create_time', 'update_time', 'run_time'], 'integer'],
            [['add_info'], 'string'],
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
            'puid' => 'Puid',
            'order_id' => 'Order ID',
            'status' => 'Status',
            'retry_count' => 'Retry Count',
            'priority' => 'Priority',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'run_time' => 'Run Time',
            'add_info' => 'Add Info',
        ];
    }
}
