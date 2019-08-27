<?php
namespace common\api\aliexpressinterface;

use \Yii;
use common\helpers\Helper_Array;
use common\helpers\Helper_Curl;
use eagle\models\Counter;
use eagle\modules\util\helpers\RedisHelper;

class AliexpressInterface_Base
{
    protected $access_token = ''; // 访问 token .
    protected $selleruserid = '';
    protected $AppKey;
    protected $appSecret;
    protected $host = 'gw.api.alibaba.com';
    protected $hosturl = 'hzapi.alibaba.com/openapi/';//'110.75.69.81/openapi/';//gw.api.alibaba.com域名访问暂时改成IP：110.75.69.81 访问
    protected $site = 'aliexpress';
    protected $dev_account = [['app_key' => '@XXX@', 'app_secret' => '@XXX@', 'min_uid' => 1, 'max_uid' => 2000],
        ['app_key' => '@XXX@', 'app_secret' => '@XXX@', 'min_uid' => 2001, 'max_uid' => 4000]
    ];

    public function __get($name)
    {
        if ('access_token' === $name) {
            return $this->access_token;
        } else {
            return false;
        }
    }


    public function __set($name, $value)
    {
        //hack 用来定位用户对应的速卖通开发者账号
        if ('access_token' === $name) {
            $this->access_token = $value;
            if (!empty($_SESSION['aliexpress_dev_account'])) {
                $this->AppKey = $_SESSION['aliexpress_dev_account']['app_key'];
                $this->appSecret = $_SESSION['aliexpress_dev_account']['app_secret'];
            }
        }
    }


    function __construct()
    {
        $this->_config();
    }

    //根据用户id分配对应开发者账号，授权或重新绑定时使用
    public function assignDevAccount($uid)
    {
        foreach ($this->dev_account as $account) {
            if ($uid >= $account['min_uid'] && $uid <= $account['max_uid']) {
                $this->AppKey = $account['app_key'];
                $this->appSecret = $account['app_secret'];
                return $account;
            }
        }

        $account = $this->dev_account[(count($this->dev_account) - 1)];
        $this->AppKey = $account['app_key'];
        $this->appSecret = $account['app_secret'];
        return $account;
    }


    /**
     * 配置
     *
     * 【速卖通API|重要通知】
     * 由于服务机房迁移，目前请各位将域名绑定
     * 如：原来绑定 110.75.216.110 gw.api.alibaba.com
     * 切换成新的绑定：110.75.69.81 gw.api.alibaba.com
     * 请相互通知！谢谢合作请尽快迁移
     * 如未做绑定，可取消绑定 直接访问网页，如遇503类似错误，IP地址访问美国了，可设置成新的绑定地址。
     * 14-6-24
     */
    function _config()
    {
        $this->AppKey = '@XXX@';
        $this->appSecret = '@XXX@';
        $this->host = 'gw.api.alibaba.com';
        $this->hosturl = 'hzapi.alibaba.com/openapi/';//'110.75.69.81/openapi/';//gw.api.alibaba.com域名访问暂时改成IP：110.75.69.81 访问
        $this->site = 'aliexpress';
    }


    /**
     *  签名 signature 算法
     */
    function signature($appParamArr1 = null, $appParamArr = null)
    {
        ksort($appParamArr);

        $sign = $appParamArr1;
        foreach ($appParamArr as $key => $val) {
            if ($key != '' && $val != '') {
                $sign .= $key . $val;
            }
        }
        $sign = strtoupper(bin2hex(hash_hmac("sha1", $sign, $this->appSecret, true)));
        return $sign;
    }

    /**
     *  签名 signature 算法 速卖通官网示例
     */
    function signature2($appParamArr1 = null, $appParamArr = null)
    {
        $sign = $appParamArr1;
        $tmp = [];
        foreach ($appParamArr as $key => $val) {
            $tmp[] = $key . $val;
        }

        sort($tmp);
        $sign_str = join('', $tmp);
        $sign .= $sign_str;

        $sign = strtoupper(bin2hex(hash_hmac("sha1", $sign, $this->appSecret, true)));
        return $sign;
    }


    /***
     * 基础的api请求方法
     */
    function request($apiName, $param, $is_signature = 1)
    {
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));


        // 基本的 url
        $baseUrlParam = 'param2/1/aliexpress.open/' . 'api.' . $apiName . '/' . $this->AppKey;

        $baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');

        $signature = $this->signature2($baseUrlParam, $param);

