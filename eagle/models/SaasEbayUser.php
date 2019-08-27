<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_ebay_user".
 *
 * @property string $ebay_uid
 * @property string $selleruserid
 * @property string $token
 * @property string $account_id
 * @property string $uid
 * @property string $expiration_time
 * @property string $create_time
 * @property string $update_time
 * @property integer $item_status
 */
class SaasEbayUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_ebay_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['selleruserid', 'token'], 'required'],
            [['token'], 'string'],
            [['account_id', 'uid', 'expiration_time', 'create_time', 'update_time', 'item_status'], 'integer'],
            [['selleruserid'], 'string', 'max' => 100],
            [['selleruserid'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ebay_uid' => 'Ebay Uid',
            'selleruserid' => 'Selleruserid',
            'token' => 'Token',
            'account_id' => 'Account ID',
            'uid' => 'Uid',
            'expiration_time' => 'Expiration Time',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'item_status' => 'Item Status',
        ];
    }
}
