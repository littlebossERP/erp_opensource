<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use \eagle\modules\listing\helpers\EnsogoHelper;
use \eagle\modules\listing\helpers\EnsogoProxyHelper;
use \eagle\modules\order\helpers\EnsogoOrderHelper;
use \eagle\modules\util\helpers\ImageHelper;
use \eagle\modules\util\helpers\TimeUtil;
use \eagle\modules\listing\helpers\SaasEnsogoFanbenSyncHelper;
use \eagle\modules\tracking\helpers\TrackingAgentHelper;
use \eagle\modules\listing\service\Queue;
use \eagle\modules\listing\service\Log;
use \eagle\modules\listing\service\ensogo\Account as EnsogoAccount;
use \eagle\models\SaasEnsogoUser;
use \eagle\models\EnsogoProduct;
use \eagle\models\EnsogoVariance;
use \eagle\models\EnsogoVarianceCountries;
use \eagle\modules\util\helpers\ConfigHelper;
use \eagle\modules\listing\models\WishFanben;
use \eagle\models\EnsogoWishTagQueue;
use console\helpers\EnsogoQueueHelper;
use \crm\models\EnsogoStatisticsProduct;
use eagle\modules\listing\service\ensogo\Product;


/**
 * Test controller
 */
class EnsogoController extends Controller {
	
    const POLLING_TIMEOUT = 3600;   // 工作时间
    const POLLING_INTERVAL = 3;     // 冷却时间
    const PRODUCT_SYNC_INTERVAL = 1200;     // 自动同步频率

    static $version;

