<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "aliexpress_listing_detail".
 *
 * @property integer $id
 * @property string $productid
 * @property string $categoryid
 * @property string $selleruserid
 * @property double $product_price
 * @property double $product_gross_weight
 * @property integer $product_length
 * @property integer $product_width
 * @property integer $product_height
 * @property string $currencyCode
 * @property string $aeopAeProductPropertys
 * @property string $aeopAeProductSKUs
 * @property string $detail
 */
class AliexpressGroupInfo extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_group_info';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('subdb');
    }
}
