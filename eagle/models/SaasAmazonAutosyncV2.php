<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_amazon_autosync_v2".
 *
 * @property integer $id
 * @property integer $eagle_platform_user_id
 * @property string $platform_user_id
 * @property string $site_id
 * @property integer $status
 * @property integer $process_status
 * @property string $type
 * @property integer $execution_interval
 * @property integer $fetch_begin_time
 * @property integer $fetch_end_time
 * @property integer $last_finish_time
 * @property integer $next_execute_time
 * @property integer $deadline_time
 * @property integer $slip_window_size
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $err_cnt
 * @property string $err_msg
 */
class SaasAmazonAutosyncV2 extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_amazon_autosync_v2';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['eagle_platform_user_id', 'platform_user_id', 'site_id', 'status', 'process_status', 'type', 'execution_interval', 'create_time'], 'required'],
            [['eagle_platform_user_id', 'status', 'process_status', 'execution_interval', 'fetch_begin_time', 'fetch_end_time', 'last_finish_time', 'next_execute_time', 'deadline_time', 'slip_window_size', 'create_time', 'update_time', 'err_cnt'], 'integer'],
            [['err_msg'], 'string'],
            [['platform_user_id', 'site_id', 'type'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'eagle_platform_user_id' => 'Eagle Platform User ID',
            'platform_user_id' => 'Platform User ID',
            'site_id' => 'Site ID',
            'status' => 'Status',
            'process_status' => 'Process Status',
            'type' => 'Type',
            'execution_interval' => 'Execution Interval',
            'fetch_begin_time' => 'Fetch Begin Time',
            'fetch_end_time' => 'Fetch End Time',
            'last_finish_time' => 'Last Finish Time',
            'next_execute_time' => 'Next Execute Time',
            'deadline_time' => 'Deadline Time',
            'slip_window_size' => 'Slip Window Size',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'err_cnt' => 'Err Cnt',
            'err_msg' => 'Err Msg',
        ];
    }
}
