<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_amazon_user_marketplace".
 *
 * @property integer $id
 * @property integer $amazon_uid
 * @property string $marketplace_id
 * @property string $access_key_id
 * @property string $secret_access_key
 * @property integer $is_active
 * @property string $create_time
 * @property string $update_time
 */
class SaasAmazonUserMarketplace extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_amazon_user_marketplace';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['amazon_uid', 'marketplace_id', 'access_key_id', 'secret_access_key', 'create_time', 'update_time'], 'required'],
            [['amazon_uid', 'is_active', 'create_time', 'update_time'], 'integer'],
            [['marketplace_id'], 'string', 'max' => 50],
            [['access_key_id', 'secret_access_key'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'amazon_uid' => 'Amazon Uid',
            'marketplace_id' => 'Marketplace ID',
            'access_key_id' => 'Access Key ID',
            'secret_access_key' => 'Secret Access Key',
            'is_active' => 'Is Active',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
