<?php
namespace eagle\modules\catalog\apihelpers;
use eagle\modules\inventory\apihelpers\InventoryApiHelper;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\catalog\helpers\ProductApiHelper as PProductApiHelper;
/**
 * 商品模块对外接口业务逻辑类
 * @author 	million
 * @version 	0.1		2015.01.13
 */
class ProductApiHelper extends PProductApiHelper{
	/**
	 * 检查这个sku 是否在商品模块对应某个商品
	 * @access 	static
	 * @param	$sku	sku 待检查的商品 SKU/alias（别名）
	 * @return	布尔值		boolean true 为有对应的商品  , false 为没有对应 的商品
	 * @author 	million
	 * @version 	0.1		2015.01.13
	 */
	static public function hasProduct($sku, $platform = '', $selleruserid = ''){
		$root_sku = ProductHelper::getRootSkuByAlias($sku, $platform, $selleruserid);
		return (! empty($root_sku));
	}
	
	/**
	 * 根据参数sku 获取与参数 sku相关的商品信息 
	 * @access 	static
	 * @param	$sku	SKU
	 * @return  如果是变参或者Bundle商品，会有Children这个属性，下面是变参或者bundle
	 * 对应的子产品  	array 	array(
	 *		Sku=>’sk1’,’name’=>’computer’,...  Type='B'/'S'/'C', 代表(’Bundle’/'Simple'/'Config'), 
	 *		Children = array (‘0’=>[sku=’’, name=’’] , ‘1’=>[sku=’’,name=’’])
	 *	)
	 * @author 	million
	 * @version 	0.1		2015.01.13
	 */
	static public function getProductInfo($sku){
		
		return ProductHelper::getProductInfo($sku);
	}
	
	
	/**
	 * +----------------------------------------------------------
	 * 根据参数sku 获取与参数 sku相关的商品信息主要用于发货配货，物流等
	 * +----------------------------------------------------------
	 * @access static
	 *+----------------------------------------------------------
	 * @param	string:		$sku						sku
	 * @param	integer:	$quantity					小老板单号
	 * @param	boolean:	$isGetPickingInfo			是否获取sku的拣货信息  false:不需要，true:需要
	 * @param	integer:	$warehouseId				拣货的仓库id
	 * @param	boolean:	$isSplitBundle				是否拆分捆绑商品 默认true:拆分
	 *+----------------------------------------------------------
	 * @return  skus
	 * 对应产品array
	 array(
	 *		0=>array(Sku=>’sk1’,’name’=>’computer’,...  Type='B'/'S'/'C', 代表(’Bundle’/'Simple'/'Config'),'qty'=>1);
	 *		1=>array(Sku=>’sk1’,’name’=>’computer’,...  Type='B'/'S'/'C', 代表(’Bundle’/'Simple'/'Config'),'qty'=>2);
	 *	)
	 *+----------------------------------------------------------
	 * log			name	date					note
	 * @author 		million 	2015/07/23				初始化
	 *+----------------------------------------------------------
	 */
	static public function getSkuInfo($sku,$quantity=1,$isSplitBundle=true,$isGetPickingInfo=false,$warehouseId=0){
		$skus = array();
		//判断空sku
		$sku = trim($sku);
		if (empty($sku)){
			return $skus;
		}
		//获取商品完整数据
		$info =  self::getProductInfo($sku);
		if ($info==null){//sku不存在
			return $skus;
		} 
		
		//组织数据
		if ($isSplitBundle){//需要拆分捆绑商品
			if ( isset($info['children'] ) && count( $info['children'] ) > 0 ){//有捆绑子商品
				foreach ($info['children'] as $one){
					$one['qty'] = $one['qty']*$quantity;
					//是否获取库存拣货信息
					if ($isGetPickingInfo){
						$pickingInfo_arr = InventoryApiHelper::getPickingInfo($warehouseId,$one['sku']);
						foreach ($pickingInfo_arr as $key => $value){
							$one[$key] = $value;
						}
					}
					$skus[] = $one;
				}
			}else{
				$info['qty'] = $quantity;
				//是否获取库存拣货信息
				if ($isGetPickingInfo){
					$pickingInfo_arr = InventoryApiHelper::getPickingInfo($warehouseId,$info['sku']);
					foreach ($pickingInfo_arr as $key => $value){
						$info[$key] = $value;
					}
				}
				$skus[]=$info;
			}
		}else{//不需要拆分捆绑商品
			$info['qty']=$quantity;
			//是否获取库存拣货信息
			if ($isGetPickingInfo){
				$pickingInfo_arr = InventoryApiHelper::getPickingInfo($warehouseId,$info['sku']);
				foreach ($pickingInfo_arr as $key => $value){
					$info[$key] = $value;
				}
			}
			$skus[] = $info;
		}
		
		return $skus;
	}
}