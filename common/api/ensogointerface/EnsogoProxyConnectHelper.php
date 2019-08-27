<?php
namespace common\api\ensogointerface;
use yii;
use yii\base\Exception;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\platform\helpers\EnsogoAccountsV2Helper;
use eagle\models\SaasEnsogoUser;
use eagle\modules\platform\helpers\EnsogoAccountsHelper;


/**
 +---------------------------------------------------------------------------------------------
 * eagle的web代码(非ENSOGO的后台程序)需要跟ENSOGO proxy进行交互，这里的helper提供了通信的基础函数
 +---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/07/21				初始化
 +---------------------------------------------------------------------------------------------
 **/


class EnsogoProxyConnectHelper{
 
    public static $ENSOGO_PROXY_URL ='';//public static

    public static function getUrl(){
        if(YII::$app->params['currentEnv'] == 'production' || !isset(YII::$app->params['currentEnv'])){  
            self::$ENSOGO_PROXY_URL='http://localhost/Ensogo_Proxy_Server_Online/ApiEntry.php';
        } else { 
            self::$ENSOGO_PROXY_URL='http://localhost/Ensogo_Proxy_Server/ApiEntry.php';
        }
    }

    // $TIME_OUT  s 单位
    // communicate to theEnsogo proxy to get the information through http
	/**
	 +---------------------------------------------------------------------------------------------
	 * 调用 ensogo proxy 的接口完全ensogo api的调用
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param	  $action					调用ensogo api 的类型 
	 * @param     $ensogo_token				ensogo token 受权信息
	 * @param     $post_params           		ensogo proxy post 需要 的参数 
	 * @param     $get_params					ensogo proxy get 需要 的参数 
	 * @param	  $timeout					等待ensogo proxy 返回的结果 超时限制 单位(秒)
	 * @param	  $return_type				返回格式
	 +---------------------------------------------------------------------------------------------
	 * @return						$data
	 *
	 * @invoking					EnsogoProxyConnectHelper::call_ENSOGO_api($action, $params );
	 *
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2016/01/20				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	public static function call_ENSOGO_api($action , $post_params=array(), $get_params = array()  ,$TIME_OUT=180, $return_type='json',$try_number = 0 ){
        self::getUrl();
        // var_dump(self::$ENSOGO_PROXY_URL);
		try {
			$url = self::$ENSOGO_PROXY_URL;
// 			$url .= "?action=$action";
// 			$url .= '&lb_auth=123&debug=true';

			$get_params['debug'] = true;
			
			$post_params ['action'] = $action;
			$post_params ['lb_auth'] = '123';
			
			$rtn['success'] = true;  //跟proxy之间的网站是否ok
			$rtn['message'] = '';
			
			//	SysLogHelper::SysLog_Create("ENSOGO", __CLASS__,__FUNCTION__,"","  reqParam:".json_encode($get_params),"Debug");
	//		$journal_id = SysLogHelper::InvokeJrn_Create("ENSOGO", __CLASS__, __FUNCTION__ , array($action,$get_params,$post_params));
			
			$getParamsUrl = "";
			foreach($get_params  as $key=>$value){
				if (empty($getParamsUrl))
					$getParamsUrl .= "?$key=".urlencode(trim($value));
				else
					$getParamsUrl .= "&$key=".urlencode(trim($value));
			}
			$url .=$getParamsUrl;
			$rtn['url'] = $url;
			$handle = curl_init($url);
			//echo "try to call proxy by url:".$url."\n";//test kh
			curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
			//echo "time out : ".$TIME_OUT;
	
			if (count($post_params)>0){
				curl_setopt($handle, CURLOPT_POST, true);
				//curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query(array("parms"=>json_encode($post_params) ) ) );
				curl_setopt($handle, CURLOPT_POSTFIELDS, $post_params);
				//var_dump($post_params);
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
                $rtn['proxyResponse'] = $rtn;
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
                    $rtn['proxyResponse'] = $rtn;
					curl_close($handle);
					return $rtn;
				}
				/* Check for 404 (file not found). */
				$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
			}

            //重试一次
            if($httpCode == '0' && $try_number == 0){
                return self::call_ENSOGO_api($action , $post_params, $get_params  , 180, 'json',1);
            } else if ($httpCode == '200' ){
			
				$rtn['proxyResponse'] = json_decode($response , true);
					
				if ($rtn['proxyResponse']==null){
					// json_decode fails
					$rtn['message'] = "content return from proxy is not in json format, content:".$response;
					//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
					//	   	SysLogHelper::SysLog_Create("ENSOGO", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
					$rtn['success'] = false ;
                    $rtn['proxyResponse'] = $rtn;
				}else
					$rtn['message'] = "";
				
				
			}else{ // network error
				$rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy";
				//write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
				//	SysLogHelper::SysLog_Create("ENSOGO", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
				$rtn['success'] = false ;
                $rtn['proxyResponse'] = $rtn;
			}
		
			//SysLogHelper::GlobalLog_Create("Platform", __CLASS__,__FUNCTION__,"Step A","Call proxy done httpCode= $httpCode , success= ".$rtn['success'] . " Response=".$response  ,"info");
			curl_close($handle);
		
		} catch (Exception $e) {
			$rtn['success'] = false;  //跟proxy之间的网站是否ok
			$rtn['message'] = $e->getMessage();
            $rtn['proxyResponse'] = $rtn;
			echo "EnsogoProxyConnectHelper exception for ".$rtn['message']."\n";
			curl_close($handle);
		}

		//SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
		return $rtn;
		
	}//end of call_ENSOGO_api by proxy
	
	/**
	 * 通过获取订单list的方式来测试用户想要绑定的ENSOGO的api信息是否ok
	 * @param $config=array('merchant_id'=>??,'marketplace_id'=>??,'access_key_id'=>??,'secret_access_key'=>??);
	 * @return true or false
	**/
	public static function testENSOGOAccount(){
		 
		$reqParams=array();
		$reqParams["token"] = "JHBia2RmMiQxMDAkQzZHVThyNDNab3dSNGh5REVHSk1pUSQwNzZ3d01NdFBhLllKeE9LRk44U0pSTndLQ2M=";
		$reqParams["token"] = str_replace('=','@@@',$reqParams["token"]);
		 
		$rtn=self::call_ENSOGO_api("GetAllProduct",$reqParams);
		return json_encode( $rtn) ;
	}
	
	/**
	 +---------------------------------------------------------------------------------------------
	 * 检查 access token 过期 就自动刷新token
	 +---------------------------------------------------------------------------------------------
	 * @access static
	 +---------------------------------------------------------------------------------------------
	 * @param $site_id		ensogo user id 
	 * @param $site_id		ensogo token 是否强制刷新
	 +---------------------------------------------------------------------------------------------
	 * @return				[success=>boolean , 'message'=>string '检查结果的描述'] 
	 * @description			检查 access token 过期 就自动刷新token
	 +---------------------------------------------------------------------------------------------
	 * log			name	date					note
	 * @author		lkh		2014/12/03				初始化
	 +---------------------------------------------------------------------------------------------
	 **/
	static public function checkEnsogoTokenExpiryOrNot($site_id , $isRefresh = false){
		$model = SaasEnsogoUser::findOne(['site_id'=>$site_id]);
		//check access token expiry or not
		if ($model['created_at']+$model['expires_in']  < time() || $isRefresh){
			// time's  up , then refresh access token
			if (!empty($model['refresh_token'])){
				// get access token 
				$ensogoReturn = EnsogoAccountsHelper::refreshEnsogoToken($model['refresh_token']);
				echo '\n *********** important message start ************* \n';
				echo '\n site id  ='.$site_id.' and token='.$model['refresh_token'].' and  ensogo retrun '.json_encode($ensogoReturn).'\n'; //refresh log 
				$result = EnsogoAccountsHelper::saveEnsogoToken($model, $ensogoReturn);
				echo '\n save token retrun '.json_encode($result).'\n'; //refresh save log 
				echo '\n *********** important message end ************* \n';
				return $result;
			}else{
				//unable to get access token 
				unset($model);
				return [
					'success'=>false,
					'message'=>'没有绑定成功， 请点击重新绑定(refresh token is empty!)'
				];
			}
		}else{
			unset($model);
			//access token is active
			return [
					'success'=>true,
					'message'=>''
				];
		}
	}
}
?>