<?php

namespace console\controllers;
use Yii;
use yii\console\Controller;
use yii\base\Exception;
use console\helpers\AliexpressHelper;
use console\helpers\AliexpressClearHelper;



class AliexpressClearQueueController extends Controller {

    function actionCheckAliexpressOrderStatus(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "ALIGOL".$seed."OrderStatus";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_check_order_status jobid=$cronJobId start \n";
        \Yii::info("aliexress_check_order_status jobid=$cronJobId start",'file');
        do{
            $rtn = AliexpressHelper::checkOrderStatus();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "aliexress_check_order_status jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_check_order_status jobid=$cronJobId sleep10",'file');
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        echo "aliexress_check_order_status jobid=$cronJobId end \n";
        \Yii::info("aliexress_check_order_status jobid=$cronJobId end",'file');
    }

    function actionCheckTokenStatus(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "ALIGOL".$seed."TokenStatus";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_check_token_status jobid=$cronJobId start \n";
        \Yii::info("aliexress_check_token_status jobid=$cronJobId start",'file');
        do{
            $rtn = AliexpressHelper::checkTokenStatus();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "aliexress_check_token_status jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_check_token_status jobid=$cronJobId sleep10",'file');
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        echo "aliexress_check_token_status jobid=$cronJobId end \n";
        \Yii::info("aliexress_check_token_status jobid=$cronJobId end",'file');
    }

    function actionPushOrderInfo(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "ALIGOL".$seed."PushOrder";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_retry_push_order jobid=$cronJobId start \n";
        \Yii::info("aliexress_retry_push_order jobid=$cronJobId start",'file');
        do{
            $rtn = AliexpressClearHelper::pushOrderInfo();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "aliexress_retry_push_order jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_retry_push_order jobid=$cronJobId sleep10",'file');
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        echo "aliexress_retry_push_order jobid=$cronJobId end \n";
        \Yii::info("aliexress_retry_push_order jobid=$cronJobId end",'file');
    }

    function actionRetryAccount(){
        $startRunTime=time();
        $seed = rand(0,99999);
        $cronJobId = "ALIGOL".$seed."RetryAccount";
        AliexpressHelper::setCronJobId($cronJobId);
        echo "aliexress_retry_account jobid=$cronJobId start \n";
        \Yii::info("aliexress_retry_account jobid=$cronJobId start",'file');
        do{
            $rtn = AliexpressClearHelper::retryAccountInfo();
            //如果没有需要handle的request了，sleep 10s后再试
            if ($rtn===false){
                echo "aliexress_retry_account jobid=$cronJobId sleep10 \n";
                \Yii::info("aliexress_retry_account jobid=$cronJobId sleep10",'file');
                sleep(10);
            }
        }while (time() < $startRunTime+3600);
        echo "aliexress_retry_account jobid=$cronJobId end \n";
        \Yii::info("aliexress_retry_account jobid=$cronJobId end",'file');
    }

    function actionSaveRetryAccountInfo(){
        $connection = Yii::$app->db_queue;
        $sql = "SELECT * FROM queue_aliexpress_retry_account group by sellerloginid asc ";
        $dataReader = $connection->createCommand($sql)->query();
        while( ($row = $dataReader->read()) !== false){
            $info_sql = "SELECT * FROM queue_aliexpress_retry_account_info WHERE sellerloginid = '".$row['sellerloginid']."'";
            $account_info = $connection->createCommand($info_sql)->query()->read();
            if($account_info === false){
                $params = [
                    'uid' => $row['uid'],
                    'sellerloginid' => $row['sellerloginid'],
                    'orderid' => $row['orderid'],
                    'times' => 0,
                    'last_time' => 0,
                    'message' => '',
                    'create_time' => time(),
                    'update_time' => time(),
                    'next_time' => time()+1800
                ];
                $command = $connection->createCommand()->insert('queue_aliexpress_retry_account_info',$params);
                $result = $command->execute();
                var_dump($result);
                echo "\n";
                echo $command->getRawSql()."\n";
            }
        }
    }
    //删除dp_enable冗余数据
    function actionDeleteDpenable(){
        $connection = Yii::$app->db;
        $bool = true;
        $start = 1;
        $limit = 100;
        do{
            $num = ($start - 1) * $limit;
            $sql = "select * from dp_enable order by dp_puid asc limit {$num},{$limit}";
            $data = $connection->createCommand($sql)->query();
            $bool = false;
            while(($row = $data->read()) !== false){
                $bool = true;
                $run_sql = "select * from saas_aliexpress_user where uid = ".$row['dp_puid']." and sellerloginid = '".$row['dp_shop_id']."'";
                $update_res = $connection->createCommand($run_sql)->query();
                //判断saas表中是否存在，存在跳过，不存在删除。
                if(($res = $update_res->read()) == false){
                    $update_sql = 'delete from dp_enable where dp_puid = '.$row['uid'].' and dp_shop_id ='.$row['dp_shop_id'];
                    $result = $connection->createCommand($update_sql)->execute();
                    $log_message = date('Y-m-d H:i:s')."dp_enable delete  : {$row['dp_shop_id']} delete success";
                    echo $log_message."\n";
                    \Yii::info($log_message,"file");

                }else{
                    continue;
                }
            }
            $start++;
        }while($bool == true);

    }
}
