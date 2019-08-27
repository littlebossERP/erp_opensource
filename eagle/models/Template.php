<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "template".
 *
 * @property integer $id
 * @property string $title
 * @property integer $categoryid
 * @property string $pic
 * @property string $content
 */
class Template extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'template';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['title', 'categoryid', 'content'], 'required'],
            [['categoryid'], 'integer'],
            [['content'], 'string'],
            [['title'], 'string', 'max' => 60],
            [['pic'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'categoryid' => 'Categoryid',
            'pic' => 'Pic',
            'content' => 'Content',
        ];
    }
}
