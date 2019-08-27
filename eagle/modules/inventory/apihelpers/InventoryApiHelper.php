<?php
namespace eagle\modules\inventory\apihelpers;
use eagle\modules\inventory\helpers\InventoryHelper;
use eagle\modules\inventory\helpers\WarehouseHelper;
use eagle\modules\inventory\helpers\OverseaWarehouseHelper;
use eagle\modules\inventory\helpers\FbaWarehouseHelper;


/**
 * 仓库模块对外接口业务逻辑类
 * @author 	million
 * @version 	0.1		2015.01.15
 */
class InventoryApiHelper{
	/**
	 * 获取一个sku的库存拣货信息
	 * @access 	static
	 * @param	string:	$sku			需要拣货的产品sku数组
	 * @param	string:	$warehouseId	拣货的仓库id
	 * @return	array:	$pickingInfo = array(
	 * 											'prod_stock_id'=>number,
	 * 											'warehouse_id'=>number,
	 * 											'sku'=>string,
	 * 											'location_grid'=>string,
	 * 											'qty_in_stock'=>number,
	 * 											'qty_purchased_coming'=>number,
	 * 											'qty_ordered'=>number,
	 * 											'qty_order_reserved'=>number,
	 * 											'average_price'=>number,
	 * 											'total_purchased'=>number,
	 * 											'addi_info'=>string,
	 * 											'update_time'=>string,
	 * 											)
	 * @author 	million
	 * @version 	0.1		2015.01.15
	 */
	public static function getPickingInfo($warehouseId,$sku){
		return $pickingInfo = InventoryHelper::getPickingInfo($warehouseId,$sku);
	}
	/**
	 * 获取多个sku的库存配货信息
	 * @access 	static
	 * @param	array:	$skuArr			需要拣货的产品sku数组
	 * @param	string:	$warehouseId	拣货的仓库id
	 * @return	array:	$pickingInfos = array(
	 * 											'prod_stock_id'=>number,
	 * 											'warehouse_id'=>number,
	 * 											'sku'=>string,
	 * 											'location_grid'=>string,
	 * 											'qty_in_stock'=>number,
	 * 											'qty_purchased_coming'=>number,
	 * 											'qty_ordered'=>number,
	 * 											'qty_order_reserved'=>number,
	 * 											'average_price'=>number,
	 * 											'total_purchased'=>number,
	 * 											'addi_info'=>string,
	 * 											'update_time'=>string,
	 * 											)
	 * @author 	million
	 * @version 	0.1		2015.01.15
	 */
	public static function getPickingInfos($skuArr=array(),$warehouseId){
		return $pickingInfos = InventoryHelper::getPickingInfos($skuArr,$warehouseId);
	}
	
	/**
	 * 更新单个sku某仓库内的待发货数量
	 * @access 	static
	 * @param	int:	$warehouseid		仓库id
	 * @param	string:	$sku	            sku
	 * @param	int:	$qty	                                    变更数量
	 * @return	array:	array(
* 								status,
* 								msg,
* 							 )
	 * @author 	lrq
	 * @version 	0.1		2016.09.08
	 */
	public static function updateQtyOrdered($warehouseid, $sku, $qty){
		return $pickingInfos = WarehouseHelper::SaveQtyOrdered($warehouseid, $sku, $qty);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取SKU对应的海外仓配对SKU，当没有匹配，则返回自身
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
     * @param $sku           订单SKU
     * @param $warehouse_id  海外仓仓库Id
     * @param $accountid     海外仓绑定账号Id
	 +---------------------------------------------------------------------------------------------
	 * @return ['status', 'seller_sku', 'msg']
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/11/24		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function Get_OverseaWarehouseSku($sku, $warehouse_id, $accountid){
		return OverseaWarehouseHelper::Get_OverseaWarehouseSKU($sku, $warehouse_id, $accountid);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 更新fba库存
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $merchant_id     Amazom账号id
	 * @param $marketplace_id  Amazom站点id
	 * @param $data            库存信息，array()
	 +---------------------------------------------------------------------------------------------
	 * @return ['status', 'msg']
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/12/05		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function UpdateFbaSku($merchant_id, $marketplace_id, $data){
		return FbaWarehouseHelper::UpdateFbaSku($merchant_id, $marketplace_id, $data);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 查询SKU对应的库存信息
	 +---------------------------------------------------------------------------------------------
	 * @param $sku_list        array    sku组合
	 * @param $warehouse_list  array    仓库id组合
	 +---------------------------------------------------------------------------------------------
	 * @return [
	 * 		warehouse_id => [
	 *	 		sku => stock,
	 * 		]
	 *	]
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2017/11/02		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function GetSkuStock($sku_list, $warehouse_list){
		return WarehouseHelper::GetSkuStock($sku_list, $warehouse_list);
	}
}