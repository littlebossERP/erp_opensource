<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "auto_check_job_log".
 *
 * @property integer $id
 * @property integer $job_id
 * @property string $log_result
 * @property integer $create_time
 */
class AutoCheckJobLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'auto_check_job_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['job_id'], 'required'],
            [['job_id', 'create_time'], 'integer'],
            [['log_result'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'job_id' => 'Job ID',
            'log_result' => 'Log Result',
            'create_time' => 'Create Time',
        ];
    }
}
