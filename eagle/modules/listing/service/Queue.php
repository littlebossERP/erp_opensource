<?php namespace eagle\modules\listing\service;

use eagle\modules\listing\service\Log;
use eagle\modules\listing\models\SyncProductApiQueue;
use eagle\modules\listing\models\WishRedisQueue;
use eagle\modules\util\helpers\RedisHelper;
use eagle\models\QueueProductLog;

class Queue 
{

	const STATUS_PENDING 		= -1;
	const STATUS_COMPLETE 		= -2;
	const STATUS_READY 			= 0;

	static public $REDIS_KEY_PREFIX_PRODUCT;

	public $platform;

	function __construct($platform){
		$this->platform = strtolower($platform);
		$this->accountClass = __NAMESPACE__.'\\'.$this->platform."\\Account";
		self::$REDIS_KEY_PREFIX_PRODUCT = WishRedisQueue::getPerfix($this->platform);
	}

	function fail($queue,$log,$msg=''){
		$queue->delete();
		if($log){
			$log->status = WishRedisQueue::STATUS_FAIL;
			$log->save();
		}
		Log::info('ERROR! -- Sync Failed:'.$msg);
		return;
	}

	function clearById($queue_id){
		$log = QueueProductLog::findOne($queue_id);
		$queue = new WishRedisQueue($log->shop,$log->platform);
		return $this->fail($queue,$log,'clear');
	}

	/**
	 * 同步商品队列
	 * $fnPerToken 每个token完成后的回调处理函数
	 * @return [type] [description]
	 */
	function executeProduct($fnPerToken = NULL){
		$AccountClass = __NAMESPACE__.'\\'.$this->platform."\\Account";
		// 获取redis队列
		$rtn = [];
		if(WishRedisQueue::count($this->platform)){
			$queues = WishRedisQueue::all($this->platform);
			foreach($queues as $token=>$info){
				$queue = new WishRedisQueue($token,$this->platform);
				$account = $AccountClass::getAccountByToken($token);
				$log = QueueProductLog::findOne((int)$info['log_id']);
				// var_dump($info['log_id']);
				// var_dump($log);
				// 如果token过期
				if(!$account){
					$this->fail($queue,$log,'access_token has revoked.');
					continue;
				}
				
				if(!$log){
					Log::info('log_id mysql not exists.'.$info['log_id']);
					$queue->delete();
					continue;
				}
				// var_dump($info);
				// 判断当前状态是否正在运行
				switch($info['status']){
					case WishRedisQueue::STATUS_PENDING:
						// 设置ready状态
						// $queue->set('status',WishRedisQueue::STATUS_READY);
						// 开始同步
						$result = $this->executeProductByToken($token);
						// var_dump($result);
						if($result['success']){
							$log->update_time = date("Y-m-d H:i:s");
							$log->status = WishRedisQueue::STATUS_COMPLETE;
							$log->total_product = $this->getProgress($info['log_id']);
							$log->save();
							$queue->delete();
							// 更新最后同步时间戳
							if(!($upLastTime = $AccountClass::updateLastProductSuccessTimeByToken($token))){
								Log::info('ERROR! -- update last_time failed！');
								continue;
							}
						}else{
							$this->fail($queue,$log,'sync product failed'.$result['code']);
							continue;
						}
						break;
					case WishRedisQueue::STATUS_SENDING: 		// 正在同步中，不进行其他操作，防止影响
						// 判断创建时间，如果大于阈值表示之前的被中断了
						if( !isset($info['create_time']) || ( time() > $info['create_time'] + WishRedisQueue::TIME_OUT) ){
							// 标记失败
							$this->fail($queue,$log,'last sync was breaked');
							continue;
						}
						break;
					case WishRedisQueue::STATUS_READY: 		// 准备同步
						
						$log->status = WishRedisQueue::STATUS_SENDING;
						$log->save();
					default: 						// 正在同步
						break;
				}
				if($fnPerToken != NULL){
					$fnPerToken($log,$info['status']);
				}
			}
			$rtn['success'] = true;
			$rtn['code'] = 200;
		}else{
			// 队列为空
			$rtn['success'] = true;
			$rtn['code'] = 404;
			$rtn['count'] = 0;
		}
		return $rtn;
	}

	/**
	 * 进度增加
	 * @param [WishRedisQueue]  $queue [description]
	 * @param integer $num
	 */
	private function addProgress(WishRedisQueue $queue,$num = 1){
		$progress = $queue->get('progress') + $num;
		$queue->set('progress',$progress);
		return $progress;
	}

