<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;
use yii\base\Exception;
use console\helpers\ShopeeHelper;

/**
 * Shopee 后台脚本
 * 2018-04-27
 */
class ShopeeController extends Controller{
	/**
	 +---------------------------------------------------------------------------------------------
	 *  按时间同步 （type=time）
	 *  同步Shopee新产生 或 有变更的订单，到队列，等待更新到db
	 *  ./yii shopee/get-order-list-by-time
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/27		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionGetOrderListByTime(){
		$startRunTime = time();
		do{
			$ret = ShopeeHelper::getOrderListByTime();
			//如果没有需要handle的request，sleep 10s再试
			if($ret === false){
				sleep(10);
			}
		}while(time() < $startRunTime + 3600);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 *  根据queue_shopee_getorder队列，同步订单信息到db
	 *  ./yii shopee/update-to-db
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/04/28		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionUpdateToDb(){
		$startRunTime = time();
		do{
			$ret = ShopeeHelper::updateToDb();
			//如果没有需要handle的request，sleep 10s再试
			if($ret === false){
				sleep(10);
			}
		}while(time() < $startRunTime + 3600);
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 同步Shopee 15天内未完成订单的订单列表
	 * 主要作用：新绑定账号，重新绑定，重新开启同步的情况下需要一次性同步15天所有未完成的订单，提高新账号同步订单速度
	 *  ./yii shopee/get-order-list-by-un-finish
	 +---------------------------------------------------------------------------------------------
	 * log			name	date			note
	 * @author		lrq		2018/05/08		初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	function actionGetOrderListByUnFinish()
	{
		$startRunTime = time();
		do {
			$rtn = ShopeeHelper::getOrderListByUnFinish();
			//如果没有需要handle的request了，sleep 10s后再试
			if ($rtn === false) {
				sleep(10);
			}
		} while (time() < $startRunTime + 3600);
	}
	
}


?>