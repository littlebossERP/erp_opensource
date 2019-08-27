<?php

namespace console\models;

use Yii;

/**
 * This is the model class for table "customer_ebay_item".
 *
 * @property integer $ID
 * @property integer $puid
 * @property string $product_id
 * @property string $category_name_one
 * @property string $status
 * @property integer $create_time
 * @property integer $update_time
 */
class CustomerEbayItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'customer_ebay_item';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('db_queue2');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['puid', 'product_id'], 'required'],
            [['puid', 'create_time', 'update_time'], 'integer'],
            [['product_id'], 'string', 'max' => 50],
            [['category_name_one'], 'string', 'max' => 100],
            [['status'], 'string', 'max' => 2]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'ID' => 'ID',
            'puid' => 'Puid',
            'product_id' => 'Product ID',
            'category_name_one' => 'Category Name One',
            'status' => 'Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
