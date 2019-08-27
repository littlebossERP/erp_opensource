<?php

namespace eagle\modules\purchase\models;

use Yii;

/**
 * This is the model class for table "pc_purchase_suggestion".
 *
 * @property string $sku
 * @property integer $pending_purchase_ship_qty
 * @property integer $pending_stock_qty
 * @property string $create_time
 */
class PurchaseSuggestion extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pc_purchase_suggestion';
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
            [['sku'], 'required'],
            [['pending_purchase_ship_qty', 'pending_stock_qty'], 'integer'],
            [['create_time'], 'safe'],
            [['sku'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sku' => 'Sku',
            'pending_purchase_ship_qty' => 'Pending Purchase Ship Qty',
            'pending_stock_qty' => 'Pending Stock Qty',
            'create_time' => 'Create Time',
        ];
    }
}
