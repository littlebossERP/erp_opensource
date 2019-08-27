<?php
namespace eagle\modules\listing\helpers;

use eagle\modules\listing\models\EbayItemVariationMap;
use eagle\modules\listing\models\EbayItem;
use common\api\ebayinterface\getitem;
use eagle\models\SaasEbayUser;
/**
 * 处理ebayitem的相关方法
 * @author witsionjs
 *
 */

class EbayitemHelper {
	/**
	 * 记录variation到ebay_item_variation_map表
	 */
	static function SaveVariation($itemid,$variations){
		try {
			$sku_arr = array();
			//记录多属性sku
			if (isset($variations['Variation']) && count($variations['Variation'])>0 && is_array($variations['Variation'])){
				foreach ($variations['Variation'] as $variation){
					if (isset($variation['SKU'])) {
						$varSku=$variation['SKU'];
					}else{
						$varSku=NULL;
					}
					// if (strlen($variation['SKU'])>0){
						if (isset($variation['SellingStatus'])){
							$qarray=array();
							$qarray=$variation['SellingStatus'];
							if (isset($qarray['QuantitySold'])){
								$quantitysold=$qarray['QuantitySold'];
							}else{
								$quantitysold=0;
							}
						}else{
							$quantitysold=0;
						}
						$sku_arr[] = $varSku;
						$obj = EbayItemVariationMap::findOne(['itemid'=>$itemid,'sku'=>$varSku]);
						if (empty($obj)){
							$obj = new EbayItemVariationMap();
							$obj->createtime = time();
						}
						$obj->itemid = $itemid;
						$obj->quantity = $variation['Quantity'];
						$obj->quantitysold = $quantitysold;
						$obj->onlinequantity = $variation['Quantity']-$quantitysold;
						$obj->startprice = isset($variation['StartPrice']['Value'])?$variation['StartPrice']['Value']:$variation['StartPrice'];
						$obj->sku = $varSku;
						$obj->updatetime = time();
						$obj->save();
					// }
				}
			}else{
				//将单属性的sku等数据也存入该表，提高搜索效率
				$item=EbayItem::findOne(['itemid'=>$itemid]);
				if (!empty($item)&&strlen($item->sku)>0){
					$sku_arr[] = $item->sku;
					$obj= EbayItemVariationMap::findOne(['itemid'=>$itemid,'sku'=>$item->sku]);
					if (empty($obj)){
						$obj = new EbayItemVariationMap();
						$obj->createtime = time();
					}
					$obj->itemid = $itemid;
					$obj->quantity = $item->quantity;
					$obj->quantitysold = $item->quantitysold;
					$obj->onlinequantity = $item->quantity-$item->quantitysold;
					$obj->startprice = $item->startprice;
					$obj->sku = $item->sku;
					$obj->updatetime = time();
					$obj->save();
				}
			}
			//删除修改后不存在的sku
			if (count($sku_arr)>0){
				// $sku_str = implode('\',\'', $sku_arr);
				// $sku_str = '\''.$sku_str.'\'';
				// EbayItemVariationMap::deleteAll('itemid = '.$itemid.' and sku not in ('.$sku_str.')');
				EbayItemVariationMap::deleteAll([
					'and',
					['itemid'=>$itemid],
					['not in', 'sku', $sku_arr]
				]);

			}else{
				EbayItemVariationMap::deleteAll(['itemid'=>$itemid]);
			}
		}catch (\Exception $e){

			echo "file:".$e->getFile().",line:".$e->getLine().",message:".$e->getMessage();
		}
	}
	
