<?php
namespace eagle\modules\listing\helpers;
use yii;
use yii\base\Exception;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\tracking\helpers\TrackingAgentHelper;


/**
 +---------------------------------------------------------------------------------------------
 * eagle的web代码(非WISH的后台程序)需要跟Cdiscount proxy进行交互，这里的helper提供了通信的基础函数
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/07/21				初始化
 +---------------------------------------------------------------------------------------------
 **/


class CdiscountProxyConnectHelper{
    // TODO proxy host
	public static $CDISCOUNT_PROXY_URL='http://localhost/cdiscount_proxy_server/ApiEntry.php';
	
	
	// $TIME_OUT  s 单位
    // communicate to the Cdiscount proxy to get the information through http
	public static function call_Cdiscount_api($action , $get_params = array()  , $post_params=array(),$TIME_OUT=180, $return_type='json' ){
		$ext_call_name="CDiscount OMS";
		$run_time = -1;
		//echo "\n enter function : call_Cdiscount_api";
		try {
			$url = self::$CDISCOUNT_PROXY_URL;
			$url .= "?action=$action&";
			$rtn['success'] = true;  //跟proxy之间的网站是否ok
			$rtn['message'] = '';
			$current_time=explode(" ",microtime());
			$time1=round($current_time[0]*1000+$current_time[1]*1000);
				
			//	SysLogHelper::SysLog_Create("WISH", __CLASS__,__FUNCTION__,"","  reqParam:".json_encode($get_params),"Debug");
			$url .= http_build_query($get_params);
			//echo "\n".print_r($get_params);
			//echo "\n".$url."\n";
			$rtn['url'] = $url;
			$handle = curl_init($url);
			
			if($action=='GetSellerInfo' || $action=='GetOfferList' || $action=='getOrderList' || $action=='getEmailByOrderID' || $action=='shipCDiscountOrder' || $action=='GetAllowedCategoryTree' ){
				echo "\n ".$url;//test
			}
			//SysLogHelper::SysLog_Create('Listing',__CLASS__, __FUNCTION__,'info','url='.$url);//test liang
			//$journal_id = SysLogHelper::InvokeJrn_Create("Listing", __CLASS__, __FUNCTION__ ,array($action,$url));
			
			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
			//echo "time out : ".$TIME_OUT;
			
			if (count($post_params)>0){
				curl_setopt($handle, CURLOPT_POST, true);
				curl_setopt($handle, CURLOPT_POSTFIELDS, $post_params );
			}
			//  output  header information
			// curl_setopt($handle, CURLINFO_HEADER_OUT , true);
			
			/* Get the HTML or whatever is linked in $url. */
			$response = curl_exec($handle);
			$current_time=explode(" ",microtime());
			$time2=round($current_time[0]*1000+$current_time[1]*1000);
			
			//计算累计做了多少次external 的调用以及耗时
			
			$run_time = $time2 - $time1; //这个得到的$time是以 ms 为单位的
			
			TrackingAgentHelper::extCallSum($ext_call_name,$run_time);
			$curl_errno = curl_errno($handle);
			$curl_error = curl_error($handle);
			if ($curl_errno > 0) { // network error
				$rtn['message']="cURL Error $curl_errno : $curl_error";
				$rtn['success'] = false ;
				$rtn['proxyResponse'] = "";
				//echo $rtn['message']."used time $run_time ms";
				curl_close($handle);
				return $rtn;
			}
			
			/* Check for 404 (file not found). */
			$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			//echo $httpCode.$response."\n";
			if ($httpCode == '200' ){
			
				$rtn['proxyResponse'] = json_decode($response , true);
				//print_r($rtn['proxyResponse']);//liang test
				if ($rtn['proxyResponse']==null){
					// json_decode fails
					$rtn['message'] = "content return from proxy is not in json format, content:".$response;
					//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
					//	   	SysLogHelper::SysLog_Create("WISH", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
					$rtn['success'] = false ;
				}else
					$rtn['message'] = "Done With http Code 200";
			}else{ // network error
				$rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy;url=$url";
				//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
				//	SysLogHelper::SysLog_Create("WISH", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
				$rtn['success'] = false ;
				$rtn['proxyResponse'] = "";
			}
			//SysLogHelper::GlobalLog_Create("Platform", __CLASS__,__FUNCTION__,"Step A","Call proxy done httpCode= $httpCode , success= ".$rtn['success'] . " Response=".$response  ,"info");
			curl_close($handle);
			
			//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		} catch (Exception $e) {
			$rtn['success'] = false;  //跟proxy之间的网站是否ok
			$rtn['message'] = $e->getMessage();
			curl_close($handle);
		}
		//echo "call cd proxy done ".$rtn['message']." used time $run_time ms";
		return $rtn;
		
	}//end of call_Cdiscount_api by proxy

	
}
?>