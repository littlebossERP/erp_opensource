<?php

namespace eagle\modules\app\models;

use Yii;

/**
 * This is the model class for table "app_user_action_log".
 *
 * @property integer $id
 * @property integer $uid
 * @property integer $puid
 * @property string $app_key
 * @property string $log_time
 * @property string $url_path
 * @property integer $param1
 * @property integer $param2
 * @property string $param_str1
 */
class AppUserActionLog extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_user_action_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'puid', 'app_key', 'url_path'], 'required'],
            [['uid', 'puid', 'param1', 'param2'], 'integer'],
            [['log_time'], 'safe'],
            [['app_key', 'param_str1'], 'string', 'max' => 50],
            [['url_path'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'puid' => 'Puid',
            'app_key' => 'App Key',
            'log_time' => 'Log Time',
            'url_path' => 'Url Path',
            'param1' => 'Param1',
            'param2' => 'Param2',
            'param_str1' => 'Param Str1',
        ];
    }
}
