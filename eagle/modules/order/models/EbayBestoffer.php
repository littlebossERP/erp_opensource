<?php

namespace eagle\modules\order\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "ebay_bestoffer".
 *
 * @property string $bestofferid
 * @property integer $uid
 * @property string $selleruserid
 * @property string $itemid
 * @property string $bestoffer
 * @property string $bestofferstatus
 * @property string $itembestoffer
 * @property integer $createtime
 * @property integer $status
 * @property string $desc
 * @property double $counterofferprice
 * @property string $operate
 */
class EbayBestoffer extends \yii\db\ActiveRecord
{
	static public $bestofferstatus=array(
			'Accepted'=>'Accepted' ,
			'Declined'=>'Declined' ,
			'Active'=>'Active' ,
			'AdminEnded'=>'AdminEnded',
			'All'=>'All',
			'Countered'=>'Countered',
			'CustomCode'=>'CustomCode',
			'Declined'=>'Declined',
			'Expired'=>'Expired',
			'Pending'=>'Pending',
			'PendingBuyerConfirmation'=>'PendingBuyerConfirmation',
			'PendingBuyerPayment'=>'PendingBuyerPayment',
			'Retracted'=>'Retracted',
	);
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_bestoffer';
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
            [['bestofferid'], 'required'],
            [['bestofferid', 'uid', 'itemid', 'createtime', 'status'], 'integer'],
            [['counterofferprice'], 'number'],
            [['selleruserid'], 'string', 'max' => 255],
            [['bestofferstatus'], 'string', 'max' => 30],
            [['desc'], 'string', 'max' => 80],
            [['operate'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'bestofferid' => 'Bestofferid',
            'uid' => 'Uid',
            'selleruserid' => 'Selleruserid',
            'itemid' => 'Itemid',
            'bestoffer' => 'Bestoffer',
            'bestofferstatus' => 'Bestofferstatus',
            'itembestoffer' => 'Itembestoffer',
            'createtime' => 'Createtime',
            'status' => 'Status',
            'desc' => 'Desc',
            'counterofferprice' => 'Counterofferprice',
            'operate' => 'Operate',
        ];
    }
    
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('bestoffer'),
    			)
    	);
    }

}
