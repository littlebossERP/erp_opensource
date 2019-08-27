<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "ebay_developer_account_info".
 *
 * @property integer $account_id
 * @property string $appID
 * @property string $devID
 * @property string $certID
 * @property string $email
 * @property string $runame
 * @property integer $used
 */
class EbayDeveloperAccountInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_developer_account_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['appID', 'devID', 'certID', 'email', 'runame'], 'required'],
            [['used'], 'integer'],
            [['appID', 'devID', 'certID', 'email', 'runame'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'account_id' => 'Account ID',
            'appID' => 'App ID',
            'devID' => 'Dev ID',
            'certID' => 'Cert ID',
            'email' => 'Email',
            'runame' => 'Runame',
            'used' => 'Used',
        ];
    }
}
