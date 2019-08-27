<?php
namespace eagle\modules\delivery\apihelpers;
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
use eagle\modules\delivery\models\OdDeliveryOrder;
use eagle\modules\order\models\OdOrder;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\delivery\models\OdDelivery;
use render\form\select\Select;
use eagle\modules\util\helpers\OperationLogHelper;
Class DeliveryApiHelper{
	/**
	 +---------------------------------------------------------------------------------------------
	 * 取消拣货单中指定订单号的相关数据
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$order_id			order id  (小老板的订单号)
	 * 			$module				模块名
	 * 			$action				执行fcwt
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 			success				执行是否成功	
	 * 			message				执行结果
	 * 			count				影响数
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 		取消拣货单中指定订单号的相关数据
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function cancelOrderDeliveryMapping($order_id,$module='order',$action='取消拣货单'){
		if (!empty(OdDeliveryOrder::findOne(['order_id'=>$order_id])->delivery_id))
			$deliveryid= OdDeliveryOrder::findOne(['order_id'=>$order_id])->delivery_id;
		else 
			$deliveryid = '';
		
		//初始化 返回结果
		$result = ['success' =>true , 'message'=>'' ,'count'=>0];
		$result['count'] = OdDeliveryOrder::deleteAll(['order_id'=>$order_id]);
		if ($result['count'] ==0){
			$result['message'] = $order_id."已经删除,请不要重复删除！";
		}else{
			$result['message']= $order_id."成功删除！";
			
			//订单操作日志
			OperationLogHelper::log($module,$order_id,$action,'删除拣货单',\Yii::$app->user->identity->getFullName());
			/**
			 * @todo 重新计算拣货单相关统计数据，订单数，sku种类上，商品总数等
			 */
			$ordercount=array();$sku_arr = array();$goodscounts = 0;
				
			$deliveryorders=OdDeliveryOrder::find()->where('delivery_id = :delivery_id',[':delivery_id'=>$deliveryid])->all();
			// 		 OdDeliveryOrder::find()->where('delivery_id = :delivery_id',[':delivery_id'=>$deliveryid]) ->sum('skucount')->all();
			foreach ($deliveryorders as $deliveryorder)
			{
				$goodscounts+=$deliveryorder->count;
				$sku_arr[$deliveryorder->sku] =1;
				$ordercount[$deliveryorder->order_id]=1;
			}
			
			
			$delivery=OdDelivery::find()->where('deliveryid = :deliveryid',[':deliveryid'=>$deliveryid])->one();
			if(!empty($delivery)){
				$delivery->ordercount=count($ordercount);
				$delivery->skucount=count($sku_arr);
				$delivery->goodscount=$goodscounts;
				$delivery->save();
			}
		}
		
		return $result;
	}//end of cancelOrderDeliveryMapping
	/**
	 +---------------------------------------------------------------------------------------------
	 * 重置订单状态
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 需要重置的orderid数组 $orderids
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 			error				执行是否全部成功
	 * 			error_orders		失败的订单
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		zwd		2016/03/16				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function resetOrder($orderids){
		$ret = ['error'=>0,'error_orders'=>''];
		foreach ($orderids as $id){
			
			$odOrder_obj = OdOrder::findOne($id);
			
			$odOrder_obj->carrier_step = OdOrder::CARRIER_CANCELED;//重新上传
			
			if(!$odOrder_obj->save()){
				$ret['msg'] .= $id.',';
			}
		}
		if(!empty($ret['msg'])) $ret['error'] = 1;
		return $ret;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取 通知平台发货承运商设置 
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 * 			$platform			平台类型
	 * 			$customShippingCode	自定义的承运商
	 +---------------------------------------------------------------------------------------------
	 * @return	array
	 * 			success				执行是否成功
	 * 			message				执行结果
	 * 			count				影响数
	 +---------------------------------------------------------------------------------------------
	 * @description
	 * 		取消拣货单中指定订单号的相关数据
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/12/17				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function getShippingCodeByPlatform($platform , $customShippingCode='' ){
		//不同的平台所拥有的标志发货选项是不同的
		switch ($platform){
			case 'ebay':
				$serviceData = json_decode(file_get_contents(\Yii::getAlias('@web').'docs/ebayServiceCode.json'),true);
				$display_type = 'text';
				break;
			case 'aliexpress':
				$serviceData = \common\api\aliexpressinterface\AliexpressInterface_Helper::getShippingCodeNameMap();
				$display_type = 'dropdownlist';
				break;
			case 'wish':
				$serviceData = \eagle\modules\order\helpers\WishOrderInterface::getShippingCodeNameMap();
				asort($serviceData);
				$display_type = 'dropdownlist';
				break;
			case 'amazon':
				$serviceData = \eagle\modules\amazon\apihelpers\AmazonApiHelper::getShippingCodeNameMap();
				asort($serviceData);
				$display_type = 'dropdownlist';
				break;
			case 'dhgate':
				$serviceData = \eagle\modules\dhgate\apihelpers\DhgateApiHelper::getShippingCodeNameMap();
				$display_type = 'dropdownlist';
				break;
			case 'lazada':
				$serviceData = \eagle\modules\lazada\apihelpers\LazadaApiHelper::getShippingCodeNameMap();
				$display_type = 'dropdownlist';
				break;
			case 'linio':
				$serviceData = \eagle\modules\lazada\apihelpers\LazadaApiHelper::getLinioShippingCodeNameMap();
				$display_type = 'dropdownlist';
				break;
			case 'ensogo':
				$serviceData = \eagle\modules\order\helpers\EnsogoOrderInterface::getShippingCodeNameMap();
				$display_type = 'dropdownlist';
				break;
			case 'priceminister':
				$serviceData = \eagle\modules\order\helpers\PriceministerOrderInterface::getShippingCodeNameMap();
				$display_type = 'dropdownlist';
				break;
			case 'cdiscount':
				$serviceData = \eagle\modules\order\helpers\CdiscountOrderInterface::getShippingCodeNameMap();
				$display_type = 'dropdownlist';
				break;
			case 'rumall':
			    $serviceData = \eagle\modules\order\helpers\RumallOrderHelper::getShippingCodeNameMap();
			    $display_type = 'dropdownlist';
			    break;
			case 'newegg':
				$serviceData = array();
				$display_type = 'text';
				break;
			default:
				$serviceData = '';
				$display_type = 'text';
				break;
				
				
			
		}
		
		//设置默认值
		if (!empty($customShippingCode) ){
			if (is_array($serviceData)){
				$serviceData [$customShippingCode] = $customShippingCode;
			}else{
				$serviceData = $customShippingCode;
			}
		
		}
			
		return array($serviceData , $display_type);
		
	}//end of getShippingCodeByPlatform
	
	public static function formatFileLog($prefix="fileLog:",$timeLogArr=array()){
		$totalTimeLog = "";
		$firstTimeLog = 0;
		$firstKey = "";
		$lastTimeLog = 0;
		$lastKey = "";
		foreach ($timeLogArr as $key=>$timeLog){
			if(!empty($totalTimeLog)){
				$totalTimeLog .= $key."_".$lastKey."=".($timeLog-$lastTimeLog).",";
			}else{
				$firstTimeLog = $timeLog;
				$firstKey = $key;
				$totalTimeLog = $prefix;
			}
			$lastKey = $key;
			$lastTimeLog = $timeLog;
		}
			
		$totalTimeLog .= $lastKey."_".$firstKey."=".($lastTimeLog-$firstTimeLog);
		\Yii::info($totalTimeLog,"file");
	}
	
}//end of class DeliveryApiHelper