<?php
namespace common\api\lazadainterface;

use console\helpers\CommonHelper;
use console\helpers\LogType;
use eagle\modules\lazada\apihelpers\LazadaApiHelper;
use Qiniu\json_decode;
use yii;


/**
 * +---------------------------------------------------------------------------------------------
 * eagle的web代码(后台程序)需要跟proxy进行交互，这里的helper提供了通信的基础函数
 * +---------------------------------------------------------------------------------------------
 * log            name    date                    note
 * @author        yzq        2014/07/21                初始化
 * +---------------------------------------------------------------------------------------------
 **/
class LazadaProxyConnectHelper
{

    // lazada 偶尔出现 proxy ip 被禁的情况，加上这个index 将请求平分到两台机器
    public static $PROXY_INDEX = 0;
    const LOG_ID = "common_api_lazadainterface_class LazadaProxyConnectHelper";
    
	// order api 
	public static $ORDER_API = array(
		'GetOrderList','GetOrderDetail','getOrderItemImage','packedByMarketplace','shipOrder','getShipmentProviders','GetDocument'
	);
	
	// TODO proxy host
	// lazada的   非商品 时读取  lazada 2018 5月份上新接口，这个入口废弃了
	public static $LAZADA_PROXY_URL = "http://localhost/Lazada_Proxy_Server/ApiEntryV2.php";
	public static $LAZADA_PROXY2_URL = "http://localhost/Lazada_Proxy_Server/ApiEntryV2.php";
	
	// lazada的   商品 时读取 入口已废弃
	public static $LAZADA_PRODUCT_PROXY_URL = "http://localhost/Lazada_Proxy_Server/ApiEntryV2.php"; // lazada新接口调整后的入口脚本
	
	// linio和jumia的   非商品 时读取
	public static $LINIO_PROXY_URL = "http://localhost/Lazada_Proxy_Server/ApiEntry.php";
	
	// linio和jumia的   商品 时读取
	public static $LINIO_PRODUCT_PROXY_URL = "http://localhost/Lazada_Proxy_Server/ApiEntry.php";
	
    // $TIME_OUT  s 单位
    // communicate to the amazon proxy to get the information through http
    public static function call_lazada_api($get_params = array(), $TIME_OUT = 60)
    {

        try {
// 			$TIME_OUT=60;//连接和网络读写超时时间，单位s
            $return_type = 'json';

            $config = json_decode($get_params['config'], true);
            if (array_key_exists($config['countryCode'], LazadaApiHelper::getLazadaCountryCodeSiteMapping())) {// lazada使用香港proxy,linio jumia 使用美国Proxy
            	// dzt20160728 分开产品类接口入口和订单类接口入口
            	if(in_array($get_params['action'],self::$ORDER_API)){

            		$url = self::$LAZADA_PROXY2_URL;
            	    if(self::$PROXY_INDEX == 0){
            	        $url = self::$LAZADA_PROXY_URL;
            	    }elseif(self::$PROXY_INDEX == 1){
            	        $url = self::$LAZADA_PROXY2_URL;
            	    }
            	}else{
            		$url = self::$LAZADA_PRODUCT_PROXY_URL;
            	}
            } else {
            	if(in_array($get_params['action'],self::$ORDER_API)){
            		$url = self::$LINIO_PROXY_URL;
            	}else{
            		$url = self::$LINIO_PRODUCT_PROXY_URL;
            	}
            }

            $url .= "?";
            $rtn['success'] = true;  //跟proxy之间的网站是否ok
            $rtn['message'] = '';
            $post_params = array();

            // dzt20151126 由于刊登传输数据量大 链接proxy 改为post
// 			foreach($get_params  as $key=>$value){
// 				$url .= "&$key=".urlencode(trim($value));
// 			}

            $handle = curl_init($url);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
            curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30); //连接超时,秒为单位

// 			if (count($post_params)>0){
// 				curl_setopt($handle, CURLOPT_POST, true);
// 				curl_setopt($handle, CURLOPT_POSTFIELDS, $post_params );
// 			}

            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $get_params);

