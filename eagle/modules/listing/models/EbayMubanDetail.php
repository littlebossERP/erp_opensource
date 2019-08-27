<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "ebay_muban_detail".
 *
 * @property integer $mubanid
 * @property string $epid
 * @property string $isbn
 * @property string $upc
 * @property string $ean
 * @property integer $primarycategory
 * @property integer $secondarycategory
 * @property string $storecategoryid
 * @property string $storecategory2id
 * @property integer $lotsize
 * @property string $itemdescription
 * @property string $imgurl
 * @property string $listingenhancement
 * @property string $hitcounter
 * @property string $paymentmethods
 * @property string $postalcode
 * @property string $country
 * @property integer $region
 * @property string $template
 * @property integer $basicinfo
 * @property string $gallery
 * @property integer $dispatchtime
 * @property string $return_policy
 * @property integer $conditionid
 * @property string $variation
 * @property string $specific
 * @property integer $bestoffer
 * @property string $bestofferprice
 * @property string $minibestofferprice
 * @property string $buyerrequirementdetails
 * @property integer $autopay
 * @property string $secondoffer
 * @property integer $privatelisting
 * @property string $itemtitle2
 * @property string $vatpercent
 * @property integer $crossbordertrade
 * @property integer $crossselling
 * @property integer $createtime
 * @property integer $updatetime
 */
class EbayMubanDetail extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_muban_detail';
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
            [['mubanid'], 'required'],
            [['mubanid', 'primarycategory', 'secondarycategory', 'storecategoryid', 'storecategory2id', 'lotsize', 'region', 'basicinfo', 'dispatchtime', 'conditionid', 'bestoffer', 'autopay', 'privatelisting', 'crossbordertrade', 'crossselling', 'createtime', 'updatetime','crossselling_two'], 'integer'],
            [['itemdescription','itemdescription_listing'], 'string'],
            [['bestofferprice', 'minibestofferprice', 'secondoffer', 'vatpercent'], 'number'],
            [['epid', 'isbn', 'upc', 'ean'], 'string', 'max' => 128],
            [['hitcounter', 'postalcode'], 'string', 'max' => 20],
            [['country'], 'string', 'max' => 10],
            [['template'], 'string', 'max' => 60],
            [['gallery'], 'string', 'max' => 200],
            [['itemtitle2'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'mubanid' => 'Mubanid',
            'epid' => 'Epid',
            'isbn' => 'Isbn',
            'upc' => 'Upc',
            'ean' => 'Ean',
            'primarycategory' => 'Primarycategory',
            'secondarycategory' => 'Secondarycategory',
            'storecategoryid' => 'Storecategoryid',
            'storecategory2id' => 'Storecategory2id',
            'lotsize' => 'Lotsize',
            'itemdescription' => 'Itemdescription',
            'imgurl' => 'Imgurl',
            'listingenhancement' => 'Listingenhancement',
            'hitcounter' => 'Hitcounter',
            'paymentmethods' => 'Paymentmethods',
            'postalcode' => 'Postalcode',
            'country' => 'Country',
            'region' => 'Region',
            'template' => 'Template',
            'basicinfo' => 'Basicinfo',
            'gallery' => 'Gallery',
            'dispatchtime' => 'Dispatchtime',
            'return_policy' => 'Return Policy',
            'conditionid' => 'Conditionid',
            'variation' => 'Variation',
            'specific' => 'Specific',
            'bestoffer' => 'Bestoffer',
            'bestofferprice' => 'Bestofferprice',
            'minibestofferprice' => 'Minibestofferprice',
            'buyerrequirementdetails' => 'Buyerrequirementdetails',
            'autopay' => 'Autopay',
            'secondoffer' => 'Secondoffer',
            'privatelisting' => 'Privatelisting',
            'itemtitle2' => 'Itemtitle2',
            'vatpercent' => 'Vatpercent',
            'crossbordertrade' => 'Crossbordertrade',
            'crossselling' => 'Crossselling',
            'createtime' => 'Createtime',
            'updatetime' => 'Updatetime',
            'crossselling_two' => 'Crosssellig Two',
            'itemdescription_listing'=>'Itemdescription Listing',
        ];
    }
    
    public function behaviors(){
    	return array(
    		'SerializeBehavior' => array(
    			'class' => SerializeBehavior::className(),
    			'serialAttributes' => array('imgurl','listingenhancement','paymentmethods','return_policy','variation',
						'specific','buyerrequirementdetails'),
    		)
    	);
    }
}
