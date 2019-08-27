<?php
namespace eagle\modules\listing\helpers;
use yii;
use eagle\modules\listing\models\WishFanben;
use eagle\models\SaasWishUser;
use yii\base\Exception;
use eagle\modules\listing\models\WishFanbenVariance;
use eagle\modules\util\helpers\GetControlData;
use eagle\modules\listing\models\SyncProductApiQueue;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\tracking\helpers\TrackingAgentHelper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TranslateHelper;
use eagle\modules\util\models\TranslateCache;

class SaasWishFanbenSyncHelper{
	private static $dummyAllProduct = "";
	private static $syncProdQueueVersion = '';
	
	private static $subQueueVersion = '';
	/**
	 +---------------------------------------------------------------------------------------------
	 *
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param
	 +---------------------------------------------------------------------------------------------
	 * @return
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		yzq		2015/3/24				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static private function changeDBPuid($puid){
		if ( empty($puid))
			return false;
	 
		return true;
	}//end of changeDBPuid
	
	/**
	 +----------------------------------------------------------
	 * 通过 wish api 获取wish 范本数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $token		授权信息	
	 * @param $returnType	数据返回类型 0为array , 1为json
	 +----------------------------------------------------------
	 * @return				wish 范本数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/04/20				初始化
	 +----------------------------------------------------------
	 **/
	static public function getWishFanBen($token , $returnType = 0){
		try {
			//设置参数
			$reqParams["token"] = $token;
			$reqParams["token"] = str_replace('=','@@@',$reqParams["token"]);
			
			//call wish api
			$result=WishProxyConnectKandengHelper::call_WISH_api("GetAllProduct",$reqParams);
			
			return $result;
		} catch (Exception $e) {
			//@todo write log 
			$result['success'] = false;
			$result['message'] = print_r($e->getMessage());
			return $result;
		}
		
	}//end of getWishFanBen
	
	
	/**
	 +----------------------------------------------------------
	 * 通过 wish api 获取wish 范本数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $token					授权信息
	 * @param $last_retrieve_time		上次获取时间  :YYYY-MM-DD
	 * @param $max_retry_count			错误重试数 
	 +----------------------------------------------------------
	 * @return				wish 范本数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/04/20				初始化
	 +----------------------------------------------------------
	 **/
	public static function getAllWishFanbenByPagination($token , $last_retrieve_time="" , $max_retry_count=1){
		//wish范本数据
		$fanbenList = [];
		$error_message = '';
		do{
			//设置默认页数
			$page_size = 200;
			//初始化变量
			if (empty($page_no)) 
				$page_no = 1;
			else 
				$page_no++;
			
			$start = ($page_no-1)*$page_size;
			
			$tmpResult = [];
			//分页获取范本, 避免超时
			//$tmpResult['proxyResponse']['success'] = true;
			$try_count = 0;
			//1.wish 返回成功 2.重试次数达到最大重试数  两者符合其中 之一 就不需要 重试
			while ((empty($tmpResult['proxyResponse']['success'])) && $try_count <= $max_retry_count ) {
				unset($tmpResult);
				$tmpResult = self::getWishFanBenWithPagination($token,$start,$last_retrieve_time,$page_size);
                //error_log("wish_tmp_result:".var_export($tmpResult,true)."\r\n",3,"/tmp/chenbin.log");
           //     $str = "wish_tmp_result:".var_export($tmpResult,true);
            //    Yii::info($str,"file");
				//最后 一次, 还是失败, 记下 error message
				if ((empty($tmpResult['proxyResponse']['success']))  && $try_count == $max_retry_count  ){
					//本机与proxy访问失败时 , 记下 当时 的失败的message
					if ( empty($tmpResult['success']) && !empty($tmpResult['message']))
						$error_message = "proxy error message : ".$tmpResult['message'];
					
					//proxy访问wish失败时 , 记下 当时 的失败的message
					if ( empty($tmpResult['proxyResponse']['success']) )
						$error_message = "wish error message : ";
					
					if (!empty($tmpResult['proxyResponse']['message']))
						$error_message .= $tmpResult['proxyResponse']['message'];
						
					if (!empty($tmpResult['proxyResponse']['wishResponse']['message']))
						$error_message .= $tmpResult['proxyResponse']['wishResponse']['message'];
					unset($fanbenList);//release memory
					unset ($tmpResult);//release memory
					return ['success'=>false, 'message'=>$error_message];
				}//end of error message
				$try_count++;
			}
			
			/**/
			if (!empty($tmpResult['proxyResponse']['wishReturn']['data'])){
				foreach($tmpResult['proxyResponse']['wishReturn']['data'] as &$row){
					$fanbenList[$row['Product']['id']] = $row['Product'];
				}
			}
            //error_log("wish_fanben:".var_export($fanbenList,true)."\r\n",3,"/tmp/chenbin_fanben_list.log");
          //  $str = "wish_fanben:".var_export($fanbenList,true);
         //   Yii::info($str,"file");


		}while ((!empty($tmpResult['proxyResponse']['wishReturn']['data'])));
		unset ($tmpResult);//release memory
		
		return ['success'=>true, 'message'=>'' , 'product'=>$fanbenList] ;
	}//end of getAllWishFanbenByPagination
	
