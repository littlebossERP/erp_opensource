<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "carrier_user_label".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $order_id
 * @property string $customer_number
 * @property string $print_param
 * @property string $create_time
 * @property string $update_time
 * @property string $label_api_file_path
 * @property string $label_items_file_path
 * @property string $merge_pdf_file_path
 * @property integer $run_status
 * @property string $err_msg
 */
class CarrierUserLabel extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'carrier_user_label';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'order_id', 'create_time', 'update_time', 'run_status'], 'integer'],
            [['print_param', 'label_api_file_path', 'label_items_file_path', 'merge_pdf_file_path', 'err_msg'], 'string'],
            [['customer_number'], 'string', 'max' => 50]
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
            'order_id' => 'Order ID',
            'customer_number' => 'Customer Number',
            'print_param' => 'Print Param',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'label_api_file_path' => 'Label Api File Path',
            'label_items_file_path' => 'Label Items File Path',
            'merge_pdf_file_path' => 'Merge Pdf File Path',
            'run_status' => 'Run Status',
            'err_msg' => 'Err Msg',
        ];
    }
}
