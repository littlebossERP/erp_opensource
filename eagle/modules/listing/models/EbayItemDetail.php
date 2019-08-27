<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\behaviors\SerializeBehavior;
use eagle\modules\listing\helpers\EbayitemHelper;
/**
 * This is the model class for table "ebay_item_detail".
 *
 * @property string $itemid
 * @property integer $primarycategory
 * @property integer $secondarycategory
 * @property integer $lotsize
 * @property integer $conditionid
 * @property string $hitcounter
 * @property string $postalcode
 * @property string $location
 * @property string $country
 * @property string $gallery
 * @property string $storecategoryid
 * @property string $storecategory2id
 * @property string $additemfee
 * @property string $additemfeecurrency
 * @property integer $bestoffer
 * @property string $bestofferprice
 * @property string $minibestofferprice
 * @property string $epid
 * @property string $isbn
 * @property string $upc
 * @property string $ean
 * @property string $subtitle
 * @property string $shippingdetails
 * @property string $sellingstatus
 * @property string $itemdescription
 * @property string $paymentmethods
 * @property string $returnpolicy
 * @property string $listingenhancement
 * @property string $imgurl
 * @property string $variation
 * @property integer $autopay
 * @property integer $privatelisting
 * @property string $buyerrequirementdetails
 * @property string $itemspecifics
 * @property string $vatpercent
 * @property integer $isreviseinventory
 * @property integer $createtime
 * @property integer $updatetime
 */
class EbayItemDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_item_detail';
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
            [['itemid'], 'required'],
            [['itemid', 'primarycategory', 'secondarycategory', 'lotsize', 'conditionid', 'storecategoryid', 'storecategory2id', 'bestoffer', 'autopay', 'privatelisting', 'isreviseinventory', 'createtime', 'updatetime'], 'integer'],
            [['additemfee', 'bestofferprice', 'minibestofferprice', 'vatpercent'], 'number'],
            [['shippingdetails', 'sellingstatus', 'itemdescription', 'paymentmethods', 'returnpolicy', 'listingenhancement', 'imgurl', 'variation', 'buyerrequirementdetails', 'itemspecifics'], 'string'],
            [['hitcounter', 'location'], 'string', 'max' => 32],
            [['postalcode'], 'string', 'max' => 20],
            [['country', 'gallery'], 'string', 'max' => 16],
            [['additemfeecurrency'], 'string', 'max' => 8],
            [['epid', 'isbn', 'upc', 'ean'], 'string', 'max' => 128],
            [['subtitle'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'itemid' => 'Itemid',
            'primarycategory' => 'Primarycategory',
            'secondarycategory' => 'Secondarycategory',
            'lotsize' => 'Lotsize',
            'conditionid' => 'Conditionid',
            'hitcounter' => 'Hitcounter',
            'postalcode' => 'Postalcode',
            'location' => 'Location',
            'country' => 'Country',
            'gallery' => 'Gallery',
            'storecategoryid' => 'Storecategoryid',
            'storecategory2id' => 'Storecategory2id',
            'additemfee' => 'Additemfee',
            'additemfeecurrency' => 'Additemfeecurrency',
            'bestoffer' => 'Bestoffer',
            'bestofferprice' => 'Bestofferprice',
            'minibestofferprice' => 'Minibestofferprice',
            'epid' => 'Epid',
            'isbn' => 'Isbn',
            'upc' => 'Upc',
            'ean' => 'Ean',
            'subtitle' => 'Subtitle',
            'shippingdetails' => 'Shippingdetails',
            'sellingstatus' => 'Sellingstatus',
            'itemdescription' => 'Itemdescription',
            'paymentmethods' => 'Paymentmethods',
            'returnpolicy' => 'Returnpolicy',
            'listingenhancement' => 'Listingenhancement',
            'imgurl' => 'Imgurl',
            'variation' => 'Variation',
            'autopay' => 'Autopay',
            'privatelisting' => 'Privatelisting',
            'buyerrequirementdetails' => 'Buyerrequirementdetails',
            'itemspecifics' => 'Itemspecifics',
            'vatpercent' => 'Vatpercent',
            'isreviseinventory' => 'Isreviseinventory',
            'createtime' => 'Createtime',
            'updatetime' => 'Updatetime',
        ];
    }
    
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('shippingdetails','sellingstatus','paymentmethods','returnpolicy','listingenhancement',
						'imgurl','variation','buyerrequirementdetails','itemspecifics'),
    			)
    	);
    }
    
    public function afterSave($insert, $changedAttributes){
    	parent::afterSave($insert, $changedAttributes);
    	EbayitemHelper::SaveVariation($this->itemid, $this->variation);
    }
}
