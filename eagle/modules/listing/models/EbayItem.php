<?php

namespace eagle\modules\listing\models;

use Yii;

/**
 * This is the model class for table "ebay_item".
 *
 * @property integer $id
 * @property integer $uid
 * @property string $itemid
 * @property string $selleruserid
 * @property string $itemtitle
 * @property string $storecategoryid
 * @property integer $primarycategory
 * @property integer $mubanid
 * @property integer $quantity
 * @property integer $quantitysold
 * @property integer $starttime
 * @property integer $endtime
 * @property integer $dispatchtime
 * @property integer $watchcount
 * @property string $viewitemurl
 * @property string $currency
 * @property string $listingtype
 * @property string $site
 * @property string $currentprice
 * @property string $listingstatus
 * @property string $listingduration
 * @property string $buyitnowprice
 * @property string $startprice
 * @property string $desc
 * @property string $sku
 * @property integer $lastsolddatetime
 * @property string $paypal
 * @property integer $outofstockcontrol
 * @property integer $isvariation
 * @property string $mainimg
 * @property integer $createtime
 * @property integer $updatetime
 * @property integer $bukucun
 * @property integer $less
 * @property integer $bu
 */
class EbayItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_item';
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
            [['uid', 'itemid', 'selleruserid', 'itemtitle', 'mubanid'], 'required'],
            [['uid', 'itemid', 'storecategoryid', 'primarycategory', 'mubanid', 'quantity', 'quantitysold', 'starttime', 'endtime', 'dispatchtime', 'watchcount', 'lastsolddatetime', 'outofstockcontrol', 'isvariation', 'createtime', 'updatetime', 'bukucun', 'less', 'bu'], 'integer'],
            [['currentprice', 'buyitnowprice', 'startprice'], 'number'],
            [['listingstatus', 'listingduration'], 'string'],
            [['selleruserid'], 'string', 'max' => 32],
            [['itemtitle'], 'string', 'max' => 100],
            [['viewitemurl', 'sku', 'paypal', 'mainimg'], 'string', 'max' => 255],
            [['currency'], 'string', 'max' => 8],
            [['listingtype'], 'string', 'max' => 20],
            [['site'], 'string', 'max' => 16],
            [['desc'], 'string', 'max' => 64]
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
            'itemid' => 'Itemid',
            'selleruserid' => 'Selleruserid',
            'itemtitle' => 'Itemtitle',
            'storecategoryid' => 'Storecategoryid',
            'primarycategory' => 'Primarycategory',
            'mubanid' => 'Mubanid',
            'quantity' => 'Quantity',
            'quantitysold' => 'Quantitysold',
            'starttime' => 'Starttime',
            'endtime' => 'Endtime',
            'dispatchtime' => 'Dispatchtime',
            'watchcount' => 'Watchcount',
            'viewitemurl' => 'Viewitemurl',
            'currency' => 'Currency',
            'listingtype' => 'Listingtype',
            'site' => 'Site',
            'currentprice' => 'Currentprice',
            'listingstatus' => 'Listingstatus',
            'listingduration' => 'Listingduration',
            'buyitnowprice' => 'Buyitnowprice',
            'startprice' => 'Startprice',
            'desc' => 'Desc',
            'sku' => 'Sku',
            'lastsolddatetime' => 'Lastsolddatetime',
            'paypal' => 'Paypal',
            'outofstockcontrol' => 'Outofstockcontrol',
            'isvariation' => 'Isvariation',
            'mainimg' => 'Mainimg',
            'createtime' => 'Createtime',
            'updatetime' => 'Updatetime',
            'bukucun' => 'Bukucun',
            'less' => 'Less',
            'bu' => 'Bu',
        ];
    }
    
    public function getDetail(){
    	return $this->hasOne(EbayItemDetail::className(),['itemid'=>'itemid']);
    }
}
