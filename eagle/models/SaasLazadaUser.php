<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_lazada_user".
 *
 * @property string $lazada_uid
 * @property string $platform_userid
 * @property string $store_name
 * @property string $puid
 * @property string $token
 * @property string $access_token
 * @property integer $token_timeout
 * @property string $refresh_token
 * @property integer $refresh_token_timeout
 * @property string $country_user_info
 * @property string $account_platform
 * @property string $lazada_site
 * @property integer $status
 * @property integer $create_time
 * @property integer $update_time
 * @property string $platform
 * @property string $shipment_providers
 * @property string $addi_info
 * @property string $version
 */
class SaasLazadaUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_lazada_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['platform_userid', 'puid', 'token', 'lazada_site', 'create_time', 'update_time'], 'required'],
            [['puid', 'token_timeout', 'refresh_token_timeout', 'status', 'create_time', 'update_time'], 'integer'],
            [['country_user_info', 'shipment_providers', 'addi_info'], 'string'],
            [['platform_userid'], 'string', 'max' => 100],
            [['store_name', 'account_platform', 'version'], 'string', 'max' => 50],
            [['token', 'access_token', 'refresh_token'], 'string', 'max' => 255],
            [['lazada_site'], 'string', 'max' => 10],
            [['platform'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'lazada_uid' => 'Lazada Uid',
            'platform_userid' => 'Platform Userid',
            'store_name' => 'Store Name',
            'puid' => 'Puid',
            'token' => 'Token',
            'access_token' => 'Access Token',
            'token_timeout' => 'Token Timeout',
            'refresh_token' => 'Refresh Token',
            'refresh_token_timeout' => 'Refresh Token Timeout',
            'country_user_info' => 'Country User Info',
            'account_platform' => 'Account Platform',
            'lazada_site' => 'Lazada Site',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'platform' => 'Platform',
            'shipment_providers' => 'Shipment Providers',
            'addi_info' => 'Addi Info',
            'version' => 'Version',
        ];
    }
}
