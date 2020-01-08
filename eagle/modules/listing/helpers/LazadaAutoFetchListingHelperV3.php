<?php
namespace eagle\modules\listing\helpers;

use common\api\lazadainterface\LazadaInterface_Helper;
use console\helpers\CommonHelper;
use console\helpers\LogType;
use eagle\models\SaasLazadaAutosync;
use eagle\models\SaasLazadaUser;
use eagle\modules\util\helpers\SQLHelper;
use eagle\modules\util\helpers\TimeUtil;
use Yii;
use eagle\modules\listing\models\LazadaListingV2;
use eagle\assets\PublicAsset;
use eagle\modules\listing\models\LazadaListing;
use eagle\models\SaasLazadaAutosyncV2;
use common\api\lazadainterface\LazadaInterface_Helper_V2;


/**
 * Class LazadaAutoFetchListingHelperV3
 * @package eagle\modules\listing\helpers
 * Lazada 数据同步类,主要执行订单数据同步
 * 复制自LazadaAutoFetchListingHelperV2 ， 但由于Lazada 接口更新，所以部分逻辑需要修改，所以出了LazadaAutoFetchListingHelperV3
 * @author dzt 2016/12/21
 * @version 3.0
 * @since 3.0
 */
class LazadaAutoFetchListingHelperV3
{
    const LOG_ID = "eagle-modules-listing-helpers-LazadaAutoFetchListingHelperV3";
    public static $LAZADA_PRODUCT_OTHER_STATUS = array(LazadaProductStatus::PENDING, LazadaProductStatus::LIVE, LazadaProductStatus::REJECTED, LazadaProductStatus::IMAGE_MISSING, LazadaProductStatus::SOLD_OUT, LazadaProductStatus::INACTIVE, LazadaProductStatus::DELETED);
    public static $LAZADA_PRODUCT_STATUS = array(LazadaProductStatus::ALL, LazadaProductStatus::PENDING, LazadaProductStatus::LIVE, LazadaProductStatus::REJECTED, LazadaProductStatus::IMAGE_MISSING, LazadaProductStatus::SOLD_OUT, LazadaProductStatus::INACTIVE, LazadaProductStatus::DELETED);
    public static $IS_FIRST_TIME = 0;
    public static $ALL_SKU = array();
    public static $PRODUCT_INFO_MAP = array();
    
    /**
     *  后台触发------获取指定时间内所有账户的有更新的订单，第一次就是获取所有listing，后面就是拉取新增或者有修改的listing
     */
    public static function getUpdatedistingByID($id)
    {
        self::localLog("getUpdatedistingByID start ...");

        // 先判断是否真的抢到待处理账号
        $saasLazadaAutosync = SaasLazadaAutosync::findOne(['lazada_uid'=>$id,'type'=>4]);
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
        $SAA_obj = SaasLazadaAutosync::findOne(['lazada_uid'=>$id,'type'=>4]);
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
    public static function getUpdatedisting()
    {
        self::localLog("getUpdatedisting start ...");
        $connection = \Yii::$app->db;
        $type = 4;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单，4----获取listing
        $nowTime = time();
        // 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题
        $sqlStr = 'select `id` from  `saas_lazada_autosync` as sla ' .
            ' where sla.`platform`="lazada" AND sla.`is_active` = 1 AND sla.`status` <>1 AND sla.`error_times` < 15 AND sla.`type`='.$type.' AND sla.next_execution_time<'.$nowTime.
            ' ORDER BY sla.`next_execution_time` ASC LIMIT 10 ';

//         $sqlStr = 'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` <>1 ' .
//                 'AND `lazada_uid` = 1 AND `error_times` < 15 AND `type`=' . $type . '  AND  next_execution_time<' . $nowTime . ' ORDER BY `next_execution_time` ASC LIMIT 10 ';
        
        $command = $connection->createCommand($sqlStr);

        $hasGotRecord=false;
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

            $hasGotRecord = true;
            self::localLog("getUpdatedisting autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid);

            //3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
            //3.1 整理请求参数
            $config = $allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];

            $result = self::getUpdatedListForOneLazada($config, $SAA_obj);
        }
        return $hasGotRecord;
    }
    
