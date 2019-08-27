<?php
namespace eagle\modules\dhgate\apihelpers;

use \Yii;
use eagle\models\SaasAliexpressAutosync;
use common\api\aliexpressinterface\AliexpressInterface_Auth;
use common\api\aliexpressinterface\AliexpressInterface_Api;
use eagle\models\QueueAliexpressGetorder;
use common\api\aliexpressinterface\AliexpressInterface_Helper;
use eagle\modules\util\helpers\ConfigHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\models\SaasDhgateAutosync;
use eagle\models\QueueDhgateGetorder;
use common\api\dhgateinterface\Dhgateinterface_Api;

/**
 +------------------------------------------------------------------------------
 * Dhgate 数据同步类
 +------------------------------------------------------------------------------
 */
class SaasDhgateAutoSyncApiHelper {
	public static $cronJobId=0;
	private static $dhgateGetOrderListVersion = null;
	/**
	 * @return the $cronJobId
	 */
	public static function getCronJobId() {
		return self::$cronJobId;
	}
	
	/**
	 * @param number $cronJobId
	 */
	public static function setCronJobId($cronJobId) {
		self::$cronJobId = $cronJobId;
	}
	/**
	 * 同步Aliexpress订单列表
	 * @author million 2015-04-03
	 * 88028624@qq.com
	 * $time 间隔时间(不同类型的订单同步的时间间隔不同，半个小时1800秒或者一天86400秒)
	 * $type 订单类型
	 * 
	 * 此方法为老的同步算法，弃用
	 */
	/* static function getOrderList($type,$time=86400){
		$connection=Yii::$app->db;
		$t = time()-$time;
		if ($type=="FINISH"){//首次绑定账号需要一次同步已完成的订单，不需要反复同步，因为数据量太大
			$command=$connection->createCommand('select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` <> 1 AND `type`="'.$type.'" AND `last_time`=0 order by `last_time` ASC');
		}else{
			$command=$connection->createCommand('select `id` from  `saas_aliexpress_autosync` where `is_active` = 1 AND `status` <> 1 AND `type`="'.$type.'" AND last_time < '.$t.' order by `last_time` ASC');
		}
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			$SAA_obj = SaasAliexpressAutosync::findOne($row['id']);
			echo $SAA_obj->sellerloginid."\n";
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ( $SAA_obj->sellerloginid );
			if ($a) {
				$SAA_obj->status = 1;
				$SAA_obj->save ();
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $SAA_obj->sellerloginid );
				//获取访问token失败
				if ($access_token === false){
					echo $SAA_obj->sellerloginid . 'not getting access token!' . "\n";
					\Yii::info($SAA_obj->sellerloginid . 'not getting access token!' . "\n");
					$SAA_obj->message = $SAA_obj->sellerloginid . ' not getting access token!';
					$SAA_obj->status = 3;
					$SAA_obj->times += 1;
					$SAA_obj->last_time = time ();
					$SAA_obj->update_time = time ();
					$SAA_obj->save ();
					continue;
				}
				$api->access_token = $access_token;
				$page = 1;
				$pageSize = 50;
				// 是否全部同步完成
				$success = true;
				do {
					// 接口传入参数
					$param = array (
							'page' => $page,
							'pageSize' => $pageSize,
							//'createDateStart' => date ( "m/d/Y H:i:s",$start_time ),
							//'createDateEnd' => date ( "m/d/Y H:i:s", $end_time ),
							'orderStatus'=>$type,
					);
					// 调用接口获取订单列表
					$result = $api->findOrderListQuery ( $param );
					//echo print_r ( $result, 1 );
					// 判断是否有订单
					if (isset ( $result ['totalItem'] )) {
						echo $result ['totalItem']."\n";
						if ($result ['totalItem'] > 0) {
							// 保存数据到同步订单详情队列
							foreach ( $result ['orderList'] as $one ) {
								// 订单产生时间
								$gmtCreate_str = substr ( $one ['gmtCreate'], 0, 14 );
								$gmtCreate = strtotime ( $gmtCreate_str );
								$QAG_obj = QueueAliexpressGetorder::findOne(['orderid'=>$one ['orderId']]);
								if (isset ( $QAG_obj )) {
									$QAG_obj->order_status = $type;
									$QAG_obj->order_info = json_encode ( $one );
									$QAG_obj->update_time = time ();
									$QAG_obj->save ();
								} else {
									$QAG_obj = new QueueAliexpressGetorder ();
									$QAG_obj->uid = $SAA_obj->uid;
									$QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
									$QAG_obj->aliexpress_uid = $SAA_obj->aliexpress_uid;
									$QAG_obj->status = 0;
									$QAG_obj->order_status = $type;
									$QAG_obj->orderid = $one ['orderId'];
									$QAG_obj->times = 0;
									$QAG_obj->order_info = json_encode ( $one );
									$QAG_obj->last_time = 0;
									$QAG_obj->gmtcreate = $gmtCreate;
									$QAG_obj->create_time = time ();
									$QAG_obj->update_time = time ();
									$QAG_obj->save ();
								}
							}
						}
					} else {
						$success = false;
					}
		
					$page ++;
					$total = isset($result ['totalItem'])?$result ['totalItem']:0;
					$p = ceil($total/50);
				} while ( $page <= $p );
				// 是否全部同步成功
				if ($success) {
					$SAA_obj->last_time = time();
					$SAA_obj->status = 2;
					$SAA_obj->times = 0;
					$SAA_obj->save ();
				} else {
					$SAA_obj->message = $result ['error_message'];
					$SAA_obj->last_time = time();
					$SAA_obj->status = 3;
					$SAA_obj->times += 1;
					$SAA_obj->save ();
				}
			} else {
				echo $SAA_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
				$SAA_obj->message = $SAA_obj->sellerloginid . ' Unauthorized or expired!';
				$SAA_obj->last_time = time();
				$SAA_obj->status = 3;
				$SAA_obj->times += 1;
				$SAA_obj->save ();
			}
		}
	} */
	
	
	/**
	 * 同步Aliexpress订单
	 * @author million 2015-04-03
	 * 88028624@qq.com
	 * $time 间隔时间(不同类型的订单同步的时间间隔不同，半个小时1800秒或者一天86400秒)
	 * $order_status 订单状态
	 * 
	 * 此方法为老的同步算法，弃用
	 */
	/* static function getOrder($order_status,$time=86400){
		// 同步订单
		$connection=Yii::$app->db;
		$t = time()-$time;
		$command=$connection->createCommand('select `id`,`sellerloginid`,`orderid` from  `queue_aliexpress_getorder` where `status` <> 1 AND order_status="'.$order_status.'" AND last_time < '.$t.' order by `last_time` ASC');
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			echo $row['orderid']."\n";
			$last_time = time();
			$QAG_obj = QueueAliexpressGetorder::findOne($row['id']);
			// 检查授权是否过期或者是否授权,返回true，false
			$a = AliexpressInterface_Auth::checkToken ($row['sellerloginid'] );
			if ($a) {
				$api = new AliexpressInterface_Api ();
				$access_token = $api->getAccessToken ( $row['sellerloginid'] );
				$api->access_token = $access_token;
				// 接口传入参数速卖通订单号
				$param = array (
						'orderId' => $row['orderid']
				);
				// 调用接口获取订单列表
				$result = $api->findOrderById ( $param );
				//echo print_r ( $result, 1 );
				// 是否同步成功
				if (isset ( $result ['error_message'] ) || empty ( $result )) {
					$QAG_obj->message = $result ['error_message'];
					$QAG_obj->status = 3;
					$QAG_obj->times += 1;
					$QAG_obj->last_time = $last_time;
					$QAG_obj->update_time = time ();
					$QAG_obj->save ();
				} else {
					// 同步成功保存数据到订单表
					if (true){
						$r = AliexpressInterface_Helper::saveAliexpressOrder ( $QAG_obj, $result );
						// 判断是否付款并且保存成功,是则删除数据，否则继续同步
						if ($r ['success'] == 0 && $result ['orderStatus']=='FINISH') {
							$QAG_obj->delete ();
						} else {
							if ($r ['success'] == 1) {
								$QAG_obj->status = 3;
								$QAG_obj->times += 1;
								$QAG_obj->message = "速卖通订单" . $QAG_obj->orderid . "保存失败".$r ['message'];
							} else {
								$QAG_obj->status = 2;
								$QAG_obj->times = 0;
							}
							$QAG_obj->order_status = $result ['orderStatus'];
							$QAG_obj->last_time = $last_time;
							$QAG_obj->update_time = time ();
							$QAG_obj->save ();
						}
					}
				}
			} else {
				echo $QAG_obj->sellerloginid . ' Unauthorized or expired!' . "\n";
				$QAG_obj->message = $QAG_obj->sellerloginid . ' Unauthorized or expired!';
				$QAG_obj->status = 3;
				$QAG_obj->times += 1;
				$QAG_obj->last_time = $last_time;
				$QAG_obj->update_time = time ();
				$QAG_obj->save ();
			}
		}
	} */
	
	
	
