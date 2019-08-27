<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "user_database".
 *
 * @property string $id
 * @property string $did
 * @property string $uid
 * @property string $user_name
 * @property integer $dbserverid
 * @property integer $status
 * @property string $ip
 * @property string $dbusername
 * @property string $password
 */
class UserDatabase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_database';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['did', 'uid', 'dbserverid', 'status'], 'integer'],
            [['user_name', 'dbusername', 'password'], 'string', 'max' => 50],
            [['ip'], 'string', 'max' => 30],
            [['did'], 'unique'],
            [['uid'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'did' => 'Did',
            'uid' => 'Uid',
            'user_name' => 'User Name',
            'dbserverid' => 'Dbserverid',
            'status' => 'Status',
            'ip' => 'Ip',
            'dbusername' => 'Dbusername',
            'password' => 'Password',
        ];
    }
}
