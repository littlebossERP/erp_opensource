<?php
 
namespace console\controllers;
 
use \console\components\Controller;
use \eagle\models\SaasWishUser;
use \eagle\modules\listing\helpers\WishHelper;
use \eagle\modules\order\helpers\WishOrderHelper;
use \eagle\modules\util\helpers\ImageHelper;
use \eagle\modules\util\helpers\TimeUtil;
use \eagle\modules\listing\helpers\SaasWishFanbenSyncHelper;
use \eagle\modules\tracking\helpers\TrackingAgentHelper;
use \eagle\modules\listing\service\Queue;
use \eagle\modules\listing\service\Log;
use \eagle\modules\listing\service\wish\Account;
use \eagle\modules\util\helpers\ConfigHelper;
use eagle\models\WishFanben;
use eagle\models\WishFanbenVariance;
use eagle\modules\listing\service\ProxyConnectHelper;
use eagle\modules\util\helpers\UserLastActionTimeHelper;

/**
 * Test controller
 */
class WishController extends Controller {
	
    const POLLING_TIMEOUT = 3600;   // 工作时间
    const POLLING_INTERVAL = 3;     // 冷却时间
    const PRODUCT_SYNC_INTERVAL = 1200;     // 自动同步频率

    static $version;

	public function actionTest() {
		echo "sdfsdf";
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 手动同步wish订单。
	 * 新的wish订单队列， 用于加快wish 订单同步速度
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 *
	 * @invoking					./yii wish/cron-manual-retrieve-wish-order-eagle2
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/7/2				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public function actionCronManualRetrieveWishOrderEagle2(){
		$start_time = date('Y-m-d H:i:s');
		$comment = "cron service runnning for CronManualRetrieveWishOrderEagle2 at $start_time";
		echo $comment;
	
    	$rtn = WishOrderHelper::cronManualRetrieveWishOrder();
    	 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for  CronManualRetrieveWishOrderEagle2 at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"file");
	}//end of actionCronSyncWishFanbenDataEagle2
	
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * Wish API Request 处理器。支持多进程一起工作
	 * 读取一条request，然后执行，然后继续读取下一条。一直到没有读取到任何pending 的request
	 * 如果执行了超过30分钟，自动退出。Job Monitor会自动重新generate一个进程
	 * 此队列处理器可以处理wish 的范本刊登的修改，创建，以及order的获取和修改。
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param 
	 +---------------------------------------------------------------------------------------------
	 * @return						array('success'=true,'message'='')
	 *
	 * @invoking					./yii wish/do-api-queue-request-product-eagle2
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/2/9				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
    public function actionDoApiQueueRequestProductEagle2() {
    	$start_time = date('Y-m-d H:i:s');
        $comment = "cron service runnning for Wish DoQueueReqeustProduct at $start_time";
        echo $comment;
        \Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
        do{
        	$rtn = WishHelper::cronQueueHandleFanben();
        	 
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
        $comment =  "cron service stops for Wish DoQueueReqeustProduct at ".date('Y-m-d H:i:s');
        $comment .= " - RAM Used: ".$memUsed."M";
        echo $comment;
        \Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
     
    }//end of function actionDo17TrackQuery
  
   
    /**
     +---------------------------------------------------------------------------------------------
     * Wish API Request 处理器。支持多进程一起工作
     * 读取一条request，然后执行，然后继续读取下一条。一直到没有读取到任何pending 的request
     * 如果执行了超过30分钟，自动退出。Job Monitor会自动重新generate一个进程
     * 此队列处理器可以处理wish 的范本刊登的修改，创建，以及order的获取和修改。
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return						array('success'=true,'message'='')
     *
     * @invoking					./yii wish/do-api-queue-request-order-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/2/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionDoApiQueueRequestOrderEagle2() {
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Wish DoQueueReqeustOrder at $start_time";
    	echo $comment;
    	\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    	do{
    	$rtn = WishOrderHelper::cronQueueHandlerExecuteWishOrderOp();
    
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
    	$comment =  "cron service stops for Wish DoQueueReqeustOrder at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
        \Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }//end of function actionDo17TrackQuery
    
    /**
     +---------------------------------------------------------------------------------------------
     * Wish 平台订单获取。
     * 由cron call 起来，会对所有绑定的wish账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return						 
     *
     * @invoking					./yii wish/fetch-changed-order-list-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/2/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchChangedOrderListEagle2() {
    	/*OBSOLETE this by yzq @2015-7-10, because the fetch unfufilled job will also fetch the changed order as well.
    	$start_time = date('Y-m-d H:i:s');
    	echo "cron service runnning for Wish cronAutoFetchChangedOrderList at $start_time";
    	
    	$rtn = WishOrderHelper::cronAutoFetchChangedOrderList();
    	
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Wish cronAutoFetchChangedOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    			echo $comment;
    	\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	*/
    }//end of function actionDo17TrackQuery

