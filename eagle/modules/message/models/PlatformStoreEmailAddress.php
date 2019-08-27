<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "platform_store_email_address".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $platform
 * @property string $store
 * @property string $addi_key
 * @property string $mail_address
 * @property string $name
 * @property string $create_time
 * @property string $update_time
 * @property integer $is_active
 */
class PlatformStoreEmailAddress extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'platform_store_email_address';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'platform', 'store', 'mail_address'], 'required'],
            [['puid', 'is_active'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['platform'], 'string', 'max' => 30],
            [['store'], 'string', 'max' => 200],
            [['addi_key', 'name'], 'string', 'max' => 100],
            [['mail_address'], 'string', 'max' => 300],
            [['platform', 'store', 'addi_key', 'puid'], 'unique', 'targetAttribute' => ['platform', 'store', 'addi_key', 'puid'], 'message' => 'The combination of Puid, Platform, Store and Addi Key has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'platform' => 'Platform',
            'store' => 'Store',
            'addi_key' => 'Addi Key',
            'mail_address' => 'Mail Address',
            'name' => 'Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'is_active' => 'Is Active',
        ];
    }
}
