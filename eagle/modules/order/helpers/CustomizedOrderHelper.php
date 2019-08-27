<?php

namespace eagle\modules\order\helpers;

use \Yii;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\manual_sync\models\Queue;
use eagle\modules\order\models\OdOrderShipped;
use eagle\models\SaasNeweggAutosync;
use eagle\models\SaasNeweggUser;


class CustomizedOrderHelper{
	
	
	static public function getCurrentOperationList($code , $type="s"){
		$OpList = OrderHelper::getCurrentOperationList($code,$type);
		//发货，修改，日志，备注，标记为已完成，检测，暂停，缺货，取消，删除，修改运输服务，面单，已出库补发，重新发货
		
		if(isset($OpList['signshipped']))
			unset($OpList['signshipped']);//去掉“标记发货”
		if(isset($OpList['givefeedback']))
			unset($OpList['givefeedback']);//去掉“给买家好评”
		
		if(isset($OpList['givefeedback']))
			unset($OpList['givefeedback']);//去掉“给买家好评”
		if(isset($OpList['delete_manual_order']))
			$OpList['delete_manual_order'] = '删除手工订单';
		
		
		switch ($code){
			case OdOrder::STATUS_PAY:
				
				break;
			case OdOrder::STATUS_WAITSEND:
				break;
			case OdOrder::STATUS_SHIPPED:
				if (isset($OpList['checkorder'])) {
					unset($OpList['checkorder']);//去掉“检测订单”
				}
				break;
		}
		if ($type =='b') {
			switch ($code) {
				case OdOrder::STATUS_PAY:
					if (isset($OpList['checkorder'])) {
						unset($OpList['checkorder']);//去掉“检测订单”
					}
					break;
				case OdOrder::STATUS_SHIPPED:
					break;
				default:
					$OpList += [ 'checkorder'=>'检测订单'];
					break;
			}

		}
		if ($type =='s'){

		}
		
		return $OpList;
	}//end getAmazonCurrentOperationList

    /**
     +---------------------------------------------------------------------------------------------
     * 自动 生成 top menu
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param	$keyword	关键字， 来控制选中的样式  '' , 100 , 200 , 300 , 400 , 500
     +---------------------------------------------------------------------------------------------
     * @return	string  	html 代码
     *
     +---------------------------------------------------------------------------------------------
     * log			name		date			note
     * @author		lzhl		2016/10/08		初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getCustomizedOmsNav($key_word){
    	$order_nav_list = [
    		'已付款'=>'/order/customized-order/list?order_status=200&pay_order_type=pending' ,
    		'发货中'=>'/order/customized-order/list?order_status=300' ,
    		'已完成'=>'/order/customized-order/list?order_status=500' ,
    	];
    
    	$order_nav_active_list = [
    		'已付款'=>'200' ,
    		'发货中'=>'300' ,
    		'已完成'=>'500' ,
    	];
    
    	$NavHtmlStr = '<ul class="main-tab">';
    
    	$mappingOrderNav = array_flip($order_nav_active_list);
    	foreach($order_nav_list as $label=>$thisUrl){
    		$NavActive='';
    		
    		if (isset($key_word)){
    			if (empty($key_word) &&  !empty($mappingOrderNav[$key_word]) &&$mappingOrderNav[$key_word] == $label && \yii::$app->controller->action->id == 'order-sync-info'){
    				$NavActive = " active ";
    			}else
    			if (!empty($key_word) &&  !empty($mappingOrderNav[$key_word]) &&$mappingOrderNav[$key_word] == $label && \yii::$app->controller->action->id != 'order-sync-info' ) {
    				$NavActive = " active ";
    			}
    		}else{
    			//$NavActive = " active ";
    		}
    		$NavHtmlStr .= '<li class="'.$NavActive.'"><a href="'.$thisUrl.'">'.TranslateHelper::t($label).'</a></li>';
    	}
    	$NavHtmlStr.='</ul>';
    	
    	return $NavHtmlStr;
    }//end of getOrderNav
    
    
    
}//end class
?>