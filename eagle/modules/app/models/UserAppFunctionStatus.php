<?php

namespace eagle\modules\app\models;

use Yii;

/**
 * This is the model class for table "user_app_function_status".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $function_key
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 * @property string $additional_info
 */
class UserAppFunctionStatus extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_app_function_status';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'function_key'], 'required'],
            [['puid', 'status'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['additional_info'], 'string'],
            [['function_key'], 'string', 'max' => 50]
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
            'function_key' => 'Function Key',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'additional_info' => 'Additional Info',
        ];
    }
}
