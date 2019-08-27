<?php

namespace eagle\modules\amazoncs\models;

use Yii;

/**
 * This is the model class for table "cs_mail_quest_list".
 *
 * @property integer $id
 * @property integer $quest_template_id
 * @property string $quest_number
 * @property string $platform
 * @property string $seller_id
 * @property string $site_id
 * @property string $order_source_order_id
 * @property string $status
 * @property integer $priority
 * @property string $mail_from
 * @property string $mail_to
 * @property string $consignee
 * @property string $subject
 * @property string $body
 * @property string $pending_send_time_location
 * @property string $pending_send_time_consignee
 * @property string $sent_time_location
 * @property string $sent_time_consignee
 * @property string $last_sent_time
 * @property string $created_time
 * @property string $update_time
 * @property integer $retry_count
 * @property string $addi_info
 */
class CsMailQuestList extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_mail_quest_list';
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
            [['quest_template_id', 'quest_number', 'platform', 'seller_id', 'site_id', 'order_source_order_id', 'status', 'mail_from', 'mail_to', 'subject'], 'required'],
            [['quest_template_id', 'priority', 'retry_count'], 'integer'],
            [['body', 'addi_info'], 'string'],
            [['pending_send_time_location', 'pending_send_time_consignee', 'sent_time_location', 'sent_time_consignee', 'last_sent_time', 'created_time', 'update_time'], 'safe'],
            [['quest_number', 'platform', 'site_id', 'order_source_order_id'], 'string', 'max' => 100],
            [['seller_id', 'mail_from', 'mail_to', 'consignee'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 20],
            [['subject'], 'string', 'max' => 1000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'quest_template_id' => 'Quest Template ID',
            'quest_number' => 'Quest Number',
            'platform' => 'Platform',
            'seller_id' => 'Seller ID',
            'site_id' => 'Site ID',
            'order_source_order_id' => 'Order Source Order ID',
            'status' => 'Status',
            'priority' => 'Priority',
            'mail_from' => 'Mail From',
            'mail_to' => 'Mail To',
            'consignee' => 'Consignee',
            'subject' => 'Subject',
            'body' => 'Body',
            'pending_send_time_location' => 'Pending Send Time Location',
            'pending_send_time_consignee' => 'Pending Send Time Consignee',
            'sent_time_location' => 'Sent Time Location',
            'sent_time_consignee' => 'Sent Time Consignee',
            'last_sent_time' => 'Last Sent Time',
            'created_time' => 'Created Time',
            'update_time' => 'Update Time',
            'retry_count' => 'Retry Count',
            'addi_info' => 'Addi Info',
        ];
    }
}
