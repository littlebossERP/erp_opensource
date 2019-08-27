<?php

namespace eagle\modules\amazoncs\models;

use Yii;

/**
 * This is the model class for table "amazon_order_info".
 *
 * @property integer $id
 * @property string $create_time
 * @property string $order_time
 * @property string $order_id
 * @property string $cust_id
 * @property string $marketplace_id
 * @property string $merchant_id
 */
class AmazonOrderInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'amazon_order_info';
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
            [['create_time', 'order_time'], 'integer'],
            [['marketplace_id', 'merchant_id'], 'required'],
            [['order_id', 'cust_id', 'marketplace_id', 'merchant_id'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'create_time' => 'Create Time',
            'order_time' => 'Order Time',
            'order_id' => 'Order ID',
            'cust_id' => 'Cust ID',
            'marketplace_id' => 'Marketplace ID',
            'merchant_id' => 'Merchant ID',
        ];
    }
}
