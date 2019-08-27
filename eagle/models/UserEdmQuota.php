<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "user_edm_quota".
 *
 * @property integer $uid
 * @property integer $remaining_quota
 * @property string $create_time
 * @property string $update_time
 * @property string $addi_info
 */
class UserEdmQuota extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_edm_quota';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'remaining_quota'], 'required'],
            [['uid', 'remaining_quota'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['addi_info'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'uid' => 'Uid',
            'remaining_quota' => 'Remaining Quota',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'addi_info' => 'Addi Info',
        ];
    }
}
