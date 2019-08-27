<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_stock_change_detail".
 *
 * @property integer $id
 * @property string $stock_change_id
 * @property string $sku
 * @property integer $qty
 * @property string $prod_name
 */
class StockChangeDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_stock_change_detail';
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
            [['qty'], 'integer'],
            [['stock_change_id'], 'string', 'max' => 255],
            [['sku'], 'string', 'max' => 255],
            [['prod_name'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'stock_change_id' => 'Stock Change ID',
            'sku' => 'Sku',
            'qty' => 'Qty',
            'prod_name' => 'Prod Name',
        ];
    }
}
