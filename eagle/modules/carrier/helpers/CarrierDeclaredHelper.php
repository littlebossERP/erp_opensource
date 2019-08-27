<?php
namespace eagle\modules\carrier\helpers;

use eagle\models\carrier\OrderDeclaredInfo;
use eagle\modules\catalog\helpers\ProductApiHelper;
use Qiniu\json_decode;


class CarrierDeclaredHelper {
	
	/**
	 +----------------------------------------------------------
	 * 批量保存订单sku级别报关信息
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param $declared_params	
	 * platform_type	对应平台的类型
	 * itemID			对应平台的ItemID
	 * sku				对应平台的SKU
	 * ch_name			中文报关名
	 * en_name			英文报关名
	 * declared_value	报关价值
	 * declared_weight	报关重量
	 * detail_hs_code	海关编码
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2017/03/02				初始化
	 +----------------------------------------------------------
	 *
	 **/
	public static function setOrderSkuDeclaredInfoBatch($declared_params){
// 		$declared_params = array();
// 		$declared_params[] = array('platform_type'=>'ebay','itemID'=>'123','sku'=>'sku001','ch_name'=>'报关中1','en_name'=>'declared_en','declared_value'=>1,'declared_weight'=>20,'detail_hs_code'=>'onon');
		
		foreach ($declared_params as $declared_val){
			$tmp_order_declare_mode = OrderDeclaredInfo::find()->where(['platform'=>$declared_val['platform_type']]);
			
			if($declared_val['itemID'] != ''){
				$tmp_order_declare_mode->andWhere(['itemID'=>$declared_val['itemID']]);
			}else if($declared_val['sku'] != ''){
				$tmp_order_declare_mode->andWhere(['sku'=>$declared_val['sku']]);
			}else {
				continue;
			}
				
			$tmp_order_declare_info = $tmp_order_declare_mode->one();
			
			if($tmp_order_declare_info == null){
				$tmp_order_declare_info = new OrderDeclaredInfo();
				$tmp_order_declare_info->platform = $declared_val['platform_type'];
				$tmp_order_declare_info->itemID = $declared_val['itemID'];
				$tmp_order_declare_info->sku = $declared_val['sku'];
			}
			
			$tmp_order_declare_info->ch_name = $declared_val['ch_name'];
			$tmp_order_declare_info->en_name = $declared_val['en_name'];
			$tmp_order_declare_info->declared_value = $declared_val['declared_value'];
			$tmp_order_declare_info->declared_weight = $declared_val['declared_weight'];
			$tmp_order_declare_info->detail_hs_code = $declared_val['detail_hs_code'];
			
			$tmp_order_declare_info->save(false);
		}
		
		return true;
	}
	
