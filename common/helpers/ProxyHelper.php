<?php namespace common\helpers;

use eagle\modules\util\helpers\RedisHelper;

class ProxyHelper 
{

	const REDIS_HOST_KEY = 'proxy_host';

	static public $timeout = 500;
	static public $connect_timeout = 60;
	static public $info;
	static public $host = 'us';


	static private $hosts = [
		'us' => '198.11.178.150',
		'hk' => '203.88.175.7'
	];

	/**
	 * 把配置信息写入redis
	 * @param [type] $host [description]
	 */
	static public function setHost($host){
		//return RedisHelper::hSet(self::REDIS_HOST_KEY,self::$host,$host);
		return RedisHelper::RedisSet(self::REDIS_HOST_KEY,self::$host,$host);
	}

	/**
	 * 从redis中获取host地址信息
	 * 如果环境没有redis则从上面的配置信息中获取$host
	 *
	 * @author hqf 2016-04-26
	 * @return [type] [description]
	 */
	static public function getHost(){
		try{
//			if(!($host = RedisHelper::hGet(self::REDIS_HOST_KEY,self::$host))){
			if(!($host = RedisHelper::RedisGet(self::REDIS_HOST_KEY,self::$host))){
				throw new \Exception("host not found in redis", 404);
			}
		}catch(\Exception $e){
			$host = self::$hosts[self::$host];
		}
		return $host;
	}

	/**
	 * @author hqf 2016-04-19
	 * @param  [type] $url  [description]
	 * @param  array  $get  [description]
	 * @param  [type] $post [description]
	 * @return [type]       [description]
	 */
	static public function send($url, $get = [], $post = NULL){
		$host = 'http://'.self::getHost(self::$host).'/'.$url;
		if(is_array($get) && count($get)){
			$host .= '?'.http_build_query($get);
		}
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $host);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, self::$timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, self::$connect_timeout);
		if(is_array($post) && count($post)){
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		}
		$result = curl_exec($ch);
		self::$info = curl_getinfo($ch);
		if($errno = curl_errno($ch)){
			throw new \Exception(curl_error($ch), $errno);
		}
		if(self::$info['http_code']!=200){
			throw new \Exception($result, self::$info['http_code']);
		}
		$contentType = explode(';',self::$info['content_type'])[0];
		switch($contentType){
			case 'text/json':
				$result = json_decode($result,true);
				break;
			default:
				break;
		}
		return $result;
	}


}