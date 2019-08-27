<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_newegg_autosync".
 *
 * @property integer $id
 * @property integer $uid
 * @property integer $site_id
 * @property integer $is_active
 * @property integer $type
 * @property integer $error_times
 * @property integer $last_start_time
 * @property integer $last_finish_time
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $status
 */
class SaasNeweggAutosync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_newegg_autosync';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id'], 'required'],
            [['id', 'uid', 'site_id', 'is_active', 'type', 'error_times', 'last_start_time', 'last_finish_time', 'create_time', 'update_time', 'status'], 'integer'],
            [['message'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'site_id' => 'Site ID',
            'is_active' => 'Is Active',
            'type' => 'Type',
            'error_times' => 'Error Times',
            'last_start_time' => 'Last Start Time',
            'last_finish_time' => 'Last Finish Time',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'status' => 'Status',
        ];
    }
}
