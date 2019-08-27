<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "login_info".
 *
 * @property integer $id
 * @property string $ip
 * @property integer $logintime
 * @property string $memo
 * @property string $userid
 * @property string $username
 */
class LoginInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'login_info';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ip', 'logintime', 'memo', 'userid', 'username'], 'required'],
            [['logintime'], 'integer'],
            [['ip', 'userid', 'username'], 'string', 'max' => 100],
            [['memo'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ip' => 'Ip',
            'logintime' => 'Logintime',
            'memo' => 'Memo',
            'userid' => 'Userid',
            'username' => 'Username',
        ];
    }
}