	public function actionTest() {
		echo "sdfsdf";
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 手动同步ensogo订单。
	 * 新的ensogo订单队列， 用于加快ensogo 订单同步速度
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii ensogo/cron-manual-retrieve-ensogo-order-eagle2
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/7/2				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCronManualRetrieveEnsogoOrderEagle2(){
		$start_time = date('Y-m-d H:i:s');
		$comment = "cron service runnning for CronManualRetrieveEnsogoOrderEagle2 at $start_time";
		echo $comment;
	
    	$rtn = EnsogoOrderHelper::cronManualRetrieveEnsogoOrder();
    	 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for  CronManualRetrieveEnsogoOrderEagle2 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"file");
	}//end of actionCronSyncEnsogoFanbenDataEagle2
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Ensogo API Request 处理器。支持多进程一起工作
	 * 读取一条request，然后执行，然后继续读取下一条。一直到没有读取到任何pending 的request
	 * 如果执行了超过30分钟，自动退出。Job Monitor会自动重新generate一个进程
	 * 此队列处理器可以处理ensogo 的范本刊登的修改，创建，以及order的获取和修改。
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					./yii ensogo/do-api-queue-request-product-eagle2
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
    public function actionDoApiQueueRequestProductEagle2() {
    	$start_time = date('Y-m-d H:i:s');
        $comment = "cron service runnning for Ensogo DoQueueReqeustProduct at $start_time";
        echo $comment;
        \Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
        do{
        	$rtn = EnsogoHelper::cronQueueHandleFanben();
        	 
        	//如果没有需要handle的request了，退出
        	if ($rtn['success'] and $rtn['message']=="n/a"){
				sleep(10);
				//echo "cron service Do17TrackQuery,no reqeust pending, sleep for 4 sec";
        	}
        	
        	$auto_exit_time = 25 + rand(1,10); // 25 - 35 minutes to leave
        	$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));

        }while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
        
        //write the memery used into it as well.
        $memUsed = floor (memory_get_usage() / 1024 / 1024);
        $comment =  "cron service stops for Ensogo DoQueueReqeustProduct at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
     
    }//end of function actionDo17TrackQuery
  
   
    /**
     +---------------------------------------------------------------------------------------------
     * Ensogo API Request 处理器。支持多进程一起工作
     * 读取一条request，然后执行，然后继续读取下一条。一直到没有读取到任何pending 的request
     * 如果执行了超过30分钟，自动退出。Job Monitor会自动重新generate一个进程
     * 此队列处理器可以处理ensogo 的范本刊登的修改，创建，以及order的获取和修改。
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return						array('success'=true,'message'='')
     *
     * @invoking					./yii ensogo/do-api-queue-request-order-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/2/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionDoApiQueueRequestOrderEagle2() {
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Ensogo DoQueueReqeustOrder at $start_time";
    	echo $comment;
    	\Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    	do{
    	$rtn = EnsogoOrderHelper::cronQueueHandlerExecuteEnsogoOrderOp();
    
    	//如果没有需要handle的request了，退出
    	if ($rtn['success'] and $rtn['message']=="n/a"){
    	sleep(10);
    	//echo "cron service Do17TrackQuery,no reqeust pending, sleep for 4 sec";
    	}
    	 
    	$auto_exit_time = 25 + rand(1,10); // 25 - 35 minutes to leave
    	$half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
    
    	}while (($start_time > $half_hour_ago)); //如果运行了超过30分钟，退出
    
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Ensogo DoQueueReqeustOrder at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        \Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }//end of function actionDo17TrackQuery
    
    /**
     +---------------------------------------------------------------------------------------------
     * Ensogo 平台订单获取。
     * 由cron call 起来，会对所有绑定的ensogo账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return						 
     *
     * @invoking					./yii ensogo/fetch-changed-order-list-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/2/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchChangedOrderListEagle2() {
    	/*OBSOLETE this by yzq @2015-7-10, because the fetch unfufilled job will also fetch the changed order as well.
    	$start_time = date('Y-m-d H:i:s');
    	echo "cron service runnning for Ensogo cronAutoFetchChangedOrderList at $start_time";
    	
    	$rtn = EnsogoOrderHelper::cronAutoFetchChangedOrderList();
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Ensogo cronAutoFetchChangedOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    			echo $comment;
    	\Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	*/
    }//end of function actionDo17TrackQuery

    /**
     +---------------------------------------------------------------------------------------------
     * Ensogo 平台新绑定的帐号订单获取。
     * 由cron call 起来，会对所有绑定的ensogo账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii ensogo/fetch-new-account-orders-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/7/29				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchNewAccountOrdersEagle2() {
    	 
    	 $start_time = date('Y-m-d H:i:s');
    	echo "cron service runnning for Ensogo FetchNewAccountOrders at $start_time";
    	 
    	$rtn = EnsogoOrderHelper::cronAutoFetchNewAccountOrderList();
    	 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Ensogo FetchNewAccountOrders at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	 
    }//end of function actionDo17TrackQuery
    /**
     +---------------------------------------------------------------------------------------------
     * Ensogo 平台订单获取。
     * 由cron call 起来，会对所有绑定的ensogo账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii ensogo/fetch-recent-changed-orders-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/2/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentChangedOrdersEagle2() {
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Ensogo cronAutoFetchUnFulfilledOrderList at $start_time";
    	echo $comment;
    	\Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    	
    	$rtn = EnsogoOrderHelper::cronAutoFetchRecentChangedOrder();
    	 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Ensogo cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    			$comment .= " - RAM Used: ".$memUsed."M";
    			echo $comment;
        \Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }//end of function actionDo17TrackQuery
	
	/**
     +---------------------------------------------------------------------------------------------
     * 同步 ensogo 平台商品。
     * 由cron call 起来，会对所有绑定的ensogo账号进行轮询，获取商品信息
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii ensogo/cron-sync-ensogo-fanben-data-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2015/7/2				初始化
     +---------------------------------------------------------------------------------------------
     **/
	public function actionCronSyncEnsogoFanbenDataEagle2(){
        $start_time = date('Y-m-d H:i:s');
		$comment = "cron service runnning for Ensogo SyncEnsogoFanbenData at $start_time";
		echo $comment;
		self::syncAllProd('ensogo');
	}//end of actionCronSyncEnsogoFanbenDataEagle2
	
	
	/**
     +---------------------------------------------------------------------------------------------
     * 同步平台商品。
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param		$platform		平台 (ensogo , ebay等)
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					self::syncAllProd('ensogo')
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2015/7/2				初始化
     +---------------------------------------------------------------------------------------------
     **/
	private static function syncAllProd($platform){
 		$start_time = date('Y-m-d H:i:s');
 		$str = "background service runnning for sync product detail from $platform at $start_time";
        \Yii::info($str,"file");
		
 		$seed = rand(0,99999);
 		global $CACHE;
 		$CACHE['JOBID'] = "MS".$seed."N";
 		$JOBID=$CACHE['JOBID'];
 		
 		$current_time=explode(" ",microtime()); 
        $start1_time=round($current_time[0]*1000+$current_time[1]*1000);
 		$rtn = SaasEnsogoFanbenSyncHelper::queueHandlerProcessing1('',$platform);
		\Yii::info(var_export($rtn,true),"file");

 		//如果没有需要handle的request了，退出
 		if ($rtn['success'] and $rtn['message']=="n/a"){
			sleep(4);
			$str = "cron service Do17TrackQuery,no reqeust pending, sleep for 4 sec";
			\Yii::info($str,"file");
 		}
 				 
 		$auto_exit_time = 30 + rand(1,10); // 25 - 35 minutes to leave
        $half_hour_ago = date('Y-m-d H:i:s',strtotime("-".$auto_exit_time." minutes"));
 		        	 
 		$current_time=explode(" ",microtime()); 
        $start2_time=round($current_time[0]*1000+$current_time[1]*1000);
 		
 		//submit 使用的external call 统计数
 		TrackingAgentHelper::extCallSum('',0,true);
		
		//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Ensogo ".(__FUNCTION__)." at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
        \Yii::info($comment,"file");
 	}



    function queueVersion(&$version,$key){
        $currentVersion = ConfigHelper::getGlobalConfig ( $key, 'NO_CACHE' );
        if(!$currentVersion){
            $currentVersion = 0;
        }
        if(!$version){
            $version = $currentVersion;
        }
        if($version!=$currentVersion){
            Log::info('版本不一致，退出脚本');
            exit('版本不一致，退出脚本'.$version);
            return false;
        }
        return true;
    }

    /**
     * ensogo商品同步进程  
     * onePerMin
     * @author hqf 2016-1-4
     * @return [type] [description]
     */
    function actionSyncEnsogoProductQueue(){
        define('TEST_MODE','PAGE');
        // TimeUtil::beginTimestampMark(__FUNCTION__);
        $platform = 'ensogo';             // 平台
        // 记录开始工作时间
        $jobID = $platform.':'.date('YmdHi');
        $start = time();
        Log::info(PHP_EOL.'asyncProduct job '.$jobID.' start work on:'.date('Y-m-d H:i:s'));
        Log::info(PHP_EOL.'memory use '.memory_get_usage());
        while(time() - $start < self::POLLING_TIMEOUT){
            // TimeUtil::markTimestamp("t1",__FUNCTION__);
            // 读取redis中的队列信息
            $queue = new Queue($platform);
            $result = $queue->executeProduct(function($log){
                // 检查版本
                $this->queueVersion(self::$version,__FUNCTION__);
            });
            if($result['code']==404){
                Log::info(PHP_EOL.'队列已全部同步完成，'.self::POLLING_INTERVAL.'s 后重新开始');
                sleep(self::POLLING_INTERVAL);
            }
            unset($queue);
            // Log::info(PHP_EOL.'memory use '.memory_get_usage());
            // TimeUtil::markTimestamp("t2",__FUNCTION__);
            // echo PHP_EOL.TimeUtil::getTimestampMarkInfo(__FUNCTION__);
        }
        Log::info(PHP_EOL.'asyncProduct job '.$jobID.' complete on:'.date('Y-m-d H:i:s').PHP_EOL);
        Log::info(PHP_EOL.'memory use '.memory_get_usage());
        return Controller::EXIT_CODE_NORMAL; // 退出
    }

    /**
     * 自动同步商品
     * onPerMin
     * @author  hqf 2016-1-5
     * @return [type] [description]
     */
    function actionAutoSyncProduct(){
        define('TEST_MODE','PAGE');
        $platform = 'ensogo';
        // 获取所有ensogo平台账号
        $users = EnsogoAccount::getAllAccounts();
        $queue = new Queue($platform);
        foreach($users as $account){
            // 判断last_time
            $last = strtotime($account->last_product_success_retrieve_time);
            $now = time();
            if($now - $last > self::PRODUCT_SYNC_INTERVAL){
                // 加入队列
                $result = $queue->addProductQueueBySiteId($account->site_id);
                Log::info('新增同步队列'.$result['id']);
            }
        }
        return Controller::EXIT_CODE_NORMAL; // 退出
    }

