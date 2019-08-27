<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "amazon_report_requset".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $merchant_id
 * @property string $marketplace_short
 * @property string $report_type
 * @property string $request_id
 * @property string $report_id
 * @property string $process_status
 * @property string $create_time
 * @property string $update_time
 * @property string $next_get_report_id_time
 * @property integer $get_report_id_count
 * @property string $next_get_report_data_time
 * @property integer $get_report_data_count
 * @property string $report_contents
 * @property string $app
 */
class AmazonReportRequset extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_report_requset';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue2');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'merchant_id', 'marketplace_short', 'report_type', 'request_id', 'process_status', 'create_time'], 'required'],
            [['uid', 'request_id', 'report_id', 'get_report_id_count', 'get_report_data_count'], 'integer'],
            [['create_time', 'update_time', 'next_get_report_id_time', 'next_get_report_data_time'], 'safe'],
            [['report_contents'], 'string'],
            [['merchant_id'], 'string', 'max' => 50],
            [['marketplace_short', 'process_status'], 'string', 'max' => 2],
            [['report_type'], 'string', 'max' => 100],
            [['app'], 'string', 'max' => 500]
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
            'merchant_id' => 'Merchant ID',
            'marketplace_short' => 'Marketplace Short',
            'report_type' => 'Report Type',
            'request_id' => 'Request ID',
            'report_id' => 'Report ID',
            'process_status' => 'Process Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'next_get_report_id_time' => 'Next Get Report Id Time',
            'get_report_id_count' => 'Get Report Id Count',
            'next_get_report_data_time' => 'Next Get Report Data Time',
            'get_report_data_count' => 'Get Report Data Count',
            'report_contents' => 'Report Contents',
            'app' => 'App',
        ];
    }
}
