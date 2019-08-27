<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\base\Exception;
use console\helpers\AliexpressV2Helper;
use eagle\modules\order\helpers\AliexpressOrderV2Helper;


/**
 * Aliexpress 后台脚本
 * @author million 88028624@qq.com
 * 2015-05-21
 */
class AliexpressV2Controller extends Controller{
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  按时间同步 （type=time）
	 *  同步速卖通新产生 或 有变更的订单，到队列，等待更新到db
	 *  ./yii aliexpress-v2/get-order-list-by-time
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/05		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetOrderListByTime()
	{
		$startRunTime = time();
		do {
			$rtn = AliexpressV2Helper::getOrderListByTime();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn === false) {
				sleep(10);
			}
		} while (time() < $startRunTime + 3600);
	}

	/**
	 +---------------------------------------------------------------------------------------------
	 *  根据queue_aliexpress_getorder_v2队列，同步订单信息到db
	 *  ./yii aliexpress-v2/update-to-db
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/05		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionUpdateToDb()
	{
		$startRunTime = time();
		do {
			$rtn = AliexpressV2Helper::updateToDb();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn === false) {
				sleep(10);
			}
		} while (time() < $startRunTime + 3600);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 按时间同步（type=day120）
	 * 同步速卖通120天内未完成订单的订单列表
	 * 主要作用：新绑定账号，重新绑定，重新开启同步的情况下需要一次性同步120天所有未完成的订单，提高新账号同步订单速度
	 *  ./yii aliexpress-v2/get-order-list-by-day120
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/01/08		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	function actionGetOrderListByDay120()
	{
		$startRunTime = time();
		do {
			$rtn = AliexpressV2Helper::getOrderListByDay120();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn === false) {
				sleep(10);
			}
		} while (time() < $startRunTime + 3600);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 获取在线商品上架状态的商品
	 * 主要作用：同步上架状态的在线商品
	 *  ./yii aliexpress-v2/get-listing-on-selling
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/02/24		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	function actionGetListingOnSelling(){
		$startRunTime = time();
		$seed = rand(0, 99999);
		$cronJobId = "ALIGOL" . $seed . "ONSELLING";
		AliexpressV2Helper::setCronJobId($cronJobId);
		echo "aliexress_get_listing_onselling jobid=$cronJobId start \n";
		\Yii::info("aliexress_get_listing_onselling jobid=$cronJobId start");
		do {
			$rtn = AliexpressV2Helper::getListing('onSelling');
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn === false) {
				echo "aliexress_get_listing_onselling jobid=$cronJobId sleep10 \n";
				\Yii::info("aliexress_get_listing_onselling jobid=$cronJobId sleep10");
				sleep(10);
			}
		} while (time() < $startRunTime + 3600);
		echo "aliexress_get_listing_onselling jobid=$cronJobId end \n";
			\Yii::info("aliexress_get_listing_onselling jobid=$cronJobId end");
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  部分puid每天同步两天内的订单
	 *  ./yii aliexpress-v2/get-order-list-manual-by-uid
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/18		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetOrderListManualByUid(){
		AliexpressV2Helper::getOrderListManualByUid();
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  接收ali的推送信息
	 *  ./yii aliexpress-v2/receive-ali-order-push
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/31		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionReceiveAliOrderPush()
	{
		$startRunTime = time();
		do {
			$rtn = AliexpressV2Helper::receiveAliOrderPush();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn === false) {
				sleep(30);
			}
		} while (time() < $startRunTime + 3600);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  从queue_aliexpress_auto_order推送结果表中,处理几个状态的订单
	 *  ./yii aliexpress-v2/get-ali-auto-order
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/31		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetAliAutoOrder()
	{
		$startRunTime = time();
		do {
			$rtn = AliexpressV2Helper::getAliAutoOrder();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn === false) {
				sleep(30);
			}
			else{
				sleep(5);
			}
		} while (time() < $startRunTime + 3600);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  速卖通用户开通推送消息
	 *  ./yii aliexpress-v2/set-ali-push
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/06/01		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionSetAliPush(){
		AliexpressV2Helper::SetAliPush();
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  后端手动同步订单
	 *  ./yii aliexpress-v2/tong-bu
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/06/01		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionTongBu( $uid,$sellid='',$t=10 ){
	
		AliexpressOrderV2Helper::getOrderListManualByUidTest($uid,$sellid,$t);
	
	}

}
