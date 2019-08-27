<?php

namespace eagle\modules\tracking\models;

use Yii;

/**
 * This is the model class for table "tracker_generate_request2queue".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $track_no
 * @property string $create_time
 * @property string $status
 * @property string $user_require_update
 */
class TrackerGenerateRequest2queue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tracker_generate_request2queue';
    }
    public static function getDb()
    {
    	return Yii::$app->get('db_queue');
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid'], 'integer'],
            [['create_time'], 'required'],
            [['create_time'], 'safe'],
            [['track_no'], 'string', 'max' => 100],
            [['status', 'user_require_update'], 'string', 'max' => 1],
            [['puid', 'track_no'], 'unique', 'targetAttribute' => ['puid', 'track_no'], 'message' => 'The combination of Puid and Track No has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'puid' => 'Puid',
            'track_no' => 'Track No',
            'create_time' => 'Create Time',
            'status' => 'Status',
            'user_require_update' => 'User Require Update',
        ];
    }
}
