<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_amazon_user".
 *
 * @property string $amazon_uid
 * @property string $merchant_id
 * @property string $store_name
 * @property integer $is_active
 * @property string $uid
 * @property string $create_time
 * @property string $update_time
 */
class SaasAmazonUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_amazon_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['is_active', 'uid', 'create_time', 'update_time'], 'integer'],
            [['merchant_id', 'store_name'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'amazon_uid' => 'Amazon Uid',
            'merchant_id' => 'Merchant ID',
            'store_name' => 'Store Name',
            'is_active' => 'Is Active',
            'uid' => 'Uid',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
