<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "rumall_order_detail".
 *
 * @property string $id
 * @property string $orderID
 * @property string $ErpOrderLineNum
 * @property string $SkuNo
 * @property string $ItemName
 * @property string $ItemImage
 * @property string $ItemOldName
 * @property string $ItemUom
 * @property integer $ItemQuantity
 * @property string $ItemPrice
 * @property string $ItemDiscount
 * @property string $CheckPrepardNo
 * @property string $CustomsPrepardNo
 * @property string $HsCode
 * @property string $ItemBrand
 * @property string $ItemSpecifications
 * @property string $BomAction
 * @property string $IsPresent
 * @property string $IsVirtualProduct
 * @property string $InventoryStatus
 * @property string $Lot
 * @property string $Note
 */
class RumallOrderDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'rumall_order_detail';
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
            [['orderID'], 'required'],
            [['ItemQuantity'], 'integer'],
            [['ItemPrice', 'ItemDiscount'], 'number'],
            [['orderID', 'ErpOrderLineNum', 'ItemName', 'ItemImage', 'ItemOldName'], 'string', 'max' => 255],
            [['SkuNo', 'ItemUom', 'CheckPrepardNo', 'CustomsPrepardNo', 'HsCode', 'ItemBrand', 'ItemSpecifications', 'BomAction', 'IsPresent', 'IsVirtualProduct', 'InventoryStatus', 'Lot', 'Note'], 'string', 'max' => 55]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'orderID' => 'Order ID',
            'ErpOrderLineNum' => 'Erp Order Line Num',
            'SkuNo' => 'Sku No',
            'ItemName' => 'Item Name',
            'ItemImage' => 'Item Image',
            'ItemOldName' => 'Item Old Name',
            'ItemUom' => 'Item Uom',
            'ItemQuantity' => 'Item Quantity',
            'ItemPrice' => 'Item Price',
            'ItemDiscount' => 'Item Discount',
            'CheckPrepardNo' => 'Check Prepard No',
            'CustomsPrepardNo' => 'Customs Prepard No',
            'HsCode' => 'Hs Code',
            'ItemBrand' => 'Item Brand',
            'ItemSpecifications' => 'Item Specifications',
            'BomAction' => 'Bom Action',
            'IsPresent' => 'Is Present',
            'IsVirtualProduct' => 'Is Virtual Product',
            'InventoryStatus' => 'Inventory Status',
            'Lot' => 'Lot',
            'Note' => 'Note',
        ];
    }
}