    /**
     +---------------------------------------------------------------------------------------------
     * Wish 平台新绑定的帐号订单获取。
     * 由cron call 起来，会对所有绑定的wish账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii wish/fetch-new-account-orders-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/7/29				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchNewAccountOrdersEagle2() {
    	 
    	 $start_time = date('Y-m-d H:i:s');
    	echo "cron service runnning for Wish FetchNewAccountOrders at $start_time";
    	 
    	$rtn = WishOrderHelper::cronAutoFetchNewAccountOrderList();
    	 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Wish FetchNewAccountOrders at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"file");
    	 
    }//end of function actionDo17TrackQuery
    /**
     +---------------------------------------------------------------------------------------------
     * Wish 平台订单获取。
     * 由cron call 起来，会对所有绑定的wish账号进行轮询，获取order
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii wish/fetch-recent-changed-orders-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		yzq		2015/2/9				初始化
     +---------------------------------------------------------------------------------------------
     **/
    public function actionFetchRecentChangedOrdersEagle20() {
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Wish cronAutoFetchUnFulfilledOrderList at $start_time";
    	echo $comment;
    	//\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    	
    	$rtn = WishOrderHelper::cronAutoFetchRecentChangedOrder(0);
    	 
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Wish cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    			$comment .= " - RAM Used: ".$memUsed."M";
    			echo $comment;
        //\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }//end of function actionFetchRecentChangedOrdersEagle20
    
    public function actionFetchRecentChangedOrdersEagle21() {
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Wish cronAutoFetchUnFulfilledOrderList at $start_time";
    	echo $comment;
    	//\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    	 
    	$rtn = WishOrderHelper::cronAutoFetchRecentChangedOrder(1);
    
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Wish cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	//\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }//end of function actionFetchRecentChangedOrdersEagle21
    
    
    public function actionFetchRecentChangedOrdersEagle22() {
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Wish cronAutoFetchUnFulfilledOrderList at $start_time";
    	echo $comment;
    	//\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    
    	$rtn = WishOrderHelper::cronAutoFetchRecentChangedOrder(2);
    
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Wish cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	//\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }//end of function actionFetchRecentChangedOrdersEagle22
	
	public function actionFetchRecentChangedOrdersEagle23() {
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Wish cronAutoFetchUnFulfilledOrderList at $start_time";
    	echo $comment;
    	//\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    
    	$rtn = WishOrderHelper::cronAutoFetchRecentChangedOrder(3);
    
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Wish cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	//\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }//end of function actionFetchRecentChangedOrdersEagle23
	
	public function actionFetchRecentChangedOrdersEagle24() {
    	$start_time = date('Y-m-d H:i:s');
    	$comment = "cron service runnning for Wish cronAutoFetchUnFulfilledOrderList at $start_time";
    	echo $comment;
    	//\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    
    	$rtn = WishOrderHelper::cronAutoFetchRecentChangedOrder(4);
    
    	//write the memery used into it as well.
    	$memUsed = floor (memory_get_usage() / 1024 / 1024);
    	$comment =  "cron service stops for Wish cronAutoFetchUnFulfilledOrderList at ".date('Y-m-d H:i:s');
    	$comment .= " - RAM Used: ".$memUsed."M";
    	echo $comment;
    	//\Yii::info(['Wish',__CLASS__,__FUNCTION__,'Background',$comment],"edb\global");
    }//end of function actionFetchRecentChangedOrdersEagle24
	
	/**
     +---------------------------------------------------------------------------------------------
     * 同步 wish 平台商品。
     * 由cron call 起来，会对所有绑定的wish账号进行轮询，获取商品信息
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					./yii wish/cron-sync-wish-fanben-data-eagle2
     *
     +---------------------------------------------------------------------------------------------
     * log			name	date					note
     * @author		lkh		2015/7/2				初始化
     +---------------------------------------------------------------------------------------------
     **/
	public function actionCronSyncWishFanbenDataEagle2(){
        $start_time = date('Y-m-d H:i:s');
		$comment = "cron service runnning for Wish SyncWishFanbenData at $start_time";
		echo $comment;
		self::syncAllProd('wish');
	}//end of actionCronSyncWishFanbenDataEagle2
	
	
	/**
     +---------------------------------------------------------------------------------------------
     * 同步平台商品。
     +---------------------------------------------------------------------------------------------
     * @access static
     +---------------------------------------------------------------------------------------------
     * @param		$platform		平台 (wish , ebay等)
     +---------------------------------------------------------------------------------------------
     * @return
     *
     * @invoking					self::syncAllProd('wish')
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
 		$rtn = SaasWishFanbenSyncHelper::queueHandlerProcessing1('',$platform);
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
    	$comment =  "cron service stops for Wish ".(__FUNCTION__)." at ".date('Y-m-d H:i:s');
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
            return false;
        }
        return true;
    }

    /**
     * wish商品同步进程  
     * onePerMin
     * @author hqf 2016-1-4
     * @return [type] [description]
     */
    function actionSyncWishProductQueue(){
        define('TEST_MODE','PAGE');
        // TimeUtil::beginTimestampMark(__FUNCTION__);
        $platform = 'wish';             // 平台
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
        $platform = 'wish';
        // 获取所有wish平台账号
        $users = Account::getAllAccounts();
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

    /**
     * 重新同步所有用户
     * @return [type] [description]
     */
    function actionResetAllUserProductStamp(){
        $sql = "UPDATE saas_wish_user SET last_product_success_retrieve_time = '2014-01-01 00:00:00' ";
        $query = \Yii::$app->db->createCommand($sql);
        $query->execute();
        return Controller::EXIT_CODE_NORMAL; // 退出

    }

    // 清除解绑账号的商品信息
    public function actionClearProductsByUid(){
        $this->addThread(function(){
            // 查询所有有效的wish账号
            $sql = "SELECT 
            uid,
            group_concat(`site_id`) as site_id
            FROM `saas_wish_user` 
            group by uid";
            $query = \Yii::$app->db->createCommand($sql)->query();
            while($user = $query->read() ){
                $this->singleThread($user,function($user){
                    $this->actionClearProductsBySiteId($user['uid'],explode(',',$user['site_id']));
                });
            }
        });
        echo 'all done'.PHP_EOL;
        return Controller::EXIT_CODE_NORMAL;
    }

    private function actionClearProductsBySiteId($uid,$site_id){
        if(true){
            $products = WishFanben::find()->where([
                'NOT IN','site_id',$site_id
            ]);
            foreach($products->each(1) as $product){
                // 查询变体
                $variants = WishFanbenVariance::find()->where([
                    'fanben_id'=>$product->id
                ]);
                foreach($variants->each(1) as $variant){
                    echo 'variant:'.$variant->sku.' success'.PHP_EOL;
                    $variant->delete();
                }
                echo 'product:'.$product->parent_sku.' success'.PHP_EOL;
                $product->delete();
            }
        }
    }

    function actionSyncAllUserProductTags(){
        $uid = UserLastActionTimeHelper::getPuidArrByInterval(480);
        $users = SaasWishUser::find()->where("token <>''")->andWhere([
            'IN','uid',$uid
        ]);
        foreach($users->each() as $user){
            $this->actionSyncUserProductTags($user);
        }
    }

    function actionSyncUserProductTags($site){
        if(is_string($site) || is_integer($site)){
            $site = SaasWishUser::findOne($site);
        }
        
        if(true){
            // 获取所有的product
            $products = WishFanben::find()->where([
                'site_id'=>$site->site_id
            ])->andwhere(
                'wish_product_id IS NOT NULL'
            )->andWhere('wish_product_id <> ""');
            foreach($products->each() as $product){
                $log = "[".date('Y-m-d H:i:s')."] site_id:{$site->site_id},sku:{$product->parent_sku},result:";
                try{
                    $product->tags = $this->syncProductTags($site->token,$product);
                    $product->save();
                    $log.="success";
                }catch(\Exception $e){
                    $log.="fail:".$e->getMessage().' code:'.$e->getCode();
                }
                error_log($log.PHP_EOL,3,'/tmp/sync-wish-tags.log');           
            }
        }else{
            error_log('['.date('Y-m-d H:i:s').']changeUserDB fail:'.$site->uid.PHP_EOL,3,'/tmp/sync-wish-tags.log');  
        }
    }

    private function syncProductTags($access_token,$product){
        $retInfo = ProxyConnectHelper::call_WISH_api("getproduct",[
            'token'=>$access_token,
            'sku'=>$product->parent_sku
        ]);
        $rtn = [];
        if(!$retInfo['proxyResponse']){
            throw new \Exception($retInfo['message'], 500);
        }
        if(!$retInfo['proxyResponse']['wishReturn']){
            throw new \Exception($retInfo['proxyResponse']['message'], 400);
        }
        $tags = $retInfo['proxyResponse']['wishReturn']['data']['Product']['tags'];
        foreach($tags as $tag){
            $rtn[] = $tag['Tag']['name'];
        }
        return implode(',',$rtn);
    }

}