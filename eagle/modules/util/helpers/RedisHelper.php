<?php namespace eagle\modules\util\helpers;

use eagle\modules\order\models\OdOrder;
/*
 * Author: Hua Qing Feng
 * */

class RedisHelper
{
	const SINGLE_THREAD = 'tmp:single-thread:';
	static $redisConnection = '';
	static $last_redis_error_msg ='';
	
	static function hmSet($key,Array $array){
		$params = [$key];
		foreach($array as $k=>$v){
			$params[]=$k;
			$params[]=$v;
		}
		$res = \Yii::$app->redis->executeCommand(strtolower(__FUNCTION__),$params);
		return $res;
	}

	static function hmGet($key,Array $array){
		$params = array_merge_recursive([$key],$array);
		$data = \Yii::$app->redis->executeCommand(strtolower(__FUNCTION__),$params);
		return array_combine($array,$data);
	}

	static function hGetAll($key){
		$data = \Yii::$app->redis->executeCommand(strtolower(__FUNCTION__),func_get_args());
		$rtn = [];
		foreach($data as $id=>$item){
			if(!($id%2)){
				$rtn[$item] = $data[$id+1];
			}
		}
		return $rtn;
	}


	static function executeCommand(){
		return call_user_func_array([\Yii::$app->redis,'executeCommand'], func_get_args());
	}

	/**
	 * hdel,del,set,get,exists,hset,hget,lIndex
	 * @return [type] [description]
	 */
	static public function __callStatic($name,$args){
		return \Yii::$app->redis->executeCommand(strtolower($name),$args);
	}
	
	/**
	 * 取单线程回调
	 * @return [type] [description]
	 */
	static public function singleThread($val){
		$key = self::SINGLE_THREAD.debug_backtrace()[0]['class'].':'.debug_backtrace()[0]['function'];
		return !(int)self::hset($key,serialize($val),1);
	}

