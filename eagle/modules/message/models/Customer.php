<?php

namespace eagle\modules\message\models;

use Yii;

/**
 * This is the model class for table "cs_customer".
 *
 * @property string $id
 * @property string $platform_source
 * @property string $last_order_id
 * @property string $seller_id
 * @property string $customer_id
 * @property string $nation_code
 * @property string $email
 * @property integer $os_flag
 * @property string $create_time
 * @property string $update_time
 * @property string $last_message_time
 * @property string $msg_sent_error
 * @property string $addi_info
 * @property string $last_order_time
 * @property string $currency
 * @property string $life_order_amount
 * @property integer $life_order_count
 */
class Customer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cs_customer';
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
            [['os_flag', 'life_order_count'], 'integer'],
            [['create_time', 'update_time', 'last_message_time', 'last_order_time'], 'safe'],
            [['addi_info'], 'string'],
            [['life_order_amount'], 'number'],
            [['platform_source', 'last_order_id', 'seller_id', 'customer_id', 'email'], 'string', 'max' => 50],
            [['nation_code'], 'string', 'max' => 2],
            [['msg_sent_error'], 'string', 'max' => 1],
            [['currency'], 'string', 'max' => 3]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'platform_source' => 'Platform Source',
            'last_order_id' => 'Last Order ID',
            'seller_id' => 'Seller ID',
            'customer_id' => 'Customer ID',
            'nation_code' => 'Nation Code',
            'email' => 'Email',
            'os_flag' => 'Os Flag',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'last_message_time' => 'Last Message Time',
            'msg_sent_error' => 'Msg Sent Error',
            'addi_info' => 'Addi Info',
            'last_order_time' => 'Last Order Time',
            'currency' => 'Currency',
            'life_order_amount' => 'Life Order Amount',
            'life_order_count' => 'Life Order Count',
        ];
    }
}
