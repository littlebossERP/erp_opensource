<?php

namespace eagle\modules\collect\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "goodscollect_ebay".
 *
 * @property integer $mubanid
 * @property integer $uid
 * @property integer $siteid
 * @property string $itemtitle
 * @property string $listingtype
 * @property string $location
 * @property string $listingduration
 * @property string $sku
 * @property string $paypal
 * @property string $selleruserid
 * @property integer $outofstockcontrol
 * @property integer $isvariation
 * @property integer $quantity
 * @property string $startprice
 * @property string $buyitnowprice
 * @property string $shippingdetails
 * @property string $mainimg
 * @property string $desc
 * @property integer $createtime
 * @property integer $updatetime
 */
class GoodscollectEbay extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'goodscollect_ebay';
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
            [['uid', 'siteid', 'outofstockcontrol', 'isvariation', 'quantity', 'createtime', 'updatetime'], 'integer'],
            [['startprice', 'buyitnowprice'], 'number'],
            [['desc'], 'string', 'max' => 100],
            [['listingtype'], 'string', 'max' => 20],
            [['location'], 'string', 'max' => 128],
            [['listingduration'], 'string', 'max' => 10],
            [['sku', 'paypal', 'mainimg'], 'string', 'max' => 255],
            [['selleruserid'], 'string', 'max' => 200]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'mubanid' => 'Mubanid',
            'uid' => 'Uid',
            'siteid' => 'Siteid',
            'itemtitle' => 'Itemtitle',
            'listingtype' => 'Listingtype',
            'location' => 'Location',
            'listingduration' => 'Listingduration',
            'sku' => 'Sku',
            'paypal' => 'Paypal',
            'selleruserid' => 'Selleruserid',
            'outofstockcontrol' => 'Outofstockcontrol',
            'isvariation' => 'Isvariation',
            'quantity' => 'Quantity',
            'startprice' => 'Startprice',
            'buyitnowprice' => 'Buyitnowprice',
            'shippingdetails' => 'Shippingdetails',
            'mainimg' => 'Mainimg',
            'desc' => 'Desc',
            'createtime' => 'Createtime',
            'updatetime' => 'Updatetime',
        ];
    }
    
    public function getDetail(){
    	return $this->hasOne(GoodscollectEbayDetail::className(),['mubanid'=>'mubanid']);
    }
    
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('shippingdetails'),
    			)
    	);
    }
}
