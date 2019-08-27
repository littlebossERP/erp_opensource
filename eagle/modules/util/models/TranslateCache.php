<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "ut_translate_cache".
 *
 * @property integer $id
 * @property string $from_lang
 * @property string $to_lang
 * @property string $input_text
 * @property string $text_out
 */
class TranslateCache extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ut_translate_cache';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['from_lang', 'to_lang', 'input_text', 'text_out'], 'required'],
            [['from_lang', 'to_lang'], 'string', 'max' => 20],
            [['input_text', 'text_out'], 'string', 'max' => 255],
            [['from_lang', 'to_lang', 'input_text'], 'unique', 'targetAttribute' => ['from_lang', 'to_lang', 'input_text'], 'message' => 'The combination of From Lang, To Lang and Input Text has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'from_lang' => 'From Lang',
            'to_lang' => 'To Lang',
            'input_text' => 'Input Text',
            'text_out' => 'Text Out',
        ];
    }
}
