<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "app_user_usage".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $key
 * @property string $is_active
 * @property string $install_time
 * @property string $activate_time
 * @property string $unactivate_time
 */
class AppUserUsage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_user_usage';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'key', 'is_active'], 'required'],
            [['puid'], 'integer'],
            [['install_time', 'activate_time', 'unactivate_time'], 'safe'],
            [['key'], 'string', 'max' => 50],
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
            'puid' => 'Puid',
            'key' => 'Key',
            'is_active' => 'Is Active',
            'install_time' => 'Install Time',
            'activate_time' => 'Activate Time',
            'unactivate_time' => 'Unactivate Time',
        ];
    }
}
