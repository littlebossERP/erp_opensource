<?php
namespace eagle\modules\platform\helpers;

use Yii;
use eagle\models\AmazonTempOrderidQueueHighpriority;
use eagle\models\AmazonTempOrderidQueueLowpriority;
use eagle\models\SaasAmazonAutosyncV2;
use eagle\models\SaasAmazonUser;


class PlatformUnbindHelper
{
    //状态
    const PARTIALLY=1;
    const SUCCESS=2;
    const FAILURE=3;
    /**
     * [AmazonUnbindRecord description]
     * @author willage 2017-10-19T17:20:53+0800
     * @update willage 2017-10-19T17:20:53+0800
     */
    public static function AmazonUnbindRecord($unbindR){
        if (empty($unbindR)) {
            return self::SUCCESS;
        }
        //更加sync表删除 amazon order detail JOB的记录
        $sync=SaasAmazonAutosyncV2::find()
                ->where(["platform_user_id"=>$unbindR->platform_sellerid])
                ->andwhere(["status"=>3])
                ->select('id');
        echo $sync->createCommand()->getRawSql()."\n";
        $syncArr=$sync->asArray()->column();

        echo json_encode($syncArr)."\n";
        if (!empty($syncArr)) {
            $ret=self::_amazonUnbindQueueDelete($syncArr);
            if ($ret != self::SUCCESS) {
                return $ret;
            }
            //清除'saas_amazon_autosync_v2'记录
            $sql="DELETE FROM `saas_amazon_autosync_v2` WHERE `status`=3 AND `platform_user_id`='".$unbindR->platform_sellerid."'";
            echo $sql."\n";
            $connection=\Yii::$app->db;
            $command=$connection->createCommand($sql);
            $affectRows = $command->execute();
            echo "affect rows:".$affectRows."\n";
        }


        //清除'saas_amazon_user_marketplace'记录
        $user=SaasAmazonUser::find()
                ->where(["merchant_id"=>$unbindR->platform_sellerid])
                ->andwhere(["is_active"=>3])
                ->select('amazon_uid');
        echo $user->createCommand()->getRawSql()."\n";

        $userArr=$user->asArray()->column();
        echo json_encode($userArr)."\n";
        if (!empty($userArr)) {
            $strUser=implode(',', $userArr);
            $sql="DELETE FROM `saas_amazon_user_marketplace` WHERE `is_active`=3 AND `amazon_uid` IN (".$strUser.")";
            echo $sql."\n";
            $connection=\Yii::$app->db;
            $command=$connection->createCommand($sql);
            $affectRows = $command->execute();
            echo "affect rows:".$affectRows."\n";
        }


        //清除'saas_amazon_user_pt'记录(不作删除,啊亮)
        //清除'saas_amazon_user'记录
        $user=SaasAmazonUser::find()
            ->where(["merchant_id"=>$unbindR->platform_sellerid])
            ->andwhere(["is_active"=>3]);
        echo $user->createCommand()->getRawSql()."\n";
        foreach ($user->each() as $val) {
            if ($val->delete()) {
                echo "SaasAmazonUser delete ok\n";
            }else{
                echo "SaasAmazonUser delete fail\n";
            }
        }
        return self::SUCCESS;
    }
    /**
     * [_amazonUnbindQueueDelete 清除amazon Job的同步队列]
     * @author willage 2017-10-19T17:03:43+0800
     * @update willage 2017-10-19T17:03:43+0800
     */
    public static function _amazonUnbindQueueDelete($syncid){
        $strSync=implode(',', $syncid);
        echo $strSync."\n";
        //清除'amazon_temp_orderid_queue_highpriority'记录
        $queryHigh = AmazonTempOrderidQueueHighpriority::find()
                    ->where(['in','saas_platform_autosync_id',$syncid])
                    ->count();
        echo "AmazonTempOrderidQueueHighpriority cnt : ".$queryHigh."\n";
        if ($queryHigh) {
            $sql="DELETE FROM `amazon_temp_orderid_queue_highpriority` WHERE `saas_platform_autosync_id` IN (".$strSync.")";
            echo $sql."\n";
            $connection=\Yii::$app->db_queue;
            $command=$connection->createCommand($sql);
            $affectRows = $command->execute();
            echo "affect rows:".$affectRows."\n";
        }

        //清除'amazon_temp_orderid_queue_lowpriority'记录
        $queryLow = AmazonTempOrderidQueueLowpriority::find()
                    ->where(['in','saas_platform_autosync_id',$syncid])
                    ->count();
        echo "AmazonTempOrderidQueueLowpriority cnt : ".$queryLow."\n";
        if ($queryLow) {
            $sql="DELETE FROM `amazon_temp_orderid_queue_lowpriority` WHERE `saas_platform_autosync_id` IN (".$strSync.")";
            echo $sql."\n";
            $connection=\Yii::$app->db_queue;
            $command=$connection->createCommand($sql);
            $affectRows = $command->execute();
            echo "affect rows:".$affectRows."\n";

        }

        //清除'amazon_report_queue_common'记录(不作删除,啊亮)

        return self::SUCCESS;
    }


}