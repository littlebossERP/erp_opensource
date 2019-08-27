<?php

namespace eagle\modules\amazoncs\models;

use Yii;

/**
 * This is the model class for table "cs_quest_template".
 *
 * @property integer $id
 * @property string $platform
 * @property string $seller_id
 * @property string $site_id
 * @property string $name
 * @property string $status
 * @property integer $auto_generate
 * @property string $subject
 * @property string $contents
 * @property string $for_order_type
 * @property integer $send_after_order_created_days
 * @property string $pending_send_time
 * @property integer $order_in_howmany_days
 * @property integer $can_send_when_reviewed
 * @property integer $can_send_when_feedbacked
 * @property integer $can_shen_when_contacted
 * @property string $filter_order_item_type
 * @property string $order_item_key_type
 * @property string $order_item_keys
 * @property integer $send_one_pre_howmany_days
 * @property integer $can_send_to_blacklist_buyer
 * @property string $create_time
 * @property string $update_time
 * @property string $last_generated_time
 * @property string $last_generated_log
 * @property string $addi_info
 */
class CsQuestTemplate extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_quest_template';
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
            [['platform', 'seller_id', 'site_id', 'name', 'order_in_howmany_days'], 'required'],
            [['auto_generate', 'send_after_order_created_days', 'order_in_howmany_days', 'can_send_when_reviewed', 'can_send_when_feedbacked', 'can_shen_when_contacted', 'send_one_pre_howmany_days', 'can_send_to_blacklist_buyer'], 'integer'],
            [['contents', 'last_generated_log', 'addi_info'], 'string'],
            [['create_time', 'update_time', 'last_generated_time'], 'safe'],
            [['platform', 'status', 'for_order_type', 'filter_order_item_type', 'order_item_key_type'], 'string', 'max' => 100],
            [['seller_id', 'site_id', 'name'], 'string', 'max' => 255],
            [['subject'], 'string', 'max' => 1000],
            [['pending_send_time'], 'string', 'max' => 2],
            [['order_item_keys'], 'string', 'max' => 500],
            [['platform', 'seller_id', 'site_id', 'name'], 'unique', 'targetAttribute' => ['platform', 'seller_id', 'site_id', 'name'], 'message' => 'The combination of Platform, Seller ID, Site ID and Name has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform' => 'Platform',
            'seller_id' => 'Seller ID',
            'site_id' => 'Site ID',
            'name' => 'Name',
            'status' => 'Status',
            'auto_generate' => 'Auto Generate',
            'subject' => 'Subject',
            'contents' => 'Contents',
            'for_order_type' => 'For Order Type',
            'send_after_order_created_days' => 'Send After Order Created Days',
            'pending_send_time' => 'Pending Send Time',
            'order_in_howmany_days' => 'Order In Howmany Days',
            'can_send_when_reviewed' => 'Can Send When Reviewed',
            'can_send_when_feedbacked' => 'Can Send When Feedbacked',
            'can_shen_when_contacted' => 'Can Shen When Contacted',
            'filter_order_item_type' => 'Filter Order Item Type',
            'order_item_key_type' => 'Order Item Key Type',
            'order_item_keys' => 'Order Item Keys',
            'send_one_pre_howmany_days' => 'Send One Pre Howmany Days',
            'can_send_to_blacklist_buyer' => 'Can Send To Blacklist Buyer',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'last_generated_time' => 'Last Generated Time',
            'last_generated_log' => 'Last Generated Log',
            'addi_info' => 'Addi Info',
        ];
    }
}