//       $signature = $this->signature($baseUrlParam,$param);
        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $signature;
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;

        $d = Helper_Curl::post($baseUrl, $param_request);
        $r = $this->response($d);
        /*
                if (isset($r['error_code']) && $r['error_message'] == 'Request need user authorized'){
                    //重新获取token
                    $auth_api = new AliexpressInterface_Auth();
                    $this->access_token = $auth_api->getAccessToken($this->selleruserid,TRUE);
                    $param['access_token'] = $this->access_token;
                        unset($param['_aop_signature']);
                    Helper_Array::removeEmpty($param);
                    $param_str='';
                    foreach($param as $k=>$v){
                        $param_str.="&$k=".$v;
                    }
                    $param_str=ltrim($param_str,'&');
                    $signature = $this->signature($baseUrlParam,$param);
                    $url=$baseUrl.'?'.$param_str.'&_aop_signature='.$signature;
                    if($is_signature)$param['_aop_signature'] = $signature;
                    $param_request=$param;
                    $d=Helper_Curl::post($baseUrl,$param_request);
                    $r = $this->response($d);
                }
        */
        return $r;
    }

    //上传图片专用的请求
    function request2($apiName, $param, $imageurl)
    {
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));

        // 基本的 url
        $baseUrlParam = 'param2/1/aliexpress.open/' . 'api.' . $apiName . '/' . $this->AppKey;

        $baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');
        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $this->signature($baseUrlParam, $param);
        $img = @file_get_contents($imageurl);
        $http_entity_type = 'application/x-www-from-urlencoded';
        $headers = array("Content-type: " . $http_entity_type);
        $d = Helper_Curl::post($url, $img, $headers);
        $r = $this->response($d);
        /*
                if (isset($r['error_code']) && $r['error_message'] == 'Request need user authorized'){
                    //重新获取token
                    $auth_api = new AliexpressInterface_Auth();
                    $this->access_token = $auth_api->getAccessToken($this->selleruserid,TRUE);

                    $param['access_token']=$this->access_token;
                    unset($param['_aop_signature']);
                    Helper_Array::removeEmpty($param);
                    $param_str='';
                    foreach($param as $k=>$v){
                        $param_str.="&$k=".$v;
                    }
                    $param_str=ltrim($param_str,'&');
                    $url=$baseUrl.'?'.$param_str.'&_aop_signature='.$this->signature($baseUrlParam,$param);
                    $img = @file_get_contents($imageurl);
                    $http_entity_type = 'application/x-www-from-urlencoded';
                    $headers = array("Content-type: " .$http_entity_type);
                    $d=Helper_Curl::post($url,$img,$headers);
                    $r = $this->response($d);
                }
        */
        return $r;
    }

    /**
     * 该request3跟request主要差别在于调用Helper_Curl::post2
     */
    function request3($apiName, $param, $is_signature = 1)
    {
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));

        // 基本的 url
        $baseUrlParam = 'param2/1/aliexpress.open/' . 'api.' . $apiName . '/' . $this->AppKey;

        $baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');

        $signature = $this->signature2($baseUrlParam, $param);

        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $signature;
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;

        $d = Helper_Curl::post2($baseUrl, $param_request, null, false, null, true);
        $r = $this->response($d);
        return $r;
    }

    function response($data)
    {
        //记录请求次数
        /* $date = date("YmdH",time());
        $command = Yii::$app->db->createCommand("update counter set times=times+1 where id =". $date);
        $affectRows = $command->execute();
        if ($affectRows==0){
            $command = Yii::$app->db->createCommand("insert into `counter` (`id`,`times`) values (".$date.",0)");
            $affectRows = $command->execute();
            $command = Yii::$app->db->createCommand("update counter set times=times+1 where id =". $date);
            $affectRows = $command->execute();
        } */

        $arr = json_decode($data, true);
        return $arr;
    }

    /***
     * 基础的api请求方法
     */
    function request4($apiName,$param,$is_signature=1){
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));


        // 基本的 url
        $baseUrlParam='param2/1/aliexpress.open/'.$apiName.'/'.$this->AppKey;

        $baseUrl='http://'.$this->hosturl.$baseUrlParam;

        //各 api的 参数
        $param_base=array(
                'access_token'=>$this->access_token,
        );
        if(is_array($param)){
            $param=$param_base+$param;
        }else{
            $param=$param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str='';
        foreach($param as $k=>$v){
            $param_str.="&$k=".$v;
        }
        $param_str=ltrim($param_str,'&');

        $signature = $this->signature2($baseUrlParam,$param);

//       $signature = $this->signature($baseUrlParam,$param);
        $url=$baseUrl.'?'.$param_str.'&_aop_signature='.$signature;
        if($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request=$param;

        $d=Helper_Curl::post($baseUrl,$param_request);
        $r = $this->response($d);

        return $r;
    }

    /**
     * 日期格式
     */
    function datetime($time = null)
    {
        if (empty($time)) {
            $time = time();
        }
        return date('m/d/Y', $time);
    }


    /***
 * 基础的api请求方法
 */
    function request5($apiName, $param, $is_signature = 1)
    {
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));


        // 基本的 url
        $baseUrlParam = 'param2/1/aliexpress.open/' . 'alibaba.ae.api.' . $apiName . '/' . $this->AppKey;

        $baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');

        $signature = $this->signature2($baseUrlParam, $param);

