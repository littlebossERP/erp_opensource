<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "background_monitor".
 *
 * @property integer $id
 * @property string $last_end_time
 * @property double $last_total_time
 * @property string $create_time
 * @property string $job_name
 * @property string $status
 * @property string $json_params
 * @property string $messages
 */
class BackgroundMonitor extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'background_monitor';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['last_end_time', 'create_time'], 'safe'],
            [['last_total_time'], 'number'],
            [['json_params', 'messages'], 'string'],
            [['job_name', 'status'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'last_end_time' => 'Last End Time',
            'last_total_time' => 'Last Total Time',
            'create_time' => 'Create Time',
            'job_name' => 'Job Name',
            'status' => 'Status',
            'json_params' => 'Json Params',
            'messages' => 'Messages',
        ];
    }
}
