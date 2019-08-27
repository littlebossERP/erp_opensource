<?php
namespace console\helpers;

use eagle\modules\util\helpers\TimeUtil;
use eagle\modules\util\helpers\ResultHelper;

class AmazonHelper {

	 static $process_status = array(
        '0' => "没同步",
        '1' => "同步中",
        '2' => "submit成功",
        '3' => "submit失败",
        '4' => "check中",
        '5' => "check成功",
        '6' => "check访问失败",
        '7' => "amazon返回之前提交的任务执行失败",
        );
	public static function checkAmazonOrderSubmitQueueTable(){
        $rtnMsg = "";
        $checkSuccess = true;
        $step = 10;
        $connection = \Yii::$app->db;
        /**
         * [$cntTotal 获取数据表的总项数]
         * @var [type]
         */
        $command = $connection->createCommand("SELECT COUNT(*) FROM `amazon_order_submit_queue`");
        $command->execute();
        $cntData = $command->queryAll();
        $cntTotal = $cntData[0]['COUNT(*)'];

        /**
         * [error_count 错误总数]
         */
        $rtnMsg = "";
        $checkSuccess = true;
        $command = $connection->createCommand("SELECT COUNT(*) FROM `amazon_order_submit_queue` WHERE error_count > 15");
        $command->execute();
        $errCount = $command->queryAll();
        if(!empty($errCount[0]['COUNT(*)'])){
            $checkSuccess = false;
            $rtnMsg = "\n======================error_count======================\n";
            $rtnMsg .= "total = ".$errCount[0]['COUNT(*)']."\n";
            $length = 0;
            for ($cnt=0; $cnt < $cntTotal ; $cnt += $step) {
	            $length = (($cnt+$length)<$cntTotal)?$step:($cntTotal-$cnt);
	            $command = $connection->createCommand("SELECT * FROM `amazon_order_submit_queue` WHERE error_count > 15 limit ".$cnt.",".$length);
	            $command->execute();
	            $errorCountData = $command->queryAll();
	            foreach ($errorCountData as $dkey => $Dataval) {
	                $rtnMsg .= "id = ".$Dataval["id"]."  error_count = ".$Dataval["error_count"]."  [error_message:"."\"".$Dataval["error_message"]."\"]"."\n\n";
	            }
        	}
        }

        if ($checkSuccess == false) return array($checkSuccess,$rtnMsg);

        /**
         * [cntUnprocessed 未处理数过多]
         * process_status:
         * 0 没同步; 1 同步中; 2 submit成功; 3 submit失败; 4 check 中; 
         * 5 check成功; 6 check访问失败;7 amazon返回之前提交的任务执行失败
         */
        $rtnMsg = "";
        $checkSuccess = true;
        $command = $connection->createCommand("SELECT COUNT(*) FROM `amazon_order_submit_queue` WHERE process_status <> 5 ");
        $command->execute();
        $cntUnprocessed = $command->queryAll();
        if ($cntUnprocessed[0]['COUNT(*)']>1000) {
            $checkSuccess = false;
            $rtnMsg = "\n======================Unprocessed======================\n";
            $rtnMsg .= "total = ".$cntUnprocessed[0]['COUNT(*)']."\n";
        }
        if ($checkSuccess == false) return array($checkSuccess,$rtnMsg);
        /**
         * [tableData 创建时间过长未处理]
         */
        $nowTime=time();
        $rtnMsg = "";
        $checkSuccess = true;
        $command = $connection->createCommand("SELECT COUNT(*) FROM `amazon_order_submit_queue` WHERE process_status <> 5 AND ($nowTime -create_time) >= (3*60*60)");
        $command->execute();
        $tableCnt = $command->queryAll();
        if(!empty($tableCnt[0]['COUNT(*)'])){
		    $checkSuccess = false;
            $rtnMsg = "\n======================too-long-time======================\n";
            $rtnMsg .= "total = ".$tableCnt[0]['COUNT(*)']."\n";
            $length = 0;
        	for ($cnt=0; $cnt < $cntTotal ; $cnt += $step) {
	            $length = (($cnt+$length)<$cntTotal)?$step:($cntTotal-$cnt);
	            $command = $connection->createCommand("SELECT * FROM `amazon_order_submit_queue` WHERE process_status <> 5 AND ($nowTime -create_time) >= (3*60*60) limit ".$cnt.",".$length);
	            $command->execute();
	            $tableData = $command->queryAll();
	            foreach ($tableData as $tkey => $tableval) {
	                $rtnMsg .= $cnt."---".$length." "."id = ".$tableval["id"]."  process_status = ".self::$process_status[$tableval["process_status"]]."  error_count = ".$tableval["error_count"]."  [error_message:"."\"".$tableval["error_message"]."\"]"."\n\n";
	            }
        	}
        }
        if ($checkSuccess == false) return array($checkSuccess,$rtnMsg);

        return array($checkSuccess,$rtnMsg);
	}

}