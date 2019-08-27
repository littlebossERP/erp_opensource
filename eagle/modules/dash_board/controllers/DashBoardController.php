<?php

namespace eagle\modules\dash_board\controllers;

use eagle\modules\platform\apihelpers\PlatformAccountApi;
use eagle\modules\order\models\OdOrder;
use eagle\modules\dash_board\helpers\DashBoardHelper;
use eagle\modules\order\helpers\CdiscountOrderInterface;
use eagle\modules\dash_board\apihelpers\DashBoardStatisticHelper;
use console\helpers\SaasEbayAutosyncstatusHelper;
use console\helpers\ConvertionHelper;
use eagle\modules\permission\apihelpers\UserApiHelper;
use eagle\modules\util\helpers\ImageCacherHelper;
use eagle\modules\util\models\PhotoCacheQueue;
use eagle\modules\util\models\PhotoCache;
use eagle\modules\permission\helpers\UserHelper;
use eagle\modules\util\helpers\RedisHelper;


class DashBoardController extends \eagle\components\Controller{
	public $enableCsrfValidation = false;
	
	function actionIndex(){
		$puid = \Yii::$app->user->identity->getParentUid();
		if(empty($puid))
			exit('请先登录!');

		$PlatformBindingSituation = PlatformAccountApi::getPlatformInfoInRedis($puid);
		$PlatformBindingSituation = json_decode($PlatformBindingSituation,true);
		$bingingPlatforms = [];
		if (empty($PlatformBindingSituation)){
			$PlatformBindingSituation = PlatformAccountApi::getAllPlatformBindingSituation([], $puid);
			PlatformAccountApi::resetPlatformInfo('all',$puid);
		}
		
		$userAuthorizePlatforms = UserHelper::getUserAuthorizePlatform();
		
		foreach ($PlatformBindingSituation as $platform=>$active){
			if($active)
				$bingingPlatforms[] = $platform;
		}
		//var_dump($userAuthorizePlatforms);
		if((is_string($userAuthorizePlatforms) && $userAuthorizePlatforms!=='all') || 
			is_array($userAuthorizePlatforms) && !in_array('all', $userAuthorizePlatforms)){
			$bingingPlatforms = array_intersect($userAuthorizePlatforms,$bingingPlatforms);
		}
		
		//订单待处理
		$pendingOrders = DashBoardHelper::getPlatformPendingOrderNumber($puid,$bingingPlatforms);
		if($pendingOrders['success']){
			$pendingOrders = $pendingOrders['data'];
			//如果统计数据出现负数，则立即初始化
			foreach($pendingOrders as $platform=>$pendings){
				foreach ($pendings as $status=>$num){
					$num = (string)$num;
					//var_dump($num);
					if( preg_match('/\-/', $num) ){
						//echo "try to init;";
						$rtn = DashBoardHelper::initCountPlatformOrderStatus($puid,false);
						if(!empty($rtn['message'])) echo $rtn['message'];
						break;
					}	
				}
			}
		}else 
			$pendingOrders = [];
		
		//消息待处理
		$messagePendings = DashBoardHelper::getPlatformMessagePendingNumber($puid,$bingingPlatforms);
		if($messagePendings['success']){
			$messagePendings = $messagePendings['data'];
		}else
			$messagePendings = [];
		
		//异常待处理--------------------------------------------------------
		//账号授权失败，数量
		$authErrorAccounts = [];
		//账号授权失败，错误信息
		$authErrorMsg = [];
		//订单拉取失败
		$orderRetrieveErrorAccounts = [];
		//刊登失败
		$listingUploadErrorAccounts = [];
		
		$platformProblemAccounts = DashBoardHelper::getUserPlatformAccountsErr($puid, $bingingPlatforms);
		
		if($platformProblemAccounts['success']){
			foreach ($platformProblemAccounts['data'] as $platform=>$problemAccounts){
				if(!empty($problemAccounts['token_expired'])){
					$authErrorAccounts[$platform] = count($problemAccounts['token_expired']);
					
					try{
						switch ($platform){
							case 'ebay':
								$authErrorMsg[$platform]['title'] = '以下账号的验证信息已经过期且自动更新失败！';
								foreach ($problemAccounts['token_expired'] as $token_expired){
									$authErrorMsg[$platform]['msg'][] = $token_expired['selleruserid'];
								}
								break;
							case 'aliexpress':
							case 'dhgate':
								$authErrorMsg[$platform]['title'] = '以下账号的的验证信息已经过期且自动更新失败！ 请检查您的绑定信息(如账号密码/API账号API密码)是否有误！';
								foreach ($problemAccounts['token_expired'] as $token_expired){
									$authErrorMsg[$platform]['msg'][] = $token_expired['sellerloginid'];
								}
								break;
							case 'cdiscount':
							case 'priceminister':
							case 'newegg':
								$authErrorMsg[$platform]['title'] = '以下账号的的验证信息已经过期且自动更新失败！ 请检查您的绑定信息(如账号密码/API账号API密码)是否有误！';
								foreach ($problemAccounts['token_expired'] as $token_expired){
									$authErrorMsg[$platform]['msg'][] = $token_expired['store_name'];
								}
								break;
							case 'lazada':
							case 'linio':
							case 'jumia':
								$authErrorMsg[$platform]['title'] = '以下账号的的验证信息已经过期且自动更新失败！ 请检查您的绑定信息(如账号密码/API账号API密码)是否有误！';
								foreach ($problemAccounts['token_expired'] as $token_expired){
									$authErrorMsg[$platform]['msg'][] = empty($token_expired['store_name']) ? $token_expired['platform_userid'] : $token_expired['store_name'];
								}
								break;
							case 'wish':
								$authErrorMsg[$platform]['title'] = '';
								foreach ($problemAccounts['token_expired'] as $token_expired){
									if(isset($token_expired['order_retrieve_message'])){
										$authErrorMsg[$platform]['msg'][] = '账号：'.$token_expired['store_name'].$token_expired['order_retrieve_message'];
									}
									else if(isset($token_expired['is_timeout'])){
										$authErrorMsg[$platform]['msg'][] = '账号：'.$token_expired['store_name'].' 的验证信息已经过期！';
									}
								}
								break;
						}
					}
					catch(\Exception $ex)
					{}
				}
				if(!empty($problemAccounts['order_retrieve_failed']))
					$orderRetrieveErrorAccounts[$platform] = count($problemAccounts['order_retrieve_failed']);
				if(!empty($problemAccounts['listing_failed']))
					$listingUploadErrorAccounts[$platform] = count($problemAccounts['listing_failed']);
			}
		}
		
		
		
		//标记发货失败
		$signShippedErr = DashBoardHelper::getSignShippedErrorNumber($puid, $bingingPlatforms);
		if($signShippedErr['success']){
			$signShippedErr = $signShippedErr['data'];
			foreach ($signShippedErr as $platform=>$num){
				$num = (string)$num;
				if( preg_match('/\-/', $num) ){
					\eagle\modules\order\helpers\OrderBackgroundHelper::initOrderSyncShipStatusCount();
					break;
				}
			}
		}else
			$signShippedErr = [];
		//物流上传失败
		
		//--------------------------------------------------------------
		
		
		//默认显示两周(14日)的日统计
		$days = 14;
		$chartData=[];
		$chartData['xAxis'] = [];//x轴,显示日期
		$today = date('Y-m-d');
		
		$StatisticsData = DashBoardHelper::getPlatformOrderStatisticsData($puid,$bingingPlatforms,true,'','','daily','all',14);
		
		if(!empty($StatisticsData['success'])){
			$chartData = $StatisticsData['data'];
		}else 
			$chartData = [];
		
		//$platformOrderTypeList = DashBoardStatisticHelper::getPlatformOrderTypeList($bingingPlatforms);
		
		return $this->render('index',[
				'bingingPlatforms'=>$bingingPlatforms,
				'pendingOrders'=>$pendingOrders,
				'messagePendings'=>$messagePendings,
				
				'authErr'=>$authErrorAccounts,
				'signShippedErr'=>$signShippedErr,
				
				'chartData'=>$chartData,
				//'platformOrderTypeList'=>$platformOrderTypeList,
				'authErrorMsg'=>$authErrorMsg,
				]);
	}
	
