<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_retry_account".
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
class QueueAliexpressRetryAccount extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_retry_account';
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
            'id' => '主键',
            'uid' => '小老板主账号ID',
            'sellerloginid' => '速卖通登陆账户',
            'aliexpress_uid' => '速卖通账户表ID',
            'status' => '同步状态',
            'type' => '订单同步类型',
            'order_status' => 'Order Status',
            'orderid' => '速卖通订单号',
            'times' => '失败次数',
            'order_info' => '订单列表信息',
            'last_time' => '最后同步时间',
            'gmtcreate' => '速卖通订单创建时间',
            'message' => '错误信息',
            'create_time' => '创建时间',
            'update_time' => '修改时间',
            'next_time' => '待重试时间',
        ];
    }
}
