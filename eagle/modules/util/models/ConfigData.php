<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_config_data".
 *
 * @property integer $id
 * @property string $path
 * @property string $value
 */
class ConfigData extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_config_data';
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
            [['path', 'value'], 'required'],
            [['value'], 'string'],
            [['path'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'path' => 'Path',
            'value' => 'Value',
        ];
    }
}
