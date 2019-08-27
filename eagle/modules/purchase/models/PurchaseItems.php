<?php

namespace eagle\modules\purchase\models;

use Yii;

/**
 * This is the model class for table "pc_purchase_items".
 *
 * @property integer $purchase_item_id
 * @property integer $purchase_id
 * @property string $sku
 * @property string $name
 * @property integer $supplier_id
 * @property string $supplier_name
 * @property string $price
 * @property integer $qty
 * @property string $amount
 * @property string $addi_info
 */
class PurchaseItems extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pc_purchase_items';
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
            [['purchase_id', 'supplier_id', 'qty'], 'integer'],
            [['price', 'amount'], 'number'],            
            [['sku', 'name', 'supplier_name', 'addi_info'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'purchase_item_id' => 'Purchase Item ID',
            'purchase_id' => 'Purchase ID',
            'sku' => 'Sku',
            'name' => 'Name',
            'supplier_id' => 'Supplier ID',
            'supplier_name' => 'Supplier Name',
            'price' => 'Price',
            'qty' => 'Qty',
            'amount' => 'Amount',
            'addi_info' => 'Addi Info',
            'remark' => 'Remark',
        ];
    }
}
