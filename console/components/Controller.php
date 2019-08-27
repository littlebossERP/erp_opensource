<?php namespace console\components;

use \yii\console\Controller as YiiController;
use \eagle\modules\util\helpers\ConfigHelper;
use \eagle\modules\util\helpers\RedisHelper;

class Controller extends YiiController
{
	const SINGLE_THREAD = 'tmp:single-thread:';
	const THREAD_COUNT = 'tmp:thread-count:';

	static $version;

	protected $singleThreadKey;

	/**
	 * 取单线程回调
	 * @return [type] [description]
	 */
	protected function singleThread($val,$callback){
		if(!$this->singleThreadKey){
			throw new \Exception("Please addThread first!", 400);
		}
		if(\Yii::$app->redis->hset(self::SINGLE_THREAD.$this->singleThreadKey,serialize($val),1)){
			$callback($val);
		}
	}

	protected function addThread($callback,$key=NULL){
		$this->singleThreadKey = $key ? $key:debug_backtrace()[1]['class'].':'.debug_backtrace()[1]['function'];
		$count = \Yii::$app->redis->get(self::THREAD_COUNT.$this->singleThreadKey);
		\Yii::$app->redis->set(self::THREAD_COUNT.$this->singleThreadKey,$count+1);
		try{
			$callback();
		}catch(\Exception $e){
			$this->actionSingleThreadReset($this->singleThreadKey);
			throw new \Exception($e->getMessage(), $e->getCode(),$e->getPrevious());
		}
		// 结束检测是否完成全部进程
		if($count = (int)\Yii::$app->redis->get(self::THREAD_COUNT.$this->singleThreadKey) -1){
			\Yii::$app->redis->set(self::THREAD_COUNT.$this->singleThreadKey,$count);
		}else{
			$this->actionSingleThreadReset($this->singleThreadKey);
		}
	}

	protected function queueVersion(&$version,$key){
        $currentVersion = ConfigHelper::getGlobalConfig ( $key, 'NO_CACHE' );
        if(!$currentVersion){
            $currentVersion = 0;
        }
        if(!$version){
            $version = $currentVersion;
        }
        if($version != $currentVersion){
            throw new \Exception('版本不一致，退出脚本', 1);
            return false;
        }
        return true;
    }

    // ----------  public function  --------------

	public function actionSingleThreadReset($key){
		\Yii::$app->redis->del(self::THREAD_COUNT.$key);
		\Yii::$app->redis->del(self::SINGLE_THREAD.$key);
		echo 'all thread went done!'.PHP_EOL;
	}

}