<?php

namespace eagle\modules\app\models;

use Yii;

/**
 * This is the model class for table "user_app_info".
 *
 * @property integer $id
 * @property string $name
 * @property string $key
 * @property string $is_active
 * @property string $install_time
 * @property string $update_time
 */
class UserAppInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_app_info';
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
            [['name', 'key', 'is_active', 'install_time'], 'required'],
            [['install_time', 'update_time'], 'safe'],
            [['name', 'key'], 'string', 'max' => 50],
            [['is_active'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'key' => 'Key',
            'is_active' => 'Is Active',
            'install_time' => 'Install Time',
            'update_time' => 'Update Time',
        ];
    }
}
