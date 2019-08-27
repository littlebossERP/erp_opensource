<?php

namespace eagle\models\comment;

use Yii;

/**
 * This is the model class for table "cm_comment_template".
 *
 * @property string $id
 * @property string $content
 * @property string $createtime
 * @property integer $is_use
 * @property string $platform
 */
class CmCommentTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_comment_template';
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
            [['content', 'platform'], 'required'],
            [['createtime', 'is_use'], 'integer'],
            [['content'], 'string', 'max' => 1000],
            [['platform'], 'string', 'max' => 30]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'content' => 'Content',
            'createtime' => 'Createtime',
            'is_use' => 'Is Use',
            'platform' => 'Platform',
        ];
    }
}
