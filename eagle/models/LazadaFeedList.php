<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lazada_feed_list".
 *
 * @property integer $id
 * @property integer $puid
 * @property integer $lazada_saas_user_id
 * @property string $site
 * @property string $type
 * @property integer $process_status
 * @property string $Feed
 * @property string $Status
 * @property string $Action
 * @property integer $CreationDate
 * @property integer $UpdatedDate
 * @property string $Source
 * @property integer $TotalRecords
 * @property integer $ProcessedRecords
 * @property integer $FailedRecords
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $next_execution_time
 * @property integer $error_times
 * @property string $message
 * @property string $FeedErrors
 * @property integer $is_running
 */
class LazadaFeedList extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_feed_list';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'lazada_saas_user_id', 'site', 'type', 'process_status'], 'required'],
            [['puid', 'lazada_saas_user_id', 'process_status', 'CreationDate', 'UpdatedDate', 'TotalRecords', 'ProcessedRecords', 'FailedRecords', 'create_time', 'update_time', 'next_execution_time', 'error_times', 'is_running'], 'integer'],
            [['FeedErrors'], 'string'],
            [['site'], 'string', 'max' => 10],
            [['type'], 'string', 'max' => 30],
            [['Feed'], 'string', 'max' => 127],
            [['Status'], 'string', 'max' => 15],
            [['Action', 'Source'], 'string', 'max' => 64],
            [['message'], 'string', 'max' => 255]
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
            'lazada_saas_user_id' => 'Lazada Saas User ID',
            'site' => 'Site',
            'type' => 'Type',
            'process_status' => 'Process Status',
            'Feed' => 'Feed',
            'Status' => 'Status',
            'Action' => 'Action',
            'CreationDate' => 'Creation Date',
            'UpdatedDate' => 'Updated Date',
            'Source' => 'Source',
            'TotalRecords' => 'Total Records',
            'ProcessedRecords' => 'Processed Records',
            'FailedRecords' => 'Failed Records',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'next_execution_time' => 'Next Execution Time',
            'error_times' => 'Error Times',
            'message' => 'Message',
            'FeedErrors' => 'Feed Errors',
            'is_running' => 'Is Running',
        ];
    }
}