	static public function slowLog($count = NULL){
		$arg = ['get'];
		if($count){
			$arg[]=$count;
		}
		return \Yii::$app->redis->executeCommand('SLOWLOG',$arg);
	}
	
	
	/*Author Yang zengqiang
	 * Date: 2016-12-21
	 * Make function for normal redis get and set, and execute command, 
	 * try catch and wait for 4 secs and retry if got exception
	 * */
	
	
	//return 是执行后影响的行数,或者读取到的内容，return -1就是执行不成功, 可以通过制定 GetRedisLastError() 读取错误msg
	public static function RedisGet($keyL1,$keyL2 ) {
		self::$last_redis_error_msg='';
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		$rtn = 0;
		try{
			$rtn = self::$redisConnection->hget($keyL1, $keyL2 );
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection->hget($keyL1, $keyL2 );
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
		return $rtn;
	}

	//return 是执行后影响的行数,或者读取到的内容，return -1就是执行不成功, 可以通过制定 GetRedisLastError() 读取错误msg
	public static function RedisGetAll($keyL1 ) {
		self::$last_redis_error_msg='';
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		$rtn = array();
		try{
			$rtn = self::$redisConnection->hgetall($keyL1 );
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection->hgetall($keyL1 );
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
		
		if (is_array($rtn)){
			$data = $rtn;
			$rtn = [];
			foreach($data as $id=>$item){
				if(!($id%2)){
					$rtn[$item] = $data[$id+1];
				}
			}
		}
		return $rtn;
	}	
	
	public static function RedisSet($keyL1,$keyL2 , $val=0) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		
		$rtn = 0;
		try{
			$rtn = self::$redisConnection ->hset($keyL1, $keyL2,  $val);
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection ->hset($keyL1, $keyL2,  $val);
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".' '.$keyL1.' '.__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
	
		return $rtn;
	
	}
	
	public static function RedisMSet($keyL1, $valueArray=array()) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		$redisParam = array();
		$redisParam [] = $keyL1;
		
		//$commandLine='$rtn = self::$redisConnection ->hmset($keyL1';
		
		foreach ($valueArray as $key=>$value){
// 			$commandLine.=" , $key ,  ".$value;
			//$commandLine.=" , '$key' , '$value'";
			$redisParam [] = $key;
			$redisParam [] = $value;
		}
		
		//$commandLine .=");";
		
		$rtn = 0;
		try{
			
			RedisHelper::RedisExe ('hmset',$redisParam);
			
			//eval($commandLine);
		}catch(\Exception $e) {
			try{ sleep(4);
			RedisHelper::RedisExe ('hmset',$redisParam);
				//eval($commandLine);
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
	
		return $rtn;
	
	}
	
	public static function RedisCreate($keyL1,$keyL2 , $val=0) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
	
		$rtn = 0;
		try{
			$rtn = self::$redisConnection ->hsetnx($keyL1, $keyL2,  $val);
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection ->hsetnx($keyL1, $keyL2,  $val);
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
	
		return $rtn;
	
	}
	
	public static function RedisDelete($keyL1,$keyL2 ) {
		return self::RedisDel($keyL1, $keyL2);
	}
	
	public static function RedisDel($keyL1,$keyL2 ) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		
		$rtn = 0;
		try{
			$rtn = self::$redisConnection ->hdel($keyL1, $keyL2 );
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection ->hdel($keyL1, $keyL2 );
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
	
		return $rtn;
	}
	
	public static function RedisExe($keyL1,$keyL2 ) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		
		$rtn = 0;
		try{
			$rtn = self::$redisConnection ->executeCommand($keyL1, $keyL2 );
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection ->executeCommand($keyL1, $keyL2 );
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
	
		return $rtn;
	}
	
	//increament 可以写负数的
	public static function RedisAdd($keyL1 , $keyL2 , $increment=1) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		$rtn = 0;
	 
		try{
			$rtn = self::$redisConnection->HINCRBY($keyL1 , $keyL2 , $increment);
		}catch(\Exception $e) {
			try{
				sleep(4);
				$rtn = self::$redisConnection->HINCRBY($keyL1 , $keyL2 , $increment);
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
	
		return $rtn;
	}
	
	
	
	public static function GetRedisLastError() {
		return self::$last_redis_error_msg  ;
	}

	/* author: YZQ
	 * 下面的这些带2结尾的，fucntion，是对Redis server 2进行操作，和默认的1号机用法一样的
	 * */
	public static function RedisGet2($keyL1,$keyL2 ) {
		self::$redisConnection = \Yii::$app->redis;
		return self::RedisGet($keyL1,$keyL2 );
	}
	
	public static function RedisGetAll2($keyL1 ) {
		self::$redisConnection = \Yii::$app->redis;
		return self::RedisGetAll($keyL1 );
	}
	
	public static function RedisSet2($keyL1,$keyL2 , $val=0) {
		self::$redisConnection = \Yii::$app->redis;
		return self::RedisSet($keyL1,$keyL2 , $val );
	}
	
	public static function RedisCreate2($keyL1,$keyL2 , $val=0) {
		self::$redisConnection = \Yii::$app->redis;
		return self::RedisCreate($keyL1,$keyL2 , $val );
	}
	
	public static function RedisDel2($keyL1,$keyL2 ) {
		self::$redisConnection = \Yii::$app->redis;
		return self::RedisDel($keyL1,$keyL2 );
	}
	
	public static function RedisExe2($keyL1,$keyL2 ) {
		self::$redisConnection = \Yii::$app->redis;
		return self::RedisExe($keyL1,$keyL2 );
	}
	
	public static function RedisAdd2($keyL1 , $keyL2 , $increment=1) {
		self::$redisConnection = \Yii::$app->redis;
		return self::RedisAdd($keyL1 , $keyL2 , $increment );
	}
	
	
	
	//do the old redis,for conversion use only
	public static function RedisGet1($keyL1,$keyL2 ) {
		self::$last_redis_error_msg='';
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		$rtn = 0;
		try{
			$rtn = self::$redisConnection->hget($keyL1, $keyL2 );
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection->hget($keyL1, $keyL2 );
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
		return $rtn;
	}
	
	//return 是执行后影响的行数,或者读取到的内容，return -1就是执行不成功, 可以通过制定 GetRedisLastError() 读取错误msg
	public static function RedisGetAll1($keyL1 ) {
		self::$last_redis_error_msg='';
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		$rtn = 0;
		try{
			$rtn = self::$redisConnection->hgetall($keyL1 );
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection->hgetall($keyL1 );
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
		return $rtn;
	}
	
	public static function RedisSet1($keyL1,$keyL2 , $val=0) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
	
		$rtn = 0;
		try{
			$rtn = self::$redisConnection ->hset($keyL1, $keyL2,  $val);
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection ->hset($keyL1, $keyL2,  $val);
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
	
		return $rtn;
	
	}
	
	public static function RedisDel1($keyL1,$keyL2 ) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
	
		$rtn = 0;
		try{
			$rtn = self::$redisConnection ->hdel($keyL1, $keyL2 );
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection ->hdel($keyL1, $keyL2 );
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
	
		return $rtn;
	}
	
	public static function RedisExe1($keyL1,$keyL2 ) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
	
		$rtn = 0;
		try{
			$rtn = self::$redisConnection ->executeCommand($keyL1, $keyL2 );
		}catch(\Exception $e) {
			try{ sleep(4);
			$rtn = self::$redisConnection ->executeCommand($keyL1, $keyL2 );
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
	
		return $rtn;
	}
	
	//increament 可以写负数的
	public static function RedisAdd1($keyL1 , $keyL2 , $increment=1) {
		//默认使用redis 服务器1
		self::$redisConnection = \Yii::$app->redis;
		$rtn = 0;
	
		try{
			$rtn = self::$redisConnection->HINCRBY($keyL1 , $keyL2 , $increment);
		}catch(\Exception $e) {
			try{
				sleep(4);
				$rtn = self::$redisConnection->HINCRBY($keyL1 , $keyL2 , $increment);
			}catch(\Exception $e) {
				$rtn = -1;
				self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
				echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
			}
		}
		
		return $rtn;
	}
	
	public static function RedisSGet($keyL1) {
	    self::$last_redis_error_msg='';
	    //默认使用redis 服务器1
	    self::$redisConnection = \Yii::$app->redis;
	    $rtn = 0;
	    try{
	        $rtn = self::$redisConnection->get($keyL1);
	    }catch(\Exception $e) {
	        try{ sleep(4);
	        $rtn = self::$redisConnection->get($keyL1);
	        }catch(\Exception $e) {
	            $rtn = -1;
	            self::$last_redis_error_msg = $e->getMessage().' '.$e->getFile().' '.$e->getLine();
	            echo "Got Exception  ".__FUNCTION__.' '.self::$last_redis_error_msg."\n";
	        }
	    }
	    return $rtn;
	}
	
	//save cache in Redis,
	public static function setOrderCache($puid=0,$platform='all',$category='',$val=''){
		$keyL1="OrderTempData";
		self::RedisSet($keyL1, $puid."_".$platform."_".$category,$val);
	}
	
	//get cache in Redis
	public static function getOrderCache($puid=0,$platform='all',$category='',$stroe='all' ){
		$keyL1="OrderTempData";
		return self::RedisGet($keyL1, $puid."_".$platform."_".$category );
	}
	
	//remove cache in Redis, if any
	public static function delOrderCache($puid=0,$platform='all',$category='' ){
		$keyL1="OrderTempData";
		$allOrderRelatedCats= array('nations','PagesModels','MenuStatisticData');
		if (empty($category) ){
			foreach ($allOrderRelatedCats as $aCat)
				self::RedisDel($keyL1, $puid."_".$platform."_".$aCat );
		}else 
			self::RedisDel($keyL1, $puid."_".$platform."_".$category );
		
		//self::delOrderCache2($puid, $platform, $category);
	}
	
	
	
	/*
	 * 	//del order cache in Redis
	public static function delOrderCache2($puid,$platform='all',$category=''){
		$keyL1="OrderTempData";
		$allOrderRelatedCats= array('nations','PagesModels','MenuStatisticData');
		if (empty($category) ){
			foreach ($allOrderRelatedCats as $aCat)
				self::RedisDel($keyL1, $puid."_".$platform."_".$aCat );
		}else
			self::RedisDel($keyL1, $puid."_".$platform."_".$category );
	}
	*/
	######################引入平台账号权限管理后的redis#########################开始
	/* save order cache in Redis,
	 * value 的结构: [
	 * 			uid1=>[all=>counterAll,storeA=>counterA,storeB=>counterB...],
	 * 			uid2=>[all=>counterAll,storeA=>counterA,storeC=>counterC...],
	 * 			...
	 * 		]
	 */
	public static function setOrderCache2($puid=0,$uid=0,$platform='all',$category='',$stroe='all',$val=''){
		$keyL1="OrderTempData";
		if(empty($uid))
			$uid = \Yii::$app->user->id;
		//初始化outdated数据
		//self::delOrderCache($puid,$platform,$category);
		self::RedisDel($keyL1, $puid."_".$uid."_".$platform."_".$category);
		
		
		$redis_data = self::RedisGet($keyL1, $puid."_".$platform."_".$category );
		$cache = empty($redis_data)?[]:json_decode($redis_data,true);
		
		$cache[$uid][$stroe] = $val;
		
		self::RedisSet($keyL1, $puid."_".$platform."_".$category,json_encode($cache));
	}
	
	//引入平台账号权限管理后的 get order cache in Redis
	public static function getOrderCache2($puid,$uid,$platform='all',$category='',$stroe='all' ){
		$keyL1="OrderTempData";
		if(empty($uid))
			$uid = \Yii::$app->user->id;
		//初始化outdated数据
		//self::delOrderCache($puid,$platform,$category);
		self::RedisDel($keyL1, $puid."_".$uid."_".$platform."_".$category);
		
		$redis_data = self::RedisGet($keyL1, $puid."_".$platform."_".$category );
		$cache = empty($redis_data)?[]:json_decode($redis_data,true);
		if(empty($cache[$uid][$stroe]))
			return [];
		else 
			return $cache[$uid][$stroe];
	}
	
	//引入平台账号权限管理后的 del order cache in Redis(和delOrderCache一样...)
	public static function delOrderCache2($puid,$platform='all',$category=''){
		$keyL1="OrderTempData";
		$allOrderRelatedCats= array('nations','PagesModels','MenuStatisticData');
		$order_source = OdOrder::$orderSource;
		if($platform=='all'){
			foreach ($order_source as $pf=>$name){
				if (empty($category) ){
					foreach ($allOrderRelatedCats as $aCat)
						self::RedisDel($keyL1, $puid."_".$pf."_".$aCat );
				}else
					self::RedisDel($keyL1, $puid."_".$pf."_".$category );
			}
		}else{
			if (empty($category) ){
				foreach ($allOrderRelatedCats as $aCat)
					self::RedisDel($keyL1, $puid."_".$platform."_".$aCat );
			}else
				self::RedisDel($keyL1, $puid."_".$platform."_".$category );
		}
		
	}
	
	/* 引入平台账号权限管理后的 save message cache in Redis,
	 * 
	 */
	public static function setMessageCache($puid=0,$uid=0,$category,$val=''){
		$keyL1="MessageTempData";
		if(empty($uid))
			$uid = \Yii::$app->user->id;
		//初始化outdated数据
		//self::RedisDel($keyL1, $puid."_".$uid."_".$platform."_".$category);
	
	
		$redis_data = self::RedisGet($keyL1, $puid."_".$category );
		$cache = empty($redis_data)?[]:json_decode($redis_data,true);
	
		$cache[$uid] = $val;
	
		self::RedisSet($keyL1, $puid."_".$category,json_encode($cache));
	}
	
	//引入平台账号权限管理后的 get message cache in Redis
	public static function getMessageCache($puid,$uid,$category=''){
		$keyL1="MessageTempData";
		if(empty($uid))
			$uid = \Yii::$app->user->id;
		//初始化outdated数据
		//self::RedisDel($keyL1, $puid."_".$uid."_".$platform."_".$category);
	
		$redis_data = self::RedisGet($keyL1, $puid."_".$category );
		$cache = empty($redis_data)?[]:json_decode($redis_data,true);
		if(empty($cache[$uid]))
			return [];
		else
			return $cache[$uid];
	}
	
	//引入平台账号权限管理后的 del message cache in Redis
	public static function delMessageCache($puid,$category=''){
		$keyL1="MessageTempData";
		$allOrderRelatedCats= array('MenuStatisticData');
		if (empty($category) ){
			foreach ($allOrderRelatedCats as $aCat)
				self::RedisDel($keyL1, $puid."_".$aCat );
		}else
			self::RedisDel($keyL1, $puid."_".$category );
	}
			
	######################引入平台账号权限管理后的redis#########################结束
	
	
}