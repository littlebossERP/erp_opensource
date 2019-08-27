<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "user_register_app".
 *
 * @property string $email
 * @property string $app_key_list
 * @property string $create_time
 */
class UserRegisterApp extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_register_app';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['email'], 'required'],
            [['create_time'], 'safe'],
            [['email'], 'string', 'max' => 45],
            [['app_key_list'], 'string', 'max' => 255],
            [['email'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'email' => 'Email',
            'app_key_list' => 'App Key List',
            'create_time' => 'Create Time',
        ];
    }
}
