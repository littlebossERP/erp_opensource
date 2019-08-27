<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_retry_account_info".
 *
 * @property string $id
 * @property integer $uid
 * @property string $sellerloginid
 * @property string $orderid
 * @property integer $times
 * @property integer $last_time
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $next_time
 */
class QueueAliexpressRetryAccountInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_retry_account_info';
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
            [['uid', 'sellerloginid', 'orderid', 'times'], 'required'],
            [['uid', 'orderid', 'times', 'last_time', 'create_time', 'update_time', 'next_time'], 'integer'],
            [['message'], 'string'],
            [['sellerloginid'], 'string', 'max' => 100]
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
            'orderid' => '速卖通订单号',
            'times' => '失败次数',
            'last_time' => '最后同步时间',
            'message' => '错误信息',
            'create_time' => '创建时间',
            'update_time' => '修改时间',
            'next_time' => '待重试时间',
        ];
    }
}
