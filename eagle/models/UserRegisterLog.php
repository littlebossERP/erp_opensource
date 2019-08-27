<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "user_register_log".
 *
 * @property integer $id
 * @property string $email
 * @property string $status
 * @property string $token
 * @property integer $send_mail_time
 * @property string $error_message
 * @property integer $create_time
 * @property integer $update_time
 */
class UserRegisterLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_register_log';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['send_mail_time', 'create_time', 'update_time'], 'integer'],
            [['error_message'], 'string'],
            [['email'], 'string', 'max' => 45],
            [['status', 'token'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'email' => 'Email',
            'status' => 'Status',
            'token' => 'Token',
            'send_mail_time' => 'Send Mail Time',
            'error_message' => 'Error Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
