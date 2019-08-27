<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_getorder4".
 *
 * @property string $id
 * @property integer $uid
 * @property string $sellerloginid
 * @property integer $aliexpress_uid

 * @property string $order_status
 * @property string $orderid

 * @property string $order_info

 * @property integer $gmtcreate


 */
class QueueAliexpressGetorder4 extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_getorder4';
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
            [['uid', 'sellerloginid', 'aliexpress_uid', 'orderid', 'gmtcreate'], 'required'],
            [['uid', 'aliexpress_uid', 'orderid', 'gmtcreate'], 'integer'],
            [['order_info'], 'string'],
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
            'order_status' => 'Order Status',
            'orderid' => 'Orderid',
            'order_info' => 'Order Info',
            'gmtcreate' => 'Gmtcreate'
        ];
    }
}