	/**
	 * 该进程判断是否需要退出
	 * 通过配置全局配置数据表ut_global_config_data的Order/dhgateGetOrderVersion 对应数值
	 * 
	 * @return  true or false
	 */
	private static function checkNeedExitNot(){
		$dhgateGetOrderVersionFromConfig = ConfigHelper::getGlobalConfig("Order/dhgateGetOrderVersion",'NO_CACHE');
		if (empty($dhgateGetOrderVersionFromConfig))  {
			//数据表没有定义该字段，不退出。
		//	self::$dhgateGetOrderListVersion ="v0";
			return false;
		}
		
		//如果自己还没有定义，去使用global config来初始化自己
		if (self::$dhgateGetOrderListVersion===null)	self::$dhgateGetOrderListVersion = $dhgateGetOrderVersionFromConfig;
			
		//如果自己的version不同于全局要求的version，自己退出，从新起job来使用新代码
		if (self::$dhgateGetOrderListVersion <> $dhgateGetOrderVersionFromConfig){			
			echo "Version new $dhgateGetOrderVersionFromConfig , this job ver ".self::$dhgateGetOrderListVersion." exits \n";
			return true;
					 
		}
		
		return false;
	} 
	

	public static  function call_dh_api($url , $getParams=array() , $postParams=array() , $auth = false ){
		$port = null;
		$scheme = "";
		if($auth){
			$scheme = 'https://';
			$port = 443;
		} else {
			$scheme = 'http://';
			$port = 80;
		}
	
		$handle = curl_init();
	
		$getKeyValue = array();
		foreach($getParams as $key=>$value){
			$getKeyValue[] =  "$key=".urlencode(trim($value));
		}
		$url .= '?'.implode("&", $getKeyValue);
	
		if(!empty($postParams)){
			curl_setopt($handle, CURLOPT_POST, 1);
			curl_setopt($handle, CURLOPT_POSTFIELDS, $postParams );
		}
	
		if($auth){
			curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, 0);
		}
	