	/**
	 +----------------------------------------------------------
	 * 单张订单获取相关报关信息，1.订单表的报关信息。2.商品档案的报关信息。3.订单SKU表的报关信息。4.全局报关信息。
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * @param $order	订单mode
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		hqw 	2017/03/02				初始化
	 +----------------------------------------------------------
	 * return $item_declared_info Array
	 (
		 [1] => Array
		 (
			 [xlb_item] => 1					小老板od_order_item_v2 表的order_item_id字段
			 [not_declaration] => 0			是否空报关信息
			 [declaration] => Array			报关相关信息
			 (
				 [nameCN] => 中1			报关中文名
				 [nameEN] => en1			报关英文名
				 [price] => 1.6			报关价值
				 [weight] => 29			报关重量
				 [code] => bg1			海关编码
			 )
		 )
		 [2] => Array
		 (
			 [xlb_item] => 2
			 [not_declaration] => 0
			 [declaration] => Array
			 (
				 [nameCN] => 充电线-红
				 [nameEN] => Charging line - red
				 [price] => 3.00
				 [weight] => 5
				 [code] =>
			 )
		 )
	 )
	 *
	 **/
	public static function getOrderDeclaredInfoByOrder($order){
		//记录需要传递的参数组织
		$order_items_info = array();
		
		//记录返回的结构
		$result_order_items_info = array();
		
		if(count($order->items) <= 0) return $result_order_items_info;
		
		foreach($order->items as $item){
			if($order->order_source == 'cdiscount'){
				$nonDeliverySku = \eagle\modules\order\helpers\CdiscountOrderInterface::getNonDeliverySku();
				if(empty($item->sku) or in_array(strtoupper($item->sku),$nonDeliverySku) ) continue;
			}
			
			$tmp_platform_itme_id = \eagle\modules\order\helpers\OrderGetDataHelper::getOrderItemSouceItemID($order->order_source , $item);
			$order_items_info[] = array('platform_type'=>$order->order_source, 'order_status'=>$order->order_status, 'xlb_item'=>$item->order_item_id,'sku'=>$item->sku, 'root_sku'=>$item->root_sku, 'itemID'=>$tmp_platform_itme_id, 'declaration'=>json_decode($item->declaration, true));
		}
		
		$result_order_items_info = self::getOrderDeclaredInfoBatch($order_items_info);
		
		return $result_order_items_info;
	}
	
	
	/**
     +----------------------------------------------------------
     * 批量订单获取相关报关信息，1.订单表的报关信息。2.商品档案的报关信息。3.订单SKU表的报关信息。4.全局报关信息。
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param $order_items_info		
     * platform_type	对应平台类型
     * order_status		小老板订单状态
     * xlb_item			小老板od_order_item_v2 表的order_item_id字段
     * root_sku			订单表的root_sku
     * itemID			对应平台的ItemID
     * declaration array(		该值直接根据od_order_item_v2表的declaration来传入即可
     * 		nameCN
     * 		nameEN
     * 		price
     * 		weight
     * 		code
     * 		isChange
     * )
     +----------------------------------------------------------
     * log			name	date					note
     * @author		hqw 	2017/03/02				初始化
     +----------------------------------------------------------
     * return $item_declared_info Array
		(
		    [1] => Array
		        (
		            [xlb_item] => 1					小老板od_order_item_v2 表的order_item_id字段
		            [not_declaration] => 0			是否空报关信息
		            [declaration] => Array			报关相关信息
		                (
		                    [nameCN] => 中1			报关中文名
		                    [nameEN] => en1			报关英文名
		                    [price] => 1.6			报关价值
		                    [weight] => 29			报关重量
		                    [code] => bg1			海关编码
		                )
		        )
		    [2] => Array
		        (
		            [xlb_item] => 2
		            [not_declaration] => 0
		            [declaration] => Array
		                (
		                    [nameCN] => 充电线-红
		                    [nameEN] => Charging line - red
		                    [price] => 3.00
		                    [weight] => 5
		                    [code] => 
		                )
		
		        )
		)
     *
     **/
	public static function getOrderDeclaredInfoBatch($order_items_info, $is_all_declared = false){
		//item报关信息
		$item_declared_info = array();
		
		//记录曾经的搜索过的SKU
		$products = array();
		
		//记录曾经搜索过的订单级别SKU
		$order_declare_all = array();
		
		//全局的报关信息
		$tmpCommonDeclaredInfo = CarrierOpenHelper::getCommonDeclaredInfoByDefault();
		
// 		//组织测试数据
// 		$order_items_info = array();
// 		$order_items_info[] = array('platform_type'=>'ebay', 'order_status'=>200, 'xlb_item'=>'1', 'root_sku'=>'root_sku_01', 'itemID'=>'0001', 'declaration'=>array('nameCN'=>'中1', 'nameEN'=>'en1', 'price'=>1.6, 'weight'=>29, 'code'=>'bg1', 'isChange'=>'Y'));
// 		$order_items_info[] = array('platform_type'=>'ebay', 'order_status'=>200, 'xlb_item'=>'2', 'root_sku'=>'123', 'itemID'=>'123', 'declaration'=>array('nameCN'=>'中2', 'nameEN'=>'en2', 'price'=>1.6, 'weight'=>25, 'code'=>'bg2', 'isChange'=>'N'));
// 		$order_items_info[] = array('platform_type'=>'ebay', 'order_status'=>200, 'xlb_item'=>'3', 'root_sku'=>'010001-10', 'itemID'=>'0002', 'declaration'=>array('nameCN'=>'中2', 'nameEN'=>'en2', 'price'=>1.6, 'weight'=>25, 'code'=>'bg2', 'isChange'=>'N'));
		
		if(count($order_items_info) == 0){
			return array('error'=>true, 'msg'=>'请传入相关item数据');
		}
		
		foreach ($order_items_info as $order_items_one){
			//判断是否需要重新读取报关信息订单级别的报关信息
			$is_order_declared = false;
			
			if((empty($order_items_one['declaration']['isChange']) ? '' : $order_items_one['declaration']['isChange']) == 'Y'){
				$is_order_declared = false;
			}
			
			//如果小老板订单状态是已付款，并且用户并没有修改过报关信息也是需要重新获取一下
			if(($order_items_one['order_status'] == 200) && ((empty($order_items_one['declaration']['isChange']) ? '' : $order_items_one['declaration']['isChange']) == 'N')){
				$is_order_declared = true;
			}
			
			if(empty($order_items_one['declaration'])){
				$is_order_declared = true;
			}
			
			//1获取订单表修改过的报关信息
			if($is_order_declared == false){
				unset($order_items_one['declaration']['isChange']);
				$item_declared_info[$order_items_one['xlb_item']] = array('xlb_item'=>$order_items_one['xlb_item'],'sku' => $order_items_one['sku'],'not_declaration' => 0, 'declaration'=>$order_items_one['declaration']);
				continue;
			}
			
			//2获取商品档案的报关信息
			if($order_items_one['root_sku'] != ''){
				//记录查找过的root_SKU
				if(!isset($products[$order_items_one['root_sku']])){
					$product = ProductApiHelper::getProductInfo($order_items_one['root_sku']);
					
					if(!empty($product)){
						$products[$order_items_one['root_sku']] = array('declaration_ch'=>$product['declaration_ch'], 'declaration_en'=>$product['declaration_en'], 'declaration_value'=>$product['declaration_value'], 'prod_weight'=>$product['prod_weight'], 'declaration_code'=>$product['declaration_code']);
					}else{
						$products[$order_items_one['root_sku']] = array();
					}
					unset($product);
				}
				
				$product = $products[$order_items_one['root_sku']];
				
				if(!empty($product)){
					$item_declared_info[$order_items_one['xlb_item']] = array(
							'xlb_item'=>$order_items_one['xlb_item'],
							'sku' => $order_items_one['sku'],
							'not_declaration' => 0,
							'declaration'=>array('nameCN'=>$product['declaration_ch'], 'nameEN'=>$product['declaration_en'], 'price'=>$product['declaration_value'], 'weight'=>$product['prod_weight'], 'code'=>$product['declaration_code'])
					);
					
					continue;
				}
			}
			
			//3获取订单SKU级别表的报关信息
			if((!empty($order_items_one['root_sku'])) || (!empty($order_items_one['itemID']))){
				$tmp_order_declare_mode = OrderDeclaredInfo::find()->where(['platform'=>$order_items_one['platform_type']]);
				
				$tmp_item_or_sku = '';
				
				if(!empty($order_items_one['itemID'])){
					//由于ebay多属性对应的itemID是一样的，所以需itemID跟sku一起匹对
					if($order_items_one['platform_type'] == 'ebay'){
						$tmp_order_declare_mode->andWhere(['itemID'=>$order_items_one['itemID'], 'sku' => $order_items_one['sku']]);
						$tmp_item_or_sku = $order_items_one['itemID'];
					}
					else{
						$tmp_order_declare_mode->andWhere(['itemID'=>$order_items_one['itemID']]);
						$tmp_item_or_sku = $order_items_one['itemID'];
					}
				}else{
					$tmp_order_declare_mode->andWhere(['sku'=>$order_items_one['root_sku']]);
					$tmp_item_or_sku = $order_items_one['root_sku'];
				}
				
				//判断是否之前已经存在
				if(!isset($order_declare_all[$order_items_one['platform_type'].$tmp_item_or_sku])){
					$tmp_order_declare_info = $tmp_order_declare_mode->one();
					
					if($tmp_order_declare_info !== null){
						$order_declare_all[$order_items_one['platform_type'].$tmp_item_or_sku] = array('ch_name'=>$tmp_order_declare_info['ch_name'], 'en_name'=>$tmp_order_declare_info['en_name'], 'declared_value'=>$tmp_order_declare_info['declared_value'], 'declared_weight'=>$tmp_order_declare_info['declared_weight'], 'detail_hs_code'=>$tmp_order_declare_info['detail_hs_code']);
					}else{
						$order_declare_all[$order_items_one['platform_type'].$tmp_item_or_sku] = array();
					}
					
					unset($tmp_order_declare_info);
				}
				
				$tmp_order_declare_info = $order_declare_all[$order_items_one['platform_type'].$tmp_item_or_sku];
				
				
				if(!empty($tmp_order_declare_info)){
					$item_declared_info[$order_items_one['xlb_item']] = array(
							'xlb_item'=>$order_items_one['xlb_item'],
							'sku' => $order_items_one['sku'],
							'not_declaration' => 0,
							'declaration'=>array('nameCN'=>$tmp_order_declare_info['ch_name'], 'nameEN'=>$tmp_order_declare_info['en_name'], 'price'=>$tmp_order_declare_info['declared_value'], 'weight'=>$tmp_order_declare_info['declared_weight'], 'code'=>$tmp_order_declare_info['detail_hs_code'])
					);
						
					continue;
				}
			}
			
			//5获取全局的报关信息
			$item_declared_info[$order_items_one['xlb_item']] = array(
					'xlb_item'=>$order_items_one['xlb_item'],
					'sku' => $order_items_one['sku'],
					'not_declaration' => (empty($tmpCommonDeclaredInfo['id']) ? 1 : (($is_all_declared == false) ? 0 : 2)),
					'declaration'=>array('nameCN'=>$tmpCommonDeclaredInfo['ch_name'], 'nameEN'=>$tmpCommonDeclaredInfo['en_name'], 'price'=>$tmpCommonDeclaredInfo['declared_value'], 'weight'=>$tmpCommonDeclaredInfo['declared_weight'], 'code'=>$tmpCommonDeclaredInfo['detail_hs_code'])
			);
		}
		
		return $item_declared_info;
	}
	
	
	
}