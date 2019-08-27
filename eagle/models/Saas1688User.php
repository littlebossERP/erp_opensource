<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_1688_user".
 *
 * @property string $uid_1688
 * @property string $uid
 * @property string $aliId
 * @property string $memberId
 * @property string $access_token
 * @property integer $access_token_timeout
 * @property string $refresh_token
 * @property integer $refresh_token_timeout
 * @property integer $is_active
 * @property integer $create_time
 * @property integer $update_time
 * @property string $app_key
 * @property string $app_secret
 * @property string $store_name
 * @property string $addi_info
 */
class Saas1688User extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_1688_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'aliId', 'memberId', 'create_time', 'update_time'], 'required'],
            [['uid', 'access_token_timeout', 'refresh_token_timeout', 'is_active', 'create_time', 'update_time'], 'integer'],
            [['addi_info'], 'string'],
            [['aliId', 'memberId', 'app_key', 'app_secret'], 'string', 'max' => 100],
            [['access_token', 'refresh_token', 'store_name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'uid_1688' => 'Uid 1688',
            'uid' => 'Uid',
            'aliId' => 'Ali ID',
            'memberId' => 'Member ID',
            'access_token' => 'Access Token',
            'access_token_timeout' => 'Access Token Timeout',
            'refresh_token' => 'Refresh Token',
            'refresh_token_timeout' => 'Refresh Token Timeout',
            'is_active' => 'Is Active',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'app_key' => 'App Key',
            'app_secret' => 'App Secret',
            'store_name' => 'Store Name',
            'addi_info' => 'Addi Info',
        ];
    }
}
