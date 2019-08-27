<?php

namespace eagle\modules\customer\models;

use Yii;

/**
 * This is the model class for table "ebay_mymessage".
 *
 * @property string $id
 * @property string $uid
 * @property string $ebay_uid
 * @property integer $order_id
 * @property string $itemid
 * @property string $messageid
 * @property string $externalmessageid
 * @property integer $is_read
 * @property integer $is_flagged
 * @property integer $replied
 * @property string $msg_type_id
 * @property string $expirationdate
 * @property string $listingstatus
 * @property string $messagetype
 * @property string $questiontype
 * @property integer $receivedate_time
 * @property string $receivedate
 * @property string $recipientuserid
 * @property string $sender
 * @property string $responseenabled
 * @property string $subject
 * @property string $from_who
 * @property string $highpriority
 */
class EbayMymessage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_ebay_mymessage';
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
            [['uid', 'ebay_uid', 'order_id', 'itemid', 'messageid', 'externalmessageid', 'is_read', 'is_flagged', 'replied', 'msg_type_id', 'receivedate_time'], 'integer'],
            [['expirationdate', 'questiontype', 'receivedate', 'recipientuserid'], 'string', 'max' => 32],
            [['listingstatus'], 'string', 'max' => 16],
            [['messagetype', 'sender'], 'string', 'max' => 128],
            [['responseenabled'], 'string', 'max' => 8],
            [['subject'], 'string', 'max' => 255],
            [['from_who'], 'string', 'max' => 50],
            [['highpriority'], 'string', 'max' => 20],
            [['messageid'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'ebay_uid' => 'Ebay Uid',
            'order_id' => 'Order ID',
            'itemid' => 'Itemid',
            'messageid' => 'Messageid',
            'externalmessageid' => 'Externalmessageid',
            'is_read' => 'Is Read',
            'is_flagged' => 'Is Flagged',
            'replied' => 'Replied',
            'msg_type_id' => 'Msg Type ID',
            'expirationdate' => 'Expirationdate',
            'listingstatus' => 'Listingstatus',
            'messagetype' => 'Messagetype',
            'questiontype' => 'Questiontype',
            'receivedate_time' => 'Receivedate Time',
            'receivedate' => 'Receivedate',
            'recipientuserid' => 'Recipientuserid',
            'sender' => 'Sender',
            'responseenabled' => 'Responseenabled',
            'subject' => 'Subject',
            'from_who' => 'From Who',
            'highpriority' => 'Highpriority',
        ];
    }
}