    /**
     *  后台触发------获取指定时间内所有账户的有更新的订单，第一次就是获取所有listing，后面就是拉取新增或者有修改的listing(新接口)
     */
    public static function getUpdatedistingV2()
    {
        self::localLog("getUpdatedistingV2 start ...");
        $connection = \Yii::$app->db;
        $type = 4;//同步job类型:1--获取旧订单,2--获取最新create产生的订单,3--获取最新update产生的订单，4----获取listing
        $nowTime = time();
        // 同步状态:  status 0--没处理过, 1--执行中, 2--完成, 3--上一次执行有问题
        $sqlStr = 'select `id` from  `saas_lazada_autosync_v2` as sla ' .
            ' where sla.`platform`="lazada" AND sla.`is_active` = 1 AND sla.`status` <>1 AND sla.`error_times` < 15 AND sla.`type`='.$type.' AND sla.next_execution_time<'.$nowTime.
            ' ORDER BY sla.`next_execution_time` ASC LIMIT 10 ';
    
        //         $sqlStr = 'select `id` from  `saas_lazada_autosync` where `is_active` = 1 AND `status` <>1 ' .
        //                 'AND `lazada_uid` = 1 AND `error_times` < 15 AND `type`=' . $type . '  AND  next_execution_time<' . $nowTime . ' ORDER BY `next_execution_time` ASC LIMIT 10 ';
    
        $command = $connection->createCommand($sqlStr);
    
        $hasGotRecord=false;
        $allLazadaAccountsInfoMap = LLJHelper::getAllLLJAccountInfoMap();
        $dataReader = $command->query();
        while (($row = $dataReader->read()) !== false) {
            self::localLog("getUpdatedistingV2 dataReader->read() " . $row['id']);
            // 先判断是否真的抢到待处理账号
            $SAA_obj = self::lockLazadaAutosyncRecordV2($row['id']);
            if ($SAA_obj === null) {
                self::localLog("task is processing and skip");
                continue;
            } //抢不到---如果是多进程的话，有抢不到的情况
    
            $hasGotRecord = true;
            self::localLog("getUpdatedistingV2 autoSyncId:" . $SAA_obj->id . ",puid:" . $SAA_obj->puid . ",lazadaUid:" . $SAA_obj->lazada_uid);
    
            //3. 调用渠道的api来获取对应账号的订单列表信息，并把渠道返回的所有的订单header的信息保存到订单详情同步表QueueLazadaGetorder
            //3.1 整理请求参数
            $config = $allLazadaAccountsInfoMap[$SAA_obj->lazada_uid];
    
            $result = self::getUpdatedListForOneLazada($config, $SAA_obj);
        }
        return $hasGotRecord;
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
        //区分是否新授权
        $user = SaasLazadaUser::findOne(["lazada_uid"=>$lazadaUid]);
        if(!empty($user->version)){//新授权
            $saasLazadaAutosync = SaasLazadaAutosyncV2::findOne(["lazada_uid" => $lazadaUid, "type" => 4]);
        }else{
            $saasLazadaAutosync = SaasLazadaAutosync::findOne(["lazada_uid" => $lazadaUid, "type" => 4]);
        }
        
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
            if(!empty($user->version)){//新授权
                $saasLazadaAutosync = SaasLazadaAutosyncV2::findOne(["lazada_uid" => $lazadaUid, "type" => 4]);
            }else{
                $saasLazadaAutosync = SaasLazadaAutosync::findOne(["lazada_uid" => $lazadaUid, "type" => 4]);
            }
            
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
        if(!empty($user->version)){//新授权
            $command = $connection->createCommand("update saas_lazada_autosync_v2 set status=1,last_finish_time=$nowTime,manual_trigger_time=$nowTime where id =" . $autosyncId . " and status<>1 ");
        }else{
            $command = $connection->createCommand("update saas_lazada_autosync set status=1,last_finish_time=$nowTime,manual_trigger_time=$nowTime where id =" . $autosyncId . " and status<>1 ");
        }
        
        $affectRows = $command->execute();
        if ($affectRows <= 0) return self::manualSyncResult($lazadaUid, false, "Error_LAZ002  同步失败 ", 0);

        if(!empty($user->version)){//新授权
            $saasLazadaAutosync = SaasLazadaAutosyncV2::findOne(["id" => $autosyncId]);
        }else{
            $saasLazadaAutosync = SaasLazadaAutosync::findOne(["id" => $autosyncId]);
        }
        //3. 指定lazada uid进行拉取，获取拉取的listing数量
        $config = LLJHelper::getSpecificLazadaAccountInfo($lazadaUid);
        self::localLog("lazadaUpList_man trigger lazadaUid:$lazadaUid,puid=" . $saasLazadaAutosync->puid . " before _getUpdatedListForOneLazada");
        $ret = self::getUpdatedListForOneLazada($config, $saasLazadaAutosync);
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
        self::$IS_FIRST_TIME = 0;
        self::$ALL_SKU[] = array();
        self::$PRODUCT_INFO_MAP = array();
        if ($saasLazadaAutosync->end_time == 0) {
            //第一次拉取
            $isFirstTime = 1;
            self::$IS_FIRST_TIME = 1;
            $start_time = 0;
            $end_time = $nowTime;
//         } else if ($nowTime - $saasLazadaAutosync->end_time >= 86400) {// 为曾经中断的job 设置的
//             $start_time = $saasLazadaAutosync->end_time;
//             $end_time = $start_time + 86400;
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
     * 先判断是否真的抢到待处理账号,新授权
     * @param  $autosyncId -- saas_lazada_autosync_v2表的id
     * @return null或者$SAA_obj , null表示抢不到记录
     */
    private
    static function lockLazadaAutosyncRecordV2($autosyncId)
    {
        $nowTime = time();
        $connection = Yii::$app->db;
        $command = $connection->createCommand("update saas_lazada_autosync_v2 set status=1,last_finish_time=$nowTime where id =" . $autosyncId . " and status<>1 ");
        $affectRows = $command->execute();
        if ($affectRows <= 0) return null; //抢不到---如果是多进程的话，有抢不到的情况
        // 抢到记录
        $saasLazadaAutoSync = SaasLazadaAutosyncV2::findOne($autosyncId);
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
    static function updateAllStatusDB($isFirstTime, $products, $saasLazadaAutosyncObj)
    {
        // (1) all 的内容先更新到数据库 ， 由于第一次拉取比较多，all 要插入比较多的数据，这时候要 通过group insert 来优化
        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        $lazadaUid = $saasLazadaAutosyncObj->lazada_uid;
        self::localLog("updateAllStatusDB start ... ");
        $nowTime = time();
        if (self::$IS_FIRST_TIME == 1) {
            self::localLog("isFirstTime updateAllStatusDB start ...");
            // 1.1 组织数组数据
            list($batchInsertArr, $toAddColumns, $allProdSku) = self::prepareInsertData($products, $saasLazadaAutosyncObj, $nowTime);
            // 1.2 吸取以前教训 在第一次插入前先请空 该店铺下的产品，确保不重复插入
            $deletedNum = LazadaListingV2::deleteAll(["lazada_uid" => $lazadaUid]);
            self::localLog("isFirstTime updateAllStatusDB lazadaUid:$lazadaUid,deletedNum:$deletedNum");
            self::$IS_FIRST_TIME = 0;
            // 1.3 批量插入
            $insertNum = SQLHelper::groupInsertToDb(LazadaListingV2::tableName(), $batchInsertArr);// dzt20170204
            // dzt20170204 第一次拉取的时候 已经调节到2000个产品一次插入 还是偶尔会出现 MySQL server has gone away或者 Got a packet bigger than 'max_allowed_packet' bytes 问题
            // 所以重新改用增强的接口插入
//             $insertNum = \Yii::$app->subdb->createCommand()->batchInsert(LazadaListingV2::tableName(), $toAddColumns, $batchInsertArr)->execute();
        } else {
            // 不是第一次拉取的话，还是按照以前的逻辑 一个个先搜索，再保存，
            // dzt20160429 客户大批量更新产品是一个个保存始终不是办法，2456 20160304-05 一天更新了5W个产品，更新信息并更新substatus 要10个小时以上
            // 由于更新substatus已使用批量更新，所以10小时大部分都是用在这里更新信息
            // 现在通过先全部删除，再全部插入的方式处理。这样 产品的operation 记录，create_time等字段就基本没作用了
            self::localLog("not FirstTime updateAllStatusDB start ...");
            list($batchInsertArr, $toAddColumns, $allProdSku) = self::prepareInsertData($products, $saasLazadaAutosyncObj, $nowTime);
            $deletedNum = LazadaListingV2::deleteAll(["lazada_uid" => $lazadaUid, "SellerSku" => $allProdSku]);
            self::localLog("not FirstTime updateAllStatusDB lazadaUid:$lazadaUid,deletedNum:$deletedNum");
            
            $insertNum = SQLHelper::groupInsertToDb(LazadaListingV2::tableName(), $batchInsertArr);// dzt20170204
//             $insertNum = \Yii::$app->subdb->createCommand()->batchInsert(LazadaListingV2::tableName(), $toAddColumns, $batchInsertArr)->execute();
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
        // dzt20170126 LazadaConfig::GET_PRODUCTS_BATCH_SIZE=5000的时候 ， 不时出现  MySQL server has gone away，Got a packet bigger than 'max_allowed_packet' bytes问题
        // 由于新接口返回产品数据比以前多，所以现在尝试只保存 2000个 看看稳定不
        while ($hasGotAll === false && $totalNum < LazadaConfig::GET_NEW_LAZADA_PRODUCTS_BATCH_SIZE) {
            $result = self::doGetProductsCall($config, $reqParams);
            if ($result["success"] === false) {
                self::localLog("pullProducts false, and result:" . json_encode($result,true));
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
        $totalNum = 0;
        
        while (!$hasGotAll) {
            $products = array();
            $reqParams["offset"] = $offset;
            $rtn = self::pullProducts($config, $reqParams, $products, $hasGotAll);
            if ($rtn[0] == false) {
                return $rtn;
            } else {
                $offset = $rtn[2];
                $totalNum += $rtn[1];
            }
            //插入数据库
            list($updateNum, $batchProdSku) = self::updateDB($products, $saasLazadaAutosyncObj, $isFirstTime, $allProdSku);
            $allProdSku = array_merge($allProdSku, $batchProdSku);
            unset($products);
        }
        return array($allProdSku, $totalNum);
    }

    private
    static function updateDB($products, $saasLazadaAutosyncObj, $isFirstTime = 0, $allProdSku = array())
    {
        $allUpdateProdSku = array();
        $updateNum = 0;
        foreach ($products as $status => $product) {
            if (!empty($product)) {
                if ($status == LazadaProductStatus::ALL) {
                    list($allUpdateProdSku, $updateNum) = self::updateAllStatusDB($isFirstTime, $product, $saasLazadaAutosyncObj);
                } else if ($status == LazadaProductStatus::DELETED) {
                    $updateNum = self::updateDeletedStatusDB($product, $saasLazadaAutosyncObj);
                } else {// dzt20170123 新流程 将不再通过get other状态的产品来填上sub_status ,而是通过 getQc ，所以按道理不需要有产品进入这里了
                    self::localLog("updateDB ERROR ,there are ".count($product)." products in status：$status should not get.",LogType::ERROR);
                }
            }
        }
        return array($updateNum, $allUpdateProdSku);
    }

    private
    static function mergeStatus($old, $newStatus)
    {
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
    static function updateDeletedStatusDB($products, $saasLazadaAutosyncObj)
    {
        self::localLog("updateDeletedStatusDB start ...");
        $nowTime = time();
        $updateNum = 0;
        foreach ($products as $product) {
            //新接口，新字段
            if(isset($product['skus'])){
                $product['Skus'] = $product['skus'];
                unset($product['skus']);
            }
            
            if(isset($product['attributes'])){
                $product['Attributes'] = $product['attributes'];
                unset($product['attributes']);
            }
            foreach ($product['Skus'] as $skuInfo){
                
                if(empty($product['group_id']))// 取产品的其中一个sku
                    $product['group_id'] = $skuInfo['SellerSku'];
                
                $listingInfo = $product;
                self::assembleLazadaListingInfoPart1($skuInfo,$listingInfo,$nowTime);
                $lazadaListingObj = self::updateDeletedOrRejectedPart1($skuInfo["SellerSku"], $saasLazadaAutosyncObj, $nowTime, LazadaProductStatus::DELETED);
                if($lazadaListingObj->isNewRecord){
                    self::assembleLazadaListingInfoPart2($listingInfo, $saasLazadaAutosyncObj, $nowTime);
                }
                $listingInfo['Skus'] = json_encode($skuInfo);
                $lazadaListingObj->setAttributes($listingInfo);
                $lazadaListingObj->save(false);
                if(!$lazadaListingObj->save(false)){
                    self::localLog("updateDeletedStatusDB lazadaListingObj->save false :".print_r($lazadaListingObj->errors,true));
                }
                $updateNum++;
            }
        }
        
        $pdNum = count($products);
        self::localLog("updateDeletedStatusDB end ,product num:$pdNum , update sku num:$updateNum");
        return $updateNum;
    }

    private
    static function getFilterListingAndSave($config, $start_time, $end_time, $saasLazadaAutosync, $isFirstTime, $nowTime)
    {
        self::localLog("getFilterListingAndSave start autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ", start:$start_time,end:$end_time");
        $fetchPeriod = 60 * 60; //秒为单位. 2次后台自动拉取的时间间隔
        $failRetryPeriod = 5 * 60;
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
            //(1)all状态产品拉取： 主要为填上产品信息
            list($allProdSku, $totalNum) = self::doGetListingAndSave($reqParams, $config, $isFirstTime, array(LazadaProductStatus::ALL), $saasLazadaAutosync);
            //如果拉取过程中出现问题,终止执行,主要问题有token出错
            if($allProdSku===false){
                self::handleGetListError($saasLazadaAutosync, $totalNum, $nowTime, $failRetryPeriod);
                return false;
            }
            
            //(2) 非 all的拉取
            // dzt20161221 根据lazada的api对接文档提议，get all获取完信息之后不再 foreach其他状态 获取状态,直接通过getQcStatus 把接口返回的状态填上
            // dzt20170117 从目前来看，deleted 的产品 ，get all还是get 不到的，rejected 则可以
            // dzt20191022 不再拉取deleted产品
            $delTotalNum = 0;
//             list($allProdSku, $delTotalNum) = self::doGetListingAndSave($reqParams, $config, $isFirstTime, array(LazadaProductStatus::DELETED), $saasLazadaAutosync, $allProdSku);
//             if($allProdSku===false){
//                 self::handleGetListError($saasLazadaAutosync, $delTotalNum, $nowTime, $failRetryPeriod);
//                 return false;
//             }
            
            //(3) 所有sku 填上qc status 
            // 回填qc status不影响流程，所以尽管数据有问题，不会设置拉取失败。
            // TODO dzt20191022 拉取deleted的sku会报错。如果要拉deleted产品，则这里要提出deleted的sku再拉取qc
            self::doGetListingQcStatusAndSave($allProdSku,$config,$saasLazadaAutosync);
            
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
            self::localLog("getFilterListingAndSave end autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ",lazadaUid:" . $saasLazadaAutosync->lazada_uid . ",totalNum=".($totalNum+$delTotalNum).",t2_1=" . ($timeMS2 - $timeMS1));
            return $totalNum+$delTotalNum;
        } catch (\Exception $e) {
//             print_r($e);
            $errorMessage = "getFilterListingAndSave error autoSyncId:" . $saasLazadaAutosync->id . ",puid:" . $saasLazadaAutosync->puid . ",Exception:file:" . $e->getFile() . ",line:" . $e->getLine() . ",message:" . $e->getMessage();
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
     * 
     * dzt 2017-01-23 针对lazada新api 返回修改 
     */
    private
    static function prepareInsertData($products, $saasLazadaAutosyncObj, $nowTime)
    {
        $batchInsertArr = array();
        $allProdSku = array();
        $toAddColumns = array();
        
        
        foreach ($products as $pdIndex=>$product) {
            //新接口，新字段
            if(isset($product['skus'])){
                $product['Skus'] = $product['skus'];
                unset($product['skus']);
            }
            
            if(isset($product['attributes'])){
                $product['Attributes'] = $product['attributes'];
                unset($product['attributes']);
            }
            foreach ($product['Skus'] as $skuIndex=>$skuInfo){
                
                if(empty($product['group_id']))// 取产品的其中一个sku
                    $product['group_id'] = $skuInfo['SellerSku'];
                
                $toAddListing = array();
                $listingInfo = $product;
                $sellerSku = $skuInfo["SellerSku"];
                 // dzt20170126 不知道为何，同一批get 回来的有重复，重复的是product 
                if(!empty(self::$PRODUCT_INFO_MAP[$sellerSku])){
                    self::localLog("prepareInsertData sellerSku:$sellerSku already existed.pd-index:$pdIndex,sku-index:$skuIndex , existing-pd-index:".self::$PRODUCT_INFO_MAP[$sellerSku]['index']);
//                     self::localLog(print_r(json_encode($product)));
//                     self::localLog(print_r(json_encode(self::$PRODUCT_INFO_MAP[$sellerSku])));
                    continue;
                }
               
                self::$ALL_SKU[] = $sellerSku;
                self::$PRODUCT_INFO_MAP[$sellerSku] = array('info'=>$product,'index'=>$pdIndex);
                
                $allProdSku[] = $sellerSku;
                self::assembleLazadaListingInfoPart1($skuInfo,$listingInfo,$nowTime);
                self::assembleLazadaListingInfoPart2($listingInfo, $saasLazadaAutosyncObj, $nowTime);
                // dzt20190325 减少保存数据，Attributes 只留下主产品保存
                if($sellerSku != $product['group_id']){
                    $listingInfo['Attributes'] = "";
                }
                
                $listingInfo['Skus'] = json_encode($skuInfo);
                
                $objSchema = LazadaListingV2::getTableSchema();
                foreach ($objSchema->columnNames as $column) {
                    if (isset($listingInfo[$column])) {
                        $toAddListing[$column] = $listingInfo[$column];
                        
                        if(empty($batchInsertArr)){// 抓取第一个批量插入的产品的时候，将要加的columns记下
                            $toAddColumns[] = $column;
                        }
                    }
                }
                
                if (!empty($toAddListing))
                    $batchInsertArr[] = $toAddListing;
            }
        }
        return array($batchInsertArr, $toAddColumns, $allProdSku);
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
            $lazadaListingObj = LazadaListingV2::findOne(["SellerSku" => $sellerSku, "lazada_uid" => $lazadaUid]);
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
    static function updateDeletedOrRejectedPart1($sellerSku, $saasLazadaAutosyncObj, $nowTime, $status)
    {
        $lazadaListingObj = LazadaListingV2::findOne(["SellerSku" => $sellerSku, "lazada_uid" => $saasLazadaAutosyncObj->lazada_uid]);
        // 由于下面$status=="deleted"的setAttributes报错过 ， 所以这里加上。只是不明白为什么会出现报错。如果find 不到，按道理会进入上面的if $notCreate==true条件里面
        if (empty($lazadaListingObj)) {
            $lazadaListingObj = new LazadaListingV2;
        }// dzt20151225 all get 不了deleted产品
        $lazadaListingObj->sub_status = $status;
        return $lazadaListingObj;
    }

    /**
     * 所有listing 需要做这部分
     * @param $listingInfo
     * 
     * dzt 2017-01-23 修改，虽然新表结构不需要这些字段必须有值，但是还是处理一下
     */
    private
    static function assembleLazadaListingInfoPart1(&$skuInfo,&$listingInfo,$nowTime)
    {
        if (empty($skuInfo["special_price"])) $skuInfo["special_price"] = 0;
        if (empty($skuInfo["price"])) $skuInfo["price"] = 0;

        if (!empty($skuInfo["special_from_date"])) {
            $skuInfo["special_from_date"] = TimeUtil::getTimestampFromISO8601($skuInfo["special_from_date"]);
        } else
            $skuInfo["special_from_date"] = 0;

        if (!empty($skuInfo["special_to_date"])) {
            $skuInfo["special_to_date"] = TimeUtil::getTimestampFromISO8601($skuInfo["special_to_date"]);
        } else
            $skuInfo["special_to_date"] = 0;
        if(!isset($listingInfo['Attributes']['name'])){
            self::localLog("assembleLazadaListingInfoPart1 fail".print_r($listingInfo,true),LogType::ERROR);
        }
        $listingInfo['name'] = $listingInfo['Attributes']['name'];
        $listingInfo['SellerSku'] = $skuInfo['SellerSku'];
        $listingInfo['Attributes'] = json_encode($listingInfo['Attributes']);
        $listingInfo['update_time'] = $nowTime;
    }

    /**
     * 新建的listing 需要做这部分
     * @param $lazadaUid
     * @param $listingInfo
     * @param $nowTime
     * @return mixed
     * 
     * dzt 2017-01-23 修改，虽然新表结构不需要这些字段必须有值，但是还是处理一下
     */
    private
    static function assembleLazadaListingInfoPart2(&$listingInfo, $saasLazadaAutosyncObj, $nowTime)
    {
        $listingInfo['create_time'] = $nowTime;
        $listingInfo['platform'] = $saasLazadaAutosyncObj->platform;
        $listingInfo['site'] = $saasLazadaAutosyncObj->site;
        $listingInfo['lazada_uid'] = $saasLazadaAutosyncObj->lazada_uid;
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
        if(!empty($config['version'])){//新授权
//             unset($config['version']);
            $result = LazadaInterface_Helper_V2::getFilterProductsV2($config, $reqParams);
        }else{
//             unset($config['version']);
            $result = LazadaInterface_Helper::getFilterProductsV2($config, $reqParams);
        }
        
        $timeMSTmp2 = TimeUtil::getCurrentTimestampMS();
        self::localLog("end doGetProductsCall and time consume t2_t1=" . ($timeMSTmp2 - $timeMSTmp1));
        return $result;
    }

    /**
     * 获取对get all 返回的sku get Qc 填上
     * @param unknown $allProdSku
     * @param unknown $config
     * @param unknown $saasLazadaAutosync
     */
    private static function doGetListingQcStatusAndSave($allProdSku,$config,$saasLazadaAutosync){
        $length = count($allProdSku);
        $step = 1000;// 接口一次只能100 但Proxy也做了切割
        $timeMS1 = TimeUtil::getCurrentTimestampMS();
        $updateNum = 0;
        for ($i = 0; $i < $length; $i = $i + $step) {
            $timeMS11 = TimeUtil::getCurrentTimestampMS();
            $toUpSubStatusSkusSlice = array_slice($allProdSku, $i, $step);
            if(!empty($config['version'])){
                $qcResponse = LazadaInterface_Helper_V2::getQcStatus($config,array('SkuSellerList'=>$toUpSubStatusSkusSlice));
            }else{
                $qcResponse = LazadaInterface_Helper::getQcStatus($config,array('SkuSellerList'=>$toUpSubStatusSkusSlice));
            }
//             self::localLog("doGetListingQcStatusAndSave qcResponse:".print_r($qcResponse,true));
            $qcStatus = array();
            if ($qcResponse["success"] == true && !empty($qcResponse["response"]) && !empty($qcResponse["response"]["products"])) {
                $qcStatus = $qcResponse["response"]["products"];
                foreach ($qcStatus as $qcStatu){
                    //新授权，新字段
                    if(isset($qcStatu['seller_sku'])){
                        $qcStatu['SellerSKU'] = $qcStatu['seller_sku'];
                        unset($qcStatu['seller_sku']);
                    }
                    if(isset($qcStatu['status'])){
                        $qcStatu['Status'] = $qcStatu['status'];
                        unset($qcStatu['status']);
                    }
                    if(isset($qcStatu['reason'])){
                        $qcStatu['Reason'] = $qcStatu['reason'];
                        unset($qcStatu['reason']);
                    }
                    $pendingSku[] = $qcStatu['SellerSKU'];
                    $lazadaListingObj = LazadaListingV2::findOne(["SellerSku" => $qcStatu['SellerSKU'], "lazada_uid" => $saasLazadaAutosync->lazada_uid]);
                    // dzt20170123 按道理不会进来， 以前回写sub_status时如果为空，依然有产品信息，重新创建产品即可，但现在getQc没有产品信息，这样只能跳过
                    // @todo 虽然按道理不会出现，后面可以看看这个log 是否有打出，然后再考虑要否用sku 来map 产品信息，保存到变量里面，然后这里再读回，然后创建产品。
                    if(empty($lazadaListingObj)){
                        self::localLog("doGetListingQcStatusAndSave writeback qcStatus cannot find sku:".$qcStatu['SellerSKU'].",lazada_uid:".$saasLazadaAutosync->lazada_uid,LogType::ERROR);
                        continue;
                    }
                    
                    $lazadaListingObj->sub_status = $qcStatu['Status'];
                    if($qcStatu['Status'] != 'pending' && !empty($qcStatu['Reason'])){
                        $lazadaListingObj->error_message = $qcStatu["Reason"];
                    }
                    $lazadaListingObj->save(false);
                    $updateNum ++;
                }
            }else{// get 不到qc sub_status只能为空
                self::localLog("doGetListingQcStatusAndSave getQcStatus fail.Index:$i,step:$step,lazada_uid:".$saasLazadaAutosync->lazada_uid.",qcResponse:".print_r($qcResponse,true),LogType::ERROR);
            }
            
            $timeMS12 = TimeUtil::getCurrentTimestampMS();
            self::localLog("doGetListingQcStatusAndSave getQcStatus index:$i,step:$step ==> t12_11=". ($timeMS12 - $timeMS11));
        }
        
        $timeMS2 = TimeUtil::getCurrentTimestampMS();
        self::localLog("doGetListingQcStatusAndSave end , update size is " . $length." ==> t2_1=". ($timeMS2 - $timeMS1));
        
        return array(true,$updateNum);
    }
    
}