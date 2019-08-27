<?php namespace eagle\modules\listing\models;

use eagle\modules\util\helpers\RedisHelper;

class WishRedisQueue 
{

	// const PREFIX = 'queue:product:wish:';
	const TIME_OUT = 3600; 		// 1小时
	const STATUS_PENDING 		= "P";
	const STATUS_COMPLETE 		= "C";
	const STATUS_READY 			= "R";
	const STATUS_SENDING 		= "S";
	const STATUS_FAIL			= "F";

	public $key;
	public $token;
	static protected $PREFIX;

	static public function getPerfix($platform){
		return "queue:product:{$platform}:";
	}

	static function count($platform='wish'){
		return (int)\Yii::$app->redis->lLen(self::getPerfix($platform).'list');
	}

	// 获取所有用户队列
	static function all($platform='wish'){
		// 先去list获取所有的key
		$keys = \Yii::$app->redis->lRange(self::getPerfix($platform).'list',0,-1);
		$rtn = [];
		foreach($keys as $key){
			$rtn[$key] = \Yii::$app->redis->hGetAll(self::getPerfix($platform).$key);
		}
		return $rtn;
	}

	function __construct($token,$platform='wish'){
		$this->token = $token;
		self::$PREFIX = self::getPerfix($platform);
		$this->key = self::$PREFIX.$this->token;
	}

	function add($attr){
		if($this->set($attr)){
			return \Yii::$app->redis->rPush(self::$PREFIX.'list',$this->token);
		}
		return false;
	}

	function get($key=NULL){
		if(!$key){
			$rtn = \Yii::$app->redis->hGetAll($this->key);
		}else{
			$rtn = \Yii::$app->redis->hGet($this->key,$key);
		}
		return $rtn;
	}

	function set($key,$val=NULL){
		if(!$val && is_array($key)){
			$rs = \Yii::$app->redis->hmSet($this->key,$key);
		}else{
			$rs = \Yii::$app->redis->hSet($this->key,$key,$val);
		}
		return $rs;
	}

	function exists(){
		// var_dump($this->key);
		return \Yii::$app->redis->exists($this->key);
	}

	function delete(){
		if(\Yii::$app->redis->del($this->key)){
			return \Yii::$app->redis->lrem(self::$PREFIX.'list',1,$this->token);
		}
		return false;
	}


}