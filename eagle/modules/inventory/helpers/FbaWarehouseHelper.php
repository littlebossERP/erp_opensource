<?php
/**
 +------------------------------------------------------------------------------
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 +------------------------------------------------------------------------------
*/

namespace eagle\modules\inventory\helpers;

use eagle\modules\util\helpers\GetControlData;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\util\helpers\OperationLogHelper;
use eagle\modules\util\helpers\SysLogHelper;
use yii\data\Pagination;
use yii;
use eagle\modules\inventory\models\WarehouseCoverNation;
use eagle\modules\inventory\models\ProductStock;

use eagle\modules\util\helpers\TranslateHelper;
use eagle\models\SysCountry;
use common\helpers\Helper_Array;
use eagle\modules\catalog\helpers\ProductHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\catalog\models\ProductBundleRelationship;
use eagle\modules\catalog\models\Product;
use eagle\modules\inventory\models\Wh3rdPartyStockage;
use yii\db\Query;

class FbaWarehouseHelper{
    
    public static function listWarehouseData($page, $pageSize, $sort, $order, $queryString){
    	$result=[];
    	$query = Wh3rdPartyStockage::find();
    	if(!empty($queryString)) {
    		foreach($queryString as $k => $v) {
    		    if($k == 'type'){
    		        if($v == '1'){
    		            $query->andWhere("usable_inventory>0");
    		        }
    		        else if($v == '3'){
    		            $query->andWhere("usable_inventory=0");
    		        }
    		    }
    		    else{
    			    $query->andWhere([$k => $v]);
    		    }
    		}
    	}
    
    	$pagination = new Pagination([
    			'page'=> $page,
    			'pageSize' => $pageSize,
    			'totalCount' => $query->count(),
    			'pageSizeLimit'=>[5,200],//每页显示条数范围
    			]);
    	$result['pagination'] = $pagination;
    	 
    	//总数量
    	$result['count'] = $query->count();
    
    	$query->limit($pagination->limit);
    	$query->offset( $pagination->offset );
    	$query->orderBy( "$sort $order");
    
    	$result['data'] = $query->asArray()->all();
    
    	return $result;
    }
    
    /**
	 +---------------------------------------------------------------------------------------------
	 * 更新fba sku和库存
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name		date			note
	 * @author		lrq		  2016/12/05		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function UpdateFbaSku($merchant_id, $marketplace_id, $data)
	{
	    $ret['status'] = '1';
	    $ret['msg'] = '';
	    
	    //清楚所有fba库存
	    Wh3rdPartyStockage::deleteAll(['warehouse_id' => '0', 'account_id' => $merchant_id, 'marketplace_id' => $marketplace_id]);
	    
		if(!empty($data) && is_array($data))
		{
			foreach ($data as $index => $val)
			{
			    if($index > 0){
    				$warhouse = Wh3rdPartyStockage::findOne(['account_id' => $merchant_id, 'marketplace_id' => $marketplace_id, 'seller_sku' => $val[0]]);
    				if(empty($warhouse)){
    					$warhouse = new Wh3rdPartyStockage();
    					$warhouse->warehouse_id = 0;
    					$warhouse->account_id = $merchant_id;
    					$warhouse->marketplace_id = $marketplace_id;
    					$warhouse->seller_sku = $val[0];
    				}
    				
    				$warhouse->platform_code = $val[2];
    				if($val[4] == 'SELLABLE'){
    				    $warhouse->usable_inventory = $val[5];
    				}
    				else if($val[4] == 'UNSELLABLE'){
    				    $warhouse->not_usable_inventory = $val[5];
    				}
    				 
    				if(!$warhouse->save()){
    				    $ret['status'] = '0';
    				    $ret['msg'] = $warhouse->getErrors();
    				}
			    }
			}
		}
		
		return $ret;
	}
    
    
    
}