	/**
	 +----------------------------------------------------------
	 * 通过 wish api 获取wish 范本数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $token					授权信息
	 * @param $start					开始条数  默认为 0
	 * @param $last_retrieve_time		上次获取时间  :YYYY-MM-DD
	 * @param $page_size				每页的数量 范围 [1,500]此参数为空的话wish默认是50  
	 * @param $returnType				数据返回类型 0为array , 1为json
	 +----------------------------------------------------------
	 * @return				wish 范本数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/04/20				初始化
	 +----------------------------------------------------------
	 **/
	static public function getWishFanBenWithPagination($token , $start = 0 , $last_retrieve_time= '' ,$page_size=200,$returnType = 0){
		try {
			//设置参数
			$reqParams["token"] = $token;
			$reqParams["token"] = str_replace('=','@@@',$reqParams["token"]);
			
			$reqParams = ['token'=>$token ,];

            $data = [
                'limit'=>$page_size ,
                'start'=>$start
            ];

			if (!empty($last_retrieve_time)){
                $data['since'] = date("Y-m-d",strtotime($last_retrieve_time));
			}
			
			//call wish api
			$result=WishProxyConnectKandengHelper::call_WISH_api("getProductsByPagination",$reqParams,['data'=>$data]);
				
			if ($returnType == 1) $result = json_encode($result);
			
			return $result;
		} catch (Exception $e) {
			//@todo write log
			$result['success'] = false;
			$result['message'] = print_r($e->getMessage());
			return $result;
		}
	
	}//end of getWishFanBen
	
	/**
	 +----------------------------------------------------------
	 * 通过 wish api 获取wish 范本数据
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $fanbenList		wish 范本信息
	 +----------------------------------------------------------
	 * @return				wish 范本数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/04/20				初始化
	 +----------------------------------------------------------
	 **/
	static public function saveOnlineFanben($site_id , &$fanbenList){
		try {
			$model = new WishFanben();
			$atrrList = $model->attributes;
			echo (__FUNCTION__)." entry !! ";//test kh
			$sub_model = new WishFanbenVariance();
			$sub_attrList = $sub_model->attributes;
			$capture_user_id = \Yii::$app->subdb->getCurrentPuid();
			$FanbenMapping = ['id'=>'wish_product_id'];
			$variantMapping = ['id'=>'variance_product_id'];
			$now_str = GetControlData::getNowDateTime_str();
			
			$IsSuccess = true;
			foreach($fanbenList as $row){
				unset($existProduct);  
				//$existProduct = WishFanben::findAll(['parent_sku'=>$row['parent_sku'],'site_id'=>$site_id]);
				$existProduct = WishFanben::findOne(['wish_product_id'=>$row['id']]);
				if (! empty($existProduct)) {
					//只有在complete的状态先重写范本信息 , 否则不更新
					if (in_array($existProduct['status'],['complete'])){
						//echo '\n '.$row['id'].' update !';
						$_model = $existProduct;
					}else{
						//echo '\n '.$row['id'].' skip !';
						continue;
					} 
				}else{
					// init 
					$data = ['site_id'=>$site_id,'capture_user_id'=>$capture_user_id , 'status'=>'online' , 'create_time'=>$now_str , 'update_time'=>$now_str];
					$_model = clone $model;
					//echo '\n '.$row['id'].' create !';
				}
					
				foreach($row as $key=>$value){
					//data formatter
					if (strtolower($key)=='extra_images'){
						unset($imagelist);
						$imagelist = explode("|", $value);
						
						for($i=0;$i<count($imagelist);$i++){
							$data['extra_image_'.($i+1)] = $imagelist[$i];
						}
						
					}else if (strtolower($key)=='tags'){
						$data[$key] = "";
						foreach ($value as $subtag){
							$data[$key] .= (empty($data[$key])?"":",").$subtag['Tag']['id'];
						}
							
					}else if (array_key_exists($key , $atrrList)){
						if (empty($FanbenMapping[$key]))
							$data[$key] = $value;
						else 
							$data[$FanbenMapping[$key]] = $value;
					}else if(strtolower($key) == 'review_status'){//add by chenbin 2015-12-2 wish lb_status 统一状态
                        if(in_array($value,['posting','pending'])){
                            $data['lb_status'] = 7;
                        } else if (in_array($value,['online','approved'])){
                            $data['lb_status'] = 8;
                        } else if (in_array($value,['rejected'])){
                            $data['lb_status'] = 9;
                        }
                    } else{
						//echo "<br> $key not in ";
					}
				}//end of data formatter 
					
				$_model->attributes = $data;
				if (! $_model->save()){
					//write log 
					$errorMsg = $_model->getErrors();
					if (is_array($errorMsg)) $errorMsg = json_encode($errorMsg);
					\Yii::error(['Listing',__CLASS__,__FUNCTION__,'Background',$errorMsg],"edb\global");
					$IsSuccess = false;
					echo (__FUNCTION__)." step 2E $errorMsg !! \n ";//test kh
				}else{
					//save variance
					if (!empty($row['variants'])){
						$sub_data = ['fanben_id'=>$_model->id , 'parent_sku'=>$_model->parent_sku];
						$is_enable = 1; //add by chenbin 2015-12-2 WISH商品记录变种是否存在下架信息 1不存在下架变种
						foreach($row['variants'] as $sub_row){
							
							$SubRow_addinfo = [];
							//$sub_attrList
							if (!empty($sub_row['Variant'])) $variantdata = $sub_row['Variant'];
							else $variantdata = $sub_row;
							
							$_sub_model = WishFanbenVariance::findOne(['variance_product_id'=>$variantdata['id']]);
							if (empty($_sub_model))
								$_sub_model = clone $sub_model;
							
							foreach($variantdata as $sub_key=>$sub_value){
								if (strtolower($sub_key)=='enabled'){
									if (is_bool($sub_value)){
										if ($sub_value)
											$sub_data['enable'] = 'Y';
										else
											$sub_data['enable'] = 'N';
									}else{
										
										if (strtolower($sub_value) == 'true')
											$sub_data['enable'] = 'Y';
										else
											$sub_data['enable'] = 'N';
									}
									
								}else if (array_key_exists($sub_key, $sub_attrList)){
									if (empty($variantMapping[$sub_key])){
										$sub_data[$sub_key] = $sub_value;
									}else{
										$sub_data[$variantMapping[$sub_key]] = $sub_value;
									}
								}else{
									$SubRow_addinfo[$sub_key] = $sub_value;
								}
							}//end of each variant field
							
							$sub_data['addinfo'] = json_encode($SubRow_addinfo);

                            if($sub_data['enable'] == 'N'){//add by chenbin 2015-12-2 WISH商品记录变种是否存在下架信息 2存在下架变种
                                $is_enable = 2;
                            }
							$_sub_model->attributes = $sub_data;
							//if (! $_sub_model->save()){  lolo20161021
							if (! $_sub_model->saveRaw()){
								$errorMsg = $_sub_model->getErrors();
								if (is_array($errorMsg)) $errorMsg = json_encode($errorMsg);
								echo "\n model error ".$errorMsg."\n";//test kh
								\Yii::error(['Listing',__CLASS__,__FUNCTION__,'Background',$errorMsg],"edb\global");
								$IsSuccess = false;
							}
						}//end of foreach variants
                        $_model->is_enable = $is_enable;
                        $_model->save();
					}//end of check variant is empty or not 
				}//end of save variants
			}//end of each fanben lsit
			return ['success'=>$IsSuccess, 'message'=>''];
		} catch (Exception $e) {
			//write log 
			$errorMsg = $e->getMessage();
			if (is_array($errorMsg)) $errorMsg = json_encode($errorMsg);
			echo "\n exception".$errorMsg."\n";//test kh
			\Yii::error(['Listing',__CLASS__,__FUNCTION__,'Background',$errorMsg],"edb\global");
			return ['success'=>false, 'message'=>$errorMsg];
		}
		echo (__FUNCTION__)." done !! ";//test kh
	}//end of saveOnlineFanben
	
