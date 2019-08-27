<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "mytemplate".
 *
 * @property integer $id
 * @property string $title
 * @property string $pic
 * @property string $content
 * @property integer $type
 */
class Mytemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'mytemplate';
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
            [['title', 'content'], 'required'],
            [['content'], 'string'],
            [['type'], 'integer'],
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
            'pic' => 'Pic',
            'content' => 'Content',
            'type' => 'Type',
        ];
    }
}
