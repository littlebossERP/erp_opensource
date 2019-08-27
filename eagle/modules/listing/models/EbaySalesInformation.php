<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_sales_information".
 *
 * @property string $id
 * @property integer $uid
 * @property string $name
 * @property string $payment
 * @property string $delivery_details
 * @property string $terms_of_sales
 * @property string $about_us
 * @property string $contact_us
 * @property integer $created
 * @property integer $updated
 */
class EbaySalesInformation extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_sales_information';
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
            [['uid', 'created', 'updated'], 'integer'],
            [['payment', 'delivery_details', 'terms_of_sales', 'about_us', 'contact_us'], 'string'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'uid' => 'Uid',
            'name' => 'Name',
            'payment' => 'Payment',
            'delivery_details' => 'Delivery Details',
            'terms_of_sales' => 'Terms Of Sales',
            'about_us' => 'About Us',
            'contact_us' => 'Contact Us',
            'created' => 'Created',
            'updated' => 'Updated',
        ];
    }
}
