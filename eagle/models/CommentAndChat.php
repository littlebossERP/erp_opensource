<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "comment_and_chat".
 *
 * @property string $id
 * @property string $uid
 * @property string $remark
 * @property integer $create_time
 */
class CommentAndChat extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comment_and_chat';
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
            [['uid'], 'required'],
            [['uid', 'create_time'], 'integer'],
            [['remark'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'remark' => 'Remark',
            'create_time' => 'Create Time',
        ];
    }
}