//       $signature = $this->signature($baseUrlParam,$param);
        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $signature;
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;

        $d = Helper_Curl::post($baseUrl, $param_request);
        $r = $this->response($d);
        /*
                if (isset($r['error_code']) && $r['error_message'] == 'Request need user authorized'){
                    //重新获取token
                    $auth_api = new AliexpressInterface_Auth();
                    $this->access_token = $auth_api->getAccessToken($this->selleruserid,TRUE);
                    $param['access_token'] = $this->access_token;
                        unset($param['_aop_signature']);
                    Helper_Array::removeEmpty($param);
                    $param_str='';
                    foreach($param as $k=>$v){
                        $param_str.="&$k=".$v;
                    }
                    $param_str=ltrim($param_str,'&');
                    $signature = $this->signature($baseUrlParam,$param);
                    $url=$baseUrl.'?'.$param_str.'&_aop_signature='.$signature;
                    if($is_signature)$param['_aop_signature'] = $signature;
                    $param_request=$param;
                    $d=Helper_Curl::post($baseUrl,$param_request);
                    $r = $this->response($d);
                }
        */
        return $r;
    }






    /***
     * 基础的api请求方法
     */
    function request6($apiName, $param, $is_signature = 1)
    {
        //统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));


        // 基本的 url
        $baseUrlParam = 'param2/1/aliexpress.open/' . 'alibaba.ae.seller.' . $apiName . '/' . $this->AppKey;

        $baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');

        $signature = $this->signature2($baseUrlParam, $param);

//       $signature = $this->signature($baseUrlParam,$param);
        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $signature;
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;

        $d = Helper_Curl::post($baseUrl, $param_request);
        $r = $this->response($d);

        return $r;
    }
    
    /***
     * 基础的api请求方法
    */
    function request7($apiName, $param, $is_signature = 1)
    {
    	//统计api调用情况
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d'));
        //每小时统计次数信息
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, $apiName.date('_Y_m_d_H'));
        RedisHelper::RedisAdd('aliexpress_api_call_'.$this->AppKey, 'all'.date('_Y_m_d_H'));
    
    
    	// 基本的 url
    	$baseUrlParam = 'param2/1/aliexpress.open/' . 'alibaba.ae.logistics.' . $apiName . '/' . $this->AppKey;
    
    	$baseUrl = 'http://' . $this->hosturl . $baseUrlParam;

        //各 api的 参数
        $param_base = array(
            'access_token' => $this->access_token,
        );
        if (is_array($param)) {
            $param = $param_base + $param;
        } else {
            $param = $param_base;
        }
        Helper_Array::removeEmpty($param);
        $param_str = '';
        foreach ($param as $k => $v) {
            $param_str .= "&$k=" . $v;
        }
        $param_str = ltrim($param_str, '&');

        $signature = $this->signature2($baseUrlParam, $param);

//       $signature = $this->signature($baseUrlParam,$param);
        $url = $baseUrl . '?' . $param_str . '&_aop_signature=' . $signature;
        if ($is_signature) {
            $param['_aop_signature'] = $signature;
        }
        $param_request = $param;

        $d = Helper_Curl::post($baseUrl, $param_request);
        $r = $this->response($d);
    
    	return $r;
    }

}
