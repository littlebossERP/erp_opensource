<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_shopee_getorder".
 *
 * @property string $id
 * @property integer $uid
 * @property string $shop_id
 * @property string $site
 * @property integer $shopee_uid
 * @property integer $status
 * @property integer $type
 * @property string $order_status
 * @property string $orderid
 * @property integer $times
 * @property integer $last_time
 * @property integer $gmtupdate
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $next_time
 */
class QueueShopeeGetorder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_shopee_getorder';
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
            [['uid', 'shop_id', 'site', 'shopee_uid', 'status', 'orderid', 'times', 'gmtupdate'], 'required'],
            [['uid', 'shopee_uid', 'status', 'type', 'orderid', 'times', 'last_time', 'gmtupdate', 'create_time', 'update_time', 'next_time'], 'integer'],
            [['message'], 'string'],
            [['shop_id', 'order_status'], 'string', 'max' => 50],
            [['site'], 'string', 'max' => 10]
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
            'shop_id' => 'Shop ID',
            'site' => 'Site',
            'shopee_uid' => 'Shopee Uid',
            'status' => 'Status',
            'type' => 'Type',
            'order_status' => 'Order Status',
            'orderid' => 'Orderid',
            'times' => 'Times',
            'last_time' => 'Last Time',
            'gmtupdate' => 'Gmtupdate',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'next_time' => 'Next Time',
        ];
    }
}
