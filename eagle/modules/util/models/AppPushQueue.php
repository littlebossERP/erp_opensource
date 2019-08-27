<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_app_push_queue".
 *
 * @property integer $id
 * @property integer $priority
 * @property string $status
 * @property string $from_app
 * @property string $to_app
 * @property string $command_line
 * @property integer $puid
 * @property string $create_time
 * @property string $update_time
 * @property integer $run_time
 * @property string $result
 */
class AppPushQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_app_push_queue';
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
            [['priority', 'status', 'from_app', 'to_app', 'command_line', 'puid', 'create_time', 'run_time'], 'required'],
            [['priority', 'puid', 'run_time'], 'integer'],
            [['command_line', 'result'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['status'], 'string', 'max' => 1],
            [['from_app', 'to_app'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'priority' => 'Priority',
            'status' => 'Status',
            'from_app' => 'From App',
            'to_app' => 'To App',
            'command_line' => 'Command Line',
            'puid' => 'Puid',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'run_time' => 'Run Time',
            'result' => 'Result',
        ];
    }
}
