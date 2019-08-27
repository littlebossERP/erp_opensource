<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "saas_priceminister_autosync".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $sellerloginid
 * @property integer $pm_uid
 * @property integer $is_active
 * @property string $status
 * @property string $type
 * @property integer $times
 * @property string $start_time
 * @property string $end_time
 * @property string $last_time
 * @property string $binding_time
 * @property string $message
 * @property string $create_time
 * @property string $update_time
 * @property integer $order_item
 */
class SaasPriceministerAutosync extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'saas_priceminister_autosync';
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['uid', 'sellerloginid', 'pm_uid', 'status', 'times'], 'required'],
            [['uid', 'pm_uid', 'is_active', 'times', 'order_item'], 'integer'],
            [['start_time', 'end_time', 'last_time', 'binding_time', 'create_time', 'update_time'], 'safe'],
            [['message'], 'string'],
            [['sellerloginid'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 2],
            [['type'], 'string', 'max' => 10],
            [['pm_uid'], 'unique'],
            [['sellerloginid'], 'unique']
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
            'sellerloginid' => 'Sellerloginid',
            'pm_uid' => 'Pm Uid',
            'is_active' => 'Is Active',
            'status' => 'Status',
            'type' => 'Type',
            'times' => 'Times',
            'start_time' => 'Start Time',
            'end_time' => 'End Time',
            'last_time' => 'Last Time',
            'binding_time' => 'Binding Time',
            'message' => 'Message',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'order_item' => 'Order Item',
        ];
    }
}