	/**
	 +----------------------------------------------------------
	 * 在eagle 2 中的同步 商品队列中增加入wish 的同步请求 
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $fanbenList		wish 范本信息
	 +----------------------------------------------------------
	 * @return				wish 范本数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/04/20				初始化
	 +----------------------------------------------------------
	 **/
	static public function addSyncProductQueue($uid){
		try {
			//获取所有有效的wish 账号
			$query = SaasWishUser::find();
			$SAASWISHUSERLIST = $query->andWhere(" is_active='1' ")
			->andWhere(['uid'=>$uid])->all();
			
			foreach($SAASWISHUSERLIST as &$WishUser){
				//检查上次同步时间
				$last_time = $WishUser->last_product_retrieve_time;
				$now = TimeUtil::getNow();
				if (!empty($last_time)){
					
					$interval_hours = 1;
					//防止过频密调用 , 占用过多 资源
					if ((strtotime($now)-strtotime($last_time))< $interval_hours*3600){
						//echo "<br> too frequent to call api then skip it  <br>"; //test kh
						return ['success'=>false, 'message'=>TranslateHelper::t('距离上次同步小于').$interval_hours.TranslateHelper::t('小时 ,请稍候重试')];
						continue;
					}
				}
					
				//检查队列中的情况 (1.wish 平台  ;2.对应 的sellerid  ;3 status 为pending ,  Submit)
				$command = \Yii::$app->db->createCommand("select * from sync_product_api_queue where platform = 'wish'  and puid = '".$uid."' and seller_id='".$WishUser['store_name']."' and status in ('P','S')");
				
				$queueList = $command->queryAll();
					
				//队列中发现 该店还存在等待执行或者 是执行中 的队列则不添加
				if (count($queueList)>0){
					//echo "<br> exist queue =".count($queueList)." <br>"; //test kh
					return ['success'=>false, 'message'=>TranslateHelper::t('还有').count($queueList).TranslateHelper::t('个未执行的请求,请等待执行')];
					continue;
				}else{
					//echo "<br> add queue <br>"; //test kh 
					//未发现 已经存在的队列 , 则插入一个新队列请求
					$_model = new SyncProductApiQueue();
					$data = [
						'status'=>'P',
						'puid'=>$uid,
						'priority' => 5,
						'seller_id' => $WishUser['store_name'],
						'create_time' => $now,
						'update_time' => $now,
						'platform' => 'wish',
						'run_time' => 0,
						'addi_info' => '',
					];
					$_model->attributes = $data;
					if ($_model->save()){
						/**/
						$WishUser->last_product_retrieve_time = $now;
						$WishUser->save();
						
						//var_dump($WishUser->errors);
						return ['success'=>true, 'message'=>TranslateHelper::t('服务器已接受请求,稍候后更新数据')];
					}else{
						//var_dump($_model->errors); 
						$message = $_model->errors;
						if (is_array($message)) $message = json_encode($message);
						\Yii::error(['Wish',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
						return ['success'=>false, 'message'=>TranslateHelper::t('请求失败,请联系客服')];
					}
				}
					
			}//end of each wish account
		} catch (Exception $e) {
			//echo $e->getMessage();
			//\Yii::error($message)
			$message = $e->getMessage();
			if (is_array($message)) $message = json_encode($message);
			\Yii::error(['Wish',__CLASS__,__FUNCTION__,'Online',$message],"edb\global");
			return ['success'=>false, 'message'=>TranslateHelper::t('请求失败,请联系客服')];
			
		}
		
	}
		// end of addSyncProductQueue
	
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
	 *        	orderid 可以指定只做某个order id
	 * @param
	 *        	platform 必须指定平台，可选option: ebay,aliexpress,wish,dhgate
	 * +---------------------------------------------------------------------------------------------
	 * @return array('success'=true,'message'='') @invoking					MessageHelper::queueHandlerProcessing1();
	 *        
	 * +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author 		lkh		2015/7/1				初始化
	 * +---------------------------------------------------------------------------------------------
	 *        
	 */
	static public function queueHandlerProcessing1($sell_id = '', $platform = '') {
		global $CACHE;
		$queue_table = 'sync_product_api_queue'; 
		if (empty ( $CACHE ['JOBID'] ))
			$CACHE ['JOBID'] = "MS" . rand ( 0, 99999 );
		
		echo "\n".(__FUNCTION__)." entry \n";//test kh
		$logTime1 =  TimeUtil::getCurrentTimestampMS();
		$WriteLog = true;
		if ($WriteLog){
			$str = var_export([ 
					'List',
					__CLASS__,
					__FUNCTION__,
					'Background',
					"SyncProdQueue 0 Enter:" . $CACHE ['JOBID'] 
			],true);
			\Yii::info ( $str, "file" );
		}
		
		$rtn ['message'] = "";
		$rtn ['success'] = true;
		$now_str = date ( 'Y-m-d H:i:s' );
		$seedMax = 15;
		$seed = rand ( 0, $seedMax );
		$one_go_count = 50;
		
		// Step 0, 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		
		
		$JOBID = $CACHE ['JOBID'];
		$current_time = explode ( " ", microtime () );
		$start1_time = round ( $current_time [0] * 1000 + $current_time [1] * 1000 );
		\Yii::info ( "multiple_process_main step1 mainjobid=$JOBID","file" );
		
		$currentSyncProdQueueVersion = ConfigHelper::getGlobalConfig ( "list/syncProdQueueVersion", 'NO_CACHE' );
		if (empty ( $currentSyncProdQueueVersion ))
			$currentSyncProdQueueVersion = 0;
			
			// 如果自己还没有定义，去使用global config来初始化自己
		if (empty ( self::$syncProdQueueVersion ))
			self::$syncProdQueueVersion = $currentSyncProdQueueVersion;
			
			// 如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$syncProdQueueVersion != $currentSyncProdQueueVersion) {
		//	TrackingAgentHelper::extCallSum ( "", 0, true );
			$str = "Version new $currentSyncProdQueueVersion , this job ver " . self::$syncProdQueueVersion . " exits for using new version $currentSyncProdQueueVersion.";
			\Yii::info ( $str,"file" );
			exit ( $str );
		}
		$logTime2 =  TimeUtil::getCurrentTimestampMS();

		$str = ['Wish',__CLASS__,__FUNCTION__,'Background',(__FUNCTION__).'current version is ok ("'.$logTime2-$logTime1.'")'];
        \Yii::info(var_export($str,true),"file");
		//echo "\n".(__FUNCTION__)." current version is ok (".$logTime2-$logTime1.") \n";//test kh
		// step 1, try to get a pending request in queue, according to priority
		$coreCriteria = ' status="P" ';
		$coreCriteria .= " and platform='$platform'" . ($sell_id == '' ? '' : " and $sell_id=:sell_id");
		
		// 防止一个客户太多request，每次随机一个数，优先处理puid mod 5 ==seed 的这个
		$command = Yii::$app->get ( 'db' )->createCommand ( "select * from $queue_table force index (ssp) where $coreCriteria order by priority,id asc limit $one_go_count" );
		
		$command->bindValue ( ':sell_id', $sell_id, \PDO::PARAM_STR );
		
		$pendingOnes = $command->queryAll ();
		$logTime3 =  TimeUtil::getCurrentTimestampMS();
		// if no pending one found, return true, message = 'n/a';
		if (empty ( $pendingOnes )) {
			$rtn ['message'] = "n/a";
			$rtn ['success'] = true;
			// echo "No pending, idle 4 sec... ";
			$str = ['Wish',__CLASS__,__FUNCTION__,'Background',(__FUNCTION__).'pending is empty ("'.$logTime3-$logTime2.'")'];
            \Yii::info(var_export($str,true),"file");
			return $rtn;
		}
		
		$current_time = explode ( " ", microtime () );
		$start2_time = round ( $current_time [0] * 1000 + $current_time [1] * 1000 );
		\Yii::info ( "send_message_$platform step2 jobid=$JOBID,t2_t1=" . ($start2_time - $start1_time),"file" );
		//TrackingAgentHelper::extCallSum ( "List.SyncProdPickOne", $start2_time - $start1_time );
		
		$doneRequestIds = array ();
		$donePuidOrders = array ();
		$logTime4 =  TimeUtil::getCurrentTimestampMS();
		$str = ['Wish',__CLASS__,__FUNCTION__,'Background',(__FUNCTION__).'start to request  ("'.$logTime4-$logTime3.'")'];
        \Yii::info(var_export($str,true),"file");
		foreach ( $pendingOnes as $pendingRequest ) {
			$logTime4_1 =  TimeUtil::getCurrentTimestampMS();
			$pk_id = $pendingRequest['id'];
			$seller_id = $pendingRequest ['seller_id'];
			$puid = $pendingRequest ['puid'];
			$ret = true;
			if ($ret === false or $puid == 0) {
				// 异常情况
				$message = "切换到该用户puid $puid 数据库失败，请联系技术支援";
				
				$doneRequestIds ["F"] [$seller_id] = $seller_id;
				$addi_info ['error'] = $message;
				$doneRequestIds ["addi_info"] [$seller_id] = json_encode ( $addi_info );
				$str = ['Wish',__CLASS__,__FUNCTION__,'Background',(__FUNCTION__).$message];
                \Yii::info(var_export($str,true),"file");
				continue;
			} // end of switch puid failed
			
			/* 单进程, 不需要设置为多进程
			//防止多进程时抢占同一个资源
			$command = Yii::$app->db->createCommand ( "update $queue_table set status='S' ,update_time='$now_str'
					where id = '".$pendingRequest['id']."' and status='P' " );
			$affectRows = $command->execute ();
			
			//$affectRows == 0 说明 该请求已经补执行
			if ($affectRows == 0) continue;
			  */
			// step 1: call platform api to sync product detail
			if (! empty ( $pendingRequest ['addi_info'] )) {
				$addi_info = json_decode ( $pendingRequest ['addi_info'], true );
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
				try {
					$logTime4_2 =  TimeUtil::getCurrentTimestampMS();
					$str =  "start-to-get-wish-fanben puid=$puid ".(__FUNCTION__)."  (".($logTime4_2-$logTime4_1).") ";//test kh
                    \Yii::info($str,"file");
					$wishAccount = SaasWishUser::find()
					->where(['uid'=>$puid , 'store_name'=>$seller_id , 'is_active'=>1])
					->asArray()
					->one();
					$logTime4_3 =  TimeUtil::getCurrentTimestampMS();

				//	$str =  "\n  puid=$puid ".(__FUNCTION__)." start-to-get-wish-fanben (".($logTime4_3-$logTime4_1).") \n";//test kh
				//	\Yii::info($str,"file");
						
					
					$tmpMsg= '';
					if (!empty($wishAccount['last_product_success_retrieve_time'])){
						$lastSuccessRetrieveTime = $wishAccount['last_product_success_retrieve_time'];
					}else{
						$lastSuccessRetrieveTime = '';
					}
					$GetFanbenResult = self::getAllWishFanbenByPagination($wishAccount['token'],$lastSuccessRetrieveTime);
                  //  \Yii::info("wish_result:".var_export($GetFanbenResult,true),"file");
                    $logTime4_4 =  TimeUtil::getCurrentTimestampMS();
					$str = "end-of-get-wish-faben puid=$puid ".(__FUNCTION__)." lastSuccessRetrieveTime=$lastSuccessRetrieveTime $tmpMsg (".($logTime4_4-$logTime4_3).") \n";//test kh
                    \Yii::info($str,"file");
				} catch (Exception $e) {
					$errorMsg = $e->getMessage();
					if (is_array($errorMsg)){
						$errorMsg = json_encode($errorMsg);
					}
					$GetFanbenResult = array('success'=>false,'message'=>'wish API Failed:'.$errorMsg);
				}
				
				//echo json_encode($GetFanbenResult,true);
				$logTime4_5 =  TimeUtil::getCurrentTimestampMS();
				
				//检查 api 返回数据
				if (empty($GetFanbenResult['success'])){
					//wish api 返回出错  了
					$doneRequestIds["F"][$pk_id] = $pk_id;
						
					if (!isset($GetFanbenResult['message']))
						$GetFanbenResult['message'] = '';
						
					$addi_info['error'] = $GetFanbenResult['message'];
					$doneRequestIds["addi_info"][$pk_id] = json_encode($addi_info);
					//$donePuidOrders["F"][$puid][ $pendingMessage['msg_id'] ] = $GetFanbenResult['message'];
				}else{
					if (!empty($GetFanbenResult['product'])){
						$str = "before-saveOnlineFanben puid=$puid ".(__FUNCTION__)." wish fanben count=".count($GetFanbenResult['product']);//test kh
                        \Yii::info($str,"file");
						self::saveOnlineFanben($wishAccount['site_id'],$GetFanbenResult['product']);
						$doneRequestIds["C"][$pk_id] = $pk_id;
					}else{
						echo "\n".(__FUNCTION__)." wish fanben count = (0) \n";//test kh
					}
					
				}
				
				$logTime4_6 =  TimeUtil::getCurrentTimestampMS();
				$str= "after-saveOnlineFanben puid=$puid".(__FUNCTION__)." save wish fanben (".($logTime4_6-$logTime4_5).") ";//test kh
                \Yii::info($str,"file");
			}//end of platform = wish 
			
			//$donePuidOrdersId [$puid] [$pendingRequest ['seller_id']] = $pendingRequest ['order_id'];
			$logTime5 =  TimeUtil::getCurrentTimestampMS();
			$current_time = explode ( " ", microtime () );
			$start2_time = round ( $current_time [0] * 1000 + $current_time [1] * 1000 );
		//	TrackingAgentHelper::extCallSum ( 'Listing.SyncProd.' . $platform, $start2_time - $start1_time );
			
			// step 2: set this request id is complete
		} // end of each pendingMessage
		  
		$logTime6 =  TimeUtil::getCurrentTimestampMS();
		// step 3, bulk update all complete flag
		$id_array = isset ( $doneRequestIds ["C"] ) ? $doneRequestIds ["C"] : array ();
		$now_str = date ( 'Y-m-d H:i:s' );
		if (! empty ( $id_array )) {
			
			$command = Yii::$app->db->createCommand ( "update $queue_table set status='C' ,update_time='$now_str'
					where id in (" . implode ( ",", $id_array ) . ")" );
			$affectRows = $command->execute ();
			
			//update product retrieve time
			$command = Yii::$app->db->createCommand ( "update saas_wish_user u ,  sync_product_api_queue q set u.last_product_success_retrieve_time = q.update_time ".
					" where  u.is_active = 1 and u.uid = q.puid AND u.store_name = q.seller_id and q.platform ='$platform' and q.status='C' ".
					" and q.id in (" . implode ( ",", $id_array ) . ")" );
					$affectRows = $command->execute ();
		}
		$logTime7 =  TimeUtil::getCurrentTimestampMS();
		//echo "\n".(__FUNCTION__)." update queue status = c (".($logTime7-$logTime6).") \n";//test kh
		$lastPuid = 0;
		  
		// step 4, update each failed, with error request in addi info
		if (isset ( $doneRequestIds ["F"] )) {
			foreach ( $doneRequestIds ["F"] as $req_id ) {
				$command = Yii::$app->db->createCommand ( "update $queue_table set status='F',   update_time='$now_str',
		addi_info=:addi_info  where id  = $req_id" );
				$command->bindValue ( ':addi_info', $doneRequestIds ["addi_info"] [$req_id], \PDO::PARAM_STR );
				$affectRows = $command->execute ();
			}
		}
		$logTime8 =  TimeUtil::getCurrentTimestampMS();
		//echo "\n".(__FUNCTION__)." update queue status = f (".($logTime8-$logTime7).") \n";//test kh
		return $rtn;
	}//end of queueHandlerProcessing1
	
	
	public static function getDummyPoductPagination(){
		self::$dummyAllProduct =<<<dummy
{"success":true,"message":"Done With http Code 200","proxyResponse":{"wishResponse":{"message":"","code":0,"data":[{"Product":{"main_image":"http:\/\/contestimg.wish.com\/api\/webimage\/5488fdb958ff0f101a66120b-original.jpg","is_promoted":"False","description":"Estimated Delivery Time  7-20","name":"Leather Case for Apple iPhone 6 (4.7&quot;) with Floral Pattern and Stand Feature - Blue","tags":[{"Tag":{"id":"case","name":"case"}},{"Tag":{"id":"phonecase","name":"phone case"}},{"Tag":{"id":"ipone6case","name":"ipone 6 case"}},{"Tag":{"id":"mobilephonebagscase","name":"mobile phone bags&amp;cases"}}],"review_status":"approved","upc":"000000000000","extra_images":"http:\/\/contestimg.wish.com\/api\/webimage\/5488fdb958ff0f101a66120b-1-original.jpg","auto_tags":[{"Tag":{"id":"blue","name":"Blues"}},{"Tag":{"id":"apple","name":"Apple"}},{"Tag":{"id":"leather","name":"leather"}},{"Tag":{"id":"iphone","name":"iphone"}},{"Tag":{"id":"leathercase","name":"Leather Cases"}},{"Tag":{"id":"iphone5","name":"iphone 5"}},{"Tag":{"id":"iphone4","name":"Iphone 4"}},{"Tag":{"id":"floral","name":"Floral"}},{"Tag":{"id":"stand","name":"Stand"}}],"number_saves":"0","variants":[{"Variant":{"sku":"CS8614102204C","msrp":"5.99","product_id":"5488fdb958ff0f101a66120b","all_images":"","price":"5.99","shipping_time":"7-21","enabled":"True","id":"5488fdb958ff0f101a66120d","shipping":"1.99","inventory":"20"}}],"parent_sku":"uus170","id":"5488fdb958ff0f101a66120b","number_sold":"0"}},{"Product":{"main_image":"http:\/\/contestimg.wish.com\/api\/webimage\/547fda2b205fea0f1e12e126-original.jpg","is_promoted":"False","description":"Estimated Delivery Time 7-14","name":"Newfashioned Polka Dot Leather Case for Apple iPhone 6 plus with Interactive View Window and Stand Feature - Rose","tags":[{"Tag":{"id":"mobilephonebagscase","name":"mobile phone bags&amp;cases"}},{"Tag":{"id":"rose","name":"Rose"}},{"Tag":{"id":"iphone6pluscase","name":"iphone 6 plus case"}}],"review_status":"approved","upc":"000000000000","extra_images":"","auto_tags":[{"Tag":{"id":"case","name":"case"}},{"Tag":{"id":"apple","name":"Apple"}},{"Tag":{"id":"leather","name":"leather"}},{"Tag":{"id":"polka","name":"Polkas"}},{"Tag":{"id":"polkadot","name":"polka dot"}},{"Tag":{"id":"stand","name":"Stand"}},{"Tag":{"id":"leathercase","name":"Leather Cases"}},{"Tag":{"id":"iphone5","name":"iphone 5"}},{"Tag":{"id":"iphone4","name":"Iphone 4"}},{"Tag":{"id":"iphone","name":"iphone"}}],"number_saves":"3","variants":[{"Variant":{"sku":"CS8514102612F","msrp":"12.99","product_id":"547fda2b205fea0f1e12e126","all_images":"","price":"12.99","shipping_time":"7-21","enabled":"True","id":"547fda2b205fea0f1e12e128","shipping":"1.99","inventory":"20"}}],"parent_sku":"CS8514102612F","id":"547fda2b205fea0f1e12e126","number_sold":"1"}}],"paging":{"next":"https:\/\/merchant.wish.com\/api\/v1\/product\/multi-get?start=3&limit=2&key=JHBia2RmMiQxMDAkQzZHVThyNDNab3dSNGh5REVHSk1pUSQwNzZ3d01NdFBhLllKeE9LRk44U0pSTndLQ2M%3D","previous":"https:\/\/merchant.wish.com\/api\/v1\/product\/multi-get?start=0&limit=2&key=JHBia2RmMiQxMDAkQzZHVThyNDNab3dSNGh5REVHSk1pUSQwNzZ3d01NdFBhLllKeE9LRk44U0pSTndLQ2M%3D"}},"message":"Done With http Code 200","success":true}}
dummy;
		
		return json_decode(self::$dummyAllProduct,true);	
	}
	
	public static function getDummyPoductEmptyPagination(){
		self::$dummyAllProduct =<<<dummy
{"wishResponse":{"message":"","code":0,"data":[],"paging":{"previous":"https:\/\/merchant.wish.com\/api\/v1\/product\/multi-get?start=501&limit=500&key=JHBia2RmMiQxMDAkQzZHVThyNDNab3dSNGh5REVHSk1pUSQwNzZ3d01NdFBhLllKeE9LRk44U0pSTndLQ2M%3D"}},"message":"Done With http Code 200","success":true}
dummy;
	
		return json_decode(self::$dummyAllProduct,true);
	}	
	
	
	public static function getDummyAllProduct(){
		self::$dummyAllProduct =<<<dummy
{
    "success": true,
    "message": "Done With http Code 200",
    "proxyResponse": {
        "success": true,
        "message": "",
        "product": [
            {
                "main_image": "http://contestimg.wish.com/api/webimage/54d1981e09eaa31ee07bcde2-original.jpg",
                "is_promoted": "False",
                "description": "福特在北美车展的新车阵容让全球车迷沸腾，以至于新福克斯RS跳票了大家都不在乎了。福特新福克斯RS在2月3日正式发布，实车将在3月的日内瓦车展亮相。高尔夫R蹦跶不了几天了。",
                "name": "新福克斯RS官图 4.5s破百/高尔夫R接招",
                "tags": [
                    {
                        "Tag": {
                            "id": "car",
                            "name": "Cars"
                        }
                    },
                    {
                        "Tag": {
                            "id": "focu",
                            "name": "Focus"
                        }
                    },
                    {
                        "Tag": {
                            "id": "cool",
                            "name": "cool"
                        }
                    }
                ],
                "brand": "ford",
                "review_status": "approved",
                "extra_images": "",
                "auto_tags": [],
                "number_saves": "0",
                "variants": [
                    {
                        "sku": "Focus1_A",
                        "product_id": "54d1981e09eaa31ee07bcde2",
                        "all_images": "",
                        "price": "2000.0",
                        "enabled": "False",
                        "shipping": "50.0",
                        "color": "red",
                        "inventory": "20",
                        "shipping_time": "5-10",
                        "id": "54d1981e09eaa31ee07bcde4",
                        "msrp": "3000.0",
                        "size": "L"
                    },
                    {
                        "sku": "Focus1_B",
                        "product_id": "54d1981e09eaa31ee07bcde2",
                        "all_images": "",
                        "price": "2000.0",
                        "enabled": "False",
                        "shipping": "50.0",
                        "color": "blue",
                        "inventory": "33",
                        "shipping_time": "5-10",
                        "id": "54d19baa0c1a481ee4bc3aa1",
                        "msrp": "2000.0",
                        "size": "L"
                    },
                    {
                        "sku": "Focus1_C",
                        "product_id": "54d1981e09eaa31ee07bcde2",
                        "all_images": "",
                        "price": "2001.0",
                        "enabled": "False",
                        "shipping": "50.0",
                        "color": "white",
                        "inventory": "55",
                        "shipping_time": "5-10",
                        "id": "54d19babde230c1e52da85fa",
                        "msrp": "2001.0",
                        "size": "L"
                    },
                    {
                        "sku": "Focus1_D",
                        "product_id": "54d1981e09eaa31ee07bcde2",
                        "all_images": "",
                        "price": "2002.0",
                        "enabled": "False",
                        "shipping": "50.0",
                        "color": "yellow",
                        "inventory": "66",
                        "shipping_time": "5-10",
                        "id": "54d1af9409eaa31ee17bd232",
                        "msrp": "2002.0",
                        "size": "L"
                    },
                    {
                        "sku": "Focus1_E",
                        "product_id": "54d1981e09eaa31ee07bcde2",
                        "all_images": "",
                        "price": "2003.0",
                        "enabled": "False",
                        "shipping": "50.0",
                        "color": "green",
                        "inventory": "77",
                        "shipping_time": "5-10",
                        "id": "54d1b576dc8ce11f949399dd",
                        "msrp": "2003.0",
                        "size": "L"
                    }
                ],
                "parent_sku": "Focus1",
                "id": "54d1981e09eaa31ee07bcde2",
                "number_sold": "0"
            }
        ]
    }
}
dummy;
		/*
		var_dump((self::$dumpAllProduct));
		$constants = get_defined_constants(true);
		$json_errors = array();
		foreach ($constants["json"] as $name => $value) {
			if (!strncmp($name, "JSON_ERROR_", 11)) {
				$json_errors[$value] = $name;
			}
		}
		var_dump(json_decode(self::$dumpAllProduct,true));
		var_dump($json_errors[json_last_error()]) ;
		*/
		return json_decode(self::$dummyAllProduct,true);
	}
	
	/**
	 +----------------------------------------------------------
	 * cron job 获取 wish faben
	 +----------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------
	 * @param $fanbenList		wish 范本信息
	 +----------------------------------------------------------
	 * @return				wish 范本数据
	 +----------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2015/04/20				初始化
	 +----------------------------------------------------------
	 **/
	static public function cronAutoFetchWishFanben(){
		//@todo write log 
		$query = SaasWishUser::find();
		$SAASWISHUSERLIST = $query->andWhere(" is_active='1' ")->asArray()->all();
		//fetch wish faben by each wish account
		foreach($SAASWISHUSERLIST as $wishAccount ){
			//var_dump($wishAccount);
			//check up token 
			if (empty($wishAccount['token'])){
				//no token , then skip it 
				//@todo write log
				continue;
			}
			
			$uid = $wishAccount['uid'];
			if (empty($uid)){
				//异常情况
				echo "uid:0  exception!!!! \n";
				//@todo write log
				//SysLogHelper::GlobalLog_Create("Order", __CLASS__,__FUNCTION__,"","site id :".$wishAccount['site_id']." uid:0","error");
				return false;
			}
			$ret = self::changeDBPuid($uid);
			
			if (empty($ret)){
				//failure to change db , then skip it 
				continue;
			}
			
			//fetch wish fanben 
			$GetFanbenResult = self::getWishFanBen($wishAccount['token']);
			echo json_encode($GetFanbenResult,true);
			
			//检查 api 返回数据
			if (empty($GetFanbenResult['success'])){
				//wish api 返回出错  了
				//@todo write log
			}else{
				//wish api 返回 成功了
				if (empty($GetFanbenResult['proxyResponse']['success'])){
					//proxy not actived
					//@todo write log
					
					//var_dump($GetFanbenResult['proxyResponse']['success']);
				}else{
					self::saveOnlineFanben($wishAccount['site_id'],$GetFanbenResult['proxyResponse']['product']);
				}
			}
			
		}//end of each wish account 
	}//end of cronAutoFetchWishFanben
	
}