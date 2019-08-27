<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "cdiscount_offer_terminator_daily".
 *
 * @property integer $id
 * @property string $product_id
 * @property string $seller_product_id
 * @property string $seller_id
 * @property string $shop_name
 * @property string $name
 * @property string $img
 * @property string $product_url
 * @property string $date
 * @property string $create_time
 * @property string $type
 * @property string $ever_been_surpassed
 * @property string $change_history
 */
class CdiscountOfferTerminatorDaily extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'cdiscount_offer_terminator_daily';
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
            [['product_id', 'seller_product_id', 'seller_id', 'date', 'type'], 'required'],
            [['img', 'product_url', 'change_history'], 'string'],
            [['date', 'create_time'], 'safe'],
            [['product_id'], 'string', 'max' => 50],
            [['seller_product_id', 'seller_id', 'shop_name'], 'string', 'max' => 100],
            [['name'], 'string', 'max' => 1000],
            [['type', 'ever_been_surpassed'], 'string', 'max' => 2]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'product_id' => 'Product ID',
            'seller_product_id' => 'Seller Product ID',
            'seller_id' => 'Seller ID',
            'shop_name' => 'Shop Name',
            'name' => 'Name',
            'img' => 'Img',
            'product_url' => 'Product Url',
            'date' => 'Date',
            'create_time' => 'Create Time',
            'type' => 'Type',
            'ever_been_surpassed' => 'Ever Been Surpassed',
            'change_history' => 'Change History',
        ];
    }
}
