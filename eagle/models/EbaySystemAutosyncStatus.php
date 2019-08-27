<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_system_autosync_status".
 *
 * @property string $id
 * @property integer $base_process
 * @property integer $feature_process
 * @property integer $specific_process
 * @property integer $siteid
 * @property string $site
 * @property integer $feature_version
 * @property string $specifics_jobid
 * @property integer $base_next_execute_time
 * @property integer $feature_next_execute_time
 * @property integer $specific_next_execute_time
 */
class EbaySystemAutosyncStatus extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_system_autosync_status';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['base_process', 'feature_process', 'specific_process', 'siteid', 'feature_version', 'base_next_execute_time', 'feature_next_execute_time', 'specific_next_execute_time'], 'integer'],
            [['siteid', 'site'], 'required'],
            [['site'], 'string', 'max' => 50],
            [['specifics_jobid'], 'string', 'max' => 64]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'base_process' => 'Base Process',
            'feature_process' => 'Feature Process',
            'specific_process' => 'Specific Process',
            'siteid' => 'Siteid',
            'site' => 'Site',
            'feature_version' => 'Feature Version',
            'specifics_jobid' => 'Specifics Jobid',
            'base_next_execute_time' => 'Base Next Execute Time',
            'feature_next_execute_time' => 'Feature Next Execute Time',
            'specific_next_execute_time' => 'Specific Next Execute Time',
        ];
    }
}