	function actionAjaxChartData(){
		$puid = \Yii::$app->user->identity->getParentUid();
		if(empty($puid))
			exit('请先登录!');
		
		if(empty($_REQUEST['select_platform']))
			$platform='all';
		else 
			$platform = $_REQUEST['select_platform'];
		
		if(!empty($_REQUEST['order_type'])){
			$order_type = $_REQUEST['order_type'];
		}else 
			$order_type = 'all';
		
		$platform_sellers = [];
		
		$periodic = 'daily';
		$columns = 14;
		if(!empty($_REQUEST['periodic'])){
			$periodic = $_REQUEST['periodic'];
			if($periodic=='weekly')
				$columns = 12;
			if($periodic=='monthly')
				$columns = 12;
		}
		
		if($platform=='all'){
			$PlatformBindingSituation = PlatformAccountApi::getPlatformInfoInRedis($puid);
			$PlatformBindingSituation = json_decode($PlatformBindingSituation,true);
			$bingingPlatforms = [];
			foreach ($PlatformBindingSituation as $platform=>$active){
				//if($platform!=='cdiscount' && $platform!=='ebay' && $platform!=='priceminister')
				//	continue;
				if($active)
					$bingingPlatforms[] = $platform;
			}
			
			$userAuthorizePlatforms = UserHelper::getUserAuthorizePlatform();
			
			//var_dump($userAuthorizePlatforms);
			if((is_string($userAuthorizePlatforms) && $userAuthorizePlatforms!=='all') ||
			is_array($userAuthorizePlatforms) && !in_array('all', $userAuthorizePlatforms)){
				$bingingPlatforms = array_intersect($userAuthorizePlatforms,$bingingPlatforms);
			}
			
			$StatisticsData = DashBoardHelper::getPlatformOrderStatisticsData($puid,$bingingPlatforms,true,'','',$periodic,'all',$columns);
		}else{
			$tmp_account_info = UserHelper::getUserAuthorizePlatformAccounts($platform);
			$allPlatformAccounts = empty($tmp_account_info[$platform])?[]:$tmp_account_info[$platform];
			//var_dump($allPlatformAccounts);
			//$allPlatformAccounts = PlatformAccountApi::getPlatformAllAccount($puid, $platform);
			//$platform_sellers = empty($allPlatformAccounts['data'])?[]:$allPlatformAccounts['data'];
			if(!empty($allPlatformAccounts)){
				//$platform_sellers = array_keys($allPlatformAccounts);
				$platform_sellers = $allPlatformAccounts;
				$StatisticsData = DashBoardHelper::getPlatformOrderStatisticsData($puid,[$platform],false,$platform_sellers,'',$periodic,$order_type,$columns);
				if(count($allPlatformAccounts)>50){
					$tmp_series = empty($StatisticsData['data']['series'])?[]:$StatisticsData['data']['series'];
					$total_series = [];
					foreach ($tmp_series as $series){
						if(!(stripos($series['name'],'全部')===false)){
							$total_series[] = $series;
						}
					}
					$StatisticsData['data']['series'] = $total_series;
				}
			}else 
				$StatisticsData = DashBoardHelper::getPlatformOrderStatisticsData($puid,[$platform],false,'','',$periodic,$order_type,$columns);
		}
		
		if(!empty($StatisticsData['success'])){
			$chartData = $StatisticsData['data'];
		}else 
			$chartData = [];
		
		return $this->renderAjax('_chart',[
				'chartData'=>$chartData,
				'platform_sellers'=>$platform_sellers,
				]);
	}
	
