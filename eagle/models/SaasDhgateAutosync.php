<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_dhgate_autosync".
 *
 * @property string $id
 * @property integer $uid
 * @property integer $dhgate_uid
 * @property integer $is_active
 * @property integer $status
 * @property string $type
 * @property integer $times
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $last_time
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $binding_time
 * @property integer $last_binding_time
 */
class SaasDhgateAutosync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_dhgate_autosync';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'dhgate_uid', 'status', 'times'], 'required'],
            [['uid', 'dhgate_uid', 'is_active', 'status', 'times', 'start_time', 'end_time', 'last_time', 'create_time', 'update_time', 'binding_time', 'last_binding_time'], 'integer'],
            [['message'], 'string'],
            [['type'], 'string', 'max' => 50]
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
            'dhgate_uid' => 'Dhgate Uid',
            'is_active' => 'Is Active',
            'status' => 'Status',
            'type' => 'Type',
            'times' => 'Times',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'last_time' => 'Last Time',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'binding_time' => 'Binding Time',
            'last_binding_time' => 'Last Binding Time',
        ];
    }
}