		// 		echo $scheme . $url;
		curl_setopt($handle, CURLOPT_URL, $scheme . $url);
		curl_setopt($handle, CURLOPT_PORT, $port);
		curl_setopt($handle, CURLOPT_HEADER, false);
		curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($handle,  CURLOPT_TIMEOUT, 700);
	
		//  output  header information
		// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
			
		/* Get the HTML or whatever is linked in $url. */
		$response = curl_exec($handle);
	
		// 		var_dump($response);
		/* Check for 404 (file not found). */
		$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		$rtn['success'] = true;
		$rtn['message'] = '';
	
		if ($httpCode == '200' ){
			$rtn['response'] = json_decode($response , true);
		}else{
			$rtn['message'] .= "Failed for get access code , Got error respond code $httpCode from Proxy";
			$rtn['success'] = false ;
			$rtn['response'] = "";
		}
	
		curl_close($handle);
		return $rtn;
	}
	
	/**
	 * 把dhgate返回的订单header的信息保存到订单详情同步表QueueDhgateGetorder
	 * @param unknown $dhgateOrderHeader--  dhgate返回的订单header信息，array形式
	 * @param unknown $SAA_obj-- 订单list同步表SaasDhgateAutosync的对象
	 * @param unknown $type--订单详情同步表QueueDhgateGetorder记录对应的类型
	 *  FRIST = 1; FINISH = 2; NEWORDER = 3; UPDATEORDER = 4; NOFINISH = 5;	
	 * @return true or false
	 */
	private static function _insertOrUpdateOrderQueue(&$dhgateOrderHeader,$SAA_obj,$type){
		// 订单产生时间
		//$gmtCreate_str = substr ( $dhgateOrderHeader ['gmtCreate'], 0, 14 );
		$gmtCreate_str = $dhgateOrderHeader ['startedDate'];
		$gmtCreate = strtotime ( $gmtCreate_str );
		 
		$QAG_obj = QueueDhgateGetorder::findOne(['orderid'=>$dhgateOrderHeader ['orderNo']]);
		if (isset ( $QAG_obj )) {
			$QAG_obj->order_status = $dhgateOrderHeader['orderStatus'];
			$QAG_obj->order_info = json_encode ( $dhgateOrderHeader );
			$QAG_obj->update_time = time ();
			$QAG_obj->save ();
		} else {
			$QAG_obj = new QueueDhgateGetorder ();
			$QAG_obj->uid = $SAA_obj->uid;
			//	$QAG_obj->sellerloginid = $SAA_obj->sellerloginid;
			$QAG_obj->dhgate_uid = $SAA_obj->dhgate_uid;
			$QAG_obj->status = 0;
			$QAG_obj->type = $type;
			$QAG_obj->order_status = $dhgateOrderHeader['orderStatus'];
			$QAG_obj->orderid = $dhgateOrderHeader ['orderNo'];
			$QAG_obj->times = 0;
			$QAG_obj->order_info = json_encode ( $dhgateOrderHeader );
			$QAG_obj->last_time = 0;
			$QAG_obj->gmtcreate = $gmtCreate;
			$QAG_obj->create_time = time ();
			$QAG_obj->update_time = time ();
			$QAG_obj->save ();
		}
		return true;
	}
	
	/**
	 * 先判断是否真的抢到待处理账号
	 * @param  $dhgateAutosyncId  -- saas_dhgate_autosync表的id
	 * @return null或者$SAA_obj , null表示抢不到记录 
	 */
 	private static function _lockDhgateAutosyncRecord($dhgateAutosyncId){
 		$connection=Yii::$app->db;
 		$command = $connection->createCommand("update saas_dhgate_autosync set status=1 where id =". $dhgateAutosyncId." and status<>1 ") ;
 		$affectRows = $command->execute();
 		if ($affectRows <= 0)	return null; //抢不到---如果是多进程的话，有抢不到的情况
 		
 		// 抢到记录
 		$SAA_obj = 	SaasDhgateAutosync::findOne($dhgateAutosyncId);
 		
 		return $SAA_obj;
 			
	}
	
	/**
	 * 同步Dhgate订单120天内没完成的订单
	 * @author million 2015-04-03
	 * 88028624@qq.com
	 */
	public static function getOrderListByDay120(){
		echo "++++++++++++getOrderListByDay120 \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;		
		
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=Yii::$app->db;
		#########################		
		$type = 'day120';
		$hasGotRecord=false;//是否抢到账号
		
		//2. 从账户同步表（订单列表同步表）saas_dhgate_autosync 提取带同步的账号。          status--- 0 没处理过; 2--已完成; 3--上一次执行有问题;
		$command=$connection->createCommand('select `id` from  `saas_dhgate_autosync` where `is_active` = 1 AND `status` in (0,3) AND `times` < 10 AND `type`="'.$type.'" order by `last_time` ASC limit 5');
		#################################
		// 订单详情同步表QueueDhgateGetorder记录对应的类型  FIRST = 1; FINISH = 2; NEWORDER = 3; UPDATEORDER = 4; NOFINISH = 5;		
		$queueDhgateGetorderType=QueueDhgateGetorder::FIRST;
		
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockDhgateAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
			
			\Yii::info("dhgate_get_order_list_by_day120 gotit jobid=$backgroundJobId start","file");			
			$hasGotRecord=true;  // 抢到记录
			
			//3. 调用dhgate的api来获取对应账号的订单列表信息，并把dhgate返回的所有的订单header的信息保存到订单详情同步表QueueDhgateGetorder
			//3.1 整理请求参数
			$start_time = $SAA_obj->binding_time-(86400*120);
			$end_time = $SAA_obj->binding_time;
			$createDateStart=date ( "Y-m-d H:i:s",$start_time );
			$createDateEnd=date ( "Y-m-d H:i:s", $end_time );
			$querytimeType=1;
			$pageNo=1;
			$pageSize = 200;
			$success=true;
			
			$appParams = array();
			$appParams["querytimeType"] = $querytimeType;
			$appParams["startDate"] = $createDateStart;
			$appParams["endDate"] = $createDateEnd;
			$appParams['orderStatus'] = '101003,102001,103001,105001,105002,105003,105004,103002,101009,106001,106002,106003,102006,102007,102111';  //敦煌未完成的订单状态
			
			$dhgateinterface_Api = new Dhgateinterface_Api();
			
			do{
				//3.2  调用dhgate的api来获取对应账号的订单列表信息。 由于有每页订单个数限制，这里需要循环多页面
				$appParams["pageNo"] = $pageNo;
				$appParams["pageSize"] = $pageSize;
				 
				//查询订单列表getOrderList
				$response = $dhgateinterface_Api->getOrderList($SAA_obj->dhgate_uid, $appParams);
				\Yii::info(print_r($response,true),"file");
				
				//TODO ---
				//3.3 根据$response的信息进行 异常判断. --跳出循环
				// access Token 过期
				// 返回error code
				// 返回值没有["response"]["orderBaseInfoList"]
				// 返回值没有["response"]["count"]
				if ($response['success'] === false){
				    $success=false;
				   	$errorMessage=$response['error_message'];
				    break;
				}
				
				if (isset ( $response ['response'] )) {
					if ($response['response']['status']['code'] != "00000000"){
						
						//当返回109时代表查询不到记录
						if ($response['response']['status']['code'] == "109")
							$success=true;
						else
							$success=false;
						$errorMessage=$response['response']['status']['message'];
						break;
					}
					
					$resultCount=$response["response"]["count"];
					$resultPages=$response["response"]["pages"];
						
					//3.4 返回结果正常，需要把dhgate返回的所有的订单header的信息保存到订单详情同步表QueueDhgateGetorder
					if ($resultCount>0){
						$orderList=$response["response"]["orderBaseInfoList"];
						foreach($orderList as $one){
							self::_insertOrUpdateOrderQueue($one, $SAA_obj, $queueDhgateGetorderType);
						}
					}
				}else{
					$success=false;
				}
				
				$pageNo++;
			} while($pageNo<=$resultPages);
			
            //4. 保存 账户同步表（订单列表同步表）saas_dhgate_autosync 提取的账号处理后的结果            
            if ($success) {
            	$SAA_obj->start_time = $start_time;
            	$SAA_obj->end_time = $end_time;
            	$SAA_obj->last_time = time();
            	$SAA_obj->status = 2;
            	$SAA_obj->times = 0;
            	$SAA_obj->save ();
            } else {
            	$SAA_obj->message = $errorMessage;
            	$SAA_obj->last_time = time();
            	$SAA_obj->status = 3;
            	$SAA_obj->times += 1;
            	$SAA_obj->save ();
            }            
            
            echo "finish \n";
		}
		return $hasGotRecord;
	}
	
	/**
	 * 拉取Dhgate所有已经完成的订单header信息
	 */
	public static function getOrderListByFinish(){
		echo "++++++++++++getOrderListByFinish \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=Yii::$app->db;
		#########################
		$type = 'finish';
		$hasGotRecord=false;//是否抢到账号
	
		//2. 从账户同步表（订单列表同步表）saas_dhgate_autosync 提取带同步的账号。          status--- 0 没处理过; 2--部分完成; 3--上一次执行有问题;4--全部完成
		$command=$connection->createCommand('select `id` from  `saas_dhgate_autosync` where `is_active` = 1 AND `status` in (0,2,3) AND `times` < 10 AND `type`="'.$type.'" order by `last_time` ASC limit 5');
		#################################
		// 订单详情同步表QueueDhgateGetorder记录对应的类型  FRIST = 1; FINISH = 2; NEWORDER = 3; UPDATEORDER = 4; NOFINISH = 5;
		$queueDhgateGetorderType=QueueDhgateGetorder::FINISH;
	
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockDhgateAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
				
			\Yii::info("dhgate_get_order_list_by_finish gotit jobid=$backgroundJobId start","file");
// 			echo "++dhgate_get_order_list_by_finish gotit jobid=$backgroundJobId start \n";
			$hasGotRecord=true;  // 抢到记录
	
			//3. 调用dhgate的api来获取对应账号的订单列表信息，并把dhgate返回的所有的订单header的信息保存到订单详情同步表QueueDhgateGetorder
			//3.1 整理请求参数
			$isLastFetch=false; //是否最后一次抓取
			//由于订单数量多，每次只拉取最近60天的。 所以同步状态细分了 "部分完成"和"全部完成"。
			if ($SAA_obj->end_time==0){
				//第一次拉取
				$start_time = $SAA_obj->binding_time-(86400*60);
				$end_time =$SAA_obj->binding_time;
			}else{
				//不是第一次拉取
				$start_time = $SAA_obj->start_time-(86400*60);
				$end_time =$SAA_obj->start_time;
				//dhgate最多支持拉取一年内的订单，保险起见，这里只拉取360天内的订单
				if (($SAA_obj->binding_time-(86400*360))>=$start_time){
					$start_time =$SAA_obj->binding_time-(86400*360);
					$isLastFetch=true;
				}
			}
			$createDateStart=date ( "Y-m-d H:i:s",$start_time );
			$createDateEnd=date ( "Y-m-d H:i:s", $end_time );
			$querytimeType=1;
			$pageNo=1;
			$pageSize = 200;
			$success=true;
			
			$appParams = array();
			$appParams["querytimeType"] = $querytimeType;
			$appParams["startDate"] = $createDateStart;
			$appParams["endDate"] = $createDateEnd;
			$appParams['orderStatus'] = '111000,111111';  //敦煌的订单结束状态为:111000,订单取消，111111,交易关闭
			
			$dhgateinterface_Api = new Dhgateinterface_Api();
			
			do{
				//3.2  调用dhgate的api来获取对应账号的订单列表信息。 由于有每页订单个数限制，这里需要循环多页面
				$appParams["pageNo"] = $pageNo;
				$appParams["pageSize"] = $pageSize;
					
				//查询订单列表getOrderList
				$response = $dhgateinterface_Api->getOrderList($SAA_obj->dhgate_uid, $appParams);
				\Yii::info(print_r($response,true),"file");
				
				//TODO ---
				//3.3 根据$response的信息进行 异常判断. --跳出循环
				// access Token 过期
				// 返回error code
				// 返回值没有["response"]["orderBaseInfoList"]
				// 返回值没有["response"]["count"]
				if ($response['success'] === false){
					$success=false;
					$errorMessage=$response['error_message'];
					break;
				}
				
				if (isset ( $response ['response'] )) {
					if ($response['response']['status']['code'] != "00000000"){
						
						//当返回109时代表查询不到记录
						if ($response['response']['status']['code'] == "109")
							$success=true;
						else
							$success=false;

						$errorMessage=$response['response']['status']['message'];
						break;
					}
					
					$resultCount=$response["response"]["count"];
					$resultPages=$response["response"]["pages"];
// 					echo "result---- resultCount:$resultCount,resultPages:$resultPages    ";
						
					//3.4 返回结果正常，需要把dhgate返回的所有的订单header的信息保存到订单详情同步表QueueDhgateGetorder
					if ($resultCount>0){
						$orderList=$response["response"]["orderBaseInfoList"];
						
						foreach($orderList as $one){
							self::_insertOrUpdateOrderQueue($one, $SAA_obj, $queueDhgateGetorderType);
						}
					}
				}else{
					$success=false;
				}
				
				$pageNo++;
			} while($pageNo<=$resultPages);
			
			//4. 保存 账户同步表（订单列表同步表）saas_dhgate_autosync 提取的账号处理后的结果
			if ($success) {
				$SAA_obj->start_time = $start_time;
				$SAA_obj->end_time = $end_time;
				$SAA_obj->last_time = time();
				if ($isLastFetch) 
					$SAA_obj->status = 4;
				else 
					$SAA_obj->status = 2;
				$SAA_obj->times = 0;
				$SAA_obj->save ();
			} else {
				$SAA_obj->message = $errorMessage;
				$SAA_obj->last_time = time();
				$SAA_obj->status = 3;
				$SAA_obj->times += 1;
				$SAA_obj->save ();
			}
	
			echo "finish \n";
		}
		return $hasGotRecord;
	}
	
	
	/**
	 * 同步Dhgate新产生的订单时间从绑定时间开始
	 */
	public static function getOrderListByTime(){
		echo "++++++++++++getOrderListByTime \n";
		//1. 检查当前本job版本，如果和自己的版本不一样，就自动exit吧
		$ret=self::checkNeedExitNot();
		if ($ret===true) exit;
	
		$backgroundJobId=self::getCronJobId(); //获取进程id号，主要是为了打印log
		$connection=Yii::$app->db;
		#########################
		$type = 'time';
		$hasGotRecord=false;//是否抢到账号	
		//2. 从账户同步表（订单列表同步表）saas_dhgate_autosync 提取带同步的账号。          status--- 0 没处理过; 2--部分完成; 3--上一次执行有问题;4--全部完成
		$t = time()-1800;//同一账号至少要隔半个小时才进行新订单列表的拉取！！！  账号第一次绑定的时候last_time=0 
		$hasGotRecord=false;		
		$command=$connection->createCommand('select `id` from  `saas_dhgate_autosync` where `is_active` = 1 AND `status` <>1 AND `times` < 10 AND `type`="'.$type.'" AND last_time < '.$t.'  order by `last_time` ASC limit 5');
		
		#################################
		// 订单详情同步表QueueDhgateGetorder记录对应的类型  FRIST = 1; FINISH = 2; NEWORDER = 3; UPDATEORDER = 4; NOFINISH = 5;
		$queueDhgateGetorderType=QueueDhgateGetorder::NEWORDER;
	
		$dataReader=$command->query();
		while(($row=$dataReader->read())!==false) {
			// 先判断是否真的抢到待处理账号
			$SAA_obj = self::_lockDhgateAutosyncRecord($row['id']);
			if ($SAA_obj===null) continue; //抢不到---如果是多进程的话，有抢不到的情况
	
			\Yii::info("dhgate_get_order_list_by_finish gotit jobid=$backgroundJobId start","file");
// 			echo "++dhgate_get_order_list_by_finish gotit jobid=$backgroundJobId start \n";
			$hasGotRecord=true;  // 抢到记录
	
			//3. 调用dhgate的api来获取对应账号的订单列表信息，并把dhgate返回的所有的订单header的信息保存到订单详情同步表QueueDhgateGetorder
			//3.1 整理请求参数
			$nowTime = time();
			if ($SAA_obj->end_time == 0){
				//第一次拉取
				$start_time = $SAA_obj->binding_time;
				$end_time = $nowTime;
			}else {
				$start_time = $SAA_obj->end_time;
				$end_time = $nowTime;
			}
			$createDateStart=date ( "Y-m-d H:i:s",$start_time );
			$createDateEnd=date ( "Y-m-d H:i:s", $end_time );
			$querytimeType=1;
			$pageNo=1;
			$pageSize = 200;
			$success=true;
			
			$appParams = array();
			$appParams["querytimeType"] = $querytimeType;
			$appParams["startDate"] = $createDateStart;
			$appParams["endDate"] = $createDateEnd;
			
			$dhgateinterface_Api = new Dhgateinterface_Api();
			
			do{
				//3.2  调用dhgate的api来获取对应账号的订单列表信息。 由于有每页订单个数限制，这里需要循环多页面
				$appParams["pageNo"] = $pageNo;
				$appParams["pageSize"] = $pageSize;
			
				//查询订单列表getOrderList
				$response = $dhgateinterface_Api->getOrderList($SAA_obj->dhgate_uid, $appParams);
				\Yii::info(print_r($response,true),"file");
			
				//TODO ---
				//3.3 根据$response的信息进行 异常判断. --跳出循环
				// access Token 过期
				// 返回error code
				// 返回值没有["response"]["orderBaseInfoList"]
				// 返回值没有["response"]["count"]
				if ($response['success'] === false){
					$success=false;
					$errorMessage=$response['error_message'];
					break;
				}
				
				if (isset ( $response ['response'] )) {
					if ($response['response']['status']['code'] != "00000000"){
						//当返回109时代表查询不到记录
						if ($response['response']['status']['code'] == "109")
							$success=true;
						else
							$success=false;
						
						$errorMessage=$response['response']['status']['message'];
						break;
					}
					
					$resultCount=$response["response"]["count"];
					$resultPages=$response["response"]["pages"];
						
					//3.4 返回结果正常，需要把dhgate返回的所有的订单header的信息保存到订单详情同步表QueueDhgateGetorder
					if ($resultCount>0){
						$orderList=$response["response"]["orderBaseInfoList"];
						foreach($orderList as $one){
							self::_insertOrUpdateOrderQueue($one, $SAA_obj, $queueDhgateGetorderType);
						}
					}
				}else{
					$success=false;
				}
				
				$pageNo++;
			} while($pageNo<=$resultPages);
			
			//4. 保存 账户同步表（订单列表同步表）saas_dhgate_autosync 提取的账号处理后的结果
			if ($success) {
				$SAA_obj->start_time = $start_time;
				$SAA_obj->end_time = $end_time;
				$SAA_obj->last_time = time();				
				$SAA_obj->status = 2;
				$SAA_obj->times = 0;
				$SAA_obj->save ();
			} else {
				$SAA_obj->message = $errorMessage;
				$SAA_obj->last_time = time();
				$SAA_obj->status = 3;
				$SAA_obj->times += 1;
				$SAA_obj->save ();
			}
		
			echo "finish \n";
		}
		return $hasGotRecord;
	}
	
}