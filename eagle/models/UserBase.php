<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "user_base".
 *
 * @property string $uid
 * @property string $user_name
 * @property string $auth_key
 * @property string $nickname
 * @property string $password
 * @property string $register_date
 * @property string $last_login_date
 * @property string $last_login_ip
 * @property string $register_ip
 * @property string $last_login_source
 * @property string $ipcn
 * @property string $puid
 * @property string $email
 * @property integer $is_active
 * @property integer $user_app_type
 * @property string $register_from_source
 * @property integer $user_level
 * @property string $source_url
 * @property string $sourcecode
 */
class UserBase extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_base';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['auth_key', 'register_date', 'email'], 'required'],
            [['register_date', 'last_login_date', 'puid', 'is_active', 'user_app_type', 'user_level'], 'integer'],
            [['user_name', 'nickname', 'password', 'last_login_source', 'email', 'register_from_source', 'sourcecode'], 'string', 'max' => 50],
            [['auth_key'], 'string', 'max' => 32],
            [['last_login_ip', 'register_ip'], 'string', 'max' => 20],
            [['ipcn'], 'string', 'max' => 80],
            [['source_url'], 'string', 'max' => 255],
            [['email'], 'unique'],
            [['user_name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'uid' => 'Uid',
            'user_name' => 'User Name',
            'auth_key' => 'Auth Key',
            'nickname' => 'Nickname',
            'password' => 'Password',
            'register_date' => 'Register Date',
            'last_login_date' => 'Last Login Date',
            'last_login_ip' => 'Last Login Ip',
            'register_ip' => 'Register Ip',
            'last_login_source' => 'Last Login Source',
            'ipcn' => 'Ipcn',
            'puid' => 'Puid',
            'email' => 'Email',
            'is_active' => 'Is Active',
            'user_app_type' => 'User App Type',
            'register_from_source' => 'Register From Source',
            'user_level' => 'User Level',
            'source_url' => 'Source Url',
            'sourcecode' => 'Sourcecode',
        ];
    }
}
