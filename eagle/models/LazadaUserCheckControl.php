<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "lazada_user_check_control".
 *
 * @property integer $id
 * @property integer $puid
 * @property integer $lazada_saas_user_id
 * @property string $type
 * @property integer $process_status
 * @property integer $need_check
 * @property integer $next_execution_time
 * @property integer $update_time
 * @property integer $need_another_check
 */
class LazadaUserCheckControl extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'lazada_user_check_control';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'lazada_saas_user_id', 'type', 'process_status', 'need_check', 'next_execution_time', 'update_time'], 'required'],
            [['puid', 'lazada_saas_user_id', 'process_status', 'need_check', 'next_execution_time', 'update_time', 'need_another_check'], 'integer'],
            [['type'], 'string', 'max' => 30]
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
            'type' => 'Type',
            'process_status' => 'Process Status',
            'need_check' => 'Need Check',
            'next_execution_time' => 'Next Execution Time',
            'update_time' => 'Update Time',
            'need_another_check' => 'Need Another Check',
        ];
    }
}
