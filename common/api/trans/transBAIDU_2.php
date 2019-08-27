<?php
namespace common\api\trans;

/**
 * 百度翻译查询函数，数字  转 英文
 * @param $query =>'apple'	需要翻译的字符串
 * @param $from =>'auto'	(语言列表：http://api.fanyi.baidu.com/api/trans/product/apidoc?qq-pf-to=pcqq.discussion)
 * @param $to =>'zh'	<不可为auto>(语言列表：http://api.fanyi.baidu.com/api/trans/product/apidoc?qq-pf-to=pcqq.discussion)
 *
 * @return
 * 	Array ( [response] => Array (
 * 							[code] => 0 		//	0（成功）/ 1(失败)
 * 							[msg] => 超时		//	  失败原因
 * 							[data] => 苹果			//	翻译结果
 * 						)
 * )
 * +-------------------------------------------------------------------------------------------
 * log			name	date					note
 * @author		lrq		2016/08/09				初始化
 * +-------------------------------------------------------------------------------------------
 */
define("CURL_TIMEOUT",   10);
define("URL",            "http://api.fanyi.baidu.com/api/trans/vip/translate");
define("APP_ID",         "20151230000008425");
define("SEC_KEY",        "iGk0Uf7EIX_b9DlN2pTn");
class transBAIDU_2
{
	//翻译入口
	static public function translate($query, $from, $to)
	{
		$args = array(
				'q' => $query,
				'appid' => APP_ID,
				'salt' => rand(10000,99999),
				'from' => $from,
				'to' => $to,

		);

		//加密
		$str = APP_ID . $query . $args['salt'] . SEC_KEY;
		$args['sign'] = md5($str);

		$ret = self::call(URL, $args);
		$ret = json_decode($ret, true);
		
		//整理输出信息
		$response = ['code' => 0, 'msg' => '', 'data' => ''];
		if( !empty($ret['error_code']))
		{
		    $response['code'] = 0;
		    $msg = '';
		    switch ($ret['error_code'])
		    {
		    	case '52000':$msg='成功';break;
		    	case '52001':$msg='请求超时';break;
		    	case '52002':$msg='系统错误';break;
		    	case '52003':$msg='未授权用户';break;
		    	case '54000':$msg='必填参数为空';break;
		    	case '58000':$msg='客户端IP非法';break;
		    	case '54001':$msg='签名错误';break;
		    	case '54003':$msg='访问频率受限';break;
		    	case '58001':$msg='译文语言方向不支持';break;
		    	case '54004':$msg='账户余额不足';break;
		    	case '54005':$msg='长query请求频繁';break;
		    }
		    $response['msg'] = $msg;
		}
		else 
		{
		    $response['code'] = 1;
		    $response['data'] = $ret['trans_result'][0]['dst'];
		}
		return $response;
	}

	//发起网络请求
	private static function call($url, $args=null, $method="post", $testflag = 0, $timeout = CURL_TIMEOUT, $headers=array())
	{
		$ret = false;
		$i = 0;
		while($ret === false)
		{
			if($i > 1)
				break;
			if($i > 0)
			{
				sleep(1);
			}
			$ret = self::callOnce($url, $args, $method, false, $timeout, $headers);
			$i++;
		}
		return $ret;
	}

	private static function callOnce($url, $args=null, $method="post", $withCookie = false, $timeout = CURL_TIMEOUT, $headers=array())
	{
		$ch = curl_init();
		if($method == "post")
		{
			$data = self::convert($args);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			curl_setopt($ch, CURLOPT_POST, 1);
		}
		else
		{
			$data = self::convert($args);
			if($data)
			{
				if(stripos($url, "?") > 0)
				{
					$url .= "&$data";
				}
				else
				{
					$url .= "?$data";
				}
			}
		}
		
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if(!empty($headers))
		{
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		if($withCookie)
		{
			curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
		}
		$r = curl_exec($ch);
		curl_close($ch);
		return $r;
	}

	private static function convert(&$args)
	{
		$data = '';
		if (is_array($args))
		{
			foreach ($args as $key=>$val)
			{
				if (is_array($val))
				{
					foreach ($val as $k=>$v)
					{
						$data .= $key.'['.$k.']='.rawurlencode($v).'&';
					}
				}
				else
				{
					$data .="$key=".rawurlencode($val)."&";
				}
			}
			return trim($data, "&");
		}
		return $args;
	}
}
?>