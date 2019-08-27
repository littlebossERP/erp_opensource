<?php

namespace eagle\models;

use Yii;
use eagle\modules\listing\models\EbayLogItem;
use eagle\modules\order\models\OdEbayTransaction;

/**
 * This is the model class for table "queue_itemprocess".
 *
 * @property integer $qid
 * @property string $itemid
 * @property string $sku
 * @property string $selleruserid
 * @property integer $type
 * @property integer $created
 * @property integer $updated
 * @property integer $status
 * @property integer $data1
 * @property double $startprice
 * @property string $username
 * @property string $comment
 * @property string $transactionid
 */
class QueueItemprocess extends \yii\db\ActiveRecord
{
	const TYPE_REVISEINVENTORYSTATUS=1;
	const TYPE_SECONDOFFER=2;
	const TYPE_REVISESTARTPRICE=3;
	const TYPE_GETITEM=4;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'queue_itemprocess';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['itemid'], 'required'],
            [['itemid', 'type', 'created', 'updated', 'status', 'data1', 'transactionid'], 'integer'],
            [['startprice'], 'number'],
            [['comment'], 'string'],
            [['sku', 'selleruserid'], 'string', 'max' => 55],
            [['username'], 'string', 'max' => 50],
            [['itemid', 'sku', 'type'], 'unique', 'targetAttribute' => ['itemid', 'sku', 'type'], 'message' => 'The combination of Itemid, Sku and Type has already been taken.']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'qid' => 'Qid',
            'itemid' => 'Itemid',
            'sku' => 'Sku',
            'selleruserid' => 'Selleruserid',
            'type' => 'Type',
            'created' => 'Created',
            'updated' => 'Updated',
            'status' => 'Status',
            'data1' => 'Data1',
            'startprice' => 'Startprice',
            'username' => 'Username',
            'comment' => 'Comment',
            'transactionid' => 'Transactionid',
        ];
    }
    
    /**
     *  自动补货
     */
    static function AddReviseInventoryStatus($itemid,$sku=null,$numsold=0,$transactionid){
    	//已修改的不重复修改
    	$bued_count = EbayLogItem::find()->where(['itemid'=>$itemid,'transactionid'=>$transactionid])->count();
    	if($bued_count>0){
    		return false;
    	}
    	//如果已经在处理队列里了，不用再处理
    	$need_count = self::find()->where(['itemid'=>$itemid,'transactionid'=>$transactionid,'type'=>self::TYPE_REVISEINVENTORYSTATUS])->count();
    	if($need_count>0){
    		return false;
    	}
    	$et=OdEbayTransaction::findOne(['itemid'=>$itemid,'transactionid'=>$transactionid]);
    	$n = self::find()->where(['itemid'=>$itemid,'type'=>self::TYPE_REVISEINVENTORYSTATUS,'sku'=>$sku])->one();
    	if (empty($n)){
    		$n=new self();
    	}
    	$n->itemid=$itemid;
    	$n->selleruserid=$et->selleruserid;
    	$n->type=self::TYPE_REVISEINVENTORYSTATUS;
    	$n->sku=$sku;
    	$n->created = time();
    	$n->updated = time();
    	$n->data1=$numsold;//$n->data1+$numsold;
    	$n->transactionid=$transactionid;
    	$n->status=0;
    	$n->save();
    	return true;
    }
}
