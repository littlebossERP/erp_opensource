<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use eagle\modules\purchase\helpers\PurchaseSugHelper;
/**
 * Test controller
 */
class PurchasesugQueueController extends Controller {
	/**
	 * @invoking					./yii cdiscount/test
	 */
	public function actionTest() {

		$createtime= date('Y-m-d\TH:i:s',strtotime("-20 days"));
		$endtime= date('Y-m-d\TH:i:s');

		exit();
	}
 
    /**
     +---------------------------------------------------------------------------------------------
     * Purchasesug 数据库处理。
     * 由cron call 起来，会对db_queue库pc_suggest_queue表中状态为P的uid作Purchasesug计算
	 * 并insert或update到user的pc_purchase_suggestion表
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii purchasesug-queue/cron-calculate-purchasesug
     *
     +---------------------------------------------------------------------------------------------
     * log		name	date		note
     * @author	lzhl	2015/7/2	初始化
     +---------------------------------------------------------------------------------------------
     **/
	public function actionCronCalculatePurchasesug() {
    	$start_time = date('Y-m-d H:i:s');

    	$comment =  "\n cron service runnning for Purchasesug at $start_time";
    	\Yii::info(['Purchasesug',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	$rtn = PurchaseSugHelper::cronCronCalculatePurchasesug();

    	if ($rtn['success'] and $rtn['message']!==""){
    		$orderCount =0;
    		if(isset($rtn['proxyResponse']['orderList']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']))
    			$orderCount = count($rtn['proxyResponse']['orderList']['GetOrderListResponse']['GetOrderListResult']['OrderList']['Order']);
    		echo "\n "."get $orderCount orders";
    	}

    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "\n cron service stops for Purchasesug at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        \Yii::info(['Purchasesug',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    }//end of function actionCronCalculatePurchasesug
        
	
}