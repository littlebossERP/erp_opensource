<?php
namespace console\controllers;

use yii;
use yii\console\Controller;
use console\helpers\SaasNeweggAutoFetchApiHelper;
use eagle\modules\order\helpers\NeweggApiHelper;

class NeweggController extends Controller
{
	/**
	 * 更新queue订单
	 * ./yii newegg/update-order-by-queue
	 */
	public function actionUpdateOrderByQueue(){
		$startRunTime = time();
		$logIDStr = "newegg_update_order-by-queue";
		$seed = rand(0, 99999);
		$cronJobId = "NGUpOrByQ" . $seed;
		SaasNeweggAutoFetchApiHelper::setCronJobId($cronJobId);
		echo "$logIDStr jobid=$cronJobId start \n";
		\Yii::info("$logIDStr jobid=$cronJobId start", "file");
		$rtn = SaasNeweggAutoFetchApiHelper::updateOrderByQueue();
		echo "$logIDStr jobid=$cronJobId end \n";
		\Yii::info("$logIDStr jobid=$cronJobId end", "file");
	}
	
	/**
	 * 拉取新订单
	 * ./yii newegg/get-new-order
	 */
    public function actionGetNewOrder(){
	    $startRunTime = time();
	    $logIDStr = "newegg_get_order_list";
    	$seed = rand(0, 99999);
	    $cronJobId = "NGGOL" . $seed;
	    SaasNeweggAutoFetchApiHelper::setCronJobId($cronJobId);
	    echo "$logIDStr jobid=$cronJobId start \n";
	    \Yii::info("$logIDStr jobid=$cronJobId start", "file");
	    $rtn = SaasNeweggAutoFetchApiHelper::getOrderList(1);
	    echo "$logIDStr jobid=$cronJobId end \n";
	    \Yii::info("$logIDStr jobid=$cronJobId end", "file");
    }
    
    /**
     * 拉取旧订单(Unshipped)
     * ./yii newegg/get-order-old-unshipped
     */
    public function actionGetOrderOldUnshipped(){
    	$startRunTime = time();
    	$logIDStr = "newegg_get_order_list";
    	$seed = rand(0, 99999);
	    $cronJobId = "NGGOL" . $seed .'UnS';
	    SaasNeweggAutoFetchApiHelper::setCronJobId($cronJobId);
	    echo "$logIDStr jobid=$cronJobId start \n";
	    \Yii::info("$logIDStr jobid=$cronJobId start", "file");
	    $rtn = SaasNeweggAutoFetchApiHelper::getOrderList(2);
	    echo "$logIDStr jobid=$cronJobId end \n";
	    \Yii::info("$logIDStr jobid=$cronJobId end", "file");
    }
    
    /**
     * 拉取旧订单(Partially Shipped)
     * ./yii newegg/get-order-old-partially-shipped
     */
    public function actionGetOrderOldPartiallyShipped(){
    	$startRunTime = time();
	    $logIDStr = "newegg_get_order_list";
	    $seed = rand(0, 99999);
	    $cronJobId = "NGGOL" . $seed .'ParS';
	    SaasNeweggAutoFetchApiHelper::setCronJobId($cronJobId);
	    echo "$logIDStr jobid=$cronJobId start \n";
	    \Yii::info("$logIDStr jobid=$cronJobId start", "file");
	    $rtn = SaasNeweggAutoFetchApiHelper::getOrderList(3);
	    echo "$logIDStr jobid=$cronJobId end \n";
	    \Yii::info("$logIDStr jobid=$cronJobId end", "file");
    }
    
    /**
     * 拉取旧订单(Shipped)
     * ./yii newegg/get-order-old-shipped
     */
    public function actionGetOrderOldShipped(){
    	$startRunTime = time();
	    $logIDStr = "newegg_get_order_list";
	    $seed = rand(0, 99999);
	    $cronJobId = "NGGOL" . $seed .'S';
	    SaasNeweggAutoFetchApiHelper::setCronJobId($cronJobId);
	    echo "$logIDStr jobid=$cronJobId start \n";
	    \Yii::info("$logIDStr jobid=$cronJobId start", "file");
	    $rtn = SaasNeweggAutoFetchApiHelper::getOrderList(4);
	    echo "$logIDStr jobid=$cronJobId end \n";
	    \Yii::info("$logIDStr jobid=$cronJobId end", "file");
    }
    
    /**
     * 获取错误信息并发邮件提醒
     * ./yii newegg/get-sys-error-list
     */
    public function actionGetSysErrorList(){
    	$ret = NeweggApiHelper::getSysErrorList(time() - 7200);
    }
}

?>