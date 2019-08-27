<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_global_config_data".
 *
 * @property integer $id
 * @property string $path
 * @property string $value
 */
class GlobalConfigData extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_global_config_data';
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
