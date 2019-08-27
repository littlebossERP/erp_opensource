<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "om_order_message_info".
 *
 * @property integer $id
 * @property integer $order_id
 * @property string $order_source_order_id
 * @property string $order_source
 * @property string $rule_type
 * @property integer $rule_id
 * @property integer $info_id
 * @property string $create_time
 * @property string $update_time
 */
class OmOrderMessageInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'om_order_message_info';
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
            [['order_id', 'rule_id', 'info_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['order_source_order_id', 'order_source'], 'string', 'max' => 50],
            [['rule_type'], 'string', 'max' => 20]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'order_source_order_id' => 'Order Source Order ID',
            'order_source' => 'Order Source',
            'rule_type' => 'Rule Type',
            'rule_id' => 'Rule ID',
            'info_id' => 'Info ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
