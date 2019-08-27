<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_global_log".
 *
 * @property integer $id
 * @property string $create_time
 * @property string $job_type
 * @property string $level
 * @property string $module
 * @property string $class
 * @property string $function
 * @property string $remark
 */
class GlobalLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_global_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time'], 'safe'],
            [['job_type', 'level'], 'required'],
            [['job_type', 'level'], 'string'],
            [['module'], 'string', 'max' => 30],
            [['class', 'function'], 'string', 'max' => 45],
            [['remark'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'create_time' => 'Create Time',
            'job_type' => 'Job Type',
            'level' => 'Level',
            'module' => 'Module',
            'class' => 'Class',
            'function' => 'Function',
            'remark' => 'Remark',
        ];
    }
}
