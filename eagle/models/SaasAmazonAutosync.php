<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_amazon_autosync".
 *
 * @property integer $id
 * @property integer $amazon_user_id
 * @property string $merchant_id
 * @property string $marketplace_id
 * @property integer $status
 * @property integer $process_status
 * @property integer $last_finish_time
 * @property integer $fetch_begin_time
 * @property integer $fetch_end_time
 * @property integer $has_massive_order
 * @property integer $max_from_to_interval
 * @property integer $next_execute_time
 * @property integer $execute_interval
 * @property integer $error_count
 * @property string $error_message
 * @property integer $dividing_time
 * @property integer $old_start_time
 * @property integer $old_last_finish_time
 * @property integer $old_begin_time
 * @property integer $old_end_time
 * @property integer $old_next_execute_time
 * @property integer $old_error_count
 * @property string $old_error_message
 */
class SaasAmazonAutosync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_amazon_autosync';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['amazon_user_id', 'merchant_id', 'marketplace_id', 'status', 'process_status', 'execute_interval'], 'required'],
            [['amazon_user_id', 'status', 'process_status', 'last_finish_time', 'fetch_begin_time', 'fetch_end_time', 'has_massive_order', 'max_from_to_interval', 'next_execute_time', 'execute_interval', 'error_count', 'dividing_time', 'old_start_time', 'old_last_finish_time', 'old_begin_time', 'old_end_time', 'old_next_execute_time', 'old_error_count'], 'integer'],
            [['error_message', 'old_error_message'], 'string'],
            [['merchant_id', 'marketplace_id'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'amazon_user_id' => 'Amazon User ID',
            'merchant_id' => 'Merchant ID',
            'marketplace_id' => 'Marketplace ID',
            'status' => 'Status',
            'process_status' => 'Process Status',
            'last_finish_time' => 'Last Finish Time',
            'fetch_begin_time' => 'Fetch Begin Time',
            'fetch_end_time' => 'Fetch End Time',
            'has_massive_order' => 'Has Massive Order',
            'max_from_to_interval' => 'Max From To Interval',
            'next_execute_time' => 'Next Execute Time',
            'execute_interval' => 'Execute Interval',
            'error_count' => 'Error Count',
            'error_message' => 'Error Message',
            'dividing_time' => 'Dividing Time',
            'old_start_time' => 'Old Start Time',
            'old_last_finish_time' => 'Old Last Finish Time',
            'old_begin_time' => 'Old Begin Time',
            'old_end_time' => 'Old End Time',
            'old_next_execute_time' => 'Old Next Execute Time',
            'old_error_count' => 'Old Error Count',
            'old_error_message' => 'Old Error Message',
        ];
    }
}
