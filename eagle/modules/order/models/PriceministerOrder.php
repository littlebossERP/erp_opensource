<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "priceminister_order".
 *
 * @property integer $id
 * @property string $purchaseid
 * @property string $order_status
 * @property string $purchasedate
 * @property string $shippingtype
 * @property string $isfullrsl
 * @property string $purchasebuyerlogin
 * @property string $purchasebuyeremail
 * @property string $deliveryaddress_civility
 * @property string $deliveryaddress_lastname
 * @property string $deliveryaddress_firstname
 * @property string $deliveryaddress_address1
 * @property string $deliveryaddress_address2
 * @property string $deliveryaddress_zipcode
 * @property string $deliveryaddress_city
 * @property string $deliveryaddress_country
 * @property string $deliveryaddress_countryalpha2
 * @property string $deliveryaddress_phonenumber1
 * @property string $deliveryaddress_phonenumber2
 * @property string $seller_id
 * @property string $seller_login
 * @property string $create
 * @property string $update
 * @property string $addi_info
 */
class PriceministerOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'priceminister_order';
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
            [['purchaseid'], 'required'],
            [['create', 'update'], 'safe'],
            [['addi_info'], 'string'],
            [['purchaseid', 'purchasebuyerlogin', 'deliveryaddress_civility', 'deliveryaddress_lastname', 'deliveryaddress_firstname', 'deliveryaddress_city', 'deliveryaddress_country', 'deliveryaddress_countryalpha2', 'seller_login'], 'string', 'max' => 255],
            [['order_status', 'purchasedate', 'shippingtype', 'deliveryaddress_zipcode', 'deliveryaddress_phonenumber1', 'deliveryaddress_phonenumber2', 'seller_id'], 'string', 'max' => 100],
            [['isfullrsl'], 'string', 'max' => 10],
            [['purchasebuyeremail', 'deliveryaddress_address1', 'deliveryaddress_address2'], 'string', 'max' => 500],
            [['purchaseid', 'seller_login'], 'unique', 'targetAttribute' => ['purchaseid', 'seller_login'], 'message' => 'The combination of Purchaseid and Seller Login has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'purchaseid' => 'Purchaseid',
            'order_status' => 'Order Status',
            'purchasedate' => 'Purchasedate',
            'shippingtype' => 'Shippingtype',
            'isfullrsl' => 'Isfullrsl',
            'purchasebuyerlogin' => 'Purchasebuyerlogin',
            'purchasebuyeremail' => 'Purchasebuyeremail',
            'deliveryaddress_civility' => 'Deliveryaddress Civility',
            'deliveryaddress_lastname' => 'Deliveryaddress Lastname',
            'deliveryaddress_firstname' => 'Deliveryaddress Firstname',
            'deliveryaddress_address1' => 'Deliveryaddress Address1',
            'deliveryaddress_address2' => 'Deliveryaddress Address2',
            'deliveryaddress_zipcode' => 'Deliveryaddress Zipcode',
            'deliveryaddress_city' => 'Deliveryaddress City',
            'deliveryaddress_country' => 'Deliveryaddress Country',
            'deliveryaddress_countryalpha2' => 'Deliveryaddress Countryalpha2',
            'deliveryaddress_phonenumber1' => 'Deliveryaddress Phonenumber1',
            'deliveryaddress_phonenumber2' => 'Deliveryaddress Phonenumber2',
            'seller_id' => 'Seller ID',
            'seller_login' => 'Seller Login',
            'create' => 'Create',
            'update' => 'Update',
            'addi_info' => 'Addi Info',
        ];
    }
}
