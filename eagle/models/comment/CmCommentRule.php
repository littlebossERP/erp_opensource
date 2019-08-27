<?php

namespace eagle\models\comment;

use Yii;

/**
 * This is the model class for table "cm_comment_rule".
 *
 * @property integer $id
 * @property string $selleruseridlist
 * @property string $content
 * @property integer $is_dispute
 * @property string $countrylist
 * @property integer $is_use
 * @property string $platform
 * @property integer $createtime
 * @property integer $updatetime
 */
class CmCommentRule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_comment_rule';
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
            [['selleruseridlist', 'content', 'platform'], 'required'],
            [['is_dispute', 'is_use', 'createtime', 'updatetime'], 'integer'],
            [['selleruseridlist'], 'string', 'max' => 255],
            [['content'], 'string', 'max' => 1000],
            [['countrylist'], 'string', 'max' => 2000],
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
            'selleruseridlist' => 'Selleruseridlist',
            'content' => 'Content',
            'is_dispute' => 'Is Dispute',
            'countrylist' => 'Countrylist',
            'is_use' => 'Is Use',
            'platform' => 'Platform',
            'createtime' => 'Createtime',
            'updatetime' => 'Updatetime',
        ];
    }
}
