<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_dhgate_user".
 *
 * @property string $dhgate_uid
 * @property string $uid
 * @property string $sellerloginid
 * @property string $platformuserid
 * @property string $access_token
 * @property integer $access_token_timeout
 * @property string $refresh_token
 * @property integer $refresh_token_timeout
 * @property string $scope
 * @property integer $is_active
 * @property integer $create_time
 * @property integer $update_time
 */
class SaasDhgateUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_dhgate_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'sellerloginid', 'platformuserid', 'scope', 'create_time', 'update_time'], 'required'],
            [['uid', 'access_token_timeout', 'refresh_token_timeout', 'is_active', 'create_time', 'update_time'], 'integer'],
            [['sellerloginid', 'platformuserid'], 'string', 'max' => 100],
            [['access_token', 'refresh_token'], 'string', 'max' => 255],
            [['scope'], 'string', 'max' => 128]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'dhgate_uid' => 'Dhgate Uid',
            'uid' => 'Uid',
            'sellerloginid' => 'Sellerloginid',
            'platformuserid' => 'Platformuserid',
            'access_token' => 'Access Token',
            'access_token_timeout' => 'Access Token Timeout',
            'refresh_token' => 'Refresh Token',
            'refresh_token_timeout' => 'Refresh Token Timeout',
            'scope' => 'Scope',
            'is_active' => 'Is Active',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
