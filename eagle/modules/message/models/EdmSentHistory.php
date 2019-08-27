<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "edm_sent_history".
 *
 * @property integer $id
 * @property string $act_name
 * @property string $module_key
 * @property string $send_to
 * @property string $send_from
 * @property string $subject
 * @property integer $status
 * @property string $error_message
 * @property string $create_time
 * @property string $update_time
 * @property string $addi_info
 */
class EdmSentHistory extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'edm_sent_history';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['act_name', 'send_to', 'send_from'], 'required'],
            [['subject', 'error_message', 'addi_info'], 'string'],
            [['status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['act_name', 'module_key', 'send_to', 'send_from'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'act_name' => 'Act Name',
            'module_key' => 'Module Key',
            'send_to' => 'Send To',
            'send_from' => 'Send From',
            'subject' => 'Subject',
            'status' => 'Status',
            'error_message' => 'Error Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'addi_info' => 'Addi Info',
        ];
    }
}
