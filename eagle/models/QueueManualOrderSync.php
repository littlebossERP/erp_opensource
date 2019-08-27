<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_manual_order_sync".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $sellerloginid
 * @property string $order_id
 * @property string $status
 * @property string $platform
 * @property string $create_time
 * @property string $update_time
 * @property string $addi_info
 * @property integer $runtime
 * @property integer $retry_count
 * @property integer $priority
 * @property string $err_msg
 */
class QueueManualOrderSync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_manual_order_sync';
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
            [['puid', 'sellerloginid', 'order_id', 'status', 'platform', 'create_time'], 'required'],
            [['puid', 'runtime', 'retry_count', 'priority'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['addi_info', 'err_msg'], 'string'],
            [['sellerloginid'], 'string', 'max' => 100],
            [['order_id'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 1],
            [['platform'], 'string', 'max' => 30]
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
            'sellerloginid' => 'Sellerloginid',
            'order_id' => 'Order ID',
            'status' => 'Status',
            'platform' => 'Platform',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'addi_info' => 'Addi Info',
            'runtime' => 'Runtime',
            'retry_count' => 'Retry Count',
            'priority' => 'Priority',
            'err_msg' => 'Err Msg',
        ];
    }
}
