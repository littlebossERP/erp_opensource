<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "amazon_order_submit_queue".
 *
 * @property integer $id
 * @property string $order_id
 * @property string $merchant_id
 * @property string $marketplace_short
 * @property integer $process_status
 * @property string $submit_id
 * @property string $api_action
 * @property string $api_type
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $submit_finish_time
 * @property integer $check_finish_time
 * @property string $parms
 * @property string $results
 * @property string $check_result
 * @property integer $error_count
 * @property string $error_message
 * @property string $check_parms
 * @property string $addition_info
 * @property integer $next_execution_time
 */
class AmazonOrderSubmitQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_order_submit_queue';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'merchant_id', 'marketplace_short', 'process_status', 'api_action', 'parms'], 'required'],
            [['process_status', 'submit_id', 'create_time', 'update_time', 'submit_finish_time', 'check_finish_time', 'error_count', 'next_execution_time'], 'integer'],
            [['parms', 'results', 'check_result', 'error_message', 'check_parms', 'addition_info'], 'string'],
            [['order_id', 'merchant_id'], 'string', 'max' => 50],
            [['marketplace_short'], 'string', 'max' => 2],
            [['api_action'], 'string', 'max' => 100],
            [['api_type'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'merchant_id' => 'Merchant ID',
            'marketplace_short' => 'Marketplace Short',
            'process_status' => 'Process Status',
            'submit_id' => 'Submit ID',
            'api_action' => 'Api Action',
            'api_type' => 'Api Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'submit_finish_time' => 'Submit Finish Time',
            'check_finish_time' => 'Check Finish Time',
            'parms' => 'Parms',
            'results' => 'Results',
            'check_result' => 'Check Result',
            'error_count' => 'Error Count',
            'error_message' => 'Error Message',
            'check_parms' => 'Check Parms',
            'addition_info' => 'Addition Info',
            'next_execution_time' => 'Next Execution Time',
        ];
    }
}
