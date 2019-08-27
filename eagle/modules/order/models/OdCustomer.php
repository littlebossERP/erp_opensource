<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "od_customer".
 *
 * @property string $id
 * @property string $user_source
 * @property string $seller_platform_uid
 * @property string $customer_platform_uid
 * @property string $customer_email
 * @property string $accumulated_order_amount
 * @property string $accumulated_trading_amount
 * @property string $create_time
 * @property string $update_time
 */
class OdCustomer extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'od_customer';
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
            [['accumulated_order_amount', 'create_time', 'update_time'], 'integer'],
            [['accumulated_trading_amount'], 'number'],
            [['user_source', 'seller_platform_uid', 'customer_platform_uid'], 'string', 'max' => 50],
            [['customer_email'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_source' => 'User Source',
            'seller_platform_uid' => 'Seller Platform Uid',
            'customer_platform_uid' => 'Customer Platform Uid',
            'customer_email' => 'Customer Email',
            'accumulated_order_amount' => 'Accumulated Order Amount',
            'accumulated_trading_amount' => 'Accumulated Trading Amount',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
