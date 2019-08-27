<?php

namespace eagle\modules\app\models;

use Yii;

/**
 * This is the model class for table "app_category_appkey".
 *
 * @property integer $id
 * @property integer $app_category_id
 * @property string $app_key
 */
class AppCategoryAppkey extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'app_category_appkey';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['app_category_id', 'app_key'], 'required'],
            [['app_category_id'], 'integer'],
            [['app_key'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'app_category_id' => 'App Category ID',
            'app_key' => 'App Key',
        ];
    }
}
