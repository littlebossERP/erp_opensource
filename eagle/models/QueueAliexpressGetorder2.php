<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_getorder".
 *
 * @property string $id
 * @property integer $uid
 * @property string $sellerloginid
 * @property integer $aliexpress_uid
 * @property integer $status
 * @property string $order_status
 * @property string $orderid
 * @property integer $times
 * @property string $order_info
 * @property integer $last_time
 * @property integer $gmtcreate
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 */
class QueueAliexpressGetorder2 extends \yii\db\ActiveRecord
{
	const FRIST = 1;
	const FINISH = 2;
	const NEWORDER = 3;
	const UPDATEORDER = 4;
	const NOFINISH = 5;
	public static $type = array(
			1=>'首次需要同步的未完成订单',
			2=>'首次需要同步的已完成订单',
			3=>'首次需要同步的新产生订单',
			4=>'订单状态发生变化的订单',
			5=>'未完成订单',
	);
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_getorder2';
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
            [['uid', 'aliexpress_uid', 'status', 'type' , 'orderid', 'times', 'last_time', 'gmtcreate', 'create_time', 'update_time','next_time'], 'integer'],
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
