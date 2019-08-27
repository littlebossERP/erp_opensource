<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "{{%ensogo_variance_countries}}".
 *
 * @property integer $id
 * @property integer $variance_id
 * @property integer $product_id
 * @property string $country_code
 * @property string $price
 * @property string $shipping
 * @property string $msrp
 * @property integer $status
 * @property string $create_time
 * @property string $update_time
 */
class EnsogoVarianceCountries extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%ensogo_variance_countries}}';
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
            [['variance_id', 'product_id', 'status'], 'integer'],
            [['price', 'shipping', 'msrp'], 'number'],
            [['status'], 'required'],
            [['create_time', 'update_time'], 'safe'],
            [['country_code'], 'string', 'max' => 100]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'variance_id' => 'Variance ID',
            'product_id' => 'Product ID',
            'country_code' => 'Country Code',
            'price' => 'Price',
            'shipping' => 'Shipping',
            'msrp' => 'Msrp',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
