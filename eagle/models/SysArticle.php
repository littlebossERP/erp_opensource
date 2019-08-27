<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "sys_article".
 *
 * @property string $id
 * @property string $cat_id
 * @property string $title
 * @property string $content
 * @property string $img_url
 * @property string $create_time
 */
class SysArticle extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'sys_article';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['cat_id', 'create_time'], 'integer'],
            [['content'], 'string'],
            [['img_url'], 'required'],
            [['title', 'img_url'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'cat_id' => 'Cat ID',
            'title' => 'Title',
            'content' => 'Content',
            'img_url' => 'Img Url',
            'create_time' => 'Create Time',
        ];
    }
}
