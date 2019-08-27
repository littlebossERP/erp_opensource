<?php

namespace eagle\models;

use Yii;

/**
 * This is the model class for table "aliexpress_order".
 *
 * @property string $id
 * @property string $selleroperatorloginid
 * @property string $buyerloginid
 * @property string $gmtcreate
 * @property string $gmtmodified
 * @property string $sellersignerfullname
 * @property string $ordermsgList
 * @property string $orderstatus
 * @property string $buyersignerfullname
 * @property string $fundstatus
 * @property string $gmtpaysuccess
 * @property string $issueinfo
 * @property string $issuestatus
 * @property string $frozenstatus
 * @property string $logisticsstatus
 * @property string $loaninfo
 * @property string $loanatatus
 * @property string $receiptaddress_zip
 * @property string $receiptaddress_address2
 * @property string $receiptaddress_detailaddress
 * @property string $receiptaddress_country
 * @property string $receiptaddress_city
 * @property string $receiptaddress_phonenumber
 * @property string $receiptaddress_province
 * @property string $receiptaddress_phonearea
 * @property string $receiptaddress_phonecountry
 * @property string $receiptaddress_contactperson
 * @property string $receiptaddress_mobileno
 * @property string $buyerinfo_lastname
 * @property string $buyerinfo_loginid
 * @property string $buyerinfo_email
 * @property string $buyerinfo_firstname
 * @property string $buyerinfo_country
 * @property double $logisticsamount_amount
 * @property string $logisticsamount_currencycode
 * @property double $orderamount_amount
 * @property string $orderamount_currencycode
 * @property double $initOderAmount_amount
 * @property string $initoderamount_currencycode
 * @property integer $create_time
 * @property integer $update_time
 */
class AliexpressOrder extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aliexpress_order';
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
            [['id', 'selleroperatorloginid', 'buyerloginid'], 'required'],
            [['logisticsamount_amount', 'orderamount_amount', 'initOderAmount_amount'], 'number'],
            [['create_time', 'update_time'], 'integer'],
            [['id', 'logisticsstatus', 'buyerinfo_lastname', 'buyerinfo_loginid', 'buyerinfo_email', 'buyerinfo_firstname'], 'string', 'max' => 50],
            [['selleroperatorloginid', 'buyerloginid', 'sellersignerfullname', 'buyersignerfullname', 'receiptaddress_city', 'receiptaddress_province', 'receiptaddress_contactperson'], 'string', 'max' => 100],
            [['gmtcreate', 'gmtmodified', 'orderstatus', 'fundstatus', 'gmtpaysuccess', 'receiptaddress_phonenumber'], 'string', 'max' => 30],
            [['ordermsgList', 'issueinfo', 'loaninfo', 'receiptaddress_address2', 'receiptaddress_detailaddress'], 'string', 'max' => 255],
            [[ 'logistics_services_name'], 'string', 'max' => 200],
            [['issuestatus', 'frozenstatus', 'loanatatus', 'receiptaddress_zip', 'receiptaddress_phonearea', 'receiptaddress_phonecountry', 'receiptaddress_mobileno'], 'string', 'max' => 20],
            [['receiptaddress_country', 'buyerinfo_country'], 'string', 'max' => 10],
            [['logisticsamount_currencycode', 'orderamount_currencycode', 'initoderamount_currencycode'], 'string', 'max' => 5]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'selleroperatorloginid' => 'Selleroperatorloginid',
            'buyerloginid' => 'Buyerloginid',
            'gmtcreate' => 'Gmtcreate',
            'gmtmodified' => 'Gmtmodified',
            'sellersignerfullname' => 'Sellersignerfullname',
            'ordermsgList' => 'Ordermsg List',
            'orderstatus' => 'Orderstatus',
            'buyersignerfullname' => 'Buyersignerfullname',
            'fundstatus' => 'Fundstatus',
            'gmtpaysuccess' => 'Gmtpaysuccess',
            'issueinfo' => 'Issueinfo',
            'issuestatus' => 'Issuestatus',
            'frozenstatus' => 'Frozenstatus',
            'logisticsstatus' => 'Logisticsstatus',
            'loaninfo' => 'Loaninfo',
            'loanatatus' => 'Loanatatus',
            'receiptaddress_zip' => 'Receiptaddress Zip',
            'receiptaddress_address2' => 'Receiptaddress Address2',
            'receiptaddress_detailaddress' => 'Receiptaddress Detailaddress',
            'receiptaddress_country' => 'Receiptaddress Country',
            'receiptaddress_city' => 'Receiptaddress City',
            'receiptaddress_phonenumber' => 'Receiptaddress Phonenumber',
            'receiptaddress_province' => 'Receiptaddress Province',
            'receiptaddress_phonearea' => 'Receiptaddress Phonearea',
            'receiptaddress_phonecountry' => 'Receiptaddress Phonecountry',
            'receiptaddress_contactperson' => 'Receiptaddress Contactperson',
            'receiptaddress_mobileno' => 'Receiptaddress Mobileno',
            'buyerinfo_lastname' => 'Buyerinfo Lastname',
            'buyerinfo_loginid' => 'Buyerinfo Loginid',
            'buyerinfo_email' => 'Buyerinfo Email',
            'buyerinfo_firstname' => 'Buyerinfo Firstname',
            'buyerinfo_country' => 'Buyerinfo Country',
            'logisticsamount_amount' => 'Logisticsamount Amount',
            'logisticsamount_currencycode' => 'Logisticsamount Currencycode',
            'orderamount_amount' => 'Orderamount Amount',
            'orderamount_currencycode' => 'Orderamount Currencycode',
            'initOderAmount_amount' => 'Init Oder Amount Amount',
            'initoderamount_currencycode' => 'Initoderamount Currencycode',
            'logistics_services_name' => 'Logistics Services Name',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
