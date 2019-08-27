<?php

namespace eagle\modules\dash_board\models;

use Yii;

/**
 * This is the model class for table "db_sales_daily".
 *
 * @property integer $id
 * @property string $thedate
 * @property string $platform
 * @property string $seller_id
 * @property string $order_type
 * @property integer $sales_count
 * @property string $sales_amount_original_currency
 * @property string $original_currency
 * @property string $sales_amount_USD
 * @property string $profit_cny
 */
class SalesDaily extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'db_sales_daily';
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
            [['thedate', 'platform', 'seller_id', 'sales_count', 'sales_amount_original_currency', 'original_currency', 'sales_amount_USD'], 'required'],
            [['thedate'], 'safe'],
            [['sales_count'], 'integer'],
            [['sales_amount_original_currency', 'sales_amount_USD', 'profit_cny'], 'number'],
            [['platform', 'seller_id'], 'string', 'max' => 255],
            [['order_type'], 'string', 'max' => 50],
            [['original_currency'], 'string', 'max' => 3],
            [['thedate', 'platform', 'seller_id', 'order_type', 'use_module_type'], 'unique', 'targetAttribute' => ['thedate', 'platform', 'seller_id', 'order_type', 'use_module_type'], 'message' => 'The combination of Thedate, Platform, Seller ID, Use Module Type and Order Type has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'thedate' => 'Thedate',
            'platform' => 'Platform',
            'seller_id' => 'Seller ID',
            'order_type' => 'Order Type',
            'sales_count' => 'Sales Count',
            'sales_amount_original_currency' => 'Sales Amount Original Currency',
            'original_currency' => 'Original Currency',
            'sales_amount_USD' => 'Sales Amount  Usd',
            'profit_cny' => 'Profit Cny',
            'use_module_type' => 'Use Module Type',
        ];
    }
}