            //  output  header information
            // curl_setopt($handle, CURLINFO_HEADER_OUT , true);

            /* Get the HTML or whatever is linked in $url. */
            $response = curl_exec($handle);
            //\yii::info("lazada proxy connect response:".print_r($response,true),"file");

            $curl_errno = curl_errno($handle);
            $curl_error = curl_error($handle);
            if ($curl_errno > 0) { // network error
                $rtn['message'] = "local:cURL Error $curl_errno : $curl_error";
                $rtn['success'] = false;
                $rtn['response'] = "";
                $rtn['state_code'] = $curl_errno;// 后面可以总结一下 无论是proxy还是本机的属于网络问题的 可以根据状态码重试 ， 目测出现$curl_errno的有可能要重试
                curl_close($handle);
                return $rtn;
            }

            /* Check for 404 (file not found). */
            $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
            //echo $httpCode.$response."\n";
            if ($httpCode == '200') {
                if ($return_type == 'xml') {
                    $rtn['response'] = $response;
                } else $rtn['response'] = json_decode($response, true);

                $rtn['state_code'] = 200;
                if ($rtn['response'] == null) {
                    // json_decode fails
                    $rtn['message'] = "content return from proxy is not in json format, content:" . print_r($response, true);
                    $rtn['success'] = false;
                    $rtn['state_code'] = 500;
                } else if ($rtn['response']['success'] === false) {
                    $rtn['message'] = "proxy:" . $rtn['response']['message'];
                    $rtn['success'] = false;
                    $rtn['state_code'] = 501;// proxy 问题
                }


            } else { // network error
                $rtn['message'] = "local: Failed for " . $get_params["action"] . " , Got error respond code $httpCode from Proxy";
                $rtn['success'] = false;
                $rtn['response'] = "";
                $rtn['state_code'] = $httpCode;
            }
            curl_close($handle);
            return $rtn;

        } catch (\Exception $e) {
            //		curl_close($handle);
            echo "lazada proxy connect exception:" . print_r($e, true);
            \Yii::error("lazada proxy connect exception:" . print_r($e, true), "file");
            $rtn['message'] = 'local:' . $e->getMessage();
            $rtn['success'] = false;
            $rtn['response'] = "";
        }
    }//end of call_amazon_api by proxy


    public static function call_lazada_api_v2($get_params = array(), $TIME_OUT = 60)
    {

        try {
// 			$TIME_OUT=60;//连接和网络读写超时时间，单位s
            $return_type = 'json';

            $config = json_decode($get_params['config'], true);
            $url = self::proxyAssign($config['countryCode']);

            $url .= "?";
            $rtn['success'] = true;  //跟proxy之间的网站是否ok
            $rtn['message'] = '';

            $handle = curl_init($url);
            self::curlAssemble($get_params, $TIME_OUT, $handle);

            /* Get the HTML or whatever is linked in $url. */
            $response = curl_exec($handle);
            if (!self::curlErrorHandle($handle, $rtn)) {
                return $rtn;
            };
            self::responseParse($get_params, $handle, $return_type, $response, $rtn);
            return $rtn;
        } catch (\Exception $e) {
            echo "lazada proxy connect exception:" . print_r($e, true);
            CommonHelper::log("lazada proxy connect exception:" . json_encode($e), self::LOG_ID, LogType::ERROR);
            $rtn['message'] = 'local:' . $e->getMessage();
            $rtn['success'] = false;
            $rtn['response'] = "";
        }
    }//end of call_amazon_api by proxy


    // $TIME_OUT  s 单位
    // communicate to theWish proxy to get the information through http
    public static function call_lazada_api_old($get_params = array())
    {

        $post_params = array();

        $url = self::$LAZADA_PROXY_URL;

        $url .= "?";
        $rtn['success'] = true;
        $rtn['message'] = '';

        foreach ($get_params as $key => $value) {
            $url .= "&$key=" . urlencode(trim($value));
        }

        $handle = curl_init($url);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);

        if (count($post_params) > 0) {
            curl_setopt($handle, CURLOPT_POST, true);
            curl_setopt($handle, CURLOPT_POSTFIELDS, $post_params);
        }
        //  output  header information
        // curl_setopt($handle, CURLINFO_HEADER_OUT , true);

        /* Get the HTML or whatever is linked in $url. */
        $response = curl_exec($handle);

        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);

        if ($httpCode == '200') {
            //	fputs($fd, $response." \n");
            $rtn['response'] = $response;
        } else {
            $rtn['message'] .= "Failed for  , Got error respond code $httpCode from Proxy";
            $rtn['success'] = false;
            $rtn['response'] = "";
        }

        curl_close($handle);
        return $rtn;

    }

    /**
     * @param $countryCode
     * @return string
     * @internal param $config
     */
    private static function proxyAssign($countryCode)
    {
        if (array_key_exists($countryCode, LazadaApiHelper::getLazadaCountryCodeSiteMapping())) {// lazada使用香港proxy,linio jumia 使用美国Proxy
            $url = self::$LAZADA_PRODUCT_PROXY_URL;
            return $url;
        } else {
            $url = self::$LINIO_PRODUCT_PROXY_URL;
            return $url;
        }
    }

    /**
     * @param $get_params
     * @param $TIME_OUT
     * @param $handle
     */
    private static function curlAssemble($get_params, $TIME_OUT, $handle)
    {
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($handle, CURLOPT_TIMEOUT, $TIME_OUT);
        curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 30); //连接超时,秒为单位
        curl_setopt($handle, CURLOPT_POST, true);
        curl_setopt($handle, CURLOPT_POSTFIELDS, $get_params);
    }

    /**
     * 调用curl过程中是否有错误发生
     * @param $handle
     * @param array $rtn
     * @return bool 如果有错误发生,返回false,否则返回true
     */
    private static function curlErrorHandle($handle, Array &$rtn)
    {
        $curl_errno = curl_errno($handle);
        $curl_error = curl_error($handle);

        if ($curl_errno > 0) { // network error
            $rtn['message'] = "local:cURL Error $curl_errno : $curl_error";
            $rtn['success'] = false;
            $rtn['response'] = "";
            $rtn['state_code'] = $curl_errno;// 后面可以总结一下 无论是proxy还是本机的属于网络问题的 可以根据状态码重试 ， 目测出现$curl_errno的有可能要重试
            curl_close($handle);
            return false;
        }
        return true;
    }

    /**
     * 解析返回值
     * @param $get_params
     * @param $handle
     * @param $return_type
     * @param $response
     * @param $rtn
     * @return mixed
     */
    private static function responseParse($get_params, $handle, $return_type, $response, &$rtn)
    {
        /* Check for 404 (file not found). */
        $httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
        //echo $httpCode.$response."\n";
        if ($httpCode == '200') {
            if ($return_type == 'xml') {
                $rtn['response'] = $response;
            } else $rtn['response'] = json_decode($response, true);

            $rtn['state_code'] = 200;
            if ($rtn['response'] == null) {
                // json_decode fails
                $rtn['message'] = "content return from proxy is not in json format, content:" . print_r($response, true);
                $rtn['success'] = false;
                $rtn['state_code'] = 500;
            } else if ($rtn['response']['success'] === false) {
                $rtn['message'] = "proxy:" . $rtn['response']['message'];
                $rtn['success'] = false;
                $rtn['state_code'] = 501;// proxy 问题
            }


        } else { // network error
            $rtn['message'] = "local: Failed for " . $get_params["action"] . " , Got error respond code $httpCode from Proxy";
            $rtn['success'] = false;
            $rtn['response'] = "";
            $rtn['state_code'] = $httpCode;
        }
        curl_close($handle);
    }
}

?>