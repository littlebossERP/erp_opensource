<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_ticket_message".
 *
 * @property string $msg_id
 * @property integer $ticket_id
 * @property string $session_id
 * @property string $message_id
 * @property integer $send_or_receiv
 * @property string $content
 * @property string $headers
 * @property integer $has_read
 * @property string $msg_contact
 * @property string $created
 * @property string $updated
 * @property string $status
 * @property string $addi_info
 * @property string $app_time
 * @property string $platform_time
 * @property integer $haveFile
 * @property string $fileUrl
 */
class TicketMessage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_ticket_message';
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
            [['ticket_id', 'send_or_receiv', 'has_read', 'haveFile'], 'integer'],
            [['content', 'headers', 'msg_contact', 'addi_info', 'fileUrl'], 'string'],
            [['created', 'updated', 'app_time', 'platform_time'], 'safe'],
            [['session_id'], 'string', 'max' => 50],
            [['message_id'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'msg_id' => 'Msg ID',
            'ticket_id' => 'Ticket ID',
            'session_id' => 'Session ID',
            'message_id' => 'Message ID',
            'send_or_receiv' => 'Send Or Receiv',
            'content' => 'Content',
            'headers' => 'Headers',
            'has_read' => 'Has Read',
            'msg_contact' => 'Msg Contact',
            'created' => 'Created',
            'updated' => 'Updated',
            'status' => 'Status',
            'addi_info' => 'Addi Info',
            'app_time' => 'App Time',
            'platform_time' => 'Platform Time',
            'haveFile' => 'Have File',
            'fileUrl' => 'File Url',
        ];
    }
}
