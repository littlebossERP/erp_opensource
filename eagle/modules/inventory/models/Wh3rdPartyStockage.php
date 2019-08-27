<?php

namespace eagle\modules\inventory\models;

use Yii;

/**
 * This is the model class for table "wh_3rd_party_stockage".
 *
 * @property integer $id
 * @property integer $warehouse_id
 * @property string $platform_code
 * @property string $sku
 * @property string $seller_sku
 * @property integer $current_inventory
 * @property integer $adding_inventory
 * @property integer $reserved_inventory
 * @property integer $usable_inventory
 * @property string $title
 * @property integer $suggest_inventory
 * @property integer $biweekly_sold_qty
 * @property integer $img_url
 * @property string $account_id
 * @property string $addinfo
 */
class Wh3rdPartyStockage extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'wh_3rd_party_stockage';
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
            [['warehouse_id', 'seller_sku'], 'required'],
            [['warehouse_id', 'current_inventory', 'adding_inventory', 'reserved_inventory', 'usable_inventory', 'suggest_inventory', 'biweekly_sold_qty', 'img_url'], 'integer'],
            [['platform_code', 'sku', 'seller_sku', 'title', 'account_id', 'addinfo'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'warehouse_id' => 'Warehouse ID',
            'platform_code' => 'Platform Code',
            'sku' => 'Sku',
            'seller_sku' => 'Seller Sku',
            'current_inventory' => 'Current Inventory',
            'adding_inventory' => 'Adding Inventory',
            'reserved_inventory' => 'Reserved Inventory',
            'usable_inventory' => 'Usable Inventory',
            'title' => 'Title',
            'suggest_inventory' => 'Suggest Inventory',
            'biweekly_sold_qty' => 'Biweekly Sold Qty',
            'img_url' => 'Img Url',
            'account_id' => 'Account ID',
            'addinfo' => 'Addinfo',
        ];
    }
}
