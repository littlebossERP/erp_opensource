<?php
/**
 * @link http://www.witsion.com/
 * @copyright Copyright (c) 2014 Yii Software LLC
 * @license http://www.witsion.com/
 */
namespace eagle\modules\html_catcher\helpers;
use eagle\modules\html_catcher\models\CollectRequestQueue;
use yii\base\Exception;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\tracking\helpers\phpQuery;
use eagle\modules\html_catcher\models\AnalyseHtmlRole;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\util\helpers\ConfigHelper;
use yii;
use eagle\modules\order\helpers\PriceministerOrderInterface;
use eagle\models\SaasPriceministerUser;
use eagle\models\SaasBonanzaUser;
use eagle\modules\order\helpers\BonanzaOrderInterface;
use eagle\modules\util\helpers\AppPushDataHelper;
/**
 +---------------------------------------------------------------------------------------------
 * html 抓取使用的业务逻辑封装的helper
 *  * After getting result, 使用异步推送队列 进行callback 时间的invoking
 * $command_line = '\eagle\modules\tracking\helpers\TrackingHelper::pushToOMS( '. $puid .' , "'. $orig_data['order_id'].'","'.$commitData['status'].'","'.$commitData['last_event_date'].'")';
AppPushDataHelper::insertOneRequest("Tracker", "OMS", $puid, $command_line);
	
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		lkh		2015/8/21				初始化
 +---------------------------------------------------------------------------------------------
 **/
class HtmlCatcherHelper{
	private static $HtmlCatcherVersion = '';
	/**
	 +----------------------------------------------------------------------------------------------------------------------------
	 * 
	 +----------------------------------------------------------------------------------------------------------------------------
	 * @access static     
	 +----------------------------------------------------------------------------------------------------------------------------
	 * @param 	$puid			string			puid
	 * 			$product_id		string/array	商品编号 
	 * 			$platform		string			平台 (amazon , ebay, wish , cdiscount)
	 * 			$subsite		string			子站 (amazon 有UK , FR ....)
	 * 			$field_list		array			需要抓取的数据 (eg. ["image","sku","title"] )
	 * 			$callback		string			回调函数 
	 * 			$falg			bloon			false：非立即排队，true：立即排队
	 * 			$priority		int				优先级 1-5，1最高，5最低
	 * 			$addi_info      array           可以忽略
	 * 			$needRefresh    bool            默认 false，有可能1天内重复的product id 请求会被supressed，
	 *                                          如果是true，那么不考虑性能，会接受进行这个请求
	 +----------------------------------------------------------------------------------------------------------------------------
	 * @return			array
	 * 	boolean				success  执行结果
	 * 	string/array		message  执行失败的提示信息
	 +----------------------------------------------------------------------------------------------------------------------------
	 * @invoking		HtmlCatcherHelper::requestCatchHtml($puid , $product_id , $platform , $field_list , $subsite  , $callback)
	 +----------------------------------------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/08/21				初始化
	 +----------------------------------------------------------------------------------------------------------------------------
	 **/
	static public function requestCatchHtml($puid , $product_id , $platform , $field_list , $subsite=''   , $callback='' ,$falg=false,$priority=3,$addi_info=[],$needRefresh=false){
		global $CACHE;
		$fields = array(
				'puid'=>1,
				'product_id'=>1,
				'field_list'=>1,
				'status'=>1,
				'platform'=>1,
				'create_time'=>1,
				'update_time'=>1,
				'addi_info'=>1,
				'runtime'=>1,
				'retry_count'=>1,
				'priority'=>1,
				'role_id'=>1,
				'subsite'=>1,
				'callback_function'=>1,
				'step'=>1
		);
		
		try {
		
			$result['success'] = true;
			
			if($platform=='priceminister'){
				$product_list = [];
				if(!empty($addi_info['key_type']))
					$key_type=$addi_info['key_type'];
				if($key_type=='sku'){
					foreach ($product_id as $sku=>$itemid){
						$product_list[] = $itemid;
						$sku_list[$itemid] = $sku;	
					}
				}else{
					$product_list = $product_id;
				}
			}else{
				if (is_string($product_id)){
					$product_list = [$product_id];
				}elseif (is_array($product_id)){
					$product_list = $product_id;
				}else{
					$product_list = [];
				}
			}
		
			if (is_array($field_list)){
				$field_list = json_encode($field_list);
			}
			
		//	$model = new CollectRequestQueue();
			$datas = [];
			foreach($product_list as $prod_id ){
				$aData = [];
				//echo "<br> $prod_id check <br>";//test kh
				if ($needRefresh) //如果是指定了 需要刷新的，那么不管怎样都要插入这条记录了
					$ExistCount = 0;
				else{
					//为了优化性能，会检查2天内（队列里面本来就只保存2天内的请求），是否有这个product id请求过了，
					//如果有，则不接收这个请求了，如果没有才做。并且会写到CACHE种，避免重复的product id 重复查询
					
					if (!isset($CACHE['CollectQueueExistsRequestFor'][(string)$puid][$prod_id])){
						//检查队列是否存在
						$query = CollectRequestQueue::find();

						$query->andWhere(['product_id'=>$prod_id])
							->andWhere(['platform'=>$platform])
							->andWhere(['puid'=>$puid])
							//->andWhere(['subsite'=>$subsite])
							->andWhere(['status'=>['P','C']]);
		
						$now_str = date('Y-m-d H:i:s',time()-3600*24*2 );
						if(!$falg){
							$query->andWhere(" update_time < '$now_str' ");
						}
						
						$ExistCount = $query->one();
						//echo "\n haven't CACHE,db existCount;".count($ExistCount);
					} else{
						$ExistCount = 1;
						//echo "\n have CACHE,existCount=1;";
					}
				}
			
				if (empty($ExistCount)){
					$now_str = date('Y-m-d H:i:s',time());
						
					//没有等待执行的队列, 则可以增加
					
					$aData['product_id'] = (String)$prod_id;
					$aData['platform'] = (String)$platform;
					$aData['subsite'] =(String)$subsite;
					$aData['field_list'] =(String)$field_list;
					$aData['callback_function'] =(String)$callback;
					$aData['puid'] =(Int)$puid;
					$aData['create_time'] =$now_str;
					$aData['update_time'] =$now_str;
					$aData['status'] ="P"; // pending
					$aData['priority'] = $priority;

					if($platform=='priceminister'){
						$addi = empty($addi_info)?[]:$addi_info;
						$addi['sku'] = empty($sku_list[$prod_id])?'':$sku_list[$prod_id];
					 
						$aData['addi_info'] = json_encode($addi);
					}else
						$aData['addi_info'] =  empty($addi_info)?'':json_encode($addi_info);
						/*
					if (! $model->save()){
						//保存失败, 返回提示
						echo "\n model save errors ".print_r($model->errors) ;
						$result[$prod_id] =  ['success'=>false,'message'=>$model->errors];
					}else{
						$result[$prod_id] =  ['success'=>true,'message'=>''];
						//这次都会添加这个prod id 了，所以设置这个未1就好
						$CACHE['CollectQueueExistsRequestFor'][$prod_id] = 1;
					}*/
					$datas[] = $aData;
					$result[$prod_id] =  ['success'=>true,'message'=>''];
					//这次都会添加这个prod id 了，所以设置这个未1就好
					$CACHE['CollectQueueExistsRequestFor'][(string)$puid][$prod_id] = 1;
					
				}else{
				    if($falg && $ExistCount instanceof CollectRequestQueue && $ExistCount->priority > $priority){// dzt20190708 for 大量待查询队列时候，需要订单查图的产品修改优先级
				        $ExistCount->priority = $priority;
				        if($ExistCount->save(false)){
				            $result[$prod_id] =  ['success'=>true , 'message'=>'还存在未执行的请求, 已经修改优先级'];
				        }else{
				            $result[$prod_id] =  ['success'=>false , 'message'=>'还存在未执行的请求, 修改优先级失败'];
				        }
				    }else{
					$result[$prod_id] =  ['success'=>false , 'message'=>'还存在未执行的请求, 请不要重复发送'];
				}
				}
			}//end of foreach($product_list as $prod_id ) 每个product id 
		
			//batch insert
			$rtn = SQLHelper::groupInsertToDb('hc_collect_request_queue', $datas,'db_queue2', $fields);
			$result['groupInsert_rtn'] = $rtn;
			return $result;
		} catch (Exception $e) {
			echo "E001 ".$e->getMessage();
			return ['success'=>false , 'message'=>"E001 ".$e->getMessage()];
		}// end of catch
		
	}//end of requestCatchHtml
	
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 根据 规则来分析 指定 url 的内容
	 * +---------------------------------------------------------------------------------------------
	 *
	 * @access static
	 * +---------------------------------------------------------------------------------------------
	 * @param
	 *        	$url 			string	指定只做某个读取某个url
	 * @param
	 *        	$roleSetting 	array	规则
	 * +---------------------------------------------------------------------------------------------
	 * @return array('success'=true,'message'='') 
	 * +---------------------------------------------------------------------------------------------
	 * @invoking					HtmlCatcherHelper::analyzeHtml($url,$roleSetting);
	 * +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2015/7/1				初始化
	 * +---------------------------------------------------------------------------------------------
	 *
	 */
	static public function analyzeHtml($url , &$roleSetting){
		
		//获取 目标网站的数据
		$targetHtml = self::getHtmlData($url);
		
		if ($targetHtml['success'] ==false){
			//获取网站数据失败
			return ['success'=>false , 'type'=>'catch' , 'message'=>$targetHtml['message']];
		}

		phpQuery::newDocument($targetHtml['Response']);
		
		$result = [];
		$partly =false;
		$otherErrMsg = '';
		
		if (is_array($roleSetting)){
			
			foreach($roleSetting as $field_name => $role){
				
				foreach ($role as $selector=>$selector_atrr){
					//假如 $selector_atrr 为空就是获取text()属性就可以 
					echo "<br>$selector length=".phpQuery::pq($selector)->length();
					if (empty($selector_atrr)){
						if (phpQuery::pq($selector)->length() != 0){
							
							if (strtolower($field_name) == 'price'){
								echo phpQuery::pq('.price')->eq(1)->text();//testkh
								echo "<br>====<br>";
								echo phpQuery::pq('.price:eq(1)')->text();//testkh
							}
							
							echo "<br> $field_name  and  $selector = <br>";//testkh
							echo phpQuery::pq($selector);//testkh
							$result[$field_name][] =  trim(phpQuery::pq($selector)->text());
						}else{
							$partly = true;
							echo "<br> $field_name  not found ! selector is $selector ";//test kh
						}
					}else{
						if (phpQuery::pq($selector)->length() != 0){
							for($i= 0; $i<phpQuery::pq($selector)->length(); $i++ ){
								$result[$field_name][] =  phpQuery::pq($selector)->eq($i)->attr($selector_atrr);
							}
						}else{
							$partly = true;
						}
					}
					
				}//end of foreach ($role as $selector=>$selector_atrr)
				
			}//end of foreach($roleSetting as $field_name => $role)
		}//end of if (is_array($roleSetting))
		else{
			$otherErrMsg = ' role setting not array '.print_r($roleSetting,true);
		}
		
		if (!empty($result)){
			//分析 成功
			return ['success'=>true, 'data'=>$result , 'partly'=>$partly];
		}else{
			print_r($targetHtml);
			//分析 失败
			return ['success'=>false , 'type'=>'analyze' , 'message'=>'规则匹配失败'.$otherErrMsg];
		}
		
	}//end of analyzeHtml
	
	
	/**
	 +-----------------------------------------------------------------------------------
	 * 根据供应商名字找出供应商编号
	 +-----------------------------------------------------------------------------------
	 * @access static     
	 +-----------------------------------------------------------------------------------
	 * @param 	
	 * 			$platform		string		平台 (amazon , )
	 * 			$subsite		string		子站 (amazon 有UK , FR ....)
	 +-----------------------------------------------------------------------------------
	 * @return				array
	 * 	boolean					success  执行结果
	 * 	string/array			message  执行失败的提示信息
	 +-----------------------------------------------------------------------------------
	 * @invoking			HtmlCatcherHelper::getActiveRoleSetting($platform,$subsite);
	 +-----------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/08/21				初始化
	 +-----------------------------------------------------------------------------------
	 **/
	static public function  getActiveRoleSetting($platform , $subsite=''){
		$query = AnalyseHtmlRole::find()->andWhere(['platform'=>$platform])->andWhere(['is_active'=>1]);
		
		if (!empty($subsite))
			$query = $query->andWhere(['subsite'=>$subsite]);
		
		$result = $query->orderBy('last_time desc')->asArray()->all();
		return $result;
	}//end of getRoleSetting
	
	
	/**
	 +--------------------------------------------------------------------------------------------------------------
	 * 获取某个网页的数据，
	 +--------------------------------------------------------------------------------------------------------------
	 *
	 * @access static
	 +--------------------------------------------------------------------------------------------------------------
	 * @param
	 *        	$url 指定只做某个读取某个url
	 * @param
	 *        	$TIME_OUT 超时时间
	 +--------------------------------------------------------------------------------------------------------------
	 * @return array('success'=true,'message'='') @invoking					HtmlCatcherHelper::queueHandlerProcessing1();
	 *
	 +--------------------------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2015/7/1				初始化
	 +--------------------------------------------------------------------------------------------------------------
	 *
	 */
	static public function getHtmlData($url  ,$TIME_OUT=180){
	
		try {
		
			$rtn['success'] = true;  //跟proxy之间的网站是否ok
			$rtn['message'] = '';
				
			//	SysLogHelper::SysLog_Create("WISH", __CLASS__,__FUNCTION__,"","  reqParam:".json_encode($get_params),"Debug");
			//$journal_id = SysLogHelper::InvokeJrn_Create("DataCatcher", __CLASS__, __FUNCTION__ , array($url));
		
			$handle = curl_init($url);
			//echo "try to call proxy by url:".$url."\n";//test kh
			curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
			curl_setopt($handle, CURLOPT_FOLLOWLOCATION, 1); // 302 redirect
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, FALSE); // 对认证证书来源的检查
			curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, FALSE); // 从证书中检查SSL加密算法
			//echo "time out : ".$TIME_OUT;
			
