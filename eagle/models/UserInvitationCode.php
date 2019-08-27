<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "user_invitation_code".
 *
 * @property integer $id
 * @property string $code
 * @property integer $create_time
 * @property integer $is_used
 * @property integer $used_time
 * @property integer $user_id
 * @property string $memo
 * @property integer $type
 * @property integer $expired_time
 * @property string $usage_record
 */
class UserInvitationCode extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_invitation_code';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['code', 'create_time'], 'required'],
            [['create_time', 'is_used', 'used_time', 'user_id', 'type', 'expired_time'], 'integer'],
            [['usage_record'], 'string'],
            [['code'], 'string', 'max' => 20],
            [['memo'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'code' => 'Code',
            'create_time' => 'Create Time',
            'is_used' => 'Is Used',
            'used_time' => 'Used Time',
            'user_id' => 'User ID',
            'memo' => 'Memo',
            'type' => 'Type',
            'expired_time' => 'Expired Time',
            'usage_record' => 'Usage Record',
        ];
    }
}
