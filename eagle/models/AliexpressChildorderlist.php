<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "aliexpress_childorderlist".
 *
 * @property string $id
 * @property string $memo
 * @property string $childid
 * @property string $productid
 * @property string $orderid
 * @property integer $lotnum
 * @property string $productattributes
 * @property string $productunit
 * @property string $skucode
 * @property integer $productcount
 * @property double $productprice_amount
 * @property string $productprice_currencycode
 * @property string $productname
 * @property string $productsnapurl
 * @property string $productimgurl
 * @property integer $create_time
 * @property integer $update_time
 */
class AliexpressChildorderlist extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_childorderlist';
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
            [['lotnum', 'productcount', 'create_time', 'update_time'], 'integer'],
            [['productattributes'], 'string'],
            [['productprice_amount'], 'number'],
            [['memo', 'productname', 'productsnapurl', 'productimgurl'], 'string', 'max' => 255],
            [['childid', 'productid'], 'string', 'max' => 20],
            [['orderid'], 'string', 'max' => 30],
            [['productunit'], 'string', 'max' => 50],
            [['skucode'], 'string', 'max' => 100],
            [['productprice_currencycode'], 'string', 'max' => 5]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'memo' => 'Memo',
            'childid' => 'Childid',
            'productid' => 'Productid',
            'orderid' => 'Orderid',
            'lotnum' => 'Lotnum',
            'productattributes' => 'Productattributes',
            'productunit' => 'Productunit',
            'skucode' => 'Skucode',
            'productcount' => 'Productcount',
            'productprice_amount' => 'Productprice Amount',
            'productprice_currencycode' => 'Productprice Currencycode',
            'productname' => 'Productname',
            'productsnapurl' => 'Productsnapurl',
            'productimgurl' => 'Productimgurl',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
