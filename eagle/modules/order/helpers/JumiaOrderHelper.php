<?php

namespace eagle\modules\order\helpers;

use \Yii;
use common\helpers\Helper_Array;
use eagle\modules\order\models\OdOrderItem;
use eagle\modules\order\models\OdOrder;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\manual_sync\models\Queue;
use eagle\modules\order\models\OdOrderShipped;


class JumiaOrderHelper{
	public static $amzapiVer = '2_2';

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
// 					if (isset($OpList['checkorder'])) {
// 						unset($OpList['checkorder']);//去掉“检测订单”
// 					}
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
			$OpList += ['updateImage' => '更新图片缓存'];
// 			$OpList += ['updateShipping' => '更新平台物流服务'];
		}
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

}//end class


?>