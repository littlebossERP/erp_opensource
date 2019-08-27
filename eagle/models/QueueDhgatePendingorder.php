<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_dhgate_pendingorder".
 *
 * @property string $id
 * @property integer $uid
 * @property integer $dhgate_uid
 * @property integer $status
 * @property string $order_status
 * @property string $orderid
 * @property integer $times
 * @property string $order_info
 * @property integer $last_time
 * @property integer $gmtcreate
 * @property string $message
 * @property integer $next_execute_time
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $type
 * @property integer $is_active
 */
class QueueDhgatePendingorder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_dhgate_pendingorder';
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
            [['uid', 'dhgate_uid', 'status', 'orderid', 'times', 'gmtcreate'], 'required'],
            [['uid', 'dhgate_uid', 'status', 'orderid', 'times', 'last_time', 'gmtcreate', 'next_execute_time', 'create_time', 'update_time', 'type', 'is_active'], 'integer'],
            [['order_info', 'message'], 'string'],
            [['order_status'], 'string', 'max' => 50]
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
            'dhgate_uid' => 'Dhgate Uid',
            'status' => 'Status',
            'order_status' => 'Order Status',
            'orderid' => 'Orderid',
            'times' => 'Times',
            'order_info' => 'Order Info',
            'last_time' => 'Last Time',
            'gmtcreate' => 'Gmtcreate',
            'message' => 'Message',
            'next_execute_time' => 'Next Execute Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'type' => 'Type',
            'is_active' => 'Is Active',
        ];
    }
}