#####################################ensogo token#######################################################################

    public function actionRefreshAccessToken(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "ALIGOL".$seed."RefreshEnsogoAccessToken";
        EnsogoQueueHelper::setCronJobId($cronJobId);
        echo "ensogo_refresh_access_token jobid=$cronJobId start \n";
        \Yii::info("ensogo_refresh_access_token jobid=$cronJobId start",'file');
        do{
            $rtn = EnsogoQueueHelper::refreshAccessToekn();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "ensogo_refresh_access_token jobid=$cronJobId sleep5M \n";
                \Yii::info("ensogo_refresh_access_token jobid=$cronJobId sleep5M",'file');
                sleep(300);
            }
        }while (time() < $startRunTime+3600);
        echo "ensogo_refresh_access_token jobid=$cronJobId end \n";
        \Yii::info("ensogo_refresh_access_token jobid=$cronJobId end",'file');
    }

#####################################ensogo token#######################################################################

    public function actionSyncRequestStatuses(){
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Ensogo SyncRequestStatuses at $start_time";
    	echo $comment;
    	\Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    	 
    	$rtn = EnsogoOrderHelper::cronSyncRequestStatuses();
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Ensogo SyncRequestStatuses at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }

    ####################同步WISH标签##########################

    function actionStatisticsWishTagList(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "ALIGOL".$seed."StatisticsWishTagList";
        EnsogoQueueHelper::setCronJobId($cronJobId);
        echo "ensogo_refresh_wish_tag jobid=$cronJobId start \n";
        \Yii::info("ensogo_refresh_wish_tag jobid=$cronJobId start",'file');
        do{
            $rtn = EnsogoQueueHelper::refreshWishTag();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                $startRunTime = 0;
            }
        }while (time() < $startRunTime+3600);
        echo "ensogo_refresh_wish_tag jobid=$cronJobId end \n";
        \Yii::info("ensogo_refresh_wish_tag jobid=$cronJobId end",'file');
    }


    public function actionSysncEnsogoStatisticProductData(){
        $sql = "SELECT uid,count(uid) as total From saas_ensogo_user group by uid asc";
        $user_info = \Yii::$app->db->createCommand($sql)->query();
        while(($row = $user_info->read()) !== false){
            for($i=0; $i< $row['total'];$i++){
                $sql = "SELECT site_id From saas_ensogo_user WHERE uid ='".$row['uid']."' limit $i,1";
                $site_id = \Yii::$app->db->createCommand($sql)->query()->read();
                $Ensogo_statistics_product = EnsogoStatisticsProduct::find()->where(['puid'=>$row['uid'],'site_id'=>$site_id['site_id']])->one();
                $is_new = false;
                if(!$Ensogo_statistics_product){
                    $Ensogo_statistics_product = new EnsogoStatisticsProduct();
                    $Ensogo_statistics_product->puid = $row['uid'];
                    $Ensogo_statistics_product->site_id = $site_id['site_id'];
                    $Ensogo_statistics_product->create_time = time();
                    $is_new = true;
                }
                $ret=true;
                echo "uid:".$row['uid'].'</br>';
                echo "site_id:".$site_id['site_id'].'</br>';
                if($ret == true){
                    // $sql = "SELECT count(v.sku) as variance_total FROM ensogo_product p,ensogo_variance v WHERE site_id = '".$site_id['site_id']."' AND  p.parent_sku = v.parent_sku AND p.type= 1 group by v.parent_sku";
                    // $sql = "select count(a.parent_sku) from ensogo_variance as a INNER JOIN ensogo_product as b on(a.product_id =b.id and b.type=1 and site_id ='".$site_id['site_id']."') group by a.parent_sku";
                    $sql = "SELECT count(v.sku) as total FROM ensogo_variance v INNER JOIN ensogo_product p ON (p.parent_sku = v.parent_sku AND p.type = 1 AND p.site_id ='".$site_id['site_id']."')";
                    $variance_info = \Yii::$app->subdb->createCommand($sql)->query()->read();
                    $product_total = 0;
                    $variance_total = 0;
                    $variance_total = $variance_info['total']; 
                    $sql = "SELECT count(parent_sku) as total FROM ensogo_product where type=1 AND site_id = '".$site_id['site_id']."'";
                    $product_info = \Yii::$app->subdb->createCommand($sql)->query()->read();
                    $product_total =$product_info['total'];
                    $Ensogo_statistics_product->product_total = $product_total ;
                    $Ensogo_statistics_product->variance_total = $variance_total;
                }else{
                      \Yii::info("user数据库切换失败",'file');
                      if($is_new){
                          $Ensogo_statistics_product->variance_total = 0;
                          $Ensogo_statistics_product->product_total = 0;
                      }
                }
                echo "product_total:".$product_total.'</br>';
                echo "variance_total:".$variance_total.'</br>';
                echo "============================</br>";
                $Ensogo_statistics_product->update_time = time();
                $Ensogo_statistics_product->save();
            }
        }
    }


    /*
     * 这个是遍历所有user，看看他们在 specified period 里面，所有Ensogo订单总数以及销售总金额
     * 
     *  Author: yzq 
     *  date: 2016-3-11
     * */
    public function actionCalcuateSales(){
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Ensogo CalcuateSales at $start_time";
    	echo $comment;
    	\Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    	$endDate = date('Y-m-d',strtotime('-30 days'));
    	$fromDate = date('Y-m-d');
    	$rtn = EnsogoOrderHelper::calcuateSales( $fromDate, $endDate); //endDate 以及 fromDate这两天的都会算入结果里面
    	 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Ensogo CalcuateSales at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	echo "result \n".print_r($rtn,true);
    	\Yii::info(['Ensogo',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }

    /**
     * 上多站点时执行的脚本，更新所有用户当前商品的所有信息
     * @author hqf
     * 
     * @return [type] [description]
     */
    public function actionSetAllVariantsCountries(){
        $users = SaasEnsogoUser::find()->orderBy('uid ASC');
        foreach($users->each() as $user){
            $this->setAllVariantsCountriesByUser($user);
        }
    }

    protected function setAllVariantsCountriesByUser($user){
        function _info($str){
            echo $str.PHP_EOL;
            error_log($str,3,'/tmp/ensogo_set_all_variants_countries/'.$user->uid.'.log');
        }
         
        if(true){
            _info('change db success: '.$user->uid);
            // 获取token
            EnsogoProxyHelper::$token = $user->token;
            // 先获取商品信息
            $products = EnsogoProduct::find()->where([
                'site_id'=>$user->site_id
            ])->andWhere("ensogo_product_id <> ''");
            // 获取所有变体
            foreach($products->each() as $product){
                $variants = EnsogoVariance::find()
                    ->where(['product_id'=>$product->id])
                    ->andWhere("variance_product_id <> ''");
                // echo $variants->createCommand()->getRawSql().PHP_EOL;
                foreach($variants->each() as $variant){
                    // 抓取平台信息
                    try{
                        $info = EnsogoProxyHelper::call('getProductVariantsById',[
                            'product_variants_id'=>$variant->variance_product_id,
                        ]);
                        if($info['code']){
                            throw new \Exception($info, 1);
                            // 记录错误信息
                            _info(var_export($info['message']));
                        }else{
                            // 保存多站点信息
                            $data = $info['data'];
                            try{
                                $this->saveVariantCountriesInfo($variant,$data);
                                _info($variant->sku.' success');
                            }catch(\Exception $e){
                                _info($e->getMessage());
                            }
                        }
                    }catch(\Exception $e){
                        _info($variant->sku.': '.$e->getMessage());
                    }
                } 
            }
            return true;
        }
    }

    protected function saveVariantCountriesInfo(EnsogoVariance $variant,$data){
        $countries = explode('|',$data['countries']);
        $prices = explode('|',$data['prices']);
        $shippings = explode('|',$data['shippings']);
        $msrps = explode('|',$data['msrps']);
        for($i = 0;$i< count($countries); $i++){
            $variantCountry = new EnsogoVarianceCountries();
            $variantCountry->variance_id = $variant->id;
            $variantCountry->product_id = $variant->product_id;
            $variantCountry->country_code = $countries[$i];
            $variantCountry->price = $prices[$i];
            $variantCountry->shipping = $shippings[$i];
            $variantCountry->msrp = $msrps[$i];
            $variantCountry->status = 2;
            $variantCountry->create_time = date('Y-m-d H:i:s');
            $variantCountry->update_time = date('Y-m-d H:i:s');
            if(!$variantCountry->save()){
                throw new \Exception(var_export($variantCountry->getErrors()), 1);
            }
        }
        return true;
    }

    function actionTest2(){
        $user = SaasEnsogoUser::find()->where(['uid'=>297])->one();
        return $this->setAllVariantsCountriesByUser($user);
    }
	
    public function actionTestEnsogoSitesSync(){
        $user = \eagle\models\SaasEnsogoUser::find()->where(['uid'=> 1,'site_id'=>18])->one();
        $this->UpdateEnsogoSiteSync($user->uid,$user->site_id,$user->token);
    }

    public function actionEnsogoSitesSync1(){
        $user = \eagle\models\SaasEnsogoUser::find()->where(['and','uid <= 3222','uid >= 1']);
        foreach($user->each() as $user){
            $this->UpdateEnsogoSiteSync($user->uid,$user->site_id,$user->token);
        }
    }

    public function actionEnsogoSitesSync2(){
        $user = \eagle\models\SaasEnsogoUser::find()->where(['and','uid >= 3236',' uid <= 3883']);
        foreach($user->each() as $user){
            $this->UpdateEnsogoSiteSync($user->uid,$user->site_id,$user->token);
        }
    }

    public function actionEnsogoSitesSync3(){
        $user = \eagle\models\SaasEnsogoUser::find()->where(['and','uid >= 3889','uid <= 4312']);
        foreach($user->each() as $user){
            $this->UpdateEnsogoSiteSync($user->uid,$user->site_id,$user->token);
        }
    }

    public function actionEnsogoSitesSync4(){
        $user = \eagle\models\SaasEnsogoUser::find()->where(['uid >= 4317']);
        foreach($user->each() as $user){
            $this->UpdateEnsogoSiteSync($user->uid,$user->site_id,$user->token);
        }
    }

    public function actionEnsogoTest(){
        $user = \eagle\models\SaasEnsogoUser::find()->where(['uid'=>4364]);
        foreach($user->each() as $user){
            $this->UpdateEnsogoSiteSync($user->uid,$user->site_id,'abcd');
        }
    }

    private function UpdateEnsogoSiteSync($uid,$site_id,$token){
        if(true){
            // $token = \eagle\models\SaasEnsogoUser::findOne($site_id)->token;
            EnsogoProxyHelper::setToken($token);
            echo PHP_EOL.'change db success.now database is user_'.$uid.' site_id:'.$site_id;
            $Products = \eagle\modules\listing\models\EnsogoProduct::find()->where('ensogo_product_id <> ""');
            foreach($Products->each() as $product){
                echo PHP_EOL.$product->parent_sku.' === '.$product->ensogo_product_id;
                try{
                $result = EnsogoProxyHelper::call('getProductById',[
                    'product_id' => $product->ensogo_product_id,

                        'site_id' => $site_id
                        // 'access_token'=> $token
                ]);
                }catch(\Exception $e){
                    var_dump($e->getCode());
                    var_dump($e->getMessage().' site_id:'.$site_id.' token: '.$token);
                }
                if(isset($result['code'],$result['data']) && !$result['code']){
                    $ensogo_variance_countires = [];
                    try{
                        if(!isset($result['data']['variants'])){
                            throw new \Exception('product_id:'.$product->ensogo_product_id.' \'s variants is not exists');
                        }
                        foreach($result['data']['variants'] as $key => $variant){
                            $sql = "SELECT id FROM ensogo_variance WHERE variance_product_id LIKE '{$variant['id']}'";
                            $variance_info =  \Yii::$app->subdb->createCommand($sql)->query()->read();
                            $ensogo_variance_countries[$key]['variance_id'] = $variance_info['id'];
                            $ensogo_variance_countries[$key]['product_id'] = $product->id;
                            $ensogo_variance_countries[$key]['create_time'] = str_replace(['T','.000Z',],[" ",""],$variant['created_at']);
                            $ensogo_variance_countries[$key]['update_time'] = str_replace(['T','.000Z',],[" ",""],$variant['updated_at']);
                            $ensogo_variance_countries[$key]['countries'] = explode('|',$variant['countries']);
                            $ensogo_variance_countries[$key]['prices'] = explode('|',$variant['prices']);
                            $ensogo_variance_countries[$key]['shippings'] = explode('|',$variant['shippings']);
                            $ensogo_variance_countries[$key]['msrps'] = explode('|',$variant['msrps']);
                            $message = 'product_id:'.$product->id.'\n varinace_id:'.$variance_info['id'].'\n';
                            echo PHP_EOL.'product_id:'.$product->id;
                            echo PHP_EOL.'variance_id:'.$variance_info['id'];
                            $sql = "DELETE FROM `ensogo_variance_countries` WHERE `variance_id`= {$ensogo_variance_countries[$key]['variance_id']} AND `product_id`= {$ensogo_variance_countries[$key]['product_id']}";
                            $result = \Yii::$app->subdb->createCommand($sql)->execute();
                            // if(!$result){
                            //     throw new \Exception('variance_id:'.$ensogo_variance_countries[$key]['variance_id'].' sites delete has failed');
                            // }                            
                            for($i=0,$len = count($ensogo_variance_countries[$key]['countries']);$i < $len;$i++){
                                $site = \Yii::$app->subdb->createCommand()->insert('ensogo_variance_countries',[
                                    'variance_id' => $ensogo_variance_countries[$key]['variance_id'],
                                    'product_id' => $ensogo_variance_countries[$key]['product_id'],
                                    'country_code' => $ensogo_variance_countries[$key]['countries'][$i],
                                    'price' => isset($ensogo_variance_countries[$key]['prices'][$i])?$ensogo_variance_countries[$key]['prices'][$i]:0,
                                    'shipping' => isset($ensogo_variance_countries[$key]['shippings'][$i])?$ensogo_variance_countries[$key]['shippings'][$i]:'0',
                                    'msrp' => (isset($ensogo_variance_countries[$key]['msrps'][$i]) && $ensogo_variance_countries[$key]['msrps'][$i]) ?$ensogo_variance_countries[$key]['msrps'][$i]:0,
                                    'create_time'=> $ensogo_variance_countries[$key]['create_time'],
                                    'update_time'=> $ensogo_variance_countries[$key]['update_time']
                                ])->execute();
                            }
                            echo PHP_EOL.' variant id is '.$variance_info['id'].' sync sites has completed!';
                            $message .= ' variant id is '.$variance_info['id'].' sync sites has completed!\n';
                            error_log($message,3,'/tmp/update_ensogo_site_sync.txt');

                        }
                    } catch(\Exception $e){
                        echo PHP_EOL.$e->getMessage();
                        echo PHP_EOL.$e->getFile();
                        echo PHP_EOL.$e->getLine();
                        $message = $e->getMessage().'\n';
                        $message .= $e->getFile().'\n';
                        $message .= $e->getLine().'\n';
						$message .= date('Y-m-d H:i:s',time());
                        error_log($message,3,'/tmp/update_ensogo_site_sync.txt');
                    }
                }
            }
        }else{
            echo PHP_EOL. 'change db fail'.$uid;
        }
    }

    /*
    *替换ensogo平台的产品缩略图
    * @author dcf 2016-04-20
    * @return void
    */
    public function actionTestUpdateImage(){
        $user = \eagle\models\SaasEnsogoUser::find()->where(['uid'=> 1,'site_id'=>18])->one();
        $this->BackToOriginal($user->uid,$user->site_id);
    }


    public function actionUpdateImageProcess1(){
       $user = \eagle\models\SaasEnsogoUser::find()->where(['and','uid <= 3222','uid >= 1']);
        foreach($user->each() as $user){
            $this->BackToOriginal($user->uid,$user->site_id);
        } 
    }

    public function actionUpdateImageProcess2(){
       $user = \eagle\models\SaasEnsogoUser::find()->where(['and','uid >= 3236',' uid <= 3883']);
        foreach($user->each() as $user){
            $this->BackToOriginal($user->uid,$user->site_id);
        } 
    }

    public function actionUpdateImageProcess3(){
       $user = \eagle\models\SaasEnsogoUser::find()->where(['and','uid >= 3889','uid <= 4312']);
        foreach($user->each() as $user){
            $this->BackToOriginal($user->uid,$user->site_id);
        } 
    }

    public function actionUpdateImageProcess4(){
       $user = \eagle\models\SaasEnsogoUser::find()->where(['uid >= 4317']);
        foreach($user->each() as $user){
            $this->BackToOriginal($user->uid,$user->site_id);
        } 
    }


    private function BackToOriginal($uid,$site_id){
        if(true){
            echo PHP_EOL.'change db success.now database is user_'.$uid;

            $Products = \eagle\modules\listing\models\EnsogoProduct::find()->where(['and','ensogo_product_id <> ""',['site_id'=>$site_id]]);     
            foreach($Products->each() as $product){
                if(isset($product) && !empty($product)){
                    echo PHP_EOL.'user_'.$uid.' site_id:'.$site_id.' start sync img.';
                    $this->BackImgOriginal($product);
                }else{
                    echo PHP_EOL.'user_'.$uid.' site_id:'.$site_id.'has no products';
                }
            }
        }
    }

    public function actionTestBack($uid,$id){
         
        $product = \eagle\modules\listing\models\EnsogoProduct::findOne($id);
        return $this->BackImgOriginal($product);
    } 

    private function BackImgOriginal($product){
        $product = self::SetProductData($product);
        $result = $product->save(false);
        $site_id = $product->site_id;
        if($result){
           try{ 
                $PushProduct = new Product($site_id);
                $return = $PushProduct->push($product);
                if($return['product']['code']){
                    $message =is_array($return['product']['message'])? join(' ',$return['product']['message']) : $return['product']['message'];
                    throw new \Exception('site_id:'.$site_id.' product_id:'.$product->id.' code:'.$return['product']['code'].',error_message:'.$message);
                    echo PHP_EOL.'site_id:'.$site_id.' product_id:'.$product->id.' push failed;';
                }else{
                    echo PHP_EOL.'site_id:'.$site_id.' product_id:'.$product->id.' push succeed;';
                }
            }catch(\Exception $e){
                echo PHP_EOL.$e->getMessage();
                echo PHP_EOL.$e->getFile();
                echo PHP_EOL.$e->getLine();
                $message = $e->getMessage().'\n';
                $message .= $e->getFile().'\n';
                $message .= $e->getLine().'\n';
                $message .= date('Y-m-d H:i:s',time());
                error_log($message,3,'/tmp/update_ensogo_img_.txt');  
            }
        }else{
            echo PHP_EOL.'site_id:'.$site_id.' product_id:'.$product->id.' save failed;';
        }
    }

    private function SetProductData($product){
        for($i=1;$i<=10;$i++){
            $alias = 'extra_image_'.$i;
            if(isset($product->$alias) && !empty($product->$alias)){
                $product->$alias = self::GetOriginalImage($product->$alias);
            }
        }
        if(isset($product->main_image) && !empty($product->main_image)){
            $product->main_image  = self::GetOriginalImage($product->main_image);
        }
        return $product; 
    }

    private function GetOriginalImage($img){
        $search ="/\?imageView2\/1\/w\/210\/h\/210/";
        return preg_replace($search,'', $img);
    }


    public function GetOnePlatformUserName(){
        $sql = "SELECT user_name FROM user_base u,saas_aliexpress_user a where u.uid = a.uid";
        $AliExpressUsers = \Yii::$app->db->createCommand($sql)->query()->read();
        $sql = "SELECT user_name FROM user_base u,saas_amazon_user a where u.uid = a.uid";
        $AmazonUsers = \Yii::$app->db->createCommand($sql)->query()->read();
        $sql = "SELECT user_name FROM user_base u,saas_ebay_user e where u.uid = e.uid";
        $EbayUsers = \Yii::$app->db->createCommand($sql)->query()->read();
        var_dump($AliExpressUsers);
        var_dump($AmazonUsers);
        var_dump($EbayUsers);
    }

}