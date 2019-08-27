<?php

namespace eagle\models;

use Yii;
use yii\behaviors\SerializeBehavior;
/**
 * This is the model class for table "queue_syncshipped".
 *
 * @property string $id
 * @property integer $uid
 * @property string $selleruserid
 * @property string $order_source
 * @property string $order_source_order_id
 * @property string $order_source_transaction_id
 * @property integer $osid
 * @property string $shipping_method_code
 * @property string $tracking_number
 * @property string $tracking_link
 * @property string $description
 * @property string $signtype
 * @property string $params
 * @property integer $status
 * @property integer $created
 */
class QueueSyncshipped extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_syncshipped';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'selleruserid', 'order_source', 'order_source_order_id', 'osid'], 'required'],
            [['uid', 'osid', 'status', 'created'], 'integer'],
            [['selleruserid'], 'string', 'max' => 80],
            [['order_source', 'order_source_order_id','shipping_method_code', 'tracking_number'], 'string', 'max' => 50],
            [['tracking_link'], 'string', 'max' => 100],
            [['description'], 'string', 'max' => 255],
            [['signtype'], 'string', 'max' => 10]
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
            'selleruserid' => '卖家账号',
            'order_source' => '订单来源  ebay,amazon,aliexpress,wish,custom',
            'order_source_order_id' => '订单来源  的订单id',
            'osid' => 'od_order_shipped主键',
            'shipping_method_code' => '平台运输服务代码',
            'tracking_number' => '物流号',
            'tracking_link' => '物流号查询网址',
            'description' => '发货备注',
            'signtype' => '全部发货all 部分发货part',
            'params' => '其它特殊参数',
            'status' => '订单标记发货状态，0未处理，1成功，2失败',
            'created' => 'Created',
        ];
    }
    
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('params'),
    			)
    	);
    }
}
