<?php namespace console\controllers;

use yii\console\Controller;
use \eagle\modules\util\helpers\ConfigHelper;
use \eagle\modules\manual_sync\models\Queue;
use \eagle\models\ManualSync;
use eagle\modules\manual_sync\helpers\SyncHelper;

use eagle\modules\manual_sync\models\Log;

class ManualSyncController extends Controller
{

	static $version = [
		'actionRun' 			=>NULL,
		'manualSyncAutoTask' 	=>NULL,
		'manual-test' 			=>NULL
	];

	static private $process_id;

	static function manualSyncProcessId(){
		if(!self::$process_id){
			self::$process_id = rand(1000,9999);
		}
		return self::$process_id;
	}

	static function log($text){
		$text = '['.date('Y-m-d H:i:s').']['.self::manualSyncProcessId().'] '.$text.PHP_EOL;

		$log = Log::get();
		$log->save(['log'=>$text]);

		echo $text;
	}

    function queueVersion($key){
        $currentVersion = ConfigHelper::getGlobalConfig ( $key, 'NO_CACHE' );
        if(!$currentVersion){
            $currentVersion = 0;
        }
        if(!self::$version[$key]){
            self::$version[$key] = $currentVersion;
        }
        if(self::$version[$key]!=$currentVersion){
        	self::log('版本不一致，退出脚本 '.self::$version[$key].':'.$currentVersion);
            return false;
        }
        return true;
    }

    function actionTestVersion(){
    	while(1){
    		$this->queueVersion('manual-test');
    	}
    }

    function actionCurrent(){
    	var_dump( \Yii::$app->params['currentEnv'] );
    }
    
    /**
     * 核心主函数入口（多进程）
     * OnePerMin
     * @return [type] [description]
     */
	function actionRun($type=NULL){
		$startRunTime = time();
		echo "manual-sync/run is start working...".PHP_EOL;
		while(time() < $startRunTime + 3600 && $this->queueVersion('actionRun')){ 		// 执行超过1小时后自动退出，等待下一个进程开启
			// 提取一条
			if($queue = Queue::getStart($type)){
				try{
					$queue->log('start');
					$result = $queue->run() ? 'success':'fail'; 	// 运行
					$queue->log('success');
				}catch(\Exception $e){
					$queue->setLog('error_message',$e->getMessage());
					$queue->fail();
				}
			}else{
				echo "queue is null".PHP_EOL;
			}
			sleep(3);
		}
	}


	/**
	 * 自动定时操作
	 * OnePerMin
	 * @return [type] [description]
	 */
	function actionAutoTask(){
		$startRunTime = time();
		$params = \Yii::$app->params['manualSync'];
		while(time() < $startRunTime + 3600 && $this->queueVersion('manualSyncAutoTask')){
			// 从配置文件中获取执行任务的函数
			foreach($params as $type=>$cfg){
				SyncHelper::addAutoTaskQueues($type,$cfg);
			}
			sleep(60);
		}
	}

	/**
	 * 清空队列
	 * @param  [type] $type [description]
	 * @return [type]       [description]
	 */
	function actionClearQueue($type){
		$q = Queue::findAll($type);
		foreach($q as $queue){
			$queue->data(['error_message'=>'system cancel']);
			$queue->fail();
			echo $queue->id.PHP_EOL;
		}
	}

	/**
	 * 测试大量的队列
	 * @author huaqingfeng 2016-04-13
	 * @return [type] [description]
	 */
	function actionTestMultiQueue(){
		$count = 50;
		for($i = 1; $i <= $count; $i++){
			// 新增队列
			$queue = Queue::get('test',$i);
			$queue->data([
				'message'=>'for test'
			]);
			$queue->save(true);
			echo $queue->id.' add.'.PHP_EOL;
		}
	}

	

}