<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "cdiscount_offer_list".
 *
 * @property integer $id
 * @property double $best_shipping_charges
 * @property string $comments
 * @property string $creation_date
 * @property double $dea_tax
 * @property string $discount_list
 * @property double $eco_tax
 * @property double $integration_price
 * @property string $last_update_date
 * @property double $minimum_price_for_price_alignment
 * @property string $offer_bench_mark
 * @property string $offer_pool_list
 * @property string $offer_state
 * @property string $parent_product_id
 * @property double $price
 * @property string $price_must_be_aligned
 * @property string $product_condition
 * @property string $product_ean
 * @property string $product_id
 * @property string $product_packaging_unit
 * @property double $product_packaging_unit_price
 * @property string $product_packaging_value
 * @property string $seller_product_id
 * @property string $shipping_information_list
 * @property string $stock
 * @property string $striked_price
 * @property string $vat_rate
 * @property string $name
 * @property string $img
 * @property string $description
 * @property string $sku
 * @property string $brand
 * @property string $is_bestseller
 * @property string $bestseller_name
 * @property string $bestseller_price
 * @property string $seller_id
 * @property string $product_url
 * @property integer $last_15_days_sold
 * @property string $concerned_status
 * @property string $terminator_active
 * @property string $matching_info
 */
class CdiscountOfferList extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cdiscount_offer_list';
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
            [['best_shipping_charges', 'dea_tax', 'eco_tax', 'integration_price', 'minimum_price_for_price_alignment', 'price', 'product_packaging_unit_price', 'product_packaging_value', 'stock', 'striked_price', 'vat_rate', 'bestseller_price'], 'number'],
            [['comments', 'discount_list', 'offer_bench_mark', 'offer_pool_list', 'shipping_information_list', 'name', 'img', 'description', 'product_url', 'matching_info'], 'string'],
            [['creation_date', 'last_update_date'], 'safe'],
            [['seller_id'], 'required'],
            [['last_15_days_sold'], 'integer'],
            [['offer_state', 'parent_product_id', 'price_must_be_aligned', 'product_condition', 'product_ean', 'product_id', 'product_packaging_unit', 'seller_product_id', 'sku', 'brand', 'bestseller_name', 'seller_id'], 'string', 'max' => 100],
            [['is_bestseller', 'concerned_status', 'terminator_active'], 'string', 'max' => 1],
            [['product_id', 'seller_id'], 'unique', 'targetAttribute' => ['product_id', 'seller_id'], 'message' => 'The combination of Product ID and Seller ID has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'best_shipping_charges' => 'Best Shipping Charges',
            'comments' => 'Comments',
            'creation_date' => 'Creation Date',
            'dea_tax' => 'Dea Tax',
            'discount_list' => 'Discount List',
            'eco_tax' => 'Eco Tax',
            'integration_price' => 'Integration Price',
            'last_update_date' => 'Last Update Date',
            'minimum_price_for_price_alignment' => 'Minimum Price For Price Alignment',
            'offer_bench_mark' => 'Offer Bench Mark',
            'offer_pool_list' => 'Offer Pool List',
            'offer_state' => 'Offer State',
            'parent_product_id' => 'Parent Product ID',
            'price' => 'Price',
            'price_must_be_aligned' => 'Price Must Be Aligned',
            'product_condition' => 'Product Condition',
            'product_ean' => 'Product Ean',
            'product_id' => 'Product ID',
            'product_packaging_unit' => 'Product Packaging Unit',
            'product_packaging_unit_price' => 'Product Packaging Unit Price',
            'product_packaging_value' => 'Product Packaging Value',
            'seller_product_id' => 'Seller Product ID',
            'shipping_information_list' => 'Shipping Information List',
            'stock' => 'Stock',
            'striked_price' => 'Striked Price',
            'vat_rate' => 'Vat Rate',
            'name' => 'Name',
            'img' => 'Img',
            'description' => 'Description',
            'sku' => 'Sku',
            'brand' => 'Brand',
            'is_bestseller' => 'Is Bestseller',
            'bestseller_name' => 'Bestseller Name',
            'bestseller_price' => 'Bestseller Price',
            'seller_id' => 'Seller ID',
            'product_url' => 'Product Url',
            'last_15_days_sold' => 'Last 15 Days Sold',
            'concerned_status' => 'Concerned Status',
            'terminator_active' => 'Terminator Active',
            'matching_info' => 'Matching Info',
        ];
    }
}
