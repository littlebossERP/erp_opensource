<?php
namespace eagle\modules\statistics\controllers;

use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\catalog\models\Product;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\StandardConst;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\catalog\models\ProductSuppliers;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\listing\models\CdiscountOfferList;
use eagle\modules\order\helpers\CdiscountOrderInterface;

class OrderProfitHelper{
	
	protected static $EXCEL_PRODUCT_COST_COLUMN_MAPPING = array (
			"A" => "sku", //SKU
			"B" => "purchase_price", // 采购价
			"C" => "additional_cost", // 其他费用
	);
	
	protected static $EXCEL_ORDER_LOGISTICS_COST_COLUMN_MAPPING = array (
			"A" => "order_number", //原始订单号
			"B" => "order_source", // 来源平台
			"C" => "logistics_cost", // 物流成本
			"D" => "logistics_weight", // 包裹重量
	);
	
	/**
	 * +----------------------------------------------------------
	 * 导入商品成本的映射关系
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @return 导入商品成本的映射关系
	 * +----------------------------------------------------------
	 * log			name		date			note
	 * @author 		lzhl		2016/03/15		初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function get_EXCEL_PRODUCT_COST_COLUMN_MAPPING(){
		return self::$EXCEL_PRODUCT_COST_COLUMN_MAPPING;
	}//end of get_EXCEL_PRODUCT_COST_COLUMN_MAPPING
	
	/**
	 * +----------------------------------------------------------
	 * 导入定点杆物流成本的映射关系
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @return 导入定点杆物流成本的映射关系
	 * +----------------------------------------------------------
	 * log			name		date			note
	 * @author 		lzhl		2016/03/15		初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function get_EXCEL_ORDER_LOGISTICS_COST_COLUMN_MAPPING(){
		return self::$EXCEL_ORDER_LOGISTICS_COST_COLUMN_MAPPING;
	}//end of get_EXCEL_ORDER_LOGISTICS_COST_COLUMN_MAPPING
	
	
	
	
	
	
	
	
	
	
	
	
	/**
	 * +----------------------------------------------------------
	 * 导入订单的发货物流成本信息
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	array
	 *+----------------------------------------------------------
	 * @return 	操作结果Array
	 * +----------------------------------------------------------
	 * log			name		date			note
	 * @author 		lzhl		2016/03/16		初始化
	 *+----------------------------------------------------------
	 *
	 */
	public static function importOrderLogisticsCostData($logisticsData){
		$rtn['success'] = true;
		$rtn['message'] = '';
		$errMsg = '';
		
		$journal_id = SysLogHelper::InvokeJrn_Create("Order", __CLASS__, __FUNCTION__ ,array($logisticsData));
		
		if(!is_array($logisticsData)){
			$rtn['success'] = false;
			$rtn['message'] ='数据格式有误。';
			return $rtn;
		}
		
		foreach ($logisticsData as $index=>$info){
			$order_no = trim($info['order_number']);
			if(empty($order_no)){
				$rtn['success'] = false;
				$rtn['message'] .= '第'.$index.'行订单号为空，跳过该行处理;<br>';
				continue;
			}
			$order_source = trim($info['order_source']);
			$order_source = strtolower($order_source);
			if(!empty(OdOrder::$orderSource))
				$platforms = array_keys(OdOrder::$orderSource);
			else 
				$platforms = ['ebay','amazon','aliexpress','wish','dhgate','cdiscount','lazada','linio','jumia','ensogo'];
			if(!in_array($order_source,$platforms)){
				$rtn['success'] = false;
				$rtn['message'] .= '第'.$index.'行订单填入了错误的销售平台值，跳过该行处理;<br>';
				continue;
			}
			$logistics_cost = floatval($info['logistics_cost']);
			$logistics_weight = floatval($info['logistics_weight']);
			$order = OdOrder::find()->where(['order_source_order_id'=>$order_no,'order_source'=>$order_source])->One();
			if(!empty($order)){
				$order->logistics_cost = $logistics_cost;
				$order->logistics_weight = $logistics_weight;
				
				$transaction = \Yii::$app->get('subdb')->beginTransaction();
				if(!$order->save(false)){
					$errMsg .= print_r($order->getErrors(),true);
					$transaction->rollBack();
					SysLogHelper::SysLog_Create('Order',__CLASS__, __FUNCTION__,'error',print_r($order->getErrors(),true));
					$rtn['success'] = false;
					$rtn['message'] .= '订单'.$order_no.'物流成本修改失败;<br>';
					continue;
				}
				$transaction->commit();
			}else{
				$rtn['success'] = false;
				$rtn['message'] .= '订单'.$order_no.'不存在，跳过该修改;<br>';
			}
		}
		
		$rtn['errMsg'] = $errMsg;
		SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		
		return $rtn;
	}
	
	
}//end of class
?>