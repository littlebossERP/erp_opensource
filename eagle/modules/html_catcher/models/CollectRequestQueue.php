<?php

namespace eagle\modules\html_catcher\models;

use Yii;

/**
 * This is the model class for table "hc_collect_request_queue".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $product_id
 * @property string $field_list
 * @property string $status
 * @property string $platform
 * @property string $create_time
 * @property string $update_time
 * @property string $addi_info
 * @property integer $runtime
 * @property integer $retry_count
 * @property integer $priority
 * @property string $err_msg
 * @property integer $role_id
 * @property string $subsite
 * @property string $callback_function
 * @property string $step
 * @property string $result
 */
class CollectRequestQueue extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'hc_collect_request_queue';
    }
    
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
            [['puid', 'product_id', 'field_list', 'status', 'platform', 'create_time'], 'required'],
            [['puid', 'runtime', 'retry_count', 'priority', 'role_id'], 'integer'],
            [['field_list', 'addi_info', 'err_msg', 'callback_function', 'result'], 'string'],
            [['create_time', 'update_time'], 'safe'],
            [['product_id', 'step'], 'string', 'max' => 50],
            [['status'], 'string', 'max' => 1],
            [['platform'], 'string', 'max' => 30],
            [['subsite'], 'string', 'max' => 20]
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
            'product_id' => 'Product ID',
            'field_list' => 'Field List',
            'status' => 'Status',
            'platform' => 'Platform',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'addi_info' => 'Addi Info',
            'runtime' => 'Runtime',
            'retry_count' => 'Retry Count',
            'priority' => 'Priority',
            'err_msg' => 'Err Msg',
            'role_id' => 'Role ID',
            'subsite' => 'Subsite',
            'callback_function' => 'Callback Function',
            'step' => 'Step',
            'result' => 'Result',
        ];
    }
}
