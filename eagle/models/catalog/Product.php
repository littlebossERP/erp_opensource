<?php

namespace eagle\models\catalog;

use Yii;

/**
 * This is the model class for table "pd_product".
 *
 * @property string $sku
 * @property string $is_has_alias
 * @property string $name
 * @property string $type
 * @property string $status
 * @property string $prod_name_ch
 * @property string $prod_name_en
 * @property string $declaration_ch
 * @property string $declaration_en
 * @property string $declaration_value_currency
 * @property string $declaration_value
 * @property integer $category_id
 * @property integer $brand_id
 * @property string $is_has_tag
 * @property integer $purchase_by
 * @property integer $prod_weight
 * @property integer $prod_width
 * @property integer $prod_length
 * @property integer $prod_height
 * @property string $other_attributes
 * @property string $photo_primary
 * @property integer $supplier_id
 * @property string $purchase_price
 * @property string $check_standard
 * @property string $comment
 * @property integer $capture_user_id
 * @property string $create_time
 * @property string $update_time
 * @property integer $total_stockage
 * @property integer $pending_ship_qty
 * @property string $create_source
 * @property integer $product_id
 */
class Product extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pd_product';
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
            [['sku', 'brand_id', 'purchase_by'], 'required'],
            [['declaration_value', 'purchase_price', 'prod_weight', 'prod_width', 'prod_length', 'prod_height'], 'number'],
            [['category_id', 'brand_id', 'purchase_by', 'supplier_id', 'capture_user_id', 'total_stockage', 'pending_ship_qty'], 'integer'],
            [['create_time', 'update_time'], 'safe'],            
            [['is_has_alias', 'type', 'is_has_tag'], 'string', 'max' => 1],
            [['sku', 'name', 'prod_name_ch', 'prod_name_en', 'other_attributes', 'photo_primary', 'check_standard', 'comment'], 'string', 'max' => 255],
            [['status'], 'string', 'max' => 2],
            [['declaration_ch', 'declaration_en'], 'string', 'max' => 100],
            [['declaration_value_currency'], 'string', 'max' => 3],
            [['create_source'], 'string', 'max' => 10]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'sku' => 'Sku',
            'is_has_alias' => 'Is Has Alias',
            'name' => 'Name',
            'type' => 'Type',
            'status' => 'Status',
            'prod_name_ch' => 'Prod Name Ch',
            'prod_name_en' => 'Prod Name En',
            'declaration_ch' => 'Declaration Ch',
            'declaration_en' => 'Declaration En',
            'declaration_value_currency' => 'Declaration Value Currency',
            'declaration_value' => 'Declaration Value',
            'category_id' => 'Category ID',
            'brand_id' => 'Brand ID',
            'is_has_tag' => 'Is Has Tag',
            'purchase_by' => 'Purchase By',
            'prod_weight' => 'Prod Weight',
            'prod_width' => 'Prod Width',
            'prod_length' => 'Prod Length',
            'prod_height' => 'Prod Height',
            'other_attributes' => 'Other Attributes',
            'photo_primary' => 'Photo Primary',
            'supplier_id' => 'Supplier ID',
            'purchase_price' => 'Purchase Price',
            'check_standard' => 'Check Standard',
            'comment' => 'Comment',
            'capture_user_id' => 'Capture User ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
            'total_stockage' => 'Total Stockage',
            'pending_ship_qty' => 'Pending Ship Qty',
            'create_source' => 'Create Source',
            'product_id' => 'Product ID',
        ];
    }
}
