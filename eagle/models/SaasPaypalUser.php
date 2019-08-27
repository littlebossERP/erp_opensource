<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_paypal_user".
 *
 * @property string $ppid
 * @property string $paypal_user
 * @property integer $is_active
 * @property string $create_time
 * @property string $update_time
 * @property string $uid
 * @property string $puid
 * @property string $overwrite_ebay_consignee_address
 */
class SaasPaypalUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_paypal_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['paypal_user'], 'required'],
            [['is_active', 'create_time', 'update_time', 'uid', 'puid'], 'integer'],
            [['paypal_user'], 'string', 'max' => 100],
            [['overwrite_ebay_consignee_address'], 'string', 'max' => 1],
            [['paypal_user'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ppid' => 'Ppid',
            'paypal_user' => 'Paypal User',
            'is_active' => 'Is Active',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'uid' => 'Uid',
            'puid' => 'Puid',
            'overwrite_ebay_consignee_address' => 'Overwrite Ebay Consignee Address',
        ];
    }
}
