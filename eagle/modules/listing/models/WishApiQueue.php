<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "wish_api_queue".
 *
 * @property integer $timerid
 * @property integer $uid
 * @property string $action_type
 * @property string $site_id
 * @property string $fanben_order_id
 * @property string $status
 * @property string $params
 * @property string $create_time
 * @property string $update_time
 * @property string $message
 */
class WishApiQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wish_api_queue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid'], 'integer'],
            [['params', 'create_time', 'update_time'], 'required'],
            [['params', 'message'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['action_type', 'status'], 'string', 'max' => 30],
            [['site_id'], 'string', 'max' => 250],
            [['fanben_order_id'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'timerid' => 'Timerid',
            'uid' => 'Uid',
            'action_type' => 'Action Type',
            'site_id' => 'Site ID',
            'fanben_order_id' => 'Fanben Order ID',
            'status' => 'Status',
            'params' => 'Params',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'message' => 'Message',
        ];
    }
}
