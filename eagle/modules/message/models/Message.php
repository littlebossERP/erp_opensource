<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_message".
 *
 * @property integer $id
 * @property string $status
 * @property integer $cpur_id
 * @property string $create_time
 * @property string $subject
 * @property string $content
 * @property string $platform
 * @property string $order_id
 * @property string $addi_info
 */
class Message extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_message';
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
            [['cpur_id'], 'integer'],
            [['create_time', 'subject', 'content', 'platform', 'order_id', 'addi_info'], 'required'],
            [['create_time'], 'safe'],
            [['subject', 'content', 'platform', 'addi_info'], 'string'],
            [['status'], 'string', 'max' => 1],
            [['order_id'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'status' => 'Status',
            'cpur_id' => 'Cpur ID',
            'create_time' => 'Create Time',
            'subject' => 'Subject',
            'content' => 'Content',
            'platform' => 'Platform',
            'order_id' => 'Order ID',
            'addi_info' => 'Addi Info',
        ];
    }
}
