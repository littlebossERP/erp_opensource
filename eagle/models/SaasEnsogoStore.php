<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_ensogo_store".
 *
 * @property integer $store_id
 * @property integer $site_id
 * @property string $store_name
 * @property string $moblie_phone
 * @property string $email
 * @property string $contact_name
 * @property string $code
 * @property string $create_time
 */
class SaasEnsogoStore extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_ensogo_store';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['site_id', 'store_name', 'moblie_phone', 'email', 'contact_name', 'create_time'], 'required'],
            [['site_id'], 'integer'],
            [['create_time'], 'safe'],
            [['store_name', 'email', 'code'], 'string', 'max' => 255],
            [['moblie_phone', 'contact_name'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'store_id' => 'Store ID',
            'site_id' => 'Site ID',
            'store_name' => 'Store Name',
            'moblie_phone' => 'Moblie Phone',
            'email' => 'Email',
            'contact_name' => 'Contact Name',
            'code' => 'Code',
            'create_time' => 'Create Time',
        ];
    }
}
