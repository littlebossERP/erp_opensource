<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_aliexpress_user".
 *
 * @property string $aliexpress_uid
 * @property string $uid
 * @property string $sellerloginid
 * @property string $access_token
 * @property integer $access_token_timeout
 * @property string $refresh_token
 * @property integer $refresh_token_timeout
 * @property integer $account_id
 * @property integer $is_active
 * @property integer $create_time
 * @property integer $update_time
 */
class SaasAliexpressUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_aliexpress_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'sellerloginid', 'create_time', 'update_time'], 'required'],
            [['uid', 'access_token_timeout', 'refresh_token_timeout', 'account_id', 'is_active', 'create_time', 'update_time'], 'integer'],
            [['sellerloginid'], 'string', 'max' => 100],
            [['access_token', 'refresh_token'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'aliexpress_uid' => 'Aliexpress Uid',
            'uid' => 'Uid',
            'sellerloginid' => 'Sellerloginid',
            'access_token' => 'Access Token',
            'access_token_timeout' => 'Access Token Timeout',
            'refresh_token' => 'Refresh Token',
            'refresh_token_timeout' => 'Refresh Token Timeout',
            'account_id' => 'Account ID',
            'is_active' => 'Is Active',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
