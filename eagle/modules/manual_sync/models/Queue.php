<?php namespace eagle\modules\manual_sync\models;

use eagle\models\ManualSync;
use eagle\modules\util\helpers\RedisHelper;
use eagle\modules\util\helpers\TimeUtil;
use console\controllers\ManualSyncController;

class Queue 
{
	const STATUS_PENDING 		= 'P';
	const STATUS_SENDING 		= 'S';
	const STATUS_COMPLETE 		= 'C';
	const STATUS_FAIL 			= 'F';

	public $site_id;
	public $id;
	public $status = 'P';
	public $create_time;
	public $update_time;
	public $progress = 0;
	public $access_token;

	protected $type;
	protected $_prefix;
	protected $_data = [];


	static $prefix = 'manual_sync:';

	function __construct($type,$site_id){
		$this->type = $type;
		$this->site_id = $site_id;
		$this->create_time = time();
	}

	public static function prefix($type){
		return self::$prefix.\Yii::$app->params['currentEnv'].':'.$type;
	}

	static function error($msg){
		throw new \Exception(self::log($msg), 1);
	}

	static function log($msg){
		if(!is_string($msg)){
			$msg = var_export($msg);
		}
		$msg = '['.date('Y-m-d H:i:s').']['.ManualSyncController::manualSyncProcessId().'] '.$msg.PHP_EOL;
		echo $msg;
		error_log($msg,3,'/tmp/manual_sync.log');
		return $msg;
	}


	/**
	 * 获取单例
	 * @param  [type] $type [description]
	 * @param  [type] $site_id  [description]
	 * @return [type]           [description]
	 */
	static function get($type,$site_id){
		self::checkType($type);
		$i = new self($type,$site_id);
		// 获取redis信息
		//$data = RedisHelper::hGet( self::prefix($type),$site_id);
		$data = RedisHelper::RedisGet(self::prefix($type),$site_id );
		$data = $data ? json_decode($data,true) : [];
		if(!count($data)){ // 新增的
			$i->isNewRecord = true;
		}
		foreach($data as $key=>$val){
			$i->$key = $val;
		}
		// 如果超时则标记失败
		$cfg = \Yii::$app->params['manualSync'][$type];
		if(!isset($i->isNewRecord) && $i->status =='S' && isset($cfg['overtime'],$i->update_time) && time() > (strtotime($i->update_time) + $cfg['overtime'])){
			$i->data([
				'error_message'=>'超时1',
				'update_time'=>$i->update_time,
				'overtime'=>$cfg['overtime']
			]);
			$i->fail();
			self::log('queue_id:'.$i->id.' 超时1');
			self::log('up:'.$i->update_time.'-over:'.$cfg['overtime']);
			return self::get($type,$site_id); 	// 重新分配一个新的
		}
		return $i;
	}

	static private function getCfg($type){
		return \Yii::$app->params['manualSync'][$type];
	}

	/**
	 * 新增
	 */
	static function add($type,$site_id,$data=[]){
		$queue = self::get($type,$site_id);
		$cfg = self::getCfg($type);
		// 判断间隔
		if( isset($cfg['retry']) && $last = $queue->getLast() ){
			$timeout = strtotime($last->update_time) + $cfg['retry'] - time();
			if($timeout > 0){
				throw new \Exception("Please retry after {$timeout} seconds", 429);
				return false;
			}
		}
		$queue->data($data);
		$queue->save(true);
		return $queue;
	}

	static function exists($type,$site_id){
		//$data = RedisHelper::hGet( self::prefix($type),$site_id);
		$data = RedisHelper::RedisGet(self::prefix($type),$site_id );
		$data = $data ? json_decode($data) : [];
		return count($data) > 0;
	}

	static function checkType($type){
		if( !array_key_exists($type, \Yii::$app->params['manualSync']) ){
			// var_dump(\Yii::$app->params['manualSync']);
			self::error('指定了错误的类型 ['.$type.']');
		}
	}

