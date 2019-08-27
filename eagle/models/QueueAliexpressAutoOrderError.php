<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "queue_aliexpress_auto_order_error".
 *
 * @property integer $id
 * @property string $ajax_message
 * @property string $message
 * @property integer $create_time
 */
class QueueAliexpressAutoOrderError extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_aliexpress_auto_order_error';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
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
            [['ajax_message', 'message'], 'string'],
            [['create_time'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'ajax_message' => 'Ajax Message',
            'message' => 'Message',
            'create_time' => 'Create Time',
        ];
    }
}
