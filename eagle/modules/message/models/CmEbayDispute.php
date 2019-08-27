<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cm_ebay_dispute".
 *
 * @property integer $id
 * @property string $disputeid
 * @property string $selleruserid
 * @property string $buyeruserid
 * @property string $user_role
 * @property string $itemid
 * @property string $transactionid
 * @property string $disputereason
 * @property string $disputeexplanation
 * @property string $disputerecordtype
 * @property string $disputestate
 * @property string $disputestatus
 * @property integer $purchaseprotection
 * @property integer $escalation
 * @property integer $has_read
 * @property string $disputemessages
 * @property integer $disputecreatedtime
 * @property integer $create_time
 * @property integer $update_time
 */
class CmEbayDispute extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cm_ebay_dispute';
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
            [['disputeid', 'disputemessages', 'disputecreatedtime', 'create_time', 'update_time'], 'required'],
            [['disputeid', 'itemid', 'purchaseprotection', 'escalation', 'has_read', 'disputecreatedtime', 'create_time', 'update_time'], 'integer'],
            [['disputemessages'], 'string'],
            [['selleruserid', 'buyeruserid', 'user_role'], 'string', 'max' => 50],
            [['transactionid'], 'string', 'max' => 14],
            [['disputereason', 'disputeexplanation', 'disputerecordtype'], 'string', 'max' => 64],
            [['disputestate', 'disputestatus'], 'string', 'max' => 128],
            [['disputeid'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'disputeid' => 'Disputeid',
            'selleruserid' => 'Selleruserid',
            'buyeruserid' => 'Buyeruserid',
            'user_role' => 'User Role',
            'itemid' => 'Itemid',
            'transactionid' => 'Transactionid',
            'disputereason' => 'Disputereason',
            'disputeexplanation' => 'Disputeexplanation',
            'disputerecordtype' => 'Disputerecordtype',
            'disputestate' => 'Disputestate',
            'disputestatus' => 'Disputestatus',
            'purchaseprotection' => 'Purchaseprotection',
            'escalation' => 'Escalation',
            'has_read' => 'Has Read',
            'disputemessages' => 'Disputemessages',
            'disputecreatedtime' => 'Disputecreatedtime',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
