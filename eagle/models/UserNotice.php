<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "user_notice".
 *
 * @property string $id
 * @property string $puid
 * @property string $app_type
 * @property integer $notice_type
 * @property string $update_time
 * @property string $content
 * @property string $url
 */
class UserNotice extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_notice';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'app_type', 'update_time', 'content'], 'required'],
            [['puid', 'notice_type'], 'integer'],
            [['update_time'], 'safe'],
            [['app_type'], 'string', 'max' => 50],
            [['content', 'url'], 'string', 'max' => 255]
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
            'app_type' => 'App Type',
            'notice_type' => 'Notice Type',
            'update_time' => 'Update Time',
            'content' => 'Content',
            'url' => 'Url',
        ];
    }
}
