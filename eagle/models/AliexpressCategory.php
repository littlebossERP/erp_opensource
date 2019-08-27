<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "aliexpress_category".
 *
 * @property integer $cateid
 * @property integer $pid
 * @property integer $level
 * @property string $name_zh
 * @property string $name_en
 * @property string $isleaf
 * @property string $attribute
 * @property integer $created
 * @property integer $updated
 */
class AliexpressCategory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_category';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cateid', 'pid'], 'required'],
            [['cateid', 'pid', 'level', 'created', 'updated'], 'integer'],
            [['isleaf', 'attribute'], 'string'],
            [['name_zh', 'name_en'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'cateid' => 'Cateid',
            'pid' => 'Pid',
            'level' => 'Level',
            'name_zh' => 'Name Zh',
            'name_en' => 'Name En',
            'isleaf' => 'Isleaf',
            'attribute' => 'Attribute',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
