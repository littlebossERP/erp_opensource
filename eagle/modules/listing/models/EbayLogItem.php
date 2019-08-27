<?php

namespace eagle\modules\listing\models;

use Yii;
use yii\behaviors\SerializeBehavior;

/**
 * This is the model class for table "ebay_log_item".
 *
 * @property string $id
 * @property integer $mubanid
 * @property integer $type
 * @property string $name
 * @property string $reason
 * @property string $itemid
 * @property string $content
 * @property string $result
 * @property string $message
 * @property integer $createtime
 * @property string $transactionid
 */
class EbayLogItem extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'ebay_log_item';
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
            [['mubanid', 'type', 'itemid', 'createtime', 'transactionid'], 'integer'],
            [['name'], 'required'],
//            [['content', 'message'], 'string'],
            [['name'], 'string', 'max' => 50],
            [['reason'], 'string', 'max' => 255],
            [['result'], 'string', 'max' => 15]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'mubanid' => 'Mubanid',
            'type' => 'Type',
            'name' => 'Name',
            'reason' => 'Reason',
            'itemid' => 'Itemid',
            'content' => 'Content',
            'result' => 'Result',
            'message' => 'Message',
            'createtime' => 'Createtime',
            'transactionid' => 'Transactionid',
        ];
    }
    
    public function behaviors(){
    	return array(
    			'SerializeBehavior' => array(
    					'class' => SerializeBehavior::className(),
    					'serialAttributes' => array('message','content'),
    			)
    	);
    }
    
    /**
     * 用于静态记录用户修改Itemlog
     * $type 修改方式（1批量修改价格 and 2修改商品描述 and 3汇率调整  and 4应用公共模块）
     * @author fanjs
     */
    public static function Addlog($mubanid,$username,$type,$itemid,$data,$result,$transactionid=null){
    	$reviseitem = new self();
    	$reviseitem->mubanid=$mubanid;
    	$reviseitem->name=$username;
    	$reviseitem->reason=$type;
    	$reviseitem->itemid=$itemid;
    	$reviseitem->transactionid=$transactionid;
    	$reviseitem->content=[$data];
    	$reviseitem->result=$result['Ack'];
    	$reviseitem->message=$result;
    	$reviseitem->createtime = time();
    	$reviseitem->save();
    }
}