	/**
	 * 取出一条记录开始 多进程取数据
	 * 队列的启动函数
	 * @return [type] [description]
	 */
	static function getStart($type = NULL){
		$where = '';
		$result = $db = false;
		if($type){ 		// 如果传入指定的type类型则只跑这个type的数据
			self::checkType($type);
			$where .= " AND `type` = '{$type}'";
		}else{ 			// 否则排除掉config.access = private的队列
			foreach(\Yii::$app->params['manualSync'] as $name=>$t){
				if(isset($t['access']) && $t['access']=='private'){
					$where .= " AND `type` <> '{$name}'";
				}
			}
		}
		// 先一次取出100待定数据
		$sql = "SELECT *
			FROM manual_sync 
			WHERE `status` = '".self::STATUS_PENDING."'
			{$where} 
			ORDER BY `create_time` ASC 
			LIMIT 100
		";
		$command = \Yii::$app->db->createCommand($sql);
		$query = $command->query();
		while( false !== ($db = $query->read()) ){
			// 使用原子级的命令更新状态，若失败则进入下一条待定数据
			$sql = "UPDATE manual_sync 
				SET `status` = '".self::STATUS_SENDING."' 
				WHERE `status` = '".self::STATUS_PENDING."' 
				AND id = {$db['id']}
				{$where}
				LIMIT 1
			";
			$command = \Yii::$app->db->createCommand($sql);
			if($result = $command->execute()){
				break;
			}
		}
		if(!$db){ 		// 没有记录
			return NULL;
		}else{
			// 返回自身对象
			$queue = self::get($db['type'], $db['site_id']);
			$queue->status = self::STATUS_SENDING;
			$queue->id = $db['id'];
			$queue->save();
			return $queue; 
		}
	}

	static function findAll($type){
		self::checkType($type);
		$lists = [];
		//$all = RedisHelper::hGetAll( self::prefix($type) ); 		// site_id
		$all = RedisHelper::RedisGetAll(self::prefix($type) );
		foreach($all as $site_id => $queue){
			$info = json_decode($queue);
			$lists[] = self::get($type, $site_id);
		}
		return $lists;
	}

	/**
	 * 获取进度信息，如果已经完成则返回MYSQL中的信息
	 * @param  [type] $type    [description]
	 * @param  [type] $site_id [description]
	 * @return [type]          [description]
	 */
	static function getProgress($type,$site_id){
		if(Queue::exists($type,$site_id)){ 
        	$queue = Queue::get($type,$site_id);
	        return [
	        	'status'=>$queue->status,
	        	'id'=>$queue->id,
	        	'progress'=>$queue->progress,
	        	'queue'=>[]
        	];
        }else{
        	$q = ManualSync::find()->where([
        		'site_id'=>$site_id,
        		'type'=>$type
        	])->orderBy('create_time DESC')->one();
        	if($q){
        		return [
		        	'status'=>$q->status,
		        	'id'=>$q->id,
		        	'progress'=>$q->total,
		        	'queue'=>$q->attributes
	        	];
        	}else{
        		return [
        			'status'=>false,
        			'progress'=>NULL
        		];
        	}
        }
	}


	function delete($saveDb = false){
		// var_dump($this->site_id);
		//$result = RedisHelper::hDel(self::prefix($this->type),$this->site_id);
		$result = RedisHelper::RedisDel(self::prefix($this->type),$this->site_id );
		if($result && ($saveDb && $db = $this->getDb()) ){
			$result = $db->delete();
		}
		return $result;
	}

	/**
	 * 完成
	 * @return [type] [description]
	 */
	function complete(){
		$this->status = self::STATUS_COMPLETE;
		$this->save(true);
		$this->delete();
		return true;
	}

	/**
	 * 标记失败
	 * @return [type] [description]
	 */
	function fail(){
		$this->status = self::STATUS_FAIL;
		$this->save(true);
		$this->delete();
		return true;
	}


	function data($data = NULL){
		if(!$data){
			return $this->_data;
		}elseif(is_array($data)){
			foreach($data as $k=>$v){
				$this->_data[$k] = $v;
			}
			return $this;
		}else{
			return isset($this->_data[$data]) ? $this->_data[$data] : NULL;
		}
	}

	/**
	 * 获取最近一次完成的记录
	 * @author hqf
	 * @version 2016-05-04 
	 * @return [type] [description]
	 */
	function getLast(){
		$log = ManualSync::find()->where([
			'type'=>$this->type,
			'site_id'=>$this->site_id
		])->andWhere([
			'IN','status',['C','F']
		])->orderBy('update_time DESC')->one();
		return $log;
	}


