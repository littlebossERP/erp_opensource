<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_ticket_session".
 *
 * @property string $ticket_id
 * @property string $platform_source
 * @property integer $message_type
 * @property string $related_id
 * @property string $related_type
 * @property string $seller_id
 * @property string $buyer_id
 * @property string $session_id
 * @property integer $has_read
 * @property integer $has_replied
 * @property integer $has_handled
 * @property string $created
 * @property string $updated
 * @property string $lastmessage
 * @property string $msg_sent_error
 * @property string $addi_info
 * @property string $last_omit_msg
 * @property string $seller_nickname
 * @property string $buyer_nickname
 * @property string $original_msg_type
 * @property string $list_contact
 * @property string $msgTitle
 */
class TicketSession extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_ticket_session';
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
            [['message_type'], 'required'],
            [['message_type', 'has_read', 'has_replied', 'has_handled'], 'integer'],
            [['created', 'updated', 'lastmessage'], 'safe'],
            [['addi_info', 'last_omit_msg', 'list_contact', 'msgTitle'], 'string'],
            [['platform_source', 'related_id', 'seller_id', 'buyer_id', 'session_id', 'seller_nickname', 'buyer_nickname', 'original_msg_type'], 'string', 'max' => 50],
            [['related_type', 'msg_sent_error'], 'string', 'max' => 1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ticket_id' => 'Ticket ID',
            'platform_source' => 'Platform Source',
            'message_type' => 'Message Type',
            'related_id' => 'Related ID',
            'related_type' => 'Related Type',
            'seller_id' => 'Seller ID',
            'buyer_id' => 'Buyer ID',
            'session_id' => 'Session ID',
            'has_read' => 'Has Read',
            'has_replied' => 'Has Replied',
            'has_handled' => 'Has Handled',
            'created' => 'Created',
            'updated' => 'Updated',
            'lastmessage' => 'Lastmessage',
            'msg_sent_error' => 'Msg Sent Error',
            'addi_info' => 'Addi Info',
            'last_omit_msg' => 'Last Omit Msg',
            'seller_nickname' => 'Seller Nickname',
            'buyer_nickname' => 'Buyer Nickname',
            'original_msg_type' => 'Original Msg Type',
            'list_contact' => 'List Contact',
            'msgTitle' => 'Msg Title',
        ];
    }
}
