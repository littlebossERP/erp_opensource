<?php

namespace eagle\models\assistant;

use Yii;

/**
 * This is the model class for table "dp_info".
 *
 * @property integer $duepay_id
 * @property integer $order_id
 * @property string $create_time
 * @property string $pay_time
 * @property integer $status
 * @property string $order_time
 * @property string $shop_id
 * @property integer $rule_id
 * @property string $consignee_country_code
 * @property integer $due_status
 * @property string $buyer
 * @property string $cost
 * @property integer $contacted
 * @property string $content
 * @property string $source_id
 * @property string $update_time
 * @property integer $msg_type
 */
class DpInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'dp_info';
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
            [['order_id', 'shop_id'], 'required'],
            [['order_id', 'status', 'rule_id', 'due_status', 'contacted', 'msg_type'], 'integer'],
            [['create_time', 'pay_time', 'order_time'], 'safe'],
            [['cost'], 'number'],
            [['content'], 'string'],
            [['shop_id', 'buyer'], 'string', 'max' => 50],
            [['consignee_country_code'], 'string', 'max' => 5],
            [['source_id'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'duepay_id' => 'Duepay ID',
            'order_id' => 'Order ID',
            'create_time' => 'Create Time',
            'pay_time' => 'Pay Time',
            'status' => 'Status',
            'order_time' => 'Order Time',
            'shop_id' => 'Shop ID',
            'rule_id' => 'Rule ID',
            'consignee_country_code' => 'Consignee Country Code',
            'due_status' => 'Due Status',
            'buyer' => 'Buyer',
            'cost' => 'Cost',
            'contacted' => 'Contacted',
            'content' => 'Content',
            'source_id' => 'Source ID',
            'update_time' => 'Update Time',
            'msg_type' => 'Msg Type',
        ];
    }
}
