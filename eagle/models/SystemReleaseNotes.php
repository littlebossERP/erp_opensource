<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "system_release_notes".
 *
 * @property string $id
 * @property string $version
 * @property string $release_date
 * @property string $app_type
 * @property string $content
 */
class SystemReleaseNotes extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'system_release_notes';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['version', 'release_date', 'app_type', 'content'], 'required'],
            [['release_date'], 'safe'],
            [['content'], 'string'],
            [['version', 'app_type'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'version' => 'Version',
            'release_date' => 'Release Date',
            'app_type' => 'App Type',
            'content' => 'Content',
        ];
    }
}
