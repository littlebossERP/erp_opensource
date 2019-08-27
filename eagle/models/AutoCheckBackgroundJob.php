<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "auto_check_background_job".
 *
 * @property integer $id
 * @property string $job_name
 * @property string $job_interval
 * @property integer $last_execution_time
 * @property integer $last_success_execution_time
 * @property integer $next_execution_time
 * @property string $execute_api
 * @property string $emails
 * @property integer $error_times
 * @property integer $is_active
 * @property integer $create_time
 * @property integer $update_time
 */
class AutoCheckBackgroundJob extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auto_check_background_job';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['job_name', 'job_interval', 'execute_api', 'emails'], 'required'],
            [['last_execution_time', 'last_success_execution_time', 'next_execution_time', 'error_times', 'is_active', 'create_time', 'update_time'], 'integer'],
            [['execute_api', 'emails'], 'string'],
            [['job_name', 'job_interval'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'job_name' => 'Job Name',
            'job_interval' => 'Job Interval',
            'last_execution_time' => 'Last Execution Time',
            'last_success_execution_time' => 'Last Success Execution Time',
            'next_execution_time' => 'Next Execution Time',
            'execute_api' => 'Execute Api',
            'emails' => 'Emails',
            'error_times' => 'Error Times',
            'is_active' => 'Is Active',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
