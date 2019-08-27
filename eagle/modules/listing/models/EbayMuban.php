<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "ebay_muban".
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
class EbayMuban extends \yii\db\ActiveRecord
{
	/**
	 *
	 * 默认值，第一次进模板页面的值
	 */
	static $default=[
		'siteid'=>0,
		'listingtype'=>'FixedPriceItem',
		'epid'=>'',
		'isbn'=>'',
		'upc'=>'',
		'ean'=>'',
		'primarycategory'=>'',
		'secondarycategory'=>'',
		'conditionid'=>'',
		'specific'=>'',
		'variation'=>'',
		'itemtitle'=>'',
		'itemtitle2'=>'',
		'sku'=>'',
		'quantity'=>1,
		'lotsize'=>0,
		'listingduration'=>'Days_3',
		'startprice'=>'0.00',
		'buyitnowprice'=>'0.00',
		'bestoffer'=>0,
		'bestofferprice'=>'0.00',
		'minibestofferprice'=>'0.00',
		'vatpercent'=>'',
		'privatelisting'=>0,
		'outofstockcontrol'=>0,
		'mainimg'=>'',
		'imgurl'=>'',
		'itemdescription'=>'',
		'dispatchtime'=>'',
		'shippingdetails'=>'',
		'paymentmethods'=>'',
		'autopay'=>0,
		'return_policy'=>'',
		'country'=>'',
		'location'=>'',
		'postalcode'=>'',
		'buyerrequirementdetails'=>['LinkedPayPalAccount'=>'false'],
		'gallery'=>'0',
		'listingenhancement'=>'',
		'hitcounter'=>'NoHitCounter',
		'desc'=>'',
		'selleruserid'=>'',
		'paypal'=>'',
		'storecategoryid'=>'',
		'storecategory2id'=>'',
		'isvariation'=>0,
		'crossbordertrade'=>0,
		'template'=>'',
		'basicinfo'=>'',
		'crossselling'=>'',
		'crossselling_two'=>'',
		'itemdescription_listing'=>'',
	];
	
	/**
	 *整理shippingservice的名字，让名字中带有物流的运送时间
	 *@author fanjs
	 */
	static public function dealshippingservice($shippingservice){
		$a = array ();
		foreach ( $shippingservice as $s ) {
			if ($s ['shippingtimemax'] > 0 && $s ['shippingtimemin'] > 0 && ($s ['shippingtimemax'] != $s ['shippingtimemin'])) {
				$s ['description'] .= '(' . $s ['shippingtimemin'] . '-' . $s ['shippingtimemax'] . 'days)';
			}
			array_push ( $a, $s );
		}
		return $a;
	}
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_muban';
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
            [['itemtitle', 'desc'], 'string', 'max' => 100],
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
    	return $this->hasOne(EbayMubanDetail::className(),['mubanid'=>'mubanid']);
    }
    
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('shippingdetails'),
    			)
    	);
    }
    
    /**
     * 生成记录
     *
     * @param string $log
     * @param int $flag	0失败，1成功，2带警告  Ebay_Log_Muban::RESULT_
     * @param Ebay_Autoadditemset $timer
     *
     * @return bool
     */
    public function createLog($logstr,$flag=0,$timer=null){
    	if ($this->isNewRecord){
    		return false;
    	}
    	$log=new EbayLogMuban();
    	$log->setAttributes(array(
    			'uid'=>@$timer->uid,
    			'selleruserid'=>@$timer->selleruserid,
    			'title'=>@$timer->itemtitle,
    			'mubanid'=>$this->mubanid,
    			'method'=>$timer->timerid != 0?'自动':'手动',
    			'timerid'=>$timer->timerid,
    			'createtime'=>time(),
    			'result'=>$flag
    	));
    	$log->save();
    
    	$log_detail=new EbayLogMubanDetail();
    	$log_detail->setAttributes(array(
    			'logid'=>$log->logid,
    			'description'=>$logstr
    	));
    	$log_detail->save();
    	return true;
    }
}