	/**
	 * 底层同步接口，根据token
	 * @param  [type] $token [description]
	 * @return [type]          [description]
	 */
	public function executeProductByToken($token){
		$AccountClass = __NAMESPACE__.'\\'.$this->platform."\\Account";
		$ProductClass = __NAMESPACE__.'\\'.$this->platform."\\Product";
		$queue = new WishRedisQueue($token,$this->platform);
		$rtn = [];
		// 获取account->site_id
		$account = $AccountClass::getAccountByToken($token);
		// 调用同步接口
		$productObj = new $ProductClass($account->site_id);

		$queue->set('status',WishRedisQueue::STATUS_SENDING);
		$products = $productObj->getProductsFromPlatform();
		// var_dump($products);die;
		if($products!==false){
			$result = [];
			// 保存信息
			if(count($products)){
				$result = $productObj->saveAllProducts($products,function($result) use ($queue){
					$this->addProgress($queue);
				}, $account->site_id);
			}
			$rtn['code'] = 200;
			$rtn['success'] = true;
			$rtn['data'] = $result;
		}else{
			$rtn['code'] = 401;
			$rtn['success'] = false;
		}
		return $rtn;
	}



	/**
	 * 获取队列进度
	 * @param  [type] $seller_id [description]
	 * @return [int]           [description]
	 * @author hqf 2016-1-5
	 */
	function getProgress($queue_id){
		$all = WishRedisQueue::all($this->platform);
		foreach($all as $token=>$info){
			if($info['log_id']==$queue_id){
				$queue = new WishRedisQueue($token,$this->platform);
				break;
			}
		}
		if(!isset($queue)){
			return NULL;
		}
		return $queue->get('progress');
	}


	/**
	 * 加入队列 redis
	 * 200 成功
	 * 201 已存在
	 * 500 失败
	 * @param [type] $site_id [description]
	 * @author hqf 2015-12-30
	 */
	function addProductQueueBySiteId($site_id){
		// 获取account信息
		$AccountClass = __NAMESPACE__.'\\'.$this->platform."\\Account";
		// $ProductClass = __NAMESPACE__.'\\'.$this->platform."\\Product";
		$account = $AccountClass::getAccountBySiteId($site_id);
		$queue = new WishRedisQueue($account->token,$this->platform);
		$rtn = [];
		$redisKey = self::$REDIS_KEY_PREFIX_PRODUCT; 
		// 查询是否存在
		if($queue->exists()){
			$rtn['code'] = 201;
			$rtn['progress'] = $queue->get('progress');
			$rtn['status'] = $queue->get('status');
			$rtn['success'] = true;
			$rtn['id'] = $queue->get('log_id');
		}else{
			$AccountClass = $this->accountClass;
			$account = $AccountClass::getAccountBySiteId($site_id);


			// 加入队列
			$this->log = new QueueProductLog;
			$this->log->create_time = date('Y-m-d H:i:s');
			$this->log->total_product = 0;
			$this->log->platform = $this->platform;
			$this->log->shop = $account->token;
			if($uid = \Yii::$app->user->id){
				$this->log->operator = $uid;
			}
			if($this->log->save()){
				$queue->add([
					'status'=>WishRedisQueue::STATUS_PENDING,
					'progress'=>0,
					'create_time'=>time(),
					'log_id'=>$this->log->id
				]);
				$rtn['code'] = 200;
				$rtn['progress'] = 0;
				$rtn['success'] = true;
				$rtn['id'] = $this->log->id;
				$rtn['status'] = WishRedisQueue::STATUS_PENDING;
			}else{
				$rtn['code'] = 500;
				$rtn['success'] = false;
			}
		}
		return $rtn;
	}

	// for test
	function test(){
		
		return \Yii::$app->user->id;
	}

	function clearQueue(){
		$queues = WishRedisQueue::all($this->platform);
		foreach($queues as $token=>$info){
			$queue = new WishRedisQueue($token,$this->platform);
			$log = QueueProductLog::findOne((int)$info['log_id']);
			$this->fail($queue,$log);
		}
	}

	// function clear($key){
	// 	var_dump( RedisHelper::del(WishRedisQueue::PREFIX.'*') );
	// }

	// function clearAll(){
	// 	$queues = WishRedisQueue::all();;
	// 	foreach($queues as $token=>$q){
	// 		RedisHelper::del(WishRedisQueue::PREFIX.$token);
	// 	}
	// 	RedisHelper::del(WishRedisQueue::PREFIX.'list');
	// }
	


}


/*****
redis 队列结构
queue:
	product:
		wish:
			token: 		(hashKey)
				status:
				progress:
				log_id:
				create_time: 		// 记录启动时间，防止意外中断造成的卡脚本
		wish:
			list:			(ListKey)
				token1
				token2
				token3
				
***/

