<?php

namespace eagle\modules\catalog\models;

use Yii;

/**
 * This is the model class for table "pd_product_suppliers".
 *
 * @property integer $product_supplier_id
 * @property string $sku
 * @property string $supplier_id
 * @property integer $priority
 * @property string $purchase_price
 */
class ProductSuppliers extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_product_suppliers';
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
            [['supplier_id', 'priority'], 'integer'],
            [['purchase_price'], 'number'],
            [['sku'], 'string', 'max' => 250],
            [['sku', 'supplier_id'], 'unique', 'targetAttribute' => ['sku', 'supplier_id'], 'message' => 'The combination of Sku and Supplier ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'product_supplier_id' => 'Product Supplier ID',
            'sku' => 'Sku',
            'supplier_id' => 'Supplier ID',
            'priority' => 'Priority',
            'purchase_price' => 'Purchase Price',
        ];
    }
}
