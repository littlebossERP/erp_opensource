<?php

namespace eagle\modules\order\models;

use Yii;

/**
 * This is the model class for table "od_order_relation".
 *
 * @property integer $id
 * @property integer $father_orderid
 * @property integer $son_orderid
 * @property string $type
 */
class OrderRelation extends \eagle\models\order\OrderRelation
{
    public function getSonorder(){
    	
    	return $this->hasOne(OdOrder::className(), ['order_id'=>'son_orderid']);
    	//return $this->hasMany(OdOrder::className(),['order_id'=>'son_orderid']); 
    }
    
    
    public function getFatherorder(){
    	return $this->hasOne(OdOrder::className(), ['order_id'=>'father_orderid']);
    }
    
    public function setSonorder($sonorder){
    	$this->sonorder = $sonorder;
    }
    
}
