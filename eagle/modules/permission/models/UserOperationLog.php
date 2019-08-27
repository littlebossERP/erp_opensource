<?php

namespace eagle\modules\permission\models;

use Yii;

/**
 * This is the model class for table "user_operation_log".
 *
 * @property integer $id
 * @property string $log_module
 * @property integer $uid
 * @property string $operator_name
 * @property integer $create_time
 * @property string $operator_content
 * @property string $remark
 * @property string $addi_info
 * @property integer $key_id
 * @property string $login_ip
 */
class UserOperationLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_operation_log';
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
            [['uid', 'create_time', 'key_id'], 'integer'],
            [['addi_info'], 'string'],
            [['log_module'], 'string', 'max' => 20],
            [['operator_name', 'login_ip'], 'string', 'max' => 100],
            [['operator_content'], 'string', 'max' => 500],
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
            'log_module' => 'Log Module',
            'uid' => 'Uid',
            'operator_name' => 'Operator Name',
            'create_time' => 'Create Time',
            'operator_content' => 'Operator Content',
            'remark' => 'Remark',
            'addi_info' => 'Addi Info',
            'key_id' => 'Key ID',
            'login_ip' => 'Login Ip',
        ];
    }
}
