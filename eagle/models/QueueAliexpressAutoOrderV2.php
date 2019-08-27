<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_auto_order_v2".
 *
 * @property integer $id
 * @property string $sellerloginid
 * @property string $order_status
 * @property string $last_status
 * @property string $order_id
 * @property integer $order_change_time
 * @property string $msg_id
 * @property string $order_type
 * @property string $gmtBorn
 * @property string $ajax_message
 * @property integer $status
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $last_time
 * @property integer $next_time
 * @property integer $times
 */
class QueueAliexpressAutoOrderV2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_auto_order_v2';
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
            [['order_id', 'order_change_time', 'msg_id', 'gmtBorn', 'status', 'create_time', 'update_time', 'last_time', 'next_time', 'times'], 'integer'],
            [['ajax_message', 'message'], 'string'],
            [['status'], 'required'],
            [['sellerloginid', 'order_status', 'last_status', 'order_type'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'sellerloginid' => 'Sellerloginid',
            'order_status' => 'Order Status',
            'last_status' => 'Last Status',
            'order_id' => 'Order ID',
            'order_change_time' => 'Order Change Time',
            'msg_id' => 'Msg ID',
            'order_type' => 'Order Type',
            'gmtBorn' => 'Gmt Born',
            'ajax_message' => 'Ajax Message',
            'status' => 'Status',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'last_time' => 'Last Time',
            'next_time' => 'Next Time',
            'times' => 'Times',
        ];
    }
}
