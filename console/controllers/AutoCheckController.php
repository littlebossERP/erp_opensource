<?php
 
namespace console\controllers;
 
use yii\console\Controller;
use eagle\models\AutoCheckBackgroundJob;
use eagle\modules\util\helpers\TimeUtil;
use console\helpers\AutoCheckHelper;
use console\helpers\AmazonHelper;
use yii;
use eagle\models\AutoCheckJobLog;
use eagle\modules\dash_board\helpers\DashBoardHelper;

if(!defined('EOL'))
define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');

class AutoCheckController extends Controller {
 
	/**
	 * 后台自动检查任务
	 * ./yii auto-check/check-all-job
	 */
	public function actionCheckAllJob() {
		echo "++++++++++++actionCheckAllJob ",EOL;
		
		$autoJobs = AutoCheckBackgroundJob::find()->where('is_active=1 && next_execution_time<'.time())->orderBy('next_execution_time asc')->all();
		foreach ($autoJobs as $autoJob) {
			echo "++++++++to be excute job:".$autoJob->id,EOL;
			$lastExcTime = time();
			$errorTimes = 0;
			$ret = false;
			$message = "";
			try {
// 				list($fullHelperName, $methodName) = AutoCheckHelper::dividFullMethodName('eagle.modules.amazon.apihelpers.AmazonApiHelper.warnGetOrderItemAbnormalStatus2');
				list($fullHelperName, $methodName) = AutoCheckHelper::dividFullMethodName($autoJob->execute_api);
				$fullHelperName = AutoCheckHelper::creatInstance($fullHelperName);
				echo $fullHelperName." ".$methodName,EOL;
				$timeMS1 = TimeUtil::getCurrentTimestampMS();
				$result = call_user_func_array(array($fullHelperName,$methodName),array());
				print_r($result);
				$timeMS2 = TimeUtil::getCurrentTimestampMS();
				if(is_array($result)){
					list($ret,$message) = $result;
				}else{
					$ret = false;
					$message = "execute_api:".$autoJob->execute_api." is not callable.";
					$errorTimes = $autoJob->error_times + 1;
				}
				
				echo $autoJob->job_name.",t2_1=".($timeMS2-$timeMS1).",ret:$ret ,message:$message",EOL;
			} catch ( \Exception $ex ) {
				print_r($ex);
				$ret = false;
				$message = "file:".$ex->getFile().", line:".$ex->getLine().", message:".$ex->getMessage();
				$errorTimes = $autoJob->error_times + 1;// 接口调用失败或异常次数，并不是接口返回错误次数
			}
			
			$nowTime = time();
			
			$jobLog = new AutoCheckJobLog();
			$jobLog->job_id = $autoJob->id;
			$jobLog->log_result = $message;
			$jobLog->create_time = $nowTime;
			$jobLog->save(false);
			
			$autoJob->last_execution_time = $lastExcTime;
			$autoJob->next_execution_time = $nowTime + $autoJob->job_interval;
			$autoJob->update_time = $nowTime;
			$autoJob->error_times = $errorTimes;
			if ($ret){
				$autoJob->last_success_execution_time = $nowTime;
			}
			$autoJob->save(false);
			
			if($ret == false){// 检查成功不用 发邮件
				$sendMailResult = false;
				try {
					$sendMailResult = AutoCheckHelper::sendEmail(json_decode($autoJob->emails), $autoJob->job_name, $message);
				} catch ( \Exception $ex ) {
					print_r($ex);
					$sendMailResult = false;
				}
					
				if ($sendMailResult === false) {
					echo $autoJob->job_name."发送邮件失败",EOL;
				} else {
					echo $autoJob->job_name."发送邮件成功",EOL;
				}
			}
			
			echo "++++++++excute job:".$autoJob->id." done",EOL;
		}
	}
	
	
	/**
	 * 后台自动检查拉取订单的成果，如果部分平台拉取结果少于预期，则发送email警报
	 * ./yii auto-check/check-it-dashboard
	 * author: yzq
	 * date: 2016-12-26
	 */
	public function actionCheckItDashboard() {
		$b = DashBoardHelper::checkTasksDefined( "Y" ); //Y = send email, N= not send
		echo "start to check if some job dead \n";
		DashBoardHelper::checkJobDead("Y" );
	}
}
