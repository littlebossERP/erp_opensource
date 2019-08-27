<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_lazada_autosync".
 *
 * @property string $id
 * @property integer $puid
 * @property integer $lazada_uid
 * @property string $platform
 * @property string $site
 * @property integer $is_active
 * @property integer $status
 * @property integer $type
 * @property integer $error_times
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $last_finish_time
 * @property integer $next_execution_time
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $binding_time
 * @property integer $last_binding_time
 * @property integer $manual_trigger_time
 */
class SaasLazadaAutosync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_lazada_autosync';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'lazada_uid', 'status', 'error_times'], 'required'],
            [['puid', 'lazada_uid', 'is_active', 'status', 'type', 'error_times', 'start_time', 'end_time', 'last_finish_time', 'next_execution_time', 'create_time', 'update_time', 'binding_time', 'last_binding_time', 'manual_trigger_time'], 'integer'],
            [['message'], 'string'],
            [['platform'], 'string', 'max' => 20],
            [['site'], 'string', 'max' => 10]
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
            'lazada_uid' => 'Lazada Uid',
            'platform' => 'Platform',
            'site' => 'Site',
            'is_active' => 'Is Active',
            'status' => 'Status',
            'type' => 'Type',
            'error_times' => 'Error Times',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'last_finish_time' => 'Last Finish Time',
            'next_execution_time' => 'Next Execution Time',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'binding_time' => 'Binding Time',
            'last_binding_time' => 'Last Binding Time',
            'manual_trigger_time' => 'Manual Trigger Time',
        ];
    }
}
