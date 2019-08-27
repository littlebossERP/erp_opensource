<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "cms_notification_management".
 *
 * @property string $id
 * @property string $title
 * @property string $content
 * @property integer $status
 * @property string $keywords
 * @property string $description
 * @property integer $in_module
 * @property string $create_time
 * @property string $update_time
 */
class CmsNotificationManagement extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cms_notification_management';
    }
    
    public static function getDb()
    {
    	return Yii::$app->get('cmsdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title'], 'required'],
            [['content'], 'string'],
            [['status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['in_module'], 'string', 'max' => 10],
            [['title'], 'string', 'max' => 50],
            [['keywords', 'description'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Notification Title',
            'content' => 'Notification Content',
            'status' => 'Notification Status',
            'keywords' => 'Notification Keywords',
            'description' => 'Notification Description',
            'in_module' => 'Notification Module',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
