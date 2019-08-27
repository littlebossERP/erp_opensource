<?php
namespace eagle\modules\listing\helpers;

use common\api\lazadainterface\LazadaInterface_Helper;
use common\mongo\lljListing\LazadaListingMG;
use common\mongo\lljListing\LLJListingDBConfig;
use common\mongo\lljListing\LLJListingManagerFactory;
use common\mongo\lljListing\TaskQueue;
use common\mongo\lljListing\TaskStatus;
use console\helpers\CommonHelper;
use console\helpers\LogType;
use eagle\models\SaasLazadaAutosync;
use eagle\models\SaasLazadaUser;
use eagle\modules\listing\models\LazadaListing;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\util\helpers\TimeUtil;
use Yii;


/**
 * Class LazadaAutoFetchListingHelperV2
 * @package eagle\modules\listing\helpers
 * Linio,Jumia 数据同步类,主要执行订单数据同步,对之前版本进行重构和改进
 * Lazada由于接口变动改到了V3类里面
 * @author vizewang,zhitian
 * @version 2.0
 * @since 2.0
 */
class LazadaAutoFetchListingHelperV2
{
    const LOG_ID = "eagle-modules-listing-helpers-LazadaAutoFetchListingHelperV2";
    public static $LAZADA_PRODUCT_OTHER_STATUS = array(LazadaProductStatus::PENDING, LazadaProductStatus::LIVE, LazadaProductStatus::REJECTED, LazadaProductStatus::IMAGE_MISSING, LazadaProductStatus::SOLD_OUT, LazadaProductStatus::INACTIVE, LazadaProductStatus::DELETED);
    public static $LAZADA_PRODUCT_STATUS = array(LazadaProductStatus::ALL, LazadaProductStatus::PENDING, LazadaProductStatus::LIVE, LazadaProductStatus::REJECTED, LazadaProductStatus::IMAGE_MISSING, LazadaProductStatus::SOLD_OUT, LazadaProductStatus::INACTIVE, LazadaProductStatus::DELETED);
    public static $LAZADA_ACCOUNT_INFO_MAP = array();
    
