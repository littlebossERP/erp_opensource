<?php
namespace console\helpers;

use yii\helpers\ArrayHelper;
use eagle\models\SaasEbayAutosyncstatus;
use eagle\models\SaasEbayUser;
use eagle\modules\listing\models\EbayAutoInventory;
/**
 * 
 */
class EbayAutoCommonHelper{
    /**
     * [getSaasAutoSync 获取saas记录]
     * @author willage 2017-03-02T16:03:31+0800
     * @editor willage 2017-04-01T09:51:31+0800
     * @param  [type]  $limitCnt                [description]
     * @return [type]                           [description]
     * next_execute_time:下次执行时间
     * pending 0
     * running 1
     * fininsh 2
     * error   3
     */
    public static function getSaasAutoSync($limitCnt,$process_type){
        //获取Saas自动队列用户记录
        switch ($process_type) {
            case 'check':
                $recondS=SaasEbayAutosyncstatus::find()
                    ->where(['status'=>1])
                    ->andwhere(['type'=>9])
                    ->andwhere('status_process IN (0,2,3)')
                    ->andwhere("next_execute_time<".time())
                    ->limit($limitCnt)
                    ->orderBy('next_execute_time asc')
                    ->all();
                break;
            case 'inventory':
                $recondS=SaasEbayAutosyncstatus::find()
                    ->where(['status'=>1])
                    ->andwhere(['type'=>10])
                    ->andwhere('status_process IN (0,2,3)')
                    ->andwhere("next_execute_time<".time())
                    ->limit($limitCnt)
                    ->orderBy('next_execute_time asc')
                    ->all();
                break;
            case 'timer_listing':
                $recondS=SaasEbayAutosyncstatus::find()
                    ->where(['status'=>1])
                    ->andwhere(['type'=>11])
                    ->andwhere('status_process IN (0,2,3)')
                    ->andwhere("next_execute_time<".time())
                    ->limit($limitCnt)
                    ->orderBy('next_execute_time asc')
                    ->all();
                break;
            case 'first_getitems'://last_first_finish_time为NULL或者创建后的在50天内
                /**
                 * [提取用户,授权过时,不拉取]
                 */
                $atvSeller=SaasEbayUser::find()->select('selleruserid')
                        ->andWhere('listing_expiration_time>='.time())
                        ->andWhere(['listing_status'=>'1'])
                        ->asArray()
                        ->all();
                $recondS=SaasEbayAutosyncstatus::find()
                    ->where(['status'=>1])
                    ->andwhere(['selleruserid'=>$atvSeller])
                    ->andwhere(['type'=>7])
                    ->andwhere(['status_process'=> [0,3]])
                    ->andwhere(['or','`last_first_finish_time`<`created`+50*24*3600',['last_first_finish_time'=>NULL]])
                    ->limit($limitCnt)
                    ->orderBy(['last_first_finish_time'=>SORT_ASC])
                    ->all();
                break;
            case 'auto_getitems':
                /**
                 * [提取用户,授权过时,不拉取]
                 */
                $atvSeller=SaasEbayUser::find()->select('selleruserid')
                        ->andWhere('listing_expiration_time>='.time())
                        ->andWhere(['listing_status'=>'1'])
                        ->asArray()
                        ->all();
                $recondS=SaasEbayAutosyncstatus::find()
                    ->where(['status'=>1])
                    ->andwhere(['selleruserid'=>$atvSeller])
                    ->andwhere(['type'=>7])
                    ->andwhere(['status_process'=>[2,20,30]])
                    ->andwhere(['<','next_execute_time',time()])
                    ->andwhere('`next_execute_time` IS NOT NULL')
                    ->limit($limitCnt)
                    ->orderBy('next_execute_time asc')
                    ->all();

                break;
            default:
                $recondS=NULL;
                break;
        }
        return $recondS;
    }
    /**
     * [lockSaasRecord 锁定saas记录]
     * @author willage 2017-03-02T17:22:23+0800
     * @editor willage 2017-04-01T09:51:31+0800
     * @param  [type]  $saasId                  [description]
     * @param  [type]  $process_type            [description]
     * @return [type]                           [description]
     */
    public static function lockSaasRecord($saasId,$process_type){
        switch ($process_type) {
            case 'check':
                $sql="UPDATE `saas_ebay_autosyncstatus` SET status_process=1 WHERE id =".$saasId." AND status_process<>1";
                break;
            case 'inventory':
                $sql="UPDATE `saas_ebay_autosyncstatus` SET status_process=1 WHERE id =".$saasId." AND status_process<>1";
                break;
            case 'timer_listing':
                $sql="UPDATE `saas_ebay_autosyncstatus` SET status_process=1 WHERE id =".$saasId." AND status_process<>1";
                break;
            case 'first_getitems':
                $sql="UPDATE `saas_ebay_autosyncstatus` SET status_process=1,lastrequestedtime=".time()." WHERE id =".$saasId." AND status_process NOT IN (1,10)";
                break;
            case 'auto_getitems':
                $sql="UPDATE `saas_ebay_autosyncstatus` SET status_process=10,lastrequestedtime=".time()." WHERE id =".$saasId." AND status_process NOT IN (1,10)";
                break;
            default:
                $recordObj=NULL;
                break;
        }
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($sql) ;
        $affectRows = $command->execute();
        if ($affectRows <= 0)   return null; //抢不到
        $recordObj=SaasEbayAutosyncstatus::find()->where('id ='.$saasId)->one();
        return $recordObj;
    }
}//enc class