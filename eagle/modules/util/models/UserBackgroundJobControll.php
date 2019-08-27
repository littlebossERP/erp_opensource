<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "user_background_job_controll".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $job_name
 * @property string $custom_name
 * @property integer $status
 * @property integer $create_time
 * @property integer $last_begin_run_time
 * @property integer $last_finish_time
 * @property integer $error_count
 * @property string $error_message
 * @property string $is_active
 * @property integer $update_time
 * @property integer $next_execution_time
 * @property string $additional_info
 * @property integer $execution_request
 */
class UserBackgroundJobControll extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_background_job_controll';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'job_name', 'create_time'], 'required'],
            [['puid', 'status', 'create_time', 'last_begin_run_time', 'last_finish_time', 'error_count', 'update_time', 'next_execution_time', 'execution_request'], 'integer'],
            [['error_message', 'additional_info'], 'string'],
            [['job_name'], 'string', 'max' => 100],
            [['custom_name'], 'string', 'max' => 50],
            [['is_active'], 'string', 'max' => 1]
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
            'job_name' => 'Job Name',
            'custom_name' => 'Custom Name',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'last_begin_run_time' => 'Last Begin Run Time',
            'last_finish_time' => 'Last Finish Time',
            'error_count' => 'Error Count',
            'error_message' => 'Error Message',
            'is_active' => 'Is Active',
            'update_time' => 'Update Time',
            'next_execution_time' => 'Next Execution Time',
            'additional_info' => 'Additional Info',
            'execution_request' => 'Execution Request',
        ];
    }
}
