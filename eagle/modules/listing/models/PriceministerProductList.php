<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "priceminister_product_list".
 *
 * @property integer $id
 * @property string $productid
 * @property string $seller_id
 * @property string $alias
 * @property string $headline
 * @property string $caption
 * @property string $topic
 * @property string $offercounts
 * @property string $bestprices
 * @property string $url
 * @property string $image
 * @property string $barcode
 * @property string $partnumber
 * @property string $reviews
 * @property string $breadcrumbselements
 */
class PriceministerProductList extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'priceminister_product_list';
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
            [['seller_id'], 'required'],
            [['headline', 'offercounts', 'bestprices', 'url', 'image', 'reviews', 'breadcrumbselements'], 'string'],
            [['productid'], 'string', 'max' => 30],
            [['seller_id', 'alias', 'caption', 'topic', 'partnumber'], 'string', 'max' => 255],
            [['barcode'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'productid' => 'Productid',
            'seller_id' => 'Seller ID',
            'alias' => 'Alias',
            'headline' => 'Headline',
            'caption' => 'Caption',
            'topic' => 'Topic',
            'offercounts' => 'Offercounts',
            'bestprices' => 'Bestprices',
            'url' => 'Url',
            'image' => 'Image',
            'barcode' => 'Barcode',
            'partnumber' => 'Partnumber',
            'reviews' => 'Reviews',
            'breadcrumbselements' => 'Breadcrumbselements',
        ];
    }
}
