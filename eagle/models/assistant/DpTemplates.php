<?php

namespace eagle\models\assistant;

use Yii;

/**
 * This is the model class for table "dp_templates".
 *
 * @property integer $id
 * @property string $content_zh
 * @property string $content_en
 * @property string $content_ge
 * @property string $content_fr
 */
class DpTemplates extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dp_templates';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['content_zh', 'content_en', 'content_ge', 'content_fr'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'content_zh' => '中文',
            'content_en' => '英文',
            'content_ge' => '德语',
            'content_fr' => '法语',
        ];
    }
}
