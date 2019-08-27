<?php

namespace console\controllers;

use yii;
use yii\console\Controller;
use eagle\models\UserBase;
use eagle\modules\inventory\apihelpers\InventoryApiHelper;
use eagle\modules\catalog\helpers\ProductApiHelper;
use eagle\modules\inventory\models\Warehouse;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\platform\apihelpers\PlatformAccountApi;

class InventoryController extends Controller
{
	/**
	 +----------------------------------------------------------
	 * 重新更新仓库->库存列表的待发货数
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2016/10/08				初始化
	 +----------------------------------------------------------
	 *
	 *./yii inventory/update-qty-ordered
	 **/
	public function actionUpdateQtyOrdered()
	{
	    //获取数据
	    $mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
	    foreach ($mainUsers as $puser)
	    {
	        try {
    	    	$puid = $puser['uid'];
    	    	
    	    	//已绑定的店铺账号
    	    	$stores = '';
    	    	$platformAccountInfo = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($puid);
    	    	foreach ($platformAccountInfo as $p_key=>$p_v){
    	    		if(!empty($p_v)){
    	    			foreach ($p_v as $s_key=>$s_v){
    	    				if($stores == ''){
    	    					$stores = "'".$s_key."'";
    	    				}
    	    				else{
    	    					$stores .= ",'".$s_key."'";
    	    				}
    	    			}
    	    		}
    	    	}
    	    	
    	    	 
    	    
    	    	$subdbConn=\yii::$app->subdb;
            	echo "\n p".$puid." Running ...";
            	
            	//清楚所有待发货数量
            	$command = Yii::$app->get('subdb')->createCommand("update wh_product_stock set qty_ordered=0; " );
            	$command->execute();
            
            	$sql = "select oditem.sku, sum(oditem.quantity) to_send_qty, od.default_warehouse_id from od_order_v2 od, od_order_item_v2 oditem where
    			oditem.order_id= od.order_id and order_status>=200 and
    			order_status<500 and shipping_status<>2 and (order_relation='normal' or order_relation='sm') 
    	        and `selleruserid` in (".$stores.")
    	        group by oditem.sku, od.default_warehouse_id";
            
            	$command = Yii::$app->get('subdb')->createCommand($sql);
            	$rows = $command->queryALL();
            	
            	if(!empty($rows))
            	{
            	    foreach($rows as $row)
            	    {
            	        //查询sku别名对应的root sku信息
            	        $root_sku = ProductApiHelper::getRootSKUByAlias($row['sku']);
            	        if(!empty($root_sku))
            	        {
            	        	$row['sku'] = $root_sku;
            	        }
            	        
            	        InventoryApiHelper::updateQtyOrdered($row['default_warehouse_id'], $row['sku'], $row['to_send_qty']);
            	    }
            	}
            	
            	echo "\n p".$puid." succes\n";
	        }
	        catch(\Exception $e)
	        {}
	    }
	}
	
	/**
	 +----------------------------------------------------------
	 * 当存在授权了海外仓的用户，开启显示海外仓仓库的设置
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2016/11/25				初始化
	 +----------------------------------------------------------
	 *
	 *./yii inventory/set-show-oversea
	 **/
	public function actionSetShowOversea()
	{
	    //获取数据
	    $mainUsers = UserBase::find()->where(['puid'=>0])->asArray()->all();
	    foreach ($mainUsers as $puser)
	    {
	    	try {
	    		$puid = $puser['uid'];
	    		 
	    			
	    		$subdbConn=\yii::$app->subdb;
	    		echo "\n p".$puid." Running ...";
	    		 
	    		//查询是否存在海外仓仓库，有则表示有绑定过海外仓
	    		$warehouse = Warehouse::findOne(['is_oversea' => 1, 'is_active' => 'Y']);
	    		if(!empty($warehouse))
	    		{
	    		    ConfigHelper::setConfig("is_show_overser_warehouse", '1');
	    		}
	    		 
	    		echo "\n p".$puid." succes\n";
	    	}
	    	catch(\Exception $e)
	    	{}
	    }
	}
	
	/**
	 +----------------------------------------------------------
	 * 单个客户，重新更新仓库->库存列表的待发货数
	 +----------------------------------------------------------
	 * @access public
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lrq 	2016/12/13				初始化
	 +----------------------------------------------------------
	 *
	 *./yii inventory/update-one-qty-ordered
	 **/
	public function actionUpdateOneQtyOrdered()
	{
		try {
			$puid = 4760;
			
			//已绑定的店铺账号
			$stores = '';
			$platformAccountInfo = PlatformAccountApi::getAllPlatformOrderSelleruseridLabelMap($puid);
			foreach ($platformAccountInfo as $p_key=>$p_v){
				if(!empty($p_v)){
					foreach ($p_v as $s_key=>$s_v){
						if($stores == ''){
							$stores = "'".$s_key."'";
						}
						else{
							$stores .= ",'".$s_key."'";
						}
					}
				}
			}
		 
			$subdbConn=\yii::$app->subdb;
			echo "\n p".$puid." Running ...";
			 
			//清楚所有待发货数量
			$command = Yii::$app->get('subdb')->createCommand("update wh_product_stock set qty_ordered=0; " );
			$command->execute();

			$sql = "select oditem.sku, sum(oditem.quantity) to_send_qty, od.default_warehouse_id from od_order_v2 od, od_order_item_v2 oditem where
			oditem.order_id= od.order_id and order_status>=200 and
			order_status<500 and shipping_status<>2 and (order_relation='normal' or order_relation='sm') 
            and `selleruserid` in (".$stores.")
			group by oditem.sku, od.default_warehouse_id";

			$command = Yii::$app->get('subdb')->createCommand($sql);
			$rows = $command->queryALL();
			 
			if(!empty($rows))
			{
				foreach($rows as $row)
				{
					//查询sku别名对应的root sku信息
					$root_sku = ProductApiHelper::getRootSKUByAlias($row['sku']);
					if(!empty($root_sku))
					{
						$row['sku'] = $root_sku;
					}
					 
					InventoryApiHelper::updateQtyOrdered($row['default_warehouse_id'], $row['sku'], $row['to_send_qty']);
				}
			}
			 
			echo "\n p".$puid." succes\n";
		}
		catch(\Exception $e){
		    echo $e->getMessage();
		}
	}
}
