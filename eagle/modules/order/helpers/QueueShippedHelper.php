<?php
namespace eagle\modules\order\helpers;
use \Yii;
use Exception;
use eagle\models\QueueSyncshipped;
use eagle\modules\order\models\OdOrder;
use eagle\modules\order\models\OdOrderShipped;
/**
 * 处理标记发货队列的相关事务
 * @author million
 * */
class QueueShippedHelper {
	public static $queueShippedDemo = [
			'uid'=>'',//puid
			'selleruserid'=>'',//卖家账号
			'order_source'=>'',//订单来源
			'order_source_order_id'=>'',//订单来源订单id
			'order_source_transaction_id'=>'',//易号或子订单id
			'osid'=>'',//od_order_shipped主键
			'shipping_method_code'=>'',//平台运输服务代码
			'tracking_number'=>'',//物流号
			'tracking_link'=>'',//物流号查询网址
			'description'=>'',//备注
			'signtype'=>'all',//发货类型 all,part
			'status'=>0,//状态
			'params'=>[],//标记发货其他参数
			
	];
	/**
	 * 插入标记队列
	 * @author million
	 * $orderId od_order主键
	 * $osid od_order_shipped主键
	 * return 布尔值
	 */
	public static function insertQueue($orderId,$order_source_order_id,$osid,$params='' , $api_type='1'){
		$orderObj = OdOrder::findOne($orderId);
		$orderShippedObj = OdOrderShipped::findOne($osid);
		\Yii::info("insertQueue: $orderId,$order_source_order_id .param:".print_r($params,true),"file");
		\Yii::info("insertQueue1","file");
		//订单不存在
		if (empty($orderObj)){
			return false;
		}
		\Yii::info("insertQueue2","file");
		
		//物流信息不存在
		if (empty($orderShippedObj)){
			return false;
		}
		\Yii::info("insertQueue3","file");
		
		//非平台订单不用标记发货
		if ($orderObj->order_source =='custom'){
			return false;
		}
		\Yii::info("insertQueue4","file");
		
		//如果有待标记还未标记的队列，以最新的值覆盖
		try {
		    
    		//平台特殊处理
    		if($orderObj->order_source=='cdiscount' && strtolower($orderShippedObj->shipping_method_code)=='other'){
    			$tmp_shipping_method_code= $orderShippedObj->shipping_method_name;
    		}
    		else
    			$tmp_shipping_method_code= $orderShippedObj->shipping_method_code;
    		
    		
    		$queue = QueueSyncshipped::find();
    		$queue->where(['order_source_order_id'=>$order_source_order_id,'status'=>0,'shipping_method_code'=>$tmp_shipping_method_code,'tracking_number'=>$orderShippedObj->tracking_number]);
    		$queueSyncshippedObj = $queue->one();
    		if (empty($queueSyncshippedObj)){
    			$queueSyncshippedObj=new QueueSyncshipped();
    		}
    		
    		$queueSyncshippedObj->uid= \Yii::$app->user->identity->getParentUid();
    		$queueSyncshippedObj->selleruserid= $orderObj->selleruserid;
    		$queueSyncshippedObj->order_source= $orderObj->order_source;
    		$queueSyncshippedObj->order_source_order_id= (string)$order_source_order_id;
    		$queueSyncshippedObj->osid= $osid;
    		
    		//平台特殊处理
    		if($orderObj->order_source=='cdiscount' && strtolower($orderShippedObj->shipping_method_code)=='other'){
    			$queueSyncshippedObj->shipping_method_code= $orderShippedObj->shipping_method_name;
    		}
    		else 
    			$queueSyncshippedObj->shipping_method_code= $orderShippedObj->shipping_method_code;
    		
    		$queueSyncshippedObj->tracking_number= $orderShippedObj->tracking_number;
    		$queueSyncshippedObj->tracking_link= $orderShippedObj->tracking_link;
    		$queueSyncshippedObj->description= $orderShippedObj->description;
    		$queueSyncshippedObj->signtype= $orderShippedObj->signtype;
    		$queueSyncshippedObj->params= $params;
    		$queueSyncshippedObj->status= 0;
    		$queueSyncshippedObj->created= time();
    		$queueSyncshippedObj->order_relation_type = $orderObj->order_relation;
    		$queueSyncshippedObj->api_type = $api_type;
    		
    		if($queueSyncshippedObj->save()){
    			\Yii::info("insertQueue: queueSyncshippedObj-save success : ".json_encode($queueSyncshippedObj->attributes),"file");
    			return true;
    		}else {
    		    \Yii::error("insertQueue: queueSyncshippedObj-save  : ".json_encode($queueSyncshippedObj->errors),"file");
    		  
//     			\Yii::error(["Order",__CLASS__,__FUNCTION__,"Online","add queue sync shipped  failure:".json_encode($queueSyncshippedObj->errors)],"edb\global");
    			return false;
    		}
		
		}catch (\Exception $e){
		    \Yii::error("insertQueue: Exception: file:".$e->getFile().",file:".$e->getLine().",file:".$e->getMessage().".","file");
		    return false;
		} 
		
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	当前订单在虚拟发货队列数据标记为失败
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $OrderSourceOrderID					平台来源订单号即 od_order_v2.order_source_order_id
	 * @param     $platform								平台
	 * @param     $selleruserid							卖家账号
	 * @param     $reasonMsg							取消虚拟发货的原因
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									roleMatch			记录每个规则 匹配中的订单
	 * 									roleAttr			记录规则 的相关信息
	 * 									serviceIdList		记录当前批次出现过的物流方式id
	 *
	 * @invoking					OrderBackgroundHelper::deleteOrderCheckQueue();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function cancelOrderSyncShippedQueue($OrderSourceOrderID , $platform , $selleruserid , $reasonMsg , $osid){
		//只更新队列中未执行（status = 0） 的数据
		$queueSyncShippedModel = QueueSyncshipped::find()->where(['uid'=>\Yii::$app->subdb->getCurrentPuid() , 'order_source_order_id'=>$OrderSourceOrderID , 'order_source'=>$platform , 'selleruserid'=> $selleruserid , 'status'=>'0'])->all();
		$osIDList = [];
		foreach($queueSyncShippedModel as $model){
			if ($model->osid == $osid) continue; //osid 为当前有效的，其他都 标记为无效
			
			$model->status = 2;//标记失败
			$model->save();
			$osIDList[] = $model->osid; 
			
			
		} 
		//$effect = QueueSyncshipped::updateAll(['status'=>'2'],['uid'=>$uid , 'order_source_order_id'=>$OrderSourceOrderID , 'order_source'=>$platform , 'selleruserid'=> $selleruserid , 'status'=>'0']);
		
		$effect = OdOrderShipped::updateAll(['status'=>'2'  , 'result'=>'false','errors'=>$reasonMsg],[ 'id'=>$osIDList]);
	
	}//end of function cancelOrderSyncShippedQueue
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *	更新虚拟发货队列中的订单类型
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param     $orderIDList							小老板单号即 od_order_v2.order_id
	 * @param     $platform								平台
	 * @param     $selleruserid							卖家账号
	 * @param     $orderRelation						订单类型 normal 正常订单 /fm 原始订单
	 +---------------------------------------------------------------------------------------------
	 * @return						array
	 * 									roleMatch			记录每个规则 匹配中的订单
	 * 									roleAttr			记录规则 的相关信息
	 * 									serviceIdList		记录当前批次出现过的物流方式id
	 *
	 * @invoking					OrderBackgroundHelper::deleteOrderCheckQueue();
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/6/30				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function setOrderSyncShippedQueueOrderRelation($orderIDList , $platform , $selleruserid , $orderRelation ){
		$OrderSourceOrderIDList = [];
		$orderList = OdOrder::findAll(['order_id'=>$orderIDList]);
		//找出 所有需要更改的 订单
		foreach($orderList as $order){
			$OrderSourceOrderIDList[] = $order->order_source_order_id;
		}
		if (!empty($OrderSourceOrderIDList)){
			//批量更新 队列的订单类型，主要 是为虚拟发货成功后， 可以成功回写到合并订单
			return QueueSyncshipped::updateAll(['order_relation_type'=>$orderRelation] , ['uid'=>\Yii::$app->user->identity->getParentUid(), 'order_source_order_id'=>$OrderSourceOrderIDList , 'order_source'=>$platform , 'selleruserid'=> $selleruserid , 'status'=>'0']);
		}
		return false;
	}//end of function setOrderSyncShippedQueueOrderRelation
	
}
