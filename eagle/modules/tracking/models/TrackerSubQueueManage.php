<?php

namespace eagle\modules\tracking\models;

use Yii;

/**
 * This is the model class for table "tracker_sub_queue_manage".
 *
 * @property string $job_name
 * @property string $create_time
 * @property string $query_start
 * @property string $query_end
 * @property string $next_start
 * @property string $is_idle
 * @property integer $api_call_count
 * @property integer $query_count
 * @property string $update_time
 * @property integer $job_key
 */
class TrackerSubQueueManage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tracker_sub_queue_manage';
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
            [['job_name', 'create_time', 'query_start', 'query_end', 'next_start', 'is_idle', 'api_call_count', 'query_count', 'update_time', 'job_key'], 'required'],
            [['create_time', 'query_start', 'query_end', 'next_start', 'update_time'], 'safe'],
            [['api_call_count', 'query_count', 'job_key'], 'integer'],
            [['job_name'], 'string', 'max' => 100],
            [['is_idle'], 'string', 'max' => 1],
            [['job_name', 'job_key'], 'unique', 'targetAttribute' => ['job_name', 'job_key'], 'message' => 'The combination of Job Name and Job Key has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'job_name' => 'Job Name',
            'create_time' => 'Create Time',
            'query_start' => 'Query Start',
            'query_end' => 'Query End',
            'next_start' => 'Next Start',
            'is_idle' => 'Is Idle',
            'api_call_count' => 'Api Call Count',
            'query_count' => 'Query Count',
            'update_time' => 'Update Time',
            'job_key' => 'Job Key',
        ];
    }
}
