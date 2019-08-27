<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "amazon_temp_orderid_queue_highpriority".
 *
 * @property string $order_id
 * @property integer $saas_platform_autosync_id
 * @property string $type
 * @property integer $process_status
 * @property integer $error_count
 * @property string $error_message
 * @property integer $create_time
 * @property integer $update_time
 * @property string $order_header_json
 * @property integer $puid
 */
class AmazonTempOrderidQueueHighpriority extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_temp_orderid_queue_highpriority';
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
            [['order_id', 'saas_platform_autosync_id', 'process_status', 'error_count', 'order_header_json', 'puid'], 'required'],
            [['saas_platform_autosync_id', 'process_status', 'error_count', 'create_time', 'update_time', 'puid'], 'integer'],
            [['error_message', 'order_header_json'], 'string'],
            [['order_id'], 'string', 'max' => 50],
            [['type'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'order_id' => 'Order ID',
            'saas_platform_autosync_id' => 'Saas Platform Autosync ID',
            'type' => 'Type',
            'process_status' => 'Process Status',
            'error_count' => 'Error Count',
            'error_message' => 'Error Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'order_header_json' => 'Order Header Json',
            'puid' => 'Puid',
        ];
    }
}
