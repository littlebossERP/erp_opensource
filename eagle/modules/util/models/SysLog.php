<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_sys_log".
 *
 * @property integer $id
 * @property string $create_time
 * @property string $level
 * @property string $module
 * @property string $class
 * @property string $function
 * @property string $remark
 */
class SysLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_sys_log';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time'], 'safe'],
            [['level', 'remark'], 'required'],
            [['level', 'remark'], 'string'],
            [['module', 'class', 'function'], 'string', 'max' => 45]
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
            'level' => 'Level',
            'module' => 'Module',
            'class' => 'Class',
            'function' => 'Function',
            'remark' => 'Remark',
        ];
    }
}
