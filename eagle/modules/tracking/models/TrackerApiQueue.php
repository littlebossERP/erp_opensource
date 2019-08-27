<?php

namespace eagle\modules\tracking\models;

use Yii;

/**
 * This is the model class for table "tracker_api_queue".
 *
 * @property integer $id
 * @property integer $priority
 * @property integer $puid
 * @property string $track_no
 * @property string $status
 * @property string $candidate_carriers
 * @property integer $selected_carrier
 * @property string $create_time
 * @property string $update_time
 * @property integer $run_time
 * @property integer $try_count
 * @property string $addinfo
 */
class TrackerApiQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tracker_api_queue';
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
            [['priority', 'puid', 'selected_carrier', 'run_time', 'try_count'], 'integer'],
            [['track_no', 'status', 'create_time', 'update_time'], 'required'],
            [['candidate_carriers'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['track_no'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 30],
            [['addinfo'], 'string', 'max' => 250]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'priority' => 'Priority',
            'puid' => 'Puid',
            'track_no' => 'Track No',
            'status' => 'Status',
            'candidate_carriers' => 'Candidate Carriers',
            'selected_carrier' => 'Selected Carrier',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'run_time' => 'Run Time',
            'try_count' => 'Try Count',
            'addinfo' => 'Addinfo',
        ];
    }
}
