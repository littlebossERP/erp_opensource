<?php
namespace eagle\modules\listing\service;
use yii;
use yii\base\Exception;
use eagle\modules\util\helpers\SysLogHelper;
use eagle\modules\platform\helpers\WishAccountsV2Helper;
use eagle\models\SaasWishUser;


/**
+---------------------------------------------------------------------------------------------
 * eagle的web代码(非WISH的后台程序)需要跟WISH proxy进行交互，这里的helper提供了通信的基础函数
+---------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		yzq		2014/07/21				初始化
+---------------------------------------------------------------------------------------------
 **/


class ProxyConnectHelper{

    // TODO proxy host
	public static $WISH_PROXY_URL='http://localhost/Wish_Proxy_Server/ApiEntryV2.php';

    // $TIME_OUT  s 单位
    // communicate to theWish proxy to get the information through http
    public static function call_WISH_api($action , $get_params = array()  , $post_params=array(),$TIME_OUT=180, $return_type='json' ){
        try {
            $url = self::$WISH_PROXY_URL;
            $url .= "?action=$action";
            $rtn['success'] = true;  //跟proxy之间的网站是否ok
            $rtn['message'] = '';

            //	SysLogHelper::SysLog_Create("WISH", __CLASS__,__FUNCTION__,"","  reqParam:".json_encode($get_params),"Debug");
            //		$journal_id = SysLogHelper::InvokeJrn_Create("WISH", __CLASS__, __FUNCTION__ , array($action,$get_params,$post_params));

            foreach($get_params  as $key=>$value){
                $url .= "&$key=".urlencode(trim($value));
            }

            $handle = curl_init($url);
            //echo "try to call proxy by url:".$url."\n";//test kh
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
                    //	   	SysLogHelper::SysLog_Create("WISH", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
                    $rtn['success'] = false ;
                }else
                    $rtn['message'] = "";


            }else{ // network error
                $rtn['message'] = "Failed for $action , Got error respond code $httpCode from Proxy";
                //write_to_log("Line ".__LINE__." ".$rtn['message'],"error",__FUNCTION__,basename(__FILE__));
                //	SysLogHelper::SysLog_Create("WISH", __CLASS__,__FUNCTION__,"","Line ".__LINE__." ".$rtn['message'],"Error");
                $rtn['success'] = false ;
                $rtn['proxyResponse'] = "";
            }

            //SysLogHelper::GlobalLog_Create("Platform", __CLASS__,__FUNCTION__,"Step A","Call proxy done httpCode= $httpCode , success= ".$rtn['success'] . " Response=".$response  ,"info");
            curl_close($handle);

        } catch (Exception $e) {
            $rtn['success'] = false;  //跟proxy之间的网站是否ok
            $rtn['message'] = $e->getMessage();
            echo "WishProxyConnectHelper exception for ".$rtn['message']."\n";
            curl_close($handle);
        }

        //SysLogHelper::InvokeJrn_UpdateResult($journal_id, $rtn);
        return $rtn;

    }//end of call_WISH_api by proxy


}
?>