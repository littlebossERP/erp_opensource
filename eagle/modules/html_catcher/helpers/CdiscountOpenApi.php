<?php
namespace eagle\modules\html_catcher\helpers;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\RedisHelper;
class CdiscountOpenApi{
	 	
    // TODO proxy host
	//PROXY 1 ：
	static $CDISCOUNT_PROXY_URL='http://localhost/cdiscount_proxy_server/OpenApiEntry.php'; 
	//PROXY 2：
	static $CDISCOUNT_PROXY_URL_2='http://localhost/cdiscount_proxy_server/OpenApiEntry.php';
	/**
	 +----------------------------------------------------------------------------------------------------------------------------
	 *	通过 open api 获取cdiscount 商品信息
	 +----------------------------------------------------------------------------------------------------------------------------
	 * @access static
	 +----------------------------------------------------------------------------------------------------------------------------
	 * @param 	$productIdList	array			cdiscount 商品编号
	 +----------------------------------------------------------------------------------------------------------------------------
	 * @return			array
	 * 	boolean				success  	执行结果
	 * 	string/array		message  	执行失败的提示信息
	 *  array				product		获取的商品信息
	 +----------------------------------------------------------------------------------------------------------------------------
	 * @invoking		CdiscountOpenApi::getCdiscountProduct($productIdList)
	 +----------------------------------------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/09/07				初始化
	 +----------------------------------------------------------------------------------------------------------------------------
	 **/
	static public function getCdiscountProduct($productIdList){
		$ProductIdGroupList = array_chunk($productIdList,5);
		$rt = [];
		foreach ($ProductIdGroupList as $subGroup){
			$params['Offers'] = true;
			$params['AssociatedProducts'] = true;
			$params['Images'] = true;
			$params['Ean'] = true;
			$params['ProductIdList'] = $subGroup;
			
			//按分钟值，分配不同的proxy机
			$time = time();
			$timeMinute = date("i",$time);
			if( $timeMinute%2==0 )
				$proxy = 1;
			else
				$proxy = 2;
			
			
			$sub_rt = self::call_cdiscount_open_api('GetProduct' , ['query_params'=>$params], [], 120, 'josn', $proxy);
			//liang test 记录调用统计到redis
			self::TotalCallOpneApiDailyCount();
			
			//1.host 与proxy 沟通成功  ;  2.proxy与cdiscount 沟成功
			if (!empty($sub_rt['success']) && !empty($sub_rt['proxyResponse']['success'])){
				if (isset($sub_rt['proxyResponse']['cdiscountReturn']['Products'] )){
					foreach($sub_rt['proxyResponse']['cdiscountReturn']['Products'] as &$row){
						//设置返回数据
						$rt[$row['Id']] = ['success'=>true , 'message'=>'' , 'product'=>$row];
					}
				}else{
					//没有找到Products 但是host 与proxy 显示沟通成功 和proxy与cdiscount 沟通成功
					foreach($subGroup as $productId){
						$rt[$productId] =  ['success'=>false , 'message'=>'cdiscount找不到对应的信息' ];
					}
				}
				
				self::ProxyCallOpneApiCount($proxy,$time);
			}else{
				//1.host 与proxy 沟通失败 ;  
				if (empty($sub_rt['success'])){
					foreach($subGroup as $productId){
						$rt[$productId] =  ['success'=>false , 'message'=>'host 访问 proxy 失败' ];
					}
				}
				
				//2.proxy与cdiscount 沟通失败
				if (empty($sub_rt['success'])){
					foreach($subGroup as $productId){
						$rt[$productId] =  ['success'=>false , 'message'=>'proxy 访问cdiscount open api 失败' ];
						//liang test 记录调用统计到redis
						self::FailedCallOpneApiDailyCount();
						self::MarkFailedCallOpneApiTime();
						self::ProxyCallOpneApiCount($proxy,$time,false);
					}
				}
			}
			
		}//end of loop get product info
		
		return $rt;
	}//end of getCdiscountProduct
	
	
	public static function call_cdiscount_open_api($action , $get_params = array()  , $post_params=array(),$TIME_OUT=180, $return_type='json', $proxy=0){
		try {
			//拉商品数据的时候随机换proxy，避免受到额度限制
			if($action=='GetProduct'){
				if(empty($proxy)){
					$rand = rand(1, 2);
				}else 
				$rand = $proxy;
				
				if($rand==1)
					$url = self::$CDISCOUNT_PROXY_URL;
				else
					$url = self::$CDISCOUNT_PROXY_URL_2;
			}
			else 
				$url = self::$CDISCOUNT_PROXY_URL;
			
			$url .= "?action=$action";
			$rtn['success'] = true;  //跟proxy之间的网站是否ok
			$rtn['message'] = '';
				
			//	SysLogHelper::SysLog_Create("CDISCOUNT", __CLASS__,__FUNCTION__,"","  reqParam:".json_encode($get_params),"Debug");
			//$journal_id = SysLogHelper::InvokeJrn_Create("CDISCOUNT", __CLASS__, __FUNCTION__ , array($action,$get_params,$post_params));
				
			foreach($get_params  as $key=>$value){
				if (is_array($value)){
					$value = json_encode($value);
				}
				$url .= "&$key=".urlencode(trim($value));
			}
	
			$handle = curl_init($url);
			echo "try to call proxy by url:".$url."\n";//test kh
			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
			//echo "time out : ".$TIME_OUT;
	
			if (count($post_params)>0){
				curl_setopt($handle, CURLOPT_POST, true);
				curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query(array("parms"=>json_encode($post_params) ) ) );
			}
			//  output  header information
			// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
				
			/* Get the HTML or whatever is linked in $url. */
			$response = curl_exec($handle);
			$curl_errno = curl_errno($handle);
			$curl_error = curl_error($handle);
			if ($curl_errno > 0) { // network error
				$rtn['message']="cURL Error $curl_errno : $curl_error";
				$rtn['success'] = false ;
				$rtn['proxyResponse'] = "";
				curl_close($handle);
				print_r($rtn);
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
					$rtn['proxyResponse'] = "";
					curl_close($handle);
					print_r($rtn);
					return $rtn;
				}
				/* Check for 404 (file not found). */
				$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			}
				
