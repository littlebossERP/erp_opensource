<?php

namespace eagle\models\assistant;

use Yii;

/**
 * This is the model class for table "dp_rule".
 *
 * @property string $rule_id
 * @property string $puid
 * @property string $country
 * @property string $create_time
 * @property string $timeout
 * @property string $min_money
 * @property string $max_money
 * @property integer $is_active
 * @property string $update_time
 * @property integer $status
 * @property integer $message_content
 * @property integer $timeout2
 * @property integer $timeout3
 * @property integer $expire_time
 * @property integer $message_content2
 * @property integer $message_content3
 * @property integer $order_message
 */
class DpRule extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dp_rule';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'timeout', 'min_money', 'max_money', 'is_active', 'status', 'message_content', 'timeout2', 'timeout3', 'expire_time', 'message_content2', 'message_content3', 'order_message'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['country'], 'string', 'max' => 2000]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'rule_id' => 'Rule ID',
            'puid' => 'Puid',
            'country' => 'Country',
            'create_time' => 'Create Time',
            'timeout' => 'Timeout',
            'min_money' => 'Min Money',
            'max_money' => 'Max Money',
            'is_active' => 'Is Active',
            'update_time' => 'Update Time',
            'status' => 'Status',
            'message_content' => 'Message Content',
            'timeout2' => 'Timeout2',
            'timeout3' => 'Timeout3',
            'expire_time' => 'Expire Time',
            'message_content2' => 'Message Content2',
            'message_content3' => 'Message Content3',
            'order_message' => 'Order Message',
        ];
    }
}
