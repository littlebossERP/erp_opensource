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


class NeweggOrderHelper{
	public static $amzapiVer = '2_2';
	
	//NewEgg可发货的item状态
	public static $CAN_SHIP_ORDERITEM_STATUS = array('Unshipped','Shipped');
	//NewEgg 不可发货的item状态
	public static $CANNOT_SHIP_ORDERITEM_STATUS = array('Cancelled');
	
	static public function getCurrentOperationList($code , $type="s"){
		$OpList = OrderHelper::getCurrentOperationList($code,$type);
		if (isset($OpList['givefeedback'])) {
			unset($OpList['givefeedback']);//去掉“给买家好评”
		}
		$temp = [ 'signshipped'=>'虚拟发货(标记发货)'];
		//把“虚拟发货”放到第一位
		self::array_insert($OpList,1,$temp);
		//var_dump($OpList);
		switch ($code){
			case OdOrder::STATUS_PAY:
				break;
			case OdOrder::STATUS_WAITSEND:
				break;
			case OdOrder::STATUS_SHIPPED:
				if (isset($OpList['signcomplete'])) {
					unset($OpList['signcomplete']);//去掉“已出库订单补发”
				}
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
			$OpList += ['invoiced' => '发票'];
			//$OpList += ['updateImage' => '更新图片缓存'];
// 			$OpList += ['updateShipping' => '更新平台物流服务'];
		}
		//$OpList += ['updateImage' => '更新图片缓存'];
		//var_dump($OpList);
		
		$tmp_is_show = true;
		if($code == ''){
			$tmp_is_show = \eagle\modules\order\helpers\OrderListV3Helper::getIsShowMenuAllOtherOperation();
		}
		
		if($tmp_is_show == false){
			unset($OpList['signshipped']);
			unset($OpList['checkorder']);
		}
		
		return $OpList;
	}//end getAmazonCurrentOperationList

	/**
	 * [array_insert 插入到数组指定位置]
	 * @Author   willage
	 * @DateTime 2016-07-15T19:04:36+0800
	 * @param    [type]                   &$array       [description]
	 * @param    [type]                   $position     [description]
	 * @param    [type]                   $insert_array [description]
	 * @return   [type]                                 [description]
	 */
    static public function array_insert (&$array, $position, $insert_array) {
        $first_array = array_splice ($array, 0, $position);
        $array = array_merge ($first_array, $insert_array, $array);
    }


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
    static public function getNeweggOmsNav($key_word){
    	$order_nav_list = [
    		'同步订单'=>'/order/newegg-order/order-sync-info' ,
    		'已付款'=>'/order/newegg-order/list?order_status=200&pay_order_type=pending' ,
    		'发货中'=>'/order/newegg-order/list?order_status=300' ,
    		'已完成'=>'/order/newegg-order/list?order_status=500' ,
    	];
    
    	$order_nav_active_list = [
    		'同步订单'=>'' ,
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
    
    /**
     +---------------------------------------------------------------------------------------------
     * 订单同步情况 数据
     * @return array 
     * log			name		date				note
     * @author		lzhl		2016/04/26			初始化
     +---------------------------------------------------------------------------------------------
     **/
    static public function getOrderSyncInfoDataList(){
    	$autoSyncStatusMapping = [
    		0=>'未同步过',
    		1=>'同步中',
    		2=>'同步完成',
    		3=>'同步异常',
    	];
    	
    	$userInfo = \Yii::$app->user->identity;
    	if ($userInfo['puid']==0){
    		$uid = $userInfo['uid'];
    	}else {
    		$uid = $userInfo['puid'];
    	}
    	
    	$AccountList = SaasNeweggUser::find()->where(['uid'=>$uid,'is_active'=>1])->asArray()->all();
    	$siteId_StoreMapping=[];
    	foreach ($AccountList as $account){
    		$siteId_StoreMapping[$account['site_id']] = $account['store_name'];
    	}
    	$syncList = SaasNeweggAutosync::find()->where(['uid'=>$uid,'type'=>1,'site_id'=>array_keys($siteId_StoreMapping)])->asArray()->all();
    	
    	$syncInfoList = [];
    	foreach($syncList as $aync){
    		$syncInfoList[$aync['site_id']] ['store_name'] = $siteId_StoreMapping[$aync['site_id']];
    		$syncInfoList[$aync['site_id']] ['is_active'] = ($aync['is_active']==1)?'开启':'未开启';
    		$syncInfoList[$aync['site_id']] ['last_time'] = (!empty($aync['last_finish_time']))?date("Y-m-d H:i:s" , $aync['last_finish_time']):'--';
    		$syncInfoList[$aync['site_id']] ['message'] = $aync['message'];
    		$syncInfoList[$aync['site_id']] ['status'] = (!empty($autoSyncStatusMapping[(int)$aync['status']]))? $autoSyncStatusMapping[(int)$aync['status']] : $aync['status'];
    		
    	}
    	return $syncInfoList;
    }//end of getOrderSyncInfoDataList
    
    
}//end class
?>