	function actionRefreshPendingOrderNum(){
		$puid = \Yii::$app->user->identity->getParentUid();
		$rtn = DashBoardHelper::initCountPlatformOrderStatus($puid,false);
		RedisHelper::delOrderCache($puid);
		RedisHelper::delOrderCache2($puid);
		\eagle\modules\order\helpers\OrderBackgroundHelper::initOrderSyncShipStatusCount();
		\eagle\modules\tracking\helpers\TrackingHelper::delTrackerTempDataToRedis('left_menu_statistics');
		exit(json_encode($rtn));
	}
	
	function actionRefreshSalesCount(){
		$puid = \Yii::$app->user->identity->getParentUid();
		$rtn = DashBoardHelper::initSalesCount($puid,14);
		if(isset($rtn['counter']))
			unset($rtn['counter']);
		exit(json_encode($rtn));
	}

	
	function actionTest(){
		$puid = \Yii::$app->user->identity->getParentUid();
		$rtn = DashBoardHelper::initCountPlatformOrderStatus($puid);
		//$rtn = DashBoardHelper::initSalesCount($puid);
		print_r($rtn);
		exit();
	}
	
	function actionTest1(){
		$puid = \Yii::$app->user->identity->getParentUid();
		if(!isset($_REQUEST['days']))
			$count_days=30;
		else
			$count_days = (int)$_REQUEST['days'];
		//$rtn = DashBoardHelper::initCountPlatformOrderStatus($puid);
		$rtn = DashBoardHelper::initSalesCount($puid,$count_days);
		print_r($rtn);
		exit();
	}
	