			if ($httpCode == '200' ){
					
				$rtn['proxyResponse'] = json_decode($response , true);
					
				if ($rtn['proxyResponse']==null){
					// json_decode fails
					$rtn['message'] = "content return from proxy is not in json format, content:".$response;
					//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
					//	   	SysLogHelper::SysLog_Create("CDISCOUNT", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
					$rtn['success'] = false ;
				}else
					$rtn['message'] = "";
	
	
			}else{ // network error
				$rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy";
				//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
				//	SysLogHelper::SysLog_Create("CDISCOUNT", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
				$rtn['success'] = false ;
				$rtn['proxyResponse'] = "";
				print_r($rtn);
			}
	
			//SysLogHelper::GlobalLog_Create("Platform", __CLASS__,__FUNCTION__,"Step A","Call proxy done httpCode= $httpCode , success= ".$rtn['success'] . " Response=".$response  ,"info");
			curl_close($handle);
	
		} catch (Exception $e) {
			$rtn['success'] = false;  //跟proxy之间的网站是否ok
			$rtn['message'] = $e->getMessage();
			echo "CDISCOUNTProxyConnectHelper exception for ".$rtn['message']."\n";
			curl_close($handle);
			print_r($rtn);
		}
	
		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		return $rtn;
	
	}//end of call_CDISCOUNT_api by proxy
	
	/*
	 * redis记录每日调用CD open API的次数
	 */
	private static function TotalCallOpneApiDailyCount(){
		try{
			$nowDay = TimeUtil::getNowDate();
			$key1 = 'CD_OPEN_API_DailyCount';
			$lastCount = RedisHelper::RedisGet($key1, $nowDay);
			if(empty($lastCount))
				$lastCount = 0;
			$lastCount = (int)$lastCount +1;
			RedisHelper::RedisSet($key1, $nowDay, $lastCount);
		}catch (\Exception $e) {
			echo "\n TotalCallOpneApiDailyCount excrption:".print_r($e->getMessage());
		}
	}
	
	/*
	 * redis记录每日调用CD open API 失败的次数
	*/
	private static function FailedCallOpneApiDailyCount(){
		try{
			$nowDay = TimeUtil::getNowDate();
			$key1 = 'CD_OPEN_API_DailyFailed';
			$lastCount = RedisHelper::RedisGet($key1, $nowDay);
			if(empty($lastCount))
				$lastCount = 0;
			$lastCount = (int)$lastCount +1;
			RedisHelper::RedisSet($key1, $nowDay, $lastCount);
		}catch (\Exception $e) {
			echo "\n FailedCallOpneApiDailyCount excrption:".print_r($e->getMessage());
		}
	}
	
	/*
	 * redis记录每日调用CD open API 失败的时段
	*/
	private static function MarkFailedCallOpneApiTime(){
		try{
			$nowTimeStamp = time();
			$nowDay = TimeUtil::getNowDate();
			$yesterDay = date("Y-m-d", $nowTimeStamp-3600*24+1 );
			$keyA = 'CD_OPEN_API_FailedTime_Settlement';//结算情况Key
			$keyB = 'CD_OPEN_API_FailedTime_Current';//当前情况Key
			
			//如果有跨日的未结算记录，先结算
			$yesterDayCurrent = RedisHelper::RedisGet($keyB, $yesterDay);
			if(!empty($yesterDayCurrent)){
				$yesterDayCurrent = json_decode($yesterDayCurrent,true);
				if(!empty($yesterDayCurrent['start_time'])){
					$tmp_settlement = [];
					$tmp_settlement['start_time'] = (int)$yesterDayCurrent['start_time'];
					$tmp_settlement['end_time'] = strtotime($nowDay);
					$tmp_settlement['continuous_time'] = $tmp_settlement['end_time'] - $tmp_settlement['start_time'];
					$yesterDaySettlement = RedisHelper::RedisGet($keyA, $yesterDay);
					if(!empty($yesterDaySettlement))
						$yesterDaySettlement = json_decode($yesterDaySettlement,true);
					else 
						$yesterDaySettlement = [];
					
					$yesterDaySettlement[] = $tmp_settlement;
					RedisHelper::RedisSet($keyA, $yesterDay, json_encode($yesterDaySettlement));
					RedisHelper::RedisDel($keyB, $yesterDay);
				}
			}
			
			//出现连接失败的时候，先get一下有无还没有结算的失败记录:
			$current = RedisHelper::RedisGet($keyB, $nowDay);
			if(!empty($current)){
				//有的话，
				$current = json_decode($current,true);
				$lastUpdate = empty($current['update_time'])?$current['start_time']:$current['update_time'];
				//判断上次update时间是否10分钟前，
				if( ($nowTimeStamp - (int)$lastUpdate)>60*10 ){
					//是的话，结算上次结果，并更新本次时间为待结算
					$tmp_settlement = [];
					$tmp_settlement['start_time'] = (int)$current['start_time'];
					$tmp_settlement['end_time'] = $nowTimeStamp;
					$tmp_settlement['continuous_time'] = $tmp_settlement['end_time'] - $tmp_settlement['start_time'];
					$nowDaySettlement = RedisHelper::RedisGet($keyA, $nowDay);
					if(!empty($nowDaySettlement))
						$nowDaySettlement = json_decode($nowDaySettlement,true);
					else
						$nowDaySettlement = [];
					
					$nowDaySettlement[] = $tmp_settlement;
					RedisHelper::RedisSet($keyA, $nowDay, json_encode($nowDaySettlement));
					$currentData = ['start_time'=>$nowTimeStamp,'update_time'=>$nowTimeStamp];
					RedisHelper::RedisSet($keyB, $nowDay, json_encode($currentData));
				}else{
					//不是的话，update本次时间到待结算失败记录 ；
					$currentData = $current;
					$currentData['update_time'] = $nowTimeStamp;
					RedisHelper::RedisSet($keyB, $nowDay, json_encode($currentData));
				}
			}
			else{
				//没有的话，记录本次时间为开始时间
				$currentData = ['start_time'=>$nowTimeStamp,'update_time'=>$nowTimeStamp];
				RedisHelper::RedisSet($keyB, $nowDay, json_encode($currentData));
			}
		}catch (\Exception $e) {
			echo "\n MarkFailedCallOpneApiTime excrption:".print_r($e->getMessage());
		}
	}
	
	public static function ProxyCallOpneApiCount($proxy,$time,$success=true){
		try{
			$timeHour = date("G",$time);
			
			$timeMinute = date("i",$time);
			$timeMinute = (int)$timeMinute;
			if($timeMinute<30)
				$timeHourHalf = 1;
			else 
				$timeHourHalf = 2;
			
			$nowDay = TimeUtil::getNowDate();
			$keyL1 = 'CD_OPEN_API_ProxyCall_Count';
			$current = RedisHelper::RedisGet($keyL1, $nowDay);
			if(!empty($current)){
				$current = json_decode($current,true);
			}else
				$current = [];
			
			$current[$timeHour] = empty($current[$timeHour])?[]:$current[$timeHour];
			if(empty($current[$timeHour]['half_'.$timeHourHalf]['total_call']))
				$current[$timeHour]['half_'.$timeHourHalf]['total_call'] = 1;
			else 
				$current[$timeHour]['half_'.$timeHourHalf]['total_call'] = (int)$current[$timeHour]['half_'.$timeHourHalf]['total_call'] +1;
			if(!$success){
				if(empty($current[$timeHour]['half_'.$timeHourHalf]['failed_call']))
					$current[$timeHour]['half_'.$timeHourHalf]['failed_call'] = 1;
				else
					$current[$timeHour]['half_'.$timeHourHalf]['failed_call'] = (int)$current[$timeHour]['half_'.$timeHourHalf]['failed_call'] +1;
			}
			$val = json_encode($current);
			RedisHelper::RedisSet($keyL1, $nowDay,$val);
		}catch (\Exception $e) {
			echo "\n ProxyCallOpneApiCount excrption:".print_r($e->getMessage());
		}
	}
}//end of CdiscountOpenApi