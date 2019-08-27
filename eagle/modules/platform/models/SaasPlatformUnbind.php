<?php

namespace eagle\modules\platform\models;

use Yii;

/**
 * This is the model class for table "saas_platform_unbind".
 *
 * @property integer $id
 * @property string $platform_name
 * @property string $platform_sellerid
 * @property integer $puid
 * @property string $status
 * @property string $process_status
 * @property integer $next_execute_time
 * @property integer $create_time
 * @property integer $update_time
 */
class SaasPlatformUnbind extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_platform_unbind';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_name', 'status', 'process_status'], 'string'],
            [['platform_sellerid', 'puid'], 'required'],
            [['puid', 'next_execute_time', 'create_time', 'update_time'], 'integer'],
            [['platform_sellerid'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_name' => 'Platform Name',
            'platform_sellerid' => 'Platform Sellerid',
            'puid' => 'Puid',
            'status' => 'Status',
            'process_status' => 'Process Status',
            'next_execute_time' => 'Next Execute Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
