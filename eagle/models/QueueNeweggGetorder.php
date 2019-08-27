<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_newegg_getorder".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $sellerID
 * @property integer $status
 * @property integer $order_source_order_id
 * @property integer $order_status
 * @property string $order_info_md5
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $last_start_time
 * @property integer $last_finish_time
 * @property integer $next_execution_time
 * @property integer $error_times
 * @property string $message
 * @property integer $type
 * @property integer $is_active
 */
class QueueNeweggGetorder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_newegg_getorder';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'sellerID'], 'required'],
            [['uid', 'status', 'order_source_order_id', 'order_status', 'create_time', 'update_time', 'last_start_time', 'last_finish_time', 'next_execution_time', 'error_times', 'type', 'is_active'], 'integer'],
            [['order_info_md5', 'message'], 'string'],
            [['sellerID'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'sellerID' => 'Seller ID',
            'status' => 'Status',
            'order_source_order_id' => 'Order Source Order ID',
            'order_status' => 'Order Status',
            'order_info_md5' => 'Order Info Md5',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'last_start_time' => 'Last Start Time',
            'last_finish_time' => 'Last Finish Time',
            'next_execution_time' => 'Next Execution Time',
            'error_times' => 'Error Times',
            'message' => 'Message',
            'type' => 'Type',
            'is_active' => 'Is Active',
        ];
    }
}
