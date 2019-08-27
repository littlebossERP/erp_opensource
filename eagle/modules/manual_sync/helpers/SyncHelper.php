<?php namespace eagle\modules\manual_sync\helpers;

use eagle\modules\manual_sync\models\Queue;
use \eagle\models\ManualSync;
use eagle\modules\util\helpers\UserLastActionTimeHelper;
use console\controllers\ManualSyncController as ConsoleCtrl;

// API入口
class SyncHelper
{

	static private $delayTime = [];

	static private $cacheData = [];

	static function log($text,$type=1){
		$text = '['.date('Y-m-d H:i:s').']['.ConsoleCtrl::manualSyncProcessId().'] '.$text.PHP_EOL;
		echo $text;
		\Yii::info('manual_sync --- '.$text,'file');
		$dest = $type?'/tmp/manual_sync.log':'/tmp/manual_sync_auto_task.log';
		error_log($text,3,$dest);
	}

	private static function getFromCache($key,$fn){
		if(!isset(self::$cacheData[$key])){
			self::$cacheData[$key] = call_user_func_array($fn, []);
		}
		return self::$cacheData[$key];
	}

	private static function getPuidArrByInterval($type,$interval){
		return self::getFromCache($type.'_'.$interval,function()use($interval){
			return UserLastActionTimeHelper::getPuidArrByInterval($interval);
		});
	}

	/**
	 * @return [type] [description]
	 */
	static function addAutoTaskQueues($type,$cfg){
		// puid列表
		if(isset($cfg['getAccounts'])){
			$accounts = call_user_func_array($cfg['getAccounts'], [function($interval)use($type){
				return self::getPuidArrByInterval($type,$interval);
			}]);
			foreach($accounts as $delay=>$users){
				$log = $type.' 频率为 '.$delay.'s 的用户数量有: '.count($users).' 个。';
				$count = 0;
				if(is_array($users)){
					foreach($users as $site_id){
						// self::log('auto-task is checking site_id: '.$site_id."($type)");
						try{
							$queue = Queue::get($type,$site_id);
							// 判断最近操作时间
							$lastQueue = $queue->getLast();
							if(!$lastQueue || time() > strtotime($lastQueue->update_time) + $delay ){
								// 进入队列
								$queue->data([
									'operator'=>'auto-task'
								]);
								if(isset($queue->isNewRecord) && $queue->isNewRecord){
									$queue->save(true);
									$count++;
								}
								// self::log('auto-task add a queue success: '.$queue->id);
							}
						}catch(\Exception $e){
							self::log($e->getMessage().' on '.$e->getFile().': '.$e->getLine(),$e->getCode());
						};
					}
					self::log($log."本次新增 {$count} 个",0);
				}
			}
		}
	}


	/**
	 * config中设置的function
	 * @param  [type] $queue [description]
	 * @return true
	 */
	static function manualSyncCallback($queue){
		Queue::error("请指定此 type(".$queue->type.") 的主函数");
		return false;  	// 返回true的话会表示已接收到信息，状态将被设为COMPLETE
	}

	static function testCallback($queue){
		// sleep(30);
		return true;
	}

	static function testGetAccounts(){
		$arr = [];
		for($i=1;$i<=50;$i++){
			$arr[]=$i;
		}
		return [
			86400=>$arr
		];
	}

	static function testGetStart(){
		$queue = Queue::getStart();
		var_dump($queue);
		$queue->fail();

	}

	static function testGet(){
		$queue = Queue::get('wish:product',27);
		var_dump($queue);
	}


}
