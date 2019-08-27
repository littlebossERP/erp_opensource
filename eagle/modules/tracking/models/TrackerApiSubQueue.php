<?php

namespace eagle\modules\tracking\models;

use Yii;

/**
 * This is the model class for table "tracker_api_sub_queue".
 *
 * @property integer $sub_id
 * @property integer $main_queue_id
 * @property integer $puid
 * @property string $track_no
 * @property integer $carrier_type
 * @property string $sub_queue_status
 * @property string $result
 * @property string $create_time
 * @property string $update_time
 * @property integer $run_time
 * @property string $addinfo
 */
class TrackerApiSubQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tracker_api_sub_queue';
    }
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
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
            [['main_queue_id', 'track_no', 'carrier_type', 'sub_queue_status', 'create_time', 'update_time'], 'required'],
            [['main_queue_id', 'puid', 'carrier_type', 'run_time'], 'integer'],
            [['result', 'addinfo'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['track_no'], 'string', 'max' => 100],
            [['sub_queue_status'], 'string', 'max' => 30],
            [['main_queue_id', 'carrier_type'], 'unique', 'targetAttribute' => ['main_queue_id', 'carrier_type'], 'message' => 'The combination of Main Queue ID and Carrier Type has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sub_id' => 'Sub ID',
            'main_queue_id' => 'Main Queue ID',
            'puid' => 'Puid',
            'track_no' => 'Track No',
            'carrier_type' => 'Carrier Type',
            'sub_queue_status' => 'Sub Queue Status',
            'result' => 'Result',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'run_time' => 'Run Time',
            'addinfo' => 'Addinfo',
        ];
    }
}
