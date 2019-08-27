<?php

namespace eagle\models\carrier;

use Yii;

/**
 * This is the model class for table "sys_tracking_number".
 *
 * @property integer $id
 * @property integer $shipping_service_id
 * @property string $service_name
 * @property string $tracking_number
 * @property integer $is_used
 * @property integer $order_id
 * @property integer $use_time
 * @property string $user_name
 * @property string $operator
 * @property integer $create_time
 * @property integer $update_time
 */
class SysTrackingNumber extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_tracking_number';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['shipping_service_id', 'tracking_number'], 'required'],
            [['shipping_service_id', 'is_used', 'order_id', 'use_time', 'create_time', 'update_time'], 'integer'],
            [['service_name'], 'string', 'max' => 255],
            [['tracking_number', 'user_name', 'operator'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'shipping_service_id' => '运输服务id',
            'service_name' => '运输服务名',
            'tracking_number' => '物流号',
            'is_used' => '是否被分配,使用状态',
            'order_id' => '小老板订单号',
            'use_time' => '分配时间 使用时间',
            'user_name' => '创建人',
            'operator' => '操作人',
            'create_time' => '创建时间',
            'update_time' => 'Update Time',
        ];
    }
}
