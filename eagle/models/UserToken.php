<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "user_token".
 *
 * @property string $key
 * @property string $token
 * @property string $create_time
 */
class UserToken extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['key', 'create_time'], 'required'],
            [['create_time'], 'integer'],
            [['key'], 'string', 'max' => 45],
            [['token'], 'string', 'max' => 100],
            [['key'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'key' => 'Key',
            'token' => 'Token',
            'create_time' => 'Create Time',
        ];
    }
}
