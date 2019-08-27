<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_lazada_getorder_v2".
 *
 * @property string $id
 * @property integer $puid
 * @property integer $lazada_uid
 * @property string $platform
 * @property string $site
 * @property integer $status
 * @property string $order_status
 * @property string $orderid
 * @property integer $error_times
 * @property string $order_info
 * @property integer $last_finish_time
 * @property integer $gmtcreate
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $type
 * @property integer $is_active
 */
class QueueLazadaGetorderV2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_lazada_getorder_v2';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'lazada_uid', 'status', 'orderid', 'error_times', 'gmtcreate'], 'required'],
            [['puid', 'lazada_uid', 'status', 'orderid', 'error_times', 'last_finish_time', 'gmtcreate', 'create_time', 'update_time', 'type', 'is_active'], 'integer'],
            [['order_info', 'message'], 'string'],
            [['platform'], 'string', 'max' => 20],
            [['site'], 'string', 'max' => 10],
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
            'puid' => 'Puid',
            'lazada_uid' => 'Lazada Uid',
            'platform' => 'Platform',
            'site' => 'Site',
            'status' => 'Status',
            'order_status' => 'Order Status',
            'orderid' => 'Orderid',
            'error_times' => 'Error Times',
            'order_info' => 'Order Info',
            'last_finish_time' => 'Last Finish Time',
            'gmtcreate' => 'Gmtcreate',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'type' => 'Type',
            'is_active' => 'Is Active',
        ];
    }
}