			//  output  header information
			// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
				
			/* Get the HTML or whatever is linked in $url. */
			$response = curl_exec($handle);
			$curl_errno = curl_errno($handle);
			$curl_error = curl_error($handle);
			if ($curl_errno > 0) { // network error
				$rtn['message']="cURL Error $curl_errno : $curl_error";
				$rtn['success'] = false ;
				$rtn['Response'] = "";
				curl_close($handle);
				return $rtn;
			}
		
			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			//echo $httpCode.$response."\n";
			if ($httpCode <> '200' ){ //retry now
				$response = curl_exec($handle);
				$curl_errno = curl_errno($handle);
				$curl_error = curl_error($handle);
				if ($curl_errno > 0) { // network error
					$rtn['message']="cURL Error $curl_errno : $curl_error";
					$rtn['success'] = false ;
					$rtn['Response'] = "";
					curl_close($handle);
					return $rtn;
				}
				/* Check for 404 (file not found). */
				$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			}
				
			if ($httpCode == '200' ){
				$rtn['Response'] = $response;
				if ($rtn['Response']==null){
					// json_decode fails
					$rtn['message'] = "content return from proxy is not in json format, content:".$response;
					//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
					//	   	SysLogHelper::SysLog_Create("WISH", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
					$rtn['success'] = false ;
				}else
					$rtn['message'] = "";
		
		
			}else{ // network error
				$rtn['message'] = "Failed for getHtmlData $url, Got error respond code $httpCode from Proxy";
				//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
				//	SysLogHelper::SysLog_Create("WISH", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
				$rtn['success'] = false ;
				$rtn['Response'] = "";
			}
		
