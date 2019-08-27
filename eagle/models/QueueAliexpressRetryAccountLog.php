<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_retry_account_log".
 *
 * @property string $id
 * @property integer $uid
 * @property string $sellerloginid
 * @property string $orderid
 * @property string $message
 * @property integer $create_time
 */
class QueueAliexpressRetryAccountLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_retry_account_log';
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
            [['uid', 'sellerloginid', 'orderid'], 'required'],
            [['uid', 'orderid', 'create_time'], 'integer'],
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
            'message' => '错误信息',
            'create_time' => '创建时间',
        ];
    }
}
