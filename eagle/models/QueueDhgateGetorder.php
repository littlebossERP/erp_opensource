<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_dhgate_getorder".
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
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $type
 * @property integer $is_active
 */
class QueueDhgateGetorder extends \yii\db\ActiveRecord
{
    // const FIRST = 1;
    // const FINISH = 2;
    // const NEWORDER = 3;
    // const UPDATEORDER = 4;
    // const NOFINISH = 5;
    // public static $type = array(
    //      1=>'首次需要同步的未完成订单',
    //      2=>'首次需要同步的已完成订单',
    //      3=>'首次需要同步的新产生订单',
    //      4=>'订单状态发生变化的订单',
    //      5=>'未完成订单',
    // );
    const OLD_UNFININSHED = 1;
    const OLD_FINISH = 2;
    const NEW_ORDER = 3;
    const DAILY_QUERY = 4;
    public static $type = array(
         1=>'绑定前的未完成订单',
         2=>'绑定前的已完成订单',
         3=>'绑定后的新产生订单',
         4=>'日常查询未完成的订单',
    );
    // 订单详情拉用到这个数组的 key值，加减要留意
    public static $orderStatus = array(
            ''=>'',
            111000=>'订单取消',
            101003=>'等待买家付款',
            102001=>'买家已付款，等待平台确认',
            103001=>'等待发货',
            105001=>'买家申请退款，等待协商结果',
            105002=>'退款协议已达成',
            105003=>'部分退款后，等待发货',
            105004=>'买家取消退款申请',
            103002=>'已部分发货',
            101009=>'等待买家确认收货',
            106001=>'退款/退货协商中，等待协议达成',
            106002=>'买家投诉到平台',
            106003=>'协议已达成，执行中',
            102006=>'人工确认收货',
            102007=>'超过预定期限，自动确认收货',
            102111=>'交易成功',
            111111=>'交易关闭'
    );
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_dhgate_getorder';
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
            [['uid', 'dhgate_uid', 'status', 'orderid', 'times', 'last_time', 'gmtcreate', 'create_time', 'update_time', 'type', 'is_active'], 'integer'],
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
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'type' => 'Type',
            'is_active' => 'Is Active',
        ];
    }
}
