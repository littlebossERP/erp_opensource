<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_aliexpress_autosync".
 *
 * @property string $id
 * @property integer $uid
 * @property string $sellerloginid
 * @property integer $aliexpress_uid
 * @property integer $is_active
 * @property integer $status
 * @property integer $times
 * @property integer $start_time
 * @property integer $end_time
 * @property integer $last_time
 * @property string $message
 * @property integer $create_time
 * @property integer $update_time
 */
class SaasAliexpressAutosync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_aliexpress_autosync';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'sellerloginid', 'aliexpress_uid', 'status', 'times'], 'required'],
            [['uid', 'aliexpress_uid', 'is_active', 'status', 'times', 'start_time', 'end_time', 'last_time', 'create_time', 'update_time'], 'integer'],
            [['message'], 'string'],
            [['sellerloginid'], 'string', 'max' => 100]
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
            'sellerloginid' => 'Sellerloginid',
            'aliexpress_uid' => 'Aliexpress Uid',
            'is_active' => 'Is Active',
            'status' => 'Status',
            'times' => 'Times',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'last_time' => 'Last Time',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