			//SysLogHelper::GlobalLog_Create("Platform", __CLASS__,__FUNCTION__,"Step A","Call proxy done httpCode= $httpCode , success= ".$rtn['success'] . " Response=".$response  ,"info");
			curl_close($handle);
		
		} catch (Exception $e) {
			$rtn['success'] = false;  //跟proxy之间的网站是否ok
			$rtn['message'] = $e->getMessage();
			echo ( __CLASS__)." exception for ".$rtn['message']."\n";
			curl_close($handle);
		}
		
		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		return $rtn;
	}
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * API队列处理器。按照priority执行一个API，然后把结果以及状态update到queue，
	 * 同时把信息写到每个user数据库的 Message 表中.
	 * 该方法只会执行排在最前面的一个request，然后就返回了，不会持续执行好多
	 * 该任务支持多进程并发执行
	 * +---------------------------------------------------------------------------------------------
	 * 
	 * @access static
	 * +---------------------------------------------------------------------------------------------
	 * @param
	 * 			puid
	 * @param
	 *        	platform 必须指定平台，可选option: ebay,aliexpress,wish,dhgate
	 * +---------------------------------------------------------------------------------------------
	 * @return array('success'=true,'message'='') @invoking					HtmlCatcherHelper::queueHandlerProcessing1();
	 *        
	 * +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2015/7/1				初始化
	 * +---------------------------------------------------------------------------------------------
	 *        
	 */
	static public function queueHandlerProcessing1($puid = '', $platform = '',$totalJobs=0,$thisJobId=0,$onceCount=300, $priority=false) {
		global $CACHE;
		$queue_table = 'hc_collect_request_queue'; 
		if (empty ( $CACHE ['JOBID'] ))
			$CACHE ['JOBID'] = "MS" . rand ( 0, 99999 );
		
		echo "\n".(__FUNCTION__)." for $platform entry \n";//test kh
		$logTime1 =  TimeUtil::getCurrentTimestampMS();
		$WriteLog = false;
		if ($WriteLog)
			\Yii::info ( [ 
					'List',
					__CLASS__,
					__FUNCTION__,
					'Background',
					"SyncProdQueue 0 Enter:" . $CACHE ['JOBID'] 
			], "edb\global" );
		
		$rtn ['message'] = "";
		$rtn ['success'] = true;
		$now_str = date ( 'Y-m-d H:i:s' );
		$two_days_ago = date ( 'Y-m-d H:i:s',time()-3600*24*2 );
		$seedMax = 15;
		$seed = rand ( 0, $seedMax );
		$one_go_count = $onceCount;
		
		// Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧

		$JOBID = $CACHE ['JOBID'];
		$current_time = explode ( " ", microtime () );
		$start1_time = round ( $current_time [0] * 1000 + $current_time [1] * 1000 );
		\Yii::info ( "multiple_process_main step1 mainjobid=$JOBID" );
		
		
		$logTime2 =  TimeUtil::getCurrentTimestampMS();
		echo "\n".(__FUNCTION__)." current version is ok (".$logTime2-$logTime1.") \n";//test kh
		// step 1, try to get a pending request in queue, according to priority
		$coreCriteria = ' (status="P" or status="R")  and retry_count <10 ';
		$coreCriteria .= " and platform='$platform'" . ($puid == '' ? '' : " and puid=:puid");
		
		//这个job 是批量job里面的一员，那么只需要取出这个job需要做的目标就可以，避免多job之间打架
		if ($totalJobs > 0){
			$coreCriteria .= " and id % $totalJobs = $thisJobId ";
		}
		
		if($priority){
			$coreCriteria .= " and `priority` = $priority ";
		}
			
		// 防止一个客户太多request，每次随机一个数，优先处理puid mod 5 ==seed 的这个
		$command = Yii::$app->get ( 'db_queue2' )->createCommand ( "select * from $queue_table where $coreCriteria order by priority,id asc limit $one_go_count" );
		
		$command->bindValue ( ':puid', $puid, \PDO::PARAM_STR );
		$pendingOnes = $command->queryAll ();
		$logTime3 =  TimeUtil::getCurrentTimestampMS();
		// if no pending one found, return true, message = 'n/a';
		if (empty ( $pendingOnes )) {
			$rtn ['message'] = "n/a";
			$rtn ['success'] = true;
			// echo "No pending, idle 4 sec... ";
			echo "\n".(__FUNCTION__)." pending is empty (".$logTime3-$logTime2.") \n";//test kh
			return $rtn;
		}
		
		$current_time = explode ( " ", microtime () );
		$start2_time = round ( $current_time [0] * 1000 + $current_time [1] * 1000 );

		//TrackingAgentHelper::extCallSum ( "HtmlCatcher.CatchDataInfo", $start2_time - $start1_time );
		
		$doneRequestIds = array ();
		$donePuidOrders = array ();
		$logTime4 =  TimeUtil::getCurrentTimestampMS();
		echo "\n".(__FUNCTION__)." start to request (".$logTime4-$logTime3.") \n";//test kh
		foreach ( $pendingOnes as $pendingRequest ) {
			$currentHtmlCatcherVersion = ConfigHelper::getGlobalConfig ( "htmlcatcher/HtmlCatchDataQueueVersion", 'NO_CACHE' );
			if (empty ( $currentHtmlCatcherVersion ))
				$currentHtmlCatcherVersion = 0;
				
			// 如果自己还没有定义，去使用global config来初始化自己
			if (empty ( self::$HtmlCatcherVersion ))
				self::$HtmlCatcherVersion = $currentHtmlCatcherVersion;
				
			// 如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
			if (self::$HtmlCatcherVersion != $currentHtmlCatcherVersion) {
				TrackingAgentHelper::extCallSum ( "", 0, true );
				exit ( "Version new $currentHtmlCatcherVersion , this job ver " . self::$HtmlCatcherVersion . " exits for using new version $currentHtmlCatcherVersion." );
			}
			$logTime4_1 =  TimeUtil::getCurrentTimestampMS();
			$pk_id = $pendingRequest['id'];
			//$seller_id = $pendingRequest ['seller_id'];
			$puid = $pendingRequest ['puid'];
			
 
			/* 单进程, 不需要设置为多进程 */
			//防止多进程时抢占同一个资源
/*
			$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='S' ,update_time='$now_str'
					where id = '".$pendingRequest['id']."' and status='P' " );
			$affectRows = $command->execute ();
			
			//$affectRows == 0 说明 该请求已经补执行
			if ($affectRows == 0) continue;
	*/		 
			// step 1: call platform api to sync product detail
			$thisSeller = '';
			if (! empty ( $pendingRequest ['addi_info'] )) {
				$addi_info = json_decode ( $pendingRequest ['addi_info'], true );
				if(!empty($addi_info['seller']))
					$thisSeller = $addi_info['seller'];
			}
			
			$current_time = explode ( " ", microtime () );
			$start1_time = round ( $current_time [0] * 1000 + $current_time [1] * 1000 );
			
			if ($platform == 'ebay') {
				//@todo ebay sync prod 
			} // end of ebay api
			
			if ($platform == 'aliexpress') {
				//@todo aliexpress sync prod
			} // end of aliexpress api
			
			if ($platform == 'dhgate') {
				//@todo dhgate sync prod
				
			} // end of dhgate api
			
			if ($platform == 'wish'){
				
			}//end of platform = wish 
			
			if ($platform == 'newegg'){
				$logTime4_1 =  TimeUtil::getCurrentTimestampMS();
				$puid;
				$item_sku = $pendingRequest['product_id'];
				$error_message = '';
				$info = [];
				$thisResult = [];
				$retry_count = (int)$pendingRequest['retry_count'];
				$thisStep = 'catch';//第一步为抓取数据
				$thisStatus = 'F';//默认为失败
				if(empty($item_sku)){
					$error_message = '没有item_sku？';
				}
				//上面执行没有问题的话，进入下一步(html抓取、规则解析)
				if(empty($error_message)){
					//进行html抓取
					try{
						$url = "https://www.newegg.com/Product/Product.aspx?Item=".$item_sku;
						$roleSetting = '{"title":{"[itemprop=name]":""},"primary_image":{".mainSlide":"imgzoompic"}}';
						$roleSetting = json_decode($roleSetting,true);
						$analyzeHtml_ret = HtmlCatcherHelper::analyzeHtml($url, $roleSetting);
					
						$thisResult['html'] = $analyzeHtml_ret;
						
						if(!$analyzeHtml_ret['success']){
							$thisStep = $analyzeHtml_ret['type'];
							$error_message .= $analyzeHtml_ret['message'];
							echo "\n puid=$puid url=".$url . " rt = ".json_encode($analyzeHtml_ret);
						}else{
							$info[$item_sku] = $analyzeHtml_ret['data'];
						}
					}catch (Exception $e){
						$thisResult['html_catch_ana_error'] = $e;
						$error_message .= json_encode($e);
						break;
					}
				}
				//上面执行没有问题的话，进入下一步(callback)
				if(empty($error_message)){
					$thisStep = 'callback';
					try{
						$ret_callback = ['success' => 0, 'message' => '回调函数好像没有执行！拿不到回调函数的返回值'];
						//执行回调函数
						$setParams = '$product_id="'.$item_sku.'"; $puid="'.$puid.'"; $uid="'.$puid.'"; '.
								'$prodcutInfo =\''.base64_encode(json_encode($info)).'\'; '.'$seller="'.$thisSeller.'"; ';
						echo $setParams. $pendingRequest['callback_function'];
						eval($setParams. '$ret_callback = ' .$pendingRequest['callback_function']);
						
						$thisResult['callback'] = $ret_callback;
						
						if(!$ret_callback['success']){
							$error_message .= $ret_callback['message'];
						}
						
					}catch (Exception $e){
						$thisResult['callback_error'] = $e;
						$error_message .= json_encode($e);
					}
					
					//如果一直没有错误，就证明成功了
					if(empty($error_message)){
						$thisStatus = 'C';
					}
				}
				
				if($thisStatus == 'F' && $retry_count < 1){
					$thisStatus = 'P';
				}
				$retry_count++;
				
				$logTime4_2 =  TimeUtil::getCurrentTimestampMS();
				$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='$thisStatus' ,update_time='$now_str' ,
						runtime = ".($logTime4_2-$logTime4_1)."
						".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
						".(!empty($thisResult)?" , result = :result ":"" )."
						".(!empty($thisStep)?" , step = :step":"" )."
						".(!isset($retry_count)?"":" , retry_count=:retry_count")."
						where id = '$pk_id'" );
				
				if (!empty($error_message)){
					$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
				}
				
				if (!empty($thisResult)){
				if(is_object($thisResult)){
					$sqlResult =self::object_to_array($thisResult);
					}else{
					$sqlResult =$thisResult;
				}
				$command->bindValue ( ':result', is_array($sqlResult)?json_encode($sqlResult):$sqlResult , \PDO::PARAM_STR );
				}
				
				if (!empty($thisStep)){
				$command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
				}
				
				if (isset($retry_count)){
					$command->bindValue ( ':retry_count', $retry_count, \PDO::PARAM_INT );
				}
				$affectRows = $command->execute ();
				//echo " <br> update what happen = $affectRows <br>";//test kh
				if ($affectRows == 0){
					//更新失败 , todo
					echo '\n '.$pendingRequest['product_id'].' failure to save platform info \n ';
				}
				
			}//end of platform = newegg
			
			if (strtolower($platform) == 'cdiscount'){
				$logTime4_1 =  TimeUtil::getCurrentTimestampMS();
				echo "\n version=1.2 pkid=$pk_id  puid=$puid  product_id=".$pendingRequest['product_id'] .' '; //testkh
				//变参商品特殊处理
				$retry_count = (int)$pendingRequest['retry_count'];
				if(preg_match('/\-/', $pendingRequest['product_id'])){//is a variant child
					echo "is a variant child " ; //testkh
					$productIdStr = explode('-', $pendingRequest['product_id']);
					if(!empty($productIdStr[0])){
						$command = Yii::$app->get ( 'db_queue2' )->createCommand ( "select * from $queue_table where puid=:puid and product_id=:product_id and status='C' and `update_time`>'".date('Y-m-d H:i:s',(time()-180))."' and `priority`=0 order by id desc limit 1" );
							
						$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
						$command->bindValue ( ':product_id', $productIdStr[0], \PDO::PARAM_STR );
						$parent_record = $command->queryAll();
						if(!empty($parent_record)){
							if(!empty($parent_record[0]['result'])){
								$thisResult = $parent_record[0]['result'];
								$thisResult = json_decode($thisResult,true);
								try {
									if (!empty($thisResult)){
										$thisResult = self::formatterResult($thisResult, $platform, ''  , 'openapi-product-foramtter');
									}
									if (is_string($thisResult)) {
										unset($thisResult);
										$thisResult = [];
									}
									$thisResult['product_id'] = $pendingRequest['product_id'];
									$setParams = '$product_id="'.$pendingRequest['product_id'].'"; $puid="'.$puid.'"; $uid="'.$puid.'"; '.
											'$prodcutInfo =\''.base64_encode(json_encode($thisResult)).'\'; '.'$seller="'.$thisSeller.'"; ';
									//echo $setParams. $pendingRequest['callback_function'];
									//eval($setParams. $pendingRequest['callback_function']);
									
									//After getting result, 使用异步推送队列 进行callback 时间的invoking
									//$command_line = '\eagle\modules\tracking\helpers\TrackingHelper::pushToOMS( '. $puid .' , "'. $orig_data['order_id'].'","'.$commitData['status'].'","'.$commitData['last_event_date'].'")';
									AppPushDataHelper::insertOneRequest("HtmlCatcher", "Terminator", $puid, $setParams. $pendingRequest['callback_function']);
									
									$thisStatus = 'C';
									$error_message = '';
								} catch (\Exception $e) {
									if (is_string($e->getMessage()))
										echo $e->getMessage();
									$error_message = $e->getMessage();
									$thisStep = 'callback';
									$thisStatus = 'F';
								}
							}else{
								$thisStatus = 'R';
								$retry_count = (int)$pendingRequest['retry_count']+1;
								$error_message = 'variant child:parent prodcut info missed,failed to copy';
								$thisStep = 'collecting';
							}
						}else{
							//有时候父商品记录和子商品记录比较靠近，父商品记录还没update好导致查询不到状态为C的父商品；
							//如果父商品依然在查询中，则自商品直接跳过，等待下次查询
							$command = Yii::$app->get ( 'db_queue2' )->createCommand ( "select * from $queue_table where puid=:puid and product_id=:product_id and `priority`=0 and (status='P' or (status='C' and `update_time`>'".date('Y-m-d H:i:s',(time()-180))."')) order by id desc " );
								
							$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
							$command->bindValue ( ':product_id', $productIdStr[0], \PDO::PARAM_STR );
							$parent_records = $command->queryAll();
							if(!empty($parent_records)){
								foreach ($parent_records as $parent_record){
									if((int)$parent_record['retry_count']>=10){
										$command = Yii::$app->db_queue2->createCommand ( 
											"update $queue_table set status='F', err_msg = 'retry_count>10'  ,update_time='$now_str' where id = ".$parent_record['id']);
										$command->execute ();
									}
								}
								continue;
							}
							
							$retry_count = (int)$pendingRequest['retry_count']+1;
							//重试最多10次避免这种情况
							if($retry_count<10){
								$thisStatus = 'P';
							}else
								$thisStatus = 'F';
							$error_message = 'variant child:have not parent prodcut got info,failed to copy';
							//没有父商品信息，那么就insert一条
							//如果最近两日有重试多次都失败的父记录，则不再查询，将状态设置为F
							$command = Yii::$app->get ( 'db_queue2' )->createCommand ( "select * from $queue_table where puid=:puid and product_id=:product_id and retry_count=10 and create_time>='$two_days_ago' " );
							$command->bindValue ( ':puid', $puid, \PDO::PARAM_INT );
							$command->bindValue ( ':product_id', $productIdStr[0], \PDO::PARAM_STR );
							$recent_parent_record = $command->queryAll();
							if(!empty($recent_parent_record)){
								$retry_count = (int)$pendingRequest['retry_count']+1;
								if($retry_count<10){
									$thisStatus = 'P';
									$recent_parent_F_ids = [];
									foreach ($recent_parent_record as $recent_parent){
										$recent_parent_F_ids[] = (int)$recent_parent['id'];
									}
									//删除近期获取失败的父商品记录
									$command_del_parent = Yii::$app->get ( 'db_queue2' )->createCommand ( "DELETE FROM $queue_table WHERE `id` in (".implode(',', $recent_parent_F_ids).")" );
									$command_del_parent->execute ();
								}else{
									$thisStatus = 'F';
									$thisStep = 'collecting';
									$error_message = 'variant child:parent prodcut recent can not got info, set failed';
								}
							}else{
								echo "\n variant child have not parent prodcut got info,failed to copy, so insert one...";
								$command = Yii::$app->get ( 'db_queue2' )->createCommand ( "INSERT INTO $queue_table
									(`puid`, `product_id`, `field_list`, `status`, `platform`, `create_time`,  `addi_info`, `priority`,`callback_function`) VALUES
									(:puid, :product_id, :field_list, 'P', 'cdiscount', :create_time, :addi_info, 0, :callback_function)" );
								$command->bindValue ( ':puid', $pendingRequest['puid'], \PDO::PARAM_INT );
								$command->bindValue ( ':product_id', $productIdStr[0], \PDO::PARAM_STR );
								$command->bindValue ( ':field_list', $pendingRequest['field_list'], \PDO::PARAM_STR );
								$command->bindValue ( ':create_time', TimeUtil::getNow(), \PDO::PARAM_STR );
								$command->bindValue ( ':addi_info', $pendingRequest['addi_info'], \PDO::PARAM_STR );
								$command->bindValue ( ':callback_function', $pendingRequest['callback_function'], \PDO::PARAM_STR );
								$command->execute ();
								
								$thisStatus = 'P';
								$thisStep = 'collecting';
							}

						}	
					}else{
						$thisStatus = 'F';
						$error_message = 'product_id format error';
						$thisStep = 'collecting';
					}
					
					if ($retry_count>=10){
						if ($thisStatus == 'P') $thisStatus ='F'; // 保险起见， 10次还为P 的重新设置多一次为F
						echo "\n retry count >10 ,set F ! ";
					}
					$logTime4_2 =  TimeUtil::getCurrentTimestampMS();
					$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='$thisStatus' ,update_time='$now_str' , 
						runtime = ".($logTime4_2-$logTime4_1)."
						".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
						".(!empty($thisResult)?" , result = :result ":"" )."
						".(!empty($thisStep)?" , step = :step":"" )."
						".(!isset($retry_count)?"":" , retry_count=:retry_count")."
						where id = '$pk_id'" );
				
					if (!empty($error_message)){
						$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
					}
					if (!empty($thisResult)){
						$command->bindValue ( ':result', (is_array($thisResult)? json_encode($thisResult):$thisResult) , \PDO::PARAM_STR );
					}
					if (!empty($thisStep)){
						$command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
					}
					if (isset($retry_count)){
						$command->bindValue ( ':retry_count', $retry_count, \PDO::PARAM_INT );
					}
					$affectRows = $command->execute ();
					if ($affectRows == 0){
						//更新失败 
						echo '\n '.$pendingRequest['product_id'].' failure to save platform info \n ';
					}
					continue;//结束变参子产品处理，即同时结束该条记录处理
				}
				
				$pendingRequest['product_id'] = strtoupper($pendingRequest['product_id']);
				$productIdList = [];
				$productIdList[] = $pendingRequest['product_id'];
				//调用 open api
				$ct_rt = CdiscountOpenApi::getCdiscountProduct($productIdList);
				$thisResult = '';
				$retry_count = 0;//重试次数
				//var_dump($ct_rt[$pendingRequest['product_id']]['product']);
				//return;
				
				
				//if (isset())
				
				if (!empty($ct_rt[$pendingRequest['product_id']]['message']) && trim($ct_rt[$pendingRequest['product_id']]['message'])=='cdiscount找不到对应的信息' ){
					//找不到产品 的不重试，浪费时间
					$thisStep = 'collecting';
					$thisStatus = "F";
					$error_message = $ct_rt[$pendingRequest['product_id']]['message'];
					echo "\n cd open api not found:".json_encode($ct_rt) ." and status=$thisStatus and message=$error_message ";
				}else{
					if (!empty($ct_rt[$pendingRequest['product_id']]['success']) && isset($ct_rt[$pendingRequest['product_id']]['product']) ){
						$thisResult = $ct_rt[$pendingRequest['product_id']]['product'];
						$thisStatus = "C";
					}else{
						if(empty($ct_rt))
							echo "\n error: ct_rt in null".PHP_EOL;
						else
							echo "\n error: ".json_encode($ct_rt).PHP_EOL;
						//获取失败
						$thisStep = 'collecting';
						if(empty($pendingRequest['retry_count']) || $pendingRequest['retry_count']<10){
							//重试次数<1时，设置为P，重做一次
							$thisStatus = "P";
							$retry_count = empty($pendingRequest['retry_count'])?1:(int)$pendingRequest['retry_count'] + 1;
						}else{
							$thisStatus = "F";
							$retry_count = (int)$pendingRequest['retry_count'] + 1;
						}
						$error_message = (!empty($ct_rt['message'])?$ct_rt['message']:"");
							
					}
				
				}
				
				if ($retry_count>=10){
					if ($thisStatus == 'P') $thisStatus ='F'; // 保险起见， 10次还为P 的重新设置多一次为F
					echo "\n cd open api result".json_encode($ct_rt) ." and status=$thisStatus and message=$error_message ";
				}
				/*khcomment20150907start
				
				//set up url 
				if (!empty($pendingRequest['product_id'])){
					$url = self::getSearchLinkByPlatform($pendingRequest['product_id'], $platform);
					//$url = "http://www.cdiscount.com/search/".$pendingRequest['product_id'].".html";
					//echo "yes";//test kh
				}else{
					$url = "";
					//echo "no";//test kh
				}
				//echo "<br>url : <br>".$url;//test kh
				
				// get the role setting
				$roleSetting = HtmlCatcherHelper::getActiveRoleSetting('cdiscount');
				$roleSetting = json_decode($roleSetting[0]['content'],true);
				
				//get html 
				$html_catch_result = self::analyzeHtml($url, $roleSetting);
				//echo " <br> analyze is ok <br>";//test kh 
				//save result 
				
				if ($html_catch_result['success'] ==false){
					//数据 抓取 和分析 出错了
					if ($html_catch_result['type'] == 'catch'){
						//数据 抓取出错了
						$thisStep = 'collecting';
					}else if ($html_catch_result['type'] == 'analyze'){
						//数据分析 出错了
						$thisStep = 'analyse';
					}else{
						//未知 错误
						$thisStep = 'undefined';
					}
					$error_message = $html_catch_result['message'];
					$thisStatus = "F";
					$thisResult = '';
					
				}else{
					//成功分析
					if ( empty($html_catch_result['partly'])){
						//匹配规则成功
						$thisStatus = "C";
					}else{
						//当前匹配规则失败请检查
						$thisStatus = "C";
					}
					
					if (!empty($html_catch_result['data'])){
						$thisResult = json_encode($html_catch_result['data']);
					}else{
						$thisResult = '';
					}
					
				}
				//@todo
				khcomment20150907end*/
				$logTime4_2 =  TimeUtil::getCurrentTimestampMS();
				$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='$thisStatus' ,update_time='$now_str' , 
						runtime = ".($logTime4_2-$logTime4_1)."
						".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
						".(!empty($thisResult)?" , result = :result ":"" )."
						".(!empty($thisStep)?" , step = :step":"" )."
						".(!isset($retry_count)?"":" , retry_count=:retry_count")."
						where id = '$pk_id'" );
				
				if (!empty($error_message)){
					$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
				}
				
				if (!empty($thisResult)){
					$command->bindValue ( ':result', (is_array($thisResult)? json_encode($thisResult):$thisResult) , \PDO::PARAM_STR );
				}
				
				if (!empty($thisStep)){
					$command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
				}
				
				if (isset($retry_count)){
					$command->bindValue ( ':retry_count', $retry_count, \PDO::PARAM_INT );
				}
				
				$affectRows = $command->execute ();
				//echo " <br> update what happen = $affectRows <br>";//test kh
				if ($affectRows == 0){
					//更新失败 , todo
					echo '\n '.$pendingRequest['product_id'].' failure to save platform info \n ';
				}else{
					if(!empty($thisStatus) && $thisStatus=='C'){
						//获取成功后，执行回调函数
						try {
							if (!empty($thisResult) && !empty($thisStatus)){
								$thisResult = self::formatterResult($thisResult, $platform, ''  , 'openapi-product-foramtter');
							}
							if (is_string($thisResult)) {
								unset($thisResult);
								$thisResult = [];
							}
							$thisResult['product_id'] = $pendingRequest['product_id'];
							$setParams = '$product_id="'.$pendingRequest['product_id'].'"; $puid="'.$puid.'"; $uid="'.$puid.'"; '.
								'$prodcutInfo =\''.base64_encode(json_encode($thisResult)).'\'; '.'$seller="'.$thisSeller.'"; ';
							//echo $setParams. $pendingRequest['callback_function'];
							//eval($setParams. $pendingRequest['callback_function']);
							//After getting result, 使用异步推送队列 进行callback 时间的invoking							
							AppPushDataHelper::insertOneRequest("HtmlCatcher", "Terminator", $puid, $setParams. $pendingRequest['callback_function']);
								
						} catch (\Exception $e) {
							if (is_string($e->getMessage()))
								echo $e->getMessage();
							$error_message = $e->getMessage();
							$thisStep = 'callback';
							
							$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='F' ,update_time='$now_str' ,
									".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
									".(!empty($thisStep)?" , step = :step":"" )."
											where id = '$pk_id'" );
							
							if (!empty($error_message)){
								if (is_array($error_message)) $error_message = json_encode($error_message);
								$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
							}
							
							if (!empty($thisStep)){
								$command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
							}
							
							$affectRows = $command->execute ();
						}
					}
				}
				
			}//end of $platform = cdiscount
			if (strtolower($platform) == 'priceminister'){
				$logTime4_1 =  TimeUtil::getCurrentTimestampMS();
				
				$productKeyList = [];
				if(trim($pendingRequest['product_id'])==''){
					$thisStep = 'collecting';
					$error_message = 'product_key lost';
					$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='F' ,update_time='$now_str' ,
							".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
								".(!empty($thisStep)?" , step = :step":"" )."
							where id = '$pk_id'" );
					if (!empty($error_message)){
						if (is_array($error_message)) $error_message = json_encode($error_message);
						$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
					}
					if (!empty($thisStep)){
						$command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
					}
					$affectRows = $command->execute ();
					continue;
				}
				$productKey = $pendingRequest['product_id'];//priceminister用的是ean来获取商品信息
				$addi_info = empty($pendingRequest['addi_info'])?[]:json_decode($pendingRequest['addi_info'],true);
				if(empty($addi_info['seller_id'])){
					$thisStep = 'collecting';
					$error_message = 'seller user lost';
					$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='F' ,update_time='$now_str' ,
							".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
								".(!empty($thisStep)?" , step = :step":"" )."
							where id = '$pk_id'" );
					if (!empty($error_message)){
						if (is_array($error_message)) $error_message = json_encode($error_message);
						$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
					}
					if (!empty($thisStep)){
						$command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
					}
					$affectRows = $command->execute ();
					continue;
				}
				if(empty($addi_info['key_type']) || ($addi_info['key_type']!=='sku' && $addi_info['key_type']!=='itemid')){
					$thisStep = 'collecting';
					$error_message = 'key_type lost or unvalidation key_type';
					$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='F' ,update_time='$now_str' ,
							".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
								".(!empty($thisStep)?" , step = :step":"" )."
							where id = '$pk_id'" );
					if (!empty($error_message)){
						if (is_array($error_message)) $error_message = json_encode($error_message);
						$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
					}
					if (!empty($thisStep)){
						$command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
					}
					$affectRows = $command->execute ();
					continue;
				}
				
				$pm_account = SaasPriceministerUser::find()->where(['uid'=>$puid,'username'=>$addi_info['seller_id']])->one();
				$pm_api = new PriceministerOrderInterface();
				$pm_api->setStoreNamePwd($pm_account->username, $pm_account->token);
				
				$ct_rt = $pm_api->GetItemInfos($productKey);
				$thisResult = '';
				
				if (!empty($ct_rt['success']) && !empty($ct_rt['iteminfo']) ){
					$thisResult = $ct_rt['iteminfo'];
					$thisStatus = "C";
				}else{
					//获取失败
					$thisStep = 'collecting';
					if(empty($pendingRequest['retry_count'])){
						//重试次数<1时，设置为P，重做一次
						$thisStatus = "P";
						$retry_count = 1;
					}else{
						$thisStatus = "F";
						$retry_count = (int)$pendingRequest['retry_count'] + 1;
					}
						
					$error_message = (!empty($ct_rt['message'])?$ct_rt['message']:"");
				}
				$logTime4_2 =  TimeUtil::getCurrentTimestampMS();
				$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='$thisStatus' ,update_time='$now_str' ,
						runtime = ".($logTime4_2-$logTime4_1)."
						".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
						".(!empty($thisResult)?" , result = :result ":"" )."
						".(!empty($thisStep)?" , step = :step":"" )."
						".(!isset($retry_count)?"":" , retry_count=:retry_count")."
						where id = '$pk_id'" );
				
				if (!empty($error_message)){
					$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
				}
				
				if (!empty($thisResult)){
					if(is_object($thisResult)){
						$sqlResult =self::object_to_array($thisResult);
					}else{
						$sqlResult =$thisResult;
					}
				$command->bindValue ( ':result', is_array($sqlResult)?json_encode($sqlResult):$sqlResult , \PDO::PARAM_STR );
				}
				
				if (!empty($thisStep)){
				$command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
				}
				
				if (isset($retry_count)){
					$command->bindValue ( ':retry_count', $retry_count, \PDO::PARAM_INT );
				}
				$affectRows = $command->execute ();
				//echo " <br> update what happen = $affectRows <br>";//test kh
				if ($affectRows == 0){
					//更新失败 , todo
					echo '\n '.$pendingRequest['product_id'].' failure to save platform info \n ';
				}else{
				//执行回调函数
					try {
						if (!empty($thisResult) && !empty($thisStatus) && $thisStatus=='C'){
							$thisResult = self::formatterResult($thisResult, $platform, '','');
						}
						if (is_string($thisResult)) {
							unset($thisResult);
							$thisResult = [];
						}
						if(!empty($addi_info['sku']))
							$thisResult['sku'] = $addi_info['sku'];
						else 
							$thisResult['sku'] ='';
						$setParams = ' $puid="'.$puid.'"; $uid="'.$puid.'"; $seller="'.$addi_info['seller_id'].'"; $prodcutInfo =\''.base64_encode(json_encode($thisResult)).'\';';
						//echo $setParams. $pendingRequest['callback_function'];
						//eval($setParams. $pendingRequest['callback_function']);
						//After getting result, 使用异步推送队列 进行callback 时间的invoking
						AppPushDataHelper::insertOneRequest("HtmlCatcher", "Terminator", $puid, $setParams. $pendingRequest['callback_function']);
							
						
					} catch (\Exception $e) {
						if (is_string($e->getMessage()))
							echo $e->getMessage();
						$error_message = $e->getMessage();
						$thisStep = 'callback';

						$command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='F' ,update_time='$now_str' ,
							".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
							".(!empty($thisStep)?" , step = :step":"" )."
							where id = '$pk_id'" );

						if (!empty($error_message)){
							if (is_array($error_message)) $error_message = json_encode($error_message);
								$command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
						}

						if (!empty($thisStep)){
							$command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
						}

						$affectRows = $command->execute ();
					}
												
				}
			}
			
			if (strtolower($platform) == 'bonanza'&&$pendingRequest['platform'] == 'bonanza'){
			    $logTime4_1 =  TimeUtil::getCurrentTimestampMS();
			    
			    $productKeyList = [];
			    if(trim($pendingRequest['product_id'])==''){
			        $thisStep = 'collecting';
			        $error_message = 'product_key lost';
			        $command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='F' ,update_time='$now_str' ,
			            ".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
								".(!empty($thisStep)?" , step = :step":"" )."
			            where id = '$pk_id'" );
			        if (!empty($error_message)){
			            if (is_array($error_message)) $error_message = json_encode($error_message);
			            $command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
			        }
			        if (!empty($thisStep)){
			            $command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
			        }
			        $affectRows = $command->execute ();
			        continue;
			    }
			    $productKey = $pendingRequest['product_id'];//bonanza用的是itemId来获取商品信息
			    $addi_info = empty($pendingRequest['addi_info'])?[]:json_decode($pendingRequest['addi_info'],true);
			    if(empty($addi_info['store_name'])){
			        $thisStep = 'collecting';
			        $error_message = 'store name lost';
			        $command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='F' ,update_time='$now_str' ,
			            ".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
								".(!empty($thisStep)?" , step = :step":"" )."
			            where id = '$pk_id'" );
			        if (!empty($error_message)){
			            if (is_array($error_message)) $error_message = json_encode($error_message);
			            $command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
			        }
			        if (!empty($thisStep)){
			            $command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
			        }
			        $affectRows = $command->execute ();
			        continue;
			    }
			    if(empty($addi_info['key_type']) || ($addi_info['key_type']!=='sku' && $addi_info['key_type']!=='itemID')){
			        $thisStep = 'collecting';
			        $error_message = 'key_type lost or unvalidation key_type';
			        $command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='F' ,update_time='$now_str' ,
			            ".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
								".(!empty($thisStep)?" , step = :step":"" )."
			            where id = '$pk_id'" );
			        if (!empty($error_message)){
			            if (is_array($error_message)) $error_message = json_encode($error_message);
			            $command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
			        }
			        if (!empty($thisStep)){
			            $command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
			        }
			        $affectRows = $command->execute ();
			        continue;
			    }
			    
			    $pm_account = SaasBonanzaUser::find()->where(['uid'=>$puid,'store_name'=>$addi_info['store_name']])->one();
			    $pm_api = new BonanzaOrderInterface();
			    $pm_api->setBonanzaToken($pm_account->token);
			    
			    $ct_rt = $pm_api->GetItemInfos($productKey);
			    $thisResult = '';
			    
			    if (!empty($ct_rt['success']) && !empty($ct_rt['iteminfo']) ){
			        $thisResult = $ct_rt['iteminfo'];
			        $thisStatus = "C";
			    }else{
			        //获取失败
			        $thisStep = 'collecting';
			        $thisStatus = "F";
			    
			        $error_message = (!empty($ct_rt['message'])?$ct_rt['message']:"");
			    }
			    $logTime4_2 =  TimeUtil::getCurrentTimestampMS();
			    $command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='$thisStatus' ,update_time='$now_str' ,
			        runtime = ".($logTime4_2-$logTime4_1)."
						".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
						".(!empty($thisResult)?" , result = :result ":"" )."
						".(!empty($thisStep)?" , step = :step":"" )."
			        where id = '$pk_id'" );
			    
			    if (!empty($error_message)){
			        $command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
			    }
			    
			    if (!empty($thisResult)){
			        if(is_object($thisResult)){
			            $sqlResult =self::object_to_array($thisResult);
			        }else{
			            $sqlResult =$thisResult;
			        }
			        $command->bindValue ( ':result', is_array($sqlResult)?json_encode($sqlResult):$sqlResult , \PDO::PARAM_STR );
			    }
			    
			    if (!empty($thisStep)){
			        $command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
			    }
			    
			    $affectRows = $command->execute ();
			    //echo " <br> update what happen = $affectRows <br>";//test kh
			    if ($affectRows == 0){
			        //更新失败 , todo
			        echo '\n '.$pendingRequest['product_id'].' failure to save platform info \n ';
			    }else{
			        //执行回调函数
			        try {
			            if (!empty($thisResult)){
			                $thisResult = self::formatterResult($thisResult, $platform, '','');//bonanza
			            }
			            if (is_string($thisResult)) {
			                unset($thisResult);
			                $thisResult = [];
			            }
			            if(!empty($addi_info['sku']))
			                $thisResult['sku'] = $addi_info['sku'];
			            else
			                $thisResult['sku'] ='';
			            $setParams = ' $puid="'.$puid.'"; $uid="'.$puid.'"; $seller="'.$addi_info['store_name'].'"; $prodcutInfo =\''.base64_encode(json_encode($thisResult)).'\';';
			            //echo $setParams. $pendingRequest['callback_function'];
			            //eval($setParams. $pendingRequest['callback_function']);
			            //After getting result, 使用异步推送队列 进行callback 时间的invoking
			            AppPushDataHelper::insertOneRequest("HtmlCatcher", "Terminator", $puid, $setParams. $pendingRequest['callback_function']);
			           
			        } catch (\Exception $e) {
			            if (is_string($e->getMessage()))
			                echo $e->getMessage();
			            $error_message = $e->getMessage();
			            $thisStep = 'callback';
			    
			            $command = Yii::$app->db_queue2->createCommand ( "update $queue_table set status='F' ,update_time='$now_str' ,
			                ".(!empty($error_message)?" , err_msg = :err_msg ":"" )."
							".(!empty($thisStep)?" , step = :step":"" )."
			                where id = '$pk_id'" );
			    
			            if (!empty($error_message)){
			                if (is_array($error_message)) $error_message = json_encode($error_message);
			                $command->bindValue ( ':err_msg', $error_message, \PDO::PARAM_STR );
			            }
			    
			            if (!empty($thisStep)){
			                $command->bindValue ( ':step', $thisStep, \PDO::PARAM_STR );
			            }
			    
			            $affectRows = $command->execute ();
			        }
			    
			    }
			}//end of $platform = bonanza
			
			//$donePuidOrdersId [$puid] [$pendingRequest ['seller_id']] = $pendingRequest ['order_id'];
			$logTime5 =  TimeUtil::getCurrentTimestampMS();
			$current_time = explode ( " ", microtime () );
			$start2_time = round ( $current_time [0] * 1000 + $current_time [1] * 1000 );
			TrackingAgentHelper::extCallSum ( 'HtmlCatcher.CatchDataInfo.' . $platform, $start2_time - $start1_time );
			
			// step 2: set this request id is complete
		} // end of each pendingMessage
		  
		$logTime6 =  TimeUtil::getCurrentTimestampMS();
		
		return $rtn;
	}//end of queueHandlerProcessing1
	
	/**
	 * +---------------------------------------------------------------------------------------------
	 * 根据 product id  , 平台 , 子网站 3个属性生成 需要采集的类型
	 * +---------------------------------------------------------------------------------------------
	 *
	 * @access static
	 * +---------------------------------------------------------------------------------------------
	 * @param
	 * 			puid
	 * @param
	 *        	platform 必须指定平台，可选option: ebay,aliexpress,wish,dhgate
	 * +---------------------------------------------------------------------------------------------
	 * @return array('success'=true,'message'='') 
	 * 
	 * @invoking					HtmlCatcherHelper::getSearchLinkByPlatform($product_id , $platform , $subsite);
	 *
	 * +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2015/7/1				初始化
	 * +---------------------------------------------------------------------------------------------
	 *
	 */
	static public function getSearchLinkByPlatform($product_id , $platform , $subsite=""){
		$url = "";
		switch (strtolower($platform)){
			case "cdiscount":
				$url = "http://www.cdiscount.com/search/10/".$product_id.".html#_his_";
				break;
			default:
				$url = "";
		}
		
		return $url;
	}//end of getSearchLinkByPlatform
	
	/**
	 +-----------------------------------------------------------------------------------
	 * 格式 化数据
	 +-----------------------------------------------------------------------------------
	 * @access static     
	 +-----------------------------------------------------------------------------------
	 * @param 	
	 * 			$platform		string		平台 (amazon , )
	 * 			$subsite		string		子站 (amazon 有UK , FR ....)
	 +-----------------------------------------------------------------------------------
	 * @return				array
	 * 	boolean					success  执行结果
	 * 	string/array			message  执行失败的提示信息
	 +-----------------------------------------------------------------------------------
	 * @invoking			HtmlCatcherHelper::getActiveRoleSetting($platform,$subsite);
	 +-----------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/08/21				初始化
	 +-----------------------------------------------------------------------------------
	 **/
	public static function formatterResult($data  , $platform , $subsite='',$method =''){
		$result = [
			'seller_product_id'=>'',
			'img'=>[],
			'title'=>'',
			'description'=>'',
			'brand'=>'',
			'is_bestseller'=>'' , 
			'bestseller_name'=> '' , 
			'bestseller_price'=>0
		];
		
		$switch_key = $platform.'-'.$subsite.'-'.$method;
		switch (strtolower($switch_key)){
			case "cdiscount--":
				$mapping = array('seller_product_id','img','title','description','brand');
				$result = array_pad($result,count($mapping),'');
				foreach($data as $field_name=>$value){
					if (in_array($field_name, $mapping)){
						if (is_array($value)){
							//假如 是数组 的
							if (count($value) == 1 )
							$result[$field_name]  = $value[0];
							else{
								foreach($value as $sub_value){
									$result[$field_name][]  = $sub_value;
								}
							}
								
						}else if (is_string($value)){
							$result[$field_name]  = $value;
						}
						
					}
				}
				if (!empty($data['primary_image'])){
					$result['img'][] = $data['primary_image'][0];
				}
					
				
				if (!empty($data['other_image'])){
					foreach($data['other_image'] as $img_url){
						$result['img'][] = $img_url;
					}
				}
				
				return $result;
				
				break;
				
			case "cdiscount--openapi-product-foramtter":
				if (isset($data['Name']))
					$result['title'] = (String)$data['Name'];
				
				if (isset($data['Description']))
					$result['description'] = (String)$data['Description'];
				
				if (isset($data['Brand']))
					$result['brand'] = (String)$data['Brand'];
				
				if (isset($data['BestOffer']['Seller']['Name']))
					$result['bestseller_name'] = $data['BestOffer']['Seller']['Name'];
				
				if (isset($data['BestOffer']['SalePrice']))
					$result['bestseller_price'] = $data['BestOffer']['SalePrice'];
				
				/*
				if (isset($data['BestOffer']['Id']) && isset($data['Offers']['Id'])){
					if ($data['BestOffer']['Id'] == $data['Offers']['Id']){
						$result['is_bestseller'] = "Y";
					}else{
						$result['is_bestseller'] = "N";
					}
				}
				*/
				
				if (isset($data['MainImageUrl'])){
					$result['img'][] = $data['MainImageUrl'];
				}
				
				if (isset($data['Images'])){
					foreach ($data['Images'] as &$row){
						if (isset($row['ImageUrl'])){
							$result['img'][] = $row['ImageUrl'];
						}
					}
				}
				
				break;
				
			case "priceminister--":
				if (isset($data->itemid) && !is_null($data->itemid))
					$result['itemid'] = (String)$data->itemid;
				if (isset($data->date) && !is_null($data->date))
					$result['date'] = (String)$data->date;
				if (isset($data->product->url) && !is_null($data->product->url))
					$result['product_url'] = (String)$data->product->url;
				if (isset($data->product->headline) && !is_null($data->product->headline))
					$result['headline'] = (String)$data->product->headline;
				if (isset($data->product->topic) && !is_null($data->product->topic))
					$result['topic'] = (String)$data->product->topic;
				if (isset($data->product->caption) && !is_null($data->product->caption))
					$result['caption'] = (String)$data->product->caption;
				if (isset($data->product->image->url) && !is_null($data->product->image->url))
					$result['photo_primary'] = (String)$data->product->image->url;
				if (isset($data->comment) && !is_null($data->comment))
					$result['comment'] = (String)$data->comment;
				if (isset($data->gallery->image) && !is_null($data->gallery->image) && is_array($data->gallery->image))
					$result['photo_other'] = $data->gallery->image;
				break;
			case "bonanza--":
			    if (isset($data["itemID"]) && !is_null($data["itemID"]))
			        $result['itemID'] = (String)$data["itemID"];
// 		        if (isset($data["lastChangeTime"]) && !is_null($data["lastChangeTime"]))
// 		            $result['lastChangeTime'] = (String)$data["lastChangeTime"];
		        if (isset($data["itemSpecifics"]) && !is_null($data["itemSpecifics"]) && is_array($data["itemSpecifics"]))
		            $result['itemSpecifics'] = $data["itemSpecifics"];
		        if (isset($data["postalCode"]) && !is_null($data["postalCode"]))
		            $result['postalCode'] = (String)$data["postalCode"];
		        if (isset($data["listingStatus"]) && !is_null($data["listingStatus"]))
		            $result['listingStatus'] = (String)$data["listingStatus"];
		        if (isset($data["listingType"]) && !is_null($data["listingType"]))
		            $result['listingType'] = (String)$data["listingType"];
		        if (isset($data["galleryURL"]) && !is_null($data["galleryURL"]))
		            $result['photo_primary'] = (String)$data["galleryURL"];
		        if (isset($data["title"]) && !is_null($data["title"]))
		            $result['title'] = (String)$data["title"];
		        if (isset($data["sku"]))
		            $result['sku'] = $data["sku"];
		        if (isset($data["pictureURL"]) && !is_null($data["pictureURL"]) && is_array($data["pictureURL"]))
		            $result['photo_other'] = $data["pictureURL"];
		        break;
			default:
				return $result;
		}
		
		return $result;
	}//end of formatterResult
	
	static public function callback_test($puid , $product_id , $platform='cdiscount'  ){
		$pendingRequest = CollectRequestQueue::findOne(['product_id'=>$product_id]);
		
		try {
			if (!empty($pendingRequest['result'])){
				$data = json_decode($pendingRequest['result'],true);
				$thisResult = self::formatterResult($data, $platform ,''  , 'openapi-product-foramtter');
			}
			
			$thisResult['product_id'] = $pendingRequest['product_id'];
			$setParams = '$product_id="'.$pendingRequest['product_id'].'"; $puid="'.$puid.'"; $uid="'.$puid.'"; '.
					'$prodcutInfo =\''.base64_encode(json_encode($thisResult)).'\';';
			
			//$setParams = '';
			//echo $setParams. $pendingRequest['callback_function'];
			//eval($setParams. $pendingRequest['callback_function']);
			//After getting result, 使用异步推送队列 进行callback 时间的invoking
			AppPushDataHelper::insertOneRequest("HtmlCatcher", "Terminator", $puid, $setParams. $pendingRequest['callback_function']);
				
		} catch (Exception $e) {
			echo $e->getMessage();
		}
	}//end of callback_test
	
	private static function object_to_array($obj){
	    if(is_array($obj)){
	        return $obj;
	    }
	    if(is_object($obj)){
	    	$_arr = get_object_vars($obj);
	    }else{
	    	$_arr = $obj;
	    }
	    $arr = [];
	    foreach ($_arr as $key=>$val){
			$val=(is_array($val)) || is_object($val)?self::object_to_array($val):$val;
			$arr[$key] = $val;
	    }
	    return $arr;
	}
}//end of class
?>