    /**
     *  后台触发------获取指定时间内所有账户的有更新的订单，第一次就是获取所有listing，后面就是拉取新增或者有修改的listing
     */
    public static function getUpdatedistingByID($id)
    {
        self::localLog("getUpdatedistingByID start ...");

        // 先判断是否真的抢到待处理账号
        $saasLazadaAutosync = SaasLazadaAutosync::findOne($id);
        if(empty($saasLazadaAutosync)){
            return false;
        }
        self::localLog("lazadaUpList_get autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ",lazadaUid:" . $saasLazadaAutosync->lazada_uid);

        //3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
        //3.1 整理请求参数
        $config = LLJHelper::assembleConfig(SaasLazadaUser::findOne($saasLazadaAutosync->lazada_uid));

        $result = self::getUpdatedListForOneLazada($config, $saasLazadaAutosync);
        if ($result !== false) {
            return true;
        }
        return false;
    }

    public static function getUpdatedListingByTimeRangeAndId($id, $startTime, $endTime)
    {
        self::localLog("getUpdatedListingByTimeRangeAndId===>id:$id,startTime:$startTime,endTime:$endTime");
        $SAA_obj = SaasLazadaAutosync::findOne($id);
        $lazadaUser = SaasLazadaUser::find()->where('lazada_uid=' . $SAA_obj->lazada_uid)->one();
        $config = array(
            "userId" => $lazadaUser->platform_userid,
            "apiKey" => $lazadaUser->token,
            "countryCode" => $lazadaUser->lazada_site
        );

        self::getFilterListingAndSave($config, $startTime, $endTime, $SAA_obj, 0, time());
    }

    public static function getUpdatedListingByTimeRange($start, $end)
    {
        $sqlStr = 'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `type`=4';
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($sqlStr);
        $dataReader = $command->query();
        $usercount = $dataReader->count();
        self::localLog("getUpdatedListingByTimeRange need to update listing usersize is $usercount");
        while (($row = $dataReader->read()) !== false) {
            self::localLog("left user " . $usercount--);
            self::getUpdatedListingByTimeRangeAndId($row['id'], $start, $end);
        }
    }

    /**
     * 为数据库中endtime小于指定值的用户重新拉取数据,指定开始和结束时间
     * @param $start
     * @param $end
     * @param $ltEndTime 指定endtime
     */
    public static function refreshListing4SpecificEndTime($start, $end, $ltEndTime)
    {
        $sqlStr = 'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `type`=4 AND `end_time` <' . $ltEndTime;
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($sqlStr);
        $dataReader = $command->query();
        $usercount = $dataReader->count();
        self::localLog("getUpdatedListingByTimeRange need to update listing usersize is $usercount");
        while (($row = $dataReader->read()) !== false) {
            self::localLog("left user " . $usercount--);
            self::getUpdatedListingByTimeRangeAndId($row['id'], $start, $end);
        }
    }

    /**
     * 为数据库中endtime小于指定值的用户重新拉取数据,不指定开始和结束时间
     * @param $ltEndTime
     */

    public static function refreshListing4SpecificEndTime2($ltEndTime)
    {
        $sqlStr = 'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `type`=4 AND `end_time` <' . $ltEndTime;
        $connection = \Yii::$app->db;
        $command = $connection->createCommand($sqlStr);
        $dataReader = $command->query();
        $usercount = $dataReader->count();
        self::localLog("getUpdatedListingByTimeRange need to update listing usersize is $usercount");
        while (($row = $dataReader->read()) !== false) {
            self::localLog("left user " . $usercount--);
            self::getUpdatedistingByID($row['id']);
        }
    }

    /**
     *  后台触发------获取指定时间内所有账户的有更新的订单，第一次就是获取所有listing，后面就是拉取新增或者有修改的listing
     */
    public static function getUpdatedisting($platforms = array())
    {
        self::localLog("getUpdatedisting start ...");
        $connection = \Yii::$app->db;
        $type = 4;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单，4----获取listing
        $nowTime = time();
        // 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题
        // dzt20161221 lazada新接口上线后，此接口将不再负责更新lazada产品，只更新linio以及jumia产品
        $sqlStr = 'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` <>1 AND `platform` IN("'.implode('","', $platforms).'") '.
            'AND `error_times` < 15 AND `type`=' . $type . '  AND  next_execution_time<' . $nowTime . ' ORDER BY `next_execution_time` ASC LIMIT 10 ';

        $command = $connection->createCommand($sqlStr);

        $allLazadaAccountsInfoMap = LLJHelper::getAllLLJAccountInfoMap();
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            self::localLog("getUpdatedisting dataReader->read() " . $row['id']);
            // 先判断是否真的抢到待处理账号
            $SAA_obj = self::lockLazadaAutosyncRecord($row['id']);
            if ($SAA_obj === null) {
                self::localLog("task is processing and skip");
                continue;
            } //抢不到---如果是多进程的话，有抢不到的情况


            self::localLog("getUpdatedisting autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid);

            //3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
            //3.1 整理请求参数
            $config = $allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];

            $result = self::getUpdatedListForOneLazada($config, $SAA_obj);
            if ($result !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取在线产品,将获取请求插入TaskQueue表,再循环检测任务是否获取结束
     */
    public static function createdUpdateListingTask()
    {
        self::localLog("createdUpdateListingTask start ...");
        $connection = \Yii::$app->db;
        $type = 4;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单，4----获取listing
        $nowTime = time();
        // 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题

        while (true) {
            $sqlStr = 'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` <>1 ' .
                'AND `error_times` < 15 AND `type`=' . $type . '  AND  next_execution_time<' . $nowTime . ' ORDER BY `next_execution_time` ASC LIMIT 100 ';

            $command = $connection->createCommand($sqlStr);

            $allLazadaAccountsInfoMap = LLJHelper::getAllLLJAccountInfoMap();
            $dataReader = $command->query();
            if ($dataReader->count() == 0) {
                self::localLog("createdUpdateListingTask end");
                break;
            }
            while (($row = $dataReader->read()) !== false) {
                self::localLog("createdUpdateListingTask dataReader->read() " . $row['id']);
                // 先判断是否真的抢到待处理账号
                $saasLazadaAutosync = self::lockLazadaAutosyncRecord($row['id']);
                if ($saasLazadaAutosync === null) {
                    self::localLog("task is processing and skip");
                    continue;
                } //抢不到---如果是多进程的话，有抢不到的情况


                self::localLog("createdUpdateListingTask autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ",lazadaUid:" . $saasLazadaAutosync->lazada_uid);
                $config = $allLazadaAccountsInfoMap[$saasLazadaAutosync->lazada_uid];
                self::getUpdatedListForOneLazadaV2($config, $saasLazadaAutosync);
            }
        }

    }

    public static function manualCreatedUpdateListingTask($saasLazadaAutosyncId, $start, $end)
    {
        self::localLog("createdUpdateListingTask start ...");
        $saasLazadaAutosync = SaasLazadaAutosync::findOne($saasLazadaAutosyncId);

        $lazadaUser = SaasLazadaUser::findOne($saasLazadaAutosync->lazada_uid);

        $config = array(
            "userId" => $lazadaUser->platform_userid,
            "apiKey" => $lazadaUser->token,
            "countryCode" => $lazadaUser->lazada_site
        );
        self::getFilterListingAndSaveV2($config, $start, $end, $saasLazadaAutosync);
    }

    public static function updateListing()
    {
        self::localLog("updateListing is start ...");
        while (true) {
            $taskQueueManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::TASK_QUEUE);
            $task = $taskQueueManager->findAndModify(array("taskStatus" => array('$in' => array(TaskStatus::PROCESS_SUCCESS, TaskStatus::RETRY_END)), "isRoot" => true), array('$set' => array("taskStatus" => TaskStatus::RETRIEVING)));
            if (empty($task)) {
                self::localLog("updateListing task is empty and skiped");
                break;
            }
            if (empty($task["foreignTaskId"])) {
                self::localLog("updateListing foreignTaskId is empty and skiped");
                continue;
            };

            $saasLazadaAutosync = SaasLazadaAutosync::findOne($task["foreignTaskId"]);
            if (empty($saasLazadaAutosync)) {
                self::localLog("updateListing foreignTaskId is mismatched and skiped");
                continue;
            }
            $responseDataManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::RESPONSE_DATA);
            $sucTaskId = array();
            $sucTaskId[] = $task['_id'];
            if ($task["taskStatus"] != TaskStatus::PROCESS_SUCCESS) {
                if (!empty($task["customized"])) {
                    $customized = $task["customized"];
                    if (!empty($customized["retryTaskIds"])) {
                        $sucTaskId = array_merge($sucTaskId, $customized["retryTaskIds"]);
                    }
                }
            }
            $product = array();
            $nowTime = time();
            $cursor = $responseDataManager->find(array('taskId' => array('$in' => $sucTaskId)));
            $count = 0;
            $batch = 0;
            foreach ($cursor as $doc) {
                $data = $doc["data"];
                foreach ($data as $status => $value) {
                    if (null != $value) {
                        if (isset($product[$status]))
                            $product[$status] = array_merge($product[$status], $value);
                        else
                            $product[$status] = $value;
                    }
                }
                $count++;
                if ($count >= LazadaConfig::UPDATE_PRODUCTS_BATCH_SIZE) {
                    self::localLog("start insert batch " . $batch);
                    $batch++;
                    self::updateDBV2($product, $saasLazadaAutosync->lazada_uid, $saasLazadaAutosync->puid, $nowTime);
                    $count = 0;
                    $product = array();
                }
            }
            if (!empty($product)) {
                self::updateDBV2($product, $saasLazadaAutosync->lazada_uid, $saasLazadaAutosync->puid, $nowTime);
            }
            $fetchPeriod = 60 * 60;
            $saasLazadaAutosync->status = 2;
            $saasLazadaAutosync->error_times = 0;
            $saasLazadaAutosync->message = "";
            $saasLazadaAutosync->update_time = $nowTime;
            $saasLazadaAutosync->next_execution_time = $nowTime + $fetchPeriod;
            $saasLazadaAutosync->save();
            $taskQueueManager->update(array("_id" => array('$in' => $sucTaskId)), array('$set' => array("taskStatus" => TaskStatus::RETRIEVE_SUCCESS)), array("multiple" => true));
        }
        self::localLog("updateListing is end");

    }

    /**
     * 用户在前端点击"手工同步"
     * @param  $lazadaUid ---saas_lazada_user的id
     * @return array($ret,$message,$number)
     * ret----true or false
     * message----失败原因
     * number----获取的 数量
     */
    public static function manualSync($lazadaUid)
    {

        self::localLog("lazadaUpList_man trigger lazadaUid:$lazadaUid");
        $saasLazadaAutosync = SaasLazadaAutosync::findOne(["lazada_uid" => $lazadaUid, "type" => 4]);
        if ($saasLazadaAutosync === null) return self::manualSyncResult($lazadaUid, false, "Error_LAZ002  指定店铺不存在 ", 0);
        if ($saasLazadaAutosync->is_active === 0) return self::manualSyncResult($lazadaUid, false, "Error_LAZ003  指定店铺已停止同步 ", 0);

        $manualInterval = 10 * 60;//s为单位。  二次手工同步的至少时间间隔 10分钟

        //1.合理性检查。
        //1.1是否已经在拉取
        $tryCount = 0;
        while ($saasLazadaAutosync->status == 1 and $tryCount < 3) {//刚好后台正在拉取新的listing
            self::localLog("lazadaUpList_man SAA_obj->status==1 sleep10");
            $tryCount++;
            sleep(10);
            $saasLazadaAutosync = SaasLazadaAutosync::findOne(["lazada_uid" => $lazadaUid, "type" => 4]);
        }
        if ($saasLazadaAutosync->status == 1) return self::manualSyncResult($lazadaUid, false, "Error_LAZ001  同步失败:产品同步任务正在进行，请稍后再试 ", 0);

        //1.2 手工触发频率控制
        $nowTime = time();
        if ($saasLazadaAutosync->manual_trigger_time > 0 and $nowTime - $saasLazadaAutosync->manual_trigger_time < $manualInterval) {
            return self::manualSyncResult($lazadaUid, false, "您的商品同步操作频率过高，请10分钟后再更新 ", 0);
        }


        //2.设置锁，正在拉取
        $autosyncId = $saasLazadaAutosync->id;
        $connection = Yii::$app->db;
        $command = $connection->createCommand("update saas_lazada_autosync set status=1,last_finish_time=$nowTime,manual_trigger_time=$nowTime where id =" . $autosyncId . " and status<>1 ");
        $affectRows = $command->execute();
        if ($affectRows <= 0) return self::manualSyncResult($lazadaUid, false, "Error_LAZ002  同步失败 ", 0);

        $saasLazadaAutosync = SaasLazadaAutosync::findOne(["id" => $autosyncId]);

        //3. 指定lazada uid进行拉取，获取拉取的listing数量
        $config = LLJHelper::getSpecificLazadaAccountInfo($lazadaUid);
        self::localLog("lazadaUpList_man trigger lazadaUid:$lazadaUid,puid=" . $saasLazadaAutosync->puid . " before _getUpdatedListForOneLazada");
        $ret = self::getFilterListingAndSaveV2($config, $saasLazadaAutosync);
        self::localLog("lazadaUpList_man trigger lazadaUid:$lazadaUid,puid=" . $saasLazadaAutosync->puid . " after _getUpdatedListForOneLazada");


        if ($ret === false)
            $result = self::manualSyncResult($lazadaUid, false, "拉取失败。请重试", 0);
        else
            $result = self::manualSyncResult($lazadaUid, true, "", $ret);

        return $result;

    }

    /**
     * 为指定的lazada账号拉取一段时间内的新增的和有修改过的listing
     * @param  $config ------该lazada的对接信息。 email和token
     * @param  $saasLazadaAutosync --- Saas_lazada_autosync 的对象
     * @return listing数量 or false
     */
    public static function getUpdatedListForOneLazada($config, $saasLazadaAutosync)
    {
        self::localLog("getUpdatedListForOneLazada start ...");

        $fetchEndTimeAdvanced = 5 * 60;  //秒为单位 .为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。

        $nowTime = time();
        $isFirstTime = 0;

        if ($saasLazadaAutosync->end_time == 0) {
            //第一次拉取
            $isFirstTime = 1;
            $start_time = 0;
            $end_time = $nowTime;
        } else if ($nowTime - $saasLazadaAutosync->end_time >= 86400) {// 为曾经中断的job 设置的
            $start_time = $saasLazadaAutosync->end_time;
            $end_time = $start_time + 86400;
        } else {
            $start_time = $saasLazadaAutosync->end_time;
            $end_time = $nowTime;
        }
        //为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
        $end_time = $end_time - $fetchEndTimeAdvanced;
        if ($start_time > $end_time)
            $start_time = $end_time - $fetchEndTimeAdvanced;

       return self::getFilterListingAndSave($config, $start_time, $end_time, $saasLazadaAutosync, $isFirstTime, $nowTime);
    }

    public static function getUpdatedListForOneLazadaV2($config, $saasLazadaAutosync)
    {
        self::localLog("getUpdatedListForOneLazada start ...");

        $fetchEndTimeAdvanced = 5 * 60;  //秒为单位 .为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。

        $nowTime = time();

        if ($saasLazadaAutosync->end_time == 0) {
            $start_time = 0;
            $end_time = $nowTime;
        } else if ($nowTime - $saasLazadaAutosync->end_time >= 86400) {// 为曾经中断的job 设置的
            $start_time = $saasLazadaAutosync->end_time;
            $end_time = $start_time + 86400;
        } else {
            $start_time = $saasLazadaAutosync->end_time;
            $end_time = $nowTime;
        }
        //为了避免由于服务器的时间不准确导致尝试拉取超前时间点的订单。这里end_time主动往前移5分钟。
        $end_time = $end_time - $fetchEndTimeAdvanced;
        if ($start_time > $end_time)
            $start_time = $end_time - $fetchEndTimeAdvanced;

        self::getFilterListingAndSaveV2($config, $start_time, $end_time, $saasLazadaAutosync);

        if (!LazadaConfig::IS_DEBUG) {
            $saasLazadaAutosync->start_time = $start_time;
            $saasLazadaAutosync->end_time = $end_time;
            $saasLazadaAutosync->save();
        }
        return true;
    }

    private static function getFilterListingAndSaveV2($config, $start_time, $end_time, $saasLazadaAutosync)
    {
        self::localLog("getFilterListingAndSaveV2 start autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ",status:" . implode(',', self::$LAZADA_PRODUCT_OTHER_STATUS) . ", start:$start_time,end:$end_time");
        if (is_numeric($start_time) && is_numeric($end_time)) {
            $reqParams = self::setUpdateTimeParam($start_time, $end_time);
        } else {
            $reqParams = array(
                "UpdatedAfter" => $start_time,
                "UpdatedBefore" => $end_time
            );
        }
        try {
            self::doInsertTask($reqParams, $config, self::$LAZADA_PRODUCT_STATUS, $saasLazadaAutosync);
            return true;
        } catch (\Exception $e) {
            $errorMessage = "getFilterListingAndSave error autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ",Exception:" . $e->getMessage();
            self::localLog($errorMessage, LogType::ERROR);
            return false;
        }
    }

    public static function manualSyncResult($lazadaUid, $ret, $message, $number)
    {
        $result = array($ret, $message, $number);

        self::localLog("lazadaUpList_man lazadaUid:$lazadaUid result:" . print_r($result, true));
        return $result;
    }

    /**
     *
     * @param unknown $saasLazadaAutosync
     * @param unknown $errorMessage
     * @param unknown $nowTime
     * @param unknown $fetchPeriod
     */
    public static function handleGetListError($saasLazadaAutosync, $errorMessage, $nowTime, $fetchPeriod)
    {
        self::localLog("lazadaUpList_fetch handleGetListError  autoSyncId:" . $saasLazadaAutosync->id . " puid:" . $saasLazadaAutosync->puid . " lazadaUid:" . $saasLazadaAutosync->lazada_uid . " errorMsg:" . $errorMessage);
        $saasLazadaAutosync->message = $errorMessage;
        $saasLazadaAutosync->error_times = $saasLazadaAutosync->error_times + 1;
        $saasLazadaAutosync->status = 3;
        //$SAA_obj->last_finish_time=$nowTime;//上一次运行时间
        $saasLazadaAutosync->next_execution_time = $nowTime + $fetchPeriod;
        $saasLazadaAutosync->update_time = $nowTime;
        if (!$saasLazadaAutosync->save(false)) {
            self::localLog("lazadaUpList_fetch handleGetListError  autoSyncId:" . $saasLazadaAutosync->id . " puid:" . $saasLazadaAutosync->puid . " lazadaUid:" . $saasLazadaAutosync->lazada_uid . "  SAA_obj->save false", "file");
        }
    }

    /**
     * 先判断是否真的抢到待处理账号
     * @param  $autosyncId -- saas_lazada_autosync表的id
     * @return null或者$SAA_obj , null表示抢不到记录
     */
    private
    static function lockLazadaAutosyncRecord($autosyncId)
    {
        $nowTime = time();
        $connection = Yii::$app->db;
        $command = $connection->createCommand("update saas_lazada_autosync set status=1,last_finish_time=$nowTime where id =" . $autosyncId . " and status<>1 ");
        $affectRows = $command->execute();
        if ($affectRows <= 0) return null; //抢不到---如果是多进程的话，有抢不到的情况
        // 抢到记录
        $saasLazadaAutoSync = SaasLazadaAutosync::findOne($autosyncId);
        return $saasLazadaAutoSync;
    }

    /**
     * 获取所有lazada用户的api访问信息。 email,token,销售站点
     */
    private
    static function getAllLazadaAccountInfoMap()
    {
        $lazadauserMap = array();

        $lazadaUsers = SaasLazadaUser::find()->all();
        foreach ($lazadaUsers as $lazadaUser) {
            $lazadauserMap[$lazadaUser->lazada_uid] = array(
                "userId" => $lazadaUser->platform_userid,
                "apiKey" => $lazadaUser->token,
                "countryCode" => $lazadaUser->lazada_site
            );
        }

        return $lazadauserMap;

    }

    /**
     * @param $start_time
     * @param $end_time
     * @return array
     */
    private
    static function setUpdateTimeParam($start_time, $end_time)
    {
        if ($start_time == 0) {
            $reqParams = array(
                "UpdatedBefore" => gmdate("Y-m-d\TH:i:s+0000", $end_time)
            );
        } else {
            $reqParams = array(
                "UpdatedAfter" => gmdate("Y-m-d\TH:i:s+0000", $start_time),
                "UpdatedBefore" => gmdate("Y-m-d\TH:i:s+0000", $end_time)
            );
        }
        return $reqParams;
    }

    private
    static function localLog($msg, $type = LogType::INFO)
    {
        CommonHelper::log($msg, self::LOG_ID, $type);
    }

    /**
     * @param $isFirstTime
     * @param $products
     * @param $lazadaUid
     * @param $totalNum
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    private
    static function updateAllStatusDB($isFirstTime, $products, $lazadaUid)
    {
        // (1) all 的内容先更新到数据库 ， 由于第一次拉取比较多，all 要插入比较多的数据，这时候要 通过group insert 来优化
        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        self::localLog("updateAllStatusDB start ... ");
        $nowTime = time();
        if ($isFirstTime == 1) {
            self::localLog("isFirstTime updateAllStatusDB start ...");
            // 1.1 组织数组数据
            list($batchInsertArr, $allProdSku) = self::prepareInsertData($products, $lazadaUid, $nowTime);
            // 1.2 吸取以前教训 在第一次插入前先请空 该店铺下的产品，确保不重复插入
            LazadaListing::deleteAll(["lazada_uid_id" => $lazadaUid]);
            // 1.3 批量插入
            $insertNum = SQLHelper::groupInsertToDb(LazadaListing::tableName(), $batchInsertArr);

        } else {
            // 不是第一次拉取的话，还是按照以前的逻辑 一个个先搜索，再保存，
            // dzt20160429 客户大批量更新产品是一个个保存始终不是办法，2456 20160304-05 一天更新了5W个产品，更新信息并更新substatus 要10个小时以上
            // 由于更新substatus已使用批量更新，所以10小时大部分都是用在这里更新信息
            // 现在通过先全部删除，再全部插入的方式处理。
            self::localLog("not FirstTime updateAllStatusDB start ...");

            list($batchInsertArr, $allProdSku) = self::prepareInsertData($products, $lazadaUid, $nowTime);

            $objSchema = LazadaListing::getTableSchema();
            $toAddColumns = array();
            if (count($batchInsertArr) > 0) {
                $listingInfo = $batchInsertArr[0];
                foreach ($objSchema->columnNames as $column) {
                    if (array_key_exists($column, $listingInfo)) {
                        $toAddColumns[] = $column;
                    }
                }
            }
            // 1.2 吸取以前教训 在第一次插入前先请空 该店铺下的产品，确保不重复插入
            LazadaListing::deleteAll(["lazada_uid_id" => $lazadaUid, "SellerSku" => $allProdSku]);
            // 1.3 批量插入
            $insertNum = \Yii::$app->subdb->createCommand()->batchInsert(LazadaListing::tableName(), $toAddColumns, $batchInsertArr)->execute();
        }
        $timeMS3 = TimeUtil::getCurrentTimestampMS();
        self::localLog("end updateAllStatusDB==> t3_2=" . ($timeMS3 - $timeMS2) . ",insertNum=" . $insertNum);
        return array($allProdSku, $insertNum);
    }

    /**
     * 调用获取产品接口,并做相关判断解析
     * @param $config
     * @param $SAA_obj
     * @param $isFirstTime
     * @param $reqParams
     * @param $products 引用类型,获取产品
     * @param $hasGotAll 引用类型,查看平台数据(lazada)是否获取完全
     * @return array|int 如果返回值为array则结束流程,返回值表示信息,如果为int,表示返回商品数量
     * 如果返回值为array则结束流程,返回值表示信息,如果为int,表示返回商品数量
     */
    public
    static function pullProducts($config, $reqParams, &$products, &$hasGotAll)
    {
        $totalNum = 0;
        $status = $reqParams["filterStatus"];
        self::localLog("pullProducts config is " . json_encode($config));
        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        while ($hasGotAll === false && $totalNum < LazadaConfig::GET_PRODUCTS_BATCH_SIZE) {
            $result = self::doGetProductsCall($config, $reqParams);
            if ($result["success"] === false) {
                self::localLog("pullProducts false, and msg is " . $result["message"]);
                return array(false, $result["message"], 0);
            }
            if (!isset($result["response"]) or !isset($result["response"]["allProducts"])) {
                self::localLog("pullProducts false, and msg is response->products not exist");
                return array(false, "response->products not exist", 0);
            }

            $offset = $result["response"]["offset"];
            $tempProducts = $result["response"]["allProducts"];

            $totalNum += self::parseProductByStatus($status, $tempProducts, $products);

            self::localLog("pullProducts==>offset:$offset totalnum=$totalNum");
            if ($result["response"]["hasGotAll"] == 1) {
                $hasGotAll = true;
            }
            $reqParams["offset"] = $offset;
        }
        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        self::localLog("end pullProducts ,status= " . json_encode($status) . ",totalNum=$totalNum,t2_1=" . ($timeMS2 - $timeMS1));
        return array(true, $totalNum, $offset);
    }

    private
    static function doGetListingAndSave($reqParams, $config, $isFirstTime, $statusArr, $saasLazadaAutosyncObj, $allProdSku = array())
    {
        $reqParams["filterStatus"] = $statusArr;
        $reqParams["offset"] = 0;
        $hasGotAll = false;
        $lazadaUid = $saasLazadaAutosyncObj->lazada_uid;
        $offset = 0;

        while (!$hasGotAll) {
            $products = array();
            $reqParams["offset"] = $offset;
            $rtn = self::pullProducts($config, $reqParams, $products, $hasGotAll);
            $totalNum = 0;
            if ($rtn[0] == false) {
                return $rtn;
            } else {
                $offset = $rtn[2];
                $totalNum += $rtn[1];
            }
            //插入数据库
            list($updateNum, $batchProdSku) = self::updateDB($products, $lazadaUid, $isFirstTime, $allProdSku);
            $allProdSku = array_merge($allProdSku, $batchProdSku);
            unset($products);
        }
        return array($allProdSku, $totalNum);
    }

    private
    static function doInsertTask($reqParams, $config, $statusArr, $saasLazadaAutosyncObj)
    {
        $reqParams["filterStatus"] = $statusArr;
        $reqParams["offset"] = 0;
        $task = new TaskQueue();
        $task->action = Actions::GET_FILTER_PRODUCTS;
        $task->config = $config;
        $task->reqParams = $reqParams;
        $task->taskStatus = TaskStatus::READY;
        $task->foreignTaskId = $saasLazadaAutosyncObj->id;
        $task->isRoot = true;
        LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::TASK_QUEUE)->insert($task);
    }

    private
    static function updateDB($products, $lazadaUid, $isFirstTime = 0, $allProdSku = array())
    {
        $allUpdateProdSku = array();
        $updateNum = 0;
        foreach ($products as $status => $product) {
            if (!empty($product)) {
                if ($status == LazadaProductStatus::ALL) {
                    list($allUpdateProdSku, $updateNum) = self::updateAllStatusDB($isFirstTime, $product, $lazadaUid);
                } else if ($status == LazadaProductStatus::DELETED) {
                    $updateNum = self::updateDeletedStatusDB($product, $lazadaUid);
                } else if ($status == LazadaProductStatus::REJECTED) {
                    $updateNum = self::updateRejectedStatusDB($product, $lazadaUid);
                } else {
                    $updateNum = self::updateOtherStatusDB($product, $lazadaUid, $allProdSku, $status);
                }
            }
        }
        return array($updateNum, $allUpdateProdSku);
    }

    private
    static function updateDBV2($product, $lazadaUid, $uid, $now)
    {
        self::localLog("updateDBV2 start ... lazadaUid:$lazadaUid,uid:$uid");
        $lazadaListingManager = LLJListingManagerFactory::getManagerByStatic(LLJListingDBConfig::LAZADA_LISTING);
        foreach ($product as $status => $productInfos) {
            $parentLazadaListings = array();
            $childLazadaListings = array();
            foreach ($productInfos as $productInfo) {
                $lazadaListing = $lazadaListingManager->findOne(array("lazadaUid" => $lazadaUid, "sellerSku" => $productInfo["SellerSku"]), array("updateTime", "subStatus", "subStatusDesc"));
                if (empty($lazadaListing)) {
                    $lazadaListing = new LazadaListingMG();
                    $lazadaListing->uid = $uid;
                    $lazadaListing->lazadaUid = $lazadaUid;
                    $lazadaListing->product = $productInfo;
                    $lazadaListing->creatTime = $now;
                    $lazadaListing->updateTime = $now;
                    $lazadaListing->subStatus = $status;
                    $lazadaListing->isEditing = 0;
                    if (!empty($productInfo["RejectReason"])) {
                        $lazadaListing->errorMsg = $productInfo["RejectReason"];
                    }
                    $lazadaListing->sellerSku = $productInfo["SellerSku"];
                    if ($productInfo["SellerSku"] == $productInfo["ParentSku"]) {
                        $lazadaListing->isParent = true;
                        $lazadaListing->childSku = array();
                        $parentLazadaListings[] = $lazadaListing;
                    } else {
                        $lazadaListing->isParent = false;
                        $childLazadaListings[] = $lazadaListing;
                    }
                } else {
                    $updateTime = $lazadaListing["updateTime"];
                    if ($now == $updateTime) {
                        $subStatus = self::mergeStatus($lazadaListing["subStatus"], $status);
                    } else {
                        $subStatus = $status;
                    }
                    if (!empty($productInfo["RejectReason"])) {
                        $lazadaListingManager->findAndModify(array("_id" => $lazadaListing["_id"]), array('$set' => array("product" => $productInfo, "subStatus" => $subStatus, "errorMsg" => $productInfo["RejectReason"])));
                    } else {
                        $lazadaListingManager->findAndModify(array("_id" => $lazadaListing["_id"]), array('$set' => array("product" => $productInfo, "subStatus" => $subStatus)));
                    }
                }
            }
            if (!empty($parentLazadaListings)) {
                $lazadaListingManager->batchInsert($parentLazadaListings);
            }
            if (!empty($childLazadaListings)) {
                foreach ($childLazadaListings as $childLazadaListing)
                    $lazadaListingManager->findAndModify(array("lazadaUid" => $lazadaUid, "sellerSku" => $childLazadaListing->product["ParentSku"]), array('$addToSet' => array("childSku" => $childLazadaListing->sellerSku)));
                $lazadaListingManager->batchInsert($childLazadaListings);
            }
        }
        self::localLog("updateDBV2 end! lazadaUid:$lazadaUid,uid:$uid");
    }

    private
    static function mergeStatus($old, $newStatus)
    {
//        $arrStr = str_split($old);
//        $index = LazadaProductStatus::$STATUS_POSITION[$newStatus];
//        if (count($arrStr) < $index + 1) {
//            for ($i = count($arrStr); $i < $index; $i++) {
//                $arrStr[$i] = "0";
//            }
//            $arrStr[$index] = "1";
//        } else {
//            $arrStr[$index] = "1";
//        }
//        return implode($arrStr);
//        if()
        if (strstr($old, $newStatus) === false) {
            return $old . ";" . $newStatus;
        } else {
            return $old;
        }
    }

    private
    static function createStatus($status)
    {
        $index = LazadaProductStatus::$STATUS_POSITION[$status];
        for ($i = 0; $i < $index; $i++) {
            $arrStr[$i] = "0";
        }
        $arrStr[$index] = "1";
        return bindec(implode($arrStr));
    }

    private
    static function updateDeletedStatusDB($products, $lazadaUid)
    {
        self::localLog("updateDeletedStatusDB start ...");
        $nowTime = time();
        foreach ($products as $listingInfo) {
            self::assembleLazadaListingInfoPart1($listingInfo);
            $lazadaListingObj = self::updateDeletedOrRejectedPart1($listingInfo["SellerSku"], $lazadaUid, $nowTime, LazadaProductStatus::DELETED);
            $lazadaListingObj->setAttributes($listingInfo);
            $lazadaListingObj->save(false);
        }
//        LazadaListing::updateAll(['sub_status' => LazadaProductStatus::DELETED, 'update_time' => $nowTime], ["lazada_uid_id" => $lazadaUid]);
        $updateNum = count($products);
        self::localLog("updateDeletedStatusDB end , update size is " . $updateNum);
        return $updateNum;
    }

    private
    static function updateRejectedStatusDB($products, $lazadaUid)
    {
        self::localLog("updateRejectedStatusDB start ...");
        $nowTime = time();
        foreach ($products as $listingInfo) {
            $lazadaListingObj = self::updateDeletedOrRejectedPart1($listingInfo["SellerSku"], $lazadaUid, $nowTime, LazadaProductStatus::REJECTED);
            if (empty($listingInfo["RejectReason"]))
                $listingInfo["RejectReason"] = "";
            $lazadaListingObj->error_message = $listingInfo["RejectReason"];
            $lazadaListingObj->save(false);
        }
//        LazadaListing::updateAll(['sub_status' => LazadaProductStatus::REJECTED, 'update_time' => $nowTime], ["lazada_uid_id" => $lazadaUid]);
        $updateNum = count($products);
        self::localLog("updateRejectedStatusDB end, update size is " . $updateNum);
        return $updateNum;
    }

    private
    static function updateOtherStatusDB($products, $lazadaUid, $allProdSku, $status)
    {
        self::localLog("updateOtherStatusDB start ... and status is " . $status);
        $nowTime = time();
        foreach ($products as $listingInfo) {
            $notCreate = self::checkIsCreated($lazadaUid, $allProdSku, $listingInfo["SellerSku"]);
//            self::localLog("listingInfo is " . json_encode($listingInfo));
            if ($notCreate) {
                //正常来说应该不会走入这个分支,get all的时候会插入所有
                // 测试究竟有多少状态会进入这里或者，根本不会进入这里
                $lazadaListingObj = new LazadaListing;
                $lazadaListingObj->setAttributes($listingInfo);
                $lazadaListingObj->sub_status = $status;
                $lazadaListingObj->create_time = $nowTime;
                $lazadaListingObj->update_time = $nowTime;
                $lazadaListingObj->lazada_uid_id = $lazadaUid;
                if(empty(self::$LAZADA_ACCOUNT_INFO_MAP[$lazadaUid])){
                    $SLU = SaasLazadaUser::findOne($lazadaUid);
                    $lazadaListingObj->platform = $SLU->platform;
                    $lazadaListingObj->site = $SLU->lazada_site;
                }else{
                    $lazadaListingObj->platform = self::$LAZADA_ACCOUNT_INFO_MAP[$lazadaUid]['platform'];
                    $lazadaListingObj->site = self::$LAZADA_ACCOUNT_INFO_MAP[$lazadaUid]['site'];
                }
                    
                $lazadaListingObj->save(false);
            } else {
                $toUpSubStatusSkus[] = $listingInfo["SellerSku"];// 剩余的通过update All 更新sub_status
            }
        }
//        self::localLog("update sku is ".json_encode($toUpSubStatusSkus));
        $length = count($toUpSubStatusSkus);
        $step = 1000;
        for ($i = 0; $i < $length; $i = $i + $step) {
            $toUpSubStatusSkusSlice = array_slice($toUpSubStatusSkus, $i, $step);
            LazadaListing::updateAll(['sub_status' => $status, 'update_time' => $nowTime], ["lazada_uid_id" => $lazadaUid, "SellerSku" => $toUpSubStatusSkusSlice]);
        }
        $updateNum = count($products);
        self::localLog("updateOtherStatusDB end , update size is " . $updateNum);
        return $updateNum;
    }

    private
    static function getFilterListingAndSave($config, $start_time, $end_time, $saasLazadaAutosync, $isFirstTime, $nowTime)
    {
        self::localLog("getFilterListingAndSave start autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ",status:" . implode(',', self::$LAZADA_PRODUCT_OTHER_STATUS) . ", start:$start_time,end:$end_time");
        $fetchPeriod = 60 * 60; //秒为单位. 2次后台自动拉取的时间间隔
        $failRetryPeriod = 5 * 60;
        
        if(empty(self::$LAZADA_ACCOUNT_INFO_MAP[$saasLazadaAutosync->lazada_uid])){
            self::$LAZADA_ACCOUNT_INFO_MAP[$saasLazadaAutosync->lazada_uid] = array(
                    'platform'=>$saasLazadaAutosync->platform,
                    'site'=>$saasLazadaAutosync->site
            );
        }
        
        if (is_numeric($start_time) && is_numeric($end_time)) {
            $reqParams = self::setUpdateTimeParam($start_time, $end_time);
        } else {
            $reqParams = array(
                "UpdatedAfter" => $start_time,
                "UpdatedBefore" => $end_time
            );
//            $utc = new \DateTimeZone('UTC');
            $start_date = date_create_from_format("Y-m-d\TH:i:sT", $start_time);
            $end_date = date_create_from_format("Y-m-d\TH:i:sT", $end_time);
            $start_time = $start_date->getTimestamp();
            $end_time = $end_date->getTimestamp();
        }
        // 由于拉取个数太多 导致重新在ealge 添加分页
        // 由于上w 个产品所有状态一起获取会导致proxy 内存爆了，所以这里 重新加入分页
        // 但由于分页获取所有状态比较麻烦，控制其他状态get 到的产 品必须 all也 get 到，所以这里干脆先单独把all get 完在get其他状态的产品
        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        try {
            //(1)all状态产品拉取
            list($allProdSku, $totalNum) = self::doGetListingAndSave($reqParams, $config, $isFirstTime, array(LazadaProductStatus::ALL), $saasLazadaAutosync);
            //如果拉取过程中出现问题,终止执行,主要问题有token出错
            if($allProdSku===false){// dzt20161222 失败修改next_execution_time
                self::handleGetListError($saasLazadaAutosync, $totalNum, $nowTime, $failRetryPeriod);
                return false;
            }
            //(2) 非 all的拉取
            self::doGetListingAndSave($reqParams, $config, $isFirstTime, self::$LAZADA_PRODUCT_OTHER_STATUS, $saasLazadaAutosync, $allProdSku);
            $saasLazadaAutosync->start_time = $start_time;
            $saasLazadaAutosync->end_time = $end_time;
            $saasLazadaAutosync->status = 2;
            $saasLazadaAutosync->error_times = 0;
            $saasLazadaAutosync->message = "";
            $saasLazadaAutosync->update_time = $nowTime;
            $saasLazadaAutosync->next_execution_time = $nowTime + $fetchPeriod;

            if (!$saasLazadaAutosync->save(false)) {
                self::localLog("getFilterListingAndSave  error  autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . "  saas_obj save false message:" . print_r($saasLazadaAutosync->errors, true), LogType::ERROR);
            }
            $timeMS2 = TimeUtil::getCurrentTimestampMS();
            self::localLog("getFilterListingAndSave end autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ",lazadaUid:" . $saasLazadaAutosync->lazada_uid . ",totalNum=$totalNum,t2_1=" . ($timeMS2 - $timeMS1));
            return $totalNum;
        } catch (\Exception $e) {
            $errorMessage = "getFilterListingAndSave error autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ",Exception:file:" . $e->getFile() . ",line:" . $e->getFile() . ",message:" . $e->getMessage();
            self::localLog($errorMessage, LogType::ERROR);
            if ($saasLazadaAutosync->error_times > 5) {
                self::handleGetListError($saasLazadaAutosync, $errorMessage, $nowTime, $fetchPeriod);
            } else {
                self::handleGetListError($saasLazadaAutosync, $errorMessage, $nowTime, $failRetryPeriod);
            }
            return false;
        }
    }


    /**
     * @param $status
     * @param $totalNum
     * @param $tempProducts
     * @param $products
     * @return array
     */
    private
    static function parseProductByStatus(array $status, $tempProducts, &$products)
    {
        $totalNum = 0;
        foreach ($status as $statu) {
            if (!empty($tempProducts[$statu])) {
                if (!empty($products[$statu])) {
                    $products[$statu] = array_merge($products[$statu], $tempProducts[$statu]);
                } else {
                    $products[$statu] = $tempProducts[$statu];
                }

                $num = count($tempProducts[$statu]);
                $totalNum += $num;
            }
        }
        return $totalNum;
    }

    /**
     * @param $listingInfo
     * @param $batchInsertArr
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    private
    static function prepareInsertData($products, $lazadaUid, $nowTime)
    {

        $batchInsertArr = array();
        $allProdSku = array();
//        foreach ($products as $product) {
        foreach ($products as $listingInfo) {
            $sellerSku = $listingInfo["SellerSku"];
            $allProdSku[] = $sellerSku;
            self::assembleLazadaListingInfoPart1($listingInfo);
            self::assembleLazadaListingInfoPart2($listingInfo, $lazadaUid, $nowTime);
            $toAddListing = array();
            $objSchema = LazadaListing::getTableSchema();
            foreach ($objSchema->columnNames as $column) {
                if (isset($listingInfo[$column])) {
                    $toAddListing[$column] = $listingInfo[$column];
                }
            }

            if (!empty($toAddListing))
                $batchInsertArr[] = $toAddListing;
        }
//        }
        return array($batchInsertArr, $allProdSku);
    }

    /**
     * @param $lazadaUid
     * @param $allProdSku
     * @param $sellerSku
     * @return array
     */
    private
    static function checkIsCreated($lazadaUid, $allProdSku, $sellerSku)
    {
        $notCreate = false;
        if (!empty($allProdSku)) {// 保底以防$allProdSku 忘了填。。
            if (!in_array($sellerSku, $allProdSku)) {// $allProdSku 是这次任务 这个账号更新的所有产品，所以不存在串号问题，这样省一个步骤的sql查询
                $notCreate = true;
                return $notCreate;
            }
            return $notCreate;
        } else {
            $lazadaListingObj = LazadaListing::findOne(["SellerSku" => $sellerSku, "lazada_uid_id" => $lazadaUid]);
            if ($lazadaListingObj === null) {
                $notCreate = true;
                return $notCreate;
            }
            return $notCreate;
        }
    }

    /**
     * @param $sellerSku
     * @param $lazadaUid
     * @param $nowTime
     * @return LazadaListing|static
     */
    private
    static function updateDeletedOrRejectedPart1($sellerSku, $lazadaUid, $nowTime, $status)
    {
        $lazadaListingObj = LazadaListing::findOne(["SellerSku" => $sellerSku, "lazada_uid_id" => $lazadaUid]);
        // 由于下面$status=="deleted"的setAttributes报错过 ， 所以这里加上。只是不明白为什么会出现报错。如果find 不到，按道理会进入上面的if $notCreate==true条件里面
        if (empty($lazadaListingObj)) {
            $lazadaListingObj = new LazadaListing;
            if(empty(self::$LAZADA_ACCOUNT_INFO_MAP[$lazadaUid])){
                $SLU = SaasLazadaUser::findOne($lazadaUid);
                $lazadaListingObj->platform = $SLU->platform;
                $lazadaListingObj->site = $SLU->lazada_site;
            }else{
                $lazadaListingObj->platform = self::$LAZADA_ACCOUNT_INFO_MAP[$lazadaUid]['platform'];
                $lazadaListingObj->site = self::$LAZADA_ACCOUNT_INFO_MAP[$lazadaUid]['site'];
            }
            
            $lazadaListingObj->lazada_uid_id = $lazadaUid;
        }// dzt20151225 all get 不了deleted产品
        
        $lazadaListingObj->sub_status = $status;
        $lazadaListingObj->update_time = $nowTime;
        return $lazadaListingObj;
    }

    /**
     * @param $listingInfo
     */
    private
    static function assembleLazadaListingInfoPart1(&$listingInfo)
    {
        if ($listingInfo["SalePrice"] === "") $listingInfo["SalePrice"] = 0;
        if (empty($listingInfo["Price"])) $listingInfo["Price"] = 0;

        if (!empty($listingInfo["SaleStartDate"])) {//dzt20151130 不能用isset ，api 返回 确实isset 但为 "" ,这样下面操作会把当前时间写入字段
            $listingInfo["SaleStartDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleStartDate"]);
        } else
            $listingInfo["SaleStartDate"] = 0;

        if (!empty($listingInfo["SaleEndDate"])) {//dzt20151130 api传输为"" setAttributes SaleStartDate为"" 但数据库不能为"" 只能为int default 0 ,  上下的SaleStartDate , SaleEndDate 同理
            $listingInfo["SaleEndDate"] = TimeUtil::getTimestampFromISO8601($listingInfo["SaleEndDate"]);
        } else
            $listingInfo["SaleEndDate"] = 0;
    }

    /**
     * @param $lazadaUid
     * @param $listingInfo
     * @param $nowTime
     * @return mixed
     */
    private
    static function assembleLazadaListingInfoPart2(&$listingInfo, $lazadaUid, $nowTime)
    {
        // SQLHelper::groupInsertToDb 插入拼出来的key值 在第一个data的 key里面获取，如果数组的key数量不一致的话会 报错
        // 所以这里控制数组所有元素 的key的数量一致
        if (empty($listingInfo["FulfillmentBySellable"]))
            $listingInfo["FulfillmentBySellable"] = 0;

        if (empty($listingInfo["FulfillmentByNonSellable"]))
            $listingInfo["FulfillmentByNonSellable"] = 0;

        if (empty($listingInfo["ReservedStock"]))
            $listingInfo["ReservedStock"] = 0;

        if (empty($listingInfo["RealTimeStock"]))
            $listingInfo["RealTimeStock"] = 0;
        $listingInfo['create_time'] = $nowTime;
        $listingInfo['update_time'] = $nowTime;
        $listingInfo['lazada_uid_id'] = $lazadaUid;
        if(empty(self::$LAZADA_ACCOUNT_INFO_MAP[$lazadaUid])){
            $SLU = SaasLazadaUser::findOne($lazadaUid);
            $listingInfo['platform'] = $SLU->platform;
            $listingInfo['site'] = $SLU->lazada_site;
        }else{
            $listingInfo['platform'] = self::$LAZADA_ACCOUNT_INFO_MAP[$lazadaUid]['platform'];
            $listingInfo['site'] = self::$LAZADA_ACCOUNT_INFO_MAP[$lazadaUid]['site'];
        }
    }

    /**
     * 调用接口
     * @param $config
     * @param $reqParams
     * @return mixed
     */
    private
    static function doGetProductsCall($config, $reqParams)
    {
        self::localLog("start doGetProductsCall " . json_encode($reqParams));
        // 目前测试到 us proxy 获取1000个product 要3s ，10000产品 60+基本可以从proxy 获取完。但100+M 传输时间也比较长
        // 但是260s 后仍然获取不到返回结果。
        $timeMSTmp1 = TimeUtil::getCurrentTimestampMS();
        $result = LazadaInterface_Helper::getFilterProductsV2($config, $reqParams);
        $timeMSTmp2 = TimeUtil::getCurrentTimestampMS();
        self::localLog("end doGetProductsCall and time consume t2_t1=" . ($timeMSTmp2 - $timeMSTmp1));
        return $result;
    }
    
    /**
     * 调用拉产品接口 搜索保存产品到本地
     * 
     */
    public static function searchAndSaveProduct($tmpConfig, $apiReqParams){
        
        
        $config =  array(
                "userId" => $tmpConfig['userId'],
                "apiKey" => $tmpConfig['apiKey'],
                "countryCode" => $tmpConfig['countryCode'],
        );
        
        \Yii::info("searchAndSaveProduct before LazadaInterface_Helper::getProducts apiReqParams:" . json_encode($apiReqParams), "file");
        $result = LazadaInterface_Helper::getProducts($config, $apiReqParams);
        // \Yii::info("LazadaInterface_Helper::getProducts result:" . json_encode($result, true), "file");
        
        if ($result["success"] === false || !isset($result["response"]) 
                || !isset($result["response"]["products"]) || count($result["response"]["products"]) == 0) {
            // 获取产品失败
            \Yii::info("searchAndSaveProduct after LazadaInterface_Helper::getProducts result:".json_encode($result), "file");
            
            $errMsg = "";
            if(!empty($result["message"]))
                $errMsg = $result["message"];
            elseif(empty($result["response"]["products"]))
                $errMsg = "没有搜索结果";
            else 
                $errMsg = "未知原因";
            return array(false, $errMsg);
            
        } else {
            $nowTime = time();
            $totalCount = count($result["response"]["products"]);
            $upCount = 0;
            foreach ($result["response"]["products"] as $updatedPdInfo) {
                if ($updatedPdInfo["SalePrice"] === "") $updatedPdInfo["SalePrice"] = 0;
                if (empty($updatedPdInfo["Price"])) $listingInfo["Price"] = 0;
                 
                if (!empty($updatedPdInfo["SaleStartDate"])) {
                    $updatedPdInfo["SaleStartDate"] = TimeUtil::getTimestampFromISO8601($updatedPdInfo["SaleStartDate"]);
                } else
                    $updatedPdInfo["SaleStartDate"] = 0;
                 
                if (!empty($updatedPdInfo["SaleEndDate"])) {
                    $updatedPdInfo["SaleEndDate"] = TimeUtil::getTimestampFromISO8601($updatedPdInfo["SaleEndDate"]);
                } else
                    $updatedPdInfo["SaleEndDate"] = 0;
                 
                if (empty($updatedPdInfo["FulfillmentBySellable"]))
                    $updatedPdInfo["FulfillmentBySellable"] = 0;
                 
                if (empty($updatedPdInfo["FulfillmentByNonSellable"]))
                    $updatedPdInfo["FulfillmentByNonSellable"] = 0;
                 
                if (empty($updatedPdInfo["ReservedStock"]))
                    $updatedPdInfo["ReservedStock"] = 0;
                 
                if (empty($updatedPdInfo["RealTimeStock"]))
                    $updatedPdInfo["RealTimeStock"] = 0;
                
                $listing = LazadaListing::findOne(['lazada_uid_id' => $tmpConfig['lazada_uid'], 'SellerSku'=>$updatedPdInfo["SellerSku"]]);
                if(empty($listing)){
                    $listing = new LazadaListing;
                    $listing->lazada_uid_id = $tmpConfig['lazada_uid'];
                    $listing->create_time = $nowTime;
                }
                
                $listing->update_time = $nowTime;
                $listing->setAttributes($updatedPdInfo);
                $listing->error_message = "";
                 
                if (!$listing->save(false)) {
                    \Yii::info("searchAndSaveProduct lazada_uid:".$tmpConfig['lazada_uid']."，SellerSku:".$updatedPdInfo['SellerSku']." listing->save fail:" . print_r($listing->errors, true), "file");
                }else{
                    $upCount++;
                }
            }
            
            return array(true, "搜索到{$totalCount} 个产品，更新了{$upCount} 个");
        }
    }
    
    
    
    
    
    

}