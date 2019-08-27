<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_getorder_v2".
 *
 * @property string $id
 * @property integer $uid
 * @property string $sellerloginid
 * @property integer $aliexpress_uid
 * @property integer $status
 * @property integer $type
 * @property string $order_status
 * @property string $orderid
 * @property integer $times
 * @property string $order_info
 * @property integer $last_time
 * @property integer $gmtcreate
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $next_time
 */
class QueueAliexpressGetorderV2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_getorder_v2';
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
            [['uid', 'sellerloginid', 'aliexpress_uid', 'status', 'orderid', 'times', 'gmtcreate'], 'required'],
            [['uid', 'aliexpress_uid', 'status', 'type', 'orderid', 'times', 'last_time', 'gmtcreate', 'create_time', 'update_time', 'next_time'], 'integer'],
            [['order_info', 'message'], 'string'],
            [['sellerloginid'], 'string', 'max' => 100],
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
            'sellerloginid' => 'Sellerloginid',
            'aliexpress_uid' => 'Aliexpress Uid',
            'status' => 'Status',
            'type' => 'Type',
            'order_status' => 'Order Status',
            'orderid' => 'Orderid',
            'times' => 'Times',
            'order_info' => 'Order Info',
            'last_time' => 'Last Time',
            'gmtcreate' => 'Gmtcreate',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'next_time' => 'Next Time',
        ];
    }
}
