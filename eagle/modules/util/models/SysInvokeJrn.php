<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_sys_invoke_jrn".
 *
 * @property integer $id
 * @property string $create_time
 * @property string $process_id
 * @property string $module
 * @property string $class
 * @property string $function
 * @property string $param_1
 * @property string $param_2
 * @property string $param_3
 * @property string $param_4
 * @property string $param_5
 * @property string $param_6
 * @property string $param_7
 * @property string $param_8
 * @property string $param_9
 * @property string $param_10
 * @property string $return_code
 */
class SysInvokeJrn extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_sys_invoke_jrn';
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
            [['create_time'], 'safe'],
            [['process_id'], 'integer'],
            [['module', 'param_1', 'param_2', 'param_3', 'param_4', 'param_5', 'param_6', 'param_7', 'param_8', 'param_9', 'param_10', 'return_code'], 'required'],
            [['module', 'param_1', 'param_2', 'param_3', 'param_4', 'param_5', 'param_6', 'param_7', 'param_8', 'param_9', 'param_10', 'return_code'], 'string'],
            [['class', 'function'], 'string', 'max' => 145]
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
            'process_id' => 'Process ID',
            'module' => 'Module',
            'class' => 'Class',
            'function' => 'Function',
            'param_1' => 'Param 1',
            'param_2' => 'Param 2',
            'param_3' => 'Param 3',
            'param_4' => 'Param 4',
            'param_5' => 'Param 5',
            'param_6' => 'Param 6',
            'param_7' => 'Param 7',
            'param_8' => 'Param 8',
            'param_9' => 'Param 9',
            'param_10' => 'Param 10',
            'return_code' => 'Return Code',
        ];
    }
}
