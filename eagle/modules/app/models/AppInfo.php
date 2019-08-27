<?php

namespace eagle\modules\app\models;

use Yii;

/**
 * This is the model class for table "app_info".
 *
 * @property integer $id
 * @property string $name
 * @property string $key
 * @property string $description
 * @property string $depends
 * @property string $is_default_chosen
 */
class AppInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_info';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'key', 'description', 'depends'], 'required'],
            [['description'], 'string'],
            [['name', 'key'], 'string', 'max' => 50],
            [['depends'], 'string', 'max' => 100],
            [['is_default_chosen'], 'string', 'max' => 1]
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
            'description' => 'Description',
            'depends' => 'Depends',
            'is_default_chosen' => 'Is Default Chosen',
        ];
    }
}
