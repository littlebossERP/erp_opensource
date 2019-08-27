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

class OverseaWarehouseHelper{
    
    public static function listWarehouseData($page, $pageSize, $sort, $order, $queryString){
    	$result=[];
    	$query = Wh3rdPartyStockage::find();
    	if(!empty($queryString)) {
    		foreach($queryString as $k => $v) {
    			    $query->andWhere([$k => $v]);
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
    	
    	//已配对数量
    	$query->andWhere("sku is not null and sku<>''");
    	$result['Ymatching'] = $query->count();
    	$result['Nmatching'] = $result['count'] - $result['Ymatching'];
    
    	return $result;
    }
    
    public static function Get_OverseaWarehouse()
    {
    	$conn=\Yii::$app->db;
    
    	$queryTmp = new Query;
    	$queryTmp->select("a.carrier_code,a.third_party_code,a.template,b.carrier_name")
    	->from("sys_shipping_method a")
    	->leftJoin("sys_carrier b", "b.carrier_code = a.carrier_code")
    	->where('b.carrier_type=1');
    
    	$queryTmp->groupBy('a.carrier_code,a.third_party_code,a.template,b.carrier_name');
    	$queryTmp->orderBy('a.carrier_code');
    
    	//所有海外仓信息
    	$shipArr = $queryTmp->createCommand($conn)->queryAll();
    	//已开启的仓库信息
    	$warehouselist = Warehouse::find()->where(["is_active"=>'Y'])->asArray()->All();
    
    	$OrerseaWarehouseList = [];
    	foreach ($shipArr as $ship)
    	{
    		foreach ($warehouselist as $wa)
    		{
    			if($wa['carrier_code']==$ship['carrier_code'] && $wa['third_party_code']==$ship['third_party_code'])
    			{
    				$OrerseaWarehouseList[] =
    				[
    				'warehouse_id'=>$wa['warehouse_id'],
    				'carrier_name'=>$ship['carrier_name'].'-'.$ship['template'],
    				'carrier_code'=>$ship['carrier_code'],
    				'third_party_code'=>$ship['third_party_code'],
    				];
    				break;
    			}
    		}
    	}
    
    	return $OrerseaWarehouseList;
    }
    
    /**
     +---------------------------------------------------------------------------------------------
     * 获取单个SKU对应的配对海外仓SKU
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param $sku           订单SKU
     * @param $warehouse_id  海外仓仓库Id
     * @param $accountid     海外仓绑定账号Id
     +---------------------------------------------------------------------------------------------
     * @return
     +---------------------------------------------------------------------------------------------
     * log			name		date			note
     * @author		lrq		  2016/11/24		初始化
     +---------------------------------------------------------------------------------------------
     **/
    public static function Get_OverseaWarehouseSKU($sku, $warehouse_id, $accountid)
    {
        $ret['status'] = 1;
        $ret['seller_sku'] = '';
        $ret['msg'] = '';
        
        if(empty($sku))
        {
            $ret['status'] = 0;
            $ret['msg'] = 'SKU不能为空！';
        }
        
        //获取主SSKU
        $root_sku = ProductApiHelper::getRootSKUByAlias( $sku);
        if(empty($root_sku))
            $root_sku = $sku;
        
        //获取配对海外仓SKU
        $warhouse = Wh3rdPartyStockage::findOne(['warehouse_id' => $warehouse_id, 'account_id' => $accountid, 'sku' => $root_sku]);
        if(!empty($warhouse))
        {
            $ret['seller_sku'] = $warhouse->seller_sku;
        }
        else 
        {
            $ret['seller_sku'] = $sku;
        }
        
        return $ret;
    }
    
    
    
    
}