	public function actionTest2(){
		 
	}
	
	public function actionTest3(){
		$rtn = PlatformAccountApi::resetPlatformInfo();
	}
	
	public function actionTestImageCacher(){
		global $failed_prefix_count,$NeedXlbPhotoCacher;
		$rtn['message'] = "";
		$rtn['count'] = 0;
		$rtn['success'] = true;
		$now_str = date('Y-m-d H:i:s');
		$classification = "PhotoCache";
		//先把已经有的photo cache 放到redis去
		ImageCacherHelper::initCacheToRedis();
		$specified_orig_url = 'https://pmcdn.priceminister.com/photo/1098779911_ML.jpg';
		
		
		$pendingReq_mod = PhotoCacheQueue::find();
		
		if (!empty($specified_orig_url))
			$pendingReq_mod->andWhere(['orig_url'=>$specified_orig_url]);
		
		$pendingReq_array = $pendingReq_mod->asArray()->all();
		
		$rtn['count'] = count($pendingReq_array);
		
		$doneIds = [];
		$failedIds = [];
		try{
			foreach ($pendingReq_array as $aReq){
				$prefix_url = str_replace("http://","",$aReq['orig_url']);
				$prefix_url = str_replace("https://","",$prefix_url);
				$postionOfSlash = strpos( $prefix_url ,'/');
				if ($postionOfSlash>1)
					$prefix_url = substr($prefix_url,0,$postionOfSlash-1);
					
				//如果已经连续4次这个link 死掉了，就不要再尝试这个,
				//例如 http://pmcdn.priceminister.com/photo/1065800093_ML.jpg
				//就是 pmcdn.priceminister.com
				if (!empty($failed_prefix_count[$prefix_url]) and $failed_prefix_count[$prefix_url]>4){
					$addi_info['errorMessage'] = "这个前缀 $prefix_url 错误次数很多了".$failed_prefix_count[$prefix_url]."，不要处理";
					$query = "update `ut_photo_cache_queue` set update_time=:update_time,
							local_path=:local_path,status='I',addi_info=:addi_info ,
							try_count = try_count + 1 where id=  ".$aReq['id'];
					$command = \Yii::$app->db_queue->createCommand($query);
					$command->bindValue(':update_time', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
					$command->bindValue(':local_path', '', \PDO::PARAM_STR);
					$command->bindValue(':addi_info', json_encode($addi_info) , \PDO::PARAM_STR);
					$insert = $command->execute();
					echo $addi_info['errorMessage'];
					continue;
				}
				
				echo "<br> Try to get photo for puid ".$aReq['puid']." ".$aReq['orig_url']." <br>";
				//首先识别 priceminister的需要用xlb photo cacher
				if (strpos($prefix_url, "priceminister",0)!== false)
					$NeedXlbPhotoCacher[$prefix_url] = true;
					
				//do the fetch,using QiNiu
				if (empty($NeedXlbPhotoCacher[$prefix_url])){
					$ret = ImageCacherHelper::askQiNiuToDoCache($aReq['orig_url'],$aReq['puid']);
					echo "<br> askQiNiuToDoCache 01 ret : <br>".json_encode($ret);
				}
				//如果第一次七牛不行，并且这个 domain name 还不是识别为使用 小老板 photo cacher的，试试用cacher看看
				if (isset($NeedXlbPhotoCacher[$prefix_url]) or !$ret['success'] and empty($NeedXlbPhotoCacher[$prefix_url])){
					$cacherRet = ImageCacherHelper::getImageByXlbCacher($aReq['orig_url']);
					echo "<br> getImageByXlbCacher ret : <br>".json_encode($cacherRet);
					if ($cacherRet['success']){
						$ret = ImageCacherHelper::askQiNiuToDoCache($cacherRet['cachedUrl'],$aReq['puid']);
						echo "<br> askQiNiuToDoCache 02 ret : <br>".json_encode($ret);
					}
					if ($ret['success'])
						$NeedXlbPhotoCacher[$prefix_url] = true;
				}
				echo "<br> local_path result : <br>". @$ret['local_path'];;
				//如果 original path 和 local path 尾部不同，就当成是失败的，不要弄进去啊
				$aReq['local_path'] = $ret['local_path'];
				$pos1 = strripos($aReq['orig_url'],"/");
				$pos1a = strripos($aReq['orig_url'],"_");
					
				if ($pos1a !== false and $pos1 < $pos1a){
					$pos1 = $pos1a;
				}
					
				$pos2 = strripos($aReq['local_path'],"/");
				$pos2a = strripos($aReq['local_path'],"_");
				if ($pos2a !== false and $pos2 < $pos2a){
					$pos2 = $pos2a;
				}
					
				if ( !empty($pos1) and !empty($pos2) ){
			
					$a = substr($aReq['orig_url'],$pos1);
					$b = substr($aReq['local_path'],$pos2);
			
					if (strlen($a) < strlen($b)){
						$len1 = strlen($a)  - 1;
					}else
						$len1 = strlen($b)  - 1;
			
					$a = substr($aReq['orig_url'],strlen($aReq['orig_url']) - $len1);
					$b = substr($aReq['local_path'],strlen($aReq['local_path']) - $len1);
					if ($a <> $b){
						$ret['success'] = false;
						$ret['message'] = "original and local file name not match ".$aReq['orig_url']." vs ".$aReq['local_path'].
						" so substr pos1 = ".$a. " but substr pos2 = ".$b ;
					}
				}
				echo "<br>".$ret['message'];
				if (!$ret['success']){
					$addi_info = array('errorMessage'=>$ret['message']);
					$query = "update `ut_photo_cache_queue` set update_time=:update_time,
							local_path=:local_path,status='".($aReq['try_count']>4 ? 'I' :'F')."',addi_info=:addi_info ,
							try_count = try_count + 1 where id=  ".$aReq['id'];
					$command = \Yii::$app->db_queue->createCommand($query);
					$command->bindValue(':update_time', date('Y-m-d H:i:s'), \PDO::PARAM_STR);
					$command->bindValue(':local_path', '', \PDO::PARAM_STR);
					$command->bindValue(':addi_info', json_encode($addi_info) , \PDO::PARAM_STR);
					$insert = $command->execute();
			
					if (empty($failed_prefix_count[$prefix_url]))
						$failed_prefix_count[$prefix_url] = 0;
			
					$failed_prefix_count[$prefix_url] ++;
			
					continue;
				}
					
				$aReq['local_path'] = $ret['local_path'];
				$doneIds[] = $aReq['id'];
					
				//insert the record to Photo Cache table, if existing orig url, replace it
				//到底是insert还是update，还要看看redis有没有现成的
				//$exist_val = \Yii::$app->redis->hget($classification,$aReq['orig_url']);
				$exist_val = RedisHelper::RedisGet($classification,$aReq['orig_url']);
				echo "<br>exist_val : ".var_dump($exist_val);
				if (empty($exist_val) or $exist_val=='pending'){//do insert
					echo "<br>do insert";
					$query = "replace INTO `ut_photo_cache`
					(`puid`, `status`, `orig_url`, `local_path`, `create_time` ) VALUES
					(".$aReq['puid'].",'C',:orig_url,:local_path, '".date("Y-m-d H:i:s")."' )";
					$command = \Yii::$app->db_queue->createCommand($query);
					$command->bindValue(':orig_url', $aReq['orig_url'], \PDO::PARAM_STR);
					$command->bindValue(':local_path', $aReq['local_path'], \PDO::PARAM_STR);
					$insert = $command->execute();
				}else{//do update
					echo "<br>do update";
					if ($exist_val <> $aReq['local_path'] ){
						PhotoCache::updateAll(['update_time'=> date('Y-m-d H:i:s'),
						'local_path'=>$aReq['local_path'] ] ,
						['orig_url'=>$aReq['orig_url'] ] );
							
					}
				}
					
				RedisHelper::RedisSet($classification,$aReq['orig_url'],$aReq['local_path']);
					
			}
		}catch ( \Exception $ex ) {
			print_r($ex->getMessage());
		}
		
		PhotoCacheQueue::updateAll(['update_time'=> date('Y-m-d H:i:s'),
		'status'=>'C'] ,[ 'id'=>$doneIds] );
		
		print_r($rtn);
		exit(json_encode($rtn));
	}
}

?>