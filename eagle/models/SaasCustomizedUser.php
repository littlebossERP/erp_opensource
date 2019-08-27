<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_customized_user".
 *
 * @property integer $site_id
 * @property string $store_name
 * @property string $username
 * @property integer $is_active
 * @property string $uid
 * @property string $create_time
 * @property string $update_time
 * @property string $addi_info
 */
class SaasCustomizedUser extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_customized_user';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['username', 'create_time'], 'required'],
            [['is_active', 'uid'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['addi_info'], 'string'],
            [['store_name'], 'string', 'max' => 50],
            [['username'], 'string', 'max' => 100],
            [['uid', 'store_name', 'username'], 'unique', 'targetAttribute' => ['uid', 'store_name', 'username'], 'message' => 'The combination of Store Name, Username and Uid has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'site_id' => 'Site ID',
            'store_name' => 'Store Name',
            'username' => 'Username',
            'is_active' => 'Is Active',
            'uid' => 'Uid',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'addi_info' => 'Addi Info',
        ];
    }
}
