<?php
namespace console\controllers;

use eagle\modules\platform\helpers\PlatformUnbindHelper;
use eagle\modules\platform\models\SaasPlatformUnbind;
use yii\console\Controller;


class PlatformUnbindController extends Controller {

	/**
	 * [AmazonUnbindClearData description]
	 * @author willage 2017-10-19T17:26:58+0800
	 * @update willage 2017-10-19T17:26:58+0800
	 * ./yii platform-unbind/amazon-unbind-clear-data
	 */
	public static function actionAmazonUnbindClearData(){
		$startRunTime=time();
		$keepRunningMins=30+rand(1,10);//分钟为单位,
		echo __FUNCTION__." start\n";
		do{
			$query=SaasPlatformUnbind::find()
				->where(["status"=>'OPEN'])
				->andwhere(["platform_name"=>'AMAZON'])
				->andwhere(["in","process_status",['PENDING','PARTIALLY','FAILURE']])
				->andwhere(["<","error_count",10])
				->orderBy('next_execute_time');
			echo $query->createCommand()->getRawSql()."\n";
			foreach ($query->each() as $unbind) {
				$unbind->process_status = 'PROCESS';
				$unbind->update_time = time();
				$unbind->save(false);
				$unbind=SaasPlatformUnbind::findOne($unbind->id);
				echo "========".$unbind->platform_sellerid."\n";
				if (empty($unbind)) {
					echo "empty continue\n";
		            continue;
		        }
				$ret=PlatformUnbindHelper::AmazonUnbindRecord($unbind);
				if ($ret == PlatformUnbindHelper::SUCCESS) {
					$unbind->process_status = 'SUCCESS';
				} else if($ret == PlatformUnbindHelper::PARTIALLY) {
					$unbind->process_status = 'PARTIALLY';
				} else if($ret == PlatformUnbindHelper::FAILURE) {
					$unbind->process_status = 'FAILURE';
					$unbind->error_count++;
				}
				$unbind->update_time = time();
				$unbind->save(false);
			}
			echo "========cycle done\n";
			sleep(15);
			$nowTime=time();

		}while (($startRunTime+60*$keepRunningMins > $nowTime));

	}


}
?>