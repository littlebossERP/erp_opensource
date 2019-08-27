<?php

namespace eagle\modules\util\models;

use Yii;

/**
 * This is the model class for table "user_last_activity_time".
 *
 * @property integer $id
 * @property integer $puid
 * @property string $last_activity_time
 */
class UserLastActivityTime extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_last_activity_time';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'last_activity_time'], 'required'],
            [['puid'], 'integer'],
            [['last_activity_time'], 'safe']
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
            'last_activity_time' => 'Last Activity Time',
        ];
    }
}
