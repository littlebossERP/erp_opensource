<?php

namespace eagle\models\comment;

use Yii;

/**
 * This is the model class for table "cm_comment_enable".
 *
 * @property string $id
 * @property string $uid
 * @property string $selleruserid
 * @property string $platform
 * @property integer $enable_status
 * @property string $createtime
 */
class CmCommentEnable extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_comment_enable';
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
            [['uid', 'selleruserid', 'platform'], 'required'],
            [['uid', 'enable_status', 'createtime'], 'integer'],
            [['selleruserid'], 'string', 'max' => 100],
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
            'uid' => 'Uid',
            'selleruserid' => 'Selleruserid',
            'platform' => 'Platform',
            'enable_status' => 'Enable Status',
            'createtime' => 'Createtime',
        ];
    }
}