	private function getDb(){
		if(!$this->id || !$db = ManualSync::findOne($this->id)){
			$db = new ManualSync();
			$db->site_id 		= (string)$this->site_id;
			$db->create_time 	= date('Y-m-d H:i:s',$this->create_time);
			$db->type 		= $this->type;
		}
		return $db;
	}

	/**
	 * 保存
	 * @param  boolean $db 是否保存到数据库
	 * @return [int]      [description]
	 */
	function save($saveDb = false){
		$this->isNewRecord = false;
		unset($this->isNewRecord);
		$hash = [
			'site_id' 		=> $this->site_id,
			'create_time' 	=> $this->create_time,
			'update_time' 	=> $this->update_time,
			'progress' 		=> $this->progress,
			'status' 		=> $this->status,
			'_data' 		=> $this->_data,
			'id' 			=> $this->id
		];
		//RedisHelper::hSet(self::prefix($this->type),$this->site_id, json_encode($hash)) ; 	// hSet返回0也表示成功，所以不判断了
		RedisHelper::RedisSet(self::prefix($this->type),$this->site_id, json_encode($hash));
		
		if($saveDb){
			// 保存数据库
			$db = $this->getDb();
			$db->status 			= $this->status;
			$db->total 				= $this->progress;
			$db->_data 				= json_encode($this->_data);
			if($result = $db->save()){
				$this->id = $db->id;
				$this->save(); 		// 把 id 写回redis
			}else{
				throw new \Exception(json_encode($db->getErrors()), 500);
			}
		}
		return $this->id ? $this->id : true;

	}

	/**
	 * 进度加N
	 */
	function addProgress($n = 1){
		$this->progress += $n;
		$this->save();
		return $this;
	}

	function getExceptionMsg($e){
		return $e->getMessage().' on: '.$e->getFile().':'.$e->getLine();
	}

	/**
	 * 运行
	 * @return [type] [description]
	 */
	function run(){
		$runResult = false;
		try{
			// 先看状态是否是sending -- sending是因为通过 getStart 获取的
			if($this->status !== self::STATUS_SENDING){
				return false;
			}
			// 读取对应type的配置信息
			$info = \Yii::$app->params['manualSync'][$this->type];
			if(!$this->site_id){
				$this->data([
					'error_message'=>'no site_id'
				]);
				$this->complete();
				return true;
			}
			// 超时检查
			if(isset($info['overtime']) && $this->update_time && time() > ($this->update_time + $info['overtime'])){
				$this->data([
					'error_message'=>'超时2',
					'update_time'=>$this->update_time,
					'overtime'=>$info['overtime']
				]);
				$this->fail();
				self::log('超时2');
				// error_log(PHP_EOL.'overtime',3,'/tmp/manual_sync.log');
				return false;
			}
			if(isset($info['function'])){
				TimeUtil::beginTimestampMark("manual_sync");
				TimeUtil::markTimestamp("t1","manual_sync");
				self::log('function start');
				$this->update_time = date('Y-m-d H:i:s');
				$this->save(true);
				try{
					$runResult = call_user_func_array($info['function'], [$this]);
				}catch(\Exception $e){
					$msg = $this->getExceptionMsg($e);
					self::log('function error: '.$msg);
					$this->data([
						'error_message'=>$e->getMessage(),
						'error_file'=>$e->getFile().':'.$e->getLine()
					]);
					$runResult = false;
				}
				self::log('function end');
				TimeUtil::markTimestamp("t2","manual_sync");
				$this->data([
					'TimeStamp' 	=> TimeUtil::getTimestampMarkInfo("manual_sync"),
					'MemoryUsed' 	=> floor(memory_get_usage() / 1024 / 1024)
				]);
				if($runResult===true){
					$this->complete();
				}elseif($runResult===false){
					self::log('主函数返回false');
					$this->fail();
				}
			}else{
				self::log('没有指定function');
			}
		}catch(\Exception $e){
			self::log($e->getMessage.' on:'.$e->getFile().' : '.$e->getLine());
		}
		return $runResult;
	}


}