	/**
	 * 获得Item的在售数量
	 *
	 * @param unknown_type $item
	 * @param unknown_type $transactionQuantity
	 * @param unknown_type $variationSKU
	 * @param         &$error
	 * @return unknown
	 */
	static function getQuantityForSale($itemid,$transactionQuantity=0,$transactionid=0,&$variationSKU=null,&$error,$fresh=false){
		$ei = EbayItem::findOne(['itemid'=>$itemid]);
		if (is_null($ei)){
			$error='Item no found 未找到';
			echo "Item no found 未找到\n";
			return false;
		}
	
		//调用一下接口获得最新的数量
		if ($fresh==true){
			$api = new getitem();
			$eu = SaasEbayUser::findOne(['selleruserid'=>$ei->selleruserid]);
			if(empty($eu)){
			    $error='itemid '.$itemid.'-account '.$ei->selleruserid.' not found';
			    echo 'itemid '.$itemid.'-account '.$ei->selleruserid.' not found\n';
			    return false;
			}
			$api->resetConfig($eu->DevAcccountID);
			$api->eBayAuthToken=$eu->token;
//  			$outputSelectors=array(
// 					'Item.Quantity',
// 					'Item.Variations.Variation',
// 					'Item.SKU',
// 					'Item.SellingStatus',
// 					'Item.ItemID',
//  			);
			$outputSelectors = null;
			$getItemResponse=$api->api($itemid,$outputSelectors,'ReturnAll');
			if (!$api->responseIsFailure()){
				$api->save($getItemResponse);
				$ei = EbayItem::findOne(['itemid'=>$itemid]);
			}else{
				//更新失败不予不库存
				$error = 'Item更新失败,系统停止补库存';
				echo "Item更新失败,系统停止补库存\n";
				return false;
			}
		}
	
		if ($ei->isvariation==0){
			if($ei->bukucun == '1'){
				return $ei->quantity-$ei->quantitysold+$transactionQuantity;
			}elseif($ei->bukucun == '2'){
				if (is_null($ei->less) || is_null($ei->bu)){
					$error = 'Item补库存设置有误,请重新设置';
					echo "Item补库存设置有误,请重新设置\n";
					return false;
				}
// 				if($ei->less<2 || $ei->bu == '0'){
// 					$error = '补库存设置在线数量最少不能少于2,补的数量至少为1';
// 					echo "补库存设置在线数量最少不能少于2,补的数量至少为1\n";
// 					return false;
// 				}
				if($ei->bu == '0'){
					$error = '补库存设置补的数量至少为1';
					echo "补库存设置补的数量至少为1\n";
					return false;
				}
				if( ($ei->quantity-$ei->quantitysold)>=$ei->less){
					$error = '补货条件不满足';
					echo "补货条件不满足\n";
					return false;
				}
				return $ei->quantity-$ei->quantitysold+$ei->bu;
			}else{
				$error = 'Item设置已关闭自动补库存';
				echo "Item设置已关闭自动补库存\n";
				return false;
			}
		}else {
			$variations=$ei->detail->variation;
			if (count($variations)<1){
				$error='no variation 没有';
				echo "no variation 没有\n";
				return false;
			}
			//Variation
			if($getItemResponse && isset($getItemResponse['Variations']) && $variationSKU){
				$variation=$getItemResponse['Variations']['Variation'];
				if (isset($variation['StartPrice'])){
					$variation=array($variation);
				}
				if($variation) foreach($variation as $k=>$v){
					if(isset($v['SKU']) && $v['SKU']==$variationSKU){
						$variationSKU=$v['SKU'];
						if ($v['Quantity']-$v['SellingStatus']['QuantitySold']<0){
							$v['Quantity']=$v['SellingStatus']['QuantitySold'];
						}
						if($ei->bukucun == '1'){
							return $v['Quantity']-$v['SellingStatus']['QuantitySold']+$transactionQuantity;
						}elseif($ei->bukucun == '2'){
							if (is_null($ei->less) || is_null($ei->bu)){
								$error = 'Item补库存设置有误,请重新设置';
								echo "Item补库存设置有误,请重新设置\n";
								return false;
							}
// 							if($ei->less<2 || $ei->bu == '0'){
// 								$error = '补库存设置在线数量最少不能少于2,补的数量至少为1';
// 								echo "补库存设置在线数量最少不能少于2,补的数量至少为1\n";
// 								return false;
// 							}
							if($ei->bu == '0'){
								$error = '补库存设置补的数量至少为1';
								echo "补库存设置补的数量至少为1\n";
								return false;
							}
							if( ($ei->quantity-$ei->quantitysold)>=$ei->less){
								$error = '补货条件不满足';
								echo "补货条件不满足\n";
								return false;
							}
							return $v['Quantity']-$v['SellingStatus']['QuantitySold']+$ei->bu;
						}else{
							$error = 'Item设置已关闭自动补库存';
							echo "Item设置已关闭自动补库存\n";
							return false;
						}
					}
				}
			}
	
			foreach ($variations['Variation'] as $k=>$v){
				if (isset($v['SKU']) && $v['SKU']==$variationSKU){
					if ($v['Quantity']-$v['SellingStatus']['QuantitySold']<0){
						$v['Quantity']=$v['SellingStatus']['QuantitySold'];
					}
					if($ei->bukucun == '1'){
						return $v['Quantity']-$v['SellingStatus']['QuantitySold']+$transactionQuantity;
					}elseif($ei->bukucun == '2'){
						if (is_null($ei->less) || is_null($ei->bu)){
							$error = 'Item补库存设置有误,请重新设置';
							echo "Item补库存设置有误,请重新设置\n";
							return false;
						}
// 						if($ei->less<2 || $ei->bu == '0'){
// 							$error = '补库存设置在线数量最少不能少于2,补的数量至少为1';
// 							echo "补库存设置在线数量最少不能少于2,补的数量至少为1\n";
// 							return false;
// 						}
						if($ei->bu == '0'){
							$error = '补库存设置补的数量至少为1';
							echo "补库存设置补的数量至少为1\n";
							return false;
						}
						if( ($ei->quantity-$ei->quantitysold)>=$ei->less){
							$error = '补货条件不满足';
							echo "补货条件不满足\n";
							return false;
						}
						return $v['Quantity']-$v['SellingStatus']['QuantitySold']+$ei->bu;
					}else{
						$error = 'Item设置已关闭自动补库存';
						echo "Item设置已关闭自动补库存\n";
						return false;
					}
				}
			}
		}
		return false;
	}
}